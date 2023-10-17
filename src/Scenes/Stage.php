<?php

namespace Alisa\Scenes;

class Stage
{
    protected static array $scenes = [];

    /**
     * Добавить экземпляр сцены.
     *
     * @param Scene $scene
     * @return void
     */
    public static function add(Scene $scene): void
    {
        self::$scenes[$scene->getName()] = $scene;
    }

    /**
     * Получить экземпляр сцены.
     *
     * @param string|null $name
     * @return Scene|null
     */
    public static function get(?string $name): ?Scene
    {
        if (!$name) {
            return null;
        }

        return self::$scenes[$name] ?? null;
    }
}