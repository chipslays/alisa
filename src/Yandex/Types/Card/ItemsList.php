<?php

namespace Alisa\Yandex\Types\Card;

use Alisa\Asset;

class ItemsList extends AbstractCard
{
    protected array $card = [
        'type' => 'ItemsList',
        'items' => [],
    ];

    public function header(string $text): self
    {
        $this->card['header']['text'] = $text;

        return $this;
    }

    public function add(string $imageId, ?string $title = null, ?string $description = null, ?Button $button = null): self
    {
        $this->card['items'][] = [
            'image_id' => Asset::get($imageId) ?? $imageId,
            'title' => $title,
            'description' => $description,
            'button' => $button?->toArray() ?? [],
        ];

        return $this;
    }

    public function footer(string $text, ?Button $button = null): self
    {
        $this->card['footer']['text'] = $text;

        if ($button) {
            $this->card['footer']['button'] = $button->toArray();
        }

        return $this;
    }
}