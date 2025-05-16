<?php
include 'db.php'; // الاتصال بقاعدة البيانات

// التحقق من بيانات المنتج
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_code = $_POST['product_code'];
    $quantity = $_POST['quantity'];

    // استعلام للتحقق من وجود المنتج في قاعدة البيانات
    $sql = "SELECT * FROM products WHERE code = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$product_code]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        // تحديث الكمية في المخزون
        $new_quantity = $product['quantity'] - $quantity;

        if ($new_quantity < 0) {
            echo "الكمية المطلوبة غير متوفرة";
        } else {
            // تحديث الكمية في قاعدة البيانات
            $update_sql = "UPDATE products SET quantity = ? WHERE code = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->execute([$new_quantity, $product_code]);

            // حساب إجمالي السعر
            $total = $product['price'] * $quantity;

            // هنا يمكنك إضافة منطق لتخزين العملية في الفاتورة
            // يمكنك تخزين الفاتورة في جدول الفواتير هنا في قاعدة البيانات

            echo "تم إضافة المنتج إلى الفاتورة. الإجمالي: " . $total . " ج";
        }
    } else {
        echo "المنتج غير موجود";
    }

    // إعادة التوجيه إلى صفحة الكاشير بعد معالجة البيانات
    
}
?>
