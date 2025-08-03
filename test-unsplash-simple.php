<?php
/**
 * تست ساده Unsplash API برای هاست وردپرس
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
$api_url = 'https://api.unsplash.com/search/photos?' . http_build_query([
    'query' => $keyword,
    'orientation' => 'landscape',
    'per_page' => 1,
    'client_id' => $access_key
]);

echo "📡 آدرس API: $api_url\n\n";

// درخواست به API با cURL
echo "🔄 ارسال درخواست به Unsplash...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; Test Script)');
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // برای هاست‌های قدیمی
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // برای هاست‌های قدیمی

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "📊 کد پاسخ: $http_code\n";

if ($error) {
    echo "❌ خطا در cURL: $error\n";
    exit;
}

if ($http_code !== 200) {
    echo "❌ خطا در API: کد $http_code\n";
    echo "پاسخ: $response\n";
    exit;
}

$data = json_decode($response, true);

echo "📄 محتوای پاسخ: " . substr($response, 0, 200) . "...\n\n";

if (empty($data['results'][0]['urls']['regular'])) {
    echo "❌ هیچ تصویری یافت نشد\n";
    echo "پاسخ کامل: $response\n";
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
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $image_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // برای هاست‌های قدیمی
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // برای هاست‌های قدیمی

$image_data = curl_exec($ch);
$image_size = strlen($image_data);
$download_error = curl_error($ch);
curl_close($ch);

if ($download_error) {
    echo "❌ خطا در دانلود تصویر: $download_error\n";
    exit;
}

echo "✅ تصویر با موفقیت دانلود شد\n";
echo "📏 اندازه: " . number_format($image_size) . " بایت\n";
echo "📏 اندازه: " . round($image_size / 1024, 2) . " کیلوبایت\n\n";

echo "🎉 تست Unsplash با موفقیت انجام شد!\n";
echo "افزونه آماده استفاده است.\n";
?> 