<?php
// Simple script to add a test product to the database
include '../app/db.php';

if (isset($_POST['add_test_product']) || isset($_GET['auto'])) {
    try {
        // Check if test product already exists
        $check_sql = "SELECT * FROM products WHERE code = 'TEST001'";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->execute();
        $existing = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            // Update existing test product
            $sql = "UPDATE products SET quantity = quantity + 10 WHERE code = 'TEST001'";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            echo "تم تحديث المنتج التجريبي بنجاح!";
        } else {
            // Add new test product
            $sql = "INSERT INTO products (name, code, price, quantity, category) 
                   VALUES ('منتج تجريبي', 'TEST001', 150.00, 20, 'تجريبي')";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            echo "تم إضافة المنتج التجريبي بنجاح!";
        }
        
        // Add a few more test products with different prices
        $test_products = [
            ['منتج تجريبي 2', 'TEST002', 75.50, 15, 'تجريبي'],
            ['منتج تجريبي 3', 'TEST003', 220.00, 8, 'تجريبي'],
            ['منتج تجريبي 4', 'TEST004', 45.25, 30, 'تجريبي']
        ];
        
        foreach ($test_products as $product) {
            $check_sql = "SELECT * FROM products WHERE code = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->execute([$product[1]]);
            $existing = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$existing) {
                $sql = "INSERT INTO products (name, code, price, quantity, category) 
                       VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->execute($product);
            }
        }
        
        // Redirect back to cashier page
        header("Location: cashier.php");
        exit();
    } catch (PDOException $e) {
        echo "حدث خطأ أثناء إضافة المنتج التجريبي: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إضافة منتج تجريبي</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Cairo', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 20px;
            text-align: center;
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2196f3;
            margin-bottom: 30px;
        }
        .btn {
            background-color: #2196f3;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #0d8aee;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #6c757d;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>إضافة منتج تجريبي</h1>
        <p>انقر على الزر أدناه لإضافة منتجات تجريبية إلى قاعدة البيانات لاختبار الكاشير</p>
        
        <form action="" method="POST">
            <button type="submit" name="add_test_product" class="btn">إضافة منتجات تجريبية</button>
        </form>
        
        <a href="cashier.php" class="back-link">العودة إلى الكاشير</a>
    </div>
</body>
</html> 