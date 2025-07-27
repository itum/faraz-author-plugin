<?php
function send_to_private_channel($post_id) {
    // Create log file if not exists
    $log_file = plugin_dir_path(__FILE__) . 'telegram_logs.txt';
    
    // Log function for debugging
    function write_log($message, $log_file) {
        $timestamp = current_time('mysql');
        $log_message = sprintf("[%s] %s\n", $timestamp, $message);
        error_log($log_message, 3, $log_file);
    }

    write_log("Starting to send post ID: " . $post_id, $log_file);

    $channel_id = get_option('farazautur_private_channel_id', '');
    $bot_token = get_option('telegram_bot_token', '');
    $hook_url = get_option('telegram_hook_url', '');

    if (empty($channel_id) || empty($bot_token) || empty($hook_url)) {
        write_log("Error: Missing required settings (channel_id, bot_token, or hook_url)", $log_file);
        return false;
    }

    write_log("Channel ID: " . $channel_id, $log_file);
    write_log("Bot Token: " . substr($bot_token, 0, 10) . '...', $log_file);
    write_log("Hook URL: " . $hook_url, $log_file);

    $thumbnail = get_the_post_thumbnail_url($post_id, 'medium');
    $title = get_the_title($post_id);
    $excerpt = get_the_excerpt($post_id);
    $permalink = get_permalink($post_id);

    write_log("Post details - Title: " . $title, $log_file);
    write_log("Post details - Thumbnail: " . ($thumbnail ? $thumbnail : 'No thumbnail'), $log_file);

    // کوتاه کردن لینک
    $short_link = wp_get_shortlink($post_id);
    if (!$short_link) {
        $short_link = $permalink;
    }

    $message = "🔸 " . $title . "\n\n";
    $message .= $excerpt . "\n\n";
    $message .= "📎 ادامه خبر را بخوانید: \n" . $short_link;
    
    // امضا فقط باید در کانال عمومی نمایش داده شود، نه در کانال خصوصی
    // if (get_option('farazautur_signature_enabled')) {
    //     $signature = get_option('farazautur_signature_text');
    //     if (!empty($signature)) {
    //         $message .= "\n\n" . wp_strip_all_tags($signature);
    //     }
    // }

    write_log("Prepared message: " . $message, $log_file);

    // Attempt to send photo first if available
    if ($thumbnail) {
        write_log("Attempting to send photo as JSON", $log_file);
        
        $photo_payload = array(
            'bot' => $bot_token,
            'chatid' => $channel_id,
            'photo' => $thumbnail,
            'caption' => $message,
            'is_photo' => 'true'
        );

        $photo_args = array(
            'method' => 'POST',
            'timeout' => 30,
            'redirection' => 5,
            'blocking' => true,
            'headers' => array(
                'Content-Type' => 'application/json; charset=utf-8'
            ),
            'body' => wp_json_encode($photo_payload),
            'sslverify' => false
        );

        write_log("Sending photo with JSON payload: " . wp_json_encode($photo_payload), $log_file);
        $photo_response = wp_remote_post($hook_url, $photo_args);

        if (is_wp_error($photo_response)) {
            write_log("Error sending photo (wp_error): " . $photo_response->get_error_message(), $log_file);
            // Fall through to send as text if photo send fails
        } else {
            $photo_response_code = wp_remote_retrieve_response_code($photo_response);
            $photo_response_body = wp_remote_retrieve_body($photo_response);
            write_log("Photo Hook Response Code: " . $photo_response_code, $log_file);
            write_log("Photo Hook Response Body: " . $photo_response_body, $log_file);
            
            $response_data = json_decode($photo_response_body, true);
            if ($photo_response_code == 200 && isset($response_data['status']) && $response_data['status'] === 'success') {
                write_log("Photo sent successfully via JSON", $log_file);
                return true; // Successfully sent photo
            } else {
                write_log("Failed to send photo via JSON or hook returned error. Response: " . $photo_response_body, $log_file);
                // If photo sending failed, proceed to send as text message
            }
        }
    }

    // If no thumbnail, or if sending photo failed, send as a plain text message
    write_log("Attempting to send message as JSON", $log_file);
    $message_payload = array(
        'bot' => $bot_token,
        'chatid' => $channel_id,
        'message' => $message
    );

    $message_args = array(
        'method' => 'POST',
        'timeout' => 30,
        'redirection' => 5,
        'blocking' => true,
        'headers' => array(
            'Content-Type' => 'application/json; charset=utf-8'
        ),
        'body' => wp_json_encode($message_payload),
        'sslverify' => false
    );

    write_log("Sending message with JSON payload: " . wp_json_encode($message_payload), $log_file);
    $response = wp_remote_post($hook_url, $message_args);

    if (is_wp_error($response)) {
        write_log("Error sending message (wp_error): " . $response->get_error_message(), $log_file);
        return false;
    }

    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);
    write_log("Message Hook Response Code: " . $response_code, $log_file);
    write_log("Message Hook Response Body: " . $response_body, $log_file);

    $response_data = json_decode($response_body, true);
    if ($response_code == 200 && isset($response_data['status']) && $response_data['status'] === 'success') {
        write_log("Message sent successfully via JSON", $log_file);
        return true;
    }

    write_log("Failed to send message via JSON. Response: " . $response_body, $log_file);
    return false;
}

function write_whatsapp_log($message) {
    $log_file = plugin_dir_path(__FILE__) . 'whatsapp_logs.txt';
    $timestamp = current_time('mysql');
    $log_message = sprintf("[%s] %s\n", $timestamp, $message);
    error_log($log_message, 3, $log_file);
}

function send_to_whatsapp_group($post_id) {
    write_whatsapp_log("Starting to send post ID: " . $post_id . " to WhatsApp");

    $api_key = get_option('farazautur_whatsapp_api_key');
    $group_id = get_option('farazautur_whatsapp_group_id');
    
    if (empty($api_key) || empty($group_id)) {
        write_whatsapp_log("Error: Missing WhatsApp API Key or Group ID");
        return false;
    }

    $title = get_the_title($post_id);
    $excerpt = get_the_excerpt($post_id);
    $thumbnail = get_the_post_thumbnail_url($post_id, 'full');
    $permalink = get_permalink($post_id);

    // کوتاه کردن لینک
    $short_link = wp_get_shortlink($post_id);
    if (!$short_link) {
        $short_link = $permalink;
    }

    // Format message like Telegram
    $message_text = "🔸 " . $title . "\n\n";
    $message_text .= $excerpt . "\n\n";
    $message_text .= "📎 ادامه خبر را بخوانید: \n" . $short_link;

    // Add signature if enabled
    if (get_option('farazautur_signature_enabled')) {
        $signature = get_option('farazautur_signature_text');
        if (!empty($signature)) {
            // Strip HTML tags for WhatsApp
            $clean_signature = wp_strip_all_tags( $signature );
            $message_text .= "\n\n" . $clean_signature;
        }
    }

    $api_url = 'https://api.360messenger.com/v2/sendGroup';

    $data = [
        'groupId' => $group_id,
        'text' => $message_text,
    ];

    if ($thumbnail) {
        $data['url'] = $thumbnail;
    }

    $headers = [
        'Authorization' => 'Bearer ' . $api_key,
    ];

    $args = [
        'body' => $data,
        'headers' => $headers,
        'method' => 'POST',
        'timeout'     => 30,
    ];
    
    write_whatsapp_log("Request ARGS: " . print_r($args, true));

    $response = wp_remote_post($api_url, $args);

    if (is_wp_error($response)) {
        write_whatsapp_log("Failed to send message to WhatsApp. WP_Error: " . $response->get_error_message());
        return false;
    }

    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);

    write_whatsapp_log("WhatsApp API Response Code: " . $response_code);
    write_whatsapp_log("WhatsApp API Response Body: " . $response_body);

    $response_data = json_decode($response_body, true);
    if ($response_code === 201 && isset($response_data['success']) && $response_data['success'] === true) {
        write_whatsapp_log("Message sent successfully to WhatsApp group.");
        return true;
    }

    write_whatsapp_log("Failed to send message to WhatsApp group. Response: " . $response_body);
    return false;
}

function farazautur_newsroom_page() {
    // Handle post actions
    if (isset($_POST['post_id'])) {
        $post_id = intval($_POST['post_id']);

        if (isset($_POST['approve_post'])) {
            // Send to private channel if auto-post is enabled
            if (get_option('farazautur_auto_post_enabled', '0') === '1') {
                $sent = send_to_private_channel($post_id);
                if ($sent) {
                    echo '<div class="notice notice-success is-dismissible"><p>خبر با موفقیت در کانال خصوصی منتشر شد. برای مشاهده جزئیات به فایل telegram_logs.txt مراجعه کنید.</p></div>';
                } else {
                    echo '<div class="notice notice-error is-dismissible"><p>خطا در ارسال خبر به کانال خصوصی. لطفاً فایل telegram_logs.txt را بررسی کنید.</p></div>';
                }
            }
        } elseif (isset($_POST['send_to_whatsapp'])) {
            // Handle sending to WhatsApp
            $sent = send_to_whatsapp_group($post_id);
            if ($sent) {
                echo '<div class="notice notice-success is-dismissible"><p>خبر با موفقیت در گروه واتس‌اپ منتشر شد. برای مشاهده جزئیات به فایل whatsapp_logs.txt مراجعه کنید.</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>خطا در ارسال خبر به گروه واتس‌اپ. لطفاً فایل whatsapp_logs.txt را بررسی کنید.</p></div>';
            }
        }
    }
    ?>
    <div class="wrap">
        <h1>اتاق خبر</h1>
        <style>
            .newsroom-container {
                max-width: 1200px;
                margin: 20px auto;
                padding: 20px;
                background: #f5f5f5;
                border-radius: 10px;
            }
            .news-item {
                background: #fff;
                padding: 20px;
                margin-bottom: 20px;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                transition: transform 0.2s ease, box-shadow 0.2s ease;
            }
            .news-item:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            }
            .news-header {
                display: flex;
                margin-bottom: 15px;
            }
            .news-thumbnail {
                flex: 0 0 200px;
                margin-left: 20px;
            }
            .news-thumbnail img {
                width: 100%;
                height: auto;
                border-radius: 4px;
                object-fit: cover;
            }
            .news-content {
                flex: 1;
            }
            .news-content h2 {
                margin: 0 0 10px 0;
                font-size: 1.4em;
                color: #333;
            }
            .news-content p {
                margin: 0;
                color: #666;
                line-height: 1.6;
            }
            .news-footer {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-top: 15px;
                padding-top: 15px;
                border-top: 1px solid #eee;
            }
            .news-footer a {
                text-decoration: none;
                color: #0073aa;
                font-weight: 500;
            }
            .news-footer a:hover {
                color: #00a0d2;
            }
            .copy-news {
                background: #0073aa;
                color: white;
                border: none;
                padding: 8px 16px;
                border-radius: 4px;
                cursor: pointer;
                transition: background 0.2s ease;
            }
            .copy-news:hover {
                background: #00a0d2;
            }
            .no-news {
                text-align: center;
                padding: 40px;
                color: #666;
                font-size: 1.1em;
            }
            .approve-news {
                background: #46b450;
                color: white;
                border: none;
                padding: 8px 16px;
                border-radius: 4px;
                cursor: pointer;
                transition: background 0.2s ease;
                margin-right: 10px;
            }
            .approve-news:hover {
                background: #389e44;
            }
        </style>
        <div class="newsroom-container">
            <?php
            $args = array(
                'post_type' => 'post',
                'post_status' => 'publish',
                'posts_per_page' => 5,
                'orderby' => 'date',
                'order' => 'DESC'
            );
            
            $latest_posts = new WP_Query($args);
            
            if ($latest_posts->have_posts()) :
                while ($latest_posts->have_posts()) : $latest_posts->the_post();
                    $thumbnail = get_the_post_thumbnail_url(get_the_ID(), 'medium');
                    $title = get_the_title();
                    $excerpt = get_the_excerpt();
                    $permalink = get_permalink();
                    ?>
                    <div class="news-item">
                        <div class="news-header">
                            <?php if ($thumbnail) : ?>
                                <div class="news-thumbnail">
                                    <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_attr($title); ?>">
                                </div>
                            <?php endif; ?>
                            <div class="news-content">
                                <h2><?php echo esc_html($title); ?></h2>
                                <p><?php echo esc_html($excerpt); ?></p>
                            </div>
                        </div>
                        <div class="news-actions">
                            <form method="post" action="" style="display: inline;">
                                <input type="hidden" name="post_id" value="<?php echo get_the_ID(); ?>">
                                <button type="submit" name="approve_post" class="button button-primary">تایید و ارسال به کانال</button>
                                <button type="submit" name="send_to_whatsapp" class="button button-secondary">ارسال به واتس اپ</button>
                            </form>
                            <a href="<?php echo get_edit_post_link(get_the_ID()); ?>" class="button">ویرایش</a>
                            <a href="<?php echo get_delete_post_link(get_the_ID()); ?>" class="button button-danger" onclick="return confirm('آیا از حذف این خبر مطمئن هستید؟')">حذف</a>
                        </div>
                    </div>
                    <?php
                endwhile;
                wp_reset_postdata();
            else :
                echo '<div class="no-news">خبری یافت نشد.</div>';
            endif;
            ?>
        </div>
    </div>

    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('.copy-news').click(function() {
            var title = $(this).data('title');
            var excerpt = $(this).data('excerpt');
            var link = $(this).data('link');
            
            var newsText = '🔸 ' + title + '\n\n';
            newsText += excerpt + '\n\n';
            newsText += '📎 ادامه خبر را بخوانید: \n' + link;
            
            // Add signature if enabled
            <?php if (get_option('farazautur_signature_enabled')): ?>
            var signature = <?php echo json_encode(wp_strip_all_tags(get_option('farazautur_signature_text', ''))); ?>;
            if (signature) {
                newsText += '\n\n' + signature;
            }
            <?php endif; ?>
            
            // Create temporary textarea
            var $temp = $("<textarea>");
            $("body").append($temp);
            $temp.val(newsText).select();
            document.execCommand("copy");
            $temp.remove();
            
            // Show success message with WordPress notice style
            var $notice = $('<div class="notice notice-success is-dismissible"><p>خبر با موفقیت کپی شد!</p></div>');
            $('.wrap h1').after($notice);
            
            // Auto remove the notice after 3 seconds
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 3000);
        });
    });
    </script>
    <?php
} 