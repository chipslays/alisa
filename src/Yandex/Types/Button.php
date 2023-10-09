<?php

namespace Alisa\Yandex\Types;

class Button
{
    protected ?string $url = null;

    protected array $payload = [];

    protected bool $hide = true;

    public function __construct(protected string $title, protected ?string $action = null)
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

    public function hide(bool $hide = true): self
    {
        $this->hide = $hide;

        return $this;
    }

    public function toArray(): array
    {
        $button = [
            'title' => $this->title,
            'hide' => $this->hide,
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