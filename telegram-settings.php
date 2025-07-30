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
        
        <?php
        // نمایش پیام‌های وضعیت وب‌هوک
        if (isset($_GET['webhook_status'])) {
            if ($_GET['webhook_status'] === 'success') {
                echo '<div class="success-message" style="display: block;">وب‌هوک با موفقیت تنظیم شد!</div>';
            } elseif ($_GET['webhook_status'] === 'error') {
                $error_msg = isset($_GET['error_msg']) ? urldecode($_GET['error_msg']) : 'خطای نامشخص';
                echo '<div class="error-message" style="display: block;">خطا در تنظیم وب‌هوک: ' . esc_html($error_msg) . '</div>';
            }
        }
        ?>
        
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
                       placeholder="مثال: https://yoursite.com/wp-json/faraz/v1/handle/">
                <small style="color: #666; font-size: 12px; margin-top: 5px; display: block;">
                    آدرس endpoint سایت شما که تلگرام پیام‌ها را به آن ارسال می‌کند
                </small>
            </div>

            <div class="form-group">
                <label>نوع هاست:</label>
                <div style="margin-top: 10px;">
                    <label style="display: inline-flex; align-items: center; margin-left: 20px;">
                        <input type="radio" name="telegram_host_type" value="foreign" 
                               <?php checked(get_option('telegram_host_type', 'foreign'), 'foreign'); ?> 
                               style="margin-left: 8px;">
                        هاست خارجی (اتصال مستقیم به تلگرام)
                    </label>
                    <label style="display: inline-flex; align-items: center;">
                        <input type="radio" name="telegram_host_type" value="iranian" 
                               <?php checked(get_option('telegram_host_type', 'foreign'), 'iranian'); ?> 
                               style="margin-left: 8px;">
                        هاست ایرانی (استفاده از پروکسی)
                    </label>
                </div>
                <small style="color: #666; font-size: 12px; margin-top: 5px; display: block;">
                    اگر هاست شما در ایران است و تلگرام فیلتر است، گزینه "هاست ایرانی" را انتخاب کنید.
                </small>
            </div>

            <div class="form-group" id="proxy_url_group" style="display: none;">
                <label for="telegram_proxy_url">آدرس پروکسی ارسال:</label>
                <input type="text" id="telegram_proxy_url" name="telegram_proxy_url" 
                       value="<?php echo esc_attr(get_option('telegram_proxy_url', 'https://arz.appwordpresss.ir/all.php')); ?>" 
                       placeholder="مثال: https://proxy-server.com/all.php">
                <small style="color: #666; font-size: 12px; margin-top: 5px; display: block;">
                    آدرس سرور پروکسی برای ارسال پیام‌های خروجی به تلگرام (فایل all.php)
                </small>
            </div>

            <div class="form-group" id="webhook_proxy_group" style="display: none;">
                <label for="telegram_webhook_proxy">آدرس میانجی وب‌هوک:</label>
                <input type="text" id="telegram_webhook_proxy" name="telegram_webhook_proxy" 
                       value="<?php echo esc_attr(get_option('telegram_webhook_proxy', 'https://your-proxy.com/tibin.php')); ?>" 
                       placeholder="مثال: https://proxy-server.com/tibin.php">
                <small style="color: #666; font-size: 12px; margin-top: 5px; display: block;">
                    آدرس میانجی برای تنظیم وب‌هوک (فایل tibin.php) - اختیاری
                </small>
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

        <!-- بخش مدیریت وب‌هوک -->
        <div style="margin-top: 40px; padding: 20px; background: #f0f9ff; border-radius: 6px; border-right: 4px solid #3498db;">
            <h3 style="margin-top: 0; color: #2c3e50;">مدیریت وب‌هوک تلگرام</h3>
            <p style="color: #666; margin-bottom: 20px;">
                از این بخش می‌توانید وضعیت وب‌هوک ربات تلگرام خود را بررسی و مدیریت کنید.
            </p>
            
            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                <button type="button" id="check-webhook-btn" class="submit-button" style="background: #27ae60;">
                    بررسی وضعیت وب‌هوک
                </button>
                <button type="button" id="delete-webhook-btn" class="submit-button" style="background: #e74c3c;">
                    حذف وب‌هوک
                </button>
                <button type="button" id="test-webhook-btn" class="submit-button" style="background: #f39c12;">
                    تست ارسال پیام
                </button>
            </div>
            
            <div id="webhook-status" style="margin-top: 20px; padding: 15px; background: white; border-radius: 4px; display: none;">
                <h4 style="margin-top: 0;">نتیجه:</h4>
                <pre id="webhook-result" style="background: #f8f9fa; padding: 10px; border-radius: 4px; font-size: 12px; overflow-x: auto;"></pre>
            </div>
        </div>

        <div class="success-message">تنظیمات با موفقیت ذخیره شد!</div>
        <div class="error-message">خطا در ذخیره تنظیمات!</div>
    </div>

    <script>
    var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
    
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('.telegram-settings-form');
        const loading = document.querySelector('.loading');
        const successMessage = document.querySelector('.success-message');
        const errorMessage = document.querySelector('.error-message');
        const hostTypeRadios = document.querySelectorAll('input[name="telegram_host_type"]');
        const proxyUrlGroup = document.getElementById('proxy_url_group');
        const webhookProxyGroup = document.getElementById('webhook_proxy_group');

        // نمایش/مخفی کردن فیلدهای پروکسی بر اساس نوع هاست
        function toggleProxyField() {
            const selectedHostType = document.querySelector('input[name="telegram_host_type"]:checked');
            if (selectedHostType && selectedHostType.value === 'iranian') {
                proxyUrlGroup.style.display = 'block';
                webhookProxyGroup.style.display = 'block';
            } else {
                proxyUrlGroup.style.display = 'none';
                webhookProxyGroup.style.display = 'none';
            }
        }

        // اجرای تابع در ابتدا
        toggleProxyField();

        // اضافه کردن event listener برای تغییر نوع هاست
        hostTypeRadios.forEach(function(radio) {
            radio.addEventListener('change', toggleProxyField);
        });

        form.addEventListener('submit', function() {
            loading.style.display = 'inline-block';
        });

        // مدیریت دکمه‌های وب‌هوک
        const checkWebhookBtn = document.getElementById('check-webhook-btn');
        const deleteWebhookBtn = document.getElementById('delete-webhook-btn');
        const testWebhookBtn = document.getElementById('test-webhook-btn');
        const webhookStatus = document.getElementById('webhook-status');
        const webhookResult = document.getElementById('webhook-result');

        function showWebhookResult(result) {
            webhookResult.textContent = result;
            webhookStatus.style.display = 'block';
        }

        function performWebhookAction(action, buttonElement) {
            console.log('performWebhookAction called with:', action, buttonElement);
            
            const tokenElement = document.getElementById('telegram_bot_token');
            if (!tokenElement) {
                console.error('Token element not found');
                alert('خطا: فیلد توکن یافت نشد.');
                return;
            }
            
            const token = tokenElement.value;
            const hostType = document.querySelector('input[name="telegram_host_type"]:checked');
            const hostTypeValue = hostType ? hostType.value : 'foreign';
            
            console.log('Token:', token ? 'Present' : 'Empty');
            console.log('Host type:', hostTypeValue);
            
            if (!token) {
                alert('لطفاً ابتدا توکن ربات را وارد کنید.');
                return;
            }

            // نمایش loading
            const originalText = buttonElement.textContent;
            buttonElement.textContent = 'در حال پردازش...';
            buttonElement.disabled = true;

            fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'telegram_webhook_action',
                    webhook_action: action,
                    token: token,
                    host_type: hostTypeValue,
                    nonce: '<?php echo wp_create_nonce('telegram_webhook_action'); ?>'
                })
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                return response.text();
            })
            .then(text => {
                console.log('Raw response:', text);
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        showWebhookResult(data.data);
                    } else {
                        showWebhookResult('خطا: ' + data.data);
                    }
                } catch (e) {
                    console.error('JSON parse error:', e);
                    showWebhookResult('خطا در پردازش پاسخ: ' + text.substring(0, 200));
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                showWebhookResult('خطا در ارتباط: ' + error.message);
            })
            .finally(() => {
                buttonElement.textContent = originalText;
                buttonElement.disabled = false;
            });
        }

        if (checkWebhookBtn) {
            checkWebhookBtn.addEventListener('click', function(event) {
                performWebhookAction('check', this);
            });
        }

        if (deleteWebhookBtn) {
            deleteWebhookBtn.addEventListener('click', function(event) {
                if (confirm('آیا از حذف وب‌هوک اطمینان دارید؟')) {
                    performWebhookAction('delete', this);
                }
            });
        }

        if (testWebhookBtn) {
            testWebhookBtn.addEventListener('click', function(event) {
                performWebhookAction('test', this);
            });
        }

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
        $host_type = sanitize_text_field($_POST['telegram_host_type']);
        $proxy_url = sanitize_text_field($_POST['telegram_proxy_url']);
        $webhook_proxy = sanitize_text_field($_POST['telegram_webhook_proxy']);
        
        update_option('telegram_bot_Chat_id', $chat_id);
        update_option('telegram_bot_info', $botinfo);
        update_option('telegram_bot_token', $token);
        update_option('telegram_bot_url', $url_p);
        update_option('telegram_host_type', $host_type);
        update_option('telegram_proxy_url', $proxy_url);
        update_option('telegram_webhook_proxy', $webhook_proxy);
        
        telegram_bot_set_webhook($token, $url_p, $host_type);
    }
}

function telegram_bot_set_webhook($token, $url_p, $host_type = 'foreign')
{
    $admin_login = false;
    update_option('admin_login_p', $admin_login);
    
    if ($host_type === 'iranian') {
        // برای هاست ایرانی از میانجی وب‌هوک استفاده می‌کنیم
        $webhook_proxy = get_option('telegram_webhook_proxy', '');
        
        if (!empty($webhook_proxy)) {
            // استفاده از میانجی tibin.php
            $cloud = 'Location: ' . $webhook_proxy . '?bot=' . $token . '&url=' . $url_p . '&setWebP=True';
        } else {
            // fallback به روش قدیمی
            $proxy_url = get_option('telegram_proxy_url', 'https://arz.appwordpresss.ir/all.php');
            $cloud = 'Location: ' . $proxy_url . '?bot=' . $token . '&url=' . $url_p . '&setWebP=True';
        }
    } else {
        // برای هاست خارجی مستقیماً به API تلگرام متصل می‌شویم
        $webhook_url = "https://api.telegram.org/bot{$token}/setWebhook?url=" . urlencode($url_p);
        
        // ارسال درخواست مستقیم به تلگرام
        $response = wp_remote_get($webhook_url, array(
            'timeout' => 30,
            'sslverify' => false
        ));
        
        if (is_wp_error($response)) {
            // در صورت خطا، به روش قدیمی برگردیم
            $cloud = 'Location: ' . $url_p . '?bot=' . $token . '&url=' . $url_p . '&setWebP=True';
        } else {
            // نمایش نتیجه تنظیم وب‌هوک
            $body = wp_remote_retrieve_body($response);
            $result = json_decode($body, true);
            
            if (isset($result['ok']) && $result['ok']) {
                wp_redirect(admin_url('admin.php?page=telegram-webhook-plugin&webhook_status=success'));
                exit;
            } else {
                wp_redirect(admin_url('admin.php?page=telegram-webhook-plugin&webhook_status=error&error_msg=' . urlencode($result['description'] ?? 'خطای نامشخص')));
                exit;
            }
        }
    }
    
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
    $token = get_option('telegram_bot_token');
    $host_type = get_option('telegram_host_type', 'foreign');
    $chat_id = get_option('telegram_bot_Chat_id');
    
    if (empty($token) || empty($chat_id)) {
        error_log('Telegram: Token or Chat ID is empty');
        return;
    }
    
    // Log file
    $log_file = plugin_dir_path(__FILE__) . 'telegram_logs.txt';
    
    if ($host_type === 'iranian') {
        // استفاده از پروکسی برای هاست ایرانی
        $workerUrl = get_option('telegram_proxy_url', 'https://arz.appwordpresss.ir/all.php');
        
        $data = array(
            'chatid' => $chat_id,
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
        
        if (curl_errno($ch)) {
            file_put_contents($log_file, "Proxy Error: " . curl_error($ch) . "\n", FILE_APPEND);
        }
        curl_close($ch);
        
    } else {
        // اتصال مستقیم به API تلگرام برای هاست خارجی - بدون واسطه
        $telegram_api_url = "https://api.telegram.org/bot{$token}/sendMessage";
        
        $data = array(
            'chat_id' => $chat_id,
            'text' => $message,
            'parse_mode' => 'HTML'
        );
        
        $response = wp_remote_post($telegram_api_url, array(
            'body' => $data,
            'timeout' => 30,
            'sslverify' => false
        ));
        
        if (is_wp_error($response)) {
            file_put_contents($log_file, "Direct API Error: " . $response->get_error_message() . "\n", FILE_APPEND);
            $response = 'Error: ' . $response->get_error_message();
        } else {
            $response = wp_remote_retrieve_body($response);
        }
    }

    // Log the response
    file_put_contents($log_file, "Telegram send response (host_type: {$host_type}): " . $response . "\n", FILE_APPEND);
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

// AJAX handler برای مدیریت وب‌هوک
add_action('wp_ajax_telegram_webhook_action', 'handle_telegram_webhook_action');

function handle_telegram_webhook_action() {
    // بررسی nonce برای امنیت
    if (!wp_verify_nonce($_POST['nonce'], 'telegram_webhook_action')) {
        wp_send_json_error('خطای امنیتی');
        return;
    }
    
    $action = sanitize_text_field($_POST['webhook_action']);
    $token = sanitize_text_field($_POST['token']);
    $host_type = sanitize_text_field($_POST['host_type']);
    
    if (empty($token)) {
        wp_send_json_error('توکن ربات خالی است');
        return;
    }
    
    switch ($action) {
        case 'check':
            $result = check_telegram_webhook($token, $host_type);
            break;
            
        case 'delete':
            $result = delete_telegram_webhook($token, $host_type);
            break;
            
        case 'test':
            $result = test_telegram_message($token, $host_type);
            break;
            
        default:
            wp_send_json_error('عملیات نامعتبر');
            return;
    }
    
    wp_send_json_success($result);
}

function check_telegram_webhook($token, $host_type) {
    if ($host_type === 'iranian') {
        return "برای هاست ایرانی، بررسی وضعیت وب‌هوک از طریق پروکسی امکان‌پذیر نیست.\nوب‌هوک از طریق پروکسی تنظیم شده است.";
    }
    
    $url = "https://api.telegram.org/bot{$token}/getWebhookInfo";
    
    $response = wp_remote_get($url, array(
        'timeout' => 30,
        'sslverify' => false
    ));
    
    if (is_wp_error($response)) {
        return 'خطا در اتصال: ' . $response->get_error_message();
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (isset($data['ok']) && $data['ok']) {
        return json_encode($data['result'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } else {
        return 'خطا: ' . ($data['description'] ?? 'نامشخص');
    }
}

function delete_telegram_webhook($token, $host_type) {
    if ($host_type === 'iranian') {
        return "برای هاست ایرانی، حذف وب‌هوک از طریق پروکسی امکان‌پذیر نیست.\nلطفاً مستقیماً با مدیر پروکسی تماس بگیرید.";
    }
    
    $url = "https://api.telegram.org/bot{$token}/deleteWebhook";
    
    $response = wp_remote_get($url, array(
        'timeout' => 30,
        'sslverify' => false
    ));
    
    if (is_wp_error($response)) {
        return 'خطا در اتصال: ' . $response->get_error_message();
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (isset($data['ok']) && $data['ok']) {
        return 'وب‌هوک با موفقیت حذف شد.';
    } else {
        return 'خطا در حذف وب‌هوک: ' . ($data['description'] ?? 'نامشخص');
    }
}

function test_telegram_message($token, $host_type) {
    $chat_id = get_option('telegram_bot_Chat_id');
    
    if (empty($chat_id)) {
        return 'شناسه چت تنظیم نشده است.';
    }
    
    // تشخیص نوع مقصد
    $destination_type = '';
    if (strpos($chat_id, '-100') === 0) {
        $destination_type = 'کانال تلگرام';
    } elseif (strpos($chat_id, '-') === 0) {
        $destination_type = 'گروه تلگرام';
    } else {
        $destination_type = 'چت خصوصی';
    }
    
    $message = "🤖 تست ارسال پیام از افزونه فراز\n\n" .
               "⏰ زمان: " . current_time('Y-m-d H:i:s') . "\n" .
               "🌐 نوع هاست: " . ($host_type === 'iranian' ? 'ایرانی (پروکسی)' : 'خارجی (مستقیم)') . "\n" .
               "📱 مقصد: " . $destination_type . " (" . $chat_id . ")\n" .
               "✅ ارتباط برقرار است!";
    
    if ($host_type === 'iranian') {
        $proxy_url = get_option('telegram_proxy_url', 'https://arz.appwordpresss.ir/all.php');
        
        $data = array(
            'chatid' => $chat_id,
            'bot' => $token,
            'message' => $message,
            'isphoto' => 'false'
        );
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $proxy_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            return 'خطا در ارسال از طریق پروکسی: ' . curl_error($ch);
        }
        
        curl_close($ch);
        
        $result = json_decode($response, true);
        if (isset($result['status']) && $result['status'] === 'success') {
            return 'پیام تست با موفقیت از طریق پروکسی ارسال شد!' .
                   "\n\n📊 اطلاعات ارسال:" .
                   "\n• مقصد: " . $destination_type . " (" . $chat_id . ")" .
                   "\n• روش: ارسال از طریق پروکسی" .
                   "\n• زمان: " . current_time('Y-m-d H:i:s');
        } else {
            return 'خطا در ارسال پیام: ' . $response;
        }
        
    } else {
        $url = "https://api.telegram.org/bot{$token}/sendMessage";
        
        $data = array(
            'chat_id' => $chat_id,
            'text' => $message,
            'parse_mode' => 'HTML'
        );
        
        $response = wp_remote_post($url, array(
            'body' => $data,
            'timeout' => 30,
            'sslverify' => false
        ));
        
        if (is_wp_error($response)) {
            return 'خطا در اتصال مستقیم: ' . $response->get_error_message();
        }
        
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        
        if (isset($result['ok']) && $result['ok']) {
            return 'پیام تست با موفقیت به صورت مستقیم ارسال شد!' . 
                   "\n\n📊 اطلاعات ارسال:" .
                   "\n• مقصد: " . $destination_type . " (" . $chat_id . ")" .
                   "\n• روش: اتصال مستقیم به API تلگرام" .
                   "\n• زمان: " . current_time('Y-m-d H:i:s');
        } else {
            return 'خطا در ارسال پیام: ' . ($result['description'] ?? 'نامشخص');
        }
    }
}