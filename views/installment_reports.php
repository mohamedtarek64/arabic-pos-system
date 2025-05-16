<?php
session_start();
include '../app/db.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// تحديد الفترة الزمنية المطلوبة
$period = $_GET['period'] ?? 'month';
$start_date = '';
$end_date = date('Y-m-d');

switch ($period) {
    case 'week':
        $start_date = date('Y-m-d', strtotime('-1 week'));
        $period_name = 'الأسبوع الأخير';
        break;
    case 'month':
        $start_date = date('Y-m-d', strtotime('-1 month'));
        $period_name = 'الشهر الأخير';
        break;
    case 'quarter':
        $start_date = date('Y-m-d', strtotime('-3 months'));
        $period_name = 'الربع الأخير';
        break;
    case 'year':
        $start_date = date('Y-m-d', strtotime('-1 year'));
        $period_name = 'السنة الأخيرة';
        break;
    case 'custom':
        $start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-1 month'));
        $end_date = $_GET['end_date'] ?? date('Y-m-d');
        $period_name = 'فترة مخصصة';
        break;
}

// الإحصائيات العامة
$stats = [
    'total_paid' => 0,
    'total_due' => 0,
    'total_late' => 0,
    'paid_amount' => 0,
    'due_amount' => 0,
    'late_amount' => 0,
    'collection_rate' => 0
];

// استعلام للأقساط المدفوعة خلال الفترة
$paid_sql = "SELECT COUNT(*) as count, SUM(amount) as amount FROM installments 
             WHERE paid = 1 AND payment_date BETWEEN ? AND ?";
$paid_stmt = $conn->prepare($paid_sql);
$paid_stmt->execute([$start_date, $end_date]);
$paid_result = $paid_stmt->fetch(PDO::FETCH_ASSOC);

// استعلام للأقساط المستحقة خلال الفترة
$due_sql = "SELECT COUNT(*) as count, SUM(amount) as amount FROM installments 
            WHERE due_date BETWEEN ? AND ?";
$due_stmt = $conn->prepare($due_sql);
$due_stmt->execute([$start_date, $end_date]);
$due_result = $due_stmt->fetch(PDO::FETCH_ASSOC);

// استعلام للأقساط المتأخرة
$late_sql = "SELECT COUNT(*) as count, SUM(amount) as amount FROM installments 
             WHERE paid = 0 AND due_date < ?";
$late_stmt = $conn->prepare($late_sql);
$late_stmt->execute([date('Y-m-d')]);
$late_result = $late_stmt->fetch(PDO::FETCH_ASSOC);

// تعبئة الإحصائيات
$stats['total_paid'] = $paid_result['count'] ?? 0;
$stats['total_due'] = $due_result['count'] ?? 0;
$stats['total_late'] = $late_result['count'] ?? 0;
$stats['paid_amount'] = $paid_result['amount'] ?? 0;
$stats['due_amount'] = $due_result['amount'] ?? 0;
$stats['late_amount'] = $late_result['amount'] ?? 0;

// حساب معدل التحصيل (الأقساط المدفوعة / الأقساط المستحقة)
if ($stats['due_amount'] > 0) {
    $stats['collection_rate'] = ($stats['paid_amount'] / $stats['due_amount']) * 100;
}

// الحصول على بيانات الأقساط المدفوعة حسب الأيام للرسم البياني
$chart_sql = "SELECT DATE(payment_date) as date, SUM(amount) as amount 
              FROM installments 
              WHERE paid = 1 AND payment_date BETWEEN ? AND ? 
              GROUP BY DATE(payment_date) 
              ORDER BY date ASC";
$chart_stmt = $conn->prepare($chart_sql);
$chart_stmt->execute([$start_date, $end_date]);
$chart_data = $chart_stmt->fetchAll(PDO::FETCH_ASSOC);

// تحويل البيانات إلى تنسيق مناسب للرسم البياني
$chart_dates = [];
$chart_amounts = [];

foreach ($chart_data as $data) {
    $chart_dates[] = date('d/m', strtotime($data['date']));
    $chart_amounts[] = $data['amount'];
}

// تحويل البيانات إلى JSON للاستخدام في JavaScript
$chart_dates_json = json_encode($chart_dates);
$chart_amounts_json = json_encode($chart_amounts);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقارير الأقساط</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #36d03b;
            --primary-dark: #1a9c1f;
            --secondary-color: #2196f3;
            --secondary-dark: #1976d2;
            --text-color: #1a2b3c;
            --text-light: #7b8a9a;
            --bg-color: #f5f7fa;
            --card-bg: #fff;
            --border-color: #e3eafc;
            --green-light: #e3f7e4;
            --shadow-color: rgba(0,0,0,0.1);
            --danger-color: #dc3545;
            --danger-light: #f8d7da;
            --warning-color: #ffc107;
            --warning-light: #fff3cd;
            --success-color: #36d03b;
            --header-gradient: linear-gradient(90deg, #36d03b, #2196f3);
        }
        
        .dark-mode {
            --primary-color: #4caf50;
            --primary-dark: #388e3c;
            --secondary-color: #42a5f5;
            --secondary-dark: #1976d2;
            --text-color: #e4e6f1;
            --text-light: #a4abc8;
            --bg-color: #121212;
            --card-bg: #1e1e1e;
            --border-color: #333;
            --green-light: rgba(76, 175, 80, 0.15);
            --danger-color: #f44336;
            --danger-light: rgba(244, 67, 54, 0.15);
            --warning-color: #ffeb3b;
            --warning-light: rgba(255, 235, 59, 0.15);
            --success-color: #4caf50;
            --shadow-color: rgba(0,0,0,0.3);
            --header-gradient: linear-gradient(90deg, #388e3c, #1976d2);
        }
        
        body, html {
            background-color: var(--bg-color);
            direction: rtl;
            font-family: 'Cairo', 'Segoe UI', Arial, sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            color: var(--text-color);
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        .layout-wrapper {
            display: flex;
            min-height: 100vh;
            width: 100%;
            position: relative;
        }
        
        .main-content-area {
            flex: 1;
            padding: 20px;
            margin-right: 280px;
            min-height: 100vh;
            z-index: 5;
            position: relative;
            transition: all 0.3s ease;
        }
        
        @media (max-width: 991px) {
            .main-content-area {
                margin-right: 0;
                width: 100%;
            }
        }
        
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            background: var(--card-bg);
            padding: 15px 20px;
            border-radius: 15px;
            box-shadow: 0 4px 15px var(--shadow-color);
        }
        
        .page-title {
            font-size: 1.8em;
            font-weight: 700;
            color: var(--secondary-color);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .period-filter {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .period-btn {
            padding: 8px 15px;
            border-radius: 8px;
            background-color: var(--bg-color);
            border: 1px solid var(--border-color);
            color: var(--text-color);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .period-btn.active {
            background-color: var(--secondary-color);
            color: white;
            border-color: var(--secondary-color);
        }
        
        .period-btn:hover:not(.active) {
            background-color: var(--border-color);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 4px 15px var(--shadow-color);
            padding: 20px;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100%;
            height: 5px;
        }
        
        .stat-card.paid::before {
            background: var(--success-color);
        }
        
        .stat-card.due::before {
            background: var(--warning-color);
        }
        
        .stat-card.late::before {
            background: var(--danger-color);
        }
        
        .stat-card.rate::before {
            background: var(--secondary-color);
        }
        
        .stat-title {
            font-size: 1em;
            color: var(--text-light);
            margin-bottom: 15px;
        }
        
        .stat-value {
            font-size: 2em;
            font-weight: 700;
        }
        
        .stat-card.paid .stat-value {
            color: var(--success-color);
        }
        
        .stat-card.due .stat-value {
            color: var(--warning-color);
        }
        
        .stat-card.late .stat-value {
            color: var(--danger-color);
        }
        
        .stat-card.rate .stat-value {
            color: var(--secondary-color);
        }
        
        .stat-subtitle {
            font-size: 0.9em;
            color: var(--text-light);
            margin-top: 5px;
        }
        
        .chart-container {
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 4px 15px var(--shadow-color);
            margin-bottom: 25px;
            padding: 0;
            position: relative;
            overflow: hidden;
        }
        
        .chart-container::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100%;
            height: 5px;
            background: var(--secondary-color);
        }
        
        .chart-header {
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .chart-title {
            font-size: 1.6em;
            font-weight: 700;
            color: var(--text-color);
            margin: 0;
        }
        
        .chart-body {
            padding: 20px;
            height: 300px;
        }
        
        .empty-chart {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: var(--text-light);
        }
        
        .empty-chart i {
            font-size: 3em;
            margin-bottom: 15px;
            opacity: 0.3;
        }
        
        .custom-date-form {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 15px;
            padding: 15px;
            background-color: var(--bg-color);
            border-radius: 8px;
            display: none;
        }
        
        .custom-date-form.active {
            display: flex;
        }
        
        .date-input-group {
            display: flex;
            flex-direction: column;
        }
        
        .date-input-label {
            font-size: 0.85em;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .date-input {
            padding: 8px 15px;
            border-radius: 6px;
            border: 1px solid var(--border-color);
            background-color: var(--card-bg);
            color: var(--text-color);
        }
        
        .apply-btn {
            background-color: var(--secondary-color);
            color: white;
            border: none;
            border-radius: 6px;
            padding: 8px 15px;
            cursor: pointer;
            font-weight: 600;
            height: 38px;
            margin-top: 20px;
        }
        
        .apply-btn:hover {
            background-color: var(--secondary-dark);
        }
        
        .export-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background-color: var(--bg-color);
            color: var(--text-color);
            padding: 10px 15px;
            border-radius: 8px;
            font-weight: 600;
            border: 1px solid var(--border-color);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .export-btn:hover {
            background-color: var(--border-color);
        }
        
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .period-filter {
                flex-wrap: wrap;
            }
            
            .custom-date-form {
                flex-direction: column;
                align-items: stretch;
            }
            
            .apply-btn {
                margin-top: 10px;
            }
        }
    </style>
</head>

<body>
    <button class="menu-toggle">☰</button>
    <div class="layout-wrapper">
        <?php include '../app/includes/sidebar.php'; ?>
        
        <div class="main-content-area">
            
            <div class="breadcrumb">
                <div class="breadcrumb-item">
                    <i class="fas fa-home"></i>
                    <a href="../app/main.php">الرئيسية</a>
                </div>
                <div class="breadcrumb-item">
                    <i class="fas fa-calculator"></i>
                    <a href="installments.php">الأقساط</a>
                </div>
                <div class="breadcrumb-item active">
                    <i class="fas fa-chart-line"></i>
                    تقارير الأقساط
                </div>
            </div>
            
            
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-chart-line"></i>
                    تقارير الأقساط
                </h1>
                
                <div class="header-actions">
                    <button class="export-btn" id="exportReportBtn">
                        <i class="fas fa-file-export"></i>
                        تصدير التقرير
                    </button>
                </div>
            </div>
            
            
            <div class="period-filter">
                <a href="?period=week" class="period-btn <?php echo $period == 'week' ? 'active' : ''; ?>">أسبوع</a>
                <a href="?period=month" class="period-btn <?php echo $period == 'month' ? 'active' : ''; ?>">شهر</a>
                <a href="?period=quarter" class="period-btn <?php echo $period == 'quarter' ? 'active' : ''; ?>">ربع سنة</a>
                <a href="?period=year" class="period-btn <?php echo $period == 'year' ? 'active' : ''; ?>">سنة</a>
                <button id="customPeriodBtn" class="period-btn <?php echo $period == 'custom' ? 'active' : ''; ?>">فترة مخصصة</button>
            </div>
            
            
            <div id="customDateForm" class="custom-date-form <?php echo $period == 'custom' ? 'active' : ''; ?>">
                <div class="date-input-group">
                    <label class="date-input-label">من تاريخ</label>
                    <input type="date" id="startDate" name="start_date" class="date-input" value="<?php echo $start_date; ?>">
                </div>
                <div class="date-input-group">
                    <label class="date-input-label">إلى تاريخ</label>
                    <input type="date" id="endDate" name="end_date" class="date-input" value="<?php echo $end_date; ?>">
                </div>
                <button id="applyCustomDate" class="apply-btn">تطبيق</button>
            </div>
            
            
            <div class="stats-grid">
                <div class="stat-card paid">
                    <div class="stat-title">الأقساط المدفوعة</div>
                    <div class="stat-value"><?php echo number_format($stats['total_paid']); ?></div>
                    <div class="stat-subtitle">إجمالي: <?php echo number_format($stats['paid_amount'], 2); ?> جنيه</div>
                </div>
                <div class="stat-card due">
                    <div class="stat-title">الأقساط المستحقة</div>
                    <div class="stat-value"><?php echo number_format($stats['total_due']); ?></div>
                    <div class="stat-subtitle">إجمالي: <?php echo number_format($stats['due_amount'], 2); ?> جنيه</div>
                </div>
                <div class="stat-card late">
                    <div class="stat-title">الأقساط المتأخرة</div>
                    <div class="stat-value"><?php echo number_format($stats['total_late']); ?></div>
                    <div class="stat-subtitle">إجمالي: <?php echo number_format($stats['late_amount'], 2); ?> جنيه</div>
                </div>
                <div class="stat-card rate">
                    <div class="stat-title">معدل التحصيل</div>
                    <div class="stat-value"><?php echo number_format($stats['collection_rate'], 1); ?>%</div>
                    <div class="stat-subtitle">خلال <?php echo $period_name; ?></div>
                </div>
            </div>
            
            
            <div class="chart-container">
                <div class="chart-header">
                    <h2 class="chart-title">الأقساط المدفوعة خلال <?php echo $period_name; ?></h2>
                </div>
                <div class="chart-body">
                    <?php if (empty($chart_data)): ?>
                        <div class="empty-chart">
                            <i class="fas fa-chart-line"></i>
                            <p>لا توجد بيانات متاحة للفترة المحددة</p>
                        </div>
                    <?php else: ?>
                        <canvas id="paymentsChart"></canvas>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // تبديل وضع الثيم الداكن/الفاتح
            const isDarkMode = localStorage.getItem('darkMode') === 'true';
            if (isDarkMode) {
                document.body.classList.add('dark-mode');
            }
            
            // إظهار/إخفاء نموذج الفترة المخصصة
            const customPeriodBtn = document.getElementById('customPeriodBtn');
            const customDateForm = document.getElementById('customDateForm');
            
            customPeriodBtn.addEventListener('click', function() {
                customDateForm.classList.toggle('active');
            });
            
            // تطبيق الفترة المخصصة
            const applyCustomDate = document.getElementById('applyCustomDate');
            const startDate = document.getElementById('startDate');
            const endDate = document.getElementById('endDate');
            
            applyCustomDate.addEventListener('click', function() {
                if (startDate.value && endDate.value) {
                    window.location.href = `?period=custom&start_date=${startDate.value}&end_date=${endDate.value}`;
                } else {
                    alert('يرجى تحديد تاريخ البداية والنهاية');
                }
            });
            
            // رسم البياني إذا كانت هناك بيانات
            <?php if (!empty($chart_data)): ?>
            const ctx = document.getElementById('paymentsChart').getContext('2d');
            
            // تعيين ألوان متوافقة مع الثيم
            const chartColor = isDarkMode ? '#42a5f5' : '#2196f3';
            const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
            const textColor = isDarkMode ? '#e4e6f1' : '#1a2b3c';
            
            const paymentsChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo $chart_dates_json; ?>,
                    datasets: [{
                        label: 'المبلغ المحصل',
                        data: <?php echo $chart_amounts_json; ?>,
                        backgroundColor: 'rgba(33, 150, 243, 0.2)',
                        borderColor: chartColor,
                        borderWidth: 2,
                        pointBackgroundColor: chartColor,
                        pointRadius: 4,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                color: textColor,
                                font: {
                                    family: "'Cairo', 'Segoe UI', Arial, sans-serif",
                                    size: 14
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + context.parsed.y.toLocaleString() + ' جنيه';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                color: gridColor
                            },
                            ticks: {
                                color: textColor,
                                font: {
                                    family: "'Cairo', 'Segoe UI', Arial, sans-serif"
                                }
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: gridColor
                            },
                            ticks: {
                                color: textColor,
                                font: {
                                    family: "'Cairo', 'Segoe UI', Arial, sans-serif"
                                },
                                callback: function(value) {
                                    return value.toLocaleString() + ' ج';
                                }
                            }
                        }
                    }
                }
            });
            <?php endif; ?>
            
            // تصدير التقرير
            document.getElementById('exportReportBtn').addEventListener('click', function() {
                alert('جاري تجهيز التقرير للتصدير... هذه الميزة قيد التطوير.');
            });
        });
    </script>
</body>
</html> 