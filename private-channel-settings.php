<?php
function farazautur_private_channel_settings_page() {
    if (isset($_POST['save_private_channel'])) {
        update_option('farazautur_private_channel_id', sanitize_text_field($_POST['private_channel_id']));
        update_option('farazautur_public_channel_id', sanitize_text_field($_POST['public_channel_id']));
        update_option('telegram_hook_url', esc_url_raw($_POST['telegram_hook_url']));
        update_option('farazautur_auto_post_enabled', isset($_POST['auto_post_enabled']) ? '1' : '0');
        echo '<div class="updated"><p>تنظیمات ذخیره شدند.</p></div>';
    }

    $channel_id = get_option('farazautur_private_channel_id', '');
    $public_channel_id = get_option('farazautur_public_channel_id', '');
    $hook_url = get_option('telegram_hook_url', '');
    $auto_post_enabled = get_option('farazautur_auto_post_enabled', '0');
    ?>
    <div class="wrap">
        <h2>تنظیمات کانال‌های تلگرامی</h2>
        <form method="post" action="">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="private_channel_id">آیدی کانال خصوصی (مدیریت)</label>
                    </th>
                    <td>
                        <input type="text" id="private_channel_id" name="private_channel_id" 
                               value="<?php echo esc_attr($channel_id); ?>" class="regular-text"
                               placeholder="مثال: -100123456789">
                        <p class="description">آیدی عددی کانال خصوصی را وارد کنید. این آیدی با - شروع می‌شود.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="public_channel_id">آیدی کانال عمومی (انتشار)</label>
                    </th>
                    <td>
                        <input type="text" id="public_channel_id" name="public_channel_id" 
                               value="<?php echo esc_attr($public_channel_id); ?>" class="regular-text"
                               placeholder="مثال: -100123456789">
                        <p class="description">آیدی عددی کانال عمومی برای انتشار نهایی اخبار را وارد کنید. در این کانال امضا نمایش داده خواهد شد.</p>
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