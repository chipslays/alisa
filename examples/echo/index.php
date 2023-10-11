<?php

use Alisa\Alisa;
use Alisa\Skill;

require __DIR__ . '/../../vendor/autoload.php';

$skill = new Skill;

// Сообщение при новой сессии
$skill->onStart(function (Alisa $alisa) {
    $alisa->reply('Привет мир!');
});

// Ответ на любую комманду
$skill->onFallback(function (Alisa $alisa) {
    // Получаем текст комманды
    $text = $alisa->request()->get('request.command');

    // Применяем эффект "хомяка" для голоса
    $alisa->reply('{effect:hamster}' . $text);
});

$skill->run();