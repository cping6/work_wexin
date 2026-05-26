<?php

declare(strict_types=1);

namespace WorkWeXin\Tests\Support;

use DateInterval;
use Psr\SimpleCache\CacheInterface;

final class ArrayCache implements CacheInterface
{
    private array $values = [];
    private array $expires = [];

    public function get($key, $default = null)
    {
        if (!$this->has($key)) {
            return $default;
        }

        return $this->values[$key] ?? $default;
    }

    public function set($key, $value, $ttl = null): bool
    {
        $this->values[$key] = $value;
        $this->expires[$key] = $this->normalizeTtl($ttl);
        return true;
    }

    public function delete($key): bool
    {
        unset($this->values[$key], $this->expires[$key]);
        return true;
    }

    public function clear(): bool
    {
        $this->values = [];
        $this->expires = [];
        return true;
    }

    public function getMultiple($keys, $default = null): iterable
    {
        $data = [];
        foreach ($keys as $key) {
            $data[$key] = $this->get($key, $default);
        }
        return $data;
    }

    public function setMultiple($values, $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }
        return true;
    }

    public function deleteMultiple($keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }
        return true;
    }

    public function has($key): bool
    {
        if (!array_key_exists($key, $this->values)) {
            return false;
        }

        $expireAt = $this->expires[$key];
        return $expireAt === 0 || $expireAt >= time();
    }

    private function normalizeTtl($ttl): int
    {
        if ($ttl === null) {
            return 0;
        }

        if ($ttl instanceof DateInterval) {
            $now = new \DateTimeImmutable();
            return $now->add($ttl)->getTimestamp();
        }

        if (is_int($ttl)) {
            return $ttl > 0 ? time() + $ttl : 0;
        }

        return 0;
    }
}
