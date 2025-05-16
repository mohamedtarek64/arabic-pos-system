function setupPaymentMethods() {
    // عند تغيير طريقة الدفع في مودال الدفع
    const checkoutModal = document.getElementById('checkoutModal');
    if (checkoutModal) {
        // إضافة مستمع لأحداث الضغط على زر طريقة الدفع
        document.querySelectorAll('.payment-method').forEach(method => {
            method.addEventListener('click', function() {
                const paymentMethod = this.getAttribute('data-method');
                
                // إخفاء جميع تفاصيل الدفع وإظهار المحدد فقط
                document.getElementById('cashPaymentDetails').style.display = 'none';
                document.getElementById('visaPaymentDetails').style.display = 'none';
                document.getElementById('installmentPaymentDetails').style.display = 'none';
                
                if (paymentMethod === 'cash') {
                    document.getElementById('cashPaymentDetails').style.display = 'block';
                } else if (paymentMethod === 'visa') {
                    document.getElementById('visaPaymentDetails').style.display = 'block';
                } else if (paymentMethod === 'installment') {
                    document.getElementById('installmentPaymentDetails').style.display = 'block';
                    // التحقق من وجود عميل محدد
                    const noCustomerWarning = document.getElementById('noCustomerWarning');
                    if (noCustomerWarning) {
                        if (!selectedCustomer) {
                            noCustomerWarning.style.display = 'block';
                        } else {
                            noCustomerWarning.style.display = 'none';
                        }
                    }
                }
            });
        });
        
        // رابط إضافة عميل سريع في تحذير عدم وجود عميل
        const quickAddCustomerLink = document.getElementById('quickAddCustomerLink');
        if (quickAddCustomerLink) {
            quickAddCustomerLink.addEventListener('click', function(e) {
                e.preventDefault();
                closeModal('checkoutModal');
                openModal('addCustomerModal');
            });
        }
    }
}


function setupModals() {
    // إضافة مستمعي أحداث لأزرار إغلاق المودال
    const closeButtons = document.querySelectorAll('.close-modal');
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modalId = this.getAttribute('data-modal');
            closeModal(modalId);
        });
    });
    
    // إضافة مستمعي أحداث لأزرار إلغاء في الموديلز
    const cancelButtons = document.querySelectorAll('.cancel-btn');
    cancelButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modalId = this.getAttribute('data-modal');
            if (modalId) {
                closeModal(modalId);
            }
        });
    });
}


function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'flex';
        
        // إيقاف قارئ الباركود عند إغلاق مودال الباركود
        if (modalId === 'barcodeModal' && window.currentBarcodeScanner) {
            setTimeout(() => {
                if (!document.getElementById('barcodeModal').style.display === 'none') {
                    try {
                        window.currentBarcodeScanner.stop();
                    } catch (error) {
                        console.error('Error stopping barcode scanner:', error);
                    }
                }
            }, 500);
        }
    }
}


function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        
        // إيقاف قارئ الباركود عند إغلاق مودال الباركود
        if (modalId === 'barcodeModal' && window.currentBarcodeScanner) {
            try {
                window.currentBarcodeScanner.stop();
            } catch (error) {
                console.error('Error stopping barcode scanner:', error);
            }
        }
    }
}


function prepareCheckoutModal() {
    const totals = updateCartTotal();
    
    // تحديث ملخص الطلب في المودال
    document.getElementById('checkoutSubtotal').textContent = totals.subtotal.toFixed(2) + ' جنيه';
    document.getElementById('checkoutDiscount').textContent = totals.discount.toFixed(2) + ' جنيه';
    document.getElementById('checkoutTotal').textContent = totals.total.toFixed(2) + ' جنيه';
    
    // عرض قسم الدفع المناسب حسب طريقة الدفع المحددة
    document.getElementById('cashPaymentDetails').style.display = 'none';
    document.getElementById('visaPaymentDetails').style.display = 'none';
    document.getElementById('installmentPaymentDetails').style.display = 'none';
    
    if (selectedPaymentMethod === 'cash') {
        document.getElementById('cashPaymentDetails').style.display = 'block';
        
        // تعيين المبلغ المدفوع بالإجمالي تلقائياً
        document.getElementById('amountPaid').value = totals.total.toFixed(2);
        document.getElementById('changeAmount').value = '0.00 جنيه';
    } else if (selectedPaymentMethod === 'visa') {
        document.getElementById('visaPaymentDetails').style.display = 'block';
    } else if (selectedPaymentMethod === 'installment') {
        document.getElementById('installmentPaymentDetails').style.display = 'block';
        
        // تحديد العميل تلقائياً في حالة اختيار عميل بالفعل
        if (selectedCustomer) {
            const installmentCustomerSelect = document.getElementById('installmentCustomer');
            const customerOption = Array.from(installmentCustomerSelect.options).find(option => 
                option.value === selectedCustomer.id.toString()
            );
            
            if (customerOption) {
                customerOption.selected = true;
                document.getElementById('noCustomerWarning').style.display = 'none';
            }
        } else {
            document.getElementById('noCustomerWarning').style.display = 'block';
        }
        
        // تعيين تاريخ افتراضي لأول قسط
        const today = new Date();
        const nextMonth = new Date(today);
        nextMonth.setMonth(today.getMonth() + 1);
        
        const dateInput = document.getElementById('firstPaymentDate');
        if (dateInput) {
            dateInput.valueAsDate = nextMonth;
        }
    }
}


function calculateChange() {
    const totals = updateCartTotal();
    const amountPaid = parseFloat(document.getElementById('amountPaid').value || 0);
    const change = amountPaid - totals.total;
    
    document.getElementById('changeAmount').value = change.toFixed(2) + ' جنيه';
}


function completePayment() {
    // التحقق إذا كانت السلة فارغة
    if (cart.length === 0) {
        alert('لا يمكن إتمام عملية بيع بسلة فارغة');
        return;
    }
    
    // تجهيز بيانات البيع حسب طريقة الدفع
    const totals = updateCartTotal();
    let paymentData = {
        items: JSON.parse(JSON.stringify(cart)), // نسخة من السلة
        subtotal: totals.subtotal,
        discount: totals.discount,
        total: totals.total,
        paymentMethod: selectedPaymentMethod,
        customer: selectedCustomer,
        date: new Date()
    };
    
    // تحقق إضافي حسب طريقة الدفع
    if (selectedPaymentMethod === 'cash') {
        const amountPaid = parseFloat(document.getElementById('amountPaid').value || 0);
        if (amountPaid < totals.total) {
            alert('المبلغ المدفوع أقل من إجمالي الفاتورة');
            return;
        }
        
        paymentData.amountPaid = amountPaid;
        paymentData.change = amountPaid - totals.total;
    } 
    else if (selectedPaymentMethod === 'visa') {
        const cardType = document.getElementById('cardType').value;
        const cardNumber = document.getElementById('cardNumber').value;
        const transactionRef = document.getElementById('transactionRef').value;
        
        if (!cardNumber || !transactionRef) {
            alert('يرجى إدخال تفاصيل بطاقة الائتمان والرقم المرجعي');
            return;
        }
        
        paymentData.cardDetails = {
            cardType: cardType,
            lastFourDigits: cardNumber,
            transactionRef: transactionRef
        };
    } 
    else if (selectedPaymentMethod === 'installment') {
        let installmentCustomer = null;
        
        if (selectedCustomer) {
            // استخدام العميل المحدد بالفعل
            installmentCustomer = selectedCustomer;
        } else {
            // إذا كان المستخدم قد اختار عميل من قائمة التقسيط في المودال
            const installmentCustomerId = document.getElementById('installmentCustomer').value;
            if (!installmentCustomerId) {
            alert('يجب اختيار عميل للتقسيط');
            return;
        }
        
            // البحث عن العميل في قائمة العملاء
            installmentCustomer = dbCustomers.find(c => c.id == installmentCustomerId);
            if (!installmentCustomer) {
                alert('العميل المحدد غير موجود');
            return;
        }
        }
        
        // إضافة المبلغ إلى حساب العميل في نظام التقسيط
        addAmountToCustomerInstallment(installmentCustomer.id, totals.total, cart);
        
        // تحديث بيانات الدفع
        paymentData.installmentDetails = {
            customerId: installmentCustomer.id,
            customerName: installmentCustomer.name
        };
    }
    
    // هنا سنفترض أن عملية الدفع تمت بنجاح
    // في التطبيق الحقيقي ستقوم بإرسال البيانات للخادم وحفظها
    console.log('Payment completed with data:', paymentData);
    
    // إغلاق مودال الدفع
    closeModal('checkoutModal');
    
    // إعداد وعرض الإيصال
    prepareReceipt(paymentData);
    openModal('receiptModal');
    
    // تفريغ السلة بعد إتمام عملية البيع
    cart = [];
    updateCartDisplay();
    
    // إزالة العميل المحدد
    removeSelectedCustomer();
    
    // رسالة نجاح
    if (selectedPaymentMethod === 'installment') {
        alert('تم إتمام عملية البيع بنجاح وإضافة المبلغ لحساب العميل في نظام التقسيط!');
    } else {
    alert('تم إتمام عملية البيع بنجاح!');
    }
}


function prepareReceipt(paymentData) {
    // معلومات الإيصال الأساسية
    const receiptDate = new Date();
    const receiptNumber = generateUniqueInvoiceNumber();
    
    // تعيين التاريخ والوقت ورقم الإيصال
    document.getElementById('receiptDate').textContent = formatDate(receiptDate);
    document.getElementById('receiptTime').textContent = formatTime(receiptDate);
    document.getElementById('receiptNumber').textContent = receiptNumber;
    
    // تعيين اسم العميل
    const selectedCustomer = document.querySelector('.customer-info-name');
    document.getElementById('receiptCustomer').textContent = selectedCustomer ? selectedCustomer.textContent : 'عميل نقدي';
    
    // تحضير قائمة المنتجات - الجزء المُعدّل
    const receiptItemsList = document.getElementById('receiptItemsList');
    if (!receiptItemsList) {
        console.error('عنصر جدول المنتجات غير موجود!');
        return;
    }
    
    // حذف أي منتجات سابقة
    receiptItemsList.innerHTML = '';
    
    // الحصول على المنتجات من السلة
    if (!window.cart || window.cart.length === 0) {
        console.warn('السلة فارغة!');
    } else {
        // إضافة المنتجات إلى الجدول
        window.cart.forEach(item => {
            const itemTotal = item.price * item.quantity;
            
            // إنشاء صف جديد للمنتج
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${item.name}</td>
            <td>${item.quantity}</td>
            <td>${item.price.toFixed(2)} جنيه</td>
                <td>${itemTotal.toFixed(2)} جنيه</td>
        `;
            
            // إضافة الصف إلى الجدول
        receiptItemsList.appendChild(row);
            console.log(`تمت إضافة المنتج ${item.name} إلى الإيصال`);
        });
    }
    
    // استخراج القيم المحسوبة
    const subtotal = calculateSubtotal();
    const discount = calculateDiscount(subtotal);
    const total = Math.max(0, subtotal - discount);
    
    // تعيين ملخص القيم
    document.getElementById('receiptSubtotal').textContent = formatPrice(subtotal) + ' جنيه';
    document.getElementById('receiptDiscount').textContent = formatPrice(discount) + ' جنيه';
    document.getElementById('receiptTotal').textContent = formatPrice(total) + ' جنيه';
}


function printReceipt() {
    const receiptContainer = document.getElementById('receipt');
    
    // استخدام jsPDF لطباعة الإيصال
    if (typeof window.jspdf !== 'undefined') {
        const { jsPDF } = window.jspdf;
        
        // استخدام html2canvas لتحويل الإيصال لصورة
        html2canvas(receiptContainer).then(canvas => {
            const imgData = canvas.toDataURL('image/png');
            const pdf = new jsPDF('p', 'mm', 'a4');
            const pdfWidth = pdf.internal.pageSize.getWidth();
            const pdfHeight = pdf.internal.pageSize.getHeight();
            const imgWidth = canvas.width;
            const imgHeight = canvas.height;
            const ratio = Math.min(pdfWidth / imgWidth, pdfHeight / imgHeight);
            const imgX = (pdfWidth - imgWidth * ratio) / 2;
            const imgY = 30;
            
            pdf.addImage(imgData, 'PNG', imgX, imgY, imgWidth * ratio, imgHeight * ratio);
            pdf.save('receipt.pdf');
        });
    } else {
        // طريقة احتياطية للطباعة باستخدام واجهة الطباعة المتصفح
        const printWindow = window.open('', '_blank');
        printWindow.document.write('<html><head><title>إيصال</title>');
        printWindow.document.write('<link rel="stylesheet" href="cashier.css">');
        printWindow.document.write('<style>body { font-family: Arial, sans-serif; direction: rtl; }</style>');
        printWindow.document.write('</head><body>');
        printWindow.document.write(receiptContainer.innerHTML);
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        
        setTimeout(() => {
            printWindow.print();
            printWindow.close();
        }, 500);
    }
}


function holdOrder() {
    if (cart.length === 0) {
        alert('لا يمكن تعليق طلب بسلة فارغة');
        return;
    }
    
    // في التطبيق الحقيقي ستقوم بحفظ بيانات الطلب في قاعدة البيانات
    // هنا سنقوم فقط بإظهار رسالة
    alert('تم تعليق الطلب بنجاح. يمكنك استرجاعه لاحقًا من قائمة الطلبات المعلقة.');
    
    // تفريغ السلة بعد تعليق الطلب
    cart = [];
    updateCartDisplay();
    
    // إزالة العميل المحدد
    removeSelectedCustomer();
}


function setupRecentTransactionsButtons() {
    const transactionActions = document.querySelectorAll('.transaction-action');
    
    transactionActions.forEach(action => {
        action.addEventListener('click', function(e) {
            e.stopPropagation();
            const actionType = this.getAttribute('data-action');
            const transactionItem = this.closest('.transaction-item');
            const transactionId = transactionItem.getAttribute('data-id');
            
            // معالجة نوع الإجراء
            if (actionType === 'view') {
                viewTransactionDetails(transactionId);
            } else if (actionType === 'print') {
                printTransactionReceipt(transactionId);
            } else if (actionType === 'receipt') {
                sendTransactionReceipt(transactionId);
            }
        });
    });
    
    // مستمع النقر على عنصر العملية
    const transactionItems = document.querySelectorAll('.transaction-item');
    transactionItems.forEach(item => {
        item.addEventListener('click', function() {
            const transactionId = this.getAttribute('data-id');
            viewTransactionDetails(transactionId);
        });
    });
}


function viewTransactionDetails(transactionId) {
    console.log('عرض تفاصيل العملية:', transactionId);
    
    // البحث عن العملية في التخزين المحلي
    const savedSales = JSON.parse(localStorage.getItem('recent_sales')) || [];
    const transaction = savedSales.find(sale => sale.invoice_number === transactionId);
    
    if (!transaction) {
        showNotification('لم يتم العثور على تفاصيل العملية', 'error');
        return;
    }
    
    // تجهيز بيانات الإيصال
    setReceiptData(transaction);
    
    // فتح مودال الإيصال
    const receiptModal = document.getElementById('receiptModal');
    if (receiptModal) {
        receiptModal.style.display = 'flex';
    }
}


function printTransactionReceipt(transactionId) {
    console.log('طباعة إيصال العملية:', transactionId);
    
    // البحث عن العملية في التخزين المحلي
    const savedSales = JSON.parse(localStorage.getItem('recent_sales')) || [];
    const transaction = savedSales.find(sale => sale.invoice_number === transactionId);
    
    if (!transaction) {
        showNotification('لم يتم العثور على تفاصيل العملية', 'error');
        return;
    }
    
    // تجهيز بيانات الإيصال
    setReceiptData(transaction);
    
    // طباعة الإيصال
    setTimeout(() => {
        window.print();
    }, 500);
}


function sendTransactionReceipt(transactionId) {
    console.log('إرسال إيصال العملية:', transactionId);
    
    // البحث عن العملية في التخزين المحلي
    const savedSales = JSON.parse(localStorage.getItem('recent_sales')) || [];
    const transaction = savedSales.find(sale => sale.invoice_number === transactionId);
    
    if (!transaction) {
        showNotification('لم يتم العثور على تفاصيل العملية', 'error');
        return;
    }
    
    // إظهار خيارات الإرسال
    const options = [
        { id: 'email', name: 'بريد إلكتروني', icon: 'fa-envelope' },
        { id: 'whatsapp', name: 'واتساب', icon: 'fa-whatsapp' },
        { id: 'pdf', name: 'تحميل PDF', icon: 'fa-file-pdf' }
    ];
    
    // إنشاء قائمة بخيارات الإرسال
    let optionsHTML = options.map(option => 
        `<div class="send-option" data-option="${option.id}" data-invoice="${transactionId}">
            <i class="fas ${option.icon}"></i>
            <span>${option.name}</span>
        </div>`
    ).join('');
    
    // إنشاء مودال خيارات الإرسال
    const modalHTML = `
        <div class="modal" id="sendOptionsModal">
            <div class="modal-content small-modal">
                <span class="close-modal">&times;</span>
                <h3 class="modal-title">خيارات إرسال الإيصال</h3>
                <div class="send-options-container">
                    ${optionsHTML}
                </div>
            </div>
        </div>
    `;
    
    // إضافة المودال للصفحة إذا لم يكن موجودًا
    if (!document.getElementById('sendOptionsModal')) {
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        // إضافة أحداث للخيارات
        document.querySelectorAll('.send-option').forEach(option => {
            option.addEventListener('click', function() {
                const optionType = this.getAttribute('data-option');
                const invoiceId = this.getAttribute('data-invoice');
                
                document.getElementById('sendOptionsModal').style.display = 'none';
                
                if (optionType === 'email') {
                    sendReceiptByEmail(invoiceId);
                } else if (optionType === 'whatsapp') {
                    sendReceiptByWhatsApp(invoiceId);
                } else if (optionType === 'pdf') {
                    downloadAsPDF(invoiceId);
                }
            });
        });
        
        // إضافة حدث إغلاق المودال
        document.querySelector('#sendOptionsModal .close-modal').addEventListener('click', function() {
            document.getElementById('sendOptionsModal').style.display = 'none';
        });
    } else {
        // تحديث إرجاع مرجع الفاتورة للأزرار الموجودة
        document.querySelectorAll('.send-option').forEach(option => {
            option.setAttribute('data-invoice', transactionId);
        });
    }
    
    // إظهار المودال
    document.getElementById('sendOptionsModal').style.display = 'flex';
}

// تعيين بيانات الإيصال
function setReceiptData(transaction) {
    // تعيين رقم الفاتورة والتاريخ والوقت والعميل
    document.getElementById('receiptNumber').textContent = transaction.invoice_number;
    document.getElementById('receiptDate').textContent = transaction.date || formatDate(new Date());
    
    // تحديد الوقت
    let receiptTime = '';
    if (transaction.timestamp) {
        const date = new Date(transaction.timestamp);
        receiptTime = formatTime(date);
    } else {
        receiptTime = formatTime(new Date());
    }
    document.getElementById('receiptTime').textContent = receiptTime;
    
    // تعيين العميل
    document.getElementById('receiptCustomer').textContent = transaction.customer || 'عميل نقدي';
    
    // تعيين طريقة الدفع
    document.getElementById('receiptPaymentMethod').textContent = transaction.payment_method || 'نقداً';
    
    // تفريغ قائمة المنتجات
    const receiptItemsList = document.getElementById('receiptItemsList');
    if (receiptItemsList) {
        receiptItemsList.innerHTML = '';
        
        // إضافة المنتجات
        if (transaction.items && transaction.items.length > 0) {
            transaction.items.forEach(item => {
                const itemTotal = item.price * item.quantity;
                
                // إنشاء صف جديد للمنتج
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${item.name}</td>
                    <td>${item.quantity}</td>
                    <td>${parseFloat(item.price).toFixed(2)} جنيه</td>
                    <td>${itemTotal.toFixed(2)} جنيه</td>
                `;
                
                // إضافة الصف للجدول
                receiptItemsList.appendChild(row);
            });
        } else {
            // إذا لم تكن هناك منتجات، نضيف صف رسالة
            const row = document.createElement('tr');
            row.innerHTML = `<td colspan="4" class="no-items-message">لا توجد منتجات</td>`;
            receiptItemsList.appendChild(row);
        }
    }
    
    // تعيين إجماليات الإيصال
    const subtotal = transaction.subtotal || transaction.total || 0;
    const discount = transaction.discount || 0;
    const total = transaction.total || subtotal;
    
    document.getElementById('receiptSubtotal').textContent = parseFloat(subtotal).toFixed(2) + ' جنيه';
    document.getElementById('receiptDiscount').textContent = parseFloat(discount).toFixed(2) + ' جنيه';
    document.getElementById('receiptTotal').textContent = parseFloat(total).toFixed(2) + ' جنيه';
}

// إنشاء رقم فاتورة فريد
function generateUniqueInvoiceNumber() {
    // إنشاء timestamp
    const timestamp = new Date().getTime();
    
    // إنشاء رقم عشوائي من 3 أرقام
    const randomNum = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
    
    // تنسيق التاريخ كسنة-شهر-يوم
    const today = new Date();
    const datePart = `${today.getFullYear()}${(today.getMonth() + 1).toString().padStart(2, '0')}${today.getDate().toString().padStart(2, '0')}`;
    
    // تجميع الفاتورة بتنسيق INV-YYYYMMDD-RANDOM-TIMESTAMP
    return `INV-${datePart}-${randomNum}-${timestamp.toString().slice(-4)}`;
}

// تنسيق التاريخ
function formatDate(date) {
    return date.toLocaleDateString('ar-EG', { year: 'numeric', month: '2-digit', day: '2-digit' });
}

// تنسيق الوقت
function formatTime(date) {
    return date.toLocaleTimeString('ar-EG', { hour: '2-digit', minute: '2-digit' });
}

// تنسيق السعر
function formatPrice(price) {
    return price.toFixed(2);
}

// حساب المجموع الفرعي
function calculateSubtotal() {
    if (!window.cart || window.cart.length === 0) {
        return 0;
    }
    return window.cart.reduce((total, item) => total + (item.price * item.quantity), 0);
}

// حساب الخصم
function calculateDiscount(subtotal) {
    const discountInput = document.getElementById('discountValue');
    const discountType = document.getElementById('discountType');
    
    if (!discountInput || !discountType) {
        return 0;
    }
    
    const value = parseFloat(discountInput.value) || 0;
    
    if (discountType.value === 'percentage') {
        return subtotal * (value / 100);
    } else {
        return value;
    }
}

// إرسال الإيصال عبر البريد الإلكتروني
function sendReceiptByEmail(invoiceId) {
    // في التطبيق الحقيقي، سيتم إرسال الإيصال عبر البريد الإلكتروني
    alert(`سيتم إرسال إيصال العملية رقم ${invoiceId} عبر البريد الإلكتروني`);
}

// إرسال الإيصال عبر واتساب
function sendReceiptByWhatsApp(invoiceId) {
    // في التطبيق الحقيقي، سيتم إرسال الإيصال عبر واتساب
    alert(`سيتم إرسال إيصال العملية رقم ${invoiceId} عبر واتساب`);
}

// تحميل الإيصال كملف PDF
function downloadAsPDF(invoiceId) {
    // في التطبيق الحقيقي، سيتم تحويل الإيصال إلى PDF وتحميله
    alert(`سيتم تحميل إيصال العملية رقم ${invoiceId} كملف PDF`);
}

// إغلاق الإيصال وإتمام العملية
function closeReceiptAndComplete() {
    // التحقق من وجود منتجات في السلة
    if (!window.cart || window.cart.length === 0) {
        showNotification('لا توجد منتجات في السلة للدفع', 'error');
        return;
    }
    
    // جمع بيانات العملية
    const receiptNumber = document.getElementById('receiptNumber').textContent;
    const receiptDate = document.getElementById('receiptDate').textContent;
    const receiptCustomer = document.getElementById('receiptCustomer').textContent;
    const receiptTotal = document.getElementById('receiptTotal').textContent;
    const paymentMethod = document.getElementById('receiptPaymentMethod').textContent || 'نقداً';
    
    // تجهيز بيانات العملية لإرسالها للخادم
    const saleData = {
        invoice_number: receiptNumber,
        date: receiptDate,
        customer: receiptCustomer,
        total: parseFloat(receiptTotal.replace(' جنيه', '')),
        payment_method: paymentMethod,
        items: window.cart.map(item => ({
            id: item.id,
            name: item.name,
            price: item.price,
            quantity: item.quantity
        }))
    };
    
    // إرسال العملية للخادم (أو تخزينها محلياً)
    saveSaleToServer(saleData);
    
    // تحديث المخزون - تنقيص الكميات المباعة
    updateInventory(window.cart);
    
    // إضافة العملية إلى قائمة آخر العمليات
    addToRecentTransactions(saleData);
    
    // إغلاق النافذة
    const receiptModal = document.getElementById('receiptModal');
    if (receiptModal) {
        receiptModal.style.display = 'none';
    }
    
    // إظهار رسالة نجاح
    showNotification('تم إتمام عملية الدفع بنجاح', 'success');
    
    // تفريغ السلة
    window.cart = [];
    
    // تحديث عرض السلة (تفريغها)
    updateCartDisplay();
    
    // إعادة تعيين الخصم
    const discountInput = document.getElementById('discountValue');
    if (discountInput) {
        discountInput.value = '0';
    }
    
    // تحديث إجمالي السلة
    updateCartTotals();
    
    console.log('تم إتمام العملية بنجاح', saleData);
}

// إرسال العملية للخادم
function saveSaleToServer(saleData) {
    // في التطبيق الحقيقي، سيتم إرسال البيانات للخادم عبر Ajax
    // هنا سنقوم بتخزينها في localStorage كبديل مؤقت
    
    try {
        // الحصول على العمليات المخزنة مسبقاً
        let savedSales = JSON.parse(localStorage.getItem('recent_sales')) || [];
        
        // إضافة العملية الجديدة في البداية
        savedSales.unshift({
            ...saleData,
            timestamp: new Date().toISOString() // إضافة طابع زمني
        });
        
        // الاحتفاظ فقط بآخر 20 عملية
        if (savedSales.length > 20) {
            savedSales = savedSales.slice(0, 20);
        }
        
        // حفظ العمليات المحدثة
        localStorage.setItem('recent_sales', JSON.stringify(savedSales));
        
        console.log('تم حفظ العملية بنجاح');
        return true;
    } catch (error) {
        console.error('حدث خطأ أثناء حفظ العملية:', error);
        return false;
    }
}

// تحديث المخزون - تنقيص الكميات المباعة
function updateInventory(cartItems) {
    if (!cartItems || cartItems.length === 0) return;
    
    try {
        // الحصول على المخزون الحالي
        let inventory = JSON.parse(localStorage.getItem('inventory')) || {};
        
        // تحديث كمية كل منتج
        cartItems.forEach(item => {
            const productId = item.id;
            
            // التحقق من وجود المنتج في المخزون
            if (!inventory[productId]) {
                // إذا لم يكن موجوداً، نضيف بيانات المنتج
                inventory[productId] = {
                    id: productId,
                    name: item.name,
                    quantity: 0 // نفترض أن الكمية الأصلية 0
                };
            }
            
            // تحديث كمية المنتج (تنقيصها)
            inventory[productId].quantity -= item.quantity;
            
            // التأكد من أن الكمية لا تقل عن صفر
            if (inventory[productId].quantity < 0) {
                inventory[productId].quantity = 0;
            }
        });
        
        // حفظ المخزون المحدث
        localStorage.setItem('inventory', JSON.stringify(inventory));
        
        // تحديث عرض المنتجات في الصفحة (إظهار الكميات الجديدة)
        updateProductsDisplay(inventory);
        
        console.log('تم تحديث المخزون بنجاح');
        return true;
    } catch (error) {
        console.error('حدث خطأ أثناء تحديث المخزون:', error);
        return false;
    }
}

// تحديث عرض المنتجات في الصفحة
function updateProductsDisplay(inventory) {
    if (!inventory) return;
    
    // البحث عن جميع بطاقات المنتجات
    const productCards = document.querySelectorAll('.product-card');
    
    productCards.forEach(card => {
        const productId = card.getAttribute('data-id');
        
        // التحقق من وجود المنتج في المخزون المحدث
        if (inventory[productId]) {
            const updatedQuantity = inventory[productId].quantity;
            
            // تحديث عرض الكمية في البطاقة
            const stockElement = card.querySelector('.product-stock');
            if (stockElement) {
                stockElement.textContent = `المخزون: ${updatedQuantity}`;
                
                // إضافة تنسيق للمخزون المنخفض
                if (updatedQuantity < 5) {
                    stockElement.classList.add('low-stock');
                } else {
                    stockElement.classList.remove('low-stock');
                }
            }
            
            // تحديث سمة الكمية
            card.setAttribute('data-stock', updatedQuantity);
            
            // إضافة تنبيه المخزون المنخفض إذا لزم الأمر
            const stockBadgeContainer = card.querySelector('.product-img');
            if (stockBadgeContainer) {
                // إزالة أي شارة موجودة
                const existingBadge = stockBadgeContainer.querySelector('.stock-badge');
                if (existingBadge) {
                    stockBadgeContainer.removeChild(existingBadge);
                }
                
                // إضافة شارة جديدة إذا كان المخزون منخفضًا
                if (updatedQuantity < 5) {
                    const badge = document.createElement('div');
                    badge.className = 'stock-badge low';
                    badge.textContent = 'المخزون منخفض';
                    stockBadgeContainer.appendChild(badge);
                }
            }
        }
    });
}

// إضافة العملية إلى قائمة آخر العمليات في الواجهة
function addToRecentTransactions(saleData) {
    // البحث عن حاوية آخر العمليات
    const transactionsList = document.querySelector('.transactions-list');
    if (!transactionsList) return;
    
    // إنشاء عنصر العملية الجديدة
    const transactionItem = document.createElement('div');
    transactionItem.className = 'transaction-item new-transaction';
    transactionItem.setAttribute('data-id', saleData.invoice_number);
    
    // تنسيق التاريخ والوقت
    const now = new Date();
    const formattedDate = `${now.getDate()}/${now.getMonth() + 1}/${now.getFullYear()}`;
    const formattedTime = `${now.getHours()}:${now.getMinutes().toString().padStart(2, '0')}`;
    
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
                ${saleData.customer || 'عميل نقدي'}
            </div>
            <div class="transaction-total">
                ${saleData.total.toFixed(2)} جنيه
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
    
    // إزالة رسالة "لا توجد عمليات" إذا كانت موجودة
    const emptyTransactions = transactionsList.querySelector('.empty-transactions');
    if (emptyTransactions) {
        transactionsList.removeChild(emptyTransactions);
    }
    
    // إضافة العملية في بداية القائمة
    if (transactionsList.firstChild) {
        transactionsList.insertBefore(transactionItem, transactionsList.firstChild);
    } else {
        transactionsList.appendChild(transactionItem);
    }
    
    // إضافة تأثير التلاشي بعد ثانيتين
    setTimeout(() => {
        transactionItem.classList.remove('new-transaction');
    }, 2000);
    
    // إضافة معالجات الأحداث للأزرار
    setupTransactionActions(transactionItem);
}

// إعداد أحداث أزرار العمليات
function setupTransactionActions(transactionItem) {
    if (!transactionItem) return;
    
    const actionButtons = transactionItem.querySelectorAll('.transaction-action');
    
    actionButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            
            const actionType = this.getAttribute('data-action');
            const transactionId = transactionItem.getAttribute('data-id');
            
            if (actionType === 'view') {
                viewTransactionDetails(transactionId);
            } else if (actionType === 'print') {
                printTransactionReceipt(transactionId);
            } else if (actionType === 'receipt') {
                sendTransactionReceipt(transactionId);
            }
        });
    });
    
    // إضافة حدث النقر على العملية نفسها
    transactionItem.addEventListener('click', function() {
        const transactionId = this.getAttribute('data-id');
        viewTransactionDetails(transactionId);
    });
}

// إظهار رسالة للمستخدم
function showNotification(message, type) {
    // إنشاء عنصر الإشعار إذا لم يكن موجودًا
    let notification = document.getElementById('notification');
    if (!notification) {
        notification = document.createElement('div');
        notification.id = 'notification';
        document.body.appendChild(notification);
    }
    
    // تعيين النوع والرسالة
    notification.className = 'alert-message ' + type;
    
    // إضافة الأيقونة المناسبة
    let icon = 'info-circle';
    if (type === 'success') icon = 'check-circle';
    if (type === 'error') icon = 'exclamation-circle';
    
    notification.innerHTML = `
        <i class="fas fa-${icon}"></i>
        <span>${message}</span>
        <button class="close-message" onclick="this.parentElement.classList.remove('show')">&times;</button>
    `;
    
    // إظهار الإشعار
    notification.classList.add('show');
    
    // إخفاء الإشعار بعد 3 ثوانٍ
    setTimeout(() => {
        notification.classList.remove('show');
    }, 3000);
}

// دالة لإضافة العميل إلى نظام التقسيط عند حفظه من الكاشير
function saveCustomerToInstallmentSystem(customerData) {
    // إنشاء بيانات النموذج لإرسالها إلى ملف add_installment.php
    const formData = new FormData();
    formData.append('customer_name', customerData.name);
    formData.append('customer_phone', customerData.phone || '');
    formData.append('customer_email', customerData.email || '');
    formData.append('customer_address', customerData.address || '');
    formData.append('from_cashier', '1'); // علامة لتحديد أن الطلب قادم من الكاشير
    
    // إرسال طلب AJAX لإضافة العميل
    fetch('../views/add_installment.php?add_customer=1', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('تم إضافة العميل بنجاح إلى نظام التقسيط', data);
            // إذا كان هناك معرف عميل تم إرجاعه، قم بتحديث العميل المحدد
            if (data.customer_id) {
                customerData.id = data.customer_id;
                // تحديث العميل في الواجهة إذا كان مطلوباً
            }
        } else {
            console.error('حدث خطأ أثناء إضافة العميل إلى نظام التقسيط', data.error);
        }
    })
    .catch(error => {
        console.error('حدث خطأ في الاتصال:', error);
    });
}

// دالة لإضافة المبلغ إلى حساب العميل في نظام التقسيط
function addAmountToCustomerInstallment(customer_id, amount, items) {
    // إنشاء بيانات النموذج للإرسال
    const formData = new FormData();
    formData.append('customer_id', customer_id);
    formData.append('amount', amount);
    formData.append('is_external_amount', '1'); // تحديد أنه مبلغ إضافي على الحساب
    formData.append('from_cashier', '1'); // علامة لتحديد أن الطلب قادم من الكاشير
    
    // إضافة تفاصيل المنتجات المشتراة في الملاحظات
    let itemsDetails = '';
    items.forEach(item => {
        itemsDetails += `${item.name} (${item.quantity}): ${item.total.toFixed(2)} جنيه\n`;
    });
    formData.append('notes', 'مشتريات من الكاشير:\n' + itemsDetails);
    
    // تعيين تاريخ افتراضي لأول قسط (الشهر القادم)
    const today = new Date();
    const nextMonth = new Date(today);
    nextMonth.setMonth(today.getMonth() + 1);
    const dueDate = nextMonth.toISOString().split('T')[0]; // تنسيق YYYY-MM-DD
    formData.append('due_date', dueDate);
    
    // إرسال طلب AJAX لإضافة المبلغ إلى حساب العميل
    fetch('../views/add_installment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('تم إضافة المبلغ بنجاح إلى حساب التقسيط للعميل', data);
            // هنا يمكن إضافة أي إجراءات إضافية بعد نجاح العملية
        } else {
            console.error('حدث خطأ أثناء إضافة المبلغ إلى حساب التقسيط', data.error);
            // إظهار رسالة خطأ للمستخدم
            alert('حدث خطأ أثناء إضافة المبلغ إلى حساب العميل. الرجاء المحاولة مرة أخرى.');
        }
    })
    .catch(error => {
        console.error('حدث خطأ في الاتصال:', error);
        alert('حدث خطأ في الاتصال. الرجاء التحقق من اتصالك بالإنترنت والمحاولة مرة أخرى.');
    });
}

// تعديل دالة saveNewCustomer لتستدعي إضافة العميل إلى نظام التقسيط
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
        id: Date.now(), // قيمة مؤقتة ستتم تحديثها عند الحفظ في قاعدة البيانات
        name: customerName,
        phone: customerPhone,
        email: customerEmail,
        address: customerAddress
    };
    
    // إضافة العميل إلى نظام التقسيط
    saveCustomerToInstallmentSystem(newCustomer);
    
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