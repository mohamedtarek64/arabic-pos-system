<?php
include '../app/db.php';

// Handle product deletion
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $deleteSql = "DELETE FROM products WHERE id = ?";
    $stmt = $conn->prepare($deleteSql);
    $stmt->execute([$id]);
    
    // Redirect to avoid resubmission
    header("Location: products.php");
    exit();
}

// Handle product addition
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $code = $_POST['code'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];
    $barcode = $_POST['barcode'];

    // إنشاء باركود فريد تلقائياً إذا لم يتم إدخاله
    if (empty($barcode)) {
        // استخدام مزيج من الوقت الحالي والرقم العشوائي والحروف العشوائية لضمان فرادة الباركود
        $timestamp = time();
        $random = rand(10000, 99999);
        $chars = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 3);
        $barcode = 'PRD' . $timestamp . $random . $chars;
    }

    // إدخال منتج
    $sql = "INSERT INTO products (name, code, price, quantity, barcode) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$name, $code, $price, $quantity, $barcode]);

    // Redirect to avoid resubmission
    header("Location: products.php");
    exit();
}

// Get all products
$sql = "SELECT * FROM products ORDER BY id DESC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total inventory value
$totalValue = 0;
foreach ($products as $product) {
    $totalValue += $product['price'] * $product['quantity'];
}

// Get total products count
$totalProducts = count($products);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المنتجات</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .products-container {
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            margin: 20px;
        }
        
        .add-product-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            border: 1px solid #e3e6f0;
        }
        
        .add-product-section h2 {
            color: #2196f3;
            margin-bottom: 20px;
            font-size: 1.5em;
            text-align: right;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .btn-add {
            background-color: #2196f3;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        
        .btn-add:hover {
            background-color: #0d8aee;
        }
        
        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .products-table th {
            background-color: #2196f3;
            color: white;
            padding: 12px;
            text-align: right;
        }
        
        .products-table td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }
        
        .products-table tr:hover {
            background-color: #f5f5f5;
        }
        
        .btn-delete {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn-delete:hover {
            background-color: #c0392b;
        }
        
        .btn-edit {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            margin-left: 5px;
            transition: background-color 0.3s;
        }
        
        .btn-edit:hover {
            background-color: #2980b9;
        }
        
        .empty-message {
            text-align: center;
            padding: 20px;
            color: #777;
            font-style: italic;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        
        .page-header {
            background-color: #fff;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .breadcrumb {
            display: flex;
            align-items: center;
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .breadcrumb-item {
            font-size: 16px;
            color: #555;
        }
        
        .breadcrumb-item a {
            color: #2196f3;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .breadcrumb-item a:hover {
            color: #0d8aee;
        }
        
        .breadcrumb-item + .breadcrumb-item::before {
            content: "/";
            padding: 0 10px;
            color: #ccc;
        }
        
        .breadcrumb-item.active {
            color: #333;
            font-weight: 600;
        }
        
        .quick-actions {
            display: flex;
            gap: 10px;
        }
        
        .quick-action-btn {
            background-color: #2196f3;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
            text-decoration: none;
            display: flex;
            align-items: center;
        }
        
        .quick-action-btn i {
            margin-left: 5px;
        }
        
        .quick-action-btn:hover {
            background-color: #0d8aee;
            transform: translateY(-2px);
        }
        
        .quick-action-btn.btn-secondary {
            background-color: #6c757d;
        }
        
        .quick-action-btn.btn-secondary:hover {
            background-color: #5a6268;
        }
        
        
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            background-color: #e3f2fd;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 15px;
        }
        
        .stat-icon i {
            font-size: 24px;
            color: #2196f3;
        }
        
        .stat-info {
            flex: 1;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            margin: 0 0 5px 0;
        }
        
        .stat-label {
            font-size: 14px;
            color: #777;
            margin: 0;
        }
        
        .stat-card.products {
            border-right: 4px solid #2196f3;
        }
        
        .stat-card.value {
            border-right: 4px solid #4caf50;
        }
        
        .stat-card.value .stat-icon {
            background-color: #e8f5e9;
        }
        
        .stat-card.value .stat-icon i {
            color: #4caf50;
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .quick-actions {
                margin-top: 15px;
                width: 100%;
                justify-content: space-between;
            }
            
            .stats-cards {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="layout-wrapper">
        <?php include '../app/includes/sidebar.php'; ?>
        
        <div class="main-content-area">
            
            <div class="page-header">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../app/main.php"><i class="fas fa-home"></i> الرئيسية</a></li>
                        <li class="breadcrumb-item active">المنتجات</li>
                    </ol>
                </nav>
                <div class="quick-actions">
                    <a href="#add-product" class="quick-action-btn">
                        <i class="fas fa-plus"></i> إضافة منتج
                    </a>
                    <a href="categories.php" class="quick-action-btn btn-secondary">
                        <i class="fas fa-tags"></i> التصنيفات
                    </a>
                </div>
            </div>
            
            
            <div class="stats-cards">
                <div class="stat-card products">
                    <div class="stat-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-info">
                        <h3 class="stat-value"><?php echo $totalProducts; ?></h3>
                        <p class="stat-label">إجمالي المنتجات</p>
                    </div>
                </div>
                <div class="stat-card value">
                    <div class="stat-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-info">
                        <h3 class="stat-value"><?php echo number_format($totalValue, 2); ?></h3>
                        <p class="stat-label">قيمة المخزون</p>
                    </div>
                </div>
            </div>
            
            <div class="products-container">
                
                <div class="add-product-section" id="add-product">
                    <h2><i class="fas fa-plus-circle"></i> إضافة منتج جديد</h2>
<form action="products.php" method="POST">
                        <div class="form-grid">
                            <div class="form-group">
    <label for="name">اسم المنتج:</label>
                                <input type="text" id="name" name="name" class="form-control" required>
                            </div>

                            <div class="form-group">
    <label for="code">كود المنتج:</label>
                                <input type="text" id="code" name="code" class="form-control" required>
                            </div>

                            <div class="form-group">
    <label for="price">السعر:</label>
                                <input type="number" id="price" name="price" step="0.01" class="form-control" required>
                            </div>

                            <div class="form-group">
    <label for="quantity">الكمية:</label>
                                <input type="number" id="quantity" name="quantity" class="form-control" required>
                            </div>

                            <div class="form-group">
    <label for="barcode">الباركود:</label>
                                <input type="text" id="barcode" name="barcode" class="form-control" placeholder="سيتم إنشاؤه تلقائياً إذا تركته فارغاً">
                            </div>
                        </div>

                        <button type="submit" class="btn-add">
                            <i class="fas fa-plus"></i> إضافة المنتج
                        </button>
</form>
                </div>
                
                
                <h2>قائمة المنتجات</h2>
                <div class="table-responsive">
                    <table class="products-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>اسم المنتج</th>
                                <th>كود المنتج</th>
                                <th>السعر</th>
                                <th>الكمية</th>
                                <th>الباركود</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($products) > 0): ?>
                                <?php foreach ($products as $index => $product): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td><?php echo htmlspecialchars($product['code']); ?></td>
                                        <td><?php echo number_format($product['price'], 2); ?></td>
                                        <td><?php echo $product['quantity']; ?></td>
                                        <td><?php echo htmlspecialchars($product['barcode']); ?></td>
                                        <td>
                                            <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn-edit">
                                                <i class="fas fa-edit"></i> تعديل
                                            </a>
                                            <a href="products.php?delete=<?php echo $product['id']; ?>" class="btn-delete" 
                                               onclick="return confirm('هل أنت متأكد من حذف هذا المنتج؟')">
                                                <i class="fas fa-trash"></i> حذف
                                            </a>
                                            <a href="print_parcode.php?id=<?php echo $product['id']; ?>" class="btn-print-barcode" target="_blank" style="margin-right:7px;">
                                                <i class="fas fa-barcode"></i> طباعة باركود
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="empty-message">لا توجد منتجات مضافة حتى الآن</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/script.js"></script>
</body>
</html>
