<?php
session_start();

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// استيراد ملف قاعدة البيانات
include '../app/db.php';

// تحديد نوع العرض (مبيعات أو نشاطات)
$type = isset($_GET['type']) ? $_GET['type'] : 'sales';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10; // عدد العناصر في الصفحة الواحدة
$offset = ($page - 1) * $perPage;

// البحث
$search = isset($_GET['search']) ? $_GET['search'] : '';
$whereClause = '';

if (!empty($search)) {
    if ($type === 'sales') {
        $whereClause = " WHERE s.invoice_number LIKE :search OR c.name LIKE :search OR s.total_amount LIKE :search OR s.payment_status LIKE :search";
    } else {
        $whereClause = " WHERE u.username LIKE :search OR a.action LIKE :search";
    }
}

// الحصول على إجمالي عدد العناصر
$countSql = '';
if ($type === 'sales') {
    $countSql = "SELECT COUNT(*) as total FROM sales s 
                LEFT JOIN customers c ON s.customer_id = c.id" . $whereClause;
} else {
    $countSql = "SELECT COUNT(*) as total FROM recent_activities a 
                LEFT JOIN users u ON a.user_id = u.id" . $whereClause;
}

$countStmt = $conn->prepare($countSql);
if (!empty($search)) {
    $countStmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
}
$countStmt->execute();
$totalItems = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalItems / $perPage);

// الحصول على البيانات
$items = [];
if ($type === 'sales') {
    $sql = "SELECT s.id, s.invoice_number, s.total_amount as amount, s.created_at, s.payment_method, s.payment_status as status,
            COALESCE(c.name, 'عميل نقدي') as customer
            FROM sales s
            LEFT JOIN customers c ON s.customer_id = c.id
            $whereClause
            ORDER BY s.created_at DESC
            LIMIT :offset, :limit";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $perPage, PDO::PARAM_INT);
    if (!empty($search)) {
        $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
    }
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the sales data
    foreach ($items as &$sale) {
        // Format date
        $date = new DateTime($sale['created_at']);
        $sale['date'] = $date->format('Y-m-d');
        $sale['time'] = $date->format('h:i A');
        
        // Translate status
        if ($sale['status'] == 'paid') {
            $sale['status'] = 'مكتمل';
        } else if ($sale['status'] == 'pending') {
            $sale['status'] = 'معلق';
        } else if ($sale['status'] == 'cancelled') {
            $sale['status'] = 'ملغي';
        } else if ($sale['status'] == 'processing') {
            $sale['status'] = 'قيد المعالجة';
        }
    }
} else {
    // For activities, we'll use a fallback in case the table doesn't exist
    try {
        $sql = "SELECT a.id, a.action, a.created_at, a.module, a.reference_id, 
                COALESCE(u.username, 'النظام') as user
                FROM recent_activities a
                LEFT JOIN users u ON a.user_id = u.id
                $whereClause
                ORDER BY a.created_at DESC
                LIMIT :offset, :limit";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $perPage, PDO::PARAM_INT);
        if (!empty($search)) {
            $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
        }
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Fallback to sales activities if the activities table doesn't exist
        $sql = "SELECT s.id, 'بيع جديد' as action, s.created_at, 
                COALESCE(u.username, 'النظام') as user
                FROM sales s
                LEFT JOIN users u ON s.user_id = u.id
                $whereClause
                ORDER BY s.created_at DESC
                LIMIT :offset, :limit";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $perPage, PDO::PARAM_INT);
        if (!empty($search)) {
            $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
        }
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Format the activities
    foreach ($items as &$activity) {
        // Format date and time
        $timestamp = new DateTime($activity['created_at']);
        $activity['date'] = $timestamp->format('Y-m-d');
        $activity['time'] = $timestamp->format('h:i A');
    }
}

// تحديد العنوان
$pageTitle = $type === 'sales' ? 'سجل المبيعات' : 'سجل النشاطات';
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title><?php echo $pageTitle; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        
        body, html {
            background-color: #f5f7fa;
            font-family: 'Cairo', 'Segoe UI', Arial, sans-serif;
        }
        
        .main-content-area {
            padding: 20px 30px;
            transition: all 0.3s ease;
        }
        
        
        .page-title {
            font-size: 2.2em;
            font-weight: 800;
            color: #1565c0;
            margin-bottom: 25px;
            text-align: right;
            position: relative;
            padding-right: 15px;
        }
        
        .page-title::before {
            content: "";
            position: absolute;
            right: 0;
            top: 8px;
            height: 70%;
            width: 4px;
            background: linear-gradient(to bottom, #1976d2, #64b5f6);
            border-radius: 4px;
        }
        
        .container {
            padding: 10px 20px 30px 20px;
            max-width: 1600px;
            margin: 0 auto;
        }
        
        
        .card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            padding: 24px;
            margin: 0 0 30px 0;
            border: 1px solid rgba(0,0,0,0.03);
        }
        
        
        .filters-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .search-container {
            flex: 1;
            max-width: 400px;
            position: relative;
        }
        
        .search-input {
            width: 100%;
            padding: 12px 16px 12px 45px;
            font-size: 0.95em;
            border: 1px solid #e1e7ef;
            border-radius: 12px;
            color: #444;
            transition: all 0.3s ease;
            background-color: #f8f9fa;
        }
        
        .search-input:focus {
            border-color: #2196f3;
            outline: none;
            box-shadow: 0 0 0 3px rgba(33,150,243,0.1);
        }
        
        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #777;
        }
        
        .tabs {
            display: flex;
            gap: 10px;
        }
        
        .tab-link {
            padding: 10px 20px;
            background-color: #f8f9fa;
            color: #444;
            font-weight: 600;
            border-radius: 12px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .tab-link.active {
            background-color: #2196f3;
            color: #fff;
        }
        
        .tab-link:hover:not(.active) {
            background-color: #e3f2fd;
            color: #1976d2;
        }
        
        
        .table-responsive {
            overflow-x: auto;
            border-radius: 12px;
        }
        
        .data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            color: #444;
        }
        
        .data-table th {
            background-color: #f8f9fa;
            color: #1565c0;
            font-weight: 600;
            padding: 15px;
            text-align: right;
            border-bottom: 2px solid #e3f2fd;
            font-size: 0.95em;
        }
        
        .data-table td {
            padding: 14px 15px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 0.95em;
            transition: all 0.3s ease;
        }
        
        .data-table tr:last-child td {
            border-bottom: none;
        }
        
        .data-table tr:hover td {
            background-color: #f5f7fa;
        }
        
        .empty-table {
            text-align: center;
            color: #999;
            padding: 30px 0 !important;
            font-style: italic;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 8px;
            font-size: 0.85em;
            font-weight: 600;
        }
        
        .status-مكتمل, .status-completed {
            background-color: #e8f5e9;
            color: #388e3c;
        }
        
        .status-معلق, .status-pending {
            background-color: #fff8e1;
            color: #ffa000;
        }
        
        .status-ملغي, .status-cancelled {
            background-color: #ffebee;
            color: #d32f2f;
        }
        
        .status-قيد-المعالجة, .status-processing {
            background-color: #e3f2fd;
            color: #1976d2;
        }
        
        .activity-type {
            padding: 6px 10px;
            border-radius: 8px;
            font-size: 0.85em;
            font-weight: 600;
            background-color: #e3f2fd;
            color: #1976d2;
        }
        
        .invoice-link {
            color: #1976d2;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .invoice-link:hover {
            color: #0d47a1;
            text-decoration: underline;
        }
        
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .page-link {
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 40px;
            height: 40px;
            background-color: #fff;
            color: #444;
            border-radius: 10px;
            text-decoration: none;
            transition: all 0.3s ease;
            border: 1px solid #f0f0f0;
            font-weight: 500;
        }
        
        .page-link.active {
            background-color: #2196f3;
            color: #fff;
            border-color: #2196f3;
        }
        
        .page-link:hover:not(.active):not(.disabled) {
            background-color: #e3f2fd;
            color: #1976d2;
        }
        
        .page-link.disabled {
            color: #bbb;
            cursor: not-allowed;
        }
        
        
        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            color: #1976d2;
            background-color: #e3f2fd;
            text-decoration: none;
            transition: all 0.3s ease;
            margin-right: 5px;
        }
        
        .action-btn:hover {
            background-color: #bbdefb;
            color: #1565c0;
            transform: translateY(-3px);
        }
        
        .action-btn i {
            font-size: 1.1em;
        }
        
        
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #444;
            text-decoration: none;
            font-weight: 600;
            padding: 10px 20px;
            background-color: #f8f9fa;
            border-radius: 12px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .back-button:hover {
            background-color: #e3f2fd;
            color: #1976d2;
            transform: translateX(5px);
        }
        
        
        @media (max-width: 768px) {
            .filters-bar {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-container {
                max-width: 100%;
            }
            
            .tabs {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .pagination {
                margin-top: 20px;
            }
        }
    </style>
</head>
<body>
    <button class="menu-toggle">☰</button>
    <div class="layout-wrapper">
        <?php include '../app/includes/sidebar.php'; ?>
        <div class="main-content-area">
            <div class="container">
                <a href="../app/main.php" class="back-button">
                    <i class="fas fa-arrow-right"></i>
                    العودة إلى لوحة التحكم
                </a>
                
                <h1 class="page-title"><?php echo $pageTitle; ?></h1>
                
                <div class="card">
                    <div class="filters-bar">
                        <div class="search-container">
                            <form action="" method="GET">
                                <input type="hidden" name="type" value="<?php echo $type; ?>">
                                <input type="text" name="search" class="search-input" placeholder="بحث..." value="<?php echo htmlspecialchars($search); ?>">
                                <i class="fas fa-search search-icon"></i>
                            </form>
                        </div>
                        
                        <div class="tabs">
                            <a href="?type=sales<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="tab-link <?php echo $type === 'sales' ? 'active' : ''; ?>">المبيعات</a>
                            <a href="?type=activities<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="tab-link <?php echo $type === 'activities' ? 'active' : ''; ?>">النشاطات</a>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <?php if ($type === 'sales'): ?>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>رقم الفاتورة</th>
                                        <th>العميل</th>
                                        <th>المبلغ</th>
                                        <th>الحالة</th>
                                        <th>التاريخ</th>
                                        <th>الوقت</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($items)): ?>
                                        <?php foreach ($items as $item): ?>
                                            <tr>
                                                <td><a href="cashier.php?invoice=<?php echo $item['id']; ?>" class="invoice-link">#<?php echo $item['invoice_number']; ?></a></td>
                                                <td><?php echo $item['customer']; ?></td>
                                                <td><?php echo $item['amount']; ?> جنيه</td>
                                                <td>
                                                    <span class="status-badge status-<?php echo strtolower($item['status']); ?>">
                                                        <?php echo $item['status']; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $item['date']; ?></td>
                                                <td><?php echo $item['time']; ?></td>
                                                <td>
                                                    <a href="cashier.php?invoice=<?php echo $item['id']; ?>" class="action-btn" title="عرض الفاتورة">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="print_invoice.php?id=<?php echo $item['id']; ?>" class="action-btn" title="طباعة الفاتورة">
                                                        <i class="fas fa-print"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="empty-table">لا توجد مبيعات</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>النشاط</th>
                                        <th>المستخدم</th>
                                        <th>التاريخ</th>
                                        <th>الوقت</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($items)): ?>
                                        <?php foreach ($items as $item): ?>
                                            <tr>
                                                <td><span class="activity-type"><?php echo $item['action']; ?></span></td>
                                                <td><?php echo $item['user']; ?></td>
                                                <td><?php echo $item['date']; ?></td>
                                                <td><?php echo $item['time']; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="empty-table">لا توجد نشاطات</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?type=<?php echo $type; ?>&page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="page-link">
                                    <i class="fas fa-angle-double-right"></i>
                                </a>
                                <a href="?type=<?php echo $type; ?>&page=<?php echo $page-1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="page-link">
                                    <i class="fas fa-angle-right"></i>
                                </a>
                            <?php else: ?>
                                <span class="page-link disabled"><i class="fas fa-angle-double-right"></i></span>
                                <span class="page-link disabled"><i class="fas fa-angle-right"></i></span>
                            <?php endif; ?>
                            
                            <?php
                            // Display page numbers with limited range
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            // Always show at least 5 pages if possible
                            if ($endPage - $startPage + 1 < 5) {
                                if ($startPage == 1) {
                                    $endPage = min($totalPages, $startPage + 4);
                                } elseif ($endPage == $totalPages) {
                                    $startPage = max(1, $endPage - 4);
                                }
                            }
                            
                            for ($i = $startPage; $i <= $endPage; $i++) {
                                if ($i == $page) {
                                    echo '<span class="page-link active">' . $i . '</span>';
                                } else {
                                    echo '<a href="?type=' . $type . '&page=' . $i . 
                                        (!empty($search) ? '&search=' . urlencode($search) : '') . 
                                        '" class="page-link">' . $i . '</a>';
                                }
                            }
                            ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="?type=<?php echo $type; ?>&page=<?php echo $page+1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="page-link">
                                    <i class="fas fa-angle-left"></i>
                                </a>
                                <a href="?type=<?php echo $type; ?>&page=<?php echo $totalPages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="page-link">
                                    <i class="fas fa-angle-double-left"></i>
                                </a>
                            <?php else: ?>
                                <span class="page-link disabled"><i class="fas fa-angle-left"></i></span>
                                <span class="page-link disabled"><i class="fas fa-angle-double-left"></i></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize sidebar toggling with overlay
            const menuToggle = document.querySelector('.menu-toggle');
            const sidebar = document.getElementById('sidebar');
            const body = document.body;
            
            if (menuToggle && sidebar) {
                // Create overlay element
                const overlay = document.createElement('div');
                overlay.className = 'sidebar-overlay';
                body.appendChild(overlay);
                
                menuToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    sidebar.classList.toggle('open');
                    body.classList.toggle('sidebar-open');
                    
                    // Toggle overlay
                    if (sidebar.classList.contains('open') && window.innerWidth <= 991) {
                        overlay.classList.add('active');
                    } else {
                        overlay.classList.remove('active');
                    }
                });
                
                // Close sidebar when clicking on overlay
                overlay.addEventListener('click', function() {
                    sidebar.classList.remove('open');
                    body.classList.remove('sidebar-open');
                    overlay.classList.remove('active');
                });
            }
            
            // Auto-submit search form on input change
            const searchInput = document.querySelector('.search-input');
            if (searchInput) {
                searchInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        this.closest('form').submit();
                    }
                });
            }
        });
    </script>
</body>
</html> 