<?php

declare(strict_types=1);

namespace WorkWeXin\Tests;

use PHPUnit\Framework\TestCase;
use WorkWeXin\Callback\CallbackCrypto;
use WorkWeXin\Service\CallbackService;

final class CallbackServiceTest extends TestCase
{
    public function testDispatchCallsEventHandler(): void
    {
        $crypto = $this->makeCrypto();
        $service = new CallbackService($crypto);
        $called = false;

        $service->onEvent("change_contact", function (array $event) use (&$called) {
            $called = true;
            return null;
        }, "create_user");

        $payload = $this->encryptEvent($crypto, $this->buildEventXml("event", "change_contact", "create_user"));
        $reply = $service->dispatch($payload["signature"], $payload["timestamp"], $payload["nonce"], $payload["xml"]);

        $this->assertSame("success", $reply);
        $this->assertTrue($called);
    }

    public function testDispatchEncryptsReply(): void
    {
        $crypto = $this->makeCrypto();
        $service = new CallbackService($crypto);

        $service->onMessage("text", function (array $event, CallbackService $callback) {
            return $callback->buildTextReply($event, "ok");
        });

        $payload = $this->encryptEvent($crypto, $this->buildEventXml("text"));
        $reply = $service->dispatch($payload["signature"], $payload["timestamp"], $payload["nonce"], $payload["xml"]);

        $this->assertNotSame("success", $reply);

        $parsed = simplexml_load_string($reply, "SimpleXMLElement", LIBXML_NOCDATA);
        $this->assertNotFalse($parsed);
        $replySig = (string) ($parsed->MsgSignature ?? "");
        $replyTime = (string) ($parsed->TimeStamp ?? "");
        $replyNonce = (string) ($parsed->Nonce ?? "");

        $decrypted = $crypto->decryptMessage($replySig, $replyTime, $replyNonce, $reply);
        $this->assertSame("text", $decrypted["MsgType"] ?? "");
        $this->assertSame("ok", $decrypted["Content"] ?? "");
    }

    private function makeCrypto(): CallbackCrypto
    {
        return new CallbackCrypto(
            "token",
            "abcdefghijklmnopqrstuvwxyz0123456789ABCDEFG",
            "wx123"
        );
    }

    private function encryptEvent(CallbackCrypto $crypto, string $xml): array
    {
        $timestamp = (string) time();
        $nonce = "nonce123";
        $encryptedXml = $crypto->encryptMessage($xml, $timestamp, $nonce);
        $parsed = simplexml_load_string($encryptedXml, "SimpleXMLElement", LIBXML_NOCDATA);
        if ($parsed === false) {
            $this->fail("Failed to parse encrypted xml");
        }

        return [
            "xml" => $encryptedXml,
            "signature" => (string) ($parsed->MsgSignature ?? ""),
            "timestamp" => (string) ($parsed->TimeStamp ?? ""),
            "nonce" => (string) ($parsed->Nonce ?? "")
        ];
    }

    private function buildEventXml(string $msgType, string $event = "", string $changeType = ""): string
    {
        $eventXml = "";
        if ($event !== "") {
            $eventXml .= "<Event><![CDATA[" . $event . "]]></Event>";
        }
        if ($changeType !== "") {
            $eventXml .= "<ChangeType><![CDATA[" . $changeType . "]]></ChangeType>";
        }

        return "<xml>"
            . "<ToUserName><![CDATA[toUser]]></ToUserName>"
            . "<FromUserName><![CDATA[fromUser]]></FromUserName>"
            . "<CreateTime>123</CreateTime>"
            . "<MsgType><![CDATA[" . $msgType . "]]></MsgType>"
            . $eventXml
            . "</xml>";
    }
}
