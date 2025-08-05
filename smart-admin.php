<?php
// --- Smart Admin Debug Setup ---
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
    @ini_set( 'log_errors', 'On' );
    @ini_set( 'display_errors', 'Off' );
    if ( ! ini_get( 'error_log' ) || stripos( ini_get( 'error_log' ), 'debug.log' ) === false ) {
        @ini_set( 'error_log', WP_CONTENT_DIR . '/debug.log' );
    }
}

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

// وارد کردن فایل بهینه‌ساز هوشمند SEO
require_once plugin_dir_path(__FILE__) . 'smart-admin-seo-auto-optimizer.php';

// بررسی وضعیت Unsplash قبل از بارگذاری فایل بهینه‌ساز تصاویر
$unsplash_enabled = false;

// بررسی وجود تابع faraz_unsplash_is_auto_featured_image_enabled
if (function_exists('faraz_unsplash_is_auto_featured_image_enabled')) {
    $unsplash_enabled = faraz_unsplash_is_auto_featured_image_enabled();
} elseif (function_exists('faraz_unsplash_is_image_generation_enabled')) {
    $unsplash_enabled = faraz_unsplash_is_image_generation_enabled();
} else {
    $unsplash_enabled = get_option('faraz_unsplash_enable_image_generation', true);
}

if ($unsplash_enabled) {
    error_log('[Smart-admin.php] Unsplash is enabled, loading image optimizer');
    // وارد کردن فایل بهینه‌ساز هوشمند تصاویر
    require_once plugin_dir_path(__FILE__) . 'smart-admin-image-optimizer.php';
} else {
    error_log('[Smart-admin.php] Unsplash is disabled, skipping image optimizer');
}

// وارد کردن فایل تنظیمات متاباکس‌ها
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
    
    // همچنین اضافه کردن به زیرمنو
    add_submenu_page(
        'faraz-telegram-plugin', // منوی والد
        'ادمین هوشمند', // عنوان صفحه
        'ادمین هوشمند', // عنوان منو
        'manage_options', // مجوز دسترسی
        'smart-admin', // slug صفحه (باید با منوی اصلی یکسان باشد)
        'smart_admin_page' // تابع نمایش صفحه
    );
    
    // اضافه کردن زیرمنوی تنظیمات متاباکس‌ها
    add_submenu_page(
        'smart-admin', // منوی والد
        'تنظیمات متاباکس‌ها', // عنوان صفحه
        'تنظیمات متاباکس‌ها', // عنوان منو
        'manage_options', // مجوز دسترسی
        'smart-admin-metabox-settings', // slug صفحه
        'smart_admin_metabox_settings' // تابع نمایش صفحه
    );
}

// افزودن تنظیمات
function smart_admin_register_settings() {
    register_setting('smart_admin_settings', 'smart_admin_api_key');
    register_setting('smart_admin_settings', 'smart_admin_model');
}
add_action('admin_init', 'smart_admin_register_settings');

// تابع ساخت پرامپت بر اساس فیلدهای فرم قالب
function build_template_prompt($form_data) {
    $prompt = '';
    
    // تشخیص نوع قالب بر اساس فیلدهای موجود
    if (isset($form_data['main_topic']) && isset($form_data['focus_keyword'])) {
        // قالب Rank Math استاندارد
        $main_topic = sanitize_text_field($form_data['main_topic']);
        $focus_keyword = sanitize_text_field($form_data['focus_keyword']);
        $target_audience = isset($form_data['target_audience']) ? sanitize_text_field($form_data['target_audience']) : '';
        $content_type = isset($form_data['content_type']) ? sanitize_text_field($form_data['content_type']) : 'آموزشی';
        $content_length = isset($form_data['content_length']) ? intval($form_data['content_length']) : 1200;
        
        $prompt = "**نقش شما:** شما یک متخصص ارشد SEO و تولیدکننده محتوای حرفه‌ای هستید که در بهینه‌سازی محتوا برای Rank Math و گوگل تخصص دارید. وظیفه شما ایجاد یک مقاله کاملاً استاندارد برای موضوع \"$main_topic\" است که امتیاز بالای ۹۰ در Rank Math کسب کند.

**هدف اصلی:** تولید مقاله‌ای که تمام استانداردهای SEO را رعایت کند و در گوگل عملکرد عالی داشته باشد.

**مراحل اجرای کار (بسیار مهم):**

**مرحله ۱: تحلیل کلمه کلیدی و تحقیق**
1. **کلمه کلیدی اصلی:** \"$focus_keyword\" - این کلمه کلیدی باید:
   - در عنوان (Title) قرار گیرد
   - در آدرس URL استفاده شود
   - در پاراگراف اول متن وجود داشته باشد
   - در توضیحات متا (Meta Description) بیاید
   - در حداقل یکی از تیترهای H2 یا H3 استفاده شود
   - حداقل ۵ بار در متن مقاله تکرار شود (تراکم مناسب ۱٪ - ۲٪)

2. **کلمات کلیدی فرعی (LSI):** حداقل ۱۰ کلمه کلیدی مرتبط معنایی پیدا کن
3. **کلمات کلیدی طولانی (Long-tail):** حداقل ۵ سوال رایج کاربران را شناسایی کن

**مرحله ۲: طراحی ساختار مقاله کاملاً استاندارد**
* **H1 (عنوان اصلی):** حداکثر ۶۰ کاراکتر، دارای کلمه کلیدی اصلی، جذاب همراه با عدد، سال یا سوال
* **مقدمه قدرتمند:** شامل کلمه کلیدی، معرفی موضوع + ایجاد کنجکاوی، ترجیحاً با Bold و فونت متفاوت
* **بدنه اصلی:** حداقل ۵ بخش H2، هر بخش شامل ۲-۳ زیربخش H3
* **نتیجه‌گیری:** جمع‌بندی نکات کلیدی + دعوت به اقدام
* **بخش FAQ:** حداقل ۳ سوال متداول با پاسخ‌های دقیق

**مرحله ۳: بهینه‌سازی کامل محتوا**

**۳-۱: بهینه‌سازی عنوان و متا**
- **عنوان (Meta Title):** حداکثر ۶۰ کاراکتر، شامل کلمه کلیدی، جذاب با عدد/سال/سوال
- **توضیحات متا (Meta Description):** حداکثر ۱۶۰ کاراکتر، شامل کلمه کلیدی، خلاصه جذاب و قابل کلیک
- **آدرس URL:** کوتاه و شامل کلمه کلیدی (مثال: yoursite.com/web-programming-guide)

**۳-۲: بهینه‌سازی محتوای اصلی**
- **پاراگراف اول:** شامل کلمه کلیدی، معرفی موضوع + ایجاد کنجکاوی، با Bold برجسته شود
- **تراکم کلمه کلیدی:** حدود ۱٪ تا ۲٪ (نه کمتر، نه بیشتر)
- **خوانایی:** جملات کوتاه (زیر ۲۰ کلمه)، پاراگراف‌های کمتر از ۵ خط، استفاده از لیست‌های Bullet و Number
- **طول مقاله:** حداقل $content_length کلمه

**۳-۳: لینک‌دهی و تصاویر**
- **لینک‌دهی داخلی:** حداقل ۲ لینک به صفحات مرتبط دیگر سایت
- **لینک‌دهی خارجی:** حداقل یک لینک به منبع معتبر (با rel=\"noopener\" و target=\"_blank\")
- **تصاویر:** حداقل یک تصویر، متن ALT شامل کلمه کلیدی اصلی

**۳-۴: Schema و بهینه‌سازی پیشرفته**
- **Schema نوع:** \"مقاله / Article\" یا \"راهنما / HowTo\"
- **جدول محتوا:** با لینک‌دهی داخلی به هدینگ‌ها
- **واکنش‌گرا بودن:** ساختار مناسب برای موبایل، تبلت و دسکتاپ

**مرحله ۴: تولید محتوای نهایی**

**۴-۱: نگارش محتوا با رعایت تمام استانداردها**
- لحن معتبر، حرفه‌ای اما روان و قابل فهم
- استفاده از سبک نگارش \"هرم معکوس\" (مهم‌ترین اطلاعات در ابتدا)
- پاراگراف‌های کوتاه (حداکثر ۳-۴ جمله)
- استفاده از لیست‌های شماره‌دار و بولت‌پوینت
- نقل قول و متن برجسته برای شکستن یکنواختی

**۴-۲: غنی‌سازی محتوا**
- آمار و ارقام (با ذکر منبع فرضی)
- مثال‌های عملی و مطالعات موردی
- پیشنهادات برای افزودن اینفوگرافیک یا ویدیو
- استفاده از کلمات کلیدی فرعی و طولانی به صورت طبیعی

**۴-۳: بهینه‌سازی نهایی**
- بررسی تراکم کلمه کلیدی (۱٪ - ۲٪)
- اطمینان از وجود کلمه کلیدی در تمام بخش‌های مهم
- بررسی خوانایی و ساختار متن
- اطمینان از وجود تمام عناصر SEO

**خروجی نهایی:**
یک مقاله کاملاً استاندارد با حداقل **$content_length کلمه** که تمام استانداردهای SEO را رعایت کند و آماده انتشار مستقیم در وردپرس باشد. مقاله باید شامل تمام عناصر ذکر شده در چک‌لیست باشد و امتیاز بالای ۹۰ در Rank Math کسب کند.

**نکات مهم:**
- از تکرار بیش از حد کلمات کلیدی (Keyword Stuffing) به شدت پرهیز کن
- محتوا باید طبیعی و خوانا باشد
- تمام عناصر SEO باید به صورت یکپارچه در متن گنجانده شوند
- مقاله باید ارزش واقعی برای کاربر ایجاد کند";
        
    } elseif (isset($form_data['main_topic']) && isset($form_data['target_audience'])) {
        // قالب مقاله جامع و بنیادی
        $main_topic = sanitize_text_field($form_data['main_topic']);
        $target_audience = sanitize_text_field($form_data['target_audience']);
        $content_goal = isset($form_data['content_goal']) ? sanitize_text_field($form_data['content_goal']) : 'آموزشی';
        
        $prompt = "**نقش شما:** شما یک استراتژیست ارشد محتوا و متخصص SEO با بیش از ۱۰ سال تجربه هستید. وظیفه شما ایجاد یک مقاله بنیادی (Pillar Page) بسیار جامع، معتبر و کاملاً بهینه‌سازی شده برای موضوع \"$main_topic\" است. این مقاله باید به عنوان منبع اصلی و نهایی برای این موضوع در وب فارسی عمل کند.

**مراحل اجرای کار (بسیار مهم):**

**مرحله ۱: تحلیل عمیق و تحقیق کلمات کلیدی**
1. **شناسایی هدف جستجو (Search Intent):** ابتدا مشخص کن که کاربرانی که در مورد \"$main_topic\" جستجو می‌کنند، به دنبال چه نوع اطلاعاتی هستند؟ (مثلاً اطلاعاتی، آموزشی، راهنمای خرید).
2. **تحقیق کلمات کلیدی:** لیستی از کلمات کلیدی مرتبط تهیه کن:
   * **کلمه کلیدی اصلی:** $main_topic
   * **کلمات کلیدی فرعی (LSI):** حداقل ۱۰ کلمه کلیدی مرتبط معنایی پیدا کن.
   * **کلمات کلیدی طولانی (Long-tail):** حداقل ۵ سوال رایج که کاربران در این مورد می‌پرسند را شناسایی کن (مثلاً \"چگونه X را انجام دهیم؟\"، \"بهترین Y برای Z چیست؟\").
3. **تحلیل رقبا:** به صورت فرضی، ۳ مقاله برتر در نتایج جستجوی گوگل برای این موضوع را تحلیل کن. نقاط قوت و ضعف آن‌ها چیست؟ چه شکاف‌های محتوایی وجود دارد که ما می‌توانیم آن‌ها را پر کنیم؟

**مرحله ۲: طراحی ساختار مقاله (Blueprint)**
بر اساس تحقیقات مرحله ۱، یک ساختار درختی و دقیق برای مقاله طراحی کن. این ساختار باید شامل موارد زیر باشد:
* **H1 (عنوان اصلی):** یک عنوان جذاب، منحصر به فرد و سئو شده (حاوی کلمه کلیدی اصلی).
* **مقدمه:** یک مقدمه قدرتمند که با یک \"قلاب\" (Hook) شروع می‌شود، مشکل کاربر را بیان می‌کند و قول یک راه‌حل جامع را می‌دهد.
* **H2 (فصل‌های اصلی):** مقاله را به بخش‌های منطقی (حداقل ۵ فصل) تقسیم کن. هر H2 باید یکی از جنبه‌های اصلی موضوع را پوشش دهد.
* **H3 (زیرفصل‌ها):** هر H2 را به H3 های مرتبط‌تر تقسیم کن تا خوانایی افزایش یابد.
* **نتیجه‌گیری:** یک جمع‌ بندی کامل که نکات کلیدی را مرور کرده و یک دیدگاه نهایی ارائه می‌دهد.
* **بخش پرسش‌های متداول (FAQ):** بر اساس کلمات کلیدی طولانی (سوالات) که پیدا کردی، یک بخش FAQ با پاسخ‌های دقیق و کوتاه ایجاد کن.

**مرحله ۳: نگارش و تولید محتوا**
با استفاده از ساختار بالا، شروع به نوشتن مقاله کن. نکات زیر را **با دقت** رعایت کن:
* **لحن و سبک:** لحنی معتبر، حرفه‌ای اما روان و قابل فهم. از سبک نگارش \"هرم معکوس\" استفاده کن (مهم‌ترین اطلاعات در ابتدا).
* **خوانایی:** پاراگراف‌ها را کوتاه (حداکثر ۳-۴ جمله) نگه دار. از لیست‌های شماره‌دار و بولت‌پوینت، **نقل قول** و **متن برجسته** برای شکستن یکنواختی متن استفاده کن.
* **چگالی کلمات کلیدی:** کلمه کلیدی اصلی را در مقدمه، نتیجه‌گیری و یکی دو تا از H2 ها به کار ببر. از کلمات کلیدی فرعی و طولانی به صورت طبیعی در سراسر متن استفاده کن. **از تکرار بیش از حد (Keyword Stuffing) به شدت پرهیز کن.**
* **غنی‌سازی محتوا:** برای اعتبار بخشیدن به متن، به **آمار و ارقام** (با ذکر منبع فرضی)، **مثال‌های عملی** و **مطالعات موردی** اشاره کن. پیشنهاداتی برای افزودن **اینفوگرافیک** یا **ویدیو** در بخش‌های مرتبط ارائه بده.
* **لینک‌دهی داخلی:** حداقل ۳ پیشنهاد برای لینک داخلی به مقالات مرتبط دیگر (با موضوعات فرضی) در متن بگنجان.

**مرحله ۴: بهینه‌سازی نهایی و CTA**
1. **بهینه‌سازی عنوان و توضیحات متا:** یک عنوان سئو (کمتر از ۶۰ کاراکتر) و یک توضیحات متا (کمتر از ۱۶۰ کاراکتر) جذاب و حاوی کلمه کلیدی اصلی پیشنهاد بده.
2. **دعوت به اقدام (Call to Action):** در انتهای مقاله، یک CTA مرتبط و هوشمندانه قرار بده (مثلاً دعوت به دانلود یک کتاب الکترونیکی، ثبت‌نام در وبینار یا مطالعه یک مقاله مرتبط دیگر).

**خروجی نهایی:**
مقاله باید حداقل **۲۵۰۰ کلمه** باشد، کاملاً ساختاریافته، فاقد هرگونه اطلاعات نادرست و آماده انتشار مستقیم در وردپرس باشد. لطفاً از نوشتن عباراتی مانند \"عنوان مقاله:\" یا \"مقدمه:\" خودداری کن و مستقیماً محتوا را تولید نما.";
        
    } elseif (isset($form_data['how_to_topic'])) {
        // قالب راهنمای عملی
        $how_to_topic = sanitize_text_field($form_data['how_to_topic']);
        $skill_level = isset($form_data['skill_level']) ? sanitize_text_field($form_data['skill_level']) : 'مبتدی';
        $required_tools = isset($form_data['required_tools']) ? sanitize_text_field($form_data['required_tools']) : '';
        
        $prompt = "**نقش شما:** شما یک مربی و نویسنده فنی هستید که در نوشتن راهنماهای عملی، واضح و قدم به قدم تخصص دارید. وظیفه شما ایجاد یک راهنمای کامل برای \"$how_to_topic\" است.

**هدف اصلی:** کاربر باید بتواند **فقط با خواندن این راهنما**، کار مورد نظر را با موفقیت و بدون هیچ مشکلی انجام دهد.

**مراحل اجرای کار:**

**مرحله ۱: برنامه‌ریزی راهنما**
1. **شناسایی مخاطب:** این راهنما برای چه کسی است؟ ($skill_level). سطح دانش فنی او را در نظر بگیر.
2. **لیست ابزار و پیش‌نیازها:** یک بخش در ابتدای مقاله با عنوان \"آنچه قبل از شروع نیاز دارید\" ایجاد کن و تمام ابزارها، مواد اولیه یا نرم‌افزارهای مورد نیاز را لیست کن.
3. **تقسیم فرآیند به مراحل:** کل فرآیند را به مراحل اصلی و قابل مدیریت (حداقل ۵ مرحله) تقسیم کن. هر مرحله باید یک اقدام مشخص باشد.

**مرحله ۲: طراحی ساختار راهنما**
* **H1 (عنوان):** عنوانی واضح و عملی (مثلاً: \"راهنمای قدم به قدم $how_to_topic برای $skill_level\").
* **مقدمه:** به طور خلاصه توضیح بده که در این راهنما چه چیزی آموزش داده می‌شود و نتیجه نهایی چه خواهد بود.
* **بخش پیش‌نیازها.**
* **بدنه اصلی (مراحل):**
  * هر مرحله باید یک تیتر H2 داشته باشد (مثلاً: \"مرحله ۱: آماده‌سازی مواد اولیه\").
  * برای هر مرحله، دستورالعمل‌ها را به صورت یک لیست شماره‌دار و واضح بنویس.
  * **نکته کلیدی:** بعد از هر چند مرحله، یک بخش \"نکات حرفه‌ای\" یا \"اشتباهات رایج\" اضافه کن.
* **بخش عیب‌یابی (Troubleshooting):** یک بخش H2 با عنوان \"مشکلات احتمالی و راه‌حل‌ها\" ایجاد کن و به ۳-۴ مشکل رایجی که ممکن است کاربر با آن مواجه شود، پاسخ بده.
* **نتیجه‌گیری:** نتیجه کار را جشن بگیر و کاربر را برای موفقیتش تشویق کن.
* **بخش FAQ.**

**مرحله ۳: نگارش محتوا**
* **زبان ساده و امری:** از جملات کوتاه، واضح و دستوری استفاده کن (مثلاً: \"فر را روی ۱۸۰ درجه تنظیم کنید.\").
* **غنی‌سازی بصری:** در هر مرحله، توضیح بده که چه نوع تصویر، گیف یا ویدیویی می‌تواند به درک بهتر کمک کند (مثلاً: \"[تصویر: نمایی نزدیک از هم زدن تخم مرغ‌ها]\").
* **تمرکز بر جزئیات:** هیچ مرحله‌ای را ناگفته نگذار. فرض کن کاربر هیچ دانشی در این زمینه ندارد.
* **کلمات کلیدی:** از کلمات کلیدی مرتبط با موضوع به صورت طبیعی استفاده کن.

**خروجی نهایی:**
یک راهنمای بسیار کاربردی با حداقل **۱۵۰۰ کلمه** که به صورت مستقیم در وردپرس قابل استفاده باشد. لحن باید دوستانه، حمایتی و تشویق‌کننده باشد. از نوشتن \"عنوان:\" و غیره خودداری کن.";
        
    } elseif (isset($form_data['list_topic'])) {
        // قالب مقاله لیستی
        $list_topic = sanitize_text_field($form_data['list_topic']);
        $list_count = isset($form_data['list_count']) ? intval($form_data['list_count']) : 10;
        $list_criteria = isset($form_data['list_criteria']) ? sanitize_text_field($form_data['list_criteria']) : '';
        
        $prompt = "**نقش شما:** شما یک وبلاگ‌نویس حرفه‌ای هستید که در نوشتن مقالات لیستی (Listicles) جذاب، ویروسی و مفید تخصص دارید. وظیفه شما نوشتن یک مقاله لیستی با عنوان \"$list_topic\" است.

**هدف اصلی:** ایجاد یک مقاله مرجع که کاربران آن را ذخیره کرده و به دیگران به اشتراک بگذارند.

**مراحل اجرای کار:**

**مرحله ۱: تحقیق و انتخاب آیتم‌های لیست**
1. **انتخاب آیتم‌ها:** لیستی از بهترین و مرتبط‌ترین گزینه‌ها را برای لیست خود تهیه کن. فقط موارد با کیفیت را انتخاب کن.
2. **معیارهای رتبه‌بندی:** مشخص کن بر چه اساسی این آیتم‌ها را رتبه‌بندی می‌کنی ($list_criteria).

**مرحله ۲: طراحی ساختار مقاله لیستی**
* **H1 (عنوان):** یک عنوان جذاب و کلیک‌خور که شامل عدد باشد (مثلاً: \"$list_count $list_topic\").
* **مقدمه:** با یک آمار جالب یا یک داستان کوتاه، اهمیت موضوع را نشان بده و بگو که این لیست چگونه به کاربر کمک خواهد کرد.
* **بدنه اصلی (آیتم‌های لیست):**
  * هر آیتم باید یک تیتر H2 داشته باشد (مثلاً: \"۱. ابزار [نام ابزار]\").
  * برای هر آیتم، بخش‌های زیر را پوشش بده:
    * **معرفی کوتاه:** این ابزار چیست و چه کار می‌کند؟
    * **ویژگی‌های کلیدی:** به صورت بولت‌پوینت، ۳-۴ ویژگی برتر آن را لیست کن.
    * **بهترین کاربرد:** این ابزار برای چه کسانی یا چه کارهایی مناسب‌تر است؟
    * **قیمت‌گذاری:** به طور خلاصه مدل قیمت‌گذاری آن را توضیح بده.
* **بخش ویژه:** یک یا دو آیتم \"افتخاری\" یا \"کمتر شناخته شده\" به انتهای لیست اضافه کن تا مقاله شما منحصر به فرد شود.
* **جدول مقایسه:** یک جدول مقایسه‌ای ساده در انتهای مقاله ایجاد کن که آیتم‌های اصلی را بر اساس معیارهای کلیدی مقایسه کند.
* **نتیجه‌گیری:** یک جمع‌بندی کوتاه ارائه بده و شاید انتخاب شخصی خودت را به عنوان \"بهترین گزینه کلی\" معرفی کن.

**مرحله ۳: نگارش محتوا**
* **لحن جذاب و پرانرژی:** از لحنی استفاده کن که خواننده را تا انتهای لیست نگه دارد.
* **توصیفات قانع‌کننده:** برای هر آیتم، به وضوح توضیح بده که چه ارزشی برای کاربر ایجاد می‌کند.
* **کلمات کلیدی:** از کلماتی مانند \"بهترین\"، \"برترین\"، \"نقد و بررسی\" در سراسر متن استفاده کن.

**خروجی نهایی:**
یک مقاله لیستی جذاب با حداقل **۱۸۰۰ کلمه** که آماده انتشار در وردپرس باشد. از نوشتن \"عنوان:\" و غیره خودداری کن.";
        
    } elseif (isset($form_data['item1']) && isset($form_data['item2'])) {
        // قالب مقاله مقایسه‌ای
        $item1 = sanitize_text_field($form_data['item1']);
        $item2 = sanitize_text_field($form_data['item2']);
        $comparison_criteria = isset($form_data['comparison_criteria']) ? sanitize_text_field($form_data['comparison_criteria']) : '';
        
        $prompt = "**نقش شما:** شما یک تحلیل‌گر و منتقد بی‌طرف هستید. وظیفه شما نوشتن یک مقاله مقایسه‌ای عمیق و منصفانه بین \"$item1\" و \"$item2\" است.

**هدف اصلی:** کمک به کاربر برای گرفتن یک تصمیم آگاهانه بر اساس نیازها و شرایط خاص خودش.

**مراحل اجرای کار:**

**مرحله ۱: تعیین معیارهای مقایسه**
لیستی از مهم‌ترین معیارهایی که برای مقایسه این دو آیتم باید در نظر گرفته شود، تهیه کن (حداقل ۷ معیار). $comparison_criteria

**مرحله ۲: طراحی ساختار مقاله**
* **H1 (عنوان):** عنوانی که به وضوح مقایسه را نشان دهد (مثلاً: \"$item1 در مقابل $item2: کدام برای شما بهتر است؟\").
* **مقدمه:** هر دو آیتم را به طور خلاصه معرفی کن و بگو که در انتهای این مقاله، کاربر قادر به انتخاب بهترین گزینه خواهد بود.
* **جدول مقایسه سریع:** یک جدول در ابتدای مقاله قرار بده که به صورت خلاصه، دو آیتم را بر اساس معیارهای اصلی مقایسه کند و برنده هر بخش را مشخص کند.
* **مقایسه تفصیلی (Head-to-Head):**
  * این بخش اصلی مقاله است. برای هر معیاری که در مرحله ۱ مشخص کردی، یک تیتر H2 ایجاد کن (مثلاً: \"مقایسه ویژگی‌ها\").
  * در زیر هر H2، توضیح بده که هر کدام از آیتم‌ها در آن معیار چگونه عمل می‌کنند و در نهایت یک \"برنده\" برای آن معیار خاص اعلام کن و دلیلش را توضیح بده.
* **بخش \"چه زمانی $item1 را انتخاب کنیم؟\":** به طور مشخص توضیح بده که $item1 برای چه نوع کاربرانی یا چه پروژه‌هایی مناسب‌تر است.
* **بخش \"چه زمانی $item2 را انتخاب کنیم؟\":** به طور مشخص توضیح بده که $item2 برای چه نوع کاربرانی یا چه پروژه‌هایی مناسب‌تر است.
* **نتیجه‌گیری نهایی:** یک جمع‌بندی کامل ارائه بده و یک توصیه نهایی بر اساس سناریوهای مختلف ارائه کن. **از اعلام یک برنده قطعی برای همه پرهیز کن.**

**مرحله ۳: نگارش محتوا**
* **بی‌طرفی:** سعی کن در سراسر متن منصف و بی‌طرف باشی. هر دو جنبه مثبت و منفی را ذکر کن.
* **استفاده از داده:** در صورت امکان، به داده‌ها، آمار یا تست‌های عملکردی (به صورت فرضی) برای پشتیبانی از ادعاهای خود اشاره کن.
* **کلمات کلیدی:** از عباراتی مانند \"$item1 vs $item2\"، \"مقایسه $item1 و $item2\"، \"تفاوت‌های $item1 و $item2\" و \"کدام بهتر است\" استفاده کن.

**خروجی نهایی:**
یک مقاله مقایسه‌ای دقیق و جامع با حداقل **۲۰۰۰ کلمه** که به کاربر در تصمیم‌گیری کمک شایانی کند. مقاله باید آماده انتشار مستقیم در وردپرس باشد.";
        
    } else {
        // قالب پیش‌فرض
        $main_topic = isset($form_data['main_topic']) ? sanitize_text_field($form_data['main_topic']) : 'موضوع مقاله';
        
        $prompt = "**نقش شما:** شما یک متخصص تولید محتوا و نویسنده حرفه‌ای هستید. وظیفه شما ایجاد یک مقاله جامع و با کیفیت برای موضوع \"$main_topic\" است.

**هدف اصلی:** تولید مقاله‌ای که ارزش واقعی برای خواننده ایجاد کند و در موتورهای جستجو عملکرد خوبی داشته باشد.

**مراحل اجرای کار:**

**مرحله ۱: تحقیق و تحلیل**
1. **شناسایی مخاطب هدف:** مشخص کن این مقاله برای چه کسانی نوشته می‌شود
2. **تحقیق کلمات کلیدی:** کلمات کلیدی مرتبط با موضوع را شناسایی کن
3. **تحلیل رقبا:** نقاط قوت و ضعف مقالات موجود را بررسی کن

**مرحله ۲: طراحی ساختار**
* **H1 (عنوان اصلی):** عنوانی جذاب و سئو شده
* **مقدمه:** معرفی موضوع و ایجاد کنجکاوی
* **بدنه اصلی:** حداقل ۵ بخش H2 با زیربخش‌های H3
* **نتیجه‌گیری:** جمع‌بندی نکات کلیدی

**مرحله ۳: نگارش محتوا**
* **لحن حرفه‌ای و قابل فهم**
* **پاراگراف‌های کوتاه و خوانا**
* **استفاده از لیست‌ها و مثال‌ها**
* **لینک‌دهی مناسب**

**خروجی نهایی:**
یک مقاله جامع با حداقل **۱۵۰۰ کلمه** که آماده انتشار در وردپرس باشد.";
    }
    
    return $prompt;
}

// صفحه ادمین هوشمند
function smart_admin_page() {
    // بررسی مجوز دسترسی
    if (!current_user_can('manage_options')) {
        wp_die(__('شما اجازه دسترسی به این صفحه را ندارید.'));
    }
    
    // پیام موفقیت برای نمایش
    $success_message = '';
    
    // ذخیره پرامپت و درخواست به API
    if (isset($_POST['smart_admin_prompt']) || (isset($_POST['is_template']) && $_POST['is_template'] == '1')) {
        // افزودن نانس برای امنیت
        check_admin_referer('smart_admin_prompt_action', 'smart_admin_nonce');
        
        $model = sanitize_text_field($_POST['smart_admin_model']);
        $api_key = get_option('smart_admin_api_key');
        
        // اگر فرم قالب است، پرامپت را بر اساس فیلدهای فرم بساز
        if (isset($_POST['is_template']) && $_POST['is_template'] == '1') {
            $prompt = build_template_prompt($_POST);
        } else {
            $prompt = sanitize_textarea_field($_POST['smart_admin_prompt']);
        }
        
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
        
        // ذخیره خودکار پیش‌نویس برای قالب‌های آماده
        if (isset($_POST['is_template']) && $_POST['is_template'] == '1' && isset($response['content']) && !empty($response['content'])) {
            // استخراج عنوان از فیلدهای فرم یا استفاده از عنوان پیش‌فرض
            $title = '';
            // استخراج عنوان SEO شده از پاسخ هوش مصنوعی
            $main_topic = !empty($_POST['main_topic']) ? sanitize_text_field($_POST['main_topic']) : '';
            $ai_title = smart_admin_extract_seo_title($response['content'], $main_topic);
            
            if (!empty($ai_title)) {
                $title = $ai_title;
            } elseif (!empty($_POST['main_topic'])) {
                $title = sanitize_text_field($_POST['main_topic']);
            } elseif (!empty($_POST['focus_keyword'])) {
                $title = sanitize_text_field($_POST['focus_keyword']);
            } else {
                $title = 'محتوا تولید شده توسط دستیار هوشمند';
            }
            
            $content = wp_kses_post($response['content']);
            
            // استخراج کلمات کلیدی از فیلدهای فرم
            $keywords = array();
            if (!empty($_POST['focus_keyword'])) {
                $keywords[] = sanitize_text_field($_POST['focus_keyword']);
            }
            if (!empty($_POST['main_topic'])) {
                $keywords[] = sanitize_text_field($_POST['main_topic']);
            }
            
            // ذخیره محتوا به عنوان پیش‌نویس
            $post_id = smart_admin_save_ai_content_as_draft($title, $content, $keywords);
            
            if (!is_wp_error($post_id)) {
                $success_message = 'محتوا با موفقیت تولید و به عنوان پیش‌نویس ذخیره شد. <a href="' . get_edit_post_link($post_id) . '" target="_blank">مشاهده و ویرایش</a>';
            }
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
        
        /* استایل‌های مودال فرم قالب‌ها */
        .template-form-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .template-form-content {
            background: white;
            border-radius: 10px;
            padding: 30px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
        }
        
        .template-form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        
        .template-form-header h3 {
            margin: 0;
            color: #2c3e50;
        }
        
        .close-template-form {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .close-template-form:hover {
            color: #e74c3c;
        }
        
        .template-card-description {
            color: #666;
            font-size: 14px;
            line-height: 1.4;
            margin-bottom: 15px;
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
                        
                        <?php if (isset($response['generated_image'])): ?>
                            <div class="generated-image-container">
                                <h4>تصویر تولید شده:</h4>
                                <div class="generated-image">
                                    <img src="<?php echo esc_url($response['generated_image']['image_url']); ?>" alt="تصویر تولید شده" style="max-width: 100%; height: auto; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                                    <div class="image-info">
                                        <p><strong>پرامپت تصویر:</strong> <?php echo esc_html($response['generated_image']['prompt']); ?></p>
                                        <button type="button" class="button button-secondary" onclick="downloadImage('<?php echo esc_url($response['generated_image']['image_url']); ?>', 'generated-image')">دانلود تصویر</button>
                                    </div>
                                </div>
                            </div>
                            
                            <style>
                                .generated-image-container {
                                    margin-top: 20px;
                                    padding: 15px;
                                    background: #f9f9f9;
                                    border-radius: 8px;
                                    border-left: 4px solid #0073aa;
                                }
                                .generated-image {
                                    text-align: center;
                                }
                                .image-info {
                                    margin-top: 10px;
                                    text-align: center;
                                }
                                .image-info p {
                                    margin-bottom: 10px;
                                    color: #666;
                                }
                            </style>
                            
                            <script>
                            function downloadImage(imageUrl, filename) {
                                const link = document.createElement('a');
                                link.href = imageUrl;
                                link.download = filename + '.jpg';
                                document.body.appendChild(link);
                                link.click();
                                document.body.removeChild(link);
                            }
                            </script>
                        <?php endif; ?>
                        
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
            <p>از این قالب‌های آماده و بهینه‌سازی شده برای تولید محتوای با کیفیت استفاده کنید. کافیست فیلدهای زیر را پر کنید.</p>
            
            <div class="templates-grid">
                <?php 
                $default_prompts = get_default_content_prompts();
                foreach ($default_prompts as $index => $prompt): 
                ?>
                    <div class="template-card">
                        <h3><?php echo esc_html($prompt['title']); ?></h3>
                        <span class="template-card-model"><?php echo esc_html($prompt['model']); ?></span>
                        <div class="template-card-description">
                            <?php 
                            // نمایش توضیحات کوتاه برای هر قالب
                            $descriptions = array(
                                'مقاله جامع و بنیادی (Pillar Page)' => 'ایجاد مقاله جامع و معتبر برای موضوع انتخابی شما',
                                'مقاله به روش آسمان‌خراش (Skyscraper)' => 'نوشتن مقاله بهتر از رقبا با محتوای برتر',
                                'راهنمای عملی قدم به قدم (How-to)' => 'آموزش مرحله به مرحله برای انجام کارهای مختلف',
                                'مقاله لیستی (مثلا: ۱۰ ابزار برتر)' => 'ایجاد لیست‌های جذاب و مفید',
                                'مقاله مقایسه‌ای (X در مقابل Y)' => 'مقایسه دقیق بین دو گزینه مختلف',
                                'مقاله کاملاً استاندارد برای Rank Math (امتیاز 90+)' => 'تولید مقاله با امتیاز بالای ۹۰ در Rank Math'
                            );
                            $description = isset($descriptions[$prompt['title']]) ? $descriptions[$prompt['title']] : 'قالب آماده برای تولید محتوا';
                            echo esc_html($description);
                            ?>
                        </div>
                        <div class="template-card-actions">
                            <button type="button" class="use-template-btn" 
                                data-template-index="<?php echo $index; ?>"
                                data-template-title="<?php echo esc_attr($prompt['title']); ?>"
                                data-model="<?php echo esc_attr($prompt['model']); ?>"
                                data-is-template="1">
                                استفاده از قالب
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- فرم داینامیک برای قالب‌ها -->
            <div id="template-form-modal" style="display: none;" class="template-form-modal">
                <div class="template-form-content">
                    <div class="template-form-header">
                        <h3 id="template-form-title">قالب انتخاب شده</h3>
                        <button type="button" class="close-template-form">&times;</button>
                    </div>
                    <form id="template-form" method="post" class="prompt-form">
                        <?php wp_nonce_field('smart_admin_prompt_action', 'smart_admin_nonce'); ?>
                        <input type="hidden" id="template-model" name="smart_admin_model" value="">
                        <input type="hidden" id="template-prompt" name="smart_admin_prompt" value="">
                        <input type="hidden" id="is_template" name="is_template" value="1">
                        
                        <div id="template-fields">
                            <!-- فیلدهای داینامیک اینجا اضافه می‌شوند -->
                        </div>
                        
                        <?php add_human_tone_option_to_form(); ?>
                        
                        <button type="submit" class="submit-button" id="generate-template-btn">
                            <span id="loading-spinner-template" class="loading-spinner"></span>
                            <span>تولید محتوا</span>
                        </button>
                    </form>
                </div>
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
                        <optgroup label="OpenAI">
                            <option value="gpt-4o" <?php selected(get_option('smart_admin_model'), 'gpt-4o'); ?>>GPT-4o - مدل پیشرفته OpenAI با قابلیت‌های چندمنظوره</option>
                        </optgroup>
                        
                        <optgroup label="Anthropic">
                            <option value="claude-opus-4-20250514" <?php selected(get_option('smart_admin_model'), 'claude-opus-4-20250514'); ?>>Claude Opus 4 - مدل پیشرفته Anthropic با قابلیت‌های فوق‌العاده</option>
                            <option value="claude-sonnet-4-20250514" <?php selected(get_option('smart_admin_model'), 'claude-sonnet-4-20250514'); ?>>Claude Sonnet 4 - مدل متعادل Anthropic با کارایی بالا</option>
                            <option value="claude-3-7-sonnet-20250219" <?php selected(get_option('smart_admin_model'), 'claude-3-7-sonnet-20250219'); ?>>Claude 3.7 Sonnet - مدل متعادل Anthropic با کارایی بالا</option>
                            <option value="claude-3-7-sonnet-20250219-thinking" <?php selected(get_option('smart_admin_model'), 'claude-3-7-sonnet-20250219-thinking'); ?>>Claude 3.7 Sonnet Thinking - مدل با قابلیت تفکر عمیق</option>
                            <option value="claude-3-5-haiku-20241022" <?php selected(get_option('smart_admin_model'), 'claude-3-5-haiku-20241022'); ?>>Claude 3.5 Haiku - مدل سریع و به‌صرفه Anthropic</option>
                        </optgroup>
                        
                        <optgroup label="Google">
                            <option value="gemini-2.0-flash" <?php selected(get_option('smart_admin_model'), 'gemini-2.0-flash'); ?>>Gemini 2.0 Flash - مدل ارزان و به‌صرفه Google</option>
                            <option value="gemini-2.0-flash-preview-image-generation" <?php selected(get_option('smart_admin_model'), 'gemini-2.0-flash-preview-image-generation'); ?>>Gemini 2.0 Flash Preview - مدل با قابلیت تولید تصویر</option>
                            <option value="gemini-2.0-flash-lite-001" <?php selected(get_option('smart_admin_model'), 'gemini-2.0-flash-lite-001'); ?>>Gemini 2.0 Flash Lite - مدل سبک و سریع</option>
                            <option value="gemini-2.0-flash-lite-preview" <?php selected(get_option('smart_admin_model'), 'gemini-2.0-flash-lite-preview'); ?>>Gemini 2.0 Flash Lite Preview - نسخه پیش‌نمایش</option>
                            <option value="gemini-2.0-flash-live-001" <?php selected(get_option('smart_admin_model'), 'gemini-2.0-flash-live-001'); ?>>Gemini 2.0 Flash Live - مدل زنده و به‌روز</option>
                            <option value="gemini-2.5-flash-preview-native-audio-dialog" <?php selected(get_option('smart_admin_model'), 'gemini-2.5-flash-preview-native-audio-dialog'); ?>>Gemini 2.5 Flash - مدل با قابلیت صوتی</option>
                            <option value="gemini-2.5-flash" <?php selected(get_option('smart_admin_model'), 'gemini-2.5-flash'); ?>>Gemini 2.5 Flash - مدل پیشرفته</option>
                            <option value="gemini-1.5-pro" <?php selected(get_option('smart_admin_model'), 'gemini-1.5-pro'); ?>>Gemini 1.5 Pro - مدل حرفه‌ای</option>
                        </optgroup>
                        
                        <optgroup label="DeepSeek">
                            <option value="deepseek-chat" <?php selected(get_option('smart_admin_model'), 'deepseek-chat'); ?>>DeepSeek Chat - مدل چت هوشمند</option>
                            <option value="deepseek-reasoner" <?php selected(get_option('smart_admin_model'), 'deepseek-reasoner'); ?>>DeepSeek Reasoner - مدل با قابلیت استدلال</option>
                        </optgroup>
                        
                        <optgroup label="Alibaba">
                            <option value="qwen3-coder-480b-a35b-instruct" <?php selected(get_option('smart_admin_model'), 'qwen3-coder-480b-a35b-instruct'); ?>>Qwen3 Coder - مدل تخصصی برنامه‌نویسی</option>
                            <option value="qwen3-235b-a22b" <?php selected(get_option('smart_admin_model'), 'qwen3-235b-a22b'); ?>>Qwen3 235B - مدل بزرگ و قدرتمند</option>
                        </optgroup>
                    </select>
                </div>
                
                <?php submit_button('ذخیره تنظیمات', 'submit-button', 'submit', false); ?>
            </form>
            
            <hr>
            <h3>اطلاعات قیمت‌گذاری مدل‌ها</h3>
            <p>جدول زیر قیمت‌های تقریبی برای ۱ میلیون توکن را نشان می‌دهد:</p>
            
            <div class="model-pricing-table">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>ارائه‌دهنده</th>
                            <th>مدل</th>
                            <th>قیمت ورودی (هر ۱M توکن)</th>
                            <th>قیمت خروجی (هر ۱M توکن)</th>
                            <th>نوع</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>OpenAI</strong></td>
                            <td>GPT-4o</td>
                            <td>$5.00</td>
                            <td>$15.00</td>
                            <td>پیشرفته</td>
                        </tr>
                        <tr>
                            <td><strong>Anthropic</strong></td>
                            <td>Claude Opus 4</td>
                            <td>$15.00</td>
                            <td>$75.00</td>
                            <td>فوق‌العاده</td>
                        </tr>
                        <tr>
                            <td><strong>Anthropic</strong></td>
                            <td>Claude Sonnet 4</td>
                            <td>$3.00</td>
                            <td>$15.00</td>
                            <td>متعادل</td>
                        </tr>
                        <tr>
                            <td><strong>Anthropic</strong></td>
                            <td>Claude 3.7 Sonnet</td>
                            <td>$3.00</td>
                            <td>$15.00</td>
                            <td>متعادل</td>
                        </tr>
                        <tr>
                            <td><strong>Anthropic</strong></td>
                            <td>Claude 3.5 Haiku</td>
                            <td>$1.00</td>
                            <td>$5.00</td>
                            <td>سریع</td>
                        </tr>
                        <tr>
                            <td><strong>Google</strong></td>
                            <td>Gemini 2.0 Flash</td>
                            <td>$0.07</td>
                            <td>$0.30</td>
                            <td>به‌صرفه</td>
                        </tr>
                        <tr>
                            <td><strong>Google</strong></td>
                            <td>Gemini 2.5 Flash</td>
                            <td>$0.30</td>
                            <td>$2.50</td>
                            <td>پیشرفته</td>
                        </tr>
                        <tr>
                            <td><strong>DeepSeek</strong></td>
                            <td>DeepSeek Chat</td>
                            <td>$0.27</td>
                            <td>$1.08</td>
                            <td>متعادل</td>
                        </tr>
                        <tr>
                            <td><strong>DeepSeek</strong></td>
                            <td>DeepSeek Reasoner</td>
                            <td>$0.55</td>
                            <td>$2.20</td>
                            <td>استدلالی</td>
                        </tr>
                        <tr>
                            <td><strong>Alibaba</strong></td>
                            <td>Qwen3 Coder</td>
                            <td>$3.00</td>
                            <td>$12.00</td>
                            <td>برنامه‌نویسی</td>
                        </tr>
                        <tr>
                            <td><strong>Alibaba</strong></td>
                            <td>Qwen3 235B</td>
                            <td>$0.16</td>
                            <td>$0.48</td>
                            <td>به‌صرفه</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <style>
                .model-pricing-table {
                    margin-top: 20px;
                }
                .model-pricing-table table {
                    font-size: 13px;
                }
                .model-pricing-table th {
                    background-color: #f1f1f1;
                    font-weight: bold;
                }
                .model-pricing-table td {
                    padding: 8px;
                }
            </style>
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
        
        // استفاده از قالب‌های آماده با فرم داینامیک
        const useTemplateButtons = document.querySelectorAll('.use-template-btn');
        useTemplateButtons.forEach(button => {
            button.addEventListener('click', function() {
                const templateIndex = this.getAttribute('data-template-index');
                const templateTitle = this.getAttribute('data-template-title');
                const model = this.getAttribute('data-model');
                
                // نمایش مودال فرم
                showTemplateForm(templateIndex, templateTitle, model);
            });
        });
        
        // بستن مودال فرم قالب
        const closeTemplateForm = document.querySelector('.close-template-form');
        if (closeTemplateForm) {
            closeTemplateForm.addEventListener('click', function() {
                document.getElementById('template-form-modal').style.display = 'none';
            });
        }
        
        // بستن مودال با کلیک روی پس‌زمینه
        const templateFormModal = document.getElementById('template-form-modal');
        if (templateFormModal) {
            templateFormModal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.style.display = 'none';
                }
            });
        }
        
        // نمایش اسپینر برای فرم قالب
        const templateForm = document.getElementById('template-form');
        const loadingSpinnerTemplate = document.getElementById('loading-spinner-template');
        
        if (templateForm) {
            templateForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // ساخت پرامپت بر اساس فیلدهای فرم
                const formData = new FormData(this);
                const prompt = buildPromptFromFormData(formData);
                
                // تنظیم پرامپت در فیلد مخفی
                document.getElementById('template-prompt').value = prompt;
                
                // نمایش اسپینر
                loadingSpinnerTemplate.style.display = 'inline-block';
                
                // ارسال فرم
                this.submit();
            });
        }
        
        // تابع ساخت پرامپت از فیلدهای فرم
        function buildPromptFromFormData(formData) {
            const mainTopic = formData.get('main_topic') || '';
            const focusKeyword = formData.get('focus_keyword') || '';
            const targetAudience = formData.get('target_audience') || '';
            const contentType = formData.get('content_type') || '';
            const contentLength = formData.get('content_length') || '1200';
            const howToTopic = formData.get('how_to_topic') || '';
            const skillLevel = formData.get('skill_level') || '';
            const requiredTools = formData.get('required_tools') || '';
            const listTopic = formData.get('list_topic') || '';
            const listCount = formData.get('list_count') || '10';
            const listCriteria = formData.get('list_criteria') || '';
            const item1 = formData.get('item1') || '';
            const item2 = formData.get('item2') || '';
            const comparisonCriteria = formData.get('comparison_criteria') || '';
            const competitorAnalysis = formData.get('competitor_analysis') || '';
            const uniqueValue = formData.get('unique_value') || '';
            const contentGoal = formData.get('content_goal') || '';
            
            // تشخیص نوع قالب بر اساس فیلدهای موجود
            if (mainTopic && focusKeyword) {
                // قالب Rank Math استاندارد
                return `**نقش شما:** شما یک متخصص ارشد SEO و تولیدکننده محتوای حرفه‌ای هستید که در بهینه‌سازی محتوا برای Rank Math و گوگل تخصص دارید. وظیفه شما ایجاد یک مقاله کاملاً استاندارد برای موضوع "${mainTopic}" است که امتیاز بالای ۹۰ در Rank Math کسب کند.

**هدف اصلی:** تولید مقاله‌ای که تمام استانداردهای SEO را رعایت کند و در گوگل عملکرد عالی داشته باشد.

**مراحل اجرای کار (بسیار مهم):**

**مرحله ۱: تحلیل کلمه کلیدی و تحقیق**
1. **کلمه کلیدی اصلی:** "${focusKeyword}" - این کلمه کلیدی باید:
   - در عنوان (Title) قرار گیرد
   - در آدرس URL استفاده شود
   - در پاراگراف اول متن وجود داشته باشد
   - در توضیحات متا (Meta Description) بیاید
   - در حداقل یکی از تیترهای H2 یا H3 استفاده شود
   - حداقل ۵ بار در متن مقاله تکرار شود (تراکم مناسب ۱٪ - ۲٪)

2. **کلمات کلیدی فرعی (LSI):** حداقل ۱۰ کلمه کلیدی مرتبط معنایی پیدا کن
3. **کلمات کلیدی طولانی (Long-tail):** حداقل ۵ سوال رایج کاربران را شناسایی کن

**مرحله ۲: طراحی ساختار مقاله کاملاً استاندارد**
* **H1 (عنوان اصلی):** حداکثر ۶۰ کاراکتر، دارای کلمه کلیدی اصلی، جذاب همراه با عدد، سال یا سوال
* **مقدمه قدرتمند:** شامل کلمه کلیدی، معرفی موضوع + ایجاد کنجکاوی، ترجیحاً با Bold و فونت متفاوت
* **بدنه اصلی:** حداقل ۵ بخش H2، هر بخش شامل ۲-۳ زیربخش H3
* **نتیجه‌گیری:** جمع‌بندی نکات کلیدی + دعوت به اقدام
* **بخش FAQ:** حداقل ۳ سوال متداول با پاسخ‌های دقیق

**مرحله ۳: بهینه‌سازی کامل محتوا**

**۳-۱: بهینه‌سازی عنوان و متا**
- **عنوان (Meta Title):** حداکثر ۶۰ کاراکتر، شامل کلمه کلیدی، جذاب با عدد/سال/سوال
- **توضیحات متا (Meta Description):** حداکثر ۱۶۰ کاراکتر، شامل کلمه کلیدی، خلاصه جذاب و قابل کلیک
- **آدرس URL:** کوتاه و شامل کلمه کلیدی (مثال: yoursite.com/web-programming-guide)

**۳-۲: بهینه‌سازی محتوای اصلی**
- **پاراگراف اول:** شامل کلمه کلیدی، معرفی موضوع + ایجاد کنجکاوی، با Bold برجسته شود
- **تراکم کلمه کلیدی:** حدود ۱٪ تا ۲٪ (نه کمتر، نه بیشتر)
- **خوانایی:** جملات کوتاه (زیر ۲۰ کلمه)، پاراگراف‌های کمتر از ۵ خط، استفاده از لیست‌های Bullet و Number
- **طول مقاله:** حداقل ${contentLength} کلمه

**۳-۳: لینک‌دهی و تصاویر**
- **لینک‌دهی داخلی:** حداقل ۲ لینک به صفحات مرتبط دیگر سایت
- **لینک‌دهی خارجی:** حداقل یک لینک به منبع معتبر (با rel="noopener" و target="_blank")
- **تصاویر:** حداقل یک تصویر، متن ALT شامل کلمه کلیدی اصلی

**۳-۴: Schema و بهینه‌سازی پیشرفته**
- **Schema نوع:** "مقاله / Article" یا "راهنما / HowTo"
- **جدول محتوا:** با لینک‌دهی داخلی به هدینگ‌ها
- **واکنش‌گرا بودن:** ساختار مناسب برای موبایل، تبلت و دسکتاپ

**مرحله ۴: تولید محتوای نهایی**

**۴-۱: نگارش محتوا با رعایت تمام استانداردها**
- لحن معتبر، حرفه‌ای اما روان و قابل فهم
- استفاده از سبک نگارش "هرم معکوس" (مهم‌ترین اطلاعات در ابتدا)
- پاراگراف‌های کوتاه (حداکثر ۳-۴ جمله)
- استفاده از لیست‌های شماره‌دار و بولت‌پوینت
- نقل قول و متن برجسته برای شکستن یکنواختی

**۴-۲: غنی‌سازی محتوا**
- آمار و ارقام (با ذکر منبع فرضی)
- مثال‌های عملی و مطالعات موردی
- پیشنهادات برای افزودن اینفوگرافیک یا ویدیو
- استفاده از کلمات کلیدی فرعی و طولانی به صورت طبیعی

**۴-۳: بهینه‌سازی نهایی**
- بررسی تراکم کلمه کلیدی (۱٪ - ۲٪)
- اطمینان از وجود کلمه کلیدی در تمام بخش‌های مهم
- بررسی خوانایی و ساختار متن
- اطمینان از وجود تمام عناصر SEO

**خروجی نهایی:**
یک مقاله کاملاً استاندارد با حداقل **${contentLength} کلمه** که تمام استانداردهای SEO را رعایت کند و آماده انتشار مستقیم در وردپرس باشد. مقاله باید شامل تمام عناصر ذکر شده در چک‌لیست باشد و امتیاز بالای ۹۰ در Rank Math کسب کند.

**نکات مهم:**
- از تکرار بیش از حد کلمات کلیدی (Keyword Stuffing) به شدت پرهیز کن
- محتوا باید طبیعی و خوانا باشد
- تمام عناصر SEO باید به صورت یکپارچه در متن گنجانده شوند
- مقاله باید ارزش واقعی برای کاربر ایجاد کند`;
                
            } else if (mainTopic && targetAudience) {
                // قالب مقاله جامع و بنیادی
                return `**نقش شما:** شما یک استراتژیست ارشد محتوا و متخصص SEO با بیش از ۱۰ سال تجربه هستید. وظیفه شما ایجاد یک مقاله بنیادی (Pillar Page) بسیار جامع، معتبر و کاملاً بهینه‌سازی شده برای موضوع "${mainTopic}" است. این مقاله باید به عنوان منبع اصلی و نهایی برای این موضوع در وب فارسی عمل کند.

**مراحل اجرای کار (بسیار مهم):**

**مرحله ۱: تحلیل عمیق و تحقیق کلمات کلیدی**
1. **شناسایی هدف جستجو (Search Intent):** ابتدا مشخص کن که کاربرانی که در مورد "${mainTopic}" جستجو می‌کنند، به دنبال چه نوع اطلاعاتی هستند؟ (مثلاً اطلاعاتی، آموزشی، راهنمای خرید).
2. **تحقیق کلمات کلیدی:** لیستی از کلمات کلیدی مرتبط تهیه کن:
   * **کلمه کلیدی اصلی:** ${mainTopic}
   * **کلمات کلیدی فرعی (LSI):** حداقل ۱۰ کلمه کلیدی مرتبط معنایی پیدا کن.
   * **کلمات کلیدی طولانی (Long-tail):** حداقل ۵ سوال رایج که کاربران در این مورد می‌پرسند را شناسایی کن (مثلاً "چگونه X را انجام دهیم؟"، "بهترین Y برای Z چیست؟").
3. **تحلیل رقبا:** به صورت فرضی، ۳ مقاله برتر در نتایج جستجوی گوگل برای این موضوع را تحلیل کن. نقاط قوت و ضعف آن‌ها چیست؟ چه شکاف‌های محتوایی وجود دارد که ما می‌توانیم آن‌ها را پر کنیم؟

**مرحله ۲: طراحی ساختار مقاله (Blueprint)**
بر اساس تحقیقات مرحله ۱، یک ساختار درختی و دقیق برای مقاله طراحی کن. این ساختار باید شامل موارد زیر باشد:
* **H1 (عنوان اصلی):** یک عنوان جذاب، منحصر به فرد و سئو شده (حاوی کلمه کلیدی اصلی).
* **مقدمه:** یک مقدمه قدرتمند که با یک "قلاب" (Hook) شروع می‌شود، مشکل کاربر را بیان می‌کند و قول یک راه‌حل جامع را می‌دهد.
* **H2 (فصل‌های اصلی):** مقاله را به بخش‌های منطقی (حداقل ۵ فصل) تقسیم کن. هر H2 باید یکی از جنبه‌های اصلی موضوع را پوشش دهد.
* **H3 (زیرفصل‌ها):** هر H2 را به H3 های مرتبط‌تر تقسیم کن تا خوانایی افزایش یابد.
* **نتیجه‌گیری:** یک جمع‌ بندی کامل که نکات کلیدی را مرور کرده و یک دیدگاه نهایی ارائه می‌دهد.
* **بخش پرسش‌های متداول (FAQ):** بر اساس کلمات کلیدی طولانی (سوالات) که پیدا کردی، یک بخش FAQ با پاسخ‌های دقیق و کوتاه ایجاد کن.

**مرحله ۳: نگارش و تولید محتوا**
با استفاده از ساختار بالا، شروع به نوشتن مقاله کن. نکات زیر را **با دقت** رعایت کن:
* **لحن و سبک:** لحنی معتبر، حرفه‌ای اما روان و قابل فهم. از سبک نگارش "هرم معکوس" استفاده کن (مهم‌ترین اطلاعات در ابتدا).
* **خوانایی:** پاراگراف‌ها را کوتاه (حداکثر ۳-۴ جمله) نگه دار. از لیست‌های شماره‌دار و بولت‌پوینت، **نقل قول** و **متن برجسته** برای شکستن یکنواختی متن استفاده کن.
* **چگالی کلمات کلیدی:** کلمه کلیدی اصلی را در مقدمه، نتیجه‌گیری و یکی دو تا از H2 ها به کار ببر. از کلمات کلیدی فرعی و طولانی به صورت طبیعی در سراسر متن استفاده کن. **از تکرار بیش از حد (Keyword Stuffing) به شدت پرهیز کن.**
* **غنی‌سازی محتوا:** برای اعتبار بخشیدن به متن، به **آمار و ارقام** (با ذکر منبع فرضی)، **مثال‌های عملی** و **مطالعات موردی** اشاره کن. پیشنهاداتی برای افزودن **اینفوگرافیک** یا **ویدیو** در بخش‌های مرتبط ارائه بده.
* **لینک‌دهی داخلی:** حداقل ۳ پیشنهاد برای لینک داخلی به مقالات مرتبط دیگر (با موضوعات فرضی) در متن بگنجان.

**مرحله ۴: بهینه‌سازی نهایی و CTA**
1. **بهینه‌سازی عنوان و توضیحات متا:** یک عنوان سئو (کمتر از ۶۰ کاراکتر) و یک توضیحات متا (کمتر از ۱۶۰ کاراکتر) جذاب و حاوی کلمه کلیدی اصلی پیشنهاد بده.
2. **دعوت به اقدام (Call to Action):** در انتهای مقاله، یک CTA مرتبط و هوشمندانه قرار بده (مثلاً دعوت به دانلود یک کتاب الکترونیکی، ثبت‌نام در وبینار یا مطالعه یک مقاله مرتبط دیگر).

**خروجی نهایی:**
مقاله باید حداقل **۲۵۰۰ کلمه** باشد، کاملاً ساختاریافته، فاقد هرگونه اطلاعات نادرست و آماده انتشار مستقیم در وردپرس باشد. لطفاً از نوشتن عباراتی مانند "عنوان مقاله:" یا "مقدمه:" خودداری کن و مستقیماً محتوا را تولید نما.`;
                
            } else if (howToTopic) {
                // قالب راهنمای عملی
                return `**نقش شما:** شما یک مربی و نویسنده فنی هستید که در نوشتن راهنماهای عملی، واضح و قدم به قدم تخصص دارید. وظیفه شما ایجاد یک راهنمای کامل برای "${howToTopic}" است.

**هدف اصلی:** کاربر باید بتواند **فقط با خواندن این راهنما**، کار مورد نظر را با موفقیت و بدون هیچ مشکلی انجام دهد.

**مراحل اجرای کار:**

**مرحله ۱: برنامه‌ریزی راهنما**
1. **شناسایی مخاطب:** این راهنما برای چه کسی است؟ (${skillLevel}). سطح دانش فنی او را در نظر بگیر.
2. **لیست ابزار و پیش‌نیازها:** یک بخش در ابتدای مقاله با عنوان "آنچه قبل از شروع نیاز دارید" ایجاد کن و تمام ابزارها، مواد اولیه یا نرم‌افزارهای مورد نیاز را لیست کن.
3. **تقسیم فرآیند به مراحل:** کل فرآیند را به مراحل اصلی و قابل مدیریت (حداقل ۵ مرحله) تقسیم کن. هر مرحله باید یک اقدام مشخص باشد.

**مرحله ۲: طراحی ساختار راهنما**
* **H1 (عنوان):** عنوانی واضح و عملی (مثلاً: "راهنمای قدم به قدم ${howToTopic} برای ${skillLevel}").
* **مقدمه:** به طور خلاصه توضیح بده که در این راهنما چه چیزی آموزش داده می‌شود و نتیجه نهایی چه خواهد بود.
* **بخش پیش‌نیازها.**
* **بدنه اصلی (مراحل):**
  * هر مرحله باید یک تیتر H2 داشته باشد (مثلاً: "مرحله ۱: آماده‌سازی مواد اولیه").
  * برای هر مرحله، دستورالعمل‌ها را به صورت یک لیست شماره‌دار و واضح بنویس.
  * **نکته کلیدی:** بعد از هر چند مرحله، یک بخش "نکات حرفه‌ای" یا "اشتباهات رایج" اضافه کن.
* **بخش عیب‌یابی (Troubleshooting):** یک بخش H2 با عنوان "مشکلات احتمالی و راه‌حل‌ها" ایجاد کن و به ۳-۴ مشکل رایجی که ممکن است کاربر با آن مواجه شود، پاسخ بده.
* **نتیجه‌گیری:** نتیجه کار را جشن بگیر و کاربر را برای موفقیتش تشویق کن.
* **بخش FAQ.**

**مرحله ۳: نگارش محتوا**
* **زبان ساده و امری:** از جملات کوتاه، واضح و دستوری استفاده کن (مثلاً: "فر را روی ۱۸۰ درجه تنظیم کنید.").
* **غنی‌سازی بصری:** در هر مرحله، توضیح بده که چه نوع تصویر، گیف یا ویدیویی می‌تواند به درک بهتر کمک کند (مثلاً: "[تصویر: نمایی نزدیک از هم زدن تخم مرغ‌ها]").
* **تمرکز بر جزئیات:** هیچ مرحله‌ای را ناگفته نگذار. فرض کن کاربر هیچ دانشی در این زمینه ندارد.
* **کلمات کلیدی:** از کلمات کلیدی مرتبط با موضوع به صورت طبیعی استفاده کن.

**خروجی نهایی:**
یک راهنمای بسیار کاربردی با حداقل **۱۵۰۰ کلمه** که به صورت مستقیم در وردپرس قابل استفاده باشد. لحن باید دوستانه، حمایتی و تشویق‌کننده باشد. از نوشتن "عنوان:" و غیره خودداری کن.`;
                
            } else if (listTopic) {
                // قالب مقاله لیستی
                return `**نقش شما:** شما یک وبلاگ‌نویس حرفه‌ای هستید که در نوشتن مقالات لیستی (Listicles) جذاب، ویروسی و مفید تخصص دارید. وظیفه شما نوشتن یک مقاله لیستی با عنوان "${listTopic}" است.

**هدف اصلی:** ایجاد یک مقاله مرجع که کاربران آن را ذخیره کرده و به دیگران به اشتراک بگذارند.

**مراحل اجرای کار:**

**مرحله ۱: تحقیق و انتخاب آیتم‌های لیست**
1. **انتخاب آیتم‌ها:** لیستی از بهترین و مرتبط‌ترین گزینه‌ها را برای لیست خود تهیه کن. فقط موارد با کیفیت را انتخاب کن.
2. **معیارهای رتبه‌بندی:** مشخص کن بر چه اساسی این آیتم‌ها را رتبه‌بندی می‌کنی (${listCriteria}).

**مرحله ۲: طراحی ساختار مقاله لیستی**
* **H1 (عنوان):** یک عنوان جذاب و کلیک‌خور که شامل عدد باشد (مثلاً: "${listCount} ${listTopic}").
* **مقدمه:** با یک آمار جالب یا یک داستان کوتاه، اهمیت موضوع را نشان بده و بگو که این لیست چگونه به کاربر کمک خواهد کرد.
* **بدنه اصلی (آیتم‌های لیست):**
  * هر آیتم باید یک تیتر H2 داشته باشد (مثلاً: "۱. ابزار [نام ابزار]").
  * برای هر آیتم، بخش‌های زیر را پوشش بده:
    * **معرفی کوتاه:** این ابزار چیست و چه کار می‌کند؟
    * **ویژگی‌های کلیدی:** به صورت بولت‌پوینت، ۳-۴ ویژگی برتر آن را لیست کن.
    * **بهترین کاربرد:** این ابزار برای چه کسانی یا چه کارهایی مناسب‌تر است؟
    * **قیمت‌گذاری:** به طور خلاصه مدل قیمت‌گذاری آن را توضیح بده.
* **بخش ویژه:** یک یا دو آیتم "افتخاری" یا "کمتر شناخته شده" به انتهای لیست اضافه کن تا مقاله شما منحصر به فرد شود.
* **جدول مقایسه:** یک جدول مقایسه‌ای ساده در انتهای مقاله ایجاد کن که آیتم‌های اصلی را بر اساس معیارهای کلیدی مقایسه کند.
* **نتیجه‌گیری:** یک جمع‌بندی کوتاه ارائه بده و شاید انتخاب شخصی خودت را به عنوان "بهترین گزینه کلی" معرفی کن.

**مرحله ۳: نگارش محتوا**
* **لحن جذاب و پرانرژی:** از لحنی استفاده کن که خواننده را تا انتهای لیست نگه دارد.
* **توصیفات قانع‌کننده:** برای هر آیتم، به وضوح توضیح بده که چه ارزشی برای کاربر ایجاد می‌کند.
* **کلمات کلیدی:** از کلماتی مانند "بهترین"، "برترین"، "نقد و بررسی" در سراسر متن استفاده کن.

**خروجی نهایی:**
یک مقاله لیستی جذاب با حداقل **۱۸۰۰ کلمه** که آماده انتشار در وردپرس باشد. از نوشتن "عنوان:" و غیره خودداری کن.`;
                
            } else if (item1 && item2) {
                // قالب مقاله مقایسه‌ای
                return `**نقش شما:** شما یک تحلیل‌گر و منتقد بی‌طرف هستید. وظیفه شما نوشتن یک مقاله مقایسه‌ای عمیق و منصفانه بین "${item1}" و "${item2}" است.

**هدف اصلی:** کمک به کاربر برای گرفتن یک تصمیم آگاهانه بر اساس نیازها و شرایط خاص خودش.

**مراحل اجرای کار:**

**مرحله ۱: تعیین معیارهای مقایسه**
لیستی از مهم‌ترین معیارهایی که برای مقایسه این دو آیتم باید در نظر گرفته شود، تهیه کن (حداقل ۷ معیار). ${comparisonCriteria}

**مرحله ۲: طراحی ساختار مقاله**
* **H1 (عنوان):** عنوانی که به وضوح مقایسه را نشان دهد (مثلاً: "${item1} در مقابل ${item2}: کدام برای شما بهتر است؟").
* **مقدمه:** هر دو آیتم را به طور خلاصه معرفی کن و بگو که در انتهای این مقاله، کاربر قادر به انتخاب بهترین گزینه خواهد بود.
* **جدول مقایسه سریع:** یک جدول در ابتدای مقاله قرار بده که به صورت خلاصه، دو آیتم را بر اساس معیارهای اصلی مقایسه کند و برنده هر بخش را مشخص کند.
* **مقایسه تفصیلی (Head-to-Head):**
  * این بخش اصلی مقاله است. برای هر معیاری که در مرحله ۱ مشخص کردی، یک تیتر H2 ایجاد کن (مثلاً: "مقایسه ویژگی‌ها").
  * در زیر هر H2، توضیح بده که هر کدام از آیتم‌ها در آن معیار چگونه عمل می‌کنند و در نهایت یک "برنده" برای آن معیار خاص اعلام کن و دلیلش را توضیح بده.
* **بخش "چه زمانی ${item1} را انتخاب کنیم؟":** به طور مشخص توضیح بده که ${item1} برای چه نوع کاربرانی یا چه پروژه‌هایی مناسب‌تر است.
* **بخش "چه زمانی ${item2} را انتخاب کنیم؟":** به طور مشخص توضیح بده که ${item2} برای چه نوع کاربرانی یا چه پروژه‌هایی مناسب‌تر است.
* **نتیجه‌گیری نهایی:** یک جمع‌بندی کامل ارائه بده و یک توصیه نهایی بر اساس سناریوهای مختلف ارائه کن. **از اعلام یک برنده قطعی برای همه پرهیز کن.**

**مرحله ۳: نگارش محتوا**
* **بی‌طرفی:** سعی کن در سراسر متن منصف و بی‌طرف باشی. هر دو جنبه مثبت و منفی را ذکر کن.
* **استفاده از داده:** در صورت امکان، به داده‌ها، آمار یا تست‌های عملکردی (به صورت فرضی) برای پشتیبانی از ادعاهای خود اشاره کن.
* **کلمات کلیدی:** از عباراتی مانند "${item1} vs ${item2}"، "مقایسه ${item1} و ${item2}"، "تفاوت‌های ${item1} و ${item2}" و "کدام بهتر است" استفاده کن.

**خروجی نهایی:**
یک مقاله مقایسه‌ای دقیق و جامع با حداقل **۲۰۰۰ کلمه** که به کاربر در تصمیم‌گیری کمک شایانی کند. مقاله باید آماده انتشار مستقیم در وردپرس باشد.`;
                
            } else {
                // قالب پیش‌فرض
                return `**نقش شما:** شما یک متخصص تولید محتوا و نویسنده حرفه‌ای هستید. وظیفه شما ایجاد یک مقاله جامع و با کیفیت برای موضوع "${mainTopic}" است.

**هدف اصلی:** تولید مقاله‌ای که ارزش واقعی برای خواننده ایجاد کند و در موتورهای جستجو عملکرد خوبی داشته باشد.

**مراحل اجرای کار:**

**مرحله ۱: تحقیق و تحلیل**
1. **شناسایی مخاطب هدف:** مشخص کن این مقاله برای چه کسانی نوشته می‌شود
2. **تحقیق کلمات کلیدی:** کلمات کلیدی مرتبط با موضوع را شناسایی کن
3. **تحلیل رقبا:** نقاط قوت و ضعف مقالات موجود را بررسی کن

**مرحله ۲: طراحی ساختار**
* **H1 (عنوان اصلی):** عنوانی جذاب و سئو شده
* **مقدمه:** معرفی موضوع و ایجاد کنجکاوی
* **بدنه اصلی:** حداقل ۵ بخش H2 با زیربخش‌های H3
* **نتیجه‌گیری:** جمع‌بندی نکات کلیدی

**مرحله ۳: نگارش محتوا**
* **لحن حرفه‌ای و قابل فهم**
* **پاراگراف‌های کوتاه و خوانا**
* **استفاده از لیست‌ها و مثال‌ها**
* **لینک‌دهی مناسب**

**خروجی نهایی:**
یک مقاله جامع با حداقل **۱۵۰۰ کلمه** که آماده انتشار در وردپرس باشد.`;
            }
        }
        
        // نمایش مودال فرم قالب
        function showTemplateForm(templateIndex, templateTitle, model) {
            const modal = document.getElementById('template-form-modal');
            const titleElement = document.getElementById('template-form-title');
            const modelInput = document.getElementById('template-model');
            const fieldsContainer = document.getElementById('template-fields');
            
            // تنظیم عنوان و مدل
            titleElement.textContent = templateTitle;
            modelInput.value = model;
            
            // ایجاد فیلدهای داینامیک بر اساس نوع قالب
            const fields = getTemplateFields(templateIndex, templateTitle);
            fieldsContainer.innerHTML = fields;
            
            // نمایش مودال
            modal.style.display = 'flex';
        }
        
        // تابع ایجاد فیلدهای داینامیک
        function getTemplateFields(templateIndex, templateTitle) {
            let fields = '';
            
            switch(templateTitle) {
                case 'مقاله جامع و بنیادی (Pillar Page)':
                    fields = `
                        <div class="form-group">
                            <label for="main_topic">موضوع اصلی مقاله:</label>
                            <input type="text" id="main_topic" name="main_topic" required placeholder="مثال: هوش مصنوعی در بازاریابی دیجیتال">
                        </div>
                        <div class="form-group">
                            <label for="target_audience">مخاطب هدف:</label>
                            <input type="text" id="target_audience" name="target_audience" placeholder="مثال: مدیران بازاریابی، کارشناسان دیجیتال">
                        </div>
                        <div class="form-group">
                            <label for="content_goal">هدف محتوا:</label>
                            <select id="content_goal" name="content_goal">
                                <option value="آموزشی">آموزشی</option>
                                <option value="اطلاعاتی">اطلاعاتی</option>
                                <option value="راهنمای خرید">راهنمای خرید</option>
                                <option value="تحلیلی">تحلیلی</option>
                            </select>
                        </div>
                    `;
                    break;
                    
                case 'مقاله به روش آسمان‌خراش (Skyscraper)':
                    fields = `
                        <div class="form-group">
                            <label for="main_topic">موضوع اصلی مقاله:</label>
                            <input type="text" id="main_topic" name="main_topic" required placeholder="مثال: بهترین ابزارهای سئو">
                        </div>
                        <div class="form-group">
                            <label for="competitor_analysis">تحلیل رقبا (اختیاری):</label>
                            <textarea id="competitor_analysis" name="competitor_analysis" placeholder="نقاط ضعف مقالات رقبا که می‌خواهید بهبود دهید..."></textarea>
                        </div>
                        <div class="form-group">
                            <label for="unique_value">ارزش افزوده منحصر به فرد:</label>
                            <input type="text" id="unique_value" name="unique_value" placeholder="مثال: مصاحبه با متخصص، چک‌لیست قابل دانلود">
                        </div>
                    `;
                    break;
                    
                case 'راهنمای عملی قدم به قدم (How-to)':
                    fields = `
                        <div class="form-group">
                            <label for="how_to_topic">موضوع راهنما:</label>
                            <input type="text" id="how_to_topic" name="how_to_topic" required placeholder="مثال: چگونه یک وب‌سایت بسازیم؟">
                        </div>
                        <div class="form-group">
                            <label for="skill_level">سطح مهارت مخاطب:</label>
                            <select id="skill_level" name="skill_level">
                                <option value="مبتدی">مبتدی</option>
                                <option value="متوسط">متوسط</option>
                                <option value="پیشرفته">پیشرفته</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="required_tools">ابزارهای مورد نیاز:</label>
                            <input type="text" id="required_tools" name="required_tools" placeholder="مثال: کامپیوتر، نرم‌افزار X، اینترنت">
                        </div>
                    `;
                    break;
                    
                case 'مقاله لیستی (مثلا: ۱۰ ابزار برتر)':
                    fields = `
                        <div class="form-group">
                            <label for="list_topic">موضوع لیست:</label>
                            <input type="text" id="list_topic" name="list_topic" required placeholder="مثال: ۱۰ ابزار برتر هوش مصنوعی">
                        </div>
                        <div class="form-group">
                            <label for="list_count">تعداد آیتم‌ها:</label>
                            <input type="number" id="list_count" name="list_count" min="5" max="50" value="10">
                        </div>
                        <div class="form-group">
                            <label for="list_criteria">معیار رتبه‌بندی:</label>
                            <input type="text" id="list_criteria" name="list_criteria" placeholder="مثال: قیمت، ویژگی‌ها، سهولت استفاده">
                        </div>
                    `;
                    break;
                    
                case 'مقاله مقایسه‌ای (X در مقابل Y)':
                    fields = `
                        <div class="form-group">
                            <label for="item1">آیتم اول:</label>
                            <input type="text" id="item1" name="item1" required placeholder="مثال: Yoast SEO">
                        </div>
                        <div class="form-group">
                            <label for="item2">آیتم دوم:</label>
                            <input type="text" id="item2" name="item2" required placeholder="مثال: Rank Math">
                        </div>
                        <div class="form-group">
                            <label for="comparison_criteria">معیارهای مقایسه:</label>
                            <textarea id="comparison_criteria" name="comparison_criteria" placeholder="مثال: ویژگی‌ها، قیمت، رابط کاربری، پشتیبانی"></textarea>
                        </div>
                    `;
                    break;
                    
                case 'مقاله کاملاً استاندارد برای Rank Math (امتیاز 90+)':
                    fields = `
                        <div class="form-group">
                            <label for="main_topic">موضوع اصلی مقاله:</label>
                            <input type="text" id="main_topic" name="main_topic" required placeholder="مثال: آموزش سئو">
                        </div>
                        <div class="form-group">
                            <label for="focus_keyword">کلمه کلیدی اصلی:</label>
                            <input type="text" id="focus_keyword" name="focus_keyword" required placeholder="مثال: آموزش سئو">
                        </div>
                        <div class="form-group">
                            <label for="target_audience">مخاطب هدف:</label>
                            <input type="text" id="target_audience" name="target_audience" placeholder="مثال: مدیران وب‌سایت، کارشناسان دیجیتال">
                        </div>
                        <div class="form-group">
                            <label for="content_type">نوع محتوا:</label>
                            <select id="content_type" name="content_type">
                                <option value="آموزشی">آموزشی</option>
                                <option value="اطلاعاتی">اطلاعاتی</option>
                                <option value="راهنما">راهنما</option>
                                <option value="تحلیلی">تحلیلی</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="content_length">طول محتوا:</label>
                            <select id="content_length" name="content_length">
                                <option value="1200">۱۲۰۰ کلمه (پیشنهادی)</option>
                                <option value="1500">۱۵۰۰ کلمه</option>
                                <option value="2000">۲۰۰۰ کلمه</option>
                                <option value="2500">۲۵۰۰ کلمه</option>
                            </select>
                        </div>
                    `;
                    break;
                    
                default:
                    fields = `
                        <div class="form-group">
                            <label for="main_topic">موضوع اصلی:</label>
                            <input type="text" id="main_topic" name="main_topic" required placeholder="موضوع مقاله خود را وارد کنید...">
                        </div>
                    `;
            }
            
            return fields;
        }
        
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

// تابع تشخیص مدل‌های تولید تصویر
function smart_admin_is_image_generation_model($model) {
    $image_generation_models = array(
        'gemini-2.0-flash-preview-image-generation',
        'gemini-2.5-flash-preview-native-audio-dialog',
        'dall-e-3',
        'dall-e-2',
        'midjourney',
        'stable-diffusion'
    );
    
    return in_array($model, $image_generation_models);
}

// تابع استخراج کلمات کلیدی برای تولید تصویر
function smart_admin_extract_image_keywords($content) {
    // حذف تگ‌های HTML
    $text = strip_tags($content);
    
    // استخراج کلمات کلیدی مهم
    $keywords = array();
    
    // جستجوی کلمات کلیدی در عنوان
    if (preg_match('/<h1[^>]*>(.*?)<\/h1>/i', $content, $matches)) {
        $title = strip_tags($matches[1]);
        $keywords[] = $title;
    }
    
    // جستجوی کلمات کلیدی در تیترهای H2
    if (preg_match_all('/<h2[^>]*>(.*?)<\/h2>/i', $content, $matches)) {
        foreach ($matches[1] as $match) {
            $keywords[] = strip_tags($match);
        }
    }
    
    // جستجوی کلمات کلیدی در متن
    $words = explode(' ', $text);
    $word_count = array_count_values($words);
    arsort($word_count);
    
    // انتخاب ۵ کلمه پرتکرار
    $count = 0;
    foreach ($word_count as $word => $frequency) {
        if ($count >= 5) break;
        if (strlen($word) > 3 && !in_array($word, array('این', 'که', 'برای', 'با', 'در', 'از', 'به', 'را', 'است', 'بود', 'شده', 'کرده', 'دارد', 'می‌شود', 'خواهد', 'تواند'))) {
            $keywords[] = $word;
            $count++;
        }
    }
    
    return array_unique($keywords);
}

// تابع تولید تصویر با API
function smart_admin_generate_image($prompt, $model, $api_key) {
    $url = 'https://api.gapgpt.app/v1/images/generations';
    
    $body = array(
        'model' => $model,
        'prompt' => $prompt,
        'n' => 1,
        'size' => '1024x1024',
        'quality' => 'standard',
        'response_format' => 'url'
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
    
    $response = wp_remote_post($url, $args);
    
    if (is_wp_error($response)) {
        return array('error' => $response->get_error_message());
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = json_decode(wp_remote_retrieve_body($response), true);
    
    if ($response_code !== 200) {
        return array('error' => isset($response_body['error']['message']) ? $response_body['error']['message'] : 'خطا در تولید تصویر');
    }
    
    if (isset($response_body['data'][0]['url'])) {
        return array(
            'image_url' => $response_body['data'][0]['url'],
            'prompt' => $prompt
        );
    } else {
        return array('error' => 'خطا در دریافت تصویر');
    }
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
        
        // تبدیل Markdown به HTML برای نمایش بهتر
        $content = smart_admin_convert_markdown_to_html($content);
        
        // استخراج کلمات کلیدی از محتوا
        $keywords = array();
        if (function_exists('smart_admin_extract_keywords_from_ai_response')) {
            $keywords = smart_admin_extract_keywords_from_ai_response($content);
        }
        
        $result = array(
            'content' => $content,
            'keywords' => $keywords
        );
        
        // اگر مدل تولید تصویر است، تصویر هم تولید کن
        if (smart_admin_is_image_generation_model($model)) {
            // استخراج کلمات کلیدی برای تولید تصویر
            $image_keywords = smart_admin_extract_image_keywords($content);
            $image_prompt = implode(' ', array_slice($image_keywords, 0, 5));
            
            // تولید تصویر
            $image_result = smart_admin_generate_image($image_prompt, $model, $api_key);
            
            if (!isset($image_result['error'])) {
                $result['generated_image'] = $image_result;
            }
        }
        
        return $result;
    } else {
        return array('error' => 'خطا در دریافت پاسخ از API');
    }
}

// تابع تبدیل Markdown به HTML
function smart_admin_convert_markdown_to_html($content) {
    // تبدیل تیترها
    $content = preg_replace('/^### (.*$)/m', '<h3>$1</h3>', $content);
    $content = preg_replace('/^## (.*$)/m', '<h2>$1</h2>', $content);
    $content = preg_replace('/^# (.*$)/m', '<h1>$1</h1>', $content);
    
    // تبدیل لیست‌ها
    $lines = explode("\n", $content);
    $in_list = false;
    $list_content = '';
    $new_content = '';
    
    foreach ($lines as $line) {
        if (preg_match('/^[\*\-] (.+)$/', $line) || preg_match('/^\d+\. (.+)$/', $line)) {
            // این خط لیست است
            if (!$in_list) {
                $in_list = true;
                if (preg_match('/^\d+\./', $line)) {
                    $list_content = '<ol>';
                } else {
                    $list_content = '<ul>';
                }
            }
            
            $item = preg_replace('/^[\*\-] (.+)$/', '$1', $line);
            $item = preg_replace('/^\d+\. (.+)$/', '$1', $item);
            $list_content .= '<li>' . $item . '</li>';
        } else {
            // این خط لیست نیست
            if ($in_list) {
                if (strpos($list_content, '<ol>') !== false) {
                    $list_content .= '</ol>';
                } else {
                    $list_content .= '</ul>';
                }
                $new_content .= $list_content;
                $in_list = false;
                $list_content = '';
            }
            $new_content .= $line . "\n";
        }
    }
    
    if ($in_list) {
        if (strpos($list_content, '<ol>') !== false) {
            $list_content .= '</ol>';
        } else {
            $list_content .= '</ul>';
        }
        $new_content .= $list_content;
    }
    
    $content = $new_content;
    
    // تبدیل بولد
    $content = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $content);
    
    // تبدیل ایتالیک
    $content = preg_replace('/\*(.*?)\*/s', '<em>$1</em>', $content);
    
    // تبدیل لینک‌ها
    $content = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2" target="_blank">$1</a>', $content);
    
    // تبدیل جداول
    $lines = explode("\n", $content);
    $in_table = false;
    $table_content = '';
    $new_content = '';
    
    foreach ($lines as $line) {
        if (strpos($line, '|') !== false && strpos($line, '|') !== strrpos($line, '|')) {
            // این خط جدول است
            if (!$in_table) {
                $in_table = true;
                $table_content = '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%; margin: 10px 0; font-size: 14px;">';
            }
            
            $cells = explode('|', trim($line, '|'));
            $table_content .= '<tr>';
            foreach ($cells as $cell) {
                $cell = trim($cell);
                if (strpos($cell, '---') !== false) {
                    // خط جداکننده - نادیده بگیر
                    continue;
                }
                $table_content .= '<td style="border: 1px solid #ddd; padding: 8px; text-align: center;">' . $cell . '</td>';
            }
            $table_content .= '</tr>';
        } else {
            // این خط جدول نیست
            if ($in_table) {
                $table_content .= '</table>';
                $new_content .= $table_content;
                $in_table = false;
                $table_content = '';
            }
            $new_content .= $line . "\n";
        }
    }
    
    if ($in_table) {
        $table_content .= '</table>';
        $new_content .= $table_content;
    }
    
    $content = $new_content;
    
    // تبدیل پاراگراف‌ها
    $lines = explode("\n", $content);
    $paragraphs = array();
    $current_paragraph = '';
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) {
            if (!empty($current_paragraph)) {
                $paragraphs[] = $current_paragraph;
                $current_paragraph = '';
            }
        } elseif (strpos($line, '<') === 0) {
            // این خط HTML است (تیتر، لیست، جدول)
            if (!empty($current_paragraph)) {
                $paragraphs[] = $current_paragraph;
                $current_paragraph = '';
            }
            $paragraphs[] = $line;
        } else {
            // این خط متن عادی است
            if (!empty($current_paragraph)) {
                $current_paragraph .= ' ' . $line;
            } else {
                $current_paragraph = $line;
            }
        }
    }
    
    if (!empty($current_paragraph)) {
        $paragraphs[] = $current_paragraph;
    }
    
    $content = '';
    foreach ($paragraphs as $paragraph) {
        if (strpos($paragraph, '<') === 0) {
            // این HTML است
            $content .= $paragraph . "\n";
        } else {
            // این متن عادی است - تبدیل به پاراگراف
            $content .= '<p>' . $paragraph . '</p>' . "\n";
        }
    }
    
    return $content;
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

// تابع دریافت تنظیمات متاباکس
function smart_admin_get_setting($key) {
    $settings = get_option('smart_admin_settings', array());
    return isset($settings[$key]) ? $settings[$key] : false;
}

// تابع ذخیره تنظیمات متاباکس
function smart_admin_save_metabox_settings() {
    if (isset($_POST['smart_admin_metabox_nonce']) && wp_verify_nonce($_POST['smart_admin_metabox_nonce'], 'smart_admin_metabox_settings')) {
        $settings = array();
        
        // ذخیره تنظیمات متاباکس
        $settings['rankmath_metabox'] = isset($_POST['smart_admin_settings']['rankmath_metabox']) ? 1 : 0;
        $settings['openai_metabox'] = isset($_POST['smart_admin_settings']['openai_metabox']) ? 1 : 0;
        $settings['send_method_metabox'] = isset($_POST['smart_admin_settings']['send_method_metabox']) ? 1 : 0;
        $settings['auto_publish'] = isset($_POST['smart_admin_settings']['auto_publish']) ? 1 : 0;
        $settings['debug_mode'] = isset($_POST['smart_admin_settings']['debug_mode']) ? 1 : 0;
        
        update_option('smart_admin_settings', $settings);
        
        // ریدایرکت با پیام موفقیت
        wp_redirect(add_query_arg('updated', 'true', admin_url('admin.php?page=smart-admin-metabox-settings')));
        exit;
    }
}
add_action('admin_init', 'smart_admin_save_metabox_settings');

// تابع فعال‌سازی حالت دیباگ
function smart_admin_enable_debug_mode() {
    if (smart_admin_get_setting('debug_mode')) {
        // فعال‌سازی لاگ‌گیری
        if (!file_exists(WP_CONTENT_DIR . '/debug.log')) {
            touch(WP_CONTENT_DIR . '/debug.log');
        }
        
        // تنظیم error_log برای debug.log
        @ini_set('error_log', WP_CONTENT_DIR . '/debug.log');
        @ini_set('log_errors', 'On');
    }
}
add_action('init', 'smart_admin_enable_debug_mode');

// تابع لاگ‌گیری برای حالت دیباگ
function smart_admin_debug_log($message, $type = 'INFO') {
    if (smart_admin_get_setting('debug_mode')) {
        $timestamp = date('Y-m-d H:i:s');
        $log_message = "[{$timestamp}] [{$type}] {$message}" . PHP_EOL;
        error_log($log_message);
    }
}

/**
 * استخراج عنوان SEO شده از پاسخ هوش مصنوعی
 * 
 * @param string $content محتوای تولید شده توسط هوش مصنوعی
 * @param string $main_topic موضوع اصلی (اختیاری)
 * @return string عنوان SEO شده یا رشته خالی در صورت عدم یافتن
 */
function smart_admin_extract_seo_title($content, $main_topic = '') {
    // لاگ برای دیباگ
    smart_admin_debug_log('Extracting SEO title from AI content', 'INFO');
    smart_admin_debug_log('Content length: ' . strlen($content), 'INFO');
    
    // بررسی اگر محتوا حاوی عنوان متا (Meta Title) صریح است
    $meta_title_patterns = array(
        // الگوی برای Meta Title صریح
        '/(?:عنوان متا|متا تایتل|meta title|عنوان سئو|عنوان SEO)[:]\s*(.*?)(?:[\.\n]|$)/i',
        '/(?:H1|عنوان اصلی|عنوان مقاله|عنوان صفحه)[:]\s*(.*?)(?:[\.\n]|$)/i'
    );
    
    foreach ($meta_title_patterns as $pattern) {
        if (preg_match($pattern, $content, $matches)) {
            $meta_title = trim(strip_tags($matches[1]));
            $meta_title = preg_replace('/[\*\#\_\[\]\(\)\{\}\<\>\"\'\`\~\|\+\=\^]/i', '', $meta_title);
            $meta_title = rtrim($meta_title, '.،:؛!؟،');
            
            if (!empty($meta_title) && strlen($meta_title) >= 10 && strlen($meta_title) <= 70) {
                smart_admin_debug_log('Found explicit Meta Title: ' . $meta_title, 'INFO');
                return $meta_title;
            }
        }
    }
    
    // بررسی موضوع محتوا و تشخیص عنوان مناسب بر اساس آن
    $content_topic = '';
    if (!empty($main_topic)) {
        $content_topic = $main_topic;
    } else {
        // تلاش برای استخراج موضوع اصلی از محتوا
        $topic_patterns = array(
            '/(?:موضوع|درباره|در مورد|مقاله درباره)[:]\s*(.*?)(?:[\.\n]|$)/i',
            '/(?:این مقاله درباره|این مطلب در مورد|این محتوا درباره)[:]*\s*(.*?)(?:[\.\n]|$)/i'
        );
        
        foreach ($topic_patterns as $pattern) {
            if (preg_match($pattern, $content, $matches)) {
                $content_topic = trim(strip_tags($matches[1]));
                $content_topic = preg_replace('/[\*\#\_\[\]\(\)\{\}\<\>\"\'\`\~\|\+\=\^]/i', '', $content_topic);
                $content_topic = rtrim($content_topic, '.،:؛!؟،');
                break;
            }
        }
    }
    
    // الگوهای مختلف برای یافتن عنوان به ترتیب اولویت
    $patterns = array(
        // الگوی 1: عنوان در تگ h1
        '/<h1[^>]*>(.*?)<\/h1>/i',
        
        // الگوی 2: عنوان با علامت # (مارک‌داون)
        '/^#\s+(.*?)$/m',
        
        // الگوی 3: خط اول که با "عنوان:" یا "موضوع:" شروع می‌شود
        '/^(?:عنوان|موضوع)[:]\s*(.*?)$/im',
        
        // الگوی 4: خط اول که با "title:" شروع می‌شود
        '/^title[:]\s*(.*?)$/im',
        
        // الگوی 5: عنوان با علامت ## (مارک‌داون سطح 2) در خط اول
        '/^##\s+(.*?)$/m',
        
        // الگوی 6: عنوان با علامت ### (مارک‌داون سطح 3) در خط اول
        '/^###\s+(.*?)$/m',
        
        // الگوی 7: عنوان با علامت ** (مارک‌داون پررنگ) در خط اول
        '/^\*\*(.*?)\*\*$/m',
        
        // الگوی 8: عنوان در تگ strong در خط اول
        '/^<strong>(.*?)<\/strong>$/m'
    );
    
    // بررسی هر الگو
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $content, $matches)) {
            $title = trim(strip_tags($matches[1]));
            
            // حذف علامت‌های مارک‌داون و HTML از عنوان
            $title = preg_replace('/[\*\#\_\[\]\(\)\{\}\<\>\"\'\`\~\|\+\=\^]/i', '', $title);
            
            // حذف نقطه از انتهای عنوان
            $title = rtrim($title, '.،:؛!؟،');
            
            // اگر عنوان معتبر است، آن را برگردان
            if (!empty($title) && strlen($title) >= 10 && strlen($title) <= 100) {
                smart_admin_debug_log('Found SEO title: ' . $title, 'INFO');
                return $title;
            }
        }
    }
    
    // بررسی دقیق‌تر محتوا برای یافتن عنوان مناسب
    // اگر محتوا در مورد برنامه‌نویسی بک‌اند و فرانت‌اند است
    if (preg_match('/(بک.?اند|فرانت.?اند|back.?end|front.?end|full.?stack)/ui', $content)) {
        $programming_patterns = array(
            // عبارات رایج در مورد برنامه‌نویسی وب
            '/(?:تفاوت|مقایسه|فرق)(?:\s+(?:بین|میان))?\s+(.*?)(?:و|با)\s+(.*?)(?:چیست|کدام است|در چیست)/ui',
            '/(?:راهنمای|آموزش|معرفی)\s+(.*?)(?:برای مبتدیان|برای تازه‌کاران|از صفر تا صد)/ui',
            '/(?:چگونه|چطور)\s+(.*?)(?:را شروع کنیم|را یاد بگیریم|را آغاز کنیم)/ui'
        );
        
        foreach ($programming_patterns as $pattern) {
            if (preg_match($pattern, $content, $matches)) {
                // ساخت عنوان مناسب بر اساس الگوی یافت شده
                if (count($matches) >= 3) {
                    $title = "تفاوت " . trim($matches[1]) . " و " . trim($matches[2]) . ": راهنمای کامل";
                } elseif (count($matches) >= 2) {
                    $title = trim($matches[0]);
                }
                
                if (!empty($title) && strlen($title) >= 10) {
                    smart_admin_debug_log('Created programming-specific title: ' . $title, 'INFO');
                    return $title;
                }
            }
        }
        
        // اگر هنوز عنوان پیدا نشد و محتوا در مورد برنامه‌نویسی است
        if (preg_match('/(?:تفاوت|مقایسه|فرق).*(?:بک.?اند|فرانت.?اند|back.?end|front.?end)/ui', $content)) {
            return "تفاوت برنامه‌نویس بک‌اند و فرانت‌اند: راهنمای کامل برای انتخاب مسیر شغلی";
        }
    }
    
    // اگر عنوان پیدا نشد، پاراگراف اول محتوا را بررسی کن
    $paragraphs = preg_split('/\n\s*\n/', $content);
    if (!empty($paragraphs[0])) {
        $first_paragraph = $paragraphs[0];
        $lines = explode("\n", $first_paragraph);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // حذف علامت‌های مارک‌داون و HTML
            $line = strip_tags($line);
            $line = preg_replace('/[\*\#\_\[\]\(\)\{\}\<\>\"\'\`\~\|\+\=\^]/i', '', $line);
            $line = rtrim($line, '.،:؛!؟،');
            
            // بررسی طول و کیفیت خط
            if (strlen($line) >= 20 && strlen($line) <= 100 && !preg_match('/^(https?|www|\d+\.)/', $line)) {
                smart_admin_debug_log('Using first paragraph line as title: ' . $line, 'INFO');
                return $line;
            }
        }
    }
    
    // اگر موضوع اصلی استخراج شده، از آن به عنوان عنوان استفاده کن
    if (!empty($content_topic)) {
        smart_admin_debug_log('Using extracted topic as title: ' . $content_topic, 'INFO');
        return $content_topic;
    }
    
    smart_admin_debug_log('No suitable title found', 'INFO');
    return '';
}

/**
 * استخراج پیوند یکتا (slug) بهینه برای SEO از پاسخ هوش مصنوعی
 * 
 * @param string $content محتوای تولید شده توسط هوش مصنوعی
 * @param string $title عنوان مقاله (اختیاری)
 * @param array $keywords کلمات کلیدی (اختیاری)
 * @return string پیوند یکتای بهینه شده
 */
function smart_admin_extract_seo_slug($content, $title = '', $keywords = array()) {
    // لاگ برای دیباگ
    smart_admin_debug_log('Extracting SEO slug from AI content', 'INFO');
    
    // بررسی اگر محتوا حاوی پیوند یکتای (Slug) صریح است
    $slug_meta_patterns = array(
        // الگوهای مختلف برای پیوند یکتا
        '/(?:پیوند یکتا|slug|permalink|url|آدرس سئو|آدرس SEO|SEO URL)[:]\s*([\w\-\p{L}]+)/ui',
        '/(?:پیوند یکتا|slug|permalink|url|آدرس سئو|آدرس SEO|SEO URL)[:]\s*[\'"]?(.*?)[\'"]?(?:[\.\n]|$)/ui',
        '/<slug>(.*?)<\/slug>/i',
        '/slug[=:]\s*[\'"]?(.*?)[\'"]?(?:[\.\n]|$)/i'
    );
    
    foreach ($slug_meta_patterns as $pattern) {
        if (preg_match($pattern, $content, $matches)) {
            $meta_slug = trim($matches[1]);
            if (!empty($meta_slug)) {
                smart_admin_debug_log('Found explicit slug in content: ' . $meta_slug, 'INFO');
                // اطمینان از اینکه پیوند یکتا معتبر است
                $slug = sanitize_title($meta_slug);
                return $slug;
            }
        }
    }
    
    // اگر عنوان ارسال نشده، آن را از محتوا استخراج کن
    if (empty($title)) {
        $title = smart_admin_extract_seo_title($content);
    }
    
    // بررسی زبان محتوا (فارسی یا انگلیسی)
    $is_persian = preg_match('/[\x{0600}-\x{06FF}]/u', $title . ' ' . $content);
    smart_admin_debug_log('Content language is: ' . ($is_persian ? 'Persian' : 'English'), 'INFO');
    
    // تشخیص موضوع محتوا و ایجاد پیوند یکتای مناسب
    
    // بررسی اگر محتوا در مورد برنامه‌نویسی بک‌اند و فرانت‌اند است
    if (preg_match('/(بک.?اند|فرانت.?اند|back.?end|front.?end|full.?stack)/ui', $content)) {
        // اگر محتوا در مورد تفاوت بک‌اند و فرانت‌اند است
        if (preg_match('/(?:تفاوت|مقایسه|فرق).*(?:بک.?اند|فرانت.?اند|back.?end|front.?end)/ui', $content)) {
            if ($is_persian) {
                $slug = 'تفاوت-برنامه-نویس-بک-اند-فرانت-اند';
            } else {
                $slug = 'backend-vs-frontend-developer-differences';
            }
            smart_admin_debug_log('Created programming-specific slug: ' . $slug, 'INFO');
            return $slug;
        }
    }
    
    // بررسی اگر محتوا در مورد مهاجرت و سفر است
    if (preg_match('/(مهاجرت|سفر|اقامت|ویزا|پاسپورت|کویت|دبی|ترکیه|کانادا|آمریکا|اروپا|استرالیا)/ui', $content)) {
        // اگر محتوا در مورد راهنمای مهاجرت به کشور خاصی است
        if (preg_match('/(?:راهنمای|آموزش|روش|چگونه|چطور).*(?:مهاجرت|سفر|اقامت).*(?:به|در)\s+(.*?)(?:[\.،,\s]|$)/ui', $content, $matches)) {
            $country = trim($matches[1]);
            if (!empty($country)) {
                if ($is_persian) {
                    $slug = 'راهنمای-مهاجرت-به-' . $country;
                } else {
                    $slug = 'immigration-guide-to-' . $country;
                }
                smart_admin_debug_log('Created immigration-specific slug: ' . $slug, 'INFO');
                return sanitize_title($slug);
            }
        }
        
        // اگر محتوا یا عنوان در مورد قدم به قدم مهاجرت است
        if (preg_match('/(?:قدم به قدم|گام به گام|مرحله به مرحله).*(?:مهاجرت|سفر|اقامت)/ui', $content . ' ' . $title) ||
            preg_match('/(?:چطور|چگونه).*(?:مهاجرت|سفر|اقامت).*(?:کنم|کنیم|کنید)/ui', $title)) {
            if ($is_persian) {
                // بررسی الگوی دقیق "راهنمای قدم به قدم چطور به کویت مهاجرت کنم"
                if (preg_match('/راهنمای قدم به قدم چطور به (.*?) مهاجرت کنم/ui', $title, $exact_matches)) {
                    $country = trim($exact_matches[1]);
                    $slug = 'راهنمای-قدم-به-قدم-مهاجرت-به-' . $country;
                }
                // استخراج نام کشور از عنوان یا محتوا
                elseif (preg_match('/(کویت|دبی|ترکیه|کانادا|آمریکا|اروپا|استرالیا|امارات)/ui', $title . ' ' . $content, $country_matches)) {
                    $country = trim($country_matches[1]);
                    $slug = 'راهنمای-قدم-به-قدم-مهاجرت-به-' . $country;
                } else {
                    $slug = 'راهنمای-قدم-به-قدم-مهاجرت';
                }
            } else {
                if (preg_match('/(kuwait|dubai|turkey|canada|usa|europe|australia|uae)/ui', $title . ' ' . $content, $country_matches)) {
                    $country = strtolower(trim($country_matches[1]));
                    $slug = 'step-by-step-immigration-guide-to-' . $country;
                } else {
                    $slug = 'step-by-step-immigration-guide';
                }
            }
            smart_admin_debug_log('Created step-by-step immigration slug: ' . $slug, 'INFO');
            return sanitize_title($slug);
        }
    }
    
    // اگر پیوند یکتای صریح پیدا نشد، از عنوان استفاده کن
    if (!empty($title)) {
        // برای محتوای فارسی
        if ($is_persian) {
            // حذف کلمات اضافی و حروف ربط از عنوان
            $title = preg_replace('/\b(و|یا|با|به|در|از|که|را|برای|این|آن|چه|چرا|چگونه|کدام)\b/ui', ' ', $title);
            
            // حداکثر 5 کلمه مهم از عنوان را استخراج کن
            $words = array_filter(preg_split('/\s+/u', $title), function($word) {
                return mb_strlen($word, 'UTF-8') > 2; // فقط کلمات با بیش از 2 حرف
            });
            
            $slug_words = array_slice($words, 0, 5);
            $slug = implode('-', $slug_words);
            
            // اطمینان از اینکه پیوند یکتا معتبر است
            $slug = sanitize_title($slug);
            
            smart_admin_debug_log('Created Persian slug from title: ' . $slug, 'INFO');
            return $slug;
        } 
        // برای محتوای انگلیسی
        else {
            // حذف کلمات اضافی از عنوان
            $title = preg_replace('/\b(and|or|the|a|an|in|on|at|by|for|with|to|of|is|are|was|were|be|been|being)\b/i', ' ', $title);
            
            // استفاده از تابع وردپرس برای ساخت پیوند یکتا از عنوان
            $slug = sanitize_title($title);
            
            // محدود کردن طول پیوند یکتا
            if (strlen($slug) > 60) {
                $slug = substr($slug, 0, 60);
                // اطمینان از اینکه در وسط یک کلمه قطع نشده
                $slug = preg_replace('/-[^-]*$/', '', $slug);
            }
            
            smart_admin_debug_log('Created English slug from title: ' . $slug, 'INFO');
            return $slug;
        }
    }
    
    // اگر عنوان خالی بود، از کلمات کلیدی استفاده کن
    if (!empty($keywords) && is_array($keywords)) {
        // حذف کلمات اضافی از کلمات کلیدی
        $filtered_keywords = array_filter($keywords, function($keyword) {
            // حذف کلمات کوتاه و کلمات ربط
            $stopwords = array('و', 'یا', 'با', 'به', 'در', 'از', 'که', 'را', 'برای', 'این', 'آن', 
                              'and', 'or', 'the', 'a', 'an', 'in', 'on', 'at', 'by', 'for', 'with', 'to');
            return !in_array(strtolower($keyword), $stopwords) && mb_strlen($keyword, 'UTF-8') > 2;
        });
        
        if (!empty($filtered_keywords)) {
            $primary_keyword = sanitize_title($filtered_keywords[0]);
            smart_admin_debug_log('Using filtered primary keyword for slug: ' . $primary_keyword, 'INFO');
            return $primary_keyword;
        }
    }
    
    // اگر محتوا در مورد برنامه‌نویسی است اما هیچ پیوند یکتایی پیدا نشد
    if (preg_match('/(برنامه.?نویس|توسعه.?دهنده|developer|programmer|coding|programming)/ui', $content)) {
        if ($is_persian) {
            return 'راهنمای-برنامه-نویسی-وب';
        } else {
            return 'web-development-guide';
        }
    }
    
    // اگر هیچ منبعی برای ساخت پیوند یکتا نبود، یک پیوند یکتا تصادفی بساز
    $random_slug = 'ai-content-' . substr(md5(uniqid(mt_rand(), true)), 0, 8);
    smart_admin_debug_log('No suitable slug source found, using random: ' . $random_slug, 'INFO');
    return $random_slug;
}