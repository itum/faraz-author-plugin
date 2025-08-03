<?php
/**
 * فایل تست برای بررسی عملکرد Unsplash
 */

// بارگذاری وردپرس
require_once('/Applications/XAMPP/xamppfiles/htdocs/farazdev/wp-load.php');

// تست وجود تابع
if (function_exists('smart_admin_fetch_unsplash_image_for_post')) {
    echo "✅ تابع Unsplash موجود است\n";
    
    // تست با یک پست نمونه
    $test_post_id = 1; // شناسه یک پست موجود
    $test_keyword = "برنامه نویسی";
    
    echo "🔄 شروع تست دریافت تصویر برای کلیدواژه: $test_keyword\n";
    
    $result = smart_admin_fetch_unsplash_image_for_post($test_post_id, $test_keyword);
    
    if ($result) {
        echo "✅ تصویر با موفقیت دریافت شد. شناسه: $result\n";
    } else {
        echo "❌ تصویر دریافت نشد\n";
    }
    
} else {
    echo "❌ تابع Unsplash موجود نیست\n";
}

echo "\n--- پایان تست ---\n";
?> 