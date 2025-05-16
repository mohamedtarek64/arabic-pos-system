// ملف مخصص للتعامل المباشر مع وظائف PDF والطباعة
document.addEventListener('DOMContentLoaded', function() {
    console.log('تم تحميل ملف cashier-direct.js');
    
    // إضافة مستمعات الأحداث لأزرار الإيصال
    const downloadPdfBtn = document.getElementById('downloadPdfBtn');
    if (downloadPdfBtn) {
        console.log('تم العثور على زر PDF');
        downloadPdfBtn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('تم النقر على زر PDF');
            generatePDF();
        });
    }
    
    const whatsappReceiptBtn = document.getElementById('whatsappReceiptBtn');
    if (whatsappReceiptBtn) {
        whatsappReceiptBtn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('تم النقر على زر واتس اب');
            shareViaWhatsApp();
        });
    }
    
    const printReceiptBtn = document.getElementById('printReceiptBtn');
    if (printReceiptBtn) {
        printReceiptBtn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('تم النقر على زر الطباعة');
            printReceipt();
        });
    }
    
    // وظيفة إنشاء ملف PDF
    function generatePDF() {
        console.log('بدء إنشاء PDF');
        
        try {
            // التحقق من وجود مكتبة jsPDF
            if (typeof window.jspdf === 'undefined') {
                console.error('مكتبة jsPDF غير موجودة');
                alert('مكتبة PDF غير متوفرة. يرجى تحديث الصفحة والمحاولة مرة أخرى.');
                return;
            }
            
            // استخدام html2canvas لتحويل الإيصال إلى صورة
            const receiptElement = document.getElementById('receipt');
            
            html2canvas(receiptElement).then(function(canvas) {
                // إنشاء PDF من الصورة
                const imgData = canvas.toDataURL('image/png');
                
                // إنشاء كائن PDF
                const { jsPDF } = window.jspdf;
                const pdf = new jsPDF({
                    orientation: 'portrait',
                    unit: 'mm',
                    format: 'a4'
                });
                
                // حساب الأبعاد للحفاظ على نسبة العرض إلى الارتفاع
                const imgWidth = 210;
                const pageHeight = 295;
                const imgHeight = canvas.height * imgWidth / canvas.width;
                let heightLeft = imgHeight;
                let position = 0;
                
                // إضافة الصورة إلى الصفحة الأولى
                pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                heightLeft -= pageHeight;
                
                // إضافة صفحات إضافية إذا كان الإيصال طويلاً
                while (heightLeft >= 0) {
                    position = heightLeft - imgHeight;
                    pdf.addPage();
                    pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                    heightLeft -= pageHeight;
                }
                
                // تحميل الملف
                const receiptNumber = document.getElementById('receiptNumber').textContent || 'receipt';
                pdf.save('فاتورة_' + receiptNumber + '.pdf');
                
                // عرض رسالة نجاح
                alert('تم تحميل الفاتورة بصيغة PDF بنجاح!');
                
                // تفريغ السلة بعد نجاح التحميل
                clearCart();
            });
        } catch (error) {
            console.error('خطأ في إنشاء PDF:', error);
            alert('حدث خطأ أثناء إنشاء ملف PDF: ' + error.message);
        }
    }
    
    // وظيفة مشاركة الفاتورة عبر واتس اب
    function shareViaWhatsApp() {
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
            console.error('خطأ في مشاركة الواتس اب:', error);
            alert('حدث خطأ أثناء مشاركة الفاتورة عبر واتس اب: ' + error.message);
        }
    }
    
    // وظيفة طباعة الإيصال
    function printReceipt() {
        try {
            // الحصول على محتوى الإيصال
            const receiptElement = document.getElementById('receipt');
            
            // إنشاء نافذة طباعة جديدة
            const printWindow = window.open('', '_blank', 'width=800,height=600');
            
            printWindow.document.write(`
                <!DOCTYPE html>
                <html lang="ar" dir="rtl">
                <head>
                    <meta charset="UTF-8">
                    <title>طباعة الإيصال</title>
                    <style>
                        body {
                            font-family: Arial, sans-serif;
                            direction: rtl;
                            padding: 20px;
                        }
                        .receipt-header {
                            text-align: center;
                            margin-bottom: 20px;
                        }
                        .receipt-header h2 {
                            margin: 0;
                            font-size: 24px;
                        }
                        .receipt-header p {
                            margin: 5px 0;
                        }
                        .receipt-details {
                            margin-bottom: 20px;
                        }
                        .receipt-row {
                            margin-bottom: 5px;
                        }
                        .receipt-items {
                            width: 100%;
                            border-collapse: collapse;
                            margin-bottom: 20px;
                        }
                        .receipt-items th, .receipt-items td {
                            border: 1px solid #ddd;
                            padding: 8px;
                            text-align: right;
                        }
                        .receipt-items th {
                            background-color: #f2f2f2;
                        }
                        .receipt-summary {
                            margin-top: 20px;
                        }
                        .summary-row {
                            margin-bottom: 5px;
                        }
                        .total {
                            font-weight: bold;
                            font-size: 18px;
                        }
                        .receipt-footer {
                            margin-top: 30px;
                            text-align: center;
                        }
                        @media print {
                            button {
                                display: none;
                            }
                        }
                    </style>
                </head>
                <body>
                    <div class="print-container">
                        ${receiptElement.outerHTML}
                    </div>
                    <div style="text-align: center; margin-top: 20px;">
                        <button onclick="window.print();">طباعة</button>
                        <button onclick="window.close();">إغلاق</button>
                    </div>
                    <script>
                        window.onload = function() {
                            setTimeout(function() {
                                window.print();
                            }, 500);
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
            console.error('خطأ في الطباعة:', error);
            alert('حدث خطأ أثناء طباعة الإيصال: ' + error.message);
        }
    }
    
    // وظيفة تفريغ السلة
    function clearCart() {
        const cartItems = document.getElementById('cartItems');
        if (!cartItems) return;
        
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
        const subtotalElement = document.getElementById('subtotal');
        const totalElement = document.getElementById('total');
        
        if (subtotalElement) subtotalElement.textContent = '0.00 جنيه';
        if (totalElement) totalElement.textContent = '0.00 جنيه';
        
        // إعادة ضبط الخصم
        const discountValueElement = document.getElementById('discountValue');
        if (discountValueElement) discountValueElement.value = '0';
        
        console.log('تم تفريغ السلة بنجاح');
    }
}); 