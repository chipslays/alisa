# Alisa

Alisa — это библиотека для разработки навыков для голосового помощника Яндекс Алиса.

## Установка

```bash
composer require alisa/alisa:@beta
```

## Примеры

Простой echo скилл в виде хомяка-повторюшки:

```php
$skill = new Alisa\Skill;

$skill->onStart(function (Alisa\Context $ctx) {
    $ctx->reply('{effect:hamster} Привет, я хомяк-повторюшка!');
});

$skill->onFallback(function (Alisa\Context $ctx) {
    $ctx->reply('{effect:hamster}' . $ctx->request()->get('request.command'));
});

$skill->run();
```

## Документация

Сейчас библиотека обкатывается, документация и примеры будут после обкатки.

## Лицензия

MIT
