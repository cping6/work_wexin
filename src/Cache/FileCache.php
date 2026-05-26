<?php

declare(strict_types=1);

namespace WorkWeXin\Cache;

use DateInterval;
use Psr\SimpleCache\CacheInterface;

final class FileCache implements CacheInterface
{
    private string $path;
    private int $ttl;

    public function __construct(string $path, int $ttl = 7200)
    {
        $this->path = rtrim($path, DIRECTORY_SEPARATOR);
        $this->ttl = $ttl;
    }

    public function get($key, $default = null)
    {
        $this->assertKey($key);
        $file = $this->getFilePath($key);
        if (!is_file($file)) {
            return $default;
        }

        $raw = file_get_contents($file);
        if ($raw === false) {
            return $default;
        }

        $data = json_decode($raw, true);
        if (!is_array($data) || !isset($data["value"], $data["expire_at"])) {
            return $default;
        }

        if ($data["expire_at"] !== 0 && $data["expire_at"] < time()) {
            @unlink($file);
            return $default;
        }

        $decoded = base64_decode((string) $data["value"], true);
        if ($decoded === false) {
            return $default;
        }

        $value = @unserialize($decoded);
        return $value === false && $decoded !== serialize(false) ? $default : $value;
    }

    public function set($key, $value, $ttl = null): bool
    {
        $this->assertKey($key);
        $ttlSeconds = $this->normalizeTtl($ttl);
        $expireAt = $ttlSeconds > 0 ? time() + $ttlSeconds : 0;
        $encoded = base64_encode(serialize($value));
        $data = json_encode([
            "value" => $encoded,
            "expire_at" => $expireAt
        ]);

        if ($data === false) {
            return false;
        }

        $this->ensureDir();
        $written = file_put_contents($this->getFilePath($key), $data, LOCK_EX);
        return $written !== false;
    }

    public function delete($key): bool
    {
        $this->assertKey($key);
        $file = $this->getFilePath($key);
        if (is_file($file)) {
            return @unlink($file);
        }

        return true;
    }

    public function clear(): bool
    {
        if (!is_dir($this->path)) {
            return true;
        }

        $files = glob($this->path . DIRECTORY_SEPARATOR . "*.json");
        if ($files === false) {
            return false;
        }

        foreach ($files as $file) {
            @unlink($file);
        }

        return true;
    }

    public function getMultiple($keys, $default = null): iterable
    {
        if (!is_iterable($keys)) {
            throw new \InvalidArgumentException("Keys must be iterable");
        }

        $values = [];
        foreach ($keys as $key) {
            $values[$key] = $this->get($key, $default);
        }

        return $values;
    }

    public function setMultiple($values, $ttl = null): bool
    {
        if (!is_iterable($values)) {
            throw new \InvalidArgumentException("Values must be iterable");
        }

        $ok = true;
        foreach ($values as $key => $value) {
            $ok = $this->set($key, $value, $ttl) && $ok;
        }

        return $ok;
    }

    public function deleteMultiple($keys): bool
    {
        if (!is_iterable($keys)) {
            throw new \InvalidArgumentException("Keys must be iterable");
        }

        $ok = true;
        foreach ($keys as $key) {
            $ok = $this->delete($key) && $ok;
        }

        return $ok;
    }

    public function has($key): bool
    {
        $this->assertKey($key);
        $file = $this->getFilePath($key);
        if (!is_file($file)) {
            return false;
        }

        $raw = file_get_contents($file);
        if ($raw === false) {
            return false;
        }

        $data = json_decode($raw, true);
        if (!is_array($data) || !isset($data["expire_at"])) {
            return false;
        }

        return $data["expire_at"] === 0 || $data["expire_at"] >= time();
    }

    private function ensureDir(): void
    {
        if (!is_dir($this->path)) {
            @mkdir($this->path, 0777, true);
        }
    }

    private function assertKey($key): void
    {
        if (!is_string($key) || $key === "") {
            throw new \InvalidArgumentException("Cache key must be a non-empty string");
        }
    }

    private function normalizeTtl($ttl): int
    {
        if ($ttl === null) {
            return $this->ttl;
        }

        if ($ttl instanceof DateInterval) {
            $now = new \DateTimeImmutable();
            $ttl = $now->add($ttl)->getTimestamp() - $now->getTimestamp();
        }

        if (!is_int($ttl)) {
            throw new \InvalidArgumentException("TTL must be int, DateInterval or null");
        }

        return $ttl;
    }

    private function getFilePath(string $key): string
    {
        $safe = preg_replace("/[^a-zA-Z0-9._-]/", "_", $key);
        return $this->path . DIRECTORY_SEPARATOR . $safe . ".json";
    }
}
