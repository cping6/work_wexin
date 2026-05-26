<?php

declare(strict_types=1);

namespace WorkWeXin\Tests;

use PHPUnit\Framework\TestCase;
use WorkWeXin\Auth\TokenManager;
use WorkWeXin\Cache\FileCache;
use WorkWeXin\Config\Config;
use WorkWeXin\Http\HttpClient;
use WorkWeXin\Service\OaService;
use WorkWeXin\Tests\Support\ArrayCache;
use WorkWeXin\Tests\Support\SpyHttpClient;
use WorkWeXin\WeCom;

final class OaServiceTest extends TestCase
{
    public function testGetCheckinDataRequiresValidRange(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->getCheckinData(0, 10, ["u1"]);
    }

    public function testGetCheckinDataRequiresUserList(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->getCheckinData(10, 20, []);
    }

    public function testGetCheckinOptionRequiresDatetime(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->getCheckinOption(0, ["u1"]);
    }

    public function testGetJournalRecordDetailRequiresUuid(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->getJournalRecordDetail("");
    }

    public function testAddScheduleRequiresSchedule(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->addSchedule([]);
    }

    public function testUpdateScheduleRequiresFields(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->updateSchedule(["schedule_id" => "s1"]);
    }

    public function testDeleteScheduleRequiresId(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->deleteSchedule("");
    }

    public function testGetScheduleRequiresId(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->getSchedule("");
    }

    public function testGetScheduleByDateRequiresRange(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->getScheduleByDate(10, 0, ["u1"]);
    }

    public function testGetScheduleByDateRequiresUserList(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->getScheduleByDate(10, 20, []);
    }

    public function testGetScheduleByDateBuildsRequest(): void
    {
        [$wecom, $httpClient] = $this->makeWeCom();

        $wecom->oa()->getScheduleByDate(100, 200, ["u1", "u2"]);

        $request = $httpClient->getLastRequest();
        $this->assertNotNull($request);
        $this->assertSame("/cgi-bin/oa/schedule/get_by_date", $request->getUri()->getPath());
        $this->assertSame("token", $this->getQueryValue((string) $request->getUri()->getQuery(), "access_token"));

        $payload = json_decode((string) $request->getBody(), true);
        $this->assertSame(100, $payload["start_time"] ?? null);
        $this->assertSame(200, $payload["end_time"] ?? null);
        $this->assertSame(["u1", "u2"], $payload["userid_list"] ?? null);
    }

    public function testShareScheduleRequiresFields(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->shareSchedule(["schedule_id" => "s1"]);
    }

    public function testShareScheduleWithPermissionRequiresFields(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->shareScheduleWithPermission("", [], 1);
    }

    public function testCancelShareScheduleRequiresFields(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->cancelShareSchedule(["schedule_id" => "s1"]);
    }

    public function testAddScheduleAttendeesRequiresFields(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->addScheduleAttendees(["schedule_id" => "s1"]);
    }

    public function testDeleteScheduleAttendeesRequiresFields(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->deleteScheduleAttendees(["schedule_id" => "s1"]);
    }

    public function testSetScheduleReminderRequiresFields(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->setScheduleReminder("", 1);
    }

    public function testSetScheduleReminderRequiresTime(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->setScheduleReminder("s1", 0);
    }

    public function testSetScheduleAttachmentsRequiresFields(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->setScheduleAttachments("", []);
    }

    public function testBookMeetingRoomRequiresFields(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->bookMeetingRoom(["meetingroom_id" => "m1"]);
    }

    public function testListMeetingRoomsByEquipmentRequiresIds(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->listMeetingRoomsByEquipment([]);
    }

    public function testCancelMeetingRoomRequiresId(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->cancelMeetingRoom("");
    }

    public function testGetMeetingRoomBookingRequiresId(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->getMeetingRoomBooking("");
    }

    public function testGetMeetingRoomAvailabilityRequiresFields(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->getMeetingRoomAvailability(["meetingroom_id" => "m1"]);
    }

    public function testGetMeetingRoomAvailabilityBuildsRequest(): void
    {
        [$wecom, $httpClient] = $this->makeWeCom();

        $wecom->oa()->getMeetingRoomAvailability([
            "meetingroom_id" => "room1",
            "start_time" => 100,
            "end_time" => 200
        ]);

        $request = $httpClient->getLastRequest();
        $this->assertNotNull($request);
        $this->assertSame("/cgi-bin/oa/meetingroom/get_booking_info", $request->getUri()->getPath());
        $this->assertSame("token", $this->getQueryValue((string) $request->getUri()->getQuery(), "access_token"));

        $payload = json_decode((string) $request->getBody(), true);
        $this->assertSame("room1", $payload["meetingroom_id"] ?? null);
        $this->assertSame(100, $payload["start_time"] ?? null);
        $this->assertSame(200, $payload["end_time"] ?? null);
    }

    public function testGetMeetingRoomRequiresId(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->getMeetingRoom("");
    }

    public function testAddMeetingRoomRequiresFields(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->addMeetingRoom(["name" => "room"]);
    }

    public function testUpdateMeetingRoomRequiresId(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->updateMeetingRoom([]);
    }

    public function testDeleteMeetingRoomRequiresId(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->deleteMeetingRoom("");
    }

    public function testMarkTodoRequiresFields(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->markTodo(["todo_id" => "t1"]);
    }

    public function testSetTodoReminderRequiresFields(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->setTodoReminder("", 1);
    }

    public function testSetTodoReminderRequiresTime(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->setTodoReminder("t1", 0);
    }

    public function testSetTodoCcRequiresFields(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->setTodoCc("", []);
    }

    public function testUpdateTodoRequiresId(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->updateTodo([]);
    }

    public function testBatchAddTodoRequiresList(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->batchAddTodo([]);
    }

    public function testBatchUpdateTodoRequiresList(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->batchUpdateTodo([]);
    }

    public function testBatchDeleteTodoRequiresList(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->batchDeleteTodo([]);
    }

    public function testDeleteTodoRequiresId(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->deleteTodo("");
    }

    public function testGetTodoRequiresId(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->getTodo("");
    }

    private function makeService(): OaService
    {
        $config = new Config([
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
        ]);
        $http = new HttpClient([]);
        $cache = new FileCache(sys_get_temp_dir());
        $tokenManager = new TokenManager($config, $http, $cache);

        return new OaService($config, $tokenManager, $http, "default", "default");
    }

    private function makeWeCom(): array
    {
        $cache = new ArrayCache();
        $cache->set("access_token:default:default", "token");
        $httpClient = new SpyHttpClient();

        $wecom = new WeCom([
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
            ],
            "cache" => [
                "instance" => $cache
            ],
            "http" => [
                "client" => $httpClient,
                "base_uri" => "https://qyapi.weixin.qq.com"
            ]
        ]);

        return [$wecom, $httpClient];
    }

    private function getQueryValue(string $query, string $key): ?string
    {
        parse_str($query, $params);
        return isset($params[$key]) ? (string) $params[$key] : null;
    }
}
