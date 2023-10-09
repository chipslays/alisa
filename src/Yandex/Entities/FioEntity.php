<?php

namespace Alisa\Yandex\Entities;

class FioEntity extends AbstractEntity
{
    public function firstName(): ?string
    {
        return $this->entity['value']['first_name'] ?? null;
    }

    public function middleName(): ?string
    {
        return $this->entity['value']['first_name'] ?? null;
    }

    public function lastName(): ?string
    {
        return $this->entity['value']['first_name'] ?? null;
    }

    public function fullName(): string
    {
        return join(' ', array_filter([
            $this->firstName(),
            $this->middleName(),
            $this->lastName()])
        );
    }

    public function __toString(): string
    {
        return $this->fullName();
    }
}