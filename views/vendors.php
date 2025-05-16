<?php
session_start();
include '../app/db.php';

// التحقق من الجلسة
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// استعلام لجلب جميع الموردين
$vendors_sql = "SELECT * FROM vendors ORDER BY name ASC";
$vendors_stmt = $conn->prepare($vendors_sql);
$vendors_stmt->execute();
$vendors = $vendors_stmt->fetchAll(PDO::FETCH_ASSOC);

// متغير لتخزين المورد المحدد
$selected_vendor = null;
$vendor_payments = [];
$vendor_debts = [];
$payment_history = [];

// إضافة مورد جديد
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_vendor'])) {
    $vendor_name = trim($_POST['vendor_name']);
    $vendor_phone = trim($_POST['vendor_phone']);
    $vendor_address = trim($_POST['vendor_address']);
    $vendor_email = trim($_POST['vendor_email'] ?? '');
    $vendor_notes = trim($_POST['vendor_notes'] ?? '');

    if (empty($vendor_name)) {
        $error_message = "يرجى إدخال اسم المورد";
    } else {
        try {
            // استعلام لإدخال المورد في قاعدة البيانات
            $sql = "INSERT INTO vendors (name, phone, address, email, notes, amount_owed) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$vendor_name, $vendor_phone, $vendor_address, $vendor_email, $vendor_notes, 0]);
            
            $success_message = "تم إضافة المورد بنجاح";
            
            // إعادة تحميل قائمة الموردين
            $vendors_stmt->execute();
            $vendors = $vendors_stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $error_message = "حدث خطأ أثناء إضافة المورد: " . $e->getMessage();
        }
    }
}

// إضافة دين جديد للمورد
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_debt'])) {
    $vendor_id = $_POST['vendor_id'];
    $amount = floatval($_POST['amount']);
    $description = trim($_POST['description']);
    $invoice_number = trim($_POST['invoice_number'] ?? '');
    $debt_date = $_POST['debt_date'];
    
    if ($amount <= 0) {
        $error_message = "يرجى إدخال مبلغ صحيح";
    } else {
        try {
            // إضافة الدين في قاعدة البيانات
            $sql = "INSERT INTO vendor_debts (vendor_id, amount, description, invoice_number, debt_date, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$vendor_id, $amount, $description, $invoice_number, $debt_date]);
            
            // تحديث المبلغ المستحق للمورد
            $update_sql = "UPDATE vendors SET amount_owed = amount_owed + ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->execute([$amount, $vendor_id]);
            
            $success_message = "تم إضافة الدين للمورد بنجاح";
            
            // تحديث قائمة الموردين
            $vendors_stmt->execute();
            $vendors = $vendors_stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $error_message = "حدث خطأ أثناء إضافة الدين: " . $e->getMessage();
        }
    }
}

// إضافة دفعة للمورد
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_payment'])) {
    $vendor_id = $_POST['vendor_id'];
    $amount = floatval($_POST['amount']);
    $payment_method = $_POST['payment_method'];
    $payment_date = $_POST['payment_date'];
    $reference_number = trim($_POST['reference_number'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    
    if ($amount <= 0) {
        $error_message = "يرجى إدخال مبلغ صحيح";
    } else {
        try {
            // إضافة الدفعة في قاعدة البيانات
            $sql = "INSERT INTO vendor_payments (vendor_id, amount, payment_method, payment_date, reference_number, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$vendor_id, $amount, $payment_method, $payment_date, $reference_number, $notes]);
            
            // تحديث المبلغ المستحق للمورد
            $update_sql = "UPDATE vendors SET amount_owed = amount_owed - ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->execute([$amount, $vendor_id]);
            
            $success_message = "تم إضافة الدفعة للمورد بنجاح";
            
            // تحديث قائمة الموردين
            $vendors_stmt->execute();
            $vendors = $vendors_stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $error_message = "حدث خطأ أثناء إضافة الدفعة: " . $e->getMessage();
        }
    }
}

// عرض تفاصيل المورد
if (isset($_GET['vendor_id'])) {
    $vendor_id = $_GET['vendor_id'];
    
    // جلب بيانات المورد
    $vendor_sql = "SELECT * FROM vendors WHERE id = ?";
    $vendor_stmt = $conn->prepare($vendor_sql);
    $vendor_stmt->execute([$vendor_id]);
    $selected_vendor = $vendor_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($selected_vendor) {
        // جلب الديون
        $debts_sql = "SELECT *, DATE_FORMAT(debt_date, '%Y-%m-%d') as formatted_debt_date FROM vendor_debts WHERE vendor_id = ? ORDER BY debt_date DESC";
        $debts_stmt = $conn->prepare($debts_sql);
        $debts_stmt->execute([$vendor_id]);
        $vendor_debts = $debts_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // جلب المدفوعات
        $payments_sql = "SELECT *, DATE_FORMAT(payment_date, '%Y-%m-%d') as formatted_payment_date FROM vendor_payments WHERE vendor_id = ? ORDER BY payment_date DESC";
        $payments_stmt = $conn->prepare($payments_sql);
        $payments_stmt->execute([$vendor_id]);
        $vendor_payments = $payments_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // جلب سجل المدفوعات والديون مرتبة حسب التاريخ
        $history_sql = "
            SELECT 
                'debt' as type, 
                amount, 
                debt_date as transaction_date, 
                description as notes, 
                invoice_number as reference
            FROM vendor_debts 
            WHERE vendor_id = ?
            UNION ALL
            SELECT 
                'payment' as type, 
                amount, 
                payment_date as transaction_date, 
                notes, 
                reference_number as reference
            FROM vendor_payments 
            WHERE vendor_id = ?
            ORDER BY transaction_date DESC
        ";
        $history_stmt = $conn->prepare($history_sql);
        $history_stmt->execute([$vendor_id, $vendor_id]);
        $payment_history = $history_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// البحث عن موردين
$search_results = [];
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['search'])) {
    $search_term = trim($_GET['search_term']);
    
    if (!empty($search_term)) {
        $search_sql = "SELECT * FROM vendors WHERE name LIKE ? OR phone LIKE ? OR address LIKE ? ORDER BY name ASC";
        $search_stmt = $conn->prepare($search_sql);
        $search_term = "%$search_term%";
        $search_stmt->execute([$search_term, $search_term, $search_term]);
        $search_results = $search_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// جلب الموردين الذين لديهم ديون
$vendors_with_debts_sql = "SELECT * FROM vendors WHERE amount_owed > 0 ORDER BY amount_owed DESC";
$vendors_with_debts_stmt = $conn->prepare($vendors_with_debts_sql);
$vendors_with_debts_stmt->execute();
$vendors_with_debts = $vendors_with_debts_stmt->fetchAll(PDO::FETCH_ASSOC);

// حساب إجمالي الديون - تعديل الاستعلام لتجنب استخدام جدول غير موجود
$total_debts_sql = "SELECT SUM(amount_owed) as total FROM vendors";
$total_debts_stmt = $conn->prepare($total_debts_sql);
$total_debts_stmt->execute();
$total_debts = $total_debts_stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// تعليق استعلام إجمالي المدفوعات لأن الجدول غير موجود
// $total_payments_sql = "SELECT SUM(amount) as total FROM vendor_payments";
// $total_payments_stmt = $conn->prepare($total_payments_sql);
// $total_payments_stmt->execute();
// $total_payments = $total_payments_stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
$total_payments = 0; // قيمة افتراضية مؤقتة

// يجب إنشاء الجداول المفقودة
// إضافة كود لإنشاء جداول vendor_payments و vendor_debts إذا لم تكن موجودة
$create_tables_sql = "
CREATE TABLE IF NOT EXISTS vendor_debts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vendor_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description TEXT,
    invoice_number VARCHAR(50),
    debt_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS vendor_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vendor_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    payment_date DATE NOT NULL,
    reference_number VARCHAR(50),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE
);
";

try {
    $conn->exec($create_tables_sql);
} catch (PDOException $e) {
    // تجاهل الأخطاء هنا، فقط للتأكد من وجود الجداول
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الموردين</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>
        body, html {
            height: 100%;
            margin: 0;
            padding: 0;
            font-size: 16px;
            font-family: 'Cairo', sans-serif;
            background-color: #f5f7fa;
        }
        
        .container {
            width: 100%;
            max-width: 100%;
            margin: 0;
            padding: 20px;
            box-shadow: none;
            border-radius: 0;
            min-height: 100vh;
        }
        
        .top-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 10px;
            background-color: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .logo {
            display: flex;
            align-items: center;
            font-size: 1.8em;
            font-weight: 700;
            color: #333;
        }
        
        .logo i {
            margin-left: 15px;
            color: #2196f3;
            font-size: 1.2em;
        }
        
        .navigation {
            display: flex;
            gap: 20px;
        }
        
        .navigation a {
            color: #333;
            text-decoration: none;
            transition: all 0.3s;
            font-size: 1.2em;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            padding: 8px 15px;
            border-radius: 8px;
        }
        
        .navigation a:hover {
            color: #2196f3;
            background-color: #e3f2fd;
        }
        
        .breadcrumb {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            background-color: #fff;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .breadcrumb-item {
            display: flex;
            align-items: center;
            font-weight: 600;
        }
        
        .breadcrumb-item:not(:last-child)::after {
            content: "/";
            margin: 0 10px;
            color: #ccc;
            font-weight: 400;
        }
        
        .breadcrumb-item a {
            color: #2196f3;
            text-decoration: none;
        }
        
        .breadcrumb-item.active {
            color: #333;
            font-weight: 700;
        }
        
        .dashboard-container {
            display: grid;
            grid-template-columns: 1fr 3fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .sidebar {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 20px;
            height: fit-content;
            max-height: 80vh;
            display: flex;
            flex-direction: column;
        }
        
        .main-content {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 25px;
            min-height: 600px;
        }
        
        .section-title {
            font-size: 1.6em;
            margin-bottom: 20px;
            color: #333;
            border-bottom: 3px solid #2196f3;
            padding-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
        }
        
        .vendor-list {
            flex: 1;
            overflow-y: auto;
            margin-top: 15px;
            padding-right: 5px;
        }
        
        .vendor-item {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s;
            border: 1px solid #eee;
        }
        
        .vendor-item:hover {
            background-color: #f5f5f5;
            transform: translateY(-2px);
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .vendor-item.active {
            background-color: #e3f2fd;
            border-right: 4px solid #2196f3;
            box-shadow: 0 3px 10px rgba(33, 150, 243, 0.2);
        }
        
        .vendor-name {
            font-weight: 700;
            font-size: 1.2em;
            margin-bottom: 8px;
            color: #333;
        }
        
        .vendor-phone {
            color: #666;
            font-size: 0.95em;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .vendor-phone i {
            color: #2196f3;
        }
        
        .vendor-debt {
            color: #f44336;
            font-weight: 700;
            font-size: 1.1em;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .vendor-debt i {
            font-size: 0.9em;
        }
        
        .vendor-date {
            color: #666;
            font-size: 0.85em;
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .vendor-date i {
            color: #9e9e9e;
        }
        
        .search-box {
            margin-bottom: 20px;
            position: relative;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 40px 12px 15px;
            border-radius: 8px;
            border: 1px solid #ddd;
            font-size: 1em;
            font-family: 'Cairo', sans-serif;
            transition: all 0.3s;
        }
        
        .search-box input:focus {
            border-color: #2196f3;
            box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.2);
            outline: none;
        }
        
        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }
        
        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .action-button {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1.1em;
            transition: all 0.3s;
            font-family: 'Cairo', sans-serif;
            font-weight: 600;
        }
        
        .primary-button {
            background-color: #2196f3;
            color: white;
        }
        
        .primary-button:hover {
            background-color: #1976d2;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(33, 150, 243, 0.3);
        }
        
        .danger-button {
            background-color: #f44336;
            color: white;
        }
        
        .danger-button:hover {
            background-color: #d32f2f;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(244, 67, 54, 0.3);
        }
        
        .success-button {
            background-color: #4caf50;
            color: white;
        }
        
        .success-button:hover {
            background-color: #388e3c;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(76, 175, 80, 0.3);
        }
        
        .warning-button {
            background-color: #ff9800;
            color: white;
        }
        
        .warning-button:hover {
            background-color: #f57c00;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(255, 152, 0, 0.3);
        }
        
        .info-button {
            background-color: #00bcd4;
            color: white;
        }
        
        .info-button:hover {
            background-color: #0097a7;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 188, 212, 0.3);
        }
        
        .vendor-details {
            margin-bottom: 30px;
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 10px;
            border-right: 4px solid #2196f3;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 12px;
            align-items: center;
        }
        
        .detail-label {
            font-weight: 700;
            width: 150px;
            color: #555;
            font-size: 1.1em;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .detail-label i {
            color: #2196f3;
            font-size: 1.1em;
        }
        
        .detail-value {
            flex: 1;
            font-size: 1.1em;
        }
        
        .tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
            gap: 5px;
        }
        
        .tab {
            padding: 12px 25px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            font-weight: 600;
            font-size: 1.1em;
            border-radius: 8px 8px 0 0;
        }
        
        .tab:hover {
            background-color: #f5f5f5;
        }
        
        .tab.active {
            border-bottom-color: #2196f3;
            color: #2196f3;
            font-weight: 700;
            background-color: #e3f2fd;
        }
        
        .tab-content {
            display: none;
            padding: 20px 0;
        }
        
        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 1.05em;
        }
        
        .data-table th, .data-table td {
            padding: 15px;
            text-align: right;
            border-bottom: 1px solid #ddd;
        }
        
        .data-table th {
            background-color: #f5f5f5;
            font-weight: 700;
            color: #333;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .data-table tbody tr {
            transition: all 0.3s;
        }
        
        .data-table tbody tr:hover {
            background-color: #f9f9f9;
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .amount-positive {
            color: #4caf50;
            font-weight: 700;
        }
        
        .amount-negative {
            color: #f44336;
            font-weight: 700;
        }
        
        .transaction-type {
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9em;
            display: inline-block;
            text-align: center;
            min-width: 80px;
        }
        
        .transaction-type.debt {
            background-color: #ffebee;
            color: #f44336;
        }
        
        .transaction-type.payment {
            background-color: #e8f5e9;
            color: #4caf50;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s ease-in-out;
        }
        
        .modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 25px;
            border-radius: 10px;
            width: 50%;
            max-width: 600px;
            position: relative;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
            animation: slideDown 0.3s ease-in-out;
        }
        
        @keyframes slideDown {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .close-modal {
            position: absolute;
            top: 15px;
            left: 15px;
            font-size: 1.5em;
            cursor: pointer;
            color: #999;
            transition: all 0.3s;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        
        .close-modal:hover {
            color: #f44336;
            background-color: #ffebee;
        }
        
        .modal-title {
            font-size: 1.6em;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #2196f3;
            font-weight: 700;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .modal-title i {
            color: #2196f3;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
            font-size: 1.05em;
        }
        
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid #ddd;
            font-size: 1.05em;
            font-family: 'Cairo', sans-serif;
            transition: all 0.3s;
        }
        
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            border-color: #2196f3;
            box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.2);
            outline: none;
        }
        
        .form-group textarea {
            height: 120px;
            resize: vertical;
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 25px;
        }
        
        .form-actions button {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.1em;
            font-family: 'Cairo', sans-serif;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .cancel-button {
            background-color: #f5f5f5;
            color: #333;
        }
        
        .cancel-button:hover {
            background-color: #e0e0e0;
        }
        
        .submit-button {
            background-color: #2196f3;
            color: white;
        }
        
        .submit-button:hover {
            background-color: #1976d2;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(33, 150, 243, 0.3);
        }
        
        .alert {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 1.1em;
            animation: slideIn 0.3s ease-in-out;
        }
        
        @keyframes slideIn {
            from { transform: translateX(50px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        .alert-success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border-right: 4px solid #4caf50;
        }
        
        .alert-danger {
            background-color: #ffebee;
            color: #c62828;
            border-right: 4px solid #f44336;
        }
        
        .alert i {
            font-size: 1.5em;
        }
        
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 20px;
            text-align: center;
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        
        .stat-card.primary {
            border-top: 5px solid #2196f3;
        }
        
        .stat-card.danger {
            border-top: 5px solid #f44336;
        }
        
        .stat-card.success {
            border-top: 5px solid #4caf50;
        }
        
        .stat-card.warning {
            border-top: 5px solid #ff9800;
        }
        
        .stat-icon {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .stat-card.primary .stat-icon {
            color: #2196f3;
        }
        
        .stat-card.danger .stat-icon {
            color: #f44336;
        }
        
        .stat-card.success .stat-icon {
            color: #4caf50;
        }
        
        .stat-card.warning .stat-icon {
            color: #ff9800;
        }
        
        .stat-value {
            font-size: 2em;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 1.1em;
        }
        
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #999;
        }
        
        .empty-state i {
            font-size: 5em;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        .empty-state-text {
            font-size: 1.2em;
            margin-bottom: 20px;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }
        
        .page-link {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            color: #2196f3;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .page-link:hover {
            background-color: #e3f2fd;
        }
        
        .page-link.active {
            background-color: #2196f3;
            color: white;
            border-color: #2196f3;
        }
        
        @media (max-width: 992px) {
            .dashboard-container {
                grid-template-columns: 1fr;
            }
            
            .modal-content {
                width: 80%;
            }
        }
        
        @media (max-width: 768px) {
            .stats-cards {
                grid-template-columns: 1fr;
            }
            
            .modal-content {
                width: 95%;
                margin: 5% auto;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="top-nav">
            <div class="logo">
                <i class="fas fa-truck"></i>
                <span>نظام إدارة الموردين</span>
            </div>
            <div class="navigation">
                <a href="../app/main.php"><i class="fas fa-home"></i> الرئيسية</a>
                <a href="products.php"><i class="fas fa-box"></i> المنتجات</a>
                <a href="customers.php"><i class="fas fa-users"></i> العملاء</a>
            </div>
        </div>
        
        <div class="breadcrumb">
            <div class="breadcrumb-item">
                <a href="../app/main.php">الرئيسية</a>
            </div>
            <div class="breadcrumb-item active">الموردين</div>
        </div>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <div class="stats-cards">
            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="fas fa-truck"></i>
                </div>
                <div class="stat-value"><?php echo count($vendors); ?></div>
                <div class="stat-label">إجمالي الموردين</div>
            </div>
            
            <div class="stat-card danger">
                <div class="stat-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-value"><?php echo number_format($total_debts, 2); ?></div>
                <div class="stat-label">إجمالي الديون المستحقة</div>
            </div>
            
            <div class="stat-card warning">
                <div class="stat-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-value"><?php echo count($vendors_with_debts); ?></div>
                <div class="stat-label">الموردين بديون مستحقة</div>
            </div>
        </div>
        
        <div class="action-buttons">
            <button class="action-button primary-button" id="addVendorBtn">
                <i class="fas fa-plus"></i> إضافة مورد جديد
            </button>
            
            <button class="action-button info-button" id="showSearchBtn">
                <i class="fas fa-search"></i> بحث عن مورد
            </button>
        </div>
        
        <div class="dashboard-container">
            <div class="sidebar">
                <div class="section-title">
                    <i class="fas fa-truck"></i> قائمة الموردين
                </div>
                
                <div class="search-box">
                    <input type="text" id="vendorSearchInput" placeholder="بحث عن مورد...">
                    <i class="fas fa-search"></i>
                </div>
                
                <div class="vendor-list">
                    <?php if (empty($vendors)): ?>
                        <div class="empty-state">
                            <i class="fas fa-truck"></i>
                            <div class="empty-state-text">لا يوجد موردين</div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($vendors as $vendor): ?>
                            <div class="vendor-item <?php echo (isset($_GET['vendor_id']) && $_GET['vendor_id'] == $vendor['id']) ? 'active' : ''; ?>" data-id="<?php echo $vendor['id']; ?>">
                                <div class="vendor-name"><?php echo htmlspecialchars($vendor['name']); ?></div>
                                <div class="vendor-phone"><?php echo htmlspecialchars($vendor['phone']); ?></div>
                                <div class="vendor-debt">المبلغ المستحق: <?php echo number_format($vendor['amount_owed'], 2); ?> ج</div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="main-content">
                <?php if ($selected_vendor): ?>
                    <div class="section-title">
                        <i class="fas fa-info-circle"></i> تفاصيل المورد
                    </div>
                    
                    <div class="vendor-details">
                        <div class="detail-row">
                            <div class="detail-label">الاسم:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($selected_vendor['name']); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">رقم الهاتف:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($selected_vendor['phone']); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">العنوان:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($selected_vendor['address']); ?></div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
