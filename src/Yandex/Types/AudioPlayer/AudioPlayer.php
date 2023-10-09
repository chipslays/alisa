<?php

namespace Alisa\Yandex\Types\AudioPlayer;

class AudioPlayer
{
    protected array $directive = [
        'action' => null,
    ];

    protected bool $shouldListen = false;

    public function play(Stream|string $stream, Metadata $meta, bool $shouldListen = false): self
    {
        $this->shouldListen = $shouldListen;

        $this->directive['action'] = 'Play';

        $this->directive['item'] = [
            'stream' => $stream instanceof Stream ? $stream->toArray() : (new Stream($stream))->toArray(),
            'metadata' => $meta->toArray(),
        ];

        return $this;
    }

    public function stop(): self
    {
        $this->directive['action'] = 'Stop';

        return $this;
    }

    public function shouldListen(): bool
    {
        return $this->shouldListen;
    }

    public function toArray(): array
    {
        return $this->directive;
    }
}