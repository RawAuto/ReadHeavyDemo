<?php

/**
 * Cache interface - designed to mirror Redis patterns.
 * This allows easy swap to RedisCache for production use.
 */
interface CacheInterface
{
    public function get(string $key): mixed;
    public function set(string $key, mixed $value, int $ttl = 300): void;
    public function has(string $key): bool;
    public function delete(string $key): void;
    public function clear(): void;
}

/**
 * Simple in-memory cache implementation.
 * 
 * Note: This cache is per-request in a typical PHP-FPM setup.
 * For persistent caching across requests, use Redis or similar.
 * 
 * In this demo, we use a static array so it persists within
 * a single request lifecycle (useful for avoiding duplicate lookups).
 */
class ArrayCache implements CacheInterface
{
    private static array $store = [];
    private static array $expiry = [];

    public function get(string $key): mixed
    {
        if (!$this->has($key)) {
            return null;
        }
        return self::$store[$key];
    }

    public function set(string $key, mixed $value, int $ttl = 300): void
    {
        self::$store[$key] = $value;
        self::$expiry[$key] = time() + $ttl;
    }

    public function has(string $key): bool
    {
        if (!isset(self::$store[$key])) {
            return false;
        }

        // Check expiry
        if (isset(self::$expiry[$key]) && time() > self::$expiry[$key]) {
            $this->delete($key);
            return false;
        }

        return true;
    }

    public function delete(string $key): void
    {
        unset(self::$store[$key], self::$expiry[$key]);
    }

    public function clear(): void
    {
        self::$store = [];
        self::$expiry = [];
    }
}



