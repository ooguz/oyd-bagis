<?php
/**
 * Simple Email Test Script
 * Run this to test if your server can send emails
 * 
 * Usage: php test_email.php
 */

echo "Testing Email Configuration...\n\n";

// Test 1: Check if mail function exists
if (function_exists('mail')) {
    echo "✅ mail() function is available\n";
} else {
    echo "❌ mail() function is not available\n";
}

// Test 2: Check sendmail path
$sendmail_path = ini_get('sendmail_path');
if ($sendmail_path) {
    echo "✅ sendmail_path: $sendmail_path\n";
} else {
    echo "❌ sendmail_path not configured\n";
}

// Test 3: Check SMTP settings
$smtp_host = ini_get('SMTP');
$smtp_port = ini_get('smtp_port');
if ($smtp_host) {
    echo "✅ SMTP Host: $smtp_host\n";
    echo "✅ SMTP Port: $smtp_port\n";
} else {
    echo "❌ SMTP not configured in php.ini\n";
}

// Test 4: Try to send a test email
echo "\nAttempting to send test email...\n";

$to = 'test@example.com';
$subject = 'Test Email from Server';
$message = 'This is a test email to verify email functionality.';
$headers = 'From: noreply@oyd.org.tr' . "\r\n" .
           'Reply-To: noreply@oyd.org.tr' . "\r\n" .
           'X-Mailer: PHP/' . phpversion();

$result = mail($to, $subject, $message, $headers);

if ($result) {
    echo "✅ Test email sent successfully\n";
} else {
    echo "❌ Failed to send test email\n";
}

// Test 5: Check mail logs
echo "\nChecking mail logs...\n";
$mail_log = '/var/log/mail.log';
if (file_exists($mail_log)) {
    echo "✅ Mail log exists: $mail_log\n";
    $last_lines = shell_exec("tail -5 $mail_log 2>/dev/null");
    if ($last_lines) {
        echo "Last 5 lines of mail log:\n$last_lines\n";
    }
} else {
    echo "❌ Mail log not found at $mail_log\n";
}

// Test 6: Check if postfix/sendmail is running
echo "\nChecking mail services...\n";
$services = ['postfix', 'sendmail', 'exim4'];
foreach ($services as $service) {
    $status = shell_exec("systemctl is-active $service 2>/dev/null");
    if (trim($status) === 'active') {
        echo "✅ $service is running\n";
    } else {
        echo "❌ $service is not running\n";
    }
}

echo "\nEmail test completed.\n";
echo "If you see errors, you may need to:\n";
echo "1. Install and configure postfix: sudo apt install postfix\n";
echo "2. Configure SMTP settings in your .env file\n";
echo "3. Use a service like Gmail SMTP or Mailgun\n";
