<?php

declare(strict_types=1);

namespace WorkWeXin;

use WorkWeXin\Auth\TokenManager;
use WorkWeXin\Cache\FileCache;
use WorkWeXin\Callback\CallbackCrypto;
use WorkWeXin\Config\Config;
use WorkWeXin\Exception\WeComException;
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

    public static function make(array $config, string $corpKey = "default", string $appKey = "default"): self
    {
        return new self($config, $corpKey, $appKey);
    }

    public static function fromApp(string $corpId, int $agentId, string $secret, array $options = []): self
    {
        return new self(array_merge($options, [
            "corp_id" => $corpId,
            "agent_id" => $agentId,
            "secret" => $secret
        ]));
    }

    public function __construct(array $config, string $corpKey = "default", string $appKey = "default")
    {
        $config = self::normalizeConfig($config, $corpKey, $appKey);
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

    public function sendText(string $content, $to): array
    {
        return $this->message()->sendText($content, $this->normalizeRecipients($to));
    }

    public function sendMarkdown(string $content, $to): array
    {
        return $this->message()->sendMarkdown($content, $this->normalizeRecipients($to));
    }

    public function sendTextCard(string $title, string $description, string $url, $to): array
    {
        return $this->message()->sendTextCard($title, $description, $url, $this->normalizeRecipients($to));
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

    private static function normalizeConfig(array $config, string $corpKey, string $appKey): array
    {
        if (isset($config["corps"])) {
            return $config;
        }

        $corpId = $config["corp_id"] ?? ($config["corpid"] ?? null);
        if ($corpId === null || $corpId === "") {
            return $config;
        }

        $app = [];
        if (array_key_exists("agent_id", $config)) {
            $app["agent_id"] = $config["agent_id"];
        } elseif (array_key_exists("agentid", $config)) {
            $app["agent_id"] = $config["agentid"];
        }
        if (array_key_exists("secret", $config)) {
            $app["secret"] = $config["secret"];
        }

        $corp = [
            "corp_id" => $corpId,
            "apps" => [
                $appKey => $app
            ]
        ];

        $callbackToken = $config["callback_token"] ?? null;
        $encodingAesKey = $config["encoding_aes_key"] ?? ($config["encodingAesKey"] ?? null);
        if ($callbackToken !== null || $encodingAesKey !== null) {
            $corp["callback"] = [
                "token" => (string) $callbackToken,
                "encoding_aes_key" => (string) $encodingAesKey
            ];
        }

        $normalized = [
            "corps" => [
                $corpKey => $corp
            ]
        ];

        $http = $config["http"] ?? [];
        foreach (["base_uri", "timeout", "client", "retry"] as $key) {
            if (array_key_exists($key, $config)) {
                $http[$key] = $config[$key];
            }
        }
        if ($http !== []) {
            $normalized["http"] = $http;
        }

        $cache = $config["cache"] ?? [];
        if (array_key_exists("cache_path", $config)) {
            $cache["path"] = $config["cache_path"];
        }
        if (array_key_exists("cache_ttl", $config)) {
            $cache["ttl"] = $config["cache_ttl"];
        }
        if (array_key_exists("cache_instance", $config)) {
            $cache["instance"] = $config["cache_instance"];
        }
        if ($cache !== []) {
            $normalized["cache"] = $cache;
        }

        if (array_key_exists("logger", $config)) {
            $normalized["logger"] = $config["logger"];
        }

        return $normalized;
    }

    private function normalizeRecipients($to): array
    {
        if (is_string($to)) {
            if ($to === "") {
                throw new WeComException(0, "recipient is required");
            }

            return ["touser" => $to];
        }

        if (!is_array($to)) {
            throw new WeComException(0, "recipient must be string or array");
        }

        if ($to === []) {
            return $to;
        }

        if (array_keys($to) === range(0, count($to) - 1)) {
            return ["touser" => implode("|", array_map("strval", $to))];
        }

        return $to;
    }
}
