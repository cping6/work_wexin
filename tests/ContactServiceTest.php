<?php

declare(strict_types=1);

namespace WorkWeXin\Tests;

use PHPUnit\Framework\TestCase;
use WorkWeXin\WeCom;
use WorkWeXin\Tests\Support\ArrayCache;
use WorkWeXin\Tests\Support\SpyHttpClient;

final class ContactServiceTest extends TestCase
{
    public function testCreateUserRequiresDepartment(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $wecom = $this->buildWeCom();
        $wecom->contacts()->createUser([
            "userid" => "u1",
            "name" => "User"
        ]);
    }

    public function testBatchDeleteRequiresUserIds(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $wecom = $this->buildWeCom();
        $wecom->contacts()->batchDeleteUsers([]);
    }

    public function testAddTagUsersRequiresList(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $wecom = $this->buildWeCom();
        $wecom->contacts()->addTagUsers(1, [], []);
    }

    private function buildWeCom(): WeCom
    {
        $cache = new ArrayCache();
        $cache->set("access_token:default:default", "token");

        $httpClient = new SpyHttpClient();

        $config = [
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
            ],
            "cache" => [
                "instance" => $cache
            ],
            "http" => [
                "client" => $httpClient
            ]
        ];

        return new WeCom($config);
    }
}
