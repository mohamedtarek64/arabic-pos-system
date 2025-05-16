// ملف لتحديث بيانات لوحة التحكم باستخدام AJAX

document.addEventListener('DOMContentLoaded', function() {
    // تحديث البيانات عند تحميل الصفحة
    updateDashboardData();
    
    // تحديث البيانات كل دقيقة (60000 مللي ثانية)
    setInterval(updateDashboardData, 60000);
    
    // ضبط التاريخ في شريط التصفية
    setupDateRange();
    
    // إعداد مستمعات الأحداث للتاريخ وفلاتر أخرى
    setupFilterListeners();
});

// دالة لتحديث بيانات لوحة التحكم
function updateDashboardData() {
    // إظهار علامة التحميل إذا لزم الأمر
    
    // طلب AJAX لجلب البيانات
    fetch('dashboard-data.php?format=json')
        .then(response => {
            if (!response.ok) {
                throw new Error('فشل في تحديث البيانات');
            }
            return response.json();
        })
        .then(data => {
            console.log('تم استلام بيانات اللوحة:', data);
            
            // تحديث قيمة المخزون
            updateInventoryValue(data.inventory_value);
            
            // تحديث المبالغ المحصلة
            updateCollectedAmounts(data.collected_amounts);
            
            // تحديث صافي الدخل
            updateNetIncome(data.net_income, data.last_month.net_income);
            
            // تحديث عمليات البيع
            updateSalesOperations(data.sales_operations, data.last_month.sales_operations);
            
            // تحديث المبيعات
            updateSalesTotal(data.sales_total, data.last_month.sales_total);
            
            // تحديث آخر النشاطات
            if (data.recent_activities && data.recent_activities.length > 0) {
                updateRecentActivities(data.recent_activities);
            }
            
            // تحديث آخر المبيعات
            if (data.recent_sales && data.recent_sales.length > 0) {
                updateRecentSales(data.recent_sales);
            }
            
            // إخفاء علامة التحميل
        })
        .catch(error => {
            console.error('خطأ في تحديث البيانات:', error);
            // إخفاء علامة التحميل وإظهار رسالة خطأ إذا لزم الأمر
        });
}

// دالة لتحديث قيمة المخزون
function updateInventoryValue(value) {
    const inventoryValueElement = document.querySelector('.card-title:contains("قيمة المخزون اليوم")').closest('.card').querySelector('.value');
    if (inventoryValueElement) {
        inventoryValueElement.innerHTML = formatCurrency(value) + ' <span>جنيه</span>';
    }
}

// دالة لتحديث المبالغ المحصلة
function updateCollectedAmounts(amounts) {
    const card = document.querySelector('.card-title:contains("المبالغ المحصلة")').closest('.card');
    
    if (card) {
        // تحديث النقدي
        const cashRow = card.querySelector('.row:nth-child(1)');
        if (cashRow) {
            const cashValue = cashRow.querySelector('span:last-child');
            if (cashValue) cashValue.textContent = formatCurrency(amounts.cash) + ' جنيه';
        }
        
        // تحديث بطاقة ائتمانية
        const cardRow = card.querySelector('.row:nth-child(2)');
        if (cardRow) {
            const cardValue = cardRow.querySelector('span:last-child');
            if (cardValue) cardValue.textContent = formatCurrency(amounts.card) + ' جنيه';
        }
        
        // تحديث أجل
        const deferredRow = card.querySelector('.row:nth-child(3)');
        if (deferredRow) {
            const deferredValue = deferredRow.querySelector('span:last-child');
            if (deferredValue) deferredValue.textContent = formatCurrency(amounts.deferred) + ' جنيه';
        }
        
        // تحديث إجمالي المبالغ
        const totalRow = card.querySelector('.row.total');
        if (totalRow) {
            const totalValue = totalRow.querySelector('span:last-child');
            if (totalValue) totalValue.textContent = formatCurrency(amounts.total) + ' جنيه';
        }
    }
}

// دالة لتحديث صافي الدخل
function updateNetIncome(value, lastMonthValue) {
    const card = document.querySelector('.card h3:contains("صافي الدخل")').closest('.card');
    
    if (card) {
        const valueElement = card.querySelector('.value');
        if (valueElement) {
            valueElement.innerHTML = formatCurrency(value) + ' <span>جنيه</span>';
        }
        
        const descElement = card.querySelector('.desc');
        if (descElement) {
            const lastMonthSpan = descElement.querySelector('span');
            if (lastMonthSpan) {
                lastMonthSpan.textContent = formatCurrency(lastMonthValue) + ' جنيه';
            }
        }
    }
}

// دالة لتحديث عمليات البيع
function updateSalesOperations(value, lastMonthValue) {
    const card = document.querySelector('.card h3:contains("عمليات البيع")').closest('.card');
    
    if (card) {
        const valueElement = card.querySelector('.value');
        if (valueElement) {
            valueElement.textContent = value;
        }
        
        const descElement = card.querySelector('.desc');
        if (descElement) {
            const lastMonthSpan = descElement.querySelector('span');
            if (lastMonthSpan) {
                lastMonthSpan.textContent = lastMonthValue;
            }
        }
    }
}

// دالة لتحديث المبيعات
function updateSalesTotal(value, lastMonthValue) {
    const card = document.querySelector('.card h3:contains("المبيعات (شامل الضريبة)")').closest('.card');
    
    if (card) {
        const valueElement = card.querySelector('.value');
        if (valueElement) {
            valueElement.innerHTML = formatCurrency(value) + ' <span>جنيه</span>';
        }
        
        const descElement = card.querySelector('.desc');
        if (descElement) {
            const lastMonthSpan = descElement.querySelector('span');
            if (lastMonthSpan) {
                lastMonthSpan.textContent = formatCurrency(lastMonthValue) + ' جنيه';
            }
        }
    }
}

// دالة لتحديث آخر النشاطات
function updateRecentActivities(activities) {
    const activityLog = document.querySelector('.activity-log-card .card-content');
    
    if (activityLog && activities.length > 0) {
        // مسح المحتوى الحالي
        activityLog.innerHTML = '';
        
        // إضافة كل نشاط
        activities.forEach(activity => {
            const activityDiv = document.createElement('div');
            activityDiv.style.display = 'flex';
            activityDiv.style.alignItems = 'center';
            activityDiv.style.justifyContent = 'space-between';
            activityDiv.style.marginBottom = '16px';
            
            // تحويل التاريخ إلى صيغة مناسبة
            const activityDate = new Date(activity.created_at);
            const timeAgo = getTimeAgo(activityDate);
            
            activityDiv.innerHTML = `
                <div style="flex: 1;">
                    <span style="font-weight: 700; color: #222; font-size: 1.05em;">${activity.user_name} ${activity.description}</span>
                    <span style="margin: 0 8px; color: #7b8a9a; font-size: 0.95em;">${activity.activity_type}</span>
                </div>
                <span style="color: #7b8a9a; font-size: 0.92em;">${timeAgo}</span>
            `;
            
            activityLog.appendChild(activityDiv);
        });
        
        // إضافة زر عرض الكل
        const viewAllDiv = document.createElement('div');
        viewAllDiv.style.display = 'flex';
        viewAllDiv.style.alignItems = 'center';
        viewAllDiv.style.justifyContent = 'space-between';
        viewAllDiv.style.marginTop = '20px';
        
        viewAllDiv.innerHTML = `
            <button class="view-all-btn">عرض الكل ←</button>
        `;
        
        activityLog.appendChild(viewAllDiv);
    }
}

// دالة لتحديث آخر المبيعات
function updateRecentSales(sales) {
    const salesLog = document.querySelector('.recent-sales-card .card-content');
    
    if (salesLog && sales.length > 0) {
        // مسح المحتوى الحالي
        salesLog.innerHTML = '';
        
        // إضافة كل عملية بيع
        sales.forEach(sale => {
            const saleDiv = document.createElement('div');
            saleDiv.style.display = 'flex';
            saleDiv.style.alignItems = 'center';
            saleDiv.style.justifyContent = 'space-between';
            saleDiv.style.marginBottom = '16px';
            
            // تحويل التاريخ إلى صيغة مناسبة
            const saleDate = new Date(sale.sale_date);
            const formattedDate = formatDate(saleDate);
            
            const customerName = sale.customer_name || 'عميل نقدي';
            
            saleDiv.innerHTML = `
                <div style="flex: 1;">
                    <span style="font-weight: 700; color: #222; font-size: 1.05em;">فاتورة #${sale.id}</span>
                    <span style="margin: 0 8px; color: #7b8a9a; font-size: 0.95em;">${customerName}</span>
                </div>
                <div>
                    <span style="font-weight: 700; color: #2196f3; font-size: 1.05em;">${formatCurrency(sale.total_amount)} جنيه</span>
                    <span style="display: block; color: #7b8a9a; font-size: 0.92em; text-align: left;">${formattedDate}</span>
                </div>
            `;
            
            salesLog.appendChild(saleDiv);
        });
        
        // إضافة زر عرض الكل
        const viewAllDiv = document.createElement('div');
        viewAllDiv.style.display = 'flex';
        viewAllDiv.style.alignItems = 'center';
        viewAllDiv.style.justifyContent = 'flex-end';
        viewAllDiv.style.marginTop = '20px';
        
        viewAllDiv.innerHTML = `
            <button class="view-all-btn">عرض الكل ←</button>
        `;
        
        salesLog.appendChild(viewAllDiv);
    } else if (salesLog) {
        // في حالة عدم وجود مبيعات
        salesLog.innerHTML = `
            <div style="color: #7b8a9a; text-align: center; margin-top: 60px; font-size: 1.05em;">لا توجد مبيعات حديثة</div>
            <div style="text-align: left; margin-top: 60px;">
                <button class="view-all-btn">عرض الكل ←</button>
            </div>
        `;
    }
}

// دالة مساعدة لتنسيق العملة
function formatCurrency(value) {
    // التحقق من القيمة ومعالجتها
    const num = parseFloat(value) || 0;
    return num.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

// دالة مساعدة لحساب الوقت المنقضي
function getTimeAgo(date) {
    const now = new Date();
    const diffMs = now - date;
    const diffSec = Math.floor(diffMs / 1000);
    const diffMin = Math.floor(diffSec / 60);
    const diffHour = Math.floor(diffMin / 60);
    const diffDay = Math.floor(diffHour / 24);
    
    if (diffDay > 30) {
        return 'منذ أكثر من شهر';
    } else if (diffDay > 0) {
        return `منذ ${diffDay} يوم`;
    } else if (diffHour > 0) {
        return `منذ ${diffHour} ساعة`;
    } else if (diffMin > 0) {
        return `منذ ${diffMin} دقيقة`;
    } else {
        return 'منذ لحظات';
    }
}

// دالة مساعدة لتنسيق التاريخ
function formatDate(date) {
    return date.toLocaleDateString('ar-EG', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// دالة لضبط نطاق التاريخ الافتراضي
function setupDateRange() {
    const dateRangeInput = document.querySelector('.date-range');
    if (dateRangeInput) {
        const today = new Date();
        const formattedDate = today.toLocaleDateString('en-US', {
            month: '2-digit',
            day: '2-digit',
            year: 'numeric'
        });
        
        dateRangeInput.value = `${formattedDate} - ${formattedDate}`;
    }
}

// دالة لإعداد مستمعات الأحداث للفلاتر
function setupFilterListeners() {
    // مستمع لتغيير التاريخ
    document.querySelectorAll('.calendar-table td').forEach(td => {
        td.addEventListener('click', function() {
            // تحديث البيانات بناءً على التاريخ المختار
            updateDashboardData();
        });
    });
    
    // مستمع لتغيير الفرع
    const branchSelect = document.querySelector('select');
    if (branchSelect) {
        branchSelect.addEventListener('change', function() {
            // تحديث البيانات بناءً على الفرع المختار
            updateDashboardData();
        });
    }
}

// إضافة طريقة contains للـ querySelector
if (!Element.prototype.querySelectorAll) {
    Element.prototype.querySelectorAll = function(selector) {
        return document.querySelectorAll(selector);
    };
}

// إضافة طريقة للبحث عن النص داخل العناصر
if (!Element.prototype.querySelector) {
    Element.prototype.querySelector = function(selector) {
        return document.querySelector(selector);
    };
}

// طريقة مساعدة للبحث عن النص في العناصر
Element.prototype.querySelectorContains = function(selector, text) {
    const elements = this.querySelectorAll(selector);
    for (let i = 0; i < elements.length; i++) {
        if (elements[i].textContent.includes(text)) {
            return elements[i];
        }
    }
    return null;
};

// إضافة :contains للـ querySelector
document.querySelector = (function(_querySelector) {
    return function(selector) {
        try {
            // تجربة السيليكتور الأصلي أولاً
            return _querySelector.call(this, selector);
        } catch (error) {
            // إذا كان السيليكتور يحتوي على :contains
            if (selector.includes(':contains(')) {
                const parts = selector.split(':contains(');
                const baseSelector = parts[0].trim();
                let searchText = parts[1].slice(0, -1); // إزالة القوس الأخير
                
                // إزالة علامات الاقتباس إذا كانت موجودة
                if ((searchText.startsWith('"') && searchText.endsWith('"')) || 
                    (searchText.startsWith("'") && searchText.endsWith("'"))) {
                    searchText = searchText.slice(1, -1);
                }
                
                const allElements = document.querySelectorAll(baseSelector);
                for (let i = 0; i < allElements.length; i++) {
                    if (allElements[i].textContent.includes(searchText)) {
                        return allElements[i];
                    }
                }
                return null;
            }
            
            // وإلا إعادة الخطأ الأصلي
            throw error;
        }
    };
})(document.querySelector); 