<?php
include '../app/db.php';

$id = isset($_GET['id']) ? $_GET['id'] : 0;

// Handle form submission for updating product
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $code = $_POST['code'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];
    $barcode = $_POST['barcode'];

    // تحديث المنتج
    $sql = "UPDATE products SET name = ?, code = ?, price = ?, quantity = ?, barcode = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$name, $code, $price, $quantity, $barcode, $id]);

    // Redirect to products page
    header("Location: products.php");
    exit();
}

// Get product details
$sql = "SELECT * FROM products WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

// If product not found, redirect to products page
if (!$product) {
    header("Location: products.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل المنتج</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .edit-product-container {
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            margin: 20px;
        }
        
        .edit-form-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            border: 1px solid #e3e6f0;
        }
        
        .edit-form-section h2 {
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
        
        .btn-update {
            background-color: #2196f3;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        
        .btn-update:hover {
            background-color: #0d8aee;
        }
        
        .btn-cancel {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-right: 10px;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s;
        }
        
        .btn-cancel:hover {
            background-color: #5a6268;
        }
        
        .buttons-container {
            margin-top: 20px;
        }
        
        
        .page-header {
            background-color: #fff;
            padding: 15px 20px;
            border-radius: 10px;
            margin: 20px 20px 20px 20px;
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
                justify-content: flex-end;
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
                        <li class="breadcrumb-item"><a href="products.php">المنتجات</a></li>
                        <li class="breadcrumb-item active">تعديل المنتج</li>
                    </ol>
                </nav>
                <div class="quick-actions">
                    <a href="products.php" class="btn-cancel">
                        <i class="fas fa-arrow-right"></i> العودة للمنتجات
                    </a>
                </div>
            </div>
            
            <div class="edit-product-container">
                <div class="edit-form-section">
                    <h2><i class="fas fa-edit"></i> تعديل بيانات المنتج: <?php echo htmlspecialchars($product['name']); ?></h2>
                    <form action="edit_product.php?id=<?php echo $id; ?>" method="POST">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="name">اسم المنتج:</label>
                                <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="code">كود المنتج:</label>
                                <input type="text" id="code" name="code" class="form-control" value="<?php echo htmlspecialchars($product['code']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="price">السعر:</label>
                                <input type="number" id="price" name="price" step="0.01" class="form-control" value="<?php echo $product['price']; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="quantity">الكمية:</label>
                                <input type="number" id="quantity" name="quantity" class="form-control" value="<?php echo $product['quantity']; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="barcode">الباركود:</label>
                                <input type="text" id="barcode" name="barcode" class="form-control" value="<?php echo htmlspecialchars($product['barcode']); ?>">
                            </div>
                        </div>
                        
                        <div class="buttons-container">
                            <button type="submit" class="btn-update">
                                <i class="fas fa-save"></i> حفظ التغييرات
                            </button>
                            <a href="products.php" class="btn-cancel">
                                <i class="fas fa-times"></i> إلغاء
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/script.js"></script>
</body>
</html> 