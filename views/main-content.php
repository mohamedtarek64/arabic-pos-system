
<button class="add-sale-btn">+ أضف عملية بيع</button>


<div class="dashboard-container">
    
    <div class="dashboard-filters">
        <button class="calendar-toggle-btn"><i class="fas fa-calendar"></i></button>
        <input type="text" class="date-range" value="05/10/2025 - 05/10/2025" readonly>
        <select>
            <option>الكل: الفروع</option>
            <option>فرع 1</option>
            <option>فرع 2</option>
        </select>
    </div>

    
    <div class="dashboard-row">
        <div class="card">
            <h3>صافي الدخل</h3>
            <div class="value">0.00 <span>ر.س</span></div>
            <div class="desc">الشهر الماضي <span>0.00 ر.س</span></div>
            <a href="#" class="button">عرض المصروفات</a>
        </div>
        <div class="card">
            <h3>عمليات البيع</h3>
            <div class="value">0</div>
            <div class="desc">الشهر الماضي <span>0</span></div>
            <a href="#" class="button">عرض التقارير</a>
        </div>
        <div class="card">
            <h3>المبيعات (شامل الضريبة)</h3>
            <div class="value">0.00 <span>ر.س</span></div>
            <div class="desc">الشهر الماضي <span>0.00 ر.س</span></div>
        </div>
    </div>

    
    <div class="sales-tabs">
        <ul class="tabs-list">
            <li class="tab-item active" data-tab="tab1">اليوم</li>
            <li class="tab-item" data-tab="tab2">الفئات</li>
            <li class="tab-item" data-tab="tab3">البائعين</li>
            <li class="tab-item" data-tab="tab4">المنتجات</li>
            <li class="tab-item" data-tab="tab5">الفروع</li>
        </ul>
        <div class="tabs-content">
            <div class="tab-content active" id="tab1">
                <div class="empty-chart-container">
                    <img src="empty-chart.svg" alt="لا يوجد بيانات" class="empty-chart-img">
                    <p class="empty-message">لا يوجد إحصائيات متوفرة</p>
                    <p class="empty-message">قم بتسجيل مبيعاتك حتى تتمكن من عرض البيانات</p>
                </div>
            </div>
            <div class="tab-content" id="tab2">محتوى الفئات</div>
            <div class="tab-content" id="tab3">محتوى البائعين</div>
            <div class="tab-content" id="tab4">محتوى المنتجات</div>
            <div class="tab-content" id="tab5">محتوى الفروع</div>
        </div>
    </div>

    
    <div class="kpi-container">
        <div class="kpi-card">
            <div class="kpi-icon">📈</div>
            <div class="kpi-title">عدد العملاء الجدد</div>
            <div class="kpi-value">15</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon">🧾</div>
            <div class="kpi-title">عدد الفواتير</div>
            <div class="kpi-value">120</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon">📦</div>
            <div class="kpi-title">منتجات منخفضة</div>
            <div class="kpi-value">8</div>
        </div>
    </div>

    
    <div class="kpi-charts">
        <canvas id="chart1"></canvas>
    </div>
</div> 