<?php

namespace Alisa\Scenes;

class Stage
{
    protected static array $scenes = [];

    public static function add(Scene $scene): void
    {
        self::$scenes[$scene->getName()] = $scene;
    }

    public static function get(?string $name): ?Scene
    {
        if (!$name) {
            return null;
        }

        return self::$scenes[$name] ?? null;
    }
}