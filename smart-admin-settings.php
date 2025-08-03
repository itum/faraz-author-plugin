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
            
            <h2>تنظیمات تولید تصویر خودکار</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">Unsplash API Key</th>
                    <td>
                        <input type="text" name="unsplash_access_key" value="<?php echo esc_attr(get_option('unsplash_access_key', '')); ?>" style="width: 400px;" />
                        <p class="description">برای استفاده از Unsplash، ابتدا در <a href="https://unsplash.com/developers" target="_blank">Unsplash Developers</a> ثبت‌نام کنید و API Key دریافت کنید.</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">اضافه کردن خودکار تصویر</th>
                    <td>
                        <label>
                            <input type="checkbox" name="auto_add_unsplash_image" value="1" <?php checked(get_option('auto_add_unsplash_image', false), 1); ?>>
                            تصویر مناسب را به صورت خودکار به نوشته‌های جدید اضافه کند
                        </label>
                        <p class="description">این گزینه تصاویر مرتبط را از Unsplash پیدا کرده و به نوشته‌ها اضافه می‌کند.</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">کیفیت تصویر</th>
                    <td>
                        <select name="unsplash_image_quality">
                            <option value="small" <?php selected(get_option('unsplash_image_quality', 'regular'), 'small'); ?>>کوچک (400px)</option>
                            <option value="regular" <?php selected(get_option('unsplash_image_quality', 'regular'), 'regular'); ?>>معمولی (1080px)</option>
                            <option value="full" <?php selected(get_option('unsplash_image_quality', 'regular'), 'full'); ?>>کامل (اصلی)</option>
                        </select>
                        <p class="description">کیفیت تصاویر دانلود شده از Unsplash</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">جهت تصویر</th>
                    <td>
                        <select name="unsplash_image_orientation">
                            <option value="landscape" <?php selected(get_option('unsplash_image_orientation', 'landscape'), 'landscape'); ?>>افقی</option>
                            <option value="portrait" <?php selected(get_option('unsplash_image_orientation', 'landscape'), 'portrait'); ?>>عمودی</option>
                            <option value="squarish" <?php selected(get_option('unsplash_image_orientation', 'landscape'), 'squarish'); ?>>مربعی</option>
                        </select>
                        <p class="description">جهت ترجیحی تصاویر</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">حداکثر تصاویر در هر نوشته</th>
                    <td>
                        <input type="number" name="unsplash_max_images_per_post" value="<?php echo esc_attr(get_option('unsplash_max_images_per_post', 1)); ?>" min="1" max="5" />
                        <p class="description">حداکثر تعداد تصاویر که می‌تواند به هر نوشته اضافه شود</p>
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

// اضافه کردن تابع برای ذخیره تنظیمات Unsplash
function smart_admin_save_unsplash_settings() {
    if (isset($_POST['submit']) && isset($_POST['smart_admin_metabox_nonce'])) {
        if (wp_verify_nonce($_POST['smart_admin_metabox_nonce'], 'smart_admin_metabox_settings')) {
            
            // ذخیره تنظیمات Unsplash
            if (isset($_POST['unsplash_access_key'])) {
                update_option('unsplash_access_key', sanitize_text_field($_POST['unsplash_access_key']));
            }
            
            if (isset($_POST['auto_add_unsplash_image'])) {
                update_option('auto_add_unsplash_image', true);
            } else {
                update_option('auto_add_unsplash_image', false);
            }
            
            if (isset($_POST['unsplash_image_quality'])) {
                update_option('unsplash_image_quality', sanitize_text_field($_POST['unsplash_image_quality']));
            }
            
            if (isset($_POST['unsplash_image_orientation'])) {
                update_option('unsplash_image_orientation', sanitize_text_field($_POST['unsplash_image_orientation']));
            }
            
            if (isset($_POST['unsplash_max_images_per_post'])) {
                update_option('unsplash_max_images_per_post', intval($_POST['unsplash_max_images_per_post']));
            }
            
            // ریدایرکت با پیام موفقیت
            wp_redirect(add_query_arg('updated', 'true', admin_url('admin.php?page=smart-admin-settings')));
            exit;
        }
    }
}
add_action('admin_init', 'smart_admin_save_unsplash_settings');