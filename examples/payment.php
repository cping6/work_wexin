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

$order = $wecom->payment()->unifiedOrder([
    "body" => "test",
    "out_trade_no" => "order_no_xxx",
    "total_fee" => 1,
    "notify_url" => "https://example.com/notify"
]);
var_dump($order);

$query = $wecom->payment()->queryOrder([
    "out_trade_no" => "order_no_xxx"
]);
var_dump($query);

$refund = $wecom->payment()->refund([
    "out_trade_no" => "order_no_xxx",
    "out_refund_no" => "refund_no_xxx",
    "refund_fee" => 1,
    "total_fee" => 1
]);
var_dump($refund);

$transfer = $wecom->payment()->transferToUser([
    "partner_trade_no" => "trade_no_xxx",
    "openid" => "openid_xxx",
    "amount" => 1,
    "desc" => "test"
]);
var_dump($transfer);

$redpack = $wecom->payment()->sendRedPack([
    "mch_billno" => "bill_no_xxx",
    "re_openid" => "openid_xxx",
    "total_amount" => 1,
    "total_num" => 1,
    "wishing" => "hello",
    "act_name" => "act"
]);
var_dump($redpack);

$bill = $wecom->payment()->downloadBill([
    "bill_date" => "20240101"
]);
var_dump($bill);
