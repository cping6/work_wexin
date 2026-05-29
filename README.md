# WorkWeXin WeCom SDK

企业微信 PHP SDK。当前按 service 模块封装能力，用户创建 `WeCom` 实例后，通过对应 service 调用方法。

## 安装

```bash
composer require workwexin/wecom
```

## 快速开始

```php
use WorkWeXin\WeCom;

$wecom = WeCom::fromApp("wx123", 1000002, "app_secret");

$wecom->message()->sendText("你好", ["touser" => "userid1"]);
$wecom->contacts()->getUser("userid1");
```

也可以使用数组配置：

```php
$wecom = WeCom::make([
    "corp_id" => "wx123",
    "agent_id" => 1000002,
    "secret" => "app_secret"
]);
```

## 调用方式

SDK 的主要调用方式是：

```php
$wecom->{service}()->{method}(...);
```

例如：

```php
$wecom->message()->sendMarkdown("**内容**", ["touser" => "userid1"]);
$wecom->contacts()->listUsers(1, true);
$wecom->approval()->getTemplateDetail("template_id");
$wecom->oa()->getCheckinData($startTime, $endTime, ["userid1"]);
```

消息模块也提供少量快捷方法：

```php
$wecom->sendText("你好", "userid1");
$wecom->sendMarkdown("**内容**", ["userid1", "userid2"]);
```

## 可用 Service

| service | 用途 |
| --- | --- |
| `message()` | 应用消息 |
| `contacts()` | 通讯录 |
| `auth()` | 身份与登录 |
| `callback()` | 回调加解密 |
| `callbackEvents()` | 回调事件分发 |
| `customerContact()` | 客户联系 |
| `approval()` | 审批 |
| `oa()` | OA：打卡、日程、会议室、待办等 |
| `miniProgram()` | 小程序 |
| `payment()` | 支付 |

## 常用示例

发送应用消息：

```php
$wecom->message()->sendText("你好", ["touser" => "userid1"]);
$wecom->message()->sendMarkdown("**加粗**内容", ["touser" => "userid1|userid2"]);
$wecom->message()->sendTextCard("标题", "描述", "https://example.com", ["touser" => "userid1"]);
```

通讯录：

```php
$wecom->contacts()->getUser("userid1");
$wecom->contacts()->listUsers(1, true);
$wecom->contacts()->createUser([
    "userid" => "userid1",
    "name" => "张三",
    "department" => [1]
]);
```

审批与 OA：

```php
$wecom->approval()->listTemplates();
$wecom->approval()->getApprovalDetail("sp_no");
$wecom->oa()->getCheckinData($startTime, $endTime, ["userid1"]);
```

客户联系：

```php
$wecom->customerContact()->listFollowUsers();
$wecom->customerContact()->listExternalContacts("userid1");
```

小程序：

```php
$wecom->miniProgram()->getSession("js_code");
$wecom->miniProgram()->getJsapiTicket();
```

支付：

```php
$wecom->payment()->queryOrder($data);
$sign = $wecom->payment()->sign($data, "payment_api_key");
```

## 配置

单企业单应用推荐使用：

```php
$wecom = WeCom::fromApp("wx123", 1000002, "app_secret", [
    "cache_path" => __DIR__ . "/runtime/cache",
    "timeout" => 5.0
]);
```

多企业或多应用使用完整配置：

```php
$wecom = WeCom::make([
    "corps" => [
        "default" => [
            "corp_id" => "wx123",
            "apps" => [
                "default" => [
                    "agent_id" => 1000002,
                    "secret" => "app_secret"
                ]
            ],
            "callback" => [
                "token" => "callback_token",
                "encoding_aes_key" => "encoding_aes_key"
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
]);
```

## Token 与签名

- 普通 service 接口统一使用企业微信 `access_token`，SDK 会自动获取并缓存。
- 回调验签和加解密使用 `callback.token` 与 `callback.encoding_aes_key`。
- 支付签名使用支付 API key，通过 `payment()->sign()` 和 `payment()->verifySignature()` 处理。

## 错误处理

企业微信接口返回错误时会抛出 `WorkWeXin\Exception\WeComException`：

```php
try {
    $wecom->contacts()->getUser("userid1");
} catch (\WorkWeXin\Exception\WeComException $e) {
    echo $e->getCode() . ": " . $e->getMessage();
}
```

## 示例文件

更多示例见 `examples/`：

- `examples/basic.php`
- `examples/callback.php`
- `examples/customer_contact.php`
- `examples/approval_oa.php`
- `examples/miniprogram.php`
- `examples/payment.php`
