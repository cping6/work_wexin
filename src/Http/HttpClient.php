<?php

declare(strict_types=1);

namespace WorkWeXin\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use WorkWeXin\Exception\WeComException;

final class HttpClient
{
    private ClientInterface $client;
    private LoggerInterface $logger;
    private array $config;
    private array $retryConfig;

    public function __construct(array $config = [], ?ClientInterface $client = null, ?LoggerInterface $logger = null)
    {
        $this->config = $config;
        $this->logger = $logger ?? new NullLogger();

        if ($client instanceof ClientInterface) {
            $this->client = $client;
        } else {
            $this->client = new Client([
                "base_uri" => $config["base_uri"] ?? "https://qyapi.weixin.qq.com",
                "timeout" => $config["timeout"] ?? 5.0
            ]);
        }

        $retry = $config["retry"] ?? [];
        $this->retryConfig = [
            "max_retries" => isset($retry["max_retries"]) ? (int) $retry["max_retries"] : 0,
            "retry_delay_ms" => isset($retry["retry_delay_ms"]) ? (int) $retry["retry_delay_ms"] : 200,
            "retry_on_status" => $retry["retry_on_status"] ?? [500, 502, 503, 504],
            "retry_on_timeout" => $retry["retry_on_timeout"] ?? true,
            "methods" => $retry["methods"] ?? ["GET"]
        ];
    }

    public function get(string $uri, array $query = []): array
    {
        return $this->request("GET", $uri, $query, null);
    }

    public function getRaw(string $uri, array $query = []): string
    {
        return $this->requestRaw("GET", $uri, $query, null);
    }

    public function post(string $uri, array $data = [], array $query = []): array
    {
        return $this->request("POST", $uri, $query, $data);
    }

    public function postRaw(string $uri, array $data = [], array $query = []): string
    {
        return $this->requestRaw("POST", $uri, $query, $data);
    }

    private function request(string $method, string $uri, array $query = [], ?array $data = null): array
    {
        $url = $this->buildUrl($uri, $query);
        $this->logger->debug("HTTP request", [
            "method" => $method,
            "url" => $this->maskUrl($url)
        ]);
        $encodedBody = null;
        $headers = ["Content-Type" => "application/json"];
        if ($method === "POST" && $data !== null) {
            $encoded = json_encode($data);
            if ($encoded === false) {
                throw new WeComException(0, "Failed to encode JSON body");
            }
            $encodedBody = $encoded;
        }

        $attempt = 0;
        $max = $this->retryConfig["max_retries"];

        while (true) {
            try {
                $requestBody = $encodedBody !== null ? Utils::streamFor($encodedBody) : null;
                $response = $this->client->sendRequest(new Request($method, $url, $headers, $requestBody));
                $status = $response->getStatusCode();
                $this->logger->debug("HTTP response", ["status" => $status]);
                if ($this->shouldRetryStatus($method, $status, $attempt, $max)) {
                    $this->logger->warning("HTTP retry on status", ["status" => $status, "attempt" => $attempt + 1]);
                    $this->sleepRetry();
                    $attempt++;
                    continue;
                }
            } catch (ClientExceptionInterface $e) {
                if ($this->shouldRetryException($method, $e, $attempt, $max)) {
                    $this->logger->warning("HTTP retry on exception", ["error" => $e->getMessage(), "attempt" => $attempt + 1]);
                    $this->sleepRetry();
                    $attempt++;
                    continue;
                }

                throw new WeComException(0, "HTTP request failed: " . $e->getMessage());
            }

            break;
        }

        $bodyText = (string) $response->getBody();
        $data = json_decode($bodyText, true);
        if (!is_array($data)) {
            throw new WeComException(0, "Invalid JSON response");
        }

        if (isset($data["errcode"]) && (int) $data["errcode"] !== 0) {
            $this->logger->error("WeCom API error", [
                "errcode" => (int) $data["errcode"],
                "errmsg" => (string) ($data["errmsg"] ?? "")
            ]);
            throw new WeComException((int) $data["errcode"], (string) $data["errmsg"]);
        }

        return $data;
    }

    private function requestRaw(string $method, string $uri, array $query = [], ?array $data = null): string
    {
        $url = $this->buildUrl($uri, $query);
        $this->logger->debug("HTTP request", [
            "method" => $method,
            "url" => $this->maskUrl($url)
        ]);

        $encodedBody = null;
        $headers = ["Content-Type" => "application/json"];
        if ($method === "POST" && $data !== null) {
            $encoded = json_encode($data);
            if ($encoded === false) {
                throw new WeComException(0, "Failed to encode JSON body");
            }
            $encodedBody = $encoded;
        }

        $attempt = 0;
        $max = $this->retryConfig["max_retries"];

        while (true) {
            try {
                $requestBody = $encodedBody !== null ? Utils::streamFor($encodedBody) : null;
                $response = $this->client->sendRequest(new Request($method, $url, $headers, $requestBody));
                $status = $response->getStatusCode();
                $this->logger->debug("HTTP response", ["status" => $status]);
                if ($this->shouldRetryStatus($method, $status, $attempt, $max)) {
                    $this->logger->warning("HTTP retry on status", ["status" => $status, "attempt" => $attempt + 1]);
                    $this->sleepRetry();
                    $attempt++;
                    continue;
                }
            } catch (ClientExceptionInterface $e) {
                if ($this->shouldRetryException($method, $e, $attempt, $max)) {
                    $this->logger->warning("HTTP retry on exception", ["error" => $e->getMessage(), "attempt" => $attempt + 1]);
                    $this->sleepRetry();
                    $attempt++;
                    continue;
                }

                throw new WeComException(0, "HTTP request failed: " . $e->getMessage());
            }

            break;
        }

        return (string) $response->getBody();
    }

    private function buildUrl(string $uri, array $query): string
    {
        $base = $this->config["base_uri"] ?? "https://qyapi.weixin.qq.com";
        $url = preg_match("/^https?:\\/\\//i", $uri) ? $uri : rtrim($base, "/") . "/" . ltrim($uri, "/");
        if (!empty($query)) {
            $sep = strpos($url, "?") === false ? "?" : "&";
            $url .= $sep . http_build_query($query);
        }

        return $url;
    }

    private function maskUrl(string $url): string
    {
        return preg_replace("/access_token=[^&]+/i", "access_token=***", $url) ?? $url;
    }

    private function shouldRetryStatus(string $method, int $status, int $attempt, int $max): bool
    {
        if ($attempt >= $max) {
            return false;
        }

        if (!$this->isRetryMethod($method)) {
            return false;
        }

        return in_array($status, (array) $this->retryConfig["retry_on_status"], true);
    }

    private function shouldRetryException(string $method, ClientExceptionInterface $e, int $attempt, int $max): bool
    {
        if ($attempt >= $max) {
            return false;
        }

        if (!$this->isRetryMethod($method)) {
            return false;
        }

        if ($this->retryConfig["retry_on_timeout"] !== true) {
            return false;
        }

        return stripos($e->getMessage(), "timed out") !== false;
    }

    private function isRetryMethod(string $method): bool
    {
        $methods = array_map("strtoupper", (array) $this->retryConfig["methods"]);
        return in_array(strtoupper($method), $methods, true);
    }

    private function sleepRetry(): void
    {
        $delayMs = max(0, (int) $this->retryConfig["retry_delay_ms"]);
        if ($delayMs > 0) {
            usleep($delayMs * 1000);
        }
    }
}
