<?php

declare(strict_types=1);

namespace WorkWeXin\Tests;

use PHPUnit\Framework\TestCase;
use WorkWeXin\WeCom;
use WorkWeXin\Exception\WeComException;
use WorkWeXin\Tests\Support\ArrayCache;
use WorkWeXin\Tests\Support\SpyHttpClient;

final class MessageServiceTest extends TestCase
{
    public function testSendInjectsAgentId(): void
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
                "client" => $httpClient,
                "base_uri" => "https://qyapi.weixin.qq.com"
            ]
        ];

        $wecom = new WeCom($config);
        $wecom->message()->send([
            "touser" => "userid1",
            "msgtype" => "text",
            "text" => ["content" => "Hello"]
        ]);

        $request = $httpClient->getLastRequest();
        $this->assertNotNull($request);
        $body = (string) $request->getBody();
        $payload = json_decode($body, true);

        $this->assertSame(1000002, $payload["agentid"] ?? null);
    }

    public function testSendRequiresMsgtype(): void
    {
        $this->expectException(WeComException::class);

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

        $wecom = new WeCom($config);
        $wecom->message()->send([
            "touser" => "userid1",
            "text" => ["content" => "Hello"]
        ]);
    }

    public function testSendTemplateCardRequiresCardType(): void
    {
        $this->expectException(WeComException::class);

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

        $wecom = new WeCom($config);
        $wecom->message()->sendTemplateCard([
            "main_title" => ["title" => "Title", "desc" => "Desc"]
        ], ["touser" => "userid1"]);
    }

    public function testUpdateTaskCardBuildsRequest(): void
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
                "client" => $httpClient,
                "base_uri" => "https://qyapi.weixin.qq.com"
            ]
        ];

        $wecom = new WeCom($config);
        $wecom->message()->updateTaskCard("userid1|userid2", "task_id_x", "done");

        $request = $httpClient->getLastRequest();
        $this->assertNotNull($request);
        $this->assertSame("/cgi-bin/message/update_taskcard", $request->getUri()->getPath());

        $payload = json_decode((string) $request->getBody(), true);
        $this->assertSame("userid1|userid2", $payload["userids"] ?? null);
        $this->assertSame("task_id_x", $payload["task_id"] ?? null);
        $this->assertSame("done", $payload["replace_name"] ?? null);
    }

    public function testMakeAcceptsFlatConfigAndShortcutSendsText(): void
    {
        $cache = new ArrayCache();
        $cache->set("access_token:default:default", "token");
        $httpClient = new SpyHttpClient();

        $wecom = WeCom::make([
            "corp_id" => "wx123",
            "agent_id" => 1000002,
            "secret" => "app_secret",
            "cache_instance" => $cache,
            "client" => $httpClient,
            "base_uri" => "https://qyapi.weixin.qq.com"
        ]);

        $wecom->sendText("Hello", "userid1");

        $request = $httpClient->getLastRequest();
        $this->assertNotNull($request);
        $this->assertSame("/cgi-bin/message/send", $request->getUri()->getPath());

        $payload = json_decode((string) $request->getBody(), true);
        $this->assertSame("userid1", $payload["touser"] ?? null);
        $this->assertSame("text", $payload["msgtype"] ?? null);
        $this->assertSame("Hello", $payload["text"]["content"] ?? null);
        $this->assertSame(1000002, $payload["agentid"] ?? null);
    }

    public function testShortcutAcceptsUserIdList(): void
    {
        $cache = new ArrayCache();
        $cache->set("access_token:default:default", "token");
        $httpClient = new SpyHttpClient();

        $wecom = WeCom::make([
            "corp_id" => "wx123",
            "agent_id" => 1000002,
            "secret" => "app_secret",
            "cache" => ["instance" => $cache],
            "http" => ["client" => $httpClient]
        ]);

        $wecom->sendMarkdown("**Hello**", ["userid1", "userid2"]);

        $payload = json_decode((string) $httpClient->getLastRequest()->getBody(), true);
        $this->assertSame("userid1|userid2", $payload["touser"] ?? null);
        $this->assertSame("markdown", $payload["msgtype"] ?? null);
        $this->assertSame("**Hello**", $payload["markdown"]["content"] ?? null);
    }

    public function testFromAppBuildsSimpleConfig(): void
    {
        $wecom = WeCom::fromApp("wx123", 1000002, "app_secret", [
            "cache_path" => sys_get_temp_dir(),
            "timeout" => 3.0
        ]);

        $config = $wecom->getConfig();
        $this->assertSame("wx123", $config["corps"]["default"]["corp_id"] ?? null);
        $this->assertSame(1000002, $config["corps"]["default"]["apps"]["default"]["agent_id"] ?? null);
        $this->assertSame("app_secret", $config["corps"]["default"]["apps"]["default"]["secret"] ?? null);
        $this->assertSame(sys_get_temp_dir(), $config["cache"]["path"] ?? null);
        $this->assertSame(3.0, $config["http"]["timeout"] ?? null);
    }
}
