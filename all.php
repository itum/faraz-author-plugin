<?php 

 
function downloadPhoto($url) { 
    $tempFile = tempnam(sys_get_temp_dir(), 'photo_');
    file_put_contents($tempFile, file_get_contents($url));
    return $tempFile;
}

function sendToTelegram($request) {
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
        $photoFilePath = downloadPhoto($photo);
        $body = [
            'chat_id' => $chatId,
            'photo' => new CURLFile($photoFilePath),
            'caption' => $caption,
            'parse_mode' => 'HTML'
        ]; 
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

header('Content-Type: application/json');
echo $response;

?>