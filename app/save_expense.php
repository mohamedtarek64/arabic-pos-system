<?php
// ملف لحفظ المصروفات في قاعدة البيانات
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
        $category = isset($_POST['category']) ? $_POST['category'] : '';
        $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
        $description = isset($_POST['description']) ? $_POST['description'] : '';
        $expense_date = isset($_POST['expense_date']) ? $_POST['expense_date'] : date('Y-m-d');

        // التحقق من البيانات
        if (empty($category)) {
            throw new Exception("يجب تحديد فئة المصروف");
        }

        if ($amount <= 0) {
            throw new Exception("يجب أن يكون المبلغ أكبر من صفر");
        }

        // بدء المعاملة
        $conn->beginTransaction();

        // إدخال بيانات المصروفات
        $expense_query = "INSERT INTO expenses (category, amount, description, expense_date) 
                        VALUES (?, ?, ?, ?)";
        $expense_stmt = $conn->prepare($expense_query);
        $result = $expense_stmt->execute([
            $category,
            $amount,
            $description,
            $expense_date
        ]);

        if (!$result) {
            throw new Exception("فشل في حفظ المصروف");
        }

        // الحصول على معرف المصروف
        $expense_id = $conn->lastInsertId();

        // إضافة نشاط جديد
        $user_id = $_SESSION['user_id'];
        $user_name = $_SESSION['user_name'] ?? 'مستخدم النظام';
        $activity_type = 'expense';
        $description = "قام بتسجيل مصروف جديد في فئة " . $category . " بمبلغ " . $amount . " جنيه";

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
            'message' => 'تم حفظ المصروف بنجاح',
            'expense_id' => $expense_id
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

        error_log("خطأ في save_expense.php: " . $e->getMessage());
    } catch (Exception $e) {
        // التراجع عن المعاملة في حالة الخطأ
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        $response = [
            'success' => false,
            'message' => $e->getMessage()
        ];

        error_log("خطأ في save_expense.php: " . $e->getMessage());
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