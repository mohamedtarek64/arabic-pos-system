<?php
include '../app/db.php';

// التحقق من وجود معرّف العميل
$customer_id = $_GET['customer_id'] ?? null;

if (!$customer_id) {
    echo "العميل غير موجود";
    exit();
}

// جلب بيانات العميل
$sql = "SELECT * FROM customers WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$customer_id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    echo "العميل غير موجود";
    exit();
}

// جلب جميع الأقساط للعميل
$installments_sql = "SELECT * FROM installments WHERE customer_id = ? ORDER BY due_date ASC";
$installments_stmt = $conn->prepare($installments_sql);
$installments_stmt->execute([$customer_id]);
$installments = $installments_stmt->fetchAll(PDO::FETCH_ASSOC);

// حساب إجمالي المبالغ المدفوعة والمتبقية
$total_amount = 0;
$paid_amount = 0;
$remaining_amount = 0;

foreach ($installments as $installment) {
    $total_amount += $installment['amount'];
    if ($installment['paid'] == 1) {
        $paid_amount += $installment['amount'];
    } else {
        $remaining_amount += $installment['amount'];
    }
}

// حساب عدد الأقساط المدفوعة والمتبقية
$total_installments = count($installments);
$paid_installments = 0;
$late_installments = 0;
$upcoming_installments = 0;
$today = date('Y-m-d');

foreach ($installments as $installment) {
    if ($installment['paid'] == 1) {
        $paid_installments++;
    } else {
        if ($installment['due_date'] < $today) {
            $late_installments++;
        } else {
            $upcoming_installments++;
        }
    }
}

// حساب النسبة المئوية للسداد
$payment_percentage = $total_installments > 0 ? ($paid_installments / $total_installments) * 100 : 0;
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>طباعة الأقساط - <?php echo htmlspecialchars($customer['name']); ?></title>
    <style>
        @media print {
            @page {
                size: A4;
                margin: 1cm;
            }
        }
        
        body {
            font-family: 'Arial', 'Segoe UI', 'Tahoma', sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
            background: white;
        }
        
        .print-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #36d03b;
            padding-bottom: 15px;
        }
        
        .store-name {
            font-size: 24px;
            font-weight: bold;
            margin: 0;
            color: #36d03b;
        }
        
        .print-date {
            font-size: 12px;
            color: #777;
            margin: 5px 0;
        }
        
        .print-title {
            font-size: 18px;
            margin: 10px 0;
        }
        
        .customer-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            border: 1px solid #eee;
            padding: 15px;
            border-radius: 5px;
        }
        
        .customer-details, .account-summary {
            flex: 1;
        }
        
        .info-group {
            margin-bottom: 10px;
        }
        
        .info-label {
            font-weight: bold;
            margin-left: 5px;
            color: #555;
        }
        
        .info-value {
            display: inline;
        }
        
        .highlight {
            color: #36d03b;
            font-weight: bold;
        }
        
        .installments-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .installments-table th {
            background-color: #f5f5f5;
            padding: 10px;
            text-align: right;
            border: 1px solid #ddd;
        }
        
        .installments-table td {
            padding: 10px;
            border: 1px solid #ddd;
        }
        
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-paid {
            background-color: #e3f7e4;
            color: #1a9c1f;
        }
        
        .status-unpaid {
            background-color: #f8f9fa;
            color: #6c757d;
        }
        
        .status-late {
            background-color: #ffebee;
            color: #d32f2f;
        }
        
        .summary-section {
            margin-top: 30px;
            border-top: 1px dashed #ddd;
            padding-top: 20px;
        }
        
        .summary-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #36d03b;
        }
        
        .summary-grid {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -10px;
        }
        
        .summary-item {
            flex: 1;
            min-width: 200px;
            margin: 10px;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 5px;
            text-align: center;
        }
        
        .summary-value {
            font-size: 20px;
            font-weight: bold;
            margin: 5px 0;
        }
        
        .summary-label {
            font-size: 14px;
            color: #777;
        }
        
        .print-footer {
            margin-top: 50px;
            text-align: center;
            font-size: 12px;
            color: #777;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }
        
        .no-print {
            margin-bottom: 20px;
        }
        
        @media print {
            .no-print {
                display: none;
            }
        }
        
        .print-btn {
            background-color: #36d03b;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .print-btn:hover {
            background-color: #1a9c1f;
        }
        
        .back-btn {
            background-color: #f5f5f5;
            color: #333;
            border: 1px solid #ddd;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
        }
        
        .back-btn:hover {
            background-color: #e5e5e5;
        }
        
        .progress-bar-container {
            height: 8px;
            background-color: #f5f5f5;
            border-radius: 4px;
            margin-top: 10px;
            position: relative;
            overflow: hidden;
            width: 100%;
        }
        
        .progress-bar {
            height: 100%;
            background: linear-gradient(135deg, #36d03b, #1a9c1f);
            border-radius: 4px;
        }
        
        .progress-text {
            position: absolute;
            top: -3px;
            right: 5px;
            font-size: 10px;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="no-print">
        <a href="installments_detail.php?customer_id=<?php echo $customer_id; ?>" class="back-btn">العودة للتفاصيل</a>
        <button onclick="window.print()" class="print-btn">طباعة</button>
    </div>
    
    <div class="print-header">
        <h1 class="store-name">متجر الإلكترونيات</h1>
        <div class="print-date">تاريخ الطباعة: <?php echo date('d/m/Y H:i'); ?></div>
        <h2 class="print-title">كشف حساب الأقساط</h2>
    </div>
    
    <div class="customer-info">
        <div class="customer-details">
            <div class="info-group">
                <span class="info-label">اسم العميل:</span>
                <span class="info-value"><?php echo htmlspecialchars($customer['name']); ?></span>
            </div>
            <div class="info-group">
                <span class="info-label">رقم العميل:</span>
                <span class="info-value"><?php echo $customer['id']; ?></span>
            </div>
            <div class="info-group">
                <span class="info-label">رقم الهاتف:</span>
                <span class="info-value"><?php echo htmlspecialchars($customer['phone'] ?? 'غير متوفر'); ?></span>
            </div>
            <div class="info-group">
                <span class="info-label">العنوان:</span>
                <span class="info-value"><?php echo htmlspecialchars($customer['address'] ?? 'غير متوفر'); ?></span>
            </div>
        </div>
        
        <div class="account-summary">
            <div class="info-group">
                <span class="info-label">قيمة الحساب الكلية:</span>
                <span class="info-value highlight"><?php echo number_format($customer['account_price'] ?? 0, 2); ?> جنيه</span>
            </div>
            <div class="info-group">
                <span class="info-label">عدد الأقساط:</span>
                <span class="info-value"><?php echo $total_installments; ?> قسط</span>
            </div>
            <div class="info-group">
                <span class="info-label">الأقساط المدفوعة:</span>
                <span class="info-value"><?php echo $paid_installments; ?> قسط</span>
            </div>
            <div class="info-group">
                <span class="info-label">نسبة السداد:</span>
                <span class="info-value"><?php echo round($payment_percentage); ?>%</span>
                <div class="progress-bar-container">
                    <div class="progress-bar" style="width: <?php echo $payment_percentage; ?>%"></div>
                </div>
            </div>
        </div>
    </div>
    
    <table class="installments-table">
        <thead>
            <tr>
                <th>رقم القسط</th>
                <th>تاريخ الاستحقاق</th>
                <th>المبلغ</th>
                <th>الحالة</th>
                <th>تاريخ الدفع</th>
                <th>الفائدة (%)</th>
                <th>ملاحظات</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $today = date('Y-m-d');
            foreach ($installments as $installment): 
                // تحديد حالة القسط
                $status = '';
                $status_class = '';
                
                if ($installment['paid'] == 1) {
                    $status = 'مدفوع';
                    $status_class = 'status-paid';
                } else {
                    if ($installment['due_date'] < $today) {
                        $status = 'متأخر';
                        $status_class = 'status-late';
                    } else {
                        $status = 'غير مدفوع';
                        $status_class = 'status-unpaid';
                    }
                }
            ?>
            <tr>
                <td><?php echo $installment['id']; ?></td>
                <td><?php echo date('d/m/Y', strtotime($installment['due_date'])); ?></td>
                <td><?php echo number_format($installment['amount'], 2); ?> جنيه</td>
                <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $status; ?></span></td>
                <td>
                    <?php 
                    if ($installment['paid'] == 1 && isset($installment['payment_date'])) {
                        echo date('d/m/Y', strtotime($installment['payment_date']));
                    } else {
                        echo '-';
                    }
                    ?>
                </td>
                <td><?php echo $installment['interest_rate'] ? $installment['interest_rate'] . '%' : '-'; ?></td>
                <td><?php echo htmlspecialchars($installment['notes'] ?? ''); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div class="summary-section">
        <h3 class="summary-title">ملخص الحساب</h3>
        <div class="summary-grid">
            <div class="summary-item">
                <div class="summary-value"><?php echo number_format($total_amount, 2); ?> جنيه</div>
                <div class="summary-label">إجمالي الأقساط</div>
            </div>
            <div class="summary-item">
                <div class="summary-value"><?php echo number_format($paid_amount, 2); ?> جنيه</div>
                <div class="summary-label">المبلغ المدفوع</div>
            </div>
            <div class="summary-item">
                <div class="summary-value"><?php echo number_format($remaining_amount, 2); ?> جنيه</div>
                <div class="summary-label">المبلغ المتبقي</div>
            </div>
            <div class="summary-item">
                <div class="summary-value"><?php echo $late_installments; ?></div>
                <div class="summary-label">الأقساط المتأخرة</div>
            </div>
        </div>
    </div>
    
    <div class="print-footer">
        <p>تم إنشاء هذا التقرير بواسطة نظام إدارة الأقساط - متجر الإلكترونيات</p>
        <p>جميع الحقوق محفوظة &copy; <?php echo date('Y'); ?></p>
    </div>
    
    <script>
        // Auto-print when the page loads
        window.onload = function() {
            // Delay printing slightly to ensure everything is rendered
            setTimeout(function() {
                // window.print();
            }, 500);
        };
    </script>
</body>
</html> 