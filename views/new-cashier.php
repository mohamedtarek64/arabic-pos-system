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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/cashier.css">
</head>
<body>
    <div class="container">
        
        <div class="top-nav">
            <div class="logo">
                <i class="fas fa-cash-register"></i>
                <span>نظام الكاشير</span>
            </div>
            <div class="navigation">
                <a href="../index.php"><i class="fas fa-home"></i> الرئيسية</a>
                <a href="products.php"><i class="fas fa-box"></i> المنتجات</a>
                <a href="customers.php"><i class="fas fa-users"></i> العملاء</a>
            </div>
        </div>
        
        <div class="main-content">
            <div class="cashier-container">
                
                <div class="cashier-products-section">
                    
                    <div class="product-search-container">
                        <div class="search-input-wrapper">
                            <input type="text" id="productSearch" placeholder="بحث عن منتج...">
                            <i class="fas fa-search"></i>
                            <button class="barcode-btn" id="barcodeBtn" title="قراءة باركود">
                                <i class="fas fa-barcode"></i>
                            </button>
                        </div>
                        
                        
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
                                     data-code="<?php echo htmlspecialchars($product['code']); ?>">
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
                                <button class="payment-method" data-method="installment">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span>تقسيط</span>
                                </button>
                                <button class="payment-method" data-method="visa">
                                    <i class="fas fa-credit-card"></i>
                                    <span>فيزا</span>
                                </button>
                            </div>
                            
                            <div class="payment-actions">
                                <button class="hold-order-btn" id="holdOrderBtn">
                                    <i class="fas fa-pause-circle"></i>
                                    <span>تعليق الطلب</span>
                                </button>
                                <button class="checkout-btn" id="checkoutBtn">
                                    <i class="fas fa-check-circle"></i>
                                    <span>إتمام البيع</span>
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
                    <button class="complete-btn" id="completePaymentBtn">إتمام الدفع</button>
                </div>
            </div>
        </div>
    </div>
    
    
    <div class="modal" id="receiptModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>إيصال البيع</h3>
                <button class="close-modal" data-modal="receiptModal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="receipt-container" id="receiptContainer">
                    <div id="receipt">
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
                                <span id="receiptNumber"></span>
                            </div>
                            <div class="receipt-row">
                                <span>التاريخ:</span>
                                <span id="receiptDate"></span>
                            </div>
                            <div class="receipt-row">
                                <span>الوقت:</span>
                                <span id="receiptTime"></span>
                            </div>
                            <div class="receipt-row">
                                <span>العميل:</span>
                                <span id="receiptCustomer"></span>
                            </div>
                        </div>
                        
                        <table class="receipt-items">
                            <thead>
                                <tr>
                                    <th>المنتج</th>
                                    <th>الكمية</th>
                                    <th>السعر</th>
                                    <th>الإجمالي</th>
                                </tr>
                            </thead>
                            <tbody id="receiptItemsList"></tbody>
                        </table>
                        
                        <div class="receipt-summary">
                            <div class="summary-row">
                                <span>المجموع:</span>
                                <span id="receiptSubtotal"></span>
                            </div>
                            <div class="summary-row">
                                <span>الخصم:</span>
                                <span id="receiptDiscount"></span>
                            </div>
                            <div class="summary-row total">
                                <span>الإجمالي:</span>
                                <span id="receiptTotal"></span>
                            </div>
                            <div class="payment-method-row">
                                <span>طريقة الدفع:</span>
                                <span id="receiptPaymentMethod"></span>
                            </div>
                        </div>
                        
                        <div class="receipt-footer">
                            <p>شكراً لثقتكم بنا</p>
                        </div>
                    </div>
                </div>
                
                <div class="receipt-actions">
                    <button class="print-btn" id="printReceiptBtn">
                        <i class="fas fa-print"></i> طباعة
                    </button>
                    <button class="email-btn" id="emailReceiptBtn">
                        <i class="fas fa-envelope"></i> إرسال بالبريد
                    </button>
                    <button class="whatsapp-btn" id="whatsappReceiptBtn">
                        <i class="fab fa-whatsapp"></i> مشاركة عبر واتس اب
                    </button>
                    <button class="payment-complete-btn" id="paymentCompleteBtn">
                        <i class="fas fa-check-circle"></i> تم الدفع
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/html5-qrcode/dist/html5-qrcode.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script>
        // إعداد بيانات المنتجات من PHP إلى JavaScript
        const dbProducts = <?php echo json_encode($all_products); ?>;
        const dbCustomers = <?php echo json_encode($customers); ?>;
    </script>
    
    
    <script src="../assets/js/cashier.js"></script>
    <script src="products-permanent-fix.js"></script>
</body>
</html> 