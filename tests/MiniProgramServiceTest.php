<?php

declare(strict_types=1);

namespace WorkWeXin\Tests;

use PHPUnit\Framework\TestCase;
use WorkWeXin\Auth\TokenManager;
use WorkWeXin\Cache\FileCache;
use WorkWeXin\Config\Config;
use WorkWeXin\Http\HttpClient;
use WorkWeXin\Service\MiniProgramService;

final class MiniProgramServiceTest extends TestCase
{
    public function testGetSessionRequiresCode(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->getSession("");
    }

    public function testBuildJsapiSignatureRequiresFields(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->buildJsapiSignature("", "nonce", 1, "https://example.com");
    }

    public function testCreateJsapiConfig(): void
    {
        $service = $this->makeService();
        $config = $service->createJsapiConfig("https://example.com", "ticket", "nonce", 123);

        $this->assertSame("nonce", $config["nonceStr"]);
        $this->assertSame(123, $config["timestamp"]);
        $this->assertIsString($config["signature"]);
    }

    private function makeService(): MiniProgramService
    {
        $config = new Config([
            "corps" => [
                "default" => [
                    "corp_id" => "wx123",
                    "apps" => [
                        "default" => [
                            "agent_id" => 1000002,
                            "secret" => "app_secret"
                        ]
                    ]
                ]
            ]
        ]);
        $http = new HttpClient([]);
        $cache = new FileCache(sys_get_temp_dir());
        $tokenManager = new TokenManager($config, $http, $cache);

        return new MiniProgramService($config, $tokenManager, $http, "default", "default");
    }
}
