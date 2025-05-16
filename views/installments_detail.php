<?php
include '../app/db.php';

// التحقق من وجود معرّف العميل
$customer_id = $_GET['customer_id'] ?? null;

if (!$customer_id) {
    echo "العميل غير موجود";
    exit();
}

// جلب بيانات العميل
    $sql = "SELECT * FROM customers WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$customer_id]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$customer) {
        echo "العميل غير موجود";
        exit();
    }

// جلب جميع الأقساط للعميل
$installments_sql = "SELECT * FROM installments WHERE customer_id = ? ORDER BY due_date ASC";
$installments_stmt = $conn->prepare($installments_sql);
$installments_stmt->execute([$customer_id]);
$installments = $installments_stmt->fetchAll(PDO::FETCH_ASSOC);

// التحقق إذا تم طلب تسديد آخر قسط عبر AJAX
if (isset($_GET['action']) && $_GET['action'] == 'pay_last_ajax') {
    // جلب آخر قسط غير مدفوع للعميل
    $last_installment_sql = "SELECT * FROM installments WHERE customer_id = ? AND paid = 0 ORDER BY due_date ASC LIMIT 1";
    $last_installment_stmt = $conn->prepare($last_installment_sql);
    $last_installment_stmt->execute([$customer_id]);
    $last_installment = $last_installment_stmt->fetch(PDO::FETCH_ASSOC);
    
    $response = ['success' => false, 'message' => ''];
    
    if ($last_installment) {
        try {
            // خصم المبلغ المدفوع من الحساب الأصلي
            $amount_to_pay = $last_installment['amount'];
            $new_account_price = $customer['account_price'] - $amount_to_pay;
            
            // تحديث حساب العميل
            $update_account_sql = "UPDATE customers SET account_price = ? WHERE id = ?";
            $update_account_stmt = $conn->prepare($update_account_sql);
            $update_account_stmt->execute([$new_account_price, $customer_id]);
            
            // تحديث حالة القسط ليصبح مدفوعًا
            $update_installment_sql = "UPDATE installments SET paid = 1, payment_date = NOW() WHERE id = ?";
            $update_installment_stmt = $conn->prepare($update_installment_sql);
            $update_installment_stmt->execute([$last_installment['id']]);
            
            // جلب بيانات العميل المحدثة
            $customer_sql = "SELECT * FROM customers WHERE id = ?";
            $customer_stmt = $conn->prepare($customer_sql);
            $customer_stmt->execute([$customer_id]);
            $updated_customer = $customer_stmt->fetch(PDO::FETCH_ASSOC);
            
            // جلب الأقساط المحدثة
            $installments_sql = "SELECT * FROM installments WHERE customer_id = ? ORDER BY due_date ASC";
            $installments_stmt = $conn->prepare($installments_sql);
            $installments_stmt->execute([$customer_id]);
            $updated_installments = $installments_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // حساب الإحصائيات المحدثة
            $total_installments = count($updated_installments);
            $paid_installments = 0;
            foreach ($updated_installments as $inst) {
                if ($inst['paid'] == 1) {
                    $paid_installments++;
                }
            }
            $percentage = ($total_installments > 0) ? ($paid_installments / $total_installments) * 100 : 0;
            
            // إعداد البيانات للرد
            $response = [
                'success' => true,
                'message' => 'تم تسديد القسط بنجاح',
                'customer' => $updated_customer,
                'paid_installment_id' => $last_installment['id'],
                'payment_date' => date('d/m/Y'),
                'account_price' => number_format($updated_customer['account_price'], 2),
                'paid_installments' => $paid_installments,
                'total_installments' => $total_installments,
                'percentage' => round($percentage)
            ];
        } catch (PDOException $e) {
            $response = ['success' => false, 'message' => 'حدث خطأ أثناء تسديد القسط: ' . $e->getMessage()];
        }
    } else {
        $response = ['success' => false, 'message' => 'لا توجد أقساط غير مدفوعة لهذا العميل'];
    }
    
    // إرجاع البيانات بصيغة JSON
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// التحقق إذا تم طلب تسديد قسط محدد عبر AJAX
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['pay_installment_ajax'])) {
    $installment_id = $_POST['installment_id'] ?? null;
    $response = ['success' => false, 'message' => ''];
    
    if ($installment_id) {
        try {
            // جلب بيانات القسط
            $sql = "SELECT * FROM installments WHERE id = ? AND customer_id = ? AND paid = 0";
    $stmt = $conn->prepare($sql);
            $stmt->execute([$installment_id, $customer_id]);
    $installment = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($installment) {
        // خصم المبلغ المدفوع من الحساب الأصلي
        $amount_to_pay = $installment['amount'];
        $new_account_price = $customer['account_price'] - $amount_to_pay;
                
                // تحقق من أن القيمة ليست سالبة
                if ($new_account_price < 0) {
                    $new_account_price = 0;
                }

        // تحديث حساب العميل
        $sql = "UPDATE customers SET account_price = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$new_account_price, $customer_id]);

        // تحديث حالة القسط ليصبح مدفوعًا
                $sql = "UPDATE installments SET paid = 1, payment_date = NOW() WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$installment_id]);
                
                // جلب بيانات العميل المحدثة
                $customer_sql = "SELECT * FROM customers WHERE id = ?";
                $customer_stmt = $conn->prepare($customer_sql);
                $customer_stmt->execute([$customer_id]);
                $updated_customer = $customer_stmt->fetch(PDO::FETCH_ASSOC);
                
                // حساب الإحصائيات المحدثة
                $installments_sql = "SELECT * FROM installments WHERE customer_id = ? ORDER BY due_date ASC";
                $installments_stmt = $conn->prepare($installments_sql);
                $installments_stmt->execute([$customer_id]);
                $updated_installments = $installments_stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $total_installments = count($updated_installments);
                $paid_installments = 0;
                foreach ($updated_installments as $inst) {
                    if ($inst['paid'] == 1) {
                        $paid_installments++;
                    }
                }
                $percentage = ($total_installments > 0) ? ($paid_installments / $total_installments) * 100 : 0;
                
                // إعداد البيانات للرد
                $response = [
                    'success' => true,
                    'message' => 'تم تسديد القسط بنجاح',
                    'customer' => $updated_customer,
                    'paid_installment_id' => $installment_id,
                    'payment_date' => date('d/m/Y'),
                    'account_price' => number_format($updated_customer['account_price'], 2),
                    'paid_installments' => $paid_installments,
                    'total_installments' => $total_installments,
                    'percentage' => round($percentage)
                ];
            } else {
                $response = ['success' => false, 'message' => 'لا يمكن العثور على القسط أو تم دفعه بالفعل'];
            }
        } catch (PDOException $e) {
            $response = ['success' => false, 'message' => 'حدث خطأ أثناء تسديد القسط: ' . $e->getMessage()];
        }
        
        // إرجاع البيانات بصيغة JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }
}

// التحقق إذا تم طلب إضافة مبلغ جديد للحساب
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_account'])) {
    $additional_amount = $_POST['additional_amount'] ?? 0;
    
    if ($additional_amount > 0) {
        try {
            // حساب المبلغ الجديد
            $new_account_price = $customer['account_price'] + $additional_amount;
            
            // تحديث حساب العميل
            $sql = "UPDATE customers SET account_price = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
            $stmt->execute([$new_account_price, $customer_id]);
            
            // إعادة تحميل الصفحة لتحديث البيانات
            header("Location: installments_detail.php?customer_id=$customer_id&added=success");
            exit();
        } catch (PDOException $e) {
            $error_message = "حدث خطأ أثناء إضافة المبلغ: " . $e->getMessage();
        }
    } else {
        $error_message = "يرجى إدخال مبلغ صحيح للإضافة";
    }
}

// التحقق إذا تم طلب تسديد آخر قسط عبر الطريقة العادية
if (isset($_GET['action']) && $_GET['action'] == 'pay_last') {
    // جلب آخر قسط غير مدفوع للعميل
    $last_installment_sql = "SELECT * FROM installments WHERE customer_id = ? AND paid = 0 ORDER BY due_date ASC LIMIT 1";
    $last_installment_stmt = $conn->prepare($last_installment_sql);
    $last_installment_stmt->execute([$customer_id]);
    $last_installment = $last_installment_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($last_installment) {
        // خصم المبلغ المدفوع من الحساب الأصلي
        $amount_to_pay = $last_installment['amount'];
        $new_account_price = $customer['account_price'] - $amount_to_pay;

        // تحديث حساب العميل
        $update_account_sql = "UPDATE customers SET account_price = ? WHERE id = ?";
        $update_account_stmt = $conn->prepare($update_account_sql);
        $update_account_stmt->execute([$new_account_price, $customer_id]);

        // تحديث حالة القسط ليصبح مدفوعًا
        $update_installment_sql = "UPDATE installments SET paid = 1, payment_date = NOW() WHERE id = ?";
        $update_installment_stmt = $conn->prepare($update_installment_sql);
        $update_installment_stmt->execute([$last_installment['id']]);

        // إعادة تحميل الصفحة لعرض التحديثات (بدون معلمة action)
        header("Location: installments_detail.php?customer_id=$customer_id&paid=success");
        exit();
    } else {
        $error_message = "لا توجد أقساط غير مدفوعة لهذا العميل";
    }
}

// التحقق إذا تم الضغط على زر دفع القسط بالطريقة العادية
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['pay_installment'])) {
    $installment_id = $_POST['installment_id'] ?? null;
    
    if ($installment_id) {
        // جلب بيانات القسط
        $sql = "SELECT * FROM installments WHERE id = ? AND customer_id = ? AND paid = 0";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$installment_id, $customer_id]);
        $installment = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($installment) {
            // خصم المبلغ المدفوع من الحساب الأصلي
            $amount_to_pay = $installment['amount'];
            $new_account_price = $customer['account_price'] - $amount_to_pay;
            
            // تحقق من أن القيمة ليست سالبة
            if ($new_account_price < 0) {
                $new_account_price = 0;
            }

            // تحديث حساب العميل
            $sql = "UPDATE customers SET account_price = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$new_account_price, $customer_id]);

            // تحديث حالة القسط ليصبح مدفوعًا
            $sql = "UPDATE installments SET paid = 1, payment_date = NOW() WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$installment_id]);

            // إعادة تحميل الصفحة لتحديث البيانات
            header("Location: installments_detail.php?customer_id=$customer_id&paid=success");
            exit();
        } else {
            $error_message = "لا يمكن العثور على القسط أو تم دفعه بالفعل";
        }
    } else {
        $error_message = "يرجى تحديد قسط للدفع";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تفاصيل الأقساط - <?php echo $customer['name']; ?></title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        :root {
            --primary-color: #36d03b;
            --primary-dark: #1a9c1f;
            --secondary-color: #2196f3;
            --secondary-dark: #1976d2;
            --text-color: #1a2b3c;
            --text-light: #7b8a9a;
            --bg-color: #f5f7fa;
            --card-bg: #fff;
            --border-color: #e3eafc;
            --green-light: #e3f7e4;
            --shadow-color: rgba(0,0,0,0.1);
            --header-gradient: linear-gradient(90deg, #36d03b, #2196f3);
            --button-gradient: linear-gradient(135deg, #36d03b, #1a9c1f);
        }
        
        .dark-mode {
            --primary-color: #4caf50;
            --primary-dark: #388e3c;
            --secondary-color: #42a5f5;
            --secondary-dark: #1976d2;
            --text-color: #e4e6f1;
            --text-light: #a4abc8;
            --bg-color: #121212;
            --card-bg: #1e1e1e;
            --border-color: #333;
            --green-light: rgba(76, 175, 80, 0.15);
            --shadow-color: rgba(0,0,0,0.3);
            --header-gradient: linear-gradient(90deg, #388e3c, #1976d2);
            --button-gradient: linear-gradient(135deg, #4caf50, #388e3c);
        }
        
        body, html {
            background-color: var(--bg-color);
            direction: rtl;
            font-family: 'Cairo', 'Segoe UI', Arial, sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            color: var(--text-color);
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        .layout-wrapper {
            display: flex;
            min-height: 100vh;
            width: 100%;
            position: relative;
        }
        
        .main-content-area {
            flex: 1;
            padding: 20px;
            margin-right: 280px;
            min-height: 100vh;
            z-index: 5;
            position: relative;
            transition: all 0.3s ease;
        }
        
        @media (max-width: 991px) {
            .main-content-area {
                margin-right: 0;
                width: 100%;
            }
        }
        
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            background: var(--card-bg);
            padding: 15px 20px;
            border-radius: 15px;
            box-shadow: 0 4px 15px var(--shadow-color);
        }
        
        .page-title {
            font-size: 1.8em;
            font-weight: 700;
            color: var(--primary-color);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .breadcrumb {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .breadcrumb-item {
            display: flex;
            align-items: center;
            margin-left: 10px;
            color: var(--text-light);
            font-size: 0.95em;
        }
        
        .breadcrumb-item:not(:last-child)::after {
            content: '\f104';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            margin-right: 10px;
            color: var(--text-light);
        }
        
        .breadcrumb-item a {
            color: var(--text-color);
            text-decoration: none;
            transition: color 0.3s ease;
            margin-right: 5px;
        }
        
        .breadcrumb-item a:hover {
            color: var(--primary-color);
        }
        
        .breadcrumb-item.active {
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .breadcrumb-item i {
            margin-left: 5px;
        }
        
        .customer-card {
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 4px 15px var(--shadow-color);
            margin-bottom: 25px;
            padding: 0;
            position: relative;
            overflow: hidden;
        }
        
        .customer-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100%;
            height: 5px;
            background: var(--header-gradient);
        }
        
        .customer-header {
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .customer-name {
            font-size: 1.6em;
            font-weight: 700;
            color: var(--text-color);
            margin: 0;
        }
        
        .customer-details {
            padding: 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .detail-group {
            margin-bottom: 15px;
        }
        
        .detail-label {
            font-weight: 600;
            color: var(--text-light);
            font-size: 0.9em;
            margin-bottom: 5px;
        }
        
        .detail-value {
            font-size: 1.1em;
            color: var(--text-color);
        }
        
        .detail-value.highlight {
            color: var(--primary-color);
            font-weight: 700;
        }
        
        .installments-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 20px;
        }
        
        .installments-table th {
            background: var(--bg-color);
            color: var(--text-color);
            font-weight: 600;
            padding: 15px;
            text-align: right;
            border-bottom: 2px solid var(--border-color);
        }
        
        .installments-table td {
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-color);
            transition: all 0.3s ease;
        }
        
        .installments-table tr {
            background-color: var(--card-bg);
            transition: all 0.2s ease;
        }
        
        .installments-table tr:hover {
            background-color: var(--bg-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px var(--shadow-color);
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 30px;
            font-size: 0.8em;
            font-weight: 700;
            text-align: center;
            min-width: 80px;
        }
        
        .status-paid {
            background-color: var(--green-light);
            color: var(--primary-color);
        }
        
        .status-unpaid {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-late {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .action-btn {
            border: none;
            border-radius: 6px;
            padding: 8px 12px;
            font-weight: 600;
            font-size: 0.85em;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 5px;
        }
        
        .pay-btn {
            background-color: var(--green-light);
            color: var(--primary-color);
        }
        
        .pay-btn:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .edit-btn {
            background-color: #e3f2fd;
            color: var(--secondary-color);
        }
        
        .edit-btn:hover {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .delete-btn {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .delete-btn:hover {
            background-color: #dc3545;
            color: white;
        }
        
        .success-message {
            background-color: var(--green-light);
            color: var(--primary-color);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        .success-message i {
            margin-left: 10px;
            font-size: 20px;
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        .error-message i {
            margin-left: 10px;
            font-size: 20px;
        }
        
        .header-actions {
            display: flex;
            gap: 10px;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background-color: #f1f3f6;
            color: var(--text-color);
            padding: 10px 15px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .back-btn:hover {
            background-color: var(--border-color);
            transform: translateY(-2px);
        }
        
        .add-installment-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: var(--button-gradient);
            color: white;
            padding: 10px 15px;
            border-radius: 8px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .add-installment-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(54, 208, 59, 0.3);
        }
        
        .add-amount-wrapper {
            background-color: var(--green-light);
            padding: 15px 20px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .card-subheader {
            margin-bottom: 10px;
        }
        
        .subheader-title {
            font-size: 1.2em;
            color: var(--primary-color);
            margin: 0;
        }
        
        .quick-actions-panel {
            display: flex;
            align-items: center;
        }
        
        .add-amount-form {
            display: flex;
            gap: 10px;
            align-items: center;
            width: 100%;
        }
        
        .amount-input-group {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
        }
        
        .amount-label {
            font-weight: 600;
            white-space: nowrap;
        }
        
        .add-amount-input {
            height: 38px;
            padding: 0 10px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            background-color: var(--bg-color);
            color: var(--text-color);
            font-family: inherit;
            flex: 1;
        }
        
        .add-amount-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: var(--button-gradient);
            color: white;
            padding: 8px 15px;
            border-radius: 8px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
        }
        
        .installment-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: linear-gradient(135deg, var(--secondary-color), var(--secondary-dark));
            color: white;
            padding: 8px 15px;
            border-radius: 8px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            white-space: nowrap;
        }
        
        .add-amount-btn:hover, .installment-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        
        .progress-bar-container {
            height: 15px;
            background-color: var(--bg-color);
            border-radius: 8px;
            margin-top: 10px;
            position: relative;
            overflow: hidden;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .progress-bar {
            height: 100%;
            background: var(--button-gradient);
            border-radius: 8px;
            transition: width 0.5s ease;
            position: relative;
            overflow: hidden;
        }
        
        .progress-bar::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(
                45deg,
                rgba(255, 255, 255, 0.2) 25%,
                transparent 25%,
                transparent 50%,
                rgba(255, 255, 255, 0.2) 50%,
                rgba(255, 255, 255, 0.2) 75%,
                transparent 75%,
                transparent
            );
            background-size: 25px 25px;
            animation: move 2s linear infinite;
            border-radius: 8px;
        }
        
        @keyframes move {
            0% {
                background-position: 0 0;
            }
            100% {
                background-position: 50px 50px;
            }
        }
        
        .progress-text {
            position: absolute;
            top: -2px;
            right: 10px;
            font-size: 0.85em;
            color: var(--text-color);
            font-weight: 700;
            text-shadow: 0 0 3px rgba(255,255,255,0.7);
        }
        
        .text-muted {
            color: var(--text-light);
            font-weight: normal;
        }
        
        .action-btn.pay-last-btn {
            background-color: #e3f7e4;
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }
        
        .action-btn.pay-last-btn:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .action-btn.print-btn {
            background-color: #e3f2fd;
            color: var(--secondary-color);
            border: 1px solid var(--secondary-color);
        }
        
        .action-btn.print-btn:hover {
            background-color: var(--secondary-color);
            color: white;
        }
        
        
        @media (max-width: 768px) {
            .customer-details {
                grid-template-columns: 1fr;
            }
            
            .installments-table {
                display: block;
                overflow-x: auto;
            }
        }
        
        /* إضافة ستايل للتبويبات */
        .customer-info-tabs {
            padding: 0;
        }
        
        .tabs-nav {
            display: flex;
            border-bottom: 1px solid var(--border-color);
            background-color: var(--bg-color);
            overflow-x: auto;
            white-space: nowrap;
            padding: 0 15px;
        }
        
        .tab-btn {
            padding: 15px 20px;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            color: var(--text-light);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: inherit;
            font-size: 1em;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .tab-btn:hover {
            color: var(--primary-color);
        }
        
        .tab-btn.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }
        
        .tab-content {
            display: none;
            padding: 20px;
        }
        
        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        /* ستايل للمرفقات والملاحظات */
        .attachment-preview {
            margin-bottom: 20px;
        }
        
        .attachment-thumbnail {
            max-width: 200px;
            max-height: 150px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            box-shadow: 0 2px 5px var(--shadow-color);
            transition: transform 0.3s ease;
        }
        
        .attachment-thumbnail:hover {
            transform: scale(1.05);
        }
        
        .attachment-link {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: var(--text-color);
            font-weight: 600;
            transition: color 0.3s ease;
        }
        
        .attachment-link:hover {
            color: var(--primary-color);
        }
        
        .file-icon {
            font-size: 3em;
            margin-bottom: 10px;
            color: var(--secondary-color);
        }
        
        .notes-section {
            padding: 15px;
            background-color: var(--bg-color);
            border-radius: 8px;
            border: 1px solid var(--border-color);
            max-height: 200px;
            overflow-y: auto;
            line-height: 1.6;
        }
    </style>
</head>

<body>
    <div class="layout-wrapper">
        <?php include '../app/includes/sidebar.php'; ?>
        
        <div class="main-content-area">
            
            <!-- قائمة التنقل الأفقية -->
            <div class="breadcrumb">
                <div class="breadcrumb-item">
                    <i class="fas fa-home"></i>
                    <a href="../app/main.php">الرئيسية</a>
                </div>
                <div class="breadcrumb-item">
                    <i class="fas fa-calculator"></i>
                    <a href="installments.php">الأقساط</a>
                </div>
                <div class="breadcrumb-item active">
                    <i class="fas fa-user"></i>
                    تفاصيل العميل
                </div>
            </div>
            
            
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-user"></i>
                    تفاصيل الأقساط -+ <?php echo htmlspecialchars($customer['name']); ?>
                </h1>
                
                <div class="header-actions">
                    <a href="installments.php" class="back-btn">
                        <i class="fas fa-arrow-right"></i>
                        العودة للقائمة
                    </a>
                    <a href="add_installment.php?customer_id=<?php echo $customer_id; ?>" class="add-installment-btn">
                        <i class="fas fa-plus"></i>
                        إضافة قسط جديد
                    </a>
                </div>
            </div>
            
            <?php if (isset($_GET['paid']) && $_GET['paid'] == 'success'): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    تم تسديد القسط بنجاح
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['added']) && $_GET['added'] == 'success'): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    تم إضافة المبلغ إلى حساب العميل بنجاح
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            
            <div class="customer-card">
                <div class="customer-header">
                    <h2 class="customer-name"><?php echo htmlspecialchars($customer['name']); ?></h2>
                    <div class="customer-id">رقم العميل: <?php echo $customer['id']; ?></div>
                </div>
                
                
                <div class="add-amount-wrapper">
                    <div class="card-subheader">
                        <h3 class="subheader-title">إضافة مبلغ للحساب</h3>
                    </div>
                    <div class="quick-actions-panel">
                        <form method="POST" action="" class="add-amount-form">
                            <div class="amount-input-group">
                                <label for="additional_amount" class="amount-label">المبلغ الجديد:</label>
                                <input type="number" name="additional_amount" id="additional_amount" class="add-amount-input" placeholder="أدخل المبلغ" min="1" step="any" required>
                            </div>
                            <button type="submit" name="add_to_account" class="add-amount-btn">
                                <i class="fas fa-plus-circle"></i> إضافة للحساب
                            </button>
                            <a href="add_installment.php?customer_id=<?php echo $customer_id; ?>" class="installment-btn">
                                <i class="fas fa-calendar-plus"></i> تقسيط المبلغ
                            </a>
</form>
                    </div>
                </div>
                
                <!-- معلومات العميل المحسنة -->
                <div class="customer-info-tabs">
                    <div class="tabs-nav">
                        <button class="tab-btn active" data-tab="basic-info">
                            <i class="fas fa-user"></i> المعلومات الأساسية
                        </button>
                        <button class="tab-btn" data-tab="financial-info">
                            <i class="fas fa-money-bill-wave"></i> المعلومات المالية
                        </button>
                        <button class="tab-btn" data-tab="attachments-notes">
                            <i class="fas fa-paperclip"></i> المرفقات والملاحظات
                        </button>
                    </div>
                    
                    <div class="tab-content active" id="basic-info">
                <div class="customer-details">
                    <div>
                        <div class="detail-group">
                            <div class="detail-label">رقم الهاتف</div>
                            <div class="detail-value">
                                <?php echo htmlspecialchars($customer['phone'] ?? 'غير متوفر'); ?>
                            </div>
                        </div>
                        <div class="detail-group">
                            <div class="detail-label">البريد الإلكتروني</div>
                            <div class="detail-value">
                                <?php echo htmlspecialchars($customer['email'] ?? 'غير متوفر'); ?>
                            </div>
                        </div>
                    </div>
                    <div>
                                <div class="detail-group">
                                    <div class="detail-label">الرقم القومي</div>
                                    <div class="detail-value">
                                        <?php echo htmlspecialchars($customer['national_id'] ?? 'غير متوفر'); ?>
                                    </div>
                                </div>
                        <div class="detail-group">
                            <div class="detail-label">العنوان</div>
                            <div class="detail-value">
                                <?php echo htmlspecialchars($customer['address'] ?? 'غير متوفر'); ?>
                            </div>
                        </div>
                            </div>
                            <div>
                        <div class="detail-group">
                            <div class="detail-label">تاريخ التسجيل</div>
                            <div class="detail-value">
                                <?php echo isset($customer['created_at']) ? date('d/m/Y', strtotime($customer['created_at'])) : 'غير متوفر'; ?>
                            </div>
                        </div>
                    </div>
                        </div>
                    </div>
                    
                    <div class="tab-content" id="financial-info">
                        <div class="customer-details">
                    <div>
                        <div class="detail-group">
                                    <div class="detail-label">السعر الأصلي</div>
                                    <div class="detail-value highlight">
                                        <?php echo number_format($customer['original_price'] ?? 0, 2); ?> جنيه
                                    </div>
                                </div>
                                <div class="detail-group">
                                    <div class="detail-label">نسبة الفائدة</div>
                                    <div class="detail-value">
                                        <?php echo number_format($customer['interest_rate'] ?? 0, 2); ?>%
                                    </div>
                                </div>
                            </div>
                            <div>
                                <div class="detail-group">
                                    <div class="detail-label">قيمة الحساب الإجمالية (مع الفائدة)</div>
                            <div class="detail-value highlight">
                                <?php echo number_format($customer['account_price'] ?? 0, 2); ?> جنيه
                            </div>
                        </div>
                                <div class="detail-group">
                                    <div class="detail-label">معاد الدفع</div>
                                    <div class="detail-value">
                                        <?php 
                                        $period_names = [
                                            'weekly' => 'أسبوعي',
                                            'monthly' => 'شهري',
                                            'biannual' => 'نصف سنوي',
                                            'annual' => 'سنوي'
                                        ];
                                        echo $period_names[$customer['payment_period'] ?? 'monthly'] ?? 'شهري'; 
                                        ?>
                                    </div>
                                </div>
                            </div>
                            <div>
                        <div class="detail-group">
                            <div class="detail-label">عدد الأقساط</div>
                            <div class="detail-value highlight">
                                <?php 
                                $total_installments = count($installments);
                                $paid_installments = 0;
                                foreach ($installments as $inst) {
                                    if ($inst['paid'] == 1) {
                                        $paid_installments++;
                                    }
                                }
                                echo "$paid_installments <small class='text-muted'>من</small> $total_installments قسط";
                                
                                // Add progress bar only if there are installments
                                if ($total_installments > 0):
                                $percentage = ($paid_installments / $total_installments) * 100;
                                ?>
                                <div class="progress-bar-container">
                                    <div class="progress-bar" style="width: <?php echo $percentage; ?>%"></div>
                                    <div class="progress-text"><?php echo round($percentage); ?>% مكتمل</div>
                                </div>
                                <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="tab-content" id="attachments-notes">
                        <div class="customer-details">
                            <?php if (!empty($customer['attachments'])): ?>
                                <div class="detail-group attachment-preview">
                                    <div class="detail-label">المرفقات</div>
                                    <div class="detail-value">
                                        <?php 
                                        $file_path = '../uploads/customers/' . $customer['attachments'];
                                        $file_extension = pathinfo($file_path, PATHINFO_EXTENSION);
                                        $is_image = in_array(strtolower($file_extension), ['jpg', 'jpeg', 'png', 'gif']);
                                        
                                        if ($is_image): 
                                        ?>
                                            <a href="<?php echo $file_path; ?>" target="_blank" class="attachment-link">
                                                <img src="<?php echo $file_path; ?>" alt="مرفق" class="attachment-thumbnail">
                                            </a>
                                        <?php else: ?>
                                            <a href="<?php echo $file_path; ?>" target="_blank" class="attachment-link">
                                                <div class="file-icon">
                                                    <i class="fas fa-file-<?php echo strtolower($file_extension) === 'pdf' ? 'pdf' : 'alt'; ?>"></i>
                                                </div>
                                                <span>عرض المرفق</span>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="detail-group">
                                    <div class="detail-label">المرفقات</div>
                                    <div class="detail-value">لا توجد مرفقات</div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="detail-group">
                                <div class="detail-label">ملاحظات</div>
                                <div class="detail-value notes-section">
                                    <?php if (!empty($customer['notes'])): ?>
                                        <?php echo nl2br(htmlspecialchars($customer['notes'])); ?>
                                    <?php else: ?>
                                        <em>لا توجد ملاحظات مضافة</em>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            
            <div class="customer-card">
                <div class="customer-header">
                    <h2 class="customer-name">الأقساط</h2>
                    <div class="header-actions">
                        <a href="#" onclick="payLastInstallmentAjax(); return false;" class="action-btn pay-last-btn">
                            <i class="fas fa-money-bill-wave"></i> تسديد آخر قسط
                        </a>
                        <noscript>
                            <a href="installments_detail.php?customer_id=<?php echo $customer_id; ?>&action=pay_last" class="action-btn pay-last-btn">
                                <i class="fas fa-money-bill-wave"></i> تسديد آخر قسط
                            </a>
                        </noscript>
                        <a href="#" onclick="printInstallments(<?php echo $customer_id; ?>)" class="action-btn print-btn">
                            <i class="fas fa-print"></i> طباعة
                        </a>
                        <noscript>
                            <a href="print_installments.php?customer_id=<?php echo $customer_id; ?>" target="_blank" class="action-btn print-btn">
                                <i class="fas fa-print"></i> طباعة
                            </a>
                        </noscript>
                    </div>
                </div>
                
                <?php if (empty($installments)): ?>
                    <div style="padding: 20px; text-align: center; color: var(--text-light);">
                        <i class="fas fa-calculator" style="font-size: 3em; margin-bottom: 10px; opacity: 0.3;"></i>
                        <p>لا توجد أقساط مسجلة لهذا العميل</p>
                    </div>
                <?php else: ?>
                    <div style="padding: 20px; overflow-x: auto;">
                        <table class="installments-table">
    <thead>
        <tr>
            <th>رقم القسط</th>
                                    <th>تاريخ الاستحقاق</th>
            <th>المبلغ</th>
                                    <th>الحالة</th>
                                    <th>تاريخ الدفع</th>
            <th>الفائدة (%)</th>
                                    <th>الإجراءات</th>
        </tr>
    </thead>
    <tbody>
                                <?php 
                                $today = date('Y-m-d');
                                foreach ($installments as $installment): 
                                    // تحديد حالة القسط
                                    $status = '';
                                    $status_class = '';
                                    
                                    if ($installment['paid'] == 1) {
                                        $status = 'مدفوع';
                                        $status_class = 'status-paid';
                                    } else {
                                        if ($installment['due_date'] < $today) {
                                            $status = 'متأخر';
                                            $status_class = 'status-late';
                                        } else {
                                            $status = 'غير مدفوع';
                                            $status_class = 'status-unpaid';
                                        }
                                    }
                                ?>
                                <tr data-id="<?php echo $installment['id']; ?>">
                    <td><?php echo $installment['id']; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($installment['due_date'])); ?></td>
                                    <td><?php echo number_format($installment['amount'], 2); ?> جنيه</td>
                                    <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $status; ?></span></td>
                                    <td>
                                        <?php 
                                        if ($installment['paid'] == 1 && isset($installment['payment_date'])) {
                                            echo date('d/m/Y', strtotime($installment['payment_date']));
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo $installment['interest_rate'] ? $installment['interest_rate'] . '%' : '-'; ?></td>
                                    <td>
                                        <?php if ($installment['paid'] == 0): ?>
                                            <button onclick="payInstallment(<?php echo $installment['id']; ?>)" class="action-btn pay-btn">
                                                <i class="fas fa-money-bill-wave"></i> دفع
                                            </button>
                                            <a href="edit_installment.php?id=<?php echo $installment['id']; ?>&customer_id=<?php echo $customer_id; ?>" class="action-btn edit-btn">
                                                <i class="fas fa-edit"></i> تعديل
                                            </a>
                                            <button onclick="if(confirm('هل أنت متأكد من حذف هذا القسط؟')) window.location.href='delete_installment.php?id=<?php echo $installment['id']; ?>&customer_id=<?php echo $customer_id; ?>'" class="action-btn delete-btn">
                                                <i class="fas fa-trash-alt"></i> حذف
                                            </button>
                                        <?php else: ?>
                                            <button class="action-btn edit-btn" disabled>
                                                <i class="fas fa-check"></i> تم الدفع
                                            </button>
                                        <?php endif; ?>
                                    </td>
                </tr>
            <?php endforeach; ?>
    </tbody>
</table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // تبديل وضع الثيم الداكن/الفاتح
            const isDarkMode = localStorage.getItem('darkMode') === 'true';
            if (isDarkMode) {
                document.body.classList.add('dark-mode');
            }
            
            // وظيفة دفع آخر قسط عن طريق AJAX
            window.payLastInstallmentAjax = function() {
                if (confirm('هل أنت متأكد من رغبتك في تسديد آخر قسط؟')) {
                    fetch('installments_detail.php?customer_id=<?php echo $customer_id; ?>&action=pay_last_ajax')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // تحديث واجهة المستخدم
                            const paidInstallment = document.querySelector(`.installments-table tr[data-id="${data.paid_installment_id}"]`);
                            if (paidInstallment) {
                                // تحديث حالة القسط
                                const statusCell = paidInstallment.querySelector('td:nth-child(4)');
                                if (statusCell) {
                                    statusCell.innerHTML = '<span class="status-badge status-paid">مدفوع</span>';
                                }
                                
                                // تحديث تاريخ الدفع
                                const dateCell = paidInstallment.querySelector('td:nth-child(5)');
                                if (dateCell) {
                                    dateCell.textContent = data.payment_date;
                                }
                                
                                // تحديث أزرار الإجراءات
                                const actionsCell = paidInstallment.querySelector('td:nth-child(7)');
                                if (actionsCell) {
                                    actionsCell.innerHTML = '<button class="action-btn edit-btn" disabled><i class="fas fa-check"></i> تم الدفع</button>';
                                }
                            }
                            
                            // تحديث قيمة الحساب
                            document.querySelectorAll('.detail-value.highlight').forEach(el => {
                                if (el.textContent.includes('جنيه')) {
                                    el.textContent = data.account_price + ' جنيه';
                                }
                            });
                            
                            // تحديث عدد الأقساط المدفوعة
                            const installmentsCountEl = document.querySelector('.detail-value.highlight:last-child');
                            if (installmentsCountEl) {
                                installmentsCountEl.innerHTML = data.paid_installments + ' <small class="text-muted">من</small> ' + data.total_installments + ' قسط';
                                
                                // تحديث شريط التقدم
                                const progressBar = installmentsCountEl.querySelector('.progress-bar');
                                const progressText = installmentsCountEl.querySelector('.progress-text');
                                if (progressBar && progressText) {
                                    progressBar.style.width = data.percentage + '%';
                                    progressText.textContent = data.percentage + '% مكتمل';
                                }
                            }
                            
                        // عرض رسالة نجاح
                            const successMessage = document.createElement('div');
                            successMessage.className = 'success-message';
                            successMessage.innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message;
                            
                            const pageHeader = document.querySelector('.page-header');
                            pageHeader.insertAdjacentElement('afterend', successMessage);
                            
                            // إزالة الرسالة بعد 3 ثوانٍ
                            setTimeout(() => {
                                successMessage.remove();
                            }, 3000);
                    } else {
                        // عرض رسالة خطأ
                            alert(data.message);
                    }
                })
                .catch(error => {
                        console.error('Error:', error);
                        alert('حدث خطأ أثناء معالجة الطلب');
                    });
                }
            };
            
            // وظيفة دفع قسط محدد عن طريق AJAX
            window.payInstallment = function(installmentId) {
                if (confirm('هل أنت متأكد من رغبتك في تسديد هذا القسط؟')) {
            const formData = new FormData();
            formData.append('installment_id', installmentId);
                    formData.append('pay_installment_ajax', true);
            
            fetch('installments_detail.php?customer_id=<?php echo $customer_id; ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                            // تحديث واجهة المستخدم بنفس الطريقة كما في وظيفة دفع آخر قسط
                            const paidInstallment = document.querySelector(`.installments-table tr[data-id="${installmentId}"]`);
                            if (paidInstallment) {
                                const statusCell = paidInstallment.querySelector('td:nth-child(4)');
                                if (statusCell) {
                                    statusCell.innerHTML = '<span class="status-badge status-paid">مدفوع</span>';
                                }
                                
                                const dateCell = paidInstallment.querySelector('td:nth-child(5)');
                                if (dateCell) {
                                    dateCell.textContent = data.payment_date;
                                }
                                
                                const actionsCell = paidInstallment.querySelector('td:nth-child(7)');
                                if (actionsCell) {
                                    actionsCell.innerHTML = '<button class="action-btn edit-btn" disabled><i class="fas fa-check"></i> تم الدفع</button>';
                                }
                            }
                            
                            // تحديث القيم الأخرى كما في وظيفة دفع آخر قسط
                            // ...
                            
                            // عرض رسالة نجاح
                            const successMessage = document.createElement('div');
                            successMessage.className = 'success-message';
                            successMessage.innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message;
                            
                            const pageHeader = document.querySelector('.page-header');
                            pageHeader.insertAdjacentElement('afterend', successMessage);
                            
                            // إزالة الرسالة بعد 3 ثوانٍ
                            setTimeout(() => {
                                successMessage.remove();
                            }, 3000);
                        } else {
                            // عرض رسالة خطأ
                            alert(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('حدث خطأ أثناء معالجة الطلب');
                    });
                }
            };
            
            // وظيفة طباعة جدول الأقساط
            window.printInstallments = function(customerId) {
                window.open('print_installments.php?customer_id=' + customerId, '_blank');
            };

            // إدارة التبويبات
            const tabButtons = document.querySelectorAll('.tab-btn');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // إزالة الفئة النشطة من جميع الأزرار والمحتويات
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));
                    
                    // إضافة الفئة النشطة للزر المضغوط والمحتوى المرتبط به
                    const tabId = this.getAttribute('data-tab');
                    this.classList.add('active');
                    document.getElementById(tabId).classList.add('active');
                });
            });
        });
    </script>
</body>
</html>
            