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

$templates = $wecom->approval()->listTemplates();
var_dump($templates);

$checkin = $wecom->oa()->getCheckinData(1700000000, 1700003600, ["userid1"]);
var_dump($checkin);
$templateDetail = $wecom->approval()->getTemplateDetail("template_id_xxx");
var_dump($templateDetail);

$approvalDetail = $wecom->approval()->getApprovalDetail("sp_no_xxx");
var_dump($approvalDetail);

$approval = $wecom->approval()->createApproval([
    "creator_userid" => "userid1",
    "template_id" => "template_id_xxx",
    "apply_data" => [
        "contents" => []
    ]
]);
var_dump($approval);

$checkinDay = $wecom->oa()->getCheckinDayData(1700000000, 1700003600, ["userid1"]);
var_dump($checkinDay);

$checkinOption = $wecom->oa()->getCheckinOption(1700000000, ["userid1"]);
var_dump($checkinOption);

$journalList = $wecom->oa()->getJournalRecordList(1700000000, 1700003600, ["userid1"]);
var_dump($journalList);

$journalDetail = $wecom->oa()->getJournalRecordDetail("journal_uuid_xxx");
var_dump($journalDetail);
$addTemplate = $wecom->approval()->addTemplate([
    "template_name" => "请假",
    "template_content" => [
        "controls" => []
    ]
]);
var_dump($addTemplate);

$updateTemplate = $wecom->approval()->updateTemplate([
    "template_id" => "template_id_xxx",
    "template_content" => [
        "controls" => []
    ]
]);
var_dump($updateTemplate);

$scheduleAdd = $wecom->oa()->addSchedule([
    "schedule" => [
        "start_time" => 1700000000,
        "end_time" => 1700003600,
        "summary" => "会议"
    ]
]);
var_dump($scheduleAdd);

$scheduleGet = $wecom->oa()->getSchedule("schedule_id_xxx");
var_dump($scheduleGet);

$scheduleList = $wecom->oa()->getScheduleByDate(1700000000, 1700003600, ["userid1"]);
var_dump($scheduleList);

$meetingRooms = $wecom->oa()->listMeetingRooms();
var_dump($meetingRooms);

$meetingBook = $wecom->oa()->bookMeetingRoom([
    "meetingroom_id" => "room_id_xxx",
    "start_time" => 1700000000,
    "end_time" => 1700003600,
    "subject" => "会议"
]);
var_dump($meetingBook);

$meetingInfo = $wecom->oa()->getMeetingRoomBooking("booking_id_xxx");
var_dump($meetingInfo);

$todoAdd = $wecom->oa()->addTodo([
    "to_userid" => "userid1",
    "title" => "任务",
    "content" => "处理客户"
]);
var_dump($todoAdd);
$deleteTemplate = $wecom->approval()->deleteTemplate("template_id_xxx");
var_dump($deleteTemplate);

$roomDetail = $wecom->oa()->getMeetingRoom("room_id_xxx");
var_dump($roomDetail);

$roomAdd = $wecom->oa()->addMeetingRoom([
    "name" => "小会议室",
    "capacity" => 8
]);
var_dump($roomAdd);

$roomUpdate = $wecom->oa()->updateMeetingRoom([
    "meetingroom_id" => "room_id_xxx",
    "name" => "会议室 A"
]);
var_dump($roomUpdate);

$roomDelete = $wecom->oa()->deleteMeetingRoom("room_id_xxx");
var_dump($roomDelete);

$todoMark = $wecom->oa()->markTodo([
    "todo_id" => "todo_id_xxx",
    "status" => 1
]);
var_dump($todoMark);
$approvalInfo = $wecom->approval()->getApprovalInfo([
    "starttime" => 1700000000,
    "endtime" => 1700003600
]);
var_dump($approvalInfo);

$scheduleAttendeesAdd = $wecom->oa()->addScheduleAttendees([
    "schedule_id" => "schedule_id_xxx",
    "attendees" => [
        ["userid" => "userid1"]
    ]
]);
var_dump($scheduleAttendeesAdd);

$scheduleAttendeesDel = $wecom->oa()->deleteScheduleAttendees([
    "schedule_id" => "schedule_id_xxx",
    "attendees" => [
        ["userid" => "userid1"]
    ]
]);
var_dump($scheduleAttendeesDel);

$meetingAvailability = $wecom->oa()->getMeetingRoomAvailability([
    "meetingroom_id" => "room_id_xxx",
    "start_time" => 1700000000,
    "end_time" => 1700003600
]);
var_dump($meetingAvailability);
$scheduleShare = $wecom->oa()->shareSchedule([
    "schedule_id" => "schedule_id_xxx",
    "share_to" => [
        ["userid" => "userid2"]
    ]
]);
var_dump($scheduleShare);

$scheduleShareCancel = $wecom->oa()->cancelShareSchedule([
    "schedule_id" => "schedule_id_xxx",
    "share_to" => [
        ["userid" => "userid2"]
    ]
]);
var_dump($scheduleShareCancel);

$meetingRoomsByEquip = $wecom->oa()->listMeetingRoomsByEquipment(["equip_id_xxx"]);
var_dump($meetingRoomsByEquip);

$todoBatch = $wecom->oa()->batchAddTodo([
    [
        "to_userid" => "userid1",
        "title" => "任务 A",
        "content" => "处理事项"
    ],
    [
        "to_userid" => "userid2",
        "title" => "任务 B",
        "content" => "处理事项"
    ]
]);
var_dump($todoBatch);

$todoBatchUpdate = $wecom->oa()->batchUpdateTodo([
    [
        "todo_id" => "todo_id_xxx",
        "title" => "已更新"
    ]
]);
var_dump($todoBatchUpdate);

$todoBatchDelete = $wecom->oa()->batchDeleteTodo(["todo_id_xxx", "todo_id_yyy"]);
var_dump($todoBatchDelete);

$approvalAdvanced = $wecom->approval()->getApprovalInfoAdvanced(1700000000, 1700003600, [
    ["key" => "template_id", "value" => "template_id_xxx"]
]);
var_dump($approvalAdvanced);
$scheduleReminder = $wecom->oa()->setScheduleReminder("schedule_id_xxx", 1700000000);
var_dump($scheduleReminder);

$scheduleAttachments = $wecom->oa()->setScheduleAttachments("schedule_id_xxx", [
    ["file_id" => "file_id_xxx"]
]);
var_dump($scheduleAttachments);

$todoReminder = $wecom->oa()->setTodoReminder("todo_id_xxx", 1700000000);
var_dump($todoReminder);

$todoCc = $wecom->oa()->setTodoCc("todo_id_xxx", ["userid2"]);
var_dump($todoCc);

$templateEnable = $wecom->approval()->enableTemplate("template_id_xxx", [
    "controls" => []
]);
var_dump($templateEnable);

$templateDisable = $wecom->approval()->disableTemplate("template_id_xxx", [
    "controls" => []
]);
var_dump($templateDisable);
$scheduleSharePermission = $wecom->oa()->shareScheduleWithPermission("schedule_id_xxx", [
    ["userid" => "userid2"]
], 1);
var_dump($scheduleSharePermission);

$meetingRoomEquipment = $wecom->oa()->getMeetingRoomEquipment("room_id_xxx");
var_dump($meetingRoomEquipment);

$meetingRoomLocation = $wecom->oa()->getMeetingRoomLocation("room_id_xxx");
var_dump($meetingRoomLocation);

$approvalByConditions = $wecom->approval()->getApprovalInfoByConditions(1700000000, 1700003600, [
    "template_id" => "template_id_xxx",
    "creator_userid" => "userid1"
]);
var_dump($approvalByConditions);

