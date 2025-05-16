<?php
session_start();
include '../app/db.php';

// التحقق من تسجيل الدخول - تعليق هذا الشرط مؤقتاً للاختبار


// جلب الإعدادات الحالية
$settings_sql = "SELECT * FROM settings WHERE category = 'installments' ORDER BY setting_key";
$settings_stmt = $conn->prepare($settings_sql);
$settings_stmt->execute();
$settings_data = $settings_stmt->fetchAll(PDO::FETCH_ASSOC);

// تحويل الإعدادات إلى مصفوفة للاستخدام السهل
$settings = [];
foreach ($settings_data as $setting) {
    $settings[$setting['setting_key']] = $setting['setting_value'];
}

// تعيين القيم الافتراضية إذا لم تكن موجودة
$default_values = [
    'default_interest_rate' => '5',
    'grace_period_days' => '3',
    'late_fee' => '10',
    'enable_sms_reminders' => '0',
    'reminder_days_before' => '3',
    'enable_late_notifications' => '1',
    'max_installments' => '24',
    'currency_symbol' => 'جنيه',
    'default_min_down_payment' => '20'
];

foreach ($default_values as $key => $value) {
    if (!isset($settings[$key])) {
        $settings[$key] = $value;
    }
}

// التحقق من إرسال النموذج
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    try {
        $conn->beginTransaction();
        
        // تحديث الإعدادات
        $update_sql = "INSERT INTO settings (category, setting_key, setting_value) 
                      VALUES ('installments', ?, ?) 
                      ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
        $update_stmt = $conn->prepare($update_sql);
        
        // حفظ إعدادات الفائدة والرسوم
        $update_stmt->execute(['default_interest_rate', $_POST['default_interest_rate'] ?? '5']);
        $update_stmt->execute(['grace_period_days', $_POST['grace_period_days'] ?? '3']);
        $update_stmt->execute(['late_fee', $_POST['late_fee'] ?? '10']);
        $update_stmt->execute(['max_installments', $_POST['max_installments'] ?? '24']);
        $update_stmt->execute(['currency_symbol', $_POST['currency_symbol'] ?? 'جنيه']);
        $update_stmt->execute(['default_min_down_payment', $_POST['default_min_down_payment'] ?? '20']);
        
        // حفظ إعدادات الإشعارات
        $update_stmt->execute(['enable_sms_reminders', isset($_POST['enable_sms_reminders']) ? '1' : '0']);
        $update_stmt->execute(['reminder_days_before', $_POST['reminder_days_before'] ?? '3']);
        $update_stmt->execute(['enable_late_notifications', isset($_POST['enable_late_notifications']) ? '1' : '0']);
        
        $conn->commit();
        $success_message = 'تم حفظ الإعدادات بنجاح';
        
        // إعادة تحميل الإعدادات بعد التحديث
        $settings_stmt->execute();
        $settings_data = $settings_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // تحديث مصفوفة الإعدادات
        foreach ($settings_data as $setting) {
            $settings[$setting['setting_key']] = $setting['setting_value'];
        }
        
    } catch (PDOException $e) {
        $conn->rollBack();
        $error_message = 'حدث خطأ أثناء حفظ الإعدادات: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعدادات الأقساط</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #36d03b;
            --primary-dark: #1a9c1f;
            --secondary-color: #2196f3;
            --secondary-dark: #1976d2;
            --text-color: #1a2b3c;
            --text-light: #7b8a9a;
            --bg-color: #f5f7fa;
            --card-bg: #fff;
            --border-color: #e3eafc;
            --green-light: #e3f7e4;
            --shadow-color: rgba(0,0,0,0.1);
            --header-gradient: linear-gradient(90deg, #36d03b, #2196f3);
            --switch-on: #36d03b;
            --switch-off: #ccc;
        }
        
        .dark-mode {
            --primary-color: #4caf50;
            --primary-dark: #388e3c;
            --secondary-color: #42a5f5;
            --secondary-dark: #1976d2;
            --text-color: #e4e6f1;
            --text-light: #a4abc8;
            --bg-color: #121212;
            --card-bg: #1e1e1e;
            --border-color: #333;
            --green-light: rgba(76, 175, 80, 0.15);
            --shadow-color: rgba(0,0,0,0.3);
            --header-gradient: linear-gradient(90deg, #388e3c, #1976d2);
                --switch-on: #4caf50;
            --switch-off: #555;
        }
        
        body, html {
            background-color: var(--bg-color);
            direction: rtl;
            font-family: 'Cairo', 'Segoe UI', Arial, sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            color: var(--text-color);
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        .layout-wrapper {
            display: flex;
            min-height: 100vh;
            width: 100%;
            position: relative;
        }
        
        .main-content-area {
            flex: 1;
            padding: 20px;
            margin-right: 280px;
            min-height: 100vh;
            z-index: 5;
            position: relative;
            transition: all 0.3s ease;
        }
        
        @media (max-width: 991px) {
            .main-content-area {
                margin-right: 0;
                width: 100%;
            }
        }
        
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            background: var(--card-bg);
            padding: 15px 20px;
            border-radius: 15px;
            box-shadow: 0 4px 15px var(--shadow-color);
        }
        
        .page-title {
            font-size: 1.8em;
            font-weight: 700;
            color: var(--secondary-color);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .settings-container {
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 4px 15px var(--shadow-color);
            margin-bottom: 25px;
            padding: 0;
            position: relative;
            overflow: hidden;
        }
        
        .settings-container::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100%;
            height: 5px;
            background: var(--header-gradient);
        }
        
        .settings-header {
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .settings-title {
            font-size: 1.6em;
            font-weight: 700;
            color: var(--text-color);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .settings-body {
            padding: 20px;
        }
        
        .settings-section {
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 1.3em;
            font-weight: 700;
            color: var(--secondary-color);
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .form-group {
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .form-label {
            flex: 0 0 240px;
            margin-left: 15px;
            font-weight: 600;
            color: var(--text-color);
        }
        
        .form-label .help-text {
            display: block;
            font-size: 0.85em;
            font-weight: normal;
            color: var(--text-light);
            margin-top: 5px;
        }
        
        .form-control {
            flex: 1;
            min-width: 200px;
            max-width: 300px;
            height: 45px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            padding: 0 15px;
            background-color: var(--bg-color);
            color: var(--text-color);
            font-size: 1em;
            transition: all 0.3s ease;
            font-family: inherit;
        }
        
        .form-control:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 2px rgba(33, 150, 243, 0.2);
            outline: none;
        }
        
        .form-control[type="number"] {
            text-align: center;
        }
        
        .success-message {
            background-color: var(--green-light);
            color: var(--primary-color);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        .success-message i {
            margin-left: 10px;
            font-size: 20px;
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        .error-message i {
            margin-left: 10px;
            font-size: 20px;
        }
        
        .switch-container {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }
        
        .switch-container input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .switch {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: var(--switch-off);
            transition: 0.4s;
            border-radius: 34px;
        }
        
        .switch:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: 0.4s;
            border-radius: 50%;
        }
        
        input:checked + .switch {
            background-color: var(--switch-on);
        }
        
        input:checked + .switch:before {
            transform: translateX(26px);
        }
        
        .save-btn {
            background: linear-gradient(135deg, var(--secondary-color), var(--secondary-dark));
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px 24px;
            font-size: 1.1em;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 20px;
        }
        
        .save-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(33, 150, 243, 0.3);
        }
        
        .reset-btn {
            background-color: var(--bg-color);
            color: var(--text-color);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 12px 24px;
            font-size: 1.1em;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 20px;
            margin-right: 10px;
        }
        
        .reset-btn:hover {
            background-color: var(--border-color);
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-start;
            margin-top: 30px;
            border-top: 1px solid var(--border-color);
            padding-top: 20px;
        }
        
        
        @media (max-width: 768px) {
            .form-group {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .form-label {
                flex: 0 0 100%;
                margin-bottom: 8px;
                margin-left: 0;
            }
            
            .form-control {
                width: 100%;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <button class="menu-toggle">☰</button>
    <div class="layout-wrapper">
        <?php include '../app/includes/sidebar.php'; ?>
        
        <div class="main-content-area">
            
            <div class="breadcrumb">
                <div class="breadcrumb-item">
                    <i class="fas fa-home"></i>
                    <a href="../app/main.php">الرئيسية</a>
                </div>
                <div class="breadcrumb-item">
                    <i class="fas fa-calculator"></i>
                    <a href="installments.php">الأقساط</a>
                </div>
                <div class="breadcrumb-item active">
                    <i class="fas fa-cog"></i>
                    إعدادات الأقساط
                </div>
            </div>
            
            
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-cog"></i>
                    إعدادات الأقساط
                </h1>
            </div>
            
            <?php if (!empty($success_message)): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="settings-container">
                    <div class="settings-header">
                        <h2 class="settings-title">
                            <i class="fas fa-sliders-h"></i>
                            الإعدادات العامة للأقساط
                        </h2>
                    </div>
                    
                    <div class="settings-body">
                        
                        <div class="settings-section">
                            <h3 class="section-title">إعدادات الفائدة والرسوم</h3>
                            
                            <div class="form-group">
                                <label class="form-label" for="default_interest_rate">
                                    نسبة الفائدة الافتراضية (%)
                                    <span class="help-text">سيتم استخدام هذه النسبة افتراضيًا عند إضافة أقساط جديدة</span>
                                </label>
                                <input type="number" id="default_interest_rate" name="default_interest_rate" class="form-control" min="0" max="100" step="0.5" value="<?php echo $settings['default_interest_rate']; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="grace_period_days">
                                    فترة السماح (أيام)
                                    <span class="help-text">عدد الأيام المسموح بها بعد تاريخ الاستحقاق قبل اعتبار القسط متأخرًا</span>
                                </label>
                                <input type="number" id="grace_period_days" name="grace_period_days" class="form-control" min="0" max="30" value="<?php echo $settings['grace_period_days']; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="late_fee">
                                    رسوم التأخير (%)
                                    <span class="help-text">نسبة الرسوم الإضافية التي تطبق على الأقساط المتأخرة</span>
                                </label>
                                <input type="number" id="late_fee" name="late_fee" class="form-control" min="0" max="100" step="0.5" value="<?php echo $settings['late_fee']; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="max_installments">
                                    الحد الأقصى لعدد الأقساط
                                    <span class="help-text">أقصى عدد من الأقساط المسموح بها لكل عميل</span>
                                </label>
                                <input type="number" id="max_installments" name="max_installments" class="form-control" min="1" max="120" value="<?php echo $settings['max_installments']; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="default_min_down_payment">
                                    نسبة الدفعة الأولى الافتراضية (%)
                                    <span class="help-text">النسبة المئوية الافتراضية للدفعة الأولى من إجمالي المبلغ</span>
                                </label>
                                <input type="number" id="default_min_down_payment" name="default_min_down_payment" class="form-control" min="0" max="100" step="5" value="<?php echo $settings['default_min_down_payment']; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="currency_symbol">
                                    رمز العملة
                                    <span class="help-text">الرمز الذي سيظهر مع المبالغ المالية في التطبيق</span>
                                </label>
                                <input type="text" id="currency_symbol" name="currency_symbol" class="form-control" value="<?php echo $settings['currency_symbol']; ?>">
                            </div>
                        </div>
                        
                        
                        <div class="settings-section">
                            <h3 class="section-title">إعدادات الإشعارات</h3>
                            
                            <div class="form-group">
                                <label class="form-label" for="enable_sms_reminders">
                                    تفعيل تذكيرات الرسائل القصيرة
                                    <span class="help-text">إرسال رسائل نصية للعملاء لتذكيرهم بمواعيد استحقاق الأقساط</span>
                                </label>
                                <label class="switch-container">
                                    <input type="checkbox" id="enable_sms_reminders" name="enable_sms_reminders" <?php echo $settings['enable_sms_reminders'] == '1' ? 'checked' : ''; ?>>
                                    <span class="switch"></span>
                                </label>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="reminder_days_before">
                                    إرسال التذكير قبل الاستحقاق بـ (أيام)
                                    <span class="help-text">عدد الأيام قبل تاريخ استحقاق القسط لإرسال تذكير</span>
                                </label>
                                <input type="number" id="reminder_days_before" name="reminder_days_before" class="form-control" min="1" max="30" value="<?php echo $settings['reminder_days_before']; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="enable_late_notifications">
                                    تفعيل إشعارات الأقساط المتأخرة
                                    <span class="help-text">إظهار تنبيهات للأقساط المتأخرة في لوحة التحكم</span>
                                </label>
                                <label class="switch-container">
                                    <input type="checkbox" id="enable_late_notifications" name="enable_late_notifications" <?php echo $settings['enable_late_notifications'] == '1' ? 'checked' : ''; ?>>
                                    <span class="switch"></span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="save_settings" class="save-btn">
                                <i class="fas fa-save"></i>
                                حفظ الإعدادات
                            </button>
                            <button type="reset" class="reset-btn">
                                <i class="fas fa-undo"></i>
                                إعادة تعيين
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // تبديل وضع الثيم الداكن/الفاتح
            const isDarkMode = localStorage.getItem('darkMode') === 'true';
            if (isDarkMode) {
                document.body.classList.add('dark-mode');
            }
            
            // التحقق من وجود الجدول في قاعدة البيانات
            function checkSettingsTable() {
                // يمكن إضافة طلب AJAX هنا للتحقق من وجود جدول الإعدادات
                // وإنشائه إذا لم يكن موجودًا
            }
            
            // تطبيق قيود إضافية على المدخلات
            const interestRateInput = document.getElementById('default_interest_rate');
            const lateFeeInput = document.getElementById('late_fee');
            
            function validateNumericInput(input, min, max) {
                input.addEventListener('change', function() {
                    const value = parseFloat(this.value);
                    if (isNaN(value) || value < min) {
                        this.value = min;
                    } else if (value > max) {
                        this.value = max;
                    }
                });
            }
            
            validateNumericInput(interestRateInput, 0, 100);
            validateNumericInput(lateFeeInput, 0, 100);
            
            // استدعاء دالة التحقق عند تحميل الصفحة
            checkSettingsTable();
        });
    </script>
</body>
</html> 