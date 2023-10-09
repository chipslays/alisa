<?php

namespace Alisa\Yandex\Types\AudioPlayer;

class Metadata
{
    protected array $meta = [
        'title' => null,
        'sub_title' => 0,
        'art' => [
            'url' => null,
        ],
        'background_image' => [
            'url' => null,
        ],
    ];

    public function __construct(string $title, string $artist, ?string $cover = null, ?string $background = null) {
        $this->meta = [
            'title' => $title,
            'sub_title' => $artist,
        ];

        if ($cover) {
            $this->meta['art'] = [
                'url' => $cover,
            ];
        }

        if ($background) {
            $this->meta['background_image'] = [
                'url' => $background,
            ];
        }
    }

    public function toArray(): array
    {
        return $this->meta;
    }
}