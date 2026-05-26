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

$followUsers = $wecom->customerContact()->listFollowUsers();
var_dump($followUsers);

$customerList = $wecom->customerContact()->listExternalContacts("userid1");
var_dump($customerList);
$detail = $wecom->customerContact()->getExternalContact("external_userid_xxx");
var_dump($detail);

$batch = $wecom->customerContact()->batchGetByUser("userid1", null, 50);
var_dump($batch);

$groups = $wecom->customerContact()->listGroupChats(0, ["userid1"], null, 100);
var_dump($groups);

$groupDetail = $wecom->customerContact()->getGroupChat("chat_id_xxx");
var_dump($groupDetail);
$remark = $wecom->customerContact()->updateRemark([
    "userid" => "userid1",
    "external_userid" => "external_userid_xxx",
    "remark" => "VIP"
]);
var_dump($remark);

$contactWay = $wecom->customerContact()->addContactWay([
    "type" => 1,
    "scene" => 2,
    "state" => "from_sdk"
]);
var_dump($contactWay);

$contactWayDetail = $wecom->customerContact()->getContactWay("config_id_xxx");
var_dump($contactWayDetail);

$contactWayUpdate = $wecom->customerContact()->updateContactWay([
    "config_id" => "config_id_xxx",
    "remark" => "updated"
]);
var_dump($contactWayUpdate);

$contactWayDelete = $wecom->customerContact()->deleteContactWay("config_id_xxx");
var_dump($contactWayDelete);

$tempChat = $wecom->customerContact()->closeTempChat("userid1", "external_userid_xxx");
var_dump($tempChat);

$welcome = $wecom->customerContact()->sendWelcome([
    "welcome_code" => "welcome_code_xxx",
    "text" => [
        "content" => "欢迎加入"
    ]
]);
var_dump($welcome);

$groupWelcome = $wecom->customerContact()->addGroupWelcomeTemplate([
    "chat_id" => "chat_id_xxx",
    "text" => [
        "content" => "欢迎"
    ]
]);
var_dump($groupWelcome);

$groupWelcomeEdit = $wecom->customerContact()->editGroupWelcomeTemplate([
    "template_id" => "template_id_xxx",
    "text" => [
        "content" => "更新欢迎词"
    ]
]);
var_dump($groupWelcomeEdit);

$groupWelcomeGet = $wecom->customerContact()->getGroupWelcomeTemplate("template_id_xxx");
var_dump($groupWelcomeGet);

$groupWelcomeDel = $wecom->customerContact()->deleteGroupWelcomeTemplate("template_id_xxx");
var_dump($groupWelcomeDel);
$tagList = $wecom->customerContact()->getCorpTagList();
var_dump($tagList);

$addTag = $wecom->customerContact()->addCorpTag([
    "group_name" => "VIP",
    "tag" => [
        ["name" => "新客户"]
    ]
]);
var_dump($addTag);

$editTag = $wecom->customerContact()->editCorpTag([
    "id" => "tag_id_xxx",
    "name" => "老客户"
]);
var_dump($editTag);

$markTag = $wecom->customerContact()->markTag([
    "userid" => "userid1",
    "external_userid" => "external_userid_xxx",
    "add_tag" => ["tag_id_xxx"]
]);
var_dump($markTag);

$unassigned = $wecom->customerContact()->getUnassignedList(0, 100);
var_dump($unassigned);

$transfer = $wecom->customerContact()->transferCustomer([
    "handover_userid" => "userid1",
    "takeover_userid" => "userid2",
    "external_userid" => ["external_userid_xxx"]
]);
var_dump($transfer);

$transferGroup = $wecom->customerContact()->transferGroupChat([
    "chat_id_list" => ["chat_id_xxx"],
    "new_owner" => "userid2"
]);
var_dump($transferGroup);

$stat = $wecom->customerContact()->getGroupChatStatistic(1700000000, 1700003600, ["userid1"], null, 100);
var_dump($stat);
$behavior = $wecom->customerContact()->getUserBehaviorData(1700000000, 1700003600, ["userid1"]);
var_dump($behavior);

$members = $wecom->customerContact()->getGroupChatMembers("chat_id_xxx");
var_dump($members);

