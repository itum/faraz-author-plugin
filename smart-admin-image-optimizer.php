<?php
/**
 * سیستم هوشمند انتخاب و بهینه‌سازی تصاویر
 * 
 * این فایل قابلیت انتخاب خودکار تصاویر مرتبط از Unsplash
 * و بهینه‌سازی آن‌ها برای SEO را فراهم می‌کند.
 */

// اطمینان از عدم دسترسی مستقیم به فایل
if (!defined('ABSPATH')) {
    exit;
}

/**
 * کلاس بهینه‌ساز هوشمند تصاویر
 */
class Smart_Admin_Image_Optimizer {
    
    /**
     * سازنده کلاس و ثبت هوک‌ها
     */
    public function __construct() {
        // افزودن اسکریپت‌ها
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // افزودن دکمه به نوار ابزار ویرایشگر
        add_action('media_buttons', array($this, 'add_image_search_button'));
        
        // ثبت اکشن‌های AJAX
        add_action('wp_ajax_smart_admin_search_images', array($this, 'ajax_search_images'));
        add_action('wp_ajax_smart_admin_auto_suggest_images', array($this, 'ajax_auto_suggest_images'));
        add_action('wp_ajax_smart_admin_insert_image', array($this, 'ajax_insert_image'));
        add_action('wp_ajax_smart_admin_test_permissions', array($this, 'ajax_test_permissions'));
        add_action('wp_ajax_smart_admin_test_image_download', array($this, 'ajax_test_image_download'));
        
        // هوک برای تولید خودکار تصویر شاخص
        add_action('wp_after_insert_post', array($this, 'auto_generate_featured_image'), 10, 2);
        
        // افزودن متاباکس برای جستجوی تصویر
        add_action('add_meta_boxes', array($this, 'add_image_search_metabox'));
    }
    
    /**
     * افزودن اسکریپت‌ها و استایل‌ها
     */
    public function enqueue_scripts($hook) {
        // فقط در صفحه ویرایش نوشته
        if (!in_array($hook, array('post.php', 'post-new.php'))) {
            return;
        }
        
        // Debug: لاگ کردن برای بررسی
        error_log('Smart Admin Image Optimizer: Loading scripts for hook: ' . $hook);
        
        // ثبت استایل‌ها - استفاده از فایل موجود
        wp_enqueue_style(
            'smart-admin-image-optimizer-css',
            plugin_dir_url(__FILE__) . 'css/smart-admin-seo-optimizer.css',
            array(),
            time()
        );
        
        // ثبت اسکریپت
        wp_enqueue_script(
            'smart-admin-image-optimizer-js',
            plugin_dir_url(__FILE__) . 'js/smart-admin-image-optimizer.js',
            array('jquery'),
            time(),
            true
        );
        
        // افزودن متغیرهای مورد نیاز به جاوااسکریپت
        wp_localize_script('smart-admin-image-optimizer-js', 'smartAdminImage', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('smart_admin_image_optimizer'),
            'searching_text' => 'در حال جستجوی تصاویر...',
            'no_images_text' => 'هیچ تصویری یافت نشد.',
            'error_text' => 'خطا در جستجوی تصاویر',
            'insert_text' => 'درج تصویر',
            'search_text' => 'جستجوی تصویر',
            'auto_suggest_text' => 'پیشنهاد خودکار تصاویر'
        ));
        
        // Debug: لاگ کردن متغیرها
        error_log('Smart Admin Image Optimizer: Scripts enqueued successfully');
    }
    
    /**
     * افزودن دکمه جستجوی تصویر به نوار ابزار
     */
    public function add_image_search_button() {
        echo '<button type="button" id="smart-admin-image-search-button" class="button">';
        echo '<span class="dashicons dashicons-format-image" style="margin: 3px 5px 0 0;"></span>';
        echo 'جستجوی تصویر هوشمند';
        echo '</button>';
    }
    
    /**
     * افزودن متاباکس جستجوی تصویر
     */
    public function add_image_search_metabox() {
        add_meta_box(
            'smart-admin-image-search',
            'جستجوی تصویر هوشمند',
            array($this, 'image_search_metabox_callback'),
            'post',
            'side',
            'default'
        );
    }
    
    /**
     * محتوای متاباکس جستجوی تصویر
     */
    public function image_search_metabox_callback($post) {
        wp_nonce_field('smart_admin_image_search', 'smart_admin_image_nonce');
        ?>
        <div id="smart-admin-image-search-container">
            <p>
                <label for="image-search-keyword">کلمه کلیدی برای جستجوی تصویر:</label>
                <input type="text" id="image-search-keyword" class="widefat" 
                       value="<?php echo esc_attr($post->post_title); ?>" 
                       placeholder="مثال: برنامه نویسی، تکنولوژی، کسب و کار">
            </p>
            <p>
                <button type="button" id="smart-image-search-button" class="button button-primary">
                    <span class="dashicons dashicons-search"></span>
                    جستجوی تصویر
                </button>
                <button type="button" id="auto-suggest-images-button" class="button button-secondary">
                    <span class="dashicons dashicons-magic"></span>
                    پیشنهاد خودکار
                </button>
            </p>
            <div id="image-search-results"></div>
            <div id="image-search-loading" style="display: none;">
                <span class="spinner is-active"></span>
                <span>در حال جستجو...</span>
            </div>
        </div>
        <?php
    }
    
    /**
     * پردازش درخواست AJAX برای جستجوی تصاویر
     */
    public function ajax_search_images() {
        check_ajax_referer('smart_admin_image_optimizer', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('شما دسترسی لازم را ندارید.');
            return;
        }
        
        $keyword = isset($_POST['keyword']) ? sanitize_text_field($_POST['keyword']) : '';
        if (empty($keyword)) {
            wp_send_json_error('کلمه کلیدی نمی‌تواند خالی باشد.');
            return;
        }
        
        $images = $this->search_unsplash_images($keyword);
        
        if (is_wp_error($images)) {
            wp_send_json_error($images->get_error_message());
            return;
        }
        
        wp_send_json_success($images);
    }
    
    /**
     * پردازش درخواست AJAX برای پیشنهاد خودکار تصاویر
     */
    public function ajax_auto_suggest_images() {
        check_ajax_referer('smart_admin_image_optimizer', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('شما دسترسی لازم را ندارید.');
            return;
        }
        
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        if (!$post_id) {
            wp_send_json_error('شناسه پست نامعتبر است.');
            return;
        }
        
        $post = get_post($post_id);
        if (!$post) {
            wp_send_json_error('پست مورد نظر یافت نشد.');
            return;
        }
        
        // استخراج کلمات کلیدی از عنوان و محتوا
        $keywords = $this->extract_content_keywords($post);
        
        if (empty($keywords)) {
            wp_send_json_error('نمی‌توان کلمات کلیدی مناسبی از محتوا استخراج کرد.');
            return;
        }
        
        // جستجوی تصاویر برای اولین کلمه کلیدی
        $primary_keyword = $keywords[0];
        $images = $this->search_unsplash_images($primary_keyword);
        
        if (is_wp_error($images)) {
            wp_send_json_error($images->get_error_message());
            return;
        }
        
        wp_send_json_success(array(
            'images' => $images,
            'keyword' => $primary_keyword,
            'suggested_keywords' => array_slice($keywords, 1, 5)
        ));
    }
    
    /**
     * پردازش درخواست AJAX برای درج تصویر
     */
    public function ajax_insert_image() {
        check_ajax_referer('smart_admin_image_optimizer', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('شما دسترسی لازم را ندارید.');
            return;
        }
        
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $image_url = isset($_POST['image_url']) ? esc_url_raw($_POST['image_url']) : '';
        $alt_text = isset($_POST['alt_text']) ? sanitize_text_field($_POST['alt_text']) : '';
        $insert_type = isset($_POST['insert_type']) ? sanitize_text_field($_POST['insert_type']) : 'content';
        
        if (empty($post_id) || empty($image_url)) {
            wp_send_json_error('اطلاعات ناقص برای درج تصویر.');
            return;
        }
        
        $result = $this->insert_image_to_post($post_id, $image_url, $alt_text, $insert_type);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
            return;
        }
        
        wp_send_json_success(array(
            'message' => $result['message'],
            'attachment_id' => $result['attachment_id'],
            'image_url' => $result['image_url']
        ));
    }
    
    /**
     * جستجوی تصاویر در Unsplash
     */
    private function search_unsplash_images($keyword, $count = 6) {
        $api_key = get_option('faraz_unsplash_api_key');
        if (empty($api_key)) {
            return new WP_Error('no_api_key', 'کلید API Unsplash تنظیم نشده است.');
        }
        
        $resolution = get_option('faraz_unsplash_image_resolution', 'regular');
        
        $url = add_query_arg([
            'query' => urlencode($keyword),
            'client_id' => $api_key,
            'per_page' => $count,
            'orientation' => 'landscape',
        ], 'https://api.unsplash.com/search/photos');
        
        $args = [
            'timeout' => 30,
            'sslverify' => false,
            'user-agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url'),
            'headers' => [
                'Accept' => 'application/json',
                'Accept-Language' => 'fa-IR,fa;q=0.9,en;q=0.8',
            ]
        ];
        
        $response = wp_remote_get($url, $args);
        
        if (is_wp_error($response)) {
            $error_message = 'خطا در اتصال به Unsplash: ' . $response->get_error_message();
            error_log('[Smart Admin Image Search] ' . $error_message . ' - URL: ' . $url);
            return new WP_Error('connection_error', $error_message);
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (empty($data['results'])) {
            return new WP_Error('no_images', 'هیچ تصویری برای این کلمه کلیدی یافت نشد.');
        }
        
        // تبدیل نتایج به فرمت مورد نیاز
        $images = array_map(function($image) use ($resolution) {
            return [
                'id' => $image['id'],
                'url' => $image['urls'][$resolution] ?? $image['urls']['regular'],
                'thumb_url' => $image['urls']['small'],
                'alt' => $image['alt_description'] ?: 'تصویر مرتبط',
                'user' => [
                    'name' => $image['user']['name'],
                    'link' => $image['user']['links']['html'],
                ],
                'description' => $image['description'] ?: '',
                'color' => $image['color'] ?: '#000000'
            ];
        }, $data['results']);
        
        return $images;
    }
    
    /**
     * استخراج کلمات کلیدی از محتوا
     */
    private function extract_content_keywords($post) {
        $content = $post->post_title . ' ' . $post->post_content;
        
        // حذف تگ‌های HTML
        $content = wp_strip_all_tags($content);
        
        // حذف کاراکترهای خاص
        $content = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $content);
        
        // تقسیم به کلمات
        $words = preg_split('/\s+/', $content);
        
        // فیلتر کردن کلمات کوتاه و غیر مرتبط
        $keywords = array_filter($words, function($word) {
            $word = trim($word);
            return strlen($word) > 2 && !in_array($word, [
                'این', 'آن', 'که', 'را', 'به', 'از', 'در', 'با', 'برای', 'تا', 'یا', 'ولی', 'اما', 'اگر', 'چون', 'زیرا'
            ]);
        });
        
        // شمارش تکرار کلمات
        $word_count = array_count_values($keywords);
        
        // مرتب‌سازی بر اساس تکرار
        arsort($word_count);
        
        // برگرداندن 10 کلمه پرتکرار
        return array_slice(array_keys($word_count), 0, 10);
    }
    
    /**
     * بررسی مجوزهای فایل و پوشه‌ها
     */
    private function check_file_permissions() {
        $upload_dir = wp_upload_dir();
        $upload_path = $upload_dir['basedir'];
        
        // بررسی مجوز نوشتن در پوشه uploads
        if (!is_writable($upload_path)) {
            return new WP_Error('permission_error', 'پوشه uploads قابل نوشتن نیست. مجوزها را بررسی کنید.');
        }
        
        // بررسی مجوز نوشتن در پوشه‌های سالانه و ماهانه
        $year_month = date('Y/m');
        $year_month_path = $upload_path . '/' . $year_month;
        
        if (!file_exists($year_month_path)) {
            if (!wp_mkdir_p($year_month_path)) {
                return new WP_Error('permission_error', 'نمی‌توان پوشه ' . $year_month_path . ' را ایجاد کرد.');
            }
        }
        
        if (!is_writable($year_month_path)) {
            return new WP_Error('permission_error', 'پوشه ' . $year_month_path . ' قابل نوشتن نیست.');
        }
        
        return true;
    }
    
    /**
     * بررسی محدودیت‌های سرور
     */
    private function check_server_limits() {
        $max_execution_time = ini_get('max_execution_time');
        $memory_limit = ini_get('memory_limit');
        $upload_max_filesize = ini_get('upload_max_filesize');
        
        $warnings = array();
        
        if ($max_execution_time > 0 && $max_execution_time < 30) {
            $warnings[] = 'زمان اجرای محدود: ' . $max_execution_time . ' ثانیه';
        }
        
        if ($memory_limit && $memory_limit !== '-1') {
            $memory_bytes = wp_convert_hr_to_bytes($memory_limit);
            if ($memory_bytes < 64 * 1024 * 1024) { // کمتر از 64MB
                $warnings[] = 'حافظه محدود: ' . $memory_limit;
            }
        }
        
        if ($upload_max_filesize && $upload_max_filesize !== '-1') {
            $upload_bytes = wp_convert_hr_to_bytes($upload_max_filesize);
            if ($upload_bytes < 5 * 1024 * 1024) { // کمتر از 5MB
                $warnings[] = 'اندازه فایل محدود: ' . $upload_max_filesize;
            }
        }
        
        if (!empty($warnings)) {
            error_log('[Smart Admin Image Optimizer] Server limits warnings: ' . implode(', ', $warnings));
        }
        
        return $warnings;
    }

    /**
     * درج تصویر به پست
     */
    private function insert_image_to_post($post_id, $image_url, $alt_text, $insert_type) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        // لاگ شروع عملیات
        error_log('[Smart Admin Image Optimizer] Starting image insertion for post: ' . $post_id);
        error_log('[Smart Admin Image Optimizer] Image URL: ' . $image_url);
        error_log('[Smart Admin Image Optimizer] Insert type: ' . $insert_type);
        
        // بررسی مجوزهای فایل
        $permission_check = $this->check_file_permissions();
        if (is_wp_error($permission_check)) {
            error_log('[Smart Admin Image Optimizer] Permission error: ' . $permission_check->get_error_message());
            return $permission_check;
        }
        error_log('[Smart Admin Image Optimizer] File permissions OK');
        
        // بررسی محدودیت‌های سرور
        $server_warnings = $this->check_server_limits();
        if (!empty($server_warnings)) {
            error_log('[Smart Admin Image Optimizer] Server warnings: ' . implode(', ', $server_warnings));
        }
        
        // بررسی URL تصویر
        if (empty($image_url) || !filter_var($image_url, FILTER_VALIDATE_URL)) {
            error_log('[Smart Admin Image Optimizer] Invalid URL: ' . $image_url);
            return new WP_Error('invalid_url', 'نشانی تصویر نامعتبر است.');
        }
        error_log('[Smart Admin Image Optimizer] URL validation OK');
        
        // بررسی دسترسی به فایل
        error_log('[Smart Admin Image Optimizer] Checking file access...');
        $headers = wp_remote_head($image_url, array(
            'timeout' => 15,
            'sslverify' => false,
            'user-agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url')
        ));
        
        if (is_wp_error($headers)) {
            error_log('[Smart Admin Image Optimizer] Head request error: ' . $headers->get_error_message());
            return new WP_Error('connection_error', 'خطا در اتصال به تصویر: ' . $headers->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($headers);
        error_log('[Smart Admin Image Optimizer] Response code: ' . $response_code);
        
        if ($response_code !== 200) {
            error_log('[Smart Admin Image Optimizer] HTTP error: ' . $response_code);
            return new WP_Error('download_error', 'خطا در دانلود تصویر: کد پاسخ ' . $response_code);
        }
        
        // بررسی نوع فایل
        $content_type = wp_remote_retrieve_header($headers, 'content-type');
        error_log('[Smart Admin Image Optimizer] Content type: ' . $content_type);
        
        if (!preg_match('/^image\/(jpeg|jpg|png|gif|webp)$/i', $content_type)) {
            error_log('[Smart Admin Image Optimizer] Invalid content type: ' . $content_type);
            return new WP_Error('invalid_type', 'فایل تصویر نامعتبر است. نوع فایل: ' . $content_type);
        }
        
        // بررسی اندازه فایل
        $content_length = wp_remote_retrieve_header($headers, 'content-length');
        error_log('[Smart Admin Image Optimizer] Content length: ' . $content_length);
        
        if ($content_length && $content_length > 10 * 1024 * 1024) { // بیش از 10MB
            error_log('[Smart Admin Image Optimizer] File too large: ' . $content_length);
            return new WP_Error('file_too_large', 'فایل تصویر بسیار بزرگ است. حداکثر اندازه: 10MB');
        }
        
        // دانلود و درج تصویر
        error_log('[Smart Admin Image Optimizer] Starting media_sideload_image...');
        // تلاش اول با media_sideload_image
        $attachment_id = media_sideload_image($image_url, $post_id, $alt_text, 'id');
        
        // اگر خطای نشانی نامعتبر بود، تلاش دوم با دانلود دستی
        if (is_wp_error($attachment_id) && $attachment_id->get_error_message() === 'نشانی تصویر نامعتبر.') {
            error_log('[Smart Admin Image Optimizer] Fallback sideload: downloading manually...');
            $tmp = download_url($image_url, 30);
            if (is_wp_error($tmp)) {
                $attachment_id = $tmp;
            } else {
                $filename = 'unsplash-' . time() . '.jpg';
                $file_array = array(
                    'name'     => $filename,
                    'tmp_name' => $tmp,
                );
                $attachment_id = media_handle_sideload($file_array, $post_id, $alt_text);
                // در صورت خطا فایل موقت را حذف کن
                if (is_wp_error($attachment_id)) {
                    @unlink($tmp);
                }
            }
        }
        
        if (is_wp_error($attachment_id)) {
            $error_message = $attachment_id->get_error_message();
            error_log('[Smart Admin Image Optimizer] media_sideload_image error: ' . $error_message);
            
            // بررسی خطاهای خاص
            if (strpos($error_message, 'HTTP') !== false) {
                return new WP_Error('download_error', 'خطا در دانلود تصویر: مشکل شبکه');
            } elseif (strpos($error_message, 'file_get_contents') !== false) {
                return new WP_Error('download_error', 'خطا در دانلود تصویر: مشکل دسترسی به فایل');
            } elseif (strpos($error_message, 'permission') !== false) {
                return new WP_Error('permission_error', 'خطا در دانلود تصویر: مشکل مجوزها');
            } else {
                return new WP_Error('download_error', 'خطا در دانلود تصویر: ' . $error_message);
            }
        }
        
        error_log('[Smart Admin Image Optimizer] media_sideload_image success, attachment_id: ' . $attachment_id);
        
        // بررسی موفقیت‌آمیز بودن درج
        if (!$attachment_id || !is_numeric($attachment_id)) {
            error_log('[Smart Admin Image Optimizer] Invalid attachment_id: ' . $attachment_id);
            return new WP_Error('insert_error', 'خطا در درج تصویر به کتابخانه رسانه');
        }
        
        // تنظیم متن جایگزین
        update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt_text);
        error_log('[Smart Admin Image Optimizer] Alt text set for attachment: ' . $attachment_id);
        
        // به‌روزرسانی اطلاعات فایل
        $file_path = get_attached_file($attachment_id);
        error_log('[Smart Admin Image Optimizer] File path: ' . $file_path);
        
        if ($file_path && file_exists($file_path)) {
            error_log('[Smart Admin Image Optimizer] File exists, updating metadata...');
            wp_update_attachment_metadata($attachment_id, wp_generate_attachment_metadata($attachment_id, $file_path));
        } else {
            error_log('[Smart Admin Image Optimizer] File does not exist: ' . $file_path);
        }
        
        if ($insert_type === 'featured') {
            // تنظیم به عنوان تصویر شاخص
            error_log('[Smart Admin Image Optimizer] Setting as featured image...');
            $result = set_post_thumbnail($post_id, $attachment_id);
            
            if (!$result) {
                error_log('[Smart Admin Image Optimizer] Failed to set featured image');
                return new WP_Error('featured_error', 'خطا در تنظیم تصویر شاخص');
            }
            
            $image_url = get_the_post_thumbnail_url($post_id, 'full');
            error_log('[Smart Admin Image Optimizer] Featured image URL: ' . $image_url);
            
            // اگر URL تصویر شاخص در دسترس نباشد، از attachment استفاده کن
            if (!$image_url) {
                $image_url = wp_get_attachment_url($attachment_id);
                error_log('[Smart Admin Image Optimizer] Using attachment URL: ' . $image_url);
            }
        } else {
            // درج در محتوا
            $image_url = wp_get_attachment_url($attachment_id);
            error_log('[Smart Admin Image Optimizer] Content image URL: ' . $image_url);
        }
        
        // لاگ کردن موفقیت
        error_log('[Smart Admin Image Optimizer] Image successfully inserted: ' . $image_url . ' for post: ' . $post_id);
        
        return [
            'attachment_id' => $attachment_id,
            'image_url' => $image_url,
            'message' => 'تصویر با موفقیت درج شد.'
        ];
    }
    
    /**
     * تست مجوزهای فایل و سرور
     */
    public function test_permissions() {
        $results = array();
        
        // تست مجوزهای فایل
        $permission_check = $this->check_file_permissions();
        if (is_wp_error($permission_check)) {
            $results['permissions'] = array(
                'status' => 'error',
                'message' => $permission_check->get_error_message()
            );
        } else {
            $results['permissions'] = array(
                'status' => 'success',
                'message' => 'مجوزهای فایل درست هستند.'
            );
        }
        
        // تست محدودیت‌های سرور
        $server_warnings = $this->check_server_limits();
        if (!empty($server_warnings)) {
            $results['server_limits'] = array(
                'status' => 'warning',
                'message' => 'هشدارهای محدودیت سرور: ' . implode(', ', $server_warnings)
            );
        } else {
            $results['server_limits'] = array(
                'status' => 'success',
                'message' => 'محدودیت‌های سرور مناسب هستند.'
            );
        }
        
        // تست اتصال به Unsplash
        $api_key = get_option('faraz_unsplash_api_key');
        if (empty($api_key)) {
            $results['unsplash_api'] = array(
                'status' => 'error',
                'message' => 'کلید API Unsplash تنظیم نشده است.'
            );
        } else {
            $test_response = wp_remote_get('https://api.unsplash.com/photos/random?client_id=' . $api_key, array(
                'timeout' => 10,
                'sslverify' => false
            ));
            
            if (is_wp_error($test_response)) {
                $results['unsplash_api'] = array(
                    'status' => 'error',
                    'message' => 'خطا در اتصال به Unsplash: ' . $test_response->get_error_message()
                );
            } else {
                $response_code = wp_remote_retrieve_response_code($test_response);
                if ($response_code === 200) {
                    $results['unsplash_api'] = array(
                        'status' => 'success',
                        'message' => 'اتصال به Unsplash موفقیت‌آمیز است.'
                    );
                } else {
                    $results['unsplash_api'] = array(
                        'status' => 'error',
                        'message' => 'خطا در اتصال به Unsplash: کد پاسخ ' . $response_code
                    );
                }
            }
        }
        
        return $results;
    }
    
    /**
     * اکشن AJAX برای تست مجوزها
     */
    public function ajax_test_permissions() {
        check_ajax_referer('smart_admin_image_optimizer', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('شما دسترسی لازم را ندارید.');
            return;
        }
        
        $results = $this->test_permissions();
        wp_send_json_success($results);
    }
    
    /**
     * تولید خودکار تصویر شاخص
     */
    public function auto_generate_featured_image($post_id, $post) {
        // فقط برای پست‌های جدید
        if ($post->post_status !== 'publish') {
            return;
        }
        
        // بررسی وجود تصویر شاخص
        if (has_post_thumbnail($post_id)) {
            return;
        }
        
        // استخراج کلمات کلیدی
        $keywords = $this->extract_content_keywords($post);
        
        if (empty($keywords)) {
            return;
        }
        
        // جستجوی تصویر
        $images = $this->search_unsplash_images($keywords[0], 1);
        
        if (is_wp_error($images)) {
            return;
        }
        
        // درج تصویر شاخص
        $image = $images[0];
        $this->insert_image_to_post($post_id, $image['url'], $image['alt'], 'featured');
    }

    /**
     * تست مستقیم دانلود تصویر
     */
    public function test_image_download($image_url) {
        error_log('[Smart Admin Image Optimizer] Testing image download: ' . $image_url);
        
        // تست 1: بررسی URL
        if (empty($image_url) || !filter_var($image_url, FILTER_VALIDATE_URL)) {
            return array('status' => 'error', 'message' => 'URL نامعتبر');
        }
        
        // تست 2: بررسی دسترسی
        $headers = wp_remote_head($image_url, array(
            'timeout' => 15,
            'sslverify' => false,
            'user-agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url')
        ));
        
        if (is_wp_error($headers)) {
            return array('status' => 'error', 'message' => 'خطا در اتصال: ' . $headers->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($headers);
        if ($response_code !== 200) {
            return array('status' => 'error', 'message' => 'کد پاسخ: ' . $response_code);
        }
        
        // تست 3: دانلود مستقیم
        $response = wp_remote_get($image_url, array(
            'timeout' => 30,
            'sslverify' => false,
            'user-agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url')
        ));
        
        if (is_wp_error($response)) {
            return array('status' => 'error', 'message' => 'خطا در دانلود: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        if (empty($body)) {
            return array('status' => 'error', 'message' => 'بدنه پاسخ خالی است');
        }
        
        // تست 4: ذخیره موقت
        $upload_dir = wp_upload_dir();
        $temp_file = $upload_dir['basedir'] . '/temp_test_image.jpg';
        
        $file_result = file_put_contents($temp_file, $body);
        if ($file_result === false) {
            return array('status' => 'error', 'message' => 'خطا در ذخیره فایل موقت');
        }
        
        // تست 5: بررسی نوع فایل
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $temp_file);
        finfo_close($finfo);
        
        if (!preg_match('/^image\/(jpeg|jpg|png|gif|webp)$/i', $mime_type)) {
            unlink($temp_file);
            return array('status' => 'error', 'message' => 'نوع فایل نامعتبر: ' . $mime_type);
        }
        
        // پاکسازی فایل موقت
        unlink($temp_file);
        
        return array('status' => 'success', 'message' => 'دانلود موفقیت‌آمیز', 'size' => strlen($body));
    }
    
    /**
     * اکشن AJAX برای تست دانلود تصویر
     */
    public function ajax_test_image_download() {
        check_ajax_referer('smart_admin_image_optimizer', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('شما دسترسی لازم را ندارید.');
            return;
        }
        
        $image_url = isset($_POST['image_url']) ? esc_url_raw($_POST['image_url']) : '';
        
        if (empty($image_url)) {
            wp_send_json_error('URL تصویر ارائه نشده است.');
            return;
        }
        
        $result = $this->test_image_download($image_url);
        
        if ($result['status'] === 'success') {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
}

// راه‌اندازی کلاس
new Smart_Admin_Image_Optimizer(); 