<?php
include '../app/db.php'; // الاتصال بقاعدة البيانات

// إضافة منتج جديد إلى المخزون
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    $product_name = $_POST['product_name'];
    $product_code = $_POST['product_code'];
    $quantity = $_POST['quantity'];
    $price = $_POST['price'];

    // التحقق إذا كان المنتج موجودًا مسبقًا
    $sql_check = "SELECT * FROM products WHERE code = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->execute([$product_code]);
    $existing_product = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if ($existing_product) {
        echo "المنتج موجود بالفعل في المخزون.";
    } else {
        // إدخال المنتج الجديد في قاعدة البيانات
        $sql = "INSERT INTO products (name, code, quantity, price) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$product_name, $product_code, $quantity, $price]);
        echo "تم إضافة المنتج بنجاح إلى المخزون.";
    }
}

// تحديث الكمية في المخزون
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_quantity'])) {
    $product_code = $_POST['product_code'];
    $new_quantity = $_POST['new_quantity'];

    // التحقق من وجود المنتج في المخزون
    $sql = "SELECT * FROM products WHERE code = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$product_code]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        // تحديث الكمية في المخزون
        $sql_update = "UPDATE products SET quantity = ? WHERE code = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->execute([$new_quantity, $product_code]);
        echo "تم تحديث الكمية بنجاح.";
    } else {
        echo "المنتج غير موجود في المخزون.";
    }
}

// حذف منتج من المخزون
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_product'])) {
    $product_code = $_POST['product_code'];

    // حذف المنتج من قاعدة البيانات
    $sql = "DELETE FROM products WHERE code = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$product_code]);
    echo "تم حذف المنتج بنجاح من المخزون.";
}

// استرجاع كافة المنتجات في المخزون
$sql = "SELECT * FROM products";
$stmt = $conn->prepare($sql);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المخزون</title>
    <link rel="stylesheet" href="index.css">
</head>
<body>
    <div class="container">
        <h2>إدارة المخزون</h2>

        
        <form method="POST" action="stocks.php">
            <h3>إضافة منتج جديد</h3>
            <label for="product_name">اسم المنتج:</label>
            <input type="text" id="product_name" name="product_name" required><br>

            <label for="product_code">كود المنتج:</label>
            <input type="text" id="product_code" name="product_code" required><br>

            <label for="quantity">الكمية:</label>
            <input type="number" id="quantity" name="quantity" required><br>

            <label for="price">السعر:</label>
            <input type="number" id="price" name="price" required><br>

            <button type="submit" name="add_product">إضافة المنتج</button>
        </form>

        
        <form method="POST" action="stocks.php">
            <h3>تحديث الكمية</h3>
            <label for="product_code">كود المنتج:</label>
            <input type="text" id="product_code" name="product_code" required><br>

            <label for="new_quantity">الكمية الجديدة:</label>
            <input type="number" id="new_quantity" name="new_quantity" required><br>

            <button type="submit" name="update_quantity">تحديث الكمية</button>
        </form>

        
        <form method="POST" action="stocks.php">
            <h3>حذف منتج</h3>
            <label for="product_code">كود المنتج:</label>
            <input type="text" id="product_code" name="product_code" required><br>

            <button type="submit" name="delete_product">حذف المنتج</button>
        </form>

        <hr>

        
        <h3>المنتجات في المخزون</h3>
        <table>
            <thead>
                <tr>
                    <th>اسم المنتج</th>
                    <th>كود المنتج</th>
                    <th>الكمية</th>
                    <th>السعر</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($products) {
                    foreach ($products as $product) {
                        echo "<tr>
                                <td>{$product['name']}</td>
                                <td>{$product['code']}</td>
                                <td>{$product['quantity']}</td>
                                <td>{$product['price']} ج</td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='4'>لا توجد منتجات في المخزون</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>
