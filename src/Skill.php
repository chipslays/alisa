<?php

namespace Alisa;

use Alisa\Http\Request;
use Alisa\Routing\Router;
use Alisa\Scenes\Scene;
use Alisa\Scenes\Stage;
use Alisa\Support\Container;
use \Closure;
use \Throwable;

use function Alisa\Support\Helpers\array_sort_by_priority;

class Skill
{
    use Router;

    protected Configuration $config;

    protected Request $request;

    protected array $onBeforeRunHandlers = [];

    protected array $onAfterRunHandlers = [];

    public function __construct(array $options = [])
    {
        $this->config = new Configuration($options);

        $this->bootstrap();
    }

    protected function bootstrap(): void
    {
        $request = new Request($this->config->get('event'));

        // https://yandex.ru/dev/dialogs/alice/doc/health-check.html
        if ($request->isPing()) {
            exit((new Alisa)->reply('pong', end: true));
        }

        $this->request = $request;

        $container = Container::getInstance();
        $container->singleton(Request::class, fn () => $request);
        $container->singleton(Configuration::class, fn () => $this->config);
        $container->singleton(__CLASS__, fn () => $this);
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

    public function request(string $key, mixed $default = null): mixed
    {
        return $this->request->get($key, $default);
    }

    public function config(): Configuration
    {
        return $this->config;
    }

    public function scene(string $name, Closure $callback): self
    {
        $scene = new Scene;

        Stage::add($name, $scene);

        call_user_func($callback, $scene);

        return $this;
    }

    public function run(?Closure $exceptionHandler = null): void
    {
        try {
            foreach (array_sort_by_priority($this->onBeforeRunHandlers) as $handler) {
                call_user_func($handler);
            }

            if ($scene = Stage::get($this->request->session()->get('scene'))) {
                $matchedRoute = $scene->dispatch();
            } else {
                $matchedRoute = $this->dispatch();
            }

            foreach (array_sort_by_priority($this->onAfterRunHandlers) as $handler) {
                call_user_func($handler, $matchedRoute);
            }
        } catch (Throwable $th) {
            if ($exceptionHandler) {
                $this->fire($exceptionHandler, [$th, $this->request]);
            } else {
                throw $th;
            }
        }
    }

    public function runAsCloudFunction(?Closure $exceptionHandler = null): string
    {
        ob_start();

        $this->run($exceptionHandler);

        $response = ob_get_contents();

        ob_end_clean();

        return $response;
    }
}