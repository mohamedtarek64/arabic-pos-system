<?php
/**
 * Installment Reminders Scheduler
 * This script is designed to be run as a scheduled task (cron job) to send reminder SMS
 * to customers with upcoming or late installment payments.
 */

// Load required files
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/sms_service.php';

// Log file for tracking script execution
$log_file = __DIR__ . '/../logs/reminder_log.txt';

/**
 * Write log message to file
 */
function write_log($message) {
    global $log_file;
    
    // Ensure the logs directory exists
    $log_dir = dirname($log_file);
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $log_entry = sprintf("[%s] %s\n", date('Y-m-d H:i:s'), $message);
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

// Log script start
write_log("=== Installment reminder task started ===");

// Get SMS settings
try {
    $settings_sql = "SELECT setting_key, setting_value FROM settings WHERE category = 'installments' AND setting_key IN ('enable_sms_reminders', 'reminder_days_before')";
    $settings_stmt = $conn->prepare($settings_sql);
    $settings_stmt->execute();
    $settings_data = $settings_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $settings = [];
    foreach ($settings_data as $setting) {
        $settings[$setting['setting_key']] = $setting['setting_value'];
    }
    
    $sms_enabled = isset($settings['enable_sms_reminders']) && $settings['enable_sms_reminders'] == '1';
    $reminder_days = isset($settings['reminder_days_before']) ? (int)$settings['reminder_days_before'] : 3;
    
    write_log("SMS reminders are " . ($sms_enabled ? "enabled" : "disabled") . ", reminder days: $reminder_days");
    
    // Exit if SMS reminders are disabled
    if (!$sms_enabled) {
        write_log("SMS reminders are disabled in settings. Exiting.");
        exit;
    }
    
    // Calculate the date for upcoming installments
    $target_date = date('Y-m-d', strtotime("+$reminder_days days"));
    
    // 1. Send reminders for upcoming installments
    write_log("Checking for upcoming installments due on $target_date");
    
    $upcoming_sql = "
        SELECT 
            i.id AS installment_id,
            i.customer_id,
            i.amount,
            i.due_date,
            c.name AS customer_name,
            c.phone AS customer_phone
        FROM 
            installments i
        JOIN 
            customers c ON i.customer_id = c.id
        WHERE 
            i.paid = 0 
            AND i.due_date = ?
    ";
    
    $upcoming_stmt = $conn->prepare($upcoming_sql);
    $upcoming_stmt->execute([$target_date]);
    $upcoming_installments = $upcoming_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $upcoming_count = count($upcoming_installments);
    write_log("Found $upcoming_count upcoming installments.");
    
    // Send reminders for upcoming installments
    $upcoming_success = 0;
    foreach ($upcoming_installments as $installment) {
        // Only send if customer has a phone number
        if (empty($installment['customer_phone'])) {
            write_log("Skipping installment ID {$installment['installment_id']} - Customer has no phone number");
            continue;
        }
        
        $result = send_installment_reminder(
            $installment['customer_id'],
            $installment['customer_name'],
            $installment['customer_phone'],
            $installment['amount'],
            $installment['due_date']
        );
        
        if ($result['success']) {
            $upcoming_success++;
            // Update the installment record to mark that a reminder was sent
            $update_sql = "UPDATE installments SET reminder_sent = 1 WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->execute([$installment['installment_id']]);
            
            write_log("Reminder sent successfully for installment ID {$installment['installment_id']}, customer: {$installment['customer_name']}");
        } else {
            write_log("Failed to send reminder for installment ID {$installment['installment_id']}: " . ($result['error'] ?? 'Unknown error'));
        }
    }
    
    // 2. Send reminders for late installments (optional)
    $current_date = date('Y-m-d');
    
    $late_sql = "
        SELECT 
            i.id AS installment_id,
            i.customer_id,
            i.amount,
            i.due_date,
            c.name AS customer_name,
            c.phone AS customer_phone,
            DATEDIFF(?, i.due_date) AS days_late
        FROM 
            installments i
        JOIN 
            customers c ON i.customer_id = c.id
        WHERE 
            i.paid = 0 
            AND i.due_date < ?
            AND (i.last_reminder_date IS NULL OR DATEDIFF(?, i.last_reminder_date) >= 7)
        ORDER BY
            i.due_date ASC
    ";
    
    $late_stmt = $conn->prepare($late_sql);
    $late_stmt->execute([$current_date, $current_date, $current_date]);
    $late_installments = $late_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $late_count = count($late_installments);
    write_log("Found $late_count late installments that need reminders.");
    
    // Send reminders for late installments
    $late_success = 0;
    foreach ($late_installments as $installment) {
        // Only send if customer has a phone number
        if (empty($installment['customer_phone'])) {
            write_log("Skipping late installment ID {$installment['installment_id']} - Customer has no phone number");
            continue;
        }
        
        $result = send_late_payment_notification(
            $installment['customer_id'],
            $installment['customer_name'],
            $installment['customer_phone'],
            $installment['amount'],
            $installment['due_date'],
            $installment['days_late']
        );
        
        if ($result['success']) {
            $late_success++;
            // Update the installment record with the reminder date
            $update_sql = "UPDATE installments SET last_reminder_date = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->execute([$current_date, $installment['installment_id']]);
            
            write_log("Late payment reminder sent successfully for installment ID {$installment['installment_id']}, customer: {$installment['customer_name']}, days late: {$installment['days_late']}");
        } else {
            write_log("Failed to send late payment reminder for installment ID {$installment['installment_id']}: " . ($result['error'] ?? 'Unknown error'));
        }
    }
    
    // Summary
    write_log("Task complete. Sent $upcoming_success/$upcoming_count upcoming reminders and $late_success/$late_count late payment notifications.");
    
} catch (PDOException $e) {
    write_log("Database error: " . $e->getMessage());
} catch (Exception $e) {
    write_log("Error: " . $e->getMessage());
}

write_log("=== Installment reminder task finished ===\n"); 