<?php
// Add settings menu
add_action('admin_menu', 'tsp_add_menu');

// مدیریت cron job برای polling
add_action('init', 'telegram_manage_polling_cron');
function telegram_manage_polling_cron() {
    $update_method = get_option('telegram_update_method', 'webhook');
    $hook_name = 'telegram_poll_updates_hook';
    
    if ($update_method === 'polling') {
        // اگر polling فعال است و cron وجود ندارد، آن را ایجاد کن
        if (!wp_next_scheduled($hook_name)) {
            // استفاده از wp_schedule_single_event برای انعطاف بیشتر
            wp_schedule_single_event(time() + 3, $hook_name);
        }
    } else {
        // اگر polling غیرفعال است و cron وجود دارد، آن را حذف کن
        $timestamp = wp_next_scheduled($hook_name);
        if ($timestamp) {
            wp_unschedule_event($timestamp, $hook_name);
        }
    }
}

// اجرای polling
add_action('telegram_poll_updates_hook', 'telegram_execute_polling');
function telegram_execute_polling() {
    $update_method = get_option('telegram_update_method', 'webhook');
    
    // فقط اگر polling فعال باشد
    if ($update_method !== 'polling') {
        return;
    }
    
    $updates = telegram_poll_updates();
    
    if ($updates !== false && !empty($updates)) {
        process_telegram_updates($updates);
    }
    
    // برنامه‌ریزی برای اجرای بعدی (3 ثانیه بعد)
    if (!wp_next_scheduled('telegram_poll_updates_hook')) {
        wp_schedule_single_event(time() + 3, 'telegram_poll_updates_hook');
    }
}

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
                <label for="faraz_telegram_admin_ids">شناسه عددی ادمین‌ها (با , یا فاصله جدا کنید):</label>
                <input type="text" id="faraz_telegram_admin_ids" name="faraz_telegram_admin_ids"
                       value="<?php echo esc_attr(get_option('faraz_telegram_admin_ids', '80266430')); ?>"
                       placeholder="مثال: 80266430, 123456789, 987654321">
                <small style="color: #666; font-size: 12px; margin-top: 5px; display: block;">آی‌دی عددی کاربرانی که اجازه ارسال پست از طریق ربات دارند. می‌توانید چند آی‌دی وارد کنید.</small>
            </div>

            <div class="form-group">
                <label for="telegram_bot_token">توکن ربات تلگرام:</label>
                <input type="text" id="telegram_bot_token" name="telegram_bot_token" 
                       value="<?php echo esc_attr(get_option('telegram_bot_token')); ?>" 
                       placeholder="مثال: 123456789:ABCdefGHIjklMNOpqrsTUVwxyz">
            </div>

            <div class="form-group">
                <label>روش دریافت پیام‌ها:</label>
                <div style="margin-top: 10px;">
                    <label style="display: inline-flex; align-items: center; margin-left: 20px;">
                        <input type="radio" name="telegram_update_method" value="webhook" 
                               <?php checked(get_option('telegram_update_method', 'webhook'), 'webhook'); ?> 
                               style="margin-left: 8px;" id="update_method_webhook">
                        Webhook (پیشنهادی برای سرورهای آنلاین)
                    </label>
                    <label style="display: inline-flex; align-items: center;">
                        <input type="radio" name="telegram_update_method" value="polling" 
                               <?php checked(get_option('telegram_update_method', 'webhook'), 'polling'); ?> 
                               style="margin-left: 8px;" id="update_method_polling">
                        Long Polling (برای localhost و سرورهای بدون دسترسی به اینترنت)
                    </label>
                </div>
                <small style="color: #666; font-size: 12px; margin-top: 5px; display: block;">
                    Webhook: تلگرام پیام‌ها را به سرور شما ارسال می‌کند (نیاز به HTTPS و دسترسی عمومی)
                </small>
                <small style="color: #666; font-size: 12px; margin-top: 5px; display: block;">
                    Long Polling: افزونه به صورت دوره‌ای از تلگرام پیام‌ها را دریافت می‌کند (مناسب برای localhost)
                </small>
            </div>

            <div class="form-group" id="webhook_url_group">
                <label for="telegram_bot_url">آدرس وب‌هوک ربات:</label>
                <input type="text" id="telegram_bot_url" name="telegram_bot_url" 
                       value="<?php echo esc_attr(get_option('telegram_bot_url', home_url('/wp-json/faraz/v1/handle/'))); ?>" 
                       placeholder="مثال: https://yoursite.com/wp-json/faraz/v1/handle/">
                <small style="color: #666; font-size: 12px; margin-top: 5px; display: block;">
                    آدرس endpoint سایت شما که تلگرام پیام‌ها را به آن ارسال می‌کند
                </small>
                <small style="color: #e74c3c; font-size: 12px; margin-top: 5px; display: block;">
                    ⚠️ مهم: آدرس باید با https شروع شود و به /handle/ ختم شود
                </small>
                <small style="color: #3498db; font-size: 12px; margin-top: 5px; display: block;">
                    💡 پیشنهاد: از آدرس پیش‌فرض استفاده کنید
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
                       value="<?php echo esc_attr(get_option('telegram_webhook_proxy', 'https://arz.appwordpresss.ir/tibin.php')); ?>" 
                       placeholder="مثال: https://proxy-server.com/tibin.php">
                <small style="color: #666; font-size: 12px; margin-top: 5px; display: block;">
                    آدرس میانجی برای تنظیم وب‌هوک (فایل tibin.php) - برای هاست ایرانی ضروری است
                </small>
                <small style="color: #e74c3c; font-size: 12px; margin-top: 5px; display: block;">
                    ⚠️ مهم: این فیلد برای تنظیم webhook در هاست ایرانی ضروری است
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
                <button type="button" id="test-callback-btn" class="submit-button" style="background: #9b59b6;">
                    تست Callback Query
                </button>
                <button type="button" id="test-url-btn" class="submit-button" style="background: #e67e22;">
                    تست URL وب‌هوک
                </button>
                <button type="button" id="fix-webhook-btn" class="submit-button" style="background: #2c3e50;">
                    🔧 اصلاح Webhook
                </button>
                <button type="button" id="manual-webhook-btn" class="submit-button" style="background: #8e44ad;">
                    ⚙️ تنظیم دستی Webhook
                </button>
                <button type="button" id="test-proxy-btn" class="submit-button" style="background: #16a085;">
                    🔍 تست پروکسی
                </button>
                <button type="button" id="switch-foreign-btn" class="submit-button" style="background: #e74c3c;">
                    🌍 تغییر به هاست خارجی
                </button>
                <button type="button" id="full-test-btn" class="submit-button" style="background: #f1c40f;">
                    🧪 تست کامل
                </button>
                <button type="button" id="test-polling-btn" class="submit-button" style="background: #1abc9c;">
                    🔄 تست Long Polling
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
        const webhookUrlGroup = document.getElementById('webhook_url_group');
        const updateMethodRadios = document.querySelectorAll('input[name="telegram_update_method"]');

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

        // نمایش/مخفی کردن فیلد webhook URL بر اساس روش دریافت
        function toggleWebhookField() {
            const selectedMethod = document.querySelector('input[name="telegram_update_method"]:checked');
            if (selectedMethod && selectedMethod.value === 'webhook') {
                webhookUrlGroup.style.display = 'block';
            } else {
                webhookUrlGroup.style.display = 'none';
            }
        }

        // اجرای توابع در ابتدا
        toggleProxyField();
        toggleWebhookField();

        // اضافه کردن event listener برای تغییر نوع هاست
        hostTypeRadios.forEach(function(radio) {
            radio.addEventListener('change', toggleProxyField);
        });

        // اضافه کردن event listener برای تغییر روش دریافت
        updateMethodRadios.forEach(function(radio) {
            radio.addEventListener('change', toggleWebhookField);
        });

        form.addEventListener('submit', function() {
            loading.style.display = 'inline-block';
        });

        // مدیریت دکمه‌های وب‌هوک
        const checkWebhookBtn = document.getElementById('check-webhook-btn');
        const deleteWebhookBtn = document.getElementById('delete-webhook-btn');
        const testWebhookBtn = document.getElementById('test-webhook-btn');
        const testCallbackBtn = document.getElementById('test-callback-btn'); // New button
        const testUrlBtn = document.getElementById('test-url-btn'); // New button
        const fixWebhookBtn = document.getElementById('fix-webhook-btn'); // New button
        const manualWebhookBtn = document.getElementById('manual-webhook-btn'); // New button
        const testProxyBtn = document.getElementById('test-proxy-btn'); // New button
        const switchForeignBtn = document.getElementById('switch-foreign-btn'); // New button
        const fullTestBtn = document.getElementById('full-test-btn'); // New button
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

        if (testCallbackBtn) {
            testCallbackBtn.addEventListener('click', function(event) {
                performWebhookAction('test_callback', this); // Assuming 'test_callback' is the action for testing callback query
            });
        }

        if (testUrlBtn) {
            testUrlBtn.addEventListener('click', function(event) {
                performWebhookAction('test_url', this); // Assuming 'test_url' is the action for testing webhook URL
            });
        }

        if (fixWebhookBtn) {
            fixWebhookBtn.addEventListener('click', function(event) {
                performWebhookAction('fix_webhook', this); // Assuming 'fix_webhook' is the action for fixing webhook URL
            });
        }

        if (manualWebhookBtn) {
            manualWebhookBtn.addEventListener('click', function(event) {
                performWebhookAction('manual_webhook', this); // Assuming 'manual_webhook' is the action for setting manual webhook
            });
        }

        if (testProxyBtn) {
            testProxyBtn.addEventListener('click', function(event) {
                performWebhookAction('test_proxy', this); // Assuming 'test_proxy' is the action for testing proxy connection
            });
        }

        if (switchForeignBtn) {
            switchForeignBtn.addEventListener('click', function(event) {
                performWebhookAction('switch_to_foreign', this); // Assuming 'switch_to_foreign' is the action for switching to foreign host
            });
        }

        if (fullTestBtn) {
            fullTestBtn.addEventListener('click', function(event) {
                performWebhookAction('full_test', this); // Assuming 'full_test' is the action for full test
            });
        }

        const testPollingBtn = document.getElementById('test-polling-btn');
        if (testPollingBtn) {
            testPollingBtn.addEventListener('click', function(event) {
                performWebhookAction('test_polling', this);
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
        $admin_ids = isset($_POST['faraz_telegram_admin_ids']) ? sanitize_text_field($_POST['faraz_telegram_admin_ids']) : '';
        $update_method = isset($_POST['telegram_update_method']) ? sanitize_text_field($_POST['telegram_update_method']) : 'webhook';

        // دریافت روش قبلی
        $old_update_method = get_option('telegram_update_method', 'webhook');

        // اگر هاست خارجی انتخاب شده باشد، مقادیر پروکسی را برای جداسازی کامل خالی می‌کنیم
        if ($host_type === 'foreign') {
            $proxy_url = '';
            $webhook_proxy = '';
        }
        
        update_option('telegram_bot_Chat_id', $chat_id);
        update_option('telegram_bot_info', $botinfo);
        update_option('telegram_bot_token', $token);
        update_option('telegram_bot_url', $url_p);
        update_option('telegram_host_type', $host_type);
        update_option('telegram_proxy_url', $proxy_url);
        update_option('telegram_webhook_proxy', $webhook_proxy);
        update_option('faraz_telegram_admin_ids', $admin_ids);
        update_option('telegram_update_method', $update_method);
        
        // مدیریت تغییر روش
        if ($update_method === 'polling') {
            // اگر polling انتخاب شده، webhook را حذف کن
            if ($old_update_method === 'webhook') {
                delete_telegram_webhook($token, $host_type);
            }
            // cron job به صورت خودکار در telegram_manage_polling_cron مدیریت می‌شود
            echo '<div class="updated"><p>تنظیمات با موفقیت ذخیره شد! Long Polling فعال شد و webhook حذف شد.</p></div>';
        } else {
            // اگر webhook انتخاب شده، polling را متوقف کن و webhook را تنظیم کن
            if ($old_update_method === 'polling') {
                // cron job به صورت خودکار در telegram_manage_polling_cron متوقف می‌شود
                // reset کردن last_update_id
                update_option('telegram_last_update_id', 0);
            }
            
            // تنظیم وب‌هوک
            if (!empty($token) && !empty($url_p)) {
                $webhook_result = telegram_bot_set_webhook($token, $url_p, $host_type);
                
                if ($webhook_result) {
                    echo '<div class="updated"><p>تنظیمات با موفقیت ذخیره شد و وب‌هوک تنظیم شد!</p></div>';
                } else {
                    echo '<div class="error"><p>تنظیمات ذخیره شد ولی خطا در تنظیم وب‌هوک!</p></div>';
                }
            } else {
                echo '<div class="updated"><p>تنظیمات با موفقیت ذخیره شد!</p></div>';
            }
        }
    }
}

function telegram_bot_set_webhook($token, $url_p, $host_type = 'foreign')
{
    $admin_login = false;
    update_option('admin_login_p', $admin_login);
    
    // جلوگیری از حذف ناخواسته وب‌هوک: اگر URL خالی باشد هرگز درخواست setWebhook ارسال نکن
    $token = trim((string)$token);
    $url_p = trim((string)$url_p);
    if ($token === '' || $url_p === '') {
        return false;
    }
    
    if ($host_type === 'iranian') {
        // برای هاست ایرانی از میانجی وب‌هوک استفاده می‌کنیم
        $webhook_proxy = get_option('telegram_webhook_proxy', '');
        
        if (!empty($webhook_proxy)) {
            // استفاده از میانجی tibin.php
            $webhook_url = $webhook_proxy . '?bot=' . $token . '&url=' . urlencode($url_p) . '&setWebP=True';
        } else {
            // fallback به روش قدیمی
            $proxy_url = get_option('telegram_proxy_url', 'https://arz.appwordpresss.ir/all.php');
            $webhook_url = $proxy_url . '?bot=' . $token . '&url=' . urlencode($url_p) . '&setWebP=True';
        }
        
        // ارسال درخواست به پروکسی
        $response = wp_remote_get($webhook_url, array(
            'timeout' => 30,
            'sslverify' => false
        ));
        
        if (is_wp_error($response)) {
            return false;
        } else {
            $body = wp_remote_retrieve_body($response);
            return true; // موفقیت
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
            return false;
        } else {
            // بررسی نتیجه تنظیم وب‌هوک
            $body = wp_remote_retrieve_body($response);
            $result = json_decode($body, true);
            
            if (isset($result['ok']) && $result['ok']) {
                return true; // موفقیت
            } else {
                return false; // خطا
            }
        }
    }
}

 
add_action('rest_api_init', function() {
    register_rest_route('faraz/v1', '/handle/', array(
        'methods' => 'POST',
        'callback' => 'handle_request',
        'permission_callback' => '__return_true',
    ));
    
    // اضافه کردن endpoint تست
    register_rest_route('faraz/v1', '/test/', array(
        'methods' => 'GET',
        'callback' => 'test_webhook_endpoint',
        'permission_callback' => '__return_true',
    ));
});

function test_webhook_endpoint() {
    if (function_exists('smart_admin_get_setting') && smart_admin_get_setting('debug_mode')) {
        if (function_exists('smart_admin_debug_log')) {
            smart_admin_debug_log("=== WEBHOOK TEST ===", "INFO");
            smart_admin_debug_log("Webhook endpoint is working!", "INFO");
        } else {
            $log_file = plugin_dir_path(__FILE__) . 'telegram_logs.txt';
            file_put_contents($log_file, "=== WEBHOOK TEST ===\n", FILE_APPEND);
            file_put_contents($log_file, "Time: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
            file_put_contents($log_file, "Webhook endpoint is working!\n", FILE_APPEND);
        }
    }
    
    return array(
        'status' => 'success',
        'message' => 'Webhook endpoint is working',
        'time' => date('Y-m-d H:i:s')
    );
}
// استخراج عنوان و متن از ورودی کاربر با پشتیبانی از «;» و «؛»
function faraz_parse_title_and_content_from_text($text) {
    $text = trim((string)$text);
    // یکسان‌سازی جداکننده‌ها
    $normalized = str_replace(['؛'], [';'], $text);

    $title = '';
    $content = '';
    if (strpos($normalized, ';') !== false) {
        list($title, $content) = array_map('trim', explode(';', $normalized, 2));
    } else {
        // fallback: اولین خط را عنوان درنظر بگیریم
        $parts = preg_split("/\r?\n/", $text, 2);
        $title = trim($parts[0]);
        $content = isset($parts[1]) ? trim($parts[1]) : $text;
    }

    // حذف علائم انتهایی از عنوان
    $title = trim($title, " \t\n\r\0\x0B:؛،-");

    // اگر ابتدای متن تکرار عنوان بود، حذفش کنیم
    $pattern = '/^\s*' . preg_quote($title, '/') . '\s*[:؛،\-–—]*\s*/u';
    $content = preg_replace($pattern, '', $content, 1);

    return array($title, $content);
}
// نرمال‌سازی ارقام فارسی/عربی به انگلیسی
function faraz_normalize_digits($text) {
    $persian = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹','٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
    $english = ['0','1','2','3','4','5','6','7','8','9','0','1','2','3','4','5','6','7','8','9'];
    return str_replace($persian, $english, (string)$text);
}

// بررسی اینکه user_id در لیست ادمین‌ها هست یا خیر
function faraz_is_telegram_admin($user_id) {
    $user_id = faraz_normalize_digits($user_id);
    $allowed_admins_option = (string) get_option('faraz_telegram_admin_ids', '');
    $allowed_admins_option = faraz_normalize_digits($allowed_admins_option);
    // جداکننده: ویرگول انگلیسی/فاصله/خط جدید/ویرگول فارسی
    $parts = preg_split('/[\s,،]+/', $allowed_admins_option);
    $parts = array_filter(array_map('trim', (array)$parts));
    // هم به صورت رشته هم عدد بررسی می‌کنیم
    foreach ($parts as $part) {
        if ($part === '') continue;
        if ((string)$part === (string)$user_id) return true;
        if (ctype_digit($part) && (int)$part === (int)$user_id) return true;
    }
    return false;
}
/**
 * دانلود فایل تلگرام و ذخیره در رسانه وردپرس و برگرداندن URL نهایی
 */
function faraz_download_telegram_file_to_wp_media($file_id) {
    $token = get_option('telegram_bot_token');
    if (empty($token) || empty($file_id)) return '';

    // گام ۱: دریافت مسیر فایل از تلگرام
    $get_file_url = "https://api.telegram.org/bot{$token}/getFile?file_id=" . urlencode($file_id);
    $response = wp_remote_get($get_file_url, array('timeout' => 30, 'sslverify' => false));
    if (is_wp_error($response)) return '';
    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (!isset($body['ok']) || !$body['ok'] || empty($body['result']['file_path'])) return '';

    $file_path = $body['result']['file_path'];
    $download_url = "https://api.telegram.org/file/bot{$token}/" . $file_path;

    // گام ۲: دانلود باینری و ذخیره موقت
    // نیاز به فایل‌های کتابخانه رسانه
    if (!function_exists('download_url')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }
    if (!function_exists('media_handle_sideload')) {
        require_once ABSPATH . 'wp-admin/includes/media.php';
    }
    if (!function_exists('wp_read_image_metadata')) {
        require_once ABSPATH . 'wp-admin/includes/image.php';
    }

    $tmp = download_url($download_url, 30);
    if (is_wp_error($tmp)) return '';

    // تعیین نام فایل
    $filename = basename($file_path);
    $file_array = array(
        'name'     => $filename,
        'tmp_name' => $tmp,
    );

    // وارد کردن به کتابخانه رسانه
    $attachment_id = media_handle_sideload($file_array, 0);
    if (is_wp_error($attachment_id)) {
        @unlink($tmp);
        return '';
    }

    $url = wp_get_attachment_url($attachment_id);
    return $url ? $url : '';
}

/**
 * درصورت نیاز الصاق تصویر خارجی به عنوان تصویر شاخص پست
 */
function faraz_attach_external_image_as_featured($post_id, $image_url) {
    if (empty($image_url)) return false;
    if (!function_exists('download_url')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }
    if (!function_exists('media_handle_sideload')) {
        require_once ABSPATH . 'wp-admin/includes/media.php';
    }
    if (!function_exists('wp_read_image_metadata')) {
        require_once ABSPATH . 'wp-admin/includes/image.php';
    }

    $tmp = download_url($image_url, 30);
    if (is_wp_error($tmp)) return false;

    $file_array = array(
        'name'     => basename(parse_url($image_url, PHP_URL_PATH)),
        'tmp_name' => $tmp,
    );

    $attachment_id = media_handle_sideload($file_array, $post_id);
    if (is_wp_error($attachment_id)) {
        @unlink($tmp);
        return false;
    }
    set_post_thumbnail($post_id, $attachment_id);
    return true;
}
function handle_request()
{
	// Log all incoming requests (حداقلی حتی بدون حالت دیباگ)
	$log_file = plugin_dir_path(__FILE__) . 'telegram_logs.txt';
	$update_raw = file_get_contents('php://input');
	file_put_contents($log_file, "=== NEW REQUEST (minimal log) ===\n", FILE_APPEND);
	file_put_contents($log_file, "Time: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
	file_put_contents($log_file, "Request method: " . ($_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN') . "\n", FILE_APPEND);
	file_put_contents($log_file, "Raw update: " . $update_raw . "\n", FILE_APPEND);
    
    if (function_exists('smart_admin_get_setting') && smart_admin_get_setting('debug_mode')) {
        if (function_exists('smart_admin_debug_log')) {
            smart_admin_debug_log("=== NEW REQUEST ===", "INFO");
            smart_admin_debug_log("Time: " . date('Y-m-d H:i:s'), "INFO");
            smart_admin_debug_log("Raw update: " . $update_raw, "INFO");
            smart_admin_debug_log("Request method: " . $_SERVER['REQUEST_METHOD'], "INFO");
            smart_admin_debug_log("Content type: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set'), "INFO");
            smart_admin_debug_log("User agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'not set'), "INFO");
            smart_admin_debug_log("Remote IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'not set'), "INFO");
        } else {
            $log_file = plugin_dir_path(__FILE__) . 'telegram_logs.txt';
            file_put_contents($log_file, "=== NEW REQUEST ===\n", FILE_APPEND);
            file_put_contents($log_file, "Time: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
            file_put_contents($log_file, "Raw update: " . $update_raw . "\n", FILE_APPEND);
            file_put_contents($log_file, "Request method: " . $_SERVER['REQUEST_METHOD'] . "\n", FILE_APPEND);
            file_put_contents($log_file, "Content type: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set') . "\n", FILE_APPEND);
            file_put_contents($log_file, "User agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'not set') . "\n", FILE_APPEND);
            file_put_contents($log_file, "Remote IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'not set') . "\n", FILE_APPEND);
        }
    }

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $update = json_decode($update_raw, true);
		file_put_contents($log_file, "Decoded keys: " . implode(', ', array_keys((array)$update)) . "\n", FILE_APPEND);

        // Log decoded update
        if (function_exists('smart_admin_get_setting') && smart_admin_get_setting('debug_mode')) {
            if (function_exists('smart_admin_debug_log')) {
                smart_admin_debug_log("Decoded update: " . print_r($update, true), "INFO");
            } else {
                file_put_contents($log_file, "Decoded update: " . print_r($update, true) . "\n", FILE_APPEND);
            }
        }

		if (isset($update['message'])) {
            $message_text = isset($update['message']['text']) ? $update['message']['text'] : '';
			$token = get_option('telegram_bot_token');
			$url_p = get_option('telegram_bot_url');
			$admin_login = get_option('admin_login_p');
			$chat_id = get_option('telegram_bot_Chat_id'); // admin/group default chat id
			$current_chat_id = isset($update['message']['chat']['id']) ? $update['message']['chat']['id'] : $chat_id; // reply to sender chat when available
            $sender_user_id = isset($update['message']['from']['id']) ? (string)$update['message']['from']['id'] : '';
            $allowed_admins_option = (string) get_option('faraz_telegram_admin_ids', '');
            $allowed_admins = array_filter(array_map('trim', preg_split('/[\s,،]+/', faraz_normalize_digits($allowed_admins_option))));
            
            if (function_exists('smart_admin_get_setting') && smart_admin_get_setting('debug_mode')) {
                if (function_exists('smart_admin_debug_log')) {
                    smart_admin_debug_log("Processing message: $message_text", "INFO");
                    smart_admin_debug_log("Sender: $sender_user_id | Admins: " . implode(',', $allowed_admins), "INFO");
                } else {
                    file_put_contents($log_file, "Processing message: $message_text\n", FILE_APPEND);
                    file_put_contents($log_file, "Sender: $sender_user_id | Admins: " . implode(',', $allowed_admins) . "\n", FILE_APPEND);
                }
            }
            
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
				// پاسخ مستقیم به همان چتی که /start فرستاده شده
				send_to_telegram($response_message, $current_chat_id);
            }
			elseif (strpos($message_text, '/ping') === 0) { 
				// پاسخ ping در همان چت
				send_to_telegram("hello", $current_chat_id);
            }
            elseif (strpos($message_text, '/send_drafts') === 0) {
                send_to_telegram("پست ها در حال ارسال هستند..."); 
                send_all_draft_posts($chat_id);
            }
            elseif(strpos($message_text, '/publish_all_drafts') === 0) {
                // کد منتشر کردن همه پست‌ها
            }
            elseif (!empty($update['message']['photo']) || ((!empty($message_text)) && (strpos(str_replace('؛',';',$message_text), ';') !== false))) {
                $is_admin = faraz_is_telegram_admin($sender_user_id);
                if (!$is_admin) {
                    send_to_telegram('⛔ شما ادمین نیستید و امکان ارسال پست را ندارید.', $current_chat_id);
                } else {
                    // حالت ارسال عکس + کپشن یا متن با الگوی عنوان;متن
                    $post_title = '';
                    $post_content = '';
                    $featured_image_url = '';

                    if (!empty($update['message']['photo'])) {
                        // کپشن اجباری برای جداسازی عنوان;متن
                        $caption_text = isset($update['message']['caption']) ? $update['message']['caption'] : '';
                        list($post_title, $post_content) = faraz_parse_title_and_content_from_text($caption_text);
                        // گرفتن فایل بزرگتر عکس
                        $photos = $update['message']['photo'];
                        $largest = end($photos);
                        $file_id = $largest['file_id'];
                        $featured_image_url = faraz_download_telegram_file_to_wp_media($file_id);
                    } else {
                        // پیام متنی با عنوان;متن
                        list($post_title, $post_content) = faraz_parse_title_and_content_from_text($message_text);
                    }

                    if (empty($post_title)) {
                        // اگر کپشن خالی یا جداکننده نداشت، یک عنوان کوتاه از متن بسازیم
                        $post_title = wp_trim_words(wp_strip_all_tags($post_content), 12, '');
                        if (empty($post_title)) { $post_title = 'بدون عنوان'; }
                    }

                    // ایجاد پیش‌نویس سفارشی (وضعیت faraz برای چرخه بازبینی)
                    $post_id = wp_insert_post([
                        'post_title'   => $post_title,
                        'post_content' => $post_content,
                        'post_status'  => 'faraz',
                        'post_type'    => 'post',
                        'post_author'  => 1,
                    ]);

                    if (!is_wp_error($post_id)) {
                        if (!empty($featured_image_url)) {
                            // مستقیماً تصویر را به‌عنوان تصویر شاخص قرار بده؛
                            // از تابع داخلی خودمان استفاده می‌کنیم تا وابسته به تنظیمات Unsplash نباشد
                            $attached = faraz_attach_external_image_as_featured($post_id, $featured_image_url);
                            if ($attached) {
                                update_post_meta($post_id, '_faraz_featured_source_url', esc_url_raw($featured_image_url));
                            }
                        }
                        // پیش‌نمایش برای ادمین ارسال شود
                        send_post_to_telegram($post_id, $current_chat_id);
                        send_to_telegram("اگر تصویر یا فایل دیگری برای اضافه‌کردن داری ارسال کن (اختیاری). برای رد کردن، چیزی نفرست.", $current_chat_id);
                    } else {
                        send_to_telegram('خطا در ایجاد پست: ' . $post_id->get_error_message(), $current_chat_id);
                    }
                }
            }
            elseif (strpos($message_text, '/id') === 0 || strpos($message_text, '/whoami') === 0) {
                $username = isset($update['message']['from']['username']) ? '@' . $update['message']['from']['username'] : '—';
                $first = isset($update['message']['from']['first_name']) ? $update['message']['from']['first_name'] : '';
                $last  = isset($update['message']['from']['last_name']) ? $update['message']['from']['last_name'] : '';
                $is_admin = faraz_is_telegram_admin($sender_user_id) ? 'بله' : 'خیر';
                $info = "👤 اطلاعات شما\nID: {$sender_user_id}\nUsername: {$username}\nName: {$first} {$last}\nادمین: {$is_admin}";
                send_to_telegram($info, $current_chat_id);
            }
        }
        elseif (isset($update['callback_query'])) {
            $callback_query = $update['callback_query'];
            $callback_data = $callback_query['data'];
            // چت مقصد همان چتی است که دکمه در آن کلیک شده (گروه/کانال/خصوصی)
            $chat_id = isset($callback_query['message']['chat']['id']) ? $callback_query['message']['chat']['id'] : $callback_query['from']['id'];
            $message_id = $callback_query['message']['message_id'];

            // Log callback query data
            if (function_exists('smart_admin_get_setting') && smart_admin_get_setting('debug_mode')) {
                if (function_exists('smart_admin_debug_log')) {
                    smart_admin_debug_log("=== CALLBACK QUERY DETECTED ===", "INFO");
                    smart_admin_debug_log("Callback data: " . $callback_data, "INFO");
                    smart_admin_debug_log("Chat ID: " . $chat_id, "INFO");
                    smart_admin_debug_log("Message ID: " . $message_id, "INFO");
                    smart_admin_debug_log("Full callback query: " . print_r($callback_query, true), "INFO");
                } else {
                    file_put_contents($log_file, "=== CALLBACK QUERY DETECTED ===\n", FILE_APPEND);
                    file_put_contents($log_file, "Callback data: " . $callback_data . "\n", FILE_APPEND);
                    file_put_contents($log_file, "Chat ID: " . $chat_id . "\n", FILE_APPEND);
                    file_put_contents($log_file, "Message ID: " . $message_id . "\n", FILE_APPEND);
                    file_put_contents($log_file, "Full callback query: " . print_r($callback_query, true) . "\n", FILE_APPEND);
                }
            }

            // پاسخ به callback_query برای حذف loading
            answer_callback_query($callback_query['id']);

            if (strpos($callback_data, 'publish_post_') === 0) {
                $post_id = str_replace('publish_post_', '', $callback_data);
                $post_status = get_post_status($post_id);

                file_put_contents($log_file, "Processing publish_post for post ID: $post_id with status: $post_status\n", FILE_APPEND);
                
                // اضافه کردن debug
                debug_callback_query($callback_data, $post_id);

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
                    send_to_telegram($confirmation_message, $chat_id);
                } else {
                    send_to_telegram("پست در حالت فراز نیست و قابل انتشار نیست!", $chat_id);
                }
            }
            elseif (strpos($callback_data, 'delete_post_') === 0) {
                $post_id = str_replace('delete_post_', '', $callback_data);
                $post_status = get_post_status($post_id);
                
                file_put_contents($log_file, "Processing delete_post for post ID: $post_id with status: $post_status\n", FILE_APPEND);
                
                // اضافه کردن debug
                debug_callback_query($callback_data, $post_id);
                
                if($post_status === 'faraz'){
                    delete_post($post_id);
                    $post_title = get_the_title($post_id);
                    send_to_telegram($post_title . " با موفقیت حذف شد!", $chat_id );
                } else {
                    send_to_telegram("پست در حالت فراز نیست و قابل حذف نیست!", $chat_id);
                }
            }
            elseif (strpos($callback_data, 'edited_post_') === 0) {
                $post_id = str_replace('edited_post_', '', $callback_data);
                file_put_contents($log_file, "Processing edited_post for post ID: $post_id\n", FILE_APPEND);
                
                // اضافه کردن debug
                debug_callback_query($callback_data, $post_id);
                
                send_post_to_telegram($post_id , $chat_id);
            }
            elseif (strpos($callback_data, 'show_post_') === 0) {
                $post_id = str_replace('show_post_', '', $callback_data);
                file_put_contents($log_file, "Processing show_post for post ID: $post_id to chat: $chat_id\n", FILE_APPEND);
                
                // اضافه کردن debug
                debug_callback_query($callback_data, $post_id);
                
                send_post_to_telegram($post_id, $chat_id);
            }
            elseif (strpos($callback_data, 'choose_cat_') === 0) {
                $post_id = (int) str_replace('choose_cat_', '', $callback_data);
                $cats = get_categories(array('hide_empty' => false));
                $rows = array();
                $row = array();
                foreach ($cats as $index => $cat) {
                    // ضمیمه کردن message_id فعلی برای ویرایش پیام اصلی پس از انتخاب
                    $row[] = ['text' => $cat->name, 'callback_data' => 'set_cat_' . $post_id . '_' . $cat->term_id . '_' . $message_id];
                    if (count($row) === 2) { $rows[] = $row; $row = array(); }
                    if (count($rows) >= 6) break; // جلوگیری از دکمه‌های بیش از حد
                }
                if (!empty($row)) $rows[] = $row;
                $rows[] = [ ['text' => 'لغو', 'callback_data' => 'cancel_cat_' . $post_id] ];

                // ارسال پیام انتخاب دسته‌بندی
                $token = get_option('telegram_bot_token');
                $host_type = get_option('telegram_host_type', 'foreign');
                $text = 'لطفاً یک دسته‌بندی برای این پست انتخاب کنید:';
                if ($host_type === 'iranian') {
                    $proxy_url = get_option('telegram_proxy_url', 'https://arz.appwordpresss.ir/all.php');
                    $data = array(
                        'chatid' => $chat_id,
                        'bot' => $token,
                        'message' => $text,
                        'reply_markup' => json_encode(['inline_keyboard' => $rows]),
                        'isphoto' => 'false'
                    );
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $proxy_url);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                    curl_exec($ch);
                    curl_close($ch);
                } else {
                    $url = "https://api.telegram.org/bot{$token}/sendMessage";
                    wp_remote_post($url, array(
                        'body' => array(
                            'chat_id' => $chat_id,
                            'text' => $text,
                            'reply_markup' => json_encode(['inline_keyboard' => $rows])
                        ),
                        'timeout' => 30,
                        'sslverify' => false
                    ));
                }
            }
            elseif (strpos($callback_data, 'set_cat_') === 0) {
                // set_cat_{postId}_{termId}
                $parts = explode('_', $callback_data);
                $post_id = isset($parts[2]) ? (int)$parts[2] : 0;
                $term_id = isset($parts[3]) ? (int)$parts[3] : 0;
                $origin_message_id = isset($parts[4]) ? (int)$parts[4] : 0; // پیام پیش‌نمایش
                if ($post_id && $term_id) {
                    wp_set_post_categories($post_id, array($term_id), false);

                    // ساخت کپشن جدید با نام دسته‌بندی انتخاب‌شده
                    $post_title = get_the_title($post_id);
                    $post_excerpt = get_the_excerpt($post_id);
                    $cats = get_the_category($post_id);
                    $cat_name = 'بدون دسته‌بندی';
                    if (!empty($cats)) { $cat_name = esc_html($cats[0]->name); }
                    $new_caption = $post_title . "\n\n" . $post_excerpt . "\n\nدسته‌بندی:  " . $cat_name;

                    // بازسازی دکمه‌های مدیریتی (بدون دکمه انتخاب دسته‌بندی اضافی)
                    $keyboard = [
                        [ ['text' => '👁️ نمایش پست', 'callback_data' => 'show_post_' . $post_id] ],
                        [ ['text' => '✅ منتشر کردن پست', 'callback_data' => 'publish_post_' . $post_id] ],
                        [ ['text' => '🗑️ پاک کردن پست', 'callback_data' => 'delete_post_' . $post_id] ]
                    ];

                    // ویرایش پیام اصلی پیش‌نمایش
                    if ($origin_message_id) {
                        $token = get_option('telegram_bot_token');
                        $url = "https://api.telegram.org/bot{$token}/editMessageCaption";
                        wp_remote_post($url, array(
                            'body' => array(
                                'chat_id' => $chat_id,
                                'message_id' => $origin_message_id,
                                'caption' => $new_caption,
                                'parse_mode' => 'HTML',
                                'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
                            ),
                            'timeout' => 30,
                            'sslverify' => false
                        ));
                    }

                    // حذف پیام انتخاب دسته‌بندی
                    $token = get_option('telegram_bot_token');
                    $delete_url = "https://api.telegram.org/bot{$token}/deleteMessage";
                    wp_remote_post($delete_url, array(
                        'body' => array(
                            'chat_id' => $chat_id,
                            'message_id' => $message_id
                        ),
                        'timeout' => 30,
                        'sslverify' => false
                    ));

                    // پیام تایید کوتاه
                    send_to_telegram('✅ دسته‌بندی انتخاب شد.', $chat_id);
                }
            }
            
            file_put_contents($log_file, "=== END CALLBACK QUERY ===\n", FILE_APPEND);
        } else {
            file_put_contents($log_file, "No message or callback_query found in update\n", FILE_APPEND);
            file_put_contents($log_file, "Available keys in update: " . implode(', ', array_keys($update)) . "\n", FILE_APPEND);
        }
		} else {
			file_put_contents($log_file, "Request method is not POST\n", FILE_APPEND);
		}
    
    // اگر callback_query در update نبود، بررسی کنیم که آیا در جای دیگری است
    if (isset($_POST['callback_query'])) {
        file_put_contents($log_file, "=== CALLBACK QUERY IN POST ===\n", FILE_APPEND);
        file_put_contents($log_file, "POST callback_query: " . print_r($_POST['callback_query'], true) . "\n", FILE_APPEND);
    }
    
    file_put_contents($log_file, "=== END REQUEST ===\n\n", FILE_APPEND);
		return array('ok' => true);
}

// تابع دریافت update ها از تلگرام با استفاده از getUpdates (Long Polling)
function telegram_poll_updates($timeout = 2) {
    $token = get_option('telegram_bot_token');
    $host_type = get_option('telegram_host_type', 'foreign');
    
    if (empty($token)) {
        return false;
    }
    
    // دریافت آخرین update_id که پردازش شده
    $offset = (int) get_option('telegram_last_update_id', 0);
    
    // فقط update های message و callback_query را دریافت می‌کنیم (برای بهینه‌سازی)
    $allowed_updates = urlencode(json_encode(['message', 'callback_query']));
    
    // timeout را به صورت پارامتر می‌گیریم (پیش‌فرض 2 ثانیه برای long polling)
    $url = "https://api.telegram.org/bot{$token}/getUpdates?timeout={$timeout}&offset={$offset}&allowed_updates={$allowed_updates}";
    
    if ($host_type === 'iranian') {
        // برای هاست ایرانی، از پروکسی استفاده می‌کنیم
        // اما getUpdates معمولاً از پروکسی پشتیبانی نمی‌شود، پس باید مستقیماً تلاش کنیم
        // یا از یک پروکسی HTTP استفاده کنیم
        $proxy_url = get_option('telegram_proxy_url', '');
        
        // اگر پروکسی برای getUpdates پشتیبانی می‌شود، از آن استفاده می‌کنیم
        // در غیر این صورت، مستقیماً تلاش می‌کنیم (ممکن است در هاست ایرانی کار نکند)
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // timeout باید کمی بیشتر از timeout درخواست باشد (timeout + 1 ثانیه برای overhead)
        curl_setopt($ch, CURLOPT_TIMEOUT, (int)$timeout + 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        // اگر پروکسی HTTP تنظیم شده باشد، از آن استفاده می‌کنیم
        if (!empty($proxy_url) && strpos($proxy_url, 'http') === 0) {
            // در اینجا می‌توانیم از پروکسی استفاده کنیم اگر پشتیبانی شود
            // اما برای سادگی، مستقیماً تلاش می‌کنیم
        }
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code !== 200 || empty($response)) {
            return false;
        }
    } else {
        // برای هاست خارجی، مستقیماً به API تلگرام متصل می‌شویم
        // timeout باید کمی بیشتر از timeout درخواست باشد (timeout + 1 ثانیه برای overhead)
        $response = wp_remote_get($url, array(
            'timeout' => (int)$timeout + 1,
            'sslverify' => false
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $response = wp_remote_retrieve_body($response);
    }
    
    $data = json_decode($response, true);
    
    if (!isset($data['ok']) || !$data['ok']) {
        return false;
    }
    
    return isset($data['result']) ? $data['result'] : array();
}

// تابع پردازش update های دریافتی
function process_telegram_updates($updates) {
    if (empty($updates) || !is_array($updates)) {
        return;
    }
    
    $log_file = plugin_dir_path(__FILE__) . 'telegram_logs.txt';
    $last_update_id = 0;
    
    foreach ($updates as $update) {
        if (!isset($update['update_id'])) {
            continue;
        }
        
        $update_id = (int) $update['update_id'];
        $last_update_id = max($last_update_id, $update_id);
        
        // فقط update هایی که message یا callback_query دارند را پردازش می‌کنیم
        // update های دیگر (مثل my_chat_member) را نادیده می‌گیریم اما update_id را ذخیره می‌کنیم
        if (isset($update['message']) || isset($update['callback_query'])) {
            handle_telegram_update_direct($update);
        } else {
            // لاگ برای update های پردازش نشده (اختیاری)
            if (function_exists('smart_admin_get_setting') && smart_admin_get_setting('debug_mode')) {
                file_put_contents($log_file, "Skipping update " . $update_id . " (no message or callback_query)\n", FILE_APPEND);
            }
        }
    }
    
    // ذخیره آخرین update_id (حتی برای update های پردازش نشده)
    if ($last_update_id > 0) {
        update_option('telegram_last_update_id', $last_update_id + 1);
        
        // لاگ برای ردیابی
        if (function_exists('smart_admin_get_setting') && smart_admin_get_setting('debug_mode')) {
            file_put_contents($log_file, "Updated last_update_id to: " . ($last_update_id + 1) . "\n", FILE_APPEND);
        }
    }
}

// تابع پردازش مستقیم یک update (بدون نیاز به HTTP request)
function handle_telegram_update_direct($update) {
    $log_file = plugin_dir_path(__FILE__) . 'telegram_logs.txt';
    
    // استفاده از همان منطق handle_request اما برای update مستقیم
    if (isset($update['message'])) {
        $message_text = isset($update['message']['text']) ? $update['message']['text'] : '';
        $token = get_option('telegram_bot_token');
        $url_p = get_option('telegram_bot_url');
        $admin_login = get_option('admin_login_p');
        $chat_id = get_option('telegram_bot_Chat_id');
        $current_chat_id = isset($update['message']['chat']['id']) ? $update['message']['chat']['id'] : $chat_id;
        $sender_user_id = isset($update['message']['from']['id']) ? (string)$update['message']['from']['id'] : '';
        $allowed_admins_option = (string) get_option('faraz_telegram_admin_ids', '');
        $allowed_admins = array_filter(array_map('trim', preg_split('/[\s,،]+/', faraz_normalize_digits($allowed_admins_option))));
        
        if (function_exists('smart_admin_get_setting') && smart_admin_get_setting('debug_mode')) {
            if (function_exists('smart_admin_debug_log')) {
                smart_admin_debug_log("Processing message (polling): $message_text", "INFO");
                smart_admin_debug_log("Sender: $sender_user_id | Admins: " . implode(',', $allowed_admins), "INFO");
            } else {
                file_put_contents($log_file, "Processing message (polling): $message_text\n", FILE_APPEND);
                file_put_contents($log_file, "Sender: $sender_user_id | Admins: " . implode(',', $allowed_admins) . "\n", FILE_APPEND);
            }
        }
        
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
            update_option('chat_id', get_option('telegram_bot_Chat_id'));
            update_option('admin_login_p', false);
            $starter_conuter = starter_conuter();
            send_to_telegram($response_message, $current_chat_id);
        }
        elseif (strpos($message_text, '/ping') === 0) { 
            send_to_telegram("hello", $current_chat_id);
        }
        elseif (strpos($message_text, '/send_drafts') === 0) {
            send_to_telegram("پست ها در حال ارسال هستند..."); 
            send_all_draft_posts($chat_id);
        }
        elseif(strpos($message_text, '/publish_all_drafts') === 0) {
            // کد منتشر کردن همه پست‌ها
        }
        elseif (!empty($update['message']['photo']) || ((!empty($message_text)) && (strpos(str_replace('؛',';',$message_text), ';') !== false))) {
            $is_admin = faraz_is_telegram_admin($sender_user_id);
            if (!$is_admin) {
                send_to_telegram('⛔ شما ادمین نیستید و امکان ارسال پست را ندارید.', $current_chat_id);
            } else {
                $post_title = '';
                $post_content = '';
                $featured_image_url = '';

                if (!empty($update['message']['photo'])) {
                    $caption_text = isset($update['message']['caption']) ? $update['message']['caption'] : '';
                    list($post_title, $post_content) = faraz_parse_title_and_content_from_text($caption_text);
                    $photos = $update['message']['photo'];
                    $largest = end($photos);
                    $file_id = $largest['file_id'];
                    $featured_image_url = faraz_download_telegram_file_to_wp_media($file_id);
                } else {
                    list($post_title, $post_content) = faraz_parse_title_and_content_from_text($message_text);
                }

                if (empty($post_title)) {
                    $post_title = wp_trim_words(wp_strip_all_tags($post_content), 12, '');
                    if (empty($post_title)) { $post_title = 'بدون عنوان'; }
                }

                $post_id = wp_insert_post([
                    'post_title'   => $post_title,
                    'post_content' => $post_content,
                    'post_status'  => 'faraz',
                    'post_type'    => 'post',
                    'post_author'  => 1,
                ]);

                if (!is_wp_error($post_id)) {
                    if (!empty($featured_image_url)) {
                        $attached = faraz_attach_external_image_as_featured($post_id, $featured_image_url);
                        if ($attached) {
                            update_post_meta($post_id, '_faraz_featured_source_url', esc_url_raw($featured_image_url));
                        }
                    }
                    send_post_to_telegram($post_id, $current_chat_id);
                    send_to_telegram("اگر تصویر یا فایل دیگری برای اضافه‌کردن داری ارسال کن (اختیاری). برای رد کردن، چیزی نفرست.", $current_chat_id);
                } else {
                    send_to_telegram('خطا در ایجاد پست: ' . $post_id->get_error_message(), $current_chat_id);
                }
            }
        }
        elseif (strpos($message_text, '/id') === 0 || strpos($message_text, '/whoami') === 0) {
            $username = isset($update['message']['from']['username']) ? '@' . $update['message']['from']['username'] : '—';
            $first = isset($update['message']['from']['first_name']) ? $update['message']['from']['first_name'] : '';
            $last  = isset($update['message']['from']['last_name']) ? $update['message']['from']['last_name'] : '';
            $is_admin = faraz_is_telegram_admin($sender_user_id) ? 'بله' : 'خیر';
            $info = "👤 اطلاعات شما\nID: {$sender_user_id}\nUsername: {$username}\nName: {$first} {$last}\nادمین: {$is_admin}";
            send_to_telegram($info, $current_chat_id);
        }
    }
    elseif (isset($update['callback_query'])) {
        $callback_query = $update['callback_query'];
        $callback_data = $callback_query['data'];
        $chat_id = isset($callback_query['message']['chat']['id']) ? $callback_query['message']['chat']['id'] : $callback_query['from']['id'];
        $message_id = $callback_query['message']['message_id'];

        answer_callback_query($callback_query['id']);

        if (strpos($callback_data, 'publish_post_') === 0) {
            $post_id = str_replace('publish_post_', '', $callback_data);
            $post_status = get_post_status($post_id);

            if($post_status === 'faraz'){
                publish_draft_post($post_id);
                $post_title = get_the_title($post_id);
                
                $post_thumbnail_url = get_the_post_thumbnail_url($post_id, 'full');
                if ($post_thumbnail_url) {
                    $post_excerpt = get_the_excerpt($post_id);
                    $post_link = get_permalink($post_id);
                    $cats = get_the_category($post_id);
                    $cat = !empty($cats) ? esc_html($cats[0]->name) : 'بدون دسته‌بندی';
                    $message = "$post_title \n\n$post_excerpt \n\nدسته‌بندی:  $cat \n\nآدرس پست در سایت: $post_link";
                    
                    $public_channel_id = get_option('farazautur_public_channel_id', '');
                    if (!empty($public_channel_id)) {
                        send_telegram_photo_with_caption($post_thumbnail_url, $message, $post_id, true, $public_channel_id);
                    }
                }
                
                $confirmation_message = $post_title . " با موفقیت منتشر شد!";
                send_to_telegram($confirmation_message, $chat_id);
            } else {
                send_to_telegram("پست در حالت فراز نیست و قابل انتشار نیست!", $chat_id);
            }
        }
        elseif (strpos($callback_data, 'delete_post_') === 0) {
            $post_id = str_replace('delete_post_', '', $callback_data);
            $post_status = get_post_status($post_id);
            
            if($post_status === 'faraz'){
                delete_post($post_id);
                $post_title = get_the_title($post_id);
                send_to_telegram($post_title . " با موفقیت حذف شد!", $chat_id);
            } else {
                send_to_telegram("پست در حالت فراز نیست و قابل حذف نیست!", $chat_id);
            }
        }
        elseif (strpos($callback_data, 'edited_post_') === 0) {
            $post_id = str_replace('edited_post_', '', $callback_data);
            send_post_to_telegram($post_id, $chat_id);
        }
        elseif (strpos($callback_data, 'show_post_') === 0) {
            $post_id = str_replace('show_post_', '', $callback_data);
            send_post_to_telegram($post_id, $chat_id);
        }
        // سایر callback_query ها مشابه handle_request
    }
}

// تابع جدید برای پاسخ به callback_query
function answer_callback_query($callback_query_id) {
    $token = get_option('telegram_bot_token');
    $host_type = get_option('telegram_host_type', 'foreign');
    
    if ($host_type === 'iranian') {
        // برای هاست ایرانی از پروکسی استفاده می‌کنیم
        $proxy_url = get_option('telegram_proxy_url', 'https://arz.appwordpresss.ir/all.php');
        
        $data = array(
            'callback_query_id' => $callback_query_id,
            'bot' => $token
        );
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $proxy_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        
        $response = curl_exec($ch);
        curl_close($ch);
        
    } else {
        // برای هاست خارجی مستقیماً به API تلگرام متصل می‌شویم
        $url = "https://api.telegram.org/bot{$token}/answerCallbackQuery";
        
        $data = array(
            'callback_query_id' => $callback_query_id
        );
        
        $response = wp_remote_post($url, array(
            'body' => $data,
            'timeout' => 30,
            'sslverify' => false
        ));
    }
}

// تابع debug برای بررسی وضعیت callback_query
function debug_callback_query($callback_data, $post_id = null) {
    $log_file = plugin_dir_path(__FILE__) . 'telegram_logs.txt';
    $debug_info = array(
        'callback_data' => $callback_data,
        'post_id' => $post_id,
        'post_status' => $post_id ? get_post_status($post_id) : 'N/A',
        'post_title' => $post_id ? get_the_title($post_id) : 'N/A',
        'time' => date('Y-m-d H:i:s')
    );
    
    file_put_contents($log_file, "DEBUG INFO: " . json_encode($debug_info, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
    
    // ارسال پیام debug به تلگرام
    $debug_message = "🔍 Debug Info:\n" .
                    "Callback: $callback_data\n" .
                    "Post ID: " . ($post_id ?: 'N/A') . "\n" .
                    "Status: " . ($post_id ? get_post_status($post_id) : 'N/A') . "\n" .
                    "Title: " . ($post_id ? get_the_title($post_id) : 'N/A') . "\n" .
                    "Time: " . date('Y-m-d H:i:s');
    
    send_to_telegram($debug_message);
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

function send_to_telegram($message, $override_chat_id = null)
{
    $token = get_option('telegram_bot_token');
    $host_type = get_option('telegram_host_type', 'foreign');
    // اگر چت آی‌دی خاصی ارسال شود، همان استفاده می‌شود؛ در غیر اینصورت مقدار تنظیمات افزونه
    $chat_id = $override_chat_id ?: get_option('telegram_bot_Chat_id');
    
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
    file_put_contents($log_file, "Telegram send response (host_type: {$host_type}, chat_id: {$chat_id}): " . $response . "\n", FILE_APPEND);
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
        }
    }
 
    $post_title = get_the_title($post_id);
    $post_excerpt = get_the_excerpt($post_id);
    $status = get_post_status($post_id);
    $post_link = ($status === 'publish') ? get_permalink($post_id) : get_preview_post_link($post_id);

    $cats = get_the_category($post_id);
    $cat = !empty($cats) ? esc_html($cats[0]->name) : 'بدون دسته‌بندی';
 
    $message = "$post_title \n\n$post_excerpt \n\nدسته‌بندی:  $cat";

    // اگر تصویر شاخص نداریم، پیام متنی با دکمه‌ها بفرستیم
    if (!empty($post_thumbnail_url)) {
    send_telegram_photo_with_caption($post_thumbnail_url, $message, $post_id, 'edit', $chat_id);
    } else {
        send_telegram_text_with_buttons($message, $post_id, 'edit', $chat_id);
    }
    $preview_link = ($status === 'publish') ? get_permalink($post_id) : get_preview_post_link($post_id);
    // کوتاه‌کننده وردپرس
    $short = wp_get_shortlink($post_id);
    if (!empty($short)) $preview_link = $short;
    if ($preview_link) {
        send_to_telegram('پیش‌نمایش/نمایش روی سایت: ' . $preview_link, $chat_id);
    }
    $edit_link = get_edit_post_link($post_id, '');
    if ($edit_link) {
        // برای لینک ویرایش کوتاه‌لینک نداریم، همان لینک پیشخوان کافی است
        send_to_telegram('ویرایش در پیشخوان: ' . $edit_link, $chat_id);
    }
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
            
        case 'test_callback':
            $result = test_callback_query($token, $host_type);
            break;
            
        case 'full_test':
            $result = full_callback_test($token, $host_type);
            break;
            
        case 'test_url':
            $result = test_webhook_url($token, $host_type);
            break;
            
        case 'fix_webhook':
            $result = fix_webhook_url($token, $host_type);
            break;
            
        case 'manual_webhook':
            $result = manual_set_webhook($token, $host_type);
            break;
            
        case 'test_proxy':
            $result = test_proxy_connection($token, $host_type);
            break;
            
        case 'switch_to_foreign':
            $result = switch_to_foreign_host($token);
            break;
            
        case 'test_polling':
            $result = test_telegram_polling($token, $host_type);
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

function test_webhook_url($token, $host_type) {
    $url_p = get_option('telegram_bot_url');
    if (empty($url_p)) {
        return 'آدرس وب‌هوک تنظیم نشده است.';
    }

    $destination_type = '';
    if (strpos($url_p, 'faraz/v1/handle/') !== false) {
        $destination_type = 'آدرس endpoint فراز';
    } elseif (strpos($url_p, 'faraz/v1/test/') !== false) {
        $destination_type = 'آدرس endpoint تست';
    } else {
        $destination_type = 'آدرس دلخواه';
    }

    $message = "🌐 تست آدرس وب‌هوک\n\n" .
               "⏰ زمان: " . current_time('Y-m-d H:i:s') . "\n" .
               "🌐 نوع هاست: " . ($host_type === 'iranian' ? 'ایرانی (پروکسی)' : 'خارجی (مستقیم)') . "\n" .
               "📝 آدرس مقصد: " . $url_p . "\n" .
               "🔗 ارتباط برقرار است!";

    if ($host_type === 'iranian') {
        $proxy_url = get_option('telegram_proxy_url', 'https://arz.appwordpresss.ir/all.php');
        
        // برای تست webhook، از روش صحیح استفاده می‌کنیم
        $webhook_proxy = get_option('telegram_webhook_proxy', '');
        
        if (!empty($webhook_proxy)) {
            // استفاده از میانجی tibin.php
            $webhook_url = $webhook_proxy . '?bot=' . $token . '&url=' . urlencode($url_p) . '&setWebP=True';
        } else {
            // fallback به روش قدیمی
            $webhook_url = $proxy_url . '?bot=' . $token . '&url=' . urlencode($url_p) . '&setWebP=True';
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $webhook_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code == 200) {
            return 'آدرس وب‌هوک با موفقیت از طریق پروکسی تنظیم شد!' .
                   "\n\n📊 اطلاعات ارسال:" .
                   "\n• مقصد: " . $destination_type . " (" . $url_p . ")" .
                   "\n• روش: ارسال از طریق پروکسی" .
                   "\n• زمان: " . current_time('Y-m-d H:i:s') .
                   "\n• پاسخ: " . $response;
        } else {
            return 'خطا در تنظیم آدرس وب‌هوک: HTTP ' . $http_code . ' - ' . $response;
        }
    } else {
        $url = "https://api.telegram.org/bot{$token}/setWebhook?url=" . urlencode($url_p);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);
        if (isset($result['ok']) && $result['ok']) {
            return 'آدرس وب‌هوک با موفقیت به صورت مستقیم تنظیم شد!' .
                   "\n\n📊 اطلاعات ارسال:" .
                   "\n• مقصد: " . $destination_type . " (" . $url_p . ")" .
                   "\n• روش: اتصال مستقیم به API تلگرام" .
                   "\n• زمان: " . current_time('Y-m-d H:i:s') .
                   "\n• پاسخ: " . $response;
        } else {
            return 'خطا در تنظیم آدرس وب‌هوک: ' . ($result['description'] ?? 'نامشخص') . ' - ' . $response;
        }
    }
}

function test_callback_query($token, $host_type) {
    $chat_id = get_option('telegram_bot_Chat_id');
    
    if (empty($chat_id)) {
        return 'شناسه چت تنظیم نشده است.';
    }
    
    $message = "🧪 تست Callback Query\n\n" .
               "⏰ زمان: " . current_time('Y-m-d H:i:s') . "\n" .
               "🔧 این پیام برای تست callback_query ارسال شده است\n" .
               "📝 دکمه‌های زیر باید کار کنند";
    
    if ($host_type === 'iranian') {
        $proxy_url = get_option('telegram_proxy_url', 'https://arz.appwordpresss.ir/all.php');
        
        $inline_keyboard = [
            [
                ['text' => '✅ تست منتشر کردن', 'callback_data' => 'publish_post_123'],
                ['text' => '🗑️ تست پاک کردن', 'callback_data' => 'delete_post_123'],
                ['text' => '👁️ تست نمایش پست', 'callback_data' => 'show_post_123']
            ]
        ];
        
        $data = array(
            'chatid' => $chat_id,
            'bot' => $token,
            'message' => $message,
            'reply_markup' => json_encode(['inline_keyboard' => $inline_keyboard]),
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
            return 'پیام تست callback_query با موفقیت از طریق پروکسی ارسال شد!' .
                   "\n\n📊 اطلاعات ارسال:" .
                   "\n• مقصد: " . $chat_id .
                   "\n• روش: ارسال از طریق پروکسی" .
                   "\n• زمان: " . current_time('Y-m-d H:i:s') .
                   "\n\n🔧 حالا دکمه‌های callback_query را تست کنید!";
        } else {
            return 'خطا در ارسال پیام: ' . $response;
        }
        
    } else {
        $url = "https://api.telegram.org/bot{$token}/sendMessage";
        
        $inline_keyboard = [
            [
                ['text' => '✅ تست منتشر کردن', 'callback_data' => 'publish_post_123'],
                ['text' => '🗑️ تست پاک کردن', 'callback_data' => 'delete_post_123'],
                ['text' => '👁️ تست نمایش پست', 'callback_data' => 'show_post_123']
            ]
        ];
        
        $data = array(
            'chat_id' => $chat_id,
            'text' => $message,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(['inline_keyboard' => $inline_keyboard])
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
            return 'پیام تست callback_query با موفقیت به صورت مستقیم ارسال شد!' . 
                   "\n\n📊 اطلاعات ارسال:" .
                   "\n• مقصد: " . $chat_id .
                   "\n• روش: اتصال مستقیم به API تلگرام" .
                   "\n• زمان: " . current_time('Y-m-d H:i:s') .
                   "\n\n🔧 حالا دکمه‌های callback_query را تست کنید!";
        } else {
            return 'خطا در ارسال پیام: ' . ($result['description'] ?? 'نامشخص');
        }
    }
}

function fix_webhook_url($token, $host_type) {
    // تنظیم مجدد webhook URL
    $current_url = get_option('telegram_bot_url');
    $site_url = home_url('/wp-json/faraz/v1/handle/');
    
    // اگر URL خالی است یا اشتباه است، آن را اصلاح کنیم
    if (empty($current_url) || strpos($current_url, 'faraz/v1/handle/') === false) {
        update_option('telegram_bot_url', $site_url);
        $current_url = $site_url;
    }
    
    if ($host_type === 'iranian') {
        $proxy_url = get_option('telegram_proxy_url', 'https://arz.appwordpresss.ir/all.php');
        
        // برای پروکسی ایرانی، از روش صحیح استفاده می‌کنیم
        $webhook_proxy = get_option('telegram_webhook_proxy', '');
        
        if (!empty($webhook_proxy)) {
            // استفاده از میانجی tibin.php
            $webhook_url = $webhook_proxy . '?bot=' . $token . '&url=' . urlencode($current_url) . '&setWebP=True';
        } else {
            // fallback به روش قدیمی
            $webhook_url = $proxy_url . '?bot=' . $token . '&url=' . urlencode($current_url) . '&setWebP=True';
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $webhook_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code == 200) {
            return '✅ Webhook URL اصلاح و تنظیم شد!' .
                   "\n\n📊 اطلاعات:" .
                   "\n• آدرس جدید: " . $current_url .
                   "\n• روش: پروکسی ایرانی" .
                   "\n• زمان: " . current_time('Y-m-d H:i:s') .
                   "\n• پاسخ: " . $response;
        } else {
            return '❌ خطا در تنظیم webhook: HTTP ' . $http_code . ' - ' . $response;
        }
    } else {
        $url = "https://api.telegram.org/bot{$token}/setWebhook?url=" . urlencode($current_url);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $result = json_decode($response, true);
        if (isset($result['ok']) && $result['ok']) {
            return '✅ Webhook URL اصلاح و تنظیم شد!' .
                   "\n\n📊 اطلاعات:" .
                   "\n• آدرس جدید: " . $current_url .
                   "\n• روش: اتصال مستقیم" .
                   "\n• زمان: " . current_time('Y-m-d H:i:s') .
                   "\n• پاسخ: " . $response;
        } else {
            return '❌ خطا در تنظیم webhook: ' . ($result['description'] ?? 'نامشخص') . ' - ' . $response;
        }
    }
}

function manual_set_webhook($token, $host_type) {
    $url_p = get_option('telegram_bot_url');
    if (empty($url_p)) {
        return 'آدرس وب‌هوک تنظیم نشده است. لطفاً ابتدا آدرس را تنظیم کنید.';
    }

    $destination_type = '';
    if (strpos($url_p, 'faraz/v1/handle/') !== false) {
        $destination_type = 'آدرس endpoint فراز';
    } elseif (strpos($url_p, 'faraz/v1/test/') !== false) {
        $destination_type = 'آدرس endpoint تست';
    } else {
        $destination_type = 'آدرس دلخواه';
    }

    $message = "🔧 تنظیم وب‌هوک دستی\n\n" .
               "⏰ زمان: " . current_time('Y-m-d H:i:s') . "\n" .
               "🌐 نوع هاست: " . ($host_type === 'iranian' ? 'ایرانی (پروکسی)' : 'خارجی (مستقیم)') . "\n" .
               "📝 آدرس مقصد: " . $url_p . "\n" .
               "🔗 ارتباط برقرار است!";

    if ($host_type === 'iranian') {
        $proxy_url = get_option('telegram_proxy_url', 'https://arz.appwordpresss.ir/all.php');
        
        // برای پروکسی ایرانی، از روش صحیح استفاده می‌کنیم
        $webhook_proxy = get_option('telegram_webhook_proxy', 'https://arz.appwordpresss.ir/tibin.php');
        
        if (!empty($webhook_proxy)) {
            // استفاده از میانجی tibin.php
            $webhook_url = $webhook_proxy . '?bot=' . $token . '&url=' . urlencode($url_p) . '&setWebP=True';
        } else {
            // fallback به روش قدیمی
            $webhook_url = $proxy_url . '?bot=' . $token . '&url=' . urlencode($url_p) . '&setWebP=True';
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $webhook_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return '❌ خطا در تنظیم webhook: ' . $error;
        }

        if ($http_code == 200) {
            return '✅ Webhook URL با موفقیت تنظیم شد!' .
                   "\n\n📊 اطلاعات:" .
                   "\n• آدرس مقصد: " . $url_p .
                   "\n• روش: پروکسی ایرانی" .
                   "\n• زمان: " . current_time('Y-m-d H:i:s') .
                   "\n• پاسخ: " . $response;
        } else {
            return '❌ خطا در تنظیم webhook: HTTP ' . $http_code . ' - ' . $response;
        }
    } else {
        $url = "https://api.telegram.org/bot{$token}/setWebhook?url=" . urlencode($url_p);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);
        if (isset($result['ok']) && $result['ok']) {
            return '✅ Webhook URL با موفقیت تنظیم شد!' .
                   "\n\n📊 اطلاعات:" .
                   "\n• آدرس مقصد: " . $url_p .
                   "\n• روش: اتصال مستقیم" .
                   "\n• زمان: " . current_time('Y-m-d H:i:s') .
                   "\n• پاسخ: " . $response;
        } else {
            return '❌ خطا در تنظیم webhook: ' . ($result['description'] ?? 'نامشخص') . ' - ' . $response;
        }
    }
}

function test_proxy_connection($token, $host_type) {
    $proxy_url = get_option('telegram_proxy_url', 'https://arz.appwordpresss.ir/all.php');
    $webhook_proxy = get_option('telegram_webhook_proxy', 'https://arz.appwordpresss.ir/tibin.php');
    
    $message = "🧪 تست ارتباط با پروکسی\n\n" .
               "⏰ زمان: " . current_time('Y-m-d H:i:s') . "\n" .
               "🌐 پروکسی ارسال: " . $proxy_url . "\n" .
               "🔗 میانجی وب‌هوک: " . $webhook_proxy . "\n" .
               "🔧 تست ارتباط...";
    
    // تست پروکسی ارسال
    $test_data = array(
        'chatid' => get_option('telegram_bot_Chat_id'),
        'bot' => $token,
        'message' => $message,
        'isphoto' => 'false'
    );
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $proxy_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_TIMEOUT, 60); // افزایش timeout
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30); // timeout اتصال
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return '❌ خطا در ارتباط با پروکسی ارسال: ' . $error . 
               "\n\n🔧 پیشنهادات:" .
               "\n• بررسی اتصال اینترنت" .
               "\n• بررسی آدرس پروکسی" .
               "\n• استفاده از VPN" .
               "\n• تغییر به هاست خارجی";
    }
    
    if ($http_code == 200) {
        $result = json_decode($response, true);
        if (isset($result['status']) && $result['status'] === 'success') {
            return '✅ ارتباط با پروکسی ارسال برقرار شد!' .
                   "\n\n📊 اطلاعات:" .
                   "\n• پروکسی: " . $proxy_url .
                   "\n• پاسخ: " . $response .
                   "\n• زمان: " . current_time('Y-m-d H:i:s');
        } else {
            return '⚠️ پروکسی پاسخ داد اما خطا: ' . $response;
        }
    } else {
        return '❌ خطا در ارتباط با پروکسی: HTTP ' . $http_code . ' - ' . $response;
    }
}

function switch_to_foreign_host($token) {
    // تنظیم مجدد آدرس وب‌هوک به هاست خارجی
    $url_p = get_option('telegram_bot_url');
    $host_type = 'foreign';
    
    // تنظیم وب‌هوک به هاست خارجی
    $webhook_result = telegram_bot_set_webhook($token, $url_p, $host_type);
    
    if ($webhook_result) {
        return '✅ آدرس وب‌هوک با موفقیت به هاست خارجی تنظیم شد!';
    } else {
        return '❌ خطا در تنظیم وب‌هوک به هاست خارجی!';
    }
}

function full_callback_test($token, $host_type) {
    $chat_id = get_option('telegram_bot_Chat_id');
    
    if (empty($chat_id)) {
        return 'شناسه چت تنظیم نشده است.';
    }
    
    // مرحله 1: تنظیم webhook
    $url_p = get_option('telegram_bot_url');
    $webhook_result = telegram_bot_set_webhook($token, $url_p, $host_type);
    
    if (!$webhook_result) {
        return '❌ خطا در تنظیم webhook!';
    }
    
    // مرحله 2: ارسال پیام با callback_query
    $message = "🧪 تست کامل Callback Query\n\n" .
               "⏰ زمان: " . current_time('Y-m-d H:i:s') . "\n" .
               "🌐 نوع هاست: " . ($host_type === 'iranian' ? 'ایرانی (پروکسی)' : 'خارجی (مستقیم)') . "\n" .
               "📝 این پیام برای تست کامل callback_query ارسال شده است\n" .
               "🔧 دکمه‌های زیر باید کار کنند";
    
    if ($host_type === 'iranian') {
        $proxy_url = get_option('telegram_proxy_url', 'https://arz.appwordpresss.ir/all.php');
        
        $inline_keyboard = [
            [
                ['text' => '✅ تست منتشر کردن', 'callback_data' => 'publish_post_123'],
                ['text' => '🗑️ تست پاک کردن', 'callback_data' => 'delete_post_123'],
                ['text' => '👁️ تست نمایش پست', 'callback_data' => 'show_post_123']
            ]
        ];
        
        $data = array(
            'chatid' => $chat_id,
            'bot' => $token,
            'message' => $message,
            'reply_markup' => json_encode(['inline_keyboard' => $inline_keyboard]),
            'isphoto' => 'false'
        );
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $proxy_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            return 'خطا در ارسال از طریق پروکسی: ' . curl_error($ch);
        }
        
        curl_close($ch);
        
        $result = json_decode($response, true);
        if (isset($result['status']) && $result['status'] === 'success') {
            return '✅ تست کامل با موفقیت انجام شد!' .
                   "\n\n📊 اطلاعات:" .
                   "\n• Webhook: تنظیم شد" .
                   "\n• پیام: ارسال شد" .
                   "\n• دکمه‌ها: اضافه شد" .
                   "\n• زمان: " . current_time('Y-m-d H:i:s') .
                   "\n\n🔧 حالا دکمه‌های callback_query را در تلگرام کلیک کنید!";
        } else {
            return 'خطا در ارسال پیام: ' . $response;
        }
        
    } else {
        $url = "https://api.telegram.org/bot{$token}/sendMessage";
        
        $inline_keyboard = [
            [
                ['text' => '✅ تست منتشر کردن', 'callback_data' => 'publish_post_123'],
                ['text' => '🗑️ تست پاک کردن', 'callback_data' => 'delete_post_123'],
                ['text' => '👁️ تست نمایش پست', 'callback_data' => 'show_post_123']
            ]
        ];
        
        $data = array(
            'chat_id' => $chat_id,
            'text' => $message,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(['inline_keyboard' => $inline_keyboard])
        );
        
        $response = wp_remote_post($url, array(
            'body' => $data,
            'timeout' => 60,
            'sslverify' => false
        ));
        
        if (is_wp_error($response)) {
            return 'خطا در اتصال مستقیم: ' . $response->get_error_message();
        }
        
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        
        if (isset($result['ok']) && $result['ok']) {
            return '✅ تست کامل با موفقیت انجام شد!' . 
                   "\n\n📊 اطلاعات:" .
                   "\n• Webhook: تنظیم شد" .
                   "\n• پیام: ارسال شد" .
                   "\n• دکمه‌ها: اضافه شد" .
                   "\n• زمان: " . current_time('Y-m-d H:i:s') .
                   "\n\n🔧 حالا دکمه‌های callback_query را در تلگرام کلیک کنید!";
        } else {
            return 'خطا در ارسال پیام: ' . ($result['description'] ?? 'نامشخص');
        }
    }
}

// تابع تست Long Polling
function test_telegram_polling($token, $host_type) {
    if (empty($token)) {
        return 'توکن ربات خالی است.';
    }
    
    $last_update_id_before = get_option('telegram_last_update_id', 0);
    
    // دریافت update ها با timeout کوتاه‌تر برای تست (1 ثانیه - حداقل مجاز)
    $updates = telegram_poll_updates(1);
    
    if ($updates === false) {
        return '❌ خطا در دریافت update ها از تلگرام!' .
               "\n\n🔧 بررسی کنید:" .
               "\n• توکن ربات صحیح است" .
               "\n• اتصال به اینترنت برقرار است" .
               "\n• برای هاست ایرانی، ممکن است نیاز به VPN باشد";
    }
    
    $update_count = is_array($updates) ? count($updates) : 0;
    
    $result_message = '✅ تست Long Polling با موفقیت انجام شد!' .
                     "\n\n📊 اطلاعات:" .
                     "\n• تعداد update های دریافت شده: " . $update_count .
                     "\n• آخرین update_id قبل از پردازش: " . $last_update_id_before .
                     "\n• زمان: " . current_time('Y-m-d H:i:s') .
                     "\n• نوع هاست: " . ($host_type === 'iranian' ? 'ایرانی (پروکسی)' : 'خارجی (مستقیم)');
    
    if ($update_count > 0) {
        $result_message .= "\n\n📝 Update های دریافت شده:" .
                          "\n" . json_encode($updates, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        // پردازش update ها
        process_telegram_updates($updates);
        
        // خواندن last_update_id بعد از پردازش
        $last_update_id_after = get_option('telegram_last_update_id', 0);
        
        $result_message .= "\n\n✅ Update ها پردازش شدند." .
                          "\n• آخرین update_id بعد از پردازش: " . $last_update_id_after;
    } else {
        $result_message .= "\n\n💡 هیچ update جدیدی دریافت نشد. این طبیعی است اگر پیامی ارسال نکرده‌اید.";
    }
    
    return $result_message;
}