<?php

declare(strict_types=1);

namespace WorkWeXin\Service;

use WorkWeXin\Config\Config;
use WorkWeXin\Auth\TokenManager;
use WorkWeXin\Http\HttpClient;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

abstract class BaseService
{
    protected Config $config;
    protected TokenManager $tokenManager;
    protected HttpClient $http;
    protected string $corpKey;
    protected string $appKey;
    protected LoggerInterface $logger;

    public function __construct(
        Config $config,
        TokenManager $tokenManager,
        HttpClient $http,
        string $corpKey,
        string $appKey,
        ?LoggerInterface $logger = null
    )
    {
        $this->config = $config;
        $this->tokenManager = $tokenManager;
        $this->http = $http;
        $this->corpKey = $corpKey;
        $this->appKey = $appKey;
        $this->logger = $logger ?? new NullLogger();
    }

    protected function get(string $uri, array $query = []): array
    {
        $query["access_token"] = $this->tokenManager->getAccessToken($this->corpKey, $this->appKey);
        return $this->http->get($uri, $query);
    }

    protected function post(string $uri, array $data = []): array
    {
        $token = $this->tokenManager->getAccessToken($this->corpKey, $this->appKey);
        return $this->http->post($uri, $data, ["access_token" => $token]);
    }

    protected function getRaw(string $uri, array $query = []): string
    {
        $query["access_token"] = $this->tokenManager->getAccessToken($this->corpKey, $this->appKey);
        return $this->http->getRaw($uri, $query);
    }

    protected function postRaw(string $uri, array $data = []): string
    {
        $token = $this->tokenManager->getAccessToken($this->corpKey, $this->appKey);
        return $this->http->postRaw($uri, $data, ["access_token" => $token]);
    }
}
