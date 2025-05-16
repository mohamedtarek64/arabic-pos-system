<?php
// Include database connection
include 'db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type for AJAX requests
header('Content-Type: application/json');

try {
    // Get recent sales transactions
    $query = "SELECT sales.*, customers.name as customer_name 
              FROM sales 
              LEFT JOIN customers ON sales.customer_id = customers.id 
              ORDER BY sales.created_at DESC 
              LIMIT 10";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format transactions for display
    $formatted_transactions = [];
    foreach ($transactions as $transaction) {
        $formatted_transactions[] = [
            'id' => $transaction['id'],
            'invoice_number' => $transaction['invoice_number'],
            'customer_name' => $transaction['customer_name'] ?: 'عميل نقدي',
            'total_amount' => number_format($transaction['total_amount'], 2),
            'payment_method' => $transaction['payment_method'],
            'payment_status' => $transaction['payment_status'],
            'date' => date('d/m/Y', strtotime($transaction['created_at'])),
            'time' => date('h:i A', strtotime($transaction['created_at']))
        ];
    }
    
    // Return success response
    echo json_encode([
        'status' => 'success',
        'data' => $formatted_transactions
    ]);
    
} catch (PDOException $e) {
    // Return error response
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'حدث خطأ أثناء جلب المعاملات: ' . $e->getMessage()
    ]);
}
?> 