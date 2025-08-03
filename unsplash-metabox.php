<?php
/**
 * Unsplash Metabox for selecting a featured image.
 */

// Add metabox
add_action('add_meta_boxes', 'faraz_unsplash_add_metabox');
function faraz_unsplash_add_metabox() {
    add_meta_box(
        'faraz_unsplash_metabox',
        'تصویر شاخص از Unsplash',
        'faraz_unsplash_metabox_callback',
        'post',
        'side',
        'default'
    );
}

// Metabox content
function faraz_unsplash_metabox_callback($post) {
    wp_nonce_field('faraz_unsplash_search', 'faraz_unsplash_nonce');
    ?>
    <div id="unsplash-metabox-container">
        <p>
            <label for="unsplash-search-keyword">کلمه کلیدی را برای جستجو وارد کنید:</label>
            <input type="text" id="unsplash-search-keyword" class="widefat" value="<?php echo esc_attr($post->post_title); ?>">
        </p>
        <p>
            <button type="button" id="unsplash-search-button" class="button button-primary">جستجو</button>
            <span class="spinner"></span>
        </p>
        <div id="unsplash-results"></div>
    </div>
    <?php
}

// Enqueue scripts and styles
add_action('admin_enqueue_scripts', 'faraz_unsplash_enqueue_assets');
function faraz_unsplash_enqueue_assets($hook) {
    if ('post.php' != $hook && 'post-new.php' != $hook) {
        return;
    }

    wp_enqueue_style('faraz-unsplash-metabox-style', plugin_dir_url(__FILE__) . 'css/unsplash-metabox.css');
    wp_enqueue_script('faraz-unsplash-metabox-script', plugin_dir_url(__FILE__) . 'js/unsplash-metabox.js', ['jquery'], null, true);
    
    wp_localize_script('faraz-unsplash-metabox-script', 'unsplash_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('faraz_unsplash_search'),
        'post_id'  => get_the_ID(),
    ]);
}

// AJAX handler for searching images
add_action('wp_ajax_faraz_unsplash_search_images', 'faraz_unsplash_search_images_callback');
function faraz_unsplash_search_images_callback() {
    check_ajax_referer('faraz_unsplash_search', 'nonce');

    $api_key = get_option('faraz_unsplash_api_key');
    if (empty($api_key)) {
        wp_send_json_error(['message' => 'کلید API Unsplash تنظیم نشده است.']);
    }

    $keyword = isset($_POST['keyword']) ? sanitize_text_field($_POST['keyword']) : '';
    if (empty($keyword)) {
        wp_send_json_error(['message' => 'کلمه کلیدی نمی‌تواند خالی باشد.']);
    }

    $resolution = get_option('faraz_unsplash_image_resolution', 'regular');
    $count = get_option('faraz_unsplash_suggestion_count', 5);

    $url = add_query_arg([
        'query' => urlencode($keyword),
        'client_id' => $api_key,
        'per_page' => $count,
        'orientation' => 'landscape',
    ], 'https://api.unsplash.com/search/photos');

    $response = wp_remote_get($url);

    if (is_wp_error($response)) {
        wp_send_json_error(['message' => 'خطا در اتصال به Unsplash: ' . $response->get_error_message()]);
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (empty($data['results'])) {
        wp_send_json_error(['message' => 'هیچ تصویری برای این کلمه کلیدی یافت نشد.']);
    }

    wp_send_json_success($data['results']);
}

// AJAX handler for setting featured image
add_action('wp_ajax_faraz_unsplash_set_image', 'faraz_unsplash_set_image_callback');
function faraz_unsplash_set_image_callback() {
    check_ajax_referer('faraz_unsplash_search', 'nonce');

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $image_url = isset($_POST['image_url']) ? esc_url_raw($_POST['image_url']) : '';
    $alt_text = isset($_POST['alt_text']) ? sanitize_text_field($_POST['alt_text']) : '';

    if (empty($post_id) || empty($image_url)) {
        wp_send_json_error(['message' => 'اطلاعات ناقص برای تنظیم تصویر.']);
    }

    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');

    $attachment_id = media_sideload_image($image_url, $post_id, $alt_text, 'id');

    if (is_wp_error($attachment_id)) {
        wp_send_json_error(['message' => 'خطا در دانلود تصویر: ' . $attachment_id->get_error_message()]);
    }

    set_post_thumbnail($post_id, $attachment_id);
    
    // Get the thumbnail URL to send back to the client
    $thumbnail_url = get_the_post_thumbnail_url($post_id, 'thumbnail');

    wp_send_json_success([
        'message' => 'تصویر شاخص با موفقیت تنظیم شد.',
        'thumbnail_url' => $thumbnail_url
    ]);
}
