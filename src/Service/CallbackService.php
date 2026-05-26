<?php

declare(strict_types=1);

namespace WorkWeXin\Service;

use WorkWeXin\Callback\CallbackCrypto;
use WorkWeXin\Exception\WeComException;

final class CallbackService
{
    private CallbackCrypto $crypto;
    private array $eventHandlers = [];
    private array $messageHandlers = [];
    private $fallbackHandler = null;

    public function __construct(CallbackCrypto $crypto)
    {
        $this->crypto = $crypto;
    }

    public function verifyUrl(string $msgSignature, string $timestamp, string $nonce, string $echoStr): string
    {
        return $this->crypto->verifyUrl($msgSignature, $timestamp, $nonce, $echoStr);
    }

    public function decrypt(string $msgSignature, string $timestamp, string $nonce, string $postData): array
    {
        return $this->crypto->decryptMessage($msgSignature, $timestamp, $nonce, $postData);
    }

    public function encryptResponse(string $replyXml, string $timestamp, string $nonce): string
    {
        return $this->crypto->encryptMessage($replyXml, $timestamp, $nonce);
    }

    public function onEvent(string $event, callable $handler, ?string $changeType = null): self
    {
        $eventKey = strtolower($event);
        $changeKey = $changeType !== null ? strtolower($changeType) : "";
        $key = $eventKey . ":" . $changeKey;
        $this->eventHandlers[$key] = $handler;
        return $this;
    }

    public function onMessage(string $msgType, callable $handler): self
    {
        $key = strtolower($msgType);
        $this->messageHandlers[$key] = $handler;
        return $this;
    }

    public function onFallback(callable $handler): self
    {
        $this->fallbackHandler = $handler;
        return $this;
    }

    public function dispatch(
        string $msgSignature,
        string $timestamp,
        string $nonce,
        string $postData,
        string $successText = "success"
    ): string
    {
        $event = $this->decrypt($msgSignature, $timestamp, $nonce, $postData);
        $replyXml = $this->handle($event);
        if ($replyXml === null || $replyXml === "") {
            return $successText;
        }

        return $this->encryptResponse($replyXml, $timestamp, $nonce);
    }

    public function buildTextReply(array $event, string $content): string
    {
        $toUser = $event["FromUserName"] ?? "";
        $fromUser = $event["ToUserName"] ?? "";
        $time = (string) time();

        return "<xml>"
            . "<ToUserName><![CDATA[" . $toUser . "]]></ToUserName>"
            . "<FromUserName><![CDATA[" . $fromUser . "]]></FromUserName>"
            . "<CreateTime>" . $time . "</CreateTime>"
            . "<MsgType><![CDATA[text]]></MsgType>"
            . "<Content><![CDATA[" . $content . "]]></Content>"
            . "</xml>";
    }

    private function handle(array $event): ?string
    {
        $msgType = strtolower((string) ($event["MsgType"] ?? ""));
        if ($msgType === "event") {
            return $this->dispatchEvent($event);
        }

        if ($msgType !== "" && isset($this->messageHandlers[$msgType])) {
            return $this->callHandler($this->messageHandlers[$msgType], $event);
        }

        return $this->callFallback($event);
    }

    private function dispatchEvent(array $event): ?string
    {
        $eventName = strtolower((string) ($event["Event"] ?? ""));
        $changeType = strtolower((string) ($event["ChangeType"] ?? ""));
        $key = $eventName . ":" . $changeType;
        if ($eventName !== "" && isset($this->eventHandlers[$key])) {
            return $this->callHandler($this->eventHandlers[$key], $event);
        }

        $keyAnyChange = $eventName . ":";
        if ($eventName !== "" && isset($this->eventHandlers[$keyAnyChange])) {
            return $this->callHandler($this->eventHandlers[$keyAnyChange], $event);
        }

        return $this->callFallback($event);
    }

    private function callHandler(callable $handler, array $event): ?string
    {
        $result = $handler($event, $this);
        if ($result === null || $result === "") {
            return null;
        }
        if (!is_string($result)) {
            throw new WeComException(0, "callback handler must return string or null");
        }

        return $result;
    }

    private function callFallback(array $event): ?string
    {
        if ($this->fallbackHandler === null) {
            return null;
        }

        return $this->callHandler($this->fallbackHandler, $event);
    }
}
