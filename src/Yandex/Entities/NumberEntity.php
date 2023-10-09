<?php

namespace Alisa\Yandex\Entities;

class NumberEntity extends AbstractEntity
{
    public function toNumber(): int
    {
        return (int) $this->entity['value'];
    }

    public function toFloat(int $percision = 2, int $mode = PHP_ROUND_HALF_UP): float
    {
        return (float) round($this->entity['value'], $percision, $mode);
    }

    public function __toString(): string
    {
        return $this->entity['value'];
    }
}