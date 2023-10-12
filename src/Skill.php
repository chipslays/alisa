<?php

namespace Alisa;

use Alisa\Exceptions\SkillException;
use Alisa\Http\Request;
use Alisa\Routing\Router;
use Alisa\Scenes\Scene;
use Alisa\Scenes\Stage;
use Alisa\Support\Asset;
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

    protected array $globalMiddlewares = [];

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
            exit((new Context)->reply('pong', end: true));
        }

        $this->container->singleton(Configuration::class, fn () => $this->config);
        $this->container->singleton(Request::class, fn () => $this->request);
        $this->container->singleton(Storage::class, fn () => $this->storage);
        $this->container->singleton(__CLASS__, fn () => $this);

        $this->request = $request;
        $this->storage = new Storage;

        Asset::override($this->config()->get('assets', []));

        $this->globalMiddlewares = $this->config()->get('middlewares', []);
    }

    protected function canAutoRepeat(): ?array
    {
        if (!$this->config->get('auto_repeat')) {
            return null;
        }

        if (!$repeat = $this->request->session()->get('repeat')) {
            return null;
        }

        $intents = $this->request->get('request.nlu.intents', []);

        if (!array_key_exists('YANDEX.REPEAT', $intents)) {
            return null;
        }

        $parameters = explode('#', $repeat); // e.g. "sceneName:0#param1&&param2"
        [$sceneName, $index] = explode(':', array_shift($parameters));

        if ($index == '-1') {
            if ($sceneName !== '') {
                if ($scene = Stage::get($sceneName)) {
                    $handler = $scene->getFallbackHandler();
                }
            } else {
                $handler = $this->getFallbackHandler();
            }

            if (!$handler) {
                return null;
            }

            return [
                'handler' => $handler,
                'parameters' => array_filter(explode('&&', implode('#', $parameters)), fn ($item) => $item !== ''),
            ];
        } else {
            if ($sceneName !== '') {
                if ($scene = Stage::get($sceneName)) {
                    $route = $scene->getRoutes()[$index];
                }
            } else {
                $route = $this->getRoutes()[$index] ?? null;
            }

            if (!$route) {
                return null;
            }

            return [
                'handler' => $route['handler'],
                'parameters' => array_filter(explode('&&', implode('#', $parameters)), fn ($item) => $item !== ''),
            ];
        }

    }

    public function onBeforeRun(Closure|array|string $handler, int $priority = 500): self
    {
        $this->onBeforeRunHandlers[$priority][] = $handler;

        return $this;
    }

    public function onAfterRun(Closure|array|string $handler, int $priority = 500): self
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
        $scene = new Scene($name);

        Stage::add($scene);

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
            array_reduce(
                array_reverse($this->globalMiddlewares),
                function ($stack, $middleware) {
                    return function () use ($stack, $middleware) {
                        if (is_string($middleware)) {
                            if (isset($this->middleware[$middleware])) {
                                $middleware = $this->middleware[$middleware];
                            } elseif (class_exists($middleware)) {
                                $middleware = new $middleware;
                            } else {
                                throw new SkillException("Global middleware not exists: {$middleware}");
                            }
                        }
                        return $this->fire($middleware, [$stack, $this->request]);
                    };
                },
                fn () => $this->runExecute()
            )();
        } catch (Throwable $th) {
            if ($this->exceptionHandler) {
                $this->fire($this->exceptionHandler, [$th, $this->request]);
            } else {
                throw $th;
            }
        }
    }

    protected function runExecute()
    {
        foreach (array_sort_by_priority($this->onBeforeRunHandlers) as $handler) {
            $this->fire($handler);
        }

        if ($repeatData = $this->canAutoRepeat()) {
            $this->fire($repeatData['handler'], [new Context, ...$repeatData['parameters']]);
        } else {
            if ($scene = Stage::get($this->request()->session()->get('scene'))) {
                $scene->dispatch();
            } else {
                $this->dispatch();
            }
        }

        foreach (array_sort_by_priority($this->onAfterRunHandlers) as $handler) {
            $this->fire($handler);
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