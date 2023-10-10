<?php

namespace Alisa\Http;

use Alisa\Support\Container;
use Alisa\Yandex\Types\AudioPlayer\AudioPlayer;
use Alisa\Yandex\Types\Button;
use Alisa\Yandex\Types\Card\AbstractCard;

class Response
{
    protected Request $request;

    protected array $data = [
        'response' => [
            'text' => null,
            'end_session' => false,
        ],
        'version' => '1.0',
    ];

    public function __construct()
    {
        $this->request = Container::getInstance()->make(Request::class);
    }

    public function text(string $text): static
    {
        $this->data['response']['text'] = $text;

        return $this;
    }

    public function tts(string $tts): static
    {
        $this->data['response']['tts'] = $tts;

        return $this;
    }

    public function buttons(array $buttons): static
    {
        $this->data['response']['buttons'] = array_map(function (Button|array $button) {
            return $button instanceof Button ? $button->toArray() : $button;
        }, $buttons);

        return $this;
    }

    public function end(bool $value = true): static
    {
        $this->data['response']['end_session'] = $value;

        return $this;
    }

    public function card(AbstractCard $card): static
    {
        $this->data['response']['card'] = $card->toArray();

        return $this;
    }

    public function player(AudioPlayer $player): static
    {
        $this->data['response']['should_listen'] = $player->shouldListen();
        $this->data['response']['directives']['audio_player'] = $player->toArray();

        return $this;
    }

    public function __toString()
    {
        if (($session = $this->request->session()) && $session->count() > 0) {
            $this->data['session_state'] = $session->all();
        }

        if (($application = $this->request->application()) && $application->count() > 0) {
            $this->data['application_state'] = $application->all();
        }

        if (($user = $this->request->user()) && $user->count() > 0) {
            $this->data['user_state_update'] = $user->all();
        }

        if (!$this->data['response']['tts']) {
            unset($this->data['response']['tts']);
        }

        return json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}