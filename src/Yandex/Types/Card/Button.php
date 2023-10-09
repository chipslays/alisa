<?php

namespace Alisa\Yandex\Types\Card;

class Button
{
    protected ?string $url = null;

    protected array $payload = [];

    public function __construct(protected string $text = '', protected ?string $action = null)
    {
        //
    }

    public function action(string $action): self
    {
        $this->action = $action;

        return $this;
    }

    public function url(?string $url = null): self
    {
        $this->url = $url;

        return $this;
    }

    public function payload(array $payload): self
    {
        $this->payload = $payload;

        return $this;
    }

    public function toArray(): array
    {
        $button = [
            'text' => $this->text,
        ];

        if ($this->url) {
            $button['url'] = $this->url;
        }

        $payload = $this->payload;

        if ($this->action) {
            $payload['action'] = $this->action;
        }

        if ($payload) {
            $button['payload'] = $payload;
        }

        return $button;
    }
}