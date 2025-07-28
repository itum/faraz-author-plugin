<?php



function send_telegram_photo_with_caption($photo_url, $caption, $post_id , $has =false , $chat_id = null) {
    $token = get_option('telegram_bot_token');
    //$workerUrl = 'https://bot.alirea.ir/fakhrzd/cloud.php';
    $workerUrl = 'https://arz.appwordpresss.ir/all.php'; 
    if(is_null($chat_id))
        $chat_id = get_option('telegram_bot_Chat_id');
    $errorChatId = 80266430;
 
    if (empty($caption)) {
        sendErrorToTelegram("Error: Caption is empty", $errorChatId, $token);
        return;
    }

    // بررسی وجود امضا در متن (برای اطمینان از حذف آن از کانال ادمین‌ها)
    $signature_text = wp_strip_all_tags(get_option('farazautur_signature_text', ''));
    if (!empty($signature_text)) {
        // حذف هر گونه امضای موجود در متن پیام
        $caption = str_replace("\n\n" . $signature_text, '', $caption);
        $caption = str_replace($signature_text, '', $caption);
    }

    // اضافه کردن امضا به پیام فقط اگر:
    // 1. امضا فعال باشد
    // 2. پیام به کانال ادمین‌ها ارسال نشود
    // 3. این یک انتشار نهایی باشد ($has = true)
    $admin_chat_id = get_option('telegram_bot_Chat_id');
    if ($has && $chat_id != $admin_chat_id && get_option('farazautur_signature_enabled')) {
        $signature = get_option('farazautur_signature_text');
        if (!empty($signature)) {
            $caption .= "\n\n" . wp_strip_all_tags($signature);
        }
    }

    if (empty($photo_url)) {
        sendErrorToTelegram("Error: Photo URL is empty", $errorChatId, $token);
        return;
    }

    $inline_keyboard = [];
    if($has === false)
    {
        $inline_keyboard = [
            [
                ['text' => '✅ منتشر کردن', 'callback_data' => 'publish_post_' . $post_id],
                ['text' => '🗑️ پاک کردن', 'callback_data' => 'delete_post_' . $post_id],
                ['text' => '👁️ نمایش پست', 'callback_data' => 'show_post_' . $post_id]
            ]
        ];
    } elseif($has === 'edit') {
        $inline_keyboard = [
            [
                ['text' => '📝 ویرایش و نمایش پست', 'web_app' => ['url' => "https://tibin.ir/wp-json/bot-rss/v1/post/$post_id?password=opkwfaopfkoan2" ] ]
            ],
            [
                ['text' => '✅ منتشر کردن پست', 'callback_data' => 'publish_post_' . $post_id]
            ],
            [
                ['text' => '🗑️ پاک کردن پست', 'callback_data' => 'delete_post_' . $post_id]
            ]
        ];
    } else {
        $inline_keyboard = [];
    }
    
    $data = [
        'chatid' => $chat_id,
        'bot' => $token,
        'photo' => $photo_url,
        'caption' => $caption,
        'reply_markup' => json_encode(['inline_keyboard' => $inline_keyboard]),
        'is_photo' => 'true'
    ];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $workerUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    $response = curl_exec($ch);
    // sendErrorToTelegram(json_encode([$response, $data ]));

    if ($response === false) {
        $errorMessage = 'Error: ' . curl_error($ch);
        sendErrorToTelegram(json_encode([$photo_url , $caption , $post_id ]), $errorChatId, $token); 
    } else {
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($http_status != 200) {
            $errorMessage = 'HTTP Error: ' . $http_status . PHP_EOL . 'Response: ' . $response;
            sendErrorToTelegram($errorMessage, $errorChatId, $token); 
        } else {
            $responseData = json_decode($response, true);
            if(isset($responseData['status']) AND $responseData['status'] == 'error') die();

            $responseData = $responseData['ok'] ?? $responseData['telegram_response'];
            if ($responseData['ok'] == true OR $responseData['ok'] == 'true' OR  isset($responseData['result']['message_id']) ) {
                // Ok
 
            } else { 
                if(!$has)
                {
                    wp_delete_post($post_id, true);
                    sendErrorToTelegram(json_encode($responseData));
                } 
                die;
                wp_die(  );
                sleep(20);

            }
        }
    }
    curl_close($ch);
}

function sendErrorToTelegram($message, $chatId = 1016239559, $token = null) {
    if (is_null($token)) $token = get_option('telegram_bot_token');
    $workerUrl = 'https://bot.alirea.ir/fakhrzd/cloud.php';  

    // بررسی اینکه message خالی نباشد
    if (empty($message)) {
        error_log("Error: message is empty");
        return;
    }

    $data = array(
        'chatid' => $chatId,
        'bot' => $token,
        'message' => $message,
        'isphoto' => 'false'  
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $workerUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }
    curl_close($ch);

    return $response;
}

?>