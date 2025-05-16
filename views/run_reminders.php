<?php
session_start();
include '../app/db.php';

// تأكد من أن المستخدم مسجل دخول ولديه صلاحيات
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// متغيرات للتحكم في عرض النتائج
$log_output = '';
$success = false;
$has_run = false;

// إذا تم إرسال النموذج لتشغيل التذكيرات
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_reminders'])) {
    $has_run = true;
    
    // بدء تسجيل المخرجات
    ob_start();
    
    try {
        // تضمين ملف التذكيرات وتشغيله
        include_once '../app/installment_reminders.php';
        $success = true;
    } catch (Exception $e) {
        echo "حدث خطأ أثناء تشغيل التذكيرات: " . $e->getMessage();
        $success = false;
    }
    
    // استخراج المخرجات
    $log_output = ob_get_clean();
}

// جلب آخر سجلات التذكيرات إذا كان ملف السجل موجودًا
$log_file = __DIR__ . '/../logs/reminder_log.txt';
$recent_logs = '';

if (file_exists($log_file)) {
    // قراءة آخر 50 سطر من ملف السجل
    $file = new SplFileObject($log_file);
    $file->seek(PHP_INT_MAX); // الانتقال إلى نهاية الملف
    $total_lines = $file->key(); // الحصول على عدد الأسطر

    $lines_to_read = min(50, $total_lines); // قراءة آخر 50 سطر أو جميع الأسطر إذا كان العدد أقل
    $start_line = max(0, $total_lines - $lines_to_read);

    $file->seek($start_line);
    $log_lines = [];
    
    while (!$file->eof()) {
        $line = $file->current();
        $log_lines[] = $line;
        $file->next();
    }
    
    $recent_logs = implode('', $log_lines);
}

// استخراج إحصائيات الأقساط
try {
    // عدد الأقساط المستحقة قريبًا (خلال 7 أيام)
    $upcoming_sql = "
        SELECT COUNT(*) as count
        FROM installments
        WHERE paid = 0 
        AND due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ";
    $upcoming_stmt = $conn->prepare($upcoming_sql);
    $upcoming_stmt->execute();
    $upcoming_count = $upcoming_stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // عدد الأقساط المتأخرة
    $late_sql = "
        SELECT COUNT(*) as count
        FROM installments
        WHERE paid = 0 
        AND due_date < CURDATE()
    ";
    $late_stmt = $conn->prepare($late_sql);
    $late_stmt->execute();
    $late_count = $late_stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // إجمالي المبالغ المتأخرة
    $late_amount_sql = "
        SELECT SUM(amount) as total
        FROM installments
        WHERE paid = 0 
        AND due_date < CURDATE()
    ";
    $late_amount_stmt = $conn->prepare($late_amount_sql);
    $late_amount_stmt->execute();
    $late_amount = $late_amount_stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
} catch (PDOException $e) {
    // التعامل مع أخطاء قاعدة البيانات
    $db_error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تشغيل تذكيرات الأقساط</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #36d03b;
            --primary-dark: #1a9c1f;
            --secondary-color: #2196f3;
            --secondary-dark: #1976d2;
            --text-color: #1a2b3c;
            --text-light: #7b8a9a;
            --bg-color: #f5f7fa;
            --card-bg: #fff;
            --border-color: #e3eafc;
            --green-light: #e3f7e4;
            --shadow-color: rgba(0,0,0,0.1);
        }
        
        body {
            background-color: var(--bg-color);
            font-family: 'Cairo', sans-serif;
            margin: 0;
            padding: 20px;
            direction: rtl;
            color: var(--text-color);
        }
        
        h1 {
            color: var(--primary-color);
            margin-bottom: 20px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background-color: var(--card-bg);
            border-radius: 10px;
            box-shadow: 0 4px 15px var(--shadow-color);
            padding: 20px;
        }
        
        .card {
            background-color: var(--bg-color);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn:hover {
            background-color: var(--primary-dark);
        }
        
        .btn-secondary {
            background-color: var(--secondary-color);
        }
        
        .btn-secondary:hover {
            background-color: var(--secondary-dark);
        }
        
        .log-container {
            background-color: #2b2b2b;
            color: #fff;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            overflow-x: auto;
            white-space: pre-wrap;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .success-message {
            background-color: var(--green-light);
            color: var(--primary-dark);
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: var(--secondary-color);
            text-decoration: none;
            margin-bottom: 20px;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background-color: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 20px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 2.5em;
            font-weight: bold;
            margin: 15px 0;
            color: var(--primary-color);
        }
        
        .stat-label {
            font-size: 1em;
            color: var(--text-light);
        }
        
        .stat-card.warning .stat-value {
            color: #f57c00;
        }
        
        .stat-card.danger .stat-value {
            color: #d32f2f;
        }
        
        .log-timestamp {
            color: #8bc34a;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="installment_settings.php" class="back-link">
            <i class="fas fa-arrow-right"></i>
            العودة إلى إعدادات الأقساط
        </a>
        
        <h1><i class="fas fa-bell"></i> تشغيل تذكيرات الأقساط</h1>
        
        <!-- الإحصائيات -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">أقساط خلال 7 أيام</div>
                <div class="stat-value"><?php echo number_format($upcoming_count); ?></div>
                <div><i class="fas fa-calendar-day"></i> قسط مستحق قريباً</div>
            </div>
            
            <div class="stat-card warning">
                <div class="stat-label">أقساط متأخرة</div>
                <div class="stat-value"><?php echo number_format($late_count); ?></div>
                <div><i class="fas fa-exclamation-triangle"></i> قسط متأخر الدفع</div>
            </div>
            
            <div class="stat-card danger">
                <div class="stat-label">إجمالي المبالغ المتأخرة</div>
                <div class="stat-value"><?php echo number_format($late_amount, 2); ?></div>
                <div><i class="fas fa-money-bill-wave"></i> جنيه متأخر الدفع</div>
            </div>
        </div>
        
        <div class="card">
            <h2>تشغيل تذكيرات الأقساط يدوياً</h2>
            <p>
                استخدم هذه الميزة لتشغيل تذكيرات الأقساط يدوياً. سيقوم النظام بإرسال رسائل تذكير للعملاء الذين لديهم أقساط مستحقة قريباً أو متأخرة.
            </p>
            <p>
                <strong>ملاحظة:</strong> يتم تشغيل هذه العملية تلقائياً بواسطة المجدول (cron job) كل يوم، لكن يمكنك تشغيلها يدوياً إذا لزم الأمر.
            </p>
            
            <?php if ($has_run): ?>
                <div class="<?php echo $success ? 'success-message' : 'error-message'; ?>">
                    <?php if ($success): ?>
                        <h3><i class="fas fa-check-circle"></i> تم تشغيل تذكيرات الأقساط بنجاح</h3>
                    <?php else: ?>
                        <h3><i class="fas fa-times-circle"></i> حدث خطأ أثناء تشغيل تذكيرات الأقساط</h3>
                    <?php endif; ?>
                    
                    <?php if (!empty($log_output)): ?>
                        <div class="log-container"><?php echo htmlspecialchars($log_output); ?></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <button type="submit" name="run_reminders" class="btn">
                    <i class="fas fa-play-circle"></i>
                    تشغيل تذكيرات الأقساط الآن
                </button>
                
                <a href="test_sms.php" class="btn btn-secondary" style="margin-right: 10px;">
                    <i class="fas fa-vial"></i>
                    اختبار إرسال الرسائل
                </a>
            </form>
        </div>
        
        <?php if (!empty($recent_logs)): ?>
        <div class="card">
            <h2>سجل التذكيرات الأخيرة</h2>
            <div class="log-container"><?php 
                // تلوين عناصر السجل
                $colored_logs = preg_replace(
                    '/(\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\])/',
                    '<span class="log-timestamp">$1</span>',
                    htmlspecialchars($recent_logs)
                );
                echo $colored_logs;
            ?></div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html> 