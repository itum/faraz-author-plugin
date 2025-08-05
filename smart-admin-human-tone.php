<?php
/**
 * تنظیمات لحن انسانی برای محتوای تولید شده توسط هوش مصنوعی
 * 
 * این فایل شامل پرامپت‌ها و تنظیمات لازم برای ایجاد محتوایی با لحن انسانی است
 * که توسط ابزارهای تشخیص هوش مصنوعی، به عنوان محتوای تولید شده توسط انسان شناسایی شود
 */

// دریافت دستورالعمل‌های لحن انسانی
function get_human_tone_instructions() {
    return "
    لطفاً محتوا را با لحنی کاملاً طبیعی و انسانی بنویس که قابل تشخیص توسط ابزارهای تشخیص هوش مصنوعی نباشد. دستورالعمل‌های زیر را دنبال کن:

    1. از ساختارهای خشک و مکانیکی پرهیز کن. گاهی جملات را ناتمام بگذار یا از ساختارهای غیرمعمول استفاده کن.
    
    2. ناهمگونی ایجاد کن. گاهی جملات کوتاه، گاهی بلند. گاهی از کلمات ساده، گاهی از کلمات پیچیده استفاده کن.
    
    3. عناصر شخصی اضافه کن. تجربیات شخصی فرضی، دیدگاه‌های اصیل و نظرات متفاوت به متن اضافه کن.
    
    4. از اصطلاحات محاوره‌ای، ضرب‌المثل‌ها و استعاره‌های خلاقانه استفاده کن.
    
    5. از تکرار پرهیز کن. تنوع واژگانی و ساختاری داشته باش.
    
    6. اشتباهات انسانی کوچک به متن اضافه کن، مثل نیم‌فاصله‌های اشتباه یا ویرگول‌های اضافی.
    
    7. از عبارات انتقالی غیرمعمول استفاده کن، نه همیشه الگوهای معمول مثل 'اول'، 'دوم' و غیره.
    
    8. گاهی متن را با سؤال‌های تأملی و غیرمنتظره غنی کن.
    
    9. از تیترهای خلاقانه و غیرقابل پیش‌بینی استفاده کن.
    
    10. عدم قطعیت را نشان بده. از عباراتی مثل 'شاید'، 'به نظر می‌رسد'، 'احتمالاً' استفاده کن.
    
    11. از قالب‌بندی‌های نامنظم مثل خط تیره، پرانتز، یا علامت‌های دیگر استفاده کن که الگوی منظمی نداشته باشد.
    
    12. مهم: از علامت‌های ** در ابتدا و انتهای تیترها و عناوین استفاده نکن.
    
    13. هرگز عبارت 'عنوان مقاله:' را در ابتدای متن یا قبل از عنوان اصلی ننویس.
    
    14. تیترها را بدون هیچ علامت خاصی و به صورت ساده بنویس. مثلاً به جای '**مقدمه**' فقط بنویس 'مقدمه'.
    ";
}

// تابع افزودن لحن انسانی به یک پرامپت
function add_human_tone_to_prompt($prompt) {
    $human_tone = get_human_tone_instructions();
    
    // اگر لحن انسانی در پرامپت وجود نداشته باشد، آن را اضافه کن
    if (strpos($prompt, 'لحن انسانی') === false && strpos($prompt, 'لحنی طبیعی') === false) {
        $prompt .= "\n\n" . $human_tone;
    }
    
    return $prompt;
}

// بهینه‌سازی پرامپت برای تولید محتوای طبیعی
function optimize_prompt_for_natural_content($prompt) {
    // حذف علامت‌های # و ### از پرامپت
    $prompt = preg_replace('/(#+\s*)(.*?)(\s*\n)/', "$2\n", $prompt);
    
    // جایگزینی پاراگراف‌های سختگیرانه با پاراگراف‌های طبیعی‌تر
    $prompt = str_replace(
        'پاراگراف‌های کوتاه و خوانا (حداکثر 3-4 جمله در هر پاراگراف)',
        'پاراگراف‌های متنوع و طبیعی با طول‌های مختلف',
        $prompt
    );
    
    // افزودن تنوع به متن
    $prompt = str_replace(
        'از لیست‌های نقطه‌ای و شماره‌دار در جایی که مناسب است',
        'از ساختارهای متنوعی مثل لیست‌ها، نقل قول‌ها و پاراگراف‌های کوتاه و بلند به صورت ترکیبی استفاده کن',
        $prompt
    );
    
    // افزودن لحن انسانی
    $prompt = add_human_tone_to_prompt($prompt);
    
    return $prompt;
}

// استفاده در فرم ارسال
function add_human_tone_option_to_form() {
    ?>
    <div class="form-group human-tone-option">
        <label class="checkbox-container">
            <input type="checkbox" id="use_human_tone" name="use_human_tone" value="1" checked>
            <span class="checkmark"></span>
            استفاده از لحن انسانی (برای جلوگیری از تشخیص توسط ابزارهای تشخیص محتوای هوش مصنوعی)
        </label>
    </div>
    <?php
}

// تابع بهبود خروجی هوش مصنوعی با لحن انسانی
function improve_ai_output_with_human_tone($content) {
    // تغییرات سبکی برای انسانی‌تر کردن متن
    $replacements = array(
        '###' => '',
        '####' => '',
        '##' => '',
        '#' => '',
        '**عنوان مقاله:**' => '',
        '**عنوان مقاله: **' => '',
        'عنوان مقاله:' => '',
        '**مقدمه**' => 'مقدمه',
        '** مقدمه **' => 'مقدمه',
        '---' => '', // حذف ---
    );

    // حذف علامت‌های ** از ابتدا و انتهای خطوط
    $content = preg_replace('/^\*\*(.*?)\*\*$/m', '$1', $content);

    // حذف علامت‌های ** از ابتدا و انتهای عبارات داخل متن
    $content = preg_replace('/\*\*(.*?)\*\*/u', '$1', $content);

    // جایگزینی الگوهای مشخص شده
    $content = str_replace(array_keys($replacements), array_values($replacements), $content);

    // حذف کدهای HTML خام که ممکن است در متن ظاهر شوند
    $content = preg_replace('/<div[^>]*class="toc-container"[^>]*>.*?<\/div>/s', '', $content);
    $content = preg_replace('/<ul[^>]*style="list-style-type: none[^>]*>.*?<\/ul>/s', '', $content);
    $content = preg_replace('/<li[^>]*>.*?<\/li>/s', '', $content);
    
    // حذف سایر تگ‌های HTML که ممکن است در متن ظاهر شوند
    $content = preg_replace('/<[^>]*>/', '', $content);
    
    // حذف فهرست مطالب نامناسب
    $content = preg_replace('/فهرست مطالب.*?\n/', '', $content);
    $content = preg_replace('/Table of Contents.*?\n/', '', $content);
    
    // حذف شماره‌های فهرست مطالب
    $content = preg_replace('/^\d+\.\s*.*?\n/m', '', $content);
    
    // حذف خطوط خالی اضافی
    $content = preg_replace('/\n\s*\n\s*\n/', "\n\n", $content);
    
    // حذف فاصله‌های اضافی از ابتدا و انتهای متن
    $content = trim($content);

    // تبدیل بلوک‌های کد مارک‌داون به <pre><code> با استایل چپ‌چین و فونت monospace
    $content = preg_replace_callback('/```([a-zA-Z]*)\n([\s\S]*?)```/', function ($matches) {
        $lang = !empty($matches[1]) ? htmlspecialchars($matches[1]) : '';
        $code = htmlspecialchars($matches[2]);
        return '<pre style="direction:ltr;text-align:left;background:#f5f5f5;padding:10px;border-radius:6px;font-family:monospace;overflow:auto;"><code class="language-' . $lang . '">' . $code . '</code></pre>';
    }, $content);

    // چپ‌چین کردن خطوط انگلیسی (که بلوک کد نیستند)
    $lines = explode("\n", $content);
    foreach ($lines as &$line) {
        // اگر خط انگلیسی باشد و بلوک کد نباشد
        if (preg_match('/^[A-Za-z0-9\s\.,;:!\?\(\)\[\]{}\-_=+@#$%^&*\/\\<>\'\"]{4,}$/', trim($line)) && strpos($line, '<pre') === false) {
            $line = '<span style="direction:ltr;text-align:left;font-family:monospace;display:block">' . htmlspecialchars($line) . '</span>';
        }
    }
    $content = implode("\n", $lines);

    return $content;
} 