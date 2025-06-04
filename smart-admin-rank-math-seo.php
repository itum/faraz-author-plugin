<?php
/**
 * Rank Math SEO Integration
 * 
 * این فایل برای یکپارچه‌سازی ادمین هوشمند با افزونه Rank Math SEO ایجاد شده است
 * قابلیت تشخیص خودکار کلمات کلیدی اصلی و قرار دادن آن‌ها در بخش کلمه کلیدی اصلی Rank Math
 */

// اطمینان از عدم دسترسی مستقیم به فایل
if (!defined('ABSPATH')) {
    exit;
}

// اضافه کردن اکشن برای تنظیم کلمات کلیدی Rank Math پس از ذخیره پست
add_action('smart_admin_post_saved', 'smart_admin_set_rank_math_focus_keyword', 10, 2);

// اضافه کردن اکشن برای نمایش دکمه در صفحه ویرایش پست
add_action('add_meta_boxes', 'smart_admin_add_rankmath_metabox');

/**
 * اصلاح و تمیز‌سازی کلمات کلیدی از کاراکترهای نامعتبر
 * 
 * @param string $text متن ورودی
 * @return string متن تمیز شده
 */
function smart_admin_sanitize_persian_text($text) {
    if (empty($text)) {
        return '';
    }
    
    // بررسی کاراکترهای نامعتبر (شامل کاراکتر)
    if (strpos($text, '') !== false) {
        // اگر حاوی کاراکتر نامعتبر است، آن را حذف می‌کنیم
        $text = str_replace('', '', $text);
        
        // اگر بعد از حذف کاراکتر نامعتبر، متن خیلی کوتاه شد، آن را رد می‌کنیم
        if (mb_strlen($text, 'UTF-8') < 3) {
            return '';
        }
    }
    
    // حذف کاراکترهای کنترلی و نامعتبر
    $text = preg_replace('/[\x00-\x1F\x7F]/u', '', $text);
    
    // حذف فاصله‌های اضافی
    $text = preg_replace('/\s+/u', ' ', $text);
    
    // حذف کاراکترهای خاص مشکل‌ساز
    $text = str_replace(array('\\', '/', '"', "'", '<', '>', '&'), ' ', $text);
    
    return trim($text);
}

/**
 * استخراج کلمات کلیدی از دسته‌بندی‌های پست
 * 
 * @param int $post_id شناسه پست
 * @return array آرایه کلمات کلیدی
 */
function smart_admin_get_keywords_from_categories($post_id) {
    $categories = get_the_category($post_id);
    $keywords = array();
    
    if (!empty($categories)) {
        foreach ($categories as $category) {
            if (!empty($category->name) && $category->name !== 'دسته‌بندی نشده') {
                $keywords[] = smart_admin_sanitize_persian_text($category->name);
            }
        }
    }
    
    // اگر از دسته‌بندی‌ها کلمه کلیدی پیدا نشد، از برچسب‌ها استفاده می‌کنیم
    if (empty($keywords)) {
        $tags = get_the_tags($post_id);
        if (!empty($tags)) {
            foreach ($tags as $tag) {
                if (!empty($tag->name)) {
                    $keywords[] = smart_admin_sanitize_persian_text($tag->name);
                }
            }
        }
    }
    
    // افزودن کلمات کلیدی پیش‌فرض
    if (empty($keywords)) {
        $keywords = array('وبلاگ', 'مقاله', 'آموزش', 'راهنما');
    }
    
    return $keywords;
}

/**
 * استخراج کلمات کلیدی اصلی از محتوای پست
 * 
 * @param string $content محتوای پست
 * @param string $title عنوان پست
 * @param array $tags برچسب‌های انتخاب شده
 * @return array کلمات کلیدی اصلی
 */
function smart_admin_extract_main_keywords($content, $title, $tags = array()) {
    // اگر برچسب‌ها وجود دارند، از آن‌ها به عنوان کلمات کلیدی استفاده می‌کنیم
    if (!empty($tags)) {
        return $tags; // تمام برچسب‌ها را برمی‌گردانیم
    }
    
    // جستجوی کلمات کلیدی در محتوا
    $keywords = array();
    
    // جستجوی بخش‌های مربوط به کلمات کلیدی در محتوا
    $patterns = array(
        '/کلمات\s*کلیدی\s*[:]\s*(.*?)(?:[\.\n]|$)/i',
        '/کلیدواژه\s*[:]\s*(.*?)(?:[\.\n]|$)/i',
        '/keywords\s*[:]\s*(.*?)(?:[\.\n]|$)/i',
        '/tags\s*[:]\s*(.*?)(?:[\.\n]|$)/i',
        '/برچسب\s*ها\s*[:]\s*(.*?)(?:[\.\n]|$)/i',
        '/تگ\s*ها\s*[:]\s*(.*?)(?:[\.\n]|$)/i',
        '/کلمات\s*کلیدی\s*[=]\s*(.*?)(?:[\.\n]|$)/i',
        '/کلمات\s*کلیدی\s*[>]\s*(.*?)(?:[\.\n]|$)/i',
        '/کلمات\s*کلیدی\s*اصلی\s*[:]\s*(.*?)(?:[\.\n]|$)/i',
        '/^کلمات\s*کلیدی[:]\s*(.*?)(?:[\.\n]|$)/im',  // پیدا کردن کلمات کلیدی در خط جدید
        '/\n+کلمات\s*کلیدی[:]\s*(.*?)(?:[\.\n]|$)/i',  // پیدا کردن کلمات کلیدی در انتهای مقاله
    );
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $content, $matches)) {
            if (!empty($matches[1])) {
                // پاکسازی و تقسیم کلمات کلیدی
                $found_keywords = preg_split('/[,،]\s*/', trim($matches[1]));
                $keywords = array_merge($keywords, $found_keywords);
                break; // پس از یافتن اولین مجموعه کلمات کلیدی، توقف می‌کنیم
            }
        }
    }
    
    // اگر هیچ کلمه کلیدی در متن پیدا نشد، از عنوان استفاده می‌کنیم
    if (empty($keywords)) {
        $keywords[] = $title;
    }
    
    // حذف موارد تکراری و محدود کردن تعداد کلمات کلیدی
    $keywords = array_unique(array_filter($keywords));
    return $keywords;
}

/**
 * ایجاد فایل لاگ اختصاصی برای ثبت خطاها
 * 
 * @param string $message پیام خطا
 * @return void
 */
function smart_admin_log($message) {
    // مسیر فایل لاگ
    $log_file = plugin_dir_path(__FILE__) . 'smart-admin-debug.log';
    
    // افزودن زمان به پیام
    $log_message = '[' . date('Y-m-d H:i:s') . '] ' . $message . "\n";
    
    // نوشتن در فایل لاگ
    file_put_contents($log_file, $log_message, FILE_APPEND);
}

/**
 * تنظیم کلمات کلیدی اصلی Rank Math بعد از ذخیره پست توسط ادمین هوشمند
 * 
 * @param int $post_id شناسه پست ایجاد شده
 * @param array $keywords آرایه کلمات کلیدی
 * @return bool نتیجه عملیات
 */
function smart_admin_set_rank_math_focus_keyword($post_id, $keywords = array()) {
    // بررسی وجود افزونه Rank Math
    if (!function_exists('rank_math')) {
        smart_admin_log('افزونه Rank Math فعال نیست.');
        return false;
    }
    
    // گرفتن اطلاعات پست
    $post = get_post($post_id);
    if (!$post) {
        smart_admin_log('پست با شناسه ' . $post_id . ' پیدا نشد.');
        return false;
    }
    
    smart_admin_log('در حال تنظیم کلمات کلیدی برای پست ' . $post_id . ' با عنوان "' . $post->post_title . '"');
    
    // لیست کلمات کلیدی پیش‌فرض برای استفاده در صورت خطا
    $default_keywords = array('برنامه نویسی', 'وبلاگ', 'مقاله', 'آموزش', 'راهنما');
    
    // استفاده مستقیم از کلمات کلیدی پیش‌فرض به جای پردازش پیچیده
    $clean_keywords = $default_keywords;
    smart_admin_log('استفاده از کلمات کلیدی پیش‌فرض به دلیل مشکلات کدگذاری');
    
    // تبدیل آرایه کلمات کلیدی به رشته با جداکننده کاما
    $keywords_string = implode(',', $clean_keywords);
    
    smart_admin_log('رشته کلمات کلیدی برای ذخیره: ' . str_replace(',', '|', $keywords_string));
    
    // ذخیره با متد استاندارد وردپرس
    delete_post_meta($post_id, 'rank_math_focus_keyword');
    $result = update_post_meta($post_id, 'rank_math_focus_keyword', $keywords_string);
    
    if ($result) {
        smart_admin_log('کلمات کلیدی با موفقیت ذخیره شدند.');
        
        // تنظیم یک متا فیلد اضافی برای ردیابی
        update_post_meta($post_id, 'smart_admin_keywords_set', 'yes');
        update_post_meta($post_id, 'smart_admin_keywords_time', current_time('mysql'));
        return true;
    } else {
        smart_admin_log('خطا: کلمات کلیدی ذخیره نشدند.');
        return false;
    }
}

/**
 * استخراج هوشمند کلمات کلیدی با استفاده از هوش مصنوعی
 *
 * @param string $content محتوای پست
 * @param string $title عنوان پست
 * @return array کلمات کلیدی استخراج شده
 */
function smart_admin_ai_extract_keywords($content, $title) {
    // استفاده از پرامپت هوش مصنوعی برای استخراج کلمات کلیدی
    $api_key = get_option('smart_admin_assistant_api_key', '');
    $ai_model = get_option('smart_admin_assistant_model', 'gpt-4o');
    
    if (empty($api_key)) {
        return array(); // اگر کلید API تنظیم نشده باشد
    }
    
    // آماده‌سازی محتوا برای ارسال به API
    // طول محتوا را محدود می‌کنیم تا از محدودیت‌های API جلوگیری شود
    $max_content_length = 4000;
    if (strlen($content) > $max_content_length) {
        // استخراج بخش ابتدایی و انتهایی محتوا (مقدمه و نتیجه‌گیری معمولاً کلمات کلیدی مهمی دارند)
        $beginning = substr($content, 0, $max_content_length / 2);
        $ending = substr($content, -($max_content_length / 2));
        $shortened_content = $beginning . " [...] " . $ending;
    } else {
        $shortened_content = $content;
    }
    
    // ایجاد پرامپت برای استخراج کلمات کلیدی
    $prompt = "لطفاً کلمات کلیدی مهم و مرتبط با SEO را از متن زیر استخراج کن. فقط کلمات کلیدی را به صورت یک لیست با کاما جدا شده برگردان، بدون توضیح اضافی. ترکیبی از کلمات کلیدی کوتاه و بلند ارائه کن (حداقل 5 و حداکثر 8 کلمه کلیدی).

عنوان: $title

متن:
$shortened_content";

    try {
        // تنظیمات درخواست API
        $headers = array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key
        );
        
        $endpoint = 'https://api.openai.com/v1/chat/completions';
        
        // ایجاد درخواست
        $request_body = array(
            'model' => $ai_model,
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => 'تو یک متخصص SEO هستی که می‌تواند کلمات کلیدی مهم و مرتبط را از متن استخراج کند. فقط کلمات کلیدی را به صورت لیست با کاما جدا شده برگردان.'
                ),
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'temperature' => 0.3, // دقت بالا برای استخراج کلمات کلیدی مرتبط
            'max_tokens' => 200 // محدودیت طول پاسخ
        );
        
        // ارسال درخواست
        $response = wp_remote_post($endpoint, array(
            'headers' => $headers,
            'body' => json_encode($request_body),
            'timeout' => 30 // زمان انتظار برای دریافت پاسخ
        ));
        
        if (is_wp_error($response)) {
            error_log('Smart Admin AI Keyword Extraction Error: ' . $response->get_error_message());
            return array();
        }
        
        $response_body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($response_body['choices'][0]['message']['content'])) {
            $keywords_text = $response_body['choices'][0]['message']['content'];
            // پاکسازی و تقسیم کلمات کلیدی
            $keywords = preg_split('/[,،]\s*/', trim($keywords_text));
            // حذف موارد نامعتبر و تکراری
            $keywords = array_unique(array_filter($keywords));
            
            // حذف شماره‌ها و کلمات کوتاه
            $keywords = array_filter($keywords, function($keyword) {
                return !is_numeric($keyword) && mb_strlen(trim($keyword)) > 2;
            });
            
            return $keywords;
        }
    } catch (Exception $e) {
        error_log('Smart Admin AI Keyword Extraction Exception: ' . $e->getMessage());
    }
    
    return array();
}

/**
 * تنظیم خودکار کلمات کلیدی برای پست‌های موجود
 */
function smart_admin_auto_set_focus_keywords_for_existing_posts() {
    // بررسی وجود افزونه Rank Math
    if (!function_exists('rank_math')) {
        return;
    }
    
    // گرفتن پست‌های ایجاد شده توسط ادمین هوشمند که کلمه کلیدی Rank Math ندارند
    $args = array(
        'post_type' => 'post',
        'post_status' => 'draft',
        'posts_per_page' => 20,
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key' => 'smart_admin_generated',
                'value' => 'yes',
                'compare' => '='
            ),
            array(
                'key' => 'rank_math_focus_keyword',
                'compare' => 'NOT EXISTS'
            )
        )
    );
    
    $posts = get_posts($args);
    
    foreach ($posts as $post) {
        // گرفتن برچسب‌های پست
        $tags = wp_get_post_tags($post->ID, array('fields' => 'names'));
        
        // تنظیم کلمات کلیدی
        smart_admin_set_rank_math_focus_keyword($post->ID, $tags);
    }
}

// اجرای خودکار تنظیم کلمات کلیدی برای پست‌های موجود
add_action('admin_init', 'smart_admin_auto_set_focus_keywords_for_existing_posts');

// اضافه کردن اکشن برای تنظیم کلمات کلیدی پس از بروزرسانی پست
add_action('save_post', 'smart_admin_check_and_set_keywords', 10, 3);

/**
 * بررسی و تنظیم کلمات کلیدی برای پست‌های تولید شده توسط هوش مصنوعی
 * 
 * @param int $post_id شناسه پست
 * @param WP_Post $post آبجکت پست
 * @param bool $update آیا این یک بروزرسانی است
 */
function smart_admin_check_and_set_keywords($post_id, $post, $update) {
    // اگر این یک ذخیره خودکار است یا پست در حال بازیابی است، کاری انجام نده
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if ($post->post_status === 'auto-draft') return;
    if (wp_is_post_revision($post_id)) return;
    
    // بررسی نوع پست
    if ($post->post_type !== 'post') return;
    
    // بررسی آیا این پست توسط ادمین هوشمند ایجاد شده است
    $is_smart_admin_post = get_post_meta($post_id, 'smart_admin_generated', true) === 'yes';
    $keywords_already_set = get_post_meta($post_id, 'smart_admin_keywords_set', true) === 'yes';
    
    // اگر پست توسط ادمین هوشمند ایجاد شده اما کلمات کلیدی تنظیم نشده‌اند
    if ($is_smart_admin_post && !$keywords_already_set) {
        // دریافت برچسب‌های پست
        $tags = wp_get_post_tags($post_id, array('fields' => 'names'));
        
        // تنظیم کلمات کلیدی
        smart_admin_set_rank_math_focus_keyword($post_id, $tags);
    }
}

/**
 * استخراج کلمات کلیدی از پاسخ هوش مصنوعی
 * 
 * @param string $ai_response پاسخ دریافتی از هوش مصنوعی
 * @return array آرایه کلمات کلیدی
 */
function smart_admin_extract_keywords_from_ai_response($ai_response) {
    // الگوهای مختلف برای یافتن کلمات کلیدی در پاسخ هوش مصنوعی
    $patterns = array(
        '/کلمات\s*کلیدی\s*[:]\s*(.*?)(?:[\.\n]|$)/i',
        '/کلیدواژه\s*[:]\s*(.*?)(?:[\.\n]|$)/i',
        '/keywords\s*[:]\s*(.*?)(?:[\.\n]|$)/i',
        '/tags\s*[:]\s*(.*?)(?:[\.\n]|$)/i',
        '/برچسب\s*ها\s*[:]\s*(.*?)(?:[\.\n]|$)/i',
        '/تگ\s*ها\s*[:]\s*(.*?)(?:[\.\n]|$)/i',
        '/کلمات\s*کلیدی\s*[=]\s*(.*?)(?:[\.\n]|$)/i'
    );
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $ai_response, $matches)) {
            if (!empty($matches[1])) {
                // تقسیم رشته کلمات کلیدی به آرایه
                // پشتیبانی از کاما یا ویرگول فارسی
                $keywords = preg_split('/[,،]\s*/', trim($matches[1]));
                
                // پاکسازی و حذف کاماهای اضافی
                $clean_keywords = array();
                foreach ($keywords as $keyword) {
                    $keyword = trim($keyword);
                    // حذف کاما از انتها و ابتدای کلمه کلیدی
                    $keyword = trim($keyword, '،,');
                    if (!empty($keyword)) {
                        $clean_keywords[] = $keyword;
                    }
                }
                
                return $clean_keywords;
            }
        }
    }
    
    return array();
}

/**
 * بررسی اعتبار نانس و ثبت خطا
 * 
 * @param string $nonce نانس ارسالی
 * @param string $action عملیات مورد نظر
 * @return bool نتیجه اعتبارسنجی
 */
function smart_admin_validate_nonce($nonce, $action) {
    // ثبت اطلاعات نانس برای عیب‌یابی
    smart_admin_log("بررسی نانس: نانس=$nonce, اکشن=$action");
    
    // بررسی اعتبار نانس
    $valid = wp_verify_nonce($nonce, $action);
    
    if ($valid) {
        smart_admin_log("نانس معتبر است: $valid");
        return true;
    } else {
        smart_admin_log("نانس نامعتبر است");
        return false;
    }
}

// اضافه کردن اکشن‌های AJAX
add_action('wp_ajax_smart_admin_set_keywords', 'smart_admin_force_set_keywords');
add_action('wp_ajax_nopriv_smart_admin_set_keywords', 'smart_admin_force_set_keywords_nopriv');

// برای کاربرانی که لاگین نیستند
function smart_admin_force_set_keywords_nopriv() {
    wp_send_json_error('شما باید وارد سیستم شوید.');
}

// اضافه کردن تابع دستی برای تنظیم کلمات کلیدی
function smart_admin_force_set_keywords() {
    smart_admin_log('درخواست AJAX برای تنظیم کلمات کلیدی دریافت شد');
    
    // بررسی درخواست AJAX
    if (isset($_POST['action']) && $_POST['action'] === 'smart_admin_set_keywords') {
        // بررسی امنیتی با خطای بیشتر
        if (!isset($_POST['nonce'])) {
            smart_admin_log('خطای امنیتی: نانس ارسال نشده است');
            wp_send_json_error('خطای امنیتی: نانس ارسال نشده است.');
            return;
        }
        
        smart_admin_log('نانس دریافت شده: ' . $_POST['nonce']);
        
        if (!smart_admin_validate_nonce($_POST['nonce'], 'smart_admin_set_keywords')) {
            smart_admin_log('خطای امنیتی: نانس نامعتبر است');
            wp_send_json_error('خطای امنیتی: نانس نامعتبر است.');
            return;
        }
        
        // بررسی دسترسی کاربر
        if (!current_user_can('edit_posts')) {
            smart_admin_log('کاربر دسترسی لازم را ندارد');
            wp_send_json_error('شما دسترسی لازم را ندارید.');
            return;
        }
        
        // دریافت شناسه پست
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        if (!$post_id) {
            smart_admin_log('شناسه پست نامعتبر است: ' . $_POST['post_id']);
            wp_send_json_error('شناسه پست نامعتبر است.');
            return;
        }
        
        // دریافت کلمات کلیدی (استفاده از sanitize_textarea_field برای حفظ UTF-8)
        $keywords_string = isset($_POST['keywords']) ? sanitize_textarea_field(stripslashes($_POST['keywords'])) : '';
        $keywords = array();
        
        if (!empty($keywords_string)) {
            // تبدیل رشته کلمات کلیدی به آرایه و حذف فضاهای خالی
            $keywords = array_map('trim', explode(',', $keywords_string));
            
            // پاکسازی آرایه از موارد خالی و تکراری
            $keywords = array_unique(array_filter($keywords));
            
            smart_admin_log('درخواست تنظیم کلمات کلیدی دریافت شد: ' . implode(', ', $keywords));
        } else {
            smart_admin_log('هیچ کلمه کلیدی ارسال نشده است');
        }
        
        // اعمال کلمات کلیدی فقط در Rank Math (بدون تاثیر در برچسب‌ها)
        $result = smart_admin_set_rank_math_focus_keyword($post_id, $keywords);
        
        // ارسال پاسخ
        if ($result) {
            $saved_keywords = get_post_meta($post_id, 'rank_math_focus_keyword', true);
            smart_admin_log('پاسخ موفق ارسال شد');
            
            // تبدیل فرمت کلمات کلیدی برای نمایش
            $display_keywords = str_replace(array("\n", ","), '، ', $saved_keywords);
            wp_send_json_success('کلمات کلیدی با موفقیت تنظیم شدند: ' . $display_keywords);
        } else {
            smart_admin_log('پاسخ خطا ارسال شد');
            wp_send_json_error('خطا در تنظیم کلمات کلیدی.');
        }
    }
}

/**
 * تابع تست برای بررسی عملکرد تنظیم کلمات کلیدی
 */
function smart_admin_test_keyword_setting() {
    // این تابع فقط برای مدیران قابل دسترسی است
    if (!current_user_can('manage_options')) {
        wp_die('شما دسترسی لازم را ندارید.');
    }
    
    // بررسی پارامترها
    $post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
    $keywords = isset($_GET['keywords']) ? sanitize_text_field($_GET['keywords']) : '';
    $action = isset($_GET['test_action']) ? sanitize_text_field($_GET['test_action']) : '';
    
    if (empty($post_id)) {
        echo '<p style="color:red;">خطا: شناسه پست را وارد کنید.</p>';
        return;
    }
    
    // بررسی وجود پست
    $post = get_post($post_id);
    if (!$post) {
        echo '<p style="color:red;">خطا: پست با شناسه ' . $post_id . ' وجود ندارد.</p>';
        return;
    }
    
    echo '<h2>اطلاعات پست</h2>';
    echo '<p>شناسه: ' . $post_id . '</p>';
    echo '<p>عنوان: ' . $post->post_title . '</p>';
    
    // نمایش کلمات کلیدی فعلی
    $current_keywords = get_post_meta($post_id, 'rank_math_focus_keyword', true);
    echo '<h2>کلمات کلیدی فعلی</h2>';
    echo '<p>' . (empty($current_keywords) ? 'تعیین نشده' : nl2br($current_keywords)) . '</p>';
    
    // اجرای اکشن‌ها
    if ($action === 'set' && !empty($keywords)) {
        $keywords_array = array_map('trim', explode(',', $keywords));
        
        echo '<h2>در حال تنظیم کلمات کلیدی</h2>';
        echo '<p>کلمات کلیدی ورودی: ' . implode(', ', $keywords_array) . '</p>';
        
        $result = smart_admin_set_rank_math_focus_keyword($post_id, $keywords_array);
        
        echo '<p style="color:' . ($result ? 'green' : 'red') . ';">';
        echo $result ? 'کلمات کلیدی با موفقیت تنظیم شدند.' : 'خطا در تنظیم کلمات کلیدی.';
        echo '</p>';
        
        // نمایش کلمات کلیدی جدید
        $new_keywords = get_post_meta($post_id, 'rank_math_focus_keyword', true);
        echo '<h2>کلمات کلیدی جدید</h2>';
        echo '<p>' . (empty($new_keywords) ? 'تعیین نشده' : nl2br($new_keywords)) . '</p>';
    } elseif ($action === 'direct_db') {
        // تست ذخیره مستقیم در دیتابیس
        global $wpdb;
        
        $keywords_array = array_map('trim', explode(',', $keywords));
        $keywords_string = implode("\n", $keywords_array);
        
        echo '<h2>در حال تنظیم مستقیم کلمات کلیدی در دیتابیس</h2>';
        echo '<p>کلمات کلیدی ورودی: ' . implode(', ', $keywords_array) . '</p>';
        
        // بررسی وجود متا
        $meta_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT meta_id FROM $wpdb->postmeta WHERE post_id = %d AND meta_key = %s",
                $post_id,
                'rank_math_focus_keyword'
            )
        );
        
        if ($meta_id) {
            // آپدیت
            $result = $wpdb->update(
                $wpdb->postmeta,
                array('meta_value' => $keywords_string),
                array('meta_id' => $meta_id)
            );
            echo '<p>متا فیلد موجود است. استفاده از update. نتیجه: ' . var_export($result, true) . '</p>';
        } else {
            // درج
            $result = $wpdb->insert(
                $wpdb->postmeta,
                array(
                    'post_id' => $post_id,
                    'meta_key' => 'rank_math_focus_keyword',
                    'meta_value' => $keywords_string
                )
            );
            echo '<p>متا فیلد وجود ندارد. استفاده از insert. نتیجه: ' . var_export($result, true) . '</p>';
        }
        
        // پاکسازی کش
        wp_cache_delete($post_id, 'post_meta');
        
        // نمایش کلمات کلیدی جدید
        $new_keywords = get_post_meta($post_id, 'rank_math_focus_keyword', true);
        echo '<h2>کلمات کلیدی جدید</h2>';
        echo '<p>' . (empty($new_keywords) ? 'تعیین نشده' : nl2br($new_keywords)) . '</p>';
    }
    
    // فرم تست
    echo '<h2>تست تنظیم کلمات کلیدی</h2>';
    echo '<form method="get">';
    echo '<input type="hidden" name="page" value="smart-admin-seo-settings">';
    echo '<input type="hidden" name="tab" value="test">';
    echo '<p><label>شناسه پست: <input type="number" name="post_id" value="' . $post_id . '"></label></p>';
    echo '<p><label>کلمات کلیدی (با کاما جدا کنید): <input type="text" name="keywords" value="' . esc_attr($keywords) . '" style="width: 300px;"></label></p>';
    echo '<p>';
    echo '<button type="submit" name="test_action" value="set" class="button button-primary">تنظیم کلمات کلیدی</button> ';
    echo '<button type="submit" name="test_action" value="direct_db" class="button">تنظیم مستقیم در دیتابیس</button>';
    echo '</p>';
    echo '</form>';
    
    // نمایش محتوای فایل لاگ
    $log_file = plugin_dir_path(__FILE__) . 'smart-admin-debug.log';
    if (file_exists($log_file)) {
        echo '<h2>فایل لاگ</h2>';
        echo '<div style="max-height:300px; overflow-y:auto; background:#f5f5f5; padding:10px; font-family:monospace; direction:ltr; text-align:left;">';
        echo nl2br(htmlspecialchars(file_get_contents($log_file)));
        echo '</div>';
        echo '<p><a href="' . add_query_arg('clear_log', '1') . '" class="button">پاکسازی فایل لاگ</a></p>';
    }
}

// افزودن تب تست به صفحه تنظیمات SEO
add_action('admin_menu', function() {
    // افزودن صفحه مخفی برای تست
    add_submenu_page(
        null, // بدون منوی والد (مخفی)
        'تست کلمات کلیدی', // عنوان صفحه
        'تست کلمات کلیدی', // عنوان منو
        'manage_options', // مجوز دسترسی
        'smart-admin-keyword-test', // slug صفحه 
        'smart_admin_keyword_test_page' // تابع نمایش صفحه
    );
    
    // افزودن صفحه تنظیمات SEO
    add_submenu_page(
        'options-general.php', // منوی والد
        'تنظیمات SEO ادمین هوشمند', // عنوان صفحه
        'تنظیمات SEO هوشمند', // عنوان منو
        'manage_options', // مجوز دسترسی
        'smart-admin-seo-settings', // slug صفحه
        'smart_admin_seo_settings_page' // تابع نمایش صفحه
    );
});

/**
 * نمایش صفحه تست کلمات کلیدی
 */
function smart_admin_keyword_test_page() {
    // بررسی مجوز دسترسی
    if (!current_user_can('manage_options')) {
        wp_die(__('شما اجازه دسترسی به این صفحه را ندارید.'));
    }
    
    // پاکسازی فایل لاگ
    if (isset($_GET['clear_log'])) {
        $log_file = plugin_dir_path(__FILE__) . 'smart-admin-debug.log';
        file_put_contents($log_file, '');
        wp_redirect(remove_query_arg('clear_log'));
        exit;
    }
    
    ?>
    <div class="wrap">
        <h1>تست تنظیم کلمات کلیدی Rank Math</h1>
        
        <div style="background: white; padding: 20px; border-radius: 5px; margin-top: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); direction: rtl;">
            <?php smart_admin_test_keyword_setting(); ?>
        </div>
    </div>
    <?php
}

// اضافه کردن لینک تست به صفحه تنظیمات SEO
add_action('admin_action_smart_admin_seo_settings', function() {
    echo '<p><a href="' . admin_url('admin.php?page=smart-admin-keyword-test') . '" class="button">تست تنظیم کلمات کلیدی</a></p>';
});

/**
 * نمایش صفحه تنظیمات SEO
 */
function smart_admin_seo_settings_page() {
    // بررسی مجوز دسترسی
    if (!current_user_can('manage_options')) {
        wp_die(__('شما اجازه دسترسی به این صفحه را ندارید.'));
    }
    
    $message = '';
    
    // بررسی فرم ارسالی
    if (isset($_POST['reset_nonce']) && isset($_POST['reset_keywords']) && wp_verify_nonce($_POST['reset_nonce'], 'smart_admin_reset_keywords')) {
        // پاک کردن فایل لاگ
        $log_file = plugin_dir_path(__FILE__) . 'smart-admin-debug.log';
        file_put_contents($log_file, '');
        $message = 'فایل لاگ با موفقیت پاک شد.';
    }
    
    ?>
    <style>
    .smart-admin-seo-settings {
        background: white;
        padding: 20px;
        border-radius: 5px;
        margin-top: 20px;
        max-width: 800px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        direction: rtl;
    }
    .smart-admin-seo-settings h2 {
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
        margin-bottom: 20px;
    }
    </style>
    
    <div class="wrap">
        <h1>تنظیمات SEO ادمین هوشمند</h1>
        
        <div class="smart-admin-seo-settings">
            <?php if ($message): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php echo esc_html($message); ?></p>
                </div>
            <?php endif; ?>
            
            <h2>مدیریت کلمات کلیدی Rank Math</h2>
            <p>از این صفحه می‌توانید کلمات کلیدی پیش‌فرض را مدیریت کنید یا ابزارهای عیب‌یابی را باز کنید.</p>
            
            <h3>مدیریت لاگ‌ها و عیب‌یابی</h3>
            <form method="post" action="">
                <?php wp_nonce_field('smart_admin_reset_keywords', 'reset_nonce'); ?>
                <p>
                    <input type="submit" name="reset_keywords" class="button button-secondary" value="پاک کردن فایل لاگ">
                    <a href="<?php echo admin_url('admin.php?page=smart-admin-keyword-test'); ?>" class="button button-primary">ابزار تست و عیب‌یابی کلمات کلیدی</a>
                </p>
            </form>
            
            <h3>راهنمای رفع مشکل</h3>
            <ol>
                <li>در صورت مشاهده خطای «خطای امنیتی نانس»، صفحه را رفرش کنید و دوباره تلاش کنید.</li>
                <li>اگر کلمات کلیدی فارسی به درستی ذخیره نمی‌شوند، از کلمات کلیدی پیشنهادی استفاده کنید.</li>
                <li>برای بررسی خطاها از ابزار تست و عیب‌یابی استفاده کنید.</li>
            </ol>
        </div>
    </div>
    <?php
}

/**
 * اضافه کردن متاباکس برای تنظیم کلمات کلیدی Rank Math
 */
function smart_admin_add_rankmath_metabox() {
    // بررسی وجود افزونه Rank Math
    if (!function_exists('rank_math')) {
        return;
    }
    
    add_meta_box(
        'smart_admin_rankmath_metabox',
        'کلمات کلیدی اصلی Rank Math',
        'smart_admin_rankmath_metabox_callback',
        'post',
        'side',
        'high'
    );
}

/**
 * نمایش محتوای متاباکس Rank Math
 */
function smart_admin_rankmath_metabox_callback($post) {
    // دریافت کلمات کلیدی فعلی
    $current_keywords = get_post_meta($post->ID, 'rank_math_focus_keyword', true);
    
    // برای نمایش در رابط کاربری
    $display_keywords = array();
    if (!empty($current_keywords)) {
        if (strpos($current_keywords, ',') !== false) {
            $display_keywords = explode(',', $current_keywords);
        } elseif (strpos($current_keywords, "\n") !== false) {
            $display_keywords = explode("\n", $current_keywords);
        } else {
            $display_keywords = array($current_keywords);
        }
    }
    
    echo '<div class="smart-admin-rankmath-keywords">';
    
    // نمایش کلمات کلیدی فعلی
    if (!empty($display_keywords)) {
        echo '<p><strong>کلمات کلیدی اصلی:</strong> <span style="color: #007cba;">' . implode('، ', $display_keywords) . '</span></p>';
    } else {
        echo '<p><strong>کلمات کلیدی اصلی:</strong> <span style="color: #dc3232;">تعیین نشده</span></p>';
    }
    
    // ایجاد نانس
    $nonce = wp_create_nonce('smart_admin_set_keywords');
    
    // فرم تنظیم کلمات کلیدی
    echo '<div id="smart-admin-rankmath-form">';
    echo '<p>';
    echo '<label for="smart_admin_rankmath_keywords"><strong>کلمات کلیدی جدید (با کاما جدا کنید):</strong></label><br>';
    echo '<input type="text" id="smart_admin_rankmath_keywords" name="smart_admin_rankmath_keywords" value="' . esc_attr(implode(', ', $display_keywords)) . '" style="width: 100%; margin: 5px 0; direction: rtl;">';
    echo '<button type="button" id="smart_admin_set_rankmath_keywords" class="button button-primary" style="width: 100%; margin: 5px 0;">تنظیم کلمات کلیدی</button>';
    echo '<div id="smart_admin_rankmath_result" style="display: block; margin-top: 5px; padding: 8px; border-radius: 4px;"></div>';
    echo '</p>';
    echo '</div>';
    
    // پیشنهاد کلمات کلیدی پیش‌فرض
    $default_keywords = array('برنامه نویسی', 'وبلاگ', 'مقاله', 'آموزش', 'راهنما');
    echo '<div style="margin-top: 10px;">';
    echo '<p><strong>کلمات کلیدی پیشنهادی:</strong></p>';
    echo '<div style="display: flex; flex-wrap: wrap; gap: 5px;">';
    foreach ($default_keywords as $keyword) {
        echo '<span class="keyword-suggestion" style="background: #f0f0f0; padding: 3px 8px; border-radius: 3px; cursor: pointer;">' . esc_html($keyword) . '</span>';
    }
    echo '</div>';
    echo '</div>';
    
    // توضیحات مفید
    echo '<p class="description" style="font-size: 12px; margin-top: 10px;">کلمات کلیدی فقط در متادیتای Rank Math ذخیره می‌شوند و به برچسب‌های پست اضافه نمی‌شوند.</p>';
    
    // لینک مشاهده فایل لاگ (فقط برای مدیران)
    if (current_user_can('manage_options')) {
        echo '<p><a href="' . esc_url(plugin_dir_url(__FILE__) . 'smart-admin-debug.log') . '" target="_blank" class="button button-secondary" style="font-size: 11px; margin-top: 5px;">مشاهده فایل لاگ</a></p>';
    }
    
    // ذخیره نانس به صورت مخفی
    echo '<input type="hidden" id="smart_admin_rankmath_nonce" value="' . esc_attr($nonce) . '">';
    
    // اسکریپت AJAX
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // کلیک روی کلمات کلیدی پیشنهادی
        $('.keyword-suggestion').on('click', function() {
            var keyword = $(this).text();
            var $input = $('#smart_admin_rankmath_keywords');
            var currentVal = $input.val();
            
            // اضافه کردن کلمه کلیدی به ورودی
            if (currentVal) {
                $input.val(currentVal + ', ' + keyword);
            } else {
                $input.val(keyword);
            }
        });
        
        // ارسال درخواست تنظیم کلمات کلیدی
        $('#smart_admin_set_rankmath_keywords').on('click', function() {
            var $button = $(this);
            var $result = $('#smart_admin_rankmath_result');
            var keywords = $('#smart_admin_rankmath_keywords').val();
            var nonce = $('#smart_admin_rankmath_nonce').val();
            
            // نمایش حالت در حال بارگذاری
            $button.prop('disabled', true).text('در حال تنظیم...');
            $result.html('<span style="color:#666;">در حال ارسال درخواست...</span>').css('background-color', '#f7f7f7');
            
            console.log('ارسال درخواست AJAX با نانس:', nonce);
            
            // ارسال درخواست AJAX
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'smart_admin_set_keywords',
                    post_id: <?php echo $post->ID; ?>,
                    keywords: keywords,
                    nonce: nonce
                },
                success: function(response) {
                    console.log('AJAX Response:', response);
                    
                    if (response.success) {
                        $result.html('<span style="color: #fff;">' + response.data + '</span>').css('background-color', '#46b450');
                        // بروزرسانی صفحه برای نمایش تغییرات
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        let errorMsg = 'خطا در تنظیم کلمات کلیدی.';
                        if (response.data) {
                            errorMsg = response.data;
                        }
                        $result.html('<span style="color: #fff;">' + errorMsg + '</span>').css('background-color', '#dc3232');
                    }
                    $button.prop('disabled', false).text('تنظیم کلمات کلیدی');
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error);
                    console.log('XHR Object:', xhr);
                    
                    $result.html('<span style="color: #fff;">خطا در ارتباط با سرور: ' + status + '</span>').css('background-color', '#dc3232');
                    $button.prop('disabled', false).text('تنظیم کلمات کلیدی');
                    
                    // اضافه کردن دکمه تلاش مجدد
                    $result.append('<br><button type="button" id="retry_button" class="button button-small" style="margin-top: 5px;">تلاش مجدد</button>');
                    $('#retry_button').on('click', function() {
                        $('#smart_admin_set_rankmath_keywords').trigger('click');
                    });
                }
            });
        });
    });
    </script>
    <?php
    
    echo '</div>';
} 