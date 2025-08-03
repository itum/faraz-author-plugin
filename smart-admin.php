<?php
// افزودن منوی ادمین هوشمند به افزونه
add_action('admin_menu', 'smart_admin_add_menu');

// وارد کردن فایل قالب‌های پرامپت
require_once plugin_dir_path(__FILE__) . 'smart-admin-templates.php';

// وارد کردن فایل تنظیمات لحن انسانی
require_once plugin_dir_path(__FILE__) . 'smart-admin-human-tone.php';

// وارد کردن فایل یکپارچه‌سازی با Rank Math SEO
require_once plugin_dir_path(__FILE__) . 'smart-admin-rank-math-seo.php';

// وارد کردن فایل زمان‌بندی محتوا
require_once plugin_dir_path(__FILE__) . 'smart-admin-scheduler.php';

// وارد کردن فایل تنظیمات
require_once plugin_dir_path(__FILE__) . 'smart-admin-settings.php';

// ریدایرکت مسیر smart-admin در wp-admin به آدرس صحیح
add_action('admin_init', 'smart_admin_redirect');

// افزودن اعلان در پنل مدیریت
add_action('admin_notices', 'smart_admin_notice');

function smart_admin_redirect() {
    global $pagenow;
    
    // بررسی آیا آدرس مورد نظر است
    if ($pagenow == 'admin.php' && isset($_GET['page']) && $_GET['page'] == 'smart-admin') {
        // آدرس صحیح است، نیازی به ریدایرکت نیست
        return;
    }
    
    // اگر کاربر به مسیر wp-admin/smart-admin وارد شده، ریدایرکت به آدرس صحیح
    $current_url = $_SERVER['REQUEST_URI'];
    if (strpos($current_url, '/wp-admin/smart-admin') !== false) {
        wp_redirect(admin_url('admin.php?page=smart-admin'));
        exit;
    }
}

function smart_admin_add_menu()
{
    // حذف اکشن قبلی برای جلوگیری از تداخل
    remove_action('admin_menu', 'smart_admin_add_menu');
    
    // افزودن منوی اصلی جدید با آیکون
    add_menu_page(
        'ادمین هوشمند', // عنوان صفحه
        'ادمین هوشمند', // عنوان منو
        'manage_options', // مجوز دسترسی
        'smart-admin', // slug صفحه
        'smart_admin_page', // تابع نمایش صفحه
        'dashicons-superhero', // آیکون
        65 // موقعیت منو
    );
    
    // اضافه کردن زیرمنوی تنظیمات
    add_submenu_page(
        'smart-admin', // منوی والد
        'تنظیمات ادمین هوشمند', // عنوان صفحه
        'تنظیمات', // عنوان منو
        'manage_options', // مجوز دسترسی
        'smart-admin-settings', // slug صفحه
        'smart_admin_metabox_settings' // تابع نمایش صفحه
    );
    
    // همچنین اضافه کردن به زیرمنو
    add_submenu_page(
        'faraz-telegram-plugin', // منوی والد
        'ادمین هوشمند', // عنوان صفحه
        'ادمین هوشمند', // عنوان منو
        'manage_options', // مجوز دسترسی
        'smart-admin', // slug صفحه (باید با منوی اصلی یکسان باشد)
        'smart_admin_page' // تابع نمایش صفحه
    );
}

// افزودن تنظیمات
function smart_admin_register_settings() {
    register_setting('smart_admin_settings', 'smart_admin_api_key');
    register_setting('smart_admin_settings', 'smart_admin_model');
}
add_action('admin_init', 'smart_admin_register_settings');

// صفحه ادمین هوشمند
function smart_admin_page() {
    // بررسی مجوز دسترسی
    if (!current_user_can('manage_options')) {
        wp_die(__('شما اجازه دسترسی به این صفحه را ندارید.'));
    }
    
    // پیام موفقیت برای نمایش
    $success_message = '';
    
    // ذخیره پرامپت و درخواست به API
    if (isset($_POST['smart_admin_prompt']) && !empty($_POST['smart_admin_prompt'])) {
        // افزودن نانس برای امنیت
        check_admin_referer('smart_admin_prompt_action', 'smart_admin_nonce');
        
        $prompt = sanitize_textarea_field($_POST['smart_admin_prompt']);
        $model = sanitize_text_field($_POST['smart_admin_model']);
        $api_key = get_option('smart_admin_api_key');
        
        // اضافه کردن لحن انسانی به پرامپت اگر انتخاب شده باشد
        if (isset($_POST['use_human_tone']) && $_POST['use_human_tone'] == '1') {
            $prompt = optimize_prompt_for_natural_content($prompt);
        }
        
        // ارسال درخواست به API
        $response = send_to_gapgpt_api($prompt, $model, $api_key);
        
        // بهبود خروجی هوش مصنوعی با لحن انسانی
        if (isset($response['content']) && !empty($response['content']) && isset($_POST['use_human_tone']) && $_POST['use_human_tone'] == '1') {
            $response['content'] = improve_ai_output_with_human_tone($response['content']);
        }
        
        // ذخیره پرامپت در تنظیمات (اگر قالب پیش‌فرض نباشد)
        if (!isset($_POST['is_template']) || $_POST['is_template'] != '1') {
            $saved_prompts = get_option('smart_admin_saved_prompts', array());
            $saved_prompts[] = array(
                'prompt' => $prompt,
                'model' => $model,
                'date' => current_time('mysql'),
                'is_template' => false
            );
            update_option('smart_admin_saved_prompts', $saved_prompts);
        }
    }
    
    // ذخیره پاسخ هوش مصنوعی به عنوان پیش‌نویس در وردپرس
    if (isset($_POST['save_as_draft']) && isset($_POST['ai_response']) && !empty($_POST['ai_response'])) {
        // افزودن نانس برای امنیت
        check_admin_referer('smart_admin_save_draft_action', 'smart_admin_save_draft_nonce');
        
        $title = sanitize_text_field($_POST['post_title']);
        $content = wp_kses_post($_POST['ai_response']);
        
        // استخراج کلمات کلیدی از فرم یا استخراج خودکار
        $keywords = array();
        if (!empty($_POST['post_keywords'])) {
            $keywords = explode(',', sanitize_text_field($_POST['post_keywords']));
            $keywords = array_map('trim', $keywords);
        } elseif (function_exists('smart_admin_extract_keywords_from_ai_response')) {
            $keywords = smart_admin_extract_keywords_from_ai_response($content);
        }
        
        // ذخیره محتوا به عنوان پیش‌نویس
        $post_id = smart_admin_save_ai_content_as_draft($title, $content, $keywords);
        
        if (!is_wp_error($post_id)) {
            $success_message = 'محتوا با موفقیت به عنوان پیش‌نویس ذخیره شد. <a href="' . get_edit_post_link($post_id) . '" target="_blank">مشاهده و ویرایش</a>';
        }
    }
    
    // حذف پرامپت ذخیره شده
    if (isset($_GET['delete_prompt']) && is_numeric($_GET['delete_prompt'])) {
        // افزودن نانس برای امنیت
        check_admin_referer('smart_admin_delete_prompt_action', 'smart_admin_delete_nonce');
        
        $prompt_index = intval($_GET['delete_prompt']);
        $saved_prompts = get_option('smart_admin_saved_prompts', array());
        
        if (isset($saved_prompts[$prompt_index])) {
            unset($saved_prompts[$prompt_index]);
            $saved_prompts = array_values($saved_prompts); // بازسازی شاخص‌ها
            update_option('smart_admin_saved_prompts', $saved_prompts);
            
            // ریدایرکت برای جلوگیری از ارسال مجدد فرم
            wp_redirect(add_query_arg(array('page' => 'smart-admin'), admin_url('admin.php')));
            exit;
        }
    }
    
    // نمایش صفحه
    ?>
    <style>
        .smart-admin-wrap {
            font-family: 'IRANSans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
            max-width: 1200px;
            margin: 40px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            direction: rtl;
        }
        
        .smart-admin-wrap h2 {
            color: #2c3e50;
            font-size: 2em;
            margin-bottom: 30px;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
            display: inline-block;
        }
        
        .smart-admin-tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        
        .smart-admin-tabs a {
            padding: 10px 20px;
            text-decoration: none;
            color: #666;
            font-weight: 500;
        }
        
        .smart-admin-tabs a.active {
            border-bottom: 3px solid #3498db;
            color: #3498db;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .settings-form, .prompt-form {
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
            font-weight: 500;
        }
        
        .form-group input[type="text"],
        .form-group select,
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
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        .form-group textarea {
            min-height: 200px;
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
        
        .response-container {
            margin-top: 30px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 6px;
            border-right: 4px solid #3498db;
        }
        
        .saved-prompts {
            margin-top: 30px;
        }
        
        .saved-prompts h3 {
            margin-bottom: 15px;
            color: #2c3e50;
        }
        
        .prompt-card {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 15px;
            border-right: 3px solid #3498db;
        }
        
        .prompt-card-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .prompt-card-model {
            font-size: 12px;
            background: #e0e0e0;
            padding: 3px 8px;
            border-radius: 4px;
        }
        
        .prompt-card-date {
            font-size: 12px;
            color: #777;
        }
        
        .prompt-card-content {
            margin-bottom: 10px;
        }
        
        .prompt-card-actions a {
            text-decoration: none;
            color: #3498db;
            margin-left: 15px;
            font-size: 13px;
        }
        
        .prompt-card-actions a:hover {
            text-decoration: underline;
        }
        
        .loading-spinner {
            display: none;
            margin-right: 10px;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(0, 0, 0, 0.1);
            border-radius: 50%;
            border-top-color: #3498db;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .response-container.error {
            border-right-color: #e74c3c;
        }
        
        .templates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .template-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            background: #f9f9f9;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .template-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .template-card h3 {
            margin-top: 0;
            color: #2c3e50;
            font-size: 18px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 8px;
        }
        
        .template-card-model {
            display: inline-block;
            font-size: 12px;
            background: #e0e0e0;
            padding: 3px 8px;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        
        .template-card-preview {
            max-height: 100px;
            overflow: hidden;
            position: relative;
            margin-bottom: 15px;
            font-size: 13px;
            color: #666;
        }
        
        .template-card-preview::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 50px;
            background: linear-gradient(transparent, #f9f9f9);
        }
        
        .template-card-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
        }
        
        .template-card-actions button {
            background: #3498db;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            transition: background 0.2s;
        }
        
        .template-card-actions button:hover {
            background: #2980b9;
        }
        
        .tab-selector {
            display: flex;
            margin-bottom: 20px;
        }
        
        .tab-selector a {
            padding: 8px 15px;
            background: #f0f0f0;
            color: #666;
            text-decoration: none;
            margin-left: 10px;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .tab-selector a.active {
            background: #3498db;
            color: white;
        }
        
        .prompt-card.template {
            border-right-color: #27ae60;
        }
        
        .prompt-card-title {
            font-weight: bold;
            margin-bottom: 5px;
            color: #2c3e50;
        }
        
        .prompt-template-tag {
            display: inline-block;
            background: #27ae60;
            color: white;
            font-size: 11px;
            padding: 2px 6px;
            border-radius: 3px;
            margin-right: 5px;
        }
        
        .save-draft-form {
            margin-top: 20px;
            background: #f0f8ff;
            padding: 20px;
            border-radius: 6px;
            border-right: 4px solid #27ae60;
        }
        
        .save-draft-form h3 {
            margin-top: 0;
            color: #2c3e50;
        }
        
        .save-draft-form .form-row {
            margin-bottom: 15px;
        }
        
        .save-draft-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .save-draft-form input[type="text"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .ai-drafts-wrap {
            margin-top: 30px;
        }
        
        .ai-drafts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .ai-draft-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            background: #f9f9f9;
            position: relative;
        }
        
        .ai-draft-card h4 {
            margin-top: 0;
            margin-bottom: 10px;
            color: #2c3e50;
            font-size: 16px;
        }
        
        .ai-draft-card-date {
            color: #777;
            font-size: 12px;
            margin-bottom: 10px;
        }
        
        .ai-draft-card-actions {
            margin-top: 15px;
            display: flex;
        }
        
        .ai-draft-card-actions a {
            background: #3498db;
            color: white;
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 13px;
            margin-left: 10px;
        }
        
        .ai-draft-card-actions a.view {
            background: #3498db;
        }
        
        .ai-draft-card-actions a.edit {
            background: #f39c12;
        }
        
        .ai-draft-card-actions a.publish {
            background: #27ae60;
        }
        
        .ai-draft-card-actions a:hover {
            opacity: 0.9;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .drafts-tab-selector {
            display: flex;
            margin-bottom: 20px;
        }
        
        .drafts-tab-selector a {
            padding: 8px 15px;
            background: #f0f0f0;
            color: #666;
            text-decoration: none;
            margin-left: 10px;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .drafts-tab-selector a.active {
            background: #3498db;
            color: white;
        }
        
        .human-tone-option {
            margin-bottom: 20px;
            background: #f0f9ff;
            padding: 12px 15px;
            border-radius: 6px;
            border-right: 3px solid #3498db;
        }
        
        .checkbox-container {
            display: flex;
            align-items: center;
            position: relative;
            padding-right: 35px;
            cursor: pointer;
            font-size: 14px;
            user-select: none;
        }
        
        .checkbox-container input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
            height: 0;
            width: 0;
        }
        
        .checkmark {
            position: absolute;
            top: 0;
            right: 0;
            height: 20px;
            width: 20px;
            background-color: #eee;
            border-radius: 4px;
        }
        
        .checkbox-container:hover input ~ .checkmark {
            background-color: #ccc;
        }
        
        .checkbox-container input:checked ~ .checkmark {
            background-color: #3498db;
        }
        
        .checkmark:after {
            content: "";
            position: absolute;
            display: none;
        }
        
        .checkbox-container input:checked ~ .checkmark:after {
            display: block;
        }
        
        .checkbox-container .checkmark:after {
            right: 7px;
            top: 3px;
            width: 5px;
            height: 10px;
            border: solid white;
            border-width: 0 3px 3px 0;
            transform: rotate(45deg);
        }
    </style>
    
    <div class="smart-admin-wrap">
        <h2>ادمین هوشمند</h2>
        
        <?php if (!empty($success_message)): ?>
            <div class="success-message">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <div class="smart-admin-tabs">
            <a href="#" class="tab-link active" data-tab="prompt">ایجاد محتوا با هوش مصنوعی</a>
            <a href="#" class="tab-link" data-tab="templates">قالب‌های آماده</a>
            <a href="#" class="tab-link" data-tab="saved">پرامپت‌های ذخیره شده</a>
            <a href="#" class="tab-link" data-tab="drafts">پیش‌نویس‌ها</a>
            <a href="#" class="tab-link" data-tab="scheduler">زمان‌بندی محتوا</a>
            <a href="#" class="tab-link" data-tab="settings">تنظیمات</a>
        </div>
        
        <div id="prompt" class="tab-content active">
            <form method="post" class="prompt-form" id="ai-prompt-form">
                <?php wp_nonce_field('smart_admin_prompt_action', 'smart_admin_nonce'); ?>
                <input type="hidden" id="is_template" name="is_template" value="0">
                
                <div class="form-group">
                    <label for="smart_admin_model">انتخاب مدل هوش مصنوعی:</label>
                    <select id="smart_admin_model" name="smart_admin_model">
                        <option value="gpt-4o">GPT-4o - مدل پیشرفته OpenAI با قابلیت‌های چندمنظوره</option>
                        <option value="claude-3-7-sonnet-20250219">Claude 3.7 Sonnet - مدل متعادل Anthropic با کارایی بالا</option>
                        <option value="gemini-2.0-flash">Gemini 2.0 Flash - مدل ارزان و به‌صرفه Google با کارایی بالا</option>
                        <option value="deepseek-chat">DeepSeek Chat - مدل چت هوشمند DeepSeek با تمرکز بر مکالمات طبیعی</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="smart_admin_prompt">متن پرامپت:</label>
                    <textarea id="smart_admin_prompt" name="smart_admin_prompt" placeholder="موضوع یا پرامپت خود را وارد کنید..."></textarea>
                </div>
                
                <?php add_human_tone_option_to_form(); ?>
                
                <button type="submit" class="submit-button" id="generate-btn">
                    <span id="loading-spinner" class="loading-spinner"></span>
                    <span>تولید محتوا</span>
                </button>
            </form>
            
            <?php if (isset($response) && !empty($response)): ?>
                <div class="response-container <?php echo isset($response['error']) ? 'error' : ''; ?>">
                    <?php if (isset($response['error'])): ?>
                        <p><strong>خطا:</strong> <?php echo esc_html($response['error']); ?></p>
                    <?php else: ?>
                        <h3>پاسخ هوش مصنوعی:</h3>
                        <div id="ai-response-content"><?php echo nl2br(esc_html($response['content'])); ?></div>
                        
                        <div class="save-draft-form">
                            <h3>ذخیره به عنوان پیش‌نویس</h3>
                            <p>این محتوا را به عنوان یک پیش‌نویس در وردپرس ذخیره کنید.</p>
                            
                            <form method="post">
                                <?php wp_nonce_field('smart_admin_save_draft_action', 'smart_admin_save_draft_nonce'); ?>
                                <input type="hidden" name="ai_response" value="<?php echo esc_attr($response['content']); ?>">
                                
                                <div class="form-row">
                                    <label for="post_title">عنوان مقاله:</label>
                                    <input type="text" id="post_title" name="post_title" required placeholder="عنوان مقاله را وارد کنید...">
                                </div>
                                
                                <div class="form-row">
                                    <label for="post_keywords">برچسب‌ها (با کاما جدا کنید):</label>
                                    <input type="text" id="post_keywords" name="post_keywords" placeholder="برچسب 1، برچسب 2، برچسب 3">
                                </div>
                                
                                <button type="submit" name="save_as_draft" class="submit-button">ذخیره به عنوان پیش‌نویس</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div id="templates" class="tab-content">
            <h3>قالب‌های آماده برای تولید محتوا</h3>
            <p>از این قالب‌های آماده و بهینه‌سازی شده برای تولید محتوای با کیفیت استفاده کنید. کافیست بخش‌های داخل براکت را با محتوای مورد نظر خود جایگزین کنید.</p>
            
            <div class="templates-grid">
                <?php 
                $default_prompts = get_default_content_prompts();
                foreach ($default_prompts as $index => $prompt): 
                ?>
                    <div class="template-card">
                        <h3><?php echo esc_html($prompt['title']); ?></h3>
                        <span class="template-card-model"><?php echo esc_html($prompt['model']); ?></span>
                        <div class="template-card-preview">
                            <?php echo nl2br(esc_html(substr($prompt['prompt'], 0, 200) . '...')); ?>
                        </div>
                        <div class="template-card-actions">
                            <button type="button" class="use-template-btn" 
                                data-prompt="<?php echo esc_attr($prompt['prompt']); ?>" 
                                data-model="<?php echo esc_attr($prompt['model']); ?>"
                                data-is-template="1">
                                استفاده از قالب
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div id="saved" class="tab-content">
            <div class="saved-prompts">
                <h3>پرامپت‌های ذخیره شده</h3>
                
                <div class="tab-selector">
                    <a href="#" class="prompt-filter active" data-filter="all">همه</a>
                    <a href="#" class="prompt-filter" data-filter="custom">پرامپت‌های سفارشی</a>
                    <a href="#" class="prompt-filter" data-filter="template">قالب‌های آماده</a>
                </div>
                
                <?php 
                $saved_prompts = get_option('smart_admin_saved_prompts', array());
                if (empty($saved_prompts)): 
                ?>
                    <p>هیچ پرامپتی ذخیره نشده است.</p>
                <?php else: ?>
                    <?php foreach ($saved_prompts as $index => $prompt): 
                        $is_template = isset($prompt['is_template']) && $prompt['is_template'];
                        $prompt_title = isset($prompt['title']) ? $prompt['title'] : 'پرامپت سفارشی';
                    ?>
                        <div class="prompt-card <?php echo $is_template ? 'template' : 'custom'; ?>">
                            <div class="prompt-card-header">
                                <div>
                                    <?php if ($is_template): ?>
                                        <span class="prompt-template-tag">قالب</span>
                                    <?php endif; ?>
                                    <span class="prompt-card-model"><?php echo esc_html($prompt['model']); ?></span>
                                </div>
                                <span class="prompt-card-date"><?php echo esc_html(human_time_diff(strtotime($prompt['date']), current_time('timestamp'))); ?> پیش</span>
                            </div>
                            <div class="prompt-card-title">
                                <?php echo esc_html($prompt_title); ?>
                            </div>
                            <div class="prompt-card-content">
                                <?php echo nl2br(esc_html(substr($prompt['prompt'], 0, 300) . (strlen($prompt['prompt']) > 300 ? '...' : ''))); ?>
                            </div>
                            <div class="prompt-card-actions">
                                <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(array('page' => 'smart-admin', 'delete_prompt' => $index), admin_url('admin.php')), 'smart_admin_delete_prompt_action', 'smart_admin_delete_nonce')); ?>" class="delete-prompt" onclick="return confirm('آیا از حذف این پرامپت اطمینان دارید؟');">حذف</a>
                                <a href="#" class="use-prompt" data-prompt="<?php echo esc_attr($prompt['prompt']); ?>" data-model="<?php echo esc_attr($prompt['model']); ?>" data-is-template="<?php echo $is_template ? '1' : '0'; ?>">استفاده مجدد</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div id="drafts" class="tab-content">
            <h3>پیش‌نویس‌های دستیار هوشمند</h3>
            <p>در این بخش می‌توانید پیش‌نویس‌های ایجاد شده توسط دستیار هوشمند را مشاهده و مدیریت کنید.</p>
            
            <div class="drafts-tab-selector">
                <a href="<?php echo admin_url('edit.php?post_status=draft&post_type=post&cat=' . get_option('smart_admin_assistant_category_id')); ?>" class="view-all-drafts">مشاهده همه پیش‌نویس‌ها</a>
                <a href="<?php echo admin_url('post-new.php?post_type=post'); ?>" class="new-post">ایجاد نوشته جدید</a>
            </div>
            
            <div class="ai-drafts-wrap">
                <?php
                $ai_drafts = smart_admin_get_ai_drafts(6);
                
                if (empty($ai_drafts)):
                ?>
                    <p>هیچ پیش‌نویسی توسط دستیار هوشمند ایجاد نشده است.</p>
                <?php else: ?>
                    <div class="ai-drafts-grid">
                        <?php foreach ($ai_drafts as $draft): ?>
                            <div class="ai-draft-card">
                                <h4><?php echo esc_html($draft->post_title); ?></h4>
                                <div class="ai-draft-card-date">
                                    <?php echo sprintf('ایجاد شده در %s', get_the_date(get_option('date_format'), $draft->ID)); ?>
                                </div>
                                <div class="ai-draft-card-actions">
                                    <a href="<?php echo get_permalink($draft->ID); ?>?preview=true" class="view" target="_blank">پیش‌نمایش</a>
                                    <a href="<?php echo get_edit_post_link($draft->ID); ?>" class="edit">ویرایش</a>
                                    <a href="<?php echo admin_url('admin.php?action=publish_ai_draft&post_id=' . $draft->ID . '&_wpnonce=' . wp_create_nonce('publish_ai_draft_' . $draft->ID)); ?>" class="publish">انتشار</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div id="settings" class="tab-content">
            <form method="post" action="options.php" class="settings-form">
                <?php settings_fields('smart_admin_settings'); ?>
                
                <div class="form-group">
                    <label for="smart_admin_api_key">کلید API گپ جی‌پی‌تی:</label>
                    <input type="text" id="smart_admin_api_key" name="smart_admin_api_key" value="<?php echo esc_attr(get_option('smart_admin_api_key', 'sk-8exa7q6H5GpW2BO7v72z50Nd5zCiEhK13hiz4nzJ9XuXyEYO')); ?>" placeholder="کلید API خود را وارد کنید" />
                </div>
                
                <div class="form-group">
                    <label for="default_model">مدل پیش‌فرض:</label>
                    <select id="default_model" name="smart_admin_model">
                        <option value="gpt-4o" <?php selected(get_option('smart_admin_model'), 'gpt-4o'); ?>>GPT-4o - مدل پیشرفته OpenAI با قابلیت‌های چندمنظوره</option>
                        <option value="claude-3-7-sonnet-20250219" <?php selected(get_option('smart_admin_model'), 'claude-3-7-sonnet-20250219'); ?>>Claude 3.7 Sonnet - مدل متعادل Anthropic با کارایی بالا</option>
                        <option value="gemini-2.0-flash" <?php selected(get_option('smart_admin_model'), 'gemini-2.0-flash'); ?>>Gemini 2.0 Flash - مدل ارزان و به‌صرفه Google با کارایی بالا</option>
                        <option value="deepseek-chat" <?php selected(get_option('smart_admin_model'), 'deepseek-chat'); ?>>DeepSeek Chat - مدل چت هوشمند DeepSeek با تمرکز بر مکالمات طبیعی</option>
                    </select>
                </div>
                
                <?php submit_button('ذخیره تنظیمات', 'submit-button', 'submit', false); ?>
            </form>
        </div>
        
        <?php
        // فراخوانی تابع برای نمایش محتوای تب زمان‌بندی
        if (function_exists('smart_admin_scheduler_tab_content')) {
            smart_admin_scheduler_tab_content();
        }
        ?>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // مدیریت تب‌ها
        const tabLinks = document.querySelectorAll('.tab-link');
        const tabContents = document.querySelectorAll('.tab-content');
        
        tabLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // حذف کلاس active از همه تب‌ها
                tabLinks.forEach(item => item.classList.remove('active'));
                tabContents.forEach(item => item.classList.remove('active'));
                
                // افزودن کلاس active به تب انتخاب شده
                this.classList.add('active');
                document.getElementById(this.getAttribute('data-tab')).classList.add('active');
                
                // ذخیره تب فعال در localStorage
                localStorage.setItem('smartAdminActiveTab', this.getAttribute('data-tab'));
            });
        });
        
        // بازیابی تب فعال از localStorage
        const activeTab = localStorage.getItem('smartAdminActiveTab');
        if (activeTab) {
            const link = document.querySelector(`.tab-link[data-tab="${activeTab}"]`);
            if (link) {
                link.click();
            }
        }

        // فیلتر کردن پرامپت‌ها
        const promptFilters = document.querySelectorAll('.prompt-filter');
        const promptCards = document.querySelectorAll('.prompt-card');
        
        promptFilters.forEach(filter => {
            filter.addEventListener('click', function(e) {
                e.preventDefault();
                
                // حذف کلاس active از همه فیلترها
                promptFilters.forEach(item => item.classList.remove('active'));
                
                // افزودن کلاس active به فیلتر انتخاب شده
                this.classList.add('active');
                
                const filterValue = this.getAttribute('data-filter');
                
                promptCards.forEach(card => {
                    if (filterValue === 'all') {
                        card.style.display = 'block';
                    } else {
                        if (card.classList.contains(filterValue)) {
                            card.style.display = 'block';
                        } else {
                            card.style.display = 'none';
                        }
                    }
                });
            });
        });
        
        // استفاده از قالب‌های آماده
        const useTemplateButtons = document.querySelectorAll('.use-template-btn');
        useTemplateButtons.forEach(button => {
            button.addEventListener('click', function() {
                const prompt = this.getAttribute('data-prompt');
                const model = this.getAttribute('data-model');
                const isTemplate = this.getAttribute('data-is-template');
                
                document.getElementById('smart_admin_prompt').value = prompt;
                document.getElementById('is_template').value = isTemplate;
                
                // انتخاب مدل در سلکت باکس
                const modelSelect = document.getElementById('smart_admin_model');
                for (let i = 0; i < modelSelect.options.length; i++) {
                    if (modelSelect.options[i].value === model) {
                        modelSelect.selectedIndex = i;
                        break;
                    }
                }
                
                // تنظیم گزینه لحن انسانی به حالت انتخاب شده
                if (document.getElementById('use_human_tone')) {
                    document.getElementById('use_human_tone').checked = true;
                }
                
                // تغییر تب به تب پرامپت
                document.querySelector('.tab-link[data-tab="prompt"]').click();
                
                // اسکرول به فرم
                document.getElementById('smart_admin_prompt').scrollIntoView({ behavior: 'smooth' });
            });
        });
        
        // استفاده مجدد از پرامپت‌های ذخیره شده
        const usePromptButtons = document.querySelectorAll('.use-prompt');
        usePromptButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                const prompt = this.getAttribute('data-prompt');
                const model = this.getAttribute('data-model');
                const isTemplate = this.getAttribute('data-is-template');
                
                document.getElementById('smart_admin_prompt').value = prompt;
                document.getElementById('is_template').value = isTemplate;
                
                // انتخاب مدل در سلکت باکس
                const modelSelect = document.getElementById('smart_admin_model');
                for (let i = 0; i < modelSelect.options.length; i++) {
                    if (modelSelect.options[i].value === model) {
                        modelSelect.selectedIndex = i;
                        break;
                    }
                }
                
                // تغییر تب به تب پرامپت
                document.querySelector('.tab-link[data-tab="prompt"]').click();
                
                // اسکرول به فرم
                document.getElementById('smart_admin_prompt').scrollIntoView({ behavior: 'smooth' });
            });
        });
        
        // نمایش اسپینر در هنگام ارسال درخواست
        const promptForm = document.getElementById('ai-prompt-form');
        const loadingSpinner = document.getElementById('loading-spinner');
        
        if (promptForm) {
            promptForm.addEventListener('submit', function() {
                loadingSpinner.style.display = 'inline-block';
            });
        }
        
        // مدیریت کپی کردن محتوای هوش مصنوعی
        const aiResponseContent = document.getElementById('ai-response-content');
        if (aiResponseContent) {
            // دریافت عنوان پیشنهادی از محتوا
            const contentText = aiResponseContent.textContent;
            const titleMatch = contentText.match(/^(.+?)(?:\n|$)/);
            
            if (titleMatch && titleMatch[1]) {
                const suggestedTitle = titleMatch[1].replace(/^#+\s+/, '').trim();
                const titleInput = document.getElementById('post_title');
                
                if (titleInput && suggestedTitle.length > 5 && suggestedTitle.length < 100) {
                    titleInput.value = suggestedTitle;
                }
            }
            
            // استخراج کلمات کلیدی از محتوا
            const keywordsInput = document.getElementById('post_keywords');
            if (keywordsInput) {
                // الگوهای مختلف برای یافتن کلمات کلیدی
                const patterns = [
                    /کلمات\s*کلیدی\s*[:]\s*(.*?)(?:[\.\n]|$)/i,
                    /کلیدواژه\s*[:]\s*(.*?)(?:[\.\n]|$)/i,
                    /keywords\s*[:]\s*(.*?)(?:[\.\n]|$)/i,
                    /tags\s*[:]\s*(.*?)(?:[\.\n]|$)/i,
                    /برچسب\s*ها\s*[:]\s*(.*?)(?:[\.\n]|$)/i,
                    /تگ\s*ها\s*[:]\s*(.*?)(?:[\.\n]|$)/i,
                    /کلمات\s*کلیدی\s*[=]\s*(.*?)(?:[\.\n]|$)/i,
                    /کلمات\s*کلیدی\s*[>]\s*(.*?)(?:[\.\n]|$)/i,
                ];

                // جستجو بر اساس الگوها
                let foundKeywords = '';
                for (const pattern of patterns) {
                    const match = contentText.match(pattern);
                    if (match && match[1]) {
                        foundKeywords = match[1].trim();
                        break;
                    }
                }

                if (foundKeywords) {
                    // حذف کاماهای اضافی از ابتدا و انتهای رشته
                    foundKeywords = foundKeywords.replace(/^[,،\s]+|[,،\s]+$/g, '');
                    // حذف فاصله‌های اضافی بعد از کاماها
                    foundKeywords = foundKeywords.replace(/[,،](\s+)/g, ',');
                    keywordsInput.value = foundKeywords;
                }
            }
        }
    });
    </script>
    <?php
}

// تابع ارسال درخواست به API گپ جی‌پی‌تی
function send_to_gapgpt_api($prompt, $model, $api_key) {
    // تنظیمات درخواست
    $url = 'https://api.gapgpt.app/v1/chat/completions';
    
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
    
    // ارسال درخواست
    $response = wp_remote_post($url, $args);
    
    // بررسی خطا
    if (is_wp_error($response)) {
        return array('error' => $response->get_error_message());
    }
    
    // دریافت پاسخ
    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = json_decode(wp_remote_retrieve_body($response), true);
    
    if ($response_code !== 200) {
        return array('error' => isset($response_body['error']['message']) ? $response_body['error']['message'] : 'خطای نامشخص');
    }
    
    // استخراج محتوای پاسخ
    if (isset($response_body['choices'][0]['message']['content'])) {
        $content = $response_body['choices'][0]['message']['content'];
        
        // استخراج کلمات کلیدی از محتوا
        $keywords = array();
        if (function_exists('smart_admin_extract_keywords_from_ai_response')) {
            $keywords = smart_admin_extract_keywords_from_ai_response($content);
        }
        
        return array(
            'content' => $content,
            'keywords' => $keywords
        );
    } else {
        return array('error' => 'خطا در دریافت پاسخ از API');
    }
}

function smart_admin_notice() {
    // نمایش اعلان فقط برای کاربران با مجوز مدیریت
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // نمایش اعلان فقط در صفحات مشخص
    $screen = get_current_screen();
    if (!$screen || !in_array($screen->id, array('dashboard', 'plugins'))) {
        return;
    }
    
    // دریافت تعداد پرامپت‌های ذخیره شده
    $saved_prompts = get_option('smart_admin_saved_prompts', array());
    $prompt_count = count($saved_prompts);
    
    ?>
    <div class="notice notice-info is-dismissible">
        <p>
            <strong>ادمین هوشمند:</strong> 
            با استفاده از <a href="<?php echo esc_url(admin_url('admin.php?page=smart-admin')); ?>">ادمین هوشمند</a> می‌توانید محتوای هوشمند با کمک هوش مصنوعی تولید کنید.
            <?php if ($prompt_count > 0): ?>
                <span>(<?php echo $prompt_count; ?> پرامپت ذخیره شده)</span>
            <?php endif; ?>
        </p>
    </div>
    <?php
}

// انتشار پیش‌نویس با یک کلیک
function smart_admin_publish_draft() {
    if (isset($_GET['action']) && $_GET['action'] == 'publish_ai_draft' && isset($_GET['post_id'])) {
        $post_id = intval($_GET['post_id']);
        
        // بررسی نانس
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'publish_ai_draft_' . $post_id)) {
            wp_die('خطای امنیتی رخ داده است.');
        }
        
        // بررسی مجوز
        if (!current_user_can('publish_posts')) {
            wp_die('شما مجوز لازم برای انتشار این نوشته را ندارید.');
        }
        
        // بررسی وجود پست
        $post = get_post($post_id);
        if (!$post || $post->post_status != 'draft' || !get_post_meta($post_id, 'smart_admin_generated', true)) {
            wp_die('نوشته مورد نظر یافت نشد یا قابل انتشار نیست.');
        }
        
        // انتشار پست
        wp_publish_post($post_id);
        
        // ریدایرکت به صفحه ویرایش پست
        wp_redirect(get_edit_post_link($post_id, 'redirect'));
        exit;
    }
}
add_action('admin_init', 'smart_admin_publish_draft');

// نمایش محتوای متاباکس
function smart_admin_metabox_callback($post) {
    // بررسی آیا این پست توسط دستیار هوشمند ایجاد شده است
    $is_ai_generated = get_post_meta($post->ID, 'smart_admin_generated', true);
    
    if ($is_ai_generated == 'yes') {
        $generation_date = get_post_meta($post->ID, 'smart_admin_generation_date', true);
        echo '<p><strong>این محتوا توسط دستیار هوشمند تولید شده است.</strong></p>';
        echo '<p>تاریخ تولید: ' . date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($generation_date)) . '</p>';
        
        // دریافت برچسب‌های پست
        $post_tags = wp_get_post_tags($post->ID, array('fields' => 'names'));
        $keywords_string = implode(', ', $post_tags);
        
        echo '<div id="smart-admin-keywords-form">';
        echo '<p><label for="smart_admin_keywords">کلمات کلیدی:</label><br>';
        echo '<input type="text" id="smart_admin_keywords" name="smart_admin_keywords" value="' . esc_attr($keywords_string) . '" style="width: 100%;">';
        echo '<button type="button" id="set_rank_math_keywords" class="button button-secondary" style="margin-top: 8px;">تنظیم کلمات کلیدی در Rank Math</button>';
        echo '<span id="keywords_result" style="display: block; margin-top: 5px;"></span>';
        echo '</p>';
        echo '</div>';
        
        wp_nonce_field('smart_admin_set_keywords', 'smart_admin_keywords_nonce');
        
        // اسکریپت برای ارسال درخواست AJAX
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#set_rank_math_keywords').on('click', function() {
                var $button = $(this);
                var $result = $('#keywords_result');
                var keywords = $('#smart_admin_keywords').val();
                
                $button.prop('disabled', true).text('در حال تنظیم...');
                $result.text('');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'smart_admin_set_keywords',
                        post_id: <?php echo $post->ID; ?>,
                        keywords: keywords,
                        nonce: $('#smart_admin_keywords_nonce').val()
                    },
                    success: function(response) {
                        if (response.success) {
                            $result.html('<span style="color: green;">' + response.data + '</span>');
                            // بروزرسانی صفحه برای نمایش تغییرات
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            $result.html('<span style="color: red;">' + response.data + '</span>');
                        }
                        $button.prop('disabled', false).text('تنظیم کلمات کلیدی در Rank Math');
                    },
                    error: function() {
                        $result.html('<span style="color: red;">خطا در ارتباط با سرور</span>');
                        $button.prop('disabled', false).text('تنظیم کلمات کلیدی در Rank Math');
                    }
                });
            });
        });
        </script>
        <?php
    } else {
        echo '<p>این محتوا توسط دستیار هوشمند تولید نشده است.</p>';
    }
} 