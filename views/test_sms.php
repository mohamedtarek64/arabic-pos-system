<?php
session_start();
include '../app/db.php';
include '../app/sms_service.php';

// تأكد من أن المستخدم مسجل دخول ولديه صلاحيات
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// متغيرات الرسالة
$result = null;
$success = false;
$phone = $_POST['phone'] ?? '';
$message = $_POST['message'] ?? '';
$test_type = $_POST['test_type'] ?? '';
$customer_id = $_POST['customer_id'] ?? '';

// إذا تم إرسال النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_test'])) {
    if (empty($phone)) {
        $error = 'يرجى إدخال رقم هاتف صحيح';
    } else {
        // إرسال رسالة اختبار بناءً على نوع الاختبار المحدد
        switch ($test_type) {
            case 'custom':
                // إرسال رسالة مخصصة
                $result = send_sms($phone, $message);
                break;
                
            case 'reminder':
                // إرسال تذكير بقسط
                $result = send_installment_reminder(0, 'عميل تجريبي', $phone, 1000, date('Y-m-d', strtotime('+3 days')));
                break;
                
            case 'late':
                // إرسال إشعار بقسط متأخر
                $result = send_late_payment_notification(0, 'عميل تجريبي', $phone, 1500, date('Y-m-d', strtotime('-5 days')), 5);
                break;
                
            case 'payment':
                // إرسال تأكيد دفع
                $result = send_payment_confirmation(0, 'عميل تجريبي', $phone, 750, date('Y-m-d'), 2250);
                break;
                
            case 'customer':
                // إرسال رسالة لعميل موجود
                if (empty($customer_id)) {
                    $error = 'يرجى اختيار عميل';
                } else {
                    // جلب بيانات العميل
                    $sql = "SELECT * FROM customers WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([$customer_id]);
                    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($customer) {
                        $result = send_sms($customer['phone'], $message);
                    } else {
                        $error = 'العميل غير موجود';
                    }
                }
                break;
        }
        
        $success = $result['success'] ?? false;
    }
}

// جلب قائمة العملاء للاختيار
$customers_sql = "SELECT id, name, phone FROM customers WHERE phone IS NOT NULL AND phone != '' ORDER BY name LIMIT 100";
$customers_stmt = $conn->prepare($customers_sql);
$customers_stmt->execute();
$customers = $customers_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اختبار إرسال الرسائل النصية</title>
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
        }
        
        body {
            background-color: var(--bg-color);
            font-family: 'Cairo', sans-serif;
            margin: 0;
            padding: 20px;
            direction: rtl;
            color: var(--text-color);
        }
        
        h1 {
            color: var(--primary-color);
            margin-bottom: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: var(--card-bg);
            border-radius: 10px;
            box-shadow: 0 4px 15px var(--shadow-color);
            padding: 20px;
        }
        
        .test-card {
            background-color: var(--bg-color);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            font-size: 16px;
        }
        
        textarea.form-control {
            min-height: 100px;
        }
        
        .radio-group {
            margin-bottom: 15px;
        }
        
        .radio-label {
            margin-right: 15px;
            cursor: pointer;
        }
        
        .btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn:hover {
            background-color: var(--primary-dark);
        }
        
        .result-area {
            margin-top: 20px;
            padding: 15px;
            border-radius: 5px;
        }
        
        .success {
            background-color: var(--green-light);
            color: var(--primary-dark);
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .config-info {
            background-color: #f8f9fa;
            border-left: 4px solid var(--secondary-color);
            padding: 10px 15px;
            margin-bottom: 20px;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: var(--secondary-color);
            text-decoration: none;
            margin-bottom: 20px;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        code {
            background-color: #f5f5f5;
            padding: 2px 5px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="installment_settings.php" class="back-link">
            <i class="fas fa-arrow-right"></i>
            العودة إلى إعدادات الأقساط
        </a>
        
        <h1><i class="fas fa-sms"></i> اختبار إرسال الرسائل النصية</h1>
        
        <div class="config-info">
            <p>
                <strong>حالة خدمة الرسائل:</strong> 
                <?php if ($SMS_CONFIG['test_mode']): ?>
                    <span style="color:orange;"><i class="fas fa-vial"></i> وضع الاختبار (لن يتم إرسال رسائل فعلية)</span>
                <?php else: ?>
                    <span style="color:green;"><i class="fas fa-check-circle"></i> وضع الإنتاج</span>
                <?php endif; ?>
            </p>
            <p>
                <strong>مسار سجل الرسائل:</strong> 
                <code><?php echo htmlspecialchars($SMS_CONFIG['log_file']); ?></code>
            </p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="result-area error">
                <i class="fas fa-times-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($result): ?>
            <div class="result-area <?php echo $success ? 'success' : 'error'; ?>">
                <?php if ($success): ?>
                    <h3><i class="fas fa-check-circle"></i> تم إرسال الرسالة بنجاح</h3>
                    <?php if (isset($result['to'])): ?>
                        <p><strong>إلى:</strong> <?php echo htmlspecialchars($result['to']); ?></p>
                    <?php endif; ?>
                    
                    <?php if (isset($result['content'])): ?>
                        <p><strong>المحتوى:</strong> <?php echo htmlspecialchars($result['content']); ?></p>
                    <?php endif; ?>
                    
                    <p><em>تم تسجيل الرسالة في ملف السجل</em></p>
                <?php else: ?>
                    <h3><i class="fas fa-times-circle"></i> فشل في إرسال الرسالة</h3>
                    <p><strong>الخطأ:</strong> <?php echo htmlspecialchars($result['error'] ?? 'خطأ غير معروف'); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="test-card">
                <h2>نوع الاختبار</h2>
                
                <div class="radio-group">
                    <label class="radio-label">
                        <input type="radio" name="test_type" value="custom" <?php echo $test_type === 'custom' || empty($test_type) ? 'checked' : ''; ?>>
                        رسالة مخصصة
                    </label>
                    
                    <label class="radio-label">
                        <input type="radio" name="test_type" value="reminder" <?php echo $test_type === 'reminder' ? 'checked' : ''; ?>>
                        تذكير بقسط
                    </label>
                    
                    <label class="radio-label">
                        <input type="radio" name="test_type" value="late" <?php echo $test_type === 'late' ? 'checked' : ''; ?>>
                        إشعار تأخير
                    </label>
                    
                    <label class="radio-label">
                        <input type="radio" name="test_type" value="payment" <?php echo $test_type === 'payment' ? 'checked' : ''; ?>>
                        تأكيد دفع
                    </label>
                    
                    <label class="radio-label">
                        <input type="radio" name="test_type" value="customer" <?php echo $test_type === 'customer' ? 'checked' : ''; ?>>
                        عميل محدد
                    </label>
                </div>
                
                <div id="customerSection" style="display: <?php echo $test_type === 'customer' ? 'block' : 'none'; ?>">
                    <div class="form-group">
                        <label class="form-label">اختر العميل</label>
                        <select name="customer_id" class="form-control">
                            <option value="">-- اختر عميل --</option>
                            <?php foreach ($customers as $customer): ?>
                                <option value="<?php echo $customer['id']; ?>" <?php echo $customer_id == $customer['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($customer['name']); ?> (<?php echo htmlspecialchars($customer['phone']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div id="phoneNumberSection" style="display: <?php echo $test_type !== 'customer' ? 'block' : 'none'; ?>">
                    <div class="form-group">
                        <label class="form-label" for="phone">رقم الهاتف</label>
                        <input type="text" name="phone" id="phone" class="form-control" value="<?php echo htmlspecialchars($phone); ?>" placeholder="أدخل رقم الهاتف هنا (مثال: 01068207217)">
                    </div>
                </div>
                
                <div id="messageSection" style="display: <?php echo $test_type === 'custom' || $test_type === 'customer' || empty($test_type) ? 'block' : 'none'; ?>">
                    <div class="form-group">
                        <label class="form-label" for="message">نص الرسالة</label>
                        <textarea name="message" id="message" class="form-control" placeholder="أدخل نص الرسالة هنا"><?php echo htmlspecialchars($message); ?></textarea>
                    </div>
                </div>
                
                <button type="submit" name="send_test" class="btn">
                    <i class="fas fa-paper-plane"></i>
                    إرسال رسالة اختبار
                </button>
            </div>
        </form>
    </div>
    
    <script>
        // تغيير حقول النموذج حسب نوع الاختبار المحدد
        document.querySelectorAll('input[name="test_type"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const messageSection = document.getElementById('messageSection');
                const phoneNumberSection = document.getElementById('phoneNumberSection');
                const customerSection = document.getElementById('customerSection');
                
                if (this.value === 'custom' || this.value === 'customer') {
                    messageSection.style.display = 'block';
                } else {
                    messageSection.style.display = 'none';
                }
                
                if (this.value === 'customer') {
                    customerSection.style.display = 'block';
                    phoneNumberSection.style.display = 'none';
                } else {
                    customerSection.style.display = 'none';
                    phoneNumberSection.style.display = 'block';
                }
            });
        });
    </script>
</body>
</html> 