<?php
/**
 * ذخیره محتوای تولید شده توسط هوش مصنوعی به عنوان پیش‌نویس در وردپرس
 * 
 * این فایل شامل توابع لازم برای ایجاد دسته‌بندی دستیار هوشمند و ذخیره محتوا در وردپرس است
 */

// ایجاد دسته‌بندی دستیار هوشمند در هنگام فعال‌سازی افزونه
function smart_admin_create_assistant_category() {
    // بررسی آیا دسته‌بندی قبلاً ایجاد شده است
    $cat_ID = get_option('smart_admin_assistant_category_id');
    if (!$cat_ID) {
        // ایجاد دسته‌بندی جدید
        $cat_name = 'دستیار هوشمند';
        $cat_slug = 'smart-assistant';
        $cat_args = array(
            'cat_name' => $cat_name,
            'category_description' => 'محتوای تولید شده توسط دستیار هوشمند هوش مصنوعی',
            'category_nicename' => $cat_slug,
            'category_parent' => 0
        );
        
        // درج دسته‌بندی جدید
        $cat_ID = wp_insert_category($cat_args);
        
        // ذخیره شناسه دسته‌بندی در تنظیمات
        if (!is_wp_error($cat_ID)) {
            update_option('smart_admin_assistant_category_id', $cat_ID);
        }
    }
}
add_action('admin_init', 'smart_admin_create_assistant_category');

// ذخیره محتوای تولید شده به عنوان پیش‌نویس
function smart_admin_save_ai_content_as_draft($title, $content, $keywords = array()) {
    // استخراج پیوند یکتای بهینه شده برای SEO
    $slug = '';
    if (function_exists('smart_admin_extract_seo_slug')) {
        $slug = smart_admin_extract_seo_slug($content, $title, $keywords);
        if (function_exists('smart_admin_debug_log')) {
            smart_admin_debug_log('Generated SEO slug for post: ' . $slug, 'INFO');
        }
    }
    
    // ایجاد آرایه پست
    $post_data = array(
        'post_title'    => sanitize_text_field($title),
        'post_content'  => wp_kses_post($content),
        'post_status'   => 'draft',
        'post_type'     => 'post',
        'post_author'   => get_current_user_id(),
        'post_name'     => $slug, // تنظیم پیوند یکتا
        'post_category' => array(get_option('smart_admin_assistant_category_id')),
        'meta_input'    => array(
            'smart_admin_generated' => 'yes',
            'smart_admin_generation_date' => current_time('mysql')
        )
    );
    
    // درج پست جدید
    $post_id = wp_insert_post($post_data);
    
    // فقط ارسال کلمات کلیدی به Rank Math بدون اضافه کردن به برچسب‌های وردپرس
    if (!empty($keywords) && !is_wp_error($post_id)) {
        // استفاده از تایمر تاخیری برای اطمینان از ذخیره‌سازی کامل پست
        wp_schedule_single_event(time() + 2, 'smart_admin_delayed_post_saved', array($post_id, $keywords));
        
        // لاگ برای تشخیص روند اجرا
        if (function_exists('faraz_unsplash_log')) {
            faraz_unsplash_log('Smart Admin: Post created with ID ' . $post_id . ' and keywords scheduled for Rank Math: ' . implode(', ', $keywords));
        }
    }

    if (!is_wp_error($post_id)) {
        // تولید خودکار تصویر شاخص بر اساس محتوا
        if (function_exists('smart_generate_featured_image')) {
            smart_generate_featured_image($post_id, $title, $content);
        }
    }
    
    return $post_id;
}

// اکشن تاخیری برای تنظیم کلمات کلیدی
add_action('smart_admin_delayed_post_saved', function($post_id, $keywords) {
    if (function_exists('faraz_unsplash_log')) {
        faraz_unsplash_log('Smart Admin: Delayed action for setting keywords - Post ID: ' . $post_id);
    }
    
    // اطمینان از تکمیل ذخیره‌سازی پست
    $post = get_post($post_id);
    if (!$post) {
        if (function_exists('faraz_unsplash_log')) {
            faraz_unsplash_log('Smart Admin: Post with ID ' . $post_id . ' not found.');
        }
        return;
    }
    
    if (function_exists('smart_admin_set_rank_math_focus_keyword')) {
        smart_admin_set_rank_math_focus_keyword($post_id, $keywords);
    } else {
        if (function_exists('faraz_unsplash_log')) {
            faraz_unsplash_log('Smart Admin: Function smart_admin_set_rank_math_focus_keyword is not defined.');
        }
    }
}, 10, 2);

// تابع دریافت پیش‌نویس‌های ایجاد شده توسط دستیار هوشمند
function smart_admin_get_ai_drafts($limit = 10) {
    $args = array(
        'post_type'      => 'post',
        'post_status'    => 'draft',
        'posts_per_page' => $limit,
        'category'       => get_option('smart_admin_assistant_category_id'),
        'meta_query'     => array(
            array(
                'key'     => 'smart_admin_generated',
                'value'   => 'yes',
                'compare' => '='
            )
        )
    );
    
    return get_posts($args);
}

// افزودن متاباکس برای نمایش اطلاعات تولید محتوا
function smart_admin_add_metabox() {
    add_meta_box(
        'smart_admin_metabox',
        'اطلاعات دستیار هوشمند',
        'smart_admin_status_metabox_callback',
        'post',
        'side',
        'high'
    );
}
add_action('add_meta_boxes', 'smart_admin_add_metabox');

// نمایش محتوای متاباکس
function smart_admin_status_metabox_callback($post) {
    // بررسی آیا این پست توسط دستیار هوشمند ایجاد شده است
    $is_ai_generated = get_post_meta($post->ID, 'smart_admin_generated', true);
    
    if ($is_ai_generated == 'yes') {
        $generation_date = get_post_meta($post->ID, 'smart_admin_generation_date', true);
        echo '<p><strong>این محتوا توسط دستیار هوشمند تولید شده است.</strong></p>';
        echo '<p>تاریخ تولید: ' . date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($generation_date)) . '</p>';
    } else {
        echo '<p>این محتوا توسط دستیار هوشمند تولید نشده است.</p>';
    }
}

// اضافه کردن ستون به لیست نوشته‌ها
function smart_admin_add_posts_columns($columns) {
    $columns['smart_admin_generated'] = 'دستیار هوشمند';
    return $columns;
}
add_filter('manage_posts_columns', 'smart_admin_add_posts_columns');

// نمایش مقدار ستون
function smart_admin_custom_column($column, $post_id) {
    if ($column == 'smart_admin_generated') {
        $is_ai_generated = get_post_meta($post_id, 'smart_admin_generated', true);
        if ($is_ai_generated == 'yes') {
            echo '<span class="dashicons dashicons-superhero" title="تولید شده توسط دستیار هوشمند"></span>';
        }
    }
}
add_action('manage_posts_custom_column', 'smart_admin_custom_column', 10, 2);
