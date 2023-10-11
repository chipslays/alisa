<?php

namespace Alisa;

class Configuration
{
    protected $options = [
        'skill_id' => null, // идентификатор навыка
        'token' => null, // OAuth-токен для загрузки изображений
        'storage' => null, // путь до папки где будут храниться файлы
        'images' => null, // путь до папки где будут храниться изображения
        'auto_repeat' => true, // авто-ответ на основе предыдущего хендлера для интента YANDEX.REPEAT
        'event' => null, // событие от диалогов (например для тестов или cloud function)
        'assets' => [], // алиасы для изображений и звуков
    ];

    public function __construct(array $options = [])
    {
        $this->options = array_replace_recursive($this->options, $options);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->options[$key] ?? $default;
    }

    public function set(string $key, mixed $value): self
    {
        $this->options[$key] = $value;

        return $this;
    }
}