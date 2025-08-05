<?php
// فایل مربوط به زمان‌بندی تولید محتوا در ادمین هوشمند

// ثبت تنظیمات زمان‌بندی
add_action('admin_init', 'smart_admin_register_scheduler_settings');

function smart_admin_register_scheduler_settings() {
    // گروه تنظیمات
    $option_group = 'smart_admin_scheduler_settings';

    // ثبت تنظیمات اصلی
    register_setting($option_group, 'smart_admin_scheduler_enabled');
    register_setting($option_group, 'smart_admin_scheduler_telegram_channel');
    register_setting($option_group, 'smart_admin_scheduler_keywords');

    // ثبت تنظیمات برای هر روز هفته
    $days_of_week = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
    foreach ($days_of_week as $day) {
        register_setting($option_group, "smart_admin_scheduler_{$day}_enabled");
        register_setting($option_group, "smart_admin_scheduler_{$day}_time");
        register_setting($option_group, "smart_admin_scheduler_{$day}_count");
    }
}

// تابع نمایش محتوای تب زمان‌بندی
function smart_admin_scheduler_tab_content() {
    ?>
    <style>
        .scheduler-wrap {
            direction: rtl;
        }
        .scheduler-form .form-group {
            margin-bottom: 25px;
        }
        .scheduler-form label {
            font-weight: bold;
            display: block;
            margin-bottom: 8px;
        }
        .scheduler-form input[type="text"],
        .scheduler-form input[type="time"],
        .scheduler-form input[type="number"],
        .scheduler-form textarea {
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        .scheduler-form .description {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
        }
        .scheduler-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .scheduler-table th,
        .scheduler-table td {
            padding: 15px;
            text-align: right;
            border: 1px solid #ddd;
        }
        .scheduler-table th {
            background-color: #f7f7f7;
        }
        .day-label {
            font-weight: bold;
            font-size: 16px;
        }
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .slider {
            background-color: #2196F3;
        }
        input:checked + .slider:before {
            transform: translateX(26px);
        }
    </style>

    <div id="scheduler" class="tab-content">
        <div class="scheduler-wrap">
            <h3>زمان‌بندی تولید و انتشار محتوا</h3>
            <p>در این بخش می‌توانید یک برنامه هفتگی برای تولید خودکار مقالات بر اساس کلمات کلیدی تعریف شده، تنظیم کنید.</p>

            <form method="post" action="options.php" class="scheduler-form">
                <?php
                settings_fields('smart_admin_scheduler_settings');
                $days_of_week = [
                    'saturday'  => 'شنبه',
                    'sunday'    => 'یکشنبه',
                    'monday'    => 'دوشنبه',
                    'tuesday'   => 'سه‌شنبه',
                    'wednesday' => 'چهارشنبه',
                    'thursday'  => 'پنج‌شنبه',
                    'friday'    => 'جمعه',
                ];
                ?>

                <div class="form-group">
                    <label>فعال‌سازی زمان‌بندی</label>
                    <label class="toggle-switch">
                        <input type="checkbox" name="smart_admin_scheduler_enabled" value="1" <?php checked(1, get_option('smart_admin_scheduler_enabled'), true); ?>>
                        <span class="slider"></span>
                    </label>
                    <p class="description">با فعال کردن این گزینه، سیستم به صورت خودکار طبق برنامه زیر مقالات را تولید می‌کند.</p>
                </div>

                <div class="form-group">
                    <label for="smart_admin_scheduler_telegram_channel">شناسه کانال تلگرام برای تایید</label>
                    <input type="text" id="smart_admin_scheduler_telegram_channel" name="smart_admin_scheduler_telegram_channel" value="<?php echo esc_attr(get_option('smart_admin_scheduler_telegram_channel')); ?>" placeholder="@YourChannelID">
                    <p class="description">شناسه کانال یا چت خصوصی تلگرام که مقالات تولید شده برای تایید به آنجا ارسال می‌شوند (مثال: -100123456789 یا @YourChannel).</p>
                </div>

                <div class="form-group">
                    <label for="smart_admin_scheduler_keywords">کلمات کلیدی اصلی (هر کلمه در یک خط)</label>
                    <textarea id="smart_admin_scheduler_keywords" name="smart_admin_scheduler_keywords" rows="8"><?php echo esc_textarea(get_option('smart_admin_scheduler_keywords')); ?></textarea>
                    <p class="description">کلمات کلیدی مورد نظر خود را وارد کنید. در هر بار اجرا، یک کلمه کلیدی به صورت تصادفی از این لیست انتخاب می‌شود.</p>
                </div>

                <hr>
                <h4>برنامه هفتگی تولید محتوا</h4>

                <table class="scheduler-table">
                    <thead>
                        <tr>
                            <th>روز هفته</th>
                            <th>وضعیت</th>
                            <th>ساعت تولید</th>
                            <th>تعداد مقالات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($days_of_week as $day_en => $day_fa) : ?>
                            <tr>
                                <td><span class="day-label"><?php echo $day_fa; ?></span></td>
                                <td>
                                    <label class="toggle-switch">
                                        <input type="checkbox" name="smart_admin_scheduler_<?php echo $day_en; ?>_enabled" value="1" <?php checked(1, get_option("smart_admin_scheduler_{$day_en}_enabled"), true); ?>>
                                        <span class="slider"></span>
                                    </label>
                                </td>
                                <td>
                                    <input type="time" name="smart_admin_scheduler_<?php echo $day_en; ?>_time" value="<?php echo esc_attr(get_option("smart_admin_scheduler_{$day_en}_time", '09:00')); ?>">
                                </td>
                                <td>
                                    <input type="number" name="smart_admin_scheduler_<?php echo $day_en; ?>_count" min="1" max="10" value="<?php echo esc_attr(get_option("smart_admin_scheduler_{$day_en}_count", 1)); ?>">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php submit_button('ذخیره تنظیمات زمان‌بندی', 'primary', 'submit', true, ['style' => 'margin-top: 20px;']); ?>
            </form>
        </div>
    </div>
    <?php
}

// فعال‌سازی و غیرفعال‌سازی رویداد زمان‌بندی شده
add_action('update_option_smart_admin_scheduler_enabled', 'smart_admin_handle_scheduler_activation', 10, 2);

function smart_admin_handle_scheduler_activation($old_value, $new_value) {
    if ($new_value == 1 && !wp_next_scheduled('smart_admin_content_cron')) {
        // اگر زمان‌بندی فعال شد و رویداد وجود نداشت، آن را ایجاد کن
        wp_schedule_event(time(), 'hourly', 'smart_admin_content_cron');
    } elseif ($new_value != 1 && wp_next_scheduled('smart_admin_content_cron')) {
        // اگر زمان‌بندی غیرفعال شد و رویداد وجود داشت، آن را حذف کن
        wp_clear_scheduled_hook('smart_admin_content_cron');
    }
}

// افزودن تابع اجرایی به رویداد زمان‌بندی شده
add_action('smart_admin_content_cron', 'smart_admin_execute_content_generation');

// تابع اصلی برای تولید محتوای زمان‌بندی شده
function smart_admin_execute_content_generation() {
    // ابتدا بررسی می‌کنیم که آیا زمان‌بندی کلی فعال است یا خیر
    if (get_option('smart_admin_scheduler_enabled') != 1) {
        return;
    }

    // دریافت روز و ساعت فعلی سرور (به وقت محلی وردپرس)
    $current_day_en = strtolower(current_time('l')); // 'l' -> نام کامل روز هفته به انگلیسی
    $current_time = current_time('H:i'); // فرمت ۲۴ ساعته

    // بررسی آیا برای امروز زمان‌بندی فعال است
    if (get_option("smart_admin_scheduler_{$current_day_en}_enabled") != 1) {
        return;
    }

    // دریافت زمان تنظیم شده برای امروز
    $scheduled_time = get_option("smart_admin_scheduler_{$current_day_en}_time");

    // مقایسه زمان فعلی با زمان تنظیم شده
    // برای جلوگیری از اجرای چندباره، یک بازه زمانی (مثلا یک ساعت) در نظر می‌گیریم
    if (strtotime($current_time) >= strtotime($scheduled_time) && strtotime($current_time) < strtotime($scheduled_time . ' +1 hour')) {

        // جلوگیری از اجرای مجدد در همان ساعت
        $last_run_option_key = "smart_admin_last_run_{$current_day_en}_{$scheduled_time}";
        $last_run_date = get_option($last_run_option_key);
        $current_date = current_time('Y-m-d');

        if ($last_run_date == $current_date) {
            return; // در این روز و این ساعت قبلا اجرا شده است
        }

        // اجرای فرآیند اصلی
        smart_admin_generate_and_send_articles();

        // ثبت زمان آخرین اجرا
        update_option($last_run_option_key, $current_date);
    }
}

// تابع تولید و ارسال مقالات
function smart_admin_generate_and_send_articles() {
    $current_day_en = strtolower(current_time('l'));
    $article_count = (int) get_option("smart_admin_scheduler_{$current_day_en}_count", 1);
    $keywords_list = get_option('smart_admin_scheduler_keywords');
    $telegram_channel = get_option('smart_admin_scheduler_telegram_channel');
    $api_key = get_option('smart_admin_api_key');
    $model = get_option('smart_admin_model', 'gpt-4o');

    if (empty($keywords_list) || empty($telegram_channel) || empty($api_key)) {
        // لاگ‌برداری از خطا
        error_log('Smart Admin Scheduler: Missing required settings (keywords, channel, or API key).');
        return;
    }

    $keywords = array_filter(array_map('trim', explode("\n", $keywords_list)));
    if (empty($keywords)) {
        return;
    }

    for ($i = 0; $i < $article_count; $i++) {
        // انتخاب یک کلمه کلیدی تصادفی
        $random_keyword = $keywords[array_rand($keywords)];

        // ایجاد پرامپت حرفه‌ای برای تولید مقاله
        $prompt = "لطفاً یک مقاله کامل، جامع و بهینه برای سئو (SEO-friendly) در مورد «{$random_keyword}» بنویس. این مقاله باید شامل موارد زیر باشد:\n1. یک عنوان جذاب و گیرا.\n2. یک مقدمه کوتاه برای معرفی موضوع.\n3. بدنه اصلی مقاله با حداقل 3 پاراگراف و استفاده از هدینگ‌های (H2, H3) مناسب.\n4. نتیجه‌گیری و جمع‌بندی مطالب.\n5. لحن مقاله باید رسمی و آموزنده باشد.";

        // ارسال درخواست به API
        $response = send_to_gapgpt_api($prompt, $model, $api_key);

        if (isset($response['content']) && !empty($response['content'])) {
            // استخراج عنوان از محتوای تولید شده
            $content = $response['content'];
            $title = "مقاله تولید شده با هوش مصنوعی: " . $random_keyword;
            
            // استفاده از تابع استخراج عنوان SEO شده
            if (function_exists('smart_admin_extract_seo_title')) {
                $ai_title = smart_admin_extract_seo_title($content);
                if (!empty($ai_title)) {
                    $title = $ai_title;
                    error_log("Smart Admin Scheduler: Using SEO title extracted from AI content: {$title}");
                }
            } else {
                // روش قدیمی: تلاش برای یافتن عنوان بهتر در خط اول محتوا
                $first_line = strtok($content, "\n");
                if (strlen($first_line) < 100) { // یک بررسی ساده برای اینکه عنوان خیلی طولانی نباشد
                    $title = str_replace(['#', '*'], '', $first_line);
                    error_log("Smart Admin Scheduler: Using first line as title: {$title}");
                }
            }

            // استخراج پیوند یکتای بهینه شده برای SEO
            $slug = '';
            if (function_exists('smart_admin_extract_seo_slug')) {
                $slug = smart_admin_extract_seo_slug($content, $title, array($random_keyword));
                error_log("Smart Admin Scheduler: Generated SEO slug: " . $slug);
            }
            
            // ذخیره مقاله به عنوان پیش‌نویس
            $post_id = wp_insert_post([
                'post_title'   => sanitize_text_field($title),
                'post_content' => wp_kses_post($content),
                'post_status'  => 'draft',
                'post_author'  => 1, // یا هر نویسنده دیگری
                'post_name'    => $slug, // تنظیم پیوند یکتا
            ]);

            if (!is_wp_error($post_id)) {
                // تولید خودکار تصویر شاخص بر اساس محتوا
                if (function_exists('smart_generate_featured_image')) {
                    $image_generated = smart_generate_featured_image($post_id, $title, $content);
                    if ($image_generated) {
                        error_log("Smart Admin Scheduler: Featured image generated successfully for post ID: {$post_id}");
                    } else {
                        error_log("Smart Admin Scheduler: Failed to generate featured image for post ID: {$post_id}");
                    }
                }
                
                // افزودن متا برای شناسایی این پست
                update_post_meta($post_id, '_smart_admin_generated', true);
                update_post_meta($post_id, '_smart_admin_keyword', $random_keyword);

                // ارسال به تلگرام (در اینجا باید تابع ارسال پیام به تلگرام را فراخوانی کنید)
                $message = "✅ یک مقاله جدید با عنوان «{$title}» بر اساس کلمه کلیدی «{$random_keyword}» تولید و به عنوان پیش‌نویس ذخیره شد.\n\n";
                $message .= "برای بررسی، ویرایش و انتشار به لینک زیر مراجعه کنید:\n";
                $message .= get_edit_post_link($post_id);

                // فرض می‌کنیم تابعی به نام faraz_send_telegram_message برای ارسال وجود دارد
                // این تابع باید از افزونه اصلی شما در دسترس باشد
                if (function_exists('faraz_send_telegram_message')) {
                    faraz_send_telegram_message($telegram_channel, $message);
                } else {
                    error_log("Smart Admin Scheduler: Function 'faraz_send_telegram_message' does not exist.");
                }
            }
        }
        // کمی تاخیر بین درخواست‌ها برای جلوگیری از فشار به API
        sleep(5);
    }
}