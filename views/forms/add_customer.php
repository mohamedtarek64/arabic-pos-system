<?php
include '../app/db.php';

// تعريف متغيرات للرسائل والأخطاء
$success_message = '';
$errors = [];

// التحقق إذا تم إرسال النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_customer'])) {
    // استلام البيانات من النموذج مع تنظيف المدخلات
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    
    // التحقق من صحة البيانات
    if (empty($name)) {
        $errors[] = 'اسم العميل مطلوب';
    } elseif (strlen($name) < 3) {
        $errors[] = 'يجب أن يكون اسم العميل على الأقل 3 أحرف';
    }
    
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'يرجى إدخال بريد إلكتروني صالح';
    }
    
    if (!empty($phone) && !preg_match('/^[0-9+\s()-]{8,15}$/', $phone)) {
        $errors[] = 'يرجى إدخال رقم هاتف صالح (8-15 رقم)';
    }
    
    // إذا لم تكن هناك أخطاء، قم بإضافة العميل
    if (empty($errors)) {
        try {
            // التحقق إذا كان العميل موجوداً بالفعل
            $check_sql = "SELECT COUNT(*) FROM customers WHERE name = ? OR (phone = ? AND phone <> '')";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->execute([$name, $phone]);
            
            if ($check_stmt->fetchColumn() > 0) {
                $errors[] = 'العميل موجود بالفعل. يرجى التحقق من الاسم أو رقم الهاتف.';
            } else {
                // إضافة العميل الجديد
                $sql = "INSERT INTO customers (name, phone, email, address, created_at) VALUES (?, ?, ?, ?, NOW())";
                $stmt = $conn->prepare($sql);
                
                if ($stmt->execute([$name, $phone, $email, $address])) {
                    $customer_id = $conn->lastInsertId();
                    $success_message = 'تم إضافة العميل بنجاح!';
                    
                    // إعادة تعيين النموذج بعد النجاح
                    $name = $phone = $email = $address = '';
                    
                    // إعادة التوجيه إذا تم تحديد الخيار
                    if (isset($_POST['redirect_after_save']) && $_POST['redirect_after_save'] == 'installments') {
                        header("Location: installments_detail.php?customer_id=" . $customer_id);
                        exit();
                    } elseif (isset($_POST['redirect_after_save']) && $_POST['redirect_after_save'] == 'add_installment') {
                        header("Location: add_installment.php?customer_id=" . $customer_id);
                        exit();
                    }
                } else {
                    $errors[] = 'حدث خطأ أثناء إضافة العميل، يرجى المحاولة مرة أخرى.';
                }
            }
        } catch (PDOException $e) {
            $errors[] = 'خطأ في قاعدة البيانات: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة عميل جديد</title>
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
            --button-gradient: linear-gradient(135deg, #36d03b, #1a9c1f);
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
            --button-gradient: linear-gradient(135deg, #4caf50, #388e3c);
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
            color: var(--primary-color);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
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
        
        .card-container {
            background: var(--card-bg);
            border-radius: 15px;
            box-shadow: 0 4px 15px var(--shadow-color);
            margin-bottom: 25px;
            overflow: hidden;
            position: relative;
        }
        
        .card-container::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100%;
            height: 5px;
            background: var(--header-gradient);
        }
        
        .card-header {
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .card-title {
            margin: 0;
            font-size: 1.4em;
            color: var(--text-color);
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .form-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-row {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .form-col {
            flex: 1;
            min-width: 200px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-color);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            background-color: var(--bg-color);
            color: var(--text-color);
            font-family: inherit;
            font-size: 1em;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(54, 208, 59, 0.15);
            outline: none;
        }
        
        .form-control::placeholder {
            color: var(--text-light);
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }
        
        .redirect-options {
            margin-top: 20px;
            padding: 15px;
            background-color: var(--bg-color);
            border-radius: 10px;
        }
        
        .redirect-title {
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--text-color);
        }
        
        .redirect-option {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .redirect-option input {
            margin-left: 10px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 25px;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: inherit;
            font-size: 1em;
            border: none;
        }
        
        .btn-primary {
            background: var(--button-gradient);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(54, 208, 59, 0.3);
        }
        
        .btn-secondary {
            background-color: var(--bg-color);
            color: var(--text-color);
            border: 1px solid var(--border-color);
        }
        
        .btn-secondary:hover {
            background-color: var(--border-color);
        }
        
        .error-list {
            padding-right: 20px;
            margin: 5px 0 0 0;
        }
        
        .error-list li {
            margin-bottom: 5px;
        }
        
        .field-info {
            display: block;
            color: var(--text-light);
            font-size: 0.85em;
            margin-top: 5px;
        }
        
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
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
                    <a href="main.php">الرئيسية</a>
                </div>
                <div class="breadcrumb-item">
                    <i class="fas fa-users"></i>
                    <a href="all_customers.php">العملاء</a>
                </div>
                <div class="breadcrumb-item active">
                    <i class="fas fa-user-plus"></i>
                    إضافة عميل جديد
                </div>
            </div>
            
            
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-user-plus"></i>
                    إضافة عميل جديد
                </h1>
                
                <div class="header-actions">
                    <a href="all_customers.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-right"></i>
                        العودة لقائمة العملاء
                    </a>
                </div>
            </div>
            
            <?php if (!empty($success_message)): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <div>
                        <strong>يرجى تصحيح الأخطاء التالية:</strong>
                        <ul class="error-list">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="card-container">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-address-card"></i>
                        معلومات العميل
                    </h2>
                </div>
                <div class="card-body">
                    <form method="POST" action="" class="form-container">
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="name" class="form-label">اسم العميل</label>
                                    <input 
                                        type="text" 
                                        id="name" 
                                        name="name" 
                                        class="form-control" 
                                        placeholder="أدخل اسم العميل الكامل" 
                                        value="<?php echo htmlspecialchars($name ?? ''); ?>"
                                        required
                                    >
                                    <span class="field-info">* مطلوب</span>
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="phone" class="form-label">رقم الهاتف</label>
                                    <input 
                                        type="tel" 
                                        id="phone" 
                                        name="phone" 
                                        class="form-control" 
                                        placeholder="أدخل رقم الهاتف"
                                        value="<?php echo htmlspecialchars($phone ?? ''); ?>"
                                    >
                                    <span class="field-info">مثال: 01xxxxxxxxx</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="email" class="form-label">البريد الإلكتروني</label>
                                    <input 
                                        type="email" 
                                        id="email" 
                                        name="email" 
                                        class="form-control" 
                                        placeholder="أدخل البريد الإلكتروني"
                                        value="<?php echo htmlspecialchars($email ?? ''); ?>"
                                    >
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="address" class="form-label">العنوان</label>
                            <textarea 
                                id="address" 
                                name="address" 
                                class="form-control" 
                                placeholder="أدخل عنوان العميل" 
                                rows="3"
                            ><?php echo htmlspecialchars($address ?? ''); ?></textarea>
                        </div>
                        
                        <div class="redirect-options">
                            <div class="redirect-title">بعد حفظ العميل:</div>
                            <div class="redirect-option">
                                <input type="radio" id="redirect-none" name="redirect_after_save" value="none" checked>
                                <label for="redirect-none">البقاء في هذه الصفحة</label>
                            </div>
                            <div class="redirect-option">
                                <input type="radio" id="redirect-installments" name="redirect_after_save" value="installments">
                                <label for="redirect-installments">الانتقال إلى صفحة تفاصيل أقساط العميل</label>
                            </div>
                            <div class="redirect-option">
                                <input type="radio" id="redirect-add-installment" name="redirect_after_save" value="add_installment">
                                <label for="redirect-add-installment">الانتقال إلى صفحة إضافة قسط جديد للعميل</label>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <a href="all_customers.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> إلغاء
                            </a>
                            <button type="submit" name="add_customer" class="btn btn-primary">
                                <i class="fas fa-save"></i> حفظ العميل
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // تبديل وضع الثيم الداكن/الفاتح
            const isDarkMode = localStorage.getItem('darkMode') === 'true';
            if (isDarkMode) {
                document.body.classList.add('dark-mode');
            }
            
            // التحقق من صحة المدخلات
            const nameInput = document.getElementById('name');
            const phoneInput = document.getElementById('phone');
            const emailInput = document.getElementById('email');
            
            // تحقق من طول الاسم
            nameInput.addEventListener('blur', function() {
                if (this.value.trim().length < 3 && this.value.trim().length > 0) {
                    this.style.borderColor = '#dc3545';
                } else {
                    this.style.borderColor = '';
                }
            });
            
            // تحقق من صيغة رقم الهاتف
            phoneInput.addEventListener('blur', function() {
                const phoneRegex = /^[0-9+\s()-]{8,15}$/;
                if (this.value.trim() !== '' && !phoneRegex.test(this.value.trim())) {
                    this.style.borderColor = '#dc3545';
                } else {
                    this.style.borderColor = '';
                }
            });
            
            // تحقق من صيغة البريد الإلكتروني
            emailInput.addEventListener('blur', function() {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (this.value.trim() !== '' && !emailRegex.test(this.value.trim())) {
                    this.style.borderColor = '#dc3545';
                } else {
                    this.style.borderColor = '';
                }
            });
        });
    </script>
</body>
</html> 