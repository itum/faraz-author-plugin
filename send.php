<?php



function send_telegram_photo_with_caption($photo_url, $caption, $post_id , $has =false , $chat_id = null) {
    $token = get_option('telegram_bot_token');
    //$workerUrl = 'https://bot.alirea.ir/fakhrzd/cloud.php';
    $workerUrl = 'https://khetabat.com/all.php'; 
    if(is_null($chat_id))
        $chat_id = get_option('telegram_bot_Chat_id');
    $errorChatId = 1016239559;
 
    if (empty($caption)) {
        sendErrorToTelegram("Error: Caption is empty", $errorChatId, $token);
        return;
    }

    if (empty($photo_url)) {
        sendErrorToTelegram("Error: Photo URL is empty", $errorChatId, $token);
        return;
    }
    if(!$has)
    {
        $inline_keyboard = [
        [
            ['text' => 'منتشر کردن پست', 'callback_data' => 'publish_post_' . $post_id],
            ['text' => 'پاک کردن پست', 'callback_data' => 'delete_post_' . $post_id],
            ['text' => 'نمایش و تغییر پست', 'callback_data' => 'edited_post_' . $post_id]
        ]
    ];
    }else     
    $inline_keyboard = [
        [
            ['text' => 'منتشر کردن پست', 'callback_data' => 'publish_post_' . $post_id],
            ['text' => 'پاک کردن پست', 'callback_data' => 'delete_post_' . $post_id]
        ],
        [
            ['text' => 'نمایش پست', 'web_app' => ['url' => "https://tibin.ir/wp-json/bot-rss/v1/post/$post_id?password=opkwfaopfkoan2" ] ]
        ]
    ];

    
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