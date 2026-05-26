<?php

require __DIR__ . "/../vendor/autoload.php";

use WorkWeXin\WeCom;

$config = [
    "corps" => [
        "corp_a" => [
            "corp_id" => "wx123",
            "apps" => [
                "app1" => [
                    "agent_id" => 1000002,
                    "secret" => "app_secret"
                ]
            ]
        ],
        "corp_b" => [
            "corp_id" => "wx456",
            "apps" => [
                "app2" => [
                    "agent_id" => 1000003,
                    "secret" => "app_secret_2"
                ]
            ]
        ]
    ],
    "cache" => [
        "path" => __DIR__ . "/../runtime/cache",
        "ttl" => 7200
    ],
    "http" => [
        "timeout" => 5.0,
        "base_uri" => "https://qyapi.weixin.qq.com"
    ]
];

$wecom = new WeCom($config, "corp_a", "app1");

$wecom->message()->send([
    "touser" => "userid1",
    "msgtype" => "text",
    "text" => ["content" => "Hello"]
]);

$users = $wecom->contacts()->listUsers(1);
var_dump($users);
