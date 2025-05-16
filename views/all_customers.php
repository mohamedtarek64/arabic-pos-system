<?php
session_start();
include '../app/db.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// استعلام البحث الافتراضي
$search_query = $_GET['search'] ?? '';
$sort_by = $_GET['sort_by'] ?? 'name';
$sort_order = $_GET['sort_order'] ?? 'asc';

// بناء استعلام SQL للبحث والفرز
$sql_search = '';
$params = [];

if (!empty($search_query)) {
    $sql_search = " WHERE name LIKE :search_name OR phone LIKE :search_phone";
    // تحقق إذا كان عمود البريد الإلكتروني موجودًا قبل البحث فيه
    try {
        $check_column = $conn->query("SHOW COLUMNS FROM customers LIKE 'email'");
        if ($check_column->rowCount() > 0) {
            $sql_search .= " OR email LIKE :search_email";
            $params[':search_email'] = '%' . $search_query . '%';
        }
    } catch (PDOException $e) {
        // إذا فشل الاستعلام، نستمر بدون البحث في عمود البريد الإلكتروني
    }
    $params[':search_name'] = '%' . $search_query . '%';
    $params[':search_phone'] = '%' . $search_query . '%';
}

// التأكد من صحة عمود الترتيب
$valid_sort_columns = ['name', 'id', 'created_at'];
// تحقق إذا كان عمود account_price موجودًا
try {
    $check_column = $conn->query("SHOW COLUMNS FROM customers LIKE 'account_price'");
    if ($check_column->rowCount() > 0) {
        $valid_sort_columns[] = 'account_price';
    }
} catch (PDOException $e) {
    // إذا فشل الاستعلام، نستمر بدون إضافة عمود account_price للترتيب
}

if (!in_array($sort_by, $valid_sort_columns)) {
    $sort_by = 'name'; // الترتيب الافتراضي
}

$valid_sort_orders = ['asc', 'desc'];
if (!in_array($sort_order, $valid_sort_orders)) {
    $sort_order = 'asc'; // الترتيب الافتراضي
}

$sql_order = " ORDER BY $sort_by $sort_order";

$sql = "SELECT * FROM customers" . $sql_search . $sql_order;
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
}

$stmt->execute();
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// إحصائيات مختصرة
$total_customers = count($customers);
$total_with_installments = 0;
$total_account_value = 0;

// استعلام للعملاء الذين لديهم أقساط
$sql_with_installments = "SELECT COUNT(DISTINCT customer_id) as count FROM installments";
$stmt_with_installments = $conn->prepare($sql_with_installments);
$stmt_with_installments->execute();
$result_with_installments = $stmt_with_installments->fetch(PDO::FETCH_ASSOC);
$total_with_installments = $result_with_installments['count'] ?? 0;

// استعلام لإجمالي قيمة حسابات العملاء (إذا كان العمود موجودًا)
try {
    $check_column = $conn->query("SHOW COLUMNS FROM customers LIKE 'account_price'");
    if ($check_column->rowCount() > 0) {
        $sql_account_value = "SELECT SUM(account_price) as total FROM customers";
        $stmt_account_value = $conn->prepare($sql_account_value);
        $stmt_account_value->execute();
        $result_account_value = $stmt_account_value->fetch(PDO::FETCH_ASSOC);
        $total_account_value = $result_account_value['total'] ?? 0;
    }
} catch (PDOException $e) {
    // إذا فشل الاستعلام، نستمر مع قيمة إجمالية 0
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>جميع العملاء</title>
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
        
        .add-customer-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: var(--button-gradient);
            color: white;
            padding: 10px 15px;
            border-radius: 8px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .add-customer-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(54, 208, 59, 0.3);
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
            background: var(--header-gradient);
        }
        
        .stat-title {
            font-size: 1em;
            color: var(--text-light);
            margin-bottom: 10px;
        }
        
        .stat-value {
            font-size: 2em;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .stat-subtitle {
            font-size: 0.9em;
            color: var(--text-light);
            margin-top: 5px;
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
            font-size: 1.4em;
            font-weight: 700;
            color: var(--text-color);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .search-container {
            padding: 20px;
            background-color: var(--bg-color);
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .search-input-group {
            flex: 1;
            min-width: 200px;
            position: relative;
        }
        
        .search-input {
            width: 100%;
            padding: 12px 15px 12px 40px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            background-color: var(--card-bg);
            color: var(--text-color);
            font-family: inherit;
            font-size: 1em;
            transition: all 0.3s ease;
        }
        
        .search-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(54, 208, 59, 0.15);
            outline: none;
        }
        
        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
        }
        
        .sort-group {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .sort-label {
            white-space: nowrap;
            font-weight: 600;
            color: var(--text-color);
        }
        
        .sort-select {
            padding: 10px 15px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            background-color: var(--card-bg);
            color: var(--text-color);
            font-family: inherit;
            appearance: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .sort-select:focus {
            border-color: var(--primary-color);
            outline: none;
        }
        
        .action-btn {
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 0.85em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .view-btn {
            background-color: var(--green-light);
            color: var(--primary-color);
        }
        
        .view-btn:hover {
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
        
        .delete-btn {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .delete-btn:hover {
            background-color: #dc3545;
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
        
        .empty-state-title {
            font-size: 1.5em;
            font-weight: 700;
            margin-bottom: 10px;
            color: var(--text-color);
        }
        
        .empty-state-text {
            font-size: 1.1em;
            margin: 10px 0;
        }
        
        .empty-state-text a {
            color: var(--primary-color);
            text-decoration: underline;
        }
        
        .customers-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .customers-table th {
            background-color: var(--bg-color);
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
            background-color: var(--bg-color);
        }
        
        .account-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            background-color: var(--green-light);
            color: var(--primary-color);
            font-weight: 700;
            font-size: 0.9em;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 20px;
        }
        
        .pagination-btn {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background-color: var(--bg-color);
            color: var(--text-color);
            font-weight: 600;
            border: 1px solid var(--border-color);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .pagination-btn:hover {
            background-color: var(--border-color);
        }
        
        .pagination-btn.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        @media (max-width: 768px) {
            .search-container {
                flex-direction: column;
            }
            
            .sort-group {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .sort-label {
                margin-bottom: 5px;
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
                    <i class="fas fa-users"></i>
                    جميع العملاء
                </div>
            </div>
            
            
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-users"></i>
                    جميع العملاء
                </h1>
                
                <div class="header-actions">
                    <a href="add_customer.php" class="add-customer-btn">
                        <i class="fas fa-plus"></i>
                        إضافة عميل جديد
                    </a>
                </div>
            </div>
            
            
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-title">إجمالي العملاء</div>
                    <div class="stat-value"><?php echo $total_customers; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">العملاء بأقساط</div>
                    <div class="stat-value"><?php echo $total_with_installments; ?></div>
                    <div class="stat-subtitle">من أصل <?php echo $total_customers; ?> عميل</div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">إجمالي الحسابات</div>
                    <div class="stat-value"><?php echo number_format($total_account_value, 2); ?></div>
                    <div class="stat-subtitle">جنيه</div>
                </div>
            </div>
            
            
            <div class="card-container">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-list-alt"></i>
                        قائمة العملاء
                    </h2>
                </div>
                
                
                <div class="search-container">
                    <div class="search-input-group">
                        <input type="text" id="searchInput" class="search-input" placeholder="البحث بالاسم أو رقم الهاتف..." value="<?php echo htmlspecialchars($search_query); ?>">
                        <i class="fas fa-search search-icon"></i>
                    </div>
                    
                    <div class="sort-group">
                        <span class="sort-label">ترتيب حسب:</span>
                        <select id="sortBySelect" class="sort-select">
                            <option value="name" <?php echo $sort_by == 'name' ? 'selected' : ''; ?>>الاسم</option>
                            <?php if (in_array('account_price', $valid_sort_columns)): ?>
                            <option value="account_price" <?php echo $sort_by == 'account_price' ? 'selected' : ''; ?>>قيمة الحساب</option>
                            <?php endif; ?>
                            <option value="created_at" <?php echo $sort_by == 'created_at' ? 'selected' : ''; ?>>تاريخ التسجيل</option>
                        </select>
                        
                        <select id="sortOrderSelect" class="sort-select">
                            <option value="asc" <?php echo $sort_order == 'asc' ? 'selected' : ''; ?>>تصاعدي</option>
                            <option value="desc" <?php echo $sort_order == 'desc' ? 'selected' : ''; ?>>تنازلي</option>
                        </select>
                        
                        <button id="applyFiltersBtn" class="action-btn view-btn">تطبيق</button>
                    </div>
                </div>
                
                
                <?php if (empty($customers)): ?>
                    <div class="empty-state">
                        <i class="fas fa-users"></i>
                        <div class="empty-state-title">لا يوجد عملاء</div>
                        <p class="empty-state-text">لم يتم العثور على أي عملاء متطابقين مع معايير البحث.</p>
                        <p class="empty-state-text"><a href="add_customer.php">إضافة عميل جديد</a></p>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table class="customers-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>الاسم</th>
                                    <th>رقم الهاتف</th>
                                    <th>البريد الإلكتروني</th>
                                    <th>قيمة الحساب</th>
                                    <th>تاريخ التسجيل</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($customers as $index => $customer): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($customer['name'] ?? 'غير متوفر'); ?></td>
                                    <td><?php echo htmlspecialchars($customer['phone'] ?? 'غير متوفر'); ?></td>
                                    <td><?php echo isset($customer['email']) ? htmlspecialchars($customer['email']) : 'غير متوفر'; ?></td>
                                    <td>
                                        <?php if (isset($customer['account_price']) && $customer['account_price'] > 0): ?>
                                            <span class="account-badge"><?php echo number_format($customer['account_price'], 2); ?> جنيه</span>
                                        <?php else: ?>
                                            <span style="color: var(--text-light);">0.00 جنيه</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo isset($customer['created_at']) ? date('d/m/Y', strtotime($customer['created_at'])) : 'غير متوفر'; ?></td>
                                    <td>
                                        <a href="customer_details.php?customer_id=<?php echo $customer['id']; ?>" class="action-btn view-btn">
                                            <i class="fas fa-user-circle"></i> البروفايل
                                        </a>
                                        <a href="installments_detail.php?customer_id=<?php echo $customer['id']; ?>" class="action-btn view-btn">
                                            <i class="fas fa-money-bill-wave"></i> الأقساط
                                        </a>
                                        <a href="add_installment.php?customer_id=<?php echo $customer['id']; ?>" class="action-btn edit-btn">
                                            <i class="fas fa-plus"></i> قسط
                                        </a>
                                        <button class="action-btn delete-btn" onclick="deleteCustomer(<?php echo $customer['id']; ?>, '<?php echo addslashes(htmlspecialchars($customer['name'] ?? '')); ?>')">
                                            <i class="fas fa-trash"></i> حذف
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
            // تبديل وضع الثيم الداكن/الفاتح
            const isDarkMode = localStorage.getItem('darkMode') === 'true';
            if (isDarkMode) {
                document.body.classList.add('dark-mode');
            }
            
            // تطبيق البحث والفلترة
            const searchInput = document.getElementById('searchInput');
            const sortBySelect = document.getElementById('sortBySelect');
            const sortOrderSelect = document.getElementById('sortOrderSelect');
            const applyFiltersBtn = document.getElementById('applyFiltersBtn');
            
            // تنفيذ البحث بالضغط على Enter
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    applyFilters();
                }
            });
            
            // زر تطبيق الفلاتر
            applyFiltersBtn.addEventListener('click', applyFilters);
            
            function applyFilters() {
                const searchQuery = searchInput.value.trim();
                const sortBy = sortBySelect.value;
                const sortOrder = sortOrderSelect.value;
                
                let url = `?sort_by=${sortBy}&sort_order=${sortOrder}`;
                
                if (searchQuery) {
                    url += `&search=${encodeURIComponent(searchQuery)}`;
                }
                
                window.location.href = url;
            }
            
            // حذف العميل
            window.deleteCustomer = function(id, name) {
                if (confirm(`هل أنت متأكد من حذف العميل "${name}"؟`)) {
                    fetch(`delete_customer.php?customer_id=${id}&ajax=1`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert(data.message);
                                // إعادة تحميل الصفحة لتحديث القائمة
                                window.location.reload();
                            } else {
                                alert(data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('حدث خطأ أثناء محاولة حذف العميل');
                        });
                }
            };
        });
    </script>
</body>
</html> 