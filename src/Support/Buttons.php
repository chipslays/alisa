<?php

namespace Alisa\Support;

class Buttons
{
    protected static array $buttons = [];

    public static function override(array $buttons): void
    {
        self::$buttons = $buttons;
    }

    public static function merge(array $buttons): void
    {
        self::$buttons = [...self::$buttons, ...$buttons];
    }

    public static function add(string $alias, array $value): void
    {
        self::$buttons[$alias] = $value;
    }

    public static function get(string $alias, ?array $default = null): ?string
    {
        return self::$buttons[$alias] ?? $default;
    }

    public static function has(string $alias): bool
    {
        return isset($alias, self::$buttons);
    }

    public static function remove(string $alias): void
    {
        unset(self::$buttons[$alias]);
    }

    public static function all(): array
    {
        return self::$buttons;
    }
}