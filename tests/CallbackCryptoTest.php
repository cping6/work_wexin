<?php

declare(strict_types=1);

namespace WorkWeXin\Tests;

use PHPUnit\Framework\TestCase;
use WorkWeXin\Callback\CallbackCrypto;
use WorkWeXin\Exception\WeComException;

final class CallbackCryptoTest extends TestCase
{
    public function testInvalidEncodingAesKeyLength(): void
    {
        $this->expectException(WeComException::class);
        new CallbackCrypto("token", "short_key", "wx123");
    }
}
