<?php
/**
 * تست Unsplash API برای هاست‌های مختلف
 * این فایل برای تست در هاست‌های مختلف طراحی شده است
 */

// تنظیمات
$access_key = 'YOUR_UNSPLASH_ACCESS_KEY'; // کلید خود را اینجا وارد کنید
$keyword = 'برنامه نویسی';

echo "<h2>🔄 تست Unsplash API برای هاست</h2>\n";
echo "<p><strong>کلیدواژه:</strong> $keyword</p>\n";
echo "<p><strong>کلید دسترسی:</strong> " . (empty($access_key) ? 'خالی' : 'موجود') . "</p>\n";

if (empty($access_key)) {
    echo "<p style='color: red;'>❌ کلید دسترسی Unsplash وارد نشده است</p>\n";
    echo "<p>لطفاً کلید خود را در متغیر \$access_key وارد کنید</p>\n";
    exit;
}

// تشکیل آدرس API
$api_url = 'https://api.unsplash.com/search/photos?' . http_build_query([
    'query' => $keyword,
    'orientation' => 'landscape',
    'per_page' => 1,
    'client_id' => $access_key
]);

echo "<p><strong>📡 آدرس API:</strong> $api_url</p>\n";

// درخواست به API با cURL
echo "<p>🔄 ارسال درخواست به Unsplash...</p>\n";

// بررسی وجود cURL
if (!function_exists('curl_init')) {
    echo "<p style='color: red;'>❌ cURL در سرور فعال نیست</p>\n";
    exit;
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; Test Script)');
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // برای هاست‌های قدیمی
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // برای هاست‌های قدیمی
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // دنبال کردن ریدایرکت‌ها

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<p><strong>📊 کد پاسخ:</strong> $http_code</p>\n";

if ($error) {
    echo "<p style='color: red;'>❌ خطا در cURL: $error</p>\n";
    exit;
}

if ($http_code !== 200) {
    echo "<p style='color: red;'>❌ خطا در API: کد $http_code</p>\n";
    echo "<p><strong>پاسخ:</strong> $response</p>\n";
    exit;
}

$data = json_decode($response, true);

echo "<p><strong>📄 محتوای پاسخ:</strong> " . substr($response, 0, 200) . "...</p>\n";

if (empty($data['results'][0]['urls']['regular'])) {
    echo "<p style='color: red;'>❌ هیچ تصویری یافت نشد</p>\n";
    echo "<p><strong>پاسخ کامل:</strong> $response</p>\n";
    exit;
}

$image_url = $data['results'][0]['urls']['regular'];
$alt_text = $data['results'][0]['alt_description'] ?? $keyword;
$photographer = $data['results'][0]['user']['name'] ?? 'نامشخص';

echo "<p style='color: green;'>✅ تصویر یافت شد!</p>\n";
echo "<p><strong>📸 آدرس تصویر:</strong> $image_url</p>\n";
echo "<p><strong>📝 متن جایگزین:</strong> $alt_text</p>\n";
echo "<p><strong>👤 عکاس:</strong> $photographer</p>\n";

// تست دانلود تصویر
echo "<p>🔄 تست دانلود تصویر...</p>\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $image_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$image_data = curl_exec($ch);
$image_size = strlen($image_data);
$download_error = curl_error($ch);
curl_close($ch);

if ($download_error) {
    echo "<p style='color: red;'>❌ خطا در دانلود تصویر: $download_error</p>\n";
    exit;
}

echo "<p style='color: green;'>✅ تصویر با موفقیت دانلود شد</p>\n";
echo "<p><strong>📏 اندازه:</strong> " . number_format($image_size) . " بایت</p>\n";
echo "<p><strong>📏 اندازه:</strong> " . round($image_size / 1024, 2) . " کیلوبایت</p>\n";

echo "<h3 style='color: green;'>🎉 تست Unsplash با موفقیت انجام شد!</h3>\n";
echo "<p>افزونه آماده استفاده است.</p>\n";

// نمایش تصویر
echo "<h3>📸 نمایش تصویر دریافت شده:</h3>\n";
echo "<img src='$image_url' alt='$alt_text' style='max-width: 100%; height: auto; border: 1px solid #ccc;'>\n";
?> 