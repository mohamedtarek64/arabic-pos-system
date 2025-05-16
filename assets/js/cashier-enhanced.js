// ملف JavaScript للتحسينات الإضافية في الكاشير

document.addEventListener('DOMContentLoaded', function() {
    // تهيئة jsPDF
    window.jsPDF = window.jspdf.jsPDF;
    
    // العناصر المستخدمة في الصفحة
    const installmentMethodBtn = document.getElementById('installmentMethodBtn');
    const installmentWarning = document.getElementById('installmentWarning');
    const selectedCustomerInfo = document.getElementById('selectedCustomerInfo');
    const selectedCustomerName = document.getElementById('selectedCustomerName');
    const selectedCustomerPhone = document.getElementById('selectedCustomerPhone');
    const editCustomerBtn = document.getElementById('editCustomerBtn');
    const removeCustomerBtn = document.getElementById('removeCustomerBtn');
    const downloadPdfBtn = document.getElementById('downloadPdfBtn');
    const whatsappReceiptBtn = document.getElementById('whatsappReceiptBtn');
    
    // متغيرات عامة
    let selectedCustomer = null;
    let currentSale = null;
    
    // استماع لأحداث اختيار العميل من البحث
    document.addEventListener('customerSelected', function(e) {
        selectedCustomer = e.detail;
        updateSelectedCustomerDisplay();
    });
    
    // استماع لأحداث إتمام البيع
    document.addEventListener('saleCompleted', function(e) {
        currentSale = e.detail;
    });
    
    // تحديث عرض العميل المحدد
    function updateSelectedCustomerDisplay() {
        if (selectedCustomer) {
            selectedCustomerName.textContent = selectedCustomer.name;
            selectedCustomerPhone.textContent = selectedCustomer.phone || 'لا يوجد رقم هاتف';
            selectedCustomerInfo.style.display = 'flex';
            document.querySelector('.no-customer-selected').style.display = 'none';
            
            // إزالة تنبيه عدم اختيار عميل للتقسيط إذا كان ظاهراً
            installmentWarning.style.display = 'none';
        } else {
            selectedCustomerInfo.style.display = 'none';
            document.querySelector('.no-customer-selected').style.display = 'flex';
        }
    }
    
    // التحقق من اختيار عميل عند النقر على زر التقسيط
    installmentMethodBtn.addEventListener('click', function() {
        if (!selectedCustomer) {
            installmentWarning.style.display = 'flex';
            installmentMethodBtn.classList.add('shake');
            
            // إزالة تأثير الاهتزاز بعد انتهائه
            setTimeout(() => {
                installmentMethodBtn.classList.remove('shake');
            }, 500);
            
            return false;
        } else {
            installmentWarning.style.display = 'none';
        }
    });
    
    // إزالة العميل المحدد
    removeCustomerBtn.addEventListener('click', function() {
        selectedCustomer = null;
        updateSelectedCustomerDisplay();
        
        // إطلاق حدث لإبلاغ بقية التطبيق بإزالة العميل
        document.dispatchEvent(new CustomEvent('customerRemoved'));
    });
    
    // تعديل بيانات العميل
    editCustomerBtn.addEventListener('click', function() {
        if (selectedCustomer) {
            // فتح نافذة تعديل العميل أو تنفيذ الإجراء المناسب
            // هذا مثال فقط، يمكن تعديله حسب متطلبات التطبيق
            alert('تعديل بيانات العميل: ' + selectedCustomer.name);
        }
    });
    
    // تحميل الفاتورة كملف PDF
    downloadPdfBtn.addEventListener('click', function() {
        generatePDF();
    });
    
    // مشاركة الفاتورة عبر واتساب
    whatsappReceiptBtn.addEventListener('click', function() {
        shareViaWhatsApp();
    });
    
    // إنشاء ملف PDF للفاتورة
    function generatePDF() {
        const { jsPDF } = window.jspdf;
        
        // إنشاء مستند PDF جديد بتوجيه RTL
        const doc = new jsPDF({
            orientation: 'portrait',
            unit: 'mm',
            format: 'a4',
            putOnlyUsedFonts: true
        });
        
        // إضافة دعم للغة العربية
        doc.addFont('https://fonts.googleapis.com/css2?family=Cairo&display=swap', 'Cairo', 'normal');
        doc.setFont('Cairo');
        
        // الحصول على بيانات الفاتورة من العناصر الموجودة
        const receiptNumber = document.getElementById('receiptNumber').textContent;
        const receiptDate = document.getElementById('receiptDate').textContent;
        const receiptCustomer = document.getElementById('receiptCustomer').textContent;
        const receiptTotal = document.getElementById('receiptTotal').textContent;
        const receiptPaymentMethod = document.getElementById('receiptPaymentMethod').textContent;
        
        // الحصول على عناصر المنتجات
        const itemsTable = document.getElementById('receiptItemsList');
        const items = [];
        
        if (itemsTable) {
            const rows = itemsTable.querySelectorAll('tr');
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length >= 4) {
                    items.push({
                        name: cells[0].textContent,
                        quantity: cells[1].textContent,
                        price: cells[2].textContent,
                        total: cells[3].textContent
                    });
                }
            });
        }
        
        // إنشاء محتوى PDF
        
        // رأس الصفحة
        doc.setFontSize(22);
        doc.text('متجر الإلكترونيات', 105, 20, { align: 'center' });
        
        doc.setFontSize(12);
        doc.text('القاهرة، مصر', 105, 30, { align: 'center' });
        doc.text('هاتف: 0123456789', 105, 35, { align: 'center' });
        
        // معلومات الفاتورة
        doc.setFontSize(14);
        doc.text('فاتورة مبيعات', 105, 45, { align: 'center' });
        
        doc.setFontSize(10);
        doc.text('رقم الفاتورة: ' + receiptNumber, 190, 55, { align: 'right' });
        doc.text('التاريخ: ' + receiptDate, 190, 60, { align: 'right' });
        doc.text('العميل: ' + receiptCustomer, 190, 65, { align: 'right' });
        doc.text('طريقة الدفع: ' + receiptPaymentMethod, 190, 70, { align: 'right' });
        
        // جدول المنتجات
        const startY = 80;
        const lineHeight = 8;
        
        // رأس الجدول
        doc.setFillColor(240, 240, 240);
        doc.rect(20, startY, 170, lineHeight, 'F');
        
        doc.setFontSize(10);
        doc.text('المنتج', 180, startY + 5, { align: 'right' });
        doc.text('الكمية', 120, startY + 5, { align: 'right' });
        doc.text('السعر', 80, startY + 5, { align: 'right' });
        doc.text('الإجمالي', 40, startY + 5, { align: 'right' });
        
        // محتوى الجدول
        let currentY = startY + lineHeight;
        
        items.forEach((item, index) => {
            const isEven = index % 2 === 0;
            
            if (isEven) {
                doc.setFillColor(250, 250, 250);
                doc.rect(20, currentY, 170, lineHeight, 'F');
            }
            
            doc.text(item.name, 180, currentY + 5, { align: 'right' });
            doc.text(item.quantity, 120, currentY + 5, { align: 'right' });
            doc.text(item.price, 80, currentY + 5, { align: 'right' });
            doc.text(item.total, 40, currentY + 5, { align: 'right' });
            
            currentY += lineHeight;
        });
        
        // خط أسفل الجدول
        doc.setDrawColor(200, 200, 200);
        doc.line(20, currentY, 190, currentY);
        
        // ملخص الفاتورة
        currentY += 10;
        doc.text('الإجمالي:', 80, currentY, { align: 'right' });
        doc.text(receiptTotal, 40, currentY, { align: 'right' });
        
        // تذييل الفاتورة
        doc.setFontSize(10);
        doc.text('شكراً لثقتكم بنا', 105, 270, { align: 'center' });
        
        // حفظ الملف
        const fileName = 'فاتورة_' + receiptNumber + '.pdf';
        doc.save(fileName);
    }
    
    // مشاركة الفاتورة عبر واتساب
    function shareViaWhatsApp() {
        // التحقق من وجود رقم هاتف للعميل
        if (!selectedCustomer || !selectedCustomer.phone) {
            alert('لا يوجد رقم هاتف للعميل لمشاركة الفاتورة عبر واتساب');
            return;
        }
        
        // تنسيق رقم الهاتف (إزالة أي أحرف غير رقمية)
        let phoneNumber = selectedCustomer.phone.replace(/\D/g, '');
        
        // إذا لم يبدأ الرقم بـ +، أضف رمز الدولة الافتراضي (مصر: 20+)
        if (!phoneNumber.startsWith('+')) {
            // إذا بدأ بصفر، قم بإزالته
            if (phoneNumber.startsWith('0')) {
                phoneNumber = phoneNumber.substring(1);
            }
            // إضافة رمز الدولة
            phoneNumber = '20' + phoneNumber;
        }
        
        // الحصول على بيانات الفاتورة
        const receiptNumber = document.getElementById('receiptNumber').textContent;
        const receiptTotal = document.getElementById('receiptTotal').textContent;
        const receiptPaymentMethod = document.getElementById('receiptPaymentMethod').textContent;
        
        // إنشاء نص الرسالة
        let message = 'مرحباً ' + selectedCustomer.name + '،\n\n';
        message += 'فيما يلي تفاصيل فاتورة مشترياتك:\n';
        message += 'رقم الفاتورة: ' + receiptNumber + '\n';
        message += 'المبلغ الإجمالي: ' + receiptTotal + '\n';
        message += 'طريقة الدفع: ' + receiptPaymentMethod + '\n\n';
        message += 'شكراً لتعاملك معنا!';
        
        // إنشاء رابط واتساب
        const whatsappUrl = 'https://wa.me/' + phoneNumber + '?text=' + encodeURIComponent(message);
        
        // فتح الرابط في نافذة جديدة
        window.open(whatsappUrl, '_blank');
    }
    
    // تحسين عرض المخزون للمنتجات
    function enhanceProductStockDisplay() {
        const productCards = document.querySelectorAll('.product-card');
        
        productCards.forEach(card => {
            const stock = parseInt(card.getAttribute('data-stock'));
            
            if (stock === 0) {
                card.classList.add('out-of-stock');
                
                // إضافة شارة "نفذ من المخزون"
                const stockBadge = document.createElement('div');
                stockBadge.className = 'stock-badge out';
                stockBadge.textContent = 'نفذ من المخزون';
                
                const productImg = card.querySelector('.product-img');
                if (productImg) {
                    productImg.appendChild(stockBadge);
                }
            }
        });
    }
    
    // تنفيذ تحسين عرض المخزون عند تحميل الصفحة
    enhanceProductStockDisplay();
}); 