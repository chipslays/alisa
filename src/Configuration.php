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
        'middlewares' => [], // глобальные мидлвары
    ];

    /**
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->options = array_replace_recursive($this->options, $options);
    }

    /**
     * Получить значение опции.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->options[$key] ?? $default;
    }

    /**
     * Установить значение опции.
     *
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function set(string $key, mixed $value): self
    {
        $this->options[$key] = $value;

        return $this;
    }

    /**
     * Существует ли опция.
     *
     * @param string $key
     * @return boolean
     */
    public function has(string $key): bool
    {
        return isset($this->options[$key]);
    }
}