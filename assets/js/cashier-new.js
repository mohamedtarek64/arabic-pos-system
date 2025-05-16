

document.addEventListener('DOMContentLoaded', function() {
    // المتغيرات العامة
    let cart = [];
    let selectedCustomer = null;
    let selectedPaymentMethod = 'cash';
    let discountValue = 0;
    let discountType = 'percentage';
    
    // الوظائف الرئيسية عند تحميل الصفحة
    initializeEventListeners();
    setupBarcodeScanner();
    setupCustomerSearch();
    setupPaymentMethods();
    setupModals();
    
    
    function initializeEventListeners() {
        // البحث عن المنتجات
        const productSearch = document.getElementById('productSearch');
        if (productSearch) {
            productSearch.addEventListener('input', filterProducts);
        }
        
        // أزرار التصنيفات
        const categoryButtons = document.querySelectorAll('.category-btn');
        categoryButtons.forEach(button => {
            button.addEventListener('click', function() {
                categoryButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                filterProductsByCategory(this.getAttribute('data-category'));
            });
        });
        
        // زر الباركود
        const barcodeBtn = document.getElementById('barcodeBtn');
        if (barcodeBtn) {
            barcodeBtn.addEventListener('click', openBarcodeScanner);
        }
        
        // زر إضافة عميل جديد
        const addCustomerBtn = document.getElementById('addCustomerBtn');
        if (addCustomerBtn) {
            addCustomerBtn.addEventListener('click', function() {
                openModal('addCustomerModal');
            });
        }
        
        // زر حفظ بيانات العميل الجديد
        const saveCustomerBtn = document.getElementById('saveCustomerBtn');
        if (saveCustomerBtn) {
            saveCustomerBtn.addEventListener('click', saveNewCustomer);
        }
        
        // زر إضافة منتج من الباركود
        const addBarcodeItemBtn = document.getElementById('addBarcodeItemBtn');
        if (addBarcodeItemBtn) {
            addBarcodeItemBtn.addEventListener('click', addProductFromBarcode);
        }
        
        // تفاعل أزرار طرق الدفع
        const paymentMethods = document.querySelectorAll('.payment-method');
        paymentMethods.forEach(method => {
            method.addEventListener('click', function() {
                paymentMethods.forEach(m => m.classList.remove('active'));
                this.classList.add('active');
                selectedPaymentMethod = this.getAttribute('data-method');
            });
        });
        
        // زر إتمام البيع
        const checkoutBtn = document.getElementById('checkoutBtn');
        if (checkoutBtn) {
            checkoutBtn.addEventListener('click', function() {
                if (cart.length === 0) {
                    alert('السلة فارغة، يرجى إضافة منتجات للسلة أولاً');
                    return;
                }
                prepareCheckoutModal();
                openModal('checkoutModal');
            });
        }
        
        // زر تعليق الطلب
        const holdOrderBtn = document.getElementById('holdOrderBtn');
        if (holdOrderBtn) {
            holdOrderBtn.addEventListener('click', holdOrder);
        }
        
        // زر تفريغ السلة
        const clearCartBtn = document.getElementById('clearCartBtn');
        if (clearCartBtn) {
            clearCartBtn.addEventListener('click', clearCart);
        }
        
        // إضافة مستمع للمنتجات المعروضة
        addProductClickListeners();
        
        // حقول الخصم
        const discountValueInput = document.getElementById('discountValue');
        const discountTypeSelect = document.getElementById('discountType');
        if (discountValueInput && discountTypeSelect) {
            discountValueInput.addEventListener('input', updateCartTotal);
            discountTypeSelect.addEventListener('change', updateCartTotal);
        }
        
        // المبلغ المدفوع والباقي
        const amountPaid = document.getElementById('amountPaid');
        if (amountPaid) {
            amountPaid.addEventListener('input', calculateChange);
        }
        
        // زر إتمام الدفع
        const completePaymentBtn = document.getElementById('completePaymentBtn');
        if (completePaymentBtn) {
            completePaymentBtn.addEventListener('click', completePayment);
        }
        
        // أزرار التعامل مع الإيصال
        const printReceiptBtn = document.getElementById('printReceiptBtn');
        if (printReceiptBtn) {
            printReceiptBtn.addEventListener('click', printReceipt);
        }
        
        // أزرار العمليات الأخيرة
        setupRecentTransactionsButtons();
    }
    
    
    function setupBarcodeScanner() {
        // سيتم تهيئة قارئ الباركود عند فتح المودال
        const manualBarcode = document.getElementById('manualBarcode');
        if (manualBarcode) {
            manualBarcode.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    addProductFromBarcode();
                    e.preventDefault();
                }
            });
        }
    }
    
    
    function filterProducts() {
        const searchTerm = document.getElementById('productSearch').value.toLowerCase();
        const productCards = document.querySelectorAll('.product-card');
        
        productCards.forEach(card => {
            const productName = card.getAttribute('data-name').toLowerCase();
            const productCode = card.getAttribute('data-code').toLowerCase();
            
            if (productName.includes(searchTerm) || productCode.includes(searchTerm)) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }
    
    
    function filterProductsByCategory(category) {
        const productCards = document.querySelectorAll('.product-card');
        
        productCards.forEach(card => {
            if (category === 'all' || card.getAttribute('data-category') === category) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }
    
    
    function addProductClickListeners() {
        const productCards = document.querySelectorAll('.product-card');
        productCards.forEach(card => {
            card.addEventListener('click', function() {
                addToCart(this);
            });
        });
    }
    
    
    function openBarcodeScanner() {
        openModal('barcodeModal');
        
        // تهيئة قارئ الباركود في المودال
        const html5QrCode = new Html5Qrcode("barcode-scanner");
        const qrConfig = { fps: 10, qrbox: { width: 250, height: 250 } };
        
        html5QrCode.start({ facingMode: "environment" }, qrConfig, onScanSuccess, onScanFailure)
            .catch(err => {
                console.error("Error starting barcode scanner:", err);
            });
            
        // تخزين مرجع لقارئ الباركود للاستخدام لاحقاً
        window.currentBarcodeScanner = html5QrCode;
    }
    
    
    function onScanSuccess(decodedText, decodedResult) {
        // وضع الباركود في حقل الإدخال اليدوي
        const manualBarcode = document.getElementById('manualBarcode');
        if (manualBarcode) {
            manualBarcode.value = decodedText;
            
            // إيقاف قارئ الباركود بعد النجاح
            if (window.currentBarcodeScanner) {
                window.currentBarcodeScanner.stop();
            }
            
            // إضافة المنتج تلقائياً
            setTimeout(addProductFromBarcode, 1000);
        }
    }
    
    
    function onScanFailure(error) {
        // لا نريد عرض أخطاء للمستخدم، فقط سجلها
        console.log(`Scan error: ${error}`);
    }
    
    
    function addProductFromBarcode() {
        const barcodeValue = document.getElementById('manualBarcode').value.trim();
        
        if (!barcodeValue) {
            alert('يرجى إدخال باركود المنتج');
            return;
        }
        
        // البحث عن المنتج في قاعدة البيانات
        const product = dbProducts.find(p => p.code === barcodeValue);
        
        if (product) {
            // إغلاق مودال الباركود
            closeModal('barcodeModal');
            
            // إضافة المنتج للسلة
            addToCart({
                getAttribute: (attr) => {
                    const map = {
                        'data-id': product.id,
                        'data-name': product.name,
                        'data-price': product.price,
                        'data-code': product.code,
                        'data-stock': product.quantity
                    };
                    return map[attr];
                }
            });
            
            // مسح حقل الباركود
            document.getElementById('manualBarcode').value = '';
            
            // إيقاف قارئ الباركود إذا كان مفعلاً
            if (window.currentBarcodeScanner) {
                window.currentBarcodeScanner.stop();
            }
        } else {
            alert('لم يتم العثور على منتج بهذا الباركود');
        }
    }
    
    
    function setupCustomerSearch() {
        const customerSearch = document.getElementById('customerSearch');
        const customerResults = document.getElementById('customerResults');
        
        if (customerSearch && customerResults) {
            customerSearch.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase().trim();
                
                if (searchTerm.length < 2) {
                    customerResults.style.display = 'none';
                    return;
                }
                
                const filteredCustomers = dbCustomers.filter(customer => 
                    customer.name.toLowerCase().includes(searchTerm) || 
                    (customer.phone && customer.phone.includes(searchTerm))
                );
                
                displayCustomerResults(filteredCustomers);
            });
            
            customerSearch.addEventListener('focus', function() {
                if (this.value.length >= 2) {
                    customerResults.style.display = 'block';
                }
            });
            
            document.addEventListener('click', function(e) {
                if (!customerSearch.contains(e.target) && !customerResults.contains(e.target)) {
                    customerResults.style.display = 'none';
                }
            });
        }
    }
    
    
    function displayCustomerResults(customers) {
        const customerResults = document.getElementById('customerResults');
        
        if (!customerResults) return;
        
        customerResults.innerHTML = '';
        
        if (customers.length === 0) {
            customerResults.innerHTML = '<div class="no-results">لا توجد نتائج مطابقة</div>';
            customerResults.style.display = 'block';
            return;
        }
        
        customers.forEach(customer => {
            const customerItem = document.createElement('div');
            customerItem.className = 'customer-result-item';
            customerItem.innerHTML = `
                <div class="customer-name">${customer.name}</div>
                <div class="customer-phone">${customer.phone || ''}</div>
            `;
            
            customerItem.addEventListener('click', function() {
                selectCustomer(customer);
                document.getElementById('customerResults').style.display = 'none';
                document.getElementById('customerSearch').value = '';
            });
            
            customerResults.appendChild(customerItem);
        });
        
        customerResults.style.display = 'block';
    }
    
    
    function selectCustomer(customer) {
        selectedCustomer = customer;
        
        const selectedCustomerElem = document.getElementById('selectedCustomer');
        if (selectedCustomerElem) {
            selectedCustomerElem.innerHTML = `
                <div class="customer-info">
                    <div class="customer-info-header">
                        <div class="customer-info-name">${customer.name}</div>
                        <button class="remove-customer" id="removeCustomerBtn">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="customer-info-details">
                        ${customer.phone ? `<div>${customer.phone}</div>` : ''}
                        ${customer.email ? `<div>${customer.email}</div>` : ''}
                    </div>
                </div>
            `;
            
            // إضافة حدث لزر إزالة العميل
            const removeCustomerBtn = document.getElementById('removeCustomerBtn');
            if (removeCustomerBtn) {
                removeCustomerBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    removeSelectedCustomer();
                });
            }
        }
    }
    
    
    function removeSelectedCustomer() {
        selectedCustomer = null;
        
        const selectedCustomerElem = document.getElementById('selectedCustomer');
        if (selectedCustomerElem) {
            selectedCustomerElem.innerHTML = `
                <div class="no-customer-selected">
                    <i class="fas fa-user-plus"></i>
                    <span>لم يتم اختيار عميل</span>
                </div>
            `;
        }
    }
    
    
    function saveNewCustomer() {
        const customerName = document.getElementById('customerName').value.trim();
        const customerPhone = document.getElementById('customerPhone').value.trim();
        const customerEmail = document.getElementById('customerEmail').value.trim();
        const customerAddress = document.getElementById('customerAddress').value.trim();
        
        if (!customerName) {
            alert('يرجى إدخال اسم العميل');
            return;
        }
        
        // إنشاء كائن العميل
        const newCustomer = {
            id: Date.now(), // قيمة مؤقتة للعرض فقط
            name: customerName,
            phone: customerPhone,
            email: customerEmail,
            address: customerAddress
        };
        
        // في التطبيق الحقيقي، ستقوم بإرسال هذه البيانات إلى قاعدة البيانات
        // ولكن للآن سنضيفه فقط إلى مصفوفة العملاء المحلية
        dbCustomers.push(newCustomer);
        
        // اختيار العميل الجديد
        selectCustomer(newCustomer);
        
        // إعادة تعيين النموذج
        document.getElementById('customerName').value = '';
        document.getElementById('customerPhone').value = '';
        document.getElementById('customerEmail').value = '';
        document.getElementById('customerAddress').value = '';
        
        // إغلاق المودال
        closeModal('addCustomerModal');
        
        // رسالة نجاح
        alert('تم إضافة العميل بنجاح!');
    }
    
    
    function addToCart(productElement) {
        const id = productElement.getAttribute('data-id');
        const name = productElement.getAttribute('data-name');
        const price = parseFloat(productElement.getAttribute('data-price'));
        const code = productElement.getAttribute('data-code');
        const availableStock = parseInt(productElement.getAttribute('data-stock') || 0);
        
        // التحقق إذا كان المنتج موجود في السلة
        const existingItem = cart.find(item => item.id === id);
        
        if (existingItem) {
            // التحقق من المخزون قبل زيادة الكمية
            if (existingItem.quantity + 1 > availableStock) {
                alert('عذراً، الكمية المطلوبة تتجاوز المخزون المتاح');
                return;
            }
            
            existingItem.quantity += 1;
            existingItem.total = existingItem.quantity * existingItem.price;
        } else {
            // التحقق من وجود المنتج في المخزون
            if (availableStock <= 0) {
                alert('عذراً، هذا المنتج غير متوفر في المخزون');
                return;
            }
            
            // إضافة منتج جديد للسلة
            cart.push({
                id: id,
                name: name,
                price: price,
                code: code,
                quantity: 1,
                total: price
            });
        }
        
        // تحديث عرض السلة
        updateCartDisplay();
    }
    
    
    function updateCartDisplay() {
        const cartItems = document.getElementById('cartItems');
        
        if (!cartItems) return;
        
        if (cart.length === 0) {
            cartItems.innerHTML = `
                <div class="empty-cart">
                    <i class="fas fa-shopping-basket"></i>
                    <p>السلة فارغة</p>
                </div>
            `;
            return;
        }
        
        let cartHTML = '';
        
        cart.forEach((item, index) => {
            cartHTML += `
                <div class="cart-item">
                    <button class="remove-item" onclick="removeCartItem(${index})">
                        <i class="fas fa-times"></i>
                    </button>
                    <div class="cart-item-details">
                        <div class="cart-item-name">${item.name}</div>
                        <div class="cart-item-price">${item.price.toFixed(2)} جنيه</div>
                    </div>
                    <div class="cart-item-quantity">
                        <button class="quantity-btn" onclick="decreaseQuantity(${index})">-</button>
                        <input type="text" class="quantity-input" value="${item.quantity}" readonly>
                        <button class="quantity-btn" onclick="increaseQuantity(${index})">+</button>
                    </div>
                    <div class="cart-item-total">${item.total.toFixed(2)} جنيه</div>
                </div>
            `;
        });
        
        cartItems.innerHTML = cartHTML;
        
        // تحديث الإجمالي
        updateCartTotal();
    }
    
    
    window.increaseQuantity = function(index) {
        const item = cart[index];
        
        // البحث عن المنتج في قاعدة البيانات للتحقق من المخزون
        const product = dbProducts.find(p => p.id === item.id);
        
        if (product && product.quantity && item.quantity + 1 > product.quantity) {
            alert('عذراً، الكمية المطلوبة تتجاوز المخزون المتاح');
            return;
        }
        
        item.quantity += 1;
        item.total = item.quantity * item.price;
        
        updateCartDisplay();
    }
    
    
    window.decreaseQuantity = function(index) {
        const item = cart[index];
        
        if (item.quantity > 1) {
            item.quantity -= 1;
            item.total = item.quantity * item.price;
            
            updateCartDisplay();
        }
    }
    
    
    window.removeCartItem = function(index) {
        cart.splice(index, 1);
        updateCartDisplay();
    }
    
    
    function updateCartTotal() {
        // حساب مجموع السلة
        const subtotal = cart.reduce((sum, item) => sum + item.total, 0);
        
        // الحصول على قيمة الخصم ونوعه
        discountValue = parseFloat(document.getElementById('discountValue').value || 0);
        discountType = document.getElementById('discountType').value;
        
        // حساب الخصم
        let discount = 0;
        if (discountType === 'percentage') {
            discount = subtotal * (discountValue / 100);
        } else { // fixed
            discount = discountValue > subtotal ? subtotal : discountValue;
        }
        
        // حساب الإجمالي بعد الخصم
        const total = subtotal - discount;
        
        // تحديث العناصر في الواجهة
        document.getElementById('subtotal').textContent = subtotal.toFixed(2) + ' جنيه';
        document.getElementById('total').textContent = total.toFixed(2) + ' جنيه';
        
        return { subtotal, discount, total };
    }
    
    
    function clearCart() {
        if (cart.length === 0) return;
        
        if (confirm('هل أنت متأكد من رغبتك في تفريغ السلة؟')) {
            cart = [];
            updateCartDisplay();
        }
    }
}); 