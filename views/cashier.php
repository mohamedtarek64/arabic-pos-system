<?php
session_start();

// التحقق من الجلسة
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include '../app/db.php'; // الاتصال بقاعدة البيانات

// التحقق إذا كانت الجلسة تحتوي على المنتجات المضافة
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = []; // إذا لم تكن الجلسة تحتوي على منتجات، قم بإنشائها كمصفوفة فارغة
}

// جلب جميع المنتجات من قاعدة البيانات
try {
    $products_sql = "SELECT * FROM products ORDER BY name ASC";
    $products_stmt = $conn->prepare($products_sql);
    $products_stmt->execute();
    $all_products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $inventory_products = $all_products;
} catch (PDOException $e) {
    error_log("خطأ في استعلام المنتجات: " . $e->getMessage());
    $all_products = [];
    $inventory_products = [];
}

// جلب جميع العملاء من قاعدة البيانات
try {
    $customers_sql = "SELECT * FROM customers ORDER BY name ASC";
    $customers_stmt = $conn->prepare($customers_sql);
    $customers_stmt->execute();
    $customers = $customers_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $customers = []; // في حالة وجود خطأ، سنستخدم مصفوفة فارغة
}

// جلب جميع الفئات من قاعدة البيانات
try {
    $categories_sql = "SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category ASC";
    $categories_stmt = $conn->prepare($categories_sql);
    $categories_stmt->execute();
    $categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categories = []; // في حالة وجود خطأ، سنستخدم مصفوفة فارغة
}

// جلب آخر العمليات/المبيعات
try {
    $recent_sales_sql = "SELECT sales.*, customers.name as customer_name 
                        FROM sales 
                        LEFT JOIN customers ON sales.customer_id = customers.id 
                        ORDER BY sales.created_at DESC 
                        LIMIT 10";
    $recent_sales_stmt = $conn->prepare($recent_sales_sql);
    $recent_sales_stmt->execute();
    $recent_sales = $recent_sales_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $recent_sales = []; // في حالة وجود خطأ، سنستخدم مصفوفة فارغة
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>الكاشير</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/cashier.css">
    <script src="../assets/js/cashier-payment.js" defer></script>
    <script src="../assets/js/cashier-updates.js" defer></script>
    <script src="../assets/js/fullscreen-force.js" defer></script>
    <link rel="stylesheet" href="../assets/js/thermal-print.css" media="print">
    <style>
        /* إعادة ضبط كامل للصفحة لتغطية 100% من المساحة */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html, body {
            width: 100vw;
            height: 100vh;
            margin: 0;
            padding: 0;
            overflow: hidden;
            font-size: 20px; /* تكبير حجم الخط الافتراضي */
            font-family: 'Tajawal', sans-serif;
        }
        
        /* زيادة حجم الخطوط لجميع العناصر */
        h1, h2, h3 {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        
        h4, h5, h6 {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        p, span, div, button, input, select, textarea {
            font-size: 18px;
        }
        
        /* إخفاء شريط التمرير */
        ::-webkit-scrollbar {
            width: 6px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 3px;
        }
        
        /* إزالة كل المساحات وضمان استخدام العرض الكامل */
        .container {
            width: 100vw;
            height: 100vh;
            max-width: 100vw;
            overflow: hidden;
            padding: 0;
            margin: 0;
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
        }
        
        .top-nav {
            height: 50px; /* زيادة ارتفاع شريط التنقل */
            width: 100vw;
            max-width: 100%;
            background-color: #2196f3;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 15px;
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.2);
            font-size: 20px; /* تكبير الخط في شريط التنقل */
        }
        
        .top-nav a {
            font-size: 18px;
            font-weight: 600;
            margin: 0 8px;
        }
        
        .main-content {
            height: calc(100vh - 50px);
            width: 100vw;
            max-width: 100%;
            overflow: hidden;
        }
        
        .cashier-container {
            display: flex;
            width: 100vw;
            max-width: 100%;
            height: 100%;
            overflow: hidden;
        }
        
        .cashier-products-section {
            flex: 7;
            height: 100%;
            overflow-y: auto;
            padding: 5px;
            border-left: 1px solid #eee;
            background-color: #f9f9f9;
        }
        
        .cashier-cart-section {
            flex: 3;
            height: 100%;
            overflow-y: auto;
            padding: 5px;
            background-color: #fff;
            display: flex;
            flex-direction: column;
        }
        
        /* تنسيق آخر العمليات بشكل أكبر */
        .recent-transactions {
            margin-bottom: 15px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 15px;
        }
        
        .recent-transactions h3 {
            font-size: 20px;
            color: #333;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
        }
        
        .recent-transactions h3 i {
            margin-left: 8px;
            color: #2196f3;
        }
        
        .recent-transactions-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .recent-transactions-table th, 
        .recent-transactions-table td {
            padding: 12px 10px;
            text-align: right;
            border-bottom: 1px solid #eee;
        }
        
        .recent-transactions-table th {
            font-weight: bold;
            color: #555;
            background-color: #f5f5f5;
        }
        
        .recent-transactions-table tr:last-child td {
            border-bottom: none;
        }
        
        .recent-transactions-table tr:hover {
            background-color: #f9f9f9;
        }
        
        /* تحسين تنسيق رسالة التحذير "العميل مطلوب للتقسيط" */
        .no-customer-warning {
            display: flex;
            align-items: center;
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            border-radius: 6px;
            padding: 12px;
            margin-top: 10px;
            margin-bottom: 12px;
            color: #856404;
            font-weight: 500;
        }
        
        .no-customer-warning i {
            margin-left: 10px;
            font-size: 20px;
            color: #f39c12;
        }
        
        .no-customer-warning a {
            color: #0066cc;
            text-decoration: underline;
            margin-right: 10px;
        }
        
        /* تنسيق خاص بشاشة 1920*1080 */
        @media (min-width: 1920px) {
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
            
            .product-img {
                height: 120px;
            }
            
            .product-info h4 {
                font-size: 16px;
            }
        }
        
        /* تنسيقات لدعم أحجام الشاشات المختلفة */
        @media (max-width: 768px) {
            .cashier-container {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        
        <div class="top-nav">
            <div class="logo">
                <i class="fas fa-cash-register"></i>
                <span>نظام الكاشير</span>
            </div>
            <div class="navigation">
                <a href="../app/main.php"><i class="fas fa-home"></i> الرئيسية</a>
                <a href="products.php"><i class="fas fa-box"></i> المنتجات</a>
                <a href="customers.php"><i class="fas fa-users"></i> العملاء</a>
            </div>
        </div>
        
        <div class="main-content">
            <div class="cashier-container">
                
                <div class="cashier-products-section">
                    
                    <div class="product-search-container">
                        <h3><i class="fas fa-search"></i> بحث عن منتج</h3>
                        <div class="search-input-wrapper">
                            <input type="text" id="productSearch" placeholder="ابحث بالاسم أو الكود أو الباركود...">
                            <i class="fas fa-search"></i>
                            <button class="barcode-btn" id="barcodeBtn" title="قراءة باركود">
                                <i class="fas fa-barcode"></i>
                            </button>
                        </div>
                        <div class="search-results-info" style="display: none;"></div>
                        
                        <div class="categories-container">
                            <button class="category-btn active" data-category="all">الكل</button>
                            <?php foreach ($categories as $category): ?>
                                <button class="category-btn" data-category="<?php echo htmlspecialchars($category['category']); ?>">
                                    <?php echo htmlspecialchars($category['category']); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    
                    <div class="recent-transactions">
                        <div class="section-header">
                            <h3><i class="fas fa-history"></i> آخر العمليات</h3>
                            <button class="view-all-btn" id="viewAllTransactionsBtn">
                                <i class="fas fa-external-link-alt"></i> عرض الكل
                            </button>
                        </div>
                        <div class="transactions-list">
                            <?php if (empty($recent_sales)): ?>
                                <div class="empty-transactions">
                                    <i class="fas fa-receipt"></i>
                                    <p>لا توجد عمليات سابقة</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($recent_sales as $sale): ?>
                                    <div class="transaction-item" data-id="<?php echo htmlspecialchars($sale['id']); ?>">
                                        <div class="transaction-info">
                                            <div class="transaction-date">
                                                <i class="fas fa-calendar-alt"></i>
                                                <?php echo date('d/m/Y', strtotime($sale['created_at'])); ?>
                                            </div>
                                            <div class="transaction-time">
                                                <i class="fas fa-clock"></i>
                                                <?php echo date('h:i A', strtotime($sale['created_at'])); ?>
                                            </div>
                                        </div>
                                        <div class="transaction-details">
                                            <div class="transaction-customer">
                                                <i class="fas fa-user"></i>
                                                <?php echo !empty($sale['customer_name']) ? htmlspecialchars($sale['customer_name']) : 'عميل نقدي'; ?>
                                            </div>
                                            <div class="transaction-total">
                                                <?php echo number_format($sale['total_amount'], 2); ?> جنيه
                                            </div>
                                        </div>
                                        <div class="transaction-actions">
                                            <button class="transaction-action" data-action="view" title="عرض التفاصيل">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="transaction-action" data-action="print" title="طباعة الفاتورة">
                                                <i class="fas fa-print"></i>
                                            </button>
                                            <button class="transaction-action" data-action="receipt" title="إرسال الإيصال">
                                                <i class="fas fa-paper-plane"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    
                    <div class="products-grid" id="productsGrid">
                        <?php if (empty($inventory_products)): ?>
                            <div style="grid-column: 1 / -1; text-align: center; padding: 20px; color: #7b8a9a;">
                                <i class="fas fa-exclamation-circle" style="font-size: 2em; margin-bottom: 10px;"></i>
                                <p>لا توجد منتجات في المخزون</p>
                                <p>يرجى إضافة منتجات من <a href="products.php" style="color: #2196f3;">صفحة المنتجات</a></p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($inventory_products as $product): ?>
                                <div class="product-card" 
                                     data-id="<?php echo htmlspecialchars($product['id']); ?>" 
                                     data-price="<?php echo htmlspecialchars($product['price']); ?>" 
                                     data-name="<?php echo htmlspecialchars($product['name']); ?>"
                                     data-category="<?php echo htmlspecialchars($product['category'] ?? ''); ?>"
                                     data-stock="<?php echo htmlspecialchars($product['quantity']); ?>"
                                     data-code="<?php echo htmlspecialchars($product['code']); ?>"
                                     data-barcode="<?php echo htmlspecialchars($product['barcode'] ?? ''); ?>">
                                    <div class="product-img">
                                        <?php if (!empty($product['image']) && file_exists($product['image'])): ?>
                                            <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                        <?php else: ?>
                                            <img src="images/product-placeholder.png" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                        <?php endif; ?>
                                        <?php if ($product['quantity'] < 5): ?>
                                            <div class="stock-badge low">المخزون منخفض</div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="product-info">
                                        <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                                        <div class="product-code"><?php echo htmlspecialchars($product['code']); ?></div>
                                        <p class="product-price"><?php echo number_format($product['price'], 2); ?> جنيه</p>
                                        <p class="product-stock <?php echo $product['quantity'] < 5 ? 'low-stock' : ''; ?>">
                                            المخزون: <?php echo htmlspecialchars($product['quantity']); ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                
                <div class="cashier-cart-section">
                    
                    <div class="customer-selection">
                        <div class="section-header">
                            <h3><i class="fas fa-user"></i> العميل</h3>
                            <button class="add-customer-btn" id="addCustomerBtn">
                                <i class="fas fa-plus"></i> عميل جديد
                            </button>
                        </div>
                        <div class="customer-search">
                            <div class="search-input-wrapper">
                                <input type="text" id="customerSearch" placeholder="بحث عن عميل...">
                                <i class="fas fa-search"></i>
                            </div>
                            <div class="customer-results" id="customerResults">
                                
                            </div>
                        </div>
                        <div class="selected-customer" id="selectedCustomer">
                            <div class="no-customer-selected">
                                <i class="fas fa-user-plus"></i>
                                <span>لم يتم اختيار عميل</span>
                            </div>
                            
                            <div class="selected-customer-info" id="selectedCustomerInfo" style="display: none;">
                                <div class="customer-details">
                                    <div class="customer-name" id="selectedCustomerName"></div>
                                    <div class="customer-contact" id="selectedCustomerPhone"></div>
                                </div>
                                <div class="customer-actions">
                                    <button class="customer-action-btn edit" id="editCustomerBtn">
                                        <i class="fas fa-edit"></i> تعديل
                                    </button>
                                    <button class="customer-action-btn" id="removeCustomerBtn">
                                        <i class="fas fa-times"></i> إزالة
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    
                    <div class="cart-container">
                        <div class="section-header">
                            <h3><i class="fas fa-shopping-cart"></i> سلة المشتريات</h3>
                            <button class="add-customer-btn" id="clearCartBtn">
                                <i class="fas fa-trash"></i> تفريغ
                            </button>
                        </div>
                        <div class="cart-items" id="cartItems">
                            <div class="empty-cart">
                                <i class="fas fa-shopping-basket"></i>
                                <p>السلة فارغة</p>
                            </div>
                            
                        </div>
                        
                        
                        <div class="cart-summary">
                            <div class="summary-row">
                                <span>المجموع</span>
                                <span id="subtotal">0.00 جنيه</span>
                            </div>
                            <div class="summary-row">
                                <span>الخصم</span>
                                <div class="discount-input">
                                    <input type="number" id="discountValue" min="0" value="0">
                                    <select id="discountType">
                                        <option value="percentage">%</option>
                                        <option value="fixed">جنيه</option>
                                    </select>
                                </div>
                            </div>
                            <div class="summary-row total">
                                <span>الإجمالي</span>
                                <span id="total">0.00 جنيه</span>
                            </div>
                        </div>
                        
                        
                        <div class="payment-options">
                            <div class="payment-methods">
                                <button class="payment-method active" data-method="cash">
                                    <i class="fas fa-money-bill-wave"></i>
                                    <span>كاش</span>
                                </button>
                                <button class="payment-method" data-method="installment" id="installmentMethodBtn">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span>تقسيط</span>
                                </button>
                                <button class="payment-method" data-method="visa">
                                    <i class="fas fa-credit-card"></i>
                                    <span>فيزا</span>
                                </button>
                            </div>
                            
                            
                            <div class="installment-warning" id="installmentWarning" style="display: none;">
                                <i class="fas fa-exclamation-triangle"></i>
                                <span>يجب تحديد عميل قبل تنفيذ عملية تقسيط.</span>
                            </div>
                            
                            <div class="payment-actions">
                                <button class="hold-order-btn" id="holdOrderBtn">
                                    <i class="fas fa-pause-circle"></i>
                                    <span>تعليق الطلب</span>
                                </button>
                                <button class="checkout-btn" id="checkoutBtn" onclick="prepareCheckout()">
                                    <i class="fas fa-credit-card"></i> الدفع
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    
    <div class="modal" id="addCustomerModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>إضافة عميل جديد</h3>
                <button class="close-modal" data-modal="addCustomerModal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="customerName">اسم العميل</label>
                    <input type="text" id="customerName" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="customerPhone">رقم الهاتف</label>
                    <input type="text" id="customerPhone" class="form-control">
                </div>
                <div class="form-group">
                    <label for="customerEmail">البريد الإلكتروني</label>
                    <input type="email" id="customerEmail" class="form-control">
                </div>
                <div class="form-group">
                    <label for="customerAddress">العنوان</label>
                    <textarea id="customerAddress" class="form-control"></textarea>
                </div>
                <div class="form-actions">
                    <button class="cancel-btn" data-modal="addCustomerModal">إلغاء</button>
                    <button class="save-btn" id="saveCustomerBtn">حفظ</button>
                </div>
            </div>
        </div>
    </div>
    
    
    <div class="modal" id="barcodeModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>قراءة باركود</h3>
                <button class="close-modal" data-modal="barcodeModal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="barcode-container">
                    <div class="barcode-scanner-container">
                        <div id="barcode-scanner"></div>
                    </div>
                    <div class="manual-barcode">
                        <p>أو أدخل الباركود يدوياً:</p>
                        <div class="form-group">
                            <input type="text" id="manualBarcode" class="form-control" placeholder="أدخل الباركود...">
                        </div>
                    </div>
                </div>
                <div class="form-actions">
                    <button class="cancel-btn" data-modal="barcodeModal">إلغاء</button>
                    <button class="save-btn" id="addBarcodeItemBtn">إضافة للسلة</button>
                </div>
            </div>
        </div>
    </div>
    
    
    <div class="modal" id="checkoutModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>إتمام عملية البيع</h3>
                <button class="close-modal" data-modal="checkoutModal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="checkout-summary">
                    <h4>ملخص الطلب</h4>
                    <table class="checkout-table">
                        <tr>
                            <td>المجموع</td>
                            <td id="checkoutSubtotal">0.00 جنيه</td>
                        </tr>
                        <tr>
                            <td>الخصم</td>
                            <td id="checkoutDiscount">0.00 جنيه</td>
                        </tr>
                        <tr class="total-row">
                            <td>الإجمالي</td>
                            <td id="checkoutTotal">0.00 جنيه</td>
                        </tr>
                    </table>
                </div>
                
                <div class="payment-details" id="cashPaymentDetails">
                    <h4>الدفع كاش</h4>
                    <div class="form-group">
                        <label for="amountPaid">المبلغ المدفوع</label>
                        <input type="number" id="amountPaid" class="form-control" step="0.01">
                    </div>
                    <div class="form-group">
                        <label for="changeAmount">المبلغ المتبقي</label>
                        <input type="text" id="changeAmount" class="form-control" readonly>
                    </div>
                </div>
                
                <div class="payment-details" id="visaPaymentDetails" style="display: none;">
                    <h4>الدفع بالفيزا</h4>
                    <div class="form-group">
                        <label for="cardType">نوع البطاقة</label>
                        <select id="cardType" class="form-control">
                            <option value="visa">فيزا</option>
                            <option value="mastercard">ماستر كارد</option>
                            <option value="mada">مدى</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="cardNumber">رقم البطاقة (آخر 4 أرقام)</label>
                        <input type="text" id="cardNumber" class="form-control" maxlength="4">
                    </div>
                    <div class="form-group">
                        <label for="transactionRef">الرقم المرجعي</label>
                        <input type="text" id="transactionRef" class="form-control">
                    </div>
                </div>
                
                <div class="payment-details" id="installmentPaymentDetails" style="display: none;">
                    <h4>التقسيط</h4>
                    <div class="form-group">
                        <label for="installmentCustomer">العميل</label>
                        <select id="installmentCustomer" class="form-control">
                            <option value="">اختر العميل</option>
                            <?php foreach ($customers as $customer): ?>
                                <option value="<?php echo $customer['id']; ?>"><?php echo $customer['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="no-customer-warning" id="noCustomerWarning" style="display: none;">
                            <p>يجب اختيار عميل للتقسيط. <a href="#" id="quickAddCustomerLink">إضافة عميل جديد</a></p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="installmentCount">عدد الأقساط</label>
                        <input type="number" id="installmentCount" class="form-control" min="1" value="1">
                    </div>
                    <div class="form-group">
                        <label for="downPayment">الدفعة المقدمة</label>
                        <input type="number" id="downPayment" class="form-control" step="0.01" value="0">
                    </div>
                    <div class="form-group">
                        <label for="firstPaymentDate">تاريخ أول قسط</label>
                        <input type="date" id="firstPaymentDate" class="form-control">
                    </div>
                </div>
                
                <div class="form-actions">
                    <button class="cancel-btn" data-modal="checkoutModal">إلغاء</button>
                    <button class="payment-btn" id="completePaymentBtn" onclick="processPayment()">
                        <i class="fas fa-check-circle"></i> إتمام الدفع
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    
    <div class="modal" id="receiptModal" class="modal">
        <div class="modal-content receipt-modal">
            <span class="close-modal green-close-btn">&times;</span>
            <div class="receipt-container" id="receipt">
                        <div class="receipt-header">
                            <div class="store-info">
                                <h2>متجر الإلكترونيات</h2>
                                <p>القاهرة، مصر</p>
                                <p>هاتف: 0123456789</p>
                            </div>
                        </div>
                        
                        <div class="receipt-details">
                            <div class="receipt-row">
                                <span>رقم الفاتورة:</span>
                        <span id="receiptNumber">INV-000693</span>
                            </div>
                            <div class="receipt-row">
                                <span>التاريخ:</span>
                        <span id="receiptDate">٢٠٢٣/٠٥/١٤</span>
                            </div>
                            <div class="receipt-row">
                                <span>الوقت:</span>
                        <span id="receiptTime">٠٦:٠٠ م</span>
                            </div>
                            <div class="receipt-row">
                                <span>العميل:</span>
                        <span id="receiptCustomer">عميل نقدي</span>
                            </div>
                        </div>
                        
                <div class="receipt-items">
                    <table class="items-table">
            <thead>
                <tr>
                    <th>المنتج</th>
                    <th>الكمية</th>
                    <th>السعر</th>
                    <th>الإجمالي</th>
                </tr>
            </thead>
                        <tbody id="receiptItemsList">
                            <!-- سيتم ملء هذا الجزء ديناميكياً -->
                        </tbody>
        </table>
                </div>

                        <div class="receipt-summary">
                    <div class="receipt-row">
                                <span>المجموع:</span>
                        <span id="receiptSubtotal">0.00 جنيه</span>
                            </div>
                    <div class="receipt-row">
                                <span>الخصم:</span>
                        <span id="receiptDiscount">0.00 جنيه</span>
                            </div>
                    <div class="receipt-row total-row">
                                <span>الإجمالي:</span>
                        <span id="receiptTotal">0.00 جنيه</span>
                            </div>
                    <div class="receipt-row">
                                <span>طريقة الدفع:</span>
                        <span id="receiptPaymentMethod">نقداً</span>
                            </div>
                        </div>
                        
                        <div class="receipt-footer">
                            <p>شكراً لثقتكم بنا</p>
                    </div>
                </div>
                
                <div class="receipt-actions">
                <button class="receipt-action-btn print-btn" onclick="window.print()">
                        <i class="fas fa-print"></i> طباعة
                    </button>
                <button class="receipt-action-btn email-btn" onclick="sendReceiptByEmail()">
                        <i class="fas fa-envelope"></i> إرسال بالبريد
                    </button>
                <button class="receipt-action-btn whatsapp-btn" onclick="sendReceiptByWhatsApp()">
                    <i class="fab fa-whatsapp"></i> واتساب
                </button>
                <button class="receipt-action-btn download-pdf-btn" onclick="downloadAsPDF()">
                    <i class="fas fa-file-pdf"></i> تحميل PDF
                    </button>
                    
                <button class="payment-complete-btn" onclick="closeReceiptAndComplete()">
                        <i class="fas fa-check-circle"></i> تم الدفع
                    </button>
                </div>
            </div>
        </div>

    <!-- إضافة مودال بحث المعاملات -->
    <div class="modal" id="searchTransactionsModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>بحث في المعاملات</h3>
                <button class="close-modal" data-modal="searchTransactionsModal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="search-transactions-container">
                    <div class="search-transactions-form">
                        <input type="text" id="transactionSearchQuery" placeholder="بحث برقم الفاتورة أو اسم العميل...">
                        <select id="transactionSearchType">
                            <option value="all">جميع المعاملات</option>
                            <option value="cash">كاش</option>
                            <option value="installment">تقسيط</option>
                            <option value="visa">فيزا</option>
                        </select>
                        <input type="date" id="transactionSearchDate">
                        <button id="transactionSearchBtn">بحث</button>
    </div>

                    <div class="search-transactions-results">
                        <table class="search-transactions-table">
                            <thead>
                                <tr>
                                    <th>رقم الفاتورة</th>
                                    <th>التاريخ</th>
                                    <th>العميل</th>
                                    <th>المبلغ</th>
                                    <th>طريقة الدفع</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody id="transactionSearchResults">
                                <!-- ستتم تعبئة النتائج هنا عبر جافا سكريبت -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // إعداد بيانات المنتجات من PHP إلى JavaScript
        const dbProducts = <?php echo json_encode($all_products); ?>;
        const dbCustomers = <?php echo json_encode($customers); ?>;

        // تهيئة عربة التسوق العالمية
        window.cart = [];

        // تحسين وظيفة البحث لتشمل البحث بالباركود والأسماء
        function searchProducts() {
            const searchTerm = document.getElementById('productSearch').value.trim();
            const searchTermNormalized = searchTerm.toLowerCase().replace(/\s+/g, ' ');
            const allProducts = document.querySelectorAll('.product-card');
            const searchResultsInfo = document.querySelector('.search-results-info') || createSearchResultsInfo();
            const productsGrid = document.getElementById('productsGrid');
            
            if (searchTerm === '') {
                // إذا كان حقل البحث فارغاً، عرض جميع المنتجات
                allProducts.forEach(product => {
                    product.style.display = 'flex';
                });
                searchResultsInfo.style.display = 'none';
                
                // إعادة تنسيق الشبكة إلى الوضع الطبيعي
                productsGrid.classList.remove('search-results-grid');
                return;
            }
            
            // تحويل الشبكة إلى وضع نتائج البحث (أكبر)
            productsGrid.classList.add('search-results-grid');
            
            // عداد للمنتجات المطابقة
            let matchCount = 0;
            let matchedProducts = [];
            
            allProducts.forEach(product => {
                const productName = product.getAttribute('data-name') || '';
                const productNameNormalized = productName.toLowerCase().replace(/\s+/g, ' ');
                const productCode = product.getAttribute('data-code') || '';
                const productBarcode = product.getAttribute('data-barcode') || '';
                
                // تحسين البحث ليعمل حتى مع كتابة جزء من الكلمة
                if (productNameNormalized.includes(searchTermNormalized) || 
                    productCode.includes(searchTerm) || 
                    productBarcode.includes(searchTerm)) {
                    product.style.display = 'flex';
                    product.classList.add('search-match');
                    matchCount++;
                    matchedProducts.push(product);
                    
                    // إظهار المنتج بوضوح كنتيجة بحث
                    const productNameElement = product.querySelector('.product-info h4');
                    if (productNameElement) {
                        // حفظ النص الأصلي إذا لم يكن محفوظاً
                        if (!product.hasAttribute('data-original-name')) {
                            product.setAttribute('data-original-name', productNameElement.textContent);
                        }
                    }
                } else {
                    product.style.display = 'none';
                    product.classList.remove('search-match');
                }
            });
            
            // إذا كان هناك منتج واحد فقط، قم بتكبيره
            if (matchCount === 1 && matchedProducts.length === 1) {
                matchedProducts[0].classList.add('single-result');
            } else {
                // إزالة التكبير من جميع المنتجات
                document.querySelectorAll('.single-result').forEach(item => {
                    item.classList.remove('single-result');
                });
            }
            
            // تحديث رسالة نتائج البحث
            searchResultsInfo.style.display = 'block';
            if (matchCount > 0) {
                searchResultsInfo.innerHTML = `تم العثور على <strong>${matchCount}</strong> منتج مطابق لـ "<span class="search-keywords">${searchTerm}</span>"`;
            } else {
                searchResultsInfo.innerHTML = `لم يتم العثور على منتجات مطابقة لـ "<span class="search-keywords">${searchTerm}</span>"`;
            }
        }
        
        // إنشاء عنصر معلومات نتائج البحث
        function createSearchResultsInfo() {
            const container = document.querySelector('.product-search-container');
            const searchResultsInfo = document.createElement('div');
            searchResultsInfo.className = 'search-results-info';
            searchResultsInfo.style.display = 'none';
            container.appendChild(searchResultsInfo);
            return searchResultsInfo;
        }
        
        // تفعيل البحث عند كتابة في حقل البحث
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('productSearch');
            if (searchInput) {
                // إضافة خاصية للبحث أثناء الكتابة
                searchInput.addEventListener('input', function() {
                    searchProducts();
                });
                
                // تمكين التركيز التلقائي عند تحميل الصفحة
                setTimeout(() => searchInput.focus(), 500);
                
                // إضافة مستمع لمسح البحث بضغطة Escape
                searchInput.addEventListener('keydown', function(event) {
                    if (event.key === 'Escape') {
                        this.value = '';
                        searchProducts();
                    }
                });
                
                // تفعيل البحث بالضغط على زر البحث
                const searchIcon = searchInput.parentElement.querySelector('i');
                if (searchIcon) {
                    searchIcon.addEventListener('click', searchProducts);
                }
                
                // تفعيل البحث بالضغط على زر Enter
                searchInput.addEventListener('keyup', function(event) {
                    if (event.key === 'Enter') {
                        searchProducts();
                    }
                });
                
                // إنشاء عنصر معلومات نتائج البحث
                createSearchResultsInfo();
            }
            
            // إضافة الباركود لبطاقات المنتجات إذا كان متاحاً
            const productCards = document.querySelectorAll('.product-card');
            productCards.forEach(card => {
                // الحصول على معرف المنتج
                const productId = card.getAttribute('data-id');
                
                // البحث عن المنتج في مصفوفة المنتجات
                const product = dbProducts.find(p => p.id == productId);
                
                // إذا كان المنتج له باركود، أضفه كسمة للبطاقة
                if (product && product.barcode) {
                    card.setAttribute('data-barcode', product.barcode);
                }
            });
        });
        
        // ضبط حجم الشاشة ليأخذ كامل المساحة المتاحة بشكل دقيق
        function forceFullWidth() {
            // ضبط العرض للامتداد إلى حدود الشاشة
            document.documentElement.style.width = '100vw';
            document.documentElement.style.maxWidth = '100vw';
            document.body.style.width = '100vw';
            document.body.style.maxWidth = '100vw';
            document.body.style.overflowX = 'hidden';
            
            // الحاوية الرئيسية
            const container = document.querySelector('.container');
            if (container) {
                container.style.width = '100vw';
                container.style.maxWidth = '100vw';
                container.style.margin = '0';
                container.style.padding = '0';
            }
            
            // شريط التنقل العلوي
            const topNav = document.querySelector('.top-nav');
            if (topNav) {
                topNav.style.width = '100vw';
                topNav.style.maxWidth = '100vw';
            }
            
            // المحتوى الرئيسي
            const mainContent = document.querySelector('.main-content');
            if (mainContent) {
                mainContent.style.width = '100vw';
                mainContent.style.maxWidth = '100vw';
                mainContent.style.margin = '0';
                mainContent.style.padding = '0';
            }
            
            // حاوية الكاشير
            const cashierContainer = document.querySelector('.cashier-container');
            if (cashierContainer) {
                cashierContainer.style.width = '100vw';
                cashierContainer.style.maxWidth = '100vw';
                cashierContainer.style.margin = '0';
                cashierContainer.style.padding = '0';
            }
            
            // منطقة المنتجات
            const productsSection = document.querySelector('.cashier-products-section');
            if (productsSection) {
                // تعيين النسبة المثالية للقسم الأيسر
                productsSection.style.flex = '7';
                productsSection.style.minWidth = '0';
            }
            
            // منطقة السلة
            const cartSection = document.querySelector('.cashier-cart-section');
            if (cartSection) {
                // تعيين النسبة المثالية للقسم الأيمن
                cartSection.style.flex = '3';
                cartSection.style.minWidth = '0';
            }
        }
        
        // إجبار العناصر على أخذ العرض الكامل عند كل تحميل وتغيير للحجم
        window.addEventListener('load', function() {
            forceFullWidth();
            
            // تطبيق العرض الكامل بعد 100 مللي ثانية
            setTimeout(forceFullWidth, 100);
            
            // تطبيق العرض الكامل بعد 500 مللي ثانية
            setTimeout(forceFullWidth, 500);
            
            // تطبيق العرض الكامل بعد 1 ثانية
            setTimeout(forceFullWidth, 1000);
        });
        
        window.addEventListener('resize', forceFullWidth);
        
        // تشغيل التابع للضبط المستمر في البداية
        let fullWidthInterval = setInterval(forceFullWidth, 100);
        
        // إيقاف الضبط المستمر بعد 5 ثوان
        setTimeout(function() {
            clearInterval(fullWidthInterval);
        }, 5000);
        
        // ضبط حجم الشاشة ليأخذ كامل المساحة المتاحة - دون أي فراغات
        function adjustFullScreenPrecise() {
            // ضبط الحاوية الرئيسية لتأخذ كامل الشاشة
            document.querySelector('.container').style.width = window.innerWidth + 'px';
            document.querySelector('.container').style.height = window.innerHeight + 'px';
            
            // ضبط المحتوى الرئيسي
            const topNavHeight = document.querySelector('.top-nav').offsetHeight;
            const mainContent = document.querySelector('.main-content');
            mainContent.style.height = (window.innerHeight - topNavHeight) + 'px';
            
            // ضبط أقسام الكاشير
            const cashierContainer = document.querySelector('.cashier-container');
            cashierContainer.style.height = mainContent.offsetHeight + 'px';
            
            // ضبط حاويات المنتجات والسلة
            const productsSection = document.querySelector('.cashier-products-section');
            const cartSection = document.querySelector('.cashier-cart-section');
            
            const customerHeight = cartSection ? cartSection.querySelector('.customer-selection').offsetHeight : 0;
            const paymentHeight = cartSection ? cartSection.querySelector('.payment-options').offsetHeight : 0;
            
            if (cartSection) {
                cartSection.style.height = (cashierContainer.offsetHeight - customerHeight - paymentHeight - 20) + 'px';
                
                // ضبط ارتفاع منطقة السلة الداخلية
                const cartHeader = cartSection.querySelector('.section-header');
                const cartSummary = cartSection.querySelector('.cart-summary');
                
                const headerHeight = cartHeader ? cartHeader.offsetHeight : 0;
                const summaryHeight = cartSummary ? cartSummary.offsetHeight : 0;
                
                const cartItems = cartSection.querySelector('.cart-items');
                if (cartItems) {
                    cartItems.style.height = (cartSection.offsetHeight - headerHeight - summaryHeight - 25) + 'px';
                }
            }
            
            // ضبط منطقة المنتجات
            const recentTransactions = productsSection ? productsSection.querySelector('.recent-transactions') : null;
            const searchContainer = productsSection ? productsSection.querySelector('.product-search-container') : null;
            const productsGrid = productsSection ? productsSection.querySelector('.products-grid') : null;
            
            const transactionsHeight = recentTransactions ? recentTransactions.offsetHeight : 0;
            const searchHeight = searchContainer ? searchContainer.offsetHeight : 0;
            
            if (productsGrid) {
                productsGrid.style.height = (productsSection.offsetHeight - transactionsHeight - searchHeight - 20) + 'px';
                productsGrid.style.overflowY = 'auto';
            }
        }
        
        // التأكد من تحميل جميع الأزرار وتفعيلها
        document.addEventListener('DOMContentLoaded', function() {
            // تفعيل أزرار المودال
            const modalCloseButtons = document.querySelectorAll('.close-modal');
            modalCloseButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const modalId = this.getAttribute('data-modal');
                    document.getElementById(modalId).style.display = 'none';
                });
            });
            
            // جعل المودالات تظهر في وسط الشاشة
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                modal.style.display = 'flex';
                modal.style.alignItems = 'center';
                modal.style.justifyContent = 'center';
                modal.style.display = 'none';
            });
            
            // تفعيل زر قراءة الباركود
            const barcodeBtn = document.getElementById('barcodeBtn');
            if (barcodeBtn) {
                barcodeBtn.addEventListener('click', function() {
                    document.getElementById('barcodeModal').style.display = 'flex';
                });
            }
            
            // تفعيل زر إضافة عميل جديد
            const addCustomerBtn = document.getElementById('addCustomerBtn');
            if (addCustomerBtn) {
                addCustomerBtn.addEventListener('click', function() {
                    document.getElementById('addCustomerModal').style.display = 'flex';
                });
            }
            
            // تفعيل زر تفريغ السلة
            const clearCartBtn = document.getElementById('clearCartBtn');
            if (clearCartBtn) {
                clearCartBtn.addEventListener('click', function() {
                    if (confirm('هل أنت متأكد من تفريغ السلة؟')) {
                        // تفريغ السلة هنا
                        document.getElementById('cartItems').innerHTML = `
                            <div class="empty-cart">
                                <i class="fas fa-shopping-basket"></i>
                                <p>السلة فارغة</p>
                            </div>
                        `;
                        // تحديث المجموع
                        document.getElementById('subtotal').textContent = '0.00 جنيه';
                        document.getElementById('total').textContent = '0.00 جنيه';
                    }
                });
            }
            
            // تفعيل زر إتمام البيع
            const checkoutBtn = document.getElementById('checkoutBtn');
            if (checkoutBtn) {
                checkoutBtn.addEventListener('click', function() {
                    if (window.cart.length === 0) {
                        alert('السلة فارغة، يرجى إضافة منتجات للسلة أولاً');
                        return;
                    }
                    prepareCheckoutModal();
                    openModal('checkoutModal');
                });
            }
            
            // تفعيل زر إتمام الدفع
            const completePaymentBtn = document.getElementById('completePaymentBtn');
            if (completePaymentBtn) {
                completePaymentBtn.addEventListener('click', function() {
                    // تنفيذ عملية الدفع هنا
                    document.getElementById('checkoutModal').style.display = 'none';
                    document.getElementById('receiptModal').style.display = 'flex';
                });
            }
            
            // تفعيل زر طباعة الإيصال
            const printReceiptBtn = document.getElementById('printReceiptBtn');
            if (printReceiptBtn) {
                printReceiptBtn.addEventListener('click', function() {
                    window.print();
                });
            }
            
            // تفعيل زر عرض كل المعاملات
            const viewAllTransactionsBtn = document.getElementById('viewAllTransactionsBtn');
            if (viewAllTransactionsBtn) {
                viewAllTransactionsBtn.addEventListener('click', function() {
                    document.getElementById('searchTransactionsModal').style.display = 'flex';
                });
            }
            
            // تفعيل البحث في المعاملات
            const transactionSearchBtn = document.getElementById('transactionSearchBtn');
            if (transactionSearchBtn) {
                transactionSearchBtn.addEventListener('click', function() {
                    searchTransactions();
                });
            }
            
            // إضافة وظيفة إضافة المنتجات للسلة
            const productCards = document.querySelectorAll('.product-card');
            productCards.forEach(card => {
                card.addEventListener('click', function() {
                    addToCart(this);
                });
            });
        });
        
        // إضافة منتج للسلة
        function addToCart(productCard) {
            // جمع بيانات المنتج
            const productId = productCard.getAttribute('data-id');
            const productName = productCard.getAttribute('data-name');
            const productPrice = parseFloat(productCard.getAttribute('data-price'));
            
            // التحقق من المخزون قبل الإضافة
            const productStock = parseInt(productCard.getAttribute('data-stock')) || 0;
            
            // البحث عن المنتج في السلة
            const existingProductIndex = window.cart.findIndex(item => item.id === productId);
            
            // إذا كان المنتج موجود بالفعل، نتحقق أن الكمية الجديدة لا تتجاوز المخزون
            if (existingProductIndex !== -1) {
                const currentQuantity = window.cart[existingProductIndex].quantity;
                
                // التحقق من المخزون المتاح
                if (productStock > 0 && currentQuantity < productStock) {
                    // زيادة الكمية
                    window.cart[existingProductIndex].quantity++;
                    
                    // تحديث واجهة السلة
                    updateCartUI();
                } else {
                    // إظهار رسالة تحذير إذا كان المخزون غير كافٍ
                    showNotification('المخزون غير كافٍ', 'error');
                }
            } else {
                // المنتج غير موجود، نضيفه إذا كان المخزون كافياً
                if (productStock > 0) {
                    // إضافة المنتج إلى السلة
                    window.cart.push({
                        id: productId,
                        name: productName,
                        price: productPrice,
                        quantity: 1
                    });
                    
                    // تحديث واجهة السلة
                    updateCartUI();
                } else {
                    // إظهار رسالة تحذير إذا كان المخزون غير كافٍ
                    showNotification('المنتج غير متوفر في المخزون', 'error');
                }
            }
        }
        
        // تحديث واجهة السلة
        function updateCartUI() {
            // البحث عن حاوية عناصر السلة
            const cartItems = document.getElementById('cartItems');
            if (!cartItems) return;
            
            // تفريغ محتوى السلة
            cartItems.innerHTML = '';
            
            // إذا كانت السلة فارغة، نعرض رسالة
            if (window.cart.length === 0) {
                cartItems.innerHTML = `
                    <div class="empty-cart">
                        <i class="fas fa-shopping-basket"></i>
                        <p>السلة فارغة</p>
                    </div>
                `;
                
                // تحديث المجموع
                updateCartTotals();
                return;
            }
            
            // إضافة المنتجات إلى السلة
            window.cart.forEach((item, index) => {
                const itemTotal = item.price * item.quantity;
                
                // إنشاء عنصر جديد للمنتج
                const cartItem = document.createElement('div');
                cartItem.className = 'cart-item';
                cartItem.setAttribute('data-product-id', item.id);
                
                cartItem.innerHTML = `
                    <div class="item-info">
                        <div class="item-name">${item.name}</div>
                        <div class="item-price">${item.price.toFixed(2)} جنيه</div>
                    </div>
                    <div class="item-controls">
                        <div class="quantity-control">
                            <button class="quantity-btn minus" onclick="decreaseQuantity(${index})">-</button>
                            <span class="item-quantity">${item.quantity}</span>
                            <button class="quantity-btn plus" onclick="increaseQuantity(${index})">+</button>
                        </div>
                        <div class="item-total">${itemTotal.toFixed(2)} جنيه</div>
                        <button class="remove-item-btn" onclick="removeItem(${index})"><i class="fas fa-trash"></i></button>
                    </div>
                `;
                
                // إضافة المنتج إلى السلة
                cartItems.appendChild(cartItem);
            });
            
            // تحديث مجموع السلة
            updateCartTotals();
            
            // التمرير لأسفل لإظهار آخر منتج تمت إضافته
            cartItems.scrollTop = cartItems.scrollHeight;
        }
        
        // زيادة كمية منتج في السلة
        function increaseQuantity(index) {
            // التحقق من وجود المنتج في السلة
            if (index < 0 || index >= window.cart.length) return;
            
            const item = window.cart[index];
            
            // التحقق من المخزون المتاح
            const productCard = document.querySelector(`.product-card[data-id="${item.id}"]`);
            const productStock = productCard ? parseInt(productCard.getAttribute('data-stock')) || 0 : 0;
            
            if (item.quantity < productStock) {
                // زيادة الكمية
                item.quantity++;
                
                // تحديث واجهة السلة
                updateCartUI();
            } else {
                // إظهار رسالة تحذير إذا كان المخزون غير كافٍ
                showNotification('المخزون غير كافٍ', 'error');
            }
        }
        
        // تقليل كمية منتج في السلة
        function decreaseQuantity(index) {
            // التحقق من وجود المنتج في السلة
            if (index < 0 || index >= window.cart.length) return;
            
            // تقليل الكمية
            if (window.cart[index].quantity > 1) {
                window.cart[index].quantity--;
                
                // تحديث واجهة السلة
                updateCartUI();
            } else {
                // إزالة المنتج إذا كانت الكمية ستصبح صفراً
                removeItem(index);
            }
        }
        
        // إزالة منتج من السلة
        function removeItem(index) {
            // التحقق من وجود المنتج في السلة
            if (index < 0 || index >= window.cart.length) return;
            
            // إزالة المنتج من السلة
            window.cart.splice(index, 1);
            
            // تحديث واجهة السلة
            updateCartUI();
        }
        
        // تحديث إجمالي السلة
        function updateCartTotals() {
            const subtotalElement = document.getElementById('subtotal');
            const totalElement = document.getElementById('total');
            const discountInput = document.getElementById('discountValue');
            const discountTypeSelect = document.getElementById('discountType');
            
            if (!subtotalElement || !totalElement) return;
            
            // حساب المجموع الفرعي
            const subtotal = window.cart.reduce((total, item) => {
                return total + (item.price * item.quantity);
            }, 0);
            
            // عرض المجموع الفرعي
            subtotalElement.textContent = subtotal.toFixed(2) + ' جنيه';
            
            // حساب الخصم
            let discount = 0;
            if (discountInput && discountTypeSelect) {
                const discountValue = parseFloat(discountInput.value) || 0;
                const discountType = discountTypeSelect.value;
                
                if (discountType === 'percentage') {
                    discount = (discountValue / 100) * subtotal;
                } else { // fixed
                    discount = discountValue;
                }
            }
            
            // تأكد من أن الخصم لا يتجاوز المجموع الفرعي
            discount = Math.min(discount, subtotal);
            
            // حساب المجموع النهائي
            const total = subtotal - discount;
            
            // عرض المجموع النهائي
            totalElement.textContent = total.toFixed(2) + ' جنيه';
            
            // تحديث زر الدفع بناءً على وجود منتجات في السلة
            const checkoutBtn = document.getElementById('checkoutBtn');
            if (checkoutBtn) {
                checkoutBtn.disabled = window.cart.length === 0 || total <= 0;
                
                // تغيير مظهر الزر
                if (window.cart.length === 0 || total <= 0) {
                    checkoutBtn.classList.add('disabled');
                    checkoutBtn.setAttribute('title', 'لا يمكن الدفع - السلة فارغة');
                } else {
                    checkoutBtn.classList.remove('disabled');
                    checkoutBtn.setAttribute('title', 'إتمام عملية الدفع');
                }
            }
            
            return { subtotal, discount, total };
        }
        
        // وظيفة البحث في المعاملات
        function searchTransactions() {
            const query = document.getElementById('transactionSearchQuery').value;
            const type = document.getElementById('transactionSearchType').value;
            const date = document.getElementById('transactionSearchDate').value;
            
            // هنا سيتم إرسال طلب AJAX للبحث في المعاملات
            // ولكن لأغراض العرض، سنمثل بعض البيانات
            
            const resultsContainer = document.getElementById('transactionSearchResults');
            resultsContainer.innerHTML = ''; // تفريغ النتائج السابقة
            
            // أمثلة على معاملات للعرض
            const sampleTransactions = [
                {id: '10001', date: '2023-05-15', customer: 'أحمد محمد', amount: 1500.50, method: 'كاش'},
                {id: '10002', date: '2023-05-14', customer: 'محمد علي', amount: 2300.75, method: 'تقسيط'},
                {id: '10003', date: '2023-05-13', customer: 'سارة أحمد', amount: 850.25, method: 'فيزا'},
                {id: '10004', date: '2023-05-12', customer: 'عمر خالد', amount: 1200.00, method: 'كاش'},
                {id: '10005', date: '2023-05-11', customer: 'فاطمة محمد', amount: 3500.00, method: 'تقسيط'}
            ];
            
            // تصفية المعاملات حسب المعايير
            let filteredTransactions = sampleTransactions;
            
            if (query) {
                filteredTransactions = filteredTransactions.filter(t => 
                    t.id.includes(query) || 
                    t.customer.includes(query)
                );
            }
            
            if (type && type !== 'all') {
                filteredTransactions = filteredTransactions.filter(t => t.method === type);
            }
            
            if (date) {
                filteredTransactions = filteredTransactions.filter(t => t.date === date);
            }
            
            // عرض النتائج
            if (filteredTransactions.length === 0) {
                resultsContainer.innerHTML = '<tr><td colspan="6" style="text-align: center;">لا توجد نتائج للبحث</td></tr>';
            } else {
                filteredTransactions.forEach(transaction => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${transaction.id}</td>
                        <td>${transaction.date}</td>
                        <td>${transaction.customer}</td>
                        <td>${transaction.amount.toFixed(2)} جنيه</td>
                        <td>${transaction.method}</td>
                        <td>
                            <button class="transaction-action" onclick="viewTransactionDetails('${transaction.id}')">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="transaction-action" onclick="printTransactionReceipt('${transaction.id}')">
                                <i class="fas fa-print"></i>
                            </button>
                        </td>
                    `;
                    resultsContainer.appendChild(row);
                });
            }
        }
        
        // وظائف إضافية للتعامل مع المعاملات
        function viewTransactionDetails(id) {
            alert(`عرض تفاصيل المعاملة رقم ${id}`);
            // هنا يمكن فتح صفحة تفاصيل المعاملة
        }
        
        function printTransactionReceipt(id) {
            alert(`طباعة إيصال المعاملة رقم ${id}`);
            // هنا يمكن فتح نافذة طباعة للإيصال
        }

        // استعداد لعملية الدفع
        function prepareCheckout() {
            // التحقق من وجود منتجات في السلة
            if (window.cart.length === 0) {
                showNotification('لا يمكن إتمام عملية الدفع، السلة فارغة!', 'error');
                return;
            }

            // فتح نافذة الدفع
            openModal('paymentModal');
            
            // حساب الإجماليات
            const subtotal = calculateSubtotal();
            document.getElementById('paymentSubtotal').textContent = subtotal.toFixed(2);
            updateDiscountTotal();
        }

        // تنفيذ عملية الدفع
        function processPayment() {
            // التحقق من اختيار طريقة دفع
            const paymentMethod = document.querySelector('input[name="paymentMethod"]:checked');
            if (!paymentMethod) {
                showNotification('الرجاء اختيار طريقة الدفع', 'error');
                return;
            }
            
            // التحقق من اختيار عميل في حالة التقسيط
            if (paymentMethod.value === 'installment') {
                const selectedCustomer = document.querySelector('.customer-select-btn.selected');
                if (!selectedCustomer) {
                    document.getElementById('installmentWarning').style.display = 'flex';
                    return;
                } else {
                    document.getElementById('installmentWarning').style.display = 'none';
                }
            }
            
            // إعداد بيانات الدفع
            closeModal('paymentModal');
            
            // إظهار الإيصال
            prepareReceipt();
            openModal('receiptModal');
            
            // في الحالة الحقيقية هنا ستقوم بإرسال البيانات إلى الخادم
            
            // مسح السلة بعد إتمام العملية
            setTimeout(() => {
                window.cart = [];
                updateCartDisplay();
            }, 1000);
        }

        // إعداد أحداث إغلاق المودال وإظهار الإشعارات
        document.addEventListener('DOMContentLoaded', function() {
            // إعداد أحداث المودالات
            setupModals();
            
            // إعداد أحداث البحث
            setupSearch();
            
            // تحميل العمليات الأخيرة من localStorage
            loadRecentTransactions();
            
            // تحميل المخزون من localStorage
            loadInventory();
            
            // إضافة معالج لزر الإغلاق الأخضر
            const greenCloseBtn = document.querySelector('.green-close-btn');
            if (greenCloseBtn) {
                greenCloseBtn.addEventListener('click', function() {
                    document.getElementById('receiptModal').style.display = 'none';
                });
            }
        });

        // إعداد أحداث المودالات
        function setupModals() {
            // إضافة معالجات لأزرار إغلاق المودال
            const closeButtons = document.querySelectorAll('.close-modal');
            closeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // العثور على أقرب مودال
                    const modal = this.closest('.modal');
                    if (modal) {
                        modal.style.display = 'none';
                    }
                });
            });
        }

        // إعداد أحداث البحث
        function setupSearch() {
            const searchInput = document.getElementById('productSearch');
            if (searchInput) {
                // بحث فوري عند الكتابة
                searchInput.addEventListener('input', searchProducts);
                
                // مسح البحث بالضغط على Esc
                searchInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        this.value = '';
                        searchProducts();
                    }
                });
            }
        }

        // تحميل العمليات الأخيرة من التخزين المحلي
        function loadRecentTransactions() {
            const transactionsList = document.querySelector('.transactions-list');
            if (!transactionsList) return;
            
            // محاولة الحصول على العمليات المخزنة
            let savedSales = [];
            try {
                savedSales = JSON.parse(localStorage.getItem('recent_sales')) || [];
            } catch (error) {
                console.error('حدث خطأ أثناء تحميل العمليات:', error);
                return;
            }
            
            // إذا لم تكن هناك عمليات مخزنة، نترك الشاشة كما هي
            if (savedSales.length === 0) return;
            
            // تفريغ قائمة العمليات الحالية
            transactionsList.innerHTML = '';
            
            // إضافة العمليات المخزنة
            savedSales.forEach(sale => {
                // إنشاء عنصر العملية
                const transactionItem = document.createElement('div');
                transactionItem.className = 'transaction-item';
                transactionItem.setAttribute('data-id', sale.invoice_number);
                
                // تحويل الطابع الزمني إذا كان موجودًا
                let formattedDate = sale.date;
                let formattedTime = '';
                
                if (sale.timestamp) {
                    const date = new Date(sale.timestamp);
                    formattedDate = `${date.getDate()}/${date.getMonth() + 1}/${date.getFullYear()}`;
                    formattedTime = `${date.getHours()}:${date.getMinutes().toString().padStart(2, '0')}`;
                }
                
                // إنشاء محتوى العملية
                transactionItem.innerHTML = `
                    <div class="transaction-info">
                        <div class="transaction-date">
                            <i class="fas fa-calendar-alt"></i>
                            ${formattedDate}
                        </div>
                        <div class="transaction-time">
                            <i class="fas fa-clock"></i>
                            ${formattedTime}
                        </div>
                    </div>
                    <div class="transaction-details">
                        <div class="transaction-customer">
                            <i class="fas fa-user"></i>
                            ${sale.customer || 'عميل نقدي'}
                        </div>
                        <div class="transaction-total">
                            ${parseFloat(sale.total).toFixed(2)} جنيه
                        </div>
                    </div>
                    <div class="transaction-actions">
                        <button class="transaction-action" data-action="view" title="عرض التفاصيل">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="transaction-action" data-action="print" title="طباعة الفاتورة">
                            <i class="fas fa-print"></i>
                        </button>
                        <button class="transaction-action" data-action="receipt" title="إرسال الإيصال">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                `;
                
                // إضافة العملية إلى القائمة
                transactionsList.appendChild(transactionItem);
                
                // إضافة معالجات الأحداث للأزرار
                setupTransactionActions(transactionItem);
            });
        }

        // تحميل بيانات المخزون من التخزين المحلي
        function loadInventory() {
            // محاولة الحصول على المخزون المخزن
            let inventory = null;
            try {
                inventory = JSON.parse(localStorage.getItem('inventory')) || {};
            } catch (error) {
                console.error('حدث خطأ أثناء تحميل المخزون:', error);
                return;
            }
            
            // إذا لم تكن هناك بيانات مخزون مخزنة، يتم التهيئة المبدئية
            if (Object.keys(inventory).length === 0) {
                initializeInventory();
            } else {
                // تحديث عرض المنتجات بالكميات المخزنة
                updateProductsDisplay(inventory);
            }
        }

        // تهيئة المخزون المبدئي من المنتجات المعروضة
        function initializeInventory() {
            const productCards = document.querySelectorAll('.product-card');
            if (!productCards.length) return;
            
            const inventory = {};
            
            productCards.forEach(card => {
                const productId = card.getAttribute('data-id');
                const productName = card.getAttribute('data-name');
                const productStock = parseInt(card.getAttribute('data-stock')) || 0;
                
                inventory[productId] = {
                    id: productId,
                    name: productName,
                    quantity: productStock
                };
            });
            
            // حفظ المخزون المبدئي
            try {
                localStorage.setItem('inventory', JSON.stringify(inventory));
            } catch (error) {
                console.error('حدث خطأ أثناء تهيئة المخزون:', error);
            }
        }
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/html5-qrcode/dist/html5-qrcode.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="../assets/js/cashier-new.js"></script>
    <script src="../assets/js/cashier-enhanced.js"></script>
    <script src="../assets/js/cashier-pdf-fix.js"></script>
    <script src="../assets/js/products-permanent-fix.js"></script>
</body>
</html>