<?php
session_start();
include '../app/db.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// التحقق من وجود معرّف العميل
if (!isset($_GET['customer_id']) || empty($_GET['customer_id'])) {
    header("Location: all_customers.php?error=invalid_customer");
    exit();
}

$customer_id = $_GET['customer_id'];
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'all_customers.php';

try {
    // جلب بيانات العميل للتأكد من وجوده
    $check_sql = "SELECT id, name FROM customers WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->execute([$customer_id]);
    $customer = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$customer) {
        header("Location: $redirect?error=customer_not_found");
        exit();
    }
    
    // التحقق من وجود أقساط مرتبطة بالعميل
    $check_installments_sql = "SELECT COUNT(*) FROM installments WHERE customer_id = ?";
    $check_installments_stmt = $conn->prepare($check_installments_sql);
    $check_installments_stmt->execute([$customer_id]);
    $has_installments = $check_installments_stmt->fetchColumn() > 0;
    
    // إذا كان هناك استدعاء AJAX، سنعيد نتائج JSON
    if (isset($_GET['ajax'])) {
        if ($has_installments) {
            echo json_encode([
                'success' => false,
                'message' => 'لا يمكن حذف العميل لأنه لديه أقساط مرتبطة به. يجب حذف الأقساط أولاً.'
            ]);
            exit();
        }
        
        // حذف العميل
        $delete_sql = "DELETE FROM customers WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $success = $delete_stmt->execute([$customer_id]);
        
        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => 'تم حذف العميل بنجاح!'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف العميل.'
            ]);
        }
        exit();
    }
    
    // إذا لم يكن AJAX، سنعالج الطلب مباشرة
    if ($has_installments) {
        header("Location: $redirect?error=has_installments&customer_id=$customer_id");
        exit();
    }
    
    // حذف العميل
    $delete_sql = "DELETE FROM customers WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    
    if ($delete_stmt->execute([$customer_id])) {
        header("Location: $redirect?success=deleted");
    } else {
        header("Location: $redirect?error=delete_failed");
    }
    
} catch (PDOException $e) {
    // تسجيل الخطأ
    error_log("خطأ في حذف العميل: " . $e->getMessage());
    
    if (isset($_GET['ajax'])) {
        echo json_encode([
            'success' => false,
            'message' => 'حدث خطأ في قاعدة البيانات: ' . $e->getMessage()
        ]);
        exit();
    }
    
    header("Location: $redirect?error=database_error");
}
?> 