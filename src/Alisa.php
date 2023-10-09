<?php

namespace Alisa;

use Alisa\Http\Request;
use Alisa\Http\Response;
use Alisa\Support\Markup;
use Alisa\Yandex\Types\AudioPlayer\AudioPlayer;
use Alisa\Yandex\Types\Card\AbstractCard;

class Alisa
{
    public function __construct(protected Request $request)
    {
        //
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function enter(string $sceneName): self
    {
        $this->request->session()->set('scene', $sceneName);

        return $this;
    }

    public function leave(): self
    {
        $this->request->session()->remove('scene');

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

        echo (new Response($this->request))
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

        $response = new Response($this->request);

        switch ($type::class) {
            case AbstractCard::class:
                $response->card($type);
                break;

            case AudioPlayer::class:
                $response->player($type);
                break;
        }

        echo $response
            ->text($processed['text'])
            ->tts($processed['tts'])
            ->end($end);
    }
}