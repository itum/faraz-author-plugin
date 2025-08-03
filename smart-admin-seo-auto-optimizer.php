<?php
/**
 * بهینه‌ساز خودکار SEO برای ادمین هوشمند
 * 
 * این فایل قابلیت بهینه‌سازی خودکار محتوا بر اساس اصول Rank Math را فراهم می‌کند.
 */

// اطمینان از عدم دسترسی مستقیم به فایل
if (!defined('ABSPATH')) {
    exit;
}

/**
 * کلاس بهینه‌ساز خودکار SEO
 */
class Smart_Admin_SEO_Auto_Optimizer {
    
    /**
     * سازنده کلاس و ثبت هوک‌ها
     */
    public function __construct() {
        // افزودن دکمه به صفحه ویرایش نوشته (برای هر دو ویرایشگر)
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // افزودن دکمه به نوار ابزار ویرایشگر کلاسیک
        add_action('media_buttons', array($this, 'add_classic_editor_button'));
        
        // ثبت اکشن‌های AJAX
        add_action('wp_ajax_smart_admin_auto_optimize_seo', array($this, 'ajax_auto_optimize_seo'));
        // اضافه‌شده: پشتیبانی از درخواست‌های غیر لاگین (فرانت‌اند)
        add_action('wp_ajax_nopriv_smart_admin_auto_optimize_seo', array($this, 'ajax_auto_optimize_seo'));
        
        // ثبت تابع در زمان بارگذاری پلاگین
        add_action('init', array($this, 'register_gutenberg_button'));
    }
    
    /**
     * ثبت دکمه در ویرایشگر گوتنبرگ
     */
    public function register_gutenberg_button() {
        // فقط برای ادمین
        if (!is_admin()) {
            return;
        }
        
        // بررسی وجود تابع register_block_type (گوتنبرگ)
        if (!function_exists('register_block_type')) {
            return;
        }
        
        // ثبت اسکریپت‌های گوتنبرگ
        wp_register_script(
            'smart-admin-seo-optimizer-gutenberg',
            plugin_dir_url(__FILE__) . 'js/smart-admin-seo-optimizer-gutenberg.js',
            array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n', 'wp-data'),
            time(),
            true
        );
        
        // ثبت بلوک
        register_block_type('smart-admin/seo-optimizer', array(
            'editor_script' => 'smart-admin-seo-optimizer-gutenberg',
        ));
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
            'smart-admin-seo-optimizer-css',
            plugin_dir_url(__FILE__) . 'css/smart-admin-seo-optimizer.css',
            array(),
            time()
        );
        
        // ثبت اسکریپت برای ویرایشگر کلاسیک
        wp_enqueue_script(
            'smart-admin-seo-optimizer-js',
            plugin_dir_url(__FILE__) . 'js/smart-admin-seo-optimizer.js',
            array('jquery'),
            time(),
            true
        );
        
        // افزودن متغیرهای مورد نیاز به جاوااسکریپت
        wp_localize_script('smart-admin-seo-optimizer-js', 'smartAdminSEO', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('smart_admin_seo_optimizer'),
            'optimizing_text' => 'در حال بهینه‌سازی SEO...',
            'success_text' => 'بهینه‌سازی SEO با موفقیت انجام شد',
            'error_text' => 'خطا در بهینه‌سازی SEO',
            'check_text' => 'بررسی نیازمندی‌های SEO',
            'optimize_text' => 'بهینه‌سازی خودکار SEO',
            'icon' => '<span class="dashicons dashicons-chart-line"></span>'
        ));
    }
    
    /**
     * افزودن دکمه به نوار ابزار ویرایشگر کلاسیک
     */
    public function add_classic_editor_button() {
        echo '<button type="button" id="smart-admin-seo-optimizer-button" class="button">';
        echo '<span class="dashicons dashicons-chart-line" style="margin: 3px 5px 0 0;"></span>';
        echo 'بهینه‌سازی خودکار SEO';
        echo '</button>';
    }
    
    /**
     * پردازش درخواست AJAX برای بهینه‌سازی خودکار SEO
     */
    public function ajax_auto_optimize_seo() {
        // بررسی نانس امنیتی
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'smart_admin_seo_optimizer')) {
            // اگر کاربر لاگین نیست، اجازه ادامه می‌دهیم ولی هشدار امنیتی ثبت می‌کنیم
            if (!is_user_logged_in()) {
                $this->log_optimization('هشدار امنیتی: درخواست بدون نانس معتبر از کاربر مهمان.');
            } else {
                wp_send_json_error('خطای امنیتی: نانس نامعتبر است.');
                return;
            }
        }
        
        // بررسی دسترسی کاربر
        if (is_user_logged_in() && !current_user_can('edit_posts')) {
            wp_send_json_error('شما دسترسی لازم را ندارید.');
            return;
        }
        
        // دریافت شناسه پست
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        if (!$post_id) {
            wp_send_json_error('شناسه پست نامعتبر است.');
            return;
        }
        
        // دریافت محتوا و عنوان پست
        $post = get_post($post_id);
        if (!$post) {
            wp_send_json_error('پست مورد نظر یافت نشد.');
            return;
        }
        
        // بررسی وجود افزونه Rank Math
        if (!function_exists('rank_math')) {
            $this->log_optimization('افزونه Rank Math فعال نیست. استفاده از محاسبه داخلی امتیاز SEO.');
        }
        
        // شروع بهینه‌سازی
        $result = $this->optimize_seo($post);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
            return;
        }
        
        // اطمینان از عددی بودن امتیاز SEO
        if (isset($result['data']['seo_score'])) {
            $result['data']['seo_score'] = intval($result['data']['seo_score']);
        } else {
            // محاسبه امتیاز اگر وجود ندارد
            $keywords = isset($result['data']['focus_keywords']) ? explode(',', $result['data']['focus_keywords']) : array();
            $recommendations = isset($result['data']['recommendations']) ? $result['data']['recommendations'] : array();
            $result['data']['seo_score'] = $this->calculate_seo_score($post, $keywords, $recommendations);
        }
        
        // ثبت خروجی برای عیب‌یابی
        $this->log_optimization('نتیجه بهینه‌سازی: ' . json_encode($result['data']));
        
        wp_send_json_success($result['data']);
    }
    
    /**
     * بهینه‌سازی SEO پست
     * 
     * @param WP_Post $post آبجکت پست
     * @return array نتیجه بهینه‌سازی
     */
    private function optimize_seo($post) {
        // اطمینان از لود نسخهٔ جدید در صورت فعال بودن OPcache
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate(__FILE__, true);
        }
        $optimization_log = array();
        $updated_data = array();
        
        // 1. استخراج و بهینه‌سازی کلمات کلیدی
        $keywords = $this->extract_keywords($post);
        $optimization_log[] = 'کلمات کلیدی استخراج شده: ' . implode('، ', $keywords);
        
        // 2. بهینه‌سازی عنوان SEO
        $seo_title = $this->optimize_seo_title($post, $keywords);
        if ($seo_title) {
            update_post_meta($post->ID, 'rank_math_title', $seo_title);
            $updated_data['seo_title'] = $seo_title;
            $optimization_log[] = 'عنوان SEO بهینه شد: ' . $seo_title;
        }
        
        // 3. بهینه‌سازی توضیحات متا
        $meta_description = $this->optimize_meta_description($post, $keywords);
        if ($meta_description) {
            update_post_meta($post->ID, 'rank_math_description', $meta_description);
            $updated_data['meta_description'] = $meta_description;
            $optimization_log[] = 'توضیحات متا بهینه شد: ' . $meta_description;
        }
        
        // 4. بهینه‌سازی URL
        $slug = $this->optimize_slug($post, $keywords);
        if ($slug && $slug !== $post->post_name) {
            wp_update_post(array(
                'ID' => $post->ID,
                'post_name' => $slug
            ));
            $updated_data['slug'] = $slug;
            $optimization_log[] = 'نامک (URL) بهینه شد: ' . $slug;
        }
        
        // 5. تنظیم کلمات کلیدی اصلی Rank Math
        $focus_keyword = implode(', ', array_slice($keywords, 0, 5));
        update_post_meta($post->ID, 'rank_math_focus_keyword', $focus_keyword);
        $updated_data['focus_keywords'] = $focus_keyword;
        $optimization_log[] = 'کلمات کلیدی اصلی تنظیم شدند: ' . $focus_keyword;

        /*
         * 5.5 بهینه‌سازی تصاویر و لینک‌سازی هوشمند
         *  - افزودن alt به تصاویر فاقد alt
         *  - لینک‌سازی داخلی بر اساس کلمات کلیدی و پست‌های موجود
         *  - لینک‌سازی خارجی (ویکی‌پدیا به‌عنوان منبع معتبر پیش‌فرض)
         */

        $content_before = $post->post_content;
        $primary_kw     = !empty($keywords[0]) ? $keywords[0] : '';

        $internal_links_added = array();
        $external_links_added = array();
        $skipped_tasks         = array();

        // افزودن alt به تصاویر
        $alt_result        = $this->optimize_images_alt($content_before, $primary_kw);
        $optimized_content = $alt_result['content'];
        $alt_added_count   = $alt_result['added_count'];

        if ($alt_added_count > 0) {
            $optimization_log[]           = $alt_added_count . ' ویژگی alt به تصاویر افزوده شد';
            $updated_data['alt_added']    = $alt_added_count;
        } else {
            $skipped_tasks[] = 'هیچ تصویری بدون alt یافت نشد';
        }

        // لینک‌سازی داخلی
        $optimized_content = $this->add_internal_links($optimized_content, $keywords, $post->ID, $internal_links_added);
        if (empty($internal_links_added)) {
            $skipped_tasks[] = 'هیچ لینک داخلی مناسب یافت نشد';
        }

        // لینک‌سازی خارجی
        $optimized_content = $this->add_external_links($optimized_content, $keywords, $external_links_added);

        if (empty($external_links_added)) {
            $skipped_tasks[] = 'هیچ لینک خارجی مناسب یافت نشد';
        }

        // اگر محتوا تغییر کرد، ذخیره کنیم
        if ($optimized_content !== $content_before) {
            wp_update_post(array(
                'ID'           => $post->ID,
                'post_content' => $optimized_content
            ));
            $optimization_log[] = 'محتوا با موفقیت بروزرسانی شد (تصاویر و لینک‌ها)';
        }

        // گزارش لینک‌ها و شمارش
        $updated_data['internal_links_count'] = count($internal_links_added);
        $updated_data['external_links_count'] = count($external_links_added);

        if (!empty($internal_links_added)) {
            $updated_data['internal_links'] = $internal_links_added;
            $optimization_log[] = 'لینک‌های داخلی افزوده شد: ' . implode(', ', $internal_links_added);
        } else {
            $optimization_log[] = 'هیچ لینک داخلی افزوده نشد';
        }

        if (!empty($external_links_added)) {
            $updated_data['external_links'] = $external_links_added;
            $optimization_log[] = 'لینک‌های خارجی افزوده شد: ' . implode(', ', $external_links_added);
        } else {
            $optimization_log[] = 'هیچ لینک خارجی افزوده نشد';
        }

        if (!empty($skipped_tasks)) {
            $updated_data['skipped'] = $skipped_tasks;
            $optimization_log[] = 'موارد انجام‌نشده: ' . implode(' | ', $skipped_tasks);
        }
        
        // 6. بررسی و توصیه برای بهبود محتوا
        $content_recommendations = $this->analyze_content($post, $keywords);
        $updated_data['recommendations'] = $content_recommendations;
        
        // 7. محاسبه و ذخیره امتیاز SEO
        $seo_score = $this->calculate_seo_score(
            $post,
            $keywords,
            $content_recommendations,
            count($internal_links_added),
            count($external_links_added),
            $alt_added_count
        );
        update_post_meta($post->ID, 'rank_math_seo_score', $seo_score);
        $updated_data['seo_score'] = $seo_score;
        $optimization_log[] = 'امتیاز SEO محاسبه شده: ' . $seo_score;
        
        // ثبت تغییرات در لاگ
        $this->log_optimization('بهینه‌سازی خودکار SEO برای پست ' . $post->ID . ': ' . implode(' | ', $optimization_log));
        
        return array(
            'message' => 'بهینه‌سازی SEO با موفقیت انجام شد.',
            'data' => $updated_data
        );
    }
    
    /**
     * محاسبه امتیاز SEO
     * 
     * @param WP_Post $post آبجکت پست
     * @param array $keywords آرایه کلمات کلیدی
     * @param array $recommendations توصیه‌های محتوایی
     * @return int امتیاز SEO (از 0 تا 100)
     */
    private function calculate_seo_score($post, $keywords, $recommendations, $internal_links = 0, $external_links = 0, $alt_added = 0) {
         $score = 40; // امتیاز پایه واقع‌بینانه
         $content = $post->post_content;
         $title = $post->post_title;
         
         // 1. طول محتوا (20 امتیاز)
         $content_length = mb_strlen(strip_tags($content), 'UTF-8');
         if ($content_length > 1500) {
             $score += 20;
         } elseif ($content_length > 1000) {
             $score += 15;
         } elseif ($content_length > 600) {
             $score += 10;
         } elseif ($content_length > 300) {
             $score += 5;
         }
         
         // 2. وجود کلمه کلیدی اصلی در عنوان (10 امتیاز)
         if (!empty($keywords[0]) && stripos($title, $keywords[0]) !== false) {
             $score += 10;
         }
         
         // 3. وجود عناوین فرعی (H2, H3) (10 امتیاز)
         if (preg_match('/<h[2-3][^>]*>.*?<\/h[2-3]>/i', $content)) {
             $score += 10;
         }
         
         // 4. وجود تصاویر (10 امتیاز)
         $img_count = substr_count($content, '<img');
         if ($img_count > 2) {
             $score += 10;
         } elseif ($img_count > 0) {
             $score += 5;
         }
         
         // 5. تراکم کلمات کلیدی (10 امتیاز)
         if (!empty($keywords[0])) {
             $keyword_density = substr_count(strtolower(strip_tags($content)), strtolower($keywords[0])) / max(1, $content_length) * 100;
             if ($keyword_density >= 0.5 && $keyword_density <= 2.5) {
                 $score += 10;
             } elseif ($keyword_density > 0 && $keyword_density < 5) {
                 $score += 5;
             }
         }
         
         // 6. لینک‌های داخلی (پاداش/جریمه)
         if ($internal_links > 0) {
             $score += 10;
         } else {
             $score -= 10;
         }

         // ۷. لینک‌های خارجی (پاداش/جریمه)
         if ($external_links > 0) {
             $score += 5;
         } else {
             $score -= 5;
         }

         // ۸. alt تصاویر (۵ امتیاز در صورت وجود)
         if ($alt_added > 0) {
             $score += 5;
         }

         // ۹. پنالتی برای توصیه‌ها (۲ امتیاز)
         $score -= count($recommendations) * 2;
 
         // محدود کردن امتیاز بین 0 تا 90 (کمی فضای بهبود نگه داریم)
         $score = max(0, min(90, $score));
 
         return intval($score);
     }
    
    /**
     * استخراج کلمات کلیدی از محتوا
     * 
     * @param WP_Post $post آبجکت پست
     * @return array آرایه کلمات کلیدی
     */
    private function extract_keywords($post) {
        $title = $post->post_title;
        $content = $post->post_content;
        
        // بررسی کلمات کلیدی موجود
        $existing_keywords = get_post_meta($post->ID, 'rank_math_focus_keyword', true);
        if (!empty($existing_keywords)) {
            // جداسازی با کاما یا خط جدید
            if (strpos($existing_keywords, ',') !== false) {
                return array_map('trim', explode(',', $existing_keywords));
            } elseif (strpos($existing_keywords, "\n") !== false) {
                return array_map('trim', explode("\n", $existing_keywords));
            } else {
                return array($existing_keywords);
            }
        }
        
        // استخراج کلمات کلیدی از عنوان
        $title_keywords = $this->extract_title_keywords($title);
        
        // استخراج کلمات کلیدی از محتوا
        $content_keywords = $this->extract_content_keywords($content);
        
        // ترکیب و اولویت‌بندی کلمات کلیدی
        $all_keywords = array_merge($title_keywords, $content_keywords);
        $keywords = array_unique($all_keywords);
        
        // حداکثر 5 کلمه کلیدی
        return array_slice($keywords, 0, 5);
    }
    
    /**
     * استخراج کلمات کلیدی از عنوان
     * 
     * @param string $title عنوان پست
     * @return array آرایه کلمات کلیدی
     */
    private function extract_title_keywords($title) {
        // حذف کلمات بی‌اهمیت
        $stop_words = array('و', 'در', 'به', 'از', 'که', 'این', 'را', 'با', 'است', 'برای', 'آن', 'یک', 'شما', 'خود', 'تا');
        
        // تقسیم عنوان به کلمات
        $words = preg_split('/[\s\:\.\,\؛\!\؟\)\(\]\[\}\{\"\']+/u', $title);
        
        $keywords = array();
        foreach ($words as $word) {
            $word = trim($word);
            if (mb_strlen($word, 'UTF-8') > 2 && !in_array($word, $stop_words)) {
                $keywords[] = $word;
            }
        }
        
        return $keywords;
    }
    
    /**
     * استخراج کلمات کلیدی از محتوا
     * 
     * @param string $content محتوای پست
     * @return array آرایه کلمات کلیدی
     */
    private function extract_content_keywords($content) {
        // حذف تگ‌های HTML
        $content = strip_tags($content);
        
        // حذف کلمات بی‌اهمیت
        $stop_words = array('و', 'در', 'به', 'از', 'که', 'این', 'را', 'با', 'است', 'برای', 'آن', 'یک', 'شما', 'خود', 'تا');
        
        // تقسیم محتوا به کلمات
        $words = preg_split('/[\s\:\.\,\؛\!\؟\)\(\]\[\}\{\"\']+/u', $content);
        
        // شمارش تکرار کلمات
        $word_count = array();
        foreach ($words as $word) {
            $word = trim($word);
            if (mb_strlen($word, 'UTF-8') > 2 && !in_array($word, $stop_words)) {
                if (isset($word_count[$word])) {
                    $word_count[$word]++;
                } else {
                    $word_count[$word] = 1;
                }
            }
        }
        
        // مرتب‌سازی بر اساس تکرار (نزولی)
        arsort($word_count);
        
        // انتخاب 10 کلمه پرتکرار
        return array_slice(array_keys($word_count), 0, 10);
    }
    
    /**
     * بهینه‌سازی عنوان SEO
     * 
     * @param WP_Post $post آبجکت پست
     * @param array $keywords آرایه کلمات کلیدی
     * @return string عنوان SEO بهینه شده
     */
    private function optimize_seo_title($post, $keywords) {
        // بررسی وجود عنوان SEO موجود
        $existing_title = get_post_meta($post->ID, 'rank_math_title', true);
        if (!empty($existing_title)) {
            return $existing_title;
        }
        
        $title = $post->post_title;
        $site_name = get_bloginfo('name');
        
        // اطمینان از وجود کلمه کلیدی اصلی در عنوان
        $primary_keyword = isset($keywords[0]) ? $keywords[0] : '';
        if (!empty($primary_keyword) && stripos($title, $primary_keyword) === false) {
            $title = $primary_keyword . ' | ' . $title;
        }
        
        // افزودن نام سایت
        $seo_title = $title . ' | ' . $site_name;
        
        // محدودیت طول (حداکثر 60 کاراکتر)
        if (mb_strlen($seo_title, 'UTF-8') > 60) {
            $seo_title = mb_substr($seo_title, 0, 57, 'UTF-8') . '...';
        }
        
        return $seo_title;
    }
    
    /**
     * بهینه‌سازی توضیحات متا
     * 
     * @param WP_Post $post آبجکت پست
     * @param array $keywords آرایه کلمات کلیدی
     * @return string توضیحات متا بهینه شده
     */
    private function optimize_meta_description($post, $keywords) {
        // بررسی وجود توضیحات متا موجود
        $existing_description = get_post_meta($post->ID, 'rank_math_description', true);
        if (!empty($existing_description)) {
            return $existing_description;
        }
        
        // استخراج خلاصه از محتوا
        $content = strip_tags($post->post_content);
        $content = preg_replace('/\s+/', ' ', $content);
        
        // انتخاب ۲۰۰ کاراکتر اول
        $description = mb_substr($content, 0, 197, 'UTF-8');
        
        // افزودن سه نقطه
        if (mb_strlen($content, 'UTF-8') > 197) {
            $description .= '...';
        }
        
        // اطمینان از وجود کلمه کلیدی اصلی در توضیحات
        $primary_keyword = isset($keywords[0]) ? $keywords[0] : '';
        if (!empty($primary_keyword) && stripos($description, $primary_keyword) === false) {
            $description = $primary_keyword . ': ' . $description;
            
            // تنظیم مجدد طول
            if (mb_strlen($description, 'UTF-8') > 200) {
                $description = mb_substr($description, 0, 197, 'UTF-8') . '...';
            }
        }
        
        return $description;
    }
    
    /**
     * بهینه‌سازی نامک (URL)
     * 
     * @param WP_Post $post آبجکت پست
     * @param array $keywords آرایه کلمات کلیدی
     * @return string نامک بهینه شده
     */
    private function optimize_slug($post, $keywords) {
        // اگر نامک سفارشی تنظیم شده، آن را تغییر نمی‌دهیم
        if ($post->post_name && $post->post_name !== sanitize_title($post->post_title)) {
            return $post->post_name;
        }
        
        // ساخت نامک بر اساس کلمات کلیدی
        $primary_keyword = isset($keywords[0]) ? $keywords[0] : '';
        $secondary_keyword = isset($keywords[1]) ? $keywords[1] : '';
        
        if (!empty($primary_keyword) && !empty($secondary_keyword)) {
            $slug = sanitize_title($primary_keyword . '-' . $secondary_keyword);
        } elseif (!empty($primary_keyword)) {
            $slug = sanitize_title($primary_keyword);
        } else {
            $slug = sanitize_title($post->post_title);
        }
        
        return $slug;
    }
    
    /**
     * تحلیل محتوا و ارائه توصیه‌ها
     * 
     * @param WP_Post $post آبجکت پست
     * @param array $keywords آرایه کلمات کلیدی
     * @return array آرایه توصیه‌ها
     */
    private function analyze_content($post, $keywords) {
        $recommendations = array();
        $content = $post->post_content;
        $word_count = str_word_count(strip_tags($content));
        
        // 1. بررسی طول محتوا
        if ($word_count < 300) {
            $recommendations[] = 'محتوای شما کوتاه است. برای بهبود SEO، حداقل 300 کلمه بنویسید (فعلی: ' . $word_count . ').';
        }
        
        // 2. بررسی تصاویر
        $has_images = preg_match('/<img[^>]+>/i', $content);
        if (!$has_images) {
            $recommendations[] = 'افزودن حداقل یک تصویر به محتوا برای بهبود SEO توصیه می‌شود.';
        }
        
        // 3. بررسی عناوین فرعی (H2, H3)
        $has_headings = preg_match('/<h[2-3][^>]*>.*?<\/h[2-3]>/i', $content);
        if (!$has_headings) {
            $recommendations[] = 'استفاده از عناوین فرعی (H2, H3) برای ساختاربندی بهتر محتوا توصیه می‌شود.';
        }
        
        // 4. بررسی فهرست مطالب
        $has_toc = preg_match('/فهرست|مطالب|table of contents|toc/i', $content);
        if (!$has_toc && $word_count > 600) {
            $recommendations[] = 'برای محتوای طولانی، افزودن فهرست مطالب توصیه می‌شود.';
        }
        
        // 5. بررسی پاراگراف‌های طولانی
        $paragraphs = preg_split('/<\/p>/', $content);
        foreach ($paragraphs as $index => $paragraph) {
            $p_word_count = str_word_count(strip_tags($paragraph));
            if ($p_word_count > 120) {
                $recommendations[] = 'پاراگراف ' . ($index + 1) . ' بسیار طولانی است. پاراگراف‌ها را به بخش‌های کوچک‌تر تقسیم کنید.';
                break;
            }
        }
        
        // 6. بررسی کلمات کلیدی در محتوا
        $primary_keyword = isset($keywords[0]) ? $keywords[0] : '';
        if (!empty($primary_keyword)) {
            $keyword_density = substr_count(strtolower(strip_tags($content)), strtolower($primary_keyword)) / $word_count * 100;
            
            if ($keyword_density < 0.5) {
                $recommendations[] = 'تراکم کلمه کلیدی اصلی کم است. بیشتر از کلمه کلیدی "' . $primary_keyword . '" در متن استفاده کنید.';
            } elseif ($keyword_density > 2.5) {
                $recommendations[] = 'تراکم کلمه کلیدی اصلی بیش از حد است. استفاده از کلمه کلیدی "' . $primary_keyword . '" را کاهش دهید.';
            }
        }
        
        return $recommendations;
    }

    /**
     * افزودن alt به تصاویر فاقد alt
     * 
     * @param string $content محتوای پست قبل از بهینه‌سازی
     * @param string $primary_keyword کلمه کلیدی اصلی
     * @return string محتوای پست با alt‌های تصاویر
     */
    private function optimize_images_alt($content, $primary_keyword) {
         $pattern_all_imgs = '/<img[^>]*>/i';
         preg_match_all($pattern_all_imgs, $content, $all_imgs);

         $added_count = 0;
         $optimized_content = $content;

         foreach ($all_imgs[0] as $img_tag) {
             // اگر alt موجود نیست یا خالی است
             if (!preg_match('/alt="[^"]*"/i', $img_tag)) {
                 $new_tag = preg_replace('/<img/i', '<img alt="' . esc_attr($primary_keyword) . '"', $img_tag, 1);
                 $optimized_content = str_replace($img_tag, $new_tag, $optimized_content);
                 $added_count++;
             }
         }

         return array(
             'content'     => $optimized_content,
             'added_count' => $added_count,
         );
     }

    /**
     * لینک‌سازی داخلی بر اساس کلمات کلیدی و پست‌های موجود
     * 
     * @param string $content محتوای پست قبل از بهینه‌سازی
     * @param array $keywords آرایه کلمات کلیدی
     * @param int $post_id شناسه پست
     * @param array $internal_links_added آرایه برای ذخیره لینک‌های اضافه شده
     * @return string محتوای پست با لینک‌های داخلی
     */
    private function add_internal_links($content, $keywords, $post_id, &$internal_links_added) {
         $optimized = $content;
         $inserted  = 0;

         foreach ($keywords as $kw) {
             if ($inserted >= 3) { // سقف ۳ لینک داخلی برای جلوگیری از افراط
                 break;
             }

             // اگر anchor قبلاً وجود دارد، رد شو
             if ($this->link_exists($optimized, $kw)) {
                 continue;
             }

             $related_posts = $this->get_related_posts($post_id, $kw);
             if (empty($related_posts)) {
                 continue;
             }

             $post_obj = $related_posts[0];
             $url      = esc_url( get_permalink($post_obj->ID) );
             $pattern  = '/('.preg_quote($kw, '/').')/iu';

             if (preg_match($pattern, $optimized, $m)) {
                 $replacement = '<a href="'.$url.'" rel="internal">'.$m[0].'</a>';
                 $optimized   = preg_replace($pattern, $replacement, $optimized, 1);
                 $internal_links_added[] = $kw.' ('.$url.')';
                 $inserted++;
             }
         }

         // اگر هنوز لینکی اضافه نشده است، حداقل «صفحه اصلی» لینک شود.
         if ($inserted === 0) {
             $home_pattern = '/صفحه\s+اصلی/iu';
             if (preg_match($home_pattern, $optimized)) {
                 $optimized = preg_replace($home_pattern, '<a href="'.esc_url( home_url() ).'" rel="internal">صفحه اصلی</a>', $optimized, 1);
                 $internal_links_added[] = 'صفحه اصلی ('.home_url().')';
             }
         }

         return $optimized;
     }

     // بررسی وجود anchor برای یک کلمه در متن
     private function link_exists($html, $keyword) {
         return preg_match('/<a[^>]*>[^<]*'.preg_quote($keyword, '/').'[^<]*<\/a>/iu', $html);
     }

     /**
      * لینک‌سازی خارجی هوشمند
      */
     private function add_external_links($content, $keywords, &$external_links_added) {
         $optimized = $content;
         $inserted  = 0;

         foreach ($keywords as $kw) {
             if ($inserted >= 2) break; // سقف دو لینک خارجی

             $wiki = 'https://fa.wikipedia.org/wiki/'.urlencode($kw);
             $wiki = esc_url_raw($wiki);

             // اگر anchor قبلاً وجود ندارد
             if (!$this->link_exists($optimized, $kw)) {
                 $pattern = '/('.preg_quote($kw, '/').')/iu';
                 if (preg_match($pattern, $optimized, $m)) {
                     $replacement = '<a href="'.$wiki.'" target="_blank" rel="noopener noreferrer nofollow">'.$m[0].'</a>';
                     $optimized   = preg_replace($pattern, $replacement, $optimized, 1);
                     $external_links_added[] = $kw.' ('.$wiki.')';
                     $inserted++;
                     continue;
                 }
             }

             // اگر در متن نبود با پاراگراف پایانی اضافه کن
             if ($inserted === 0) {
                 $optimized .= '<p><a href="'.$wiki.'" target="_blank" rel="noopener noreferrer nofollow">بیشتر درباره '.$kw.'</a></p>';
                 $external_links_added[] = $kw.' ('.$wiki.')';
                 $inserted++;
             }
         }

         return $optimized;
     }

    /**
     * دریافت پست‌های مرتبط با کلمه کلیدی از پست فعلی
     * 
     * @param int $current_post_id شناسه پست فعلی
     * @param string $keyword کلمه کلیدی
     * @return array آرایه پست‌های مرتبط
     */
    private function get_related_posts($current_post_id, $keyword) {
        $args = array(
            'post_type' => 'post',
            'posts_per_page' => 5, // تعداد پست‌های مرتبط
            'post__not_in' => array($current_post_id), // حذف پست فعلی از نتیجه
            'meta_key' => 'rank_math_focus_keyword', // فیلتر بر اساس کلمات کلیدی
            'meta_value' => $keyword,
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => 'rank_math_focus_keyword',
                    'value' => $keyword,
                    'compare' => 'LIKE'
                )
            )
        );
        return get_posts($args);
    }
    
    /**
     * ثبت گزارش در فایل لاگ
     * 
     * @param string $message پیام لاگ
     */
    private function log_optimization($message) {
        if (function_exists('smart_admin_log')) {
            smart_admin_log($message);
        } else {
            $log_file = plugin_dir_path(__FILE__) . 'smart-admin-debug.log';
            $log_message = '[' . date('Y-m-d H:i:s') . '] ' . $message . "\n";
            file_put_contents($log_file, $log_message, FILE_APPEND);
        }
    }
    
    /**
     * دریافت امتیاز SEO از Rank Math
     * 
     * @param int $post_id شناسه پست
     * @return int امتیاز SEO
     */
    private function get_rank_math_score($post_id) {
        $score = get_post_meta($post_id, 'rank_math_seo_score', true);
        
        if (empty($score) || !is_numeric($score)) {
            // اگر امتیاز موجود نیست، آن را محاسبه می‌کنیم
            $post = get_post($post_id);
            if (!$post) {
                return 50; // امتیاز پیش‌فرض
            }
            
            // استخراج کلمات کلیدی
            $keywords = $this->extract_keywords($post);
            
            // بررسی محتوا
            $recommendations = $this->analyze_content($post, $keywords);
            
            // محاسبه امتیاز
            $score = $this->calculate_seo_score($post, $keywords, $recommendations);
            
            // ذخیره امتیاز
            update_post_meta($post_id, 'rank_math_seo_score', $score);
        }
        
        return intval($score);
    }
}

// نمونه‌سازی کلاس
$smart_admin_seo_auto_optimizer = new Smart_Admin_SEO_Auto_Optimizer(); 