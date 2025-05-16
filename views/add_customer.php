<?php
include '../app/db.php';

// تعريف متغيرات للرسائل والأخطاء
$success_message = '';
$errors = [];

// تعريف متغيرات للمدخلات
$name = $phone = $email = $address = $national_id = $notes = '';
$original_price = 0;
$interest_rate = 0;
$payment_period = 'monthly';
$attachment_path = '';

// التحقق إذا تم إرسال النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_customer'])) {
    // استلام البيانات من النموذج مع تنظيف المدخلات
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $national_id = trim($_POST['national_id'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $original_price = floatval($_POST['original_price'] ?? 0);
    $interest_rate = floatval($_POST['interest_rate'] ?? 0);
    $payment_period = $_POST['payment_period'] ?? 'monthly';
    
    // معالجة الملف المرفق إذا تم تحميله
    $attachment_path = '';
    if (isset($_FILES['attachment']) && $_FILES['attachment']['size'] > 0) {
        $upload_dir = '../uploads/customers/';
        
        // التأكد من وجود مجلد التحميل
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_name = time() . '_' . basename($_FILES['attachment']['name']);
        $target_file = $upload_dir . $file_name;
        
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
        $file_type = $_FILES['attachment']['type'];
        
        if (in_array($file_type, $allowed_types) && $_FILES['attachment']['size'] < 5000000) { // حد 5 ميجابايت
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $target_file)) {
                $attachment_path = $file_name;
            } else {
                $errors[] = 'حدث خطأ أثناء تحميل الملف';
            }
        } else {
            $errors[] = 'نوع الملف غير مسموح به أو حجم الملف كبير جدًا (الحد الأقصى 5 ميجابايت)';
        }
    }
    
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
    
    if (!empty($national_id) && !preg_match('/^[0-9]{14}$/', $national_id)) {
        $errors[] = 'الرقم القومي يجب أن يتكون من 14 رقم';
    }
    
    if ($original_price < 0) {
        $errors[] = 'السعر الأصلي يجب أن يكون أكبر من أو يساوي صفر';
    }
    
    if ($interest_rate < 0 || $interest_rate > 100) {
        $errors[] = 'نسبة الفائدة يجب أن تكون بين 0 و 100';
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
                // حساب قيمة الحساب مع الفائدة
                $account_price = $original_price;
                if ($interest_rate > 0) {
                    $account_price += ($original_price * $interest_rate / 100);
                }
                
                // إضافة العميل الجديد
                $sql = "INSERT INTO customers (name, phone, email, address, national_id, notes, 
                        attachments, original_price, account_price, interest_rate, payment_period, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                $stmt = $conn->prepare($sql);
                
                if ($stmt->execute([
                    $name, $phone, $email, $address, $national_id, $notes, 
                    $attachment_path, $original_price, $account_price, $interest_rate, $payment_period
                ])) {
                    $customer_id = $conn->lastInsertId();
                    $success_message = 'تم إضافة العميل بنجاح!';
                    
                    // إعادة تعيين النموذج بعد النجاح
                    $name = $phone = $email = $address = $national_id = $notes = '';
                    $original_price = 0;
                    $interest_rate = 0;
                    $payment_period = 'monthly';
                    
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
    <link rel="stylesheet" href="../assets/css/sidebar-custom.css">
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
        
        .form-section-title {
            margin: 25px 0 15px;
            padding-bottom: 10px;
            border-bottom: 1px dashed var(--border-color);
        }
        
        .form-section-title h3 {
            color: var(--primary-color);
            font-size: 1.3em;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
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
                    <form method="POST" action="" class="form-container" enctype="multipart/form-data">
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
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="national_id" class="form-label">الرقم القومي</label>
                                    <input 
                                        type="text" 
                                        id="national_id" 
                                        name="national_id" 
                                        class="form-control" 
                                        placeholder="أدخل الرقم القومي (14 رقم)"
                                        value="<?php echo htmlspecialchars($national_id ?? ''); ?>"
                                        pattern="[0-9]{14}"
                                    >
                                    <span class="field-info">يرجى إدخال 14 رقم</span>
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
                                rows="2"
                            ><?php echo htmlspecialchars($address ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-section-title">
                            <h3><i class="fas fa-money-bill-wave"></i> معلومات القسط</h3>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="original_price" class="form-label">السعر الأصلي</label>
                                    <input 
                                        type="number" 
                                        id="original_price" 
                                        name="original_price" 
                                        class="form-control" 
                                        placeholder="أدخل السعر الأصلي"
                                        value="<?php echo htmlspecialchars($original_price ?? 0); ?>"
                                        min="0" 
                                        step="0.01"
                                    >
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="interest_rate" class="form-label">نسبة الفائدة (%)</label>
                                    <input 
                                        type="number" 
                                        id="interest_rate" 
                                        name="interest_rate" 
                                        class="form-control" 
                                        placeholder="أدخل نسبة الفائدة"
                                        value="<?php echo htmlspecialchars($interest_rate ?? 0); ?>"
                                        min="0" 
                                        max="100" 
                                        step="0.01"
                                    >
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="payment_period" class="form-label">معاد الدفع</label>
                                    <select id="payment_period" name="payment_period" class="form-control">
                                        <option value="weekly" <?php echo $payment_period === 'weekly' ? 'selected' : ''; ?>>أسبوعي</option>
                                        <option value="monthly" <?php echo $payment_period === 'monthly' ? 'selected' : ''; ?>>شهري</option>
                                        <option value="biannual" <?php echo $payment_period === 'biannual' ? 'selected' : ''; ?>>نصف سنوي</option>
                                        <option value="annual" <?php echo $payment_period === 'annual' ? 'selected' : ''; ?>>سنوي</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="total_with_interest" class="form-label">إجمالي المبلغ مع الفائدة</label>
                                    <input 
                                        type="text" 
                                        id="total_with_interest" 
                                        class="form-control" 
                                        readonly
                                        value="0.00"
                                        style="background-color: #f9f9f9;"
                                    >
                                    <span class="field-info">يتم حسابه تلقائياً = السعر الأصلي + (السعر الأصلي × نسبة الفائدة / 100)</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section-title">
                            <h3><i class="fas fa-paperclip"></i> المرفقات والملاحظات</h3>
                        </div>
                        
                        <div class="form-group">
                            <label for="attachment" class="form-label">المرفقات</label>
                            <input 
                                type="file" 
                                id="attachment" 
                                name="attachment" 
                                class="form-control"
                                accept=".jpg,.jpeg,.png,.gif,.pdf"
                            >
                            <span class="field-info">يمكنك إرفاق صورة أو ملف PDF (بحد أقصى 5 ميجابايت)</span>
                        </div>
                        
                        <div class="form-group">
                            <label for="notes" class="form-label">ملاحظات</label>
                            <textarea 
                                id="notes" 
                                name="notes" 
                                class="form-control" 
                                placeholder="أدخل أي ملاحظات إضافية عن العميل" 
                                rows="3"
                            ><?php echo htmlspecialchars($notes ?? ''); ?></textarea>
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
            
            // حساب المبلغ الإجمالي مع الفائدة
            const originalPriceInput = document.getElementById('original_price');
            const interestRateInput = document.getElementById('interest_rate');
            const totalWithInterestInput = document.getElementById('total_with_interest');
            
            function updateTotalWithInterest() {
                const originalPrice = parseFloat(originalPriceInput.value) || 0;
                const interestRate = parseFloat(interestRateInput.value) || 0;
                const totalWithInterest = originalPrice + (originalPrice * interestRate / 100);
                totalWithInterestInput.value = totalWithInterest.toFixed(2);
            }
            
            originalPriceInput.addEventListener('input', updateTotalWithInterest);
            interestRateInput.addEventListener('input', updateTotalWithInterest);
            
            // التحقق من صحة المدخلات
            const nameInput = document.getElementById('name');
            const phoneInput = document.getElementById('phone');
            const emailInput = document.getElementById('email');
            const nationalIdInput = document.getElementById('national_id');
            
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
            
            // تحقق من صيغة الرقم القومي
            nationalIdInput.addEventListener('blur', function() {
                const idRegex = /^[0-9]{14}$/;
                if (this.value.trim() !== '' && !idRegex.test(this.value.trim())) {
                    this.style.borderColor = '#dc3545';
                } else {
                    this.style.borderColor = '';
                }
            });
            
            // تنفيذ الحساب المبدئي
            updateTotalWithInterest();
        });
    </script>
</body>
</html> 