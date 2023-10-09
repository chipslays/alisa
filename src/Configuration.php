<?php

namespace Alisa;

class Configuration
{
    protected $options = [
        'event' => null,
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