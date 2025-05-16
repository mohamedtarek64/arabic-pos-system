<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم الرئيسية - نسخة بسيطة</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f9;
            direction: rtl;
            margin: 0;
            padding: 20px;
        }
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 20px;
        }
        h1 {
            color: #1976d2;
            text-align: center;
        }
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-top: 20px;
        }
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 20px;
            text-align: center;
        }
        .card-title {
            font-size: 18px;
            color: #666;
            margin-bottom: 10px;
        }
        .card-value {
            font-size: 32px;
            font-weight: bold;
            color: #1976d2;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <h1>لوحة التحكم الرئيسية (نسخة تجريبية)</h1>
        
        <div class="dashboard-grid">
            <div class="card">
                <div class="card-title">إجمالي المبيعات</div>
                <div class="card-value">0.00 ر.س</div>
            </div>
            
            <div class="card">
                <div class="card-title">عدد عمليات البيع</div>
                <div class="card-value">0</div>
            </div>
            
            <div class="card">
                <div class="card-title">صافي الدخل</div>
                <div class="card-value">0.00 ر.س</div>
            </div>
            
            <div class="card">
                <div class="card-title">الأقساط المدفوعة</div>
                <div class="card-value">0.00 ر.س</div>
            </div>
        </div>
        
        <div style="margin-top: 30px; text-align: center;">
            <p>هذه نسخة تجريبية بسيطة للتأكد من عمل PHP والمتصفح بشكل صحيح.</p>
        </div>
    </div>
</body>
</html> 