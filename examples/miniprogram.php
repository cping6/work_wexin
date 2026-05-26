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
            ]
        ]
    ]
];

$wecom = new WeCom($config);

$session = $wecom->miniProgram()->getSession("js_code_xxx");
var_dump($session);

$code = $wecom->miniProgram()->getCode([
    "path" => "pages/index/index"
]);
var_dump($code);

$qr = $wecom->miniProgram()->getQrCode([
    "path" => "pages/index/index"
]);
var_dump($qr);

$ticket = $wecom->miniProgram()->getJsapiTicket();
var_dump($ticket);

$config = $wecom->miniProgram()->createJsapiConfig("https://example.com", $ticket["ticket"] ?? "ticket");
var_dump($config);
