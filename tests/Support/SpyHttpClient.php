<?php

declare(strict_types=1);

namespace WorkWeXin\Tests\Support;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class SpyHttpClient implements ClientInterface
{
    private ?RequestInterface $lastRequest = null;
    private ResponseInterface $response;

    public function __construct(?ResponseInterface $response = null)
    {
        $this->response = $response ?? new Response(200, [], json_encode(["errcode" => 0]));
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->lastRequest = $request;
        return $this->response;
    }

    public function getLastRequest(): ?RequestInterface
    {
        return $this->lastRequest;
    }
}
