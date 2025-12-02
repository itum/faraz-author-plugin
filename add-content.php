<?php
include_once 'jdf.php';

add_filter('wp_feed_cache_transient_lifetime', function() {
    return 600;  
});

/**
 * حذف تصاویر استفاده نشده از پست‌ها
 */
function deleted_post1() { 
    $processed_items = [];
    $start_time = microtime(true);  

    global $wpdb;

    $unused_images = $wpdb->get_results("
        SELECT ID 
        FROM $wpdb->posts 
        WHERE post_type = 'attachment' 
        AND post_mime_type LIKE 'image/%' 
        AND post_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
        AND ID NOT IN (
            SELECT meta_value 
            FROM $wpdb->postmeta 
            WHERE meta_key = '_thumbnail_id'
        )
        ORDER BY post_date DESC
        LIMIT 50
    ");
      
    foreach ($unused_images as $image) { 
        if ((microtime(true) - $start_time) > 25) {
            if (strpos($_SERVER['REQUEST_URI'], 'wp-json')) {
                return new WP_REST_Response(array('processed_items' => $processed_items), 200);
            } 
        }

        $post_id = $wpdb->get_var($wpdb->prepare("
            SELECT post_id 
            FROM $wpdb->postmeta 
            WHERE meta_value = %d 
            AND meta_key = '_thumbnail_id'
        ", $image->ID));
     
        if (empty($post_id)) {
            wp_delete_attachment($image->ID, true);  
            $processed_items[] = $image->ID;
        }
    }
    
    return $processed_items;
}

/**
 * حذف پست‌های قدیمی و تصاویر استفاده نشده
 */
function deleted_post()
{
    $processed_items = [];
    $start_time = microtime(true);  
    
    if (strpos($_SERVER['REQUEST_URI'], 'wp-json')) {
        return new WP_REST_Response(array('processed_items' => $processed_items), 200);
    }
    
    global $wpdb;
 
    $unused_images = $wpdb->get_results("
        SELECT ID 
        FROM $wpdb->posts 
        WHERE post_type = 'attachment' 
        AND post_mime_type LIKE 'image/%' 
        AND post_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
        AND ID NOT IN (
            SELECT meta_value 
            FROM $wpdb->postmeta 
            WHERE meta_key = '_thumbnail_id'
        )
        LIMIT 80
    ");
      
    foreach ($unused_images as $image) { 
        if ((microtime(true) - $start_time) > 25) {
            if (strpos($_SERVER['REQUEST_URI'], 'wp-json')) {
                return new WP_REST_Response(array('processed_items' => $processed_items), 200);
            } 
        }

        $post_id = $wpdb->get_var($wpdb->prepare("
            SELECT post_id 
            FROM $wpdb->postmeta 
            WHERE meta_value = %d 
            AND meta_key = '_thumbnail_id'
        ", $image->ID));
     
        if (empty($post_id)) {
            wp_delete_attachment($image->ID, true);  
            $processed_items[] = $image->ID;
        }
    }
    
    return $processed_items;
}

/**
 * بررسی وجود پست با عنوان، خلاصه یا محتوای مشابه
 */
function check_post_title_exists($title, $excerpt, $post_content) {
    global $wpdb;
  
    $query = $wpdb->prepare("
        SELECT COUNT(*) 
        FROM $wpdb->posts 
        WHERE post_title = %s OR post_excerpt = %s OR post_content = %s
    ", $title, $excerpt, $post_content);

    $count = intval($wpdb->get_var($query)); 
    return $count;  
}
 
/**
 * بررسی و دریافت آیتم‌های جدید از RSS feeds
 */
function stp_check_for_new_rss_items() { 
    $log_file = plugin_dir_path(__FILE__) . 'rss_logs.txt';
    $processed_items = [];
    
    file_put_contents($log_file, "Starting RSS check at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

    $start_time = microtime(true);  
    global $wpdb;

    // حذف پست‌های قدیمی
    $date_threshold = date('Y-m-d H:i:s', current_time('timestamp') - (12 * 3600));
 
    $posts = $wpdb->get_results($wpdb->prepare("
        SELECT ID 
        FROM $wpdb->posts 
        WHERE (post_status = 'draft' OR post_status = 'faraz') 
        AND post_date < %s
    ", $date_threshold));
        
    foreach ($posts as $post) { 
        $thumbnail_id = get_post_thumbnail_id($post->ID);
        if ($thumbnail_id) {
            wp_delete_attachment($thumbnail_id, true);  
        } 
        wp_delete_post($post->ID, true);

        if ((microtime(true) - $start_time) > 25) {
            if (strpos($_SERVER['REQUEST_URI'], 'wp-json')) {
                return new WP_REST_Response(array('processed_items' => $processed_items), 200);
            }
        }
    }

    $entries = get_option('stp_entries', array());
    include_once(ABSPATH . WPINC . '/feed.php');
  
    try {
        $debug_test_sent = false; // برای جلوگیری از ارسال زیاد پیام تست
        foreach ($entries as $entry) {
            file_put_contents($log_file, "Processing RSS feed: " . $entry['url'] . "\n", FILE_APPEND);
            $rss = fetch_feed($entry['url']);
            $class = $entry['class'];
            $type = $entry['type'];

            if (!is_wp_error($rss)) {
                $max_items = $rss->get_item_quantity(4);
                $rss_items = $rss->get_items(0, $max_items);
                file_put_contents($log_file, "Found " . count($rss_items) . " RSS items\n", FILE_APPEND);
                // ارسال پیام تست به تلگرام برای اطمینان از گردش کار (فقط در حالت دیباگ یا فعال بودن گزینه)
                if (!$debug_test_sent && ((function_exists('smart_admin_get_setting') && smart_admin_get_setting('debug_mode')) || get_option('telegram_rss_debug', false))) {
                    send_to_telegram("🧪 RSS Debug: " . $entry['url'] . " | items: " . count($rss_items));
                    $debug_test_sent = true;
                }
                
                foreach ($rss_items as $item) { 
                    $date = strtotime($item->get_date('Y-m-d H:i:s'));
                    if (empty($date)) {
                        file_put_contents($log_file, "Empty date for item: " . $item->get_title() . "\n", FILE_APPEND);
                        sendErrorToTelegram('Empty date for RSS item: ' . $item->get_title());
                        continue;
                    }
                
                    if ($date !== false AND ((3600 * 2) < (time() - $date))) {
                        file_put_contents($log_file, "Item too old, skipping: " . $item->get_title() . "\n", FILE_APPEND);
                        continue;
                    }
                
                    if ((microtime(true) - $start_time) > 25) {
                        if (strpos($_SERVER['REQUEST_URI'], 'wp-json')) {
                            return new WP_REST_Response(array('processed_items' => $processed_items), 200);
                        } else break 2;
                    }

                    $datej = jdate('Y-m-d H:i:s', $date);
                    $link = $item->get_permalink();
                    $title = $item->get_title();
                    
                    // دریافت تصویر شاخص
                    $thumbnail_data = fetch_thumbnail($link);
                    $thumbnail_url = $thumbnail_data['image'];
                    
                    if (empty($thumbnail_url)){
                        file_put_contents($log_file, "No thumbnail found for: " . $title . "\n", FILE_APPEND);
                        $processed_items[] = ['error_thumbnail' => [$title, $datej, $link, $thumbnail_url]];
                        continue; 
                    }
                    
                    $source = parse_url($link, PHP_URL_HOST);
                    $processed_items[] = [$title, $datej, $link, $source];
                
                    $full_text = fetch_full_text($link, $type, $class);
                    $excerpt = $item->get_content();

                    file_put_contents($log_file, "Checking if post exists: " . $title . "\n", FILE_APPEND);
                    if (check_post_title_exists($title, $excerpt, $full_text) > 0) {
                        file_put_contents($log_file, "Post already exists, skipping: " . $title . "\n", FILE_APPEND);
                        continue;
                    }
                
                    $category_id = $entry['channel_title'];
                
                    file_put_contents($log_file, "Creating post with title: " . $title . "\n", FILE_APPEND);

                    $post_data = [
                        'post_title'    => $title,
                        'post_content'  => $full_text,
                        'post_excerpt'  => $excerpt,
                        'post_status'   => 'faraz',
                        'post_author'   => 1,
                        'post_category' => [(int) $category_id]
                    ];
                    $post_id = wp_insert_post($post_data);
                
                    if (is_wp_error($post_id)) {
                        file_put_contents($log_file, "Error creating post: " . $post_id->get_error_message() . "\n", FILE_APPEND);
                        sendErrorToTelegram('Error creating post: ' . $post_id->get_error_message());
                        continue;
                    }
                
                    file_put_contents($log_file, "Post created successfully with ID: " . $post_id . "\n", FILE_APPEND);
                
                    if ((check_post_title_exists($title, $excerpt, $full_text) >= 2) AND (wp_delete_post($post_id, true) OR true)) {
                        file_put_contents($log_file, "Duplicate post detected, deleting: " . $post_id . "\n", FILE_APPEND);
                        continue;
                    }
                    
                    if ($post_id && $thumbnail_url) {
                        file_put_contents($log_file, "Attaching thumbnail to post: " . $post_id . "\n", FILE_APPEND);
                        attach_thumbnail($post_id, $thumbnail_url);
                    }
                
                    if ($post_id && $thumbnail_url) { 
                        $cat = get_cat_name(intval(esc_html($entry['channel_title'])));
                        $message = "$title \n\n$excerpt \n\nدسته بندی : $cat \n\nنام سایت : $source \n\n  $datej ";
                        file_put_contents($log_file, "Sending to Telegram for post ID: " . $post_id . "\n", FILE_APPEND);
                        send_telegram_photo_with_caption($thumbnail_url, $message, $post_id);
                        // پیام تست کوتاه به ادمین برای تایید جریان کار
                        if ((function_exists('smart_admin_get_setting') && smart_admin_get_setting('debug_mode')) || get_option('telegram_rss_debug', false)) {
                            send_to_telegram("✅ Test: ارسال RSS برای پست #$post_id انجام شد: $title");
                        }
                    } else {
                        file_put_contents($log_file, "Cannot send to Telegram - missing post_id or thumbnail_url\n", FILE_APPEND);
                    }
                
                    usleep(333);  
                }
                
            } else {
                file_put_contents($log_file, "RSS feed error: " . $rss->get_error_message() . "\n", FILE_APPEND);
                sendErrorToTelegram('RSS feed error: ' . $rss->get_error_message());
            }
        }
 
        file_put_contents($log_file, "Finished RSS check at " . date('Y-m-d H:i:s') . "\n\n", FILE_APPEND);
        
    } catch (Exception $e) {
        sendErrorToTelegram('An error occurred: ' . $e->getMessage());
        file_put_contents($log_file, "Exception: " . $e->getMessage() . "\n", FILE_APPEND);
        if (strpos($_SERVER['REQUEST_URI'], 'wp-json')) {
            return new WP_REST_Response(array('error' => $e->getMessage()), 500);
        }
        return 'An error occurred: ' . $e->getMessage();
    }

    if (strpos($_SERVER['REQUEST_URI'], 'wp-json')) {
        return new WP_REST_Response(array('processed_items' => $processed_items), 200);
    }

    return 'RSS items have been processed.';
}




    


/**
 * دریافت متن کامل از URL با استفاده از کلاس و نوع مشخص شده
 */
function fetch_full_text($url, $type, $class) {
    $content = fetch_content($url);
    if ($content === false) {
        error_log('[Content Fetch] Failed to fetch content from: ' . $url);
        return '';  
    }
 
    libxml_use_internal_errors(true);
 
    $dom = new DOMDocument();
     
    $content = mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8');
    
    @$dom->loadHTML($content, LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED);
 
    $xpath = new DOMXPath($dom);
    $query = "//{$type}[contains(@class, '{$class}')]";
    $nodes = $xpath->query($query);

    // بررسی وجود نودها
    if ($nodes->length > 0) {
        $full_text = '';
        foreach ($nodes as $node) {
            $full_text .= $dom->saveHTML($node);
        }
        return $full_text;
    } else {
        error_log('[Content Fetch] No matching nodes found for type: ' . $type . ', class: ' . $class);
        return '';  
    }
}

/**
 * دریافت تصویر شاخص و تاریخ انتشار از URL
 */
function fetch_thumbnail($url)
{
    $content = fetch_content($url);
    if ($content === false) {
        error_log('[Thumbnail Fetch] Failed to fetch content from: ' . $url);
        return ['image' => '', 'date_published' => ''];
    }

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="UTF-8" ?>' . $content);

    $xpath = new DOMXPath($dom);
 
    $image = '';
    
    // جستجوی تصویر در Open Graph
    $nodes = $xpath->query('//meta[@property="og:image"]');
    if ($nodes->length > 0) {
        $image = $nodes->item(0)->getAttribute('content');
    } else { 
        // جستجو در JSON-LD
        $scriptNodes = $xpath->query('//script[@type="application/ld+json"]');
        foreach ($scriptNodes as $node) {
            $jsonContent = $node->textContent;
            $jsonData = json_decode($jsonContent, true);

            if (isset($jsonData['image'])) {
                if (is_string($jsonData['image'])) {
                    $image = $jsonData['image'];
                } elseif (is_array($jsonData['image']) && count($jsonData['image']) > 0) {
                    $image = $jsonData['image'][0];
                }
            }
        }
    } 
    
    // جستجو در Twitter Card
    if (empty($image)) {
        $twitterNodes = $xpath->query('//meta[@name="twitter:image"]');
        if ($twitterNodes->length > 0) {
            $image = $twitterNodes->item(0)->getAttribute('content');
        }
    }
    
    // جستجو در اولین تصویر موجود
    if (empty($image)) {
        $imgNodes = $xpath->query('//img[@src]');
        if ($imgNodes->length > 0) {
            $image = $imgNodes->item(0)->getAttribute('src');
        }
    }
 
    $date_published = '';
    $dateNodes = $xpath->query('//meta[@property="article:published_time"]');
    if ($dateNodes->length > 0) {
        $date_published = $dateNodes->item(0)->getAttribute('content');
    } else { 
        $modifiedNodes = $xpath->query('//meta[@property="article:modified_time"]');
        if ($modifiedNodes->length > 0) {
            $date_published = $modifiedNodes->item(0)->getAttribute('content');
        }
    }

    return ['image' => $image, 'date_published' => $date_published];
}

/**
 * دریافت محتوای صفحه از URL
 */
function fetch_content($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FAILONERROR, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    $output = curl_exec($ch);
    
    if(curl_error($ch)) {
        error_log('[Content Fetch] cURL Error: ' . curl_error($ch) . ' for URL: ' . $url);
        curl_close($ch);
        return false;
    }
    
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($http_code !== 200) {
        error_log('[Content Fetch] HTTP Error: ' . $http_code . ' for URL: ' . $url);
        curl_close($ch);
        return false;
    }
    
    curl_close($ch);
    return $output ? $output : false;
}

/**
 * پاکسازی URL تصویر Unsplash
 */
function clean_unsplash_url($url) {
    // حذف پارامترهای اضافی از URL
    $url = preg_replace('/&ixlib=[^&]*/', '', $url);
    $url = preg_replace('/&ixid=[^&]*/', '', $url);
    
    // اطمینان از وجود پارامترهای ضروری
    if (strpos($url, '?') === false) {
        $url .= '?';
    }
    
    // اضافه کردن پارامترهای ضروری
    $url .= '&fm=jpg&q=80&w=1080';
    
    return $url;
}

/**
 * اتصال تصویر شاخص به پست
 */
function attach_thumbnail($post_id, $thumbnail_url) { 
    error_log('[Smart Image Generation] Attaching thumbnail to post ID: ' . $post_id . ' from URL: ' . $thumbnail_url);
    
    // بررسی وضعیت فعال بودن Unsplash
    if (function_exists('faraz_unsplash_is_auto_featured_image_enabled')) {
        $unsplash_enabled = faraz_unsplash_is_auto_featured_image_enabled();
        error_log('[Smart Image Generation] Unsplash auto featured image enabled: ' . ($unsplash_enabled ? 'true' : 'false'));
        
        if (!$unsplash_enabled) {
            error_log('[Smart Image Generation] Unsplash auto featured image is disabled, skipping attachment');
            return false;
        }
    } elseif (function_exists('faraz_unsplash_is_image_generation_enabled')) {
        $unsplash_enabled = faraz_unsplash_is_image_generation_enabled();
        error_log('[Smart Image Generation] Unsplash image generation enabled: ' . ($unsplash_enabled ? 'true' : 'false'));
        
        if (!$unsplash_enabled) {
            error_log('[Smart Image Generation] Unsplash image generation is disabled, skipping attachment');
            return false;
        }
    } else {
        // بررسی مستقیم گزینه‌ها
        $image_generation_enabled = get_option('faraz_unsplash_enable_image_generation', true);
        $auto_featured_enabled = get_option('faraz_unsplash_enable_auto_featured_image', true);
        
        if (!$image_generation_enabled || !$auto_featured_enabled) {
            error_log('[Smart Image Generation] Unsplash options are disabled, skipping attachment');
            return false;
        }
    }
    
    // بررسی URL تصویر
    if (empty($thumbnail_url) || !filter_var($thumbnail_url, FILTER_VALIDATE_URL)) {
        error_log('[Smart Image Generation] Invalid thumbnail URL: ' . $thumbnail_url);
        return false;
    }
    
    // بررسی دسترسی به فایل
    $headers = wp_remote_head($thumbnail_url, array(
        'timeout' => 15,
        'sslverify' => false,
        'user-agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url')
    ));
    
    if (is_wp_error($headers)) {
        error_log('[Smart Image Generation] Head request error: ' . $headers->get_error_message());
        return false;
    }
    
    $response_code = wp_remote_retrieve_response_code($headers);
    error_log('[Smart Image Generation] Response code: ' . $response_code);
    
    if ($response_code !== 200) {
        error_log('[Smart Image Generation] HTTP error: ' . $response_code);
        return false;
    }
    
    // بررسی نوع فایل
    $content_type = wp_remote_retrieve_header($headers, 'content-type');
    error_log('[Smart Image Generation] Content type: ' . $content_type);
    
    if (!preg_match('/^image\/(jpeg|jpg|png|gif|webp)$/i', $content_type)) {
        error_log('[Smart Image Generation] Invalid content type: ' . $content_type);
        return false;
    }
    
    if (!function_exists('media_sideload_image')) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
    }
    
    error_log('[Smart Image Generation] Starting media_sideload_image...');
    
    // تلاش اول با media_sideload_image
    $image_id = media_sideload_image($thumbnail_url, $post_id, null, 'id');
 
    // اگر خطای نشانی نامعتبر بود، تلاش دوم با دانلود دستی
    if (is_wp_error($image_id) && strpos($image_id->get_error_message(), 'نشانی تصویر نامعتبر') !== false) {
        error_log('[Smart Image Generation] media_sideload_image failed, trying manual download...');
        
        // دانلود مستقیم فایل
        $tmp = download_url($thumbnail_url, 30);
        if (is_wp_error($tmp)) {
            error_log('[Smart Image Generation] Manual download failed: ' . $tmp->get_error_message());
            return false;
        }
        
        // ایجاد نام فایل
        $filename = 'unsplash-' . time() . '.jpg';
        $file_array = array(
            'name'     => $filename,
            'tmp_name' => $tmp,
        );
        
        // درج فایل به کتابخانه رسانه
        $image_id = media_handle_sideload($file_array, $post_id, 'تصویر شاخص');
        
        // در صورت خطا فایل موقت را حذف کن
        if (is_wp_error($image_id)) {
            @unlink($tmp);
            error_log('[Smart Image Generation] Manual sideload failed: ' . $image_id->get_error_message());
            return false;
        }
        
        error_log('[Smart Image Generation] Manual download successful, image_id: ' . $image_id);
    } elseif (is_wp_error($image_id)) {
        error_log('[Smart Image Generation] Failed to sideload image: ' . $image_id->get_error_message());
        return false;
    }
    
    if (!$image_id || !is_numeric($image_id)) {
        error_log('[Smart Image Generation] Invalid image_id returned: ' . $image_id);
        return false;
    }
    
    error_log('[Smart Image Generation] Image sideloaded successfully with ID: ' . $image_id);
    
    // تنظیم متن جایگزین
    update_post_meta($image_id, '_wp_attachment_image_alt', 'تصویر شاخص');
    
    // به‌روزرسانی اطلاعات فایل
    $file_path = get_attached_file($image_id);
    if ($file_path && file_exists($file_path)) {
        error_log('[Smart Image Generation] Updating attachment metadata...');
        wp_update_attachment_metadata($image_id, wp_generate_attachment_metadata($image_id, $file_path));
    }
    
    // تنظیم به عنوان تصویر شاخص
    error_log('[Smart Image Generation] Setting as featured image...');
    $result = set_post_thumbnail($post_id, $image_id);
    
    if ($result) {
        error_log('[Smart Image Generation] Featured image set successfully');
        return true;
    } else {
        error_log('[Smart Image Generation] Failed to set featured image');
        return false;
    }
}

/**
 * تولید هوشمند تصویر شاخص بر اساس محتوا
 */
function smart_generate_featured_image($post_id, $post_title, $post_content) {
    error_log('[Smart Image Generation] Starting for post ID: ' . $post_id);
    error_log('[Smart Image Generation] Post title: ' . $post_title);
    error_log('[Smart Image Generation] Post content length: ' . strlen($post_content));
    
    // بررسی وضعیت فعال بودن Unsplash
    if (function_exists('faraz_unsplash_is_auto_featured_image_enabled')) {
        $unsplash_enabled = faraz_unsplash_is_auto_featured_image_enabled();
        error_log('[Smart Image Generation] Unsplash auto featured image enabled: ' . ($unsplash_enabled ? 'true' : 'false'));
        
        if (!$unsplash_enabled) {
            error_log('[Smart Image Generation] Unsplash auto featured image is disabled, skipping');
            return false;
        }
    } elseif (function_exists('faraz_unsplash_is_image_generation_enabled')) {
        $unsplash_enabled = faraz_unsplash_is_image_generation_enabled();
        error_log('[Smart Image Generation] Unsplash image generation enabled: ' . ($unsplash_enabled ? 'true' : 'false'));
        
        if (!$unsplash_enabled) {
            error_log('[Smart Image Generation] Unsplash image generation is disabled, skipping');
            return false;
        }
    } else {
        // بررسی مستقیم گزینه‌ها
        $image_generation_enabled = get_option('faraz_unsplash_enable_image_generation', true);
        $auto_featured_enabled = get_option('faraz_unsplash_enable_auto_featured_image', true);
        
        error_log('[Smart Image Generation] Direct option check - image_generation: ' . ($image_generation_enabled ? 'true' : 'false') . ', auto_featured: ' . ($auto_featured_enabled ? 'true' : 'false'));
        
        if (!$image_generation_enabled || !$auto_featured_enabled) {
            error_log('[Smart Image Generation] Unsplash options are disabled, skipping');
            return false;
        }
    }
    
    // بررسی وجود تصویر شاخص
    if (has_post_thumbnail($post_id)) {
        error_log('[Smart Image Generation] Post already has featured image');
        return true;
    }
    
    // استخراج کلمات کلیدی از عنوان و محتوا
    $keywords = extract_content_keywords($post_title . ' ' . $post_content);
    error_log('[Smart Image Generation] Extracted keywords: ' . implode(', ', $keywords));
    
    if (empty($keywords)) {
        error_log('[Smart Image Generation] No keywords extracted');
        return false;
    }
    
    // جستجوی تصویر در Unsplash
    $api_key = get_option('faraz_unsplash_api_key');
    if (empty($api_key)) {
        error_log('[Smart Image Generation] No Unsplash API key found');
        return false;
    }
    
    error_log('[Smart Image Generation] API key found, searching for keyword: ' . $keywords[0]);
    $primary_keyword = $keywords[0];
    $image = search_unsplash_image($primary_keyword, $api_key);
    
    if ($image) {
        error_log('[Smart Image Generation] Image found, URL: ' . $image['url']);
        error_log('[Smart Image Generation] Image alt: ' . $image['alt']);
        $result = attach_thumbnail($post_id, $image['url']);
        if ($result) {
            error_log('[Smart Image Generation] Image attached successfully');
            return true;
        } else {
            error_log('[Smart Image Generation] Failed to attach image');
            return false;
        }
    } else {
        error_log('[Smart Image Generation] No image found for keyword: ' . $primary_keyword);
        return false;
    }
}

/**
 * استخراج کلمات کلیدی از محتوا
 */
function extract_content_keywords($content) {
    error_log('[Smart Image Generation] Extracting keywords from content...');
    
    // حذف تگ‌های HTML
    $content = wp_strip_all_tags($content);
    error_log('[Smart Image Generation] Content after stripping tags: ' . substr($content, 0, 100) . '...');
    
    // حذف کاراکترهای خاص
    $content = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $content);
    
    // تقسیم به کلمات
    $words = preg_split('/\s+/', $content);
    error_log('[Smart Image Generation] Total words found: ' . count($words));
    
    // فیلتر کردن کلمات کوتاه و غیر مرتبط
    $stop_words = [
        'این', 'آن', 'که', 'را', 'به', 'از', 'در', 'با', 'برای', 'تا', 'یا', 'ولی', 'اما', 'اگر', 'چون', 'زیرا',
        'the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'is', 'are', 'was', 'were',
        'a', 'an', 'as', 'be', 'been', 'being', 'do', 'does', 'did', 'have', 'has', 'had', 'will', 'would', 'could',
        'should', 'may', 'might', 'must', 'can', 'cannot', 'can\'t', 'don\'t', 'doesn\'t', 'didn\'t', 'won\'t',
        'wouldn\'t', 'couldn\'t', 'shouldn\'t', 'mayn\'t', 'mightn\'t', 'mustn\'t'
    ];
    
    $keywords = array_filter($words, function($word) use ($stop_words) {
        $word = trim($word);
        return strlen($word) > 2 && !in_array(strtolower($word), array_map('strtolower', $stop_words));
    });
    
    error_log('[Smart Image Generation] Keywords after filtering: ' . count($keywords));
    
    // شمارش تکرار کلمات
    $word_count = array_count_values($keywords);
    
    // مرتب‌سازی بر اساس تکرار
    arsort($word_count);
    
    // برگرداندن 5 کلمه پرتکرار
    $top_keywords = array_slice(array_keys($word_count), 0, 5);
    error_log('[Smart Image Generation] Top keywords: ' . implode(', ', $top_keywords));
    
    return $top_keywords;
}

/**
 * جستجوی تصویر در Unsplash
 */
function search_unsplash_image($keyword, $api_key) {
    error_log('[Smart Image Generation] Searching Unsplash for keyword: ' . $keyword);
    
    // بررسی وضعیت فعال بودن Unsplash
    if (function_exists('faraz_unsplash_is_auto_featured_image_enabled')) {
        $unsplash_enabled = faraz_unsplash_is_auto_featured_image_enabled();
        error_log('[Smart Image Generation] Unsplash auto featured image enabled: ' . ($unsplash_enabled ? 'true' : 'false'));
        
        if (!$unsplash_enabled) {
            error_log('[Smart Image Generation] Unsplash auto featured image is disabled, skipping search');
            return false;
        }
    } elseif (function_exists('faraz_unsplash_is_image_generation_enabled')) {
        $unsplash_enabled = faraz_unsplash_is_image_generation_enabled();
        error_log('[Smart Image Generation] Unsplash image generation enabled: ' . ($unsplash_enabled ? 'true' : 'false'));
        
        if (!$unsplash_enabled) {
            error_log('[Smart Image Generation] Unsplash image generation is disabled, skipping search');
            return false;
        }
    } else {
        // بررسی مستقیم گزینه‌ها
        $image_generation_enabled = get_option('faraz_unsplash_enable_image_generation', true);
        $auto_featured_enabled = get_option('faraz_unsplash_enable_auto_featured_image', true);
        
        if (!$image_generation_enabled || !$auto_featured_enabled) {
            error_log('[Smart Image Generation] Unsplash options are disabled, skipping search');
            return false;
        }
    }
    
    if (empty($api_key)) {
        error_log('[Smart Image Generation] No API key provided');
        return false;
    }
    
    $url = add_query_arg([
        'query' => urlencode($keyword),
        'client_id' => $api_key,
        'per_page' => 1,
        'orientation' => 'landscape',
    ], 'https://api.unsplash.com/search/photos');
    
    error_log('[Smart Image Generation] Unsplash URL: ' . $url);
    
    $args = [
        'timeout' => 30,
        'sslverify' => false,
        'user-agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url'),
        'headers' => [
            'Accept' => 'application/json',
            'Accept-Language' => 'fa-IR,fa;q=0.9,en;q=0.8',
        ]
    ];
    
    error_log('[Smart Image Generation] Making request to Unsplash...');
    $response = wp_remote_get($url, $args);
    
    if (is_wp_error($response)) {
        error_log('[Smart Image Generation] HTTP Error: ' . $response->get_error_message());
        return false;
    }
    
    $status_code = wp_remote_retrieve_response_code($response);
    error_log('[Smart Image Generation] HTTP Status Code: ' . $status_code);
    
    if ($status_code !== 200) {
        error_log('[Smart Image Generation] HTTP Error: Status code ' . $status_code);
        return false;
    }
    
    $body = wp_remote_retrieve_body($response);
    error_log('[Smart Image Generation] Response body length: ' . strlen($body));
    
    if (empty($body)) {
        error_log('[Smart Image Generation] Empty response body');
        return false;
    }
    
    $data = json_decode($body, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('[Smart Image Generation] JSON decode error: ' . json_last_error_msg());
        return false;
    }
    
    if (empty($data['results'])) {
        error_log('[Smart Image Generation] No results found in response');
        error_log('[Smart Image Generation] Response data: ' . print_r($data, true));
        return false;
    }
    
    $image = $data['results'][0];
    $resolution = get_option('faraz_unsplash_image_resolution', 'regular');
    
    error_log('[Smart Image Generation] Image found: ' . $image['id']);
    error_log('[Smart Image Generation] Image URLs: ' . print_r($image['urls'], true));
    
    $image_url = $image['urls'][$resolution] ?? $image['urls']['regular'];
    
    // پاکسازی URL
    $image_url = clean_unsplash_url($image_url);
    
    error_log('[Smart Image Generation] Selected image URL: ' . $image_url);
    
    if (empty($image_url)) {
        error_log('[Smart Image Generation] No valid image URL found');
        return false;
    }
    
    return [
        'url' => $image_url,
        'alt' => $image['alt_description'] ?: $keyword,
        'user' => $image['user']['name']
    ];
}

/**
 * تابع کمکی برای بررسی وضعیت پلاگین
 */
function check_plugin_status() {
    $status = [
        'rss_feeds' => get_option('stp_entries', []),
        'telegram_token' => !empty(get_option('telegram_bot_token')),
        'unsplash_api' => !empty(get_option('faraz_unsplash_api_key')),
        'last_rss_check' => get_option('last_rss_check_time', 'Never'),
        'total_posts_created' => get_option('total_posts_created', 0),
        'total_telegram_sent' => get_option('total_telegram_sent', 0)
    ];
    
    return $status;
}

/**
 * تابع کمکی برای پاکسازی لاگ‌های قدیمی
 */
function cleanup_old_logs() {
    $log_files = [
        plugin_dir_path(__FILE__) . 'rss_logs.txt',
        plugin_dir_path(__FILE__) . 'telegram_logs.txt',
        plugin_dir_path(__FILE__) . 'whatsapp_logs.txt'
    ];
    
    foreach ($log_files as $log_file) {
        if (file_exists($log_file)) {
            $file_size = filesize($log_file);
            if ($file_size > 5 * 1024 * 1024) { // بیش از 5 مگابایت
                $content = file_get_contents($log_file);
                $lines = explode("\n", $content);
                $recent_lines = array_slice($lines, -1000); // نگه داشتن 1000 خط آخر
                file_put_contents($log_file, implode("\n", $recent_lines));
            }
        }
    }
}

/**
 * تابع کمکی برای بهینه‌سازی پایگاه داده
 */
function optimize_database() {
    global $wpdb;
    
    // حذف پست‌های قدیمی با وضعیت draft یا faraz
    $old_posts = $wpdb->get_results($wpdb->prepare("
        SELECT ID 
        FROM $wpdb->posts 
        WHERE (post_status = 'draft' OR post_status = 'faraz') 
        AND post_date < DATE_SUB(NOW(), INTERVAL 7 DAY)
        LIMIT 100
    "));
    
    foreach ($old_posts as $post) {
        wp_delete_post($post->ID, true);
    }
    
    // حذف تصاویر استفاده نشده
    $unused_attachments = $wpdb->get_results("
        SELECT ID 
        FROM $wpdb->posts 
        WHERE post_type = 'attachment' 
        AND post_mime_type LIKE 'image/%' 
        AND post_date < DATE_SUB(NOW(), INTERVAL 30 DAY) 
        AND ID NOT IN (
            SELECT meta_value 
            FROM $wpdb->postmeta 
            WHERE meta_key = '_thumbnail_id'
        )
        LIMIT 50
    ");
    
    foreach ($unused_attachments as $attachment) {
        wp_delete_attachment($attachment->ID, true);
    }
    
    return [
        'deleted_posts' => count($old_posts),
        'deleted_attachments' => count($unused_attachments)
    ];
}
