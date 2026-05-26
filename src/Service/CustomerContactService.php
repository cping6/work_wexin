<?php

declare(strict_types=1);

namespace WorkWeXin\Service;

final class CustomerContactService extends BaseService
{
    public function listFollowUsers(): array
    {
        return $this->get("/cgi-bin/externalcontact/get_follow_user_list");
    }

    public function listExternalContacts(string $userId): array
    {
        if ($userId === "") {
            throw new \InvalidArgumentException("userId is required");
        }

        return $this->get("/cgi-bin/externalcontact/list", ["userid" => $userId]);
    }

    public function getExternalContact(string $externalUserId, ?string $cursor = null): array
    {
        if ($externalUserId === "") {
            throw new \InvalidArgumentException("externalUserId is required");
        }

        $query = ["external_userid" => $externalUserId];
        if ($cursor !== null && $cursor !== "") {
            $query["cursor"] = $cursor;
        }

        return $this->get("/cgi-bin/externalcontact/get", $query);
    }

    public function batchGetByUser(string $userId, ?string $cursor = null, int $limit = 100): array
    {
        if ($userId === "") {
            throw new \InvalidArgumentException("userId is required");
        }
        if ($limit <= 0) {
            throw new \InvalidArgumentException("limit is required");
        }

        $data = [
            "userid" => $userId,
            "limit" => $limit
        ];
        if ($cursor !== null && $cursor !== "") {
            $data["cursor"] = $cursor;
        }

        return $this->post("/cgi-bin/externalcontact/batch/get_by_user", $data);
    }

    public function listGroupChats(int $statusFilter = 0, array $ownerFilter = [], ?string $cursor = null, int $limit = 1000): array
    {
        if ($limit <= 0) {
            throw new \InvalidArgumentException("limit is required");
        }

        $data = [
            "status_filter" => $statusFilter,
            "limit" => $limit
        ];
        if ($ownerFilter !== []) {
            $data["owner_filter"] = ["userid_list" => array_values($ownerFilter)];
        }
        if ($cursor !== null && $cursor !== "") {
            $data["cursor"] = $cursor;
        }

        return $this->post("/cgi-bin/externalcontact/groupchat/list", $data);
    }

    public function getGroupChat(string $chatId, int $needName = 1): array
    {
        if ($chatId === "") {
            throw new \InvalidArgumentException("chatId is required");
        }

        return $this->post("/cgi-bin/externalcontact/groupchat/get", [
            "chat_id" => $chatId,
            "need_name" => $needName
        ]);
    }

    public function updateRemark(array $data): array
    {
        $this->assertRequired($data, ["userid", "external_userid"]);
        return $this->post("/cgi-bin/externalcontact/remark", $data);
    }

    public function addContactWay(array $data): array
    {
        $this->assertRequired($data, ["type", "scene"]);
        return $this->post("/cgi-bin/externalcontact/add_contact_way", $data);
    }

    public function getContactWay(string $configId): array
    {
        if ($configId === "") {
            throw new \InvalidArgumentException("configId is required");
        }

        return $this->post("/cgi-bin/externalcontact/get_contact_way", ["config_id" => $configId]);
    }

    public function updateContactWay(array $data): array
    {
        $this->assertRequired($data, ["config_id"]);
        return $this->post("/cgi-bin/externalcontact/update_contact_way", $data);
    }

    public function deleteContactWay(string $configId): array
    {
        if ($configId === "") {
            throw new \InvalidArgumentException("configId is required");
        }

        return $this->post("/cgi-bin/externalcontact/del_contact_way", ["config_id" => $configId]);
    }

    public function closeTempChat(string $userId, string $externalUserId): array
    {
        if ($userId === "") {
            throw new \InvalidArgumentException("userId is required");
        }
        if ($externalUserId === "") {
            throw new \InvalidArgumentException("externalUserId is required");
        }

        return $this->post("/cgi-bin/externalcontact/close_temp_chat", [
            "userid" => $userId,
            "external_userid" => $externalUserId
        ]);
    }

    public function sendWelcome(array $data): array
    {
        $this->assertRequired($data, ["welcome_code"]);
        return $this->post("/cgi-bin/externalcontact/send_welcome_msg", $data);
    }

    public function getCorpTagList(array $tagIds = [], array $groupIds = []): array
    {
        $data = [];
        if ($tagIds !== []) {
            $data["tag_id"] = array_values($tagIds);
        }
        if ($groupIds !== []) {
            $data["group_id"] = array_values($groupIds);
        }

        return $this->post("/cgi-bin/externalcontact/get_corp_tag_list", $data);
    }

    public function addCorpTag(array $data): array
    {
        $this->assertRequired($data, ["group_name", "tag"]);
        return $this->post("/cgi-bin/externalcontact/add_corp_tag", $data);
    }

    public function editCorpTag(array $data): array
    {
        $this->assertRequired($data, ["id"]);
        return $this->post("/cgi-bin/externalcontact/edit_corp_tag", $data);
    }

    public function deleteCorpTag(array $tagIds = [], array $groupIds = []): array
    {
        if ($tagIds === [] && $groupIds === []) {
            throw new \InvalidArgumentException("tagIds/groupIds requires at least one");
        }

        $data = [];
        if ($tagIds !== []) {
            $data["tag_id"] = array_values($tagIds);
        }
        if ($groupIds !== []) {
            $data["group_id"] = array_values($groupIds);
        }

        return $this->post("/cgi-bin/externalcontact/del_corp_tag", $data);
    }

    public function markTag(array $data): array
    {
        $this->assertRequired($data, ["userid", "external_userid"]);
        $addTag = $data["add_tag"] ?? [];
        $removeTag = $data["remove_tag"] ?? [];
        if ($addTag === [] && $removeTag === []) {
            throw new \InvalidArgumentException("add_tag/remove_tag requires at least one");
        }

        return $this->post("/cgi-bin/externalcontact/mark_tag", $data);
    }

    public function getUnassignedList(int $pageId = 0, int $pageSize = 1000): array
    {
        if ($pageId < 0) {
            throw new \InvalidArgumentException("pageId is invalid");
        }
        if ($pageSize <= 0) {
            throw new \InvalidArgumentException("pageSize is required");
        }

        return $this->post("/cgi-bin/externalcontact/get_unassigned_list", [
            "page_id" => $pageId,
            "page_size" => $pageSize
        ]);
    }

    public function transferCustomer(array $data): array
    {
        $this->assertRequired($data, ["handover_userid", "takeover_userid", "external_userid"]);
        return $this->post("/cgi-bin/externalcontact/transfer_customer", $data);
    }

    public function transferGroupChat(array $data): array
    {
        $this->assertRequired($data, ["chat_id_list", "new_owner"]);
        return $this->post("/cgi-bin/externalcontact/groupchat/transfer", $data);
    }

    public function getGroupChatStatistic(int $startTime, int $endTime, array $ownerFilter = [], ?string $cursor = null, int $limit = 1000): array
    {
        if ($startTime <= 0 || $endTime <= 0 || $endTime < $startTime) {
            throw new \InvalidArgumentException("startTime/endTime is invalid");
        }
        if ($limit <= 0) {
            throw new \InvalidArgumentException("limit is required");
        }

        $data = [
            "start_time" => $startTime,
            "end_time" => $endTime,
            "limit" => $limit
        ];
        if ($ownerFilter !== []) {
            $data["owner_filter"] = ["userid_list" => array_values($ownerFilter)];
        }
        if ($cursor !== null && $cursor !== "") {
            $data["cursor"] = $cursor;
        }

        return $this->post("/cgi-bin/externalcontact/groupchat/statistic", $data);
    }

    public function getUserBehaviorData(int $startTime, int $endTime, array $userIdList): array
    {
        if ($startTime <= 0 || $endTime <= 0 || $endTime < $startTime) {
            throw new \InvalidArgumentException("startTime/endTime is invalid");
        }
        if ($userIdList === []) {
            throw new \InvalidArgumentException("userIdList is required");
        }

        return $this->post("/cgi-bin/externalcontact/get_user_behavior_data", [
            "start_time" => $startTime,
            "end_time" => $endTime,
            "userid_list" => array_values($userIdList)
        ]);
    }

    public function getGroupChatMembers(string $chatId, int $needName = 1): array
    {
        $data = $this->getGroupChat($chatId, $needName);
        $group = $data["group_chat"] ?? [];
        $members = $group["member_list"] ?? [];
        return is_array($members) ? $members : [];
    }

    public function addGroupWelcomeTemplate(array $data): array
    {
        $this->assertRequired($data, ["chat_id"]);
        return $this->post("/cgi-bin/externalcontact/group_welcome_template/add", $data);
    }

    public function editGroupWelcomeTemplate(array $data): array
    {
        $this->assertRequired($data, ["template_id"]);
        return $this->post("/cgi-bin/externalcontact/group_welcome_template/edit", $data);
    }

    public function deleteGroupWelcomeTemplate(string $templateId): array
    {
        if ($templateId === "") {
            throw new \InvalidArgumentException("templateId is required");
        }

        return $this->post("/cgi-bin/externalcontact/group_welcome_template/del", ["template_id" => $templateId]);
    }

    public function getGroupWelcomeTemplate(string $templateId): array
    {
        if ($templateId === "") {
            throw new \InvalidArgumentException("templateId is required");
        }

        return $this->post("/cgi-bin/externalcontact/group_welcome_template/get", ["template_id" => $templateId]);
    }

    private function assertRequired(array $data, array $fields): void
    {
        foreach ($fields as $field) {
            if (!array_key_exists($field, $data)) {
                throw new \InvalidArgumentException($field . " is required");
            }
        }
    }
}
