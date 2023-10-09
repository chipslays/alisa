<?php

namespace Alisa\Yandex\Types\Card;

use Alisa\Asset;

class ImageGallery extends AbstractCard
{
    protected array $card = [
        'type' => 'ImageGallery',
        'items' => [],
    ];

    public function add(string $imageId, string $title, ?Button $button = null): self
    {
        $this->card['items'][] = [
            'image_id' => Asset::get($imageId) ?? $imageId,
            'title' => $title,
            'button' => $button?->toArray() ?? [],
        ];

        return $this;
    }
}