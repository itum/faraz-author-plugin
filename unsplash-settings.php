<?php
/**
 * Unsplash API Settings Page
 */

// Add submenu for Unsplash settings
add_action('admin_menu', 'faraz_unsplash_add_submenu', 30);

function faraz_unsplash_add_submenu() {
    add_submenu_page(
        'faraz-telegram-plugin', // Slug of the parent menu
        'تنظیمات Unsplash',      // Page title
        'تنظیمات Unsplash',      // Menu title
        'manage_options',       // Capability required
        'faraz-unsplash-settings', // Menu slug
        'faraz_unsplash_settings_page_callback' // Callback function to display the page
    );
}

// Register settings
add_action('admin_init', 'faraz_unsplash_register_settings');

function faraz_unsplash_register_settings() {
    register_setting(
        'faraz_unsplash_settings_group', // Option group
        'faraz_unsplash_api_key',      // Option name
        [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ]
    );

    register_setting(
        'faraz_unsplash_settings_group',
        'faraz_unsplash_image_resolution',
        [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'regular'
        ]
    );

    register_setting(
        'faraz_unsplash_settings_group',
        'faraz_unsplash_suggestion_count',
        [
            'type' => 'number',
            'sanitize_callback' => 'absint',
            'default' => 3
        ]
    );
}

// Settings page content
function faraz_unsplash_settings_page_callback() {
    $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'settings';
    ?>
    <div class="wrap">
        <h1>تنظیمات اتصال به Unsplash</h1>
        <h2 class="nav-tab-wrapper">
            <a href="?page=faraz-unsplash-settings&tab=settings" class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>">تنظیمات</a>
            <a href="?page=faraz-unsplash-settings&tab=logs" class="nav-tab <?php echo $active_tab == 'logs' ? 'nav-tab-active' : ''; ?>">لاگ‌ها</a>
        </h2>

        <?php if ($active_tab == 'settings') : ?>
            <p>برای دریافت کلید API، به <a href="https://unsplash.com/developers" target="_blank">صفحه توسعه‌دهندگان Unsplash</a> مراجعه کنید.</p>
            <form method="post" action="options.php">
                <?php
                settings_fields('faraz_unsplash_settings_group');
                do_settings_sections('faraz_unsplash_settings_group');
                ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">
                            <label for="faraz_unsplash_api_key">کلید API Unsplash</label>
                        </th>
                        <td>
                            <input type="text" id="faraz_unsplash_api_key" name="faraz_unsplash_api_key" value="<?php echo esc_attr(get_option('faraz_unsplash_api_key')); ?>" class="regular-text" />
                            <p class="description">کلید API خود را که از Unsplash دریافت کرده‌اید، در این قسمت وارد کنید.</p>
                        </td>
                    </tr>
                    
                    <tr valign="top">
                        <th scope="row">
                            <label for="faraz_unsplash_image_resolution">رزولوشن تصویر</label>
                        </th>
                        <td>
                            <select id="faraz_unsplash_image_resolution" name="faraz_unsplash_image_resolution">
                                <option value="raw" <?php selected(get_option('faraz_unsplash_image_resolution'), 'raw'); ?>>خام (Raw)</option>
                                <option value="full" <?php selected(get_option('faraz_unsplash_image_resolution'), 'full'); ?>>کامل (Full)</option>
                                <option value="regular" <?php selected(get_option('faraz_unsplash_image_resolution'), 'regular'); ?>>معمولی (Regular)</option>
                                <option value="small" <?php selected(get_option('faraz_unsplash_image_resolution'), 'small'); ?>>کوچک (Small)</option>
                                <option value="thumb" <?php selected(get_option('faraz_unsplash_image_resolution'), 'thumb'); ?>>بندانگشتی (Thumb)</option>
                            </select>
                            <p class="description">کیفیت و اندازه تصویری که از Unsplash دانلود می‌شود را انتخاب کنید.</p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">
                            <label for="faraz_unsplash_suggestion_count">تعداد تصاویر پیشنهادی</label>
                        </th>
                        <td>
                            <input type="number" id="faraz_unsplash_suggestion_count" name="faraz_unsplash_suggestion_count" value="<?php echo esc_attr(get_option('faraz_unsplash_suggestion_count', 3)); ?>" min="1" max="10" class="small-text" />
                            <p class="description">تعداد تصاویری که برای انتخاب به شما پیشنهاد می‌شود (بین ۱ تا ۱۰).</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('ذخیره تنظیمات'); ?>
            </form>
        <?php elseif ($active_tab == 'logs') : 
            $log_file = plugin_dir_path(__FILE__) . 'unsplash_logs.txt';

            if (isset($_POST['clear_log_file']) && check_admin_referer('clear_unsplash_logs_nonce')) {
                if (file_exists($log_file)) {
                    unlink($log_file);
                    echo '<div class="notice notice-success is-dismissible"><p>فایل لاگ با موفقیت پاک شد.</p></div>';
                }
            }
        ?>
            <h2>لاگ‌های Unsplash</h2>
            <div id="log-viewer">
                <?php
                if (file_exists($log_file)) {
                    echo '<pre>' . esc_textarea(file_get_contents($log_file)) . '</pre>';
                } else {
                    echo '<p>هنوز هیچ لاگی ثبت نشده است.</p>';
                }
                ?>
            </div>
            <form method="post" action="">
                <?php wp_nonce_field('clear_unsplash_logs_nonce'); ?>
                <p class="submit">
                    <input type="submit" name="clear_log_file" class="button button-secondary" value="پاک کردن لاگ" onclick="return confirm('آیا از پاک کردن فایل لاگ اطمینان دارید؟')">
                </p>
            </form>
            <style>
                #log-viewer {
                    background: #fff;
                    border: 1px solid #e5e5e5;
                    padding: 10px;
                    height: 400px;
                    overflow-y: scroll;
                    white-space: pre-wrap;
                }
            </style>
        <?php endif; ?>
    </div>
    <?php
}
