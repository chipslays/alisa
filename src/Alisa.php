<?php

namespace Alisa;

use Alisa\Http\Request;
use Alisa\Http\Response;
use Alisa\Support\Storage;
use Alisa\Support\Container;
use Alisa\Support\Markup;
use Alisa\Yandex\Types\AudioPlayer\AudioPlayer;
use Alisa\Yandex\Types\Card\AbstractCard;

class Alisa
{
    protected Request $request;

    protected Storage $storage;

    protected Configuration $config;

    protected Container $container;

    public function __construct()
    {
        $this->container = Container::getInstance();
    }

    public function container(): Container
    {
        return $this->container;
    }

    public function request(): Request
    {
        if (!isset($this->request)) {
            $this->request = $this->container->make(Request::class);
        }

        return $this->request;
    }

    public function config(): Configuration
    {
        if (!isset($this->config)) {
            $this->config = $this->container->make(Configuration::class);
        }

        return $this->config;
    }

    public function storage(): Storage
    {
        if (!isset($this->request)) {
            $this->storage = $this->container->make(Storage::class);
        }

        return $this->storage;
    }

    public function enter(string $sceneName): self
    {
        $this->request()->session()->set('scene', $sceneName);

        return $this;
    }

    public function leave(): self
    {
        $this->request()->session()->remove('scene');

        return $this;
    }

    public function reply(string|array $text = '', ?string $tts = null, array $buttons = [], bool $end = false): void
    {
        if (is_array($text)) {
            $text = Markup::variant($text);
        }

        $processed = Markup::process([
            'text' => $text,
            'tts' => $tts ?? $text,
        ]);

        echo (new Response)
            ->text($processed['text'])
            ->tts($processed['tts'])
            ->buttons($buttons)
            ->end($end);
    }

    public function replyWith(AbstractCard|AudioPlayer $type, string|array $text = '', ?string $tts = null, bool $end = false): void
    {
        if (is_array($text)) {
            $text = Markup::variant($text);
        }

        $processed = Markup::process([
            'text' => $text,
            'tts' => $tts ?? $text,
        ]);

        $response = new Response;

        if ($type instanceof AbstractCard) {
            $response->card($type);
        }

        if ($type instanceof AudioPlayer) {
            $response->player($type);
        }

        echo $response
            ->text($processed['text'])
            ->tts($processed['tts'])
            ->end($end);
    }
}