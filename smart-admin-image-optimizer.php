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
        
        // ثبت استایل‌ها
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
            'message' => 'تصویر با موفقیت درج شد.',
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
     * درج تصویر به پست
     */
    private function insert_image_to_post($post_id, $image_url, $alt_text, $insert_type) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        // دانلود و درج تصویر
        $attachment_id = media_sideload_image($image_url, $post_id, $alt_text, 'id');
        
        if (is_wp_error($attachment_id)) {
            return new WP_Error('download_error', 'خطا در دانلود تصویر: ' . $attachment_id->get_error_message());
        }
        
        // تنظیم متن جایگزین
        update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt_text);
        
        if ($insert_type === 'featured') {
            // تنظیم به عنوان تصویر شاخص
            set_post_thumbnail($post_id, $attachment_id);
            $image_url = get_the_post_thumbnail_url($post_id, 'full');
        } else {
            // درج در محتوا
            $image_url = wp_get_attachment_url($attachment_id);
        }
        
        return [
            'attachment_id' => $attachment_id,
            'image_url' => $image_url
        ];
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
}

// راه‌اندازی کلاس
new Smart_Admin_Image_Optimizer(); 