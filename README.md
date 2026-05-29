# WorkWeXin WeCom SDK

面向企业微信的 PHP SDK（当前包含：应用消息 + 通讯录 + 身份与登录 + 回调事件 + 客户联系 + OA/审批 + 小程序 + 支付）。

## 安装

```bash
composer require workwexin/wecom
```

## 快速开始

SDK 推荐按 service 模块调用：先创建 `WeCom` 实例，再进入对应 service 调用方法。

```php
use WorkWeXin\WeCom;

$wecom = WeCom::make([
    "corp_id" => "wx123",
    "agent_id" => 1000002,
    "secret" => "app_secret"
]);

$wecom->message()->sendText("你好", ["touser" => "userid1"]);
$wecom->contacts()->getUser("userid1");
$wecom->approval()->getTemplateDetail("template_id");
```

如果只接入一个企业应用，也可以用三参数工厂方法：

```php
$wecom = WeCom::fromApp("wx123", 1000002, "app_secret");

$wecom->message()->sendMarkdown("**加粗**内容", ["touser" => "userid1|userid2"]);
```

当前可用 service：

| service | 说明 |
| --- | --- |
| `$wecom->message()` | 应用消息 |
| `$wecom->contacts()` | 通讯录 |
| `$wecom->auth()` | 身份与登录 |
| `$wecom->callback()` | 回调加解密 |
| `$wecom->callbackEvents()` | 回调事件分发 |
| `$wecom->customerContact()` | 客户联系 |
| `$wecom->approval()` | 审批 |
| `$wecom->oa()` | OA：打卡、日程、会议室、待办等 |
| `$wecom->miniProgram()` | 小程序 |
| `$wecom->payment()` | 支付 |

应用消息提供少量顶层快捷方法，适合简单发送场景：

```php
$wecom->sendText("你好", "userid1");
$wecom->sendMarkdown("**加粗**内容", ["userid1", "userid2"]);
$wecom->sendTextCard("标题", "描述", "https://example.com", "userid1");
```

快捷发送方法中，收件人可以传字符串、用户列表或完整收件人数组。

## 配置说明

完整多企业、多应用配置仍然支持：

```php
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
        "path" => __DIR__ . "/runtime/cache",
        "ttl" => 7200
    ],
    "http" => [
        "timeout" => 5.0,
        "base_uri" => "https://qyapi.weixin.qq.com"
    ]
];
```

字段说明：
- `corps`：多企业配置，key 为企业别名
- `corps.{corp}.corp_id`：企业 ID
- `corps.{corp}.apps`：企业下的多个应用
- `corps.{corp}.apps.{app}.agent_id`：应用 AgentId
- `corps.{corp}.apps.{app}.secret`：应用 Secret
- `cache.path`：文件缓存目录（access_token 缓存）
- `cache.ttl`：默认缓存时间（秒）
- `cache.instance`：自定义缓存（需实现 `Psr\SimpleCache\CacheInterface`）
- `http.timeout`：HTTP 超时时间
- `http.base_uri`：企业微信 API 根地址
- `http.client`：自定义 HTTP 客户端（需实现 `Psr\Http\Client\ClientInterface`）
- `http.retry.max_retries`：最大重试次数（默认 0）
- `http.retry.retry_delay_ms`：重试间隔毫秒（默认 200）
- `http.retry.retry_on_status`：重试的 HTTP 状态码（默认 500/502/503/504）
- `http.retry.retry_on_timeout`：是否对超时重试（默认 true）
- `http.retry.methods`：允许重试的 HTTP 方法（默认 `["GET"]`）
- `logger`：自定义日志（需实现 `Psr\Log\LoggerInterface`）
- `corps.{corp}.callback.token`：回调 Token
- `corps.{corp}.callback.encoding_aes_key`：回调 EncodingAESKey

说明：`new WeCom($config, "default", "default")` 和 `WeCom::make($config)` 等价；`agentid` 如果未传，会从配置里的 `agent_id` 自动注入。

## Token 与签名说明

- 普通 service 接口调用统一使用 `access_token`。SDK 会通过 `TokenManager` 自动获取、缓存，并在请求中附带 `access_token`。
- 回调验签与加解密使用企业微信后台配置的 `callback.token` 和 `callback.encoding_aes_key`，不使用接口调用的 `access_token`。
- 支付签名使用支付 API key，通过 `payment()->sign()` 和 `payment()->verifySignature()` 处理，和 `access_token`、回调 Token 都不是同一个凭证。

## 应用消息示例

```php
$wecom->message()->sendText("你好", ["touser" => "userid1"]);
$wecom->message()->sendMarkdown("**加粗**内容", ["touser" => "userid1"]);
$wecom->message()->sendTextCard("标题", "描述", "https://example.com", ["touser" => "userid1"]);
```

任务卡片更新示例：

```php
$wecom->message()->updateTaskCard("userid1|userid2", "task_id_xxx", "已完成");
```

## 通讯录 API

```php
$wecom->contacts()->getUser("userid1");
$wecom->contacts()->listUsers(1, true);
$wecom->contacts()->createUser([
    "userid" => "userid1",
    "name" => "张三",
    "department" => [1]
]);
$wecom->contacts()->updateUser([
    "userid" => "userid1",
    "name" => "李四"
]);
$wecom->contacts()->deleteUser("userid1");

$wecom->contacts()->listDepartments();
$wecom->contacts()->createDepartment([
    "name" => "研发部",
    "parentid" => 1
]);
$wecom->contacts()->updateDepartment([
    "id" => 2,
    "name" => "技术部"
]);
$wecom->contacts()->deleteDepartment(2);
```

## 可用方法与字段说明

说明：以下为当前 SDK 可用方法（MVP：应用消息 + 通讯录）。


小程序：

| 方法 | 说明 | 主要字段（必填/可选） |
| --- | --- | --- |
| `miniProgram()->getSession(string $jsCode): array` | 小程序登录（jscode2session） | 必填：`jsCode` |
| `miniProgram()->getCode(array $data): array` | 获取小程序码 | 必填：`path` |
| `miniProgram()->getQrCode(array $data): array` | 获取小程序二维码 | 必填：`path` |
| `miniProgram()->getJsapiTicket(string $type = "agent_config"): array` | 获取 JSAPI ticket | 可选：`type` |
| `miniProgram()->createJsapiConfig(string $url, string $ticket, ?string $nonceStr = null, ?int $timestamp = null): array` | 生成 JSAPI 签名配置 | 必填：`url`、`ticket` |

支付：

| 方法 | 说明 | 主要字段（必填/可选） |
| --- | --- | --- |
| `payment()->unifiedOrder(array $data): array` | 统一下单 | 必填：`data` |
| `payment()->queryOrder(array $data): array` | 订单查询 | 必填：`data` |
| `payment()->refund(array $data): array` | 退款 | 必填：`data` |
| `payment()->transferToUser(array $data): array` | 企业付款到个人 | 必填：`data` |
| `payment()->sendRedPack(array $data): array` | 企业红包 | 必填：`data` |
| `payment()->downloadBill(array $query): string` | 下载账单 | 必填：`query` |
| `payment()->sign(array $data, string $apiKey, string $signType = "HMAC-SHA256"): string` | 支付签名 | 必填：`data`、`apiKey` |
| `payment()->verifySignature(array $data, string $apiKey, string $signField = "sign", string $signTypeField = "sign_type"): bool` | 校验支付签名 | 必填：`data`、`apiKey` |


客户联系：

| 方法 | 说明 | 主要字段（必填/可选） |
| --- | --- | --- |
| `customerContact()->listFollowUsers(): array` | 获取配置了客户联系功能的成员列表 | - |
| `customerContact()->listExternalContacts(string $userId): array` | 获取客户列表 | 必填：`userId` |
| `customerContact()->getExternalContact(string $externalUserId, ?string $cursor = null): array` | 获取客户详情 | 必填：`externalUserId`。可选：`cursor` |
| `customerContact()->batchGetByUser(string $userId, ?string $cursor = null, int $limit = 100): array` | 批量获取客户详情 | 必填：`userId`、`limit`。可选：`cursor` |
| `customerContact()->listGroupChats(int $statusFilter = 0, array $ownerFilter = [], ?string $cursor = null, int $limit = 1000): array` | 获取客户群列表 | 可选：`statusFilter`、`ownerFilter`、`cursor`、`limit` |
| `customerContact()->getGroupChat(string $chatId, int $needName = 1): array` | 获取客户群详情 | 必填：`chatId`。可选：`needName` |
| `customerContact()->updateRemark(array $data): array` | 更新客户备注 | 必填：`userid`、`external_userid` |
| `customerContact()->addContactWay(array $data): array` | 添加联系我 | 必填：`type`、`scene` |
| `customerContact()->getContactWay(string $configId): array` | 获取联系我配置 | 必填：`configId` |
| `customerContact()->updateContactWay(array $data): array` | 更新联系我配置 | 必填：`config_id` |
| `customerContact()->deleteContactWay(string $configId): array` | 删除联系我配置 | 必填：`configId` |
| `customerContact()->closeTempChat(string $userId, string $externalUserId): array` | 结束临时会话 | 必填：`userId`、`externalUserId` |
| `customerContact()->sendWelcome(array $data): array` | 发送新客户欢迎语 | 必填：`welcome_code` |
| `customerContact()->getCorpTagList(array $tagIds = [], array $groupIds = []): array` | 获取客户标签 | 可选：`tagIds`、`groupIds` |
| `customerContact()->addCorpTag(array $data): array` | 添加客户标签 | 必填：`group_name`、`tag` |
| `customerContact()->editCorpTag(array $data): array` | 更新客户标签 | 必填：`id` |
| `customerContact()->deleteCorpTag(array $tagIds = [], array $groupIds = []): array` | 删除客户标签 | 必填：`tagIds`/`groupIds` |
| `customerContact()->markTag(array $data): array` | 为客户打标签 | 必填：`userid`、`external_userid`，`add_tag`/`remove_tag` 至少一项 |
| `customerContact()->getUnassignedList(int $pageId = 0, int $pageSize = 1000): array` | 获取待分配客户 | 必填：`pageSize` |
| `customerContact()->transferCustomer(array $data): array` | 分配客户给新成员 | 必填：`handover_userid`、`takeover_userid`、`external_userid` |
| `customerContact()->transferGroupChat(array $data): array` | 分配客户群 | 必填：`chat_id_list`、`new_owner` |
| `customerContact()->getGroupChatStatistic(int $startTime, int $endTime, array $ownerFilter = [], ?string $cursor = null, int $limit = 1000): array` | 获取客户群统计 | 必填：`startTime`、`endTime`、`limit` |
| `customerContact()->getUserBehaviorData(int $startTime, int $endTime, array $userIdList): array` | 获取客户联系统计 | 必填：`startTime`、`endTime`、`userIdList` |
| `customerContact()->getGroupChatMembers(string $chatId, int $needName = 1): array` | 获取客户群成员详情 | 必填：`chatId`。可选：`needName` |
| `customerContact()->addGroupWelcomeTemplate(array $data): array` | 添加客户群欢迎语 | 必填：`chat_id` |
| `customerContact()->editGroupWelcomeTemplate(array $data): array` | 更新客户群欢迎语 | 必填：`template_id` |
| `customerContact()->getGroupWelcomeTemplate(string $templateId): array` | 获取客户群欢迎语 | 必填：`templateId` |
| `customerContact()->deleteGroupWelcomeTemplate(string $templateId): array` | 删除客户群欢迎语 | 必填：`templateId` |

OA 与审批：

| 方法 | 说明 | 主要字段（必填/可选） |
| --- | --- | --- |
| `approval()->listTemplates(array $query = []): array` | 获取审批模板列表 | 可选：`query` |
| `approval()->getTemplateDetail(string $templateId): array` | 获取审批模板详情 | 必填：`templateId` |
| `approval()->getApprovalDetail(string $spNo): array` | 获取审批详情 | 必填：`spNo` |
| `approval()->createApproval(array $data): array` | 发起审批 | 必填：`creator_userid`、`template_id`、`apply_data` |
| `oa()->getCheckinData(int $startTime, int $endTime, array $userIdList): array` | 获取打卡数据 | 必填：`startTime`、`endTime`、`userIdList` |
| `oa()->getCheckinDayData(int $startTime, int $endTime, array $userIdList): array` | 获取打卡日报数据 | 必填：`startTime`、`endTime`、`userIdList` |
| `oa()->getCheckinOption(int $datetime, array $userIdList): array` | 获取打卡规则 | 必填：`datetime`、`userIdList` |
| `oa()->getJournalRecordList(int $startTime, int $endTime, array $userIdList): array` | 获取日报列表 | 必填：`startTime`、`endTime`、`userIdList` |
| `oa()->getJournalRecordDetail(string $journalUuid): array` | 获取日报详情 | 必填：`journalUuid` |
| `approval()->addTemplate(array $data): array` | 添加审批模板 | 必填：`template_name`、`template_content` |
| `approval()->updateTemplate(array $data): array` | 更新审批模板 | 必填：`template_id`、`template_content` |
| `approval()->deleteTemplate(string $templateId): array` | 删除审批模板 | 必填：`templateId` |
| `approval()->getApprovalInfo(array $data): array` | 批量获取审批单号 | 必填：`starttime`、`endtime` |
| `approval()->getApprovalInfoAdvanced(int $startTime, int $endTime, array $filters = [], ?string $cursor = null, int $size = 100): array` | 按分页和筛选获取审批单号 | 必填：`startTime`、`endTime`、`size` |
| `approval()->buildApprovalFilters(array $conditions): array` | 构造审批筛选条件 | 必填：`conditions` |
| `approval()->getApprovalInfoByConditions(int $startTime, int $endTime, array $conditions, ?string $cursor = null, int $size = 100): array` | 按条件获取审批单号 | 必填：`startTime`、`endTime`、`conditions`、`size` |
| `approval()->enableTemplate(string $templateId, array $templateContent): array` | 启用审批模板 | 必填：`templateId`、`templateContent` |
| `approval()->disableTemplate(string $templateId, array $templateContent): array` | 停用审批模板 | 必填：`templateId`、`templateContent` |
| `oa()->addSchedule(array $data): array` | 新增日程 | 必填：`schedule` |
| `oa()->updateSchedule(array $data): array` | 更新日程 | 必填：`schedule_id`、`schedule` |
| `oa()->deleteSchedule(string $scheduleId): array` | 删除日程 | 必填：`scheduleId` |
| `oa()->getSchedule(string $scheduleId): array` | 获取日程详情 | 必填：`scheduleId` |
| `oa()->getScheduleByDate(int $startTime, int $endTime, array $userIdList): array` | 按日期获取日程 | 必填：`startTime`、`endTime`、`userIdList` |
| `oa()->shareSchedule(array $data): array` | 分享日程 | 必填：`schedule_id`、`share_to` |
| `oa()->shareScheduleWithPermission(string $scheduleId, array $shareTo, int $permission): array` | 按权限分享日程 | 必填：`scheduleId`、`shareTo`、`permission` |
| `oa()->cancelShareSchedule(array $data): array` | 取消日程分享 | 必填：`schedule_id`、`share_to` |
| `oa()->setScheduleReminder(string $scheduleId, int $remindTime, ?int $remindType = null): array` | 设置日程提醒 | 必填：`scheduleId`、`remindTime` |
| `oa()->setScheduleAttachments(string $scheduleId, array $attachments): array` | 设置日程附件 | 必填：`scheduleId`、`attachments` |
| `oa()->addScheduleAttendees(array $data): array` | 添加日程参与人 | 必填：`schedule_id`、`attendees` |
| `oa()->deleteScheduleAttendees(array $data): array` | 删除日程参与人 | 必填：`schedule_id`、`attendees` |
| `oa()->listMeetingRooms(array $query = []): array` | 获取会议室列表 | 可选：`query` |
| `oa()->listMeetingRoomsByEquipment(array $equipmentIds, array $query = []): array` | 按设备筛选会议室 | 必填：`equipmentIds` |
| `oa()->getMeetingRoom(string $meetingroomId): array` | 获取会议室详情 | 必填：`meetingroomId` |
| `oa()->getMeetingRoomEquipment(string $meetingroomId): array` | 获取会议室设备 | 必填：`meetingroomId` |
| `oa()->getMeetingRoomLocation(string $meetingroomId): array` | 获取会议室位置 | 必填：`meetingroomId` |
| `oa()->addMeetingRoom(array $data): array` | 添加会议室 | 必填：`name`、`capacity` |
| `oa()->updateMeetingRoom(array $data): array` | 更新会议室 | 必填：`meetingroom_id` |
| `oa()->deleteMeetingRoom(string $meetingroomId): array` | 删除会议室 | 必填：`meetingroomId` |
| `oa()->bookMeetingRoom(array $data): array` | 预定会议室 | 必填：`meetingroom_id`、`start_time`、`end_time` |
| `oa()->cancelMeetingRoom(string $bookingId): array` | 取消会议室预定 | 必填：`bookingId` |
| `oa()->getMeetingRoomBooking(string $bookingId): array` | 获取会议室预定详情 | 必填：`bookingId` |
| `oa()->getMeetingRoomAvailability(array $data): array` | 查询会议室可用情况 | 必填：`meetingroom_id`、`start_time`、`end_time` |
| `oa()->addTodo(array $data): array` | 新增待办 | 必填：`data` |
| `oa()->batchAddTodo(array $todos): array` | 批量新增待办 | 必填：`todos` |
| `oa()->batchUpdateTodo(array $todos): array` | 批量更新待办 | 必填：`todos` |
| `oa()->batchDeleteTodo(array $todoIds): array` | 批量删除待办 | 必填：`todoIds` |
| `oa()->markTodo(array $data): array` | 标记待办状态 | 必填：`todo_id`、`status` |
| `oa()->setTodoReminder(string $todoId, int $remindTime): array` | 设置待办提醒 | 必填：`todoId`、`remindTime` |
| `oa()->setTodoCc(string $todoId, array $ccUserIds): array` | 设置待办抄送人 | 必填：`todoId`、`ccUserIds` |
| `oa()->updateTodo(array $data): array` | 更新待办 | 必填：`todo_id` |
| `oa()->deleteTodo(string $todoId): array` | 删除待办 | 必填：`todoId` |
| `oa()->getTodo(string $todoId): array` | 获取待办详情 | 必填：`todoId` |
| `oa()->listTodo(array $query = []): array` | 获取待办列表 | 可选：`query` |


身份与登录：

| 方法 | 说明 | 主要字段（必填/可选） |
| --- | --- | --- |
| `auth()->getAuthorizeUrl(string $redirectUri, string $state = "STATE", string $scope = "snsapi_base", ?string $agentId = null): string` | 生成网页授权 URL | 必填：`redirectUri`。可选：`state`、`scope`（`snsapi_base`/`snsapi_userinfo`）、`agentId` |
| `auth()->getQrConnectUrl(string $redirectUri, string $state = "STATE", ?string $agentId = null): string` | 生成扫码登录 URL | 必填：`redirectUri`。可选：`state`、`agentId` |
| `auth()->getUserInfo(string $code): array` | 通过 code 获取用户身份 | 必填：`code` |
| `auth()->getUserDetail(string $userTicket): array` | 通过 user_ticket 获取用户详情 | 必填：`userTicket` |

应用消息：

| 方法 | 说明 | 主要字段（必填/可选） |
| --- | --- | --- |
| `message()->send(array $payload): array` | 发送应用消息 | 必填：`msgtype`、`agentid`（可省略自动注入）、`touser`/`toparty`/`totag` 至少一项。可选：`safe`、`enable_duplicate_check`、`duplicate_check_interval`。按消息类型补充字段见下表。 |
| `message()->sendText(string $content, array $to = []): array` | 发送文本 | 必填：`content`。可选：`to` 内的 `touser`/`toparty`/`totag` |
| `message()->sendImage(string $mediaId, array $to = []): array` | 发送图片 | 必填：`mediaId`。可选：`to` 内的 `touser`/`toparty`/`totag` |
| `message()->sendVoice(string $mediaId, array $to = []): array` | 发送语音 | 必填：`mediaId`。可选：`to` 内的 `touser`/`toparty`/`totag` |
| `message()->sendVideo(string $mediaId, string $title, string $description, array $to = []): array` | 发送视频 | 必填：`mediaId`、`title`、`description`。可选：`to` 内的 `touser`/`toparty`/`totag` |
| `message()->sendFile(string $mediaId, array $to = []): array` | 发送文件 | 必填：`mediaId`。可选：`to` 内的 `touser`/`toparty`/`totag` |
| `message()->sendMarkdown(string $content, array $to = []): array` | 发送 Markdown | 必填：`content`。可选：`to` 内的 `touser`/`toparty`/`totag` |
| `message()->sendNews(array $articles, array $to = []): array` | 发送图文 | 必填：`articles`。可选：`to` 内的 `touser`/`toparty`/`totag` |
| `message()->sendMpNews(array $articles, array $to = []): array` | 发送图文（mpnews） | 必填：`articles`。可选：`to` 内的 `touser`/`toparty`/`totag` |
| `message()->sendTextCard(string $title, string $description, string $url, array $to = []): array` | 发送文本卡片 | 必填：`title`、`description`、`url`。可选：`to` 内的 `touser`/`toparty`/`totag` |
| `message()->sendTaskCard(string $title, string $description, string $url, string $taskId, array $btn, array $to = []): array` | 发送任务卡片 | 必填：`title`、`description`、`url`、`taskId`、`btn`。可选：`to` 内的 `touser`/`toparty`/`totag` |
| `message()->sendTemplateCard(array $card, array $to = []): array` | 发送模板卡片 | 必填：`card`（含 `card_type`）。可选：`to` 内的 `touser`/`toparty`/`totag` |
| `message()->updateTaskCard(string $userIds, string $taskId, string $replaceName): array` | 更新任务卡片 | 必填：`userIds`、`taskId`、`replaceName` |

消息类型字段说明：

| msgtype | 必填字段 | 说明 |
| --- | --- | --- |
| `text` | `text.content` | 文本内容 |
| `image` | `image.media_id` | 图片素材 media_id |
| `voice` | `voice.media_id` | 语音素材 media_id |
| `video` | `video.media_id`、`video.title`、`video.description` | 视频素材与标题描述 |
| `file` | `file.media_id` | 文件素材 media_id |
| `markdown` | `markdown.content` | Markdown 内容 |
| `news` | `news.articles` | 图文数组，元素需含 `title`、`description`、`url`、`picurl` |
| `mpnews` | `mpnews.articles` | 图文数组，元素需含 `title`、`thumb_media_id`、`content` |
| `textcard` | `textcard.title`、`textcard.description`、`textcard.url` | 文本卡片 |
| `taskcard` | `taskcard.title`、`taskcard.description`、`taskcard.url`、`taskcard.task_id`、`taskcard.btn` | 任务卡片 |
| `template_card` | `template_card.card_type` | 模板卡片（结构按官方卡片类型） |

模板卡片字段说明：

通用字段：

| 字段 | 说明 | 备注 |
| --- | --- | --- |
| `template_card.card_type` | 卡片类型 | 必填，`text_notice`/`news_notice`/`button_interaction`/`vote_interaction`/`multiple_interaction` |
| `template_card.source` | 来源信息对象 | 可选，见下表 |
| `template_card.main_title` | 主标题对象 | 多数卡片必填，见下表 |
| `template_card.card_action` | 卡片整体跳转 | 多数卡片必填，见下表 |
| `template_card.quote_area` | 引用区域对象 | 可选，见下表 |
| `template_card.emphasis_content` | 强调内容对象 | 可选，见下表 |
| `template_card.sub_title_text` | 副标题文本 | 可选 |
| `template_card.horizontal_content_list` | 横向内容列表 | 可选，元素结构见下表 |
| `template_card.jump_list` | 跳转列表 | 可选，元素结构见下表 |
| `template_card.card_image` | 图片对象 | 可选，见下表 |
| `template_card.button_selection` | 选择按钮对象 | 交互卡片可用，见下表 |
| `template_card.button_list` | 按钮数组 | 交互卡片可用，元素结构见下表 |
| `template_card.submit_button` | 提交按钮对象 | 交互卡片可用，见下表 |

对象字段：

| 对象 | 字段 | 说明 |
| --- | --- | --- |
| `source` | `icon_url` | 来源图标 URL |
| `source` | `desc` | 来源描述 |
| `source` | `desc_color` | 描述颜色 |
| `main_title` | `title` | 主标题 |
| `main_title` | `desc` | 主标题描述 |
| `quote_area` | `type` | 引用类型 |
| `quote_area` | `url` | 引用跳转 URL |
| `quote_area` | `title` | 引用标题 |
| `quote_area` | `quote_text` | 引用文本 |
| `quote_area` | `image_url` | 引用图片 URL |
| `emphasis_content` | `title` | 强调标题 |
| `emphasis_content` | `desc` | 强调描述 |
| `horizontal_content_list` | `keyname` | 名称 |
| `horizontal_content_list` | `value` | 值 |
| `horizontal_content_list` | `type` | 类型 |
| `horizontal_content_list` | `url` | 跳转 URL |
| `horizontal_content_list` | `media_id` | 素材 ID |
| `jump_list` | `type` | 跳转类型 |
| `jump_list` | `title` | 文案 |
| `jump_list` | `url` | 跳转 URL |
| `jump_list` | `appid` | 小程序 appid |
| `jump_list` | `pagepath` | 小程序路径 |
| `card_image` | `url` | 图片 URL |
| `card_image` | `aspect_ratio` | 图片比例 |
| `card_action` | `type` | 跳转类型 |
| `card_action` | `url` | 跳转 URL |
| `card_action` | `appid` | 小程序 appid |
| `card_action` | `pagepath` | 小程序路径 |
| `button_selection` | `question_key` | 选择题 key |
| `button_selection` | `title` | 选择题标题 |
| `button_selection` | `option_list` | 选项数组（含 `id`、`text`） |
| `button_selection` | `selected_id` | 已选项 ID |
| `button_list` | `text` | 按钮文案 |
| `button_list` | `style` | 按钮样式 |
| `button_list` | `key` | 按钮 key |
| `button_list` | `url` | 按钮跳转 URL |
| `button_list` | `appid` | 小程序 appid |
| `button_list` | `pagepath` | 小程序路径 |
| `submit_button` | `text` | 提交按钮文案 |
| `submit_button` | `style` | 提交按钮样式 |
| `submit_button` | `key` | 提交按钮 key |

说明：不同 `card_type` 对字段组合有要求，具体必填规则请以企业微信官方文档为准。
通讯录（成员）：


| 方法 | 说明 | 主要字段（必填/可选） |
| --- | --- | --- |
| `contacts()->getUser(string $userId): array` | 获取成员详情 | 必填：`userId` |
| `contacts()->listUsers(int $departmentId = 1, bool $fetchChild = true, ?int $status = null): array` | 获取部门成员 | 可选：`departmentId`（默认 1）、`fetchChild`（是否递归子部门）、`status` |
| `contacts()->listSimpleUsers(int $departmentId = 1, bool $fetchChild = true, ?int $status = null): array` | 获取部门成员（精简） | 可选：`departmentId`、`fetchChild`、`status` |
| `contacts()->createUser(array $data): array` | 创建成员 | 必填：`userid`、`name`、`department`。可选：`mobile`、`email`、`position`、`gender`、`enable` 等 |
| `contacts()->updateUser(array $data): array` | 更新成员 | 必填：`userid`。可选：其余需要更新的字段 |
| `contacts()->deleteUser(string $userId): array` | 删除成员 | 必填：`userId` |

通讯录（部门）：

| 方法 | 说明 | 主要字段（必填/可选） |
| --- | --- | --- |
| `contacts()->listDepartments(?int $departmentId = null): array` | 获取部门列表 | 可选：`departmentId`（不传返回全部） |
| `contacts()->createDepartment(array $data): array` | 创建部门 | 必填：`name`、`parentid`。可选：`order`、`id` |
| `contacts()->updateDepartment(array $data): array` | 更新部门 | 必填：`id`。可选：`name`、`parentid`、`order` |
| `contacts()->deleteDepartment(int $departmentId): array` | 删除部门 | 必填：`departmentId` |


## 多企业/多应用

```php
$wecom = new WeCom($config, "corp_a", "app1");
$wecomB = $wecom->forCorpApp("corp_b", "app2");
```

## 高级配置示例

```php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use WorkWeXin\WeCom;

$logger = new Logger("wecom");
$logger->pushHandler(new StreamHandler(__DIR__ . "/runtime/wecom.log"));

$config["logger"] = $logger;
$config["http"]["retry"] = [
    "max_retries" => 2,
    "retry_delay_ms" => 300,
    "retry_on_status" => [500, 502, 503, 504],
    "retry_on_timeout" => true
];

$wecom = new WeCom($config, "default", "default");
```

## 错误处理

SDK 会在接口返回 `errcode != 0` 时抛出异常：

```php
use WorkWeXin\Exception\WeComException;

try {
    $wecom->message()->send([...]);
} catch (WeComException $e) {
    // $e->getErrCode() 获取 errcode
    // $e->getMessage() 获取 errmsg
}
```


## 通讯录扩展接口

批量/异步接口：

| 方法 | 说明 | 主要字段 |
| --- | --- | --- |
| `contacts()->batchDeleteUsers(array $userIds): array` | 批量删除成员 | `userIds` |
| `contacts()->inviteUsers(array $userIds = [], array $partyIds = [], array $tagIds = []): array` | 批量邀请 | `userIds`/`partyIds`/`tagIds` 至少一项 |
| `contacts()->batchSyncUsers(array $data): array` | 批量同步成员 | `media_id` |
| `contacts()->batchReplaceUsers(array $data): array` | 批量更新成员 | `media_id` |
| `contacts()->batchReplaceDepartments(array $data): array` | 批量替换部门 | `media_id` |
| `contacts()->getBatchResult(string $jobId): array` | 获取异步结果 | `jobId` |

标签管理：

| 方法 | 说明 | 主要字段 |
| --- | --- | --- |
| `contacts()->listTags(): array` | 获取标签列表 | - |
| `contacts()->getTag(int $tagId): array` | 获取标签成员 | `tagId` |
| `contacts()->createTag(string $name, ?int $tagId = null): array` | 创建标签 | `name` |
| `contacts()->updateTag(int $tagId, string $tagName): array` | 更新标签 | `tagId`、`tagName` |
| `contacts()->deleteTag(int $tagId): array` | 删除标签 | `tagId` |
| `contacts()->addTagUsers(int $tagId, array $userList = [], array $partyList = []): array` | 标签增加成员 | `tagId`、`userList`/`partyList` |
| `contacts()->deleteTagUsers(int $tagId, array $userList = [], array $partyList = []): array` | 标签删除成员 | `tagId`、`userList`/`partyList` |

## 通讯录回调与变更同步

回调配置：

```php
$config["corps"]["default"]["callback"] = [
    "token" => "your_token",
    "encoding_aes_key" => "your_encoding_aes_key_43_chars"
];
```

使用示例：

```php
$crypto = $wecom->callback();
$plain = $crypto->verifyUrl($msgSignature, $timestamp, $nonce, $echoStr);
$event = $crypto->decryptMessage($msgSignature, $timestamp, $nonce, $postData);
```

回调事件分发示例：

```php
$events = $wecom->callbackEvents();

$events->onEvent("change_contact", function (array $event, $service) {
    // 例如：成员/部门变更
    return null;
});

$events->onMessage("text", function (array $event, $service) {
    // 这里返回明文 XML，SDK 会自动加密
    return $service->buildTextReply($event, "已收到");
});

$reply = $events->dispatch($msgSignature, $timestamp, $nonce, $postData);
// echo $reply;
```

常见回调类型（示例）：

- Event: `change_contact`
  - ChangeType: `create_user`/`update_user`/`delete_user`/`create_party`/`update_party`/`delete_party`
- Event: `change_external_contact`
  - ChangeType: `add_external_contact`/`edit_external_contact`/`del_external_contact`/`add_half_external_contact`/`del_half_external_contact`
- Event: `sys_approval_change`
  - ChangeType: `approve`/`reject`/`cancel`



## 示例

参考 `examples/basic.php`、`examples/callback.php`、`examples/customer_contact.php`、`examples/approval_oa.php`、`examples/miniprogram.php`、`examples/payment.php`。
