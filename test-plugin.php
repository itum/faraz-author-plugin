<?php
/**
 * فایل تست برای پلاگین FarazAutur
 * این فایل برای بررسی عملکرد پلاگین و عیب‌یابی استفاده می‌شود
 */

// بررسی اینکه آیا وردپرس بارگذاری شده است
if (!defined('ABSPATH')) {
    require_once('../../../wp-load.php');
}

// بررسی مجوزهای کاربر
if (!current_user_can('manage_options')) {
    wp_die('شما مجوز دسترسی به این صفحه را ندارید.');
}

/**
 * تست عملکرد دریافت محتوا
 */
function test_content_fetching() {
    echo "<h3>تست دریافت محتوا</h3>";
    
    $test_url = 'https://example.com';
    $content = fetch_content($test_url);
    
    if ($content !== false) {
        echo "<p style='color: green;'>✅ دریافت محتوا موفقیت‌آمیز بود</p>";
        echo "<p>طول محتوا: " . strlen($content) . " کاراکتر</p>";
    } else {
        echo "<p style='color: red;'>❌ دریافت محتوا ناموفق بود</p>";
    }
}

/**
 * تست عملکرد دریافت تصویر شاخص
 */
function test_thumbnail_fetching() {
    echo "<h3>تست دریافت تصویر شاخص</h3>";
    
    $test_url = 'https://example.com';
    $thumbnail_data = fetch_thumbnail($test_url);
    
    if (!empty($thumbnail_data['image'])) {
        echo "<p style='color: green;'>✅ دریافت تصویر شاخص موفقیت‌آمیز بود</p>";
        echo "<p>URL تصویر: " . $thumbnail_data['image'] . "</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ تصویر شاخص یافت نشد</p>";
    }
}

/**
 * تست عملکرد تلگرام
 */
function test_telegram_functionality() {
    echo "<h3>تست عملکرد تلگرام</h3>";
    
    $token = get_option('telegram_bot_token');
    $chat_id = get_option('telegram_bot_Chat_id');
    
    if (!empty($token)) {
        echo "<p style='color: green;'>✅ توکن تلگرام تنظیم شده است</p>";
    } else {
        echo "<p style='color: red;'>❌ توکن تلگرام تنظیم نشده است</p>";
    }
    
    if (!empty($chat_id)) {
        echo "<p style='color: green;'>✅ شناسه کانال تلگرام تنظیم شده است</p>";
    } else {
        echo "<p style='color: red;'>❌ شناسه کانال تلگرام تنظیم نشده است</p>";
    }
}

/**
 * تست عملکرد Unsplash
 */
function test_unsplash_functionality() {
    echo "<h3>تست عملکرد Unsplash</h3>";
    
    $api_key = get_option('faraz_unsplash_api_key');
    
    if (!empty($api_key)) {
        echo "<p style='color: green;'>✅ کلید API Unsplash تنظیم شده است</p>";
        
        // تست جستجوی تصویر
        $test_keyword = 'nature';
        $image = search_unsplash_image($test_keyword, $api_key);
        
        if ($image && !empty($image['url'])) {
            echo "<p style='color: green;'>✅ جستجوی تصویر در Unsplash موفقیت‌آمیز بود</p>";
            echo "<p>URL تصویر: " . $image['url'] . "</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ جستجوی تصویر در Unsplash ناموفق بود</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ کلید API Unsplash تنظیم نشده است</p>";
    }
}

/**
 * تست عملکرد RSS
 */
function test_rss_functionality() {
    echo "<h3>تست عملکرد RSS</h3>";
    
    $entries = get_option('stp_entries', array());
    
    if (!empty($entries)) {
        echo "<p style='color: green;'>✅ RSS feeds تنظیم شده‌اند</p>";
        echo "<p>تعداد RSS feeds: " . count($entries) . "</p>";
        
        foreach ($entries as $index => $entry) {
            echo "<p>RSS " . ($index + 1) . ": " . $entry['url'] . "</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ هیچ RSS feed تنظیم نشده است</p>";
    }
}

/**
 * تست عملکرد پایگاه داده
 */
function test_database_functionality() {
    echo "<h3>تست عملکرد پایگاه داده</h3>";
    
    global $wpdb;
    
    // بررسی تعداد پست‌ها
    $posts_count = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_status = 'faraz'");
    echo "<p>تعداد پست‌های faraz: " . $posts_count . "</p>";
    
    // بررسی تعداد تصاویر
    $attachments_count = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = 'attachment'");
    echo "<p>تعداد تصاویر: " . $attachments_count . "</p>";
    
    // بررسی پست‌های قدیمی
    $old_posts = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) 
        FROM $wpdb->posts 
        WHERE (post_status = 'draft' OR post_status = 'faraz') 
        AND post_date < DATE_SUB(NOW(), INTERVAL 7 DAY)
    "));
    echo "<p>تعداد پست‌های قدیمی: " . $old_posts . "</p>";
}

/**
 * تست عملکرد سیستم
 */
function test_system_performance() {
    echo "<h3>تست عملکرد سیستم</h3>";
    
    // بررسی حافظه
    $memory_limit = ini_get('memory_limit');
    $memory_usage = memory_get_usage(true);
    $memory_peak = memory_get_peak_usage(true);
    
    echo "<p>محدودیت حافظه: " . $memory_limit . "</p>";
    echo "<p>استفاده فعلی حافظه: " . format_bytes($memory_usage) . "</p>";
    echo "<p>حداکثر استفاده حافظه: " . format_bytes($memory_peak) . "</p>";
    
    // بررسی زمان اجرا
    $execution_time = microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"];
    echo "<p>زمان اجرا: " . round($execution_time, 3) . " ثانیه</p>";
}

/**
 * فرمت کردن بایت‌ها
 */
function format_bytes($bytes) {
    $units = array('B', 'KB', 'MB', 'GB');
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * نمایش نتایج تست
 */
function run_all_tests() {
    echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; border: 1px solid #ccc;'>";
    echo "<h1 style='color: #333;'>تست عملکرد پلاگین FarazAutur</h1>";
    echo "<p style='color: #666;'>تاریخ تست: " . date('Y-m-d H:i:s') . "</p>";
    
    test_content_fetching();
    test_thumbnail_fetching();
    test_telegram_functionality();
    test_unsplash_functionality();
    test_rss_functionality();
    test_database_functionality();
    test_system_performance();
    
    echo "<h3>خلاصه تست</h3>";
    echo "<p>برای مشاهده لاگ‌های کامل، فایل‌های لاگ را بررسی کنید:</p>";
    echo "<ul>";
    echo "<li>rss_logs.txt</li>";
    echo "<li>telegram_logs.txt</li>";
    echo "<li>whatsapp_logs.txt</li>";
    echo "</ul>";
    
    echo "</div>";
}

// اجرای تست‌ها
if (isset($_GET['run_tests'])) {
    run_all_tests();
} else {
    echo "<div style='text-align: center; margin: 50px;'>";
    echo "<h2>تست پلاگین FarazAutur</h2>";
    echo "<p>برای اجرای تست‌ها، روی دکمه زیر کلیک کنید:</p>";
    echo "<a href='?run_tests=1' style='background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>اجرای تست‌ها</a>";
    echo "</div>";
}
?> 