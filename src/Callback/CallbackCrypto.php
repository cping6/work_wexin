<?php

declare(strict_types=1);

namespace WorkWeXin\Callback;

use WorkWeXin\Exception\WeComException;

final class CallbackCrypto
{
    private string $token;
    private string $aesKey;
    private string $corpId;
    private string $key;
    private string $iv;

    public function __construct(string $token, string $encodingAesKey, string $corpId)
    {
        if ($token === "" || $encodingAesKey === "" || $corpId === "") {
            throw new WeComException(0, "token/encodingAesKey/corpId is required");
        }

        if (strlen($encodingAesKey) !== 43) {
            throw new WeComException(0, "encodingAesKey length invalid");
        }

        $this->token = $token;
        $this->aesKey = $encodingAesKey;
        $this->corpId = $corpId;
        $this->key = base64_decode($encodingAesKey . "=", true);
        if ($this->key === false) {
            throw new WeComException(0, "encodingAesKey decode failed");
        }
        $this->iv = substr($this->key, 0, 16);
    }

    public function verifyUrl(string $msgSignature, string $timestamp, string $nonce, string $echoStr): string
    {
        $this->assertSignature($msgSignature, $timestamp, $nonce, $echoStr);
        return $this->decrypt($echoStr);
    }

    public function decryptMessage(string $msgSignature, string $timestamp, string $nonce, string $postData): array
    {
        $encrypt = $this->extractEncrypt($postData);
        $this->assertSignature($msgSignature, $timestamp, $nonce, $encrypt);
        $xml = $this->decrypt($encrypt);
        return $this->xmlToArray($xml);
    }

    public function encryptMessage(string $replyXml, string $timestamp, string $nonce): string
    {
        $encrypt = $this->encrypt($replyXml);
        $signature = $this->getSignature($timestamp, $nonce, $encrypt);
        return $this->buildXml($encrypt, $signature, $timestamp, $nonce);
    }

    private function decrypt(string $encrypt): string
    {
        $ciphertext = base64_decode($encrypt, true);
        if ($ciphertext === false) {
            throw new WeComException(0, "invalid encrypt base64");
        }

        $plain = openssl_decrypt($ciphertext, "AES-256-CBC", $this->key, OPENSSL_RAW_DATA, $this->iv);
        if ($plain === false) {
            throw new WeComException(0, "decrypt failed");
        }

        $plain = $this->pkcs7Unpad($plain);
        $content = substr($plain, 16);
        $len = unpack("N", substr($content, 0, 4))[1];
        $xml = substr($content, 4, $len);
        $corpId = substr($content, 4 + $len);
        if ($corpId !== $this->corpId) {
            throw new WeComException(0, "corpId mismatch");
        }

        return $xml;
    }

    private function encrypt(string $xml): string
    {
        $random = random_bytes(16);
        $len = pack("N", strlen($xml));
        $text = $random . $len . $xml . $this->corpId;
        $padded = $this->pkcs7Pad($text);

        $cipher = openssl_encrypt($padded, "AES-256-CBC", $this->key, OPENSSL_RAW_DATA, $this->iv);
        if ($cipher === false) {
            throw new WeComException(0, "encrypt failed");
        }

        return base64_encode($cipher);
    }

    private function pkcs7Pad(string $text, int $blockSize = 32): string
    {
        $pad = $blockSize - (strlen($text) % $blockSize);
        $pad = $pad === 0 ? $blockSize : $pad;
        return $text . str_repeat(chr($pad), $pad);
    }

    private function pkcs7Unpad(string $text, int $blockSize = 32): string
    {
        $pad = ord(substr($text, -1));
        if ($pad < 1 || $pad > $blockSize) {
            throw new WeComException(0, "invalid padding");
        }
        return substr($text, 0, -$pad);
    }

    private function getSignature(string $timestamp, string $nonce, string $encrypt): string
    {
        $arr = [$this->token, $timestamp, $nonce, $encrypt];
        sort($arr, SORT_STRING);
        return sha1(implode($arr));
    }

    private function assertSignature(string $msgSignature, string $timestamp, string $nonce, string $encrypt): void
    {
        $sign = $this->getSignature($timestamp, $nonce, $encrypt);
        if ($sign !== $msgSignature) {
            throw new WeComException(0, "signature mismatch");
        }
    }

    private function extractEncrypt(string $postData): string
    {
        $xml = simplexml_load_string($postData, "SimpleXMLElement", LIBXML_NOCDATA);
        if ($xml === false || empty($xml->Encrypt)) {
            throw new WeComException(0, "encrypt node missing");
        }

        return (string) $xml->Encrypt;
    }

    private function xmlToArray(string $xml): array
    {
        $data = simplexml_load_string($xml, "SimpleXMLElement", LIBXML_NOCDATA);
        if ($data === false) {
            throw new WeComException(0, "invalid xml");
        }

        return json_decode(json_encode($data), true) ?? [];
    }

    private function buildXml(string $encrypt, string $signature, string $timestamp, string $nonce): string
    {
        return "<xml>"
            . "<Encrypt><![CDATA[" . $encrypt . "]]></Encrypt>"
            . "<MsgSignature><![CDATA[" . $signature . "]]></MsgSignature>"
            . "<TimeStamp>" . $timestamp . "</TimeStamp>"
            . "<Nonce><![CDATA[" . $nonce . "]]></Nonce>"
            . "</xml>";
    }
}
