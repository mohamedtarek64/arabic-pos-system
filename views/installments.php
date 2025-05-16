<?php
include '../app/db.php';

// الحصول على جميع الأقساط للعميل - تحسين وظيفة البحث
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['search_customer'])) {
    $customer_input = $_GET['search_customer'];
    $filter_type = $_GET['filter_type'] ?? 'all';

    // التحقق إذا كان المدخل رقم العميل أو اسم العميل
    if (is_numeric($customer_input)) {
        // البحث باستخدام رقم العميل
        $sql = "SELECT * FROM customers WHERE id = ?";
    } else {
        // البحث باستخدام اسم العميل
        $sql = "SELECT * FROM customers WHERE name LIKE ?";
        $customer_input = "%" . $customer_input . "%";  // إضافة النسبة للبحث بالاسم
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute([$customer_input]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($customer) {
        $customer_id = $customer['id'];
        // إعادة التوجيه إلى صفحة تفاصيل الأقساط
        header("Location: installments_detail.php?customer_id=" . $customer_id);
        exit();
    } else {
        $error_message = "العميل غير موجود";
    }
}

// وظيفة البحث الفوري باستخدام AJAX
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['live_search'])) {
    $search_term = $_GET['live_search'];
    $filter_type = $_GET['filter_type'] ?? 'all';
    $date_filter = $_GET['date_filter'] ?? 'all';
    $date_from = $_GET['date_from'] ?? '';
    $date_to = $_GET['date_to'] ?? '';
    
    $results = [];

    if (!empty($search_term)) {
        $search_term = "%{$search_term}%";
        $params = [$search_term, $search_term];

        // إعداد الاستعلام حسب نوع الفلتر
        $sql = "SELECT c.*, COUNT(i.id) as installment_count, SUM(i.amount) as total_amount 
                FROM customers c 
                LEFT JOIN installments i ON c.id = i.customer_id 
                WHERE (c.name LIKE ? OR c.phone LIKE ?) ";
        
        // إضافة شروط الفلترة حسب النوع
        if ($filter_type == 'late') {
            $sql .= "AND i.paid = 0 AND i.due_date < CURDATE() ";
        }
        
        // إضافة شروط التصفية حسب التاريخ
        if ($date_filter == 'this-month') {
            $sql .= "AND i.due_date BETWEEN DATE_FORMAT(NOW(), '%Y-%m-01') AND LAST_DAY(NOW()) ";
        } else if ($date_filter == 'next-month') {
            $sql .= "AND i.due_date BETWEEN DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 1 MONTH), '%Y-%m-01') 
                      AND LAST_DAY(DATE_ADD(NOW(), INTERVAL 1 MONTH)) ";
        } else if ($date_filter == 'custom' && !empty($date_from) && !empty($date_to)) {
            $sql .= "AND i.due_date BETWEEN ? AND ? ";
            $params[] = $date_from;
            $params[] = $date_to;
        }
        
        $sql .= "GROUP BY c.id LIMIT 5";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // إذا كان طلب AJAX سنعيد البيانات بصيغة JSON
    if (isset($_GET['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode($results);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الأقساط</title>
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
        
        
        .breadcrumb {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            background: var(--card-bg);
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px var(--shadow-color);
        }
        
        .breadcrumb-item {
            display: flex;
            align-items: center;
            color: var(--text-light);
            font-size: 0.9em;
        }
        
        .breadcrumb-item:not(:last-child)::after {
            content: '/';
            margin: 0 10px;
            color: var(--text-light);
        }
        
        .breadcrumb-item.active {
            color: var(--primary-color);
            font-weight: 700;
        }
        
        .breadcrumb-item i {
            margin-left: 5px;
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
        
        .page-title i {
            font-size: 0.8em;
            background: var(--primary-color);
            color: white;
            padding: 8px;
            border-radius: 50%;
        }
        
        .theme-switcher {
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--card-bg);
            border-radius: 25px;
            padding: 5px;
            border: 1px solid var(--border-color);
            box-shadow: 0 2px 8px var(--shadow-color);
        }
        
        .theme-option {
            padding: 8px 15px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.9em;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }
        
        .theme-option.active {
            background: var(--primary-color);
            color: white;
        }
        
        .search-container {
            max-width: 100%;
            margin: 0 0 30px 0;
            background: var(--card-bg);
            border-radius: 20px;
            box-shadow: 0 8px 30px var(--shadow-color);
            padding: 30px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .search-container::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100%;
            height: 5px;
            background: var(--header-gradient);
            border-radius: 3px 3px 0 0;
        }
        
        .search-title {
            font-size: 1.6em;
            color: var(--text-color);
            margin-bottom: 5px;
            font-weight: 700;
            text-align: right;
        }
        
        .search-subtitle {
            color: var(--text-light);
            margin-bottom: 25px;
            font-size: 1em;
        }
        
        .search-options {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 25px;
        }
        
        .search-option {
            padding: 10px 20px;
            background: var(--bg-color);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            color: var(--text-light);
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.95em;
            font-weight: 600;
        }
        
        .search-option.active {
            background: var(--green-light);
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .search-option:hover:not(.active) {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px var(--shadow-color);
        }
        
        .search-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .search-input-group {
            position: relative;
            flex: 1;
        }
        
        .search-input {
            width: 100%;
            height: 50px;
            background: var(--bg-color);
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 0 45px 0 20px;
            font-size: 1em;
            color: var(--text-color);
            transition: all 0.3s ease;
        }
        
        .search-input:focus {
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
        
        .search-input:focus + .search-icon {
            color: var(--primary-color);
        }
        
        .search-btn {
            height: 50px;
            border: none;
            background: var(--button-gradient);
            color: white;
            border-radius: 12px;
            padding: 0 25px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Cairo', 'Segoe UI', Arial, sans-serif;
            white-space: nowrap;
        }
        
        .search-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(54, 208, 59, 0.3);
        }
        
        .search-btn i {
            margin-left: 8px;
        }
        
        .recent-searches {
            margin-top: 30px;
            background: var(--bg-color);
            border-radius: 15px;
            padding: 15px 20px;
            transition: all 0.3s ease;
        }
        
        .recent-title {
            font-size: 1em;
            color: var(--text-color);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            font-weight: 600;
        }
        
        .recent-title i {
            margin-left: 8px;
            color: var(--primary-color);
        }
        
        .recent-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .recent-item {
            padding: 8px 15px;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-color);
            font-size: 0.9em;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
        }
        
        .recent-item:hover {
            background: var(--green-light);
            color: var(--primary-color);
            transform: translateY(-2px);
        }
        
        .recent-item::before {
            content: '\f007';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            margin-left: 8px;
            color: var(--text-light);
            transition: color 0.3s;
            font-size: 0.9em;
        }
        
        .error-message {
            background: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            border-right: 3px solid #e74c3c;
        }
        
        .error-message i {
            margin-left: 10px;
            font-size: 1.2em;
        }
        
        .results-container {
            margin-top: 40px;
            background: var(--card-bg);
            border-radius: 20px;
            box-shadow: 0 8px 30px var(--shadow-color);
            padding: 30px;
            transition: all 0.3s ease;
        }
        
        .results-title {
            font-size: 1.4em;
            color: var(--text-color);
            margin-bottom: 20px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .results-title i {
            color: var(--primary-color);
            font-size: 0.9em;
        }
        
        .customers-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 20px;
        }
        
        .customers-table th {
            background: var(--bg-color);
            color: var(--text-color);
            font-weight: 600;
            padding: 15px;
            text-align: right;
            border-bottom: 2px solid var(--border-color);
        }
        
        .customers-table td {
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-color);
        }
        
        .customers-table tr:hover td {
            background: var(--bg-color);
        }
        
        .action-btn {
            padding: 8px 15px;
            border-radius: 8px;
            font-size: 0.9em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
        }
        
        .view-btn {
            background: var(--green-light);
            color: var(--primary-color);
        }
        
        .view-btn:hover {
            background: var(--primary-color);
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 0;
            color: var(--text-light);
        }
        
        .empty-state i {
            font-size: 4em;
            margin-bottom: 20px;
            color: var(--border-color);
        }
        
        .empty-state p {
            font-size: 1.1em;
            margin: 10px 0;
        }
        
        
        .quick-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        
        .quick-action-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 18px;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            color: var(--secondary-color);
            font-weight: 700;
            font-size: 1.1em;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .quick-action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px var(--shadow-color);
            border-color: var(--secondary-color);
            background: var(--secondary-color);
            color: white;
        }
        
        .quick-action-btn i {
            font-size: 1.2em;
        }
        
        @media (max-width: 768px) {
            .search-form {
                flex-direction: column;
            }
            
            .search-btn {
                width: 100%;
                margin-top: 10px;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .theme-switcher {
                align-self: flex-end;
            }
            
            .quick-actions {
                flex-direction: column;
            }
            
            .quick-action-btn {
                width: 100%;
            }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fadeIn {
            animation: fadeIn 0.3s ease forwards;
        }
        
        
        .live-search-results {
            position: absolute;
            top: 100%;
            right: 0;
            width: 100%;
            background: var(--card-bg);
            border-radius: 10px;
            box-shadow: 0 4px 15px var(--shadow-color);
            z-index: 100;
            max-height: 300px;
            overflow-y: auto;
            display: none;
            margin-top: 5px;
        }
        
        .result-item {
            padding: 12px 15px;
            border-bottom: 1px solid var(--border-color);
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .result-item:last-child {
            border-bottom: none;
        }
        
        .result-item:hover {
            background-color: var(--bg-color);
        }
        
        .customer-name {
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 5px;
        }
        
        .customer-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--text-light);
            font-size: 0.9em;
        }
        
        .customer-phone {
            display: flex;
            align-items: center;
        }
        
        .customer-phone i {
            margin-left: 5px;
        }
        
        .result-actions {
            display: flex;
            gap: 5px;
            margin-top: 8px;
        }
        
        .result-action-btn {
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.75em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .view-details-btn {
            background-color: var(--green-light);
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }
        
        .view-details-btn:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .pay-installment-btn {
            background-color: #e3f2fd;
            color: var(--secondary-color);
            border: 1px solid var(--secondary-color);
        }
        
        .pay-installment-btn:hover {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .filter-options {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
            background: var(--bg-color);
            padding: 15px;
            border-radius: 10px;
        }
        
        .filter-label {
            font-weight: 600;
            color: var(--text-color);
            margin-left: 10px;
        }
        
        .filter-option {
            display: flex;
            align-items: center;
        }
        
        .filter-option input {
            margin-left: 5px;
        }
        
        .no-results {
            padding: 20px;
            text-align: center;
            color: var(--text-light);
        }
        
        .result-count {
            color: var(--primary-color);
            font-weight: 700;
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
                    <i class="fas fa-search"></i>
                    بحث الأقساط
                </div>
            </div>
            
            
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-calculator"></i>
                    إدارة الأقساط
                </h1>
                
                <div class="theme-switcher">
                    <div class="theme-option light-option active" id="light-mode">
                        <i class="fas fa-sun"></i>
                        <span>فاتح</span>
                    </div>
                    <div class="theme-option dark-option" id="dark-mode">
                        <i class="fas fa-moon"></i>
                        <span>داكن</span>
                    </div>
                </div>
            </div>
            
            
            <div class="quick-actions">
                <a href="add_installment.php" class="quick-action-btn">
                    <i class="fas fa-plus-circle"></i>
                    إضافة قسط جديد
                </a>
                <a href="late_installments.php" class="quick-action-btn">
                    <i class="fas fa-clock"></i>
                    الأقساط المتأخرة
                </a>
                <a href="installment_reports.php" class="quick-action-btn">
                    <i class="fas fa-file-invoice-dollar"></i>
                    تقارير الأقساط
                </a>
                <a href="all_customers.php" class="quick-action-btn">
                    <i class="fas fa-users"></i>
                    جميع العملاء
                </a>
                <a href="installment_settings.php" class="quick-action-btn">
                    <i class="fas fa-cog"></i>
                    إعدادات الأقساط
                </a>
            </div>
            
            
            <div class="search-container fadeIn">
                <?php if (isset($error_message)): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <h2 class="search-title">البحث عن عميل</h2>
                <p class="search-subtitle">ابحث عن العميل بالاسم أو الرقم لعرض تفاصيل الأقساط</p>
                
                <div class="search-options">
                    <div class="search-option active" id="filter-all" data-filter="all">جميع العملاء</div>
                    <div class="search-option" id="filter-name" data-filter="name">البحث بالاسم</div>
                    <div class="search-option" id="filter-id" data-filter="id">البحث بالرقم</div>
                    <div class="search-option" id="filter-late" data-filter="late">الأقساط المتأخرة</div>
                </div>
                
                
                <form method="GET" action="installments.php" class="search-form" id="searchForm">
                    <div class="search-input-group" style="position: relative;">
                        <input 
                            type="text" 
                            id="search_customer" 
                            name="search_customer" 
                            class="search-input" 
                            placeholder="اكتب اسم العميل أو رقمه..." 
                            autocomplete="off"
                        >
                        <i class="fas fa-search search-icon"></i>
                        
                        
                        <div class="live-search-results" id="liveSearchResults"></div>
                    </div>
                    
                    <input type="hidden" name="filter_type" id="filter_type" value="all">
                    
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search-dollar"></i> بحث
                    </button>
</form>
                
                
                <div class="filter-options">
                    <span class="filter-label">فلترة إضافية:</span>
                    <div class="filter-option">
                        <input type="checkbox" id="filter-active" value="active">
                        <label for="filter-active">عملاء نشطون فقط</label>
                    </div>
                    <div class="filter-option">
                        <input type="checkbox" id="filter-has-installments" value="has-installments">
                        <label for="filter-has-installments">لديهم أقساط فقط</label>
                    </div>
                    <div class="filter-option">
                        <input type="checkbox" id="filter-this-month" value="this-month">
                        <label for="filter-this-month">أقساط الشهر الحالي</label>
                    </div>
                </div>
                
                
                <div class="filter-options">
                    <span class="filter-label">التصفية حسب التاريخ:</span>
                    <div class="filter-option">
                        <input type="radio" name="date_filter" id="filter-all-dates" value="all" checked>
                        <label for="filter-all-dates">جميع التواريخ</label>
                    </div>
                    <div class="filter-option">
                        <input type="radio" name="date_filter" id="filter-this-month" value="this-month">
                        <label for="filter-this-month">الشهر الحالي</label>
                    </div>
                    <div class="filter-option">
                        <input type="radio" name="date_filter" id="filter-next-month" value="next-month">
                        <label for="filter-next-month">الشهر القادم</label>
                    </div>
                    <div class="filter-option">
                        <input type="radio" name="date_filter" id="filter-custom" value="custom">
                        <label for="filter-custom">تاريخ محدد</label>
                    </div>
                </div>
                
                
                <div id="custom-date-fields" class="custom-date-fields" style="display: none; margin-top: 10px;">
                    <div style="display: flex; gap: 10px;">
                        <div style="flex: 1;">
                            <label for="date-from">من:</label>
                            <input type="date" id="date-from" name="date_from" class="form-control">
                        </div>
                        <div style="flex: 1;">
                            <label for="date-to">إلى:</label>
                            <input type="date" id="date-to" name="date_to" class="form-control">
                        </div>
                        <div style="display: flex; align-items: flex-end;">
                            <button type="button" id="apply-date-filter" class="search-btn" style="height: 40px;">
                                <i class="fas fa-filter"></i> تطبيق
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="recent-searches">
                    <div class="recent-title">
                        <i class="fas fa-history"></i>
                        عمليات البحث الأخيرة
                    </div>
                    <div class="recent-list" id="recentSearches">
                        
                    </div>
                </div>
            </div>
            
            
            <div class="results-container" id="resultsContainer">
                <h2 class="results-title">
                    <i class="fas fa-users"></i>
                    نتائج البحث <span class="result-count" id="resultCount"></span>
                </h2>
                
                <table class="customers-table">
                    <thead>
                        <tr>
                            <th>رقم العميل</th>
                            <th>اسم العميل</th>
                            <th>رقم الهاتف</th>
                            <th>عدد الأقساط</th>
                            <th>إجمالي المبلغ</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody id="searchResults">
                        
                    </tbody>
                </table>
                
                
                <div class="empty-state" id="noResultsMessage" style="display: none;">
                    <i class="far fa-folder-open"></i>
                    <p>لم يتم العثور على أي عملاء مطابقين.</p>
                    <p>يرجى تعديل معايير البحث أو <a href="add_customer.php">إضافة عميل جديد</a>.</p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // استدعاء العناصر من DOM
            const searchInput = document.getElementById('search_customer');
            const liveSearchResults = document.getElementById('liveSearchResults');
            const filterOptions = document.querySelectorAll('.search-option');
            const filterTypeInput = document.getElementById('filter_type');
            const searchForm = document.getElementById('searchForm');
            const resultsContainer = document.getElementById('resultsContainer');
            const searchResults = document.getElementById('searchResults');
            const noResultsMessage = document.getElementById('noResultsMessage');
            const resultCount = document.getElementById('resultCount');
            const recentSearches = document.getElementById('recentSearches');
            
            // استرجاع عمليات البحث الأخيرة من localStorage
            loadRecentSearches();
            
            // إضافة مستمعي الأحداث
            searchInput.addEventListener('input', debounce(performLiveSearch, 300));
            searchInput.addEventListener('focus', function() {
                if (this.value.length > 1) {
                    performLiveSearch();
                }
            });
            
            // إخفاء نتائج البحث عند النقر خارجها
            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !liveSearchResults.contains(e.target)) {
                    liveSearchResults.style.display = 'none';
                }
            });
            
            // التبديل بين خيارات البحث
            filterOptions.forEach(option => {
                option.addEventListener('click', function() {
                    filterOptions.forEach(opt => opt.classList.remove('active'));
                    this.classList.add('active');
                    
                    const filterType = this.getAttribute('data-filter');
                    filterTypeInput.value = filterType;
                    
                    // تغيير نص العنصر النائب للبحث
                    switch(filterType) {
                        case 'name':
                            searchInput.placeholder = "اكتب اسم العميل...";
                            break;
                        case 'id':
                            searchInput.placeholder = "اكتب رقم العميل...";
                            break;
                        case 'late':
                            searchInput.placeholder = "البحث في الأقساط المتأخرة...";
                            break;
                        default:
                            searchInput.placeholder = "اكتب اسم العميل أو رقمه...";
                    }
                    
                    // تنفيذ البحث الفوري إذا كان هناك نص بالفعل
                    if (searchInput.value.length > 1) {
                        performLiveSearch();
                    }
                    
                    // التركيز على حقل البحث
                    searchInput.focus();
                });
            });
            
            // تقديم نموذج البحث
            searchForm.addEventListener('submit', function(e) {
                // حفظ البحث في عمليات البحث الأخيرة
                if (searchInput.value.trim() !== '') {
                    saveRecentSearch(searchInput.value.trim());
                }
            });
            
            // وظيفة البحث الفوري
            function performLiveSearch() {
                const searchTerm = searchInput.value.trim();
                const filterType = filterTypeInput.value;
                const dateFilter = document.querySelector('input[name="date_filter"]:checked').value;
                const dateFrom = dateFromInput.value;
                const dateTo = dateToInput.value;
                
                if (searchTerm.length < 2) {
                    liveSearchResults.style.display = 'none';
                    return;
                }
                
                // استدعاء AJAX للبحث مع معلمات التصفية
                fetch(`installments.php?live_search=${searchTerm}&filter_type=${filterType}&date_filter=${dateFilter}&date_from=${dateFrom}&date_to=${dateTo}&ajax=1`)
                    .then(response => response.json())
                    .then(data => {
                        // عرض النتائج
                        displayLiveSearchResults(data);
                    })
                    .catch(error => {
                        console.error('Error performing live search:', error);
                    });
            }
            
            // عرض نتائج البحث الفوري
            function displayLiveSearchResults(results) {
                if (results.length === 0) {
                    liveSearchResults.innerHTML = `
                        <div class="no-results">
                            <p>لم يتم العثور على نتائج</p>
                        </div>
                    `;
                } else {
                    let resultsHTML = '';
                    
                    results.forEach(customer => {
                        resultsHTML += `
                            <div class="result-item" data-id="${customer.id}">
                                <div class="customer-name">${customer.name}</div>
                                <div class="customer-info">
                                    <div class="customer-phone">
                                        <i class="fas fa-phone"></i>
                                        ${customer.phone || 'لا يوجد رقم'}
                                    </div>
                                    <div>
                                        <span>${customer.installment_count || 0} أقساط</span>
                                    </div>
                                </div>
                                <div class="result-actions">
                                    <button class="result-action-btn view-details-btn" onclick="viewCustomerDetails(${customer.id})">
                                        <i class="fas fa-eye"></i> عرض التفاصيل
                                    </button>
                                    <button class="result-action-btn pay-installment-btn" onclick="payLastInstallment(${customer.id})">
                                        <i class="fas fa-money-bill-wave"></i> تسديد آخر قسط
                                    </button>
                                </div>
                            </div>
                        `;
                    });
                    
                    liveSearchResults.innerHTML = resultsHTML;
                }
                
                liveSearchResults.style.display = 'block';
            }
            
            // حفظ عملية البحث في عمليات البحث الأخيرة
            function saveRecentSearch(searchTerm) {
                let recentSearches = JSON.parse(localStorage.getItem('recentSearches') || '[]');
                
                // التحقق إذا كان البحث موجود بالفعل
                const existingIndex = recentSearches.indexOf(searchTerm);
                if (existingIndex !== -1) {
                    // إزالة البحث الموجود
                    recentSearches.splice(existingIndex, 1);
                }
                
                // إضافة البحث الجديد في البداية
                recentSearches.unshift(searchTerm);
                
                // الاحتفاظ بأحدث 5 عمليات بحث فقط
                recentSearches = recentSearches.slice(0, 5);
                
                // حفظ في localStorage
                localStorage.setItem('recentSearches', JSON.stringify(recentSearches));
                
                // تحديث واجهة المستخدم
                loadRecentSearches();
            }
            
            // تحميل عمليات البحث الأخيرة من localStorage
            function loadRecentSearches() {
                const recentSearches = JSON.parse(localStorage.getItem('recentSearches') || '[]');
                
                if (recentSearches.length === 0) {
                    document.querySelector('.recent-searches').style.display = 'none';
                    return;
                }
                
                let recentSearchesHTML = '';
                recentSearches.forEach(search => {
                    recentSearchesHTML += `
                        <div class="recent-item" onclick="searchWithTerm('${search}')">${search}</div>
                    `;
                });
                
                document.getElementById('recentSearches').innerHTML = recentSearchesHTML;
                document.querySelector('.recent-searches').style.display = 'block';
            }
            
            // وظيفة debounce لتأخير استدعاء البحث الفوري
            function debounce(func, delay) {
                let timeout;
                return function() {
                    const context = this;
                    const args = arguments;
                    clearTimeout(timeout);
                    timeout = setTimeout(() => func.apply(context, args), delay);
                };
            }
        });
        
        // وظائف عامة
        function viewCustomerDetails(customerId) {
            // الانتقال إلى صفحة تفاصيل العميل
            window.location.href = `installments_detail.php?customer_id=${customerId}`;
        }
        
        function payLastInstallment(customerId) {
            // سننتقل إلى صفحة تفاصيل العميل مع تمرير علم لتنفيذ عملية دفع آخر قسط
            window.location.href = `installments_detail.php?customer_id=${customerId}&action=pay_last`;
        }
        
        function searchWithTerm(term) {
            // تعيين قيمة البحث والتقديم التلقائي للنموذج
            document.getElementById('search_customer').value = term;
            document.getElementById('searchForm').submit();
        }

        // التعامل مع تصفية التاريخ
        const dateFilterOptions = document.querySelectorAll('input[name="date_filter"]');
        const customDateFields = document.getElementById('custom-date-fields');
        const applyDateFilterBtn = document.getElementById('apply-date-filter');
        const dateFromInput = document.getElementById('date-from');
        const dateToInput = document.getElementById('date-to');

        // تعيين التواريخ الافتراضية
        setDefaultDates();

        // مستمع حدث لخيارات تصفية التاريخ
        dateFilterOptions.forEach(option => {
            option.addEventListener('change', function() {
                if (this.value === 'custom' && this.checked) {
                    customDateFields.style.display = 'block';
                } else {
                    customDateFields.style.display = 'none';
                    
                    // تطبيق التصفية مباشرة للخيارات الأخرى
                    if (this.checked && searchInput.value.length > 1) {
                        performLiveSearch();
                    }
                }
            });
        });

        // مستمع حدث لزر تطبيق التصفية
        applyDateFilterBtn.addEventListener('click', function() {
            if (searchInput.value.length > 1) {
                performLiveSearch();
            }
        });

        // تعيين التواريخ الافتراضية
        function setDefaultDates() {
            // تعيين تاريخ "من" إلى بداية الشهر الحالي
            const today = new Date();
            const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
            dateFromInput.valueAsDate = firstDayOfMonth;
            
            // تعيين تاريخ "إلى" إلى اليوم الحالي
            dateToInput.valueAsDate = today;
        }
    </script>
</body>
</html>
