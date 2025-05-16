<?php
// ملف لحفظ عملية البيع في قاعدة البيانات
session_start();
include 'db.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// التحقق من وجود بيانات POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // استلام البيانات
        $customer_id = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : null;
        $total_amount = isset($_POST['total_amount']) ? (float)$_POST['total_amount'] : 0;
        $tax_amount = isset($_POST['tax_amount']) ? (float)$_POST['tax_amount'] : 0;
        $discount_amount = isset($_POST['discount_amount']) ? (float)$_POST['discount_amount'] : 0;
        $payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'cash';
        $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
        $cart_items = isset($_POST['cart_items']) ? json_decode($_POST['cart_items'], true) : [];

        // التحقق من البيانات
        if (empty($cart_items)) {
            throw new Exception("سلة المشتريات فارغة");
        }

        // بدء المعاملة
        $conn->beginTransaction();

        // إدخال بيانات المبيعات
        $sale_query = "INSERT INTO sales (customer_id, total_amount, tax_amount, discount_amount, payment_method, notes) 
                      VALUES (?, ?, ?, ?, ?, ?)";
        $sale_stmt = $conn->prepare($sale_query);
        $result = $sale_stmt->execute([
            $customer_id, 
            $total_amount, 
            $tax_amount,
            $discount_amount,
            $payment_method,
            $notes
        ]);

        if (!$result) {
            throw new Exception("فشل في حفظ عملية البيع");
        }

        // الحصول على معرف البيع
        $sale_id = $conn->lastInsertId();

        // إدخال عناصر المبيعات
        $item_query = "INSERT INTO sale_items (sale_id, product_id, quantity, price, total) 
                      VALUES (?, ?, ?, ?, ?)";
        $item_stmt = $conn->prepare($item_query);

        // تحديث المخزون
        $update_stock_query = "UPDATE products SET quantity = quantity - ? WHERE id = ?";
        $update_stock_stmt = $conn->prepare($update_stock_query);

        foreach ($cart_items as $item) {
            // إضافة عنصر البيع
            $item_stmt->execute([
                $sale_id,
                $item['id'],
                $item['quantity'],
                $item['price'],
                $item['total']
            ]);

            // تحديث المخزون
            $update_stock_stmt->execute([
                $item['quantity'],
                $item['id']
            ]);
        }

        // إضافة نشاط جديد
        $user_id = $_SESSION['user_id'];
        $user_name = $_SESSION['user_name'] ?? 'مستخدم النظام';
        $activity_type = 'sale';
        $description = "قام بإنشاء فاتورة بيع جديدة رقم #" . $sale_id;

        $activity_query = "INSERT INTO activities (user_id, user_name, activity_type, description) 
                          VALUES (?, ?, ?, ?)";
        $activity_stmt = $conn->prepare($activity_query);
        $activity_stmt->execute([
            $user_id,
            $user_name,
            $activity_type,
            $description
        ]);

        // تنفيذ المعاملة
        $conn->commit();

        // إعداد الاستجابة
        $response = [
            'success' => true,
            'message' => 'تم حفظ عملية البيع بنجاح',
            'sale_id' => $sale_id
        ];

    } catch (PDOException $e) {
        // التراجع عن المعاملة في حالة الخطأ
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        $response = [
            'success' => false,
            'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()
        ];

        error_log("خطأ في save_sale.php: " . $e->getMessage());
    } catch (Exception $e) {
        // التراجع عن المعاملة في حالة الخطأ
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        $response = [
            'success' => false,
            'message' => $e->getMessage()
        ];

        error_log("خطأ في save_sale.php: " . $e->getMessage());
    }

    // إرجاع الاستجابة كـ JSON
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
} else {
    // في حالة عدم استخدام طريقة POST
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'طريقة غير مسموح بها'
    ]);
    exit();
} 