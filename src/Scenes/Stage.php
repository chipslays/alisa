<?php

namespace Alisa\Scenes;

class Stage
{
    protected static array $scenes = [];

    public static function add(string $name, Scene $scene): void
    {
        self::$scenes[$name] = $scene;
    }

    public static function get(?string $name): ?Scene
    {
        if (!$name) {
            return null;
        }

        return self::$scenes[$name] ?? null;
    }
}