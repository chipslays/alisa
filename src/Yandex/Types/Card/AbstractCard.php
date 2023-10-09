<?php

namespace Alisa\Yandex\Types\Card;

abstract class AbstractCard
{
    protected array $card = [];

    public function toArray(): array
    {
        return $this->card;
    }
}