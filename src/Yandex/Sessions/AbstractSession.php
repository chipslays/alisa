<?php

namespace Alisa\Yandex\Sessions;

abstract class AbstractSession
{
    public function __construct(protected array $state)
    {
        //
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->state[$key] ?? $default;
    }

    public function set(string $key, mixed $value): self
    {
        $this->state[$key] = $value;

        return $this;
    }

    public function has(string $key): bool
    {
        return isset($this->state[$key]);
    }

    public function remove(string $key): self
    {
        unset($this->state[$key]);

        return $this;
    }

    public function count(): int
    {
        return count($this->state);
    }

    public function destroy(): self
    {
        $this->state = [];

        return $this;
    }

    public function all(): array
    {
        return $this->state;
    }
}