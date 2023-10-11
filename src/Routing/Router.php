<?php

namespace Alisa\Routing;

use Alisa\Context;
use Alisa\Http\Request;
use Alisa\Exceptions\RouterException;
use \Closure;

use function Alisa\Support\Helpers\array_sort_by_priority;

/**
 * @property Request $request
 */
trait Router
{
    protected array $routes = [];

    protected array $middleware = [];

    protected Closure|array|string|null $fallbackHandler = null;

    protected ?array $matchedRoute = null;

    protected array $groupMiddlewares = [];

    public function middleware(string|array $name, Closure|array|string|null $handler = null): self
    {
        if (is_array($name)) {
            $this->middleware = [...$this->middleware, ...$name];
        } else {
            $this->middleware[$name] = $handler;
        }

        return $this;
    }

    public function group(string|array $middleware, Closure $callback): self
    {
        $this->groupMiddlewares = (array) $middleware;

        call_user_func($callback, $this);

        $this->groupMiddlewares = [];

        return $this;
    }

    public function onStart(Closure|array|string $handler, string|array $middleware = []): self
    {
        $this->on(function (Request $request): bool {
            return
                $request->get('session.new') === true &&

                // только если комманда пустая,
                // чтобы не пропустить запрос вида: спроси у <навыка> что-нибудь
                in_array($request->get('request.command'), [null, ''], true);
        }, $handler, $middleware, -500);

        return $this;
    }

    public function onFallback(Closure|array|string|null $handler): self
    {
        $this->fallbackHandler = $handler;

        return $this;
    }

    public function onIntent(string|array $id, Closure|array|string $handler, string|array $middleware = []): self
    {
        $this->on(function (Request $request) use ($id): bool {
            return (bool) array_intersect((array) $id, array_keys(
                $request->get('request.nlu.intents', [])
            ));
        }, $handler, $middleware, -300);

        return $this;
    }

    public function onConfirm(Closure|array|string $handler, string|array $middleware = []): self
    {
        $this->onIntent('YANDEX.CONFIRM', $handler, $middleware, -300);

        return $this;
    }

    public function onReject(Closure|array|string $handler, string|array $middleware = []): self
    {
        $this->onIntent('YANDEX.REJECT', $handler, $middleware, -300);

        return $this;
    }

    public function onHelp(Closure|array|string $handler, string|array $middleware = []): self
    {
        $this->onIntent('YANDEX.HELP', $handler, $middleware, -300);

        return $this;
    }

    public function onRepeat(Closure|array|string $handler, string|array $middleware = []): self
    {
        $this->onIntent('YANDEX.REPEAT', $handler, $middleware, -300);

        return $this;
    }

    public function onWhatCanYouDo(Closure|array|string $handler, string|array $middleware = []): self
    {
        $this->onIntent('YANDEX.WHAT_CAN_YOU_DO', $handler, $middleware, -300);

        return $this;
    }

    public function on(Closure|array $pattern, Closure|array|string $handler, string|array $middleware = [], int $priority = 500): self
    {
        $middleware = [...$this->groupMiddlewares, ...(array) $middleware];

        $this->routes[$priority][] = compact('pattern', 'handler', 'middleware');

        return $this;
    }

    public function onCommand(string|array $command, Closure|array|string $handler, string|array $middleware = []): self
    {
        $this->on(function (Request $request) use ($command): bool|array {
            if (
                $request->get('request.type') == 'SimpleUtterance' &&
                $matches = $this->match($command, $request->get('request.command'))
            ) {
                return $matches;
            }

            return false;
        }, $handler, $middleware);

        return $this;
    }

    public function onAction(string|array $action, Closure|array|string $handler, string|array $middleware = []): self
    {
        $this->on(['request.payload.action' => $action], $handler, $middleware, -200);

        return $this;
    }

    public function onDangerous(Closure|array|string $handler, string|array $middleware = []): self
    {
        $this->on(['request.markup.dangerous_context' => true], $handler, $middleware, -400);

        return $this;
    }

    public function dispatch(): ?array
    {
        return $this->matchRoute();
    }

    public function matchRoute(): ?array
    {
        foreach (array_sort_by_priority($this->routes) as $route) {
            foreach ((array) $route['pattern'] as $pattern => $needles) {
                /**
                 * $router->on(function (Alisa\Alisa $alisa): bool|array { ... }, ...)
                 */
                if ($needles instanceof Closure) {
                    if ($matches = call_user_func($needles, $this->request)) {
                        if (is_array($matches)) {
                            $this->pipeline($route, $matches);
                        } else {
                            $this->pipeline($route);
                        }

                        break 2;
                    }

                    continue;
                }

                $haystack = $this->request->get($pattern);

                if (!$haystack) {
                    continue;
                }

                foreach ((array) $needles as $needle) {
                    if (($matches = $this->match($needle, $haystack)) !== null) {
                        $this->pipeline($route, $matches);
                        break 3;
                    }
                }
            }
        }

        if (!$this->matchedRoute && $this->fallbackHandler !== null) {
            $this->fire($this->fallbackHandler, [new Context]);
        }

        return $this->matchedRoute;
    }

    public function match($needle, $haystack): ?array
    {
        /**
         * $router->on(['pattern' => 'needle'], ...)
         */
        if ($needle === $haystack) {
            return [];
        }

        $pattern = preg_replace('~\s{\w+\?}~', '(?: (.*?))?', $needle);
        $pattern = '~^' . preg_replace('/{\w+}/', '(.*?)', $pattern) . '$~iu';

        if (@preg_match($pattern, $haystack, $matches)) {
            unset($matches[0]);

            return array_values($matches);
        }

        /**
         * $router->on(['pattern' => '/regex/iu'], ...)
         */
        if (@preg_match($needle, $haystack, $matches)) {
            unset($matches[0]);

            return array_values($matches);
        }

        return null;
    }

    protected function pipeline(array $route, array $parameters = []): bool
    {
        // добавляем в конец обработчик
        $route['middleware'][] = function () use ($route, $parameters) {
            $this->matchedRoute = $route;
            $this->fire($route, [new Context, ...$parameters]);
        };

        array_reduce(
            array_reverse($route['middleware']),
            function ($stack, $middleware) {
                return function () use ($stack, $middleware) {
                    if (is_string($middleware)) {
                        if (isset($this->middleware[$middleware])) {
                            $middleware = $this->middleware[$middleware];
                        } elseif (class_exists($middleware)) {
                            $middleware = new $middleware;
                        } else {
                            throw new RouterException("Middleware not exists: {$middleware}");
                        }
                    }

                    return $this->fire($middleware, [$stack, $this->request]);
                };
            }
        )();

        return $this->matchedRoute !== null;
    }

    protected function fire(Closure|array|string|callable $route, array $parameters = []): void
    {
        // из роута
        if (is_array($route) && isset($route['handler'])) {
            $handler = $route['handler'];
        } else {
            $handler = $route;
        }

        // on(NameController::class) __invoke() метод
        if (is_string($handler)) {
            $handler = new $handler;
        }

        // on([NameController::class]) __invoke() метод
        if (is_array($handler) && count($handler) === 1) {
            $handler = new $handler[0];
        }

        // on([NameController::class, 'methodName'])
        else if (is_array($handler) && array_is_list($handler) && count($handler) === 2) {
            $handler = [new $handler[0], $handler[1]];
        }

        call_user_func_array($handler, $parameters);
    }
}