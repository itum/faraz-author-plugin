<?php
/*
 * فایل گزارش نویسی خودکار
 * این فایل شامل توابع مورد نیاز برای قابلیت گزارش نویسی خودکار افزونه است
 */

// جلوگیری از دسترسی مستقیم به فایل
if (!defined('ABSPATH')) {
    exit;
}

// تابع لاگ کردن برای دیباگ
function faraz_auto_report_log($message, $type = 'info') {
    $log_file = plugin_dir_path(__FILE__) . 'auto-report-debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[{$timestamp}] [{$type}] {$message}" . PHP_EOL;
    
    // برای اطمینان از عدم رشد بیش از حد فایل لاگ
    if (file_exists($log_file) && filesize($log_file) > 5 * 1024 * 1024) { // 5MB
        unlink($log_file); // حذف فایل لاگ قبلی اگر خیلی بزرگ شده باشد
    }
    
    // نوشتن پیام لاگ در فایل
    file_put_contents($log_file, $log_message, FILE_APPEND);
}

// ریدایرکت آدرس‌های مستقیم به آدرس صحیح
function faraz_auto_report_redirect() {
    global $pagenow;
    
    // بررسی آیا آدرس مورد نظر است
    if ($pagenow == 'admin.php' && isset($_GET['page']) && $_GET['page'] == 'faraz-auto-report') {
        // آدرس صحیح است، نیازی به ریدایرکت نیست
        return;
    }
    
    // اگر کاربر به مسیر wp-admin/faraz-auto-report وارد شده، ریدایرکت به آدرس صحیح
    $current_url = $_SERVER['REQUEST_URI'];
    if (strpos($current_url, '/wp-admin/faraz-auto-report') !== false) {
        wp_redirect(admin_url('admin.php?page=faraz-auto-report'));
        exit;
    }
}
add_action('admin_init', 'faraz_auto_report_redirect');

// افزودن منوی گزارش نویسی خودکار به منوی اصلی افزونه
function faraz_auto_report_add_menu() {
    // اصلاح اولویت هوک برای اطمینان از اجرا بعد از منوی اصلی
    add_submenu_page(
        'faraz-telegram-plugin', // منوی والد
        'گزارش نویسی خودکار', // عنوان صفحه
        'گزارش نویسی خودکار', // عنوان منو
        'manage_options', // مجوز دسترسی
        'faraz-auto-report', // slug صفحه
        'faraz_auto_report_page' // تابع نمایش صفحه
    );
    
    // همچنین به عنوان منوی اصلی هم اضافه کنیم (برای دسترسی مستقیم)
    add_menu_page(
        'گزارش نویسی خودکار', // عنوان صفحه
        'گزارش نویسی خودکار', // عنوان منو
        'manage_options', // مجوز دسترسی
        'faraz-auto-report', // slug صفحه
        'faraz_auto_report_page', // تابع نمایش صفحه
        'dashicons-media-text', // آیکون
        85 // موقعیت منو
    );
}
// تغییر اولویت برای اطمینان از اجرا بعد از ایجاد منوی اصلی
add_action('admin_menu', 'faraz_auto_report_add_menu', 20);

// افزودن تنظیمات گزارش نویسی خودکار
function faraz_auto_report_register_settings() {
    register_setting('faraz_auto_report_settings', 'faraz_auto_report_prefix', array(
        'type' => 'string',
        'default' => 'گزارش روابط عمومی: '
    ));
    register_setting('faraz_auto_report_settings', 'faraz_auto_report_model', array(
        'type' => 'string',
        'default' => 'gemini-2.0-flash'
    ));
    register_setting('faraz_auto_report_settings', 'faraz_auto_report_template', array(
        'type' => 'string',
        'default' => 'یک گزارش رسمی خبری با لحن روابط عمومی برای انتشار در رسانه‌های سازمانی با رعایت نکات زیر تهیه کنید:

موضوع: [SUBJECT]
توضیحات اولیه: [DESCRIPTION]

دستورالعمل‌های نگارش:
1. در پاراگراف اول، خلاصه خبر را در یک جمله با ذکر تاریخ، مکان و افراد حاضر بنویسید.
2. به این فرمت بنویسید: "[عنوان رویداد]، روز [روز هفته] [تاریخ] با حضور [افراد مهم حاضر] در [مکان] برگزار شد."
3. در پاراگراف دوم، با عبارت "به گزارش روابط عمومی..." شروع کنید و جزئیات بیشتر و نقل قول از مسئولان را بیاورید.
4. در پاراگراف سوم، به اقدامات انجام شده در مراسم مانند تقدیر، اهدای جوایز و دستاوردها اشاره کنید.
5. از هرگونه علامت تأکید مانند ستاره (**) یا قالب‌های غیرمتعارف استفاده نکنید.
6. از جملات رسمی، کوتاه و روان استفاده کنید.
7. از ضمیر سوم شخص استفاده کنید.

نمونه ساختار:
پاراگراف اول: رویداد، زمان، مکان، شرکت‌کنندگان.
پاراگراف دوم: "به گزارش روابط عمومی..."، نقل قول از مسئولان.
پاراگراف سوم: جزئیات اقدامات انجام شده در مراسم و نتایج.'
    ));
    register_setting('faraz_auto_report_settings', 'faraz_auto_report_default_category', array(
        'type' => 'integer',
        'default' => 0
    ));
}
add_action('admin_init', 'faraz_auto_report_register_settings');

// افزودن متاباکس برای نمایش اطلاعات گزارش خودکار
function faraz_auto_report_add_metabox() {
    add_meta_box(
        'faraz_auto_report_info',
        'اطلاعات گزارش خودکار',
        'faraz_auto_report_metabox_callback',
        'post',
        'side',
        'high'
    );
}
add_action('add_meta_boxes', 'faraz_auto_report_add_metabox');

// نمایش محتوای متاباکس
function faraz_auto_report_metabox_callback($post) {
    // بررسی آیا این پست یک گزارش خودکار است
    $is_auto_report = get_post_meta($post->ID, 'faraz_auto_report', true);
    
    if ($is_auto_report == 'yes') {
        $generation_date = get_post_meta($post->ID, 'faraz_auto_report_date', true);
        $report_subject = get_post_meta($post->ID, 'faraz_auto_report_subject', true);
        
        echo '<p><strong>این گزارش به صورت خودکار تولید شده است.</strong></p>';
        echo '<p><strong>موضوع:</strong> ' . esc_html($report_subject) . '</p>';
        echo '<p><strong>تاریخ تولید:</strong> ' . date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($generation_date)) . '</p>';
    } else {
        echo '<p>این پست یک گزارش خودکار نیست.</p>';
    }
}

// صفحه گزارش نویسی خودکار
function faraz_auto_report_page() {
    // بررسی مجوز دسترسی
    if (!current_user_can('manage_options')) {
        wp_die(__('شما اجازه دسترسی به این صفحه را ندارید.'));
    }
    
    // تنظیمات
    $report_prefix = get_option('faraz_auto_report_prefix', 'گزارش روابط عمومی: ');
    $report_model = get_option('faraz_auto_report_model', 'gemini-2.0-flash');
    $report_template = get_option('faraz_auto_report_template', '');
    
    // اطمینان از وجود قالب پرامپت پیش‌فرض
    if (empty($report_template)) {
        $default_template = 'یک گزارش رسمی خبری با لحن روابط عمومی برای انتشار در رسانه‌های سازمانی با رعایت نکات زیر تهیه کنید:

موضوع: [SUBJECT]
توضیحات اولیه: [DESCRIPTION]

دستورالعمل‌های نگارش:
1. در پاراگراف اول، خلاصه خبر را در یک جمله با ذکر تاریخ، مکان و افراد حاضر بنویسید.
2. به این فرمت بنویسید: "[عنوان رویداد]، روز [روز هفته] [تاریخ] با حضور [افراد مهم حاضر] در [مکان] برگزار شد."
3. در پاراگراف دوم، با عبارت "به گزارش روابط عمومی..." شروع کنید و جزئیات بیشتر و نقل قول از مسئولان را بیاورید.
4. در پاراگراف سوم، به اقدامات انجام شده در مراسم مانند تقدیر، اهدای جوایز و دستاوردها اشاره کنید.
5. از هرگونه علامت تأکید مانند ستاره (**) یا قالب‌های غیرمتعارف استفاده نکنید.
6. از جملات رسمی، کوتاه و روان استفاده کنید.
7. از ضمیر سوم شخص استفاده کنید.

نمونه ساختار:
پاراگراف اول: رویداد، زمان، مکان، شرکت‌کنندگان.
پاراگراف دوم: "به گزارش روابط عمومی..."، نقل قول از مسئولان.
پاراگراف سوم: جزئیات اقدامات انجام شده در مراسم و نتایج.';
        
        $report_template = $default_template;
        update_option('faraz_auto_report_template', $default_template);
        faraz_auto_report_log("قالب پرامپت پیش‌فرض تنظیم شد");
    }
    
    $default_category = get_option('faraz_auto_report_default_category', 0);
    $api_key = get_option('smart_admin_api_key', ''); // استفاده از کلید API موجود افزونه
    
    // ذخیره فرم گزارش
    if (isset($_POST['submit_report']) && isset($_POST['report_subject']) && !empty($_POST['report_subject'])) {
        // شروع لاگ فرآیند ایجاد گزارش
        faraz_auto_report_log('شروع فرآیند ایجاد گزارش جدید');
        
        try {
            // افزودن نانس برای امنیت
            check_admin_referer('faraz_auto_report_form', 'faraz_auto_report_nonce');
            faraz_auto_report_log('بررسی نانس با موفقیت انجام شد');
            
            $subject = sanitize_text_field($_POST['report_subject']);
            $description = isset($_POST['report_description']) ? sanitize_textarea_field($_POST['report_description']) : '';
            $category = isset($_POST['report_category']) ? intval($_POST['report_category']) : $default_category;
            
            faraz_auto_report_log("اطلاعات فرم: موضوع: {$subject}, دسته‌بندی: {$category}");
            
            // تنظیم پرامپت برای هوش مصنوعی
            $prompt = str_replace(
                array('[SUBJECT]', '[DESCRIPTION]'),
                array($subject, $description),
                $report_template
            );
            
            faraz_auto_report_log("پرامپت آماده شد. مدل: {$report_model}");
            faraz_auto_report_log("شروع ارسال درخواست به API هوش مصنوعی");
            
            // بررسی پرامپت خالی
            if (empty($prompt)) {
                faraz_auto_report_log("خطا: پرامپت خالی برای ارسال به API", 'error');
                throw new Exception("پرامپت خالی است. لطفاً موضوع و توضیحات را با دقت وارد کنید.");
            }
            
            // دریافت پاسخ از هوش مصنوعی - استفاده از یک تابع واحد برای همه مدل‌ها
            faraz_auto_report_log("استفاده از API استاندارد برای مدل {$report_model}");
            $response = send_to_gemini_api($prompt, $report_model, $api_key);
            
            if (isset($response['error'])) {
                faraz_auto_report_log("خطا در دریافت پاسخ از هوش مصنوعی: " . $response['error'], 'error');
                throw new Exception("خطا در دریافت پاسخ از هوش مصنوعی: " . $response['error']);
            }
            
            faraz_auto_report_log("پاسخ از هوش مصنوعی با موفقیت دریافت شد");
            
            // ذخیره به عنوان پیش‌نویس نوشته
            if (isset($response['content']) && !empty($response['content'])) {
                $report_content = $response['content'];
                faraz_auto_report_log("محتوای گزارش آماده شد. تعداد کاراکترها: " . strlen($report_content));
                
                // ایجاد پست جدید
                $post_data = array(
                    'post_title'    => $report_prefix . $subject,
                    'post_content'  => $report_content,
                    'post_status'   => 'draft',
                    'post_type'     => 'post',
                    'post_author'   => get_current_user_id(),
                    'post_category' => array($category)
                );
                
                faraz_auto_report_log("شروع ایجاد پست جدید با عنوان: " . $post_data['post_title']);
                
                // درج پست
                $post_id = wp_insert_post($post_data);
                
                if (is_wp_error($post_id)) {
                    faraz_auto_report_log("خطا در ایجاد پست: " . $post_id->get_error_message(), 'error');
                    throw new Exception("خطا در ایجاد پست: " . $post_id->get_error_message());
                }
                
                faraz_auto_report_log("پست با موفقیت ایجاد شد. شناسه پست: {$post_id}");
                
                // ذخیره متادیتای گزارش
                update_post_meta($post_id, 'faraz_auto_report', 'yes');
                update_post_meta($post_id, 'faraz_auto_report_date', current_time('mysql'));
                update_post_meta($post_id, 'faraz_auto_report_subject', $subject);
                update_post_meta($post_id, 'faraz_auto_report_description', $description);
                
                faraz_auto_report_log("متادیتای گزارش با موفقیت ذخیره شد");
                
                // پردازش تصاویر (از رسانه وردپرس یا آپلود مستقیم)
                $image_ids = array();
                
                // بررسی تصاویر انتخاب شده از رسانه وردپرس
                if (!empty($_POST['selected_images'])) {
                    faraz_auto_report_log("شروع پردازش تصاویر انتخاب شده از رسانه");
                    
                    $selected_image_ids = explode(',', sanitize_text_field($_POST['selected_images']));
                    foreach ($selected_image_ids as $img_id) {
                        $img_id = intval($img_id);
                        if ($img_id > 0) {
                            faraz_auto_report_log("اضافه کردن تصویر از کتابخانه رسانه با شناسه: {$img_id}");
                            $image_ids[] = $img_id;
                        }
                    }
                }
                
                // بررسی تصاویر آپلود شده مستقیم
                if (!empty($_FILES['report_images']['name'][0])) {
                    faraz_auto_report_log("شروع پردازش تصاویر آپلود شده");
                    
                    require_once(ABSPATH . 'wp-admin/includes/image.php');
                    require_once(ABSPATH . 'wp-admin/includes/file.php');
                    require_once(ABSPATH . 'wp-admin/includes/media.php');
                    
                    foreach ($_FILES['report_images']['name'] as $key => $value) {
                        if ($_FILES['report_images']['name'][$key]) {
                            faraz_auto_report_log("پردازش تصویر: " . $_FILES['report_images']['name'][$key]);
                            
                            $file = array(
                                'name'     => $_FILES['report_images']['name'][$key],
                                'type'     => $_FILES['report_images']['type'][$key],
                                'tmp_name' => $_FILES['report_images']['tmp_name'][$key],
                                'error'    => $_FILES['report_images']['error'][$key],
                                'size'     => $_FILES['report_images']['size'][$key]
                            );
                            
                            $image_id = media_handle_sideload($file, $post_id);
                            
                            if (is_wp_error($image_id)) {
                                faraz_auto_report_log("خطا در آپلود تصویر: " . $image_id->get_error_message(), 'error');
                            } else {
                                faraz_auto_report_log("تصویر با موفقیت آپلود شد. شناسه تصویر: {$image_id}");
                                $image_ids[] = $image_id;
                            }
                            }
                        }
                    }
                    
                // پردازش تصاویر برای اضافه کردن به محتوا
                    if (!empty($image_ids)) {
                    faraz_auto_report_log("تعداد تصاویر انتخاب شده: " . count($image_ids));
                    
                        // تنظیم تصویر شاخص
                        set_post_thumbnail($post_id, $image_ids[0]);
                    faraz_auto_report_log("تصویر شاخص با موفقیت تنظیم شد با شناسه: " . $image_ids[0]);
                        
                        // اضافه کردن سایر تصاویر به محتوا
                        if (count($image_ids) > 1) {
                            $post_content = $report_content;
                            
                            for ($i = 1; $i < count($image_ids); $i++) {
                                $image_url = wp_get_attachment_url($image_ids[$i]);
                                $post_content .= "\n\n<img src=\"{$image_url}\" alt=\"تصویر گزارش\" class=\"size-full\">";
                                faraz_auto_report_log("تصویر اضافی به محتوا اضافه شد: {$image_url}");
                            }
                            
                            // به‌روزرسانی محتوای پست
                            $update_result = wp_update_post(array(
                                'ID' => $post_id,
                                'post_content' => $post_content
                            ));
                            
                            if (is_wp_error($update_result)) {
                                faraz_auto_report_log("خطا در به‌روزرسانی محتوای پست: " . $update_result->get_error_message(), 'error');
                            } else {
                                faraz_auto_report_log("محتوای پست با تصاویر اضافی به‌روزرسانی شد");
                        }
                    }
                } else {
                    faraz_auto_report_log("هیچ تصویری برای گزارش انتخاب یا آپلود نشده است");
                }
                
                // پیام موفقیت
                $success_message = 'گزارش با موفقیت به عنوان پیش‌نویس ذخیره شد. <a href="' . get_edit_post_link($post_id) . '" target="_blank">مشاهده و ویرایش</a>';
                faraz_auto_report_log("فرآیند ایجاد گزارش با موفقیت به پایان رسید");
            } else {
                faraz_auto_report_log("پاسخ هوش مصنوعی خالی است", 'error');
                throw new Exception("پاسخ هوش مصنوعی خالی است");
            }
        } catch (Exception $e) {
            faraz_auto_report_log("خطا در فرآیند ایجاد گزارش: " . $e->getMessage(), 'error');
            $error_message = $e->getMessage();
        }
    }
    
    // ذخیره تنظیمات
    if (isset($_POST['save_settings'])) {
        // افزودن نانس برای امنیت
        check_admin_referer('faraz_auto_report_settings_form', 'faraz_auto_report_settings_nonce');
        
        $report_prefix = sanitize_text_field($_POST['report_prefix']);
        $report_model = sanitize_text_field($_POST['report_model']);
        $report_template = sanitize_textarea_field($_POST['report_template']);
        $default_category = intval($_POST['default_category']);
        
        update_option('faraz_auto_report_prefix', $report_prefix);
        update_option('faraz_auto_report_model', $report_model);
        update_option('faraz_auto_report_template', $report_template);
        update_option('faraz_auto_report_default_category', $default_category);
        
        // پیام موفقیت
        $settings_success_message = 'تنظیمات با موفقیت ذخیره شد.';
    }
    
    // دریافت لیست گزارش‌های تولید شده
    $reports_query = new WP_Query(array(
        'post_type' => 'post',
        'post_status' => array('draft', 'publish'),
        'meta_key' => 'faraz_auto_report',
        'meta_value' => 'yes',
        'posts_per_page' => 10,
        'orderby' => 'date',
        'order' => 'DESC'
    ));
    
    // نمایش صفحه
    ?>
    <div class="wrap">
        <h1>گزارش نویسی خودکار</h1>
        
        <div class="nav-tab-wrapper">
            <a href="?page=faraz-auto-report" class="nav-tab <?php echo (!isset($_GET['tab']) || $_GET['tab'] === 'new') ? 'nav-tab-active' : ''; ?>">گزارش جدید</a>
            <a href="?page=faraz-auto-report&tab=reports" class="nav-tab <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'reports') ? 'nav-tab-active' : ''; ?>">گزارش‌های ثبت شده</a>
            <a href="?page=faraz-auto-report&tab=settings" class="nav-tab <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'settings') ? 'nav-tab-active' : ''; ?>">تنظیمات</a>
            <a href="?page=faraz-auto-report&tab=logs" class="nav-tab <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'logs') ? 'nav-tab-active' : ''; ?>">لاگ‌ها</a>
        </div>
        
        <?php if (!isset($_GET['tab']) || $_GET['tab'] === 'new'): ?>
            <!-- فرم ایجاد گزارش جدید -->
            <div class="auto-report-form-container">
                <?php if (isset($success_message)): ?>
                    <div class="notice notice-success is-dismissible">
                        <p><?php echo $success_message; ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                    <div class="notice notice-error is-dismissible">
                        <p><strong>خطا:</strong> <?php echo $error_message; ?></p>
                        <p>اطلاعات بیشتر در فایل لاگ <code>auto-report-debug.log</code> ثبت شده است.</p>
                    </div>
                <?php endif; ?>
                
                <div class="auto-report-guide">
                    <h3>راهنمای گزارش نویسی خودکار</h3>
                    <p>با استفاده از این ابزار می‌توانید گزارش‌های سازمانی و خبری را به صورت خودکار تولید کنید. مراحل کار به شرح زیر است:</p>
                    <ol>
                        <li><strong>موضوع گزارش</strong> را وارد کنید - مثال: "افتتاح ساختمان جدید شرکت"</li>
                        <li><strong>توضیحات کوتاه</strong> را وارد کنید - مثال: "مراسم افتتاحیه ساختمان جدید شرکت با حضور مدیرعامل و اعضای هیئت مدیره برگزار شد. این ساختمان با مساحت 5000 متر مربع در منطقه شمال شهر واقع شده است."</li>
                        <li>یک <strong>دسته‌بندی</strong> برای گزارش انتخاب کنید</li>
                        <li>در صورت نیاز، <strong>تصاویر</strong> مرتبط با گزارش را از کتابخانه رسانه انتخاب کنید یا مستقیماً آپلود نمایید</li>
                        <li>بر روی دکمه <strong>ایجاد گزارش</strong> کلیک کنید</li>
                    </ol>
                    <p>سیستم به صورت خودکار یک گزارش کامل با استفاده از هوش مصنوعی تولید می‌کند و آن را به عنوان پیش‌نویس ذخیره می‌کند.</p>
                </div>
                
                <form method="post" enctype="multipart/form-data">
                    <?php 
                    // اضافه کردن Media Uploader وردپرس
                    wp_enqueue_media();
                    
                    // اضافه کردن کد جاوااسکریپت برای مدیریت انتخاب تصاویر
                    ?>
                    <script type="text/javascript">
                    jQuery(document).ready(function($) {
                        var selectedImages = [];
                        
                        if ($('#selected_images').val()) {
                            selectedImages = $('#selected_images').val().split(',').map(Number);
                        }

                        function updateSelectedImagesInput() {
                            $('#selected_images').val(selectedImages.join(','));
                        }

                        function addImageToPreview(attachment) {
                            if (!selectedImages.includes(attachment.id)) {
                                selectedImages.push(attachment.id);
                                
                                $('#selected_images_preview').append(
                                    '<div class="selected-image-item" data-id="' + attachment.id + '">' +
                                    '<img src="' + (attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url) + '" alt="">' +
                                    '<span class="remove-image" title="حذف">×</span>' +
                                    '</div>'
                                );
                                updateSelectedImagesInput();
                            }
                        }
                        
                        $('#select_media_button').click(function(e) {
                            e.preventDefault();
                            
                            var mediaUploader = wp.media({
                                title: 'انتخاب تصاویر گزارش',
                                button: { text: 'انتخاب تصاویر' },
                                multiple: true,
                                library: { type: 'image' }
                            });
                            
                            mediaUploader.on('select', function() {
                                var attachments = mediaUploader.state().get('selection').toJSON();
                                attachments.forEach(addImageToPreview);
                            });
                            
                            mediaUploader.open();
                        });
                        
                        $(document).on('click', '.remove-image', function() {
                            var imageItem = $(this).parent('.selected-image-item');
                            var imageId = imageItem.data('id');
                            
                            selectedImages = selectedImages.filter(id => id != imageId);
                            imageItem.remove();
                            updateSelectedImagesInput();
                        });

                        // Unsplash Modal Logic
                        $('#unsplash-search-modal-button').click(function() {
                            $('#unsplash-modal').show();
                            $('#unsplash-modal-search-keyword').val($('#report_subject').val());
                        });

                        $('.close-unsplash-modal').click(function() {
                            $('#unsplash-modal').hide();
                        });

                        $('#unsplash-modal-search-button').click(function() {
                            const keyword = $('#unsplash-modal-search-keyword').val();
                            const resultsContainer = $('#unsplash-modal-results');
                            const spinner = $('#unsplash-modal .spinner');

                            spinner.css('visibility', 'visible');
                            resultsContainer.html('');

                            $.ajax({
                                url: "<?php echo admin_url('admin-ajax.php'); ?>",
                                type: 'POST',
                                data: {
                                    action: 'faraz_unsplash_search_images',
                                    nonce: "<?php echo wp_create_nonce('faraz_unsplash_search'); ?>",
                                    keyword: keyword
                                },
                                success: function(response) {
                                    if (response.success) {
                                        response.data.forEach(function(image) {
                                            const img = $('<img>').attr('src', image.urls.thumb).data('full_url', image.urls.regular).data('alt', image.alt_description || keyword);
                                            img.click(function() {
                                                spinner.css('visibility', 'visible');
                                                $.ajax({
                                                    url: "<?php echo admin_url('admin-ajax.php'); ?>",
                                                    type: 'POST',
                                                    data: {
                                                        action: 'faraz_unsplash_sideload_image',
                                                        nonce: "<?php echo wp_create_nonce('faraz_unsplash_sideload'); ?>",
                                                        image_url: $(this).data('full_url'),
                                                        alt_text: $(this).data('alt')
                                                    },
                                                    success: function(sideloadResponse) {
                                                        if (sideloadResponse.success) {
                                                            addImageToPreview(sideloadResponse.data);
                                                            $('#unsplash-modal').hide();
                                                        } else {
                                                            alert('خطا در افزودن تصویر: ' + sideloadResponse.data.message);
                                                        }
                                                    },
                                                    complete: function() {
                                                        spinner.css('visibility', 'hidden');
                                                    }
                                                });
                                            });
                                            resultsContainer.append(img);
                                        });
                                    } else {
                                        resultsContainer.html('<p>' + response.data.message + '</p>');
                                    }
                                },
                                complete: function() {
                                    spinner.css('visibility', 'hidden');
                                }
                            });
                        });
                    });
                    </script>
                    
                    <?php wp_nonce_field('faraz_auto_report_form', 'faraz_auto_report_nonce'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="report_subject">موضوع گزارش</label></th>
                            <td>
                                <input type="text" name="report_subject" id="report_subject" class="regular-text" required>
                                <p class="description">موضوع اصلی گزارش را وارد کنید.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="report_description">توضیحات کوتاه</label></th>
                            <td>
                                <textarea name="report_description" id="report_description" class="large-text" rows="5"></textarea>
                                <p class="description">توضیحات کوتاهی در مورد گزارش بنویسید. هوش مصنوعی از این توضیحات برای تولید گزارش کامل استفاده خواهد کرد.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="report_category">دسته‌بندی</label></th>
                            <td>
                                <?php
                                wp_dropdown_categories(array(
                                    'name' => 'report_category',
                                    'id' => 'report_category',
                                    'selected' => $default_category,
                                    'show_option_none' => 'انتخاب دسته‌بندی',
                                    'option_none_value' => '0',
                                    'hide_empty' => 0
                                ));
                                ?>
                                <p class="description">دسته‌بندی گزارش را انتخاب کنید.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="report_images">تصاویر گزارش</label></th>
                            <td>
                                <div class="media-selection-container">
                                    <input type="hidden" name="selected_images" id="selected_images" value="">
                                    <button type="button" id="select_media_button" class="button">انتخاب از کتابخانه رسانه</button>
                                    <span class="or-separator">یا</span>
                                    <button type="button" id="unsplash-search-modal-button" class="button">جستجو در Unsplash</button>
                                    <span class="or-separator">یا</span>
                                    <input type="file" name="report_images[]" id="report_images" multiple accept="image/*">
                                    
                                    <div id="selected_images_preview" class="selected-images-preview"></div>
                                    <p class="description">می‌توانید تصاویر را از کتابخانه رسانه، Unsplash، یا مستقیماً آپلود نمایید. اولین تصویر به عنوان تصویر شاخص استفاده خواهد شد.</p>
                                </div>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" name="submit_report" class="button button-primary" value="ایجاد گزارش">
                    </p>
                </form>

                <!-- Unsplash Modal -->
                <div id="unsplash-modal" class="unsplash-modal-container" style="display:none;">
                    <div class="unsplash-modal-content">
                        <span class="close-unsplash-modal">&times;</span>
                        <h2>جستجو در Unsplash</h2>
                        <div class="unsplash-modal-search-bar">
                            <input type="text" id="unsplash-modal-search-keyword" class="regular-text">
                            <button type="button" id="unsplash-modal-search-button" class="button button-primary">جستجو</button>
                            <span class="spinner"></span>
                        </div>
                        <div id="unsplash-modal-results" class="unsplash-modal-results-grid"></div>
                    </div>
                </div>

            </div>
            
        <?php elseif (isset($_GET['tab']) && $_GET['tab'] === 'reports'): ?>
            <!-- نمایش گزارش‌های ثبت شده -->
            <div class="auto-report-list-container">
                <h2>گزارش‌های ثبت شده</h2>
                
                <?php if ($reports_query->have_posts()): ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>عنوان</th>
                                <th>تاریخ</th>
                                <th>وضعیت</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($reports_query->have_posts()): $reports_query->the_post(); ?>
                                <tr>
                                    <td><?php the_title(); ?></td>
                                    <td><?php echo get_the_date(); ?></td>
                                    <td><?php echo (get_post_status() == 'publish') ? 'منتشر شده' : 'پیش‌نویس'; ?></td>
                                    <td>
                                        <a href="<?php echo get_edit_post_link(); ?>" class="button button-small">ویرایش</a>
                                        <a href="<?php echo get_permalink(); ?>" class="button button-small" target="_blank">نمایش</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>هیچ گزارشی ثبت نشده است.</p>
                <?php endif; ?>
                
                <?php wp_reset_postdata(); ?>
            </div>
            
        <?php elseif (isset($_GET['tab']) && $_GET['tab'] === 'settings'): ?>
            <!-- فرم تنظیمات -->
            <div class="auto-report-settings-container">
                <?php if (isset($settings_success_message)): ?>
                    <div class="notice notice-success is-dismissible">
                        <p><?php echo $settings_success_message; ?></p>
                    </div>
                <?php endif; ?>
                
                <form method="post">
                    <?php wp_nonce_field('faraz_auto_report_settings_form', 'faraz_auto_report_settings_nonce'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="report_prefix">پیشوند عنوان گزارش</label></th>
                            <td>
                                <input type="text" name="report_prefix" id="report_prefix" class="regular-text" value="<?php echo esc_attr($report_prefix); ?>">
                                <p class="description">این متن قبل از عنوان گزارش قرار می‌گیرد. مثلا: "گزارش روابط عمومی: "</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="report_model">مدل هوش مصنوعی</label></th>
                            <td>
                                <select name="report_model" id="report_model">
                                    <option value="gpt-4o" <?php selected($report_model, 'gpt-4o'); ?>>GPT-4o - مدل پیشرفته OpenAI با قابلیت‌های چندمنظوره</option>
                                    <option value="claude-3-7-sonnet-20250219" <?php selected($report_model, 'claude-3-7-sonnet-20250219'); ?>>Claude 3.7 Sonnet - مدل متعادل Anthropic با کارایی بالا</option>
                                    <option value="gemini-2.0-flash" <?php selected($report_model, 'gemini-2.0-flash'); ?>>Gemini 2.0 Flash - مدل ارزان و به‌صرفه Google با کارایی بالا</option>
                                    <option value="deepseek-chat" <?php selected($report_model, 'deepseek-chat'); ?>>DeepSeek Chat - مدل چت هوشمند DeepSeek با تمرکز بر مکالمات طبیعی</option>
                                </select>
                                <p class="description">مدل هوش مصنوعی برای تولید گزارش را انتخاب کنید.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="default_category">دسته‌بندی پیش‌فرض</label></th>
                            <td>
                                <?php
                                wp_dropdown_categories(array(
                                    'name' => 'default_category',
                                    'id' => 'default_category',
                                    'selected' => $default_category,
                                    'show_option_none' => 'بدون دسته‌بندی',
                                    'option_none_value' => '0',
                                    'hide_empty' => 0
                                ));
                                ?>
                                <p class="description">دسته‌بندی پیش‌فرض برای گزارش‌های جدید را انتخاب کنید.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="report_template">قالب پرامپت</label></th>
                            <td>
                                <textarea name="report_template" id="report_template" class="large-text" rows="10"><?php echo esc_textarea($report_template); ?></textarea>
                                <p class="description">
                                    قالب پرامپت برای هوش مصنوعی. از [SUBJECT] برای موضوع و [DESCRIPTION] برای توضیحات استفاده کنید.
                                </p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" name="save_settings" class="button button-primary" value="ذخیره تنظیمات">
                    </p>
                </form>
                
                <!-- بخش تست اتصال به هوش مصنوعی -->
                <div class="ai-connection-test">
                    <h3>تست اتصال به هوش مصنوعی</h3>
                    <p>با استفاده از دکمه زیر می‌توانید اتصال به API هوش مصنوعی را تست کنید.</p>
                    
                    <div class="notice notice-info">
                        <p><strong>نکته مهم:</strong> خطای "در گروه فعلی کانال در دسترسی برای مدل X وجود ندارد" به این معنی است که مدل انتخاب شده در دسترس نیست. لطفاً مدل دیگری انتخاب کنید.</p>
                        <p>توصیه می‌شود از مدل‌های زیر استفاده کنید:</p>
                        <ul>
                            <li><strong>gpt-4o</strong> - برای نتایج با کیفیت بالا</li>
                            <li><strong>gemini-2.0-flash</strong> - برای سرعت بیشتر</li>
                        </ul>
                        <p>کلید API فعلی: <code><?php echo esc_html(get_option('smart_admin_api_key', '(تنظیم نشده)')); ?></code></p>
                    </div>
                    
                    <?php
                    // تست اتصال به API
                    if (isset($_POST['test_ai_connection'])) {
                        // افزودن نانس برای امنیت
                        check_admin_referer('faraz_auto_report_test_ai', 'faraz_auto_report_test_nonce');
                        
                        $api_key = get_option('smart_admin_api_key', '');
                        $test_model = isset($_POST['test_model']) ? sanitize_text_field($_POST['test_model']) : 'gemini-2.0-flash';
                        
                        if (empty($api_key)) {
                            echo '<div class="notice notice-error"><p>کلید API تنظیم نشده است. لطفاً ابتدا در تنظیمات <a href="' . admin_url('admin.php?page=smart-admin') . '">ادمین هوشمند</a> کلید API را تنظیم کنید.</p></div>';
                        } else {
                            // تست ارسال یک درخواست ساده
                            $test_prompt = "لطفاً پاسخ دهید: آیا اتصال به API برقرار است؟ (پاسخ کوتاه)";
                            
                            // استفاده از تابع واحد برای همه مدل‌ها
                            faraz_auto_report_log("تست اتصال: استفاده از API استاندارد برای مدل {$test_model}");
                            $response = send_to_gemini_api($test_prompt, $test_model, $api_key);
                            
                            if (isset($response['error'])) {
                                echo '<div class="notice notice-error"><p>خطا در اتصال به هوش مصنوعی: ' . esc_html($response['error']) . '</p>';
                                echo '<p>لطفاً از یک مدل دیگر استفاده کنید. مدل فعلی: ' . esc_html($test_model) . '</p></div>';
                            } else {
                                echo '<div class="notice notice-success"><p>اتصال به هوش مصنوعی با موفقیت برقرار شد.</p>';
                                echo '<p><strong>مدل:</strong> ' . esc_html($test_model) . '</p>';
                                echo '<p><strong>پاسخ دریافتی:</strong> ' . esc_html(substr($response['content'], 0, 100)) . '...</p></div>';
                                
                                // ذخیره مدل موفق به عنوان مدل پیش‌فرض
                                update_option('faraz_auto_report_model', $test_model);
                                echo '<div class="notice notice-info"><p>مدل ' . esc_html($test_model) . ' به عنوان مدل پیش‌فرض برای گزارش‌های خودکار تنظیم شد.</p></div>';
                            }
                        }
                    }
                    ?>
                    
                    <form method="post">
                        <?php wp_nonce_field('faraz_auto_report_test_ai', 'faraz_auto_report_test_nonce'); ?>
                        <p>
                            <label for="test_model">مدل برای تست: </label>
                            <select name="test_model" id="test_model">
                                <option value="gpt-4o">GPT-4o - مدل پیشرفته OpenAI</option>
                                <option value="claude-3-7-sonnet-20250219">Claude 3.7 Sonnet - مدل Anthropic</option>
                                <option value="gemini-2.0-flash" selected>Gemini 2.0 Flash - مدل Google (توصیه شده)</option>
                                <option value="deepseek-chat">DeepSeek Chat - مدل DeepSeek</option>
                            </select>
                            <input type="submit" name="test_ai_connection" class="button button-secondary" value="تست اتصال به هوش مصنوعی">
                        </p>
                    </form>
                </div>
            </div>
        <?php elseif (isset($_GET['tab']) && $_GET['tab'] === 'logs'): ?>
            <!-- نمایش لاگ‌ها -->
            <div class="auto-report-logs-container">
                <h2>لاگ‌ها</h2>
                
                <?php
                // دریافت لیست فایل‌های لاگ
                $log_file = plugin_dir_path(__FILE__) . 'auto-report-debug.log';
                
                // پاک کردن لاگ‌ها در صورت درخواست
                if (isset($_GET['clear_logs']) && $_GET['clear_logs'] == 1) {
                    if (file_exists($log_file)) {
                        unlink($log_file);
                        echo '<div class="notice notice-success is-dismissible"><p>فایل لاگ با موفقیت پاک شد.</p></div>';
                    }
                }
                ?>
                
                <?php if (file_exists($log_file)): ?>
                    <div class="log-actions">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=faraz-auto-report&tab=logs&clear_logs=1')); ?>" class="button button-secondary" onclick="return confirm('آیا از پاک کردن فایل لاگ اطمینان دارید؟');">پاک کردن لاگ‌ها</a>
                    </div>
                    
                    <div class="log-content">
                        <h3>محتوای فایل لاگ</h3>
                        <div class="log-viewer">
                            <pre><?php 
                                $log_content = file_get_contents($log_file);
                                // نمایش معکوس لاگ‌ها (جدیدترین لاگ‌ها در بالا)
                                $log_lines = array_reverse(explode(PHP_EOL, $log_content));
                                // محدود کردن تعداد خطوط لاگ به 1000 خط
                                $log_lines = array_slice($log_lines, 0, 1000);
                                
                                foreach ($log_lines as $line) {
                                    if (!empty($line)) {
                                        // رنگ‌بندی خطاها
                                        if (strpos($line, '[error]') !== false) {
                                            echo '<span class="log-error">' . esc_html($line) . '</span>' . PHP_EOL;
                                        } else {
                                            echo esc_html($line) . PHP_EOL;
                                        }
                                    }
                                }
                            ?></pre>
                        </div>
                    </div>
                <?php else: ?>
                    <p>هیچ فایل لاگی ثبت نشده است.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <style>
        .auto-report-form-container,
        .auto-report-settings-container,
        .auto-report-list-container {
            background: #fff;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .nav-tab-wrapper {
            margin-bottom: 20px;
        }
        
        .wp-list-table {
            margin-top: 20px;
        }
        
        .ai-connection-test {
            background: #f8f8f8;
            padding: 20px;
            border-radius: 5px;
            margin-top: 30px;
            border-top: 3px solid #3498db;
        }
        
        .ai-connection-test h3 {
            margin-top: 0;
            color: #2c3e50;
        }
        
        .auto-report-guide {
            background: #f0f8ff;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 30px;
            border-right: 4px solid #3498db;
        }
        
        .auto-report-guide h3 {
            margin-top: 0;
            color: #2c3e50;
        }
        
        .auto-report-guide ol {
            margin-right: 20px;
        }
        
        .auto-report-guide li {
            margin-bottom: 10px;
        }
        
        .auto-report-logs-container {
            background: #fff;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .log-actions {
            margin-bottom: 20px;
        }
        
        .log-viewer {
            background: #f5f5f5;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            padding: 15px;
            overflow: auto;
            max-height: 500px;
            direction: ltr;
            text-align: left;
            font-family: monospace;
            font-size: 12px;
            line-height: 1.5;
        }
        
        .log-viewer pre {
            margin: 0;
            white-space: pre-wrap;
        }
        
        .log-error {
            color: #e74c3c;
            font-weight: bold;
        }
        
        .media-selection-container {
            margin-bottom: 15px;
        }
        
        .or-separator {
            margin: 0 10px;
        }
        
        .selected-images-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }
        
        .selected-image-item {
            position: relative;
            width: 100px;
            height: 100px;
            border: 1px solid #ddd;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .selected-image-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .remove-image {
            position: absolute;
            top: 3px;
            right: 3px;
            background: rgba(255, 0, 0, 0.7);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            text-align: center;
            line-height: 20px;
            cursor: pointer;
            font-size: 12px;
        }

        /* Unsplash Modal Styles */
        .unsplash-modal-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .unsplash-modal-content {
            background: #fff;
            padding: 20px;
            border-radius: 5px;
            width: 80%;
            max-width: 900px;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
        }
        .close-unsplash-modal {
            position: absolute;
            top: 10px;
            right: 20px;
            font-size: 28px;
            cursor: pointer;
        }
        .unsplash-modal-results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 20px;
        }
        .unsplash-modal-results-grid img {
            width: 100%;
            height: auto;
            cursor: pointer;
            border: 2px solid transparent;
        }
        .unsplash-modal-results-grid img:hover {
            border-color: #007cba;
        }
        .unsplash-modal-search-bar .spinner {
             visibility: hidden;
        }

    </style>
    <?php
}

// تابع ارسال درخواست به API Gemini
function send_to_gemini_api($prompt, $model, $api_key) {
    if (empty($prompt)) {
        faraz_auto_report_log("خطا: پرامپت خالی ارسال شده است", 'error');
        return array('error' => 'پرامپت خالی ارسال شده است');
    }
    
    $url = 'https://api.gapgpt.app/v1/chat/completions';
    
    faraz_auto_report_log("استفاده از URL استاندارد برای تمام مدل‌ها: " . $url);
    
    $body = array(
        'model' => $model,
        'messages' => array(
            array(
                'role' => 'user',
                'content' => $prompt
            )
        )
    );
    
    $args = array(
        'body' => json_encode($body),
        'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key
        ),
        'timeout' => 60,
        'method' => 'POST'
    );
    
    faraz_auto_report_log("ارسال درخواست به API با URL: " . $url);
    faraz_auto_report_log("ساختار درخواست: " . json_encode($body));
    
    $response = wp_remote_post($url, $args);
    
    if (is_wp_error($response)) {
        faraz_auto_report_log("خطای WP در درخواست: " . $response->get_error_message(), 'error');
        return array('error' => $response->get_error_message());
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = json_decode(wp_remote_retrieve_body($response), true);
    
    faraz_auto_report_log("کد پاسخ API: " . $response_code);
    faraz_auto_report_log("پاسخ API: " . json_encode($response_body));
    
    if ($response_code !== 200) {
        $error_message = isset($response_body['error']['message']) ? $response_body['error']['message'] : 'خطای نامشخص';
        faraz_auto_report_log("خطا در پاسخ API: " . $error_message, 'error');
        return array('error' => $error_message);
    }
    
    if (isset($response_body['choices'][0]['message']['content'])) {
        $content = $response_body['choices'][0]['message']['content'];
        
        faraz_auto_report_log("محتوای دریافتی از API با موفقیت استخراج شد");
        
        return array(
            'content' => $content,
            'keywords' => array() // در اینجا استخراج کلمات کلیدی انجام نمی‌شود
        );
    } else {
        faraz_auto_report_log("خطا در استخراج محتوا از پاسخ API", 'error');
        return array('error' => 'خطا در دریافت پاسخ از API');
    }
}
// AJAX handler for sideloading Unsplash image
add_action('wp_ajax_faraz_unsplash_sideload_image', 'faraz_unsplash_sideload_image_callback');
function faraz_unsplash_sideload_image_callback() {
    check_ajax_referer('faraz_unsplash_sideload', 'nonce');

    $image_url = isset($_POST['image_url']) ? esc_url_raw($_POST['image_url']) : '';
    $alt_text = isset($_POST['alt_text']) ? sanitize_text_field($_POST['alt_text']) : '';

    if (empty($image_url)) {
        wp_send_json_error(['message' => 'URL تصویر نامعتبر است.']);
    }

    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');

    // '0' for post_id means the attachment is not attached to any post yet.
    $attachment_id = media_sideload_image($image_url, 0, $alt_text, 'id');

    if (is_wp_error($attachment_id)) {
        wp_send_json_error(['message' => 'خطا در دانلود تصویر: ' . $attachment_id->get_error_message()]);
    }

    // Prepare data to send back to the client, simulating a media library attachment object
    $attachment = [
        'id' => $attachment_id,
        'url' => wp_get_attachment_url($attachment_id),
        'sizes' => [
            'thumbnail' => [
                'url' => wp_get_attachment_image_url($attachment_id, 'thumbnail')
            ]
        ]
    ];

    wp_send_json_success($attachment);
}
