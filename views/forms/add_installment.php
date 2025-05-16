<?php
session_start();
include '../app/db.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// جلب جميع العملاء للاختيار منهم - لم نعد نحتاج لجميع العملاء مرة واحدة
// $customers_sql = "SELECT * FROM customers ORDER BY name ASC";
// $customers_stmt = $conn->prepare($customers_sql);
// $customers_stmt->execute();
// $customers = $customers_stmt->fetchAll(PDO::FETCH_ASSOC);

// للبحث عن العملاء
$search_query = $_GET['search'] ?? '';
$selected_customer = null;

// إذا تم إرسال معرف العميل في عنوان URL، نقوم بجلب معلوماته
if (isset($_GET['customer_id']) && !empty($_GET['customer_id'])) {
    $customer_id = $_GET['customer_id'];
    $customer_sql = "SELECT * FROM customers WHERE id = ?";
    $customer_stmt = $conn->prepare($customer_sql);
    $customer_stmt->execute([$customer_id]);
    $selected_customer = $customer_stmt->fetch(PDO::FETCH_ASSOC);
}

// إذا كان هناك بحث، نجلب نتائج البحث
$search_results = [];
if (!empty($search_query)) {
    $search_sql = "SELECT id, name, phone, account_price FROM customers WHERE name LIKE ? OR phone LIKE ? LIMIT 10";
    $search_stmt = $conn->prepare($search_sql);
    $search_param = '%' . $search_query . '%';
    $search_stmt->execute([$search_param, $search_param]);
    $search_results = $search_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// AJAX للبحث المباشر عن العملاء
if (isset($_GET['ajax_search']) && !empty($_GET['query'])) {
    $query = $_GET['query'];
    $search_sql = "SELECT id, name, phone, account_price FROM customers WHERE name LIKE ? OR phone LIKE ? LIMIT 10";
    $search_stmt = $conn->prepare($search_sql);
    $search_param = '%' . $query . '%';
    $search_stmt->execute([$search_param, $search_param]);
    $search_results = $search_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // إرجاع النتائج بصيغة JSON
    header('Content-Type: application/json');
    echo json_encode($search_results);
    exit;
}

// عند إرسال النموذج - إضافة قسط جديد
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_id = $_POST['customer_id'] ?? null;
    $amount = $_POST['amount'] ?? 0;
    $due_date = $_POST['due_date'] ?? null;
    $interest_rate = $_POST['interest_rate'] ?? 0;
    $notes = $_POST['notes'] ?? '';
    $total_installments = $_POST['total_installments'] ?? 1;
    
    // التحقق من البيانات
    $errors = [];
    
    if (empty($customer_id)) {
        $errors[] = "يرجى اختيار العميل";
    }
    
    if (empty($amount) || !is_numeric($amount) || $amount <= 0) {
        $errors[] = "يرجى إدخال مبلغ صحيح للقسط";
    }
    
    if (empty($due_date)) {
        $errors[] = "يرجى تحديد تاريخ استحقاق القسط";
    }
    
    // إذا لم تكن هناك أخطاء، نضيف القسط/الأقساط
    if (empty($errors)) {
        try {
            // البدء بمعاملة قاعدة البيانات
            $conn->beginTransaction();
            
            // حساب تاريخ القسط الأول
            $first_due_date = new DateTime($due_date);
            
            // إجمالي المبلغ لجميع الأقساط
            $total_amount = $amount * $total_installments;
            
            // جلب حساب العميل الحالي
            $customer_sql = "SELECT account_price FROM customers WHERE id = ?";
            $customer_stmt = $conn->prepare($customer_sql);
            $customer_stmt->execute([$customer_id]);
            $customer = $customer_stmt->fetch(PDO::FETCH_ASSOC);
            
            // التعامل مع حساب العميل
            $is_external_amount = isset($_POST['is_external_amount']) && $_POST['is_external_amount'] == 1;
            
            // إذا كان المبلغ خارجي (مبلغ إضافي)، نقوم بإضافته إلى الحساب
            if ($is_external_amount) {
                $new_account_price = ($customer['account_price'] ?? 0) + $total_amount;
                
                // تحديث حساب العميل بالزيادة
                $update_customer_sql = "UPDATE customers SET account_price = ? WHERE id = ?";
                $update_customer_stmt = $conn->prepare($update_customer_sql);
                $update_customer_stmt->execute([$new_account_price, $customer_id]);
            }
            
            // إضافة الأقساط
            $add_installment_sql = "INSERT INTO installments (customer_id, amount, due_date, interest_rate, notes) VALUES (?, ?, ?, ?, ?)";
            $add_installment_stmt = $conn->prepare($add_installment_sql);
            
            for ($i = 0; $i < $total_installments; $i++) {
                // حساب تاريخ القسط
                if ($i > 0) {
                    // إضافة شهر واحد لكل قسط إضافي
                    $first_due_date->modify('+1 month');
                }
                
                $current_due_date = $first_due_date->format('Y-m-d');
                
                // إضافة القسط
                $add_installment_stmt->execute([
                    $customer_id,
                    $amount,
                    $current_due_date,
                    $interest_rate,
                    $notes
                ]);
            }
            
            // تأكيد المعاملة
            $conn->commit();
            
            // رسالة نجاح
            $success_message = "تم إضافة " . ($total_installments > 1 ? "$total_installments أقساط" : "القسط") . " بنجاح";
            
            // إعادة التوجيه إلى صفحة تفاصيل العميل
            header("Location: installments_detail.php?customer_id=$customer_id&added=success");
            exit();
            
        } catch (PDOException $e) {
            // التراجع عن المعاملة في حالة وجود خطأ
            $conn->rollBack();
            $errors[] = "حدث خطأ أثناء إضافة القسط: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة قسط جديد</title>
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
        
        .form-select {
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
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%237b8a9a' viewBox='0 0 16 16'%3E%3Cpath d='M8 10.5l4.5-4.5H3.5z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: left 15px center;
            padding-left: 40px;
        }
        
        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 15px rgba(54, 208, 59, 0.15);
            outline: none;
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
        
        .tooltip {
            position: relative;
            display: inline-block;
            margin-right: 5px;
            color: var(--text-light);
            cursor: help;
        }
        
        .tooltip .tooltip-text {
            visibility: hidden;
            width: 200px;
            background-color: var(--card-bg);
            color: var(--text-color);
            text-align: center;
            border-radius: 6px;
            padding: 10px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            right: -10px;
            margin-right: -100px;
            opacity: 0;
            transition: opacity 0.3s;
            box-shadow: 0 4px 15px var(--shadow-color);
            font-size: 0.9em;
            border: 1px solid var(--border-color);
        }
        
        .tooltip:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }
        
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 10px;
            }
        }
        
        .checkbox-group {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-weight: 600;
            color: var(--text-color);
        }
        
        .checkbox-label input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--primary-color);
        }
        
        .search-customer-container {
            position: relative;
            margin-bottom: 20px;
        }
        
        .search-customer-input {
            width: 100%;
            height: 45px;
            background-color: var(--bg-color);
            border: 2px solid var(--border-color);
            border-radius: 8px;
            padding: 0 45px 0 15px;
            font-size: 1em;
            color: var(--text-color);
            transition: all 0.3s ease;
            font-family: inherit;
        }
        
        .search-customer-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 15px rgba(54, 208, 59, 0.15);
            outline: none;
        }
        
        .search-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 1.2em;
            transition: all 0.3s ease;
        }
        
        .search-customer-input:focus + .search-icon {
            color: var(--primary-color);
        }
        
        .search-results {
            position: absolute;
            top: 100%;
            right: 0;
            left: 0;
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            box-shadow: 0 4px 15px var(--shadow-color);
            max-height: 300px;
            overflow-y: auto;
            z-index: 10;
            display: none;
        }
        
        .search-results.show {
            display: block;
        }
        
        .search-result-item {
            padding: 12px 15px;
            border-bottom: 1px solid var(--border-color);
            cursor: pointer;
            transition: background-color 0.3s ease;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .search-result-item:hover {
            background-color: var(--bg-color);
        }
        
        .search-result-item:last-child {
            border-bottom: none;
        }
        
        .search-result-info {
            flex: 1;
        }
        
        .search-result-name {
            font-weight: 600;
            color: var(--text-color);
            font-size: 1.1em;
        }
        
        .search-result-phone {
            font-size: 0.9em;
            color: var(--text-light);
            margin-top: 3px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .search-result-account {
            font-size: 0.9em;
            color: var(--primary-color);
            font-weight: 600;
            margin-top: 3px;
        }
        
        .search-result-action {
            padding: 5px 10px;
            background-color: var(--green-light);
            color: var(--primary-color);
            border-radius: 5px;
            font-size: 0.85em;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .search-result-action:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .no-results {
            padding: 15px;
            text-align: center;
            color: var(--text-light);
        }
        
        .search-loading {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            display: none;
        }
        
        .search-loading.show {
            display: block;
        }
        
        @keyframes spin {
            0% { transform: translateY(-50%) rotate(0deg); }
            100% { transform: translateY(-50%) rotate(360deg); }
        }
        
        .search-loading i {
            animation: spin 1s linear infinite;
        }
        
        .selected-customer {
            background-color: var(--bg-color);
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            border: 2px solid var(--primary-color);
        }
        
        .selected-customer-name {
            font-weight: 700;
            font-size: 1.2em;
            margin-bottom: 5px;
            color: var(--text-color);
        }
        
        .selected-customer-info {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .selected-customer-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .selected-customer-label {
            color: var(--text-light);
            font-size: 0.9em;
        }
        
        .selected-customer-value {
            font-weight: 600;
        }
        
        .clear-selection {
            background-color: #f8d7da;
            color: #721c24;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.85em;
            margin-right: 10px;
            transition: all 0.3s ease;
        }
        
        .clear-selection:hover {
            background-color: #dc3545;
            color: white;
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
                    <i class="fas fa-calculator"></i>
                    <a href="installments.php">الأقساط</a>
                </div>
                <div class="breadcrumb-item active">
                    <i class="fas fa-plus"></i>
                    إضافة قسط جديد
                </div>
            </div>
            
            
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-plus-circle"></i>
                    إضافة قسط جديد
                </h1>
                
                <div class="header-actions">
                    <a href="installments.php" class="back-btn">
                        <i class="fas fa-arrow-right"></i>
                        العودة للقائمة
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
            
            <?php if (isset($success_message)): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            
            <div class="card-container">
                <div class="card-header">
                    <h2 class="card-title">بيانات القسط الجديد</h2>
                </div>
                <div class="card-body">
                    <form method="POST" action="" class="form-container" id="installmentForm">
                        <div class="form-group">
                            <label for="customer_search" class="form-label">البحث عن العميل</label>
                            
                            <div class="search-customer-container">
                                <input type="text" id="customer_search" class="search-customer-input" placeholder="ابحث بالاسم أو رقم الهاتف..." autocomplete="off" value="<?php echo htmlspecialchars($search_query); ?>">
                                <i class="fas fa-search search-icon"></i>
                                <div class="search-loading" id="searchLoading">
                                    <i class="fas fa-spinner"></i>
                                </div>
                                
                                <div id="searchResults" class="search-results">
                                    
                                </div>
                            </div>
                            
                            <?php if ($selected_customer): ?>
                            <div class="selected-customer">
                                <div class="selected-customer-name">
                                    <button type="button" class="clear-selection">x</button>
                                    <?php echo htmlspecialchars($selected_customer['name']); ?>
                                </div>
                                <div class="selected-customer-info">
                                    <div class="selected-customer-item">
                                        <span class="selected-customer-label">رقم الهاتف:</span>
                                        <span class="selected-customer-value"><?php echo htmlspecialchars($selected_customer['phone'] ?? 'غير متوفر'); ?></span>
                                    </div>
                                    <?php if (isset($selected_customer['account_price'])): ?>
                                    <div class="selected-customer-item">
                                        <span class="selected-customer-label">الحساب:</span>
                                        <span class="selected-customer-value"><?php echo number_format($selected_customer['account_price'], 2); ?> جنيه</span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <input type="hidden" id="customer_id" name="customer_id" value="<?php echo $selected_customer ? $selected_customer['id'] : ''; ?>">
                            
                            <div style="text-align: left; margin-top: 5px;">
                                <a href="add_customer.php" style="color: var(--secondary-color); font-size: 0.9em;">
                                    <i class="fas fa-plus-circle"></i> إضافة عميل جديد
                                </a>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="amount" class="form-label">
                                        مبلغ القسط
                                        <span class="tooltip">
                                            <i class="fas fa-info-circle"></i>
                                            <span class="tooltip-text">أدخل مبلغ القسط الواحد. في حالة الأقساط المتعددة، سيتم استخدام نفس المبلغ لكل قسط.</span>
                                        </span>
                                    </label>
                                    <input type="number" id="amount" name="amount" class="form-control" min="0" step="0.01" value="<?php echo $_POST['amount'] ?? ''; ?>" required>
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="total_installments" class="form-label">
                                        عدد الأقساط
                                        <span class="tooltip">
                                            <i class="fas fa-info-circle"></i>
                                            <span class="tooltip-text">عدد الأقساط المراد إنشاؤها. سيتم إنشاء أقساط شهرية متتالية بنفس المبلغ.</span>
                                        </span>
                                    </label>
                                    <input type="number" id="total_installments" name="total_installments" class="form-control" min="1" value="<?php echo $_POST['total_installments'] ?? '1'; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <div class="checkbox-group" style="margin-top: 10px;">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="is_external_amount" id="is_external_amount" value="1" <?php echo (isset($_POST['is_external_amount']) && $_POST['is_external_amount'] == 1) ? 'checked' : ''; ?>>
                                    <span>مبلغ إضافي جديد (زيادة قيمة الحساب)</span>
                                </label>
                                <div class="tooltip" style="margin-right: 5px;">
                                    <i class="fas fa-info-circle"></i>
                                    <span class="tooltip-text">حدد هذا الخيار إذا كان هذا مبلغ إضافي يجب زيادته في حساب العميل. تركه فارغًا يعني أن الأقساط هي تقسيم للمبلغ الحالي.</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="due_date" class="form-label">تاريخ الاستحقاق</label>
                                    <input type="date" id="due_date" name="due_date" class="form-control" value="<?php echo $_POST['due_date'] ?? ''; ?>" required>
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="interest_rate" class="form-label">
                                        نسبة الفائدة %
                                        <span class="tooltip">
                                            <i class="fas fa-info-circle"></i>
                                            <span class="tooltip-text">أدخل نسبة الفائدة إن وجدت (اختياري).</span>
                                        </span>
                                    </label>
                                    <input type="number" id="interest_rate" name="interest_rate" class="form-control" min="0" max="100" step="0.01" value="<?php echo $_POST['interest_rate'] ?? '0'; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="notes" class="form-label">ملاحظات</label>
                            <textarea id="notes" name="notes" class="form-control"><?php echo $_POST['notes'] ?? ''; ?></textarea>
                        </div>
                        
                        <div class="form-actions">
                            <a href="installments.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> إلغاء
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus-circle"></i> إضافة القسط
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // إذا لم يكن هناك تاريخ محدد، نضع تاريخ افتراضي (بعد شهر من الآن)
            const dueDateInput = document.getElementById('due_date');
            if (!dueDateInput.value) {
                const today = new Date();
                const nextMonth = new Date(today);
                nextMonth.setMonth(today.getMonth() + 1);
                
                // تنسيق التاريخ للإدخال: YYYY-MM-DD
                const formattedDate = nextMonth.toISOString().split('T')[0];
                dueDateInput.value = formattedDate;
            }
            
            // تبديل وضع الثيم الداكن/الفاتح من خلال localStorage
            const isDarkMode = localStorage.getItem('darkMode') === 'true';
            if (isDarkMode) {
                document.body.classList.add('dark-mode');
            }
            
            // البحث عن العملاء
            const searchInput = document.getElementById('customer_search');
            const searchResults = document.getElementById('searchResults');
            const customerIdInput = document.getElementById('customer_id');
            const installmentForm = document.getElementById('installmentForm');
            const searchLoading = document.getElementById('searchLoading');
            
            // متغير لتخزين مؤقت البحث
            let searchTimeout;
            
            // عرض نتائج البحث عند الكتابة
            searchInput.addEventListener('input', function() {
                const query = this.value.trim();
                
                // إلغاء المؤقت السابق إذا وجد
                if (searchTimeout) {
                    clearTimeout(searchTimeout);
                }
                
                // إظهار أيقونة التحميل
                if (query.length > 0) {
                    searchLoading.classList.add('show');
                } else {
                    searchLoading.classList.remove('show');
                    searchResults.classList.remove('show');
                    return;
                }
                
                // تعيين مؤقت جديد للبحث (300 مللي ثانية)
                searchTimeout = setTimeout(function() {
                    if (query.length > 1) {
                        // إجراء طلب AJAX للبحث
                        fetch(`add_installment.php?ajax_search=1&query=${encodeURIComponent(query)}`)
                            .then(response => response.json())
                            .then(data => {
                                // إخفاء أيقونة التحميل
                                searchLoading.classList.remove('show');
                                
                                // عرض النتائج
                                displaySearchResults(data);
                            })
                            .catch(error => {
                                console.error('Error searching customers:', error);
                                searchLoading.classList.remove('show');
                            });
                    } else {
                        searchLoading.classList.remove('show');
                        searchResults.classList.remove('show');
                    }
                }, 300);
            });
            
            // عرض نتائج البحث عند التركيز
            searchInput.addEventListener('focus', function() {
                if (this.value.trim().length > 1) {
                    searchResults.classList.add('show');
                }
            });
            
            // إخفاء نتائج البحث عند النقر خارجها
            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                    searchResults.classList.remove('show');
                }
            });
            
            // عرض نتائج البحث
            function displaySearchResults(results) {
                if (results.length === 0) {
                    searchResults.innerHTML = `
                        <div class="no-results">
                            <p>لم يتم العثور على نتائج</p>
                            <a href="add_customer.php" style="color: var(--secondary-color);">
                                <i class="fas fa-plus-circle"></i> إضافة عميل جديد
                            </a>
                        </div>
                    `;
                } else {
                    let resultsHTML = '';
                    
                    results.forEach(customer => {
                        resultsHTML += `
                            <div class="search-result-item" data-id="${customer.id}">
                                <div class="search-result-info">
                                    <div class="search-result-name">${customer.name}</div>
                                    <div class="search-result-phone">
                                        <i class="fas fa-phone"></i>
                                        ${customer.phone || 'لا يوجد رقم'}
                                    </div>
                                    ${customer.account_price > 0 ? 
                                        `<div class="search-result-account">الحساب: ${parseFloat(customer.account_price).toLocaleString()} جنيه</div>` : 
                                        ''}
                                </div>
                                <div class="search-result-action">اختيار</div>
                            </div>
                        `;
                    });
                    
                    searchResults.innerHTML = resultsHTML;
                    
                    // إضافة مستمعي أحداث للنتائج
                    document.querySelectorAll('.search-result-item').forEach(item => {
                        item.addEventListener('click', function() {
                            const customerId = this.getAttribute('data-id');
                            
                            // إعادة توجيه الصفحة مع معلمة العميل المختار
                            const currentUrl = new URL(window.location.href);
                            currentUrl.searchParams.set('customer_id', customerId);
                            
                            // إزالة معلمة البحث
                            if (currentUrl.searchParams.has('search')) {
                                currentUrl.searchParams.delete('search');
                            }
                            
                            window.location.href = currentUrl.toString();
                        });
                    });
                }
                
                searchResults.classList.add('show');
            }
            
            // إزالة العميل المختار
            const clearButton = document.querySelector('.clear-selection');
            if (clearButton) {
                clearButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // إعادة توجيه الصفحة بدون معلمات العميل
                    const currentUrl = new URL(window.location.href);
                    
                    if (currentUrl.searchParams.has('customer_id')) {
                        currentUrl.searchParams.delete('customer_id');
                    }
                    
                    window.location.href = currentUrl.toString();
                });
            }
            
            // التحقق من صحة النموذج قبل الإرسال
            installmentForm.addEventListener('submit', function(e) {
                if (!customerIdInput.value) {
                    e.preventDefault();
                    alert('الرجاء اختيار العميل أولاً');
                    return false;
                }
            });
        });
    </script>
</body>
</html> 