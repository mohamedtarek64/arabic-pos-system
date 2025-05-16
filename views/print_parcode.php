<?php
require __DIR__ . '/../vendor/autoload.php';
use Picqer\Barcode\BarcodeGeneratorPNG;

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$conn = new mysqli("localhost", "root", "", "store");
$result = $conn->query("SELECT * FROM products WHERE id = $id");

if (!$result || $result->num_rows == 0) {
    die("المنتج غير موجود.");
}

$product = $result->fetch_assoc();
$code = $product['barcode'];
$name = $product['name'];

$generator = new BarcodeGeneratorPNG();
$barcodeImage = base64_encode($generator->getBarcode($code, $generator::TYPE_CODE_128));
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>باركود المنتج</title>
    <style>
        @media print {
            @page {
                size: 40mm 30mm;
                margin: 0;
            }
            body, html {
                width: 40mm;
                height: 30mm;
                margin: 0;
                padding: 0;
                background: #fff !important;
            }
            .print-btn, .not-print, header, footer {
                display: none !important;
            }
            .barcode-label {
                margin: 0 !important;
                border: none;
                box-shadow: none;
                page-break-after: avoid;
            }
        }
        body {
            direction: rtl;
            font-family: Arial, sans-serif;
            background: #fff;
        }
        .barcode-label {
            width: 40mm;
            height: 30mm;
            border: 1px solid #000;
            text-align: center;
            padding: 2mm;
            margin: 20px auto 0 auto;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .barcode-label img {
            width: 100%;
            height: auto;
        }
        .barcode-label strong {
            font-size: 13px;
            margin-bottom: 2mm;
        }
        .barcode-label small {
            font-size: 10px;
        }
        .print-btn {
            display: block;
            margin: 15px auto;
        }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">طباعة</button>
    <div class="barcode-label">
        <strong><?= htmlspecialchars($name) ?></strong>
        <img src="data:image/png;base64,<?= $barcodeImage ?>" alt="barcode">
        <small><?= htmlspecialchars($code) ?></small>
    </div>
</body>
</html>