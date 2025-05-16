<?php
// Include database connection
include '../app/db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type
header('Content-Type: application/json');

// Get the sale ID from request
$saleId = isset($_GET['id']) ? intval($_GET['id']) : null;

// Validate sale ID
if (!$saleId) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'رقم الفاتورة مطلوب']);
    exit;
}

try {
    // Get sale details
    $saleStmt = $conn->prepare("
        SELECT s.*, c.name as customer_name, c.phone as customer_phone, c.email as customer_email, c.address as customer_address
        FROM sales s
        LEFT JOIN customers c ON s.customer_id = c.id
        WHERE s.id = ?
    ");
    
    $saleStmt->execute([$saleId]);
    $sale = $saleStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sale) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'لم يتم العثور على الفاتورة']);
        exit;
    }
    
    // Add status information
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
    
    // Format dates
    $sale['created_date'] = date('Y-m-d', strtotime($sale['created_at']));
    $sale['created_time'] = date('h:i A', strtotime($sale['created_at']));
    
    // Format payment method for display
    switch ($sale['payment_method']) {
        case 'cash':
            $sale['payment_method_text'] = 'كاش';
            break;
        case 'visa':
            $sale['payment_method_text'] = 'فيزا';
            break;
        case 'installment':
            $sale['payment_method_text'] = 'تقسيط';
            break;
        default:
            $sale['payment_method_text'] = $sale['payment_method'];
    }
    
    // Get all items in this sale
    $itemsStmt = $conn->prepare("
        SELECT si.*, p.name as product_name, p.code as product_code, p.image as product_image
        FROM sales_items si
        LEFT JOIN products p ON si.product_id = p.id
        WHERE si.sale_id = ?
    ");
    
    $itemsStmt->execute([$saleId]);
    $sale['items'] = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all payments for this sale
    $paymentsStmt = $conn->prepare("
        SELECT *
        FROM payments
        WHERE sale_id = ?
        ORDER BY payment_date DESC
    ");
    
    $paymentsStmt->execute([$saleId]);
    $sale['payments'] = $paymentsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get installment details if applicable
    if ($sale['payment_method'] === 'installment') {
        $installmentsStmt = $conn->prepare("
            SELECT *
            FROM installments
            WHERE sale_id = ?
            ORDER BY installment_number ASC
        ");
        
        $installmentsStmt->execute([$saleId]);
        $sale['installments'] = $installmentsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate total paid and remaining amounts
        $totalPaid = 0;
        $totalRemaining = 0;
        
        // Add from payments table
        foreach ($sale['payments'] as $payment) {
            $totalPaid += floatval($payment['amount']);
        }
        
        // Add from installments table
        foreach ($sale['installments'] as &$installment) {
            $installment['formatted_due_date'] = date('Y-m-d', strtotime($installment['due_date']));
            
            if ($installment['status'] === 'paid') {
                $totalPaid += floatval($installment['paid_amount']);
            } else {
                $totalRemaining += floatval($installment['amount']);
            }
        }
        
        $sale['total_paid'] = $totalPaid;
        $sale['total_remaining'] = $totalRemaining;
    }
    
    // Return the results
    echo json_encode([
        'status' => 'success',
        'data' => $sale
    ]);
    
} catch (PDOException $e) {
    // Return error response
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'حدث خطأ: ' . $e->getMessage()]);
}
?> 