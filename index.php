<?php
/*
Plugin Name: faraz Telegram Plugin
Description: A WordPress plugin to add rss and save them as drafts and send them to the Telegram bot.
Version: 5
Author: تیم توسعه تجارت الکترونیک فراز 
*/ 

// Include signature settings
require_once plugin_dir_path(__FILE__) . 'signature-settings.php';
require_once plugin_dir_path(__FILE__) . 'newsroom.php';
require_once plugin_dir_path(__FILE__) . 'private-channel-settings.php';
require_once plugin_dir_path(__FILE__) . 'smart-admin.php';
require_once plugin_dir_path(__FILE__) . 'smart-admin-templates.php';
require_once plugin_dir_path(__FILE__) . 'smart-admin-save-post.php';
require_once plugin_dir_path(__FILE__) . 'smart-admin-human-tone.php';
  
add_action('admin_menu', 'stp_add_menu'); 

function stp_add_menu()
{
    // Add main menu
    add_menu_page(
        'faraz Telegram Plugin', // Page title
        'faraz Plugin', // Menu title
        'manage_options', // Capability
        'faraz-telegram-plugin', // Menu slug
        'stp_render_page' // Function
    );
    
    // Add RSS Management submenu
    add_submenu_page(
        'faraz-telegram-plugin',
        'مدیریت RSS ها',
        'مدیریت RSS ها',
        'manage_options',
        'faraz-telegram-plugin',
        'stp_render_page'
    );
    
    // Add Signature Settings submenu
    add_submenu_page(
        'faraz-telegram-plugin',
        'تنظیمات امضا',
        'تنظیمات امضا',
        'manage_options',
        'faraz-telegram-plugin&tab=signature',
        'farazautur_signature_page'
    );

    // Add Newsroom submenu
    add_submenu_page(
        'faraz-telegram-plugin',
        'اتاق خبر',
        'اتاق خبر',
        'manage_options',
        'faraz-telegram-plugin-newsroom',
        'farazautur_newsroom_page'
    );

    // Add Private Channel Settings submenu
    add_submenu_page(
        'faraz-telegram-plugin',
        'تنظیمات کانال خصوصی',
        'تنظیمات کانال خصوصی',
        'manage_options',
        'faraz-telegram-plugin-private-channel',
        'farazautur_private_channel_settings_page'
    );
}
if ( ! function_exists( 'post_exists' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/post.php' );
}

function stp_update_entry_with_channel_data($entry)
{
    include_once(ABSPATH . WPINC . '/feed.php');

    $rss = fetch_feed($entry['url']);

    if (!is_wp_error($rss)) {
        // $entry['channel_title'] = $rss->get_title();
        $entry['channel_description'] = $rss->get_description();
    }

    return $entry;
}

function custom_cron_schedule($schedules) {
    $schedules['every_five_minutes'] = array(
        'interval' => 300,  
        'display'  => __('Every Five Minutes'),
    );
    return $schedules;
}
add_filter('cron_schedules', 'custom_cron_schedule');

if (!wp_next_scheduled('stp_check_for_new_rss_items_hook')) {
    wp_schedule_event(time(), 'every_five_minutes', 'stp_check_for_new_rss_items_hook');
}

add_action('stp_check_for_new_rss_items_hook', 'stp_check_for_new_rss_items');

function stp_deactivate_plugin() {
    $timestamp = wp_next_scheduled('stp_check_for_new_rss_items_hook');
    wp_unschedule_event($timestamp, 'stp_check_for_new_rss_items_hook');
}
register_deactivation_hook(__FILE__, 'stp_deactivate_plugin');







function trigger_cron_job() {
    stp_check_for_new_rss_items();  
    wp_die();  
}
add_action('wp_ajax_trigger_cron', 'trigger_cron_job');
add_action('wp_ajax_nopriv_trigger_cron', 'trigger_cron_job');

add_action('wp_footer', 'add_ajax_script');
function add_ajax_script() {
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        var data = {
            'action': 'trigger_cron' 
        }; 
        $.post('<?php echo admin_url('admin-ajax.php'); ?>', data, function(response) {
            console.log('Cron job triggered by AJAX.');
        });
    });
    </script>
    <?php
}

add_action('rest_api_init', function () {
    register_rest_route('bot-rss/v1', '/post/(?P<id>\d+)', array(
        'methods' => array('GET', 'POST'),
        'callback' => 'bot_rss_handle_post',
        'permission_callback' => 'bot_rss_check_api_password',
    ));
});

add_action('rest_api_init', function () {
    register_rest_route('bot-cron/v1', '/check-rss-items', array(
        'methods' => 'GET',
        'callback' => 'stp_check_for_new_rss_items',
        'permission_callback' => '__return_true',
    ));
});
 

function bot_rss_check_api_password($request) {
    $provided_password = $request->get_param('password');
    $correct_password = 'opkwfaopfkoan2';  

    if ($provided_password === $correct_password) {
        return true;
    }
    return false;
}

function bot_rss_handle_post($data) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        return bot_rss_update_post($data);
    } else {
        return bot_rss_display_post_full_text($data);
    }
}

function bot_rss_update_post($data) {
    $post_id = $data['id'];
    $new_title = isset($_POST['post_title']) ? $_POST['post_title'] : '';
    $new_content = isset($_POST['post_content']) ? $_POST['post_content'] : '';
    $new_category_id = isset($_POST['post_category']) ? $_POST['post_category'] : '';
    $new_tags = isset($_POST['post_tags']) ? $_POST['post_tags'] : '';

    if (empty($post_id) || empty($new_content) || empty($new_title) || empty($new_category_id)) {
        wp_die('Invalid post ID, title, content, or category', 'Invalid Input', array('response' => 400));
    }

    $post_update = array(
        'ID' => $post_id,
        'post_title' => $new_title,
        'post_content' => $new_content,
        'post_category' => array($new_category_id),  
    );

    wp_update_post($post_update);
 
    wp_set_post_tags($post_id, $new_tags, false);

    wp_die('Post updated successfully', 'Success', array('response' => 200));
}

function bot_rss_display_post_full_text($data) {
    $post_id = $data['id'];
    $post = get_post($post_id);

    if (empty($post)) {
        wp_die('Invalid post ID', 'Post Not Found', array('response' => 404));
    }

    $post_title = get_the_title($post);
    $post_content = apply_filters('the_content', $post->post_content);
    $post_excerpt = get_the_excerpt($post);
    $post_thumbnail_url = get_the_post_thumbnail_url($post, 'full');
    $post_categories = get_the_category($post_id);
    $post_tags = wp_get_post_tags($post_id, array('fields' => 'names'));
    $all_categories = get_categories(array('hide_empty' => 0)); 
    $all_tags = []; 
    $current_category_id = !empty($post_categories) ? $post_categories[0]->term_id : '';

    $output = '<!DOCTYPE html>';
    $output .= '<html lang="fa" dir="rtl"><head>';
    $output .= '<meta charset="utf-8">';
    $output .= '<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">';
    $output .= '<meta name="description" content="">';
    $output .= '<meta name="author" content="">';
    $output .= '<title>' . $post_title . '</title>';
    $output .= '<link rel="icon" type="image/x-icon" href="assets/favicon.ico">';
    $output .= '<link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">';
    $output .= '<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />';
    $output .= '<script src="https://cdn.ckeditor.com/ckeditor5/34.2.0/classic/ckeditor.js"></script>';
    $output .= '<style>
                    body {
                        background-color: #f8f9fa;
                        padding-top: 50px;
                        direction: rtl;
                        text-align: right;
                    }
                    .container {
                        max-width: 800px;
                        margin: auto;
                    }
                    .card {
                        margin: 20px 0;
                    }
                    .card-img-top {
                        max-height: 400px;
                        object-fit: cover;
                    }
                </style>';
    $output .= '</head><body>';
    $output .= '<nav class="navbar navbar-expand-lg navbar-dark bg-dark">';
    $output .= '<div class="container">';
    $output .= '<a class="navbar-brand" href="#!">Rss Bot</a>';
    $output .= '<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>';
    $output .= '<div class="collapse navbar-collapse" id="navbarSupportedContent">';
    $output .= '<ul class="navbar-nav ml-auto">';
    $output .= '</ul>';
    $output .= '</div>';
    $output .= '</div>';
    $output .= '</nav>';
    $output .= '<div class="container mt-5">';
    $output .= '<div class="card">';
    if ($post_thumbnail_url) {
        $output .= '<img src="' . $post_thumbnail_url . '" class="card-img-top" alt="' . $post_title . '">';
    }
    $output .= '<div class="card-body">';
    $output .= '<h1 class="card-title">' . $post_title . '</h1>';
    $output .= '<div class="text-muted mb-2">تاریخ انتشار ' . get_the_date('F j, Y', $post) . ' توسط ' . get_the_author_meta('display_name', $post->post_author) . '</div>';
    $output .= '<form method="post">';
    $output .= '<div class="form-group">';
    $output .= '<label for="post_title">عنوان پست:</label>';
    $output .= '<input type="text" id="post_title" name="post_title" class="form-control" value="' . htmlspecialchars($post_title) . '">';
    $output .= '</div>';
    $output .= '<div class="form-group">';
    $output .= '<label for="post_content">متن پست:</label>';
    $output .= '<textarea id="post_content" name="post_content">' . htmlspecialchars($post->post_content) . '</textarea>';
    $output .= '</div>';
    $output .= '<div class="form-group">';
    $output .= '<label for="post_category">دسته‌بندی:</label>';
    $output .= '<select id="post_category" name="post_category" class="form-control">';
     
    foreach ($all_categories as $category) {
        $selected = $category->term_id == $current_category_id ? 'selected' : '';
        $output .= '<option value="' . $category->term_id . '" ' . $selected . '>' . $category->name . '</option>';
    }

    $output .= '</select>';
    $output .= '</div>';

    $output .= '<div class="form-group">';
    $output .= '<label for="post_tags">تگ‌ها:</label>';
    $output .= '<select id="post_tags" name="post_tags[]" class="form-control" multiple="multiple">';
     
    foreach ($all_tags as $tag) {
        $selected = in_array($tag->name, $post_tags) ? 'selected' : '';
        $output .= '<option value="' . $tag->name . '" ' . $selected . '>' . $tag->name . '</option>';
    }

    $output .= '</select>';
    $output .= '</div>';

    $output .= '<button type="submit" class="btn btn-primary mt-3">به‌روزرسانی پست</button>';
    $output .= '</form>';
    $output .= '<p class="mt-4"><strong>خلاصه:</strong> ' . $post_excerpt . '</p>';
    $output .= '</div>';  
    $output .= '</div>'; 
    $output .= '</div>';  
    $output .= '<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>';
    $output .= '<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>';
    $output .= '<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.full.min.js"></script>';
    $output .= '<script>
                    ClassicEditor
                        .create(document.querySelector("#post_content"), {
                            language: "fa"
                        })
                        .catch(error => {
                            console.error(error);
                        });

                    $("#post_tags").select2({
                        tags: true,
                        tokenSeparators: [","],
                        placeholder: "تگ‌ها را انتخاب یا اضافه کنید",
                        language: {
                            noResults: function() {
                                return "تگی یافت نشد. برای ایجاد، تگ را تایپ کنید و Enter بزنید.";
                            }
                        },
                        createTag: function(params) {
                            var term = $.trim(params.term);
                            if (term === "") {
                                return null;
                            }
                            return {
                                id: term,
                                text: term,
                                newTag: true
                            };
                        }
                    });
                </script>';
    $output .= '</body></html>';

    wp_die($output);
}





include_once plugin_dir_path(__FILE__) . 'send.php';
include_once plugin_dir_path(__FILE__) . 'telegram-settings.php';
include_once plugin_dir_path(__FILE__) . 'add-status.php';
include_once plugin_dir_path(__FILE__) . 'index-ui.php';
include_once plugin_dir_path(__FILE__) . 'add-content.php';
