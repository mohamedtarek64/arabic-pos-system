// ملف جديد لإصلاح مشكلة أزرار الكاشير
document.addEventListener('DOMContentLoaded', function() {
    // العناصر الرئيسية
    const checkoutBtn = document.getElementById('checkoutBtn');
    const holdOrderBtn = document.getElementById('holdOrderBtn');
    const checkoutModal = document.getElementById('checkoutModal');
    
    // إضافة مستمعي الأحداث للأزرار مباشرة
    const downloadPdfBtn = document.getElementById('downloadPdfBtn');
    if (downloadPdfBtn) {
        downloadPdfBtn.addEventListener('click', generatePDF);
    }
    
    const whatsappReceiptBtn = document.getElementById('whatsappReceiptBtn');
    if (whatsappReceiptBtn) {
        whatsappReceiptBtn.addEventListener('click', shareViaWhatsApp);
    }
    
    const printReceiptBtn = document.getElementById('printReceiptBtn');
    if (printReceiptBtn) {
        printReceiptBtn.addEventListener('click', printReceipt);
    }
    
    // إضافة مستمعي الأحداث للأزرار
    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', function() {
            // التحقق من وجود منتجات في السلة
            const cartItems = document.querySelectorAll('.cart-item');
            if (cartItems.length === 0) {
                alert('لا يمكن إتمام البيع، السلة فارغة!');
                return;
            }
            
            // تحديث معلومات الدفع في المودال
            updateCheckoutModalInfo();
            
            // عرض مودال إتمام البيع
            checkoutModal.style.display = 'flex';
        });
    }
    
    if (holdOrderBtn) {
        holdOrderBtn.addEventListener('click', function() {
            // التحقق من وجود منتجات في السلة
            const cartItems = document.querySelectorAll('.cart-item');
            if (cartItems.length === 0) {
                alert('لا يمكن تعليق الطلب، السلة فارغة!');
                return;
            }
            
            // حفظ الطلب المعلق
            saveHoldOrder();
        });
    }
    
    // وظيفة تحديث معلومات الدفع في المودال
    function updateCheckoutModalInfo() {
        const subtotalElement = document.getElementById('subtotal');
        const totalElement = document.getElementById('total');
        const discountValue = document.getElementById('discountValue').value;
        const discountType = document.getElementById('discountType').value;
        
        // نسخ القيم إلى مودال الدفع
        document.getElementById('checkoutSubtotal').textContent = subtotalElement.textContent;
        document.getElementById('checkoutTotal').textContent = totalElement.textContent;
        
        // حساب قيمة الخصم وعرضها
        let discountAmount = 0;
        const subtotal = parseFloat(subtotalElement.textContent.replace(' جنيه', ''));
        
        if (discountType === 'percentage') {
            discountAmount = (subtotal * parseFloat(discountValue || 0)) / 100;
        } else {
            discountAmount = parseFloat(discountValue || 0);
        }
        
        document.getElementById('checkoutDiscount').textContent = discountAmount.toFixed(2) + ' جنيه';
        
        // تحديث حقل المبلغ المدفوع بقيمة الإجمالي افتراضياً
        const totalAmount = parseFloat(totalElement.textContent.replace(' جنيه', ''));
        document.getElementById('amountPaid').value = totalAmount.toFixed(2);
        
        // إعادة ضبط حقل المتبقي
        document.getElementById('changeAmount').value = '0.00';
        
        // تحديث تاريخ أول قسط إلى اليوم الحالي
        const today = new Date();
        const formattedDate = today.toISOString().split('T')[0];
        const firstPaymentDateField = document.getElementById('firstPaymentDate');
        if (firstPaymentDateField) {
            firstPaymentDateField.value = formattedDate;
        }
    }
    
    // وظيفة حفظ الطلب المعلق
    function saveHoldOrder() {
        // جمع بيانات السلة
        const cartItems = document.querySelectorAll('.cart-item');
        const cartData = [];
        
        cartItems.forEach(item => {
            const productId = item.getAttribute('data-id');
            const productName = item.querySelector('.cart-item-name').textContent;
            const price = parseFloat(item.querySelector('.cart-item-price').textContent.replace(' جنيه', ''));
            const quantity = parseInt(item.querySelector('.cart-item-quantity-input').value);
            
            cartData.push({
                id: productId,
                name: productName,
                price: price,
                quantity: quantity
            });
        });
        
        // الحصول على معلومات العميل إذا تم اختياره
        let customerId = null;
        let customerName = 'عميل نقدي';
        
        const selectedCustomerInfo = document.getElementById('selectedCustomerInfo');
        if (selectedCustomerInfo && selectedCustomerInfo.style.display !== 'none') {
            customerId = selectedCustomerInfo.getAttribute('data-id');
            customerName = document.getElementById('selectedCustomerName').textContent;
        }
        
        // الحصول على قيم الخصم والإجمالي
        const subtotal = document.getElementById('subtotal').textContent;
        const discountValue = document.getElementById('discountValue').value;
        const discountType = document.getElementById('discountType').value;
        const total = document.getElementById('total').textContent;
        
        // إنشاء كائن الطلب المعلق
        const holdOrder = {
            items: cartData,
            customer: {
                id: customerId,
                name: customerName
            },
            subtotal: subtotal,
            discount: {
                value: discountValue,
                type: discountType
            },
            total: total,
            timestamp: new Date().toISOString()
        };
        
        // حفظ الطلب في التخزين المحلي
        let holdOrders = JSON.parse(localStorage.getItem('holdOrders') || '[]');
        holdOrders.push(holdOrder);
        localStorage.setItem('holdOrders', JSON.stringify(holdOrders));
        
        // عرض رسالة نجاح
        alert('تم تعليق الطلب بنجاح!');
        
        // تفريغ السلة
        clearCart();
    }
    
    // وظيفة تفريغ السلة
    function clearCart() {
        const cartItems = document.getElementById('cartItems');
        
        // إزالة جميع عناصر السلة
        while (cartItems.firstChild) {
            cartItems.removeChild(cartItems.firstChild);
        }
        
        // إضافة رسالة السلة الفارغة
        const emptyCartMessage = document.createElement('div');
        emptyCartMessage.className = 'empty-cart';
        emptyCartMessage.innerHTML = `
            <i class="fas fa-shopping-basket"></i>
            <p>السلة فارغة</p>
        `;
        cartItems.appendChild(emptyCartMessage);
        
        // تحديث المجموع والإجمالي
        document.getElementById('subtotal').textContent = '0.00 جنيه';
        document.getElementById('total').textContent = '0.00 جنيه';
        
        // إعادة ضبط الخصم
        document.getElementById('discountValue').value = '0';
    }
    
    // إضافة مستمع للمبلغ المدفوع لحساب المتبقي
    const amountPaidInput = document.getElementById('amountPaid');
    if (amountPaidInput) {
        amountPaidInput.addEventListener('input', function() {
            const totalAmount = parseFloat(document.getElementById('checkoutTotal').textContent.replace(' جنيه', ''));
            const amountPaid = parseFloat(this.value || 0);
            const changeAmount = amountPaid - totalAmount;
            
            document.getElementById('changeAmount').value = changeAmount.toFixed(2);
        });
    }
    
    // إضافة مستمعي الأحداث لإغلاق المودالات
    const closeModalButtons = document.querySelectorAll('.close-modal, .cancel-btn');
    closeModalButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modalId = this.getAttribute('data-modal');
            if (modalId) {
                document.getElementById(modalId).style.display = 'none';
            }
        });
    });
    
    // إضافة مستمع لزر إتمام الدفع
    const completePaymentBtn = document.getElementById('completePaymentBtn');
    if (completePaymentBtn) {
        completePaymentBtn.addEventListener('click', function() {
            // معالجة عملية الدفع
            processPayment();
        });
    }
    
    // وظيفة معالجة الدفع
    function processPayment() {
        // الحصول على طريقة الدفع المحددة
        const paymentMethod = document.querySelector('.payment-method.active').getAttribute('data-method');
        
        // التحقق من صحة البيانات حسب طريقة الدفع
        if (paymentMethod === 'cash') {
            const amountPaid = parseFloat(document.getElementById('amountPaid').value || 0);
            const totalAmount = parseFloat(document.getElementById('checkoutTotal').textContent.replace(' جنيه', ''));
            
            if (amountPaid < totalAmount) {
                alert('المبلغ المدفوع أقل من إجمالي الفاتورة!');
                return;
            }
        } else if (paymentMethod === 'installment') {
            const customerId = document.getElementById('installmentCustomer').value;
            if (!customerId) {
                alert('يجب اختيار عميل للتقسيط!');
                return;
            }
            
            const installmentCount = parseInt(document.getElementById('installmentCount').value || 0);
            if (installmentCount <= 0) {
                alert('يجب تحديد عدد أقساط صحيح!');
                return;
            }
            
            const firstPaymentDate = document.getElementById('firstPaymentDate').value;
            if (!firstPaymentDate) {
                alert('يجب تحديد تاريخ أول قسط!');
                return;
            }
        } else if (paymentMethod === 'visa') {
            const cardNumber = document.getElementById('cardNumber').value;
            if (!cardNumber || cardNumber.length !== 4) {
                alert('يرجى إدخال آخر 4 أرقام من البطاقة!');
                return;
            }
        }
        
        // إغلاق مودال الدفع
        document.getElementById('checkoutModal').style.display = 'none';
        
        // عرض مودال الإيصال
        showReceiptModal(paymentMethod);
        
        // تفريغ السلة بعد إتمام عملية الدفع
        // clearCart(); - سنقوم بتفريغ السلة بعد طباعة الإيصال أو تحميل PDF
    }
    
    // وظيفة عرض مودال الإيصال
    function showReceiptModal(paymentMethod) {
        // تحديث بيانات الإيصال
        updateReceiptData(paymentMethod);
        
        // عرض المودال
        document.getElementById('receiptModal').style.display = 'flex';
    }
    
    // وظيفة تحديث بيانات الإيصال
    function updateReceiptData(paymentMethod) {
        // تعيين رقم الفاتورة والتاريخ والوقت
        const now = new Date();
        const receiptNumber = 'INV-' + now.getTime().toString().substr(-6);
        const formattedDate = now.toLocaleDateString('ar-EG');
        const formattedTime = now.toLocaleTimeString('ar-EG');
        
        document.getElementById('receiptNumber').textContent = receiptNumber;
        document.getElementById('receiptDate').textContent = formattedDate;
        document.getElementById('receiptTime').textContent = formattedTime;
        
        // تعيين اسم العميل
        let customerName = 'عميل نقدي';
        const selectedCustomerInfo = document.getElementById('selectedCustomerInfo');
        if (selectedCustomerInfo && selectedCustomerInfo.style.display !== 'none') {
            customerName = document.getElementById('selectedCustomerName').textContent;
        }
        document.getElementById('receiptCustomer').textContent = customerName;
        
        // إضافة عناصر السلة إلى الإيصال
        const receiptItemsList = document.getElementById('receiptItemsList');
        receiptItemsList.innerHTML = '';
        
        const cartItems = document.querySelectorAll('.cart-item');
        let subtotal = 0;
        
        cartItems.forEach(item => {
            const productName = item.querySelector('.cart-item-name').textContent;
            const price = parseFloat(item.querySelector('.cart-item-price').textContent.replace(' جنيه', ''));
            const quantity = parseInt(item.querySelector('.cart-item-quantity-input').value);
            const total = price * quantity;
            subtotal += total;
            
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${productName}</td>
                <td>${quantity}</td>
                <td>${price.toFixed(2)} جنيه</td>
                <td>${total.toFixed(2)} جنيه</td>
            `;
            receiptItemsList.appendChild(tr);
        });
        
        // تعيين المجموع والخصم والإجمالي
        document.getElementById('receiptSubtotal').textContent = subtotal.toFixed(2) + ' جنيه';
        
        const discountValue = document.getElementById('discountValue').value;
        const discountType = document.getElementById('discountType').value;
        let discountAmount = 0;
        
        if (discountType === 'percentage') {
            discountAmount = (subtotal * parseFloat(discountValue || 0)) / 100;
        } else {
            discountAmount = parseFloat(discountValue || 0);
        }
        
        document.getElementById('receiptDiscount').textContent = discountAmount.toFixed(2) + ' جنيه';
        
        const total = subtotal - discountAmount;
        document.getElementById('receiptTotal').textContent = total.toFixed(2) + ' جنيه';
        
        // تعيين طريقة الدفع
        let paymentMethodText = 'كاش';
        if (paymentMethod === 'installment') {
            paymentMethodText = 'تقسيط';
        } else if (paymentMethod === 'visa') {
            paymentMethodText = 'فيزا';
        }
        
        document.getElementById('receiptPaymentMethod').textContent = paymentMethodText;
    }
    
    // وظيفة إنشاء ملف PDF
    function generatePDF() {
        console.log("تنفيذ وظيفة إنشاء PDF");
        
        try {
            // تحقق من وجود jsPDF
            if (typeof window.jspdf === 'undefined') {
                console.error("مكتبة jsPDF غير موجودة");
                alert("مكتبة PDF غير متوفرة. جاري إعادة تحميل الصفحة...");
                location.reload();
                return;
            }
            
            // إنشاء كائن PDF جديد
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF({
                orientation: 'portrait',
                unit: 'mm',
                format: 'a5'
            });
            
            // تعيين اتجاه النص من اليمين إلى اليسار
            doc.setR2L(true);
            
            // إضافة بيانات المتجر
            doc.setFontSize(18);
            doc.text('متجر الإلكترونيات', 105, 20, { align: 'center' });
            doc.setFontSize(12);
            doc.text('القاهرة، مصر', 105, 28, { align: 'center' });
            doc.text('هاتف: 0123456789', 105, 35, { align: 'center' });
            
            // إضافة خط فاصل
            doc.setLineWidth(0.5);
            doc.line(20, 40, 190, 40);
            
            // إضافة بيانات الفاتورة
            doc.setFontSize(14);
            doc.text('فاتورة مبيعات', 105, 50, { align: 'center' });
            
            // إضافة تفاصيل الفاتورة
            doc.setFontSize(10);
            doc.text('رقم الفاتورة: ' + document.getElementById('receiptNumber').textContent, 20, 60);
            doc.text('التاريخ: ' + document.getElementById('receiptDate').textContent, 20, 67);
            doc.text('الوقت: ' + document.getElementById('receiptTime').textContent, 20, 74);
            doc.text('العميل: ' + document.getElementById('receiptCustomer').textContent, 20, 81);
            
            // إضافة خط فاصل
            doc.line(20, 85, 190, 85);
            
            // إضافة عناوين الأعمدة
            doc.text('المنتج', 170, 92);
            doc.text('الكمية', 130, 92);
            doc.text('السعر', 90, 92);
            doc.text('الإجمالي', 50, 92);
            
            // إضافة خط فاصل
            doc.line(20, 95, 190, 95);
            
            // إضافة المنتجات
            const cartItems = document.querySelectorAll('.cart-item');
            let y = 102;
            
            cartItems.forEach(item => {
                const productName = item.querySelector('.cart-item-name').textContent;
                const price = parseFloat(item.querySelector('.cart-item-price').textContent.replace(' جنيه', ''));
                const quantity = parseInt(item.querySelector('.cart-item-quantity-input').value);
                const total = price * quantity;
                
                doc.text(productName, 170, y, { align: 'right' });
                doc.text(quantity.toString(), 130, y, { align: 'center' });
                doc.text(price.toFixed(2) + ' جنيه', 90, y, { align: 'center' });
                doc.text(total.toFixed(2) + ' جنيه', 50, y, { align: 'center' });
                
                y += 10;
            });
            
            // إضافة خط فاصل
            doc.line(20, y, 190, y);
            y += 10;
            
            // إضافة ملخص الفاتورة
            doc.text('المجموع:', 170, y);
            doc.text(document.getElementById('receiptSubtotal').textContent, 50, y, { align: 'center' });
            y += 10;
            
            doc.text('الخصم:', 170, y);
            doc.text(document.getElementById('receiptDiscount').textContent, 50, y, { align: 'center' });
            y += 10;
            
            doc.setFontSize(12);
            doc.text('الإجمالي:', 170, y);
            doc.text(document.getElementById('receiptTotal').textContent, 50, y, { align: 'center' });
            y += 10;
            
            doc.text('طريقة الدفع:', 170, y);
            doc.text(document.getElementById('receiptPaymentMethod').textContent, 50, y, { align: 'center' });
            y += 20;
            
            // إضافة نص الشكر
            doc.setFontSize(10);
            doc.text('شكراً لثقتكم بنا', 105, y, { align: 'center' });
            
            // تحميل الملف
            const receiptNumber = document.getElementById('receiptNumber').textContent;
            doc.save('فاتورة_' + receiptNumber + '.pdf');
            
            // عرض رسالة نجاح
            alert('تم تحميل الفاتورة بصيغة PDF بنجاح!');
            
            // تفريغ السلة بعد نجاح التحميل
            clearCart();
        } catch (error) {
            console.error("خطأ في إنشاء PDF:", error);
            alert("حدث خطأ أثناء إنشاء ملف PDF: " + error.message);
        }
    }
    
    // وظيفة مشاركة الفاتورة عبر واتس اب
    function shareViaWhatsApp() {
        console.log("تنفيذ وظيفة مشاركة عبر واتس اب");
        
        try {
            // الحصول على بيانات الفاتورة
            const receiptNumber = document.getElementById('receiptNumber').textContent;
            const receiptDate = document.getElementById('receiptDate').textContent;
            const receiptCustomer = document.getElementById('receiptCustomer').textContent;
            const receiptTotal = document.getElementById('receiptTotal').textContent;
            
            // إنشاء نص الرسالة
            let message = `*فاتورة مبيعات - ${receiptNumber}*\n`;
            message += `التاريخ: ${receiptDate}\n`;
            message += `العميل: ${receiptCustomer}\n`;
            message += `المبلغ الإجمالي: ${receiptTotal}\n`;
            message += `\nشكراً لتعاملكم معنا - متجر الإلكترونيات`;
            
            // إنشاء رابط واتس اب
            const encodedMessage = encodeURIComponent(message);
            const whatsappURL = `https://wa.me/?text=${encodedMessage}`;
            
            // فتح الرابط في نافذة جديدة
            window.open(whatsappURL, '_blank');
            
            // تفريغ السلة بعد المشاركة
            clearCart();
        } catch (error) {
            console.error("خطأ في مشاركة الواتس اب:", error);
            alert("حدث خطأ أثناء مشاركة الفاتورة عبر واتس اب: " + error.message);
        }
    }
    
    // وظيفة طباعة الإيصال
    function printReceipt() {
        console.log("تنفيذ وظيفة الطباعة");
        
        try {
            // الحصول على محتوى الإيصال
            const receiptContent = document.getElementById('receipt');
            if (!receiptContent) {
                console.error("عنصر الإيصال غير موجود");
                alert("لا يمكن العثور على محتوى الإيصال");
                return;
            }
            
            // إنشاء نافذة طباعة جديدة
            const printWindow = window.open('', '_blank', 'width=600,height=600');
            if (!printWindow) {
                alert("تم حظر النوافذ المنبثقة. يرجى السماح بالنوافذ المنبثقة وإعادة المحاولة.");
                return;
            }
            
            printWindow.document.open();
            printWindow.document.write(`
                <!DOCTYPE html>
                <html lang="ar" dir="rtl">
                <head>
                    <meta charset="UTF-8">
                    <title>طباعة الإيصال</title>
                    <style>
                        @media print {
                            body {
                                font-family: Arial, sans-serif;
                                margin: 0;
                                padding: 10mm;
                                direction: rtl;
                            }
                            .receipt-header {
                                text-align: center;
                                margin-bottom: 10mm;
                            }
                            .receipt-header h2 {
                                margin: 0;
                                font-size: 18pt;
                            }
                            .receipt-header p {
                                margin: 2mm 0;
                                font-size: 10pt;
                            }
                            .receipt-details {
                                margin-bottom: 10mm;
                                font-size: 10pt;
                            }
                            .receipt-row {
                                margin-bottom: 2mm;
                            }
                            .receipt-items {
                                width: 100%;
                                border-collapse: collapse;
                                margin-bottom: 10mm;
                            }
                            .receipt-items th {
                                border-bottom: 1px solid #000;
                                padding: 2mm;
                                text-align: right;
                                font-size: 10pt;
                            }
                            .receipt-items td {
                                padding: 2mm;
                                text-align: right;
                                font-size: 10pt;
                            }
                            .receipt-summary {
                                margin-top: 5mm;
                                font-size: 10pt;
                            }
                            .summary-row {
                                margin-bottom: 2mm;
                            }
                            .total {
                                font-weight: bold;
                                font-size: 12pt;
                            }
                            .receipt-footer {
                                margin-top: 10mm;
                                text-align: center;
                                font-size: 10pt;
                            }
                        }
                    </style>
                </head>
                <body>
                    <div class="receipt-content">
                        ${receiptContent.outerHTML}
                    </div>
                    <script>
                        window.onload = function() {
                            window.print();
                            setTimeout(function() { window.close(); }, 500);
                        };
                    </script>
                </body>
                </html>
            `);
            printWindow.document.close();
            
            // تفريغ السلة بعد الطباعة
            setTimeout(function() {
                clearCart();
            }, 1000);
        } catch (error) {
            console.error("خطأ في الطباعة:", error);
            alert("حدث خطأ أثناء طباعة الإيصال: " + error.message);
        }
    }
}); 