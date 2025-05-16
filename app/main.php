<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم الرئيسية</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/sidebar-custom.css">
    <link rel="stylesheet" href="../assets/css/horizontal-nav.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #3a5a8c;
            --primary-light: #5d7eaf;
            --primary-dark: #2c4870;
            --secondary-color: #e7795c;
            --secondary-light: #f1a18c;
            --secondary-dark: #c35b3e;
            --success-color: #6ca975;
            --warning-color: #e7c35c;
            --danger-color: #dc6868;
            --info-color: #60b3d7;
            --bg-color: #f8f9fa;
            --card-color: #ffffff;
            --text-color: #3a4750;
            --text-light: #5a6774;
            --text-muted: #8d99a6;
            --border-color: #e9ecef;
            --border-radius: 10px;
            --shadow: 0 4px 15px rgba(0,0,0,0.05);
            --shadow-lg: 0 8px 20px rgba(0,0,0,0.08);
            --transition: all 0.3s ease;
            --sidebar-width: 280px;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Cairo', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            font-size: 18px;
            direction: rtl;
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }
        
        /* تنسيق هيكل الصفحة */
        .main-container {
            display: flex;
            min-height: 100vh;
            flex-direction: column;
        }
        
        /* قائمة التنقل العلوية الحديثة */
        .modern-navbar {
            background-color: var(--primary-color);
            color: white;
            display: flex;
            flex-direction: column;
            width: 100%;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.15);
        }
        
        /* الشريط العلوي - معلومات المستخدم */
        .navbar-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 25px;
            background-color: var(--primary-dark);
        }
        
        .brand {
            display: flex;
            align-items: center;
            font-weight: bold;
            font-size: 1.7rem;
        }
        
        .brand i {
            margin-left: 12px;
            color: white;
            background-color: var(--secondary-color);
            width: 42px;
            height: 42px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            background-color: rgba(255, 255, 255, 0.1);
            padding: 10px 15px;
            border-radius: 30px;
            transition: var(--transition);
        }
        
        .user-info:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        .user-image {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background-color: var(--secondary-color);
            margin-left: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .user-image i {
            font-size: 22px;
        }
        
        .user-details {
            display: flex;
            flex-direction: column;
        }
        
        .user-name {
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        .user-role {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        /* شريط القائمة الرئيسية */
        .navbar-main {
            display: flex;
            justify-content: space-between;
            padding: 0 20px;
            background-color: var(--primary-color);
        }
        
        /* قائمة روابط التنقل */
        .nav-menu {
            display: flex;
            list-style: none;
            flex-wrap: wrap;
            margin: 0;
            padding: 0;
        }
        
        .nav-item {
            position: relative;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 18px;
            color: rgba(255,255,255,0.92);
            text-decoration: none;
            transition: var(--transition);
            white-space: nowrap;
            border-radius: 6px;
            margin: 5px 3px;
            font-size: 1.15rem;
        }
        
        .nav-link:hover {
            background-color: rgba(255,255,255,0.18);
            color: white;
            transform: translateY(-2px);
        }
        
        .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.25);
            font-weight: 500;
            position: relative;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 15%;
            right: 15%;
            height: 3px;
            background-color: var(--secondary-color);
            border-radius: 3px;
        }
        
        .nav-link i {
            margin-left: 10px;
            font-size: 1.25rem;
            width: 28px;
            text-align: center;
        }
        
        /* القوائم المنسدلة */
        .dropdown-menu {
            position: absolute;
            top: 120%;
            right: 0;
            background-color: #3a5a8c;
            min-width: 240px;
            box-shadow: var(--shadow-lg);
            border-radius: 12px;
            display: none;
            z-index: 100;
            padding: 12px;
                opacity: 0;
            transform: translateY(15px);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            border-top: 3px solid var(--secondary-color);
        }
        
        .dropdown-menu a {
            display: flex;
            align-items: center;
            padding: 14px 16px;
            color: white;
            text-decoration: none;
            transition: var(--transition);
            border-radius: 8px;
            margin: 4px 0;
            font-size: 1.05rem;
        }
        
        .dropdown-menu a:hover {
            background-color: rgba(255, 255, 255, 0.15);
            color: white;
            transform: translateX(-5px);
        }
        
        .dropdown-menu a::before {
            content: '';
            width: 6px;
            height: 6px;
            background-color: rgba(255, 255, 255, 0.7);
            border-radius: 50%;
            margin-left: 10px;
            transition: var(--transition);
        }
        
        .dropdown-menu a:hover::before {
            background-color: white;
            transform: scale(1.3);
        }
        
        .nav-item:hover .dropdown-menu {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }
        
        /* الأيقونات على اليمين */
        .navbar-actions {
            display: flex;
            align-items: center;
        }
        
        .action-btn {
            background: none;
            border: none;
            color: rgba(255,255,255,0.95);
            font-size: 1.3rem;
            padding: 10px;
            margin: 0 6px;
            cursor: pointer;
            transition: var(--transition);
            border-radius: 50%;
            width: 46px;
            height: 46px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .action-btn:hover {
            color: white;
            background-color: rgba(255,255,255,0.18);
            transform: translateY(-2px);
        }
        
        /* زر القائمة للجوال */
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.6rem;
            cursor: pointer;
            width: 50px;
            height: 50px;
            border-radius: 8px;
            align-items: center;
            justify-content: center;
            background-color: rgba(255,255,255,0.12);
        }
        
        /* المحتوى الرئيسي */
        .content-wrapper {
            flex: 1;
            padding: 25px;
            margin-right: 0;
            transition: var(--transition);
        }
        
        /* تنسيقات للمحتوى */
        .content {
            display: flex;
            flex-direction: column;
            max-width: 1500px;
            margin: 0 auto;
        }
        
        .content-header {
            order: 0;
            margin-bottom: 30px !important;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 30px 35px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        
        .content-header::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.12) 0%, transparent 70%);
            z-index: 1;
        }
        
        .content-header h1 {
            position: relative;
            z-index: 2;
            margin: 0;
            font-size: 2.2rem;
        }
        
        .date-filter {
            order: 1;
            margin-bottom: 30px;
            background-color: white;
            padding: 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .date-filter span {
            font-size: 1.15rem;
            margin-left: 15px;
            font-weight: 500;
        }
        
        .modern-datepicker {
            position: relative;
            display: inline-block;
            margin: 0 15px;
        }
        
        .datepicker-input {
            padding: 12px 45px 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-family: 'Cairo', sans-serif;
            transition: var(--transition);
            background-color: white;
            font-size: 1rem;
            min-width: 200px;
        }
        
        .datepicker-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(90, 120, 160, 0.15);
        }
        
        .datepicker-label {
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            color: var(--primary-color);
            font-size: 1.1rem;
        }
        
        .reset-btn {
            background-color: #f8f9fa;
            border: 1px solid var(--border-color);
            color: var(--text-color);
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            transition: var(--transition);
            font-size: 1.05rem;
            font-weight: 500;
        }
        
        .reset-btn i {
            margin-left: 10px;
            font-size: 1rem;
        }
        
        .reset-btn:hover {
            background-color: var(--bg-color);
            border-color: var(--primary-light);
            color: var(--primary-color);
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 25px;
            margin-bottom: 35px;
        }
        
        .charts-grid {
            order: 3;
        }
        
        .tables-grid {
            order: 4;
        }
        
        /* للشاشات الصغيرة */
        @media (max-width: 992px) {
            .dashboard-grid {
                grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            }
            
            .charts-grid {
                grid-template-columns: 1fr;
                gap: 25px;
            }
            
            .tables-grid {
                grid-template-columns: 1fr;
                gap: 25px;
            }
        }
        
        @media (max-width: 768px) {
            .content-wrapper {
                padding: 15px;
            }
            
            .navbar-main {
                flex-direction: column;
                padding: 0;
            }
            
            .nav-menu {
                flex-direction: column;
                width: 100%;
                display: none;
                padding: 10px;
            }
            
            .nav-menu.active {
                display: flex;
            }
            
            .mobile-menu-toggle {
            display: flex;
                padding: 10px;
                margin: 10px;
            }
            
            .dropdown-menu {
                position: static;
                box-shadow: none;
                border-top: none;
                background-color: rgba(255, 255, 255, 0.05);
                width: 100%;
                margin: 5px 0;
                padding: 5px;
            }
            
            .dropdown-menu a {
                color: rgba(255, 255, 255, 0.8);
                padding: 10px;
                font-size: 1rem;
            }
            
            .dropdown-menu a:hover {
                background-color: rgba(255, 255, 255, 0.1);
                color: white;
            }
            
            .dropdown-menu a::before {
                background-color: rgba(255, 255, 255, 0.5);
            }
            
            .navbar-actions {
                justify-content: center;
                width: 100%;
            padding: 10px 0;
                border-top: 1px solid rgba(255, 255, 255, 0.1);
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .content-header {
                flex-direction: column;
                align-items: flex-start;
                padding: 25px;
            }
            
            .content-header h1 {
                margin-bottom: 10px;
                font-size: 1.8rem;
            }
            
            .date-filter {
                flex-direction: column;
                align-items: flex-start;
                padding: 20px;
            }
            
            .date-filter span {
                margin-bottom: 15px;
            }
            
            .modern-datepicker {
            width: 100%;
                margin: 0 0 15px 0;
            }
            
            .datepicker-input {
                width: 100%;
            }
            
            .reset-btn {
                width: 100%;
                justify-content: center;
            }
            
            .card-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .card-title {
                margin-bottom: 10px;
            }
        }
        
        @media (max-width: 480px) {
            .navbar-top {
                flex-direction: column;
            text-align: center;
                padding: 15px;
            }
            
            .brand {
                margin-bottom: 15px;
                justify-content: center;
            }
            
            .user-info {
                width: 100%;
                justify-content: center;
            }
            
            .content-header h1 {
                font-size: 1.6rem;
            }
            
            .kpi-value {
                font-size: 1.6rem;
            }
        }
        
        /* ===== القائمة الجانبية (السايدبار) ===== */
        #sidebar, .modern-sidebar {
            background-color: #3a416f;
            color: white;
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            top: 0;
            right: 0;
            z-index: 1000;
            padding: 20px 0;
            overflow-y: auto;
            box-shadow: -2px 0 15px rgba(0,0,0,0.1);
            /* إزالة transition للسماح للجافاسكريبت بالتحكم الكامل */
        }
        
        /* إضافة فئة للإخفاء - JS */
        .sidebar-hidden {
            display: none !important;
        }
        
        /* إضافة فئة للإظهار - JS */
        .sidebar-visible {
            display: block !important;
        }
        
        /* ===== المحتوى الرئيسي ===== */
        .content-wrapper {
            margin-right: 280px;
            padding: 20px;
            min-height: 100vh;
            transition: margin-right 0.3s ease;
        }
        
        /* ===== زر التوجل ===== */
        #toggleSidebar {
            position: fixed;
            top: 15px;
            right: 15px;
            width: 50px;
            height: 50px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 24px;
            cursor: pointer;
            z-index: 1001;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
            transition: all 0.3s ease;
        }
        
        #toggleSidebar:hover {
            background-color: #303f9f;
        }
        
        /* السلوك على الشاشات الصغيرة */
        @media (max-width: 768px) {
            #sidebar, .modern-sidebar {
                transform: translateX(var(--sidebar-width)); /* مخفي افتراضيًا */
            }
            
            .content-wrapper {
                margin-right: 0;
            }
            
            /* حالة عند تفعيل السايدبار */
            body.sidebar-active #sidebar, 
            body.sidebar-active .modern-sidebar {
                transform: translateX(0);
            }
            
            #toggleSidebar {
                background-color: var(--primary-color);
            }
            
            body.sidebar-active #toggleSidebar {
                background-color: var(--secondary-color);
            }
        }
        
        /* نقل محتوى لوحة التحكم للأعلى */
        .content {
            display: flex;
            flex-direction: column;
        }
        
        .content-header {
            background: linear-gradient(90deg, #3f51b5 0%, #5c6bc0 100%);
            color: white;
            padding: 25px;
            border-radius: var(--border-radius);
            margin-bottom: 25px;
            box-shadow: var(--shadow);
        }
        
        .content-header h1 {
            margin: 0;
            font-size: 2rem;
        }
        
        /* ===== بطاقات العرض ===== */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 25px;
            margin-bottom: 35px;
        }
        
        .kpi-card {
            background: var(--card-color);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 28px;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .kpi-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }
        
        .kpi-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            opacity: 0;
            transition: var(--transition);
        }
        
        .kpi-card:hover::after {
            opacity: 1;
        }
        
        .kpi-icon {
            font-size: 2.6rem;
            margin-bottom: 18px;
            color: var(--primary-color);
            background-color: rgba(90, 120, 160, 0.1);
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .kpi-card:nth-child(2) .kpi-icon {
            color: var(--secondary-color);
            background-color: rgba(231, 121, 92, 0.1);
        }
        
        .kpi-card:nth-child(3) .kpi-icon {
            color: var(--success-color);
            background-color: rgba(108, 169, 117, 0.1);
        }
        
        .kpi-card:nth-child(4) .kpi-icon {
            color: var(--info-color);
            background-color: rgba(96, 179, 215, 0.1);
        }
        
        .kpi-title {
            font-size: 1.2rem;
            color: var(--text-light);
            margin-bottom: 12px;
        }
        
        .kpi-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--text-color);
            margin-bottom: 8px;
        }
        
        .kpi-subtitle {
            font-size: 1rem;
            color: var(--text-muted);
        }
        
        /* ===== المخططات البيانية ===== */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
            margin-bottom: 35px;
            order: 3;
        }
        
        .chart-container {
            background-color: var(--card-color);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 28px;
            transition: var(--transition);
        }
        
        .chart-container:hover {
            box-shadow: var(--shadow-lg);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .card-title {
            font-size: 1.35rem;
            font-weight: bold;
            color: var(--text-color);
        }
        
        .view-all {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 1rem;
            display: flex;
            align-items: center;
            transition: var(--transition);
        }
        
        .view-all:hover {
            color: var(--secondary-color);
        }
        
        .chart-wrapper {
            height: 320px;
            position: relative;
        }
        
        /* ===== الجداول ===== */
        .tables-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
            order: 4;
            margin-bottom: 35px;
        }
        
        .table-card {
            background-color: var(--card-color);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 28px;
            transition: var(--transition);
        }
        
        .table-card:hover {
            box-shadow: var(--shadow-lg);
        }
        
        .data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .data-table th {
            text-align: right;
            padding: 16px;
            color: var(--text-color);
            font-weight: 600;
            border-bottom: 2px solid var(--border-color);
            position: relative;
            font-size: 1.1rem;
        }
        
        .data-table td {
            padding: 18px 16px;
            border-bottom: 1px solid var(--border-color);
            font-size: 1.05rem;
            color: var(--text-color);
        }
        
        .data-table tbody tr {
            transition: var(--transition);
        }
        
        .data-table tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }
        
        .data-table tbody tr:last-child td {
            border-bottom: none;
        }
        
        /* Status indicators */
        .status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .status-pending {
            background-color: rgba(243, 156, 18, 0.15);
            color: #f39c12;
        }
        
        .status-completed {
            background-color: rgba(46, 204, 113, 0.15);
            color: #2ecc71;
        }
        
        .status-cancelled {
            background-color: rgba(231, 76, 60, 0.15);
            color: #e74c3c;
        }
        
        .status-payment {
            background-color: rgba(52, 152, 219, 0.15);
            color: #3498db;
        }
        
        .status-sale {
            background-color: rgba(155, 89, 182, 0.15);
            color: #9b59b6;
        }
        
        /* Empty state */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            color: var(--text-muted);
            text-align: center;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.6;
        }
        
        .empty-state p {
            font-size: 1.1rem;
        }
        
        /* ===== تخصيص عناصر أخرى ===== */
        input[type="date"]::-webkit-calendar-picker-indicator {
            opacity: 0;
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        
        @media (max-width: 992px) {
            .dashboard-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .charts-grid {
                grid-template-columns: 1fr;
            }
            .tables-grid {
                grid-template-columns: 1fr;
            }
            .sidebar {
                width: 240px;
            }
            .content-wrapper {
                margin-right: 240px;
            }
        }
        
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            body {
                margin-right: 0;
            }
            .sidebar {
                transform: translateX(100%); /* يختفي على الشاشات الصغيرة */
            }
            .sidebar.active {
                transform: translateX(0) !important; /* يظهر عند إضافة فئة active */
            }
            .content-wrapper {
                margin-right: 0 !important;
            }
        }
        
        /* Modern Sidebar Specific Styles */
        .modern-sidebar {
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        /* تنسيق الملف الشخصي في السايدبار */
        .sidebar-profile {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 15px;
        }
        
        .profile-image {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: #5c6bc0;
            margin: 0 auto 10px;
            position: relative;
            overflow: hidden;
        }
        
        .profile-info h3 {
            color: white;
            margin: 5px 0;
            font-size: 1.2rem;
        }
        
        .profile-role {
            color: rgba(255,255,255,0.7);
            font-size: 0.9rem;
        }
        
        .online-status {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 10px;
        }
        
        .status-dot {
            width: 10px;
            height: 10px;
            background-color: #4CAF50;
            border-radius: 50%;
            margin-left: 5px;
        }
        
        .status-text {
            color: rgba(255,255,255,0.8);
            font-size: 0.9rem;
        }
        
        .sidebar-nav-container {
            padding: 10px 15px;
        }
        
        .nav-section-title {
            display: block;
            color: rgba(255,255,255,0.5);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 15px 10px 10px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            margin-bottom: 10px;
        }
        
        .sidebar-nav ul {
            list-style: none;
            padding: 0;
            margin: 0 0 15px;
        }
        
        .sidebar-nav li {
            margin: 3px 0;
            position: relative;
        }
        
        .sidebar-nav li a {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            border-radius: 8px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.2s ease;
        }
        
        .sidebar-nav li a:hover {
            background-color: rgba(255,255,255,0.1);
            color: white;
        }
        
        .sidebar-nav li.active {
            background-color: rgba(255,255,255,0.2);
            color: white;
            font-weight: bold;
        }
        
        .menu-icon {
            width: 24px;
            text-align: center;
            margin-left: 10px;
        }
        
        .menu-arrow {
            margin-right: auto;
            transition: transform 0.2s ease;
        }
        
        .sidebar-nav li.dropdown.open > a .menu-arrow {
            transform: rotate(-90deg);
        }
        
        .dropdown-menu {
            display: none;
            padding-right: 34px;
            margin-top: 5px;
        }
        
        .dropdown-menu li a {
            padding: 8px 15px;
            font-size: 0.9rem;
        }
        
        .sub-dropdown-menu {
            display: none;
            padding-right: 20px;
        }
        
        .sidebar-footer {
            padding: 15px;
            border-top: 1px solid rgba(255,255,255,0.05);
            margin-top: auto;
        }
        
        .system-info {
            display: flex;
            justify-content: space-around;
            margin-bottom: 15px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: rgba(255,255,255,0.7);
            font-size: 0.8rem;
        }
        
        .info-item i {
            font-size: 1.1rem;
            margin-bottom: 5px;
        }
        
        .logout-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(255,255,255,0.1);
            color: white;
            padding: 10px;
            border-radius: 8px;
            text-decoration: none;
            transition: background-color 0.2s;
        }
        
        .logout-btn:hover {
            background-color: rgba(255,255,255,0.2);
        }
        
        .logout-btn i {
            margin-left: 8px;
        }
    </style>
</head>
<body>
    <?php 
    try {
        include 'includes/topbar.php'; // إعادة تضمين شريط التنقل العلوي
    } catch (Exception $e) {
        echo "<div style='background-color: #ffdddd; padding: 15px; margin: 15px;'>";
        echo "<p>خطأ في تحميل شريط التنقل العلوي: " . $e->getMessage() . "</p>";
        echo "</div>";
    }
    ?>
    
    <!-- القائمة العلوية الحديثة -->
    <header class="modern-navbar">
        <div class="navbar-top">
            <div class="brand">
                <i class="fas fa-store"></i>
                <span>نظام إدارة المتجر</span>
                            </div>
            <div class="user-info">
                <div class="user-image">
                    <i class="fas fa-user"></i>
                        </div>
                <div class="user-details">
                    <span class="user-name">محمد</span>
                    </div>
            </div>
                </div>
                
        <nav class="navbar-main">
            <button class="mobile-menu-toggle" id="mobileMenuToggle">
                <i class="fas fa-bars"></i>
            </button>
            
            <ul class="nav-menu" id="mainMenu">
                <!-- القائمة الرئيسية -->
                <li class="nav-item">
                    <a href="main.php" class="nav-link active">
                        <i class="fas fa-home"></i>
                        <span>الرئيسية</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="../views/cashier.php" class="nav-link">
                        <i class="fas fa-cash-register"></i>
                        <span>الكاشير</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="javascript:void(0)" class="nav-link">
                            <i class="fas fa-box"></i>
                        <span>المنتجات</span>
                        <i class="fas fa-chevron-down" style="margin-right: 5px; font-size: 0.8em;"></i>
                    </a>
                    <div class="dropdown-menu">
                        <a href="../views/products.php">جميع المنتجات</a>
                        <a href="../views/edit_product.php">إضافة منتج</a>
                        <a href="../views/categories.php">التصنيفات</a>
                        </div>
                </li>
                
                <li class="nav-item">
                    <a href="javascript:void(0)" class="nav-link">
                        <i class="fas fa-shopping-cart"></i>
                        <span>الطلبات</span>
                        <i class="fas fa-chevron-down" style="margin-right: 5px; font-size: 0.8em;"></i>
                    </a>
                    <div class="dropdown-menu">
                        <a href="../views/order.php?status=new">الطلبات الجديدة</a>
                        <a href="../views/order.php?status=processing">قيد المعالجة</a>
                        <a href="../views/order.php?status=completed">مكتملة</a>
                        </div>
                </li>
                
                <li class="nav-item">
                    <a href="javascript:void(0)" class="nav-link">
                        <i class="fas fa-users"></i>
                        <span>العملاء</span>
                        <i class="fas fa-chevron-down" style="margin-right: 5px; font-size: 0.8em;"></i>
                    </a>
                    <div class="dropdown-menu">
                        <a href="../views/customers.php">جميع العملاء</a>
                        <a href="../views/add_customer.php">إضافة عميل</a>
                        <a href="../views/customers.php?section=groups">مجموعات العملاء</a>
                        <a href="../views/customers.php?section=loyalty">برنامج الولاء</a>
                    </div>
                </li>
                
                <li class="nav-item">
                    <a href="javascript:void(0)" class="nav-link">
                        <i class="fas fa-chart-bar"></i>
                        <span>التقارير</span>
                        <i class="fas fa-chevron-down" style="margin-right: 5px; font-size: 0.8em;"></i>
                    </a>
                    <div class="dropdown-menu">
                        <a href="../views/reports.php?type=sellers">تقارير البائعين</a>
                        <a href="../views/reports.php?type=workers">تقارير الموظفين</a>
                        <a href="../views/reports.php?type=suppliers">تقارير الموردين</a>
                        </div>
                </li>
                
                <li class="nav-item">
                    <a href="javascript:void(0)" class="nav-link">
                        <i class="fas fa-truck"></i>
                        <span>الموردين</span>
                        <i class="fas fa-chevron-down" style="margin-right: 5px; font-size: 0.8em;"></i>
                    </a>
                    <div class="dropdown-menu">
                        <a href="../views/vendors.php">جميع الموردين</a>
                        <a href="../views/vendors.php?action=add">إضافة مورد</a>
                        <a href="../views/vendors.php?action=rate">تقييم الموردين</a>
                                </div>
                </li>
                
                <li class="nav-item">
                    <a href="javascript:void(0)" class="nav-link">
                        <i class="fas fa-money-check-alt"></i>
                        <span>الأقساط</span>
                        <i class="fas fa-chevron-down" style="margin-right: 5px; font-size: 0.8em;"></i>
                    </a>
                    <div class="dropdown-menu">
                        <a href="../views/installments.php">نظام الأقساط</a>
                        <a href="../views/all_customers.php">العملاء والحسابات</a>
                        <a href="../views/add_installment.php">إضافة قسط جديد</a>
                        <a href="../views/late_installments.php">الأقساط المتأخرة</a>
                        <a href="../views/installment_reports.php">تقارير وإحصائيات</a>
                        <a href="../views/installment_settings.php">الإعدادات</a>
                                </div>
                </li>
            </ul>
            
            <div class="navbar-actions">
                <button class="action-btn">
                    <i class="fas fa-bell"></i>
                </button>
                <button class="action-btn">
                    <i class="fas fa-cog"></i>
                </button>
                <a href="../views/logout.php" class="action-btn">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
                                </div>
        </nav>
    </header>
    
    <div class="main-container">
        <div class="content-wrapper">
            <div class="content">
                <div class="content-header">
                    <h1>نظام إدارة المتجر</h1>
                                </div>
                
                <!-- تحسين شكل التقويم -->
                <div class="date-filter">
                    <span>تصفية حسب التاريخ:</span>
                    <div class="modern-datepicker">
                        <input type="date" id="filter-date" class="datepicker-input" onchange="filterByDate(this.value)">
                        <label for="filter-date" class="datepicker-label">
                            <i class="fas fa-calendar-alt"></i>
                        </label>
                                </div>
                    <button onclick="resetFilter()" class="reset-btn">
                        <i class="fas fa-redo-alt"></i>
                        إعادة تعيين
                    </button>
                                </div>
                
                <!-- KPI Cards -->
                <div class="dashboard-grid">
                    <div class="kpi-card">
                        <div class="kpi-icon">
                            <i class="fas fa-chart-line"></i>
                                </div>
                        <div class="kpi-title">إجمالي المبيعات</div>
                        <div class="kpi-value" id="total-sales">0.00 ج.م</div>
                        <div class="kpi-subtitle">(المبيعات + الأقساط)</div>
                                </div>
                    
                    <div class="kpi-card">
                        <div class="kpi-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="kpi-title">عدد عمليات البيع</div>
                        <div class="kpi-value" id="sales-count">0</div>
                        <div class="kpi-subtitle">العام الحالي</div>
                </div>

                    <div class="kpi-card">
                        <div class="kpi-icon">
                            <i class="fas fa-coins"></i>
                        </div>
                        <div class="kpi-title">صافي الدخل</div>
                        <div class="kpi-value" id="net-income">0.00 ج.م</div>
                        <div class="kpi-subtitle">الإيرادات</div>
                    </div>
                    
                    <div class="kpi-card">
                        <div class="kpi-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="kpi-title">الأقساط المدفوعة</div>
                        <div class="kpi-value" id="paid-installments">0.00 ج.م</div>
                        <div class="kpi-subtitle">إجمالي المدفوعات</div>
                        </div>
                    </div>
                    
                <!-- Charts -->
                <div class="charts-grid">
                    <div class="chart-container monthly">
                        <div class="card-header">
                            <div class="card-title">المبيعات خلال آخر 12 شهر</div>
                        </div>
                        <div class="chart-wrapper">
                            <canvas id="monthly-sales-chart"></canvas>
                    </div>
                </div>
                
                    <div class="chart-container weekly">
                    <div class="card-header">
                            <div class="card-title">مبيعات الأسبوع الحالي</div>
                    </div>
                        <div class="chart-wrapper">
                            <canvas id="weekly-sales-chart"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activities and Recent Sales -->
                <div class="tables-grid">
                    <div class="table-card">
                        <div class="card-header">
                            <div class="card-title">آخر المبيعات</div>
                            <a href="save_sale.php" class="view-all">عرض الكل <i class="fas fa-arrow-left" style="margin-right: 5px;"></i></a>
                        </div>
                        <div id="recent-sales-container">
                            <table class="data-table">
                                        <thead>
                                            <tr>
                                        <th>العميل</th>
                                                <th>التاريخ</th>
                                        <th>القيمة</th>
                                            </tr>
                                        </thead>
                                <tbody id="recent-sales-table">
                                    <!-- Will be populated by JavaScript -->
                                        </tbody>
                                    </table>
                        </div>
                    </div>
                    
                    <div class="table-card">
                        <div class="card-header">
                            <div class="card-title">آخر النشاطات</div>
                            <a href="load-recent-transactions.php" class="view-all">عرض الكل <i class="fas fa-arrow-left" style="margin-right: 5px;"></i></a>
                        </div>
                        <div id="recent-activities-container">
                            <table class="data-table">
                                        <thead>
                                            <tr>
                                        <th>المسؤول</th>
                                        <th>العملية</th>
                                                <th>التاريخ</th>
                                            </tr>
                                        </thead>
                                <tbody id="recent-activities-table">
                                    <!-- Will be populated by JavaScript -->
                                        </tbody>
                                    </table>
                                    </div>
                            </div>
                                    </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile menu toggle
            const mobileMenuToggle = document.getElementById('mobileMenuToggle');
            const mainMenu = document.getElementById('mainMenu');
            
            if (mobileMenuToggle && mainMenu) {
                mobileMenuToggle.addEventListener('click', function() {
                    mainMenu.classList.toggle('active');
                    this.classList.toggle('active');
                    
                    // Change icon based on state
                    const icon = this.querySelector('i');
                    if (mainMenu.classList.contains('active')) {
                        icon.classList.remove('fa-bars');
                        icon.classList.add('fa-times');
                    } else {
                        icon.classList.remove('fa-times');
                        icon.classList.add('fa-bars');
                    }
                });
            }
                
            // Close mobile menu when clicking outside
                document.addEventListener('click', function(e) {
                if (window.innerWidth <= 768) {
                    if (mainMenu && mobileMenuToggle) {
                        if (!mainMenu.contains(e.target) && e.target !== mobileMenuToggle && !mobileMenuToggle.contains(e.target)) {
                            mainMenu.classList.remove('active');
                            
                            // Reset icon
                            const icon = mobileMenuToggle.querySelector('i');
                            if (icon) {
                                icon.classList.remove('fa-times');
                                icon.classList.add('fa-bars');
                            }
                        }
                    }
                }
            });
            
            // Add hover effect for cards based on mouse position
            const kpiCards = document.querySelectorAll('.kpi-card');
            
            kpiCards.forEach(card => {
                card.addEventListener('mousemove', function(e) {
                    const rect = this.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;
                    
                    this.style.background = `radial-gradient(circle at ${x}px ${y}px, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 50%), white`;
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.background = 'white';
                });
            });
            
            // Fetch dashboard data
            fetchDashboardData();
        });
        
        // Function to fetch dashboard data
        function fetchDashboardData(date = null) {
            let url = 'dashboard-data-simple.php';
            if (date) {
                url += `?date=${date}`;
            }
            
            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    updateDashboard(data);
                })
                .catch(error => {
                    console.error('Error fetching dashboard data:', error);
                    // Show error message on the page
                    const errorDiv = document.createElement('div');
                    errorDiv.style.backgroundColor = '#fff3f3';
                    errorDiv.style.padding = '15px';
                    errorDiv.style.margin = '15px';
                    errorDiv.style.borderRadius = '10px';
                    errorDiv.style.borderLeft = '4px solid #e74c3c';
                    errorDiv.innerHTML = `<h3>خطأ في تحميل البيانات:</h3><p>${error.message}</p>`;
                    document.querySelector('.content').appendChild(errorDiv);
                });
        }
        
        // Update dashboard with fetched data
        function updateDashboard(data) {
            // Update KPI cards
            document.getElementById('total-sales').textContent = formatCurrency(data.total_sales);
            document.getElementById('sales-count').textContent = data.sales_count;
            document.getElementById('net-income').textContent = formatCurrency(data.net_income);
            document.getElementById('paid-installments').textContent = formatCurrency(data.paid_installments);
            
            // Update monthly sales chart
            updateMonthlySalesChart(data.monthly_sales);
            
            // Update weekly sales chart
            updateWeeklySalesChart(data.weekly_sales);
            
            // Update recent sales table
            updateRecentSales(data.recent_sales);
            
            // Update recent activities table
            updateRecentActivities(data.recent_activities);
            
            // Add animation effect to values
            animateNumbers();
        }
        
        // Add animation to numbers
        function animateNumbers() {
            const kpiValues = document.querySelectorAll('.kpi-value');
            kpiValues.forEach(value => {
                value.style.animation = 'none';
                setTimeout(() => {
                    value.style.animation = 'fadeIn 0.5s ease-out';
                }, 10);
            });
        }
        
        // Format currency values
        function formatCurrency(value) {
            return parseFloat(value).toFixed(2) + ' ج.م';
        }
        
        // Filter dashboard by date
        function filterByDate(date) {
            if (date) {
                fetchDashboardData(date);
            }
        }
        
        // Reset date filter
        function resetFilter() {
            document.getElementById('filter-date').value = '';
            fetchDashboardData();
        }
        
        // Update monthly sales chart
        function updateMonthlySalesChart(monthlyData) {
            const ctx = document.getElementById('monthly-sales-chart').getContext('2d');
            
            // Check if chart instance exists
            if (window.monthlySalesChart instanceof Chart) {
                window.monthlySalesChart.destroy();
            }
            
            if (!monthlyData || monthlyData.length === 0) {
                showEmptyState(ctx.canvas.parentNode, 'لا توجد بيانات مبيعات شهرية متاحة');
                return;
            }
            
            // Prepare data for the chart
            const labels = monthlyData.map(item => item.month);
            const values = monthlyData.map(item => item.total);
            
            window.monthlySalesChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'المبيعات الشهرية',
                        data: values,
                        backgroundColor: 'rgba(63, 81, 181, 0.6)',
                        borderColor: 'rgba(63, 81, 181, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return formatCurrency(context.raw);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value + ' ج.م';
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Update weekly sales chart
        function updateWeeklySalesChart(weeklyData) {
            const ctx = document.getElementById('weekly-sales-chart').getContext('2d');
            
            // Check if chart instance exists
            if (window.weeklySalesChart instanceof Chart) {
                window.weeklySalesChart.destroy();
            }
            
            if (!weeklyData || weeklyData.length === 0) {
                showEmptyState(ctx.canvas.parentNode, 'لا توجد بيانات مبيعات أسبوعية متاحة');
                return;
            }
            
            // Prepare data for the chart
            const labels = ['السبت', 'الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة'];
            const values = weeklyData.map(item => item.total);
            
            window.weeklySalesChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'مبيعات الأسبوع',
                        data: values,
                        backgroundColor: 'rgba(126, 87, 194, 0.2)',
                        borderColor: 'rgba(126, 87, 194, 1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return formatCurrency(context.raw);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value + ' ج.م';
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Update recent sales table
        function updateRecentSales(salesData) {
            const tableBody = document.getElementById('recent-sales-table');
            const container = document.getElementById('recent-sales-container');
            
            if (!salesData || salesData.length === 0) {
                showEmptyState(container, 'لا توجد بيانات مبيعات حديثة');
                return;
            }
            
            tableBody.innerHTML = '';
            
            salesData.forEach(sale => {
                const row = document.createElement('tr');
                
                const customerCell = document.createElement('td');
                customerCell.textContent = sale.customer_name || 'عميل غير مسجل';
                
                const dateCell = document.createElement('td');
                dateCell.textContent = formatDate(sale.created_at);
                
                const amountCell = document.createElement('td');
                amountCell.textContent = formatCurrency(sale.total_amount);
                
                row.appendChild(customerCell);
                row.appendChild(dateCell);
                row.appendChild(amountCell);
                
                tableBody.appendChild(row);
            });
        }
        
        // Update recent activities table
        function updateRecentActivities(activitiesData) {
            const tableBody = document.getElementById('recent-activities-table');
            const container = document.getElementById('recent-activities-container');
            
            if (!activitiesData || activitiesData.length === 0) {
                showEmptyState(container, 'لا توجد نشاطات حديثة');
                return;
            }
            
            tableBody.innerHTML = '';
            
            activitiesData.forEach(activity => {
                const row = document.createElement('tr');
                
                const userCell = document.createElement('td');
                userCell.textContent = activity.user_name || 'النظام';
                
                const actionCell = document.createElement('td');
                const actionSpan = document.createElement('span');
                actionSpan.textContent = activity.activity_type;
                actionSpan.className = 'activity-status ' + getActivityStatusClass(activity.activity_type);
                actionCell.appendChild(actionSpan);
                
                const dateCell = document.createElement('td');
                dateCell.textContent = formatDate(activity.created_at);
                
                row.appendChild(userCell);
                row.appendChild(actionCell);
                row.appendChild(dateCell);
                
                tableBody.appendChild(row);
            });
        }
        
        // Helper function to get activity status class
        function getActivityStatusClass(activityType) {
            if (activityType.includes('دفع') || activityType.includes('قسط')) {
                return 'status-payment';
            } else if (activityType.includes('بيع') || activityType.includes('مبيعات')) {
                return 'status-sale';
            }
            return '';
        }
        
        // Format date for display
        function formatDate(dateString) {
            const options = { year: 'numeric', month: 'numeric', day: 'numeric' };
            return new Date(dateString).toLocaleDateString('ar-SA', options);
        }
        
        // Show empty state when no data is available
        function showEmptyState(container, message) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-chart-area"></i>
                    <p>${message}</p>
                </div>
            `;
        }
    </script>
</body>
</html>