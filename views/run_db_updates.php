<?php
// صفحة لتنفيذ تحديثات قاعدة البيانات

// التحقق من الوصول
$allowed_ips = ['::1', '127.0.0.1']; // يسمح فقط للوصول المحلي
if (!in_array($_SERVER['REMOTE_ADDR'], $allowed_ips)) {
    die('غير مسموح بالوصول');
}

// تعريف المتغيرات
$success_messages = [];
$error_messages = [];

// إضافة متضمنات قاعدة البيانات
require_once '../app/db.php';

// إضافة دالة مساعدة لتسجيل الرسائل
function addMessage($message, $isSuccess = true) {
    global $success_messages, $error_messages;
    if ($isSuccess) {
        $success_messages[] = $message;
    } else {
        $error_messages[] = $message;
    }
}

// التحقق من وجود الأعمدة الجديدة
function columnExists($table, $column) {
    global $conn;
    $sql = "SHOW COLUMNS FROM $table LIKE '$column'";
    $result = $conn->query($sql);
    return $result->rowCount() > 0;
}

// تنفيذ التحديثات
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_updates'])) {
    try {
        // إضافة عمود الرقم القومي
        if (!columnExists('customers', 'national_id')) {
            $conn->exec("ALTER TABLE customers ADD COLUMN national_id VARCHAR(20) DEFAULT NULL");
            addMessage("تم إضافة عمود الرقم القومي بنجاح.");
        } else {
            addMessage("عمود الرقم القومي موجود بالفعل.", false);
        }
        
        // إضافة عمود للملاحظات
        if (!columnExists('customers', 'notes')) {
            $conn->exec("ALTER TABLE customers ADD COLUMN notes TEXT DEFAULT NULL");
            addMessage("تم إضافة عمود الملاحظات بنجاح.");
        } else {
            addMessage("عمود الملاحظات موجود بالفعل.", false);
        }
        
        // إضافة عمود لمسار المرفقات
        if (!columnExists('customers', 'attachments')) {
            $conn->exec("ALTER TABLE customers ADD COLUMN attachments VARCHAR(255) DEFAULT NULL");
            addMessage("تم إضافة عمود المرفقات بنجاح.");
        } else {
            addMessage("عمود المرفقات موجود بالفعل.", false);
        }
        
        // إضافة عمود للسعر الأصلي
        if (!columnExists('customers', 'original_price')) {
            $conn->exec("ALTER TABLE customers ADD COLUMN original_price DECIMAL(10,2) DEFAULT 0");
            addMessage("تم إضافة عمود السعر الأصلي بنجاح.");
            
            // تحديث القيم الموجودة
            $conn->exec("UPDATE customers SET original_price = account_price");
            addMessage("تم تحديث قيم السعر الأصلي بناءً على قيمة الحساب الحالية.");
        } else {
            addMessage("عمود السعر الأصلي موجود بالفعل.", false);
        }
        
        // إضافة عمود لنسبة الفائدة
        if (!columnExists('customers', 'interest_rate')) {
            $conn->exec("ALTER TABLE customers ADD COLUMN interest_rate DECIMAL(5,2) DEFAULT 0");
            addMessage("تم إضافة عمود نسبة الفائدة بنجاح.");
        } else {
            addMessage("عمود نسبة الفائدة موجود بالفعل.", false);
        }
        
        // إضافة عمود لمعاد الدفع (فترة السداد)
        if (!columnExists('customers', 'payment_period')) {
            $conn->exec("ALTER TABLE customers ADD COLUMN payment_period ENUM('weekly', 'monthly', 'biannual', 'annual') DEFAULT 'monthly'");
            addMessage("تم إضافة عمود فترة السداد بنجاح.");
        } else {
            addMessage("عمود فترة السداد موجود بالفعل.", false);
        }
        
        // إنشاء مجلد التحميلات إذا لم يكن موجوداً
        $upload_dir = '../uploads/customers/';
        if (!is_dir($upload_dir)) {
            if (mkdir($upload_dir, 0755, true)) {
                addMessage("تم إنشاء مجلد التحميلات بنجاح.");
            } else {
                addMessage("فشل في إنشاء مجلد التحميلات.", false);
            }
        } else {
            addMessage("مجلد التحميلات موجود بالفعل.", false);
        }
        
    } catch (PDOException $e) {
        addMessage("خطأ في قاعدة البيانات: " . $e->getMessage(), false);
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تحديث قاعدة البيانات</title>
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
            --red-light: #fee2e2;
            --shadow-color: rgba(0,0,0,0.1);
        }
        
        body, html {
            background-color: var(--bg-color);
            direction: rtl;
            font-family: 'Cairo', 'Segoe UI', Arial, sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            color: var(--text-color);
        }
        
        .container {
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
        }
        
        .card {
            background-color: var(--card-bg);
            border-radius: 15px;
            box-shadow: 0 4px 15px var(--shadow-color);
            overflow: hidden;
            position: relative;
        }
        
        .card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #36d03b, #2196f3);
        }
        
        .card-header {
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .card-title {
            margin: 0;
            font-size: 1.6em;
            color: var(--text-color);
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card-body {
            padding: 20px;
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
            background: linear-gradient(135deg, #36d03b, #1a9c1f);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(54, 208, 59, 0.3);
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: var(--green-light);
            color: var(--primary-dark);
            border: 1px solid rgba(54, 208, 59, 0.3);
        }
        
        .alert-danger {
            background-color: var(--red-light);
            color: #a12312;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }
        
        .message-title {
            font-weight: 700;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .message-list {
            margin: 0;
            padding-right: 20px;
        }
        
        .message-list li {
            margin-bottom: 5px;
        }
        
        .description {
            margin-bottom: 20px;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1 class="card-title">
                    <i class="fas fa-database"></i>
                    تحديث قاعدة البيانات
                </h1>
            </div>
            <div class="card-body">
                <div class="description">
                    <p>هذه الصفحة تقوم بإجراء التحديثات اللازمة على قاعدة البيانات لدعم الميزات الجديدة:</p>
                    <ul>
                        <li>إضافة حقل الرقم القومي للعملاء</li>
                        <li>إضافة حقل للملاحظات</li>
                        <li>إضافة حقل للمرفقات</li>
                        <li>إضافة حقل للسعر الأصلي</li>
                        <li>إضافة حقل لنسبة الفائدة</li>
                        <li>إضافة حقل لمعاد الدفع (فترة السداد)</li>
                        <li>إنشاء مجلد للمرفقات</li>
                    </ul>
                </div>
                
                <?php if (!empty($success_messages)): ?>
                    <div class="alert alert-success">
                        <div class="message-title">
                            <i class="fas fa-check-circle"></i>
                            تم التحديث بنجاح
                        </div>
                        <ul class="message-list">
                            <?php foreach ($success_messages as $message): ?>
                                <li><?php echo $message; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error_messages)): ?>
                    <div class="alert alert-danger">
                        <div class="message-title">
                            <i class="fas fa-exclamation-circle"></i>
                            تنبيهات
                        </div>
                        <ul class="message-list">
                            <?php foreach ($error_messages as $message): ?>
                                <li><?php echo $message; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <button type="submit" name="run_updates" class="btn btn-primary">
                        <i class="fas fa-sync"></i>
                        تنفيذ التحديثات
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html> 