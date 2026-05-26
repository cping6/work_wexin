<?php

declare(strict_types=1);

namespace WorkWeXin\Service;

use WorkWeXin\Exception\WeComException;

final class MiniProgramService extends BaseService
{
    public function getSession(string $jsCode): array
    {
        if ($jsCode === "") {
            throw new \InvalidArgumentException("jsCode is required");
        }

        return $this->get("/cgi-bin/miniprogram/jscode2session", [
            "js_code" => $jsCode,
            "grant_type" => "authorization_code"
        ]);
    }

    public function getCode(array $data): array
    {
        $this->assertRequired($data, ["path"]);
        return $this->post("/cgi-bin/miniprogram/getcode", $data);
    }

    public function getQrCode(array $data): array
    {
        $this->assertRequired($data, ["path"]);
        return $this->post("/cgi-bin/miniprogram/get_qrcode", $data);
    }

    public function getJsapiTicket(string $type = "agent_config"): array
    {
        return $this->get("/cgi-bin/get_jsapi_ticket", ["type" => $type]);
    }

    public function createJsapiConfig(string $url, string $ticket, ?string $nonceStr = null, ?int $timestamp = null): array
    {
        if ($url === "") {
            throw new \InvalidArgumentException("url is required");
        }
        if ($ticket === "") {
            throw new \InvalidArgumentException("ticket is required");
        }

        $nonceStr = $nonceStr ?? bin2hex(random_bytes(8));
        $timestamp = $timestamp ?? time();
        $signature = $this->buildJsapiSignature($ticket, $nonceStr, $timestamp, $url);

        return [
            "nonceStr" => $nonceStr,
            "timestamp" => $timestamp,
            "signature" => $signature
        ];
    }

    public function buildJsapiSignature(string $ticket, string $nonceStr, int $timestamp, string $url): string
    {
        if ($ticket === "" || $nonceStr === "" || $url === "") {
            throw new \InvalidArgumentException("ticket/nonceStr/url is required");
        }
        if ($timestamp <= 0) {
            throw new \InvalidArgumentException("timestamp is required");
        }

        $plain = "jsapi_ticket=" . $ticket
            . "&noncestr=" . $nonceStr
            . "&timestamp=" . $timestamp
            . "&url=" . $url;

        return sha1($plain);
    }

    private function assertRequired(array $data, array $fields): void
    {
        foreach ($fields as $field) {
            if (!array_key_exists($field, $data)) {
                throw new WeComException(0, $field . " is required");
            }
        }
    }
}
