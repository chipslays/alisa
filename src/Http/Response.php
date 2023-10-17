<?php

namespace Alisa\Http;

use Alisa\Support\Buttons;
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

    /**
     * Установить текст ответа.
     *
     * @param string $text
     * @return static
     */
    public function text(string $text): static
    {
        $this->data['response']['text'] = $text;

        return $this;
    }

    /**
     * Установить TTS для ответа.
     *
     * @param string $tts
     * @return static
     */
    public function tts(string $tts): static
    {
        $this->data['response']['tts'] = $tts;

        return $this;
    }

    /**
     * Установить кнопки в ответе.
     *
     * @param array|string $buttons
     * @return static
     */
    public function buttons(array|string $buttons): static
    {
        if (is_string($buttons)) {
            $buttons = Buttons::get($buttons);
        }

        $this->data['response']['buttons'] = array_map(function (Button|array $button) {
            return $button instanceof Button ? $button->toArray() : $button;
        }, array_filter($buttons));

        return $this;
    }

    /**
     * Завершить сессию.
     *
     * @param boolean $value
     * @return static
     */
    public function end(bool $value = true): static
    {
        $this->data['response']['end_session'] = $value;

        return $this;
    }

    /**
     * Установить карточку в ответе.
     *
     * @param AbstractCard $card
     * @return static
     */
    public function card(AbstractCard $card): static
    {
        $this->data['response']['card'] = $card->toArray();

        return $this;
    }

    /**
     * Управление плеером.
     *
     * @param AudioPlayer $player
     * @return static
     */
    public function player(AudioPlayer $player): static
    {
        $this->data['response']['should_listen'] = $player->shouldListen();
        $this->data['response']['directives']['audio_player'] = $player->toArray();

        return $this;
    }

    /**
     * Вернуть ответ Диалогам в виде JSON.
     *
     * @return string
     */
    public function __toString(): string
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