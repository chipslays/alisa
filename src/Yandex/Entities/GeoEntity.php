<?php

namespace Alisa\Yandex\Entities;

class GeoEntity extends AbstractEntity
{
    public function country(): ?string
    {
        return $this->entity['value']['country'] ?? null;
    }

    public function city(): ?string
    {
        return $this->entity['value']['city'] ?? null;
    }

    public function street(): ?string
    {
        return $this->entity['value']['street'] ?? null;
    }

    public function houseNumber(): ?string
    {
        return $this->entity['value']['house_number'] ?? null;
    }

    public function airport(): ?string
    {
        return $this->entity['value']['airport'] ?? null;
    }

    public function fullAddress(): ?string
    {
        $addressParts = [];

        if ($country = $this->country()) {
            $addressParts[] = $country;
        }

        if ($city = $this->city()) {
            $addressParts[] = $city;
        }

        if ($street = $this->street()) {
            $addressParts[] = $street;
        }

        if ($houseNumber = $this->houseNumber()) {
            $addressParts[] = $houseNumber;
        }

        if (empty($addressParts)) {
            return null;
        }

        return implode(', ', $addressParts);
    }

    public function __toString(): string
    {
        return (string) $this->fullAddress();
    }
}