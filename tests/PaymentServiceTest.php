<?php

declare(strict_types=1);

namespace WorkWeXin\Tests;

use PHPUnit\Framework\TestCase;
use WorkWeXin\Auth\TokenManager;
use WorkWeXin\Cache\FileCache;
use WorkWeXin\Config\Config;
use WorkWeXin\Http\HttpClient;
use WorkWeXin\Service\PaymentService;

final class PaymentServiceTest extends TestCase
{
    public function testSignBuildsHmac(): void
    {
        $service = $this->makeService();
        $sign = $service->sign(["a" => "1", "b" => "2"], "key", "HMAC-SHA256");

        $this->assertSame(strtoupper(hash_hmac("sha256", "a=1&b=2&key=key", "key")), $sign);
    }

    public function testVerifySignature(): void
    {
        $service = $this->makeService();
        $data = ["a" => "1", "b" => "2", "sign_type" => "HMAC-SHA256"];
        $data["sign"] = $service->sign($data, "key");

        $this->assertTrue($service->verifySignature($data, "key"));
    }

    private function makeService(): PaymentService
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

        return new PaymentService($config, $tokenManager, $http, "default", "default");
    }
}
