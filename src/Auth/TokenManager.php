<?php

declare(strict_types=1);

namespace WorkWeXin\Auth;

use WorkWeXin\Config\Config;
use WorkWeXin\Exception\WeComException;
use WorkWeXin\Http\HttpClient;
use Psr\SimpleCache\CacheInterface;

final class TokenManager
{
    private Config $config;
    private HttpClient $http;
    private CacheInterface $cache;

    public function __construct(Config $config, HttpClient $http, CacheInterface $cache)
    {
        $this->config = $config;
        $this->http = $http;
        $this->cache = $cache;
    }

    public function getAccessToken(string $corpKey, string $appKey): string
    {
        $cacheKey = "access_token:" . $corpKey . ":" . $appKey;
        $token = $this->cache->get($cacheKey);
        if (is_string($token) && $token !== "") {
            return $token;
        }

        $corp = $this->config->getCorp($corpKey);
        $app = $this->config->getApp($corpKey, $appKey);
        $corpId = $corp["corp_id"] ?? "";
        $secret = $app["secret"] ?? "";
        if ($corpId === "" || $secret === "") {
            throw new WeComException(0, "corp_id or secret missing");
        }

        $data = $this->http->get("/cgi-bin/gettoken", [
            "corpid" => $corpId,
            "corpsecret" => $secret
        ]);

        $token = $data["access_token"] ?? "";
        if (!is_string($token) || $token === "") {
            throw new WeComException(0, "access_token missing in response");
        }

        $expiresIn = isset($data["expires_in"]) ? (int) $data["expires_in"] : null;
        $ttl = $expiresIn !== null ? max(60, $expiresIn - 60) : null;
        $this->cache->set($cacheKey, $token, $ttl);

        return $token;
    }
}
