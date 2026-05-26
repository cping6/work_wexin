<?php

declare(strict_types=1);

namespace WorkWeXin\Tests;

use PHPUnit\Framework\TestCase;
use WorkWeXin\Auth\TokenManager;
use WorkWeXin\Cache\FileCache;
use WorkWeXin\Config\Config;
use WorkWeXin\Http\HttpClient;
use WorkWeXin\Service\ApprovalService;
use WorkWeXin\Tests\Support\ArrayCache;
use WorkWeXin\Tests\Support\SpyHttpClient;
use WorkWeXin\WeCom;

final class ApprovalServiceTest extends TestCase
{
    public function testGetTemplateDetailRequiresId(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->getTemplateDetail("");
    }

    public function testGetApprovalDetailRequiresSpNo(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->getApprovalDetail("");
    }

    public function testCreateApprovalRequiresFields(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->createApproval(["creator_userid" => "u1"]);
    }

    public function testAddTemplateRequiresFields(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->addTemplate(["template_name" => "t1"]);
    }

    public function testUpdateTemplateRequiresFields(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->updateTemplate(["template_id" => "tid"]);
    }

    public function testDeleteTemplateRequiresId(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->deleteTemplate("");
    }

    public function testGetApprovalInfoRequiresFields(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->getApprovalInfo(["starttime" => 1]);
    }

    public function testGetApprovalInfoAdvancedBuildsRequest(): void
    {
        [$wecom, $httpClient] = $this->makeWeCom();

        $wecom->approval()->getApprovalInfoAdvanced(100, 200, [
            ["key" => "template_id", "value" => "tpl1"]
        ], "cursor1", 50);

        $request = $httpClient->getLastRequest();
        $this->assertNotNull($request);
        $this->assertSame("/cgi-bin/oa/approval/getapprovalinfo", $request->getUri()->getPath());
        $this->assertSame("token", $this->getQueryValue((string) $request->getUri()->getQuery(), "access_token"));

        $payload = json_decode((string) $request->getBody(), true);
        $this->assertSame(100, $payload["starttime"] ?? null);
        $this->assertSame(200, $payload["endtime"] ?? null);
        $this->assertSame(50, $payload["size"] ?? null);
        $this->assertSame("cursor1", $payload["cursor"] ?? null);
        $this->assertSame("template_id", $payload["filters"][0]["key"] ?? null);
        $this->assertSame("tpl1", $payload["filters"][0]["value"] ?? null);
    }

    public function testGetApprovalInfoAdvancedRequiresRange(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->getApprovalInfoAdvanced(10, 0);
    }

    public function testGetApprovalInfoAdvancedRequiresSize(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->getApprovalInfoAdvanced(10, 20, [], null, 0);
    }

    public function testBuildApprovalFiltersSkipsEmpty(): void
    {
        $service = $this->makeService();

        $filters = $service->buildApprovalFilters([
            "template_id" => "t1",
            "creator" => "",
            "status" => null
        ]);

        $this->assertCount(1, $filters);
        $this->assertSame("template_id", $filters[0]["key"]);
        $this->assertSame("t1", $filters[0]["value"]);
    }

    public function testEnableTemplateRequiresFields(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->enableTemplate("", []);
    }

    public function testDisableTemplateRequiresFields(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->disableTemplate("", []);
    }

    private function makeService(): ApprovalService
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

        return new ApprovalService($config, $tokenManager, $http, "default", "default");
    }

    private function makeWeCom(): array
    {
        $cache = new ArrayCache();
        $cache->set("access_token:default:default", "token");
        $httpClient = new SpyHttpClient();

        $wecom = new WeCom([
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
        ]);

        return [$wecom, $httpClient];
    }

    private function getQueryValue(string $query, string $key): ?string
    {
        parse_str($query, $params);
        return isset($params[$key]) ? (string) $params[$key] : null;
    }
}
