<?php
session_start();
include '../app/db.php'; // الاتصال بقاعدة البيانات

// التحقق من الجلسة
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // إذا لم يكن المستخدم مسجل الدخول
    exit();
}

// إنشاء سلة المشتريات إذا لم تكن موجودة
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// جلب جميع المنتجات من قاعدة البيانات
try {
    $products_sql = "SELECT * FROM products ORDER BY name ASC";
    $products_stmt = $conn->prepare($products_sql);
    $products_stmt->execute();
    $all_products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("خطأ في استعلام المنتجات: " . $e->getMessage());
    $all_products = [];
}

// إضافة منتج مخصص للسلة (منتج غير موجود في المخزون)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_custom_product'])) {
    $product_name = trim($_POST['custom_product_name']);
    $product_price = floatval($_POST['custom_product_price']);
    $quantity = intval($_POST['custom_product_quantity']);
    
    if (empty($product_name)) {
        $error_message = "يرجى إدخال اسم المنتج المخصص";
    } elseif ($product_price <= 0) {
        $error_message = "يرجى إدخال سعر صحيح للمنتج";
    } elseif ($quantity <= 0) {
        $error_message = "يرجى إدخال كمية صحيحة";
    } else {
        // إضافة المنتج المخصص إلى السلة
        $_SESSION['cart'][] = [
            'id' => 'custom_' . time(), // معرف مؤقت للمنتج المخصص
            'name' => $product_name . ' (مخصص)',
            'price' => $product_price,
            'quantity' => $quantity,
            'total' => $product_price * $quantity,
            'is_custom' => true
        ];
        
        $success_message = "تمت إضافة المنتج المخصص إلى السلة";
    }
}

// إضافة منتج للسلة
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    $product_id = $_POST['product_id'];
    $quantity = intval($_POST['quantity']);
    
    if ($quantity <= 0) {
        $error_message = "يرجى إدخال كمية صحيحة";
    } else {
        // التحقق من وجود المنتج
        $product_sql = "SELECT * FROM products WHERE id = ?";
        $product_stmt = $conn->prepare($product_sql);
        $product_stmt->execute([$product_id]);
        $product = $product_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product) {
            // التحقق من توفر الكمية
            if ($product['quantity'] >= $quantity) {
                // إضافة المنتج إلى السلة أو تحديث الكمية إذا كان موجودًا
                $product_exists = false;
                
                foreach ($_SESSION['cart'] as $key => $item) {
                    if ($item['id'] == $product_id) {
                        $_SESSION['cart'][$key]['quantity'] += $quantity;
                        $_SESSION['cart'][$key]['total'] = $_SESSION['cart'][$key]['price'] * $_SESSION['cart'][$key]['quantity'];
                        $product_exists = true;
                        break;
                    }
                }
                
                if (!$product_exists) {
                    $_SESSION['cart'][] = [
                        'id' => $product['id'],
                        'name' => $product['name'],
                        'price' => $product['price'],
                        'quantity' => $quantity,
                        'total' => $product['price'] * $quantity,
                        'is_custom' => false
                    ];
                }
                
                $success_message = "تمت إضافة المنتج إلى السلة";
            } else {
                $error_message = "الكمية المطلوبة غير متوفرة. الكمية المتاحة: " . $product['quantity'];
            }
        } else {
            $error_message = "المنتج غير موجود";
        }
    }
}

// حذف منتج من السلة
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['remove_product'])) {
    $index = $_POST['cart_index'];
    if (isset($_SESSION['cart'][$index])) {
        array_splice($_SESSION['cart'], $index, 1);
        $success_message = "تم حذف المنتج من السلة";
    }
}

// تفريغ السلة
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['clear_cart'])) {
    $_SESSION['cart'] = [];
    $success_message = "تم تفريغ السلة";
}

// حساب إجمالي السلة
$cart_total = 0;
foreach ($_SESSION['cart'] as $item) {
    $cart_total += $item['total'];
}

// إتمام الطلب
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['complete_order'])) {
    if (empty($_SESSION['cart'])) {
        $error_message = "السلة فارغة. يرجى إضافة منتجات قبل إتمام الطلب";
    } else {
        try {
            // بدء المعاملة
            $conn->beginTransaction();
            
            // إنشاء سجل المبيعات
            $sale_sql = "INSERT INTO sales (total_amount, payment_method, created_at) VALUES (?, ?, NOW())";
            $sale_stmt = $conn->prepare($sale_sql);
            $sale_stmt->execute([$cart_total, 'cash']);
            $sale_id = $conn->lastInsertId();
            
            // إضافة تفاصيل المبيعات
            foreach ($_SESSION['cart'] as $item) {
                if (!isset($item['is_custom']) || !$item['is_custom']) {
                    // منتج عادي من المخزون
                    $detail_sql = "INSERT INTO sale_details (sale_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
                    $detail_stmt = $conn->prepare($detail_sql);
                    $detail_stmt->execute([$sale_id, $item['id'], $item['quantity'], $item['price']]);
                    
                    // تحديث المخزون
                    $update_sql = "UPDATE products SET quantity = quantity - ? WHERE id = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->execute([$item['quantity'], $item['id']]);
                } else {
                    // منتج مخصص (لا يتم تحديث المخزون)
                    $detail_sql = "INSERT INTO sale_details (sale_id, product_name, quantity, price, is_custom) VALUES (?, ?, ?, ?, 1)";
                    $detail_stmt = $conn->prepare($detail_sql);
                    $detail_stmt->execute([$sale_id, $item['name'], $item['quantity'], $item['price']]);
                }
            }
            
            // إتمام المعاملة
            $conn->commit();
            
            // تفريغ السلة بعد إتمام الطلب
            $_SESSION['cart'] = [];
            $success_message = "تم إتمام الطلب بنجاح. رقم الطلب: " . $sale_id;
            
        } catch (PDOException $e) {
            // التراجع عن المعاملة في حالة حدوث خطأ
            $conn->rollBack();
            $error_message = "حدث خطأ أثناء معالجة الطلب: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الطلبات</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        body, html {
            height: 100%;
            margin: 0;
            padding: 0;
            font-size: 16px;
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
        
        .order-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            height: calc(100vh - 150px);
        }
        
        .product-section {
            flex: 1;
            min-width: 400px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 20px;
            display: flex;
            flex-direction: column;
        }
        
        .cart-section {
            flex: 1;
            min-width: 400px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 20px;
            display: flex;
            flex-direction: column;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        
        .section-header h3 {
            font-size: 1.8em;
            margin: 0;
            text-align: right;
        }
        
        .search-container {
            margin-bottom: 20px;
            position: relative;
        }
        
        .search-container i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #777;
        }
        
        .search-input {
            width: 100%;
            padding: 15px 40px 15px 15px;
            border-radius: 30px;
            border: 1px solid #ddd;
            font-size: 1.1em;
            transition: all 0.3s;
        }
        
        .search-input:focus {
            border-color: #4CAF50;
            box-shadow: 0 0 8px rgba(76, 175, 80, 0.3);
            outline: none;
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 20px;
            margin-top: 20px;
            overflow-y: auto;
            flex: 1;
            padding-right: 5px;
        }
        
        .product-card {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
            border: 1px solid #eee;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .product-card img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            margin-bottom: 15px;
            border-radius: 5px;
        }
        
        .product-card h4 {
            font-size: 1.2em;
            margin: 5px 0;
            color: #333;
        }
        
        .product-card .price {
            font-weight: bold;
            color: #4CAF50;
            margin: 10px 0;
            font-size: 1.1em;
        }
        
        .product-card .stock {
            font-size: 1em;
            color: #777;
        }
        
        .product-card .low-stock {
            color: #f44336;
        }
        
        .cart-items {
            flex: 1;
            overflow-y: auto;
            margin-bottom: 20px;
            padding-right: 5px;
        }
        
        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #eee;
            margin-bottom: 10px;
        }
        
        .cart-item-details {
            flex: 1;
        }
        
        .cart-item-name {
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 1.1em;
        }
        
        .cart-item-price {
            color: #777;
            font-size: 1em;
        }
        
        .cart-item-quantity {
            display: flex;
            align-items: center;
            margin: 0 15px;
            font-size: 1.1em;
        }
        
        .quantity-btn {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            border: none;
            background-color: #f1f1f1;
            color: #333;
            font-size: 1.2em;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: all 0.3s;
        }
        
        .quantity-btn:hover {
            background-color: #e0e0e0;
        }
        
        .cart-item-quantity input {
            width: 60px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin: 0 5px;
            padding: 8px;
            font-size: 1em;
        }
        
        .cart-item-total {
            font-weight: bold;
            color: #4CAF50;
            min-width: 100px;
            text-align: left;
            font-size: 1.1em;
        }
        
        .cart-item-remove {
            background-color: transparent;
            border: none;
            color: #f44336;
            cursor: pointer;
            font-size: 1.3em;
            padding: 8px;
            transition: all 0.3s;
        }
        
        .cart-item-remove:hover {
            color: #d32f2f;
            transform: scale(1.2);
        }
        
        .empty-cart {
            text-align: center;
            padding: 40px;
            color: #777;
        }
        
        .empty-cart i {
            font-size: 4em;
            margin-bottom: 15px;
            color: #ddd;
        }
        
        .empty-cart p {
            font-size: 1.2em;
        }
        
        .cart-summary {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 1.1em;
        }
        
        .summary-row.total {
            font-size: 1.4em;
            font-weight: bold;
            border-top: 1px solid #ddd;
            padding-top: 15px;
            margin-top: 15px;
        }
        
        .cart-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        
        .clear-btn {
            background-color: #f44336;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 12px 25px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 1.1em;
        }
        
        .clear-btn:hover {
            background-color: #d32f2f;
        }
        
        .checkout-btn {
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 12px 25px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 1.1em;
        }
        
        .checkout-btn:hover {
            background-color: #388e3c;
        }
        
        .checkout-btn:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-size: 1.1em;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .add-product-form {
            margin-top: 20px;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            font-size: 1.1em;
        }
        
        .form-group select, .form-group input {
            width: 100%;
            padding: 12px;
            border-radius: 5px;
            border: 1px solid #ddd;
            font-size: 1.1em;
        }
        
        .top-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .logo {
            display: flex;
            align-items: center;
            font-size: 1.8em;
            font-weight: bold;
            color: #333;
        }
        
        .logo i {
            margin-left: 15px;
            color: #4CAF50;
        }
        
        .navigation a {
            margin-right: 20px;
            color: #333;
            text-decoration: none;
            transition: all 0.3s;
            font-size: 1.2em;
        }
        
        .navigation a:hover {
            color: #4CAF50;
        }
        
        .navigation a i {
            margin-left: 8px;
        }
        
        .custom-product-note {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            display: flex;
            align-items: center;
        }
        
        .custom-product-note i {
            font-size: 1.5em;
            margin-left: 15px;
            color: #ffc107;
        }
        
        .custom-product-note p {
            margin: 0;
            font-size: 1.1em;
        }
        
        .custom-product-btn {
            background-color: #ffc107;
            color: #856404;
            border: none;
            border-radius: 5px;
            padding: 10px 15px;
            margin-right: 10px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: bold;
        }
        
        .custom-product-btn:hover {
            background-color: #e0a800;
        }
        
        .custom-product-form {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-row .form-group {
            flex: 1;
            margin-bottom: 0;
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 15px;
        }
        
        .cancel-btn {
            background-color: #6c757d;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 10px 20px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .cancel-btn:hover {
            background-color: #5a6268;
        }
        
        @media (max-width: 768px) {
            .order-container {
                flex-direction: column;
                height: auto;
            }
            
            .product-section, .cart-section {
                width: 100%;
                min-height: 500px;
            }
            
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                max-height: 500px;
            }
            
            .cart-items {
                max-height: 400px;
            }
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="top-nav">
            <div class="logo">
                <i class="fas fa-shopping-cart"></i>
                <span>نظام إدارة الطلبات</span>
            </div>
            <div class="navigation">
                <a href="../app/main.php"><i class="fas fa-home"></i> الرئيسية</a>
                <a href="products.php"><i class="fas fa-box"></i> المنتجات</a>
                <a href="customers.php"><i class="fas fa-users"></i> العملاء</a>
            </div>
        </div>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <div class="order-container">
            <div class="product-section">
                <div class="section-header">
                    <h3><i class="fas fa-box"></i> المنتجات</h3>
                </div>
                
                <div class="search-container">
                    <input type="text" id="productSearch" class="search-input" placeholder="بحث عن منتج...">
                    <i class="fas fa-search"></i>
                </div>
                
                <div class="custom-product-note">
                    <i class="fas fa-lightbulb"></i>
                    <p>لم تجد المنتج الذي تبحث عنه؟ يمكنك إضافة منتج مخصص غير مدرج في المخزون مثل تذكرة أو خدمة.</p>
                    <button class="custom-product-btn" id="showCustomProductForm">
                        <i class="fas fa-plus-circle"></i> إضافة منتج مخصص
                    </button>
                </div>
                
                <div class="custom-product-form" id="customProductForm" style="display: none;">
                    <form action="order.php" method="POST">
                        <input type="hidden" name="is_custom_product" value="1">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="custom_product_name">اسم المنتج المخصص</label>
                                <input type="text" name="custom_product_name" id="custom_product_name" required>
                            </div>
                            <div class="form-group">
                                <label for="custom_product_price">السعر</label>
                                <input type="number" name="custom_product_price" id="custom_product_price" min="0" step="0.01" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="custom_product_quantity">الكمية</label>
                            <input type="number" name="custom_product_quantity" id="custom_product_quantity" min="1" value="1" required>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="cancel-btn" id="cancelCustomProduct">إلغاء</button>
                            <button type="submit" name="add_custom_product" class="checkout-btn">إضافة للسلة</button>
                        </div>
                    </form>
                </div>
                
                <div class="products-grid" id="productsGrid">
                    <?php if (empty($all_products)): ?>
                        <div style="grid-column: 1 / -1; text-align: center; padding: 20px; color: #777;">
                            <i class="fas fa-exclamation-circle" style="font-size: 2em; margin-bottom: 10px;"></i>
                            <p>لا توجد منتجات في المخزون</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($all_products as $product): ?>
                            <div class="product-card" data-id="<?php echo $product['id']; ?>" data-name="<?php echo $product['name']; ?>">
                                <?php if (!empty($product['image']) && file_exists($product['image'])): ?>
                                    <img src="<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>">
                                <?php else: ?>
                                    <img src="../assets/images/product-placeholder.png" alt="<?php echo $product['name']; ?>">
                                <?php endif; ?>
                                <h4><?php echo $product['name']; ?></h4>
                                <div class="price"><?php echo number_format($product['price'], 2); ?> ج</div>
                                <div class="stock <?php echo $product['quantity'] < 5 ? 'low-stock' : ''; ?>">
                                    المخزون: <?php echo $product['quantity']; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="add-product-form" id="addProductForm" style="display: none;">
                    <form action="order.php" method="POST">
                        <div class="form-group">
                            <label for="product_id">المنتج</label>
                            <select name="product_id" id="product_id" required>
                                <option value="">اختر المنتج</option>
                                <?php foreach ($all_products as $product): ?>
                                    <option value="<?php echo $product['id']; ?>"><?php echo $product['name']; ?> - <?php echo number_format($product['price'], 2); ?> ج</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="quantity">الكمية</label>
                            <input type="number" name="quantity" id="quantity" min="1" value="1" required>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="cancel-btn" id="cancelAddProduct">إلغاء</button>
                            <button type="submit" name="add_product" class="checkout-btn">إضافة للسلة</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="cart-section">
                <div class="section-header">
                    <h3><i class="fas fa-shopping-basket"></i> سلة المشتريات</h3>
                </div>
                
                <div class="cart-items">
                    <?php if (empty($_SESSION['cart'])): ?>
                        <div class="empty-cart">
                            <i class="fas fa-shopping-basket"></i>
                            <p>السلة فارغة</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($_SESSION['cart'] as $index => $item): ?>
                            <div class="cart-item">
                                <div class="cart-item-details">
                                    <div class="cart-item-name"><?php echo $item['name']; ?></div>
                                    <div class="cart-item-price"><?php echo number_format($item['price'], 2); ?> ج</div>
                                </div>
                                <div class="cart-item-quantity">
                                    <span>الكمية: <?php echo $item['quantity']; ?></span>
                                </div>
                                <div class="cart-item-total"><?php echo number_format($item['total'], 2); ?> ج</div>
                                <form action="order.php" method="POST" style="margin: 0;">
                                    <input type="hidden" name="cart_index" value="<?php echo $index; ?>">
                                    <button type="submit" name="remove_product" class="cart-item-remove" title="حذف">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="cart-summary">
                    <div class="summary-row total">
                        <span>الإجمالي:</span>
                        <span><?php echo number_format($cart_total, 2); ?> ج</span>
                    </div>
                </div>
                
                <div class="cart-actions">
                    <form action="order.php" method="POST">
                        <button type="submit" name="clear_cart" class="clear-btn" <?php echo empty($_SESSION['cart']) ? 'disabled' : ''; ?>>
                            <i class="fas fa-trash"></i> تفريغ السلة
                        </button>
                    </form>
                    
                    <form action="order.php" method="POST">
                        <button type="submit" name="complete_order" class="checkout-btn" <?php echo empty($_SESSION['cart']) ? 'disabled' : ''; ?>>
                            <i class="fas fa-check"></i> إتمام الطلب
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // البحث عن المنتجات
            const productSearch = document.getElementById('productSearch');
            const productsGrid = document.getElementById('productsGrid');
            const productCards = document.querySelectorAll('.product-card');
            
            productSearch.addEventListener('input', function() {
                const searchTerm = this.value.trim().toLowerCase();
                
                productCards.forEach(card => {
                    const productName = card.getAttribute('data-name').toLowerCase();
                    
                    if (productName.includes(searchTerm)) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
            
            // إضافة منتج للسلة عند النقر عليه
            productCards.forEach(card => {
                card.addEventListener('click', function() {
                    const productId = this.getAttribute('data-id');
                    document.getElementById('product_id').value = productId;
                    document.getElementById('addProductForm').style.display = 'block';
                    document.getElementById('customProductForm').style.display = 'none';
                    document.getElementById('addProductForm').scrollIntoView({ behavior: 'smooth' });
                });
            });
            
            // إخفاء رسائل التنبيه بعد 5 ثوانٍ
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => {
                        alert.style.display = 'none';
                    }, 500);
                }, 5000);
            });
            
            // إظهار نموذج إضافة منتج مخصص
            document.getElementById('showCustomProductForm').addEventListener('click', function() {
                document.getElementById('customProductForm').style.display = 'block';
                document.getElementById('addProductForm').style.display = 'none';
                document.getElementById('customProductForm').scrollIntoView({ behavior: 'smooth' });
            });
            
            // إلغاء إضافة منتج مخصص
            document.getElementById('cancelCustomProduct').addEventListener('click', function() {
                document.getElementById('customProductForm').style.display = 'none';
                document.getElementById('custom_product_name').value = '';
                document.getElementById('custom_product_price').value = '';
                document.getElementById('custom_product_quantity').value = '1';
            });
            
            // إلغاء إضافة منتج
            document.getElementById('cancelAddProduct').addEventListener('click', function() {
                document.getElementById('addProductForm').style.display = 'none';
            });
        });
    </script>
</body>
</html>
