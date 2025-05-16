<?php
// Include database connection
include 'db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type
header('Content-Type: application/json');

// Get query parameters
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$date = isset($_GET['date']) ? $_GET['date'] : null;

try {
    // Build the query based on filters
    $query = "
        SELECT s.*, c.name as customer_name 
        FROM sales s
        LEFT JOIN customers c ON s.customer_id = c.id
    ";
    
    $params = [];
    $whereConditions = [];
    
    // Add date filter if provided
    if ($date !== null) {
        switch ($date) {
            case 'today':
                $whereConditions[] = "DATE(s.created_at) = CURDATE()";
                break;
            case 'yesterday':
                $whereConditions[] = "DATE(s.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
                break;
            case 'week':
                $whereConditions[] = "DATE(s.created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                break;
            case 'month':
                $whereConditions[] = "DATE(s.created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
                break;
            default:
                // If specific date is provided (format: YYYY-MM-DD)
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                    $whereConditions[] = "DATE(s.created_at) = ?";
                    $params[] = $date;
                }
        }
    }
    
    // Add search term if provided
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = '%' . $_GET['search'] . '%';
        $whereConditions[] = "(s.invoice_number LIKE ? OR c.name LIKE ?)";
        $params[] = $search;
        $params[] = $search;
    }
    
    // Add payment status filter if provided
    if (isset($_GET['status']) && !empty($_GET['status'])) {
        $whereConditions[] = "s.payment_status = ?";
        $params[] = $_GET['status'];
    }
    
    // Add WHERE clause if conditions exist
    if (!empty($whereConditions)) {
        $query .= " WHERE " . implode(" AND ", $whereConditions);
    }
    
    // Add ordering
    $query .= " ORDER BY s.created_at DESC";
    
    // Add limit and offset
    $query .= " LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    // Prepare and execute the query
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    
    // Fetch the results
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get the total count (without limit/offset)
    $countQuery = "
        SELECT COUNT(*) as total 
        FROM sales s
        LEFT JOIN customers c ON s.customer_id = c.id
    ";
    
    if (!empty($whereConditions)) {
        $countQuery .= " WHERE " . implode(" AND ", $whereConditions);
    }
    
    $countStmt = $conn->prepare($countQuery);
    $countStmt->execute($countParams);
    $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Process each sale to add items and color coding
    foreach ($results as &$sale) {
        // Add status color
        switch ($sale['payment_status']) {
            case 'paid':
                $sale['status_color'] = 'green';
                $sale['status_text'] = 'مدفوع';
                break;
            case 'partial':
                $sale['status_color'] = 'orange';
                $sale['status_text'] = 'جزئي';
                break;
            case 'unpaid':
                $sale['status_color'] = 'red';
                $sale['status_text'] = 'غير مدفوع';
                break;
            default:
                $sale['status_color'] = 'gray';
                $sale['status_text'] = 'غير معروف';
        }
        
        // Format created_at
        $sale['created_date'] = date('Y-m-d', strtotime($sale['created_at']));
        $sale['created_time'] = date('h:i A', strtotime($sale['created_at']));
        
        // Get items
        $itemsStmt = $conn->prepare("
            SELECT si.*, p.name as product_name, p.code as product_code
            FROM sales_items si
            LEFT JOIN products p ON si.product_id = p.id
            WHERE si.sale_id = ?
        ");
        
        $itemsStmt->execute([$sale['id']]);
        $sale['items'] = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get payments
        $paymentsStmt = $conn->prepare("
            SELECT * FROM payments WHERE sale_id = ?
        ");
        
        $paymentsStmt->execute([$sale['id']]);
        $sale['payments'] = $paymentsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get installments if applicable
        if ($sale['payment_method'] === 'installment') {
            $installmentsStmt = $conn->prepare("
                SELECT * FROM installments WHERE sale_id = ? ORDER BY installment_number ASC
            ");
            
            $installmentsStmt->execute([$sale['id']]);
            $sale['installments'] = $installmentsStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    
    // Return the results
    echo json_encode([
        'status' => 'success',
        'count' => count($results),
        'total' => $totalCount,
        'data' => $results
    ]);
    
} catch (PDOException $e) {
    // Return error response
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'حدث خطأ: ' . $e->getMessage()]);
}
?> 