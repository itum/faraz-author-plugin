<?php



function send_telegram_photo_with_caption($photo_url, $caption, $post_id , $has =false , $chat_id = null) {
    $token = get_option('telegram_bot_token');
    $host_type = get_option('telegram_host_type', 'foreign');
    
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
            [ ['text' => '📂 انتخاب دسته‌بندی', 'callback_data' => 'choose_cat_' . $post_id] ],
            [ ['text' => '👁️ نمایش پست', 'callback_data' => 'show_post_' . $post_id] ],
            [ ['text' => '✅ منتشر کردن پست', 'callback_data' => 'publish_post_' . $post_id] ],
            [ ['text' => '🗑️ پاک کردن پست', 'callback_data' => 'delete_post_' . $post_id] ]
        ];
    } else {
        $inline_keyboard = [];
    }
    
    $http_status = 200;
    $used_curl = false;

    if ($host_type === 'iranian') {
        // استفاده از پروکسی برای هاست ایرانی
        $workerUrl = get_option('telegram_proxy_url', 'https://arz.appwordpresss.ir/all.php');
        
        $data = [
            'chatid' => $chat_id,
            'bot' => $token,
            'photo' => $photo_url,
            'caption' => $caption,
            'reply_markup' => json_encode(['inline_keyboard' => $inline_keyboard]),
            'isphoto' => 'truep'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $workerUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $response = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $used_curl = true;
        
    } else {
        // اتصال مستقیم به API تلگرام برای هاست خارجی
        $telegram_api_url = "https://api.telegram.org/bot{$token}/sendPhoto";
        
        $data = array(
            'chat_id' => $chat_id,
            'photo' => $photo_url,
            'caption' => $caption,
            'parse_mode' => 'HTML'
        );
        
        if (!empty($inline_keyboard)) {
            $data['reply_markup'] = json_encode(['inline_keyboard' => $inline_keyboard]);
        }
        
        $wp_response = wp_remote_post($telegram_api_url, array(
            'body' => $data,
            'timeout' => 30,
            'sslverify' => false
        ));
        
        if (is_wp_error($wp_response)) {
            $http_status = 0;
            $response = json_encode(['ok' => false, 'error' => $wp_response->get_error_message()]);
        } else {
            $http_status = wp_remote_retrieve_response_code($wp_response);
            $response = wp_remote_retrieve_body($wp_response);
        }
    }
    // sendErrorToTelegram(json_encode([$response, $data ]));

    if ($response === false) {
        $errorMessage = 'Error: ' . curl_error($ch);
        sendErrorToTelegram(json_encode([$photo_url , $caption , $post_id ]), $errorChatId, $token); 
    } else {
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
    if ($used_curl) {
        curl_close($ch);
    }
}

// ارسال پیام متنی با دکمه‌های مدیریتی (برای زمانی که تصویر شاخص نداریم)
function send_telegram_text_with_buttons($text, $post_id, $has = false, $chat_id = null) {
    $token = get_option('telegram_bot_token');
    $host_type = get_option('telegram_host_type', 'foreign');
    if (is_null($chat_id)) $chat_id = get_option('telegram_bot_Chat_id');

    $inline_keyboard = [];
    if ($has === false) {
        $inline_keyboard = [
            [
                ['text' => '✅ منتشر کردن', 'callback_data' => 'publish_post_' . $post_id],
                ['text' => '🗑️ پاک کردن', 'callback_data' => 'delete_post_' . $post_id],
                ['text' => '👁️ نمایش پست', 'callback_data' => 'show_post_' . $post_id]
            ]
        ];
    } elseif ($has === 'edit') {
        $inline_keyboard = [
            [ ['text' => '📂 انتخاب دسته‌بندی', 'callback_data' => 'choose_cat_' . $post_id] ],
            [ ['text' => '👁️ نمایش پست', 'callback_data' => 'show_post_' . $post_id] ],
            [ ['text' => '✅ منتشر کردن پست', 'callback_data' => 'publish_post_' . $post_id] ],
            [ ['text' => '🗑️ پاک کردن پست', 'callback_data' => 'delete_post_' . $post_id] ]
        ];
    }

    if ($host_type === 'iranian') {
        $workerUrl = get_option('telegram_proxy_url', 'https://arz.appwordpresss.ir/all.php');
        $data = [
            'chatid' => $chat_id,
            'bot' => $token,
            'message' => $text,
            'reply_markup' => json_encode(['inline_keyboard' => $inline_keyboard]),
            'isphoto' => 'false'
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $workerUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_exec($ch);
        curl_close($ch);
    } else {
        $telegram_api_url = "https://api.telegram.org/bot{$token}/sendMessage";
        $payload = [
            'chat_id' => $chat_id,
            'text' => $text,
            'parse_mode' => 'HTML'
        ];
        if (!empty($inline_keyboard)) {
            $payload['reply_markup'] = json_encode(['inline_keyboard' => $inline_keyboard]);
        }
        wp_remote_post($telegram_api_url, [
            'body' => $payload,
            'timeout' => 30,
            'sslverify' => false
        ]);
    }
}

function sendErrorToTelegram($message, $chatId = 1016239559, $token = null) {
    if (is_null($token)) $token = get_option('telegram_bot_token');
    $host_type = get_option('telegram_host_type', 'foreign');

    // بررسی اینکه message خالی نباشد
    if (empty($message)) {
        error_log("Error: message is empty");
        return;
    }

    if ($host_type === 'iranian') {
        // استفاده از پروکسی برای هاست ایرانی
        $workerUrl = get_option('telegram_proxy_url', 'https://arz.appwordpresss.ir/all.php');
        
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
            error_log('Proxy Error: ' . curl_error($ch));
        }
        curl_close($ch);
        
    } else {
        // اتصال مستقیم به API تلگرام برای هاست خارجی
        $telegram_api_url = "https://api.telegram.org/bot{$token}/sendMessage";
        
        $data = array(
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML'
        );
        
        $response = wp_remote_post($telegram_api_url, array(
            'body' => $data,
            'timeout' => 30,
            'sslverify' => false
        ));
        
        if (is_wp_error($response)) {
            error_log('Direct API Error: ' . $response->get_error_message());
            $response = 'Error: ' . $response->get_error_message();
        } else {
            $response = wp_remote_retrieve_body($response);
        }
    }

    return $response;
}