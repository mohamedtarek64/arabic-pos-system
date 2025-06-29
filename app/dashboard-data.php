<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
include 'db.php';

// Set content type to JSON
header('Content-Type: application/json');

// Initialize response array
$response = [
    'total_sales' => 0,
    'sales_count' => 0,
    'net_income' => 0,
    'paid_installments' => 0,
    'monthly_sales' => [],
    'weekly_sales' => [],
    'recent_sales' => [],
    'recent_activities' => []
];

// Get filter date if provided
$filter_date = null;
if (isset($_GET['date']) && !empty($_GET['date'])) {
    $filter_date = $_GET['date'];
}

try {
    // === Calculate Total Sales (sales.subtotal_amount + installments.amount) ===
    $where_clause = '';
    $params = [];
    
    if ($filter_date) {
        $where_clause = " WHERE DATE(s.created_at) = ? ";
        $params[] = $filter_date;
    }
    
    // Get total sales from sales table
    $sql = "SELECT COALESCE(SUM(s.subtotal_amount), 0) as total_sales 
            FROM sales s 
            $where_clause";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->execute($params);
    } else {
        $stmt->execute();
    }
    
    $sales_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_sales = $sales_result['total_sales'];
    
    // Get total installments amount
    $where_clause = '';
    $params = [];
    
    if ($filter_date) {
        $where_clause = " WHERE DATE(i.created_at) = ? ";
        $params[] = $filter_date;
    }
    
    $sql = "SELECT COALESCE(SUM(i.amount), 0) as total_installments 
            FROM installments i 
            $where_clause";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->execute($params);
    } else {
        $stmt->execute();
    }
    
    $installments_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_installments = $installments_result['total_installments'];
    
    // Calculate total (sales + installments)
    $response['total_sales'] = $total_sales + $total_installments;
    
    // === Calculate Sales Count ===
    $where_clause = '';
    $params = [];
    
    if ($filter_date) {
        $where_clause = " WHERE DATE(created_at) = ? ";
        $params[] = $filter_date;
    }
    
    $sql = "SELECT COUNT(*) as sales_count FROM sales $where_clause";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->execute($params);
    } else {
        $stmt->execute();
    }
    
    $count_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $response['sales_count'] = $count_result['sales_count'];
    
    // === Calculate Net Income (same as total_sales for now) ===
    $response['net_income'] = $response['total_sales'];
    
    // === Calculate Paid Installments ===
    $where_clause = '';
    $params = [];
    
    if ($filter_date) {
        $where_clause = " WHERE DATE(payment_date) = ? AND paid = 1 ";
        $params[] = $filter_date;
    } else {
        $where_clause = " WHERE paid = 1 ";
    }
    
    $sql = "SELECT COALESCE(SUM(amount), 0) as paid_amount 
            FROM installments 
            $where_clause";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->execute($params);
    } else {
        $stmt->execute();
    }
    
    $paid_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $response['paid_installments'] = $paid_result['paid_amount'];
    
    // === Get Monthly Sales Data (Last 12 months) ===
    $sql = "SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                DATE_FORMAT(created_at, '%M %Y') as month_name,
                COALESCE(SUM(subtotal_amount), 0) as total
            FROM sales
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $monthly_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fill in missing months with zero values
    $last_12_months = [];
    for ($i = 11; $i >= 0; $i--) {
        $month_key = date('Y-m', strtotime("-$i month"));
        $month_name = date('F Y', strtotime("-$i month"));
        $last_12_months[$month_key] = [
            'month' => $month_name,
            'total' => 0
        ];
    }
    
    foreach ($monthly_sales as $sale) {
        if (isset($last_12_months[$sale['month']])) {
            $last_12_months[$sale['month']]['total'] = (float)$sale['total'];
        }
    }
    
    $response['monthly_sales'] = array_values($last_12_months);
    
    // === Get Weekly Sales Data (Current Week, Saturday to Friday) ===
    $current_day = date('N'); // 1 (Monday) to 7 (Sunday)
    $start_of_week = date('Y-m-d', strtotime('-' . (($current_day + 1) % 7) . ' days')); // Saturday
    $end_of_week = date('Y-m-d', strtotime($start_of_week . ' +6 days')); // Friday
    
    $sql = "SELECT 
                DAYOFWEEK(created_at) as day_num, 
                DATE_FORMAT(created_at, '%a') as day_name,
                DATE(created_at) as sale_date,
                COALESCE(SUM(subtotal_amount), 0) as total
            FROM sales
            WHERE created_at BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)
            GROUP BY DATE(created_at)
            ORDER BY DATE(created_at) ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$start_of_week, $end_of_week]);
    $weekly_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Initialize array for all days of the week
    $week_data = [];
    for ($i = 0; $i < 7; $i++) {
        $day_date = date('Y-m-d', strtotime($start_of_week . " +$i days"));
        $week_data[$day_date] = [
            'day' => $i + 1,
            'date' => $day_date,
            'total' => 0
        ];
    }
    
    // Fill in actual sales data
    foreach ($weekly_sales as $sale) {
        if (isset($week_data[$sale['sale_date']])) {
            $week_data[$sale['sale_date']]['total'] = (float)$sale['total'];
        }
    }
    
    $response['weekly_sales'] = array_values($week_data);
    
    // === Get Recent Sales (Last 5) ===
    $sql = "SELECT s.id, s.total_amount, s.created_at, COALESCE(c.name, 'عميل غير مسجل') as customer_name
            FROM sales s
            LEFT JOIN customers c ON s.customer_id = c.id
            ORDER BY s.created_at DESC
            LIMIT 5";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $response['recent_sales'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // === Get Recent Activities ===
    // First try to get from activity_log if it exists
    try {
        $sql = "SELECT 
                    a.id, 
                    a.activity_type, 
                    a.created_at, 
                    COALESCE(u.username, 'النظام') as user_name
                FROM activity_log a
                LEFT JOIN users u ON a.user_id = u.id
                ORDER BY a.created_at DESC
                LIMIT 5";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($activities) > 0) {
            $response['recent_activities'] = $activities;
        }
    } catch (PDOException $e) {
        // Table probably doesn't exist, use installments as activities
        $sql = "SELECT 
                    i.id, 
                    CONCAT('دفع قسط') as activity_type, 
                    COALESCE(i.payment_date, i.created_at) as created_at, 
                    COALESCE(c.name, 'عميل') as user_name
                FROM installments i
                LEFT JOIN customers c ON i.customer_id = c.id
                WHERE i.paid = 1
                ORDER BY COALESCE(i.payment_date, i.created_at) DESC
                LIMIT 5";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $installment_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Also get recent sales as activities
        $sql = "SELECT 
                    s.id, 
                    'عملية بيع جديدة' as activity_type, 
                    s.created_at, 
                    COALESCE(c.name, 'عميل') as user_name
                FROM sales s
                LEFT JOIN customers c ON s.customer_id = c.id
                ORDER BY s.created_at DESC
                LIMIT 5";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $sales_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Combine and sort both activity types
        $combined_activities = array_merge($installment_activities, $sales_activities);
        
        // Sort by date (newest first)
        usort($combined_activities, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        // Take only the first 5
        $response['recent_activities'] = array_slice($combined_activities, 0, 5);
    }
    
    // Return JSON response
    echo json_encode($response);
    
} catch (PDOException $e) {
    // Return error response
    $error_response = [
        'status' => 'error',
        'message' => 'حدث خطأ أثناء جلب البيانات: ' . $e->getMessage()
    ];
    echo json_encode($error_response);
}
?>
