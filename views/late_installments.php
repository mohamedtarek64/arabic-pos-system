<?php
session_start();
include '../app/db.php';
include '../app/sms_service.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// التحقق من وجود معلمة 'send_sms' في الطلب، للاستخدام مع AJAX
if (isset($_GET['send_sms']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // استلام البيانات
    $data = json_decode(file_get_contents('php://input'), true);
    $customer_id = $data['customer_id'] ?? 0;
    $customer_name = $data['customer_name'] ?? '';
    $customer_phone = $data['customer_phone'] ?? '';
    $amount = $data['amount'] ?? 0;
    $due_date = $data['due_date'] ?? '';
    $days_late = $data['days_late'] ?? 0;
    
    // استخراج مزيد من بيانات العميل إذا لزم الأمر
    if (empty($customer_phone) && !empty($customer_id)) {
        $sql = "SELECT phone FROM customers WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$customer_id]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        $customer_phone = $customer['phone'] ?? '';
    }
    
    // إرسال رسالة التذكير
    $result = send_late_payment_notification(
        $customer_id, 
        $customer_name, 
        $customer_phone, 
        $amount, 
        $due_date, 
        $days_late
    );
    
    // إرجاع النتيجة
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

// تحديد التاريخ الحالي
$current_date = date('Y-m-d');

// جلب الأقساط المتأخرة (تاريخ الاستحقاق أقل من التاريخ الحالي وغير مدفوعة)
$sql = "SELECT i.*, c.name as customer_name, c.phone as customer_phone, c.account_price
        FROM installments i
        JOIN customers c ON i.customer_id = c.id
        WHERE i.due_date < ? AND i.paid = 0
        ORDER BY i.due_date ASC";

$stmt = $conn->prepare($sql);
$stmt->execute([$current_date]);
$late_installments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// تنفيذ بعض الإحصائيات
$total_late_amount = 0;
$customers_count = [];

foreach ($late_installments as $installment) {
    $total_late_amount += $installment['amount'];
    if (!isset($customers_count[$installment['customer_id']])) {
        $customers_count[$installment['customer_id']] = 1;
    } else {
        $customers_count[$installment['customer_id']]++;
    }
}

$unique_customers_count = count($customers_count);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الأقساط المتأخرة</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/sidebar-custom.css">
    <link rel="stylesheet" href="../assets/css/horizontal-nav.css">
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
            --danger-color: #dc3545;
            --danger-light: #f8d7da;
            --warning-color: #ffc107;
            --warning-light: #fff3cd;
            --header-gradient: linear-gradient(90deg, #36d03b, #2196f3);
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
            --danger-color: #f44336;
            --danger-light: rgba(244, 67, 54, 0.15);
            --warning-color: #ffeb3b;
            --warning-light: rgba(255, 235, 59, 0.15);
            --shadow-color: rgba(0,0,0,0.3);
            --header-gradient: linear-gradient(90deg, #388e3c, #1976d2);
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
            color: var(--danger-color);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 4px 15px var(--shadow-color);
            padding: 20px;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100%;
            height: 5px;
            background: var(--danger-color);
        }
        
        .stat-label {
            font-size: 1em;
            color: var(--text-light);
            margin-bottom: 10px;
        }
        
        .stat-value {
            font-size: 2em;
            font-weight: 700;
            color: var(--danger-color);
        }
        
        .installments-list {
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 4px 15px var(--shadow-color);
            margin-bottom: 25px;
            padding: 0;
            position: relative;
            overflow: hidden;
        }
        
        .installments-list::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100%;
            height: 5px;
            background: var(--danger-color);
        }
        
        .list-header {
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .list-title {
            font-size: 1.6em;
            font-weight: 700;
            color: var(--text-color);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .list-actions {
            display: flex;
            gap: 10px;
        }
        
        .filter-container {
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            background-color: var(--bg-color);
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        
        .filter-label {
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 0.9em;
            color: var(--text-color);
        }
        
        .filter-input {
            padding: 10px 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background-color: var(--card-bg);
            color: var(--text-color);
        }
        
        .filter-input:focus {
            border-color: var(--danger-color);
            outline: none;
        }
        
        .installments-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .installments-table th {
            background: var(--bg-color);
            color: var(--text-color);
            font-weight: 600;
            padding: 15px;
            text-align: right;
            border-bottom: 2px solid var(--border-color);
        }
        
        .installments-table td {
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-color);
            transition: all 0.3s ease;
        }
        
        .installments-table tr {
            background-color: var(--card-bg);
            transition: all 0.2s ease;
        }
        
        .installments-table tr:hover {
            background-color: var(--bg-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px var(--shadow-color);
        }
        
        .delay-days {
            color: var(--danger-color);
            font-weight: 700;
        }
        
        .action-btn {
            border: none;
            border-radius: 6px;
            padding: 8px 12px;
            font-weight: 600;
            font-size: 0.85em;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            margin-right: 5px;
        }
        
        .pay-btn {
            background-color: var(--green-light);
            color: var(--primary-color);
        }
        
        .pay-btn:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .edit-btn {
            background-color: #e3f2fd;
            color: var(--secondary-color);
        }
        
        .edit-btn:hover {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .view-btn {
            background-color: var(--bg-color);
            color: var(--text-color);
        }
        
        .view-btn:hover {
            background-color: var(--border-color);
        }
        
        .remind-btn {
            background-color: var(--warning-light);
            color: var(--warning-color);
        }
        
        .remind-btn:hover {
            background-color: var(--warning-color);
            color: white;
        }
        
        .empty-state {
            padding: 40px 20px;
            text-align: center;
            color: var(--text-light);
        }
        
        .empty-state i {
            font-size: 4em;
            margin-bottom: 15px;
            opacity: 0.3;
        }
        
        .empty-state-title {
            font-size: 1.5em;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .empty-state-text {
            font-size: 1.1em;
            max-width: 500px;
            margin: 0 auto;
        }
        
        .export-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: var(--danger-light);
            color: var(--danger-color);
            padding: 10px 15px;
            border-radius: 8px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .export-btn:hover {
            background-color: var(--danger-color);
            color: white;
        }
        
        .customer-link {
            color: var(--secondary-color);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .customer-link:hover {
            color: var(--secondary-dark);
            text-decoration: underline;
        }
        
        
        @media (max-width: 768px) {
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .filter-container {
                grid-template-columns: 1fr;
            }
            
            .installments-table {
                display: block;
                overflow-x: auto;
            }
            
            .breadcrumb {
                flex-direction: row;
                flex-wrap: wrap;
                gap: 5px;
                padding: 10px;
            }
            
            .breadcrumb-item {
                padding: 5px 10px;
                border: none;
            }
            
            .breadcrumb-item:not(:last-child) {
                border-left: none;
                position: relative;
            }
            
            .breadcrumb-item:not(:last-child)::after {
                content: '/';
                position: absolute;
                left: -5px;
                color: var(--text-light);
            }
        }
        
        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .breadcrumb {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            background-color: var(--card-bg);
            border-radius: 12px;
            padding: 12px 15px;
            box-shadow: 0 2px 8px var(--shadow-color);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            animation: slideIn 0.5s ease-out forwards;
        }
        
        .breadcrumb:hover {
            box-shadow: 0 4px 12px var(--shadow-color);
        }
        
        .breadcrumb::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 5px;
            height: 100%;
            background: var(--header-gradient);
        }
        
        .breadcrumb-item {
            display: flex;
            align-items: center;
            position: relative;
            padding: 0 15px;
            animation: fadeIn 0.5s ease-out forwards;
            opacity: 0;
        }
        
        .breadcrumb-item:nth-child(1) {
            animation-delay: 0.1s;
        }
        
        .breadcrumb-item:nth-child(2) {
            animation-delay: 0.3s;
        }
        
        .breadcrumb-item:nth-child(3) {
            animation-delay: 0.5s;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateX(10px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .breadcrumb-item:first-child {
            padding-right: 5px;
        }
        
        .breadcrumb-item:not(:last-child) {
            border-left: 2px solid var(--border-color);
        }
        
        .breadcrumb-item i {
            margin-left: 10px;
            font-size: 1.2em;
            color: var(--secondary-color);
            transition: transform 0.3s ease;
        }
        
        .breadcrumb-item:hover i {
            transform: scale(1.2);
        }
        
        .breadcrumb-item a {
            color: var(--secondary-color);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 1.05em;
            position: relative;
            padding-bottom: 2px;
        }
        
        .breadcrumb-item a::after {
            content: '';
            position: absolute;
            bottom: 0;
            right: 0;
            width: 0;
            height: 2px;
            background: var(--secondary-color);
            transition: width 0.3s ease;
        }
        
        .breadcrumb-item a:hover::after {
            width: 100%;
        }
        
        .breadcrumb-item a:hover {
            color: var(--secondary-dark);
        }
        
        .breadcrumb-item.active {
            color: var(--danger-color);
            font-weight: 700;
            font-size: 1.05em;
            position: relative;
            padding-right: 20px;
        }
        
        .breadcrumb-item.active::before {
            content: '';
            position: absolute;
            top: 50%;
            right: 0;
            width: 10px;
            height: 10px;
            background: var(--danger-color);
            border-radius: 50%;
            transform: translateY(-50%);
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.2);
        }
        
        .breadcrumb-item.active i {
            color: var(--danger-color);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.2);
            }
            100% {
                transform: scale(1);
            }
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
                    <a href="../app/main.php">الرئيسية</a>
                </div>
                <div class="breadcrumb-item">
                    <i class="fas fa-calculator"></i>
                    <a href="installments.php">الأقساط</a>
                </div>
                <div class="breadcrumb-item active">
                    <i class="fas fa-clock"></i>
                    المتأخرات
                </div>
            </div>
            
            
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-exclamation-circle"></i>
                    الأقساط المتأخرة
                </h1>
                
                <div class="header-actions">
                    <button class="export-btn" id="exportBtn">
                        <i class="fas fa-file-export"></i>
                        تصدير التقرير
                    </button>
                </div>
            </div>
            
            
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-label">إجمالي الأقساط المتأخرة</div>
                    <div class="stat-value"><?php echo count($late_installments); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">إجمالي المبالغ المتأخرة</div>
                    <div class="stat-value"><?php echo number_format($total_late_amount, 2); ?> جنيه</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">عدد العملاء المتأخرين</div>
                    <div class="stat-value"><?php echo $unique_customers_count; ?></div>
                </div>
            </div>
            
            
            <div class="installments-list">
                <div class="list-header">
                    <h2 class="list-title">
                        <i class="fas fa-list-alt"></i>
                        قائمة الأقساط المتأخرة
                    </h2>
                </div>
                
                
                <div class="filter-container">
                    <div class="filter-group">
                        <label class="filter-label">بحث بإسم العميل</label>
                        <input type="text" class="filter-input" id="searchCustomer" placeholder="أدخل اسم العميل...">
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">الترتيب حسب</label>
                        <select class="filter-input" id="sortOrder">
                            <option value="date-asc">التاريخ - الأقدم أولاً</option>
                            <option value="date-desc">التاريخ - الأحدث أولاً</option>
                            <option value="amount-asc">المبلغ - الأقل أولاً</option>
                            <option value="amount-desc">المبلغ - الأعلى أولاً</option>
                            <option value="delay-asc">مدة التأخير - الأقل أولاً</option>
                            <option value="delay-desc">مدة التأخير - الأكثر أولاً</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">تصفية حسب مدة التأخير</label>
                        <select class="filter-input" id="delayFilter">
                            <option value="all">جميع الأقساط المتأخرة</option>
                            <option value="1-7">متأخر أقل من أسبوع</option>
                            <option value="8-30">متأخر من أسبوع لشهر</option>
                            <option value="31-90">متأخر من شهر لثلاثة أشهر</option>
                            <option value="90+">متأخر أكثر من ثلاثة أشهر</option>
                        </select>
                    </div>
                </div>
                
                
                <?php if (empty($late_installments)): ?>
                    <div class="empty-state">
                        <i class="fas fa-check-circle"></i>
                        <div class="empty-state-title">لا توجد أقساط متأخرة</div>
                        <p class="empty-state-text">جميع الأقساط تم سدادها في موعدها. أحسنت!</p>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table class="installments-table" id="lateInstallmentsTable">
                            <thead>
                                <tr>
                                    <th>العميل</th>
                                    <th>رقم القسط</th>
                                    <th>تاريخ الاستحقاق</th>
                                    <th>المبلغ</th>
                                    <th>مدة التأخير</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($late_installments as $installment): 
                                    // حساب مدة التأخير بالأيام
                                    $due_date = new DateTime($installment['due_date']);
                                    $today = new DateTime($current_date);
                                    $delay = $due_date->diff($today);
                                    $delay_days = $delay->days;
                                ?>
                                <tr data-delay="<?php echo $delay_days; ?>" data-amount="<?php echo $installment['amount']; ?>" data-date="<?php echo $installment['due_date']; ?>">
                                    <td>
                                        <a href="installments_detail.php?customer_id=<?php echo $installment['customer_id']; ?>" class="customer-link">
                                            <?php echo htmlspecialchars($installment['customer_name']); ?>
                                        </a>
                                        <div style="font-size: 0.85em; color: var(--text-light);">
                                            <?php echo htmlspecialchars($installment['customer_phone'] ?? 'لا يوجد رقم'); ?>
                                        </div>
                                    </td>
                                    <td><?php echo $installment['id']; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($installment['due_date'])); ?></td>
                                    <td><?php echo number_format($installment['amount'], 2); ?> جنيه</td>
                                    <td class="delay-days"><?php echo $delay_days; ?> يوم</td>
                                    <td>
                                        <a href="installments_detail.php?customer_id=<?php echo $installment['customer_id']; ?>" class="action-btn view-btn">
                                            <i class="fas fa-eye"></i> عرض
                                        </a>
                                        <button class="action-btn pay-btn" onclick="payInstallment(<?php echo $installment['id']; ?>, <?php echo $installment['customer_id']; ?>)">
                                            <i class="fas fa-money-bill"></i> تسديد
                                        </button>
                                        <button class="action-btn remind-btn" onclick="sendReminder(<?php echo $installment['customer_id']; ?>, '<?php echo $installment['customer_name']; ?>', <?php echo $installment['amount']; ?>, '<?php echo $installment['due_date']; ?>')">
                                            <i class="fas fa-bell"></i> تذكير
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchCustomer');
            const sortSelect = document.getElementById('sortOrder');
            const delayFilter = document.getElementById('delayFilter');
            const tableBody = document.querySelector('#lateInstallmentsTable tbody');
            const rows = tableBody ? Array.from(tableBody.querySelectorAll('tr')) : [];
            
            // Function to filter and sort the table
            function filterAndSortTable() {
                if (!tableBody) return;
                
                const searchTerm = searchInput.value.toLowerCase();
                const sortValue = sortSelect.value;
                const delayValue = delayFilter.value;
                
                while (tableBody.firstChild) {
                    tableBody.removeChild(tableBody.firstChild);
                }
                
                // Filter the rows
                let filteredRows = rows.filter(row => {
                    const customerName = row.querySelector('td:first-child').textContent.toLowerCase();
                    const delayDays = parseInt(row.getAttribute('data-delay'));
                    
                    // Filter by customer name
                    if (searchTerm && !customerName.includes(searchTerm)) {
                        return false;
                    }
                    
                    // Filter by delay period
                    if (delayValue !== 'all') {
                        const [min, max] = delayValue.split('-');
                        if (max) {
                            if (delayDays < parseInt(min) || delayDays > parseInt(max)) {
                                return false;
                            }
                        } else {
                            // Handle 90+ case
                            if (delayDays < parseInt(min.replace('+', ''))) {
                                return false;
                            }
                        }
                    }
                    
                    return true;
                });
                
                // Sort the rows
                filteredRows.sort((a, b) => {
                    const [sortBy, sortOrder] = sortValue.split('-');
                    const aValue = a.getAttribute(`data-${sortBy}`);
                    const bValue = b.getAttribute(`data-${sortBy}`);
                    
                    let comparison = 0;
                    if (sortBy === 'amount' || sortBy === 'delay') {
                        comparison = parseFloat(aValue) - parseFloat(bValue);
                    } else {
                        comparison = aValue.localeCompare(bValue);
                    }
                    
                    return sortOrder === 'asc' ? comparison : -comparison;
                });
                
                // Add the filtered and sorted rows back to the table
                filteredRows.forEach(row => {
                    tableBody.appendChild(row.cloneNode(true));
                });
                
                // Show empty state if no results
                if (filteredRows.length === 0) {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td colspan="6" style="text-align: center; padding: 30px;">
                            <i class="fas fa-search" style="font-size: 2em; margin-bottom: 10px; opacity: 0.3;"></i>
                            <p>لا توجد نتائج مطابقة للبحث</p>
                        </td>
                    `;
                    tableBody.appendChild(tr);
                }
            }
            
            // Add event listeners
            if (searchInput) searchInput.addEventListener('input', filterAndSortTable);
            if (sortSelect) sortSelect.addEventListener('change', filterAndSortTable);
            if (delayFilter) delayFilter.addEventListener('change', filterAndSortTable);
            
            // Function to handle payment
            window.payInstallment = function(installmentId, customerId) {
                if (confirm('هل تريد تسديد هذا القسط؟')) {
                    window.location.href = `installments_detail.php?customer_id=${customerId}&action=pay_specific&installment_id=${installmentId}`;
                }
            };
            
            // Function to handle sending reminders
            window.sendReminder = function(customerId, customerName, amount, dueDate) {
                // حساب عدد أيام التأخير
                const today = new Date();
                const dueDateObj = new Date(dueDate);
                const diffTime = today - dueDateObj;
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                
                // تحويل تنسيق التاريخ
                const formattedDate = dueDateObj.toLocaleDateString('ar-EG');
                
                // البحث عن رقم هاتف العميل من الصف المحدد
                const row = event.target.closest('tr');
                const phoneElement = row.querySelector('td:first-child div');
                let customerPhone = '';
                
                if (phoneElement) {
                    customerPhone = phoneElement.textContent.trim();
                    // إزالة "لا يوجد رقم" إذا وجد
                    if (customerPhone === 'لا يوجد رقم') {
                        customerPhone = '';
                    }
                }
                
                // إنشاء رسالة التذكير للعرض
                const message = `تذكير: عزيزي ${customerName}، لديك قسط متأخر بقيمة ${amount} جنيه، كان موعد استحقاقه ${formattedDate} (متأخر بـ ${diffDays} يوم). نرجو سداده في أقرب وقت ممكن.`;
                
                // عرض مؤشر التحميل
                const button = event.target.closest('button');
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري الإرسال...';
                button.disabled = true;
                
                // إرسال طلب AJAX للخادم
                fetch('late_installments.php?send_sms=1', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        customer_id: customerId,
                        customer_name: customerName,
                        customer_phone: customerPhone,
                        amount: amount,
                        due_date: dueDate,
                        days_late: diffDays
                    })
                })
                .then(response => response.json())
                .then(data => {
                    // استعادة النص الأصلي للزر
                    button.innerHTML = originalText;
                    button.disabled = false;
                    
                    if (data.success) {
                        // عرض رسالة نجاح
                        alert('تم إرسال رسالة التذكير بنجاح\n\n' + message);
                    } else {
                        // عرض رسالة خطأ
                        alert('حدث خطأ أثناء إرسال التذكير: ' + (data.error || 'خطأ غير معروف'));
                    }
                })
                .catch(error => {
                    // استعادة النص الأصلي للزر
                    button.innerHTML = originalText;
                    button.disabled = false;
                    
                    // عرض رسالة الخطأ
                    alert('حدث خطأ أثناء الاتصال بالخادم: ' + error.message);
                });
            };
            
            // Export functionality
            document.getElementById('exportBtn').addEventListener('click', function() {
                // يمكن إضافة وظيفة تصدير البيانات إلى Excel أو PDF هنا
                alert('سيتم تصدير التقرير قريباً. هذه الميزة قيد التطوير.');
            });
            
            // تبديل وضع الثيم الداكن/الفاتح من خلال localStorage
            const isDarkMode = localStorage.getItem('darkMode') === 'true';
            if (isDarkMode) {
                document.body.classList.add('dark-mode');
            }
        });
    </script>
</body>
</html> 