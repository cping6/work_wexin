<?php

require __DIR__ . "/../vendor/autoload.php";

use WorkWeXin\WeCom;

$config = [
    "corps" => [
        "default" => [
            "corp_id" => "wx123",
            "apps" => [
                "default" => [
                    "agent_id" => 1000002,
                    "secret" => "app_secret"
                ]
            ],
            "callback" => [
                "token" => "your_token",
                "encoding_aes_key" => "your_encoding_aes_key_43_chars"
            ]
        ]
    ]
];

$wecom = new WeCom($config);
$events = $wecom->callbackEvents();

$events->onEvent("change_contact", function (array $event, $service) {
    // ChangeType: create_user/update_user/delete_user/create_party/update_party/delete_party
    return null;
});

$events->onMessage("text", function (array $event, $service) {
    return $service->buildTextReply($event, "已收到");
});

$reply = $events->dispatch($msgSignature, $timestamp, $nonce, $postData);
// echo $reply;
