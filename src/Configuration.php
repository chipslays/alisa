<?php

namespace Alisa;

class Configuration
{
    protected $options = [
        'skill_id' => null,
        'token' => null,
        'storage' => null, // путь до папки где будут храниться файлы
        'event' => null,
        'assets' => [
            'game-win-1' => 'alice-sounds-game-win-1.opus',
            'game-win-2' => 'alice-sounds-game-win-2.opus',
            'game-win-3' => 'alice-sounds-game-win-3.opus',
        ],
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