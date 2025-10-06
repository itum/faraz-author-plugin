<?php
// --- Smart Admin Debug Setup ---
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
    @ini_set( 'log_errors', 'On' );
    @ini_set( 'display_errors', 'Off' );
    if ( ! ini_get( 'error_log' ) || stripos( ini_get( 'error_log' ), 'debug.log' ) === false ) {
        @ini_set( 'error_log', WP_CONTENT_DIR . '/debug.log' );
    }
}

// افزودن منوی ادمین هوشمند به افزونه
add_action('admin_menu', 'smart_admin_add_menu');

// بارگذاری CSS ها برای صفحه ادمین
add_action('admin_enqueue_scripts', 'smart_admin_enqueue_styles');

// تابع بارگذاری CSS ها
function smart_admin_enqueue_styles($hook) {
    // فقط در صفحه ادمین هوشمند CSS ها را بارگذاری کن
    if ($hook !== 'toplevel_page_smart-admin' && $hook !== 'smart-admin_page_smart-admin-metabox-settings') {
        return;
    }
    
    // بارگذاری فایل CSS فونت پیش‌فرض وردپرس
    wp_enqueue_style(
        'smart-admin-wordpress-font',
        plugin_dir_url(__FILE__) . 'css/wordpress-font-inheritance.css',
        array(),
        '1.0.0'
    );
    
    // بارگذاری فایل CSS SEO Optimizer
    wp_enqueue_style(
        'smart-admin-seo-optimizer',
        plugin_dir_url(__FILE__) . 'css/smart-admin-seo-optimizer.css',
        array(),
        '1.0.0'
    );
    
    // بارگذاری فایل CSS Unsplash Metabox
    wp_enqueue_style(
        'smart-admin-unsplash-metabox',
        plugin_dir_url(__FILE__) . 'css/unsplash-metabox.css',
        array(),
        '1.0.0'
    );
}

// وارد کردن فایل قالب‌های پرامپت
require_once plugin_dir_path(__FILE__) . 'smart-admin-templates.php';

// وارد کردن فایل تنظیمات لحن انسانی
require_once plugin_dir_path(__FILE__) . 'smart-admin-human-tone.php';

// وارد کردن فایل یکپارچه‌سازی با Rank Math SEO
require_once plugin_dir_path(__FILE__) . 'smart-admin-rank-math-seo.php';

// وارد کردن فایل زمان‌بندی محتوا
require_once plugin_dir_path(__FILE__) . 'smart-admin-scheduler.php';

// وارد کردن فایل بهینه‌ساز هوشمند SEO
require_once plugin_dir_path(__FILE__) . 'smart-admin-seo-auto-optimizer.php';

// بررسی وضعیت Unsplash قبل از بارگذاری فایل بهینه‌ساز تصاویر
$unsplash_enabled = false;

// لاگر داخلی افزونه
if (!function_exists('smart_admin_log')) {
    function smart_admin_log($message) {
        $prefix = '[Smart-Admin] ';
        $timestamp = date('Y-m-d H:i:s');
        
        // اگر پیام شامل HTML است، آن را برای نمایش بهتر در لاگ پردازش کن
        if (strpos($message, '<') !== false && strpos($message, '>') !== false) {
            // HTML را به صورت خوانا در لاگ نمایش بده
            $readable_message = html_entity_decode($message, ENT_QUOTES, 'UTF-8');
            $readable_message = str_replace(array('<h1>', '</h1>', '<h2>', '</h2>', '<h3>', '</h3>'), 
                                          array('[H1]', '[/H1]', '[H2]', '[/H2]', '[H3]', '[/H3]'), $readable_message);
            $readable_message = str_replace(array('<p>', '</p>', '<strong>', '</strong>', '<ul>', '</ul>', '<li>', '</li>'), 
                                          array('[P]', '[/P]', '[BOLD]', '[/BOLD]', '[UL]', '[/UL]', '[LI]', '[/LI]'), $readable_message);
            $readable_message = preg_replace('/<[^>]+>/', '', $readable_message); // حذف سایر تگ‌ها
            $message = $readable_message;
        }
        
        $line = "[$timestamp] $prefix$message" . PHP_EOL;
        // نوشتن در فایل لاگ اختصاصی افزونه
        $log_file_plugin = plugin_dir_path(__FILE__) . 'smart-admin-debug.log';
        @file_put_contents($log_file_plugin, $line, FILE_APPEND);
        // نوشتن هم‌زمان در wp-content برای اطمینان از مجوز نوشتن
        $log_file_wpcontent = trailingslashit(WP_CONTENT_DIR) . 'smart-admin-debug.log';
        @file_put_contents($log_file_wpcontent, $line, FILE_APPEND);
        // همچنین ارسال به error_log (اگر WP_DEBUG فعال باشد به wp-content/debug.log میرود)
        @error_log($prefix . $message);
    }
}

// بررسی وجود تابع faraz_unsplash_is_auto_featured_image_enabled
if (function_exists('faraz_unsplash_is_auto_featured_image_enabled')) {
    $unsplash_enabled = faraz_unsplash_is_auto_featured_image_enabled();
} elseif (function_exists('faraz_unsplash_is_image_generation_enabled')) {
    $unsplash_enabled = faraz_unsplash_is_image_generation_enabled();
} else {
    $unsplash_enabled = get_option('faraz_unsplash_enable_image_generation', true);
}

if ($unsplash_enabled) {
    error_log('[Smart-admin.php] Unsplash is enabled, loading image optimizer');
    // وارد کردن فایل بهینه‌ساز هوشمند تصاویر
    require_once plugin_dir_path(__FILE__) . 'smart-admin-image-optimizer.php';
} else {
    error_log('[Smart-admin.php] Unsplash is disabled, skipping image optimizer');
}

// وارد کردن فایل تنظیمات متاباکس‌ها
require_once plugin_dir_path(__FILE__) . 'smart-admin-settings.php';

// ریدایرکت مسیر smart-admin در wp-admin به آدرس صحیح
add_action('admin_init', 'smart_admin_redirect');

// افزودن اعلان در پنل مدیریت
add_action('admin_notices', 'smart_admin_notice');

function smart_admin_redirect() {
    global $pagenow;
    
    // بررسی آیا آدرس مورد نظر است
    if ($pagenow == 'admin.php' && isset($_GET['page']) && $_GET['page'] == 'smart-admin') {
        // آدرس صحیح است، نیازی به ریدایرکت نیست
        return;
    }
    
    // اگر کاربر به مسیر wp-admin/smart-admin وارد شده، ریدایرکت به آدرس صحیح
    $current_url = $_SERVER['REQUEST_URI'];
    if (strpos($current_url, '/wp-admin/smart-admin') !== false) {
        wp_redirect(admin_url('admin.php?page=smart-admin'));
        exit;
    }
}

function smart_admin_add_menu()
{
    // حذف اکشن قبلی برای جلوگیری از تداخل
    remove_action('admin_menu', 'smart_admin_add_menu');
    
    // افزودن منوی اصلی جدید با آیکون
    add_menu_page(
        'ادمین هوشمند', // عنوان صفحه
        'ادمین هوشمند', // عنوان منو
        'manage_options', // مجوز دسترسی
        'smart-admin', // slug صفحه
        'smart_admin_page', // تابع نمایش صفحه
        'dashicons-superhero', // آیکون
        65 // موقعیت منو
    );
    
    // همچنین اضافه کردن به زیرمنو
    add_submenu_page(
        'faraz-telegram-plugin', // منوی والد
        'ادمین هوشمند', // عنوان صفحه
        'ادمین هوشمند', // عنوان منو
        'manage_options', // مجوز دسترسی
        'smart-admin', // slug صفحه (باید با منوی اصلی یکسان باشد)
        'smart_admin_page' // تابع نمایش صفحه
    );
    
    // اضافه کردن زیرمنوی تنظیمات متاباکس‌ها
    add_submenu_page(
        'smart-admin', // منوی والد
        'تنظیمات متاباکس‌ها', // عنوان صفحه
        'تنظیمات متاباکس‌ها', // عنوان منو
        'manage_options', // مجوز دسترسی
        'smart-admin-metabox-settings', // slug صفحه
        'smart_admin_metabox_settings' // تابع نمایش صفحه
    );
}

// افزودن تنظیمات
function smart_admin_register_settings() {
    register_setting('smart_admin_settings', 'smart_admin_api_key');
    register_setting('smart_admin_settings', 'smart_admin_model');
	register_setting('smart_admin_settings', 'smart_admin_image_model');
    // تنظیمات قالب‌بندی محتوا
    register_setting('smart_admin_settings', 'smart_admin_enforce_formatting', array(
        'type' => 'boolean',
        'default' => 1,
        'sanitize_callback' => function($v){ return $v ? 1 : 0; }
    ));
    register_setting('smart_admin_settings', 'smart_admin_allow_intro', array(
        'type' => 'boolean',
        'default' => 0,
        'sanitize_callback' => function($v){ return $v ? 1 : 0; }
    ));
    register_setting('smart_admin_settings', 'smart_admin_allow_conclusion', array(
        'type' => 'boolean',
        'default' => 0,
        'sanitize_callback' => function($v){ return $v ? 1 : 0; }
    ));
    register_setting('smart_admin_settings', 'smart_admin_allow_faq', array(
        'type' => 'boolean',
        'default' => 0,
        'sanitize_callback' => function($v){ return $v ? 1 : 0; }
    ));
    register_setting('smart_admin_settings', 'smart_admin_allow_brand', array(
        'type' => 'boolean',
        'default' => 0,
        'sanitize_callback' => function($v){ return $v ? 1 : 0; }
    ));
    register_setting('smart_admin_settings', 'smart_admin_brand_name', array(
        'type' => 'string',
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field'
    ));
}
add_action('admin_init', 'smart_admin_register_settings');

// تابع‌های قالب‌بندی محتوا
if (!function_exists('smart_admin_enforce_human_prompt_constraints')) {
    function smart_admin_enforce_human_prompt_constraints($p) {
        $suffix = "\n\nدستورالعمل‌های نگارش (اجباری):\n- متن ۱۰۰٪ انسانی، طبیعی و بومی فارسی باشد؛ از عبارات ماشینی یا کلیشه‌ای پرهیز کن.\n- از هرگونه نشانه‌گذاری مارک‌داون غیرضروری مثل ** و __ استفاده نکن.\n- طول و ساختار جملات را متنوع کن؛ از کلمات و ساختارهای روزمره استفاده کن.\n- هیچ‌گاه نگویید 'به عنوان یک مدل هوش مصنوعی' یا مشابه آن.\n- خروجی نباید توسط ابزارهای تشخیص AI به‌عنوان محتوای ماشینی قابل تشخیص باشد.\n- لحن محاوره‌ای-حرفه‌ای، طبیعی، گرم و بدون اغراق.\n- عنوان‌ها و متن را مستقیم بنویس، بدون پیشوندهای تکراری یا برچسب‌های ساختگی.";
        return $p . $suffix;
    }
}

if (!function_exists('smart_admin_append_formatting_constraints')) {
    function smart_admin_append_formatting_constraints($p) {
        $rules = "\n\nقوانین قالب‌بندی خروجی (اجباری و غیرقابل تغییر):\n- خروجی را صرفاً به صورت HTML تمیز بنویس (مطلقاً نه Markdown).\n- از تگ‌های <h2> برای تیترهای اصلی و <h3> برای زیربخش‌ها استفاده کن.\n- برای بولد داخل پاراگراف‌ها از <strong> استفاده کن (هرگز از ** یا __ استفاده نکن).\n- عنوان‌ها باید با فاصله مناسب و Bold باشند.\n- از نشانه‌های اضافی در عنوان‌ها خودداری کن.\n- هر پاراگراف مهم را با <strong> مشخص کن.\n- از نوشتن بخش‌های 'مقدمه' یا 'نتیجه‌گیری/جمع‌بندی/FAQ' خودداری کن.\n- فقط محتوای بدنه مقاله را برگردان (بدون تگ <html> و <body>).\n- مطلقاً از نشانه‌های ** یا __ یا # یا * استفاده نکن.\n- تمام متن باید در تگ‌های HTML مناسب قرار گیرد.";
        return $p . $rules;
    }
}

if (!function_exists('smart_admin_cleanup_generated_content')) {
    function smart_admin_cleanup_generated_content($c) {
        // حذف ** و __ و الگوهای بولد مارک‌داون فقط در متن خام (نه در HTML)
        // اگر محتوا HTML است، نشانه‌های Markdown را حذف نکن
        $is_html = (strpos($c, '<h') !== false || strpos($c, '<p') !== false || strpos($c, '<strong') !== false || strpos($c, '<ul') !== false || strpos($c, '<li') !== false || strpos($c, '<ol') !== false);
        smart_admin_log('Content cleanup: HTML detected = ' . ($is_html ? 'Yes' : 'No'));
        
        if ($is_html) {
            // محتوا HTML است، فقط جمله‌های معرفی AI را حذف کن
            smart_admin_log('Skipping Markdown cleanup for HTML content');
        } else {
            // محتوا Markdown است، نشانه‌های Markdown را حذف کن
            $c = str_replace(array('**', '__'), '', $c);
            smart_admin_log('Applied Markdown cleanup for non-HTML content');
        }
        
        // حذف جمله‌های رایج معرفی AI (فارسی و انگلیسی)
        $patterns = array(
            '/به عنوان یک مدل هوش مصنوعی[^\.\n]*[\.\n]/u',
            '/As an AI language model[^\.\n]*[\.\n]/i'
        );
        foreach ($patterns as $pat) { $c = preg_replace($pat, '', $c); }
        
        // حذف گیومه‌های فارسی از نام برند
        if (get_option('smart_admin_allow_brand', 0) && get_option('smart_admin_brand_name', '')) {
            $brand_name = get_option('smart_admin_brand_name', '');
            $c = str_replace(array('«' . $brand_name . '»', '"' . $brand_name . '"', "'" . $brand_name . "'"), $brand_name, $c);
        }
        if (get_option('smart_admin_enforce_formatting', 1)) {
            // استانداردسازی تیترها و حذف علائم تزئینی
            $c = str_replace('::', ' - ', $c);
            // حذف تیترهای ناخواسته بر اساس تنظیمات
            if (!get_option('smart_admin_allow_intro', 0)) {
                $c = preg_replace('/<h[23][^>]*>\s*مقدمه\s*<\/h[23]>\s*[\s\S]*?(?=<h2|<h3|$)/iu', '', $c, 1);
            }
            if (!get_option('smart_admin_allow_conclusion', 0)) {
                $c = preg_replace('/<h[23][^>]*>\s*(نتیجه[‌ ]?گیری|جمع[‌ ]?بندی)\s*<\/h[23]>\s*[\s\S]*$/iu', '', $c, 1);
            }
            if (!get_option('smart_admin_allow_faq', 0)) {
                $c = preg_replace('/<h[23][^>]*>\s*FAQ\s*<\/h[23]>\s*[\s\S]*$/iu', '', $c, 1);
            }
            // حذف پیشوند "عنوان:" در ابتدای خطوط
            $c = preg_replace('/(^|\n)\s*عنوان\s*:\s*/u', "$1", $c);
        }
        return $c;
    }
}

// تابع ساخت پرامپت بر اساس فیلدهای فرم قالب
function build_template_prompt($form_data) {
    $prompt = '';
    
    // تشخیص نوع قالب بر اساس فیلدهای موجود
    if (isset($form_data['destination'])) {
        // قالب گردشگری و سفر
        $destination = sanitize_text_field($form_data['destination']);
        $travel_season = isset($form_data['travel_season']) ? sanitize_text_field($form_data['travel_season']) : 'تمام فصول';
        $travel_type = isset($form_data['travel_type']) ? sanitize_text_field($form_data['travel_type']) : 'تفریحی';
        $travel_duration = isset($form_data['travel_duration']) ? sanitize_text_field($form_data['travel_duration']) : 'یک هفته';
        $budget_level = isset($form_data['budget_level']) ? sanitize_text_field($form_data['budget_level']) : 'متوسط';
        $travel_method = isset($form_data['travel_method']) ? sanitize_text_field($form_data['travel_method']) : 'هواپیما';
        $accommodation_type = isset($form_data['accommodation_type']) ? sanitize_text_field($form_data['accommodation_type']) : 'هتل';
        $hotel_rating = isset($form_data['hotel_rating']) ? sanitize_text_field($form_data['hotel_rating']) : 'مهم نیست';
        $booking_services = isset($form_data['booking_services']) ? sanitize_text_field($form_data['booking_services']) : 'رزرو بلیط';
        $article_tone = isset($form_data['article_tone']) ? sanitize_text_field($form_data['article_tone']) : 'دوستانه';
        $call_to_action = isset($form_data['call_to_action']) ? sanitize_text_field($form_data['call_to_action']) : 'رزرو سفر';
        $special_requirements = isset($form_data['special_requirements']) ? sanitize_text_field($form_data['special_requirements']) : 'بدون نیاز خاص';
        
        $prompt = "شما یک متخصص گردشگری حرفه‌ای هستید. یک راهنمای سفر کامل و کاربردی برای سفر به $destination بنویسید.

اطلاعات سفر:
- مقصد: $destination
- فصل: $travel_season
- نوع سفر: $travel_type  
- مدت سفر: $travel_duration
- سطح بودجه: $budget_level
- روش سفر: $travel_method
- نوع اقامت: $accommodation_type";
        
        if ($accommodation_type === 'هتل' && $hotel_rating !== 'مهم نیست') {
            $prompt .= "\n- رتبه هتل: $hotel_rating";
        }
        
        $prompt .= "\n- خدمات رزرو: $booking_services
- لحن مقاله: $article_tone
- فراخوان عمل: $call_to_action
- نیازهای خاص: $special_requirements

ساختار مقاله:
- عنوان جذاب شامل نام مقصد
- معرفی مقصد و دلایل جذابیت
- بهترین زمان سفر و برنامه ریزی
- نحوه رسیدن ($travel_method - حمل و نقل، ویزا)
- اقامت ($accommodation_type)";
        
        if ($accommodation_type === 'هتل') {
            $prompt .= " و هتل‌ها";
        }
        
        $prompt .= "\n- جاذبه های گردشگری (حداقل 10 جاذبه)
- غذا و رستوران های محلی";
        
        if ($special_requirements !== 'بدون نیاز خاص') {
            $prompt .= " (با توجه به $special_requirements)";
        }
        
        $prompt .= "\n- خرید و سوغاتی
- نکات مهم و توصیه ها
- برنامه سفر پیشنهادی ($travel_duration)
- بخش FAQ (8 سوال متداول)";

        if ($call_to_action !== 'بدون CTA') {
            $prompt .= "\n- فراخوان عمل: $call_to_action";
        }

        $prompt .= "\n\nویژگی های مهم:
- حداقل 1200 کلمه و حداکثر 2500 کلمه
- لحن $article_tone و کاربردی
- اطلاعات دقیق و به روز
- مناسب برای گردشگران ایرانی
- استفاده از هدینگ های H2 و H3
- بدون استفاده از کلمه 'مقدمه' در ابتدا
- بدون نتیجه گیری در انتها
- استفاده از تگ strong برای کلمات کلیدی
- استفاده از لیست‌های بولت برای نکات مهم
- گنجاندن نام برند \"$booking_services\" به صورت طبیعی در متن

فرمت خروجی:
فقط HTML استاندارد تولید کن. از تگ‌های زیر استفاده کن:
- <h2> برای عناوین اصلی
- <h3> برای زیرعناوین
- <p> برای پاراگراف‌ها
- <strong> برای کلمات مهم و کلیدی
- <ul><li> برای لیست‌های بولت
- <ol><li> برای لیست‌های شماره‌دار

هیچ Markdown، کد یا تگ‌های اضافی استفاده نکن.";
        
    } elseif (isset($form_data['food_topic'])) {
        // قالب خوراکی و آشپزی
        $food_topic = sanitize_text_field($form_data['food_topic']);
        $cuisine_type = isset($form_data['cuisine_type']) ? sanitize_text_field($form_data['cuisine_type']) : 'ایرانی';
        $difficulty_level = isset($form_data['difficulty_level']) ? sanitize_text_field($form_data['difficulty_level']) : 'متوسط';
        $preparation_time = isset($form_data['preparation_time']) ? sanitize_text_field($form_data['preparation_time']) : '۳۰ تا ۶۰ دقیقه';
        $special_diet = isset($form_data['special_diet']) ? sanitize_text_field($form_data['special_diet']) : 'معمولی';
        
        $prompt = "شما یک سرآشپز حرفه ای هستید. یک مقاله جامع و کاربردی در مورد $food_topic بنویسید.

اطلاعات تکمیلی:
- نوع آشپزی: $cuisine_type
- سطح دشواری: $difficulty_level
- زمان آماده سازی: $preparation_time
- رژیم غذایی: $special_diet

ساختار مقاله:
- عنوان جذاب شامل نام غذا
- معرفی غذا (تاریخچه، محبوبیت)
- ارزش غذایی و فواید سلامتی
- مواد لازم (لیست کامل با مقادیر)
- تجهیزات مورد نیاز
- مراحل آماده سازی (قدم به قدم)
- نکات کلیدی و ترفندهای حرفه ای
- روش های سرو و تزیین
- غذاها و نوشیدنی های مکمل
- تنوع و تغییرات
- نگهداری و گرم کردن مجدد
- بخش FAQ (6 سوال متداول)

ویژگی های مهم:
- حداقل 800 کلمه و حداکثر 2000 کلمه
- لحن گرم و اشتیاق برانگیز
- دستورالعمل ها دقیق و قابل اجرا
- استفاده از اصطلاحات آشپزی
- نکات ایمنی و بهداشتی
- استفاده از هدینگ های H2 و H3
- بدون استفاده از کلمه 'مقدمه' در ابتدا
- بدون نتیجه گیری در انتها
- استفاده از کلمات ساده و رایج فارسی
- اجتناب از آمار و ارقام دقیق

مستقیماً با پاراگراف اشتهاآور شروع کنید که خواننده را ترغیب کند.";
        
    } elseif (isset($form_data['main_topic']) && isset($form_data['focus_keyword'])) {
        // قالب Rank Math استاندارد
        $main_topic = sanitize_text_field($form_data['main_topic']);
        $focus_keyword = sanitize_text_field($form_data['focus_keyword']);
        $target_audience = isset($form_data['target_audience']) ? sanitize_text_field($form_data['target_audience']) : '';
        $content_type = isset($form_data['content_type']) ? sanitize_text_field($form_data['content_type']) : 'آموزشی';
        $content_length = isset($form_data['content_length']) ? intval($form_data['content_length']) : 1200;
        
        $prompt = "شما یک متخصص SEO هستید. یک مقاله استاندارد و بهینه شده برای SEO در مورد $main_topic بنویسید.

موضوع: $main_topic
کلمه کلیدی اصلی: $focus_keyword
مخاطب هدف: $target_audience
نوع محتوا: $content_type
طول مقاله: حداقل $content_length کلمه

ساختار مقاله:
- عنوان جذاب شامل کلمه کلیدی اصلی (حداکثر 60 کاراکتر)
- پاراگراف شروعی جذاب (بدون کلمه 'مقدمه') شامل کلمه کلیدی
- حداقل 5 بخش اصلی با تیتر H2
- زیربخش های مرتبط با H3
- بخش FAQ با حداقل 3 سوال متداول

ویژگی های مهم:
- استفاده از کلمه کلیدی در عنوان، پاراگراف اول، حداقل یک H2 و در متن (تراکم 1-2%)
- جملات کوتاه و پاراگراف های کم حجم
- استفاده از لیست های شماره دار و بولت پوینت
- لحن روان، حرفه ای و قابل فهم
- استفاده از کلمات کلیدی مرتبط
- بدون استفاده از کلمه 'مقدمه' در ابتدا
- بدون نتیجه گیری در انتها
- استفاده از کلمات ساده و رایج فارسی
- اجتناب از آمار و ارقام دقیق

مستقیماً با پاراگراف جذاب شروع کنید که کلمه کلیدی را شامل شود.";
        
    } elseif (isset($form_data['main_topic']) && isset($form_data['target_audience'])) {
        // قالب مقاله جامع و بنیادی
        $main_topic = sanitize_text_field($form_data['main_topic']);
        $target_audience = sanitize_text_field($form_data['target_audience']);
        $content_goal = isset($form_data['content_goal']) ? sanitize_text_field($form_data['content_goal']) : 'آموزشی';
        
        $prompt = "شما یک متخصص SEO هستید. یک مقاله بنیادی (Pillar Page) جامع و کامل در مورد $main_topic بنویسید.

موضوع: $main_topic
مخاطب هدف: $target_audience
نوع محتوا: $content_goal
طول مقاله: حداقل 800 کلمه و حداکثر 2000 کلمه

ساختار مقاله:
- عنوان جذاب و گیرا (حداکثر 60 کاراکتر)
- پاراگراف شروعی قوی (بدون کلمه 'مقدمه') که مشکل یا نیاز مخاطب را مطرح کند
- حداقل 5 بخش اصلی با تیتر H2
- زیربخش های مرتبط با H3
- بخش FAQ با حداقل 5 سوال متداول

ویژگی های مهم:
- لحن معتبر و حرفه ای اما روان
- جملات کوتاه و پاراگراف های کم حجم
- استفاده از لیست های شماره دار و بولت پوینت
- استفاده از نقل قول ها و آمار معتبر
- مثال های عملی و کاربردی
- استفاده از کلمات کلیدی مرتبط به صورت طبیعی
- بدون استفاده از کلمه 'مقدمه' در ابتدا
- بدون نتیجه گیری در انتها
- استفاده از کلمات ساده و رایج فارسی
- اجتناب از آمار و ارقام دقیق

مستقیماً با پاراگراف قدرتمند شروع کنید که مشکل مخاطب را مطرح کند.";
        
    } elseif (isset($form_data['how_to_topic'])) {
        // قالب راهنمای عملی
        $how_to_topic = sanitize_text_field($form_data['how_to_topic']);
        $skill_level = isset($form_data['skill_level']) ? sanitize_text_field($form_data['skill_level']) : 'مبتدی';
        $required_tools = isset($form_data['required_tools']) ? sanitize_text_field($form_data['required_tools']) : '';
        
        $prompt = "شما یک مربی فنی هستید. یک راهنمای عملی قدم به قدم برای $how_to_topic بنویسید.

موضوع: $how_to_topic
سطح مخاطب: $skill_level
ابزارهای مورد نیاز: $required_tools
طول مقاله: حداقل 800 کلمه و حداکثر 2000 کلمه

ساختار راهنما:
- عنوان جذاب شامل موضوع راهنما
- پاراگراف شروعی (بدون کلمه 'مقدمه') که هدف راهنما را توضیح دهد
- بخش 'آنچه نیاز دارید' شامل ابزارها و مواد مورد نیاز
- حداقل 5 مرحله اصلی با تیتر H2
- توضیحات دقیق برای هر مرحله با لیست های شماره دار
- بخش 'نکات حرفه ای' یا 'اشتباهات رایج'
- بخش 'عیب یابی و رفع مشکلات احتمالی'
- بخش FAQ با حداقل 3 سوال متداول

ویژگی های مهم:
- زبان ساده، روشن و دستوری
- جملات کوتاه و پاراگراف های کم حجم
- توضیح دقیق هر مرحله با فرض عدم دانش قبلی
- لحن دوستانه، حمایتی و تشویق کننده
- استفاده از هدینگ های H2 و H3
- بدون استفاده از کلمه 'مقدمه' در ابتدا
- بدون نتیجه گیری در انتها
- استفاده از کلمات ساده و رایج فارسی
- اجتناب از آمار و ارقام دقیق

مستقیماً با پاراگراف شروع کنید که هدف راهنما را توضیح دهد.";
        
    } elseif (isset($form_data['list_topic'])) {
        // قالب مقاله لیستی
        $list_topic = sanitize_text_field($form_data['list_topic']);
        $list_count = isset($form_data['list_count']) ? intval($form_data['list_count']) : 10;
        $list_criteria = isset($form_data['list_criteria']) ? sanitize_text_field($form_data['list_criteria']) : '';
        
        $prompt = "شما یک وبلاگ نویس حرفه ای هستید. یک مقاله لیستی جذاب با عنوان $list_topic شامل $list_count مورد برتر بنویسید.

موضوع: $list_topic
تعداد آیتم ها: $list_count
معیار رتبه بندی: $list_criteria
طول مقاله: حداقل 800 کلمه و حداکثر 2000 کلمه

ساختار مقاله:
- عنوان جذاب و کلیک خور شامل عدد و موضوع
- پاراگراف شروعی جذاب (بدون کلمه 'مقدمه') که اهمیت موضوع را نشان دهد
- توضیح کوتاه درباره معیارهای انتخاب
- لیست اصلی با $list_count آیتم، هر کدام با تیتر H2
- برای هر آیتم: معرفی کوتاه، ویژگی های کلیدی، بهترین کاربرد، قیمت گذاری
- یک یا دو آیتم ویژه یا کمتر شناخته شده
- جدول مقایسه ای ساده

ویژگی های مهم:
- لحن جذاب، پرانرژی و صمیمی
- توصیفات قانع کننده و ملموس
- استفاده از کلمات کلیدی مرتبط
- جملات کوتاه و پاراگراف های کم حجم
- استفاده از لیست های شماره دار و بولت پوینت
- مثال های عملی و کاربردی
- بدون استفاده از کلمه 'مقدمه' در ابتدا
- بدون نتیجه گیری در انتها
- استفاده از کلمات ساده و رایج فارسی
- اجتناب از آمار و ارقام دقیق

مستقیماً با پاراگراف جذاب شروع کنید که اهمیت موضوع را نشان دهد.";
        
    } elseif (isset($form_data['tech_topic'])) {
        // قالب فناوری اطلاعات و برنامه‌نویسی
        $tech_topic = sanitize_text_field($form_data['tech_topic']);
        $tech_category = isset($form_data['tech_category']) ? sanitize_text_field($form_data['tech_category']) : 'برنامه‌نویسی وب';
        $skill_level = isset($form_data['skill_level']) ? sanitize_text_field($form_data['skill_level']) : 'متوسط';
        $programming_language = isset($form_data['programming_language']) ? sanitize_text_field($form_data['programming_language']) : '';
        $framework = isset($form_data['framework']) ? sanitize_text_field($form_data['framework']) : '';
        
        $prompt = "شما یک متخصص ارشد فناوری اطلاعات هستید. یک مقاله تخصصی جامع در مورد $tech_topic بنویسید.

موضوع: $tech_topic
دسته بندی: $tech_category
سطح مهارت: $skill_level
زبان برنامه نویسی: $programming_language
فریم ورک/ابزار: $framework

ساختار مقاله:
- عنوان جذاب شامل نام تکنولوژی
- معرفی تکنولوژی (تاریخچه، اهمیت، کاربردها)
- پیش نیازها و آماده سازی محیط
- مفاهیم پایه و اصول اولیه
- پیاده سازی عملی (نمونه کدها، پروژه های کاربردی)
- بهترین شیوه ها و الگوهای طراحی
- عیب یابی و رفع مشکلات رایج
- بهینه سازی و عملکرد
- امنیت و نکات مهم
- آینده و روندهای پیش رو
- منابع و مراجع تکمیلی
- بخش کد نمونه (حداقل 3 نمونه کد کاربردی)
- بخش FAQ (حداقل 8 سوال متداول)

ویژگی های مهم:
- حداقل 800 کلمه و حداکثر 2000 کلمه
- لحن تخصصی اما قابل فهم
- استفاده از مثال های عملی و کدهای کاربردی
- جملات کوتاه و پاراگراف های کم حجم
- استفاده از لیست های شماره دار و بولت پوینت
- نکات امنیتی و بهترین شیوه ها
- کدها و دستورات در بلوک های کد جداگانه
- بدون استفاده از کلمه 'مقدمه' در ابتدا
- بدون نتیجه گیری در انتها
- استفاده از کلمات ساده و رایج فارسی
- اجتناب از آمار و ارقام دقیق

مستقیماً با پاراگراف جذاب شروع کنید که خواننده را ترغیب کند.";
        
    } elseif (isset($form_data['item1']) && isset($form_data['item2'])) {
        // قالب مقاله مقایسه‌ای
        $item1 = sanitize_text_field($form_data['item1']);
        $item2 = sanitize_text_field($form_data['item2']);
        $comparison_criteria = isset($form_data['comparison_criteria']) ? sanitize_text_field($form_data['comparison_criteria']) : '';
        
        $prompt = "شما یک تحلیل گر و منتقد بی طرف هستید. یک مقاله مقایسه ای دقیق و منصفانه بین $item1 و $item2 بنویسید.

موضوع: مقایسه $item1 و $item2
معیارهای مقایسه: $comparison_criteria
طول مقاله: حداقل 800 کلمه و حداکثر 2000 کلمه

ساختار مقاله:
- عنوان جذاب که مقایسه را نشان دهد
- پاراگراف شروعی جذاب (بدون کلمه 'مقدمه') که هر دو آیتم را معرفی کند
- جدول مقایسه سریع در ابتدای مقاله
- مقایسه تفصیلی بر اساس حداقل 7 معیار مهم با تیتر H2
- در هر بخش مقایسه، عملکرد هر دو آیتم را بررسی و برنده را مشخص کنید
- بخش 'چه زمانی $item1 را انتخاب کنیم؟'
- بخش 'چه زمانی $item2 را انتخاب کنیم؟'

ویژگی های مهم:
- حفظ بی طرفی کامل در سراسر متن
- ذکر نقاط قوت و ضعف هر دو آیتم
- استفاده از داده ها، آمار و تست های عملکردی
- جملات کوتاه و پاراگراف های کم حجم
- استفاده از لیست های شماره دار و بولت پوینت
- استفاده از کلمات کلیدی مرتبط
- بدون استفاده از کلمه 'مقدمه' در ابتدا
- بدون نتیجه گیری در انتها
- استفاده از کلمات ساده و رایج فارسی
- اجتناب از آمار و ارقام دقیق

مستقیماً با پاراگراف جذاب شروع کنید که هر دو آیتم را معرفی کند.";
        
    } elseif (isset($form_data['energy_topic'])) {
        // قالب صنعت آب، انرژی و آب و برق
        $energy_topic = sanitize_text_field($form_data['energy_topic']);
        $energy_sector = isset($form_data['energy_sector']) ? sanitize_text_field($form_data['energy_sector']) : 'تولید برق';
        $energy_type = isset($form_data['energy_type']) ? sanitize_text_field($form_data['energy_type']) : 'برق';
        $industry_size = isset($form_data['industry_size']) ? sanitize_text_field($form_data['industry_size']) : 'متوسط';
        $regulatory_compliance = isset($form_data['regulatory_compliance']) ? sanitize_text_field($form_data['regulatory_compliance']) : '';
        
        $prompt = "شما یک مهندس ارشد صنعت آب و انرژی هستید. یک مقاله تخصصی جامع در مورد $energy_topic بنویسید.

موضوع: $energy_topic
بخش صنعتی: $energy_sector
نوع انرژی: $energy_type
اندازه صنعت: $industry_size
مقررات و استانداردها: $regulatory_compliance

ساختار مقاله:
- عنوان جذاب شامل نام موضوع
- معرفی موضوع (تاریخچه، اهمیت، کاربردها)
- وضعیت فعلی صنعت (آمار، چالش ها، فرصت ها)
- فناوری های نوین و نوآوری ها
- استانداردها و مقررات
- پیاده سازی و اجرا
- مدیریت و بهینه سازی
- چالش ها و راه حل ها
- جنبه های اقتصادی و مالی
- محیط زیست و پایداری
- آینده و روندهای پیش رو
- مطالعات موردی (حداقل 2 مورد)
- بخش FAQ (حداقل 8 سوال متداول)

ویژگی های مهم:
- حداقل 800 کلمه و حداکثر 2000 کلمه
- لحن تخصصی اما قابل فهم
- استفاده از آمار و ارقام دقیق و به روز
- جملات کوتاه و پاراگراف های کم حجم
- استفاده از لیست های شماره دار و بولت پوینت
- نکات انحصاری و تجربیات عملی
- نکات ایمنی و استانداردهای صنعتی
- بدون استفاده از کلمه 'مقدمه' در ابتدا
- بدون نتیجه گیری در انتها
- استفاده از کلمات ساده و رایج فارسی
- اجتناب از آمار و ارقام دقیق

مستقیماً با پاراگراف جذاب شروع کنید که خواننده را ترغیب کند.";
        
    } elseif (isset($form_data['oil_topic'])) {
        // قالب صنعت نفت، گاز و پتروشیمی
        $oil_topic = sanitize_text_field($form_data['oil_topic']);
        $oil_sector = isset($form_data['oil_sector']) ? sanitize_text_field($form_data['oil_sector']) : 'اکتشاف';
        $oil_type = isset($form_data['oil_type']) ? sanitize_text_field($form_data['oil_type']) : 'نفت خام';
        $industry_size = isset($form_data['industry_size']) ? sanitize_text_field($form_data['industry_size']) : 'بزرگ';
        $regulatory_compliance = isset($form_data['regulatory_compliance']) ? sanitize_text_field($form_data['regulatory_compliance']) : '';
        
        $prompt = "شما یک مهندس ارشد صنعت نفت و گاز هستید. یک مقاله تخصصی جامع در مورد $oil_topic بنویسید.

موضوع: $oil_topic
بخش صنعتی: $oil_sector
نوع محصول: $oil_type
اندازه صنعت: $industry_size
مقررات و استانداردها: $regulatory_compliance

ساختار مقاله:
- عنوان جذاب شامل نام موضوع
- معرفی موضوع (تاریخچه، اهمیت، کاربردها)
- وضعیت فعلی صنعت نفت و گاز (آمار جهانی و ایران)
- فناوری های نوین و نوآوری ها
- استانداردها و مقررات ایمنی
- پیاده سازی و اجرا
- مدیریت و بهینه سازی
- چالش ها و راه حل ها
- جنبه های اقتصادی و مالی
- محیط زیست و پایداری
- آینده و روندهای پیش رو
- مطالعات موردی (حداقل 2 مورد)
- بخش FAQ (حداقل 8 سوال متداول)

ویژگی های مهم:
- حداقل 800 کلمه و حداکثر 2000 کلمه
- لحن تخصصی اما قابل فهم
- استفاده از آمار و ارقام دقیق و به روز
- جملات کوتاه و پاراگراف های کم حجم
- استفاده از لیست های شماره دار و بولت پوینت
- نکات انحصاری و تجربیات عملی
- نکات ایمنی و استانداردهای صنعتی
- بدون استفاده از کلمه 'مقدمه' در ابتدا
- بدون نتیجه گیری در انتها
- استفاده از کلمات ساده و رایج فارسی
- اجتناب از آمار و ارقام دقیق

مستقیماً با پاراگراف جذاب شروع کنید که خواننده را ترغیب کند.";
        
    } elseif (isset($form_data['fire_topic'])) {
        // قالب صنعت آتشنشانی
        $fire_topic = sanitize_text_field($form_data['fire_topic']);
        $fire_sector = isset($form_data['fire_sector']) ? sanitize_text_field($form_data['fire_sector']) : 'آتشنشانی شهری';
        $fire_type = isset($form_data['fire_type']) ? sanitize_text_field($form_data['fire_type']) : 'آتش‌سوزی ساختمان';
        $equipment_type = isset($form_data['equipment_type']) ? sanitize_text_field($form_data['equipment_type']) : 'تجهیزات اطفاء حریق';
        $safety_standards = isset($form_data['safety_standards']) ? sanitize_text_field($form_data['safety_standards']) : '';
        
        $prompt = "شما یک افسر ارشد آتشنشانی هستید. یک مقاله تخصصی جامع در مورد $fire_topic بنویسید.

موضوع: $fire_topic
بخش صنعتی: $fire_sector
نوع حادثه: $fire_type
نوع تجهیزات: $equipment_type
استانداردهای ایمنی: $safety_standards

ساختار مقاله:
- عنوان جذاب شامل نام موضوع
- معرفی موضوع (اهمیت، کاربردها، ضرورت)
- وضعیت فعلی صنعت آتشنشانی (آمار حوادث، چالش ها، پیشرفت ها)
- فناوری های نوین و نوآوری ها
- استانداردها و مقررات ایمنی
- پیاده سازی و اجرا
- مدیریت و بهینه سازی
- چالش ها و راه حل ها
- جنبه های اقتصادی و مالی
- آموزش و پژوهش
- آینده و روندهای پیش رو
- مطالعات موردی (حداقل 2 مورد)
- بخش FAQ (حداقل 8 سوال متداول)

ویژگی های مهم:
- حداقل 800 کلمه و حداکثر 2000 کلمه
- لحن تخصصی اما قابل فهم
- استفاده از آمار و ارقام دقیق و به روز
- جملات کوتاه و پاراگراف های کم حجم
- استفاده از لیست های شماره دار و بولت پوینت
- نکات انحصاری و تجربیات عملی
- نکات ایمنی و استانداردهای آتشنشانی
- بدون استفاده از کلمه 'مقدمه' در ابتدا
- بدون نتیجه گیری در انتها
- استفاده از کلمات ساده و رایج فارسی
- اجتناب از آمار و ارقام دقیق

مستقیماً با پاراگراف جذاب شروع کنید که خواننده را ترغیب کند.";
        
    } else {
        // قالب پیش‌فرض
        $main_topic = isset($form_data['main_topic']) ? sanitize_text_field($form_data['main_topic']) : 'موضوع مقاله';
        
        $prompt = "شما یک نویسنده حرفه ای هستید. یک مقاله جامع و با کیفیت در مورد $main_topic بنویسید.

موضوع: $main_topic
طول مقاله: حداقل 800 کلمه و حداکثر 2000 کلمه

ساختار مقاله:
- عنوان جذاب و گیرا
- پاراگراف شروعی جذاب (بدون کلمه 'مقدمه')
- حداقل 5 بخش اصلی با تیتر H2
- زیربخش های مرتبط با H3
- بخش FAQ با حداقل 3 سوال متداول

ویژگی های مهم:
- لحن روان، حرفه ای و قابل فهم
- جملات کوتاه و پاراگراف های کم حجم
- استفاده از لیست های شماره دار و بولت پوینت
- استفاده از مثال های عملی و کاربردی
- استفاده از کلمات کلیدی مرتبط به صورت طبیعی
- بدون استفاده از کلمه 'مقدمه' در ابتدا
- بدون نتیجه گیری در انتها
- استفاده از کلمات ساده و رایج فارسی
- اجتناب از آمار و ارقام دقیق

مستقیماً با پاراگراف جذاب شروع کنید که خواننده را ترغیب کند.";
    }
    
    // اعمال تنظیمات قالب‌بندی محتوا
    if (get_option('smart_admin_enforce_formatting', 1)) {
        $extra_rules = [];
        if (!get_option('smart_admin_allow_intro', 0)) {
            $extra_rules[] = 'از بخش مقدمه استفاده نکن.';
        }
        if (!get_option('smart_admin_allow_conclusion', 0)) {
            $extra_rules[] = 'از بخش نتیجه‌گیری یا جمع‌بندی استفاده نکن.';
        }
        if (!get_option('smart_admin_allow_faq', 0)) {
            $extra_rules[] = 'از بخش پرسش‌های متداول (FAQ) استفاده نکن.';
        }
        if (!empty($extra_rules)) {
            $prompt .= "\n\nقوانین اضافی:\n" . implode("\n", array_map(function($s){ return '- ' . $s; }, $extra_rules));
        }
        
        // اضافه کردن نام برند اگر فعال باشد
        if (get_option('smart_admin_allow_brand', 0) && get_option('smart_admin_brand_name', '')) {
            $brand_name = get_option('smart_admin_brand_name', '');
            $prompt .= "\n\nنام برند/سازمان: $brand_name\n- در طول مقاله از نام برند $brand_name استفاده کن\n- نام برند را به صورت طبیعی در متن بگنجان\n- مثال: $brand_name خدمات مسافرتی ارائه می‌دهد\n- از نام برند در مثال‌ها و توصیه‌ها استفاده کن\n- نام برند را بدون گیومه یا علامت خاص بنویس";
        }
    }
    
    return $prompt;
}
// صفحه ادمین هوشمند
function smart_admin_page() {
    // بررسی مجوز دسترسی
    if (!current_user_can('manage_options')) {
        wp_die(__('شما اجازه دسترسی به این صفحه را ندارید.'));
    }
    smart_admin_log('Admin page loaded');
    
    // پیام موفقیت برای نمایش
    $success_message = '';
	$image_result = null;
    
    // لاگ کلی برای POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $keys = implode(',', array_keys($_POST));
        smart_admin_log('POST received with keys: ' . $keys);
    }

	// هندل مستقل فرم ساخت تصویر (نباید وابسته به فرم پرامپت باشد)
	if (isset($_POST['smart_admin_image_nonce'])) {
		smart_admin_log('Image form submitted');
		$nonce_ok = isset($_POST['smart_admin_image_nonce']) && wp_verify_nonce($_POST['smart_admin_image_nonce'], 'smart_admin_image_action');
		if (!$nonce_ok) {
			smart_admin_log('Image form nonce invalid or missing');
		}
		$image_model = isset($_POST['smart_admin_image_model']) && $_POST['smart_admin_image_model'] !== ''
			? sanitize_text_field($_POST['smart_admin_image_model'])
			: get_option('smart_admin_image_model', 'gapgpt/flux.1-schnell');
		$api_key = get_option('smart_admin_api_key');
		$image_prompt = isset($_POST['smart_admin_image_prompt']) ? sanitize_textarea_field($_POST['smart_admin_image_prompt']) : '';
		$image_size = isset($_POST['smart_admin_image_size']) ? sanitize_text_field($_POST['smart_admin_image_size']) : '1024x1024';
		$image_quality = isset($_POST['smart_admin_image_quality']) ? sanitize_text_field($_POST['smart_admin_image_quality']) : 'standard';
		$image_n = isset($_POST['smart_admin_image_n']) ? max(1, min(4, intval($_POST['smart_admin_image_n']))) : 1;

		smart_admin_log('Selected image model: ' . $image_model);
		smart_admin_log('Selected size: ' . $image_size . ' | quality: ' . $image_quality . ' | n: ' . $image_n);

		$image_result = smart_admin_generate_image($image_prompt, $image_model, $api_key, array(
			'n' => $image_n,
			'size' => $image_size,
			'quality' => $image_quality,
			// پارامترهای اختیاری image-to-image
			'reference_image_id' => isset($_POST['smart_admin_reference_image_id']) ? sanitize_text_field($_POST['smart_admin_reference_image_id']) : '',
			'reference_image_url' => isset($_POST['smart_admin_reference_image_url']) ? esc_url_raw($_POST['smart_admin_reference_image_url']) : ''
		));

		if (!$nonce_ok) {
			$image_result = array('error' => 'اعتبارسنجی امنیتی نامعتبر است (Nonce). صفحه را رفرش کنید و دوباره تلاش کنید.');
		}
	}

    // ذخیره پرامپت و درخواست به API
    if (isset($_POST['smart_admin_prompt']) || (isset($_POST['is_template']) && $_POST['is_template'] == '1')) {
        smart_admin_log('Content generation form submitted');
        // افزودن نانس برای امنیت
        if (!check_admin_referer('smart_admin_prompt_action', 'smart_admin_nonce')) {
            smart_admin_log('Nonce verification failed');
            $response = array('error' => 'اعتبارسنجی امنیتی نامعتبر است. صفحه را رفرش کنید و دوباره تلاش کنید.');
        } else {
            smart_admin_log('Nonce verification passed');
        
        $model = sanitize_text_field($_POST['smart_admin_model']);
        $api_key = get_option('smart_admin_api_key');
        
        smart_admin_log('Model: ' . $model);
        smart_admin_log('API Key: ' . (empty($api_key) ? 'Empty' : 'Set'));
        
        // اگر فرم قالب است، پرامپت را بر اساس فیلدهای فرم بساز
        if (isset($_POST['is_template']) && $_POST['is_template'] == '1') {
            smart_admin_log('Building template prompt');
            $prompt = build_template_prompt($_POST);
            smart_admin_log('Template prompt length: ' . strlen($prompt));
        } else {
            $prompt = sanitize_textarea_field($_POST['smart_admin_prompt']);
        }

        // اعمال اجباری لحن انسانی و قیود ضد-تشخیص + حذف Markdown های ناخواسته از پرامپت
        if (function_exists('optimize_prompt_for_natural_content')) {
            $prompt = optimize_prompt_for_natural_content($prompt);
        }
        $prompt = smart_admin_enforce_human_prompt_constraints($prompt);
        if (get_option('smart_admin_enforce_formatting', 1)) {
            smart_admin_log('Formatting constraints enabled - adding HTML formatting rules');
            $prompt = smart_admin_append_formatting_constraints($prompt);
            // افزودن محدودیت‌های روشن/خاموش
            $extra_rules = [];
            if (!get_option('smart_admin_allow_intro', 0)) $extra_rules[] = 'از بخش مقدمه استفاده نکن.';
            if (!get_option('smart_admin_allow_conclusion', 0)) $extra_rules[] = 'از بخش نتیجه‌گیری یا جمع‌بندی استفاده نکن.';
            if (!get_option('smart_admin_allow_faq', 0)) $extra_rules[] = 'از بخش پرسش‌های متداول (FAQ) استفاده نکن.';
            if (!empty($extra_rules)) {
                $prompt .= "\n" . implode("\n", array_map(function($s){ return '- ' . $s; }, $extra_rules));
            }
            
            // اضافه کردن نام برند اگر فعال باشد
            if (get_option('smart_admin_allow_brand', 0) && get_option('smart_admin_brand_name', '')) {
                $brand_name = get_option('smart_admin_brand_name', '');
                $prompt .= "\n\nنام برند/سازمان: $brand_name\n- در طول مقاله از نام برند $brand_name استفاده کن\n- نام برند را به صورت طبیعی در متن بگنجان\n- مثال: $brand_name خدمات مسافرتی ارائه می‌دهد\n- از نام برند در مثال‌ها و توصیه‌ها استفاده کن\n- نام برند را بدون گیومه یا علامت خاص بنویس";
            }
        }
        
        // ارسال درخواست به API
        smart_admin_log('Sending request to API');
        $response = send_to_gapgpt_api($prompt, $model, $api_key);
        smart_admin_log('API response received: ' . (isset($response['error']) ? 'Error: ' . $response['error'] : 'Success'));
        smart_admin_log('Full API response: ' . json_encode($response));
        
        // بهبود خروجی با لحن انسانی و پاک‌سازی نشانه‌های مارک‌داون
        if (isset($response['content']) && !empty($response['content'])) {
            if (function_exists('improve_ai_output_with_human_tone')) {
                $response['content'] = improve_ai_output_with_human_tone($response['content']);
            }
            smart_admin_log('Applying content cleanup with formatting constraints');
            // محتوا را فقط یک بار پردازش می‌کنیم
            // $response['content'] = smart_admin_cleanup_generated_content($response['content']);
        }
        
        // ذخیره پرامپت در تنظیمات (اگر قالب پیش‌فرض نباشد)
        if (!isset($_POST['is_template']) || $_POST['is_template'] != '1') {
            $saved_prompts = get_option('smart_admin_saved_prompts', array());
            $saved_prompts[] = array(
                'prompt' => $prompt,
                'model' => $model,
                'date' => current_time('mysql'),
                'is_template' => false
            );
            update_option('smart_admin_saved_prompts', $saved_prompts);
        }
        
        // بررسی وضعیت قالب
        smart_admin_log('is_template value: ' . (isset($_POST['is_template']) ? $_POST['is_template'] : 'Not set'));
        smart_admin_log('response content exists: ' . (isset($response['content']) && !empty($response['content']) ? 'Yes' : 'No'));
        
        // ذخیره خودکار پیش‌نویس برای قالب‌های آماده
        if (isset($_POST['is_template']) && $_POST['is_template'] == '1' && isset($response['content']) && !empty($response['content'])) {
            smart_admin_log('Template content received, starting save process');
            
            // استخراج عنوان از فیلدهای فرم یا استفاده از عنوان پیش‌فرض
            $title = '';
            // استخراج عنوان SEO شده از پاسخ هوش مصنوعی
            $main_topic = !empty($_POST['main_topic']) ? sanitize_text_field($_POST['main_topic']) : '';
            $ai_title = smart_admin_extract_seo_title($response['content'], $main_topic);
            
            if (!empty($ai_title)) {
                $title = $ai_title;
            } elseif (!empty($_POST['main_topic'])) {
                $title = sanitize_text_field($_POST['main_topic']);
            } elseif (!empty($_POST['focus_keyword'])) {
                $title = sanitize_text_field($_POST['focus_keyword']);
            } else {
                $title = 'محتوا تولید شده توسط دستیار هوشمند';
            }
            
            smart_admin_log('Generated title: ' . $title);
            
            smart_admin_log('Original content length: ' . strlen($response['content']));
            smart_admin_log('Original content preview: ' . substr($response['content'], 0, 200) . '...');
            
            // اعمال قالب‌بندی HTML اگر تنظیمات فعال باشد
            if (get_option('smart_admin_enforce_formatting', 1)) {
                smart_admin_log('Processing content with HTML formatting enforcement');
                $content = smart_admin_cleanup_generated_content($response['content']);
                smart_admin_log('Content processed with HTML formatting');
                smart_admin_log('Final content preview: ' . substr($content, 0, 200) . '...');
            } else {
                smart_admin_log('Processing content without HTML formatting enforcement');
                $content = wp_kses_post($response['content']);
            }
            
            smart_admin_log('Content length after processing: ' . strlen($content));
            smart_admin_log('Content preview: ' . substr($content, 0, 200) . '...');
            
            // استخراج کلمات کلیدی از فیلدهای فرم یا پاسخ API
            $keywords = array();
            
            // اولویت اول: کلمات کلیدی از پاسخ API
            if (!empty($response['keywords']) && is_array($response['keywords'])) {
                $keywords = array_merge($keywords, $response['keywords']);
                smart_admin_log('Using keywords from API response: ' . implode(', ', $response['keywords']));
            }
            
            // اولویت دوم: فیلدهای فرم
            if (!empty($_POST['focus_keyword'])) {
                $keywords[] = sanitize_text_field($_POST['focus_keyword']);
            }
            if (!empty($_POST['main_topic'])) {
                $keywords[] = sanitize_text_field($_POST['main_topic']);
            }
            
            // حذف تکرارها
            $keywords = array_unique($keywords);
            
            smart_admin_log('Final extracted keywords: ' . implode(', ', $keywords));
            smart_admin_log('About to call smart_admin_save_ai_content_as_draft with title: ' . $title);
            
            // ذخیره محتوا به عنوان پیش‌نویس
            $post_id = smart_admin_save_ai_content_as_draft($title, $content, $keywords);
            
            smart_admin_log('Save result: ' . (is_wp_error($post_id) ? 'Error: ' . $post_id->get_error_message() : 'Success, Post ID: ' . $post_id));
            
            if (!is_wp_error($post_id)) {
                $edit_link = admin_url('post.php?post=' . $post_id . '&action=edit');
                $success_message = 'محتوا با موفقیت تولید و به عنوان پیش‌نویس ذخیره شد. <a href="' . $edit_link . '" target="_blank">مشاهده و ویرایش</a>';
                smart_admin_log('Success message created with edit link: ' . $edit_link);
            } else {
                $success_message = 'خطا در ذخیره‌سازی محتوا: ' . $post_id->get_error_message();
                smart_admin_log('Error in saving: ' . $post_id->get_error_message());
            }
        } else if (isset($response['content']) && !empty($response['content'])) {
            // اگر محتوا تولید شده ولی قالب نباشد، باز هم ذخیره کن
            smart_admin_log('Non-template content received, starting save process');
            
            // استخراج عنوان از فیلدهای فرم یا استفاده از عنوان پیش‌فرض
            $title = '';
            if (!empty($_POST['focus_keyword'])) {
                $title = sanitize_text_field($_POST['focus_keyword']);
            } elseif (!empty($_POST['main_topic'])) {
                $title = sanitize_text_field($_POST['main_topic']);
            } else {
                $title = 'محتوا تولید شده توسط دستیار هوشمند';
            }
            
            smart_admin_log('Generated title: ' . $title);
            
            smart_admin_log('Original content length: ' . strlen($response['content']));
            smart_admin_log('Original content preview: ' . substr($response['content'], 0, 200) . '...');
            
            // اعمال قالب‌بندی HTML اگر تنظیمات فعال باشد
            if (get_option('smart_admin_enforce_formatting', 1)) {
                smart_admin_log('Processing content with HTML formatting enforcement');
                $content = smart_admin_cleanup_generated_content($response['content']);
                smart_admin_log('Content processed with HTML formatting');
                smart_admin_log('Final content preview: ' . substr($content, 0, 200) . '...');
            } else {
                smart_admin_log('Processing content without HTML formatting enforcement');
                $content = wp_kses_post($response['content']);
            }
            
            smart_admin_log('Content length after processing: ' . strlen($content));
            smart_admin_log('Content preview: ' . substr($content, 0, 200) . '...');
            
            // استخراج کلمات کلیدی از فیلدهای فرم یا پاسخ API
            $keywords = array();
            
            // اولویت اول: کلمات کلیدی از پاسخ API
            if (!empty($response['keywords']) && is_array($response['keywords'])) {
                $keywords = array_merge($keywords, $response['keywords']);
                smart_admin_log('Using keywords from API response: ' . implode(', ', $response['keywords']));
            }
            
            // اولویت دوم: فیلدهای فرم
            if (!empty($_POST['focus_keyword'])) {
                $keywords[] = sanitize_text_field($_POST['focus_keyword']);
            }
            if (!empty($_POST['main_topic'])) {
                $keywords[] = sanitize_text_field($_POST['main_topic']);
            }
            
            // حذف تکرارها
            $keywords = array_unique($keywords);
            
            smart_admin_log('Final extracted keywords: ' . implode(', ', $keywords));
            smart_admin_log('About to call smart_admin_save_ai_content_as_draft with title: ' . $title);
            
            // ذخیره محتوا به عنوان پیش‌نویس
            $post_id = smart_admin_save_ai_content_as_draft($title, $content, $keywords);
            
            smart_admin_log('Save result: ' . (is_wp_error($post_id) ? 'Error: ' . $post_id->get_error_message() : 'Success, Post ID: ' . $post_id));
            
            if (!is_wp_error($post_id)) {
                $edit_link = admin_url('post.php?post=' . $post_id . '&action=edit');
                $success_message = 'محتوا با موفقیت تولید و به عنوان پیش‌نویس ذخیره شد. <a href="' . $edit_link . '" target="_blank">مشاهده و ویرایش</a>';
                smart_admin_log('Success message created with edit link: ' . $edit_link);
            } else {
                $success_message = 'خطا در ذخیره‌سازی محتوا: ' . $post_id->get_error_message();
                smart_admin_log('Error in saving: ' . $post_id->get_error_message());
            }
        }
        }
    }
    // ذخیره پاسخ هوش مصنوعی به عنوان پیش‌نویس در وردپرس
    if (isset($_POST['save_as_draft']) && isset($_POST['ai_response']) && !empty($_POST['ai_response'])) {
        // افزودن نانس برای امنیت
        check_admin_referer('smart_admin_save_draft_action', 'smart_admin_save_draft_nonce');
        
        $title = sanitize_text_field($_POST['post_title']);
        
        // اعمال قالب‌بندی HTML اگر تنظیمات فعال باشد
        if (get_option('smart_admin_enforce_formatting', 1)) {
            $content = smart_admin_cleanup_generated_content($_POST['ai_response']);
        } else {
            $content = wp_kses_post($_POST['ai_response']);
        }
        
        // استخراج کلمات کلیدی از فرم یا استخراج خودکار
        $keywords = array();
        if (!empty($_POST['post_keywords'])) {
            $keywords = explode(',', sanitize_text_field($_POST['post_keywords']));
            $keywords = array_map('trim', $keywords);
        } elseif (function_exists('smart_admin_extract_keywords_from_ai_response')) {
            $keywords = smart_admin_extract_keywords_from_ai_response($content);
        }
        
        // ذخیره محتوا به عنوان پیش‌نویس
        $post_id = smart_admin_save_ai_content_as_draft($title, $content, $keywords);
        
        if (!is_wp_error($post_id)) {
            $edit_link = admin_url('post.php?post=' . $post_id . '&action=edit');
            // فقط اگر پیام موفقیت قبلی وجود ندارد، پیام جدید تنظیم کن
            if (empty($success_message)) {
                $success_message = 'محتوا با موفقیت به عنوان پیش‌نویس ذخیره شد. <a href="' . $edit_link . '" target="_blank">مشاهده و ویرایش</a>';
            }
        } else {
            $success_message = 'خطا در ذخیره‌سازی محتوا: ' . $post_id->get_error_message();
        }
    }
    
    // حذف پرامپت ذخیره شده
    if (isset($_GET['delete_prompt']) && is_numeric($_GET['delete_prompt'])) {
        // افزودن نانس برای امنیت
        check_admin_referer('smart_admin_delete_prompt_action', 'smart_admin_delete_nonce');
        
        $prompt_index = intval($_GET['delete_prompt']);
        $saved_prompts = get_option('smart_admin_saved_prompts', array());
        
        if (isset($saved_prompts[$prompt_index])) {
            unset($saved_prompts[$prompt_index]);
            $saved_prompts = array_values($saved_prompts); // بازسازی شاخص‌ها
            update_option('smart_admin_saved_prompts', $saved_prompts);
            
            // ریدایرکت برای جلوگیری از ارسال مجدد فرم
            wp_redirect(add_query_arg(array('page' => 'smart-admin'), admin_url('admin.php')));
            exit;
        }
    }
    
    // نمایش صفحه
    ?>
    <style>
        .smart-admin-wrap {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
            max-width: 1200px;
            margin: 40px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            direction: rtl;
        }
        
        .smart-admin-wrap h2 {
            color: #2c3e50;
            font-size: 2em;
            margin-bottom: 30px;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
            display: inline-block;
        }
        
        .smart-admin-tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        
        .smart-admin-tabs a {
            padding: 10px 20px;
            text-decoration: none;
            color: #666;
            font-weight: 500;
        }
        
        .smart-admin-tabs a.active {
            border-bottom: 3px solid #3498db;
            color: #3498db;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .settings-form, .prompt-form {
            display: grid;
            gap: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .form-group label {
            color: #2c3e50;
            font-weight: 500;
        }
        
        .form-group input[type="text"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s ease;
            background: #f8f9fa;
        }
        
        .form-group input[type="text"]:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        .form-group textarea {
            min-height: 200px;
            resize: vertical;
        }
        
        .submit-button {
            background: #3498db;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s ease;
            margin-top: 20px;
            width: fit-content;
        }
        
        .submit-button:hover {
            background: #2980b9;
        }
        
        .response-container {
            margin-top: 30px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 6px;
            border-right: 4px solid #3498db;
        }
        
        .saved-prompts {
            margin-top: 30px;
        }
        
        .saved-prompts h3 {
            margin-bottom: 15px;
            color: #2c3e50;
        }
        
        .prompt-card {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 15px;
            border-right: 3px solid #3498db;
        }
        
        .prompt-card-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .prompt-card-model {
            font-size: 12px;
            background: #e0e0e0;
            padding: 3px 8px;
            border-radius: 4px;
        }
        
        .prompt-card-date {
            font-size: 12px;
            color: #777;
        }
        
        .prompt-card-content {
            margin-bottom: 10px;
        }
        .prompt-card-actions a {
            text-decoration: none;
            color: #3498db;
            margin-left: 15px;
            font-size: 13px;
        }
        
        .prompt-card-actions a:hover {
            text-decoration: underline;
        }
        
        .loading-spinner {
            display: none;
            margin-right: 10px;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(0, 0, 0, 0.1);
            border-radius: 50%;
            border-top-color: #3498db;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* بهبود استایل لودینگ */
        .loading-message {
            text-align: center;
            padding: 30px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 2px solid #dee2e6;
            border-radius: 12px;
            margin: 20px 0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .loading-message .loading-icon {
            font-size: 48px;
            margin-bottom: 15px;
            animation: pulse 2s ease-in-out infinite;
        }
        
        .loading-message .loading-text {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .loading-message .loading-subtext {
            font-size: 14px;
            color: #6c757d;
            line-height: 1.5;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(1.1); }
        }
        
        /* استایل پیام‌های خطا */
        .response-container.error {
            border-right-color: #e74c3c;
            background-color: #fdf2f2;
        }
        
        .response-container.error p {
            color: #c53030;
            font-weight: 600;
        }
        
        .error-icon {
            font-size: 24px;
            margin-right: 10px;
            color: #e74c3c;
        }
        
        .response-container.error {
            border-right-color: #e74c3c;
        }
        
        .templates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .template-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            background: #f9f9f9;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .template-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .template-card h3 {
            margin-top: 0;
            color: #2c3e50;
            font-size: 18px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 8px;
        }
        
        .template-card-model {
            display: inline-block;
            font-size: 12px;
            background: #e0e0e0;
            padding: 3px 8px;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        
        .template-card-preview {
            max-height: 100px;
            overflow: hidden;
            position: relative;
            margin-bottom: 15px;
            font-size: 13px;
            color: #666;
        }
        
        .template-card-preview::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 50px;
            background: linear-gradient(transparent, #f9f9f9);
        }
        
        .template-card-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
        }
        
        .template-card-actions button {
            background: #3498db;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            transition: background 0.2s;
        }
        
        .template-card-actions button:hover {
            background: #2980b9;
        }
        
        .tab-selector {
            display: flex;
            margin-bottom: 20px;
        }
        
        .tab-selector a {
            padding: 8px 15px;
            background: #f0f0f0;
            color: #666;
            text-decoration: none;
            margin-left: 10px;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .tab-selector a.active {
            background: #3498db;
            color: white;
        }
        
        .prompt-card.template {
            border-right-color: #27ae60;
        }
        
        .prompt-card-title {
            font-weight: bold;
            margin-bottom: 5px;
            color: #2c3e50;
        }
        
        .prompt-template-tag {
            display: inline-block;
            background: #27ae60;
            color: white;
            font-size: 11px;
            padding: 2px 6px;
            border-radius: 3px;
            margin-right: 5px;
        }
        .save-draft-form {
            margin-top: 20px;
            background: #f0f8ff;
            padding: 20px;
            border-radius: 6px;
            border-right: 4px solid #27ae60;
        }
        
        .save-draft-form h3 {
            margin-top: 0;
            color: #2c3e50;
        }
        
        .save-draft-form .form-row {
            margin-bottom: 15px;
        }
        
        .save-draft-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .save-draft-form input[type="text"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .ai-drafts-wrap {
            margin-top: 30px;
        }
        
        .ai-drafts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .ai-draft-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            background: #f9f9f9;
            position: relative;
        }
        
        .ai-draft-card h4 {
            margin-top: 0;
            margin-bottom: 10px;
            color: #2c3e50;
            font-size: 16px;
        }
        
        .ai-draft-card-date {
            color: #777;
            font-size: 12px;
            margin-bottom: 10px;
        }
        
        .ai-draft-card-actions {
            margin-top: 15px;
            display: flex;
        }
        
        .ai-draft-card-actions a {
            background: #3498db;
            color: white;
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 13px;
            margin-left: 10px;
        }
        
        .ai-draft-card-actions a.view {
            background: #3498db;
        }
        
        .ai-draft-card-actions a.edit {
            background: #f39c12;
        }
        
        .ai-draft-card-actions a.publish {
            background: #27ae60;
        }
        
        .ai-draft-card-actions a:hover {
            opacity: 0.9;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .drafts-tab-selector {
            display: flex;
            margin-bottom: 20px;
        }
        
        .drafts-tab-selector a {
            padding: 8px 15px;
            background: #f0f0f0;
            color: #666;
            text-decoration: none;
            margin-left: 10px;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .drafts-tab-selector a.active {
            background: #3498db;
            color: white;
        }
        
        .human-tone-option {
            margin-bottom: 20px;
            background: #f0f9ff;
            padding: 12px 15px;
            border-radius: 6px;
            border-right: 3px solid #3498db;
        }
        
        .checkbox-container {
            display: flex;
            align-items: center;
            position: relative;
            padding-right: 35px;
            cursor: pointer;
            font-size: 14px;
            user-select: none;
        }
        
        .checkbox-container input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
            height: 0;
            width: 0;
        }
        
        .checkmark {
            position: absolute;
            top: 0;
            right: 0;
            height: 20px;
            width: 20px;
            background-color: #eee;
            border-radius: 4px;
        }
        
        .checkbox-container:hover input ~ .checkmark {
            background-color: #ccc;
        }
        
        .checkbox-container input:checked ~ .checkmark {
            background-color: #3498db;
        }
        
        .checkmark:after {
            content: "";
            position: absolute;
            display: none;
        }
        
        .checkbox-container input:checked ~ .checkmark:after {
            display: block;
        }
        
        .checkbox-container .checkmark:after {
            right: 7px;
            top: 3px;
            width: 5px;
            height: 10px;
            border: solid white;
            border-width: 0 3px 3px 0;
            transform: rotate(45deg);
        }
        
        /* استایل‌های مودال فرم قالب‌ها */
        .template-form-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .template-form-content {
            background: white;
            border-radius: 10px;
            padding: 30px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
        }
        
        .template-form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        
        .template-form-header h3 {
            margin: 0;
            color: #2c3e50;
        }
        
        .close-template-form {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .close-template-form:hover {
            color: #e74c3c;
        }
        
        .template-card-description {
            color: #666;
            font-size: 14px;
            line-height: 1.4;
            margin-bottom: 15px;
        }
        
        /* استایل‌های تب لاگ‌ها */
        .smart-admin-wrap .log-section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        
        .smart-admin-wrap .log-section h4 {
            margin-top: 0;
            color: #333;
            border-bottom: 2px solid #0073aa;
            padding-bottom: 10px;
        }
        
        .smart-admin-wrap .log-section h5 {
            color: #555;
            margin-bottom: 5px;
        }
        
        .smart-admin-wrap .log-display {
            background: #fff;
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 15px;
            margin: 10px 0;
            max-height: 400px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.4;
            direction: ltr;
            text-align: left;
        }
        
        .smart-admin-wrap .log-display pre {
            margin: 0;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        
        .smart-admin-wrap .log-file-info {
            background: #fff;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .smart-admin-wrap .system-info {
            background: #fff;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .smart-admin-wrap .system-info p {
            margin: 5px 0;
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }
        
        .smart-admin-wrap .system-info p:last-child {
            border-bottom: none;
        }
    </style>
    
    <div class="smart-admin-wrap">
        <h2>ادمین هوشمند</h2>
        
        
        <div class="smart-admin-tabs">
            <a href="#" class="tab-link active" data-tab="prompt">ایجاد محتوا با هوش مصنوعی</a>
            <a href="#" class="tab-link" data-tab="templates">قالب‌های آماده</a>
            <a href="#" class="tab-link" data-tab="images">ساخت تصویر</a>
            <a href="#" class="tab-link" data-tab="saved">پرامپت‌های ذخیره شده</a>
            <a href="#" class="tab-link" data-tab="drafts">پیش‌نویس‌ها</a>
            <a href="#" class="tab-link" data-tab="scheduler">زمان‌بندی محتوا</a>
            <a href="#" class="tab-link" data-tab="settings">تنظیمات</a>
            <a href="#" class="tab-link" data-tab="logs">لاگ‌ها</a>
        </div>
        
        <div id="images" class="tab-content">
            <?php if (function_exists('wp_enqueue_media')) { wp_enqueue_media(); } ?>
            <h3>ساخت تصویر با مدل‌های GapGPT و دیگر ارائه‌دهندگان</h3>
            <form method="post" class="prompt-form">
                <?php wp_nonce_field('smart_admin_image_action', 'smart_admin_image_nonce'); ?>
                <div class="form-group">
                    <label for="smart_admin_image_model">مدل ساخت تصویر:</label>
                    <select id="smart_admin_image_model" name="smart_admin_image_model">
                        <optgroup label="GapGPT">
                            <option value="gapgpt/flux.1-schnell" <?php selected(get_option('smart_admin_image_model', 'gapgpt/flux.1-schnell'), 'gapgpt/flux.1-schnell'); ?>>Flux 1 Schnell (GapGPT)</option>
                            <option value="gapgpt/flux.1-dev" <?php selected(get_option('smart_admin_image_model'), 'gapgpt/flux.1-dev'); ?>>Flux 1 Dev (GapGPT)</option>
                        </optgroup>
                        <optgroup label="OpenAI">
                            <option value="dall-e-3" <?php selected(get_option('smart_admin_image_model'), 'dall-e-3'); ?>>DALL·E 3</option>
                            <option value="dall-e-2" <?php selected(get_option('smart_admin_image_model'), 'dall-e-2'); ?>>DALL·E 2</option>
                        </optgroup>
                        <optgroup label="BFL - FLUX">
                            <option value="flux-1-schnell" <?php selected(get_option('smart_admin_image_model'), 'flux-1-schnell'); ?>>FLUX 1 Schnell</option>
                            <option value="flux/dev" <?php selected(get_option('smart_admin_image_model'), 'flux/dev'); ?>>FLUX Dev</option>
                            <option value="flux-pro/kontext/text-to-image" <?php selected(get_option('smart_admin_image_model'), 'flux-pro/kontext/text-to-image'); ?>>FLUX Pro Kontext</option>
                            <option value="flux-pro/kontext/max/text-to-image" <?php selected(get_option('smart_admin_image_model'), 'flux-pro/kontext/max/text-to-image'); ?>>FLUX Pro Kontext Max</option>
                        </optgroup>
                        <optgroup label="Google">
                            <option value="imagen-3.0-generate-002" <?php selected(get_option('smart_admin_image_model'), 'imagen-3.0-generate-002'); ?>>Imagen 3 Generate 002</option>
                        </optgroup>
                    </select>
                </div>

                <div class="form-group">
                    <label for="smart_admin_image_prompt">شرح تصویر (پرامپت):</label>
                    <textarea id="smart_admin_image_prompt" name="smart_admin_image_prompt" placeholder="توضیح دقیق تصویر مورد نظر را وارد کنید..."></textarea>
                </div>

                <div class="form-group">
                    <label for="smart_admin_image_size">اندازه خروجی:</label>
                    <select id="smart_admin_image_size" name="smart_admin_image_size">
                        <option value="512x512">512x512</option>
                        <option value="768x768">768x768</option>
                        <option value="1024x1024" selected>1024x1024</option>
                        <option value="2048x2048">2048x2048</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="smart_admin_image_quality">کیفیت:</label>
                    <select id="smart_admin_image_quality" name="smart_admin_image_quality">
                        <option value="standard" selected>Standard</option>
                        <option value="high">High</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="smart_admin_image_n">تعداد تصاویر (1 تا 4):</label>
                    <input type="number" id="smart_admin_image_n" name="smart_admin_image_n" min="1" max="4" value="1">
                </div>

                <!-- ارجاع تصویر مرجع (برای image-to-image) -->
                <input type="hidden" id="smart_admin_reference_image_id" name="smart_admin_reference_image_id" value="">
                <input type="hidden" id="smart_admin_reference_image_url" name="smart_admin_reference_image_url" value="">
			<input type="hidden" id="smart_admin_reference_image_urls" name="smart_admin_reference_image_urls" value="">
                <div id="reference-image-preview" style="display:none;margin:12px 0;">
                    <div style="font-size:12px;color:#555;margin-bottom:6px;">تصویر مرجع انتخاب‌شده:</div>
                    <img id="reference-image-thumb" src="" alt="reference" style="max-width:220px;height:auto;border:1px solid #e5e5e5;border-radius:6px;">
                </div>

                <button type="submit" class="submit-button">تولید تصویر</button>
            </form>

            <!-- قالب‌های آماده ساخت تصویر (عمومی) -->
            <div class="image-templates-wrap" style="margin-top: 28px;">
                <h3>قالب‌های آماده ساخت تصویر — دسته‌بندی: عمومی</h3>
                <p>با استفاده از این قالب‌ها می‌توانید پرامپت‌های دقیق برای ساخت تصویر ایجاد کنید. پس از پر کردن فرم مودال، پرامپت به صورت خودکار در فرم بالا قرار گرفته و ارسال می‌شود.</p>

                <div class="templates-grid">
                    <?php 
                    $default_image_templates = array(
                        array(
                            'title' => 'پرتره استودیویی حرفه‌ای',
                            'model' => get_option('smart_admin_image_model', 'gapgpt/flux.1-schnell'),
                            'description' => 'چهره انسان با نورپردازی استودیویی، پس‌زمینه ساده، جزئیات بالا، تمرکز روی چشم‌ها، پوست طبیعی'
                        ),
                        array(
                            'title' => 'عکس محصول ای‌کامرس (پس‌زمینه سفید)',
                            'model' => get_option('smart_admin_image_model', 'gapgpt/flux.1-schnell'),
                            'description' => 'عکاسی محصول با نور نرم، سایه طبیعی، پس‌زمینه سفید خالص، مناسب فروشگاه اینترنتی'
                        ),
                        array(
                            'title' => 'پوستر تبلیغاتی مینیمال',
                            'model' => get_option('smart_admin_image_model', 'dall-e-3'),
                            'description' => 'طراحی پوستر مینیمال با پیام اصلی، رنگ‌های برند و تایپوگرافی برجسته'
                        ),
                        array(
                            'title' => 'منظرۀ سینمایی حماسی',
                            'model' => get_option('smart_admin_image_model', 'gapgpt/flux.1-dev'),
                            'description' => 'منظره طبیعی با عمق میدان سینمایی، نور طلوع/غروب، حال‌وهوای دراماتیک'
                        ),
                        array(
                            'title' => 'آیکون فلت مدرن',
                            'model' => get_option('smart_admin_image_model', 'dall-e-3'),
                            'description' => 'آیکون ساده و فلت با رنگ‌های محدود، کنتراست خوب و خوانایی در اندازه کوچک'
                        ),
                        array(
                            'title' => 'تصویر شاخص خبر/مقاله (حرفه‌ای)',
                            'model' => get_option('smart_admin_image_model', 'gapgpt/flux.1-schnell'),
                            'description' => 'کاور حرفه‌ای بدون متن برای خبر یا مقاله؛ با سبک بصری منسجم و موضوع‌بندی'
                        ),
                        array(
                            'title' => 'اسکرین‌شات حرفه‌ای (فریم دستگاه/مرورگر)',
                            'model' => get_option('smart_admin_image_model', 'gapgpt/flux.1-schnell'),
                            'description' => 'تصویر سبک اسکرین‌شات با قاب دسکتاپ/موبایل، آدرس‌بار و UI شبیه‌سازی‌شده'
                        ),
                        array(
                            'title' => 'تصویر محصول حرفه‌ای (ویرایش با مرجع کتابخانه)',
                            'model' => get_option('smart_admin_image_model', 'gapgpt/flux.1-dev'),
                            'description' => 'بهبود/تمیزکاری تصویر محصول با انتخاب تصویر مرجع از کتابخانه وردپرس (image-to-image)'
                        )
                    );
                    foreach ($default_image_templates as $index => $tpl):
                    ?>
                        <div class="template-card">
                            <h3><?php echo esc_html($tpl['title']); ?></h3>
                            <span class="template-card-model">مدل پیش‌فرض: <?php echo esc_html($tpl['model']); ?></span>
                            <div class="template-card-description"><?php echo esc_html($tpl['description']); ?></div>
                            <div class="template-card-actions">
                                <button type="button" class="use-image-template-btn"
                                    data-template-title="<?php echo esc_attr($tpl['title']); ?>"
                                    data-model="<?php echo esc_attr($tpl['model']); ?>">
                                    استفاده از این قالب
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- مودال فرم داینامیک قالب‌های تصویر -->
            <div id="image-template-form-modal" style="display: none;" class="template-form-modal">
                <div class="template-form-content">
                    <div class="template-form-header">
                        <h3 id="image-template-form-title">قالب ساخت تصویر</h3>
                        <button type="button" class="close-image-template-form">&times;</button>
                    </div>
                    <form id="image-template-form" class="prompt-form">
                        <div class="form-group">
                            <label for="image-template-model-select">مدل ساخت تصویر:</label>
                            <select id="image-template-model-select" name="image_model">
                                <optgroup label="GapGPT">
                                    <option value="gapgpt/flux.1-schnell" <?php selected(get_option('smart_admin_image_model', 'gapgpt/flux.1-schnell'), 'gapgpt/flux.1-schnell'); ?>>Flux 1 Schnell (GapGPT)</option>
                                    <option value="gapgpt/flux.1-dev" <?php selected(get_option('smart_admin_image_model'), 'gapgpt/flux.1-dev'); ?>>Flux 1 Dev (GapGPT)</option>
                                </optgroup>
                                <optgroup label="OpenAI">
                                    <option value="dall-e-3" <?php selected(get_option('smart_admin_image_model'), 'dall-e-3'); ?>>DALL·E 3</option>
                                    <option value="dall-e-2" <?php selected(get_option('smart_admin_image_model'), 'dall-e-2'); ?>>DALL·E 2</option>
                                </optgroup>
                                <optgroup label="BFL - FLUX">
                                    <option value="flux-1-schnell" <?php selected(get_option('smart_admin_image_model'), 'flux-1-schnell'); ?>>FLUX 1 Schnell</option>
                                    <option value="flux/dev" <?php selected(get_option('smart_admin_image_model'), 'flux/dev'); ?>>FLUX Dev</option>
                                    <option value="flux-pro/kontext/text-to-image" <?php selected(get_option('smart_admin_image_model'), 'flux-pro/kontext/text-to-image'); ?>>FLUX Pro Kontext</option>
                                    <option value="flux-pro/kontext/max/text-to-image" <?php selected(get_option('smart_admin_image_model'), 'flux-pro/kontext/max/text-to-image'); ?>>FLUX Pro Kontext Max</option>
                                </optgroup>
                                <optgroup label="Google">
                                    <option value="imagen-3.0-generate-002" <?php selected(get_option('smart_admin_image_model'), 'imagen-3.0-generate-002'); ?>>Imagen 3 Generate 002</option>
                                </optgroup>
                            </select>
                        </div>

                        <div class="form-row" style="display:flex; gap:12px; flex-wrap:wrap;">
                            <div class="form-group" style="flex:1 1 180px; min-width:180px;">
                                <label for="image-template-size">اندازه خروجی:</label>
                                <select id="image-template-size" name="image_size">
                                    <option value="512x512">512x512</option>
                                    <option value="768x768">768x768</option>
                                    <option value="1024x1024" selected>1024x1024</option>
                                    <option value="2048x2048">2048x2048</option>
                                </select>
                            </div>
                            <div class="form-group" style="flex:1 1 180px; min-width:180px;">
                                <label for="image-template-quality">کیفیت:</label>
                                <select id="image-template-quality" name="image_quality">
                                    <option value="standard" selected>Standard</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                            <div class="form-group" style="flex:1 1 180px; min-width:180px;">
                                <label for="image-template-n">تعداد تصاویر:</label>
                                <input type="number" id="image-template-n" name="image_n" min="1" max="4" value="1">
                            </div>
                        </div>

                        <!-- انتخاب حوزه محتوا (پروفایل) -->
                        <div class="form-group">
                            <label for="global-domain-select">حوزه محتوا (برای پیشنهاد گزینه‌ها):</label>
                            <select id="global-domain-select" name="global_domain_select" data-allow-custom="1">
                                <option value="عمومی" selected>عمومی</option>
                                <option value="فناوری">فناوری</option>
                                <option value="اقتصادی/کسب‌وکار">اقتصادی/کسب‌وکار</option>
                                <option value="گردشگری">گردشگری</option>
                                <option value="خوراکی و آشپزی">خوراکی و آشپزی</option>
                                <option value="آب و انرژی">آب و انرژی</option>
                                <option value="نفت و گاز">نفت و گاز</option>
                                <option value="ایمنی و آتشنشانی">ایمنی و آتشنشانی</option>
                                <option value="custom">سفارشی...</option>
                            </select>
                            <div class="custom-input-wrap" style="display:none;margin-top:6px;">
                                <input type="text" id="global-domain-custom" name="global_domain_custom" placeholder="حوزه سفارشی را وارد کنید">
                            </div>
                        </div>

                        <div id="image-template-fields"></div>

                        <button type="submit" class="submit-button" id="generate-image-template-btn">
                            <span class="loading-spinner" id="loading-spinner-image-template" style="display:none"></span>
                            <span>تولید تصویر با این قالب</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- قالب‌های ویژه محصول ووکامرس -->
            <div class="image-templates-wrap" style="margin-top: 28px;">
                <h3>قالب‌های ویژه محصول ووکامرس</h3>
                <p>از این قالب برای ساخت تصاویر محصول حرفه‌ای استفاده کنید. می‌توانید یک یا چند تصویر مرجع از کتابخانه انتخاب کنید.</p>
                <div class="templates-grid">
                    <div class="template-card">
                        <h3>تصویر استودیویی محصول (E-commerce)</h3>
                        <span class="template-card-model">مدل پیشنهادی: <?php echo esc_html(get_option('smart_admin_image_model', 'gapgpt/flux.1-dev')); ?></span>
                        <div class="template-card-description">پس‌زمینه استاندارد، نور یکنواخت، مناسب صفحه محصول، با سایه طبیعی</div>
                        <div class="template-card-actions">
                            <button type="button" class="use-product-template-btn" data-template="studio" data-model="<?php echo esc_attr(get_option('smart_admin_image_model', 'gapgpt/flux.1-dev')); ?>">استفاده از این قالب</button>
                        </div>
                    </div>
                    <div class="template-card">
                        <h3>تصویر لایف‌استایل مینیمال</h3>
                        <span class="template-card-model">مدل پیشنهادی: <?php echo esc_html(get_option('smart_admin_image_model', 'gapgpt/flux.1-dev')); ?></span>
                        <div class="template-card-description">چیدمان ساده و زیبای محصول در صحنه واقعی مینیمال</div>
                        <div class="template-card-actions">
                            <button type="button" class="use-product-template-btn" data-template="lifestyle" data-model="<?php echo esc_attr(get_option('smart_admin_image_model', 'gapgpt/flux.1-dev')); ?>">استفاده از این قالب</button>
                        </div>
                    </div>
                    <div class="template-card">
                        <h3>تصویر با انعکاس شیشه‌ای</h3>
                        <span class="template-card-model">مدل پیشنهادی: <?php echo esc_html(get_option('smart_admin_image_model', 'gapgpt/flux.1-dev')); ?></span>
                        <div class="template-card-description">پس‌زمینه روشن با انعکاس ظریف زیر محصول</div>
                        <div class="template-card-actions">
                            <button type="button" class="use-product-template-btn" data-template="reflect" data-model="<?php echo esc_attr(get_option('smart_admin_image_model', 'gapgpt/flux.1-dev')); ?>">استفاده از این قالب</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- مودال قالب محصول ووکامرس -->
            <div id="product-image-template-modal" style="display:none;" class="template-form-modal">
                <div class="template-form-content">
                    <div class="template-form-header">
                        <h3 id="product-template-form-title">قالب محصول ووکامرس</h3>
                        <button type="button" class="close-product-template-form">&times;</button>
                    </div>
                    <form id="product-image-template-form" class="prompt-form">
                        <div class="form-group">
                            <label for="product-template-model-select">مدل ساخت تصویر:</label>
                            <select id="product-template-model-select" name="image_model">
                                <option value="gapgpt/flux.1-dev" selected>Flux 1 Dev (GapGPT) — پیشنهاد</option>
                                <option value="gapgpt/flux.1-schnell">Flux 1 Schnell (GapGPT)</option>
                                <option value="dall-e-3">DALL·E 3</option>
                                <option value="flux-1-schnell">FLUX 1 Schnell</option>
                            </select>
                        </div>

                        <div class="form-row" style="display:flex; gap:12px; flex-wrap:wrap;">
                            <div class="form-group" style="flex:1 1 260px;">
                                <label for="product_title">نام/نوع محصول:</label>
                                <input type="text" id="product_title" name="product_title" placeholder="مثال: کفش رانینگ سفید مردانه">
                            </div>
                            <div class="form-group" style="flex:1 1 200px;">
                                <label for="product_aspect">نسبت تصویر:</label>
                                <select id="product_aspect" name="product_aspect">
                                    <option value="1:1" selected>1:1</option>
                                    <option value="4:5">4:5</option>
                                    <option value="16:9">16:9</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row" style="display:flex; gap:12px; flex-wrap:wrap;">
                            <div class="form-group" style="flex:1 1 200px;">
                                <label for="product_angle">زاویه دید:</label>
                                <select id="product_angle" name="product_angle">
                                    <option value="Front">نمای روبه‌رو</option>
                                    <option value="3/4 45-deg" selected>سه‌چهارم 45 درجه</option>
                                    <option value="Top-down">نمای بالا</option>
                                    <option value="Side">نمای جانبی</option>
                                </select>
                            </div>
                            <div class="form-group" style="flex:1 1 200px;">
                                <label for="product_bg">پس‌زمینه:</label>
                                <select id="product_bg" name="product_bg" data-allow-custom="1">
                                    <option value="سفید خالص" selected>سفید خالص</option>
                                    <option value="گرادیان خیلی روشن">گرادیان خیلی روشن</option>
                                    <option value="شفاف">شفاف</option>
                                    <option value="سطح چوب روشن مینیمال">سطح چوب روشن مینیمال</option>
                                    <option value="بتن روشن مینیمال">بتن روشن مینیمال</option>
                                    <option value="custom">سفارشی...</option>
                                </select>
                                <div class="custom-input-wrap" style="display:none;margin-top:6px;">
                                    <input type="text" id="product_bg_custom" name="product_bg_custom" placeholder="پس‌زمینه سفارشی">
                                </div>
                            </div>
                            <div class="form-group" style="flex:1 1 200px;">
                                <label for="product_shadow">سایه/انعکاس:</label>
                                <select id="product_shadow" name="product_shadow">
                                    <option value="بدون انعکاس، سایه نرم طبیعی" selected>سایه نرم طبیعی</option>
                                    <option value="سایه Drop Shadow طبیعی">Drop Shadow</option>
                                    <option value="انعکاس شیشه‌ای ظریف">انعکاس شیشه‌ای ظریف</option>
                                    <option value="بدون سایه/انعکاس">بدون سایه/انعکاس</option>
                                </select>
                            </div>
                            <div class="form-group" style="flex:1 1 220px;">
                                <label for="product_style">سبک خروجی:</label>
                                <select id="product_style" name="product_style">
                                    <option value="E-commerce studio" selected>استودیویی E-commerce</option>
                                    <option value="Minimal lifestyle scene">لایف‌استایل مینیمال</option>
                                    <option value="Floating with soft shadow">شناور با سایه نرم</option>
                                    <option value="Glossy reflection">انعکاس براق</option>
                                </select>
                            </div>
                        </div>

                        <!-- انتخاب تصاویر مرجع -->
                        <div class="form-group">
                            <label>انتخاب تصاویر مرجع:</label>
                            <div class="button-group" style="display:flex; gap:8px; flex-wrap:wrap;">
                                <button type="button" class="button" id="select-product-ref-single">انتخاب ۱ تصویر</button>
                                <button type="button" class="button" id="select-product-ref-multi">انتخاب چند تصویر</button>
                            </div>
                            <input type="hidden" id="product-reference-images-json" value="[]">
                            <div id="product-reference-gallery" style="display:none;margin-top:10px;display:flex;gap:8px;flex-wrap:wrap;"></div>
                        </div>

                        ${getCommonProcessingFields()}

                        <button type="submit" class="submit-button" id="generate-product-image-template-btn">
                            <span class="loading-spinner" id="loading-spinner-product-image" style="display:none"></span>
                            <span>تولید تصویر محصول</span>
                        </button>
                    </form>
                </div>
            </div>

            <script>
            (function(){
                // پروفایل‌های حوزه برای پیشنهاد گزینه‌های پیش‌فرض
                const DOMAIN_PROFILES = {
                    'عمومی': {
                        mood: ['حرفه‌ای','دوستانه','مینیمال'],
                        colorPalettes: ['Neutral','Vibrant','Pastel','Monochrome'],
                        processing: ['Photorealistic','Cinematic','Illustration']
                    },
                    'فناوری': {
                        mood: ['حرفه‌ای','خنثی','های-تک'],
                        colorPalettes: ['آبی سازمانی + فیروزه‌ای','Monochrome','Duotone'],
                        processing: ['Photorealistic','Flat Vector','Isometric']
                    },
                    'اقتصادی/کسب‌وکار': {
                        mood: ['حرفه‌ای','رسمی','اعتمادساز'],
                        colorPalettes: ['Monochrome','آبی سازمانی + فیروزه‌ای','Neutral'],
                        processing: ['Photorealistic','Cinematic','Flat Vector']
                    },
                    'گردشگری': {
                        mood: ['شاد','الهام‌بخش','ماجراجویانه'],
                        colorPalettes: ['Vibrant','Warm','Pastel'],
                        processing: ['Photorealistic','Cinematic','Illustration']
                    },
                    'خوراکی و آشپزی': {
                        mood: ['گرم و صمیمی','خانوادگی','هیجان‌انگیز'],
                        colorPalettes: ['Warm','Vibrant','Pastel'],
                        processing: ['Photorealistic','Cinematic']
                    },
                    'آب و انرژی': {
                        mood: ['پایدار','حرفه‌ای','امیدبخش'],
                        colorPalettes: ['سبز + آبی خنثی','Neutral','Monochrome'],
                        processing: ['Photorealistic','Illustration']
                    },
                    'نفت و گاز': {
                        mood: ['صنعتی','جدی','حرفه‌ای'],
                        colorPalettes: ['Monochrome','Warm','Neutral'],
                        processing: ['Photorealistic','Cinematic']
                    },
                    'ایمنی و آتشنشانی': {
                        mood: ['هشداردهنده','حرفه‌ای','آموزشی'],
                        colorPalettes: ['قرمز/نارنجی + خاکستری','High-contrast','Monochrome'],
                        processing: ['Photorealistic','Illustration']
                    }
                };

                const imageTemplateButtons = document.querySelectorAll('.use-image-template-btn');
                imageTemplateButtons.forEach(btn => {
                    btn.addEventListener('click', function(){
                        const title = this.getAttribute('data-template-title');
                        const model = this.getAttribute('data-model');
                        showImageTemplateForm(title, model);
                    });
                });

                const closeImageTemplateBtn = document.querySelector('.close-image-template-form');
                if (closeImageTemplateBtn) {
                    closeImageTemplateBtn.addEventListener('click', function(){
                        document.getElementById('image-template-form-modal').style.display = 'none';
                    });
                }
                const imageTemplateModal = document.getElementById('image-template-form-modal');
                if (imageTemplateModal) {
                    imageTemplateModal.addEventListener('click', function(e){
                        if (e.target === this) {
                            this.style.display = 'none';
                        }
                    });
                }

                const imageTemplateForm = document.getElementById('image-template-form');
                if (imageTemplateForm) {
                    imageTemplateForm.addEventListener('submit', function(e){
                        e.preventDefault();
                        const formData = new FormData(imageTemplateForm);
                        // تزریق حوزه انتخاب شده به فیلدهای خاص قالب‌های مرتبط
                        const globalDomain = document.getElementById('global-domain-select')?.value;
                        if (globalDomain && globalDomain !== 'custom') {
                            const domainSelect = document.getElementById('domain_select');
                            if (domainSelect && !domainSelect.value) domainSelect.value = globalDomain;
                        } else if (globalDomain === 'custom') {
                            const custom = document.getElementById('global-domain-custom')?.value || '';
                            const domainCustom = document.getElementById('domain_custom');
                            const domainSel = document.getElementById('domain_select');
                            if (domainCustom) domainCustom.value = custom;
                            if (domainSel) domainSel.value = 'custom';
                        }
                        const templateTitle = document.getElementById('image-template-form-title').textContent;
                        const prompt = buildImagePromptFromFormData(formData, templateTitle);

                        // اعمال در فرم اصلی ساخت تصویر
                        const imageTab = document.getElementById('images');
                        const mainForm = imageTab ? imageTab.querySelector('form') : null;
                        if (mainForm) {
                            const promptField = mainForm.querySelector('#smart_admin_image_prompt');
                            const modelSelect = mainForm.querySelector('#smart_admin_image_model');
                            const sizeSelect = mainForm.querySelector('#smart_admin_image_size');
                            const qualitySelect = mainForm.querySelector('#smart_admin_image_quality');
                            const nInput = mainForm.querySelector('#smart_admin_image_n');
                            const refIdInput = mainForm.querySelector('#smart_admin_reference_image_id');
                            const refUrlInput = mainForm.querySelector('#smart_admin_reference_image_url');

                            if (promptField) promptField.value = prompt;
                            if (modelSelect) modelSelect.value = document.getElementById('image-template-model-select').value || modelSelect.value;
                            if (sizeSelect) sizeSelect.value = document.getElementById('image-template-size').value || sizeSelect.value;
                            if (qualitySelect) qualitySelect.value = document.getElementById('image-template-quality').value || qualitySelect.value;
                            if (nInput) nInput.value = document.getElementById('image-template-n').value || nInput.value;

                            // انتقال تصویر مرجع از مودال (در صورت وجود)
                            const modalRefId = document.getElementById('image-template-reference-image-id');
                            const modalRefUrl = document.getElementById('image-template-reference-image-url');
                            if (modalRefId && modalRefId.value && refIdInput) refIdInput.value = modalRefId.value;
                            if (modalRefUrl && modalRefUrl.value && refUrlInput) {
                                refUrlInput.value = modalRefUrl.value;
                                const preview = document.getElementById('reference-image-preview');
                                const img = document.getElementById('reference-image-thumb');
                                if (preview && img) { img.src = modalRefUrl.value; preview.style.display = 'block'; }
                            }

                            // ارسال خودکار فرم اصلی
                            document.getElementById('image-template-form-modal').style.display = 'none';
                            mainForm.submit();
                        }
                    });
                }
                function showImageTemplateForm(templateTitle, model){
                    const modal = document.getElementById('image-template-form-modal');
                    const titleEl = document.getElementById('image-template-form-title');
                    const modelSelect = document.getElementById('image-template-model-select');
                    const fieldsContainer = document.getElementById('image-template-fields');
                    titleEl.textContent = templateTitle;
                    if (modelSelect) modelSelect.value = model;
                    fieldsContainer.innerHTML = getImageTemplateFields(templateTitle);
                    initCustomSelectHandlers(fieldsContainer);
                    modal.style.display = 'flex';
                }
                // ========== Product Template Modal ==========
                const productTemplateButtons = document.querySelectorAll('.use-product-template-btn');
                productTemplateButtons.forEach(btn => {
                    btn.addEventListener('click', function(){
                        const model = this.getAttribute('data-model');
                        const type = this.getAttribute('data-template');
                        showProductTemplateForm(type, model);
                    });
                });

                const productModal = document.getElementById('product-image-template-modal');
                const closeProductTemplateBtn = document.querySelector('.close-product-template-form');
                if (closeProductTemplateBtn) {
                    closeProductTemplateBtn.addEventListener('click', function(){
                        productModal.style.display = 'none';
                    });
                }
                if (productModal) {
                    productModal.addEventListener('click', function(e){ if (e.target === this) this.style.display = 'none'; });
                }

                function showProductTemplateForm(type, model){
                    const titleMap = { studio: 'تصویر استودیویی محصول (E-commerce)', lifestyle: 'تصویر لایف‌استایل مینیمال', reflect: 'تصویر با انعکاس شیشه‌ای' };
                    document.getElementById('product-template-form-title').textContent = titleMap[type] || 'قالب محصول ووکامرس';
                    const modelSelect = document.getElementById('product-template-model-select');
                    if (modelSelect) modelSelect.value = model || 'gapgpt/flux.1-dev';
                    // پیش‌تنظیم فیلدها بر اساس نوع
                    if (type === 'studio') {
                        document.getElementById('product_bg').value = 'سفید خالص';
                        document.getElementById('product_shadow').value = 'بدون انعکاس، سایه نرم طبیعی';
                        document.getElementById('product_style').value = 'E-commerce studio';
                    } else if (type === 'lifestyle') {
                        document.getElementById('product_bg').value = 'سطح چوب روشن مینیمال';
                        document.getElementById('product_shadow').value = 'سایه Drop Shadow طبیعی';
                        document.getElementById('product_style').value = 'Minimal lifestyle scene';
                    } else if (type === 'reflect') {
                        document.getElementById('product_bg').value = 'گرادیان خیلی روشن';
                        document.getElementById('product_shadow').value = 'انعکاس شیشه‌ای ظریف';
                        document.getElementById('product_style').value = 'Glossy reflection';
                    }
                    initCustomSelectHandlers(productModal);
                    productModal.style.display = 'flex';
                }

                // انتخاب تصویر(های) مرجع محصول
                document.addEventListener('click', function(e){
                    if (e.target && (e.target.id === 'select-product-ref-single' || e.target.id === 'select-product-ref-multi')) {
                        e.preventDefault();
                        if (typeof wp === 'undefined' || !wp.media) { alert('کتابخانه رسانه وردپرس در دسترس نیست.'); return; }
                        const multiple = e.target.id === 'select-product-ref-multi';
                        const frame = wp.media({ title: multiple ? 'انتخاب چند تصویر' : 'انتخاب تصویر', multiple, library: { type: 'image' } });
                        frame.on('select', function(){
                            const sel = frame.state().get('selection');
                            const items = [];
                            sel.each(att => { const a = att.toJSON(); items.push({ id: a.id, url: a.url }); });
                            const jsonEl = document.getElementById('product-reference-images-json');
                            jsonEl.value = JSON.stringify(items);
                            const gal = document.getElementById('product-reference-gallery');
                            gal.innerHTML = '';
                            if (items.length) {
                                gal.style.display = 'flex';
                                items.forEach(it => {
                                    const img = document.createElement('img');
                                    img.src = it.url; img.style.maxWidth = '110px'; img.style.height = 'auto'; img.style.border = '1px solid #e5e5e5'; img.style.borderRadius = '6px';
                                    gal.appendChild(img);
                                });
                            } else { gal.style.display = 'none'; }
                        });
                        frame.open();
                    }
                });

                // سابمیت قالب محصول
                const productForm = document.getElementById('product-image-template-form');
                if (productForm) {
                    productForm.addEventListener('submit', function(e){
                        e.preventDefault();
                        const fd = new FormData(productForm);
                        const items = JSON.parse(document.getElementById('product-reference-images-json').value || '[]');
                        const prompt = buildProductPromptFromFormData(fd);

                        const imageTab = document.getElementById('images');
                        const mainForm = imageTab ? imageTab.querySelector('form') : null;
                        if (mainForm) {
                            const promptField = mainForm.querySelector('#smart_admin_image_prompt');
                            const modelSelect = mainForm.querySelector('#smart_admin_image_model');
                            const sizeSelect = mainForm.querySelector('#smart_admin_image_size');
                            const qualitySelect = mainForm.querySelector('#smart_admin_image_quality');
                            const refIdInput = mainForm.querySelector('#smart_admin_reference_image_id');
                            const refUrlInput = mainForm.querySelector('#smart_admin_reference_image_url');
                            const refUrlsInput = mainForm.querySelector('#smart_admin_reference_image_urls');

                            if (promptField) promptField.value = prompt;
                            if (modelSelect) modelSelect.value = document.getElementById('product-template-model-select').value || modelSelect.value;
                            if (qualitySelect) qualitySelect.value = 'high';

                            // اندازه براساس نسبت
                            const aspect = fd.get('product_aspect') || '1:1';
                            const aspectToSize = { '1:1': '1024x1024', '4:5': '1024x1280', '16:9': '1280x720' };
                            if (sizeSelect && aspectToSize[aspect]) sizeSelect.value = aspectToSize[aspect];

                            // تصویر(های) مرجع
                            if (items.length) {
                                if (refIdInput) refIdInput.value = items[0].id;
                                if (refUrlInput) refUrlInput.value = items[0].url;
                                if (refUrlsInput) refUrlsInput.value = JSON.stringify(items.map(i => i.url));
                                const preview = document.getElementById('reference-image-preview');
                                const img = document.getElementById('reference-image-thumb');
                                if (preview && img) { img.src = items[0].url; preview.style.display = 'block'; }
                            }

                            productModal.style.display = 'none';
                            mainForm.submit();
                        }
                    });
                }

                function buildProductPromptFromFormData(fd){
                    const title = fd.get('product_title') || 'یک محصول';
                    const angle = fd.get('product_angle') || '3/4 45-deg';
                    const bg = (fd.get('product_bg') === 'custom' ? (fd.get('product_bg_custom') || '') : fd.get('product_bg')) || 'سفید خالص';
                    const shadow = fd.get('product_shadow') || 'بدون انعکاس، سایه نرم طبیعی';
                    const style = fd.get('product_style') || 'E-commerce studio';
                    const aspect = fd.get('product_aspect') || '1:1';
                    const processing = document.getElementById('processing_style_select')?.value || 'Photorealistic';
                    const stylization = document.getElementById('stylization_select')?.value || 'Medium';
                    const tone = document.getElementById('color_tone_select')?.value || 'Neutral';
                    const parts = [];
                    parts.push(`Product photo: ${title}`);
                    parts.push(`Angle: ${angle}`);
                    parts.push(`Background: ${bg}`);
                    parts.push(`Shadow/Reflection: ${shadow}`);
                    parts.push(`Style: ${style}`);
                    parts.push(`Aspect ratio: ${aspect}`);
                    parts.push(`Processing style: ${processing}`);
                    parts.push(`Stylization: ${stylization}`);
                    parts.push(`Color tone: ${tone}`);
                    parts.push('Ultra-clean studio quality, high detail, sharp focus, consistent lighting, e-commerce ready');
                    return parts.join(' | ');
                }
                function getImageTemplateFields(templateTitle){
                    switch(templateTitle){
                        case 'پرتره استودیویی حرفه‌ای':
                            return `
                                <div class="form-row" style="display:flex; gap:12px; flex-wrap:wrap;">
                                    <div class="form-group" style="flex:1 1 260px;">
                                        <label for="subject">سوژه (جنسیت/سن/ظاهر):</label>
                                        <input type="text" id="subject" name="subject" placeholder="مثال: زن ۳۰ ساله با موهای مشکی و فر">
                                    </div>
                                    <div class="form-group" style="flex:1 1 220px;">
                                        <label for="mood_select">حال‌و‌هوا:</label>
                                        <select id="mood_select" name="mood_select" data-allow-custom="1">
                                            <option value="حرفه‌ای">حرفه‌ای</option>
                                            <option value="دوستانه">دوستانه</option>
                                            <option value="جدی">جدی</option>
                                            <option value="خلاقانه">خلاقانه</option>
                                            <option value="دراماتیک">دراماتیک</option>
                                            <option value="شاد">شاد</option>
                                            <option value="مینیمال">مینیمال</option>
                                            <option value="custom">سفارشی...</option>
                                        </select>
                                        <div class="custom-input-wrap" style="display:none;margin-top:6px;">
                                            <input type="text" id="mood_custom" name="mood_custom" placeholder="حال‌وهوای سفارشی را وارد کنید">
                                        </div>
                                    </div>
                                </div>
                                <div class="form-row" style="display:flex; gap:12px; flex-wrap:wrap;">
                                    <div class="form-group" style="flex:1 1 220px;">
                                        <label for="lighting_select">نورپردازی:</label>
                                        <select id="lighting_select" name="lighting_select" data-allow-custom="1">
                                            <option value="ریم لایت">ریم لایت</option>
                                            <option value="Rembrandt">Rembrandt</option>
                                            <option value="Butterfly">Butterfly</option>
                                            <option value="High-key">High-key</option>
                                            <option value="Low-key">Low-key</option>
                                            <option value="نور پنجره طبیعی">نور پنجره طبیعی</option>
                                            <option value="Softbox 45°">Softbox 45°</option>
                                            <option value="custom">سفارشی...</option>
                                        </select>
                                        <div class="custom-input-wrap" style="display:none;margin-top:6px;">
                                            <input type="text" id="lighting_custom" name="lighting_custom" placeholder="نورپردازی سفارشی را وارد کنید">
                                        </div>
                                    </div>
                                    <div class="form-group" style="flex:1 1 220px;">
                                        <label for="camera_select">دوربین/لنز:</label>
                                        <select id="camera_select" name="camera_select" data-allow-custom="1">
                                            <option value="85mm f/1.4">85mm f/1.4</option>
                                            <option value="50mm f/1.8">50mm f/1.8</option>
                                            <option value="35mm f/1.4">35mm f/1.4</option>
                                            <option value="24-70mm f/2.8">24-70mm f/2.8</option>
                                            <option value="Medium format">Medium format</option>
                                            <option value="custom">سفارشی...</option>
                                        </select>
                                        <div class="custom-input-wrap" style="display:none;margin-top:6px;">
                                            <input type="text" id="camera_custom" name="camera_custom" placeholder="لنز/دوربین سفارشی را وارد کنید">
                                        </div>
                                    </div>
                                    <div class="form-group" style="flex:1 1 220px;">
                                        <label for="background_select">پس‌زمینه:</label>
                                        <select id="background_select" name="background_select" data-allow-custom="1">
                                            <option value="تیره">تیره</option>
                                            <option value="روشن">روشن</option>
                                            <option value="گرادیانی">گرادیانی</option>
                                            <option value="سفید خالص">سفید خالص</option>
                                            <option value="خاکستری روشن">خاکستری روشن</option>
                                            <option value="بافت ملایم">بافت ملایم</option>
                                            <option value="custom">سفارشی...</option>
                                        </select>
                                        <div class="custom-input-wrap" style="display:none;margin-top:6px;">
                                            <input type="text" id="background_custom" name="background_custom" placeholder="پس‌زمینه سفارشی را وارد کنید">
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="details_select">جزئیات و سبک:</label>
                                    <select id="details_select" name="details_select" data-allow-custom="1">
                                        <option value="پوست طبیعی، رتوش سبک">پوست طبیعی، رتوش سبک</option>
                                        <option value="جزئیات بالا، کنتراست متعادل">جزئیات بالا، کنتراست متعادل</option>
                                        <option value="بوکه پس‌زمینه نرم">بوکه پس‌زمینه نرم</option>
                                        <option value="HDR ملایم">HDR ملایم</option>
                                        <option value="اشباع رنگ ملایم">اشباع رنگ ملایم</option>
                                        <option value="custom">سفارشی...</option>
                                    </select>
                                    <div class="custom-input-wrap" style="display:none;margin-top:6px;">
                                        <input type="text" id="details_custom" name="details_custom" placeholder="جزئیات/سبک سفارشی را وارد کنید">
                                    </div>
                                </div>
                                ${getCommonProcessingFields()}
                            `;
                        case 'عکس محصول ای‌کامرس (پس‌زمینه سفید)':
                            return `
                                <div class="form-row" style="display:flex; gap:12px; flex-wrap:wrap;">
                                    <div class="form-group" style="flex:1 1 260px;">
                                        <label for="product">نام محصول/دسته:</label>
                                        <input type="text" id="product" name="product" placeholder="مثال: کفش ورزشی سفید">
                                    </div>
                                    <div class="form-group" style="flex:1 1 220px;">
                                        <label for="angle_select">زاویه دید:</label>
                                        <select id="angle_select" name="angle_select" data-allow-custom="1">
                                            <option value="سه‌چهارم 45 درجه">سه‌چهارم 45 درجه</option>
                                            <option value="نمای روبه‌رو (Front)">نمای روبه‌رو (Front)</option>
                                            <option value="نمای بالا (Top-down)">نمای بالا (Top-down)</option>
                                            <option value="نمای جانبی (Side)">نمای جانبی (Side)</option>
                                            <option value="ماکرو (Macro)">ماکرو (Macro)</option>
                                            <option value="custom">سفارشی...</option>
                                        </select>
                                        <div class="custom-input-wrap" style="display:none;margin-top:6px;">
                                            <input type="text" id="angle_custom" name="angle_custom" placeholder="زاویه سفارشی را وارد کنید">
                                        </div>
                                    </div>
                                    <div class="form-group" style="flex:1 1 220px;">
                                        <label for="shadow_select">سایه/انعکاس:</label>
                                        <select id="shadow_select" name="shadow_select" data-allow-custom="1">
                                            <option value="بدون انعکاس، سایه نرم طبیعی">بدون انعکاس، سایه نرم طبیعی</option>
                                            <option value="سایه Drop Shadow طبیعی">سایه Drop Shadow طبیعی</option>
                                            <option value="انعکاس ملایم زیر محصول">انعکاس ملایم زیر محصول</option>
                                            <option value="بدون سایه/انعکاس">بدون سایه/انعکاس</option>
                                            <option value="custom">سفارشی...</option>
                                        </select>
                                        <div class="custom-input-wrap" style="display:none;margin-top:6px;">
                                            <input type="text" id="shadow_custom" name="shadow_custom" placeholder="سایه/انعکاس سفارشی را وارد کنید">
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="props_select">تجهیزات/جزئیات تکمیلی:</label>
                                    <select id="props_select" name="props_select" data-allow-custom="1">
                                        <option value="نور یکنواخت، پس‌زمینه کاملاً سفید">نور یکنواخت، پس‌زمینه کاملاً سفید</option>
                                        <option value="سطح سفید تمیز با سایه نرم">سطح سفید تمیز با سایه نرم</option>
                                        <option value="پس‌زمینه گرادیانی خیلی روشن">پس‌زمینه گرادیانی خیلی روشن</option>
                                        <option value="پس‌زمینه شفاف (برای برش)">پس‌زمینه شفاف (برای برش)</option>
                                        <option value="custom">سفارشی...</option>
                                    </select>
                                    <div class="custom-input-wrap" style="display:none;margin-top:6px;">
                                        <input type="text" id="props_custom" name="props_custom" placeholder="جزئیات سفارشی را وارد کنید">
                                    </div>
                                </div>
                                ${getCommonProcessingFields()}
                            `;
                        case 'پوستر تبلیغاتی مینیمال':
                            return `
                                <div class="form-row" style="display:flex; gap:12px; flex-wrap:wrap;">
                                    <div class="form-group" style="flex:1 1 240px;">
                                        <label for="topic">موضوع/محصول/خدمت:</label>
                                        <input type="text" id="topic" name="topic" placeholder="مثال: اپلیکیشن مدیریت مالی">
                                    </div>
                                    <div class="form-group" style="flex:1 1 200px;">
                                        <label for="message">پیام کلیدی:</label>
                                        <input type="text" id="message" name="message" placeholder="مثال: ساده، سریع، امن">
                                    </div>
                                    <div class="form-group" style="flex:1 1 200px;">
                                        <label for="brand_colors_select">رنگ‌های برند/پالت:</label>
                                        <select id="brand_colors_select" name="brand_colors_select" data-allow-custom="1">
                                            <option value="رنگ‌های برند">رنگ‌های برند</option>
                                            <option value="مونوکروم (سیاه/سفید/خاکستری)">مونوکروم (سیاه/سفید/خاکستری)</option>
                                            <option value="دو‌رنگ (Duotone)">دو‌رنگ (Duotone)</option>
                                            <option value="پاستلی">پاستلی</option>
                                            <option value="پرطراوت (Vibrant)">پرطراوت (Vibrant)</option>
                                            <option value="custom">سفارشی...</option>
                                        </select>
                                        <div class="custom-input-wrap" style="display:none;margin-top:6px;">
                                            <input type="text" id="brand_colors_custom" name="brand_colors_custom" placeholder="پالت رنگ سفارشی">
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="audience">مخاطب هدف:</label>
                                    <input type="text" id="audience" name="audience" placeholder="مثال: دانشجویان و فریلنسرها">
                                </div>
                                ${getCommonProcessingFields()}
                            `;
                        case 'منظرۀ سینمایی حماسی':
                            return `
                                <div class="form-row" style="display:flex; gap:12px; flex-wrap:wrap;">
                                    <div class="form-group" style="flex:1 1 240px;">
                                        <label for="location">لوکیشن/منظره:</label>
                                        <input type="text" id="location" name="location" placeholder="مثال: کوهستان مه‌آلود با دریاچه">
                                    </div>
                                    <div class="form-group" style="flex:1 1 200px;">
                                        <label for="time_select">زمان/نور:</label>
                                        <select id="time_select" name="time_select" data-allow-custom="1">
                                            <option value="طلوع">طلوع</option>
                                            <option value="غروب">غروب</option>
                                            <option value="Golden hour">Golden hour</option>
                                            <option value="Blue hour">Blue hour</option>
                                            <option value="شب">شب</option>
                                            <option value="custom">سفارشی...</option>
                                        </select>
                                        <div class="custom-input-wrap" style="display:none;margin-top:6px;">
                                            <input type="text" id="time_custom" name="time_custom" placeholder="زمان/نور سفارشی">
                                        </div>
                                    </div>
                                    <div class="form-group" style="flex:1 1 200px;">
                                        <label for="weather_select">هوا/اتمسفر:</label>
                                        <select id="weather_select" name="weather_select" data-allow-custom="1">
                                            <option value="هوای صاف">هوای صاف</option>
                                            <option value="ابری">ابری</option>
                                            <option value="مه ملایم">مه ملایم</option>
                                            <option value="بارانی">بارانی</option>
                                            <option value="برفی">برفی</option>
                                            <option value="custom">سفارشی...</option>
                                        </select>
                                        <div class="custom-input-wrap" style="display:none;margin-top:6px;">
                                            <input type="text" id="weather_custom" name="weather_custom" placeholder="وضعیت هوا سفارشی">
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="cinematic_select">جزئیات سینمایی/کمپوزیشن:</label>
                                    <select id="cinematic_select" name="cinematic_select" data-allow-custom="1">
                                        <option value="عمق میدان کم">عمق میدان کم</option>
                                        <option value="نسبت طلایی">نسبت طلایی</option>
                                        <option value="Rule of thirds">Rule of thirds</option>
                                        <option value="واید انگل دراماتیک">واید انگل دراماتیک</option>
                                        <option value="لانگ اکسپوژر">لانگ اکسپوژر</option>
                                        <option value="aerial">نمای هوایی</option>
                                        <option value="custom">سفارشی...</option>
                                    </select>
                                    <div class="custom-input-wrap" style="display:none;margin-top:6px;">
                                        <input type="text" id="cinematic_custom" name="cinematic_custom" placeholder="جزئیات سینمایی سفارشی">
                                    </div>
                                </div>
                                ${getCommonProcessingFields()}
                            `;
                        case 'آیکون فلت مدرن':
                            return `
                                <div class="form-row" style="display:flex; gap:12px; flex-wrap:wrap;">
                                    <div class="form-group" style="flex:1 1 260px;">
                                        <label for="icon_concept">مفهوم آیکون:</label>
                                        <input type="text" id="icon_concept" name="icon_concept" placeholder="مثال: ابر داده (Cloud)">
                                    </div>
                                    <div class="form-group" style="flex:1 1 200px;">
                                        <label for="icon_colors_select">پالت رنگی:</label>
                                        <select id="icon_colors_select" name="icon_colors_select" data-allow-custom="1">
                                            <option value="۲-۳ رنگ تخت با کنتراست بالا">۲-۳ رنگ تخت با کنتراست بالا</option>
                                            <option value="تک‌رنگ (Monochrome)">تک‌رنگ (Monochrome)</option>
                                            <option value="دو‌رنگ (Duotone)">دو‌رنگ (Duotone)</option>
                                            <option value="پاستلی">پاستلی</option>
                                            <option value="custom">سفارشی...</option>
                                        </select>
                                        <div class="custom-input-wrap" style="display:none;margin-top:6px;">
                                            <input type="text" id="icon_colors_custom" name="icon_colors_custom" placeholder="پالت سفارشی">
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="icon_rules_select">قیود سبک/خوانایی:</label>
                                    <select id="icon_rules_select" name="icon_rules_select" data-allow-custom="1">
                                        <option value="خطوط ساده، بدون نویز، بدون متن">خطوط ساده، بدون نویز، بدون متن</option>
                                        <option value="Outline با ضخامت یکنواخت">Outline با ضخامت یکنواخت</option>
                                        <option value="Fill تخت بدون گرادیان">Fill تخت بدون گرادیان</option>
                                        <option value="سایه خیلی ظریف">سایه خیلی ظریف</option>
                                        <option value="custom">سفارشی...</option>
                                    </select>
                                    <div class="custom-input-wrap" style="display:none;margin-top:6px;">
                                        <input type="text" id="icon_rules_custom" name="icon_rules_custom" placeholder="قیود سفارشی">
                                    </div>
                                </div>
                                ${getCommonProcessingFields()}
                            `;
                        case 'تصویر شاخص خبر/مقاله (حرفه‌ای)':
                            return `
                                <div class="form-row" style="display:flex; gap:12px; flex-wrap:wrap;">
                                    <div class="form-group" style="flex:1 1 260px;">
                                        <label for="headline_topic">موضوع خبر/مقاله:</label>
                                        <input type="text" id="headline_topic" name="headline_topic" placeholder="مثال: رشد بازار انرژی های تجدیدپذیر">
                                    </div>
                                    <div class="form-group" style="flex:1 1 200px;">
                                        <label for="domain_select">حوزه (برای هماهنگی سبک):</label>
                                        <select id="domain_select" name="domain_select" data-allow-custom="1">
                                            <option value="فناوری">فناوری</option>
                                            <option value="گردشگری">گردشگری</option>
                                            <option value="خوراکی و آشپزی">خوراکی و آشپزی</option>
                                            <option value="اقتصادی/کسب‌وکار">اقتصادی/کسب‌وکار</option>
                                            <option value="آب و انرژی">آب و انرژی</option>
                                            <option value="نفت و گاز">نفت و گاز</option>
                                            <option value="ایمنی و آتشنشانی">ایمنی و آتشنشانی</option>
                        					<option value="عمومی">عمومی</option>
                                            <option value="custom">سفارشی...</option>
                                        </select>
                                        <div class="custom-input-wrap" style="display:none;margin-top:6px;">
                                            <input type="text" id="domain_custom" name="domain_custom" placeholder="حوزه سفارشی">
                                        </div>
                                    </div>
                                </div>
                                <div class="form-row" style="display:flex; gap:12px; flex-wrap:wrap;">
                                    <div class="form-group" style="flex:1 1 200px;">
                                        <label for="color_style_select">استایل رنگ:</label>
                                        <select id="color_style_select" name="color_style_select" data-allow-custom="1">
                                            <option value="مونوکروم حرفه‌ای">مونوکروم حرفه‌ای</option>
                                            <option value="آبی سازمانی + فیروزه‌ای">آبی سازمانی + فیروزه‌ای</option>
                                            <option value="پالت پاستلی ملایم">پالت پاستلی ملایم</option>
                                            <option value="پالت پرطراوت (Vibrant)">پالت پرطراوت (Vibrant)</option>
                                            <option value="custom">سفارشی...</option>
                                        </select>
                                        <div class="custom-input-wrap" style="display:none;margin-top:6px;">
                                            <input type="text" id="color_style_custom" name="color_style_custom" placeholder="استایل رنگ سفارشی">
                                        </div>
                                    </div>
                                    <div class="form-group" style="flex:1 1 200px;">
                                        <label for="visual_style_select">حال‌و‌هوای بصری:</label>
                                        <select id="visual_style_select" name="visual_style_select" data-allow-custom="1">
                                            <option value="حرفه‌ای، خبری، مدرن، بدون متن">حرفه‌ای، خبری، مدرن، بدون متن</option>
                                            <option value="مینیمال، تمیز، بدون متن">مینیمال، تمیز، بدون متن</option>
                                            <option value="سینمایی ملایم، بدون متن">سینمایی ملایم، بدون متن</option>
                                            <option value="Illustrative بدون متن">Illustrative بدون متن</option>
                                            <option value="custom">سفارشی...</option>
                                        </select>
                                        <div class="custom-input-wrap" style="display:none;margin-top:6px;">
                                            <input type="text" id="visual_style_custom" name="visual_style_custom" placeholder="حال‌وهوای بصری سفارشی">
                                        </div>
                                    </div>
                                </div>
                                ${getCommonProcessingFields()}
                            `;
                        case 'اسکرین‌شات حرفه‌ای (فریم دستگاه/مرورگر)':
                            return `
                                <div class="form-row" style="display:flex; gap:12px; flex-wrap:wrap;">
                                    <div class="form-group" style="flex:1 1 220px;">
                                        <label for="screen_subject">موضوع صفحه/اپ:</label>
                                        <input type="text" id="screen_subject" name="screen_subject" placeholder="مثال: داشبورد تحلیل فروش">
                                    </div>
                                    <div class="form-group" style="flex:1 1 180px;">
                                        <label for="device_frame">نوع قاب:</label>
                                        <select id="device_frame" name="device_frame">
                                            <option value="desktop">Desktop</option>
                                            <option value="laptop">Laptop</option>
                                            <option value="tablet">Tablet</option>
                                            <option value="mobile">Mobile</option>
                                            <option value="browser">Browser Frame</option>
                                        </select>
                                    </div>
                                    <div class="form-group" style="flex:1 1 180px;">
                                        <label for="theme_mode">تم:</label>
                                        <select id="theme_mode" name="theme_mode">
                                            <option value="light">روشن</option>
                                            <option value="dark">تیره</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="ui_details">جزئیات UI که باید دیده شود:</label>
                                    <input type="text" id="ui_details" name="ui_details" placeholder="مثال: نمودار خطی، کارت متریک، سایدبار مینیمال، بدون متن واقعی">
                                </div>
                                ${getCommonProcessingFields()}
                            `;
                        case 'تصویر محصول حرفه‌ای (ویرایش با مرجع کتابخانه)':
                            return `
                                <div class="form-row" style="display:flex; gap:12px; flex-wrap:wrap;">
                                    <div class="form-group" style="flex:1 1 240px;">
                                        <label for="product_fix_select">اصلاحات مورد نظر:</label>
                                        <select id="product_fix_select" name="product_fix_select" data-allow-custom="1">
                                            <option value="حذف پس‌زمینه به سفید خالص">حذف پس‌زمینه به سفید خالص</option>
                                            <option value="پاکسازی نویز و لکه + افزایش شفافیت">پاکسازی نویز و لکه + افزایش شفافیت</option>
                                            <option value="اصلاح رنگ و کنتراست استاندارد">اصلاح رنگ و کنتراست استاندارد</option>
                                            <option value="صاف‌کردن پرسپکتیو و راستا">صاف‌کردن پرسپکتیو و راستا</option>
                                            <option value="افزودن سایه نرم طبیعی">افزودن سایه نرم طبیعی</option>
                                            <option value="custom">سفارشی...</option>
                                        </select>
                                        <div class="custom-input-wrap" style="display:none;margin-top:6px;">
                                            <input type="text" id="product_fix_custom" name="product_fix_custom" placeholder="اصلاحات سفارشی">
                                        </div>
                                    </div>
                                    <div class="form-group" style="flex:1 1 220px;">
                                        <label for="product_context">سناریوی خروجی (اختیاری):</label>
                                        <input type="text" id="product_context" name="product_context" placeholder="مثال: چیدمان ویترینی مینیمال">
                                    </div>
                                </div>
                                <input type="hidden" id="image-template-reference-image-id" value="">
                                <input type="hidden" id="image-template-reference-image-url" value="">
                                <div class="form-group">
                                    <button type="button" class="button" id="select-reference-image">انتخاب تصویر از کتابخانه وردپرس</button>
                                    <div id="image-template-reference-preview" style="display:none;margin-top:8px;">
                                        <img id="image-template-reference-thumb" src="" alt="ref" style="max-width:220px;height:auto;border:1px solid #e5e5e5;border-radius:6px;">
                                    </div>
                                </div>
                                ${getCommonProcessingFields()}
                            `;
                        default:
                            return '';
                    }
                }

                function getChoice(fd, base){
                    const sel = fd.get(base + '_select');
                    if (!sel) return '';
                    if (sel === 'custom') {
                        return fd.get(base + '_custom') || '';
                    }
                    return sel;
                }
                function getCommonProcessingFields(){
                    return `
                        <div class="form-group" style="margin-top:8px;">
                            <label for="processing_style_select">نوع پردازش/سبک کلی:</label>
                            <select id="processing_style_select" name="processing_style_select" data-allow-custom="1">
                                <option value="Photorealistic">Photorealistic</option>
                                <option value="Cinematic">Cinematic</option>
                                <option value="Illustration">Illustration</option>
                                <option value="3D Render">3D Render</option>
                                <option value="Watercolor">Watercolor</option>
                                <option value="Flat Vector">Flat Vector</option>
                                <option value="Isometric">Isometric</option>
                                <option value="custom">سفارشی...</option>
                            </select>
                            <div class="custom-input-wrap" style="display:none;margin-top:6px;">
                                <input type="text" id="processing_style_custom" name="processing_style_custom" placeholder="نوع پردازش سفارشی">
                            </div>
                        </div>
                        <div class="form-row" style="display:flex; gap:12px; flex-wrap:wrap;">
                            <div class="form-group" style="flex:1 1 200px;">
                                <label for="stylization_select">میزان استایل/پردازش:</label>
                                <select id="stylization_select" name="stylization_select" data-allow-custom="1">
                                    <option value="Very Low">Very Low</option>
                                    <option value="Low">Low</option>
                                    <option value="Medium" selected>Medium</option>
                                    <option value="High">High</option>
                                    <option value="Very High">Very High</option>
                                    <option value="custom">سفارشی...</option>
                                </select>
                                <div class="custom-input-wrap" style="display:none;margin-top:6px;">
                                    <input type="text" id="stylization_custom" name="stylization_custom" placeholder="میزان پردازش سفارشی">
                                </div>
                            </div>
                            <div class="form-group" style="flex:1 1 200px;">
                                <label for="color_tone_select">تونالیته رنگ:</label>
                                <select id="color_tone_select" name="color_tone_select" data-allow-custom="1">
                                    <option value="Neutral">Neutral</option>
                                    <option value="Vibrant">Vibrant</option>
                                    <option value="Pastel">Pastel</option>
                                    <option value="Warm">Warm</option>
                                    <option value="Cool">Cool</option>
                                    <option value="Monochrome">Monochrome</option>
                                    <option value="Duotone">Duotone</option>
                                    <option value="custom">سفارشی...</option>
                                </select>
                                <div class="custom-input-wrap" style="display:none;margin-top:6px;">
                                    <input type="text" id="color_tone_custom" name="color_tone_custom" placeholder="تونالیته سفارشی">
                                </div>
                            </div>
                        </div>
                    `;
                }

                function initCustomSelectHandlers(container){
                    const selects = container.querySelectorAll('select[data-allow-custom="1"]');
                    selects.forEach(sel => {
                        const wrap = sel.parentElement.querySelector('.custom-input-wrap');
                        const toggle = () => { if (wrap) wrap.style.display = sel.value === 'custom' ? 'block' : 'none'; };
                        sel.addEventListener('change', toggle);
                        toggle();
                    });
                    // واکنش به تغییر حوزه کلی و پیشنهاد مقادیر
                    const globalDomain = document.getElementById('global-domain-select');
                    if (globalDomain) {
                        const applyProfile = () => {
                            const profileKey = globalDomain.value === 'custom' ? 'عمومی' : globalDomain.value;
                            const profile = DOMAIN_PROFILES[profileKey] || DOMAIN_PROFILES['عمومی'];
                            // حال‌وهوا
                            const moodSel = container.querySelector('#mood_select');
                            if (moodSel && profile.mood) {
                                // اگر گزینه موجود بود، مقدار اول پروفایل را بگذار
                                for (const opt of moodSel.options) {
                                    if (profile.mood.includes(opt.value)) { moodSel.value = opt.value; break; }
                                }
                            }
                            // پالت رنگ در پوستر/آیکون/خبر
                            const posterPalette = container.querySelector('#brand_colors_select');
                            const iconPalette = container.querySelector('#icon_colors_select');
                            const colorStyle = container.querySelector('#color_style_select');
                            const candidates = [posterPalette, iconPalette, colorStyle];
                            candidates.forEach(sel => {
                                if (sel && profile.colorPalettes) {
                                    for (const opt of sel.options) {
                                        if (profile.colorPalettes.includes(opt.value)) { sel.value = opt.value; break; }
                                    }
                                }
                            });
                            // نوع پردازش مشترک
                            const processingSel = container.querySelector('#processing_style_select');
                            if (processingSel && profile.processing) {
                                for (const opt of processingSel.options) {
                                    if (profile.processing.includes(opt.value)) { processingSel.value = opt.value; break; }
                                }
                            }
                        };
                        globalDomain.addEventListener('change', applyProfile);
                        applyProfile();
                    }
                }

                function buildImagePromptFromFormData(fd, templateTitle){
                    let parts = [];
                    switch(templateTitle){
                        case 'پرتره استودیویی حرفه‌ای':
                            parts.push(`پرتره استودیویی رئالیستی از ${fd.get('subject') || 'یک فرد'}، حال‌و‌هوا: ${getChoice(fd,'mood') || 'حرفه‌ای'}`);
                            parts.push(`نورپردازی: ${getChoice(fd,'lighting') || 'ریم لایت + نور نرم سافت‌باکس'}`);
                            parts.push(`دوربین/لنز: ${getChoice(fd,'camera') || '85mm f/1.4'}`);
                            parts.push(`پس‌زمینه: ${getChoice(fd,'background') || 'تیره و مینیمال'}`);
                            parts.push(`جزئیات: ${getChoice(fd,'details') || 'پوست طبیعی، فوکوس روی چشم‌ها، جزئیات بالا'}`);
                            break;
                        case 'عکس محصول ای‌کامرس (پس‌زمینه سفید)':
                            parts.push(`عکاسی محصول از ${fd.get('product') || 'یک محصول'} روی پس‌زمینه سفید خالص`);
                            parts.push(`زاویه دید: ${getChoice(fd,'angle') || 'سه‌چهارم 45 درجه'}`);
                            parts.push(`سایه/انعکاس: ${getChoice(fd,'shadow') || 'سایه نرم طبیعی، بدون انعکاس'}`);
                            parts.push(`${getChoice(fd,'props') || 'نور یکنواخت، سطح سفید تمیز'}`);
                            parts.push('ترکیب‌بندی تمیز، مناسب فروشگاه اینترنتی، وضوح بالا');
                            break;
                        case 'پوستر تبلیغاتی مینیمال':
                            parts.push(`طراحی پوستر تبلیغاتی مینیمال برای ${fd.get('topic') || 'یک محصول'}`);
                            parts.push(`پیام/تیتر: ${fd.get('message') || 'پیام اصلی کوتاه و واضح'}`);
                            parts.push(`پالت رنگ: ${getChoice(fd,'brand_colors') || 'پالت محدود با کنتراست بالا'}`);
                            parts.push(`مخاطب هدف: ${fd.get('audience') || 'عمومی'}`);
                            parts.push('فضای منفی کافی، تایپوگرافی برجسته، بدون شلوغی، ترکیب‌بندی متوازن');
                            break;
                        case 'منظرۀ سینمایی حماسی':
                            parts.push(`منظرۀ طبیعی سینمایی: ${fd.get('location') || 'منظره طبیعی'}، زمان: ${getChoice(fd,'time') || 'غروب'}، هوا: ${getChoice(fd,'weather') || 'مه ملایم'}`);
                            parts.push(`جزئیات سینمایی/کمپوزیشن: ${getChoice(fd,'cinematic') || 'عمق میدان کم، نسبت طلایی، نور دراماتیک'}`);
                            parts.push('رنگ‌های سینمایی، کنتراست بالا، حس مقیاس و عمق');
                            break;
                        case 'آیکون فلت مدرن':
                            parts.push(`آیکون فلت مدرن با مفهوم ${fd.get('icon_concept') || 'مفهوم مشخص'}`);
                            parts.push(`پالت رنگ: ${getChoice(fd,'icon_colors') || '۲-۳ رنگ تخت با کنتراست بالا'}`);
                            parts.push(`${getChoice(fd,'icon_rules') || 'خطوط ساده، بدون نویز، بدون متن، پس‌زمینه تک‌رنگ'}`);
                            parts.push('سبک فلت، ساده، خوانا در اندازه کوچک');
                            break;
                        case 'تصویر شاخص خبر/مقاله (حرفه‌ای)':
                            parts.push(`کاور حرفه‌ای بدون متن برای ${fd.get('headline_topic') || 'خبر/مقاله'}`);
                            parts.push(`حوزه: ${getChoice(fd,'domain') || 'عمومی'}`);
                            parts.push(`استایل رنگ: ${getChoice(fd,'color_style') || 'رنگ‌های برند یا مونوکروم حرفه‌ای'}`);
                            parts.push(`حال‌و‌هوا: ${getChoice(fd,'visual_style') || 'مدرن، خبری، تمیز، بدون متن'}`);
                            parts.push('ترکیب‌بندی متوازن، کنتراست مناسب، تمرکز سوژه واضح، بدون نویز');
                            break;
                        case 'اسکرین‌شات حرفه‌ای (فریم دستگاه/مرورگر)':
                            parts.push(`تصویر سبک اسکرین‌شات از ${fd.get('screen_subject') || 'یک اپ/سایت'}`);
                            parts.push(`قاب دستگاه: ${fd.get('device_frame') || 'browser'}`);
                            parts.push(`تم: ${fd.get('theme_mode') || 'light'}`);
                            parts.push(`${fd.get('ui_details') || 'نمودار/کارت/سایدبار، بدون متن واقعی، آیکون‌های عمومی'}`);
                            parts.push('قاب آدرس‌بار/نوار ابزار شبیه‌سازی‌شده، سایه نرم، پس‌زمینه تمیز');
                            break;
                        case 'تصویر محصول حرفه‌ای (ویرایش با مرجع کتابخانه)':
                            parts.push(`ویرایش تصویر محصول با حفظ ظاهر واقعی: ${getChoice(fd,'product_fix') || 'حذف پس‌زمینه به سفید خالص، نور یکنواخت، سایه نرم'}`);
                            if (fd.get('product_context')) parts.push(`سناریو: ${fd.get('product_context')}`);
                            parts.push('کیفیت بالا، مناسب ای‌کامرس، جزئیات واضح');
                            break;
                        default:
                            parts.push('تصویر با کیفیت بالا و ترکیب‌بندی دقیق');
                    }
                    // مشترک: نوع پردازش و رنگ
                    const processing = getChoice(fd,'processing_style');
                    const stylization = getChoice(fd,'stylization');
                    const tone = getChoice(fd,'color_tone');
                    if (processing) parts.push(`Processing style: ${processing}`);
                    if (stylization) parts.push(`Stylization: ${stylization}`);
                    if (tone) parts.push(`Color tone: ${tone}`);
                    return parts.filter(Boolean).join(' | ');
                }

                // انتخاب تصویر از کتابخانه وردپرس (برای image-to-image)
                document.addEventListener('click', function(e){
                    if (e.target && e.target.id === 'select-reference-image') {
                        e.preventDefault();
                        if (typeof wp === 'undefined' || !wp.media) { alert('کتابخانه رسانه وردپرس در دسترس نیست.'); return; }
                        const frame = wp.media({ title: 'انتخاب تصویر مرجع', multiple: false, library: { type: 'image' } });
                        frame.on('select', function(){
                            const attachment = frame.state().get('selection').first().toJSON();
                            const idEl = document.getElementById('image-template-reference-image-id');
                            const urlEl = document.getElementById('image-template-reference-image-url');
                            if (idEl) idEl.value = attachment.id;
                            if (urlEl) urlEl.value = attachment.url;
                            const prev = document.getElementById('image-template-reference-preview');
                            const img = document.getElementById('image-template-reference-thumb');
                            if (prev && img) { img.src = attachment.url; prev.style.display = 'block'; }
                        });
                        frame.open();
                    }
                });
            })();
            </script>
            <?php if (!empty($image_result)): ?>
                <div class="generated-image-container" style="margin-top:20px">
                    <?php if (isset($image_result['error'])): ?>
                        <div class="response-container error"><p><span class="error-icon">⚠️</span><strong>خطا:</strong> <?php echo esc_html($image_result['error']); ?></p></div>
                    <?php else: ?>
                        <h4>نتیجه:</h4>
                        <div class="generated-image" style="display:flex;gap:12px;flex-wrap:wrap">
                            <?php foreach ($image_result['images'] as $img): ?>
                                <div style="text-align:center">
                                    <img src="<?php echo esc_url($img); ?>" alt="تصویر تولید شده" style="max-width:250px;height:auto;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,.1)">
                                    <div class="image-info" style="margin-top:8px">
                                        <button type="button" class="button button-secondary" onclick="downloadImage('<?php echo esc_url($img); ?>', 'generated-image')">دانلود</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <div id="prompt" class="tab-content active">
            <form method="post" class="prompt-form" id="ai-prompt-form">
                <?php wp_nonce_field('smart_admin_prompt_action', 'smart_admin_nonce'); ?>
                <input type="hidden" id="is_template" name="is_template" value="0">
                
                <div class="form-group">
                    <label for="smart_admin_model">انتخاب مدل هوش مصنوعی:</label>
                    <select id="smart_admin_model" name="smart_admin_model">
                        <option value="gpt-4o">GPT-4o - مدل پیشرفته OpenAI با قابلیت‌های چندمنظوره</option>
                        <option value="claude-3-7-sonnet-20250219">Claude 3.7 Sonnet - مدل متعادل Anthropic با کارایی بالا</option>
                        <option value="gemini-2.0-flash">Gemini 2.0 Flash - مدل ارزان و به‌صرفه Google با کارایی بالا</option>
                        <option value="deepseek-chat">DeepSeek Chat - مدل چت هوشمند DeepSeek با تمرکز بر مکالمات طبیعی</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="smart_admin_prompt">متن پرامپت:</label>
                    <textarea id="smart_admin_prompt" name="smart_admin_prompt" placeholder="موضوع یا پرامپت خود را وارد کنید..."></textarea>
                </div>
                
                <?php add_human_tone_option_to_form(); ?>
                
                <button type="submit" class="submit-button" id="generate-btn">
                    <span id="loading-spinner" class="loading-spinner"></span>
                    <span>تولید محتوا</span>
                </button>
            </form>
            
            <?php if (isset($response) && !empty($response)): ?>
                <div class="response-container <?php echo isset($response['error']) ? 'error' : ''; ?>">
                    <?php if (isset($response['error'])): ?>
                        <p><span class="error-icon">⚠️</span><strong>خطا:</strong> <?php echo esc_html($response['error']); ?></p>
                    <?php else: ?>
                        <h3>پاسخ هوش مصنوعی:</h3>
                        <div id="ai-response-content"><?php echo nl2br(esc_html($response['content'])); ?></div>
                        
                        <?php if (isset($response['generated_image'])): ?>
                            <div class="generated-image-container">
                                <h4>تصویر تولید شده:</h4>
                                <div class="generated-image">
                                    <img src="<?php echo esc_url($response['generated_image']['image_url']); ?>" alt="تصویر تولید شده" style="max-width: 100%; height: auto; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                                    <div class="image-info">
                                        <p><strong>پرامپت تصویر:</strong> <?php echo esc_html($response['generated_image']['prompt']); ?></p>
                                        <button type="button" class="button button-secondary" onclick="downloadImage('<?php echo esc_url($response['generated_image']['image_url']); ?>', 'generated-image')">دانلود تصویر</button>
                                    </div>
                                </div>
                            </div>
                            
                            <style>
                                .generated-image-container {
                                    margin-top: 20px;
                                    padding: 15px;
                                    background: #f9f9f9;
                                    border-radius: 8px;
                                    border-left: 4px solid #0073aa;
                                }
                                .generated-image {
                                    text-align: center;
                                }
                                .image-info {
                                    margin-top: 10px;
                                    text-align: center;
                                }
                                .image-info p {
                                    margin-bottom: 10px;
                                    color: #666;
                                }
                            </style>
                            
                            <script>
                            function downloadImage(imageUrl, filename) {
                                const link = document.createElement('a');
                                link.href = imageUrl;
                                link.download = filename + '.jpg';
                                document.body.appendChild(link);
                                link.click();
                                document.body.removeChild(link);
                            }
                            </script>
                        <?php endif; ?>
                        
                        <div class="save-draft-form">
                            <h3>ذخیره به عنوان پیش‌نویس</h3>
                            <p>این محتوا را به عنوان یک پیش‌نویس در وردپرس ذخیره کنید.</p>
                            
                            <form method="post">
                                <?php wp_nonce_field('smart_admin_save_draft_action', 'smart_admin_save_draft_nonce'); ?>
                                <input type="hidden" name="ai_response" value="<?php echo esc_attr($response['content']); ?>">
                                
                                <div class="form-row">
                                    <label for="post_title">عنوان مقاله:</label>
                                    <input type="text" id="post_title" name="post_title" required placeholder="عنوان مقاله را وارد کنید...">
                                </div>
                                
                                <div class="form-row">
                                    <label for="post_keywords">برچسب‌ها (با کاما جدا کنید):</label>
                                    <input type="text" id="post_keywords" name="post_keywords" placeholder="برچسب 1، برچسب 2، برچسب 3">
                                </div>
                                
                                <button type="submit" name="save_as_draft" class="submit-button">ذخیره به عنوان پیش‌نویس</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <div id="templates" class="tab-content">
            <h3>قالب‌های آماده برای تولید محتوا</h3>
            <p>از این قالب‌های آماده و بهینه‌سازی شده برای تولید محتوای با کیفیت استفاده کنید. کافیست فیلدهای زیر را پر کنید.</p>
            <p><strong>💡 نکته:</strong> می‌توانید مدل هوش مصنوعی را از لیست موجود انتخاب کنید. مدل پیش‌فرض هر قالب در کارت آن نمایش داده شده است.</p>
            
            <?php if (!empty($success_message)): ?>
                <div class="success-message">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <div class="templates-grid">
                <?php 
                $default_prompts = get_default_content_prompts();
                foreach ($default_prompts as $index => $prompt): 
                ?>
                    <div class="template-card">
                        <h3><?php echo esc_html($prompt['title']); ?></h3>
                        <span class="template-card-model">مدل پیش‌فرض: <?php echo esc_html($prompt['model']); ?></span>
                        <div class="template-card-description">
                            <?php 
                            // نمایش توضیحات کوتاه برای هر قالب
                            $descriptions = array(
                                'مقاله تخصصی گردشگری و سفر' => 'ایجاد راهنمای سفر کامل و جامع برای مقاصد گردشگری',
                                'مقاله تخصصی خوراکی و آشپزی' => 'تولید محتوای تخصصی در حوزه غذا، آشپزی و دستور پخت',
                                'مقاله جامع و بنیادی (Pillar Page)' => 'ایجاد مقاله جامع و معتبر برای موضوع انتخابی شما',
                                'مقاله به روش آسمان‌خراش (Skyscraper)' => 'نوشتن مقاله بهتر از رقبا با محتوای برتر',
                                'راهنمای عملی قدم به قدم (How-to)' => 'آموزش مرحله به مرحله برای انجام کارهای مختلف',
                                'مقاله لیستی (مثلا: ۱۰ ابزار برتر)' => 'ایجاد لیست‌های جذاب و مفید',
                                'مقاله مقایسه‌ای (X در مقابل Y)' => 'مقایسه دقیق بین دو گزینه مختلف',
                                'مقاله کاملاً استاندارد برای Rank Math (امتیاز 90+)' => 'تولید مقاله با امتیاز بالای ۹۰ در Rank Math',
                                'مقاله تخصصی فناوری اطلاعات و برنامه‌نویسی' => 'تولید محتوای تخصصی برای حوزه IT و برنامه‌نویسی',
                                'مقاله تخصصی صنعت آب، انرژی و آب و برق' => 'تولید محتوای تخصصی برای صنایع آب و انرژی',
                                'مقاله تخصصی صنعت نفت، گاز و پتروشیمی' => 'تولید محتوای تخصصی برای صنایع نفت و گاز',
                                'مقاله تخصصی صنعت آتشنشانی' => 'تولید محتوای تخصصی برای حوزه آتشنشانی و ایمنی'
                            );
                            $description = isset($descriptions[$prompt['title']]) ? $descriptions[$prompt['title']] : 'قالب آماده برای تولید محتوا';
                            echo esc_html($description);
                            ?>
                        </div>
                        <div class="template-card-actions">
                            <button type="button" class="use-template-btn" 
                                data-template-index="<?php echo $index; ?>"
                                data-template-title="<?php echo esc_attr($prompt['title']); ?>"
                                data-model="<?php echo esc_attr($prompt['model']); ?>"
                                data-is-template="1">
                                استفاده از قالب
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <!-- فرم داینامیک برای قالب‌ها -->
            <div id="template-form-modal" style="display: none;" class="template-form-modal">
                <div class="template-form-content">
                    <div class="template-form-header">
                        <h3 id="template-form-title">قالب انتخاب شده</h3>
                        <button type="button" class="close-template-form">&times;</button>
                    </div>
                    <form id="template-form" method="post" class="prompt-form">
                        <?php wp_nonce_field('smart_admin_prompt_action', 'smart_admin_nonce'); ?>
                        <input type="hidden" id="template-prompt" name="smart_admin_prompt" value="">
                        <input type="hidden" id="is_template" name="is_template" value="1">
                        
                        <!-- انتخاب مدل هوش مصنوعی -->
                        <div class="form-group">
                            <label for="template-model-select">مدل هوش مصنوعی: <span style="color: #666; font-size: 12px;">(می‌توانید مدل را تغییر دهید)</span></label>
                            <select id="template-model-select" name="smart_admin_model" required>
                                <optgroup label="OpenAI">
                                    <option value="gpt-4o" selected>GPT-4o - مدل پیشرفته OpenAI با قابلیت‌های چندمنظوره</option>
                                </optgroup>
                                
                                <optgroup label="Anthropic">
                                    <option value="claude-opus-4-20250514">Claude Opus 4 - مدل پیشرفته Anthropic با قابلیت‌های فوق‌العاده</option>
                                    <option value="claude-sonnet-4-20250514">Claude Sonnet 4 - مدل متعادل Anthropic با کارایی بالا</option>
                                    <option value="claude-3-7-sonnet-20250219">Claude 3.7 Sonnet - مدل متعادل Anthropic با کارایی بالا</option>
                                    <option value="claude-3-7-sonnet-20250219-thinking">Claude 3.7 Sonnet Thinking - مدل با قابلیت تفکر عمیق</option>
                                    <option value="claude-3-5-haiku-20241022">Claude 3.5 Haiku - مدل سریع و به‌صرفه Anthropic</option>
                                </optgroup>
                                
                                <optgroup label="Google">
                                    <option value="gemini-2.0-flash">Gemini 2.0 Flash - مدل ارزان و به‌صرفه Google</option>
                                    <option value="gemini-2.0-flash-preview-image-generation">Gemini 2.0 Flash Preview - مدل با قابلیت تولید تصویر</option>
                                    <option value="gemini-2.0-flash-lite-001">Gemini 2.0 Flash Lite - مدل سبک و سریع</option>
                                    <option value="gemini-2.0-flash-lite-preview">Gemini 2.0 Flash Lite Preview - نسخه پیش‌نمایش</option>
                                    <option value="gemini-2.0-flash-live-001">Gemini 2.0 Flash Live - مدل زنده و به‌روز</option>
                                    <option value="gemini-2.5-flash-preview-native-audio-dialog">Gemini 2.5 Flash - مدل با قابلیت صوتی</option>
                                    <option value="gemini-2.5-flash">Gemini 2.5 Flash - مدل پیشرفته</option>
                                    <option value="gemini-1.5-pro">Gemini 1.5 Pro - مدل حرفه‌ای</option>
                                </optgroup>
                                
                                <optgroup label="DeepSeek">
                                    <option value="deepseek-chat">DeepSeek Chat - مدل چت هوشمند</option>
                                    <option value="deepseek-reasoner">DeepSeek Reasoner - مدل با قابلیت استدلال</option>
                                </optgroup>
                                
                                <optgroup label="Alibaba">
                                    <option value="qwen3-coder-480b-a35b-instruct">Qwen3 Coder - مدل تخصصی برنامه‌نویسی</option>
                                    <option value="qwen3-235b-a22b">Qwen3 235B - مدل بزرگ و قدرتمند</option>
                                </optgroup>
                            </select>
                        </div>
                        
                        <div id="template-fields">
                            <!-- فیلدهای داینامیک اینجا اضافه می‌شوند -->
                        </div>
                        
                        <?php add_human_tone_option_to_form(); ?>
                        
                        <button type="submit" class="submit-button" id="generate-template-btn">
                            <span id="loading-spinner-template" class="loading-spinner"></span>
                            <span>تولید محتوا</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div id="saved" class="tab-content">
            <div class="saved-prompts">
                <h3>پرامپت‌های ذخیره شده</h3>
                
                <div class="tab-selector">
                    <a href="#" class="prompt-filter active" data-filter="all">همه</a>
                    <a href="#" class="prompt-filter" data-filter="custom">پرامپت‌های سفارشی</a>
                    <a href="#" class="prompt-filter" data-filter="template">قالب‌های آماده</a>
                </div>
                
                <?php 
                $saved_prompts = get_option('smart_admin_saved_prompts', array());
                if (empty($saved_prompts)): 
                ?>
                    <p>هیچ پرامپتی ذخیره نشده است.</p>
                <?php else: ?>
                    <?php foreach ($saved_prompts as $index => $prompt): 
                        $is_template = isset($prompt['is_template']) && $prompt['is_template'];
                        $prompt_title = isset($prompt['title']) ? $prompt['title'] : 'پرامپت سفارشی';
                    ?>
                        <div class="prompt-card <?php echo $is_template ? 'template' : 'custom'; ?>">
                            <div class="prompt-card-header">
                                <div>
                                    <?php if ($is_template): ?>
                                        <span class="prompt-template-tag">قالب</span>
                                    <?php endif; ?>
                                    <span class="prompt-card-model"><?php echo esc_html($prompt['model']); ?></span>
                                </div>
                                <span class="prompt-card-date"><?php echo esc_html(human_time_diff(strtotime($prompt['date']), current_time('timestamp'))); ?> پیش</span>
                            </div>
                            <div class="prompt-card-title">
                                <?php echo esc_html($prompt_title); ?>
                            </div>
                            <div class="prompt-card-content">
                                <?php echo nl2br(esc_html(substr($prompt['prompt'], 0, 300) . (strlen($prompt['prompt']) > 300 ? '...' : ''))); ?>
                            </div>
                            <div class="prompt-card-actions">
                                <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(array('page' => 'smart-admin', 'delete_prompt' => $index), admin_url('admin.php')), 'smart_admin_delete_prompt_action', 'smart_admin_delete_nonce')); ?>" class="delete-prompt" onclick="return confirm('آیا از حذف این پرامپت اطمینان دارید؟');">حذف</a>
                                <a href="#" class="use-prompt" data-prompt="<?php echo esc_attr($prompt['prompt']); ?>" data-model="<?php echo esc_attr($prompt['model']); ?>" data-is-template="<?php echo $is_template ? '1' : '0'; ?>">استفاده مجدد</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div id="drafts" class="tab-content">
            <h3>پیش‌نویس‌های دستیار هوشمند</h3>
            <p>در این بخش می‌توانید پیش‌نویس‌های ایجاد شده توسط دستیار هوشمند را مشاهده و مدیریت کنید.</p>
            
            <div class="drafts-tab-selector">
                <a href="<?php echo admin_url('edit.php?post_status=draft&post_type=post&cat=' . get_option('smart_admin_assistant_category_id')); ?>" class="view-all-drafts">مشاهده همه پیش‌نویس‌ها</a>
                <a href="<?php echo admin_url('post-new.php?post_type=post'); ?>" class="new-post">ایجاد نوشته جدید</a>
            </div>
            
            <div class="ai-drafts-wrap">
                <?php
                $ai_drafts = smart_admin_get_ai_drafts(6);
                
                if (empty($ai_drafts)):
                ?>
                    <p>هیچ پیش‌نویسی توسط دستیار هوشمند ایجاد نشده است.</p>
                <?php else: ?>
                    <div class="ai-drafts-grid">
                        <?php foreach ($ai_drafts as $draft): ?>
                            <div class="ai-draft-card">
                                <h4><?php echo esc_html($draft->post_title); ?></h4>
                                <div class="ai-draft-card-date">
                                    <?php echo sprintf('ایجاد شده در %s', get_the_date(get_option('date_format'), $draft->ID)); ?>
                                </div>
                                <div class="ai-draft-card-actions">
                                    <a href="<?php echo get_permalink($draft->ID); ?>?preview=true" class="view" target="_blank">پیش‌نمایش</a>
                                    <a href="<?php echo get_edit_post_link($draft->ID); ?>" class="edit">ویرایش</a>
                                    <a href="<?php echo admin_url('admin.php?action=publish_ai_draft&post_id=' . $draft->ID . '&_wpnonce=' . wp_create_nonce('publish_ai_draft_' . $draft->ID)); ?>" class="publish">انتشار</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div id="settings" class="tab-content">
            <form method="post" action="options.php" class="settings-form">
                <?php settings_fields('smart_admin_settings'); ?>
                
                <div class="form-group">
                    <label for="smart_admin_api_key">کلید API گپ جی‌پی‌تی:</label>
                    <input type="text" id="smart_admin_api_key" name="smart_admin_api_key" value="<?php echo esc_attr(get_option('smart_admin_api_key', 'sk-8exa7q6H5GpW2BO7v72z50Nd5zCiEhK13hiz4nzJ9XuXyEYO')); ?>" placeholder="کلید API خود را وارد کنید" />
                </div>
                
                <div class="form-group">
                    <label for="default_model">مدل پیش‌فرض:</label>
                    <select id="default_model" name="smart_admin_model">
                        <optgroup label="OpenAI">
                            <option value="gpt-4o" <?php selected(get_option('smart_admin_model'), 'gpt-4o'); ?>>GPT-4o - مدل پیشرفته OpenAI با قابلیت‌های چندمنظوره</option>
                        </optgroup>
                        
                        <optgroup label="Anthropic">
                            <option value="claude-opus-4-20250514" <?php selected(get_option('smart_admin_model'), 'claude-opus-4-20250514'); ?>>Claude Opus 4 - مدل پیشرفته Anthropic با قابلیت‌های فوق‌العاده</option>
                            <option value="claude-sonnet-4-20250514" <?php selected(get_option('smart_admin_model'), 'claude-sonnet-4-20250514'); ?>>Claude Sonnet 4 - مدل متعادل Anthropic با کارایی بالا</option>
                            <option value="claude-3-7-sonnet-20250219" <?php selected(get_option('smart_admin_model'), 'claude-3-7-sonnet-20250219'); ?>>Claude 3.7 Sonnet - مدل متعادل Anthropic با کارایی بالا</option>
                            <option value="claude-3-7-sonnet-20250219-thinking" <?php selected(get_option('smart_admin_model'), 'claude-3-7-sonnet-20250219-thinking'); ?>>Claude 3.7 Sonnet Thinking - مدل با قابلیت تفکر عمیق</option>
                            <option value="claude-3-5-haiku-20241022" <?php selected(get_option('smart_admin_model'), 'claude-3-5-haiku-20241022'); ?>>Claude 3.5 Haiku - مدل سریع و به‌صرفه Anthropic</option>
                        </optgroup>
                        
                        <optgroup label="Google">
                            <option value="gemini-2.0-flash" <?php selected(get_option('smart_admin_model'), 'gemini-2.0-flash'); ?>>Gemini 2.0 Flash - مدل ارزان و به‌صرفه Google</option>
                            <option value="gemini-2.0-flash-preview-image-generation" <?php selected(get_option('smart_admin_model'), 'gemini-2.0-flash-preview-image-generation'); ?>>Gemini 2.0 Flash Preview - مدل با قابلیت تولید تصویر</option>
                            <option value="gemini-2.0-flash-lite-001" <?php selected(get_option('smart_admin_model'), 'gemini-2.0-flash-lite-001'); ?>>Gemini 2.0 Flash Lite - مدل سبک و سریع</option>
                            <option value="gemini-2.0-flash-lite-preview" <?php selected(get_option('smart_admin_model'), 'gemini-2.0-flash-lite-preview'); ?>>Gemini 2.0 Flash Lite Preview - نسخه پیش‌نمایش</option>
                            <option value="gemini-2.0-flash-live-001" <?php selected(get_option('smart_admin_model'), 'gemini-2.0-flash-live-001'); ?>>Gemini 2.0 Flash Live - مدل زنده و به‌روز</option>
                            <option value="gemini-2.5-flash-preview-native-audio-dialog" <?php selected(get_option('smart_admin_model'), 'gemini-2.5-flash-preview-native-audio-dialog'); ?>>Gemini 2.5 Flash - مدل با قابلیت صوتی</option>
                            <option value="gemini-2.5-flash" <?php selected(get_option('smart_admin_model'), 'gemini-2.5-flash'); ?>>Gemini 2.5 Flash - مدل پیشرفته</option>
                            <option value="gemini-1.5-pro" <?php selected(get_option('smart_admin_model'), 'gemini-1.5-pro'); ?>>Gemini 1.5 Pro - مدل حرفه‌ای</option>
                        </optgroup>
                        
                        <optgroup label="DeepSeek">
                            <option value="deepseek-chat" <?php selected(get_option('smart_admin_model'), 'deepseek-chat'); ?>>DeepSeek Chat - مدل چت هوشمند</option>
                            <option value="deepseek-reasoner" <?php selected(get_option('smart_admin_model'), 'deepseek-reasoner'); ?>>DeepSeek Reasoner - مدل با قابلیت استدلال</option>
                        </optgroup>
                        
                        <optgroup label="Alibaba">
                            <option value="qwen3-coder-480b-a35b-instruct" <?php selected(get_option('smart_admin_model'), 'qwen3-coder-480b-a35b-instruct'); ?>>Qwen3 Coder - مدل تخصصی برنامه‌نویسی</option>
                            <option value="qwen3-235b-a22b" <?php selected(get_option('smart_admin_model'), 'qwen3-235b-a22b'); ?>>Qwen3 235B - مدل بزرگ و قدرتمند</option>
                        </optgroup>
                    </select>
                </div>
                <fieldset class="form-group" style="border:1px solid #e5e5e5;padding:12px;border-radius:8px;margin-top:14px;">
                    <legend style="padding:0 8px;">قوانین قالب‌بندی محتوا</legend>
                    <label style="display:flex;gap:8px;align-items:center;margin-bottom:8px;">
                        <input type="checkbox" name="smart_admin_enforce_formatting" value="1" <?php checked(get_option('smart_admin_enforce_formatting', 1), 1); ?>>
                        <span>اجبار قالب‌بندی استاندارد (HTML با h2/h3 و strong، بدون Markdown)</span>
                    </label>
                    <div style="display:flex;gap:16px;flex-wrap:wrap;">
                        <label style="display:flex;gap:8px;align-items:center;">
                            <input type="checkbox" name="smart_admin_allow_intro" value="1" <?php checked(get_option('smart_admin_allow_intro', 0), 1); ?>>
                            <span>اجازه «مقدمه»</span>
                        </label>
                        <label style="display:flex;gap:8px;align-items:center;">
                            <input type="checkbox" name="smart_admin_allow_conclusion" value="1" <?php checked(get_option('smart_admin_allow_conclusion', 0), 1); ?>>
                            <span>اجازه «نتیجه‌گیری/جمع‌بندی»</span>
                        </label>
                        <label style="display:flex;gap:8px;align-items:center;">
                            <input type="checkbox" name="smart_admin_allow_faq" value="1" <?php checked(get_option('smart_admin_allow_faq', 0), 1); ?>>
                            <span>اجازه «FAQ»</span>
                        </label>
                    </div>
                    <p class="description">در صورت غیرفعال بودن هرکدام، آن بخش‌ها از خروجی حذف خواهند شد.</p>
                </fieldset>
                
                <fieldset class="form-group" style="border:1px solid #e5e5e5;padding:12px;border-radius:8px;margin-top:14px;">
                    <legend style="padding:0 8px;">تنظیمات نام برند</legend>
                    <label style="display:flex;gap:8px;align-items:center;margin-bottom:12px;">
                        <input type="checkbox" id="smart_admin_allow_brand" name="smart_admin_allow_brand" value="1" <?php checked(get_option('smart_admin_allow_brand', 0), 1); ?>>
                        <span>اجازه استفاده از نام برند در محتوا</span>
                    </label>
                    <div id="brand_name_field" style="display:none;margin-top:8px;">
                        <label for="smart_admin_brand_name" style="display:block;margin-bottom:4px;font-weight:bold;">نام برند یا سازمان:</label>
                        <input type="text" id="smart_admin_brand_name" name="smart_admin_brand_name" value="<?php echo esc_attr(get_option('smart_admin_brand_name', '')); ?>" placeholder="مثال: پیک خورشید اهواز" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;">
                        <p class="description" style="margin-top:4px;font-size:12px;color:#666;">نام برند شما در طول مقاله به صورت طبیعی استفاده خواهد شد.</p>
                    </div>
                    
                    <script>
                    // اسکریپت مستقیم برای مدیریت فیلد نام برند
                    (function() {
                        function toggleBrandField() {
                            const checkbox = document.getElementById('smart_admin_allow_brand');
                            const field = document.getElementById('brand_name_field');
                            
                            if (checkbox && field) {
                                field.style.display = checkbox.checked ? 'block' : 'none';
                            }
                        }
                        
                        // اجرای فوری
                        setTimeout(toggleBrandField, 100);
                        
                        // اضافه کردن event listener
                        const checkbox = document.getElementById('smart_admin_allow_brand');
                        if (checkbox) {
                            checkbox.addEventListener('change', toggleBrandField);
                        }
                    })();
                    </script>
                </fieldset>

				<div class="form-group">
					<label for="smart_admin_image_model">مدل پیش‌فرض ساخت تصویر (GapGPT):</label>
					<select id="smart_admin_image_model" name="smart_admin_image_model">
						<optgroup label="GapGPT - Image">
							<option value="gapgpt/flux.1-schnell" <?php selected(get_option('smart_admin_image_model'), 'gapgpt/flux.1-schnell'); ?>>Flux 1 Schnell (GapGPT)</option>
							<option value="gapgpt/flux.1-dev" <?php selected(get_option('smart_admin_image_model'), 'gapgpt/flux.1-dev'); ?>>Flux 1 Dev (GapGPT)</option>
						</optgroup>
						<optgroup label="OpenAI - Image">
							<option value="dall-e-3" <?php selected(get_option('smart_admin_image_model'), 'dall-e-3'); ?>>DALL·E 3</option>
							<option value="dall-e-2" <?php selected(get_option('smart_admin_image_model'), 'dall-e-2'); ?>>DALL·E 2</option>
						</optgroup>
						<optgroup label="BFL - FLUX">
							<option value="flux-1-schnell" <?php selected(get_option('smart_admin_image_model'), 'flux-1-schnell'); ?>>FLUX 1 Schnell</option>
							<option value="flux/dev" <?php selected(get_option('smart_admin_image_model'), 'flux/dev'); ?>>FLUX Dev</option>
							<option value="flux-pro/kontext/text-to-image" <?php selected(get_option('smart_admin_image_model'), 'flux-pro/kontext/text-to-image'); ?>>FLUX Pro Kontext</option>
							<option value="flux-pro/kontext/max/text-to-image" <?php selected(get_option('smart_admin_image_model'), 'flux-pro/kontext/max/text-to-image'); ?>>FLUX Pro Kontext Max</option>
						</optgroup>
						<optgroup label="Google">
							<option value="imagen-3.0-generate-002" <?php selected(get_option('smart_admin_image_model'), 'imagen-3.0-generate-002'); ?>>Imagen 3 Generate 002</option>
						</optgroup>
					</select>
				</div>
                
                <?php submit_button('ذخیره تنظیمات', 'submit-button', 'submit', false); ?>
            </form>
            
            <hr>
            <h3>اطلاعات قیمت‌گذاری مدل‌ها</h3>
            <p>جدول زیر قیمت‌های تقریبی برای ۱ میلیون توکن را نشان می‌دهد:</p>
            
            <div class="model-pricing-table">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>ارائه‌دهنده</th>
                            <th>مدل</th>
                            <th>قیمت ورودی (هر ۱M توکن)</th>
                            <th>قیمت خروجی (هر ۱M توکن)</th>
                            <th>نوع</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>OpenAI</strong></td>
                            <td>GPT-4o</td>
                            <td>$5.00</td>
                            <td>$15.00</td>
                            <td>پیشرفته</td>
                        </tr>
                        <tr>
                            <td><strong>Anthropic</strong></td>
                            <td>Claude Opus 4</td>
                            <td>$15.00</td>
                            <td>$75.00</td>
                            <td>فوق‌العاده</td>
                        </tr>
                        <tr>
                            <td><strong>Anthropic</strong></td>
                            <td>Claude Sonnet 4</td>
                            <td>$3.00</td>
                            <td>$15.00</td>
                            <td>متعادل</td>
                        </tr>
                        <tr>
                            <td><strong>Anthropic</strong></td>
                            <td>Claude 3.7 Sonnet</td>
                            <td>$3.00</td>
                            <td>$15.00</td>
                            <td>متعادل</td>
                        </tr>
                        <tr>
                            <td><strong>Anthropic</strong></td>
                            <td>Claude 3.5 Haiku</td>
                            <td>$1.00</td>
                            <td>$5.00</td>
                            <td>سریع</td>
                        </tr>
                        <tr>
                            <td><strong>Google</strong></td>
                            <td>Gemini 2.0 Flash</td>
                            <td>$0.07</td>
                            <td>$0.30</td>
                            <td>به‌صرفه</td>
                        </tr>
                        <tr>
                            <td><strong>Google</strong></td>
                            <td>Gemini 2.5 Flash</td>
                            <td>$0.30</td>
                            <td>$2.50</td>
                            <td>پیشرفته</td>
                        </tr>
                        <tr>
                            <td><strong>DeepSeek</strong></td>
                            <td>DeepSeek Chat</td>
                            <td>$0.27</td>
                            <td>$1.08</td>
                            <td>متعادل</td>
                        </tr>
                        <tr>
                            <td><strong>DeepSeek</strong></td>
                            <td>DeepSeek Reasoner</td>
                            <td>$0.55</td>
                            <td>$2.20</td>
                            <td>استدلالی</td>
                        </tr>
                        <tr>
                            <td><strong>Alibaba</strong></td>
                            <td>Qwen3 Coder</td>
                            <td>$3.00</td>
                            <td>$12.00</td>
                            <td>برنامه‌نویسی</td>
                        </tr>
                        <tr>
                            <td><strong>Alibaba</strong></td>
                            <td>Qwen3 235B</td>
                            <td>$0.16</td>
                            <td>$0.48</td>
                            <td>به‌صرفه</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <style>
                .model-pricing-table {
                    margin-top: 20px;
                }
                .model-pricing-table table {
                    font-size: 13px;
                }
                .model-pricing-table th {
                    background-color: #f1f1f1;
                    font-weight: bold;
                }
                .model-pricing-table td {
                    padding: 8px;
                }
            </style>
        </div>
        
        <?php
        // فراخوانی تابع برای نمایش محتوای تب زمان‌بندی
        if (function_exists('smart_admin_scheduler_tab_content')) {
            smart_admin_scheduler_tab_content();
        }
        ?>
    </div>
    
    <!-- تب لاگ‌ها -->
    <div id="logs" class="tab-content">
        <h3>لاگ‌های سیستم</h3>
        
        <div class="log-section">
            <h4>لاگ‌های Smart Admin</h4>
            <?php
            $smart_admin_log_file = plugin_dir_path(__FILE__) . 'smart-admin-debug.log';
            if (file_exists($smart_admin_log_file)) {
                $log_content = file_get_contents($smart_admin_log_file);
                $log_lines = explode("\n", $log_content);
                $recent_lines = array_slice($log_lines, -50); // آخرین 50 خط
                echo '<div class="log-display">';
                echo '<pre>' . htmlspecialchars(implode("\n", $recent_lines)) . '</pre>';
                echo '</div>';
                echo '<p><a href="' . plugin_dir_url(__FILE__) . 'smart-admin-debug.log" target="_blank" class="button button-secondary">دانلود فایل کامل لاگ</a></p>';
            } else {
                echo '<p>فایل لاگ Smart Admin موجود نیست.</p>';
            }
            ?>
        </div>
        
        <div class="log-section">
            <h4>لاگ‌های عمومی WordPress</h4>
            <?php
            $wp_debug_log = WP_CONTENT_DIR . '/debug.log';
            if (file_exists($wp_debug_log)) {
                $log_content = file_get_contents($wp_debug_log);
                $log_lines = explode("\n", $log_content);
                $recent_lines = array_slice($log_lines, -30); // آخرین 30 خط
                echo '<div class="log-display">';
                echo '<pre>' . htmlspecialchars(implode("\n", $recent_lines)) . '</pre>';
                echo '</div>';
                echo '<p><a href="' . content_url('debug.log') . '" target="_blank" class="button button-secondary">دانلود فایل کامل لاگ</a></p>';
            } else {
                echo '<p>فایل لاگ WordPress موجود نیست.</p>';
            }
            ?>
        </div>
        
        <div class="log-section">
            <h4>لاگ‌های افزونه</h4>
            <?php
            $plugin_logs = [
                'auto-report-debug.log' => 'لاگ گزارش خودکار',
                'telegram_logs.txt' => 'لاگ تلگرام',
                'whatsapp_logs.txt' => 'لاگ واتساپ'
            ];
            
            foreach ($plugin_logs as $log_file => $log_name) {
                $log_path = plugin_dir_path(__FILE__) . $log_file;
                if (file_exists($log_path)) {
                    $size = filesize($log_path);
                    echo '<div class="log-file-info">';
                    echo '<h5>' . $log_name . '</h5>';
                    echo '<p>اندازه: ' . number_format($size) . ' بایت</p>';
                    echo '<p><a href="' . plugin_dir_url(__FILE__) . $log_file . '" target="_blank" class="button button-small">مشاهده</a></p>';
                    echo '</div>';
                }
            }
            ?>
        </div>
        
        <div class="log-section">
            <h4>اطلاعات سیستم</h4>
            <div class="system-info">
                <p><strong>API Key:</strong> <?php echo get_option('smart_admin_api_key') ? 'تنظیم شده' : 'تنظیم نشده'; ?></p>
                <p><strong>مدل پیش‌فرض:</strong> <?php echo get_option('smart_admin_model', 'تنظیم نشده'); ?></p>
                <p><strong>حالت دیباگ:</strong> <?php echo function_exists('smart_admin_get_setting') && smart_admin_get_setting('debug_mode') ? 'فعال' : 'غیرفعال'; ?></p>
                <p><strong>نسخه WordPress:</strong> <?php echo get_bloginfo('version'); ?></p>
                <p><strong>نسخه PHP:</strong> <?php echo PHP_VERSION; ?></p>
            </div>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // مدیریت فیلد نام برند
        function initBrandField() {
            const brandCheckbox = document.getElementById('smart_admin_allow_brand');
            const brandField = document.getElementById('brand_name_field');
            
            if (brandCheckbox && brandField) {
                function toggleBrandField() {
                    brandField.style.display = brandCheckbox.checked ? 'block' : 'none';
                }
                
                // تنظیم حالت اولیه
                toggleBrandField();
                
                // اضافه کردن event listener
                brandCheckbox.addEventListener('change', toggleBrandField);
            }
        }
        
        // اجرای فوری
        initBrandField();
        
        // اجرای مجدد بعد از کمی تاخیر
        setTimeout(initBrandField, 500);
        
        // تابع عمومی دانلود تصویر برای تمام تب‌ها
        window.downloadImage = function(imageUrl, filename) {
            const link = document.createElement('a');
            link.href = imageUrl;
            link.download = (filename || 'image') + '.jpg';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        };

        // مدیریت تب‌ها
        const tabLinks = document.querySelectorAll('.tab-link');
        const tabContents = document.querySelectorAll('.tab-content');
        
        tabLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // حذف کلاس active از همه تب‌ها
                tabLinks.forEach(item => item.classList.remove('active'));
                tabContents.forEach(item => item.classList.remove('active'));
                
                // افزودن کلاس active به تب انتخاب شده
                this.classList.add('active');
                document.getElementById(this.getAttribute('data-tab')).classList.add('active');
                
                // ذخیره تب فعال در localStorage
                localStorage.setItem('smartAdminActiveTab', this.getAttribute('data-tab'));
            });
        });
        
        // بازیابی تب فعال از localStorage
        const activeTab = localStorage.getItem('smartAdminActiveTab');
        if (activeTab) {
            const link = document.querySelector(`.tab-link[data-tab="${activeTab}"]`);
            if (link) {
                link.click();
            }
        }

        // فیلتر کردن پرامپت‌ها
        const promptFilters = document.querySelectorAll('.prompt-filter');
        const promptCards = document.querySelectorAll('.prompt-card');
        
        promptFilters.forEach(filter => {
            filter.addEventListener('click', function(e) {
                e.preventDefault();
                
                // حذف کلاس active از همه فیلترها
                promptFilters.forEach(item => item.classList.remove('active'));
                
                // افزودن کلاس active به فیلتر انتخاب شده
                this.classList.add('active');
                
                const filterValue = this.getAttribute('data-filter');
                
                promptCards.forEach(card => {
                    if (filterValue === 'all') {
                        card.style.display = 'block';
                    } else {
                        if (card.classList.contains(filterValue)) {
                            card.style.display = 'block';
                        } else {
                            card.style.display = 'none';
                        }
                    }
                });
            });
        });
        
        // استفاده از قالب‌های آماده با فرم داینامیک
        const useTemplateButtons = document.querySelectorAll('.use-template-btn');
        useTemplateButtons.forEach(button => {
            button.addEventListener('click', function() {
                const templateIndex = this.getAttribute('data-template-index');
                const templateTitle = this.getAttribute('data-template-title');
                const model = this.getAttribute('data-model');
                
                // نمایش مودال فرم
                showTemplateForm(templateIndex, templateTitle, model);
            });
        });
        
        // بستن مودال فرم قالب
        const closeTemplateForm = document.querySelector('.close-template-form');
        if (closeTemplateForm) {
            closeTemplateForm.addEventListener('click', function() {
                document.getElementById('template-form-modal').style.display = 'none';
            });
        }
        
        // بستن مودال با کلیک روی پس‌زمینه
        const templateFormModal = document.getElementById('template-form-modal');
        if (templateFormModal) {
            templateFormModal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.style.display = 'none';
                }
            });
        }
        
        // نمایش اسپینر برای فرم قالب
        const templateForm = document.getElementById('template-form');
        const loadingSpinnerTemplate = document.getElementById('loading-spinner-template');
        
        if (templateForm) {
            templateForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // ساخت پرامپت بر اساس فیلدهای فرم
                const formData = new FormData(this);
                const prompt = buildPromptFromFormData(formData, document.getElementById('template-form-title').textContent);
                
                // تنظیم پرامپت در فیلد مخفی
                document.getElementById('template-prompt').value = prompt;
                
                // نمایش اسپینر
                loadingSpinnerTemplate.style.display = 'inline-block';
                
                // غیرفعال کردن دکمه برای جلوگیری از ارسال مجدد
                const submitButton = this.querySelector('.submit-button');
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.style.opacity = '0.6';
                    submitButton.style.cursor = 'not-allowed';
                }
                
                // نمایش پیام در حال بارگذاری
                const loadingMessage = document.createElement('div');
                loadingMessage.id = 'loading-message-template';
                loadingMessage.className = 'loading-message';
                loadingMessage.innerHTML = '<div class="loading-icon">🔄</div><div class="loading-text">در حال تولید محتوا...</div><div class="loading-subtext">لطفاً صبر کنید، این فرآیند ممکن است چند دقیقه طول بکشد.</div>';
                
                // اضافه کردن پیام به مودال
                const modalContent = document.querySelector('.template-form-content');
                if (modalContent) {
                    // حذف پیام‌های قبلی
                    const existingMessage = modalContent.querySelector('#loading-message-template');
                    if (existingMessage) {
                        existingMessage.remove();
                    }
                    
                    // اضافه کردن پیام جدید
                    modalContent.appendChild(loadingMessage);
                    
                    // اسکرول به پیام لودینگ
                    setTimeout(() => {
                        loadingMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }, 100);
                }
                
                // ارسال فرم
                this.submit();
            });
        }
        // تابع ساخت پرامپت از فیلدهای فرم
        function buildPromptFromFormData(formData, templateTitle) {
            const mainTopic = formData.get('main_topic') || '';
            const focusKeyword = formData.get('focus_keyword') || '';
            const targetAudience = formData.get('target_audience') || '';
            const contentType = formData.get('content_type') || '';
            const contentLength = formData.get('content_length') || '1200';
            const howToTopic = formData.get('how_to_topic') || '';
            const skillLevel = formData.get('skill_level') || '';
            const requiredTools = formData.get('required_tools') || '';
            const listTopic = formData.get('list_topic') || '';
            const listCount = formData.get('list_count') || '10';
            const listCriteria = formData.get('list_criteria') || '';
            const item1 = formData.get('item1') || '';
            const item2 = formData.get('item2') || '';
            const comparisonCriteria = formData.get('comparison_criteria') || '';
            const competitorAnalysis = formData.get('competitor_analysis') || '';
            const uniqueValue = formData.get('unique_value') || '';
            const contentGoal = formData.get('content_goal') || '';

            // فیلدهای قالب گردشگری
            const destination = formData.get('destination') || '';
            const travelSeason = formData.get('travel_season') || '';
            const travelType = formData.get('travel_type') || '';
            const travelDuration = formData.get('travel_duration') || '';
            const budgetLevel = formData.get('budget_level') || '';
            const travelMethod = formData.get('travel_method') || '';
            const accommodationType = formData.get('accommodation_type') || '';
            const hotelRating = formData.get('hotel_rating') || '';
            const bookingServices = formData.get('booking_services') || '';
            const articleTone = formData.get('article_tone') || '';
            const callToAction = formData.get('call_to_action') || '';
            const specialRequirements = formData.get('special_requirements') || '';

            // فیلدهای قالب خوراکی
            const foodTopic = formData.get('food_topic') || '';
            const cuisineType = formData.get('cuisine_type') || '';
            const difficultyLevel = formData.get('difficulty_level') || '';
            const preparationTime = formData.get('preparation_time') || '';
            const specialDiet = formData.get('special_diet') || '';

            // فیلدهای صنعت آب و انرژی
            const energyTopic = formData.get('energy_topic') || '';
            const energySector = formData.get('energy_sector') || '';
            const energyType = formData.get('energy_type') || '';
            const industrySize = formData.get('industry_size') || '';
            const regulatoryCompliance = formData.get('regulatory_compliance') || '';

            // فیلدهای نفت و گاز
            const oilTopic = formData.get('oil_topic') || '';
            const oilSector = formData.get('oil_sector') || '';
            const hydrocarbonType = formData.get('hydrocarbon_type') || '';
            const facilityType = formData.get('facility_type') || '';
            const safetyStandards = formData.get('safety_standards') || '';

            // فیلدهای آتشنشانی
            const fireTopic = formData.get('fire_topic') || '';
            const fireSector = formData.get('fire_sector') || '';
            const fireType = formData.get('fire_type') || '';
            const equipmentType = formData.get('equipment_type') || '';
            const safetyProtocols = formData.get('safety_protocols') || '';
            
            // اگر عنوان قالب مشخص است، بر اساس آن پرامپت کوتاه و استاندارد تولید می‌کنیم
            switch (templateTitle) {
                case 'مقاله تخصصی گردشگری و سفر':
                    return `شما یک متخصص گردشگری حرفه‌ای هستید. یک راهنمای سفر کامل و کاربردی برای سفر به ${destination} بنویسید.

اطلاعات سفر:
- مقصد: ${destination}
- فصل: ${travelSeason}
- نوع سفر: ${travelType}
- مدت سفر: ${travelDuration}
- سطح بودجه: ${budgetLevel}
- روش سفر: ${travelMethod}
- نوع اقامت: ${accommodationType}${accommodationType === 'هتل' && hotelRating !== 'مهم نیست' ? ' (' + hotelRating + ')' : ''}
- خدمات رزرو: ${bookingServices}
- لحن مقاله: ${articleTone}
- فراخوان عمل: ${callToAction}
- نیازهای خاص: ${specialRequirements}

ساختار مقاله:
- عنوان جذاب شامل نام مقصد
- معرفی مقصد و دلایل جذابیت
- بهترین زمان سفر و برنامه ریزی
- نحوه رسیدن (${travelMethod} - حمل و نقل، ویزا)
- اقامت (${accommodationType})${accommodationType === 'هتل' ? ' و هتل‌ها' : ''}
- جاذبه های گردشگری (حداقل 10 جاذبه)
- غذا و رستوران های محلی${specialRequirements !== 'بدون نیاز خاص' ? ' (با توجه به ' + specialRequirements + ')' : ''}
- خرید و سوغاتی
- نکات مهم و توصیه ها
- برنامه سفر پیشنهادی (${travelDuration})${callToAction !== 'بدون CTA' ? '\n- فراخوان عمل: ' + callToAction : ''}

ویژگی های مهم:
- حداقل 1200 کلمه و حداکثر 2500 کلمه
- لحن ${articleTone} و کاربردی
- اطلاعات دقیق و به روز
- مناسب برای گردشگران ایرانی
- استفاده از هدینگ های H2 و H3
- بدون استفاده از کلمه 'مقدمه' در ابتدا
- بدون نتیجه گیری در انتها
- استفاده از تگ strong برای کلمات کلیدی
- استفاده از لیست‌های بولت برای نکات مهم
- گنجاندن نام برند "${bookingServices}" به صورت طبیعی در متن

فرمت خروجی:
فقط HTML استاندارد تولید کن. از تگ‌های زیر استفاده کن:
- <h2> برای عناوین اصلی
- <h3> برای زیرعناوین
- <p> برای پاراگراف‌ها
- <strong> برای کلمات مهم و کلیدی
- <ul><li> برای لیست‌های بولت
- <ol><li> برای لیست‌های شماره‌دار

هیچ Markdown، کد یا تگ‌های اضافی استفاده نکن.`;

                case 'مقاله تخصصی خوراکی و آشپزی':
                    return `فقط HTML تمیز. h2/h3، strong و لیست‌ها. موضوع: ${foodTopic} (${cuisineType})، سختی: ${difficultyLevel}، زمان آماده‌سازی: ${preparationTime}، رژیم: ${specialDiet}.

ساختار:
<h2>معرفی و ارزش غذایی</h2>
<h2>مواد لازم</h2>
<h2>تجهیزات</h2>
<h2>مراحل تهیه (بولت شماره‌دار)</h2>
<h2>نکات کلیدی و سرو</h2>`;

                case 'مقاله جامع و بنیادی (Pillar Page)':
                    return `خروجی HTML تمیز با h2/h3 و strong. موضوع: ${mainTopic} | مخاطب: ${targetAudience} | هدف: ${contentGoal}.
بخش‌ها: مقدمه کوتاه، مروری بر مفاهیم، فصل‌های اصلی (5+ h2)، سوالات متداول، جمع‌بندی. پاراگراف‌ها کوتاه.`;

                case 'مقاله به روش آسمان‌خراش (Skyscraper)':
                    return `HTML تمیز. موضوع: ${mainTopic}. ارزش افزوده: ${uniqueValue}. ${competitorAnalysis ? 'نکات رقبا: ' + competitorAnalysis : ''}
ساختار: مقدمه، h2 های اصلی با زیربخش‌های h3، جدول یا لیست مزایا/معایب، FAQ، جمع‌بندی.`;

                case 'راهنمای عملی قدم به قدم (How-to)':
                    return `HTML تمیز. راهنمای ${howToTopic} برای سطح ${skillLevel}. ابزار: ${requiredTools}.
ساختار: مقدمه، پیش‌نیازها، مراحل شماره‌دار (حداقل 5 مرحله)، عیب‌یابی، نکات حرفه‌ای، جمع‌بندی.`;

                case 'مقاله لیستی (مثلا: ۱۰ ابزار برتر)':
                    return `HTML تمیز. عنوان لیست: ${listTopic} | تعداد: ${listCount} | معیار: ${listCriteria}.
ساختار: مقدمه، برای هر آیتم یک h2 با معرفی کوتاه + بولت ویژگی‌ها، در پایان جمع‌بندی و یک جدول مقایسه ساده.`;

                case 'مقاله مقایسه‌ای (X در مقابل Y)':
                    return `HTML تمیز. مقایسه ${item1} در برابر ${item2}. معیارها: ${comparisonCriteria}.
بخش‌ها: مرور سریع (جدول خلاصه)، h2 برای هر معیار با تحلیل و برنده، چه زمانی ${item1}، چه زمانی ${item2}، جمع‌بندی.`;

                case 'مقاله کاملاً استاندارد برای Rank Math (امتیاز 90+)':
                    if (mainTopic && focusKeyword) {
                        return `HTML تمیز. موضوع: ${mainTopic} | کلمه کلیدی: ${focusKeyword} | طول تقریبی: ${contentLength} کلمه.
ساختار: مقدمه کوتاه (حاوی کلمه کلیدی)، 5 بخش h2 با h3 های لازم، FAQ (3 سوال)، جمع‌بندی. از strong و لیست‌ها برای خوانایی استفاده کن.`;
                    }
                    break;

                case 'مقاله تخصصی فناوری اطلاعات و برنامه‌نویسی':
                    return `HTML تمیز. موضوع: ${mainTopic || ''}${mainTopic ? ' - ' : ''}${formData.get('tech_topic') || ''}. دسته: ${formData.get('tech_category') || ''}، سطح: ${skillLevel}.
ساختار: مقدمه، پیش‌نیازها/نصب، مفاهیم پایه، پیاده‌سازی عملی (با توضیح بدون کد بلاک)، بهترین شیوه‌ها، FAQ، جمع‌بندی.`;

                case 'مقاله تخصصی صنعت آب، انرژی و آب و برق':
                    return `HTML تمیز. موضوع: ${energyTopic} | بخش: ${energySector} | نوع انرژی: ${energyType} | اندازه صنعت: ${industrySize} | مقررات: ${regulatoryCompliance}.
بخش‌ها: معرفی و اهمیت، فناوری‌ها، پیاده‌سازی، استانداردها، چالش‌ها/راه‌حل‌ها، جمع‌بندی.`;

                case 'مقاله تخصصی صنعت نفت، گاز و پتروشیمی':
                    return `HTML تمیز. موضوع: ${oilTopic} | بخش: ${oilSector} | هیدروکربن: ${hydrocarbonType} | تأسیسات: ${facilityType} | ایمنی: ${safetyStandards}.
بخش‌ها: وضعیت صنعت، فناوری‌های نوین، استانداردهای ایمنی، پیاده‌سازی، چالش‌ها، جمع‌بندی.`;

                case 'مقاله تخصصی صنعت آتشنشانی':
                    return `HTML تمیز. موضوع: ${fireTopic} | بخش: ${fireSector} | نوع آتش: ${fireType} | تجهیزات: ${equipmentType} | پروتکل‌ها: ${safetyProtocols}.
بخش‌ها: معرفی و اهمیت، تجهیزات/روش‌ها، استانداردها، سناریوهای عملیاتی، نکات ایمنی، جمع‌بندی.`;
            }

            // تشخیص نوع قالب بر اساس فیلدهای موجود (پیش‌فرض‌های قبلی)
            if (mainTopic && focusKeyword) {
                // قالب Rank Math استاندارد
                return `**نقش شما:** شما یک متخصص ارشد SEO و تولیدکننده محتوای حرفه‌ای هستید که در بهینه‌سازی محتوا برای Rank Math و گوگل تخصص دارید. وظیفه شما ایجاد یک مقاله کاملاً استاندارد برای موضوع "${mainTopic}" است که امتیاز بالای ۹۰ در Rank Math کسب کند.

**هدف اصلی:** تولید مقاله‌ای که تمام استانداردهای SEO را رعایت کند و در گوگل عملکرد عالی داشته باشد.

**مراحل اجرای کار (بسیار مهم):**

**مرحله ۱: تحلیل کلمه کلیدی و تحقیق**
1. **کلمه کلیدی اصلی:** "${focusKeyword}" - این کلمه کلیدی باید:
   - در عنوان (Title) قرار گیرد
   - در آدرس URL استفاده شود
   - در پاراگراف اول متن وجود داشته باشد
   - در توضیحات متا (Meta Description) بیاید
   - در حداقل یکی از تیترهای H2 یا H3 استفاده شود
   - حداقل ۵ بار در متن مقاله تکرار شود (تراکم مناسب ۱٪ - ۲٪)

2. **کلمات کلیدی فرعی (LSI):** حداقل ۱۰ کلمه کلیدی مرتبط معنایی پیدا کن
3. **کلمات کلیدی طولانی (Long-tail):** حداقل ۵ سوال رایج کاربران را شناسایی کن
**مرحله ۲: طراحی ساختار مقاله کاملاً استاندارد**
* **H1 (عنوان اصلی):** حداکثر ۶۰ کاراکتر، دارای کلمه کلیدی اصلی، جذاب همراه با عدد، سال یا سوال
* **مقدمه قدرتمند:** شامل کلمه کلیدی، معرفی موضوع + ایجاد کنجکاوی، ترجیحاً با Bold و فونت متفاوت
* **بدنه اصلی:** حداقل ۵ بخش H2، هر بخش شامل ۲-۳ زیربخش H3
* **نتیجه‌گیری:** جمع‌بندی نکات کلیدی + دعوت به اقدام
* **بخش FAQ:** حداقل ۳ سوال متداول با پاسخ‌های دقیق

**مرحله ۳: بهینه‌سازی کامل محتوا**

**۳-۱: بهینه‌سازی عنوان و متا**
- **عنوان (Meta Title):** حداکثر ۶۰ کاراکتر، شامل کلمه کلیدی، جذاب با عدد/سال/سوال
- **توضیحات متا (Meta Description):** حداکثر ۱۶۰ کاراکتر، شامل کلمه کلیدی، خلاصه جذاب و قابل کلیک
- **آدرس URL:** کوتاه و شامل کلمه کلیدی (مثال: yoursite.com/web-programming-guide)

**۳-۲: بهینه‌سازی محتوای اصلی**
- **پاراگراف اول:** شامل کلمه کلیدی، معرفی موضوع + ایجاد کنجکاوی، با Bold برجسته شود
- **تراکم کلمه کلیدی:** حدود ۱٪ تا ۲٪ (نه کمتر، نه بیشتر)
- **خوانایی:** جملات کوتاه (زیر ۲۰ کلمه)، پاراگراف‌های کمتر از ۵ خط، استفاده از لیست‌های Bullet و Number
- **طول مقاله:** حداقل ${contentLength} کلمه

**۳-۳: لینک‌دهی و تصاویر**
- **لینک‌دهی داخلی:** حداقل ۲ لینک به صفحات مرتبط دیگر سایت
- **لینک‌دهی خارجی:** حداقل یک لینک به منبع معتبر (با rel="noopener" و target="_blank")
- **تصاویر:** حداقل یک تصویر، متن ALT شامل کلمه کلیدی اصلی

**۳-۴: Schema و بهینه‌سازی پیشرفته**
- **Schema نوع:** "مقاله / Article" یا "راهنما / HowTo"
- **جدول محتوا:** با لینک‌دهی داخلی به هدینگ‌ها
- **واکنش‌گرا بودن:** ساختار مناسب برای موبایل، تبلت و دسکتاپ

**مرحله ۴: تولید محتوای نهایی**

**۴-۱: نگارش محتوا با رعایت تمام استانداردها**
- لحن معتبر، حرفه‌ای اما روان و قابل فهم
- استفاده از سبک نگارش "هرم معکوس" (مهم‌ترین اطلاعات در ابتدا)
- پاراگراف‌های کوتاه (حداکثر ۳-۴ جمله)
- استفاده از لیست‌های شماره‌دار و بولت‌پوینت
- نقل قول و متن برجسته برای شکستن یکنواختی

**۴-۲: غنی‌سازی محتوا**
- آمار و ارقام (با ذکر منبع فرضی)
- مثال‌های عملی و مطالعات موردی
- پیشنهادات برای افزودن اینفوگرافیک یا ویدیو
- استفاده از کلمات کلیدی فرعی و طولانی به صورت طبیعی

**۴-۳: بهینه‌سازی نهایی**
- بررسی تراکم کلمه کلیدی (۱٪ - ۲٪)
- اطمینان از وجود کلمه کلیدی در تمام بخش‌های مهم
- بررسی خوانایی و ساختار متن
- اطمینان از وجود تمام عناصر SEO

**خروجی نهایی:**
یک مقاله کاملاً استاندارد با حداقل **${contentLength} کلمه** که تمام استانداردهای SEO را رعایت کند و آماده انتشار مستقیم در وردپرس باشد. مقاله باید شامل تمام عناصر ذکر شده در چک‌لیست باشد و امتیاز بالای ۹۰ در Rank Math کسب کند.

**نکات مهم:**
- از تکرار بیش از حد کلمات کلیدی (Keyword Stuffing) به شدت پرهیز کن
- محتوا باید طبیعی و خوانا باشد
- تمام عناصر SEO باید به صورت یکپارچه در متن گنجانده شوند
- مقاله باید ارزش واقعی برای کاربر ایجاد کند`;
                
            } else if (mainTopic && targetAudience) {
                // قالب مقاله جامع و بنیادی
                return `**نقش شما:** شما یک استراتژیست ارشد محتوا و متخصص SEO با بیش از ۱۰ سال تجربه هستید. وظیفه شما ایجاد یک مقاله بنیادی (Pillar Page) بسیار جامع، معتبر و کاملاً بهینه‌سازی شده برای موضوع "${mainTopic}" است. این مقاله باید به عنوان منبع اصلی و نهایی برای این موضوع در وب فارسی عمل کند.

**مراحل اجرای کار (بسیار مهم):**

**مرحله ۱: تحلیل عمیق و تحقیق کلمات کلیدی**
1. **شناسایی هدف جستجو (Search Intent):** ابتدا مشخص کن که کاربرانی که در مورد "${mainTopic}" جستجو می‌کنند، به دنبال چه نوع اطلاعاتی هستند؟ (مثلاً اطلاعاتی، آموزشی، راهنمای خرید).
2. **تحقیق کلمات کلیدی:** لیستی از کلمات کلیدی مرتبط تهیه کن:
   * **کلمه کلیدی اصلی:** ${mainTopic}
   * **کلمات کلیدی فرعی (LSI):** حداقل ۱۰ کلمه کلیدی مرتبط معنایی پیدا کن.
   * **کلمات کلیدی طولانی (Long-tail):** حداقل ۵ سوال رایج که کاربران در این مورد می‌پرسند را شناسایی کن (مثلاً "چگونه X را انجام دهیم؟"، "بهترین Y برای Z چیست؟").
3. **تحلیل رقبا:** به صورت فرضی، ۳ مقاله برتر در نتایج جستجوی گوگل برای این موضوع را تحلیل کن. نقاط قوت و ضعف آن‌ها چیست؟ چه شکاف‌های محتوایی وجود دارد که ما می‌توانیم آن‌ها را پر کنیم؟

**مرحله ۲: طراحی ساختار مقاله (Blueprint)**
بر اساس تحقیقات مرحله ۱، یک ساختار درختی و دقیق برای مقاله طراحی کن. این ساختار باید شامل موارد زیر باشد:
* **H1 (عنوان اصلی):** یک عنوان جذاب، منحصر به فرد و سئو شده (حاوی کلمه کلیدی اصلی).
* **مقدمه:** یک مقدمه قدرتمند که با یک "قلاب" (Hook) شروع می‌شود، مشکل کاربر را بیان می‌کند و قول یک راه‌حل جامع را می‌دهد.
* **H2 (فصل‌های اصلی):** مقاله را به بخش‌های منطقی (حداقل ۵ فصل) تقسیم کن. هر H2 باید یکی از جنبه‌های اصلی موضوع را پوشش دهد.
* **H3 (زیرفصل‌ها):** هر H2 را به H3 های مرتبط‌تر تقسیم کن تا خوانایی افزایش یابد.
* **نتیجه‌گیری:** یک جمع‌ بندی کامل که نکات کلیدی را مرور کرده و یک دیدگاه نهایی ارائه می‌دهد.
* **بخش پرسش‌های متداول (FAQ):** بر اساس کلمات کلیدی طولانی (سوالات) که پیدا کردی، یک بخش FAQ با پاسخ‌های دقیق و کوتاه ایجاد کن.

**مرحله ۳: نگارش و تولید محتوا**
با استفاده از ساختار بالا، شروع به نوشتن مقاله کن. نکات زیر را **با دقت** رعایت کن:
* **لحن و سبک:** لحنی معتبر، حرفه‌ای اما روان و قابل فهم. از سبک نگارش "هرم معکوس" استفاده کن (مهم‌ترین اطلاعات در ابتدا).
* **خوانایی:** پاراگراف‌ها را کوتاه (حداکثر ۳-۴ جمله) نگه دار. از لیست‌های شماره‌دار و بولت‌پوینت، **نقل قول** و **متن برجسته** برای شکستن یکنواختی متن استفاده کن.
* **چگالی کلمه کلیدی:** کلمه کلیدی اصلی را در مقدمه، نتیجه‌گیری و یکی دو تا از H2 ها به کار ببر. از کلمات کلیدی فرعی و طولانی به صورت طبیعی در سراسر متن استفاده کن. **از تکرار بیش از حد (Keyword Stuffing) به شدت پرهیز کن.**
* **غنی‌سازی محتوا:** برای اعتبار بخشیدن به متن، به **آمار و ارقام** (با ذکر منبع فرضی)، **مثال‌های عملی** و **مطالعات موردی** اشاره کن. پیشنهاداتی برای افزودن **اینفوگرافیک** یا **ویدیو** در بخش‌های مرتبط ارائه بده.
* **لینک‌دهی داخلی:** حداقل ۳ پیشنهاد برای لینک داخلی به مقالات مرتبط دیگر (با موضوعات فرضی) در متن بگنجان.

**مرحله ۴: بهینه‌سازی نهایی و CTA**
1. **بهینه‌سازی عنوان و توضیحات متا:** یک عنوان سئو (کمتر از ۶۰ کاراکتر) و یک توضیحات متا (کمتر از ۱۶۰ کاراکتر) جذاب و حاوی کلمه کلیدی اصلی پیشنهاد بده.
2. **دعوت به اقدام (Call to Action):** در انتهای مقاله، یک CTA مرتبط و هوشمندانه قرار بده (مثلاً دعوت به دانلود یک کتاب الکترونیکی، ثبت‌نام در وبینار یا مطالعه یک مقاله مرتبط دیگر).

**خروجی نهایی:**
مقاله باید حداقل **۲۵۰۰ کلمه** باشد، کاملاً ساختاریافته، فاقد هرگونه اطلاعات نادرست و آماده انتشار مستقیم در وردپرس باشد. لطفاً از نوشتن عباراتی مانند "عنوان مقاله:" یا "مقدمه:" خودداری کن و مستقیماً محتوا را تولید نما.`;
                
            } else if (howToTopic) {
                // قالب راهنمای عملی
                return `**نقش شما:** شما یک مربی و نویسنده فنی هستید که در نوشتن راهنماهای عملی، واضح و قدم به قدم تخصص دارید. وظیفه شما ایجاد یک راهنمای کامل برای "${howToTopic}" است.

**هدف اصلی:** کاربر باید بتواند **فقط با خواندن این راهنما**، کار مورد نظر را با موفقیت و بدون هیچ مشکلی انجام دهد.

**مراحل اجرای کار:**

**مرحله ۱: برنامه‌ریزی راهنما**
1. **شناسایی مخاطب:** این راهنما برای چه کسی است؟ (${skillLevel}). سطح دانش فنی او را در نظر بگیر.
2. **لیست ابزار و پیش‌نیازها:** یک بخش در ابتدای مقاله با عنوان "آنچه قبل از شروع نیاز دارید" ایجاد کن و تمام ابزارها، مواد اولیه یا نرم‌افزارهای مورد نیاز را لیست کن.
3. **تقسیم فرآیند به مراحل:** کل فرآیند را به مراحل اصلی و قابل مدیریت (حداقل ۵ مرحله) تقسیم کن. هر مرحله باید یک اقدام مشخص باشد.

**مرحله ۲: طراحی ساختار راهنما**
* **H1 (عنوان):** عنوانی واضح و عملی (مثلاً: "راهنمای قدم به قدم ${howToTopic} برای ${skillLevel}").
* **مقدمه:** به طور خلاصه توضیح بده که در این راهنما چه چیزی آموزش داده می‌شود و نتیجه نهایی چه خواهد بود.
* **بخش پیش‌نیازها.**
* **بدنه اصلی (مراحل):**
  * هر مرحله باید یک تیتر H2 داشته باشد (مثلاً: "مرحله ۱: آماده‌سازی مواد اولیه").
  * برای هر مرحله، دستورالعمل‌ها را به صورت یک لیست شماره‌دار و واضح بنویس.
  * **نکته کلیدی:** بعد از هر چند مرحله، یک بخش "نکات حرفه‌ای" یا "اشتباهات رایج" اضافه کن.
* **بخش عیب‌یابی (Troubleshooting):** یک بخش H2 با عنوان "مشکلات احتمالی و راه‌حل‌ها" ایجاد کن و به ۳-۴ مشکل رایجی که ممکن است کاربر با آن مواجه شود، پاسخ بده.
* **نتیجه‌گیری:** نتیجه کار را جشن بگیر و کاربر را برای موفقیتش تشویق کن.
* **بخش FAQ.**

**مرحله ۳: نگارش محتوا**
* **زبان ساده و امری:** از جملات کوتاه، واضح و دستوری استفاده کن (مثلاً: "فر را روی ۱۸۰ درجه تنظیم کنید.").
* **غنی‌سازی بصری:** در هر مرحله، توضیح بده که چه نوع تصویر، گیف یا ویدیویی می‌تواند به درک بهتر کمک کند (مثلاً: "[تصویر: نمایی نزدیک از هم زدن تخم مرغ‌ها]").
* **تمرکز بر جزئیات:** هیچ مرحله‌ای را ناگفته نگذار. فرض کن کاربر هیچ دانشی در این زمینه ندارد.
* **کلمات کلیدی:** از کلمات کلیدی مرتبط با موضوع به صورت طبیعی استفاده کن.

**خروجی نهایی:**
یک راهنمای بسیار کاربردی با حداقل **۱۵۰۰ کلمه** که به صورت مستقیم در وردپرس قابل استفاده باشد. لحن باید دوستانه، حمایتی و تشویق‌کننده باشد. از نوشتن "عنوان:" و غیره خودداری کن.`;
                
            } else if (listTopic) {
                // قالب مقاله لیستی
                return `**نقش شما:** شما یک وبلاگ‌نویس حرفه‌ای هستید که در نوشتن مقالات لیستی (Listicles) جذاب، ویروسی و مفید تخصص دارید. وظیفه شما نوشتن یک مقاله لیستی با عنوان "${listTopic}" است.

**هدف اصلی:** ایجاد یک مقاله مرجع که کاربران آن را ذخیره کرده و به دیگران به اشتراک بگذارند.

**مراحل اجرای کار:**

**مرحله ۱: تحقیق و انتخاب آیتم‌های لیست**
1. **انتخاب آیتم‌ها:** لیستی از بهترین و مرتبط‌ترین گزینه‌ها را برای لیست خود تهیه کن. فقط موارد با کیفیت را انتخاب کن.
2. **معیارهای رتبه‌بندی:** مشخص کن بر چه اساسی این آیتم‌ها را رتبه‌بندی می‌کنی (${listCriteria}).

**مرحله ۲: طراحی ساختار مقاله لیستی**
* **H1 (عنوان):** یک عنوان جذاب و کلیک‌خور که شامل عدد باشد (مثلاً: "${listCount} ${listTopic}").
* **مقدمه:** با یک آمار جالب یا یک داستان کوتاه، اهمیت موضوع را نشان بده و بگو که این لیست چگونه به کاربر کمک خواهد کرد.
* **بدنه اصلی (آیتم‌های لیست):**
  * هر آیتم باید یک تیتر H2 داشته باشد (مثلاً: "۱. ابزار [نام ابزار]").
  * برای هر آیتم، بخش‌های زیر را پوشش بده:
    * **معرفی کوتاه:** این ابزار چیست و چه کار می‌کند؟
    * **ویژگی‌های کلیدی:** به صورت بولت‌پوینت، ۳-۴ ویژگی برتر آن را لیست کن.
    * **بهترین کاربرد:** این ابزار برای چه کسانی یا چه کارهایی مناسب‌تر است؟
    * **قیمت‌گذاری:** به طور خلاصه مدل قیمت‌گذاری آن را توضیح بده.
* **بخش ویژه:** یک یا دو آیتم "افتخاری" یا "کمتر شناخته شده" به انتهای لیست اضافه کن تا مقاله شما منحصر به فرد شود.
* **جدول مقایسه:** یک جدول مقایسه‌ای ساده در انتهای مقاله ایجاد کن که آیتم‌های اصلی را بر اساس معیارهای کلیدی مقایسه کند.
* **نتیجه‌گیری:** یک جمع‌بندی کوتاه ارائه بده و شاید انتخاب شخصی خودت را به عنوان "برترین گزینه کلی" معرفی کن.
**مرحله ۳: نگارش محتوا**
* **لحن جذاب و پرانرژی:** از لحنی استفاده کن که خواننده را تا انتهای لیست نگه دارد.
* **توصیفات قانع‌کننده:** برای هر آیتم، به وضوح توضیح بده که چه ارزشی برای کاربر ایجاد می‌کند.
* **کلمات کلیدی:** از کلماتی مانند "بهترین"، "برترین"، "نقد و بررسی" در سراسر متن استفاده کن.

**خروجی نهایی:**
یک مقاله لیستی جذاب با حداقل **۱۸۰۰ کلمه** که آماده انتشار در وردپرس باشد. از نوشتن "عنوان:" و غیره خودداری کن.`;
                
            } else if (item1 && item2) {
                // قالب مقاله مقایسه‌ای
                return `**نقش شما:** شما یک تحلیل‌گر و منتقد بی‌طرف هستید. وظیفه شما نوشتن یک مقاله مقایسه‌ای عمیق و منصفانه بین "${item1}" و "${item2}" است.

**هدف اصلی:** کمک به کاربر برای گرفتن یک تصمیم آگاهانه بر اساس نیازها و شرایط خاص خودش.

**مراحل اجرای کار:**

**مرحله ۱: تعیین معیارهای مقایسه**
لیستی از مهم‌ترین معیارهایی که برای مقایسه این دو آیتم باید در نظر گرفته شود، تهیه کن (حداقل ۷ معیار). ${comparisonCriteria}

**مرحله ۲: طراحی ساختار مقاله**
* **H1 (عنوان):** عنوانی که به وضوح مقایسه را نشان دهد (مثلاً: "${item1} در مقابل ${item2}: کدام برای شما بهتر است؟").
* **مقدمه:** هر دو آیتم را به طور خلاصه معرفی کن و بگو که در انتهای این مقاله، کاربر قادر به انتخاب بهترین گزینه خواهد بود.
* **جدول مقایسه سریع:** یک جدول در ابتدای مقاله قرار بده که به صورت خلاصه، دو آیتم را بر اساس معیارهای اصلی مقایسه کند و برنده هر بخش را مشخص کند.
* **مقایسه تفصیلی (Head-to-Head):**
  * این بخش اصلی مقاله است. برای هر معیاری که در مرحله ۱ مشخص کردی، یک تیتر H2 ایجاد کن (مثلاً: "مقایسه ویژگی‌ها").
  * در زیر هر H2، توضیح بده که هر کدام از آیتم‌ها در آن معیار چگونه عمل می‌کنند و در نهایت یک "برنده" برای آن معیار خاص اعلام کن و دلیلش را توضیح بده.
* **بخش "چه زمانی ${item1} را انتخاب کنیم؟":** به طور مشخص توضیح بده که ${item1} برای چه نوع کاربرانی یا چه پروژه‌هایی مناسب‌تر است.
* **بخش "چه زمانی ${item2} را انتخاب کنیم؟":** به طور مشخص توضیح بده که ${item2} برای چه نوع کاربرانی یا چه پروژه‌هایی مناسب‌تر است.
* **نتیجه‌گیری نهایی:** یک جمع‌بندی کامل ارائه بده و یک توصیه نهایی بر اساس سناریوهای مختلف ارائه کن. **از اعلام یک برنده قطعی برای همه پرهیز کن.**

**مرحله ۳: نگارش محتوا**
* **بی‌طرفی:** سعی کن در سراسر متن منصف و بی‌طرف باشی. هر دو جنبه مثبت و منفی را ذکر کن.
* **استفاده از داده:** در صورت امکان، به داده‌ها، آمار یا تست‌های عملکردی (به صورت فرضی) برای پشتیبانی از ادعاهای خود اشاره کن.
* **کلمات کلیدی:** از عباراتی مانند "${item1} vs ${item2}"، "مقایسه ${item1} و ${item2}"، "تفاوت‌های ${item1} و ${item2}" و "کدام بهتر است" استفاده کن.

**خروجی نهایی:**
یک مقاله مقایسه‌ای دقیق و جامع با حداقل **۲۰۰۰ کلمه** که به کاربر در تصمیم‌گیری کمک شایانی کند. مقاله باید آماده انتشار مستقیم در وردپرس باشد.`;
                
            } else {
                // قالب پیش‌فرض
                return `**نقش شما:** شما یک متخصص تولید محتوا و نویسنده حرفه‌ای هستید. وظیفه شما ایجاد یک مقاله جامع و با کیفیت برای موضوع "${mainTopic}" است.
**هدف اصلی:** تولید مقاله‌ای که ارزش واقعی برای خواننده ایجاد کند و در موتورهای جستجو عملکرد خوبی داشته باشد.

**مراحل اجرای کار:**

**مرحله ۱: تحقیق و تحلیل**
1. **شناسایی مخاطب هدف:** مشخص کن این مقاله برای چه کسانی نوشته می‌شود
2. **تحقیق کلمات کلیدی:** کلمات کلیدی مرتبط با موضوع را شناسایی کن
3. **تحلیل رقبا:** نقاط قوت و ضعف مقالات موجود را بررسی کن

**مرحله ۲: طراحی ساختار**
* **H1 (عنوان اصلی):** عنوانی جذاب و سئو شده
* **مقدمه:** معرفی موضوع و ایجاد کنجکاوی
* **بدنه اصلی:** حداقل ۵ بخش H2 با زیربخش‌های H3
* **نتیجه‌گیری:** جمع‌بندی نکات کلیدی

**مرحله ۳: نگارش محتوا**
* **لحن حرفه‌ای و قابل فهم**
* **پاراگراف‌های کوتاه و خوانا**
* **استفاده از لیست‌ها و مثال‌ها**
* **لینک‌دهی مناسب**

**خروجی نهایی:**
یک مقاله جامع با حداقل **۱۵۰۰ کلمه** که آماده انتشار در وردپرس باشد.`;
            }
        }
        
        // نمایش مودال فرم قالب
        function showTemplateForm(templateIndex, templateTitle, model) {
            const modal = document.getElementById('template-form-modal');
            const titleElement = document.getElementById('template-form-title');
            const modelSelect = document.getElementById('template-model-select');
            const fieldsContainer = document.getElementById('template-fields');
            
            // تنظیم عنوان و مدل
            titleElement.textContent = templateTitle;
            modelSelect.value = model; // تنظیم مدل پیش‌فرض در select
            
            // ایجاد فیلدهای داینامیک بر اساس نوع قالب
            const fields = getTemplateFields(templateIndex, templateTitle);
            fieldsContainer.innerHTML = fields;
            
            // نمایش مودال
            modal.style.display = 'flex';
        }
        
        // تابع ایجاد فیلدهای داینامیک
        function getTemplateFields(templateIndex, templateTitle) {
            let fields = '';
            
            switch(templateTitle) {
                case 'مقاله تخصصی گردشگری و سفر':
                    fields = `
                        <div class="form-group">
                            <label for="destination">مقصد گردشگری:</label>
                            <input type="text" id="destination" name="destination" required placeholder="مثال: استانبول، شیراز، بالی">
                        </div>
                        <div class="form-group">
                            <label for="travel_season">فصل سفر:</label>
                            <select id="travel_season" name="travel_season">
                                <option value="بهار">بهار</option>
                                <option value="تابستان">تابستان</option>
                                <option value="پاییز">پاییز</option>
                                <option value="زمستان">زمستان</option>
                                <option value="تمام فصول">تمام فصول</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="travel_type">نوع سفر:</label>
                            <select id="travel_type" name="travel_type">
                                <option value="تفریحی">تفریحی</option>
                                <option value="تاریخی">تاریخی</option>
                                <option value="طبیعت‌گردی">طبیعت‌گردی</option>
                                <option value="ماجراجویی">ماجراجویی</option>
                                <option value="خانوادگی">خانوادگی</option>
                                <option value="زیارتی">زیارتی</option>
                                <option value="غذا و خوراک">غذا و خوراک</option>
                                <option value="کوهنوردی">کوهنوردی</option>
                                <option value="دریا و ساحل">دریا و ساحل</option>
                                <option value="شهری">شهری</option>
                                <option value="روستایی">روستایی</option>
                                <option value="تجاری">تجاری</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="travel_duration">مدت سفر پیشنهادی:</label>
                            <select id="travel_duration" name="travel_duration">
                                <option value="یک روز">یک روز</option>
                                <option value="آخر هفته (۲-۳ روز)">آخر هفته (۲-۳ روز)</option>
                                <option value="یک هفته">یک هفته</option>
                                <option value="دو هفته">دو هفته</option>
                                <option value="سه هفته">سه هفته</option>
                                <option value="بیش از سه هفته">بیش از سه هفته</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="budget_level">سطح بودجه:</label>
                            <select id="budget_level" name="budget_level">
                                <option value="اقتصادی">اقتصادی</option>
                                <option value="متوسط">متوسط</option>
                                <option value="بالا">بالا</option>
                                <option value="لوکس">لوکس</option>
                                <option value="نامحدود">نامحدود</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="travel_method">روش سفر:</label>
                            <select id="travel_method" name="travel_method">
                                <option value="هواپیما">هواپیما</option>
                                <option value="قطار">قطار</option>
                                <option value="اتوبوس">اتوبوس</option>
                                <option value="خودرو شخصی">خودرو شخصی</option>
                                <option value="موتورسیکلت">موتورسیکلت</option>
                                <option value="دوچرخه">دوچرخه</option>
                                <option value="کشتی">کشتی</option>
                                <option value="ترکیبی">ترکیبی</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="accommodation_type">نوع اقامت:</label>
                            <select id="accommodation_type" name="accommodation_type">
                                <option value="هتل">هتل</option>
                                <option value="مهمان‌سرا">مهمان‌سرا</option>
                                <option value="پانسیون">پانسیون</option>
                                <option value="آپارتمان اجاره‌ای">آپارتمان اجاره‌ای</option>
                                <option value="خانه محلی">خانه محلی</option>
                                <option value="کمپینگ">کمپینگ</option>
                                <option value="استراحتگاه">استراحتگاه</option>
                                <option value="بدون اقامت">بدون اقامت</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="hotel_rating">رتبه هتل (در صورت انتخاب هتل):</label>
                            <select id="hotel_rating" name="hotel_rating">
                                <option value="3 ستاره">3 ستاره</option>
                                <option value="4 ستاره">4 ستاره</option>
                                <option value="5 ستاره">5 ستاره</option>
                                <option value="مهم نیست">مهم نیست</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="booking_services">خدمات رزرو:</label>
                            <select id="booking_services" name="booking_services">
                                <option value="رزرو بلیط">رزرو بلیط</option>
                                <option value="رزرو هتل">رزرو هتل</option>
                                <option value="رزرو کامل">رزرو کامل (بلیط + هتل)</option>
                                <option value="بدون رزرو">بدون رزرو</option>
                                <option value="رزرو محلی">رزرو محلی</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="article_tone">لحن مقاله:</label>
                            <select id="article_tone" name="article_tone">
                                <option value="دوستانه">دوستانه</option>
                                <option value="رسمی">رسمی</option>
                                <option value="سرگرم‌کننده">سرگرم‌کننده</option>
                                <option value="آموزشی">آموزشی</option>
                                <option value="ماجراجویانه">ماجراجویانه</option>
                                <option value="عاشقانه">عاشقانه</option>
                                <option value="خانوادگی">خانوادگی</option>
                                <option value="تجاری">تجاری</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="call_to_action">فراخوان عمل (CTA):</label>
                            <select id="call_to_action" name="call_to_action">
                                <option value="رزرو سفر">رزرو سفر</option>
                                <option value="مشاوره رایگان">مشاوره رایگان</option>
                                <option value="دریافت برنامه سفر">دریافت برنامه سفر</option>
                                <option value="تماس با ما">تماس با ما</option>
                                <option value="خرید بسته گردشگری">خرید بسته گردشگری</option>
                                <option value="عضویت در خبرنامه">عضویت در خبرنامه</option>
                                <option value="دنبال کردن در شبکه‌های اجتماعی">دنبال کردن در شبکه‌های اجتماعی</option>
                                <option value="بدون CTA">بدون CTA</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="special_requirements">نیازهای خاص:</label>
                            <select id="special_requirements" name="special_requirements">
                                <option value="بدون نیاز خاص">بدون نیاز خاص</option>
                                <option value="دسترسی آسان">دسترسی آسان</option>
                                <option value="مناسب برای کودکان">مناسب برای کودکان</option>
                                <option value="مناسب برای سالمندان">مناسب برای سالمندان</option>
                                <option value="گیاهخواری">گیاهخواری</option>
                                <option value="حلال">حلال</option>
                                <option value="بدون الکل">بدون الکل</option>
                                <option value="ورزشی">ورزشی</option>
                                <option value="درمانی">درمانی</option>
                            </select>
                        </div>
                    `;
                    break;
                    
                case 'مقاله تخصصی خوراکی و آشپزی':
                    fields = `
                        <div class="form-group">
                            <label for="food_topic">موضوع خوراکی:</label>
                            <input type="text" id="food_topic" name="food_topic" required placeholder="مثال: دستور پخت کباب کوبیده اصیل ایرانی">
                        </div>
                        <div class="form-group">
                            <label for="cuisine_type">نوع آشپزی:</label>
                            <select id="cuisine_type" name="cuisine_type">
                                <option value="ایرانی">ایرانی</option>
                                <option value="ایتالیایی">ایتالیایی</option>
                                <option value="چینی">چینی</option>
                                <option value="هندی">هندی</option>
                                <option value="عربی">عربی</option>
                                <option value="مدیترانه‌ای">مدیترانه‌ای</option>
                                <option value="مکزیکی">مکزیکی</option>
                                <option value="ژاپنی">ژاپنی</option>
                                <option value="فرانسوی">فرانسوی</option>
                                <option value="سایر">سایر</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="difficulty_level">سطح دشواری:</label>
                            <select id="difficulty_level" name="difficulty_level">
                                <option value="آسان">آسان</option>
                                <option value="متوسط">متوسط</option>
                                <option value="پیشرفته">پیشرفته</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="preparation_time">زمان آماده‌سازی:</label>
                            <select id="preparation_time" name="preparation_time">
                                <option value="کمتر از ۳۰ دقیقه">کمتر از ۳۰ دقیقه</option>
                                <option value="۳۰ تا ۶۰ دقیقه">۳۰ تا ۶۰ دقیقه</option>
                                <option value="۱ تا ۲ ساعت">۱ تا ۲ ساعت</option>
                                <option value="بیش از ۲ ساعت">بیش از ۲ ساعت</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="special_diet">رژیم غذایی خاص:</label>
                            <select id="special_diet" name="special_diet">
                                <option value="معمولی">معمولی</option>
                                <option value="گیاهخواری">گیاهخواری</option>
                                <option value="وگان">وگان</option>
                                <option value="بدون گلوتن">بدون گلوتن</option>
                                <option value="کم کربوهیدرات">کم کربوهیدرات</option>
                                <option value="کتوژنیک">کتوژنیک</option>
                            </select>
                        </div>
                    `;
                    break;
                case 'مقاله جامع و بنیادی (Pillar Page)':
                    fields = `
                        <div class="form-group">
                            <label for="main_topic">موضوع اصلی مقاله:</label>
                            <input type="text" id="main_topic" name="main_topic" required placeholder="مثال: هوش مصنوعی در بازاریابی دیجیتال">
                        </div>
                        <div class="form-group">
                            <label for="target_audience">مخاطب هدف:</label>
                            <input type="text" id="target_audience" name="target_audience" placeholder="مثال: مدیران بازاریابی، کارشناسان دیجیتال">
                        </div>
                        <div class="form-group">
                            <label for="content_goal">هدف محتوا:</label>
                            <select id="content_goal" name="content_goal">
                                <option value="آموزشی">آموزشی</option>
                                <option value="اطلاعاتی">اطلاعاتی</option>
                                <option value="راهنمای خرید">راهنمای خرید</option>
                                <option value="تحلیلی">تحلیلی</option>
                            </select>
                        </div>
                    `;
                    break;
                    
                case 'مقاله به روش آسمان‌خراش (Skyscraper)':
                    fields = `
                        <div class="form-group">
                            <label for="main_topic">موضوع اصلی مقاله:</label>
                            <input type="text" id="main_topic" name="main_topic" required placeholder="مثال: بهترین ابزارهای سئو">
                        </div>
                        <div class="form-group">
                            <label for="competitor_analysis">تحلیل رقبا (اختیاری):</label>
                            <textarea id="competitor_analysis" name="competitor_analysis" placeholder="نقاط ضعف مقالات رقبا که می‌خواهید بهبود دهید..."></textarea>
                        </div>
                        <div class="form-group">
                            <label for="unique_value">ارزش افزوده منحصر به فرد:</label>
                            <input type="text" id="unique_value" name="unique_value" placeholder="مثال: مصاحبه با متخصص، چک‌لیست قابل دانلود">
                        </div>
                    `;
                    break;
                case 'راهنمای عملی قدم به قدم (How-to)':
                    fields = `
                        <div class="form-group">
                            <label for="how_to_topic">موضوع راهنما:</label>
                            <input type="text" id="how_to_topic" name="how_to_topic" required placeholder="مثال: چگونه یک وب‌سایت بسازیم؟">
                        </div>
                        <div class="form-group">
                            <label for="skill_level">سطح مهارت مخاطب:</label>
                            <select id="skill_level" name="skill_level">
                                <option value="مبتدی">مبتدی</option>
                                <option value="متوسط">متوسط</option>
                                <option value="پیشرفته">پیشرفته</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="required_tools">ابزارهای مورد نیاز:</label>
                            <input type="text" id="required_tools" name="required_tools" placeholder="مثال: کامپیوتر، نرم‌افزار X، اینترنت">
                        </div>
                    `;
                    break;
                    
                case 'مقاله لیستی (مثلا: ۱۰ ابزار برتر)':
                    fields = `
                        <div class="form-group">
                            <label for="list_topic">موضوع لیست:</label>
                            <input type="text" id="list_topic" name="list_topic" required placeholder="مثال: ۱۰ ابزار برتر هوش مصنوعی">
                        </div>
                        <div class="form-group">
                            <label for="list_count">تعداد آیتم‌ها:</label>
                            <input type="number" id="list_count" name="list_count" min="5" max="50" value="10">
                        </div>
                        <div class="form-group">
                            <label for="list_criteria">معیار رتبه‌بندی:</label>
                            <input type="text" id="list_criteria" name="list_criteria" placeholder="مثال: قیمت، ویژگی‌ها، سهولت استفاده">
                        </div>
                    `;
                    break;
                    
                case 'مقاله مقایسه‌ای (X در مقابل Y)':
                    fields = `
                        <div class="form-group">
                            <label for="item1">آیتم اول:</label>
                            <input type="text" id="item1" name="item1" required placeholder="مثال: Yoast SEO">
                        </div>
                        <div class="form-group">
                            <label for="item2">آیتم دوم:</label>
                            <input type="text" id="item2" name="item2" required placeholder="مثال: Rank Math">
                        </div>
                        <div class="form-group">
                            <label for="comparison_criteria">معیارهای مقایسه:</label>
                            <textarea id="comparison_criteria" name="comparison_criteria" placeholder="مثال: ویژگی‌ها، قیمت، رابط کاربری، پشتیبانی"></textarea>
                        </div>
                    `;
                    break;
                    
                case 'مقاله کاملاً استاندارد برای Rank Math (امتیاز 90+)':
                    fields = `
                        <div class="form-group">
                            <label for="main_topic">موضوع اصلی مقاله:</label>
                            <input type="text" id="main_topic" name="main_topic" required placeholder="مثال: آموزش سئو">
                        </div>
                        <div class="form-group">
                            <label for="focus_keyword">کلمه کلیدی اصلی:</label>
                            <input type="text" id="focus_keyword" name="focus_keyword" required placeholder="مثال: آموزش سئو">
                        </div>
                        <div class="form-group">
                            <label for="target_audience">مخاطب هدف:</label>
                            <input type="text" id="target_audience" name="target_audience" placeholder="مثال: مدیران وب‌سایت، کارشناسان دیجیتال">
                        </div>
                        <div class="form-group">
                            <label for="content_type">نوع محتوا:</label>
                            <select id="content_type" name="content_type">
                                <option value="آموزشی">آموزشی</option>
                                <option value="اطلاعاتی">اطلاعاتی</option>
                                <option value="راهنما">راهنما</option>
                                <option value="تحلیلی">تحلیلی</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="content_length">طول محتوا:</label>
                            <select id="content_length" name="content_length">
                                <option value="1200">۱۲۰۰ کلمه (پیشنهادی)</option>
                                <option value="1500">۱۵۰۰ کلمه</option>
                                <option value="2000">۲۰۰۰ کلمه</option>
                                <option value="2500">۲۵۰۰ کلمه</option>
                            </select>
                        </div>
                    `;
                    break;
                    
                case 'مقاله تخصصی فناوری اطلاعات و برنامه‌نویسی':
                    fields = `
                        <div class="form-group">
                            <label for="tech_topic">موضوع فناوری:</label>
                            <input type="text" id="tech_topic" name="tech_topic" required placeholder="مثال: آموزش React.js، Docker، Machine Learning">
                        </div>
                        <div class="form-group">
                            <label for="tech_category">دسته‌بندی فناوری:</label>
                            <select id="tech_category" name="tech_category">
                                <option value="برنامه‌نویسی وب">برنامه‌نویسی وب</option>
                                <option value="برنامه‌نویسی موبایل">برنامه‌نویسی موبایل</option>
                                <option value="هوش مصنوعی">هوش مصنوعی</option>
                                <option value="دیتابیس">دیتابیس</option>
                                <option value="DevOps">DevOps</option>
                                <option value="امنیت سایبری">امنیت سایبری</option>
                                <option value="کلود کامپیوتینگ">کلود کامپیوتینگ</option>
                                <option value="بلاکچین">بلاکچین</option>
                                <option value="سایر">سایر</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="skill_level">سطح مهارت مخاطب:</label>
                            <select id="skill_level" name="skill_level">
                                <option value="مبتدی">مبتدی</option>
                                <option value="متوسط">متوسط</option>
                                <option value="پیشرفته">پیشرفته</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="programming_language">زبان برنامه‌نویسی (اختیاری):</label>
                            <input type="text" id="programming_language" name="programming_language" placeholder="مثال: JavaScript، Python، Java">
                        </div>
                        <div class="form-group">
                            <label for="framework">فریم‌ورک یا ابزار (اختیاری):</label>
                            <input type="text" id="framework" name="framework" placeholder="مثال: React، Django، Docker">
                        </div>
                    `;
                    break;
                case 'مقاله تخصصی صنعت آب، انرژی و آب و برق':
                    fields = `
                        <div class="form-group">
                            <label for="energy_topic"><strong>موضوع صنعتی:</strong></label>
                            <input type="text" id="energy_topic" name="energy_topic" required placeholder="مثال: مدیریت مصرف انرژی در صنایع، انرژی‌های تجدیدپذیر">
                        </div>
                        <div class="form-group">
                            <label for="energy_sector"><strong>بخش صنعتی:</strong></label>
                            <select id="energy_sector" name="energy_sector">
                                <option value="آب و فاضلاب">آب و فاضلاب</option>
                                <option value="تولید برق">تولید برق</option>
                                <option value="توزیع برق">توزیع برق</option>
                                <option value="انرژی‌های تجدیدپذیر">انرژی‌های تجدیدپذیر</option>
                                <option value="بهینه‌سازی انرژی">بهینه‌سازی انرژی</option>
                                <option value="تجهیزات صنعتی">تجهیزات صنعتی</option>
                                <option value="سایر">سایر</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="energy_type"><strong>نوع انرژی:</strong></label>
                            <select id="energy_type" name="energy_type">
                                <option value="برق">برق</option>
                                <option value="گاز">گاز</option>
                                <option value="آب">آب</option>
                                <option value="خورشیدی">خورشیدی</option>
                                <option value="بادی">بادی</option>
                                <option value="زیست‌توده">زیست‌توده</option>
                                <option value="سایر">سایر</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="industry_size"><strong>اندازه صنعت:</strong></label>
                            <select id="industry_size" name="industry_size">
                                <option value="کوچک">کوچک</option>
                                <option value="متوسط">متوسط</option>
                                <option value="بزرگ">بزرگ</option>
                                <option value="صنعتی">صنعتی</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="regulatory_compliance"><strong>مقررات و استانداردها:</strong></label>
                            <input type="text" id="regulatory_compliance" name="regulatory_compliance" placeholder="مثال: استانداردهای ISO، مقررات محیط زیست">
                        </div>
                    `;
                    break;
                case 'مقاله تخصصی صنعت نفت، گاز و پتروشیمی':
                    fields = `
                        <div class="form-group">
                            <label for="oil_topic"><strong>موضوع صنعتی:</strong></label>
                            <input type="text" id="oil_topic" name="oil_topic" required placeholder="مثال: فرآیندهای پالایش نفت خام، اکتشاف گاز">
                        </div>
                        <div class="form-group">
                            <label for="oil_sector"><strong>بخش صنعتی:</strong></label>
                            <select id="oil_sector" name="oil_sector">
                                <option value="اکتشاف">اکتشاف</option>
                                <option value="استخراج">استخراج</option>
                                <option value="پالایش">پالایش</option>
                                <option value="پتروشیمی">پتروشیمی</option>
                                <option value="حمل و نقل">حمل و نقل</option>
                                <option value="فروش و توزیع">فروش و توزیع</option>
                                <option value="سایر">سایر</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="hydrocarbon_type"><strong>نوع هیدروکربن:</strong></label>
                            <select id="hydrocarbon_type" name="hydrocarbon_type">
                                <option value="نفت خام">نفت خام</option>
                                <option value="گاز طبیعی">گاز طبیعی</option>
                                <option value="مایعات گازی">مایعات گازی</option>
                                <option value="پتروشیمی">پتروشیمی</option>
                                <option value="سایر">سایر</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="facility_type"><strong>نوع تأسیسات:</strong></label>
                            <select id="facility_type" name="facility_type">
                                <option value="پالایشگاه">پالایشگاه</option>
                                <option value="پتروشیمی">پتروشیمی</option>
                                <option value="پایانه">پایانه</option>
                                <option value="خط لوله">خط لوله</option>
                                <option value="سایر">سایر</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="safety_standards"><strong>استانداردهای ایمنی:</strong></label>
                            <input type="text" id="safety_standards" name="safety_standards" placeholder="مثال: API، ISO، OSHA">
                        </div>
                    `;
                    break;
                    
                case 'مقاله تخصصی صنعت آتشنشانی':
                    fields = `
                        <div class="form-group">
                            <label for="fire_topic"><strong>موضوع آتشنشانی:</strong></label>
                            <input type="text" id="fire_topic" name="fire_topic" required placeholder="مثال: تجهیزات پیشرفته آتشنشانی، پروتکل‌های ایمنی">
                        </div>
                        <div class="form-group">
                            <label for="fire_sector"><strong>بخش آتشنشانی:</strong></label>
                            <select id="fire_sector" name="fire_sector">
                                <option value="آتشنشانی شهری">آتشنشانی شهری</option>
                                <option value="آتشنشانی صنعتی">آتشنشانی صنعتی</option>
                                <option value="آتشنشانی فرودگاهی">آتشنشانی فرودگاهی</option>
                                <option value="آتشنشانی جنگل">آتشنشانی جنگل</option>
                                <option value="نجات و امداد">نجات و امداد</option>
                                <option value="پیشگیری از آتش">پیشگیری از آتش</option>
                                <option value="سایر">سایر</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="fire_type"><strong>نوع آتش:</strong></label>
                            <select id="fire_type" name="fire_type">
                                <option value="کلاس A">کلاس A (مواد جامد)</option>
                                <option value="کلاس B">کلاس B (مایعات قابل اشتعال)</option>
                                <option value="کلاس C">کلاس C (گازها)</option>
                                <option value="کلاس D">کلاس D (فلزات)</option>
                                <option value="کلاس E">کلاس E (الکتریکی)</option>
                                <option value="کلاس F">کلاس F (روغن‌های آشپزی)</option>
                                <option value="سایر">سایر</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="equipment_type"><strong>نوع تجهیزات:</strong></label>
                            <select id="equipment_type" name="equipment_type">
                                <option value="تجهیزات اطفاء حریق">تجهیزات اطفاء حریق</option>
                                <option value="تجهیزات نجات">تجهیزات نجات</option>
                                <option value="تجهیزات حفاظت فردی">تجهیزات حفاظت فردی</option>
                                <option value="سیستم‌های هشدار">سیستم‌های هشدار</option>
                                <option value="سایر">سایر</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="safety_protocols"><strong>پروتکل‌های ایمنی:</strong></label>
                            <input type="text" id="safety_protocols" name="safety_protocols" placeholder="مثال: NFPA، ISO، استانداردهای ملی">
                        </div>
                    `;
                    break;
                    
                default:
                    fields = `
                        <div class="form-group">
                            <label for="main_topic"><strong>موضوع اصلی:</strong></label>
                            <input type="text" id="main_topic" name="main_topic" required placeholder="موضوع مقاله خود را وارد کنید...">
                        </div>
                    `;
            }
            
            return fields;
        }
        
        // استفاده مجدد از پرامپت‌های ذخیره شده
        const usePromptButtons = document.querySelectorAll('.use-prompt');
        usePromptButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                const prompt = this.getAttribute('data-prompt');
                const model = this.getAttribute('data-model');
                const isTemplate = this.getAttribute('data-is-template');
                
                document.getElementById('smart_admin_prompt').value = prompt;
                document.getElementById('is_template').value = isTemplate;
                
                // انتخاب مدل در سلکت باکس
                const modelSelect = document.getElementById('smart_admin_model');
                for (let i = 0; i < modelSelect.options.length; i++) {
                    if (modelSelect.options[i].value === model) {
                        modelSelect.selectedIndex = i;
                        break;
                    }
                }
                
                // تغییر تب به تب پرامپت
                document.querySelector('.tab-link[data-tab="prompt"]').click();
                
                // اسکرول به فرم
                document.getElementById('smart_admin_prompt').scrollIntoView({ behavior: 'smooth' });
            });
        });
        // نمایش اسپینر در هنگام ارسال درخواست
        const promptForm = document.getElementById('ai-prompt-form');
        const loadingSpinner = document.getElementById('loading-spinner');
        
        if (promptForm) {
            promptForm.addEventListener('submit', function(e) {
                // نمایش لودینگ
                loadingSpinner.style.display = 'inline-block';
                
                // غیرفعال کردن دکمه برای جلوگیری از ارسال مجدد
                const submitButton = this.querySelector('.submit-button');
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.style.opacity = '0.6';
                    submitButton.style.cursor = 'not-allowed';
                }
                
                // نمایش پیام در حال بارگذاری
                const loadingMessage = document.createElement('div');
                loadingMessage.id = 'loading-message';
                loadingMessage.className = 'loading-message';
                loadingMessage.innerHTML = '<div class="loading-icon">🔄</div><div class="loading-text">در حال تولید محتوا...</div><div class="loading-subtext">لطفاً صبر کنید، این فرآیند ممکن است چند دقیقه طول بکشد.</div>';
                
                // اضافه کردن پیام به صفحه
                const responseContainer = document.querySelector('.response-container');
                if (responseContainer) {
                    responseContainer.innerHTML = '';
                    responseContainer.appendChild(loadingMessage);
                    responseContainer.style.display = 'block';
                } else {
                    // اگر container وجود ندارد، آن را ایجاد کن
                    const newContainer = document.createElement('div');
                    newContainer.className = 'response-container';
                    newContainer.appendChild(loadingMessage);
                    promptForm.parentNode.insertBefore(newContainer, promptForm.nextSibling);
                }
                
                // اسکرول به پیام لودینگ
                setTimeout(() => {
                    loadingMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 100);
            });
        }
        // مدیریت کپی کردن محتوای هوش مصنوعی
        const aiResponseContent = document.getElementById('ai-response-content');
        if (aiResponseContent) {
            // دریافت عنوان پیشنهادی از محتوا
            const contentText = aiResponseContent.textContent;
            const titleMatch = contentText.match(/^(.+?)(?:\n|$)/);
            
            if (titleMatch && titleMatch[1]) {
                const suggestedTitle = titleMatch[1].replace(/^#+\s+/, '').trim();
                const titleInput = document.getElementById('post_title');
                
                if (titleInput && suggestedTitle.length > 5 && suggestedTitle.length < 100) {
                    titleInput.value = suggestedTitle;
                }
            }
            
            // استخراج کلمات کلیدی از محتوا
            const keywordsInput = document.getElementById('post_keywords');
            if (keywordsInput) {
                // الگوهای مختلف برای یافتن کلمات کلیدی
                const patterns = [
                    /کلمات\s*کلیدی\s*[:]\s*(.*?)(?:[\.\n]|$)/i,
                    /کلیدواژه\s*[:]\s*(.*?)(?:[\.\n]|$)/i,
                    /keywords\s*[:]\s*(.*?)(?:[\.\n]|$)/i,
                    /tags\s*[:]\s*(.*?)(?:[\.\n]|$)/i,
                    /برچسب\s*ها\s*[:]\s*(.*?)(?:[\.\n]|$)/i,
                    /تگ\s*ها\s*[:]\s*(.*?)(?:[\.\n]|$)/i,
                    /کلمات\s*کلیدی\s*[=]\s*(.*?)(?:[\.\n]|$)/i,
                    /کلمات\s*کلیدی\s*[>]\s*(.*?)(?:[\.\n]|$)/i,
                ];

                // جستجو بر اساس الگوها
                let foundKeywords = '';
                for (const pattern of patterns) {
                    const match = contentText.match(pattern);
                    if (match && match[1]) {
                        foundKeywords = match[1].trim();
                        break;
                    }
                }

                if (foundKeywords) {
                    // حذف کاماهای اضافی از ابتدا و انتهای رشته
                    foundKeywords = foundKeywords.replace(/^[,،\s]+|[,،\s]+$/g, '');
                    // حذف فاصله‌های اضافی بعد از کاماها
                    foundKeywords = foundKeywords.replace(/[,،](\s+)/g, ',');
                    keywordsInput.value = foundKeywords;
                }
            }
        }
    });
    </script>
    <?php
}

// تابع تشخیص مدل‌های تولید تصویر
function smart_admin_is_image_generation_model($model) {
    $image_generation_models = array(
        'gapgpt/flux.1-schnell',
        'gapgpt/flux.1-dev',
        'gemini-2.0-flash-preview-image-generation',
        'gemini-2.5-flash-preview-native-audio-dialog',
        'dall-e-3',
        'dall-e-2',
        'flux-1-schnell',
        'flux/dev',
        'flux-pro/kontext/text-to-image',
        'flux-pro/kontext/max/text-to-image',
        'midjourney',
        'stable-diffusion'
    );
    
    return in_array($model, $image_generation_models);
}

// تابع استخراج کلمات کلیدی برای تولید تصویر
function smart_admin_extract_image_keywords($content) {
    // حذف تگ‌های HTML
    $text = strip_tags($content);
    
    // استخراج کلمات کلیدی مهم
    $keywords = array();
    
    // جستجوی کلمات کلیدی در عنوان
    if (preg_match('/<h1[^>]*>(.*?)<\/h1>/i', $content, $matches)) {
        $title = strip_tags($matches[1]);
        $keywords[] = $title;
    }
    
    // جستجوی کلمات کلیدی در تیترهای H2
    if (preg_match_all('/<h2[^>]*>(.*?)<\/h2>/i', $content, $matches)) {
        foreach ($matches[1] as $match) {
            $keywords[] = strip_tags($match);
        }
    }
    
    // جستجوی کلمات کلیدی در متن
    $words = explode(' ', $text);
    $word_count = array_count_values($words);
    arsort($word_count);
    
    // انتخاب ۵ کلمه پرتکرار
    $count = 0;
    foreach ($word_count as $word => $frequency) {
        if ($count >= 5) break;
        if (strlen($word) > 3 && !in_array($word, array('این', 'که', 'برای', 'با', 'در', 'از', 'به', 'را', 'است', 'بود', 'شده', 'کرده', 'دارد', 'می‌شود', 'خواهد', 'تواند'))) {
            $keywords[] = $word;
            $count++;
        }
    }
    
    return array_unique($keywords);
}
// تابع تولید تصویر با API
function smart_admin_generate_image($prompt, $model, $api_key, $options = array()) {
    smart_admin_log('Generate image called');
    smart_admin_log('Model: ' . $model);
    smart_admin_log('Prompt: ' . mb_substr($prompt, 0, 200));
    $url = 'https://api.gapgpt.app/v1/images/generations';
    
    $n = isset($options['n']) ? max(1, min(4, intval($options['n']))) : 1;
    $size = isset($options['size']) ? $options['size'] : '1024x1024';
    $quality = isset($options['quality']) ? $options['quality'] : 'standard';
    $response_format = isset($options['response_format']) ? $options['response_format'] : 'url';
    $reference_image_url = isset($options['reference_image_url']) ? $options['reference_image_url'] : '';
    $reference_image_id = isset($options['reference_image_id']) ? $options['reference_image_id'] : '';
    $reference_image_urls = isset($options['reference_image_urls']) ? $options['reference_image_urls'] : '';

    $body = array(
        'model' => $model,
        'prompt' => $prompt,
        'n' => $n,
        'size' => $size,
        'quality' => $quality,
        'response_format' => $response_format
    );

    // اضافه کردن تصویر مرجع در صورت وجود (image-to-image)
    if (!empty($reference_image_url)) {
        $body['reference_image_url'] = $reference_image_url;
    }
    if (!empty($reference_image_id)) {
        $body['reference_image_id'] = $reference_image_id;
    }
    if (!empty($reference_image_urls)) {
        // اگر رشته JSON باشد، همان را پاس می‌دهیم؛ در غیر اینصورت آرایه را ارسال می‌کنیم
        if (is_string($reference_image_urls)) {
            $decoded = json_decode($reference_image_urls, true);
            $body['reference_image_urls'] = is_array($decoded) ? $decoded : array($reference_image_urls);
        } else if (is_array($reference_image_urls)) {
            $body['reference_image_urls'] = $reference_image_urls;
        }
    }

    smart_admin_log('Request URL: ' . $url);
    smart_admin_log('Request Body: ' . json_encode($body, JSON_UNESCAPED_UNICODE));
    
    $args = array(
        'body' => json_encode($body),
        'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key
        ),
        'timeout' => 60,
        'method' => 'POST'
    );
    
    $response = wp_remote_post($url, $args);
    if (is_wp_error($response)) {
        smart_admin_log('HTTP Error: ' . $response->get_error_message());
    } else {
        smart_admin_log('HTTP Status: ' . wp_remote_retrieve_response_code($response));
        smart_admin_log('Raw Response: ' . wp_remote_retrieve_body($response));
    }
    
    if (is_wp_error($response)) {
        return array('error' => $response->get_error_message());
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = json_decode(wp_remote_retrieve_body($response), true);
    
    if ($response_code !== 200) {
        $msg = isset($response_body['error']['message']) ? $response_body['error']['message'] : 'خطا در تولید تصویر';
        smart_admin_log('API Error: ' . $msg);
        return array('error' => $msg);
    }
    if (isset($response_body['data']) && is_array($response_body['data']) && !empty($response_body['data'])) {
        $urls = array();
        foreach ($response_body['data'] as $item) {
            if (isset($item['url'])) {
                $urls[] = $item['url'];
            }
        }
        if (!empty($urls)) {
            return array(
                'image_url' => $urls[0],
                'images' => $urls,
                'prompt' => $prompt
            );
        }
    }
    smart_admin_log('Image URLs not found in response payload');
    return array('error' => 'خطا در دریافت تصویر');
}

// تابع ترجمه و بهبود پیام‌های خطا
function smart_admin_translate_error_message($error_message) {
    $error_messages = array(
        'insufficient_quota' => 'اعتبار حساب شما تمام شده است. لطفاً حساب خود را شارژ کنید.',
        'quota_exceeded' => 'محدودیت استفاده از API تمام شده است. لطفاً حساب خود را شارژ کنید.',
        'billing_not_active' => 'حساب شما فعال نیست. لطفاً حساب خود را فعال کنید.',
        'invalid_api_key' => 'کلید API نامعتبر است. لطفاً کلید API صحیح را وارد کنید.',
        'rate_limit_exceeded' => 'محدودیت تعداد درخواست‌ها تمام شده است. لطفاً کمی صبر کنید.',
        'model_not_found' => 'مدل انتخاب شده موجود نیست. لطفاً مدل دیگری انتخاب کنید.',
        'context_length_exceeded' => 'متن ورودی خیلی طولانی است. لطفاً متن کوتاه‌تری وارد کنید.',
        'invalid_request' => 'درخواست نامعتبر است. لطفاً فیلدهای فرم را بررسی کنید.',
        'server_error' => 'خطای سرور. لطفاً بعداً تلاش کنید.',
        'timeout' => 'زمان انتظار تمام شد. لطفاً دوباره تلاش کنید.',
        'network_error' => 'خطا در اتصال شبکه. لطفاً اتصال اینترنت خود را بررسی کنید.',
        'authentication_failed' => 'خطا در احراز هویت. لطفاً کلید API را بررسی کنید.',
        'payment_required' => 'پرداخت مورد نیاز است. لطفاً حساب خود را شارژ کنید.',
        'account_suspended' => 'حساب شما معلق شده است. لطفاً با پشتیبانی تماس بگیرید.',
        'service_unavailable' => 'سرویس در دسترس نیست. لطفاً بعداً تلاش کنید.',
        'maintenance_mode' => 'سرویس در حال تعمیر است. لطفاً بعداً تلاش کنید.',
        'invalid_model' => 'مدل انتخاب شده نامعتبر است.',
        'model_overloaded' => 'مدل در حال حاضر بیش از حد بارگذاری شده است. لطفاً کمی صبر کنید.',
        'content_filter' => 'محتوای ارسالی فیلتر شده است. لطفاً محتوای دیگری امتحان کنید.',
        'token_limit_exceeded' => 'محدودیت توکن‌ها تمام شده است. لطفاً متن کوتاه‌تری وارد کنید.'
    );
    
    // جستجوی کلیدهای خطا در پیام
    foreach ($error_messages as $key => $persian_message) {
        if (stripos($error_message, $key) !== false) {
            return $persian_message;
        }
    }
    
    // جستجوی کلمات کلیدی در پیام
    $keywords = array(
        'quota' => 'اعتبار حساب شما تمام شده است. لطفاً حساب خود را شارژ کنید.',
        'billing' => 'مشکل در صورتحساب. لطفاً حساب خود را بررسی کنید.',
        'payment' => 'پرداخت مورد نیاز است. لطفاً حساب خود را شارژ کنید.',
        'credit' => 'اعتبار کافی نیست. لطفاً حساب خود را شارژ کنید.',
        'balance' => 'موجودی کافی نیست. لطفاً حساب خود را شارژ کنید.',
        'limit' => 'محدودیت استفاده تمام شده است.',
        'rate' => 'محدودیت تعداد درخواست‌ها تمام شده است.',
        'timeout' => 'زمان انتظار تمام شد. لطفاً دوباره تلاش کنید.',
        'network' => 'خطا در اتصال شبکه.',
        'server' => 'خطای سرور. لطفاً بعداً تلاش کنید.',
        'maintenance' => 'سرویس در حال تعمیر است.',
        'overloaded' => 'سرور بیش از حد بارگذاری شده است.',
        'filter' => 'محتوای ارسالی فیلتر شده است.',
        'token' => 'محدودیت توکن‌ها تمام شده است.',
        'context' => 'متن ورودی خیلی طولانی است.',
        'model' => 'مشکل در مدل انتخاب شده.',
        'api' => 'مشکل در کلید API.',
        'auth' => 'خطا در احراز هویت.',
        'suspend' => 'حساب شما معلق شده است.',
        'unavailable' => 'سرویس در دسترس نیست.'
    );
    
    foreach ($keywords as $keyword => $persian_message) {
        if (stripos($error_message, $keyword) !== false) {
            return $persian_message;
        }
    }
    
    // اگر هیچ ترجمه‌ای پیدا نشد، پیام اصلی را برگردان
    return 'خطا: ' . $error_message;
}
// تابع ارسال درخواست به API گپ جی‌پی‌تی
function send_to_gapgpt_api($prompt, $model, $api_key) {
    smart_admin_log('Chat API called - model: ' . $model);
    // تنظیمات درخواست
    $url = 'https://api.gapgpt.app/v1/chat/completions';
    
    $body = array(
        'model' => $model,
        'messages' => array(
            array(
                'role' => 'user',
                'content' => $prompt
            )
        )
    );
    
    $args = array(
        'body' => json_encode($body),
        'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key
        ),
        'timeout' => 60,
        'method' => 'POST'
    );
    
    // ارسال درخواست
    $response = wp_remote_post($url, $args);
    if (is_wp_error($response)) {
        smart_admin_log('Chat HTTP Error: ' . $response->get_error_message());
    } else {
        smart_admin_log('Chat HTTP Status: ' . wp_remote_retrieve_response_code($response));
    }
    
    // بررسی خطا
    if (is_wp_error($response)) {
        return array('error' => 'خطا در اتصال به سرور: ' . $response->get_error_message());
    }
    
    // دریافت پاسخ
    $response_code = wp_remote_retrieve_response_code($response);
    $response_body_raw = wp_remote_retrieve_body($response);
    $response_body = json_decode($response_body_raw, true);
    
        // smart_admin_log('Raw response body: ' . $response_body_raw);
        // smart_admin_log('Parsed response body: ' . json_encode($response_body));
    
    if ($response_code !== 200) {
        $error_message = 'خطای نامشخص';
        
        // بررسی انواع خطاهای رایج
        if (isset($response_body['error']['message'])) {
            $error_message = $response_body['error']['message'];
        } elseif (isset($response_body['error'])) {
            $error_message = is_string($response_body['error']) ? $response_body['error'] : 'خطای نامشخص';
        }
        
        // ترجمه و بهبود پیام‌های خطا
        $error_message = smart_admin_translate_error_message($error_message);
        smart_admin_log('Chat API Error: ' . $error_message);
        
        return array('error' => $error_message);
    }
    
    // استخراج محتوای پاسخ
    // smart_admin_log('Checking for content in response: ' . (isset($response_body['choices'][0]['message']['content']) ? 'Found' : 'Not found'));
    // if (isset($response_body['choices'])) {
    //     smart_admin_log('Choices count: ' . count($response_body['choices']));
    //     if (isset($response_body['choices'][0])) {
    //         smart_admin_log('First choice structure: ' . json_encode($response_body['choices'][0]));
    //     }
    // }
    
    if (isset($response_body['choices'][0]['message']['content'])) {
        $content = $response_body['choices'][0]['message']['content'];
        smart_admin_log('Content extracted: ' . substr($content, 0, 100) . '...');
        
        // تبدیل Markdown به HTML برای نمایش بهتر
        $content = smart_admin_convert_markdown_to_html($content);

        // اگر مدل به دلیل قوانین از پاسخ‌گویی امتناع کرد، یک بار با دستورالعمل ساده‌تر تلاش مجدد می‌کنیم
        $refusal_detected = false;
        $plain_text_content = strip_tags($content);
        $refusal_patterns = '/(متاسفم|متأسفم|نمی[\s‌]*توانم|نمیتوانم|قادر نیستم|ممکن نیست|نمی شود|سیاست|قوانین|I\\s*can\'t|I cannot|I\\s*won\'t|cannot comply|policy)/iu';
        if (preg_match($refusal_patterns, $plain_text_content)) {
            $refusal_detected = true;
            smart_admin_log('Refusal detected in AI response. Retrying with simplified HTML-focused prompt.');
        }

        if ($refusal_detected) {
            $retry_instructions = "\n\nفقط بدنه مقاله را به صورت HTML تمیز تولید کن. هیچ عذرخواهی یا توضیح درباره قوانین ننویس. از <h2> و <h3> برای تیترها، از <ul><li> برای بولت‌پوینت‌ها و از <strong> برای تأکید استفاده کن. خروجی صرفاً محتوای HTML باشد (بدون <html> یا <body>).";
            $retry_body = array(
                'model' => $model,
                'messages' => array(
                    array(
                        'role' => 'user',
                        'content' => $prompt . $retry_instructions
                    )
                )
            );
            $retry_args = $args;
            $retry_args['body'] = json_encode($retry_body);

            $retry_response = wp_remote_post($url, $retry_args);
            if (!is_wp_error($retry_response) && wp_remote_retrieve_response_code($retry_response) === 200) {
                $retry_response_body = json_decode(wp_remote_retrieve_body($retry_response), true);
                if (isset($retry_response_body['choices'][0]['message']['content'])) {
                    $content = smart_admin_convert_markdown_to_html($retry_response_body['choices'][0]['message']['content']);
                }
            }
        }
        
        // استخراج کلمات کلیدی از محتوا
        $keywords = array();
        if (function_exists('smart_admin_extract_keywords_from_ai_response')) {
            $keywords = smart_admin_extract_keywords_from_ai_response($content);
        }
        
        $result = array(
            'content' => $content,
            'keywords' => $keywords
        );
        
        // اگر مدل تولید تصویر است، تصویر هم تولید کن
        if (smart_admin_is_image_generation_model($model)) {
            // استخراج کلمات کلیدی برای تولید تصویر
            $image_keywords = smart_admin_extract_image_keywords($content);
            $image_prompt = implode(' ', array_slice($image_keywords, 0, 5));
            
            // تولید تصویر
            $image_result = smart_admin_generate_image($image_prompt, $model, $api_key);
            
            if (!isset($image_result['error'])) {
                $result['generated_image'] = $image_result;
            }
        }
        
        return $result;
    } else {
        return array('error' => 'خطا در دریافت پاسخ از API');
    }
}
// تابع استخراج تیترهای H2 و ایجاد فهرست مطالب برای محتوای Markdown
function smart_admin_generate_table_of_contents($content) {
    // استخراج تیترهای H2 از محتوا
    $h2_pattern = '/## (.*?)(?=\n|$)/m';
    $h2_matches = [];
    preg_match_all($h2_pattern, $content, $h2_matches);
    
    // اگر کمتر از 3 تیتر H2 وجود داشته باشد، فهرست مطالب ایجاد نمی‌کنیم
    if (count($h2_matches[1]) < 3) {
        return $content;
    }
    
    // ایجاد فهرست مطالب با استایل بهتر
    $toc = "<div class=\"toc-container\" style=\"background-color: #f9f9f9; padding: 20px; margin: 30px 0; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);\">\n";
    $toc .= "<h3 style=\"margin-top: 0; margin-bottom: 15px; font-size: 18px; color: #333; border-bottom: 1px solid #eee; padding-bottom: 10px;\">فهرست مطالب</h3>\n";
    $toc .= "<ul style=\"list-style-type: none; padding-right: 0; margin: 0;\">\n";
    
    // ایجاد آرایه برای ذخیره تیترها و شناسه‌های آنها
    $headings = [];
    
    foreach ($h2_matches[1] as $index => $title) {
        // پاکسازی عنوان از کاراکترهای اضافی
        $clean_title = trim($title);
        
        // ایجاد آیدی منحصر به فرد برای هر تیتر با استفاده از متن تیتر
        $id = 'section-' . sanitize_title($clean_title) . '-' . ($index + 1);
        
        // ذخیره تیتر و شناسه آن
        $headings[] = [
            'title' => $clean_title,
            'id' => $id,
            'index' => $index + 1
        ];
        
        // اضافه کردن آیتم به فهرست مطالب
        $toc .= "<li style=\"margin-bottom: 10px; line-height: 1.5;\">";
        $toc .= "<a href=\"#$id\" style=\"text-decoration: none; color: #0073aa; display: block; padding: 5px 10px; border-radius: 4px; transition: background-color 0.2s ease;\" onmouseover=\"this.style.backgroundColor='#f0f0f0'\" onmouseout=\"this.style.backgroundColor='transparent'\">";
        $toc .= "<span style=\"font-weight: bold; margin-left: 5px;\">" . ($index + 1) . ".</span> " . $clean_title;
        $toc .= "</a></li>\n";
    }
    
    $toc .= "</ul>\n";
    $toc .= "</div>\n\n";
    
    // جایگزینی تیترها در محتوا با نسخه دارای آیدی
    foreach ($headings as $heading) {
        $content = preg_replace(
            '/## ' . preg_quote($heading['title'], '/') . '(?=\n|$)/m',
            "<h2 id=\"{$heading['id']}\">{$heading['title']}</h2>",
            $content,
            1
        );
    }
    
    // پیدا کردن مکان مناسب برای قرار دادن فهرست مطالب
    // ترجیح می‌دهیم بعد از اولین پاراگراف باشد
    $first_h1_end = strpos($content, "</h1>");
    $first_paragraph_end = strpos($content, "\n\n", $first_h1_end !== false ? $first_h1_end : 0);
    
    if ($first_paragraph_end !== false) {
        // قرار دادن فهرست مطالب بعد از اولین پاراگراف
        $content = substr_replace($content, "\n\n" . $toc, $first_paragraph_end, 0);
    } else {
        // اگر پاراگراف اول پیدا نشد، فهرست مطالب را بعد از اولین H1 قرار می‌دهیم
        if ($first_h1_end !== false) {
            $content = substr_replace($content, "\n\n" . $toc, $first_h1_end + 5, 0);
        } else {
            // در غیر این صورت، فهرست مطالب را در ابتدای محتوا قرار می‌دهیم
            $content = $toc . $content;
        }
    }
    
    return $content;
}
// تابع استخراج تیترهای H2 و ایجاد فهرست مطالب برای محتوای HTML
function smart_admin_generate_html_table_of_contents($content) {
    // استخراج تیترهای H2 از محتوای HTML
    $h2_pattern = '/<h2[^>]*>(.*?)<\/h2>/is';
    $h2_matches = [];
    preg_match_all($h2_pattern, $content, $h2_matches);
    
    // اگر کمتر از 3 تیتر H2 وجود داشته باشد، فهرست مطالب ایجاد نمی‌کنیم
    if (count($h2_matches[1]) < 3) {
        return $content;
    }
    
    // ایجاد فهرست مطالب با استایل بهتر
    $toc = "<div class=\"toc-container\" style=\"background-color: #f9f9f9; padding: 20px; margin: 30px 0; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);\">\n";
    $toc .= "<h3 style=\"margin-top: 0; margin-bottom: 15px; font-size: 18px; color: #333; border-bottom: 1px solid #eee; padding-bottom: 10px;\">فهرست مطالب</h3>\n";
    $toc .= "<ul style=\"list-style-type: none; padding-right: 0; margin: 0;\">\n";
    
    // ایجاد آرایه برای ذخیره تیترها و شناسه‌های آنها
    $headings = [];
    
    foreach ($h2_matches[1] as $index => $title) {
        // پاکسازی عنوان از تگ‌های HTML و کاراکترهای اضافی
        $clean_title = trim(strip_tags($title));
        
        // ایجاد آیدی منحصر به فرد برای هر تیتر با استفاده از متن تیتر
        $id = 'section-' . sanitize_title($clean_title) . '-' . ($index + 1);
        
        // ذخیره تیتر و شناسه آن
        $headings[] = [
            'title' => $clean_title,
            'id' => $id,
            'index' => $index + 1,
            'original' => $title
        ];
        
        // اضافه کردن آیتم به فهرست مطالب
        $toc .= "<li style=\"margin-bottom: 10px; line-height: 1.5;\">";
        $toc .= "<a href=\"#$id\" style=\"text-decoration: none; color: #0073aa; display: block; padding: 5px 10px; border-radius: 4px; transition: background-color 0.2s ease;\" onmouseover=\"this.style.backgroundColor='#f0f0f0'\" onmouseout=\"this.style.backgroundColor='transparent'\">";
        $toc .= "<span style=\"font-weight: bold; margin-left: 5px;\">" . ($index + 1) . ".</span> " . $clean_title;
        $toc .= "</a></li>\n";
    }
    
    $toc .= "</ul>\n";
    $toc .= "</div>\n\n";
    
    // جایگزینی تیترها در محتوا با نسخه دارای آیدی
    foreach ($headings as $heading) {
        $original_title = preg_quote($heading['original'], '/');
        $content = preg_replace(
            '/<h2[^>]*>' . $original_title . '<\/h2>/is',
            "<h2 id=\"{$heading['id']}\">{$heading['original']}</h2>",
            $content,
            1
        );
    }
    
    // پیدا کردن مکان مناسب برای قرار دادن فهرست مطالب
    // ترجیح می‌دهیم بعد از اولین پاراگراف باشد
    $first_h1_end = strpos($content, "</h1>");
    $first_paragraph_end = strpos($content, "</p>", $first_h1_end !== false ? $first_h1_end : 0);
    
    if ($first_paragraph_end !== false) {
        // قرار دادن فهرست مطالب بعد از اولین پاراگراف
        $content = substr_replace($content, $toc, $first_paragraph_end + 4, 0);
    } else {
        // اگر پاراگراف اول پیدا نشد، فهرست مطالب را بعد از اولین H1 قرار می‌دهیم
        if ($first_h1_end !== false) {
            $content = substr_replace($content, $toc, $first_h1_end + 5, 0);
        } else {
            // در غیر این صورت، فهرست مطالب را در ابتدای محتوا قرار می‌دهیم
            $content = $toc . $content;
        }
    }
    
    return $content;
}
// تابع تبدیل Markdown به HTML
function smart_admin_convert_markdown_to_html($content) {
    // بررسی اینکه آیا محتوا HTML است یا نه
    $is_html_content = (strpos($content, '<h') !== false || strpos($content, '<p') !== false || strpos($content, '<strong') !== false || strpos($content, '<ul') !== false || strpos($content, '<li') !== false || strpos($content, '<ol') !== false);
    
    if ($is_html_content) {
        // اگر محتوا HTML است، فقط ```html و ``` را حذف کن
        $content = preg_replace('/```html\s*/i', '', $content);
        $content = preg_replace('/```\s*$/i', '', $content);
        smart_admin_log('HTML content detected, skipping markdown conversion');
        return $content;
    }
    
    // پاکسازی بلاک‌های کد و تگ‌های ناخواسته که بعضی مدل‌ها اضافه می‌کنند
    // حذف بلاک‌های کد سه‌تایی ```...```
    $content = preg_replace('/```[\s\S]*?```/m', '', $content);
    // حذف بلاک‌های کد ~~~...~~~
    $content = preg_replace('/~~~[\s\S]*?~~~/m', '', $content);
    // حذف بک‌تیک‌های تک‌خطی
    $content = preg_replace('/`([^`]+)`/m', '$1', $content);
    // حذف هرگونه تگ pre/code باقی‌مانده یا رها شده
    $content = str_replace(array('</code></pre>', '</pre></code>'), '', $content);
    $content = preg_replace('/<\/?pre[^>]*>/i', '', $content);
    $content = preg_replace('/<\/?code[^>]*>/i', '', $content);

    // ابتدا فهرست مطالب را ایجاد می‌کنیم (قبل از تبدیل مارک‌داون به HTML)
    $content = smart_admin_generate_table_of_contents($content);
    
    // تبدیل تیترها
    $content = preg_replace('/^### (.*$)/m', '<h3>$1</h3>', $content);
    // تیترهای H2 که قبلاً در فهرست مطالب پردازش نشده‌اند را تبدیل می‌کنیم
    $content = preg_replace('/^## (.*$)/m', '<h2>$1</h2>', $content);
    $content = preg_replace('/^# (.*$)/m', '<h1>$1</h1>', $content);
    
    // تبدیل لیست‌ها
    $lines = explode("\n", $content);
    $in_list = false;
    $list_content = '';
    $new_content = '';
    
    foreach ($lines as $line) {
        if (preg_match('/^[\*\-] (.+)$/u', $line) || preg_match('/^\d+\. (.+)$/u', $line) || preg_match('/^•\s+(.+)$/u', $line)) {
            // این خط لیست است
            if (!$in_list) {
                $in_list = true;
                if (preg_match('/^\d+\./u', $line)) {
                    $list_content = '<ol>';
                } else {
                    $list_content = '<ul>';
                }
            }
            
            $item = preg_replace('/^[\*\-] (.+)$/u', '$1', $line);
            $item = preg_replace('/^\d+\. (.+)$/u', '$1', $item);
            $item = preg_replace('/^•\s+(.+)$/u', '$1', $item);
            $list_content .= '<li>' . $item . '</li>';
        } else {
            // این خط لیست نیست
            if ($in_list) {
                if (strpos($list_content, '<ol>') !== false) {
                    $list_content .= '</ol>';
                } else {
                    $list_content .= '</ul>';
                }
                $new_content .= $list_content;
                $in_list = false;
                $list_content = '';
            }
            $new_content .= $line . "\n";
        }
    }
    
    if ($in_list) {
        if (strpos($list_content, '<ol>') !== false) {
            $list_content .= '</ol>';
        } else {
            $list_content .= '</ul>';
        }
        $new_content .= $list_content;
    }
    
    $content = $new_content;
    
    // تبدیل بولد
    $content = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $content);
    
    // تبدیل ایتالیک
    $content = preg_replace('/\*(.*?)\*/s', '<em>$1</em>', $content);
    
    // تبدیل لینک‌ها
    $content = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2" target="_blank">$1</a>', $content);
    
    // تبدیل جداول
    $lines = explode("\n", $content);
    $in_table = false;
    $table_content = '';
    $new_content = '';
    
    foreach ($lines as $line) {
        if (strpos($line, '|') !== false && strpos($line, '|') !== strrpos($line, '|')) {
            // این خط جدول است
            if (!$in_table) {
                $in_table = true;
                $table_content = '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%; margin: 10px 0; font-size: 14px;">';
            }
            
            $cells = explode('|', trim($line, '|'));
            $table_content .= '<tr>';
            foreach ($cells as $cell) {
                $cell = trim($cell);
                if (strpos($cell, '---') !== false) {
                    // خط جداکننده - نادیده بگیر
                    continue;
                }
                $table_content .= '<td style="border: 1px solid #ddd; padding: 8px; text-align: center;">' . $cell . '</td>';
            }
            $table_content .= '</tr>';
        } else {
            // این خط جدول نیست
            if ($in_table) {
                $table_content .= '</table>';
                $new_content .= $table_content;
                $in_table = false;
                $table_content = '';
            }
            $new_content .= $line . "\n";
        }
    }
    
    if ($in_table) {
        $table_content .= '</table>';
        $new_content .= $table_content;
    }
    $content = $new_content;
    
    // تبدیل پاراگراف‌ها
    $lines = explode("\n", $content);
    $paragraphs = array();
    $current_paragraph = '';
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) {
            if (!empty($current_paragraph)) {
                $paragraphs[] = $current_paragraph;
                $current_paragraph = '';
            }
        } elseif (strpos($line, '<') === 0) {
            // این خط HTML است (تیتر، لیست، جدول)
            if (!empty($current_paragraph)) {
                $paragraphs[] = $current_paragraph;
                $current_paragraph = '';
            }
            $paragraphs[] = $line;
        } else {
            // این خط متن عادی است
            if (!empty($current_paragraph)) {
                $current_paragraph .= ' ' . $line;
            } else {
                $current_paragraph = $line;
            }
        }
    }
    
    if (!empty($current_paragraph)) {
        $paragraphs[] = $current_paragraph;
    }
    
    $content = '';
    foreach ($paragraphs as $paragraph) {
        if (strpos($paragraph, '<') === 0) {
            // این HTML است
            $content .= $paragraph . "\n";
        } else {
            // این متن عادی است - تبدیل به پاراگراف
            $content .= '<p>' . $paragraph . '</p>' . "\n";
        }
    }
    
    return $content;
}

function smart_admin_notice() {
    // نمایش اعلان فقط برای کاربران با مجوز مدیریت
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // نمایش اعلان فقط در صفحات مشخص
    $screen = get_current_screen();
    if (!$screen || !in_array($screen->id, array('dashboard', 'plugins'))) {
        return;
    }
    
    // دریافت تعداد پرامپت‌های ذخیره شده
    $saved_prompts = get_option('smart_admin_saved_prompts', array());
    $prompt_count = count($saved_prompts);
    
    ?>
    <div class="notice notice-info is-dismissible">
        <p>
            <strong>ادمین هوشمند:</strong> 
            با استفاده از <a href="<?php echo esc_url(admin_url('admin.php?page=smart-admin')); ?>">ادمین هوشمند</a> می‌توانید محتوای هوشمند با کمک هوش مصنوعی تولید کنید.
            <?php if ($prompt_count > 0): ?>
                <span>(<?php echo $prompt_count; ?> پرامپت ذخیره شده)</span>
            <?php endif; ?>
        </p>
    </div>
    <?php
}
// انتشار پیش‌نویس با یک کلیک
function smart_admin_publish_draft() {
    if (isset($_GET['action']) && $_GET['action'] == 'publish_ai_draft' && isset($_GET['post_id'])) {
        $post_id = intval($_GET['post_id']);
        
        // بررسی نانس
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'publish_ai_draft_' . $post_id)) {
            wp_die('خطای امنیتی رخ داده است.');
        }
        
        // بررسی مجوز
        if (!current_user_can('publish_posts')) {
            wp_die('شما مجوز لازم برای انتشار این نوشته را ندارید.');
        }
        
        // بررسی وجود پست
        $post = get_post($post_id);
        if (!$post || $post->post_status != 'draft' || !get_post_meta($post_id, 'smart_admin_generated', true)) {
            wp_die('نوشته مورد نظر یافت نشد یا قابل انتشار نیست.');
        }
        
        // انتشار پست
        wp_publish_post($post_id);
        
        // ریدایرکت به صفحه ویرایش پست
        wp_redirect(get_edit_post_link($post_id, 'redirect'));
        exit;
    }
}
add_action('admin_init', 'smart_admin_publish_draft');

// نمایش محتوای متاباکس
function smart_admin_metabox_callback($post) {
    // بررسی آیا این پست توسط دستیار هوشمند ایجاد شده است
    $is_ai_generated = get_post_meta($post->ID, 'smart_admin_generated', true);
    
    if ($is_ai_generated == 'yes') {
        $generation_date = get_post_meta($post->ID, 'smart_admin_generation_date', true);
        echo '<p><strong>این محتوا توسط دستیار هوشمند تولید شده است.</strong></p>';
        echo '<p>تاریخ تولید: ' . date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($generation_date)) . '</p>';
        
        // دریافت برچسب‌های پست
        $post_tags = wp_get_post_tags($post->ID, array('fields' => 'names'));
        $keywords_string = implode(', ', $post_tags);
        
        echo '<div id="smart-admin-keywords-form">';
        echo '<p><label for="smart_admin_keywords">کلمات کلیدی:</label><br>';
        echo '<input type="text" id="smart_admin_keywords" name="smart_admin_keywords" value="' . esc_attr($keywords_string) . '" style="width: 100%;">';
        echo '<button type="button" id="set_rank_math_keywords" class="button button-secondary" style="margin-top: 8px;">تنظیم کلمات کلیدی در Rank Math</button>';
        echo '<span id="keywords_result" style="display: block; margin-top: 5px;"></span>';
        echo '</p>';
        echo '</div>';
        
        wp_nonce_field('smart_admin_set_keywords', 'smart_admin_keywords_nonce');
        
        // اسکریپت برای ارسال درخواست AJAX
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#set_rank_math_keywords').on('click', function() {
                var $button = $(this);
                var $result = $('#keywords_result');
                var keywords = $('#smart_admin_keywords').val();
                
                $button.prop('disabled', true).text('در حال تنظیم...');
                $result.text('');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'smart_admin_set_keywords',
                        post_id: <?php echo $post->ID; ?>,
                        keywords: keywords,
                        nonce: $('#smart_admin_keywords_nonce').val()
                    },
                    success: function(response) {
                        if (response.success) {
                            $result.html('<span style="color: green;">' + response.data + '</span>');
                            // بروزرسانی صفحه برای نمایش تغییرات
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            $result.html('<span style="color: red;">' + response.data + '</span>');
                        }
                        $button.prop('disabled', false).text('تنظیم کلمات کلیدی در Rank Math');
                    },
                    error: function() {
                        $result.html('<span style="color: red;">خطا در ارتباط با سرور</span>');
                        $button.prop('disabled', false).text('تنظیم کلمات کلیدی در Rank Math');
                    }
                });
            });
        });
        
        // مدیریت نمایش/مخفی کردن فیلد نام برند
        function initBrandFieldToggle() {
            const brandCheckbox = document.getElementById('smart_admin_allow_brand');
            const brandField = document.getElementById('brand_name_field');
            
            if (!brandCheckbox || !brandField) {
                console.log('عناصر نام برند یافت نشدند');
                return;
            }
            
            function toggleBrandField() {
                if (brandCheckbox.checked) {
                    brandField.style.display = 'block';
                    console.log('فیلد نام برند نمایش داده شد');
                } else {
                    brandField.style.display = 'none';
                    console.log('فیلد نام برند مخفی شد');
                }
            }
            
            // تنظیم حالت اولیه
            toggleBrandField();
            
            // اضافه کردن event listener
            brandCheckbox.addEventListener('change', toggleBrandField);
            console.log('Event listener برای نام برند اضافه شد');
        }
        
        // اجرای تابع در چند حالت مختلف
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initBrandFieldToggle);
        } else {
            initBrandFieldToggle();
        }
        
        // اجرای مجدد بعد از 1 ثانیه برای اطمینان
        setTimeout(initBrandFieldToggle, 1000);
        </script>
        <?php
    } else {
        echo '<p>این محتوا توسط دستیار هوشمند تولید نشده است.</p>';
    }
} 

// تابع دریافت تنظیمات متاباکس
function smart_admin_get_setting($key) {
    $settings = get_option('smart_admin_settings', array());
    return isset($settings[$key]) ? $settings[$key] : false;
}
// تابع ذخیره تنظیمات متاباکس
function smart_admin_save_metabox_settings() {
    if (isset($_POST['smart_admin_metabox_nonce']) && wp_verify_nonce($_POST['smart_admin_metabox_nonce'], 'smart_admin_metabox_settings')) {
        $settings = array();
        
        // ذخیره تنظیمات متاباکس
        $settings['rankmath_metabox'] = isset($_POST['smart_admin_settings']['rankmath_metabox']) ? 1 : 0;
        $settings['openai_metabox'] = isset($_POST['smart_admin_settings']['openai_metabox']) ? 1 : 0;
        $settings['send_method_metabox'] = isset($_POST['smart_admin_settings']['send_method_metabox']) ? 1 : 0;
        $settings['auto_publish'] = isset($_POST['smart_admin_settings']['auto_publish']) ? 1 : 0;
        $settings['debug_mode'] = isset($_POST['smart_admin_settings']['debug_mode']) ? 1 : 0;
        
        update_option('smart_admin_settings', $settings);
        
        // ریدایرکت با پیام موفقیت
        wp_redirect(add_query_arg('updated', 'true', admin_url('admin.php?page=smart-admin-metabox-settings')));
        exit;
    }
}
add_action('admin_init', 'smart_admin_save_metabox_settings');

// تابع فعال‌سازی حالت دیباگ
function smart_admin_enable_debug_mode() {
    if (smart_admin_get_setting('debug_mode')) {
        // فعال‌سازی لاگ‌گیری
        if (!file_exists(WP_CONTENT_DIR . '/debug.log')) {
            touch(WP_CONTENT_DIR . '/debug.log');
        }
        
        // تنظیم error_log برای debug.log
        @ini_set('error_log', WP_CONTENT_DIR . '/debug.log');
        @ini_set('log_errors', 'On');
    }
}
add_action('init', 'smart_admin_enable_debug_mode');

// تابع لاگ‌گیری برای حالت دیباگ
function smart_admin_debug_log($message, $type = 'INFO') {
    if (smart_admin_get_setting('debug_mode')) {
        $timestamp = date('Y-m-d H:i:s');
        $log_message = "[{$timestamp}] [{$type}] {$message}" . PHP_EOL;
        error_log($log_message);
    }
}
/**
 * استخراج عنوان SEO شده از پاسخ هوش مصنوعی
 * 
 * @param string $content محتوای تولید شده توسط هوش مصنوعی
 * @param string $main_topic موضوع اصلی (اختیاری)
 * @return string عنوان SEO شده یا رشته خالی در صورت عدم یافتن
 */
function smart_admin_extract_seo_title($content, $main_topic = '') {
    // لاگ برای دیباگ
    smart_admin_debug_log('Extracting SEO title from AI content', 'INFO');
    smart_admin_debug_log('Content length: ' . strlen($content), 'INFO');
    
    // حذف کلمات نامناسب برای عنوان
    $inappropriate_words = array(
        'فهرست مطالب', 'فهرست', 'مطالب', 'لیست', 'جدول', 'تعداد', 'شماره',
        'table of contents', 'contents', 'list', 'index', 'menu', 'navigation'
    );
    
    // بررسی اگر محتوا حاوی عنوان متا (Meta Title) صریح است
    $meta_title_patterns = array(
        // الگوی برای Meta Title صریح
        '/(?:عنوان متا|متا تایتل|meta title|عنوان سئو|عنوان SEO)[:]\s*(.*?)(?:[\.\n]|$)/i',
        '/(?:H1|عنوان اصلی|عنوان مقاله|عنوان صفحه)[:]\s*(.*?)(?:[\.\n]|$)/i'
    );
    
    foreach ($meta_title_patterns as $pattern) {
        if (preg_match($pattern, $content, $matches)) {
            $meta_title = trim(strip_tags($matches[1]));
            $meta_title = preg_replace('/[\*\#\_\[\]\(\)\{\}\<\>\"\'\`\~\|\+\=\^]/i', '', $meta_title);
            $meta_title = rtrim($meta_title, '.،:؛!؟،');
            
            // بررسی مناسب بودن عنوان
            $is_appropriate = true;
            foreach ($inappropriate_words as $word) {
                if (stripos($meta_title, $word) !== false) {
                    $is_appropriate = false;
                    break;
                }
            }
            
            if (!empty($meta_title) && strlen($meta_title) >= 10 && strlen($meta_title) <= 70 && $is_appropriate) {
                smart_admin_debug_log('Found explicit Meta Title: ' . $meta_title, 'INFO');
                return $meta_title;
            }
        }
    }
    
    // بررسی موضوع محتوا و تشخیص عنوان مناسب بر اساس آن
    $content_topic = '';
    if (!empty($main_topic)) {
        $content_topic = $main_topic;
    } else {
        // تلاش برای استخراج موضوع اصلی از محتوا
        $topic_patterns = array(
            '/(?:موضوع|درباره|در مورد|مقاله درباره)[:]\s*(.*?)(?:[\.\n]|$)/i',
            '/(?:این مقاله درباره|این مطلب در مورد|این محتوا درباره)[:]*\s*(.*?)(?:[\.\n]|$)/i'
        );
        
        foreach ($topic_patterns as $pattern) {
            if (preg_match($pattern, $content, $matches)) {
                $content_topic = trim(strip_tags($matches[1]));
                $content_topic = preg_replace('/[\*\#\_\[\]\(\)\{\}\<\>\"\'\`\~\|\+\=\^]/i', '', $content_topic);
                $content_topic = rtrim($content_topic, '.،:؛!؟،');
                break;
            }
        }
    }
    
    // الگوهای مختلف برای یافتن عنوان به ترتیب اولویت
    $patterns = array(
        // الگوی 1: عنوان در تگ h1
        '/<h1[^>]*>(.*?)<\/h1>/i',
        
        // الگوی 2: عنوان با علامت # (مارک‌داون)
        '/^#\s+(.*?)$/m',
        
        // الگوی 3: خط اول که با "عنوان:" یا "موضوع:" شروع می‌شود
        '/^(?:عنوان|موضوع)[:]\s*(.*?)$/im',
        
        // الگوی 4: خط اول که با "title:" شروع می‌شود
        '/^title[:]\s*(.*?)$/im',
        
        // الگوی 5: عنوان با علامت ## (مارک‌داون سطح 2) در خط اول
        '/^##\s+(.*?)$/m',
        
        // الگوی 6: عنوان با علامت ### (مارک‌داون سطح 3) در خط اول
        '/^###\s+(.*?)$/m',
        
        // الگوی 7: عنوان با علامت ** (مارک‌داون پررنگ) در خط اول
        '/^\*\*(.*?)\*\*$/m',
        
        // الگوی 8: عنوان در تگ strong در خط اول
        '/^<strong>(.*?)<\/strong>$/m'
    );
    
    // بررسی هر الگو
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $content, $matches)) {
            $title = trim(strip_tags($matches[1]));
            
            // حذف علامت‌های مارک‌داون و HTML از عنوان
            $title = preg_replace('/[\*\#\_\[\]\(\)\{\}\<\>\"\'\`\~\|\+\=\^]/i', '', $title);
            
            // حذف نقطه از انتهای عنوان
            $title = rtrim($title, '.،:؛!؟،');
            
            // بررسی مناسب بودن عنوان
            $is_appropriate = true;
            foreach ($inappropriate_words as $word) {
                if (stripos($title, $word) !== false) {
                    $is_appropriate = false;
                    break;
                }
            }
            
            // اگر عنوان معتبر است، آن را برگردان
            if (!empty($title) && strlen($title) >= 10 && strlen($title) <= 100 && $is_appropriate) {
                smart_admin_debug_log('Found SEO title: ' . $title, 'INFO');
                return $title;
            }
        }
    }
    
    // بررسی دقیق‌تر محتوا برای یافتن عنوان مناسب
    // اگر محتوا در مورد برنامه‌نویسی بک‌اند و فرانت‌اند است
    if (preg_match('/(بک.?اند|فرانت.?اند|back.?end|front.?end|full.?stack)/ui', $content)) {
        $programming_patterns = array(
            // عبارات رایج در مورد برنامه‌نویسی وب
            '/(?:تفاوت|مقایسه|فرق)(?:\s+(?:بین|میان))?\s+(.*?)(?:و|با)\s+(.*?)(?:چیست|کدام است|در چیست)/ui',
            '/(?:راهنمای|آموزش|معرفی)\s+(.*?)(?:برای مبتدیان|برای تازه‌کاران|از صفر تا صد)/ui',
            '/(?:چگونه|چطور)\s+(.*?)(?:را شروع کنیم|را یاد بگیریم|را آغاز کنیم)/ui'
        );
        
        foreach ($programming_patterns as $pattern) {
            if (preg_match($pattern, $content, $matches)) {
                // ساخت عنوان مناسب بر اساس الگوی یافت شده
                if (count($matches) >= 3) {
                    $title = "تفاوت " . trim($matches[1]) . " و " . trim($matches[2]) . ": راهنمای کامل";
                } elseif (count($matches) >= 2) {
                    $title = trim($matches[0]);
                }
                
                if (!empty($title) && strlen($title) >= 10) {
                    smart_admin_debug_log('Created programming-specific title: ' . $title, 'INFO');
                    return $title;
                }
            }
        }
        
        // اگر هنوز عنوان پیدا نشد و محتوا در مورد برنامه‌نویسی است
        if (preg_match('/(?:تفاوت|مقایسه|فرق).*(?:بک.?اند|فرانت.?اند|back.?end|front.?end)/ui', $content)) {
            return "تفاوت برنامه‌نویس بک‌اند و فرانت‌اند: راهنمای کامل برای انتخاب مسیر شغلی";
        }
    }
    // بررسی برای محتوای فناوری اطلاعات
    if (preg_match('/(?:Node\.?js|nodejs|javascript|js|php|python|java|react|vue|angular|laravel|django|wordpress|seo|api|database|server|deployment|deploy|hosting|vps|server|ubuntu|linux|windows|mac|git|github|docker|kubernetes|aws|azure|google cloud|cloud|ci\/cd|devops)/ui', $content)) {
        $tech_patterns = array(
            '/(?:آموزش|راهنمای|معرفی|توتوریال|tutorial)\s+(.*?)(?:در|با|برای|روی|on|with|for)\s+(.*?)(?:از صفر|step by step|گام به گام|step-by-step)/ui',
            '/(?:چگونه|چطور|how to)\s+(.*?)(?:را|را در|را روی|را با)\s+(.*?)(?:نصب|install|setup|configure|deploy|deployment)/ui',
            '/(?:راهنمای کامل|complete guide|comprehensive guide)\s+(.*?)(?:برای|for|on)\s+(.*?)/ui',
            '/(?:deploy|deployment|install|setup|configure)\s+(.*?)(?:on|with|for|در|روی|با)\s+(.*?)/ui'
        );
        
        foreach ($tech_patterns as $pattern) {
            if (preg_match($pattern, $content, $matches)) {
                if (count($matches) >= 3) {
                    $title = "آموزش " . trim($matches[1]) . " روی " . trim($matches[2]) . ": راهنمای کامل و تخصصی";
                } elseif (count($matches) >= 2) {
                    $title = trim($matches[0]);
                }
                
                if (!empty($title) && strlen($title) >= 10) {
                    smart_admin_debug_log('Created tech-specific title: ' . $title, 'INFO');
                    return $title;
                }
            }
        }
    }
    // بررسی برای محتوای گردشگری و سفر
    if (preg_match('/(?:سفر|گردشگری|توریسم|مسافرت|راهنمای سفر|travel|tourism|tourist|vacation|holiday|destination|visit)/ui', $content)) {
        $travel_patterns = array(
            '/(?:راهنمای|راهنما|guide)\s+(?:سفر|travel)\s+(?:به|to)\s+(.*?)(?:[\.،,\s]|$)/ui',
            '/(?:سفر|travel)\s+(?:به|to)\s+(.*?)(?:[\.،,\s]|$)/ui',
            '/(?:راهنمای کامل|complete guide)\s+(?:سفر|travel)\s+(?:به|to)\s+(.*?)(?:[\.،,\s]|$)/ui',
            '/(?:تجربه|experience)\s+(?:سفر|travel)\s+(?:به|to)\s+(.*?)(?:[\.،,\s]|$)/ui'
        );
        
        foreach ($travel_patterns as $pattern) {
            if (preg_match($pattern, $content, $matches)) {
                $destination = trim($matches[1]);
                if (!empty($destination)) {
                    // عنوان استاندارد بدون کلیشه‌ها و نشانه‌های تزئینی
                    $title = "سفر به " . $destination . " - نکات و جاذبه‌ها";
                    smart_admin_debug_log('Created travel-specific title: ' . $title, 'INFO');
                    return $title;
                }
            }
        }
        
        // اگر مقصد خاصی پیدا نشد، عنوان عمومی برای سفر
        if (preg_match('/(?:سفر|travel|گردشگری|tourism)/ui', $content)) {
            $title = "راهنمای سفر - نکات و برنامه پیشنهادی";
            smart_admin_debug_log('Created general travel title: ' . $title, 'INFO');
            return $title;
        }
    }
    
    // اگر عنوان پیدا نشد، پاراگراف اول محتوا را بررسی کن
    $paragraphs = preg_split('/\n\s*\n/', $content);
    if (!empty($paragraphs[0])) {
        $first_paragraph = $paragraphs[0];
        $lines = explode("\n", $first_paragraph);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // حذف علامت‌های مارک‌داون و HTML
            $line = strip_tags($line);
            $line = preg_replace('/[\*\#\_\[\]\(\)\{\}\<\>\"\'\`\~\|\+\=\^]/i', '', $line);
            $line = rtrim($line, '.،:؛!؟،');
            
            // بررسی مناسب بودن خط
            $is_appropriate = true;
            foreach ($inappropriate_words as $word) {
                if (stripos($line, $word) !== false) {
                    $is_appropriate = false;
                    break;
                }
            }
            
            // بررسی طول و کیفیت خط
            if (strlen($line) >= 20 && strlen($line) <= 100 && !preg_match('/^(https?|www|\d+\.)/', $line) && $is_appropriate) {
                smart_admin_debug_log('Using first paragraph line as title: ' . $line, 'INFO');
                return $line;
            }
        }
    }
    
    // اگر موضوع اصلی استخراج شده، از آن به عنوان عنوان استفاده کن
    if (!empty($content_topic)) {
        // بررسی مناسب بودن موضوع
        $is_appropriate = true;
        foreach ($inappropriate_words as $word) {
            if (stripos($content_topic, $word) !== false) {
                $is_appropriate = false;
                break;
            }
        }
        
        if ($is_appropriate) {
            smart_admin_debug_log('Using extracted topic as title: ' . $content_topic, 'INFO');
            return $content_topic;
        }
    }
    
    // اگر هیچ عنوان مناسبی پیدا نشد، عنوان پیش‌فرض برگردان
    smart_admin_debug_log('No suitable title found, using default', 'INFO');
    return 'محتوا تولید شده توسط دستیار هوشمند';
}

/**
 * استخراج پیوند یکتا (slug) بهینه برای SEO از پاسخ هوش مصنوعی
 * 
 * @param string $content محتوای تولید شده توسط هوش مصنوعی
 * @param string $title عنوان مقاله (اختیاری)
 * @param array $keywords کلمات کلیدی (اختیاری)
 * @return string پیوند یکتای بهینه شده
 */
function smart_admin_extract_seo_slug($content, $title = '', $keywords = array()) {
    // لاگ برای دیباگ
    smart_admin_debug_log('Extracting SEO slug from AI content', 'INFO');
    
    // بررسی اگر محتوا حاوی پیوند یکتای (Slug) صریح است
    $slug_meta_patterns = array(
        // الگوهای مختلف برای پیوند یکتا
        '/(?:پیوند یکتا|slug|permalink|url|آدرس سئو|آدرس SEO|SEO URL)[:]\s*([\w\-\p{L}]+)/ui',
        '/(?:پیوند یکتا|slug|permalink|url|آدرس سئو|آدرس SEO|SEO URL)[:]\s*[\'"]?(.*?)[\'"]?(?:[\.\n]|$)/ui',
        '/<slug>(.*?)<\/slug>/i',
        '/slug[=:]\s*[\'"]?(.*?)[\'"]?(?:[\.\n]|$)/i'
    );
    
    foreach ($slug_meta_patterns as $pattern) {
        if (preg_match($pattern, $content, $matches)) {
            $meta_slug = trim($matches[1]);
            if (!empty($meta_slug)) {
                smart_admin_debug_log('Found explicit slug in content: ' . $meta_slug, 'INFO');
                // اطمینان از اینکه پیوند یکتا معتبر است
                $slug = sanitize_title($meta_slug);
                return $slug;
            }
        }
    }
    
    // اگر عنوان ارسال نشده، آن را از محتوا استخراج کن
    if (empty($title)) {
        $title = smart_admin_extract_seo_title($content);
    }
    
    // بررسی زبان محتوا (فارسی یا انگلیسی)
    $is_persian = preg_match('/[\x{0600}-\x{06FF}]/u', $title . ' ' . $content);
    smart_admin_debug_log('Content language is: ' . ($is_persian ? 'Persian' : 'English'), 'INFO');
    
    // تشخیص موضوع محتوا و ایجاد پیوند یکتای مناسب
    
    // بررسی اگر محتوا در مورد برنامه‌نویسی بک‌اند و فرانت‌اند است
    if (preg_match('/(بک.?اند|فرانت.?اند|back.?end|front.?end|full.?stack)/ui', $content)) {
        // اگر محتوا در مورد تفاوت بک‌اند و فرانت‌اند است
        if (preg_match('/(?:تفاوت|مقایسه|فرق).*(?:بک.?اند|فرانت.?اند|back.?end|front.?end)/ui', $content)) {
            if ($is_persian) {
                $slug = 'تفاوت-برنامه-نویس-بک-اند-فرانت-اند';
            } else {
                $slug = 'backend-vs-frontend-developer-differences';
            }
            smart_admin_debug_log('Created programming-specific slug: ' . $slug, 'INFO');
            return $slug;
        }
    }
    // بررسی اگر محتوا در مورد مهاجرت و سفر است
    if (preg_match('/(مهاجرت|سفر|اقامت|ویزا|پاسپورت|کویت|دبی|ترکیه|کانادا|آمریکا|اروپا|استرالیا)/ui', $content)) {
        // اگر محتوا در مورد راهنمای مهاجرت به کشور خاصی است
        if (preg_match('/(?:راهنمای|آموزش|روش|چگونه|چطور).*(?:مهاجرت|سفر|اقامت).*(?:به|در)\s+(.*?)(?:[\.،,\s]|$)/ui', $content, $matches)) {
            $country = trim($matches[1]);
            if (!empty($country)) {
                if ($is_persian) {
                    $slug = 'راهنمای-مهاجرت-به-' . $country;
                } else {
                    $slug = 'immigration-guide-to-' . $country;
                }
                smart_admin_debug_log('Created immigration-specific slug: ' . $slug, 'INFO');
                return sanitize_title($slug);
            }
        }
        
        // اگر محتوا یا عنوان در مورد قدم به قدم مهاجرت است
        if (preg_match('/(?:قدم به قدم|گام به گام|مرحله به مرحله).*(?:مهاجرت|سفر|اقامت)/ui', $content . ' ' . $title) ||
            preg_match('/(?:چطور|چگونه).*(?:مهاجرت|سفر|اقامت).*(?:کنم|کنیم|کنید)/ui', $title)) {
            if ($is_persian) {
                // بررسی الگوی دقیق "راهنمای قدم به قدم چطور به کویت مهاجرت کنم"
                if (preg_match('/راهنمای قدم به قدم چطور به (.*?) مهاجرت کنم/ui', $title, $exact_matches)) {
                    $country = trim($exact_matches[1]);
                    $slug = 'راهنمای-قدم-به-قدم-مهاجرت-به-' . $country;
                }
                // استخراج نام کشور از عنوان یا محتوا
                elseif (preg_match('/(کویت|دبی|ترکیه|کانادا|آمریکا|اروپا|استرالیا|امارات)/ui', $title . ' ' . $content, $country_matches)) {
                    $country = trim($country_matches[1]);
                    $slug = 'راهنمای-قدم-به-قدم-مهاجرت-به-' . $country;
                } else {
                    $slug = 'راهنمای-قدم-به-قدم-مهاجرت';
                }
            } else {
                if (preg_match('/(kuwait|dubai|turkey|canada|usa|europe|australia|uae)/ui', $title . ' ' . $content, $country_matches)) {
                    $country = strtolower(trim($country_matches[1]));
                    $slug = 'step-by-step-immigration-guide-to-' . $country;
                } else {
                    $slug = 'step-by-step-immigration-guide';
                }
            }
            smart_admin_debug_log('Created step-by-step immigration slug: ' . $slug, 'INFO');
            return sanitize_title($slug);
        }
    }
    // بررسی برای محتوای گردشگری و سفر
    if (preg_match('/(?:سفر|گردشگری|توریسم|مسافرت|راهنمای سفر|travel|tourism|tourist|vacation|holiday|destination|visit)/ui', $content)) {
        $travel_slug_patterns = array(
            '/(?:راهنمای|راهنما|guide)\s+(?:سفر|travel)\s+(?:به|to)\s+(.*?)(?:[\.،,\s]|$)/ui',
            '/(?:سفر|travel)\s+(?:به|to)\s+(.*?)(?:[\.،,\s]|$)/ui',
            '/(?:راهنمای کامل|complete guide)\s+(?:سفر|travel)\s+(?:به|to)\s+(.*?)(?:[\.،,\s]|$)/ui'
        );
        
        foreach ($travel_slug_patterns as $pattern) {
            if (preg_match($pattern, $content, $matches)) {
                $destination = trim($matches[1]);
                if (!empty($destination)) {
                    if ($is_persian) {
                        $slug = 'راهنمای-سفر-به-' . $destination;
                    } else {
                        $slug = 'travel-guide-to-' . $destination;
                    }
                    smart_admin_debug_log('Created travel-specific slug: ' . $slug, 'INFO');
                    return sanitize_title($slug);
                }
            }
        }
        
        // اگر مقصد خاصی پیدا نشد، پیوند یکتای عمومی برای سفر
        if ($is_persian) {
            $slug = 'راهنمای-سفر-کامل';
        } else {
            $slug = 'complete-travel-guide';
        }
        smart_admin_debug_log('Created general travel slug: ' . $slug, 'INFO');
        return sanitize_title($slug);
    }
    
    // اگر پیوند یکتای صریح پیدا نشد، از عنوان استفاده کن
    if (!empty($title)) {
        // برای محتوای فارسی
        if ($is_persian) {
            // حذف کلمات اضافی و حروف ربط از عنوان
            $title = preg_replace('/\b(و|یا|با|به|در|از|که|را|برای|این|آن|چه|چرا|چگونه|کدام)\b/ui', ' ', $title);
            
            // حداکثر 5 کلمه مهم از عنوان را استخراج کن
            $words = array_filter(preg_split('/\s+/u', $title), function($word) {
                return mb_strlen($word, 'UTF-8') > 2; // فقط کلمات با بیش از 2 حرف
            });
            
            $slug_words = array_slice($words, 0, 5);
            $slug = implode('-', $slug_words);
            
            // اطمینان از اینکه پیوند یکتا معتبر است
            $slug = sanitize_title($slug);
            
            smart_admin_debug_log('Created Persian slug from title: ' . $slug, 'INFO');
            return $slug;
        } 
        // برای محتوای انگلیسی
        else {
            // حذف کلمات اضافی از عنوان
            $title = preg_replace('/\b(and|or|the|a|an|in|on|at|by|for|with|to|of|is|are|was|were|be|been|being)\b/i', ' ', $title);
            
            // استفاده از تابع وردپرس برای ساخت پیوند یکتا از عنوان
            $slug = sanitize_title($title);
            
            // محدود کردن طول پیوند یکتا
            if (strlen($slug) > 60) {
                $slug = substr($slug, 0, 60);
                // اطمینان از اینکه در وسط یک کلمه قطع نشده
                $slug = preg_replace('/-[^-]*$/', '', $slug);
            }
            
            smart_admin_debug_log('Created English slug from title: ' . $slug, 'INFO');
            return $slug;
        }
    }
    
    // اگر عنوان خالی بود، از کلمات کلیدی استفاده کن
    if (!empty($keywords) && is_array($keywords)) {
        // حذف کلمات اضافی از کلمات کلیدی
        $filtered_keywords = array_filter($keywords, function($keyword) {
            // حذف کلمات کوتاه و کلمات ربط
            $stopwords = array('و', 'یا', 'با', 'به', 'در', 'از', 'که', 'را', 'برای', 'این', 'آن', 
                              'and', 'or', 'the', 'a', 'an', 'in', 'on', 'at', 'by', 'for', 'with', 'to');
            return !in_array(strtolower($keyword), $stopwords) && mb_strlen($keyword, 'UTF-8') > 2;
        });
        
        if (!empty($filtered_keywords)) {
            $primary_keyword = sanitize_title($filtered_keywords[0]);
            smart_admin_debug_log('Using filtered primary keyword for slug: ' . $primary_keyword, 'INFO');
            return $primary_keyword;
        }
    }
    
    // اگر محتوا در مورد برنامه‌نویسی است اما هیچ پیوند یکتایی پیدا نشد
    if (preg_match('/(برنامه.?نویس|توسعه.?دهنده|developer|programmer|coding|programming)/ui', $content)) {
        if ($is_persian) {
            return 'راهنمای-برنامه-نویسی-وب';
        } else {
            return 'web-development-guide';
        }
    }
    
    // اگر هیچ منبعی برای ساخت پیوند یکتا نبود، یک پیوند یکتا تصادفی بساز
    $random_slug = 'ai-content-' . substr(md5(uniqid(mt_rand(), true)), 0, 8);
    smart_admin_debug_log('No suitable slug source found, using random: ' . $random_slug, 'INFO');
    return $random_slug;
}
// تابع راهنمایی‌های پیشرفته تولید محتوا
function smart_admin_advanced_content_guidelines($prompt) {
    $advanced_guidelines = [
        '• **اعتبار محتوا:**',
        '  - از منابع علمی و تخصصی معتبر استفاده شود.',
        '  - هر ادعا باید با حداقل یک منبع معتبر پشتیبانی شود.',
        '  - در صورت امکان، از نظرات متخصصان و خبرگان استفاده شود.',
        
        '• **ساختار محتوا:**',
        '  - از ساختار "هرم معکوس" در نگارش استفاده شود.',
        '  - مهم‌ترین اطلاعات در ابتدای هر بخش قرار گیرد.',
        '  - از پاراگراف‌های کوتاه (۳-۴ جمله) استفاده شود.',
        
        '• **غنای محتوا:**',
        '  - حداقل ۳ مثال عملی یا مطالعه موردی ارائه شود.',
        '  - آمار و ارقام دقیق با ذکر منبع استفاده شود.',
        '  - پیشنهادات کاربردی و راهکارهای عملی گنجانده شود.',
        
        '• **سئو و خوانایی:**',
        '  - از کلمات کلیدی به صورت طبیعی استفاده شود.',
        '  - از تکرار بیش از حد کلمات کلیدی (Keyword Stuffing) پرهیز شود.',
        '  - از تگ‌های هدینگ (H2, H3) برای ساختاربندی استفاده شود.',
        
        '• **تنوع محتوا:**',
        '  - از لیست‌های شماره‌دار و بولت پوینت استفاده شود.',
        '  - متون مهم با تگ <strong> مشخص شوند.',
        '  - از نقل قول‌های تخصصی برای افزایش اعتبار استفاده شود.',
        
        '• **اصالت محتوا:**',
        '  - محتوا کاملاً اختصاصی و منحصر به فرد باشد.',
        '  - از کپی‌برداری مستقیم یا چرخشی محتوا جداً پرهیز شود.',
        '  - ارزش افزوده برای خواننده ایجاد شود.'
    ];
    
    return $prompt . "\n\n" . implode("\n", $advanced_guidelines);
}

// اضافه کردن فیلتر برای اعمال راهنمایی‌ها
add_filter('smart_admin_prompt_generation', 'smart_admin_advanced_content_guidelines', 25);
// تابع رفع محدودیت تولید محتوای HTML
function smart_admin_html_content_generator($prompt) {
    $html_guidelines = [
        '• محتوا باید با استانداردهای HTML5 تولید شود.',
        '• از تگ‌های معنایی HTML استفاده کن (h1, h2, p, strong, etc.).',
        '• محتوا باید کاملاً قابل خواندن و با کیفیت باشد.',
        '• از ساختار استاندارد HTML برای تولید محتوا استفاده کن.'
    ];
    
    return $prompt . "\n\n" . implode("\n", $html_guidelines);
}

// اضافه کردن فیلتر برای اعمال راهنمایی‌های HTML
add_filter('smart_admin_prompt_generation', 'smart_admin_html_content_generator', 30);
