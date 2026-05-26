<?php

declare(strict_types=1);

namespace WorkWeXin\Service;

final class ContactService extends BaseService
{
    public function getUser(string $userId): array
    {
        if ($userId === "") {
            throw new \InvalidArgumentException("userId is required");
        }

        return $this->get("/cgi-bin/user/get", ["userid" => $userId]);
    }

    public function listUsers(int $departmentId = 1, bool $fetchChild = true, ?int $status = null): array
    {
        $query = [
            "department_id" => $departmentId,
            "fetch_child" => $fetchChild ? 1 : 0
        ];
        if ($status !== null) {
            $query["status"] = $status;
        }

        return $this->get("/cgi-bin/user/list", $query);
    }

    public function listSimpleUsers(int $departmentId = 1, bool $fetchChild = true, ?int $status = null): array
    {
        $query = [
            "department_id" => $departmentId,
            "fetch_child" => $fetchChild ? 1 : 0
        ];
        if ($status !== null) {
            $query["status"] = $status;
        }

        return $this->get("/cgi-bin/user/simplelist", $query);
    }

    public function createUser(array $data): array
    {
        $this->assertRequired($data, ["userid", "name", "department"]);
        $this->assertNonEmptyString($data["userid"], "userid");
        $this->assertNonEmptyString($data["name"], "name");
        $this->assertArrayNotEmpty($data["department"], "department");
        $this->assertOptionalEmail($data["email"] ?? null);
        $this->assertOptionalMobile($data["mobile"] ?? null);
        return $this->post("/cgi-bin/user/create", $data);
    }

    public function updateUser(array $data): array
    {
        $this->assertRequired($data, ["userid"]);
        $this->assertNonEmptyString($data["userid"], "userid");
        if (array_key_exists("department", $data)) {
            $this->assertArrayNotEmpty((array) $data["department"], "department");
        }
        $this->assertOptionalEmail($data["email"] ?? null);
        $this->assertOptionalMobile($data["mobile"] ?? null);
        return $this->post("/cgi-bin/user/update", $data);
    }

    public function deleteUser(string $userId): array
    {
        if ($userId === "") {
            throw new \InvalidArgumentException("userId is required");
        }

        return $this->get("/cgi-bin/user/delete", ["userid" => $userId]);
    }

    public function batchDeleteUsers(array $userIds): array
    {
        $this->assertArrayNotEmpty($userIds, "userIds");
        return $this->post("/cgi-bin/user/batchdelete", ["useridlist" => array_values($userIds)]);
    }

    public function inviteUsers(array $userIds = [], array $partyIds = [], array $tagIds = []): array
    {
        if ($userIds === [] && $partyIds === [] && $tagIds === []) {
            throw new \InvalidArgumentException("userIds/partyIds/tagIds requires at least one");
        }

        return $this->post("/cgi-bin/batch/invite", [
            "user" => array_values($userIds),
            "party" => array_values($partyIds),
            "tag" => array_values($tagIds)
        ]);
    }

    public function batchSyncUsers(array $data): array
    {
        $this->assertRequired($data, ["media_id"]);
        return $this->post("/cgi-bin/batch/syncuser", $data);
    }

    public function batchReplaceUsers(array $data): array
    {
        $this->assertRequired($data, ["media_id"]);
        return $this->post("/cgi-bin/batch/replaceuser", $data);
    }

    public function batchReplaceDepartments(array $data): array
    {
        $this->assertRequired($data, ["media_id"]);
        return $this->post("/cgi-bin/batch/replaceparty", $data);
    }

    public function getBatchResult(string $jobId): array
    {
        if ($jobId === "") {
            throw new \InvalidArgumentException("jobId is required");
        }

        return $this->get("/cgi-bin/batch/getresult", ["jobid" => $jobId]);
    }

    public function listDepartments(?int $departmentId = null): array
    {
        $query = [];
        if ($departmentId !== null) {
            $query["id"] = $departmentId;
        }

        return $this->get("/cgi-bin/department/list", $query);
    }

    public function createDepartment(array $data): array
    {
        $this->assertRequired($data, ["name", "parentid"]);
        return $this->post("/cgi-bin/department/create", $data);
    }

    public function updateDepartment(array $data): array
    {
        $this->assertRequired($data, ["id"]);
        return $this->post("/cgi-bin/department/update", $data);
    }

    public function deleteDepartment(int $departmentId): array
    {
        if ($departmentId <= 0) {
            throw new \InvalidArgumentException("departmentId is required");
        }

        return $this->get("/cgi-bin/department/delete", ["id" => $departmentId]);
    }

    public function listTags(): array
    {
        return $this->get("/cgi-bin/tag/list");
    }

    public function getTag(int $tagId): array
    {
        if ($tagId <= 0) {
            throw new \InvalidArgumentException("tagId is required");
        }

        return $this->get("/cgi-bin/tag/get", ["tagid" => $tagId]);
    }

    public function createTag(string $name, ?int $tagId = null): array
    {
        if ($name === "") {
            throw new \InvalidArgumentException("name is required");
        }

        $data = ["tagname" => $name];
        if ($tagId !== null) {
            $data["tagid"] = $tagId;
        }

        return $this->post("/cgi-bin/tag/create", $data);
    }

    public function updateTag(int $tagId, string $tagName): array
    {
        if ($tagId <= 0) {
            throw new \InvalidArgumentException("tagId is required");
        }
        if ($tagName === "") {
            throw new \InvalidArgumentException("tagName is required");
        }

        return $this->post("/cgi-bin/tag/update", ["tagid" => $tagId, "tagname" => $tagName]);
    }

    public function deleteTag(int $tagId): array
    {
        if ($tagId <= 0) {
            throw new \InvalidArgumentException("tagId is required");
        }

        return $this->get("/cgi-bin/tag/delete", ["tagid" => $tagId]);
    }

    public function addTagUsers(int $tagId, array $userList = [], array $partyList = []): array
    {
        if ($tagId <= 0) {
            throw new \InvalidArgumentException("tagId is required");
        }
        if ($userList === [] && $partyList === []) {
            throw new \InvalidArgumentException("userList/partyList requires at least one");
        }

        return $this->post("/cgi-bin/tag/addtagusers", [
            "tagid" => $tagId,
            "userlist" => array_values($userList),
            "partylist" => array_values($partyList)
        ]);
    }

    public function deleteTagUsers(int $tagId, array $userList = [], array $partyList = []): array
    {
        if ($tagId <= 0) {
            throw new \InvalidArgumentException("tagId is required");
        }
        if ($userList === [] && $partyList === []) {
            throw new \InvalidArgumentException("userList/partyList requires at least one");
        }

        return $this->post("/cgi-bin/tag/deltagusers", [
            "tagid" => $tagId,
            "userlist" => array_values($userList),
            "partylist" => array_values($partyList)
        ]);
    }

    private function assertRequired(array $data, array $fields): void
    {
        foreach ($fields as $field) {
            if (!array_key_exists($field, $data)) {
                throw new \InvalidArgumentException($field . " is required");
            }
        }
    }

    private function assertArrayNotEmpty(array $data, string $name): void
    {
        if ($data === []) {
            throw new \InvalidArgumentException($name . " is required");
        }
    }

    private function assertNonEmptyString($value, string $name): void
    {
        if (!is_string($value) || $value === "") {
            throw new \InvalidArgumentException($name . " is required");
        }
    }

    private function assertOptionalEmail($value): void
    {
        if ($value === null || $value === "") {
            return;
        }
        if (!is_string($value) || filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            throw new \InvalidArgumentException("email is invalid");
        }
    }

    private function assertOptionalMobile($value): void
    {
        if ($value === null || $value === "") {
            return;
        }
        if (!is_string($value)) {
            throw new \InvalidArgumentException("mobile is invalid");
        }
    }
}
