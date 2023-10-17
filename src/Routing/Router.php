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

    /**
     * Получить все установленные роуты.
     *
     * @return array
     */
    public function getRoutes(): array
    {
        return array_sort_by_priority($this->routes);
    }

    /**
     * Получить обработчик по-умолчанию.
     *
     * @return Closure|array|string|null
     */
    public function getFallbackHandler(): Closure|array|string|null
    {
        return $this->fallbackHandler;
    }

    /**
     * Зарегистрировать мидлвар для дальнейшего использования.
     *
     * @param string|array $name
     * @param Closure|array|string|null|null $handler
     * @return self
     */
    public function middleware(string|array $name, Closure|array|string|null $handler = null): self
    {
        if (is_array($name)) {
            $this->middleware = [...$this->middleware, ...$name];
        } else {
            $this->middleware[$name] = $handler;
        }

        return $this;
    }

    /**
     * Применить мидлвары для обработчиков внутри колбэка.
     *
     * @param string|array $middleware
     * @param Closure $callback
     * @return self
     */
    public function group(string|array $middleware, Closure $callback): self
    {
        $this->groupMiddlewares = (array) $middleware;

        call_user_func($callback, $this);

        $this->groupMiddlewares = [];

        return $this;
    }

    /**
     * Навык запущен.
     *
     * Сработает если `session.new === true`,
     * и значение `request.command` пустое.
     *
     * @param Closure|array|string $handler
     * @param array $middleware
     * @return self
     */
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

    /**
     * Установить обработчик по-умолчанию.
     *
     * Например, если пользователь сказал не то, что ожидалось.
     *
     * @param Closure|array|string|null $handler
     * @return self
     */
    public function onFallback(Closure|array|string|null $handler): self
    {
        $this->fallbackHandler = $handler;

        return $this;
    }

    /**
     * Обработка интентов по их ID.
     *
     * Например, мы создали интент в Диалогах с ID `turn.on`,
     * тогда этот ID нужно указывать здесь.
     *
     * Если в запросе приходит несколько интентов,
     * то вы можете дать `priority` для них.
     *
     * По-умолчанию все интенты имеют `priority` -300.
     *
     * @param string|array $id
     * @param Closure|array|string $handler
     * @param array $middleware
     * @param int $priority По-умолчанию `-300`.
     * @return self
     */
    public function onIntent(string|array $id, Closure|array|string $handler, string|array $middleware = [], int $priority = -300): self
    {
        $this->on(function (Request $request) use ($id): bool {
            return (bool) array_intersect((array) $id, array_keys(
                $request->get('request.nlu.intents', [])
            ));
        }, $handler, $middleware, $priority);

        return $this;
    }

    /**
     * При подтверждении, соглассии и т.п.
     *
     * @param Closure|array|string $handler
     * @param array $middleware
     * @return self
     */
    public function onConfirm(Closure|array|string $handler, string|array $middleware = []): self
    {
        $this->onIntent('YANDEX.CONFIRM', $handler, $middleware, -300);

        return $this;
    }

    /**
     * При отказе, не согласии и т.п.
     *
     * @param Closure|array|string $handler
     * @param array $middleware
     * @return self
     */
    public function onReject(Closure|array|string $handler, string|array $middleware = []): self
    {
        $this->onIntent('YANDEX.REJECT', $handler, $middleware, -300);

        return $this;
    }

    /**
     * Запрос помощи от пользователя.
     *
     * @param Closure|array|string $handler
     * @param array $middleware
     * @return self
     */
    public function onHelp(Closure|array|string $handler, string|array $middleware = []): self
    {
        $this->onIntent('YANDEX.HELP', $handler, $middleware, -300);

        return $this;
    }

    /**
     * Пользователь просит повторить ответ.
     *
     * @param Closure|array|string $handler
     * @param array $middleware
     * @return self
     */
    public function onRepeat(Closure|array|string $handler, string|array $middleware = []): self
    {
        $this->onIntent('YANDEX.REPEAT', $handler, $middleware, -300);

        return $this;
    }

    /**
     * При запросе "что ты умеешь?".
     *
     * @param Closure|array|string $handler
     * @param array $middleware
     * @return self
     */
    public function onWhatCanYouDo(Closure|array|string $handler, string|array $middleware = []): self
    {
        $this->onIntent('YANDEX.WHAT_CAN_YOU_DO', $handler, $middleware, -300);

        return $this;
    }

    /**
     * Универсальный обработчик запроса.
     *
     * @param Closure|array $pattern
     * @param Closure|array|string $handler
     * @param array $middleware
     * @param integer $priority
     * @return self
     */
    public function on(Closure|array $pattern, Closure|array|string $handler, string|array $middleware = [], int $priority = 500): self
    {
        $middleware = [...$this->groupMiddlewares, ...(array) $middleware];

        $this->routes[$priority][] = compact('pattern', 'handler', 'middleware');

        return $this;
    }

    /**
     * Обработка комманды от пользователя.
     *
     * Только если `request.type` имеет значение `SimpleUtterance`.
     *
     * @param string|array $command
     * @param Closure|array|string $handler
     * @param array $middleware
     * @return self
     */
    public function onCommand(string|array $command, Closure|array|string $handler, string|array $middleware = []): self
    {
        $this->on(function (Request $request) use ($command): bool|array {
            // вернуть массив параметров, они будут переданы хендлер
            // вернуть false тогда проверку не пройдет

            $matches = $this->match($command, $request->get('request.command'));

            if ($request->get('request.type') == 'SimpleUtterance' && $matches !== null) {
                return $matches;
            }

            return false;
        }, $handler, $middleware);

        return $this;
    }

    /**
     * Пользователь нажал на кнопку,
     * сработает только если в ранее переданной кнопке был указан параметр `action`.
     *
     * @param string|array $action
     * @param Closure|array|string $handler
     * @param array $middleware
     * @return self
     */
    public function onAction(string|array $action, Closure|array|string $handler, string|array $middleware = []): self
    {
        $this->on(['request.payload.action' => $action], $handler, $middleware, -200);

        return $this;
    }

    /**
     * Признак реплики, которая содержит криминальный подтекст
     * (самоубийство, разжигание ненависти, угрозы).
     *
     * @param Closure|array|string $handler
     * @param array $middleware
     * @return self
     */
    public function onDangerous(Closure|array|string $handler, string|array $middleware = []): self
    {
        $this->on(['request.markup.dangerous_context' => true], $handler, $middleware, -400);

        return $this;
    }

    /**
     * @return void
     */
    public function dispatch(): void
    {
        $this->matchRoute();
    }

    /**
     * @return void
     */
    protected function matchRoute(): void
    {
        foreach (array_sort_by_priority($this->routes) as $index => $route) {
            foreach ((array) $route['pattern'] as $pattern => $needles) {
                /**
                 * $router->on(function (Alisa\Alisa $alisa): bool|array { ... }, ...)
                 */
                if ($needles instanceof Closure) {
                    $matches = call_user_func($needles, $this->request);
                    if ($matches !== false) {
                        if (is_array($matches)) {
                            $this->pipeline($index, $route, $matches);
                        } else {
                            $this->pipeline($index, $route);
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
                        $this->pipeline($index, $route, $matches);
                        break 3;
                    }
                }
            }
        }

        if (!$this->matchedRoute && $this->fallbackHandler !== null) {
            $repeatStr = ':-1#';
            $this->request()->session()->set('repeat', $repeatStr);
            $this->fire($this->fallbackHandler, [new Context]);
        }
    }

    /**
     * @param string $needle
     * @param string $haystack
     * @return array|null
     */
    public function match(string $needle, string $haystack): ?array
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

    /**
     * @param integer $index
     * @param array $route
     * @param array $parameters
     * @return boolean
     */
    protected function pipeline(int $index, array $route, array $parameters = []): bool
    {
        // добавляем в конец обработчик
        $route['middleware'][] = function () use ($index, $route, $parameters) {
            // че с ним делать куда зачем пусть будет пока
            $this->matchedRoute = $route;
            $this->matchedRoute['index'] = $index; // индекс роута из массива
            $this->matchedRoute['scene'] = isset($this->name) ?: null; // name есть только у Scene класса

            $repeatStr = $this->matchedRoute['scene'] . ':' . $this->matchedRoute['index'] . '#' . implode('&&', $parameters);
            $this->request()->session()->set('repeat', $repeatStr);

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

    /**
     * @param Closure|array|string|callable $route
     * @param array $parameters
     * @return void
     */
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