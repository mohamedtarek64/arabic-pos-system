<div id="sidebar" class="modern-sidebar">
  
  <div class="sidebar-profile">
    <div class="profile-image">
    </div>
    <div class="profile-info">
      <h3>محمد</h3>
      <span class="profile-role">مدير النظام</span>
    </div>
    <div class="online-status">
      <span class="status-dot"></span>
      <span class="status-text">متصل</span>
    </div>
  </div>
  
  
  <div class="sidebar-nav-container">
    <span class="nav-section-title">القائمة الرئيسية</span>
    <nav class="sidebar-nav">
      <ul>
        <li class="active">
          <a href="main.php">
            <div class="menu-icon"><i class="fas fa-home"></i></div>
            <span>الرئيسية</span>
          </a>
        </li>
        
        <li>
          <a href="cashier.php">
            <div class="menu-icon"><i class="fas fa-cash-register"></i></div>
            <span>الكاشير</span>
          </a>
        </li>
        
        <li class="dropdown">
          <a href="javascript:void(0)">
            <div class="menu-icon"><i class="fas fa-box"></i></div>
            <span>المنتجات</span>
            <div class="menu-arrow"><i class="fas fa-chevron-left"></i></div>
          </a>
          <ul class="dropdown-menu">
            <li><a href="products.php?section=all">جميع المنتجات</a></li>
            <li><a href="products.php?section=add">إضافة منتج</a></li>
            <li><a href="products.php?section=categories">التصنيفات</a></li>
          </ul>
        </li>
        
        <li class="dropdown">
          <a href="javascript:void(0)">
            <div class="menu-icon"><i class="fas fa-shopping-cart"></i></div>
            <span>الطلبات</span>
            <div class="menu-arrow"><i class="fas fa-chevron-left"></i></div>
          </a>
          <ul class="dropdown-menu">
            <li><a href="orders.php?section=new">الطلبات الجديدة</a></li>
            <li><a href="orders.php?section=processing">قيد المعالجة</a></li>
            <li><a href="orders.php?section=completed">مكتملة</a></li>
          </ul>
        </li>
        
        <li class="dropdown">
          <a href="javascript:void(0)">
            <div class="menu-icon"><i class="fas fa-users"></i></div>
            <span>العملاء</span>
            <div class="menu-arrow"><i class="fas fa-chevron-left"></i></div>
          </a>
          <ul class="dropdown-menu">
            <li><a href="customers.php?section=all">جميع العملاء</a></li>
            <li><a href="customers.php?section=add">إضافة عميل</a></li>
            <li><a href="customers.php?section=groups">مجموعات العملاء</a></li>
            <li><a href="customers.php?section=loyalty">برنامج الولاء</a></li>
          </ul>
        </li>
      </ul>
    </nav>
    
    <span class="nav-section-title">التقارير والإدارة</span>
    <nav class="sidebar-nav">
      <ul>
        <li class="dropdown">
          <a href="javascript:void(0)">
            <div class="menu-icon"><i class="fas fa-chart-bar"></i></div>
            <span>التقارير</span>
            <div class="menu-arrow"><i class="fas fa-chevron-left"></i></div>
          </a>
          <ul class="dropdown-menu">
            <li><a href="reports.php?section=sellers">تقارير البائعين</a>
              <ul class="sub-dropdown-menu">
                <li><a href="reports.php?section=sellers&type=daily">التقارير اليومية</a></li>
                <li><a href="reports.php?section=sellers&type=monthly">التقارير الشهرية</a></li>
                <li><a href="reports.php?section=sellers&type=yearly">التقارير السنوية</a></li>
                <li><a href="reports.php?section=sellers&type=performance">تقييم الأداء</a></li>
              </ul>
            </li>
            <li><a href="reports.php?section=workers">تقارير الموظفين</a></li>
            <li><a href="reports.php?section=suppliers">تقارير الموردين</a></li>
          </ul>
        </li>
        
        <li class="dropdown">
          <a href="javascript:void(0)">
            <div class="menu-icon"><i class="fas fa-truck"></i></div>
            <span>الموردين</span>
            <div class="menu-arrow"><i class="fas fa-chevron-left"></i></div>
          </a>
          <ul class="dropdown-menu">
            <li><a href="suppliers.php?section=all">جميع الموردين</a></li>
            <li><a href="suppliers.php?section=add">إضافة مورد</a></li>
            <li><a href="suppliers.php?section=rate">تقييم الموردين</a></li>
          </ul>
        </li>
        
        <li class="dropdown active">
          <a href="javascript:void(0)">
            <div class="menu-icon"><i class="fas fa-money-check-alt"></i></div>
            <span>الأقساط</span>
            <div class="menu-arrow"><i class="fas fa-chevron-left"></i></div>
          </a>
          <ul class="dropdown-menu" style="display: block;">
            <li><a href="installments.php">نظام الأقساط</a></li>
            <li><a href="all_customers.php">العملاء والحسابات</a></li>
            <li><a href="add_installment.php">إضافة قسط جديد</a></li>
            <li><a href="late_installments.php">الأقساط المتأخرة</a></li>
            <li><a href="installment_reports.php">تقارير وإحصائيات</a></li>
            <li><a href="installment_settings.php">الإعدادات</a></li>
          </ul>
        </li>
      </ul>
    </nav>
    
    <span class="nav-section-title">النظام</span>
    <nav class="sidebar-nav">
      <ul>
        <li>
          <a href="settings.php">
            <div class="menu-icon"><i class="fas fa-cog"></i></div>
            <span>الإعدادات</span>
          </a>
        </li>
        <li>
          <a href="help.php">
            <div class="menu-icon"><i class="fas fa-question-circle"></i></div>
            <span>المساعدة</span>
          </a>
        </li>
      </ul>
    </nav>
  </div>
  
  
  <div class="sidebar-footer">
    <div class="system-info">
      <div class="info-item">
        <i class="fas fa-database"></i>
        <span>محدث</span>
      </div>
      <div class="info-item">
        <i class="fas fa-wifi"></i>
        <span>متصل</span>
      </div>
    </div>
    <a href="logout.php" class="logout-btn">
      <i class="fas fa-sign-out-alt"></i>
      <span>تسجيل الخروج</span>
    </a>
  </div>
</div>
