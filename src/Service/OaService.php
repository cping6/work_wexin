<?php

declare(strict_types=1);

namespace WorkWeXin\Service;

final class OaService extends BaseService
{
    public function getCheckinData(int $startTime, int $endTime, array $userIdList): array
    {
        $this->assertTimeRange($startTime, $endTime);
        $this->assertUserList($userIdList);

        return $this->post("/cgi-bin/checkin/getcheckindata", [
            "starttime" => $startTime,
            "endtime" => $endTime,
            "useridlist" => array_values($userIdList)
        ]);
    }

    public function getCheckinDayData(int $startTime, int $endTime, array $userIdList): array
    {
        $this->assertTimeRange($startTime, $endTime);
        $this->assertUserList($userIdList);

        return $this->post("/cgi-bin/checkin/getcheckin_daydata", [
            "starttime" => $startTime,
            "endtime" => $endTime,
            "useridlist" => array_values($userIdList)
        ]);
    }

    public function getCheckinOption(int $datetime, array $userIdList): array
    {
        if ($datetime <= 0) {
            throw new \InvalidArgumentException("datetime is required");
        }
        $this->assertUserList($userIdList);

        return $this->post("/cgi-bin/checkin/getcheckinoption", [
            "datetime" => $datetime,
            "useridlist" => array_values($userIdList)
        ]);
    }

    public function getJournalRecordList(int $startTime, int $endTime, array $userIdList): array
    {
        $this->assertTimeRange($startTime, $endTime);
        $this->assertUserList($userIdList);

        return $this->post("/cgi-bin/oa/journal/get_record_list", [
            "starttime" => $startTime,
            "endtime" => $endTime,
            "useridlist" => array_values($userIdList)
        ]);
    }

    public function getJournalRecordDetail(string $journalUuid): array
    {
        if ($journalUuid === "") {
            throw new \InvalidArgumentException("journalUuid is required");
        }

        return $this->post("/cgi-bin/oa/journal/get_record_detail", [
            "journaluuid" => $journalUuid
        ]);
    }

    public function addSchedule(array $data): array
    {
        $this->assertRequired($data, ["schedule"]);
        return $this->post("/cgi-bin/oa/schedule/add", $data);
    }

    public function updateSchedule(array $data): array
    {
        $this->assertRequired($data, ["schedule_id", "schedule"]);
        return $this->post("/cgi-bin/oa/schedule/update", $data);
    }

    public function deleteSchedule(string $scheduleId): array
    {
        if ($scheduleId === "") {
            throw new \InvalidArgumentException("scheduleId is required");
        }

        return $this->post("/cgi-bin/oa/schedule/del", ["schedule_id" => $scheduleId]);
    }

    public function getSchedule(string $scheduleId): array
    {
        if ($scheduleId === "") {
            throw new \InvalidArgumentException("scheduleId is required");
        }

        return $this->post("/cgi-bin/oa/schedule/get", ["schedule_id" => $scheduleId]);
    }

    public function getScheduleByDate(int $startTime, int $endTime, array $userIdList): array
    {
        $this->assertTimeRange($startTime, $endTime);
        $this->assertUserList($userIdList);

        return $this->post("/cgi-bin/oa/schedule/get_by_date", [
            "start_time" => $startTime,
            "end_time" => $endTime,
            "userid_list" => array_values($userIdList)
        ]);
    }

    public function shareSchedule(array $data): array
    {
        $this->assertRequired($data, ["schedule_id", "share_to"]);
        return $this->post("/cgi-bin/oa/schedule/add_share", $data);
    }

    public function shareScheduleWithPermission(string $scheduleId, array $shareTo, int $permission): array
    {
        if ($scheduleId === "") {
            throw new \InvalidArgumentException("scheduleId is required");
        }
        if ($shareTo === []) {
            throw new \InvalidArgumentException("shareTo is required");
        }

        $shareToWithPermission = array_map(function ($item) use ($permission) {
            if (is_array($item) && !array_key_exists("permission", $item)) {
                $item["permission"] = $permission;
            }
            return $item;
        }, $shareTo);

        return $this->shareSchedule([
            "schedule_id" => $scheduleId,
            "share_to" => $shareToWithPermission
        ]);
    }

    public function cancelShareSchedule(array $data): array
    {
        $this->assertRequired($data, ["schedule_id", "share_to"]);
        return $this->post("/cgi-bin/oa/schedule/del_share", $data);
    }

    public function addScheduleAttendees(array $data): array
    {
        $this->assertRequired($data, ["schedule_id", "attendees"]);
        return $this->post("/cgi-bin/oa/schedule/add_attendees", $data);
    }

    public function deleteScheduleAttendees(array $data): array
    {
        $this->assertRequired($data, ["schedule_id", "attendees"]);
        return $this->post("/cgi-bin/oa/schedule/del_attendees", $data);
    }

    public function setScheduleReminder(string $scheduleId, int $remindTime, ?int $remindType = null): array
    {
        if ($scheduleId === "") {
            throw new \InvalidArgumentException("scheduleId is required");
        }
        if ($remindTime <= 0) {
            throw new \InvalidArgumentException("remindTime is required");
        }

        $schedule = ["remind_time" => $remindTime];
        if ($remindType !== null) {
            $schedule["remind_type"] = $remindType;
        }

        return $this->updateSchedule([
            "schedule_id" => $scheduleId,
            "schedule" => $schedule
        ]);
    }

    public function setScheduleAttachments(string $scheduleId, array $attachments): array
    {
        if ($scheduleId === "") {
            throw new \InvalidArgumentException("scheduleId is required");
        }
        if ($attachments === []) {
            throw new \InvalidArgumentException("attachments is required");
        }

        return $this->updateSchedule([
            "schedule_id" => $scheduleId,
            "schedule" => ["attachments" => $attachments]
        ]);
    }

    public function listMeetingRooms(array $query = []): array
    {
        return $this->post("/cgi-bin/oa/meetingroom/list", $query);
    }

    public function listMeetingRoomsByEquipment(array $equipmentIds, array $query = []): array
    {
        if ($equipmentIds === []) {
            throw new \InvalidArgumentException("equipmentIds is required");
        }

        $query["equipment_id"] = array_values($equipmentIds);
        return $this->listMeetingRooms($query);
    }

    public function getMeetingRoom(string $meetingroomId): array
    {
        if ($meetingroomId === "") {
            throw new \InvalidArgumentException("meetingroomId is required");
        }

        return $this->post("/cgi-bin/oa/meetingroom/get", ["meetingroom_id" => $meetingroomId]);
    }

    public function getMeetingRoomEquipment(string $meetingroomId): array
    {
        $data = $this->getMeetingRoom($meetingroomId);
        $room = $data["meetingroom"] ?? ($data["meeting_room"] ?? []);
        if (!is_array($room)) {
            return [];
        }

        $equipment = $room["equipment"] ?? ($room["equipment_list"] ?? []);
        return is_array($equipment) ? $equipment : [];
    }

    public function getMeetingRoomLocation(string $meetingroomId): array
    {
        $data = $this->getMeetingRoom($meetingroomId);
        $room = $data["meetingroom"] ?? ($data["meeting_room"] ?? []);
        if (!is_array($room)) {
            return [];
        }

        $location = $room["location"] ?? [];
        if (is_array($location) && $location !== []) {
            return $location;
        }

        $address = $room["address"] ?? null;
        return $address !== null ? ["address" => $address] : [];
    }

    public function addMeetingRoom(array $data): array
    {
        $this->assertRequired($data, ["name", "capacity"]);
        return $this->post("/cgi-bin/oa/meetingroom/add", $data);
    }

    public function updateMeetingRoom(array $data): array
    {
        $this->assertRequired($data, ["meetingroom_id"]);
        return $this->post("/cgi-bin/oa/meetingroom/update", $data);
    }

    public function deleteMeetingRoom(string $meetingroomId): array
    {
        if ($meetingroomId === "") {
            throw new \InvalidArgumentException("meetingroomId is required");
        }

        return $this->post("/cgi-bin/oa/meetingroom/del", ["meetingroom_id" => $meetingroomId]);
    }

    public function bookMeetingRoom(array $data): array
    {
        $this->assertRequired($data, ["meetingroom_id", "start_time", "end_time"]);
        return $this->post("/cgi-bin/oa/meetingroom/book", $data);
    }

    public function cancelMeetingRoom(string $bookingId): array
    {
        if ($bookingId === "") {
            throw new \InvalidArgumentException("bookingId is required");
        }

        return $this->post("/cgi-bin/oa/meetingroom/cancel", ["booking_id" => $bookingId]);
    }

    public function getMeetingRoomBooking(string $bookingId): array
    {
        if ($bookingId === "") {
            throw new \InvalidArgumentException("bookingId is required");
        }

        return $this->post("/cgi-bin/oa/meetingroom/get_booking_info", ["booking_id" => $bookingId]);
    }

    public function getMeetingRoomAvailability(array $data): array
    {
        $this->assertRequired($data, ["meetingroom_id", "start_time", "end_time"]);
        return $this->post("/cgi-bin/oa/meetingroom/get_booking_info", $data);
    }

    public function addTodo(array $data): array
    {
        return $this->post("/cgi-bin/oa/todo/add", $data);
    }

    public function batchAddTodo(array $todos): array
    {
        if ($todos === []) {
            throw new \InvalidArgumentException("todos is required");
        }

        return $this->batchCall($todos, function (array $todo): array {
            return $this->addTodo($todo);
        });
    }

    public function markTodo(array $data): array
    {
        $this->assertRequired($data, ["todo_id", "status"]);
        return $this->post("/cgi-bin/oa/todo/mark", $data);
    }

    public function setTodoReminder(string $todoId, int $remindTime): array
    {
        if ($todoId === "") {
            throw new \InvalidArgumentException("todoId is required");
        }
        if ($remindTime <= 0) {
            throw new \InvalidArgumentException("remindTime is required");
        }

        return $this->updateTodo([
            "todo_id" => $todoId,
            "remind_time" => $remindTime
        ]);
    }

    public function setTodoCc(string $todoId, array $ccUserIds): array
    {
        if ($todoId === "") {
            throw new \InvalidArgumentException("todoId is required");
        }
        if ($ccUserIds === []) {
            throw new \InvalidArgumentException("ccUserIds is required");
        }

        return $this->updateTodo([
            "todo_id" => $todoId,
            "cc_userid" => array_values($ccUserIds)
        ]);
    }

    public function batchUpdateTodo(array $todos): array
    {
        if ($todos === []) {
            throw new \InvalidArgumentException("todos is required");
        }

        return $this->batchCall($todos, function (array $todo): array {
            return $this->updateTodo($todo);
        });
    }

    public function updateTodo(array $data): array
    {
        $this->assertRequired($data, ["todo_id"]);
        return $this->post("/cgi-bin/oa/todo/update", $data);
    }

    public function batchDeleteTodo(array $todoIds): array
    {
        if ($todoIds === []) {
            throw new \InvalidArgumentException("todoIds is required");
        }

        return $this->batchCall($todoIds, function (string $todoId): array {
            return $this->deleteTodo($todoId);
        });
    }

    public function deleteTodo(string $todoId): array
    {
        if ($todoId === "") {
            throw new \InvalidArgumentException("todoId is required");
        }

        return $this->post("/cgi-bin/oa/todo/del", ["todo_id" => $todoId]);
    }

    public function getTodo(string $todoId): array
    {
        if ($todoId === "") {
            throw new \InvalidArgumentException("todoId is required");
        }

        return $this->post("/cgi-bin/oa/todo/get", ["todo_id" => $todoId]);
    }

    public function listTodo(array $query = []): array
    {
        return $this->post("/cgi-bin/oa/todo/list", $query);
    }

    private function assertTimeRange(int $startTime, int $endTime): void
    {
        if ($startTime <= 0 || $endTime <= 0 || $endTime < $startTime) {
            throw new \InvalidArgumentException("startTime/endTime is invalid");
        }
    }

    private function assertUserList(array $userIdList): void
    {
        if ($userIdList === []) {
            throw new \InvalidArgumentException("userIdList is required");
        }
    }

    private function assertRequired(array $data, array $fields): void
    {
        foreach ($fields as $field) {
            if (!array_key_exists($field, $data)) {
                throw new \InvalidArgumentException($field . " is required");
            }
        }
    }

    private function batchCall(array $items, callable $handler): array
    {
        $results = [];
        $errors = [];
        foreach ($items as $index => $item) {
            try {
                $results[$index] = $handler($item);
            } catch (\Throwable $e) {
                $errors[$index] = ["message" => $e->getMessage(), "code" => $e->getCode()];
            }
        }

        return ["results" => $results, "errors" => $errors];
    }
}
