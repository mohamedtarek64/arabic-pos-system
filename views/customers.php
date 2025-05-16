<?php
include '../app/db.php';

// إضافة عميل
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $account_price = $_POST['account_price'];  // إضافة السعر

    // التحقق من أن رقم الهاتف يحتوي فقط على أرقام
    if (!preg_match('/^[0-9]{11}$/', $phone)) {
        echo "رقم الهاتف يجب أن يتكون من 11 رقمًا.";
        exit();
    }

    // إدخال العميل في قاعدة البيانات
    $sql = "INSERT INTO customers (name, phone, address, account_price) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$name, $phone, $address, $account_price]);

    echo "تم إضافة العميل بنجاح";
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>العملاء</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    
</body>
</html>
    