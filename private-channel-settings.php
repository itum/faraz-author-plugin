<?php
function farazautur_private_channel_settings_page() {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_private_channel'])) {
        // Save settings
        update_option('farazautur_private_channel_id', sanitize_text_field($_POST['private_channel_id']));
        update_option('farazautur_auto_post_enabled', isset($_POST['auto_post_enabled']) ? '1' : '0');
        update_option('telegram_hook_url', esc_url_raw($_POST['telegram_hook_url']));
        update_option('telegram_bot_token', sanitize_text_field($_POST['telegram_bot_token']));
        
        echo '<div class="notice notice-success is-dismissible"><p>تنظیمات با موفقیت ذخیره شد.</p></div>';
    }

    // Get current settings
    $channel_id = get_option('farazautur_private_channel_id', '');
    $auto_post_enabled = get_option('farazautur_auto_post_enabled', '0');
    $hook_url = get_option('telegram_hook_url', '');
    $bot_token = get_option('telegram_bot_token', '');
    ?>
    <div class="wrap">
        <h1>تنظیمات کانال خصوصی</h1>
        <form method="post" action="">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="private_channel_id">شناسه عددی کانال</label>
                    </th>
                    <td>
                        <input type="text" id="private_channel_id" name="private_channel_id" 
                               value="<?php echo esc_attr($channel_id); ?>" class="regular-text"
                               placeholder="مثال: -1001234567890">
                        <p class="description">شناسه عددی کانال خصوصی را وارد کنید. این شناسه باید با - شروع شود.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="telegram_bot_token">توکن ربات تلگرام</label>
                    </th>
                    <td>
                        <input type="text" id="telegram_bot_token" name="telegram_bot_token" 
                               value="<?php echo esc_attr($bot_token); ?>" class="regular-text"
                               placeholder="مثال: 123456789:ABCDEFGHIJKLMNOPQRSTUVWXYZabcdef">
                        <p class="description">توکن کامل ربات تلگرام را وارد کنید. این توکن را از BotFather دریافت می‌کنید و شبیه 123456789:ABC... است.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="telegram_hook_url">آدرس هوک میانی تلگرام</label>
                    </th>
                    <td>
                        <input type="url" id="telegram_hook_url" name="telegram_hook_url" 
                               value="<?php echo esc_url($hook_url); ?>" class="regular-text"
                               placeholder="مثال: https://example.com/all.php">
                        <p class="description">آدرس هوک میانی برای ارسال پیام به تلگرام را وارد کنید.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">ارسال خودکار</th>
                    <td>
                        <label>
                            <input type="checkbox" name="auto_post_enabled" value="1" 
                                   <?php checked($auto_post_enabled, '1'); ?>>
                            ارسال خودکار اخبار تایید شده به کانال خصوصی
                        </label>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="save_private_channel" class="button button-primary" value="ذخیره تنظیمات">
            </p>
        </form>
    </div>
    <?php
} 