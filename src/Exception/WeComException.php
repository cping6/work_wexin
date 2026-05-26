<?php

declare(strict_types=1);

namespace WorkWeXin\Exception;

use RuntimeException;

final class WeComException extends RuntimeException
{
    private int $errCode;

    public function __construct(int $errCode, string $message)
    {
        parent::__construct($message, $errCode);
        $this->errCode = $errCode;
    }

    public function getErrCode(): int
    {
        return $this->errCode;
    }
}
