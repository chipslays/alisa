<?php

use Alisa\Alisa;
use Alisa\Skill;

require __DIR__ . '/../../vendor/autoload.php';

$skill = new Skill;

// Сообщение при новой сессии
$skill->onStart(function (Alisa $alisa) {
    $alisa->reply('Привет мир!');
});

// Ответ на любую команду
$skill->onFallback(function (Alisa $alisa) {
    // Получаем текст команды
    $text = $alisa->request()->get('request.command');

    // Применяем эффект "хомяка" для голоса
    $alisa->reply('{effect:hamster}' . $text);
});

$skill->run();