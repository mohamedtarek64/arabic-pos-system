<?php
// بدء الجلسة
session_start();

// تدمير الجلسة
session_destroy();

// إعادة التوجيه إلى صفحة تسجيل الدخول
header("Location: login.php"); // تغيير هذا إلى مسار صفحة تسجيل الدخول الخاصة بك
exit();
?>
