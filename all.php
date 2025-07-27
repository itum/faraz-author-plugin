<?php 

 
function downloadPhoto($url) { 
    $tempFile = tempnam(sys_get_temp_dir(), 'photo_');
    
    // Use cURL for better error handling
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $imageData = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $imageData !== false) {
        file_put_contents($tempFile, $imageData);
        return $tempFile;
    } else {
        // Log error
        $log_file_all = dirname(__FILE__) . '/all_logs.txt';
        file_put_contents($log_file_all, "Failed to download photo from: " . $url . " (HTTP Code: " . $httpCode . ")\n", FILE_APPEND);
        return false;
    }
}

function sendToTelegram($request) {
    $log_file_all = dirname(__FILE__) . '/all_logs.txt';
    file_put_contents($log_file_all, "Received request in all.php: " . $request . "\n", FILE_APPEND);

    $data = json_decode($request, true);
 
    $botToken = $data['bot'] ?? null;
    $chatId = $data['chatid'] ?? null;
    $message = $data['message'] ?? null;
    $photo = $data['photo'] ?? null;
    $caption = $data['caption'] ?? '';
    $isPhoto = $data['is_photo'] ?? 'false';
    $replyMarkup = $data['reply_markup'] ?? null;

    // Validate token format
    if (!$botToken || !$chatId || (!$message && !$photo)) {
        return json_encode([
            'status' => 'error',
            'message' => 'Missing required fields: bot, chatid, and either message or photo.'
        ]);
    }
    
    // اگر به جای توکن کامل فقط نام کاربری ربات ارسال شده، خطا برگردان
    if (strpos($botToken, ':') === false) {
        return json_encode([
            'status' => 'error',
            'message' => 'Invalid bot token format. Bot token should include both ID and token parts separated by colon.'
        ]);
    }
 
    if ($isPhoto == 'false') {
        $telegramApiUrl = "https://api.telegram.org/bot$botToken/sendMessage";
        $body = [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML'
        ];
    }
 
    if ($isPhoto == 'true') {
        $telegramApiUrl = "https://api.telegram.org/bot$botToken/sendPhoto"; 
        file_put_contents($log_file_all, "Downloading photo from: " . $photo . "\n", FILE_APPEND);
        $photoFilePath = downloadPhoto($photo);
        
        if ($photoFilePath === false) {
            // If photo download failed, send as text message
            file_put_contents($log_file_all, "Photo download failed, sending as text message\n", FILE_APPEND);
            $telegramApiUrl = "https://api.telegram.org/bot$botToken/sendMessage";
            $body = [
                'chat_id' => $chatId,
                'text' => $caption,
                'parse_mode' => 'HTML'
            ];
        } else {
            file_put_contents($log_file_all, "Photo downloaded to: " . $photoFilePath . "\n", FILE_APPEND);
            $body = [
                'chat_id' => $chatId,
                'photo' => new CURLFile($photoFilePath),
                'caption' => $caption,
                'parse_mode' => 'HTML'
            ]; 
        }
    }
 
    if ($replyMarkup) {
        $body['reply_markup'] = $replyMarkup;
    }
 
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $telegramApiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: multipart/form-data']);

    $response = curl_exec($ch);
    file_put_contents($log_file_all, "Telegram API response: " . $response . "\n", FILE_APPEND);
    $errorMessage = null;
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        $errorMessage = 'HTTP Error: ' . curl_error($ch);
    } else {
        $responseData = json_decode($response, true);
        if (!isset($responseData['ok']) || $responseData['ok'] !== true) {
            $errorMessage = 'Telegram API Error: ' . ($responseData['description'] ?? 'Unknown error');
        }
    }

    curl_close($ch);
 
    if (isset($photoFilePath)) {
        unlink($photoFilePath);
    }

    if ($errorMessage) {
        return json_encode([
            'status' => 'error',
            'message' => $errorMessage,
            'http_code' => $httpCode,
            'raw_response' => $response,
            'request_data' => [
                'url' => $telegramApiUrl,
                'chat_id' => $chatId,
                'is_photo' => $isPhoto
            ]
        ]);
    }

    return json_encode([
        'status' => 'success',
        'message' => $isPhoto === 'true' ? 'Photo sent successfully' : 'Message sent successfully',
        'telegram_response' => $responseData
    ]);
}

$request = file_get_contents('php://input');
$response = sendToTelegram($request);

file_put_contents(dirname(__FILE__) . '/all_logs.txt', "Final response from all.php: " . $response . "\n\n", FILE_APPEND);

header('Content-Type: application/json');
echo $response;

?>