<?php

declare(strict_types=1);

namespace WorkWeXin\Config;

use WorkWeXin\Exception\WeComException;

final class Config
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function getCorp(string $corpKey): array
    {
        $corps = $this->config["corps"] ?? [];
        if (!isset($corps[$corpKey])) {
            throw new WeComException(0, "Corp config not found: " . $corpKey);
        }

        $corp = $corps[$corpKey];
        if (empty($corp["corp_id"])) {
            throw new WeComException(0, "corp_id is required for corp: " . $corpKey);
        }

        return $corp;
    }

    public function getApp(string $corpKey, string $appKey): array
    {
        $corp = $this->getCorp($corpKey);
        $apps = $corp["apps"] ?? [];
        if (!isset($apps[$appKey])) {
            throw new WeComException(0, "App config not found: " . $corpKey . "." . $appKey);
        }

        $app = $apps[$appKey];
        if (empty($app["secret"])) {
            throw new WeComException(0, "secret is required for app: " . $corpKey . "." . $appKey);
        }

        return $app;
    }

    public function getHttp(): array
    {
        return $this->config["http"] ?? [];
    }

    public function getCache(): array
    {
        return $this->config["cache"] ?? [];
    }

    public function getCallback(string $corpKey): array
    {
        $corp = $this->getCorp($corpKey);
        $callback = $corp["callback"] ?? ($this->config["callback"] ?? []);
        if (empty($callback["token"]) || empty($callback["encoding_aes_key"])) {
            throw new WeComException(0, "callback token or encoding_aes_key is required for corp: " . $corpKey);
        }

        return $callback;
    }

    public function getAll(): array
    {
        return $this->config;
    }
}
