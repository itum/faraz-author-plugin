<?php
// Add settings menu
add_action('admin_menu', 'tsp_add_menu');

function tsp_add_menu()
{
    add_submenu_page('faraz-telegram-plugin', 'Telegram Webhook', 'Telegram Webhook', 'manage_options', 'telegram-webhook-plugin', 'telegram_bot_settings_page');
}

function telegram_bot_settings_page()
{
?>
    <style>
    .telegram-settings-wrap {
        font-family: 'IRANSans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
        max-width: 800px;
        margin: 40px auto;
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .telegram-settings-wrap h2 {
        color: #2c3e50;
        font-size: 2em;
        margin-bottom: 30px;
        border-bottom: 3px solid #3498db;
        padding-bottom: 10px;
        display: inline-block;
    }

    .telegram-settings-form {
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
        font-size: 1em;
        font-weight: 500;
    }

    .form-group input[type="text"],
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
    .form-group textarea:focus {
        border-color: #3498db;
        outline: none;
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
    }

    .form-group textarea {
        min-height: 150px;
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

    .success-message,
    .error-message {
        display: none;
        padding: 15px 20px;
        border-radius: 6px;
        margin-top: 20px;
        font-weight: 500;
    }

    .success-message {
        background: #2ecc71;
        color: white;
    }

    .error-message {
        background: #e74c3c;
        color: white;
    }

    /* Loading animation */
    .loading {
        display: none;
        width: 20px;
        height: 20px;
        border: 2px solid #f3f3f3;
        border-top: 2px solid #3498db;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin-left: 10px;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    </style>

    <div class="telegram-settings-wrap">
        <h2>تنظیمات ربات تلگرام</h2>
        <form method="post" action="" class="telegram-settings-form">
            <?php wp_nonce_field('save_telegram_bot_token', 'telegram_bot_nonce'); ?>
            
            <div class="form-group">
                <label for="telegram_bot_chat_id">شناسه چت گروه تلگرام:</label>
                <input type="text" id="telegram_bot_chat_id" name="telegram_bot_Chat_id" 
                       value="<?php echo esc_attr(get_option('telegram_bot_Chat_id')); ?>" 
                       placeholder="مثال: -1001234567890">
            </div>

            <div class="form-group">
                <label for="telegram_bot_token">توکن ربات تلگرام:</label>
                <input type="text" id="telegram_bot_token" name="telegram_bot_token" 
                       value="<?php echo esc_attr(get_option('telegram_bot_token')); ?>" 
                       placeholder="مثال: 123456789:ABCdefGHIjklMNOpqrsTUVwxyz">
            </div>

            <div class="form-group">
                <label for="telegram_bot_url">آدرس وب‌هوک ربات:</label>
                <input type="text" id="telegram_bot_url" name="telegram_bot_url" 
                       value="<?php echo esc_attr(get_option('telegram_bot_url')); ?>" 
                       placeholder="مثال: https://example.com/webhook">
            </div>

            <div class="form-group">
                <label for="telegram_bot_info">پیام خوش‌آمدگویی ربات:</label>
                <textarea id="telegram_bot_info" name="telegram_bot_info" 
                          placeholder="پیام خوش‌آمدگویی و راهنمای دستورات ربات را وارد کنید"><?php echo esc_attr(get_option('telegram_bot_info')); ?></textarea>
            </div>

            <button type="submit" name="submit_token" class="submit-button">
                <span>ذخیره تنظیمات</span>
                <span class="loading"></span>
            </button>
        </form>

        <div class="success-message">تنظیمات با موفقیت ذخیره شد!</div>
        <div class="error-message">خطا در ذخیره تنظیمات!</div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('.telegram-settings-form');
        const loading = document.querySelector('.loading');
        const successMessage = document.querySelector('.success-message');
        const errorMessage = document.querySelector('.error-message');

        form.addEventListener('submit', function() {
            loading.style.display = 'inline-block';
        });

        <?php if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_token'])) : ?>
            <?php if (check_admin_referer('save_telegram_bot_token', 'telegram_bot_nonce')) : ?>
                successMessage.style.display = 'block';
                setTimeout(function() {
                    successMessage.style.display = 'none';
                }, 3000);
            <?php else : ?>
                errorMessage.style.display = 'block';
                setTimeout(function() {
                    errorMessage.style.display = 'none';
                }, 3000);
            <?php endif; ?>
        <?php endif; ?>
    });
    </script>
<?php
}

add_action('admin_init', 'telegram_bot_save_token');

function telegram_bot_save_token()
{
    if (isset($_POST['submit_token']) && check_admin_referer('save_telegram_bot_token', 'telegram_bot_nonce')) {
        $token = sanitize_text_field($_POST['telegram_bot_token']);
        $url_p = sanitize_textarea_field($_POST['telegram_bot_url']);
        $botinfo = sanitize_textarea_field($_POST['telegram_bot_info']);
        $chat_id = sanitize_textarea_field($_POST['telegram_bot_Chat_id']);
        update_option('telegram_bot_Chat_id', $chat_id);
        update_option('telegram_bot_info', $botinfo);
        update_option('telegram_bot_token', $token);
        update_option('telegram_bot_url', $url_p);
        telegram_bot_set_webhook($token, $url_p);
    }
}

function telegram_bot_set_webhook($token, $url_p)
{
    $admin_login = false;
    update_option('admin_login_p', $admin_login);
    $cloud = 'Location: ' . $url_p . '?bot=' . $token . '&url=' . $url_p .  '?bot=' . $token . '&setWebP=True';
    header($cloud);
    exit;
}

 
add_action('rest_api_init', function() {
    register_rest_route('faraz/v1', '/handle/', array(
        'methods' => 'POST',
        'callback' => 'handle_request',
        'permission_callback' => '__return_true',
    ));
});
function handle_request()
{
    // Log all incoming requests
    $log_file = plugin_dir_path(__FILE__) . 'telegram_logs.txt';
    $update_raw = file_get_contents('php://input');
    file_put_contents($log_file, "Received update: " . $update_raw . "\n", FILE_APPEND);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $update = json_decode($update_raw, true);

        // Log decoded update
        file_put_contents($log_file, "Decoded update: " . print_r($update, true) . "\n", FILE_APPEND);

        if (isset($update['message'])) {
            $message_text = $update['message']['text'];
            $token = get_option('telegram_bot_token');
            $url_p = get_option('telegram_bot_url');
            $admin_login = get_option('admin_login_p');
            $chat_id = get_option('telegram_bot_token');
                if (strpos($message_text, '/start') === 0) { 
                    
                    
                    $botinfo = get_option('telegram_bot_info');
                    if ($botinfo == "") {
                        $botinfo = "
                        به بات فراز خوش اومدی :)
                
                از کامند های زیر استفاده کن : 
                /start 
                /send_drafts
                /publish_all_drafts
                        ";
                    }
                    
                    $response_message = $botinfo;
                    update_option('chat_id', get_option('telegram_bot_Chat_id') );
                    update_option('admin_login_p', false) ;
                    $starter_conuter  = starter_conuter();
                    send_to_telegram($response_message);
                }
                if (strpos($message_text, '/ping') === 0) { 
                    send_to_telegram("hello");
                }
                elseif (strpos($message_text, '/send_drafts') === 0) {
                    send_to_telegram("پست ها در حال ارسال هستند..."); 
                    send_all_draft_posts($chat_id);
                }elseif(strpos($message_text, '/publish_all_drafts') === 0) {
 
                    // $args = array(
                    //     'post_type'      => 'post',
                    //     'post_status'    => 'faraz',
                    //     'posts_per_page' => -1,
                    // );
 
                    // $draft_posts = new WP_Query($args);
 
                    // if ($draft_posts->have_posts()) {
                        // while ($draft_posts->have_posts()) {
                            // $draft_posts->the_post();
                            // $post_id = get_the_ID();
                             
                            // $updated = wp_update_post(array(
                            //     'ID'           => $post_id,
                            //     'post_status'  => 'publish'
                            // ));
                            
                            // if (is_wp_error($updated)) {
                            //      sendErrorToTelegram('Failed to publish post with ID: ' . $post_id);
                            // } else {
                            //      sendErrorToTelegram('Successfully published post with ID: ' . $post_id);
                            // }
                        // }
                        //  wp_reset_postdata();
                    // } else {
                    //     sendErrorToTelegram('No draft posts found with status "faraz".');
                    // }
                }elseif (strpos($message_text, $token) === 0) {
                    $admin_login = true ;
                    update_option('admin_login_p', $admin_login);
                    send_to_telegram("به عنوان ادمین وارد شدید! ");
                }
            // send_to_telegram($message_text);
        }
        elseif (isset($update['callback_query'])) {
            $callback_query = $update['callback_query'];
            $callback_data = $callback_query['data'];
            $chat_id = $callback_query['message']['user']['id'];
            $message_id = $callback_query['message']['message_id'];

            // Log callback query data
            file_put_contents($log_file, "Callback query data: " . $callback_data . "\n", FILE_APPEND);

            if (strpos($callback_data, 'publish_post_') === 0) {
                $post_id = str_replace('publish_post_', '', $callback_data);
                $post_status = get_post_status($post_id);

                // Log post status
                file_put_contents($log_file, "Processing publish_post for post ID: $post_id with status: $post_status\n", FILE_APPEND);

                if($post_status === 'faraz'){
                    // ابتدا پست را منتشر کنیم
                    publish_draft_post($post_id);
                    $post_title = get_the_title($post_id);
                    
                    // ارسال پست به کانال عمومی با امضا
                    $post_thumbnail_url = get_the_post_thumbnail_url($post_id, 'full');
                    if ($post_thumbnail_url) {
                        $post_excerpt = get_the_excerpt($post_id);
                        $post_link = get_permalink($post_id);
                        $cats = get_the_category($post_id);
                        $cat = !empty($cats) ? esc_html($cats[0]->name) : 'بدون دسته‌بندی';
                        $message = "$post_title \n\n$post_excerpt \n\nدسته‌بندی:  $cat \n\nآدرس پست در سایت: $post_link";
                        
                        // ارسال به کانال عمومی
                        $public_channel_id = get_option('farazautur_public_channel_id', '');
                        if (!empty($public_channel_id)) {
                            // ارسال پست به کانال عمومی
                            send_telegram_photo_with_caption($post_thumbnail_url, $message, $post_id, true, $public_channel_id);
                        }
                    }
                    
                    // ارسال پیام تایید به ادمین
                    $confirmation_message = $post_title . " با موفقیت منتشر شد!";
                    file_put_contents($log_file, "Sending confirmation to admin: $confirmation_message\n", FILE_APPEND);
                    send_to_telegram($confirmation_message);
                }
            }
            if (strpos($callback_data, 'delete_post_') === 0) {
                $post_id = str_replace('delete_post_', '', $callback_data);
                $post_status = get_post_status($post_id);
                if($post_status === 'faraz'){
                    delete_post($post_id);
                    $post_title = get_the_title($post_id);
                    send_to_telegram($post_title . " با موفقیت حذف شد!" );
                }
            }
            if (strpos($callback_data, 'edited_post_') === 0) {
                $post_id = str_replace('edited_post_', '', $callback_data);
 
                send_post_to_telegram($post_id , $update['callback_query']['from']['id']);
            }
            if (strpos($callback_data, 'show_post_') === 0) {
                $post_id = str_replace('show_post_', '', $callback_data);
                $user_id = $callback_query['from']['id'];
                send_post_to_telegram($post_id, $user_id);
            }
            // send_to_telegram("پست در حالت فراز نیست و قبلا پاک یا حذف شده است!");
        }
    }
    
}
// Edit Telegram message
function edit_telegram_message( $message_id, $new_text)
{
    $token = get_option('telegram_bot_token');
    $url_p = get_option('telegram_bot_url');
    $chat_id = get_option('telegram_bot_Chat_id');
    $myObj = new stdClass();
    $myObj->url = $url_p;
    $myObj->chatid = $chat_id;
    $myObj->bot = $token;
    
    $myObj->isedit = "isedit";
    $myObj->message_id = $message_id;
    $myObj->text = $new_text;
    
    $myJSON = json_encode($myObj);

    echo $myJSON;
    exit;
}

function send_to_telegram($message)
{
    if(is_null($token)) $token = get_option('telegram_bot_token');
    $workerUrl = 'https://bold-scene-ab65.alireza63ad.workers.dev';   
  
    $data = array(
        'chatid' =>  get_option('telegram_bot_Chat_id'),
        'bot' => $token,
        'message' => $message,
        'isphoto' => 'false'  
    );

     
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $workerUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

    $response = curl_exec($ch);

    // Log the response from the worker
    $log_file = plugin_dir_path(__FILE__) . 'telegram_logs.txt';
    file_put_contents($log_file, "Telegram send response: " . $response . "\n", FILE_APPEND);

    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }
    curl_close($ch);
}

//start conuter 
function starter_conuter()
{
    $args = array(
        'post_type' => 'post',
        'post_status' => 'faraz',
        'posts_per_page' => -1,
    );
    $draft_posts = new WP_Query($args);
    if ($draft_posts->have_posts()) {
        $count_post_p = 0;
        while ($draft_posts->have_posts()) {
            $draft_posts->the_post();
            $post_id = get_the_ID();
            $post_status = get_post_status($post_id);
            if ($post_status === 'faraz') {
                $count_post_p += 1;
            }
        }
    }
    return ($count_post_p);
}
function send_post_to_telegram($post_id, $chat_id)
{  
    $post_thumbnail_url = get_the_post_thumbnail_url($post_id , 'full');
    if (!$post_thumbnail_url) { 
        $thumbnail_id = get_post_thumbnail_id($post_id);
        $post_thumbnail_data = wp_get_attachment_image_src($thumbnail_id, 'full');
        
        if ($post_thumbnail_data && isset($post_thumbnail_data[0])) {
            $post_thumbnail_url = $post_thumbnail_data[0];
        } else {
            send_to_telegram('پست مورد نظر تصویر شاخص ندارد.');
            return;
        }
    }
 
    $post_title = get_the_title($post_id);
    $post_excerpt = get_the_excerpt($post_id);
    $post_link = get_permalink($post_id);

    $cats = get_the_category($post_id);
    $cat = !empty($cats) ? esc_html($cats[0]->name) : 'بدون دسته‌بندی';
 
    $message = "$post_title \n\n$post_excerpt \n\nدسته‌بندی:  $cat \n\nآدرس پست در سایت شما: $post_link";

    // این تابع برای نمایش و تغییر پست به ادمین ها ارسال می‌شود
    // در اینجا همیشه باید پارامتر false ارسال شود تا امضا اضافه نشود
    send_telegram_photo_with_caption($post_thumbnail_url, $message, $post_id, 'edit', $chat_id);
}
function send_all_draft_posts($chat_id)
{
    $args = array(
        'post_type' => 'post',
        'post_status' => 'faraz',
        'posts_per_page' => 20,
    );

    $draft_posts = new WP_Query($args);

    if ($draft_posts->have_posts()) {
        $count_post_p = 0;
        while ($draft_posts->have_posts()) {
            $draft_posts->the_post();
            $post_id = get_the_ID();
            $post_status = get_post_status($post_id);


            $draft_posts->the_post();
            $post_id = get_the_ID();
 

            // sendErrorToTelegram(json_encode([ $post_status ,$post_id ]));
            if ($post_status === 'faraz' AND $post_thumbnail_url = get_the_post_thumbnail_url($post_id ) ) {
                $post_title = get_the_title();
                $post_excerpt = get_the_excerpt();
                $post_link = get_permalink();
 
                $cats = get_the_category();
                if (!empty($cats)) {
                    $cat = esc_html($cats[0]->name);
                }

                $message = "$post_title \n\n$post_excerpt \n\nدسته بندی :  $cat \n\n ادرس پست در سایت شما: $post_link ";
                 
                // پارامتر false به معنی عدم نمایش امضا در کانال ادمین‌ها است
                send_telegram_photo_with_caption($post_thumbnail_url, $message, $post_id, false);

                $count_post_p += 1;
                if($count_post_p > 10) break;
            }
        }
        wp_reset_postdata();
        if ($count_post_p > 0) {
 
        } else {
            send_to_telegram( 'هیچ پیش نویسی یافت نشد.');
        }
    } else {
        send_to_telegram( 'هیچ پیش نویسی یافت نشد.');
    }
}
// Publish draft post
function publish_draft_post($post_id)
{
    $post = array(
        'ID' => $post_id,
        'post_status' => 'publish'
    );
    wp_update_post($post);
}

function delete_post($post_id) {
   wp_trash_post($post_id);
}

// Publish all draft posts
function publish_all_draft_posts()
{
    $args = array(
        'post_type' => 'post',
        'post_status' => 'faraz',
        'posts_per_page' => 10,
    );

    $draft_posts = new WP_Query($args);

    if ($draft_posts->have_posts()) {
        while ($draft_posts->have_posts()) {
            $draft_posts->the_post();
            $post_id = get_the_ID();
            $post_status = get_post_status($post_id);

            if ($post_status === 'faraz') {
                publish_draft_post($post_id);
            }
        }
        wp_reset_postdata();
    }
}

?>