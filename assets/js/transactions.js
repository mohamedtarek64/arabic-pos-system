

// متغيرات عامة
let currentPage = 1;
let itemsPerPage = 10;
let totalItems = 0;
let currentFilter = 'all'; // الفلتر الحالي: all, today, week, month
let searchTerm = '';

// تهيئة التاريخ عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    // إضافة مستمعات الأحداث
    setupEventListeners();
    
    // تحميل آخر المعاملات
    loadTransactions();
    
    // تحميل معلومات لوحة المعلومات إذا كانت موجودة
    if (document.querySelector('.dashboard-metrics')) {
        loadDashboardMetrics();
    }
});


function setupEventListeners() {
    // مستمعات لأزرار الفلتر
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            // إزالة الفئة النشطة من جميع الأزرار
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            // إضافة الفئة النشطة للزر الحالي
            this.classList.add('active');
            
            // تحديث الفلتر الحالي
            currentFilter = this.getAttribute('data-filter');
            currentPage = 1; // إعادة تعيين الصفحة الحالية
            
            // إعادة تحميل المعاملات
            loadTransactions();
        });
    });
    
    // مستمع لمربع البحث
    const searchInput = document.querySelector('.search-input input');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            searchTerm = this.value.trim();
            if (searchTerm.length >= 2 || searchTerm.length === 0) {
                currentPage = 1; // إعادة تعيين الصفحة الحالية
                loadTransactions();
            }
        });
    }
    
    // إعداد مستمعات لأزرار التنقل بين الصفحات (سيتم إنشاؤها ديناميكياً)
}


function loadTransactions() {
    // عرض مؤشر التحميل
    const transactionsTable = document.querySelector('.transactions-table tbody');
    if (transactionsTable) {
        transactionsTable.innerHTML = '<tr><td colspan="6" class="loading-message">جاري تحميل المعاملات...</td></tr>';
    }
    
    // بناء URL مع المعايير
    let url = `get-transactions.php?limit=${itemsPerPage}&offset=${(currentPage - 1) * itemsPerPage}`;
    
    // إضافة الفلتر إذا كان محدداً
    if (currentFilter !== 'all') {
        url += `&date=${currentFilter}`;
    }
    
    // إضافة مصطلح البحث إذا تم إدخاله
    if (searchTerm) {
        url += `&search=${encodeURIComponent(searchTerm)}`;
    }
    
    // إرسال الطلب
    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error('حدث خطأ في الاتصال بالسيرفر');
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                // حفظ العدد الإجمالي
                totalItems = data.total;
                
                // عرض المعاملات
                displayTransactions(data.data);
                
                // تحديث أزرار الصفحات
                updatePagination();
            } else {
                throw new Error(data.message || 'حدث خطأ أثناء تحميل المعاملات');
            }
        })
        .catch(error => {
            console.error('Error loading transactions:', error);
            // عرض رسالة الخطأ
            if (transactionsTable) {
                transactionsTable.innerHTML = `<tr><td colspan="6" class="error-message">
                    <i class="fas fa-exclamation-triangle"></i> 
                    خطأ: ${error.message}
                </td></tr>`;
            }
        });
}


function displayTransactions(transactions) {
    const transactionsTable = document.querySelector('.transactions-table tbody');
    if (!transactionsTable) return;
    
    // إذا لم تكن هناك معاملات
    if (!transactions || transactions.length === 0) {
        transactionsTable.innerHTML = `<tr><td colspan="6" class="empty-message">
            <i class="fas fa-search"></i>
            لا توجد معاملات مطابقة للمعايير المحددة
        </td></tr>`;
        return;
    }
    
    // عرض المعاملات
    let tableContent = '';
    
    transactions.forEach(transaction => {
        tableContent += `
            <tr data-id="${transaction.id}">
                <td class="invoice-number">${transaction.invoice_number}</td>
                <td>${transaction.customer_name || 'عميل نقدي'}</td>
                <td class="transaction-date">
                    <span class="date">${transaction.created_date}</span>
                    <span class="time">${transaction.created_time}</span>
                </td>
                <td class="transaction-amount">${transaction.total_amount} جنيه</td>
                <td>
                    <span class="transaction-status status-${transaction.payment_status}">
                        ${transaction.status_text}
                    </span>
                </td>
                <td>
                    <div class="transaction-actions">
                        <button class="transaction-action-btn view" data-action="view" title="عرض التفاصيل">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="transaction-action-btn print" data-action="print" title="طباعة الفاتورة">
                            <i class="fas fa-print"></i>
                        </button>
                        <button class="transaction-action-btn download" data-action="download" title="تحميل PDF">
                            <i class="fas fa-file-pdf"></i>
                        </button>
                        <button class="transaction-action-btn share" data-action="share" title="مشاركة">
                            <i class="fas fa-share-alt"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });
    
    transactionsTable.innerHTML = tableContent;
    
    // إضافة مستمعات الأحداث لأزرار الإجراءات
    setupActionButtons();
}


function setupActionButtons() {
    document.querySelectorAll('.transaction-action-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const transactionId = this.closest('tr').getAttribute('data-id');
            const action = this.getAttribute('data-action');
            
            switch(action) {
                case 'view':
                    viewTransactionDetails(transactionId);
                    break;
                case 'print':
                    printTransaction(transactionId);
                    break;
                case 'download':
                    downloadTransactionPDF(transactionId);
                    break;
                case 'share':
                    shareTransaction(transactionId);
                    break;
            }
        });
    });
}


function updatePagination() {
    const paginationContainer = document.querySelector('.transactions-pagination .pagination-buttons');
    if (!paginationContainer) return;
    
    // حساب عدد الصفحات
    const totalPages = Math.ceil(totalItems / itemsPerPage);
    
    // تحديث معلومات عرض النتائج
    const paginationInfo = document.querySelector('.transactions-pagination .pagination-info');
    if (paginationInfo) {
        const start = Math.min((currentPage - 1) * itemsPerPage + 1, totalItems);
        const end = Math.min(currentPage * itemsPerPage, totalItems);
        paginationInfo.textContent = `عرض ${start} إلى ${end} من إجمالي ${totalItems} معاملة`;
    }
    
    // إذا كان هناك صفحة واحدة فقط، أخفِ أزرار التنقل
    if (totalPages <= 1) {
        paginationContainer.innerHTML = '';
        return;
    }
    
    // إنشاء أزرار التنقل
    let paginationHTML = '';
    
    // زر الصفحة السابقة
    paginationHTML += `
        <button class="pagination-btn prev ${currentPage === 1 ? 'disabled' : ''}" 
            ${currentPage === 1 ? 'disabled' : ''}>
            <i class="fas fa-chevron-right"></i>
        </button>
    `;
    
    // تحديد نطاق الصفحات للعرض
    let startPage = Math.max(1, currentPage - 2);
    let endPage = Math.min(totalPages, startPage + 4);
    
    // إعادة ضبط النطاق إذا كان أقل من 5 صفحات
    if (endPage - startPage < 4) {
        startPage = Math.max(1, endPage - 4);
    }
    
    // إضافة زر الصفحة الأولى إذا لم تكن مرئية
    if (startPage > 1) {
        paginationHTML += `
            <button class="pagination-btn" data-page="1">1</button>
            ${startPage > 2 ? '<span class="pagination-ellipsis">...</span>' : ''}
        `;
    }
    
    // إضافة أزرار الصفحات
    for (let i = startPage; i <= endPage; i++) {
        paginationHTML += `
            <button class="pagination-btn ${i === currentPage ? 'active' : ''}" 
                data-page="${i}">${i}</button>
        `;
    }
    
    // إضافة زر الصفحة الأخيرة إذا لم تكن مرئية
    if (endPage < totalPages) {
        paginationHTML += `
            ${endPage < totalPages - 1 ? '<span class="pagination-ellipsis">...</span>' : ''}
            <button class="pagination-btn" data-page="${totalPages}">${totalPages}</button>
        `;
    }
    
    // زر الصفحة التالية
    paginationHTML += `
        <button class="pagination-btn next ${currentPage === totalPages ? 'disabled' : ''}" 
            ${currentPage === totalPages ? 'disabled' : ''}>
            <i class="fas fa-chevron-left"></i>
        </button>
    `;
    
    paginationContainer.innerHTML = paginationHTML;
    
    // إضافة مستمعات الأحداث لأزرار الصفحات
    document.querySelectorAll('.pagination-btn:not(.disabled)').forEach(btn => {
        btn.addEventListener('click', function() {
            if (this.classList.contains('prev')) {
                currentPage--;
            } else if (this.classList.contains('next')) {
                currentPage++;
            } else {
                currentPage = parseInt(this.getAttribute('data-page'));
            }
            
            loadTransactions();
            
            // تمرير إلى الأعلى
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });
}


function viewTransactionDetails(transactionId) {
    // عرض مؤشر التحميل
    const modal = document.getElementById('transactionDetailModal');
    if (!modal) return;
    
    // إظهار المودال
    modal.style.display = 'flex';
    
    // عرض مؤشر التحميل في المودال
    const detailBody = modal.querySelector('.transaction-detail-body');
    if (detailBody) {
        detailBody.innerHTML = `
            <div class="loading-spinner">
                <i class="fas fa-spinner fa-spin"></i>
                <p>جاري تحميل تفاصيل المعاملة...</p>
            </div>
        `;
    }
    
    // تحميل تفاصيل المعاملة
    fetch(`transaction-detail.php?id=${transactionId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('حدث خطأ في الاتصال بالسيرفر');
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                // عرض تفاصيل المعاملة
                renderTransactionDetails(data.data);
            } else {
                throw new Error(data.message || 'حدث خطأ أثناء تحميل تفاصيل المعاملة');
            }
        })
        .catch(error => {
            console.error('Error loading transaction details:', error);
            // عرض رسالة الخطأ
            if (detailBody) {
                detailBody.innerHTML = `
                    <div class="error-message">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p>خطأ: ${error.message}</p>
                    </div>
                `;
            }
        });
}


function printTransaction(transactionId) {
    // فتح مودال الطباعة أو نافذة جديدة مع واجهة الطباعة
    window.open(`print_invoice.php?id=${transactionId}`, '_blank');
}


function downloadTransactionPDF(transactionId) {
    // إعداد الطلب لتحميل PDF
    fetch(`generate_pdf.php?id=${transactionId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('حدث خطأ في الاتصال بالسيرفر');
            }
            return response.blob();
        })
        .then(blob => {
            // إنشاء رابط لتحميل الملف
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `فاتورة-${transactionId}.pdf`;
            document.body.appendChild(a);
            a.click();
            
            // تنظيف
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        })
        .catch(error => {
            console.error('Error downloading PDF:', error);
            alert(`حدث خطأ أثناء تحميل ملف PDF: ${error.message}`);
        });
}


function shareTransaction(transactionId) {
    // فتح المشاركة عبر واتساب
    fetch(`transaction-detail.php?id=${transactionId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const transaction = data.data;
                
                // إنشاء رسالة للمشاركة
                let message = `*فاتورة رقم: ${transaction.invoice_number}*\n`;
                message += `التاريخ: ${transaction.created_date}\n`;
                message += `العميل: ${transaction.customer_name || 'عميل نقدي'}\n`;
                message += `المبلغ: ${transaction.total_amount} جنيه\n`;
                message += `الحالة: ${transaction.status_text}\n\n`;
                
                // إضافة المنتجات للرسالة
                if (transaction.items && transaction.items.length > 0) {
                    message += "*المنتجات:*\n";
                    transaction.items.forEach((item, index) => {
                        message += `${index + 1}. ${item.product_name} (${item.quantity}) - ${item.total_price} جنيه\n`;
                    });
                }
                
                // فتح واتساب
                const whatsappUrl = `https://wa.me/?text=${encodeURIComponent(message)}`;
                window.open(whatsappUrl, '_blank');
            } else {
                throw new Error(data.message || 'حدث خطأ أثناء تحميل بيانات المعاملة');
            }
        })
        .catch(error => {
            console.error('Error sharing transaction:', error);
            alert(`حدث خطأ أثناء مشاركة المعاملة: ${error.message}`);
        });
}


function renderTransactionDetails(transaction) {
    const detailBody = document.querySelector('.transaction-detail-body');
    if (!detailBody) return;
    
    // تنسيق تاريخ المعاملة
    const createdDate = transaction.created_date;
    const createdTime = transaction.created_time;
    
    // إعداد فئة حالة الدفع
    const statusClass = `status-${transaction.payment_status}`;
    
    // بناء المحتوى
    let detailHTML = `
        <div class="transaction-detail-info">
            <div class="transaction-info-group">
                <div class="transaction-info-label">رقم الفاتورة</div>
                <div class="transaction-info-value">${transaction.invoice_number}</div>
            </div>
            <div class="transaction-info-group">
                <div class="transaction-info-label">التاريخ</div>
                <div class="transaction-info-value">${createdDate} - ${createdTime}</div>
            </div>
            <div class="transaction-info-group">
                <div class="transaction-info-label">العميل</div>
                <div class="transaction-info-value">${transaction.customer_name || 'عميل نقدي'}</div>
            </div>
            <div class="transaction-info-group">
                <div class="transaction-info-label">طريقة الدفع</div>
                <div class="transaction-info-value">${transaction.payment_method_text}</div>
            </div>
            <div class="transaction-info-group">
                <div class="transaction-info-label">حالة الدفع</div>
                <div class="transaction-info-value">
                    <span class="transaction-status ${statusClass}">${transaction.status_text}</span>
                </div>
            </div>
        </div>
        
        <h4>المنتجات</h4>
        <table class="transaction-items-table">
            <thead>
                <tr>
                    <th>المنتج</th>
                    <th>الكمية</th>
                    <th>السعر</th>
                    <th>الإجمالي</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    // إضافة المنتجات
    if (transaction.items && transaction.items.length > 0) {
        transaction.items.forEach(item => {
            detailHTML += `
                <tr>
                    <td>${item.product_name}</td>
                    <td>${item.quantity}</td>
                    <td>${item.unit_price} جنيه</td>
                    <td>${item.total_price} جنيه</td>
                </tr>
            `;
        });
    } else {
        detailHTML += `
            <tr>
                <td colspan="4" class="empty-message">لا توجد منتجات</td>
            </tr>
        `;
    }
    
    detailHTML += `
            </tbody>
        </table>
        
        <div class="transaction-summary">
            <div class="summary-row">
                <div class="summary-label">المجموع</div>
                <div class="summary-value">${transaction.subtotal_amount} جنيه</div>
            </div>
            <div class="summary-row">
                <div class="summary-label">الخصم</div>
                <div class="summary-value">${transaction.discount_amount} جنيه</div>
            </div>
            <div class="summary-row summary-total">
                <div class="summary-label">الإجمالي</div>
                <div class="summary-value">${transaction.total_amount} جنيه</div>
            </div>
        </div>
    `;
    
    // إضافة معلومات التقسيط إذا كانت موجودة
    if (transaction.payment_method === 'installment' && transaction.installments) {
        detailHTML += `
            <h4>الأقساط</h4>
            <table class="transaction-items-table">
                <thead>
                    <tr>
                        <th>رقم القسط</th>
                        <th>تاريخ الاستحقاق</th>
                        <th>المبلغ</th>
                        <th>الحالة</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        transaction.installments.forEach(installment => {
            const statusClass = installment.status === 'paid' ? 'status-paid' : 
                               (installment.status === 'partial' ? 'status-partial' : 'status-unpaid');
            
            const statusText = installment.status === 'paid' ? 'مدفوع' : 
                              (installment.status === 'partial' ? 'جزئي' : 'غير مدفوع');
            
            detailHTML += `
                <tr>
                    <td>القسط ${installment.installment_number}</td>
                    <td>${installment.formatted_due_date}</td>
                    <td>${installment.amount} جنيه</td>
                    <td><span class="transaction-status ${statusClass}">${statusText}</span></td>
                </tr>
            `;
        });
        
        detailHTML += `
                </tbody>
            </table>
            
            <div class="transaction-summary">
                <div class="summary-row">
                    <div class="summary-label">المدفوع</div>
                    <div class="summary-value">${transaction.total_paid || 0} جنيه</div>
                </div>
                <div class="summary-row summary-total">
                    <div class="summary-label">المتبقي</div>
                    <div class="summary-value">${transaction.total_remaining || 0} جنيه</div>
                </div>
            </div>
        `;
    }
    
    detailBody.innerHTML = detailHTML;
    
    // تعيين بيانات المعاملة للأزرار في المودال
    const modalFooter = document.querySelector('.transaction-detail-footer');
    if (modalFooter) {
        modalFooter.innerHTML = `
            <button class="detail-action-btn btn-print" data-id="${transaction.id}">
                <i class="fas fa-print"></i> طباعة
            </button>
            <button class="detail-action-btn btn-download" data-id="${transaction.id}">
                <i class="fas fa-file-pdf"></i> تحميل PDF
            </button>
            <button class="detail-action-btn btn-share" data-id="${transaction.id}">
                <i class="fas fa-share-alt"></i> مشاركة
            </button>
        `;
        
        // إضافة مستمعات الأحداث للأزرار
        modalFooter.querySelector('.btn-print').addEventListener('click', function() {
            printTransaction(this.getAttribute('data-id'));
        });
        
        modalFooter.querySelector('.btn-download').addEventListener('click', function() {
            downloadTransactionPDF(this.getAttribute('data-id'));
        });
        
        modalFooter.querySelector('.btn-share').addEventListener('click', function() {
            shareTransaction(this.getAttribute('data-id'));
        });
    }
}


function loadDashboardMetrics() {
    fetch('dashboard-data.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('حدث خطأ في الاتصال بالسيرفر');
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                updateDashboardMetrics(data.data);
            } else {
                throw new Error(data.message || 'حدث خطأ أثناء تحميل بيانات لوحة المعلومات');
            }
        })
        .catch(error => {
            console.error('Error loading dashboard metrics:', error);
        });
}


function updateDashboardMetrics(metrics) {
    // تحديث عدد المبيعات
    const salesCount = document.querySelector('.metric-sales-count .metric-value');
    if (salesCount) {
        salesCount.textContent = metrics.sales_count || 0;
    }
    
    // تحديث صافي الدخل
    const netIncome = document.querySelector('.metric-net-income .metric-value');
    if (netIncome) {
        netIncome.textContent = (metrics.net_income || 0) + ' جنيه';
    }
    
    // تحديث عدد التقسيطات
    const installmentsCount = document.querySelector('.metric-installments-count .metric-value');
    if (installmentsCount) {
        installmentsCount.textContent = metrics.installments_count || 0;
    }
    
    // تحديث عدد المنتجات
    const productsCount = document.querySelector('.metric-products-count .metric-value');
    if (productsCount) {
        productsCount.textContent = metrics.products_count || 0;
    }
}