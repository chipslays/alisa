<?php

namespace Alisa\Support;

use Alisa\Configuration;
use Alisa\Support\Container;

class Storage
{
    protected string $path;

    public function __construct()
    {
        /** @var Configuration */
        $config = Container::getInstance()->make(Configuration::class);

        $tempPath = sys_get_temp_dir() . '/alisa/' . $config->get('skill_id', '_all_skills') . '/storage';

        $this->path = rtrim($config->get('storage', $tempPath), '\/');

        if (!file_exists($this->path)) {
            mkdir($this->path, recursive: true);
        }
    }

    public function set(string $key, mixed $value): self
    {
        file_put_contents($this->path . '/' . $key, json_encode($value), LOCK_EX);

        return $this;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->has($key)) {
            return $default;
        }

        return json_decode(file_get_contents($this->path . '/' . $key), true);
    }

    public function has(string $key): bool
    {
        return file_exists($this->path . '/' . $key);
    }

    public function remove(string $key): bool
    {
        return unlink($this->path . '/' . $key);
    }
}