<?php
/**
 * تنظیمات متاباکس‌ها
 */
function smart_admin_metabox_settings() {
    ?>
    <div class="wrap">
        <h1>تنظیمات متاباکس‌های ادمین هوشمند</h1>
        
        <?php if (isset($_GET['updated']) && $_GET['updated'] === 'true') : ?>
            <div class="notice notice-success is-dismissible">
                <p>تنظیمات با موفقیت ذخیره شدند.</p>
            </div>
        <?php endif; ?>
        
        <form method="post" action="">
            <?php wp_nonce_field('smart_admin_metabox_settings', 'smart_admin_metabox_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">متاباکس Rank Math</th>
                    <td>
                        <label>
                            <input type="checkbox" name="smart_admin_settings[rankmath_metabox]" value="1" <?php checked(smart_admin_get_setting('rankmath_metabox'), 1); ?>>
                            فعال‌سازی متاباکس Rank Math برای تنظیم کلمات کلیدی اصلی
                        </label>
                        <p class="description">این گزینه یک متاباکس برای تنظیم کلمات کلیدی اصلی Rank Math اضافه می‌کند.</p>
                        <p><a href="<?php echo admin_url('admin.php?page=smart-admin-keyword-test'); ?>" class="button button-secondary">ابزار تست و عیب‌یابی کلمات کلیدی</a></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">متاباکس OpenAI</th>
                    <td>
                        <label>
                            <input type="checkbox" name="smart_admin_settings[openai_metabox]" value="1" <?php checked(smart_admin_get_setting('openai_metabox'), 1); ?>>
                            فعال‌سازی متاباکس OpenAI برای ایجاد محتوا
                        </label>
                        <p class="description">این گزینه یک متاباکس برای تولید محتوا با استفاده از OpenAI اضافه می‌کند.</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">متاباکس روش ارسال</th>
                    <td>
                        <label>
                            <input type="checkbox" name="smart_admin_settings[send_method_metabox]" value="1" <?php checked(smart_admin_get_setting('send_method_metabox'), 1); ?>>
                            فعال‌سازی متاباکس روش ارسال
                        </label>
                        <p class="description">این گزینه یک متاباکس برای انتخاب روش ارسال به سایر سرویس‌ها اضافه می‌کند.</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">انتشار خودکار پست‌ها</th>
                    <td>
                        <label>
                            <input type="checkbox" name="smart_admin_settings[auto_publish]" value="1" <?php checked(smart_admin_get_setting('auto_publish'), 1); ?>>
                            انتشار خودکار پست‌هایی که توسط هوش مصنوعی ایجاد می‌شوند
                        </label>
                        <p class="description">اگر این گزینه فعال باشد، پست‌هایی که توسط هوش مصنوعی ایجاد می‌شوند بصورت خودکار منتشر می‌شوند.</p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="ذخیره تغییرات">
            </p>
        </form>
    </div>
    <?php
}