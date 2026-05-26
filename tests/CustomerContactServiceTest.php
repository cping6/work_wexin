<?php

declare(strict_types=1);

namespace WorkWeXin\Tests;

use PHPUnit\Framework\TestCase;
use WorkWeXin\Auth\TokenManager;
use WorkWeXin\Cache\FileCache;
use WorkWeXin\Config\Config;
use WorkWeXin\Http\HttpClient;
use WorkWeXin\Service\CustomerContactService;

final class CustomerContactServiceTest extends TestCase
{
    public function testListExternalContactsRequiresUserId(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->listExternalContacts("");
    }

    public function testGetExternalContactRequiresExternalUserId(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->getExternalContact("");
    }

    public function testBatchGetByUserRequiresUserId(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->batchGetByUser("");
    }

    public function testBatchGetByUserRequiresLimit(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->batchGetByUser("userid1", null, 0);
    }

    public function testListGroupChatsRequiresLimit(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->listGroupChats(0, [], null, 0);
    }

    public function testGetGroupChatRequiresChatId(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->getGroupChat("");
    }

    public function testUpdateRemarkRequiresFields(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->updateRemark(["userid" => "u1"]);
    }

    public function testAddContactWayRequiresFields(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->addContactWay(["type" => 1]);
    }

    public function testGetContactWayRequiresConfigId(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->getContactWay("");
    }

    public function testUpdateContactWayRequiresConfigId(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->updateContactWay([]);
    }

    public function testDeleteContactWayRequiresConfigId(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->deleteContactWay("");
    }

    public function testCloseTempChatRequiresUserId(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->closeTempChat("", "ext1");
    }

    public function testSendWelcomeRequiresCode(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->sendWelcome([]);
    }

    public function testAddCorpTagRequiresFields(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->addCorpTag(["group_name" => "g1"]);
    }

    public function testEditCorpTagRequiresId(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->editCorpTag([]);
    }

    public function testDeleteCorpTagRequiresIds(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->deleteCorpTag();
    }

    public function testMarkTagRequiresFields(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->markTag(["userid" => "u1", "external_userid" => "ext1"]);
    }

    public function testGetUnassignedListRequiresPageSize(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->getUnassignedList(0, 0);
    }

    public function testTransferCustomerRequiresFields(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->transferCustomer(["handover_userid" => "u1"]);
    }

    public function testTransferGroupChatRequiresFields(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->transferGroupChat(["chat_id_list" => ["c1"]]);
    }

    public function testGroupChatStatisticRequiresRange(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->getGroupChatStatistic(10, 0);
    }

    public function testGetUserBehaviorDataRequiresRange(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->getUserBehaviorData(10, 0, ["u1"]);
    }

    public function testGetUserBehaviorDataRequiresUserList(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->getUserBehaviorData(10, 20, []);
    }

    public function testAddGroupWelcomeTemplateRequiresChatId(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->addGroupWelcomeTemplate([]);
    }

    public function testEditGroupWelcomeTemplateRequiresTemplateId(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->editGroupWelcomeTemplate([]);
    }

    public function testGroupWelcomeTemplateRequiresId(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->getGroupWelcomeTemplate("");
    }

    public function testDeleteGroupWelcomeTemplateRequiresId(): void
    {
        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);
        $service->deleteGroupWelcomeTemplate("");
    }

    private function makeService(): CustomerContactService
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

        return new CustomerContactService($config, $tokenManager, $http, "default", "default");
    }
}
