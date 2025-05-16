<?php
include '../app/db.php';

$sql = "SELECT SUM(amount) AS total_sales FROM sales";
$stmt = $conn->prepare($sql);
$stmt->execute();
$total_sales = $stmt->fetch(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التقارير</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <div class="container">
        <h2>التقارير</h2>
        <p><strong>إجمالي المبيعات: </strong><?= $total_sales['total_sales']; ?> ج</p>
    </div>
</body>
</html>
