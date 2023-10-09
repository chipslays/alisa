<?php

namespace Alisa\Yandex\Types\AudioPlayer;

class Stream
{
    protected array $stream = [
        'url' => null,
        'offset_ms' => 0,
        'token' => null,
    ];

    public function __construct(string $url, ?string $token = null, int $offsetMs = 0) {
        $this->stream = [
            'url' => $url,
            'token' => $token ?? md5($url),
            'offset_ms' => $offsetMs,
        ];
    }

    public function toArray(): array
    {
        return $this->stream;
    }
}