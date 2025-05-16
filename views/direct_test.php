<?php
// تضمين ملف الـ SMS
include_once '../app/sms_service.php';

// إرسال رسالة للرقم المطلوب
$result = send_sms('01068207217', 'اختبار رسالة من نظام الأقساط للعميل: هذا اختبار. شكراً!');

// عرض النتيجة
header('Content-Type: text/plain; charset=utf-8');
echo "تم محاولة إرسال الرسالة. النتيجة:\n\n";
print_r($result);

// عرض محتوى ملف السجل
$log_file = __DIR__ . '/../logs/sms_log.txt';
if (file_exists($log_file)) {
    echo "\n\nمحتوى ملف السجل:\n";
    echo file_get_contents($log_file);
} else {
    echo "\n\nملف السجل لم يتم إنشاؤه بعد: $log_file";
} 