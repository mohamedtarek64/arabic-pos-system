<?php
// Enable full error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering to catch any errors
ob_start();

// Function to log error information
function log_error($errno, $errstr, $errfile, $errline) {
    echo "<div style='background-color: #ffdddd; border: 1px solid #ff0000; padding: 10px; margin: 10px 0;'>";
    echo "<h3>PHP Error:</h3>";
    echo "<p><strong>Error Type:</strong> $errno</p>";
    echo "<p><strong>Error Message:</strong> $errstr</p>";
    echo "<p><strong>File:</strong> $errfile</p>";
    echo "<p><strong>Line:</strong> $errline</p>";
    echo "</div>";
    return true; // Don't execute PHP's error handler
}

// Set our custom error handler
set_error_handler("log_error");

try {
    // Check if required files exist
    echo "<div style='background-color: #eee; padding: 10px; margin: 10px 0;'>";
    echo "<h3>File Check:</h3>";
    
    $requiredFiles = [
        'includes/topbar.php',
        'includes/sidebar.php',
        'db.php',
        '../assets/css/styles.css'
    ];
    
    foreach($requiredFiles as $file) {
        if(file_exists($file)) {
            echo "<p><strong>$file:</strong> <span style='color:green'>Exists</span></p>";
        } else {
            echo "<p><strong>$file:</strong> <span style='color:red'>Missing</span></p>";
        }
    }
    echo "</div>";
    
    // Test database connection
    echo "<div style='background-color: #eee; padding: 10px; margin: 10px 0;'>";
    echo "<h3>Database Connection Test:</h3>";
    
    try {
        include_once 'db.php';
        echo "<p style='color:green'>Database connection successful!</p>";
    } catch (Exception $e) {
        echo "<p style='color:red'>Database Error: " . $e->getMessage() . "</p>";
    }
    echo "</div>";
    
    // Now include the actual main.php content but with try/catch blocks around includes
    ?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم الرئيسية (مع التصحيح)</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Debug Styles */
        .debug-info {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }
        .debug-section {
            margin-bottom: 15px;
        }
        .debug-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .success {
            color: green;
        }
        .error {
            color: red;
        }
        
        /* Main Dashboard Styles */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            grid-gap: 20px;
            margin-bottom: 30px;
        }
        
        .kpi-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
            grid-column: span 3;
        }
        
        .kpi-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.12);
        }
        
        .kpi-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: #1976d2;
        }
        
        .kpi-title {
            font-size: 1rem;
            color: #666;
            margin-bottom: 5px;
        }
        
        .kpi-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: #333;
        }
        
        .kpi-subtitle {
            font-size: 0.9rem;
            color: #888;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="debug-info">
        <div class="debug-section">
            <div class="debug-title">Including topbar.php:</div>
            <?php 
            try {
                include 'includes/topbar.php';
                echo "<span class='success'>Successfully included topbar.php</span>";
            } catch (Exception $e) {
                echo "<span class='error'>Error including topbar.php: " . $e->getMessage() . "</span>";
            }
            ?>
        </div>
    </div>
    
    <div class="main-container">
        <div class="debug-info">
            <div class="debug-section">
                <div class="debug-title">Including sidebar.php:</div>
                <?php 
                try {
                    include 'includes/sidebar.php';
                    echo "<span class='success'>Successfully included sidebar.php</span>";
                } catch (Exception $e) {
                    echo "<span class='error'>Error including sidebar.php: " . $e->getMessage() . "</span>";
                }
                ?>
            </div>
        </div>
        
        <div class="content">
            <div class="content-header">
                <h1>لوحة التحكم الرئيسية (وضع التصحيح)</h1>
            </div>
            
            <!-- Date Filter -->
            <div class="date-filter">
                <span>تصفية حسب التاريخ:</span>
                <input type="date" id="filter-date" onchange="filterByDate(this.value)">
                <button onclick="resetFilter()">إعادة تعيين</button>
            </div>
            
            <!-- KPI Cards -->
            <div class="dashboard-grid">
                <div class="kpi-card">
                    <div class="kpi-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="kpi-title">إجمالي المبيعات</div>
                    <div class="kpi-value" id="total-sales">0.00 ر.س</div>
                    <div class="kpi-subtitle">(المبيعات + الأقساط)</div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="kpi-title">عدد عمليات البيع</div>
                    <div class="kpi-value" id="sales-count">0</div>
                    <div class="kpi-subtitle">العام الحالي</div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-icon">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div class="kpi-title">صافي الدخل</div>
                    <div class="kpi-value" id="net-income">0.00 ر.س</div>
                    <div class="kpi-subtitle">الإيرادات</div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="kpi-title">الأقساط المدفوعة</div>
                    <div class="kpi-value" id="paid-installments">0.00 ر.س</div>
                    <div class="kpi-subtitle">إجمالي المدفوعات</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="debug-info">
        <div class="debug-section">
            <div class="debug-title">Testing dashboard-data.php API:</div>
            <div id="api-test-result">Testing API...</div>
        </div>
    </div>
    
    <script>
        // Test the dashboard-data.php API
        fetch('dashboard-data.php')
            .then(response => {
                document.getElementById('api-test-result').innerHTML = 
                    `API Response Status: <span class="${response.ok ? 'success' : 'error'}">${response.status} ${response.statusText}</span>`;
                return response.json();
            })
            .then(data => {
                console.log('API Response Data:', data);
                document.getElementById('api-test-result').innerHTML += 
                    `<br>API Response Data: <pre>${JSON.stringify(data, null, 2)}</pre>`;
                
                // Update dashboard values
                document.getElementById('total-sales').textContent = formatCurrency(data.total_sales);
                document.getElementById('sales-count').textContent = data.sales_count;
                document.getElementById('net-income').textContent = formatCurrency(data.net_income);
                document.getElementById('paid-installments').textContent = formatCurrency(data.paid_installments);
            })
            .catch(error => {
                console.error('Error fetching dashboard data:', error);
                document.getElementById('api-test-result').innerHTML += 
                    `<br><span class="error">Error: ${error.message}</span>`;
            });
        
        // Format currency values
        function formatCurrency(value) {
            return parseFloat(value).toFixed(2) + ' ر.س';
        }
    </script>
</body>
</html>
    <?php
} catch (Exception $e) {
    echo "<div style='background-color: #ffdddd; border: 1px solid #ff0000; padding: 10px; margin: 10px 0;'>";
    echo "<h3>Fatal Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
    echo "</div>";
}

// Get the output buffer and display it
$output = ob_get_clean();
echo $output;
?> 