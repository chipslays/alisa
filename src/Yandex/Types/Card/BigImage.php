<?php

namespace Alisa\Yandex\Types\Card;

use Alisa\Support\Asset;

class BigImage extends AbstractCard
{
    protected array $card = [
        'type' => 'BigImage',
        'image_id' => null,
        'title' => null,
        'description' => null,
        'button' => [],
    ];

    public function __construct(string $imageId, ?string $title = null, ?string $description = null, ?Button $button = null)
    {
        if ($button) {
            $this->button($button);
        }

        $this
            ->image(Asset::get($imageId) ?? $imageId)
            ->title($title)
            ->description($description);
    }

    public function image(string $imageId): self
    {
        $this->card['image_id'] = Asset::get($imageId) ?? $imageId;

        return $this;
    }

    public function title(?string $title = null): self
    {
        $this->card['title'] = $title;

        return $this;
    }

    public function description(?string $description = null): self
    {
        $this->card['description'] = $description;

        return $this;
    }

    public function button(Button|string $text, ?string $url = null, array $payload = [], ?string $action = null): self
    {
        $this->card['button'] = $text instanceof Button
            ? $text->toArray()
            : (new Button($text, $action))
                ->payload($payload)
                ->url($url)
                ->toArray();

        return $this;
    }
}