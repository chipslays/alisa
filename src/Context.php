<?php

namespace Alisa;

use Alisa\Http\Request;
use Alisa\Http\Response;
use Alisa\Support\Storage;
use Alisa\Support\Container;
use Alisa\Support\Markup;
use Alisa\Yandex\Types\AudioPlayer\AudioPlayer;
use Alisa\Yandex\Types\Card\AbstractCard;

class Context
{
    protected Request $request;

    protected Storage $storage;

    protected Configuration $config;

    protected Container $container;

    public function __construct()
    {
        $this->container = Container::getInstance();
    }

    /**
     * Получить экземпляр контейнера.
     *
     * @return Container
     */
    public function container(): Container
    {
        return $this->container;
    }

    /**
     * Получить экземпляр запроса.
     *
     * @return Request
     */
    public function request(): Request
    {
        if (!isset($this->request)) {
            $this->request = $this->container->make(Request::class);
        }

        return $this->request;
    }

    /**
     * Получить экземпляр конфига.
     *
     * @return Configuration
     */
    public function config(): Configuration
    {
        if (!isset($this->config)) {
            $this->config = $this->container->make(Configuration::class);
        }

        return $this->config;
    }

    /**
     * Получить экземпляр локального хранилища.
     *
     * @return Storage
     */
    public function storage(): Storage
    {
        if (!isset($this->storage)) {
            $this->storage = $this->container->make(Storage::class);
        }

        return $this->storage;
    }

    /**
     * Перейти к сцене и обрабатывать последущие запросы в этой сцене.
     *
     * @param string $sceneName
     * @return self
     */
    public function enter(string $sceneName): self
    {
        $this->request()->session()->set('scene', $sceneName);

        return $this;
    }

    /**
     * Покинуть сцену.
     *
     * @return self
     */
    public function leave(): self
    {
        $this->request()->session()->remove('scene');

        return $this;
    }

    /**
     * Простой ответ.
     *
     * @param string $text
     * @param string|null $tts
     * @param array $buttons
     * @param boolean $end
     * @return void
     */
    public function reply(string|array $text = '', ?string $tts = null, array|string $buttons = [], bool $end = false): void
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

    /**
     * Ответ с изображениями или управление аудиоплеером.
     *
     * @param AbstractCard|AudioPlayer $type
     * @param string $text
     * @param string|null $tts
     * @param boolean $end
     * @return void
     */
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