<?php

namespace Alisa\Http;

use Alisa\Exceptions\RequestException;
use Alisa\Yandex\Entities\DatetimeEntity;
use Alisa\Yandex\Entities\FioEntity;
use Alisa\Yandex\Entities\GeoEntity;
use Alisa\Yandex\Entities\NumberEntity;
use Alisa\Yandex\Sessions\Application;
use Alisa\Yandex\Sessions\Session;
use Alisa\Yandex\Sessions\User;
use Alisa\Yandex\Entities\AbstractEntity;
use Alisa\Yandex\Entities\Entity;
use Alisa\Support\Collection;
use Alisa\Support\Container;

class Request
{
    protected Collection $data;

    /**
     * @param array|null $data Данные запроса в виде массива.
     */
    public function __construct(array $data = null)
    {
        if ($data) {
            $this->event($data);
        } else {
            $this->capture();
        }

        $this->bootstrap();
    }

    /**
     * @return void
     */
    protected function bootstrap(): void
    {
        $container = Container::getInstance();

        // https://yandex.ru/dev/dialogs/alice/doc/session-persistence.html#session-persistence__store-session
        $session = new Session($this->get('state.session', []));
        $container->singleton(Session::class, fn () => $session);
        $this->data->set('state.session', $session);

        // https://yandex.ru/dev/dialogs/alice/doc/session-persistence.html#session-persistence__store-application
        $application = new Application($this->get('state.application', []));
        $container->singleton(Application::class, fn () => $application);
        $this->data->set('state.application', $application);

        // https://yandex.ru/dev/dialogs/alice/doc/session-persistence.html#session-persistence__store-between-sessions
        $user = new User($this->get('state.user', []));
        $container->singleton(User::class, fn () => $user);
        $this->data->set('state.user', $user);

        // https://yandex.ru/dev/dialogs/alice/doc/naming-entities.html
        foreach ($this->data->get('request.nlu.entities', []) as $key => $entity) {
            $this->data->set('request.nlu.entities.'.$key, match ($entity['type']) {
                'YANDEX.FIO' => new FioEntity($entity),
                'YANDEX.GEO' => new GeoEntity($entity),
                'YANDEX.NUMBER' => new NumberEntity($entity),
                'YANDEX.DATETIME' => new DatetimeEntity($entity),
                default => new Entity($entity),
            });
        }
    }

    /**
     * Получить экземпляр сессии в рамках текущей сессии.
     *
     * @return Session
     */
    public function session(): Session
    {
        return $this->data->get('state.session');
    }

    /**
     * Получить экземпляр сесси поверхности где запущн навык.
     *
     * @return Application
     */
    public function application(): Application
    {
        return $this->data->get('state.application');
    }

    /**
     * Получить экземпляр сессии пользователя.
     *
     * Если пользователь не авторизован в Яндексе,
     * данные в классе `User` будут пустыми.
     *
     * @return User
     */
    public function user(): User
    {
        return $this->data->get('state.user');
    }

    /**
     * @param array $data
     * @return self
     */
    protected function event(array $data): self
    {
        $this->data = new Collection($data);

        return $this;
    }

    /**
     * @param array|null $data
     * @return self
     */
    protected function capture(?array $data = null): self
    {
        if ($data) {
            return $this->event($data);
        }

        $input = file_get_contents('php://input');

        if (!$input) {
            throw new RequestException('Запрос не содержит данных от Яндекс.Диалоги');
        }

        $this->data = new Collection(json_decode($input, true));

        return $this;
    }

    /**
     * Получить значение из запроса от Яндекс Диалогов.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data->get($key, $default);
    }

    /**
     * Вернуть запрос в виде массива.
     *
     * @return array
     */
    public function all(): array
    {
        return $this->data->all();
    }

    /**
     * Получить все сущности из запроса.
     *
     * @return Collection
     */
    public function entities(): Collection
    {
        return new Collection($this->get('request.nlu.entities', []));
    }

    /**
     * Получить конкретную сущность.
     *
     * @param string $type
     * @return array
     */
    public function entity(string $type): array
    {
        $result = [];

        /** @var AbstractEntity $entity */
        foreach ($this->get('request.nlu.entities', []) as $entity) {
            if ($entity->type() !== $type) {
                continue;
            }

            $result[] = $entity;
        }

        return $result;
    }

    /**
     * Получить все интенты из запроса.
     *
     * @return Collection
     */
    public function intents(): Collection
    {
        return new Collection($this->get('request.nlu.intents', []));
    }

    /**
     * Получить конкретный интент.
     *
     * @param string $id
     * @return Collection|null
     */
    public function intent(string $id): ?Collection
    {
        $arr = $this->get('request.nlu.intents.' . $id, null);

        return $arr ? new Collection($arr) : null;
    }

    /**
     * Это запрос с пингом от Диалогов?
     *
     * @return boolean
     */
    public function isPing(): bool
    {
        return
            $this->get('request.command') === '' &&
            $this->get('request.original_utterance') === 'ping' &&
            $this->get('request.type') === 'SimpleUtterance';
    }

    public function __toString(): string
    {
        return json_encode($this->all(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}