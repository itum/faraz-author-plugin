<?php
/**
 * فایل تست برای بررسی عملکرد حالت دیباگ
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    require_once('../../../wp-load.php');
}

// تست تابع smart_admin_get_setting
echo "<h2>تست تنظیمات دیباگ</h2>";

if (function_exists('smart_admin_get_setting')) {
    $debug_mode = smart_admin_get_setting('debug_mode');
    echo "<p>حالت دیباگ: " . ($debug_mode ? 'فعال' : 'غیرفعال') . "</p>";
} else {
    echo "<p>تابع smart_admin_get_setting موجود نیست!</p>";
}

// تست تابع smart_admin_debug_log
echo "<h2>تست لاگ‌گیری</h2>";

if (function_exists('smart_admin_debug_log')) {
    smart_admin_debug_log("این یک پیام تست است", "TEST");
    echo "<p>پیام تست ارسال شد. فایل debug.log را بررسی کنید.</p>";
} else {
    echo "<p>تابع smart_admin_debug_log موجود نیست!</p>";
}

// تست فایل debug.log
echo "<h2>محتوای فایل debug.log</h2>";

$debug_log_path = WP_CONTENT_DIR . '/debug.log';
if (file_exists($debug_log_path)) {
    $content = file_get_contents($debug_log_path);
    echo "<pre>" . htmlspecialchars($content) . "</pre>";
} else {
    echo "<p>فایل debug.log موجود نیست.</p>";
}

// تست سایر فایل‌های لاگ
echo "<h2>سایر فایل‌های لاگ</h2>";

$log_files = [
    'auto-report-debug.log',
    'telegram_logs.txt',
    'whatsapp_logs.txt',
    'debug.log'
];

foreach ($log_files as $log_file) {
    $log_path = plugin_dir_path(__FILE__) . $log_file;
    if (file_exists($log_path)) {
        $size = filesize($log_path);
        echo "<p>{$log_file}: " . number_format($size) . " بایت</p>";
    } else {
        echo "<p>{$log_file}: موجود نیست</p>";
    }
}
?> 