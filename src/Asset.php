<?php

namespace Alisa;

class Asset
{
    protected static array $assets = [];

    public static function add(string $alias, string $value): void
    {
        self::$assets[$alias] = $value;
    }

    public static function get(string $alias): ?string
    {
        return self::$assets[$alias] ?? null;
    }

    public static function has(string $alias): bool
    {
        return isset($alias, self::$assets);
    }

    public static function remove(string $alias): void
    {
        unset(self::$assets[$alias]);
    }

    public static function all(): array
    {
        return self::$assets;
    }
}