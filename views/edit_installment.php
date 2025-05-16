<?php
session_start();
include '../app/db.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// التحقق من وجود معرّف القسط ومعرّف العميل
$installment_id = $_GET['id'] ?? null;
$customer_id = $_GET['customer_id'] ?? null;

if (!$installment_id || !$customer_id) {
    echo "معلومات القسط غير كاملة";
    exit();
}

// جلب بيانات القسط
$installment_sql = "SELECT * FROM installments WHERE id = ? AND customer_id = ?";
$installment_stmt = $conn->prepare($installment_sql);
$installment_stmt->execute([$installment_id, $customer_id]);
$installment = $installment_stmt->fetch(PDO::FETCH_ASSOC);

if (!$installment) {
    echo "القسط غير موجود";
    exit();
}

// التحقق من أن القسط غير مدفوع
if ($installment['paid'] == 1) {
    header("Location: installments_detail.php?customer_id=$customer_id&error=paid_installment");
    exit();
}

// جلب بيانات العميل
$customer_sql = "SELECT * FROM customers WHERE id = ?";
$customer_stmt = $conn->prepare($customer_sql);
$customer_stmt->execute([$customer_id]);
$customer = $customer_stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    echo "العميل غير موجود";
    exit();
}

// معالجة النموذج عند الإرسال
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $amount = $_POST['amount'] ?? 0;
    $due_date = $_POST['due_date'] ?? null;
    $interest_rate = $_POST['interest_rate'] ?? 0;
    $notes = $_POST['notes'] ?? '';
    
    // التحقق من البيانات
    $errors = [];
    
    if (empty($amount) || !is_numeric($amount) || $amount <= 0) {
        $errors[] = "يرجى إدخال مبلغ صحيح للقسط";
    }
    
    if (empty($due_date)) {
        $errors[] = "يرجى تحديد تاريخ استحقاق القسط";
    }
    
    // إذا لم تكن هناك أخطاء، نقوم بتحديث القسط
    if (empty($errors)) {
        try {
            // تحديث بيانات القسط
            $update_sql = "UPDATE installments SET amount = ?, due_date = ?, interest_rate = ?, notes = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->execute([$amount, $due_date, $interest_rate, $notes, $installment_id]);
            
            // رسالة نجاح وإعادة التوجيه
            header("Location: installments_detail.php?customer_id=$customer_id&updated=success");
            exit();
        } catch (PDOException $e) {
            $errors[] = "حدث خطأ أثناء تحديث القسط: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل القسط - <?php echo htmlspecialchars($customer['name']); ?></title>
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
        
        .card-container {
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 4px 15px var(--shadow-color);
            margin-bottom: 25px;
            padding: 0;
            position: relative;
            overflow: hidden;
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
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .card-title {
            font-size: 1.6em;
            font-weight: 700;
            color: var(--text-color);
            margin: 0;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .form-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-color);
        }
        
        .form-control {
            width: 100%;
            height: 45px;
            background-color: var(--bg-color);
            border: 2px solid var(--border-color);
            border-radius: 8px;
            padding: 0 15px;
            font-size: 1em;
            color: var(--text-color);
            transition: all 0.3s ease;
            font-family: inherit;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 15px rgba(54, 208, 59, 0.15);
            outline: none;
        }
        
        textarea.form-control {
            height: 100px;
            padding: 10px 15px;
            resize: vertical;
        }
        
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-col {
            flex: 1;
        }
        
        .form-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            font-family: inherit;
            font-size: 1em;
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
        
        .error-list {
            margin: 0;
            padding-right: 20px;
        }
        
        .header-actions {
            display: flex;
            gap: 10px;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background-color: #f1f3f6;
            color: var(--text-color);
            padding: 10px 15px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .back-btn:hover {
            background-color: var(--border-color);
            transform: translateY(-2px);
        }
        
        .customer-info {
            background-color: var(--bg-color);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid var(--border-color);
        }
        
        .customer-name {
            font-weight: 700;
            font-size: 1.2em;
            margin-bottom: 8px;
        }
        
        .customer-details {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .customer-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .customer-label {
            color: var(--text-light);
            font-size: 0.9em;
        }
        
        .customer-value {
            font-weight: 600;
        }
        
        .breadcrumb {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .breadcrumb-item {
            display: flex;
            align-items: center;
            margin-left: 10px;
            color: var(--text-light);
            font-size: 0.95em;
        }
        
        .breadcrumb-item:not(:last-child)::after {
            content: '\f104';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            margin-right: 10px;
            color: var(--text-light);
        }
        
        .breadcrumb-item a {
            color: var(--text-color);
            text-decoration: none;
            transition: color 0.3s ease;
            margin-right: 5px;
        }
        
        .breadcrumb-item a:hover {
            color: var(--primary-color);
        }
        
        .breadcrumb-item.active {
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .breadcrumb-item i {
            margin-left: 5px;
        }
    </style>
</head>
<body>
    <div class="layout-wrapper">
        <?php include '../app/includes/sidebar.php'; ?>
        
        <div class="main-content-area">
            
            <div class="breadcrumb">
                <div class="breadcrumb-item">
                    <i class="fas fa-home"></i>
                    <a href="main.php">الرئيسية</a>
                </div>
                <div class="breadcrumb-item">
                    <i class="fas fa-calculator"></i>
                    <a href="installments.php">الأقساط</a>
                </div>
                <div class="breadcrumb-item">
                    <i class="fas fa-user"></i>
                    <a href="installments_detail.php?customer_id=<?php echo $customer_id; ?>">تفاصيل العميل</a>
                </div>
                <div class="breadcrumb-item active">
                    <i class="fas fa-edit"></i>
                    تعديل القسط
                </div>
            </div>
            
            
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-edit"></i>
                    تعديل القسط
                </h1>
                
                <div class="header-actions">
                    <a href="installments_detail.php?customer_id=<?php echo $customer_id; ?>" class="back-btn">
                        <i class="fas fa-arrow-right"></i>
                        العودة للتفاصيل
                    </a>
                </div>
            </div>
            
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
                    <h2 class="card-title">تعديل بيانات القسط</h2>
                </div>
                <div class="card-body">
                    
                    <div class="customer-info">
                        <div class="customer-name"><?php echo htmlspecialchars($customer['name']); ?></div>
                        <div class="customer-details">
                            <div class="customer-item">
                                <span class="customer-label">رقم الهاتف:</span>
                                <span class="customer-value"><?php echo htmlspecialchars($customer['phone'] ?? 'غير متوفر'); ?></span>
                            </div>
                            <div class="customer-item">
                                <span class="customer-label">الحساب:</span>
                                <span class="customer-value"><?php echo number_format($customer['account_price'], 2); ?> جنيه</span>
                            </div>
                            <div class="customer-item">
                                <span class="customer-label">رقم القسط:</span>
                                <span class="customer-value"><?php echo $installment['id']; ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <form method="POST" action="" class="form-container">
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="amount" class="form-label">مبلغ القسط</label>
                                    <input type="number" id="amount" name="amount" class="form-control" min="0" step="0.01" value="<?php echo $installment['amount']; ?>" required>
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="due_date" class="form-label">تاريخ الاستحقاق</label>
                                    <input type="date" id="due_date" name="due_date" class="form-control" value="<?php echo $installment['due_date']; ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="interest_rate" class="form-label">نسبة الفائدة %</label>
                            <input type="number" id="interest_rate" name="interest_rate" class="form-control" min="0" max="100" step="0.01" value="<?php echo $installment['interest_rate']; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="notes" class="form-label">ملاحظات</label>
                            <textarea id="notes" name="notes" class="form-control"><?php echo htmlspecialchars($installment['notes'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-actions">
                            <a href="installments_detail.php?customer_id=<?php echo $customer_id; ?>" class="btn btn-secondary">
                                <i class="fas fa-times"></i> إلغاء
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> حفظ التعديلات
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // تبديل وضع الثيم الداكن/الفاتح من خلال localStorage
            const isDarkMode = localStorage.getItem('darkMode') === 'true';
            if (isDarkMode) {
                document.body.classList.add('dark-mode');
            }
        });
    </script>
</body>
</html> 