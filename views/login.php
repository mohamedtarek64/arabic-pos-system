<?php
// session_start();

// if ($_SERVER['REQUEST_METHOD'] == 'POST') {
//     $username = $_POST['username'];
//     $password = $_POST['password'];

//     if ($username == "demo" && $password == "demo") {
//         $_SESSION['user_id'] = 1;
//         $_SESSION['username'] = $username;
//         $_SESSION['user_role'] = 'مدير النظام';
//         header('Location: ../app/main.php');
//         exit();
//     } else {
//         $error_message = "اسم المستخدم أو كلمة المرور غير صحيحة";
//     }
// }
?>
<!-- This is a demo login page - authentication is disabled in this version. -->

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - نظام إدارة المتجر</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/login.css">
</head>
<body>
    
    <div class="loading-overlay" id="loading-overlay">
        <div class="spinner"></div>
    </div>
    
    <div class="login-container">
        
        <div class="login-image">
            <div class="login-image-content">
                <div class="store-logo">
                    <i class="fas fa-store"></i> نظام إدارة المتجر
                </div>
                <p class="store-description">
                    أهلاً بك في نظام إدارة المتجر المتكامل، الحل الأمثل لإدارة منتجاتك ومبيعاتك بكفاءة عالية
                </p>
                
                <div class="store-features">
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="feature-text">إدارة المنتجات والمخزون</div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="feature-text">تقارير المبيعات والأرباح</div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="feature-text">إدارة العملاء والموظفين</div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="feature-text">نظام الأقساط والمدفوعات</div>
                    </div>
                </div>
            </div>
        </div>
        
        
        <div class="login-form">
            <div class="login-header">
                <h1 class="login-title">تسجيل الدخول</h1>
                <p class="login-subtitle">قم بتسجيل الدخول للوصول إلى لوحة التحكم</p>
            </div>
            
            <?php if (isset($error_message)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <form action="login.php" method="POST" id="login-form">
                <div class="form-group">
                    <label for="username">اسم المستخدم</label>
                    <div class="input-with-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" id="username" name="username" class="form-control" placeholder="أدخل اسم المستخدم" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">كلمة المرور</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" class="form-control" placeholder="أدخل كلمة المرور" required>
                    </div>
                </div>
                
                <div class="remember-me">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">تذكرني</label>
                </div>
                
                <button type="submit" class="btn-login">
                    تسجيل الدخول <i class="fas fa-sign-in-alt"></i>
                </button>
            </form>
            
            <div class="login-footer">
                <p>© <?php echo date('Y'); ?> جميع الحقوق محفوظة - نظام إدارة المتجر</p>
            </div>
        </div>
    </div>
    
    <script>
        // Show loading overlay when form is submitted
        document.getElementById('login-form').addEventListener('submit', function() {
            document.getElementById('loading-overlay').classList.add('active');
        });
    </script>
</body>
</html>

