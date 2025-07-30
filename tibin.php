<?php
function handleRequest() {
    $url = parse_url($_SERVER['REQUEST_URI']);
    parse_str($url['query'], $queryParams);

    $bot = isset($queryParams['bot']) ? $queryParams['bot'] : null;
    $url_p = isset($queryParams['url']) ? $queryParams['url'] : null;
    $setWebP = isset($queryParams['setWebP']) ? $queryParams['setWebP'] : null;
    $json = isset($queryParams['json']) ? $queryParams['json'] : null;

    if ($json == "r") {
        $response = readjson();
        return $response;
    }

    if ($setWebP === "True") {
        if (!$bot || !$url_p) {
            return new Response('Missing parameters', 400);
        }
        $apiUrl = "https://api.telegram.org/bot{$bot}/setWebhook?url={$url_p}";
        $response = setWebhook($apiUrl);
        return $response;
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $requestBody = json_decode(file_get_contents('php://input'), true);
        $PhpResponse = SendToPhp($requestBody, $bot);
        $TelegramResponse = SendToTelegram($PhpResponse);
        return $TelegramResponse;
    }

    return new Response('All down', 200);
}

function readjson() {
    $jsonUrl = "https://test.shr1.ir/wp-admin/parsa.json";
    $response = file_get_contents($jsonUrl);
    $data = json_decode($response, true);
    $TelegramResponse = SendToTelegram($data);
    return $TelegramResponse;
}

function SendToTelegram($PhpResponse) {
    $chatId = $PhpResponse['chatid'];
    $token = $PhpResponse['bot'];
    $isphoto = $PhpResponse['isphoto'];

    if ($isphoto === "truep") {
        $caption = $PhpResponse['caption'];
        $photo = $PhpResponse['photo'];
        $reply_markup = $PhpResponse['reply_markup'];
        $responseBody = [
            'chat_id' => $chatId,
            'photo' => $photo,
            'reply_markup' => $reply_markup,
            'caption' => $caption,
        ];
        $urlT = "https://api.telegram.org/bot{$token}/sendPhoto";
    } else {
        $message = $PhpResponse['message'];
        $responseBody = [
            'chat_id' => $chatId,
            'text' => $message,
        ];
        $urlT = "https://api.telegram.org/bot{$token}/sendMessage";
    }

    $options = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n",
            'method'  => 'POST',
            'content' => json_encode($responseBody),
        ],
    ];
    $context  = stream_context_create($options);
    file_get_contents($urlT, false, $context);

    return new Response('Message processed', 200);
}

function SendToPhp($requestBody, $bot) {
    $PhpUrl = "https://tibin.ir/wp-json/faraz/v1/handle";

    $options = [
        'http' => [
            'header'  => "Content-Type: text/plain\r\n",
            'method'  => 'POST',
            'content' => json_encode($requestBody),
        ],
    ];
    $context  = stream_context_create($options);
    $response = file_get_contents($PhpUrl, false, $context);

    if ($response === FALSE) {
        return new Response('Error calling PHP endpoint', 500);
    }

    return json_decode($response, true);
}

function setWebhook($apiUrl) {
    $response = file_get_contents($apiUrl);
    $data = json_decode($response, true);

    if ($data['ok']) {
        return new Response('Webhook set successfully!', 200);
    } else {
        return new Response("Error setting webhook: " . $data['description'], 200);
    }
}

class Response {
    private $message;
    private $status;

    public function __construct($message, $status) {
        $this->message = $message;
        $this->status = $status;
    }

    public function send() {
        http_response_code($this->status);
        echo $this->message;
    }
}

handleRequest()->send();