<?php
// Include database connection
include 'db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set header to handle AJAX requests
header('Content-Type: application/json');

// Get the raw POST data
$postData = file_get_contents('php://input');

// Decode the JSON data
$data = json_decode($postData, true);

// Check if data was received
if (!$data) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'No data received or invalid JSON']);
    exit;
}

// Log the received data
file_put_contents('transaction_log.txt', date('Y-m-d H:i:s') . " - Received data: " . $postData . PHP_EOL, FILE_APPEND);

try {
    // Start transaction
    $conn->beginTransaction();
    
    // Generate invoice number if not provided
    $invoiceNumber = isset($data['invoiceNumber']) ? $data['invoiceNumber'] : 'INV-' . time();
    
    // Get customer ID if available (default to NULL for cash customers)
    $customerId = null;
    if (isset($data['customer']) && $data['customer'] !== 'عميل نقدي') {
        // Try to find the customer by name
        $customerStmt = $conn->prepare("SELECT id FROM customers WHERE name = ? LIMIT 1");
        $customerStmt->execute([$data['customer']]);
        $customerResult = $customerStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($customerResult) {
            $customerId = $customerResult['id'];
        }
    }
    
    // Get payment method
    $paymentMethod = isset($data['paymentMethod']) ? strtolower($data['paymentMethod']) : 'cash';
    
    // Determine payment status based on payment method
    $paymentStatus = 'paid'; // Default for cash and visa
    if ($paymentMethod === 'تقسيط' || $paymentMethod === 'installment') {
        $paymentStatus = 'partial'; // For installment payments
        $paymentMethod = 'installment';
    } else if ($paymentMethod === 'فيزا' || $paymentMethod === 'visa') {
        $paymentMethod = 'visa';
    } else {
        $paymentMethod = 'cash'; // Default to cash
    }
    
    // Calculate totals
    $subtotal = isset($data['subtotal']) ? floatval($data['subtotal']) : 0;
    $discount = isset($data['discount']) ? floatval($data['discount']) : 0;
    $total = isset($data['total']) ? floatval($data['total']) : 0;
    
    // If we only have the total but not subtotal, assume no discount
    if ($total > 0 && $subtotal == 0) {
        $subtotal = $total;
        $discount = 0;
    }
    
    // Insert into sales table
    $salesStmt = $conn->prepare("
        INSERT INTO sales (
            invoice_number, 
            customer_id, 
            total_amount, 
            discount_amount, 
            subtotal_amount, 
            payment_method, 
            payment_status, 
            notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $salesStmt->execute([
        $invoiceNumber,
        $customerId,
        $total,
        $discount,
        $subtotal,
        $paymentMethod,
        $paymentStatus,
        isset($data['notes']) ? $data['notes'] : null
    ]);
    
    // Get the sale ID
    $saleId = $conn->lastInsertId();
    
    // Check if items array exists
    if (isset($data['items']) && is_array($data['items'])) {
        // Insert sale items
        $itemStmt = $conn->prepare("
            INSERT INTO sales_items (
                sale_id, 
                product_id, 
                quantity, 
                unit_price, 
                total_price
            ) VALUES (?, ?, ?, ?, ?)
        ");
        
        foreach ($data['items'] as $item) {
            // Get product ID by name
            $productStmt = $conn->prepare("SELECT id, quantity FROM products WHERE name = ? LIMIT 1");
            $productStmt->execute([$item['name']]);
            $product = $productStmt->fetch(PDO::FETCH_ASSOC);
            
            $productId = $product ? $product['id'] : null;
            
            // If product was found, add to sales_items and update inventory
            if ($productId) {
                // Add to sales_items
                $itemStmt->execute([
                    $saleId,
                    $productId,
                    $item['quantity'],
                    $item['price'],
                    $item['total']
                ]);
                
                // Update inventory
                $newQuantity = $product['quantity'] - $item['quantity'];
                $updateStmt = $conn->prepare("UPDATE products SET quantity = ? WHERE id = ?");
                $updateStmt->execute([$newQuantity, $productId]);
            }
        }
    }
    
    // Handle payment entry
    if ($paymentMethod === 'cash' || $paymentMethod === 'visa') {
        // For cash and visa, record full payment
        $paymentStmt = $conn->prepare("
            INSERT INTO payments (
                sale_id, 
                amount, 
                payment_method, 
                payment_reference
            ) VALUES (?, ?, ?, ?)
        ");
        
        $paymentStmt->execute([
            $saleId,
            $total,
            $paymentMethod,
            isset($data['transaction_ref']) ? $data['transaction_ref'] : null
        ]);
    } 
    else if ($paymentMethod === 'installment') {
        // For installment, record down payment
        $downPayment = isset($data['downPayment']) ? floatval($data['downPayment']) : 0;
        
        if ($downPayment > 0) {
            $paymentStmt = $conn->prepare("
                INSERT INTO payments (
                    sale_id, 
                    amount, 
                    payment_method, 
                    payment_reference,
                    notes
                ) VALUES (?, ?, ?, ?, ?)
            ");
            
            $paymentStmt->execute([
                $saleId,
                $downPayment,
                'cash', // Down payment is typically cash
                null,
                'دفعة مقدمة'
            ]);
        }
        
        // Create installment records
        if (isset($data['installmentCount']) && intval($data['installmentCount']) > 0) {
            $installmentCount = intval($data['installmentCount']);
            $installmentAmount = ($total - $downPayment) / $installmentCount;
            $startDate = isset($data['firstPaymentDate']) ? $data['firstPaymentDate'] : date('Y-m-d', strtotime('+1 month'));
            
            $installmentStmt = $conn->prepare("
                INSERT INTO installments (
                    sale_id, 
                    installment_number, 
                    amount, 
                    due_date, 
                    status
                ) VALUES (?, ?, ?, ?, ?)
            ");
            
            for ($i = 1; $i <= $installmentCount; $i++) {
                $dueDate = date('Y-m-d', strtotime($startDate . ' +' . ($i-1) . ' month'));
                
                $installmentStmt->execute([
                    $saleId,
                    $i,
                    $installmentAmount,
                    $dueDate,
                    'pending'
                ]);
            }
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    // Return success response
    echo json_encode([
        'status' => 'success',
        'message' => 'تم حفظ المعاملة بنجاح',
        'data' => [
            'sale_id' => $saleId,
            'invoice_number' => $invoiceNumber
        ]
    ]);
    
} catch (PDOException $e) {
    // Rollback transaction on error
    $conn->rollBack();
    
    // Log error
    file_put_contents('transaction_error_log.txt', date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . " - Data: " . $postData . PHP_EOL, FILE_APPEND);
    
    // Return error response
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'حدث خطأ أثناء حفظ المعاملة: ' . $e->getMessage()]);
}
?> 