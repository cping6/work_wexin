<?php

declare(strict_types=1);

namespace WorkWeXin\Service;

final class PaymentService extends BaseService
{
    public function unifiedOrder(array $data): array
    {
        return $this->post("/cgi-bin/pay/unifiedorder", $data);
    }

    public function queryOrder(array $data): array
    {
        return $this->post("/cgi-bin/pay/orderquery", $data);
    }

    public function refund(array $data): array
    {
        return $this->post("/cgi-bin/pay/refund", $data);
    }

    public function transferToUser(array $data): array
    {
        return $this->post("/cgi-bin/pay/transfer", $data);
    }

    public function sendRedPack(array $data): array
    {
        return $this->post("/cgi-bin/pay/sendredpack", $data);
    }

    public function downloadBill(array $query): string
    {
        return $this->getRaw("/cgi-bin/pay/downloadbill", $query);
    }

    public function sign(array $data, string $apiKey, string $signType = "HMAC-SHA256"): string
    {
        $pairs = $this->buildSignPairs($data);
        $string = $pairs !== "" ? ($pairs . "&key=" . $apiKey) : ("key=" . $apiKey);
        $signType = strtoupper($signType);

        if ($signType === "MD5") {
            return strtoupper(md5($string));
        }

        return strtoupper(hash_hmac("sha256", $string, $apiKey));
    }

    public function verifySignature(array $data, string $apiKey, string $signField = "sign", string $signTypeField = "sign_type"): bool
    {
        if (!isset($data[$signField])) {
            return false;
        }
        $signType = isset($data[$signTypeField]) ? (string) $data[$signTypeField] : "HMAC-SHA256";
        $sign = (string) $data[$signField];

        $calc = $this->sign($data, $apiKey, $signType);
        return strtoupper($sign) === $calc;
    }

    private function buildSignPairs(array $data): string
    {
        unset($data["sign"]);
        ksort($data);
        $pairs = [];
        foreach ($data as $key => $value) {
            if ($value === null || $value === "" || is_array($value)) {
                continue;
            }
            $pairs[] = $key . "=" . $value;
        }

        return implode("&", $pairs);
    }
}
