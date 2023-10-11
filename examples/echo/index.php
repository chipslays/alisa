<?php

use Alisa\Context;
use Alisa\Skill;

require __DIR__ . '/../../vendor/autoload.php';

$skill = new Skill;

// Сообщение при новой сессии
$skill->onStart(function (Context $ctx) {
    $ctx->reply('Привет мир!');
});

// Ответ на любую команду
$skill->onFallback(function (Context $ctx) {
    // Получаем текст команды
    $text = $ctx->request()->get('request.command');

    // Применяем эффект "хомяка" для голоса
    $ctx->reply('{effect:hamster}' . $text);
});

$skill->run();