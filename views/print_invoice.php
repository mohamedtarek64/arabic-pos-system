<?php
include '../app/db.php';

$invoice_id = $_GET['invoice_id'];  // تحديد معرف الفاتورة

$sql = "SELECT * FROM invoices WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$invoice_id]);
$invoice = $stmt->fetch(PDO::FETCH_ASSOC);

// جلب تفاصيل المنتجات في الفاتورة
$sql = "SELECT * FROM invoice_items WHERE invoice_id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$invoice_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <style>
        .invoice-container {
            width: 80%;
            margin: 0 auto;
            border: 1px solid #000;
            padding: 20px;
            font-family: Arial, sans-serif;
        }
        .invoice-header {
            text-align: center;
            font-size: 24px;
            margin-bottom: 20px;
        }
        .invoice-details {
            margin-bottom: 20px;
        }
        .invoice-items th, .invoice-items td {
            padding: 10px;
            border: 1px solid #000;
            text-align: center;
        }
        .total {
            text-align: right;
            font-size: 18px;
            margin-top: 20px;
        }
    </style>
</head>
<body>

<div class="invoice-container">
    <div class="invoice-header">
        <h2>فاتورة البيع</h2>
        <p>رقم الفاتورة: <?= $invoice['id'] ?></p>
    </div>
    <div class="invoice-details">
        <p>اسم العميل: <?= $invoice['customer_name'] ?></p>
        <p>التاريخ: <?= $invoice['date'] ?></p>
    </div>
    <table class="invoice-items">
        <thead>
            <tr>
                <th>اسم المنتج</th>
                <th>الكمية</th>
                <th>السعر</th>
                <th>المجموع</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $total = 0;
            foreach ($items as $item) {
                $item_total = $item['quantity'] * $item['price'];
                $total += $item_total;
            ?>
                <tr>
                    <td><?= $item['product_name'] ?></td>
                    <td><?= $item['quantity'] ?></td>
                    <td><?= $item['price'] ?> ج</td>
                    <td><?= $item_total ?> ج</td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
    <div class="total">
        <p><strong>الإجمالي: <?= $total ?> ج</strong></p>
    </div>
</div>

</body>
</html>
