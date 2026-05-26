<?php

declare(strict_types=1);

namespace WorkWeXin\Service;

final class ApprovalService extends BaseService
{
    public function listTemplates(array $query = []): array
    {
        return $this->get("/cgi-bin/oa/approval/template/list", $query);
    }

    public function getTemplateDetail(string $templateId): array
    {
        if ($templateId === "") {
            throw new \InvalidArgumentException("templateId is required");
        }

        return $this->get("/cgi-bin/oa/approval/template/get", ["template_id" => $templateId]);
    }

    public function getApprovalDetail(string $spNo): array
    {
        if ($spNo === "") {
            throw new \InvalidArgumentException("spNo is required");
        }

        return $this->post("/cgi-bin/oa/approval/getapprovaldetail", ["sp_no" => $spNo]);
    }

    public function createApproval(array $data): array
    {
        $this->assertRequired($data, ["creator_userid", "template_id", "apply_data"]);
        return $this->post("/cgi-bin/oa/approval/applyevent", $data);
    }

    public function addTemplate(array $data): array
    {
        $this->assertRequired($data, ["template_name", "template_content"]);
        return $this->post("/cgi-bin/oa/approval/template/add", $data);
    }

    public function updateTemplate(array $data): array
    {
        $this->assertRequired($data, ["template_id", "template_content"]);
        return $this->post("/cgi-bin/oa/approval/template/update", $data);
    }

    public function deleteTemplate(string $templateId): array
    {
        if ($templateId === "") {
            throw new \InvalidArgumentException("templateId is required");
        }

        return $this->post("/cgi-bin/oa/approval/template/del", ["template_id" => $templateId]);
    }

    public function getApprovalInfo(array $data): array
    {
        $this->assertRequired($data, ["starttime", "endtime"]);
        return $this->post("/cgi-bin/oa/approval/getapprovalinfo", $data);
    }

    public function getApprovalInfoAdvanced(int $startTime, int $endTime, array $filters = [], ?string $cursor = null, int $size = 100): array
    {
        if ($startTime <= 0 || $endTime <= 0 || $endTime < $startTime) {
            throw new \InvalidArgumentException("startTime/endTime is invalid");
        }
        if ($size <= 0) {
            throw new \InvalidArgumentException("size is required");
        }

        $data = [
            "starttime" => $startTime,
            "endtime" => $endTime,
            "size" => $size
        ];
        if ($cursor !== null && $cursor !== "") {
            $data["cursor"] = $cursor;
        }
        if ($filters !== []) {
            $data["filters"] = $filters;
        }

        return $this->getApprovalInfo($data);
    }

    public function buildApprovalFilters(array $conditions): array
    {
        $filters = [];
        foreach ($conditions as $key => $value) {
            if ($value === null || $value === "" || $value === []) {
                continue;
            }
            $filters[] = ["key" => (string) $key, "value" => $value];
        }

        return $filters;
    }

    public function getApprovalInfoByConditions(int $startTime, int $endTime, array $conditions, ?string $cursor = null, int $size = 100): array
    {
        $filters = $this->buildApprovalFilters($conditions);
        return $this->getApprovalInfoAdvanced($startTime, $endTime, $filters, $cursor, $size);
    }

    public function enableTemplate(string $templateId, array $templateContent): array
    {
        if ($templateId === "") {
            throw new \InvalidArgumentException("templateId is required");
        }
        if ($templateContent === []) {
            throw new \InvalidArgumentException("templateContent is required");
        }

        return $this->post("/cgi-bin/oa/approval/template/update", [
            "template_id" => $templateId,
            "template_content" => $templateContent,
            "status" => 1
        ]);
    }

    public function disableTemplate(string $templateId, array $templateContent): array
    {
        if ($templateId === "") {
            throw new \InvalidArgumentException("templateId is required");
        }
        if ($templateContent === []) {
            throw new \InvalidArgumentException("templateContent is required");
        }

        return $this->post("/cgi-bin/oa/approval/template/update", [
            "template_id" => $templateId,
            "template_content" => $templateContent,
            "status" => 0
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
}
