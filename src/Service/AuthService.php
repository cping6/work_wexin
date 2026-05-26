<?php

declare(strict_types=1);

namespace WorkWeXin\Service;

use WorkWeXin\Exception\WeComException;

final class AuthService extends BaseService
{
    public function getAuthorizeUrl(
        string $redirectUri,
        string $state = "STATE",
        string $scope = "snsapi_base",
        ?string $agentId = null
    ): string
    {
        if ($redirectUri === "") {
            throw new \InvalidArgumentException("redirectUri is required");
        }

        $allowedScopes = ["snsapi_base", "snsapi_userinfo"];
        if (!in_array($scope, $allowedScopes, true)) {
            throw new \InvalidArgumentException("scope is invalid");
        }

        $corp = $this->config->getCorp($this->corpKey);
        $app = $this->config->getApp($this->corpKey, $this->appKey);
        $corpId = $corp["corp_id"] ?? "";
        if ($corpId === "") {
            throw new WeComException(0, "corp_id is required for authorize");
        }

        if ($agentId === null) {
            $agentId = $app["agent_id"] ?? null;
        }

        $query = [
            "appid" => $corpId,
            "redirect_uri" => $redirectUri,
            "response_type" => "code",
            "scope" => $scope,
            "state" => $state
        ];
        if ($agentId !== null) {
            $query["agentid"] = (string) $agentId;
        }

        return "https://open.weixin.qq.com/connect/oauth2/authorize?" . http_build_query($query) . "#wechat_redirect";
    }

    public function getQrConnectUrl(string $redirectUri, string $state = "STATE", ?string $agentId = null): string
    {
        if ($redirectUri === "") {
            throw new \InvalidArgumentException("redirectUri is required");
        }

        $corp = $this->config->getCorp($this->corpKey);
        $app = $this->config->getApp($this->corpKey, $this->appKey);
        $corpId = $corp["corp_id"] ?? "";
        if ($corpId === "") {
            throw new WeComException(0, "corp_id is required for qrconnect");
        }

        if ($agentId === null) {
            $agentId = $app["agent_id"] ?? null;
        }

        $query = [
            "appid" => $corpId,
            "redirect_uri" => $redirectUri,
            "state" => $state
        ];
        if ($agentId !== null) {
            $query["agentid"] = (string) $agentId;
        }

        return "https://open.work.weixin.qq.com/wwopen/sso/qrConnect?" . http_build_query($query);
    }

    public function getUserInfo(string $code): array
    {
        if ($code === "") {
            throw new \InvalidArgumentException("code is required");
        }

        return $this->get("/cgi-bin/user/getuserinfo", ["code" => $code]);
    }

    public function getUserDetail(string $userTicket): array
    {
        if ($userTicket === "") {
            throw new \InvalidArgumentException("userTicket is required");
        }

        return $this->post("/cgi-bin/user/getuserdetail", ["user_ticket" => $userTicket]);
    }
}
