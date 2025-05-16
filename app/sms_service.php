<?php
/**
 * SMS Service for the Installment System
 * This file handles sending SMS notifications to customers
 */

/**
 * تنبيه مهم:
 * لاستخدام خدمة الرسائل القصيرة الحقيقية، يجب عليك:
 * 1. الاشتراك مع مزود خدمة رسائل SMS
 * 2. الحصول على مفتاح API واستبدال 'YOUR_API_KEY_HERE' بالمفتاح الحقيقي
 * 3. تغيير رابط الـ API (CURLOPT_URL) وفقًا لخدمة الرسائل التي تستخدمها
 * 4. التأكد من أن وضع الاختبار (test_mode) مضبوط على false
 */

// تكوين خدمة الرسائل القصيرة
$SMS_CONFIG = [
    'api_key' => 'YOUR_API_KEY_HERE', // ضع مفتاح API الخاص بخدمة الرسائل النصية هنا
    'sender_id' => 'Store', // معرف المرسل
    'test_mode' => true, // وضع الاختبار
    'log_file' => __DIR__ . '/../logs/sms_log.txt', // ملف تسجيل الرسائل
];

/**
 * إرسال رسالة نصية للعميل
 * 
 * @param string $phone_number رقم هاتف المستلم
 * @param string $message نص الرسالة
 * @return array نتيجة الإرسال
 */
function send_sms($phone_number, $message) {
    global $SMS_CONFIG;
    
    // تنسيق رقم الهاتف (إزالة المسافات والرموز غير المرغوب فيها)
    $phone_number = format_phone_number($phone_number);
    
    // فحص رقم الهاتف
    if (empty($phone_number)) {
        return ['success' => false, 'error' => 'رقم الهاتف غير صالح'];
    }
    
    // تسجيل الرسالة
    log_sms($phone_number, $message);
    
    // إذا كان وضع الاختبار مفعل، نقوم بمحاكاة الإرسال بدون الاتصال بـ API
    if ($SMS_CONFIG['test_mode']) {
        return [
            'success' => true, 
            'message' => 'تم إرسال الرسالة في وضع الاختبار',
            'to' => $phone_number,
            'content' => $message
        ];
    }
    
    // الاتصال بخدمة SMS API الحقيقية هنا
    // هذا مثال على كيفية الاتصال بخدمة SMS
    try {
        // رمز الاتصال بـ API - قم بتعديل الرابط والإعدادات وفقًا لمزود الخدمة الخاص بك
        $curl = curl_init();
        
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.yoursmsservice.com/send', // قم بتغيير هذا الرابط
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode([
                'apiKey' => $SMS_CONFIG['api_key'],
                'senderId' => $SMS_CONFIG['sender_id'],
                'to' => $phone_number,
                'message' => $message
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json'
            ],
        ]);
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        curl_close($curl);
        
        if ($err) {
            return ['success' => false, 'error' => 'خطأ في الاتصال: ' . $err];
        }
        
        $result = json_decode($response, true);
        
        return [
            'success' => true,
            'message' => 'تم إرسال الرسالة بنجاح',
            'response' => $result
        ];
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'خطأ: ' . $e->getMessage()];
    }
}

/**
 * تنسيق رقم الهاتف
 * 
 * @param string $phone_number رقم الهاتف
 * @return string رقم الهاتف المنسق
 */
function format_phone_number($phone_number) {
    // إزالة المسافات والرموز
    $phone_number = preg_replace('/[^0-9]/', '', $phone_number);
    
    // التحقق من صيغة رقم الهاتف المصري
    if (strlen($phone_number) === 10 && substr($phone_number, 0, 1) === '0') {
        // تحويل 0100... إلى +2010...
        $phone_number = '2' . $phone_number;
    } else if (strlen($phone_number) === 11 && substr($phone_number, 0, 2) === '01') {
        // تحويل 01... إلى +201...
        $phone_number = '2' . $phone_number;
    } else if (strlen($phone_number) === 12 && substr($phone_number, 0, 3) === '201') {
        // الرقم صحيح بالفعل بصيغة 201...
    } else if (strlen($phone_number) === 13 && substr($phone_number, 0, 4) === '+201') {
        // إزالة علامة +
        $phone_number = substr($phone_number, 1);
    }
    
    return $phone_number;
}

/**
 * تسجيل الرسائل المرسلة
 * 
 * @param string $phone_number رقم المستلم
 * @param string $message نص الرسالة
 */
function log_sms($phone_number, $message) {
    global $SMS_CONFIG;
    
    // إنشاء دليل السجلات إذا لم يكن موجودًا
    $log_dir = dirname($SMS_CONFIG['log_file']);
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    // تسجيل الرسالة في الملف
    $log_entry = sprintf(
        "[%s] TO: %s, MESSAGE: %s\n",
        date('Y-m-d H:i:s'),
        $phone_number,
        $message
    );
    
    file_put_contents(
        $SMS_CONFIG['log_file'],
        $log_entry,
        FILE_APPEND
    );
}

/**
 * إرسال تذكير قسط
 * 
 * @param int $customer_id معرف العميل
 * @param string $customer_name اسم العميل
 * @param string $customer_phone رقم هاتف العميل
 * @param float $amount مبلغ القسط
 * @param string $due_date تاريخ الاستحقاق
 * @return array نتيجة الإرسال
 */
function send_installment_reminder($customer_id, $customer_name, $customer_phone, $amount, $due_date) {
    // تنسيق التاريخ
    $formatted_date = date('d/m/Y', strtotime($due_date));
    $amount_formatted = number_format($amount, 2);
    
    // إنشاء نص الرسالة
    $message = "تذكير: عزيزي $customer_name، لديك قسط بقيمة $amount_formatted جنيه، موعد استحقاقه $formatted_date. نرجو السداد في الموعد. شكراً لك.";
    
    // إرسال الرسالة
    return send_sms($customer_phone, $message);
}

/**
 * إرسال إشعار تأخير سداد القسط
 * 
 * @param int $customer_id معرف العميل
 * @param string $customer_name اسم العميل
 * @param string $customer_phone رقم هاتف العميل
 * @param float $amount مبلغ القسط
 * @param string $due_date تاريخ الاستحقاق
 * @param int $days_late عدد أيام التأخير
 * @return array نتيجة الإرسال
 */
function send_late_payment_notification($customer_id, $customer_name, $customer_phone, $amount, $due_date, $days_late) {
    // تنسيق التاريخ والمبلغ
    $formatted_date = date('d/m/Y', strtotime($due_date));
    $amount_formatted = number_format($amount, 2);
    
    // إنشاء نص الرسالة
    $message = "تنبيه: عزيزي $customer_name، لديك قسط متأخر بقيمة $amount_formatted جنيه، كان موعد استحقاقه $formatted_date (متأخر بـ $days_late يوم). نرجو سرعة السداد. شكراً لك.";
    
    // إرسال الرسالة
    return send_sms($customer_phone, $message);
}

/**
 * إرسال إشعار سداد قسط
 * 
 * @param int $customer_id معرف العميل
 * @param string $customer_name اسم العميل
 * @param string $customer_phone رقم هاتف العميل
 * @param float $amount مبلغ القسط المسدد
 * @param string $payment_date تاريخ السداد
 * @param float $remaining مبلغ الأقساط المتبقية (اختياري)
 * @return array نتيجة الإرسال
 */
function send_payment_confirmation($customer_id, $customer_name, $customer_phone, $amount, $payment_date, $remaining = null) {
    // تنسيق التاريخ والمبلغ
    $formatted_date = date('d/m/Y', strtotime($payment_date));
    $amount_formatted = number_format($amount, 2);
    
    // إنشاء نص الرسالة
    $message = "شكراً $customer_name، تم استلام دفعة بقيمة $amount_formatted جنيه بتاريخ $formatted_date.";
    
    // إضافة معلومات المبلغ المتبقي إذا كانت متوفرة
    if ($remaining !== null) {
        $remaining_formatted = number_format($remaining, 2);
        $message .= " المبلغ المتبقي: $remaining_formatted جنيه.";
    }
    
    $message .= " نشكرك على الالتزام بالسداد.";
    
    // إرسال الرسالة
    return send_sms($customer_phone, $message);
} 