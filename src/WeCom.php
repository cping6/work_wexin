<?php

declare(strict_types=1);

namespace WorkWeXin;

use WorkWeXin\Auth\TokenManager;
use WorkWeXin\Cache\FileCache;
use WorkWeXin\Callback\CallbackCrypto;
use WorkWeXin\Config\Config;
use WorkWeXin\Http\HttpClient;
use WorkWeXin\Service\ApprovalService;
use WorkWeXin\Service\AuthService;
use WorkWeXin\Service\CallbackService;
use WorkWeXin\Service\ContactService;
use WorkWeXin\Service\CustomerContactService;
use WorkWeXin\Service\MessageService;
use WorkWeXin\Service\OaService;
use WorkWeXin\Service\MiniProgramService;
use WorkWeXin\Service\PaymentService;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\SimpleCache\CacheInterface;

final class WeCom
{
    private Config $config;
    private array $rawConfig;
    private string $corpKey;
    private string $appKey;
    private HttpClient $http;
    private CacheInterface $cache;
    private TokenManager $tokenManager;
    private LoggerInterface $logger;

    public function __construct(array $config, string $corpKey = "default", string $appKey = "default")
    {
        $this->rawConfig = $config;
        $this->config = new Config($config);
        $this->corpKey = $corpKey;
        $this->appKey = $appKey;

        $logger = $config["logger"] ?? null;
        $this->logger = $logger instanceof LoggerInterface ? $logger : new NullLogger();

        $httpConfig = $this->config->getHttp();
        $client = $httpConfig["client"] ?? null;
        $httpClient = $client instanceof ClientInterface ? $client : null;
        $this->http = new HttpClient($httpConfig, $httpClient, $this->logger);

        $cacheConfig = $this->config->getCache();
        $cacheInstance = $cacheConfig["instance"] ?? null;
        if ($cacheInstance instanceof CacheInterface) {
            $this->cache = $cacheInstance;
        } else {
            $cachePath = $cacheConfig["path"] ?? (getcwd() . DIRECTORY_SEPARATOR . "runtime" . DIRECTORY_SEPARATOR . "cache");
            $cacheTtl = isset($cacheConfig["ttl"]) ? (int) $cacheConfig["ttl"] : 7200;
            $this->cache = new FileCache($cachePath, $cacheTtl);
        }

        $this->tokenManager = new TokenManager($this->config, $this->http, $this->cache);
    }

    public function forCorpApp(string $corpKey, string $appKey): self
    {
        return new self($this->rawConfig, $corpKey, $appKey);
    }

    public function message(): MessageService
    {
        return new MessageService($this->config, $this->tokenManager, $this->http, $this->corpKey, $this->appKey, $this->logger);
    }

    public function customerContact(): CustomerContactService
    {
        return new CustomerContactService($this->config, $this->tokenManager, $this->http, $this->corpKey, $this->appKey, $this->logger);
    }

    public function approval(): ApprovalService
    {
        return new ApprovalService($this->config, $this->tokenManager, $this->http, $this->corpKey, $this->appKey, $this->logger);
    }

    public function oa(): OaService
    {
        return new OaService($this->config, $this->tokenManager, $this->http, $this->corpKey, $this->appKey, $this->logger);
    }

    public function miniProgram(): MiniProgramService
    {
        return new MiniProgramService($this->config, $this->tokenManager, $this->http, $this->corpKey, $this->appKey, $this->logger);
    }

    public function payment(): PaymentService
    {
        return new PaymentService($this->config, $this->tokenManager, $this->http, $this->corpKey, $this->appKey, $this->logger);
    }

    public function auth(): AuthService
    {
        return new AuthService($this->config, $this->tokenManager, $this->http, $this->corpKey, $this->appKey, $this->logger);
    }

    public function contacts(): ContactService
    {
        return new ContactService($this->config, $this->tokenManager, $this->http, $this->corpKey, $this->appKey, $this->logger);
    }

    public function callback(): CallbackCrypto
    {
        $corp = $this->config->getCorp($this->corpKey);
        $callback = $this->config->getCallback($this->corpKey);
        return new CallbackCrypto($callback["token"], $callback["encoding_aes_key"], $corp["corp_id"]);
    }

    public function callbackEvents(): CallbackService
    {
        return new CallbackService($this->callback());
    }

    public function getConfig(): array
    {
        return $this->config->getAll();
    }
}
