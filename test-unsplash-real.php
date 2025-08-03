<?php
/**
 * تست واقعی Unsplash API
 */

// تنظیمات
$access_key = 'YOUR_UNSPLASH_ACCESS_KEY'; // کلید خود را اینجا وارد کنید
$keyword = 'برنامه نویسی';

echo "🔄 شروع تست Unsplash API\n";
echo "کلیدواژه: $keyword\n";
echo "کلید دسترسی: " . (empty($access_key) ? 'خالی' : 'موجود') . "\n\n";

if (empty($access_key)) {
    echo "❌ کلید دسترسی Unsplash وارد نشده است\n";
    echo "لطفاً کلید خود را در متغیر \$access_key وارد کنید\n";
    exit;
}

// تشکیل آدرس API
$api_url = add_query_arg(array(
    'query'       => urlencode($keyword),
    'orientation' => 'landscape',
    'per_page'    => 1,
    'client_id'   => $access_key,
), 'https://api.unsplash.com/search/photos');

echo "📡 آدرس API: $api_url\n\n";

// درخواست به API
echo "🔄 ارسال درخواست به Unsplash...\n";
$response = wp_remote_get($api_url, array('timeout' => 15));

if (is_wp_error($response)) {
    echo "❌ خطا در دریافت پاسخ: " . $response->get_error_message() . "\n";
    exit;
}

$body = wp_remote_retrieve_body($response);
$data = json_decode($body, true);

echo "📊 کد پاسخ: " . wp_remote_retrieve_response_code($response) . "\n";
echo "📄 محتوای پاسخ: " . substr($body, 0, 200) . "...\n\n";

if (empty($data['results'][0]['urls']['regular'])) {
    echo "❌ هیچ تصویری یافت نشد\n";
    echo "پاسخ کامل: " . $body . "\n";
    exit;
}

$image_url = $data['results'][0]['urls']['regular'];
$alt_text = $data['results'][0]['alt_description'] ?? $keyword;
$photographer = $data['results'][0]['user']['name'] ?? 'نامشخص';

echo "✅ تصویر یافت شد!\n";
echo "📸 آدرس تصویر: $image_url\n";
echo "📝 متن جایگزین: $alt_text\n";
echo "👤 عکاس: $photographer\n\n";

// تست دانلود تصویر
echo "🔄 تست دانلود تصویر...\n";
$image_content = wp_remote_get($image_url);

if (is_wp_error($image_content)) {
    echo "❌ خطا در دانلود تصویر: " . $image_content->get_error_message() . "\n";
    exit;
}

$image_data = wp_remote_retrieve_body($image_content);
$image_size = strlen($image_data);

echo "✅ تصویر با موفقیت دانلود شد\n";
echo "📏 اندازه: " . number_format($image_size) . " بایت\n";
echo "📏 اندازه: " . round($image_size / 1024, 2) . " کیلوبایت\n\n";

echo "🎉 تست Unsplash با موفقیت انجام شد!\n";
echo "افزونه آماده استفاده است.\n";
?> 