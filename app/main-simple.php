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
    <title>لوحة التحكم الرئيسية (نسخة بسيطة)</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f9;
            direction: rtl;
            margin: 0;
            padding: 0;
        }
        
        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .content-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .content-header h1 {
            color: #1976d2;
            font-size: 2em;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .kpi-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
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
        
        .chart-container {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .chart-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .card-title {
            font-size: 1.2rem;
            font-weight: bold;
            color: #333;
        }
        
        .tables-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .table-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th {
            text-align: right;
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            color: #666;
        }
        
        .data-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }
        
        .data-table tbody tr:hover {
            background-color: #f9f9f9;
        }
        
        .activity-status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
        }
        
        .status-payment {
            background-color: #e3f2fd;
            color: #1976d2;
        }
        
        .status-sale {
            background-color: #e8f5e9;
            color: #388e3c;
        }
        
        .empty-state {
            padding: 30px;
            text-align: center;
            color: #888;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #ddd;
        }
        
        @media (max-width: 992px) {
            .dashboard-grid, .chart-grid, .tables-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .dashboard-grid, .chart-grid, .tables-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="content-header">
            <h1>لوحة التحكم الرئيسية (نسخة بسيطة)</h1>
            <p>هذه نسخة مبسطة من لوحة التحكم لاختبار الوظائف الأساسية</p>
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
        
        <!-- Charts -->
        <div class="chart-grid">
            <div class="chart-container">
                <div class="card-header">
                    <div class="card-title">المبيعات خلال آخر 12 شهر</div>
                </div>
                <canvas id="monthly-sales-chart"></canvas>
            </div>
            
            <div class="chart-container">
                <div class="card-header">
                    <div class="card-title">مبيعات الأسبوع الحالي</div>
                </div>
                <canvas id="weekly-sales-chart"></canvas>
            </div>
        </div>
        
        <!-- Recent Activities and Recent Sales -->
        <div class="tables-grid">
            <div class="table-card">
                <div class="card-header">
                    <div class="card-title">آخر المبيعات</div>
                </div>
                <div id="recent-sales-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>العميل</th>
                                <th>التاريخ</th>
                                <th>القيمة</th>
                            </tr>
                        </thead>
                        <tbody id="recent-sales-table">
                            <!-- Will be populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="table-card">
                <div class="card-header">
                    <div class="card-title">آخر النشاطات</div>
                </div>
                <div id="recent-activities-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>المسؤول</th>
                                <th>العملية</th>
                                <th>التاريخ</th>
                            </tr>
                        </thead>
                        <tbody id="recent-activities-table">
                            <!-- Will be populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Fetch dashboard data on page load
        document.addEventListener('DOMContentLoaded', function() {
            fetchDashboardData();
        });
        
        // Function to fetch dashboard data
        function fetchDashboardData() {
            fetch('dashboard-data-simple.php')
                .then(response => response.json())
                .then(data => {
                    updateDashboard(data);
                })
                .catch(error => {
                    console.error('Error fetching dashboard data:', error);
                    document.body.innerHTML += `<div style="background-color: #ffdddd; padding: 20px; margin: 20px; border-radius: 5px;"><h3>خطأ في تحميل البيانات:</h3><p>${error.message}</p></div>`;
                });
        }
        
        // Update dashboard with fetched data
        function updateDashboard(data) {
            // Update KPI cards
            document.getElementById('total-sales').textContent = formatCurrency(data.total_sales);
            document.getElementById('sales-count').textContent = data.sales_count;
            document.getElementById('net-income').textContent = formatCurrency(data.net_income);
            document.getElementById('paid-installments').textContent = formatCurrency(data.paid_installments);
            
            // Update monthly sales chart
            updateMonthlySalesChart(data.monthly_sales);
            
            // Update weekly sales chart
            updateWeeklySalesChart(data.weekly_sales);
            
            // Update recent sales table
            updateRecentSales(data.recent_sales);
            
            // Update recent activities table
            updateRecentActivities(data.recent_activities);
        }
        
        // Format currency values
        function formatCurrency(value) {
            return parseFloat(value).toFixed(2) + ' ر.س';
        }
        
        // Update monthly sales chart
        function updateMonthlySalesChart(monthlyData) {
            const ctx = document.getElementById('monthly-sales-chart').getContext('2d');
            
            // Check if chart instance exists
            if (window.monthlySalesChart instanceof Chart) {
                window.monthlySalesChart.destroy();
            }
            
            if (!monthlyData || monthlyData.length === 0) {
                showEmptyState(ctx.canvas.parentNode, 'لا توجد بيانات مبيعات شهرية متاحة');
                return;
            }
            
            // Prepare data for the chart
            const labels = monthlyData.map(item => item.month);
            const values = monthlyData.map(item => item.total);
            
            window.monthlySalesChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'المبيعات الشهرية',
                        data: values,
                        backgroundColor: 'rgba(25, 118, 210, 0.6)',
                        borderColor: 'rgba(25, 118, 210, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return formatCurrency(context.raw);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value + ' ر.س';
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Update weekly sales chart
        function updateWeeklySalesChart(weeklyData) {
            const ctx = document.getElementById('weekly-sales-chart').getContext('2d');
            
            // Check if chart instance exists
            if (window.weeklySalesChart instanceof Chart) {
                window.weeklySalesChart.destroy();
            }
            
            if (!weeklyData || weeklyData.length === 0) {
                showEmptyState(ctx.canvas.parentNode, 'لا توجد بيانات مبيعات أسبوعية متاحة');
                return;
            }
            
            // Prepare data for the chart
            const labels = ['السبت', 'الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة'];
            const values = weeklyData.map(item => item.total);
            
            window.weeklySalesChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'مبيعات الأسبوع',
                        data: values,
                        backgroundColor: 'rgba(76, 175, 80, 0.2)',
                        borderColor: 'rgba(76, 175, 80, 1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return formatCurrency(context.raw);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value + ' ر.س';
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Update recent sales table
        function updateRecentSales(salesData) {
            const tableBody = document.getElementById('recent-sales-table');
            const container = document.getElementById('recent-sales-container');
            
            if (!salesData || salesData.length === 0) {
                showEmptyState(container, 'لا توجد بيانات مبيعات حديثة');
                return;
            }
            
            tableBody.innerHTML = '';
            
            salesData.forEach(sale => {
                const row = document.createElement('tr');
                
                const customerCell = document.createElement('td');
                customerCell.textContent = sale.customer_name || 'عميل غير مسجل';
                
                const dateCell = document.createElement('td');
                dateCell.textContent = formatDate(sale.created_at);
                
                const amountCell = document.createElement('td');
                amountCell.textContent = formatCurrency(sale.total_amount);
                
                row.appendChild(customerCell);
                row.appendChild(dateCell);
                row.appendChild(amountCell);
                
                tableBody.appendChild(row);
            });
        }
        
        // Update recent activities table
        function updateRecentActivities(activitiesData) {
            const tableBody = document.getElementById('recent-activities-table');
            const container = document.getElementById('recent-activities-container');
            
            if (!activitiesData || activitiesData.length === 0) {
                showEmptyState(container, 'لا توجد نشاطات حديثة');
                return;
            }
            
            tableBody.innerHTML = '';
            
            activitiesData.forEach(activity => {
                const row = document.createElement('tr');
                
                const userCell = document.createElement('td');
                userCell.textContent = activity.user_name || 'النظام';
                
                const actionCell = document.createElement('td');
                const actionSpan = document.createElement('span');
                actionSpan.textContent = activity.activity_type;
                actionSpan.className = 'activity-status ' + getActivityStatusClass(activity.activity_type);
                actionCell.appendChild(actionSpan);
                
                const dateCell = document.createElement('td');
                dateCell.textContent = formatDate(activity.created_at);
                
                row.appendChild(userCell);
                row.appendChild(actionCell);
                row.appendChild(dateCell);
                
                tableBody.appendChild(row);
            });
        }
        
        // Helper function to get activity status class
        function getActivityStatusClass(activityType) {
            if (activityType.includes('دفع') || activityType.includes('قسط')) {
                return 'status-payment';
            } else if (activityType.includes('بيع') || activityType.includes('مبيعات')) {
                return 'status-sale';
            }
            return '';
        }
        
        // Format date for display
        function formatDate(dateString) {
            const options = { year: 'numeric', month: 'numeric', day: 'numeric' };
            return new Date(dateString).toLocaleDateString('ar-SA', options);
        }
        
        // Show empty state when no data is available
        function showEmptyState(container, message) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-chart-area"></i>
                    <p>${message}</p>
                </div>
            `;
        }
    </script>
</body>
</html> 