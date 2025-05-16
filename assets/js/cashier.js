// Cashier JavaScript Functionality
document.addEventListener('DOMContentLoaded', function() {
    console.log('Cashier.js loaded successfully');
    
    // Initialize variables
    let cart = [];
    let selectedCustomer = null;
    let selectedPaymentMethod = 'cash';
    let taxRate = 0.14; // 14% tax rate
    
    // DOM Elements
    const productSearch = document.getElementById('productSearch');
    const productsGrid = document.getElementById('productsGrid');
    const categoryBtns = document.querySelectorAll('.category-btn');
    const customerSearch = document.getElementById('customerSearch');
    const customerResults = document.getElementById('customerResults');
    const selectedCustomerEl = document.getElementById('selectedCustomer');
    const addCustomerBtn = document.getElementById('addCustomerBtn');
    const customerModal = document.getElementById('customerModal');
    const addCustomerForm = document.getElementById('addCustomerForm');
    const cartItemsEl = document.getElementById('cartItems');
    const clearCartBtn = document.getElementById('clearCartBtn');
    const subtotalEl = document.getElementById('subtotal');
    const taxEl = document.getElementById('tax');
    const discountValueEl = document.getElementById('discountValue');
    const discountTypeEl = document.getElementById('discountType');
    const totalEl = document.getElementById('total');
    const paymentMethods = document.querySelectorAll('.payment-method');
    const checkoutBtn = document.getElementById('checkoutBtn');
    const holdOrderBtn = document.getElementById('holdOrderBtn');
    const checkoutModal = document.getElementById('checkoutModal');
    const receiptModal = document.getElementById('receiptModal');
    const closeModalBtns = document.querySelectorAll('.close-modal');
    const cancelBtns = document.querySelectorAll('.cancel-btn');
    const completeBtn = document.querySelector('.complete-btn');
    const printReceiptBtn = document.getElementById('printReceiptBtn');
    const emailReceiptBtn = document.getElementById('emailReceiptBtn');
    const whatsappReceiptBtn = document.getElementById('whatsappReceiptBtn');
    const barcodeBtn = document.getElementById('barcodeBtn');
    
    // Initialize the products grid
    function initProductsGrid(category = 'all') {
        productsGrid.innerHTML = '';
        
        const filteredProducts = category === 'all' 
            ? products 
            : products.filter(product => product.category === category);
        
        filteredProducts.forEach(product => {
            const productCard = document.createElement('div');
            productCard.className = 'product-card';
            productCard.dataset.id = product.id;
            productCard.dataset.price = product.price;
            productCard.dataset.name = product.name;
            
            productCard.innerHTML = `
                <div class="product-img">
                    <img src="${product.image}" alt="${product.name}">
                </div>
                <div class="product-info">
                    <h4>${product.name}</h4>
                    <p class="product-price">${formatPrice(product.price)} جنيه</p>
                </div>
            `;
            
            productCard.addEventListener('click', () => addToCart(product));
            productsGrid.appendChild(productCard);
        });
    }
    
    // Add product to cart
    function addToCart(product) {
        const existingItem = cart.find(item => item.id === product.id);
        
        if (existingItem) {
            existingItem.quantity += 1;
        } else {
            cart.push({
                id: product.id,
                name: product.name,
                price: product.price,
                quantity: 1
            });
        }
        
        updateCart();
    }
    
    // Update cart display
    function updateCart() {
        if (cart.length === 0) {
            cartItemsEl.innerHTML = `
                <div class="empty-cart">
                    <i class="fas fa-shopping-basket"></i>
                    <p>السلة فارغة</p>
                </div>
            `;
        } else {
            cartItemsEl.innerHTML = '';
            
            cart.forEach(item => {
                const cartItem = document.createElement('div');
                cartItem.className = 'cart-item';
                
                cartItem.innerHTML = `
                    <div class="cart-item-details">
                        <div class="cart-item-name">${item.name}</div>
                        <div class="cart-item-price">${formatPrice(item.price)} جنيه</div>
                    </div>
                    <div class="cart-item-quantity">
                        <button class="quantity-btn minus" data-id="${item.id}">-</button>
                        <input type="text" class="quantity-input" value="${item.quantity}" readonly>
                        <button class="quantity-btn plus" data-id="${item.id}">+</button>
                    </div>
                    <div class="cart-item-total">${formatPrice(item.price * item.quantity)} جنيه</div>
                    <button class="remove-item" data-id="${item.id}">×</button>
                `;
                
                cartItemsEl.appendChild(cartItem);
            });
            
            // Add event listeners for quantity buttons and remove buttons
            document.querySelectorAll('.quantity-btn.minus').forEach(btn => {
                btn.addEventListener('click', () => decreaseQuantity(parseInt(btn.dataset.id)));
            });
            
            document.querySelectorAll('.quantity-btn.plus').forEach(btn => {
                btn.addEventListener('click', () => increaseQuantity(parseInt(btn.dataset.id)));
            });
            
            document.querySelectorAll('.remove-item').forEach(btn => {
                btn.addEventListener('click', () => removeFromCart(parseInt(btn.dataset.id)));
            });
        }
        
        updateCartSummary();
    }
    
    // Increase item quantity
    function increaseQuantity(id) {
        const item = cart.find(item => item.id === id);
        if (item) {
            item.quantity += 1;
            updateCart();
        }
    }
    
    // Decrease item quantity
    function decreaseQuantity(id) {
        const item = cart.find(item => item.id === id);
        if (item) {
            item.quantity -= 1;
            if (item.quantity <= 0) {
                removeFromCart(id);
            } else {
                updateCart();
            }
        }
    }
    
    function removeFromCart(id) {
        cart = cart.filter(item => item.id !== id);
        updateCart();
    }
    
    // Clear cart
    function clearCart() {
        cart = [];
        updateCart();
    }
    
    // Update cart summary
    function updateCartSummary() {
        const subtotal = calculateSubtotal();
        const tax = subtotal * taxRate;
        const discount = calculateDiscount(subtotal);
        const total = subtotal + tax - discount;
        
        subtotalEl.textContent = `${formatPrice(subtotal)} جنيه`;
        taxEl.textContent = `${formatPrice(tax)} جنيه`;
        totalEl.textContent = `${formatPrice(total)} جنيه`;
        
        // Update checkout modal summary
        if (document.getElementById('checkoutSubtotal')) {
            document.getElementById('checkoutSubtotal').textContent = `${formatPrice(subtotal)} جنيه`;
            document.getElementById('checkoutTax').textContent = `${formatPrice(tax)} جنيه`;
            document.getElementById('checkoutDiscount').textContent = `${formatPrice(discount)} جنيه`;
            document.getElementById('checkoutTotal').textContent = `${formatPrice(total)} جنيه`;
        }
    }
    
    // Calculate subtotal
    function calculateSubtotal() {
        return cart.reduce((total, item) => total + (item.price * item.quantity), 0);
    }
    
    // Calculate discount
    function calculateDiscount(subtotal) {
        const value = parseFloat(discountValueEl.value) || 0;
        const type = discountTypeEl.value;
        
        if (type === 'percentage') {
            return subtotal * (value / 100);
        } else {
            return value;
        }
    }
    
    // Format price with thousand separators
    function formatPrice(price) {
        return price.toLocaleString('ar-EG');
    }
    
    // Search products
    function searchProducts(query) {
        if (!query) {
            initProductsGrid(document.querySelector('.category-btn.active').dataset.category);
            return;
        }
        
        const filteredProducts = products.filter(product => 
            product.name.toLowerCase().includes(query.toLowerCase()) || 
            product.code.toLowerCase().includes(query.toLowerCase()) || 
            product.barcode.toLowerCase().includes(query.toLowerCase())
        );
        
        productsGrid.innerHTML = '';
        
        if (filteredProducts.length === 0) {
            productsGrid.innerHTML = `
                <div style="grid-column: 1 / -1; text-align: center; padding: 20px; color: #7b8a9a;">
                    <i class="fas fa-search" style="font-size: 2em; margin-bottom: 10px;"></i>
                    <p>لا توجد منتجات مطابقة للبحث</p>
                </div>
            `;
            return;
        }
        
        filteredProducts.forEach(product => {
            const productCard = document.createElement('div');
            productCard.className = 'product-card';
            productCard.dataset.id = product.id;
            productCard.dataset.price = product.price;
            productCard.dataset.name = product.name;
            productCard.dataset.code = product.code;
            productCard.dataset.barcode = product.barcode;
            
            productCard.innerHTML = `
                <div class="product-img">
                    <img src="${product.image}" alt="${product.name}">
                </div>
                <div class="product-info">
                    <h4>${product.name}</h4>
                    <p class="product-price">${formatPrice(product.price)} جنيه</p>
                </div>
            `;
            
            productCard.addEventListener('click', () => addToCart(product));
            productsGrid.appendChild(productCard);
        });
    }
    
    // Search customers
    function searchCustomers(query) {
        if (!query) {
            customerResults.style.display = 'none';
            return;
        }
        
        const filteredCustomers = customers.filter(customer => 
            customer.name.toLowerCase().includes(query.toLowerCase()) || 
            customer.phone.includes(query)
        );
        
        if (filteredCustomers.length === 0) {
            customerResults.innerHTML = `
                <div style="padding: 10px; text-align: center; color: #7b8a9a;">
                    لا يوجد عملاء مطابقين للبحث
                </div>
            `;
            customerResults.style.display = 'block';
            return;
        }
        
        customerResults.innerHTML = '';
        
        filteredCustomers.forEach(customer => {
            const customerItem = document.createElement('div');
            customerItem.className = 'customer-result-item';
            customerItem.dataset.id = customer.id;
            
            customerItem.innerHTML = `
                <div class="customer-name">${customer.name}</div>
                <div class="customer-phone">${customer.phone}</div>
            `;
            
            customerItem.addEventListener('click', () => selectCustomer(customer));
            customerResults.appendChild(customerItem);
        });
        
        customerResults.style.display = 'block';
    }
    
    // Select customer
    function selectCustomer(customer) {
        selectedCustomer = customer;
        
        selectedCustomerEl.innerHTML = `
            <div class="customer-info">
                <div class="customer-info-header">
                    <span class="customer-info-name">${customer.name}</span>
                    <button class="remove-customer">×</button>
                </div>
                <div class="customer-info-details">
                    <div>${customer.phone}</div>
                    <div>${customer.email || ''}</div>
                </div>
            </div>
        `;
        
        customerResults.style.display = 'none';
        customerSearch.value = '';
        
        // Add event listener to remove customer button
        document.querySelector('.remove-customer').addEventListener('click', removeCustomer);
    }
    
    function removeCustomer() {
        selectedCustomer = null;
        
        selectedCustomerEl.innerHTML = `
            <div class="no-customer-selected">
                <i class="fas fa-user-plus"></i>
                <span>لم يتم اختيار عميل</span>
            </div>
        `;
    }
    
    // Show add customer modal
    function showAddCustomerModal() {
        customerModal.style.display = 'flex';
        document.getElementById('customerName').focus();
    }
    
    // Hide modal
    function hideModal(modal) {
        modal.style.display = 'none';
    }
    
    // Add new customer
    function addNewCustomer(e) {
        e.preventDefault();
        
        const name = document.getElementById('customerName').value;
        const phone = document.getElementById('customerPhone').value;
        const email = document.getElementById('customerEmail').value;
        const address = document.getElementById('customerAddress').value;
        
        const newCustomer = {
            id: customers.length + 1,
            name,
            phone,
            email,
            address
        };
        
        customers.push(newCustomer);
        selectCustomer(newCustomer);
        hideModal(customerModal);
        
        // Reset form
        addCustomerForm.reset();
    }
    
    // Show checkout modal
    function showCheckoutModal() {
        if (cart.length === 0) {
            alert('السلة فارغة');
            return;
        }
        
        // Update payment details based on selected payment method
        document.getElementById('cashPaymentDetails').style.display = selectedPaymentMethod === 'cash' ? 'block' : 'none';
        document.getElementById('cardPaymentDetails').style.display = selectedPaymentMethod === 'card' ? 'block' : 'none';
        document.getElementById('laterPaymentDetails').style.display = selectedPaymentMethod === 'later' ? 'block' : 'none';
        
        // Set amount paid to total by default
        const total = calculateSubtotal() + (calculateSubtotal() * taxRate) - calculateDiscount(calculateSubtotal());
        document.getElementById('amountPaid').value = total.toFixed(2);
        document.getElementById('changeAmount').value = '0.00';
        
        checkoutModal.style.display = 'flex';
    }
    
    // Complete checkout
    function completeCheckout() {
        // In a real app, this would save the order to the database
        
        // Show receipt modal
        showReceiptModal();
        hideModal(checkoutModal);
    }
    
    // Show receipt modal
    function showReceiptModal() {
        // Generate receipt number
        const receiptNumber = 'INV-' + Math.floor(Math.random() * 10000).toString().padStart(4, '0');
        
        // Get current date and time
        const now = new Date();
        const date = now.toLocaleDateString('ar-EG');
        const time = now.toLocaleTimeString('ar-EG');
        
        // Set receipt details
        document.getElementById('receiptNumber').textContent = receiptNumber;
        document.getElementById('receiptDate').textContent = date;
        document.getElementById('receiptTime').textContent = time;
        document.getElementById('receiptCustomer').textContent = selectedCustomer ? selectedCustomer.name : 'عميل نقدي';
        
        // Set receipt items
        const receiptItemsList = document.getElementById('receiptItemsList');
        receiptItemsList.innerHTML = '';
        
        cart.forEach(item => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${item.name}</td>
                <td>${item.quantity}</td>
                <td>${formatPrice(item.price)} جنيه</td>
                <td>${formatPrice(item.price * item.quantity)} جنيه</td>
            `;
            receiptItemsList.appendChild(tr);
        });
        
        // Set receipt summary
        const subtotal = calculateSubtotal();
        const tax = subtotal * taxRate;
        const discount = calculateDiscount(subtotal);
        const total = subtotal + tax - discount;
        
        document.getElementById('receiptSubtotal').textContent = `${formatPrice(subtotal)} جنيه`;
        document.getElementById('receiptTax').textContent = `${formatPrice(tax)} جنيه`;
        document.getElementById('receiptDiscount').textContent = `${formatPrice(discount)} جنيه`;
        document.getElementById('receiptTotal').textContent = `${formatPrice(total)} جنيه`;
        
        receiptModal.style.display = 'flex';
        
        // After showing receipt, clear cart and customer
        clearCart();
        removeCustomer();
    }
    
    // Print receipt
    function printReceipt() {
        const receiptContent = document.getElementById('receipt').innerHTML;
        const printWindow = window.open('', '_blank');
        
        printWindow.document.write(`
            <html>
                <head>
                    <title>فاتورة</title>
                    <style>
                        body {
                            font-family: 'Cairo', 'Segoe UI', Arial, sans-serif;
                            direction: rtl;
                            padding: 20px;
                        }
                        .receipt-header {
                            text-align: center;
                            margin-bottom: 15px;
                            padding-bottom: 15px;
                            border-bottom: 1px dashed #ccc;
                        }
                        .store-info h2 {
                            margin: 0 0 5px 0;
                        }
                        .store-info p {
                            margin: 0 0 5px 0;
                        }
                        .receipt-details {
                            margin-bottom: 15px;
                            padding-bottom: 15px;
                            border-bottom: 1px dashed #ccc;
                        }
                        .receipt-row {
                            display: flex;
                            justify-content: space-between;
                            margin-bottom: 5px;
                        }
                        .receipt-items {
                            width: 100%;
                            border-collapse: collapse;
                            margin-bottom: 15px;
                        }
                        .receipt-items th, .receipt-items td {
                            padding: 8px;
                            text-align: right;
                        }
                        .receipt-items th {
                            border-bottom: 1px solid #ccc;
                        }
                        .receipt-summary {
                            margin-top: 15px;
                            padding-top: 15px;
                            border-top: 1px dashed #ccc;
                        }
                        .receipt-footer {
                            text-align: center;
                            margin-top: 15px;
                            padding-top: 15px;
                            border-top: 1px dashed #ccc;
                        }
                    </style>
                </head>
                <body>
                    <div class="receipt-container">
                        ${receiptContent}
                    </div>
                </body>
            </html>
        `);
        
        printWindow.document.close();
        printWindow.focus();
        
        // Print after a short delay to ensure the content is loaded
        setTimeout(() => {
            printWindow.print();
            printWindow.close();
        }, 500);
    }
    
    // Calculate change amount
    function calculateChange() {
        const total = calculateSubtotal() + (calculateSubtotal() * taxRate) - calculateDiscount(calculateSubtotal());
        const amountPaid = parseFloat(document.getElementById('amountPaid').value) || 0;
        const change = amountPaid - total;
        
        document.getElementById('changeAmount').value = change.toFixed(2);
    }
    
    // Event Listeners
    
    // Initialize products grid on page load
    initProductsGrid();
    
    // Category buttons
    categoryBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            categoryBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            initProductsGrid(btn.dataset.category);
        });
    });
    
    // Product search
    productSearch.addEventListener('input', () => {
        searchProducts(productSearch.value);
    });
    
    // Customer search
    customerSearch.addEventListener('input', () => {
        searchCustomers(customerSearch.value);
    });
    
    // Hide customer results when clicking outside
    document.addEventListener('click', (e) => {
        if (!customerSearch.contains(e.target) && !customerResults.contains(e.target)) {
            customerResults.style.display = 'none';
        }
    });
    
    // Add customer button
    addCustomerBtn.addEventListener('click', showAddCustomerModal);
    
    // Add customer form submit
    addCustomerForm.addEventListener('submit', addNewCustomer);
    
    // Clear cart button
    clearCartBtn.addEventListener('click', clearCart);
    
    // Discount input
    discountValueEl.addEventListener('input', updateCartSummary);
    discountTypeEl.addEventListener('change', updateCartSummary);
    
    // Payment method selection
    paymentMethods.forEach(method => {
        method.addEventListener('click', () => {
            paymentMethods.forEach(m => m.classList.remove('active'));
            method.classList.add('active');
            selectedPaymentMethod = method.dataset.method;
        });
    });
    
    // Checkout button
    checkoutBtn.addEventListener('click', showCheckoutModal);
    
    // Complete checkout button
    completeBtn.addEventListener('click', completeCheckout);
    
    // Print receipt button
    printReceiptBtn.addEventListener('click', printReceipt);
    
    // Email receipt button (placeholder)
    emailReceiptBtn.addEventListener('click', () => {
        alert('سيتم إرسال الفاتورة عبر البريد الإلكتروني');
        hideModal(receiptModal);
    });
    
    // WhatsApp receipt button (placeholder)
    whatsappReceiptBtn.addEventListener('click', () => {
        alert('سيتم إرسال الفاتورة عبر واتساب');
        hideModal(receiptModal);
    });
    
    // Amount paid input
    document.getElementById('amountPaid').addEventListener('input', calculateChange);
    
    // Barcode button (placeholder)
    barcodeBtn.addEventListener('click', () => {
        alert('سيتم فتح قارئ الباركود');
    });
    
    // Hold order button (placeholder)
    holdOrderBtn.addEventListener('click', () => {
        if (cart.length === 0) {
            alert('السلة فارغة');
            return;
        }
        alert('تم تعليق الطلب');
    });
    
    // Close modal buttons
    closeModalBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const modal = btn.closest('.modal');
            hideModal(modal);
        });
    });
    
    // Cancel buttons
    cancelBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const modal = btn.closest('.modal');
            hideModal(modal);
        });
    });
    
    // Close modal when clicking outside
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                hideModal(modal);
            }
        });
    });
    
    // Initialize sidebar toggle functionality
    const menuToggle = document.querySelector('.menu-toggle');
    const sidebar = document.getElementById('sidebar');
    
    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            document.body.classList.toggle('sidebar-open');
        });
    }
    
    // التأكد من أن جميع العناصر مرئية
    ensureVisibility();
    
    // إعداد وظائف زر البحث
    setupSearchFunctions();
    
    // تهيئة أزرار الدفع وإضافة معالجات الأحداث
    setupPaymentButtons();
    
    // إعداد أزرار الفئات
    setupCategoryButtons();
    
    // إعداد أحداث بطاقات المنتجات
    setupProductCards();
    
    // إعداد سلة المشتريات والوظائف المرتبطة بها
    setupCart();
    
    // اضافة أحداث المودال
    setupModalEvents();
    
    // إعداد التفاعلات المتعلقة بالعملاء
    setupCustomerInteractions();
    
    // التأكد من أن كل العناصر ظاهرة بشكل صحيح بعد تحميل الصفحة
    setTimeout(ensureVisibility, 500);
    setTimeout(ensureVisibility, 1000);
    
    // إضافة مستمع حدث لزر "تم الدفع"
    const paymentCompleteBtn = document.getElementById('paymentCompleteBtn');
    if (paymentCompleteBtn) {
        paymentCompleteBtn.addEventListener('click', saveTransaction);
    }
});

// دالة للتأكد من رؤية كل العناصر
function ensureVisibility() {
    console.log('Ensuring visibility of all elements...');
    
    // التأكد من رؤية الحاويات الرئيسية
    document.querySelector('.cashier-container').style.display = 'flex';
    document.querySelector('.cashier-container').style.flexDirection = 'row-reverse';
    
    document.querySelector('.cashier-products-section').style.display = 'block';
    document.querySelector('.cashier-cart-section').style.display = 'flex';
    
    // التأكد من أن أزرار الدفع ظاهرة
    const paymentMethods = document.querySelectorAll('.payment-method');
    paymentMethods.forEach(btn => {
        btn.style.display = 'flex';
        btn.style.visibility = 'visible';
        btn.style.opacity = '1';
    });
    
    // التأكد من أن زر إتمام البيع ظاهر
    const checkoutBtn = document.getElementById('checkoutBtn');
    if (checkoutBtn) {
        checkoutBtn.style.display = 'flex';
        checkoutBtn.style.visibility = 'visible';
        checkoutBtn.style.opacity = '1';
    }
    
    // التأكد من أن شبكة المنتجات ظاهرة
    const productsGrid = document.getElementById('productsGrid');
    if (productsGrid) {
        productsGrid.style.display = 'grid';
        productsGrid.style.visibility = 'visible';
        productsGrid.style.opacity = '1';
    }
}

// إعداد وظائف البحث
function setupSearchFunctions() {
    const productSearch = document.getElementById('productSearch');
    if (productSearch) {
        productSearch.addEventListener('input', function() {
            filterProducts(this.value);
        });
    }
}

// تصفية المنتجات بناءً على نص البحث
function filterProducts(searchText) {
    const productCards = document.querySelectorAll('.product-card');
    const searchLower = searchText.toLowerCase();
    
    productCards.forEach(card => {
        const name = card.getAttribute('data-name').toLowerCase();
        const code = card.getAttribute('data-code').toLowerCase();
        const barcode = card.getAttribute('data-barcode')?.toLowerCase() || '';
        
        if (name.includes(searchLower) || 
            code.includes(searchLower) || 
            barcode.includes(searchLower) || 
            searchText === '') {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

// إعداد أزرار الفئات
function setupCategoryButtons() {
    const categoryButtons = document.querySelectorAll('.category-btn');
    
    categoryButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            // إزالة الفئة النشطة من جميع الأزرار
            categoryButtons.forEach(b => b.classList.remove('active'));
            
            // إضافة الفئة النشطة للزر المحدد
            this.classList.add('active');
            
            // تصفية المنتجات حسب الفئة
            filterByCategory(this.getAttribute('data-category'));
        });
    });
}

// تصفية المنتجات حسب الفئة
function filterByCategory(category) {
    const productCards = document.querySelectorAll('.product-card');
    
    productCards.forEach(card => {
        const productCategory = card.getAttribute('data-category');
        
        if (category === 'all' || productCategory === category) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

// إعداد بطاقات المنتجات
function setupProductCards() {
    const productCards = document.querySelectorAll('.product-card');
    
    productCards.forEach(card => {
        // استخدام addEventListener بدلاً من onclick للحصول على تغطية أفضل للأحداث
        card.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            const price = parseFloat(this.getAttribute('data-price'));
            const code = this.getAttribute('data-code');
            
            console.log('Product clicked: ' + name);
            
            addToCart(id, name, price, code);
        });
    });
}

// إعداد وظائف سلة المشتريات
function setupCart() {
    // تهيئة سلة فارغة
    window.cart = window.cart || [];
    
    // عرض محتويات السلة في بداية التحميل
    updateCartDisplay();
    
    // إعداد زر تفريغ السلة
    const clearCartBtn = document.getElementById('clearCartBtn');
    if (clearCartBtn) {
        clearCartBtn.addEventListener('click', function() {
            clearCart();
        });
    }
    
    // إعداد حقل الخصم
    const discountInput = document.getElementById('discountValue');
    const discountType = document.getElementById('discountType');
    
    if (discountInput && discountType) {
        discountInput.addEventListener('input', updateCartTotal);
        discountType.addEventListener('change', updateCartTotal);
    }
}

// إضافة منتج إلى السلة
function addToCart(id, name, price, code) {
    // البحث عن المنتج في السلة
    const existingItemIndex = window.cart.findIndex(item => item.id === id);
    
    if (existingItemIndex !== -1) {
        // إذا كان المنتج موجودًا، زيادة الكمية
        window.cart[existingItemIndex].quantity += 1;
    } else {
        // إذا لم يكن موجودًا، إضافة منتج جديد
        window.cart.push({
            id: id,
            name: name,
            price: price,
            code: code,
            quantity: 1
        });
    }
    
    // تحديث عرض السلة
    updateCartDisplay();
}

// تحديث عرض السلة
function updateCartDisplay() {
    const cartItems = document.getElementById('cartItems');
    
    if (!cartItems) return;
    
    if (window.cart.length === 0) {
        // إذا كانت السلة فارغة
        cartItems.innerHTML = `
            <div class="empty-cart">
                <i class="fas fa-shopping-basket"></i>
                <p>السلة فارغة</p>
            </div>
        `;
    } else {
        // إذا كانت السلة تحتوي على منتجات
        cartItems.innerHTML = '';
        
        window.cart.forEach((item, index) => {
            const itemTotal = item.price * item.quantity;
            
            const itemElement = document.createElement('div');
            itemElement.className = 'cart-item';
            itemElement.innerHTML = `
                <div class="cart-item-details">
                    <div class="cart-item-name">${item.name}</div>
                    <div class="cart-item-price">${item.price.toFixed(2)} جنيه</div>
                </div>
                <div class="cart-item-quantity">
                    <button class="quantity-btn minus" data-index="${index}">-</button>
                    <input type="text" class="quantity-input" value="${item.quantity}" readonly>
                    <button class="quantity-btn plus" data-index="${index}">+</button>
                </div>
                <div class="cart-item-total">${itemTotal.toFixed(2)} جنيه</div>
                <button class="remove-item" data-index="${index}">&times;</button>
            `;
            
            cartItems.appendChild(itemElement);
        });
        
        // إضافة أحداث للأزرار الجديدة
        addCartItemEvents();
    }
    
    // تحديث المجموع
    updateCartTotal();
}

// إضافة أحداث لعناصر السلة
function addCartItemEvents() {
    // أزرار زيادة الكمية
    document.querySelectorAll('.quantity-btn.plus').forEach(btn => {
        btn.addEventListener('click', function() {
            const index = parseInt(this.getAttribute('data-index'));
            increaseQuantity(index);
        });
    });
    
    // أزرار إنقاص الكمية
    document.querySelectorAll('.quantity-btn.minus').forEach(btn => {
        btn.addEventListener('click', function() {
            const index = parseInt(this.getAttribute('data-index'));
            decreaseQuantity(index);
        });
    });
    
    // أزرار حذف المنتج
    document.querySelectorAll('.remove-item').forEach(btn => {
        btn.addEventListener('click', function() {
            const index = parseInt(this.getAttribute('data-index'));
            removeCartItem(index);
        });
    });
}

// زيادة كمية منتج
function increaseQuantity(index) {
    if (window.cart[index]) {
        window.cart[index].quantity += 1;
        updateCartDisplay();
    }
}

// إنقاص كمية منتج
function decreaseQuantity(index) {
    if (window.cart[index] && window.cart[index].quantity > 1) {
        window.cart[index].quantity -= 1;
        updateCartDisplay();
    } else if (window.cart[index] && window.cart[index].quantity === 1) {
        removeCartItem(index);
    }
}

// إزالة منتج من السلة
function removeCartItem(index) {
    window.cart.splice(index, 1);
    updateCartDisplay();
}

// تفريغ السلة
function clearCart() {
    window.cart = [];
    updateCartDisplay();
}

// تحديث إجمالي السلة
function updateCartTotal() {
    const subtotalElement = document.getElementById('subtotal');
    const totalElement = document.getElementById('total');
    
    if (!subtotalElement || !totalElement) return;
    
    // حساب المجموع الفرعي
    const subtotal = window.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    
    // الحصول على قيمة الخصم ونوعه
    const discountInput = document.getElementById('discountValue');
    const discountType = document.getElementById('discountType');
    let discount = 0;
    
    if (discountInput && discountType) {
        const discountValue = parseFloat(discountInput.value) || 0;
        
        if (discountType.value === 'percentage') {
            // خصم نسبة مئوية
            discount = subtotal * (discountValue / 100);
        } else {
            // خصم مبلغ ثابت
            discount = discountValue;
        }
    }
    
    // حساب الإجمالي بعد الخصم
    const total = Math.max(0, subtotal - discount);
    
    // تحديث العناصر في صفحة
    subtotalElement.textContent = subtotal.toFixed(2) + ' جنيه';
    totalElement.textContent = total.toFixed(2) + ' جنيه';
}

// إعداد أزرار الدفع
function setupPaymentButtons() {
    // أزرار طرق الدفع
    const paymentMethods = document.querySelectorAll('.payment-method');
    paymentMethods.forEach(btn => {
        // إضافة أحداث متعددة لضمان عمل الأزرار
        btn.addEventListener('click', function(e) {
            // إزالة الفئة النشطة من جميع الأزرار
            paymentMethods.forEach(b => b.classList.remove('active'));
            
            // إضافة الفئة النشطة للزر المختار
            this.classList.add('active');
            
            console.log('Payment method selected: ' + this.getAttribute('data-method'));
            
            // منع السلوك الافتراضي وانتشار الحدث
            e.preventDefault();
            e.stopPropagation();
            return false;
        });
    });
    
    // زر إتمام البيع
    const checkoutBtn = document.getElementById('checkoutBtn');
    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', function(e) {
            console.log('Checkout button clicked');
            
            if (window.cart.length === 0) {
                alert('السلة فارغة');
                return;
            }
            
            showCheckoutModal();
            
            // منع السلوك الافتراضي
            e.preventDefault();
            return false;
        });
    }
}

// عرض مودال إتمام البيع
function showCheckoutModal() {
    const modal = document.getElementById('checkoutModal');
    if (!modal) return;
    
    const subtotal = window.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const discountInput = document.getElementById('discountValue');
    const discountType = document.getElementById('discountType');
    let discount = 0;
    
    if (discountInput && discountType) {
        const discountValue = parseFloat(discountInput.value) || 0;
        
        if (discountType.value === 'percentage') {
            discount = subtotal * (discountValue / 100);
        } else {
            discount = discountValue;
        }
    }
    
    const total = Math.max(0, subtotal - discount);
    
    // تحديث البيانات في المودال
    document.getElementById('checkoutSubtotal').textContent = subtotal.toFixed(2) + ' جنيه';
    document.getElementById('checkoutDiscount').textContent = discount.toFixed(2) + ' جنيه';
    document.getElementById('checkoutTotal').textContent = total.toFixed(2) + ' جنيه';
    
    // استخدام dataset لتخزين قيم حسابية للاستخدام لاحقًا
    modal.dataset.subtotal = subtotal;
    modal.dataset.discount = discount;
    modal.dataset.total = total;
    
    // إظهار المودال
    modal.style.display = 'flex';
}

// إعداد أحداث المودال
function setupModalEvents() {
    // أزرار إغلاق المودال
    document.querySelectorAll('.close-modal, .cancel-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const modalId = this.getAttribute('data-modal');
            if (modalId) {
                document.getElementById(modalId).style.display = 'none';
            } else {
                const modal = this.closest('.modal');
                if (modal) modal.style.display = 'none';
            }
        });
    });
    
    // أزرار خيارات الدفع في المودال
    document.querySelectorAll('.payment-option').forEach(option => {
        option.addEventListener('click', function() {
            // إزالة الفئة النشطة من جميع الخيارات
            document.querySelectorAll('.payment-option').forEach(o => o.classList.remove('active'));
            
            // إضافة الفئة النشطة للخيار المحدد
            this.classList.add('active');
            
            // إظهار تفاصيل طريقة الدفع المناسبة
            const method = this.getAttribute('data-method');
            document.querySelectorAll('#cashDetails, #cardDetails, #deferredDetails').forEach(detail => {
                detail.classList.remove('active');
            });
            
            document.getElementById(method + 'Details').classList.add('active');
        });
    });
    
    // زر إتمام الدفع
    const completePaymentBtn = document.getElementById('completePaymentBtn');
    if (completePaymentBtn) {
        completePaymentBtn.addEventListener('click', function() {
            completePayment();
        });
    }
}

// إتمام عملية الدفع
function completePayment() {
    // إغلاق مودال الدفع
    document.getElementById('checkoutModal').style.display = 'none';
    
    // تحضير الإيصال وعرضه
    prepareReceipt();
    
    // عرض مودال الإيصال
    document.getElementById('receiptModal').style.display = 'flex';
    
    // تفريغ السلة بعد عملية الدفع الناجحة
    setTimeout(() => {
        clearCart();
    }, 500);
}

// تحضير الإيصال
function prepareReceipt() {
    const receiptDate = new Date();
    const receiptNumber = generateReceiptNumber();
    
    // تعيين التاريخ والوقت ورقم الإيصال
    document.getElementById('receiptDate').textContent = receiptDate.toLocaleDateString('ar-EG');
    document.getElementById('receiptTime').textContent = receiptDate.toLocaleTimeString('ar-EG');
    document.getElementById('receiptNumber').textContent = receiptNumber;
    
    // تعيين اسم العميل
    const selectedCustomer = document.querySelector('.customer-info-name');
    document.getElementById('receiptCustomer').textContent = selectedCustomer ? selectedCustomer.textContent : 'عميل نقدي';
    
    // تحضير قائمة المنتجات
    const receiptItemsList = document.getElementById('receiptItemsList');
    receiptItemsList.innerHTML = '';
    
    window.cart.forEach(item => {
        const itemTotal = item.price * item.quantity;
        
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${item.name}</td>
            <td>${item.quantity}</td>
            <td>${item.price.toFixed(2)} جنيه</td>
            <td>${itemTotal.toFixed(2)} جنيه</td>
        `;
        
        receiptItemsList.appendChild(row);
    });
    
    // استخراج القيم المحسوبة من مودال الدفع
    const checkoutModal = document.getElementById('checkoutModal');
    const subtotal = parseFloat(checkoutModal.dataset.subtotal) || 0;
    const discount = parseFloat(checkoutModal.dataset.discount) || 0;
    const total = parseFloat(checkoutModal.dataset.total) || 0;
    
    // تعيين ملخص القيم
    document.getElementById('receiptSubtotal').textContent = `${formatPrice(subtotal)} جنيه`;
    document.getElementById('receiptDiscount').textContent = `${formatPrice(discount)} جنيه`;
    document.getElementById('receiptTotal').textContent = `${formatPrice(total)} جنيه`;
}

// إنشاء رقم إيصال فريد
function generateReceiptNumber() {
    const timestamp = new Date().getTime();
    const random = Math.floor(Math.random() * 1000);
    return 'INV-' + timestamp.toString().slice(-6) + '-' + random;
}

// إعداد تفاعلات العملاء
function setupCustomerInteractions() {
    const addCustomerBtn = document.getElementById('addCustomerBtn');
    const customerSearch = document.getElementById('customerSearch');
    
    if (addCustomerBtn) {
        addCustomerBtn.addEventListener('click', function() {
            // افتح نموذج إضافة عميل جديد أو انتقل إلى صفحة العملاء
            alert('انتقل إلى صفحة العملاء لإضافة عميل جديد');
            return false;
        });
    }
    
    if (customerSearch) {
        customerSearch.addEventListener('input', function() {
            // محاكاة البحث عن العملاء
            const searchText = this.value.trim();
            
            if (searchText.length >= 2) {
                // عرض نتائج البحث (هذه مجرد عينة)
                showCustomerSearchResults(searchText);
            } else {
                // إخفاء نتائج البحث
                document.getElementById('customerResults').style.display = 'none';
            }
        });
    }
}

// عرض نتائج البحث عن العملاء
function showCustomerSearchResults(searchText) {
    const resultsContainer = document.getElementById('customerResults');
    
    // هنا يمكنك استبدال هذا بطلب AJAX للحصول على العملاء الفعليين
    const customers = [
        { id: 1, name: 'أحمد محمد', phone: '0123456789' },
        { id: 2, name: 'محمد أحمد', phone: '0123456788' },
        { id: 3, name: 'سارة أحمد', phone: '0123456787' }
    ];
    
    // تصفية العملاء
    const filteredCustomers = customers.filter(customer => 
        customer.name.includes(searchText) || customer.phone.includes(searchText)
    );
    
    if (filteredCustomers.length > 0) {
        resultsContainer.innerHTML = '';
        
        filteredCustomers.forEach(customer => {
            const resultItem = document.createElement('div');
            resultItem.className = 'customer-result-item';
            resultItem.setAttribute('data-id', customer.id);
            resultItem.innerHTML = `
                <div class="customer-name">${customer.name}</div>
                <div class="customer-phone">${customer.phone}</div>
            `;
            
            resultItem.addEventListener('click', function() {
                selectCustomer(customer);
                resultsContainer.style.display = 'none';
            });
            
            resultsContainer.appendChild(resultItem);
        });
        
        resultsContainer.style.display = 'block';
    } else {
        resultsContainer.innerHTML = '<div class="customer-result-item">لا توجد نتائج</div>';
        resultsContainer.style.display = 'block';
    }
}

// اختيار عميل
function selectCustomer(customer) {
    const selectedCustomer = document.getElementById('selectedCustomer');
    
    selectedCustomer.innerHTML = `
        <div class="customer-info">
            <div class="customer-info-header">
                <div class="customer-info-name">${customer.name}</div>
                <button class="remove-customer" id="removeCustomer">&times;</button>
            </div>
            <div class="customer-info-details">
                <div>الهاتف: ${customer.phone}</div>
            </div>
        </div>
    `;
    
    // إضافة حدث لزر إزالة العميل
    document.getElementById('removeCustomer').addEventListener('click', function() {
        removeSelectedCustomer();
    });
    
    // مسح مربع البحث
    document.getElementById('customerSearch').value = '';
}

// إزالة العميل المحدد
function removeSelectedCustomer() {
    document.getElementById('selectedCustomer').innerHTML = `
        <div class="no-customer-selected">
            <i class="fas fa-user-plus"></i>
            <span>لم يتم اختيار عميل</span>
        </div>
    `;
}

// وظيفة حفظ المعاملة في قاعدة البيانات
function saveTransaction() {
    try {
        console.log("جاري حفظ المعاملة...");
        
        // جمع بيانات المعاملة
        const transactionData = {
            // رقم الفاتورة
            invoiceNumber: document.getElementById('receiptNumber').textContent,
            
            // معلومات العميل
            customer: document.getElementById('receiptCustomer').textContent,
            
            // طريقة الدفع
            paymentMethod: document.getElementById('receiptPaymentMethod').textContent,
            
            // المبالغ
            subtotal: parseFloat(document.getElementById('receiptSubtotal').textContent.replace(' جنيه', '')),
            discount: parseFloat(document.getElementById('receiptDiscount').textContent.replace(' جنيه', '')),
            total: parseFloat(document.getElementById('receiptTotal').textContent.replace(' جنيه', '')),
            
            // بيانات التقسيط إذا كان مختاراً
            downPayment: parseFloat(document.getElementById('downPayment')?.value || 0),
            installmentCount: parseInt(document.getElementById('installmentCount')?.value || 0),
            firstPaymentDate: document.getElementById('firstPaymentDate')?.value || null,
            
            // بيانات البطاقة إذا كان الدفع بالفيزا
            cardType: document.getElementById('cardType')?.value || null,
            cardNumber: document.getElementById('cardNumber')?.value || null,
            transaction_ref: document.getElementById('transactionRef')?.value || null,
            
            // منتجات المعاملة
            items: extractProductsFromCart()
        };
        
        // التأكد من وجود قيم صالحة
        if (!transactionData.invoiceNumber || !transactionData.total) {
            showErrorMessage("بيانات المعاملة غير كاملة");
            return;
        }
        
        // إرسال البيانات إلى الخادم
        fetch('save-transaction.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(transactionData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // عرض رسالة نجاح
                showSuccessMessage("تم حفظ المعاملة بنجاح");
                
                // تفريغ السلة
                clearCart();
                
                // إغلاق مودال الإيصال
                const receiptModal = document.getElementById('receiptModal');
                if (receiptModal) {
                    receiptModal.style.display = 'none';
                }
                
                // تحديث قائمة آخر المعاملات
                loadRecentTransactions();
                
                console.log("تم حفظ المعاملة بنجاح:", data);
            } else {
                // عرض رسالة خطأ
                showErrorMessage(data.message || "حدث خطأ أثناء حفظ المعاملة");
                console.error("خطأ في حفظ المعاملة:", data);
            }
        })
        .catch(error => {
            showErrorMessage("حدث خطأ أثناء الاتصال بالخادم");
            console.error("خطأ في الاتصال بالخادم:", error);
        });
        
    } catch (error) {
        showErrorMessage("حدث خطأ أثناء إعداد المعاملة");
        console.error("خطأ في إعداد المعاملة:", error);
    }
}

// وظيفة لعرض رسالة نجاح
function showSuccessMessage(message) {
    // إنشاء عنصر للرسالة
    const messageElement = document.createElement('div');
    messageElement.className = 'alert-message success';
    messageElement.innerHTML = `
        <i class="fas fa-check-circle"></i>
        <span>${message}</span>
        <button class="close-message">&times;</button>
    `;
    
    // إضافة الرسالة إلى الصفحة
    document.body.appendChild(messageElement);
    
    // عرض الرسالة بتأثير متحرك
    setTimeout(() => {
        messageElement.classList.add('show');
    }, 10);
    
    // إضافة حدث لإغلاق الرسالة عند النقر على زر الإغلاق
    messageElement.querySelector('.close-message').addEventListener('click', () => {
        messageElement.classList.remove('show');
        setTimeout(() => {
            document.body.removeChild(messageElement);
        }, 300);
    });
    
    // إغلاق الرسالة تلقائياً بعد 5 ثوان
    setTimeout(() => {
        if (document.body.contains(messageElement)) {
            messageElement.classList.remove('show');
            setTimeout(() => {
                if (document.body.contains(messageElement)) {
                    document.body.removeChild(messageElement);
                }
            }, 300);
        }
    }, 5000);
}

// وظيفة لعرض رسالة خطأ
function showErrorMessage(message) {
    // إنشاء عنصر للرسالة
    const messageElement = document.createElement('div');
    messageElement.className = 'alert-message error';
    messageElement.innerHTML = `
        <i class="fas fa-exclamation-circle"></i>
        <span>${message}</span>
        <button class="close-message">&times;</button>
    `;
    
    // إضافة الرسالة إلى الصفحة
    document.body.appendChild(messageElement);
    
    // عرض الرسالة بتأثير متحرك
    setTimeout(() => {
        messageElement.classList.add('show');
    }, 10);
    
    // إضافة حدث لإغلاق الرسالة عند النقر على زر الإغلاق
    messageElement.querySelector('.close-message').addEventListener('click', () => {
        messageElement.classList.remove('show');
        setTimeout(() => {
            document.body.removeChild(messageElement);
        }, 300);
    });
    
    // إغلاق الرسالة تلقائياً بعد 5 ثوان
    setTimeout(() => {
        if (document.body.contains(messageElement)) {
            messageElement.classList.remove('show');
            setTimeout(() => {
                if (document.body.contains(messageElement)) {
                    document.body.removeChild(messageElement);
                }
            }, 300);
        }
    }, 5000);
}

// وظيفة تحميل آخر المعاملات
function loadRecentTransactions() {
    console.log("تحميل آخر المعاملات...");
    
    // الحصول على حاوية قائمة المعاملات
    const transactionsList = document.querySelector('.transactions-list');
    if (!transactionsList) {
        console.error("لم يتم العثور على حاوية قائمة المعاملات");
        return;
    }
    
    // عرض حالة التحميل
    transactionsList.innerHTML = `
        <div class="loading-transactions">
            <i class="fas fa-spinner fa-spin"></i>
            <p>جاري تحميل المعاملات...</p>
        </div>
    `;
    
    // طلب آخر المعاملات من الخادم
    fetch('load-recent-transactions.php')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && data.data) {
                if (data.data.length === 0) {
                    // لا توجد معاملات
                    transactionsList.innerHTML = `
                        <div class="empty-transactions">
                            <i class="fas fa-receipt"></i>
                            <p>لا توجد عمليات سابقة</p>
                        </div>
                    `;
                    return;
                }
                
                // إفراغ حاوية المعاملات
                transactionsList.innerHTML = '';
                
                // إضافة المعاملات إلى القائمة
                data.data.forEach(transaction => {
                    const transactionItem = document.createElement('div');
                    transactionItem.className = 'transaction-item';
                    transactionItem.dataset.id = transaction.id;
                    
                    // تحديد حالة المعاملة
                    let statusClass = 'status-paid';
                    let statusText = 'مدفوع';
                    
                    if (transaction.payment_status === 'partial') {
                        statusClass = 'status-partial';
                        statusText = 'دفعة جزئية';
                    } else if (transaction.payment_status === 'pending') {
                        statusClass = 'status-pending';
                        statusText = 'معلق';
                    }
                    
                    transactionItem.innerHTML = `
                        <div class="transaction-info">
                            <div class="transaction-date">
                                <i class="fas fa-calendar-alt"></i>
                                ${transaction.date}
                            </div>
                            <div class="transaction-time">
                                <i class="fas fa-clock"></i>
                                ${transaction.time}
                            </div>
                            <div class="transaction-status ${statusClass}">
                                <i class="fas fa-circle"></i>
                                ${statusText}
                            </div>
                        </div>
                        <div class="transaction-details">
                            <div class="transaction-customer">
                                <i class="fas fa-user"></i>
                                ${transaction.customer_name}
                            </div>
                            <div class="transaction-total">
                                ${transaction.total_amount} جنيه
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
                    
                    // إضافة أحداث لأزرار العمليات
                    const viewBtn = transactionItem.querySelector('[data-action="view"]');
                    const printBtn = transactionItem.querySelector('[data-action="print"]');
                    const receiptBtn = transactionItem.querySelector('[data-action="receipt"]');
                    
                    if (viewBtn) {
                        viewBtn.addEventListener('click', () => viewTransaction(transaction.id));
                    }
                    
                    if (printBtn) {
                        printBtn.addEventListener('click', () => printTransaction(transaction.id));
                    }
                    
                    if (receiptBtn) {
                        receiptBtn.addEventListener('click', () => shareTransaction(transaction.id));
                    }
                    
                    // إضافة عنصر المعاملة إلى القائمة
                    transactionsList.appendChild(transactionItem);
                });
                
                console.log(`تم تحميل ${data.data.length} معاملة`);
            } else {
                transactionsList.innerHTML = `
                    <div class="error-transactions">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p>حدث خطأ أثناء تحميل المعاملات</p>
                    </div>
                `;
                console.error("خطأ في البيانات المستلمة:", data);
            }
        })
        .catch(error => {
            transactionsList.innerHTML = `
                <div class="error-transactions">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>حدث خطأ أثناء الاتصال بالخادم</p>
                </div>
            `;
            console.error("خطأ في تحميل المعاملات:", error);
        });
}

// وظائف التفاعل مع المعاملات
function viewTransaction(id) {
    console.log(`عرض تفاصيل المعاملة ${id}`);
    // فتح صفحة تفاصيل المعاملة
    window.open(`transaction-detail.php?id=${id}`, '_blank');
}

function printTransaction(id) {
    console.log(`طباعة الفاتورة ${id}`);
    // فتح صفحة طباعة الفاتورة
    window.open(`print-invoice.php?id=${id}`, '_blank');
}

function shareTransaction(id) {
    console.log(`مشاركة الإيصال ${id}`);
    // فتح مودال مشاركة الإيصال
    alert(`سيتم مشاركة الإيصال رقم ${id} قريباً`);
}

// تحميل آخر المعاملات عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    // تحميل المعاملات فور تحميل الصفحة
    setTimeout(loadRecentTransactions, 500);
    
    // إضافة مستمع حدث لزر عرض الكل
    const viewAllBtn = document.getElementById('viewAllTransactionsBtn');
    if (viewAllBtn) {
        viewAllBtn.addEventListener('click', function() {
            window.location.href = 'transactions.php';
        });
    }
});

// وظيفة استخراج المنتجات من العربة أو الإيصال
function extractProductsFromCart() {
    const products = [];
    
    try {
        // محاولة استخراج المنتجات من جدول الإيصال أولاً
        const receiptItems = document.querySelectorAll('#receiptItemsList tr');
        if (receiptItems && receiptItems.length > 0) {
            console.log("استخراج المنتجات من جدول الإيصال...");
            
            receiptItems.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length >= 4) {
                    products.push({
                        name: cells[0].textContent.trim(),
                        quantity: parseInt(cells[1].textContent.trim()),
                        price: parseFloat(cells[2].textContent.replace(' جنيه', '').trim()),
                        total: parseFloat(cells[3].textContent.replace(' جنيه', '').trim())
                    });
                }
            });
            
            if (products.length > 0) {
                console.log(`تم استخراج ${products.length} منتج من جدول الإيصال`);
                return products;
            }
        }
        
        // إذا لم يتم العثور على منتجات في جدول الإيصال، نحاول من سلة التسوق
        const cartItems = document.querySelectorAll('.cart-item');
        if (cartItems && cartItems.length > 0) {
            console.log("استخراج المنتجات من سلة التسوق...");
            
            cartItems.forEach(item => {
                // استخراج اسم المنتج
                const nameElement = item.querySelector('.cart-item-name');
                const name = nameElement ? nameElement.textContent.trim() : '';
                
                // استخراج السعر
                const priceElement = item.querySelector('.cart-item-price');
                const priceText = priceElement ? priceElement.textContent.trim() : '0';
                const price = parseFloat(priceText.replace(' جنيه', ''));
                
                // استخراج الكمية
                const quantityInput = item.querySelector('.quantity-input');
                const quantity = quantityInput ? parseInt(quantityInput.value) : 1;
                
                // حساب الإجمالي
                const total = price * quantity;
                
                // إضافة المنتج إلى المصفوفة
                if (name && price > 0) {
                    products.push({
                        name: name,
                        quantity: quantity,
                        price: price,
                        total: total
                    });
                }
            });
            
            if (products.length > 0) {
                console.log(`تم استخراج ${products.length} منتج من سلة التسوق`);
                return products;
            }
        }
        
        // إذا وصلنا إلى هنا، فلم يتم العثور على أي منتجات
        console.warn("لم يتم العثور على منتجات في الإيصال أو السلة");
        return [];
        
    } catch (error) {
        console.error("خطأ في استخراج المنتجات:", error);
        return [];
    }
} 