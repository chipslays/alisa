<?php

namespace Alisa;

use Alisa\Http\Request;
use Alisa\Routing\Router;
use Alisa\Scenes\Scene;
use Alisa\Scenes\Stage;
use Alisa\Support\Storage;
use Alisa\Support\Container;
use \Closure;
use \Throwable;

use function Alisa\Support\Helpers\array_sort_by_priority;

class Skill
{
    use Router;

    protected Configuration $config;

    protected Request $request;

    protected Storage $storage;

    protected Container $container;

    protected array $onBeforeRunHandlers = [];

    protected array $onAfterRunHandlers = [];

    protected Closure|array|string|null $exceptionHandler = null;

    public function __construct(array $options = [])
    {
        $this->container = Container::getInstance();

        $this->config = new Configuration($options);

        $this->bootstrap();
    }

    protected function bootstrap(): void
    {
        $request = new Request($this->config()->get('event'));

        // https://yandex.ru/dev/dialogs/alice/doc/health-check.html
        if ($request->isPing()) {
            exit((new Alisa)->reply('pong', end: true));
        }

        $this->container->singleton(Configuration::class, fn () => $this->config);
        $this->container->singleton(Request::class, fn () => $this->request);
        $this->container->singleton(Storage::class, fn () => $this->storage);
        $this->container->singleton(__CLASS__, fn () => $this);

        $this->request = $request;
        $this->storage = new Storage;

        Asset::override($this->config()->get('assets', []));
    }

    public function onBeforeRun(Closure $handler, int $priority = 500): self
    {
        $this->onBeforeRunHandlers[$priority][] = $handler;

        return $this;
    }

    public function onAfterRun(Closure $handler, int $priority = 500): self
    {
        $this->onAfterRunHandlers[$priority][] = $handler;

        return $this;
    }

    public function container(): Container
    {
        return $this->container;
    }

    public function request(): Request
    {
        return $this->request;
    }

    public function config(): Configuration
    {
        return $this->config;
    }

    public function storage(): Storage
    {
        return $this->storage;
    }

    public function scene(string $name, Closure $callback): self
    {
        $scene = new Scene;

        Stage::add($name, $scene);

        call_user_func($callback, $scene);

        return $this;
    }

    public function onException(Closure|array|string|null $callback): self
    {
        $this->exceptionHandler = $callback;

        return $this;
    }

    public function run(): void
    {
        try {
            foreach (array_sort_by_priority($this->onBeforeRunHandlers) as $handler) {
                call_user_func($handler);
            }

            if ($scene = Stage::get($this->request()->session()->get('scene'))) {
                $matchedRoute = $scene->dispatch();
            } else {
                $matchedRoute = $this->dispatch();
            }

            foreach (array_sort_by_priority($this->onAfterRunHandlers) as $handler) {
                call_user_func($handler, $matchedRoute);
            }
        } catch (Throwable $th) {
            if ($this->exceptionHandler) {
                $this->fire($this->exceptionHandler, [$th, $this->request]);
            } else {
                throw $th;
            }
        }
    }

    public function runAsCloudFunction(): string
    {
        ob_start();

        $this->run();

        $response = ob_get_contents();

        ob_end_clean();

        return $response;
    }
}