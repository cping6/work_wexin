<?php

declare(strict_types=1);

namespace WorkWeXin\Service;

use WorkWeXin\Exception\WeComException;

final class MessageService extends BaseService
{
    public function send(array $payload): array
    {
        if (empty($payload["msgtype"])) {
            throw new WeComException(0, "msgtype is required for message send");
        }

        if (empty($payload["touser"]) && empty($payload["toparty"]) && empty($payload["totag"])) {
            throw new WeComException(0, "touser/toparty/totag requires at least one");
        }

        if (!isset($payload["agentid"])) {
            $app = $this->config->getApp($this->corpKey, $this->appKey);
            $agentId = $app["agent_id"] ?? null;
            if ($agentId === null) {
                throw new WeComException(0, "agent_id is required for message send");
            }

            $payload["agentid"] = (int) $agentId;
        }

        $msgtype = (string) $payload["msgtype"];
        $this->validateMessage($msgtype, $payload);

        return $this->post("/cgi-bin/message/send", $payload);
    }

    public function sendText(string $content, array $to = []): array
    {
        return $this->send($this->buildPayload("text", $to, [
            "text" => ["content" => $content]
        ]));
    }

    public function sendImage(string $mediaId, array $to = []): array
    {
        return $this->send($this->buildPayload("image", $to, [
            "image" => ["media_id" => $mediaId]
        ]));
    }

    public function sendVoice(string $mediaId, array $to = []): array
    {
        return $this->send($this->buildPayload("voice", $to, [
            "voice" => ["media_id" => $mediaId]
        ]));
    }

    public function sendVideo(string $mediaId, string $title, string $description, array $to = []): array
    {
        return $this->send($this->buildPayload("video", $to, [
            "video" => [
                "media_id" => $mediaId,
                "title" => $title,
                "description" => $description
            ]
        ]));
    }

    public function sendFile(string $mediaId, array $to = []): array
    {
        return $this->send($this->buildPayload("file", $to, [
            "file" => ["media_id" => $mediaId]
        ]));
    }

    public function sendMarkdown(string $content, array $to = []): array
    {
        return $this->send($this->buildPayload("markdown", $to, [
            "markdown" => ["content" => $content]
        ]));
    }

    public function sendNews(array $articles, array $to = []): array
    {
        return $this->send($this->buildPayload("news", $to, [
            "news" => ["articles" => $articles]
        ]));
    }

    public function sendMpNews(array $articles, array $to = []): array
    {
        return $this->send($this->buildPayload("mpnews", $to, [
            "mpnews" => ["articles" => $articles]
        ]));
    }

    public function sendTextCard(string $title, string $description, string $url, array $to = []): array
    {
        return $this->send($this->buildPayload("textcard", $to, [
            "textcard" => [
                "title" => $title,
                "description" => $description,
                "url" => $url
            ]
        ]));
    }

    public function sendTaskCard(string $title, string $description, string $url, string $taskId, array $btn, array $to = []): array
    {
        return $this->send($this->buildPayload("taskcard", $to, [
            "taskcard" => [
                "title" => $title,
                "description" => $description,
                "url" => $url,
                "task_id" => $taskId,
                "btn" => $btn
            ]
        ]));
    }

    public function sendTemplateCard(array $card, array $to = []): array
    {
        return $this->send($this->buildPayload("template_card", $to, [
            "template_card" => $card
        ]));
    }

    public function updateTaskCard(string $userIds, string $taskId, string $replaceName): array
    {
        if ($userIds === "") {
            throw new WeComException(0, "userids is required for update_taskcard");
        }
        if ($taskId === "") {
            throw new WeComException(0, "task_id is required for update_taskcard");
        }
        if ($replaceName === "") {
            throw new WeComException(0, "replace_name is required for update_taskcard");
        }

        return $this->post("/cgi-bin/message/update_taskcard", [
            "userids" => $userIds,
            "task_id" => $taskId,
            "replace_name" => $replaceName
        ]);
    }

    private function buildPayload(string $msgtype, array $to, array $data): array
    {
        return array_merge($to, ["msgtype" => $msgtype], $data);
    }

    private function validateMessage(string $msgtype, array $payload): void
    {
        $validators = [
            "text" => function (array $payload): void {
                $content = $payload["text"]["content"] ?? "";
                if (!is_string($content) || $content === "") {
                    throw new WeComException(0, "text.content is required for text message");
                }
            },
            "image" => function (array $payload): void {
                $this->assertMediaId($payload, "image");
            },
            "voice" => function (array $payload): void {
                $this->assertMediaId($payload, "voice");
            },
            "video" => function (array $payload): void {
                $this->assertMediaId($payload, "video");
                $title = $payload["video"]["title"] ?? "";
                $desc = $payload["video"]["description"] ?? "";
                if (!is_string($title) || $title === "") {
                    throw new WeComException(0, "video.title is required for video message");
                }
                if (!is_string($desc) || $desc === "") {
                    throw new WeComException(0, "video.description is required for video message");
                }
            },
            "file" => function (array $payload): void {
                $this->assertMediaId($payload, "file");
            },
            "markdown" => function (array $payload): void {
                $content = $payload["markdown"]["content"] ?? "";
                if (!is_string($content) || $content === "") {
                    throw new WeComException(0, "markdown.content is required for markdown message");
                }
            },
            "news" => function (array $payload): void {
                $articles = $payload["news"]["articles"] ?? null;
                if (!is_array($articles) || $articles === []) {
                    throw new WeComException(0, "news.articles is required for news message");
                }
            },
            "mpnews" => function (array $payload): void {
                $articles = $payload["mpnews"]["articles"] ?? null;
                if (!is_array($articles) || $articles === []) {
                    throw new WeComException(0, "mpnews.articles is required for mpnews message");
                }
            },
            "textcard" => function (array $payload): void {
                $card = $payload["textcard"] ?? [];
                $this->assertRequiredArray($card, ["title", "description", "url"], "textcard");
            },
            "taskcard" => function (array $payload): void {
                $card = $payload["taskcard"] ?? [];
                $this->assertRequiredArray($card, ["title", "description", "url", "task_id", "btn"], "taskcard");
                if (!is_array($card["btn"])) {
                    throw new WeComException(0, "taskcard.btn must be array");
                }
            },
            "template_card" => function (array $payload): void {
                $card = $payload["template_card"] ?? null;
                if (!is_array($card)) {
                    throw new WeComException(0, "template_card is required for template_card message");
                }
                if (empty($card["card_type"])) {
                    throw new WeComException(0, "template_card.card_type is required");
                }
            }
        ];

        if (!isset($validators[$msgtype])) {
            throw new WeComException(0, "msgtype not supported: " . $msgtype);
        }

        $validators[$msgtype]($payload);
    }

    private function assertMediaId(array $payload, string $type): void
    {
        $mediaId = $payload[$type]["media_id"] ?? "";
        if (!is_string($mediaId) || $mediaId === "") {
            throw new WeComException(0, $type . ".media_id is required");
        }
    }

    private function assertRequiredArray(array $data, array $fields, string $prefix): void
    {
        foreach ($fields as $field) {
            $value = $data[$field] ?? null;
            if ($value === null || $value === "") {
                throw new WeComException(0, $prefix . "." . $field . " is required");
            }
        }
    }
}
