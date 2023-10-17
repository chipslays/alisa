<?php

namespace Alisa\Routing;

use Alisa\Configuration;
use Alisa\Context;
use Alisa\Skill;
use Alisa\Http\Request;
use Alisa\Support\Container;
use \Closure;

abstract class Middleware
{
    protected Container $container;

    protected Request $request;

    protected Skill $skill;

    protected Context $ctx;

    protected Configuration $config;

    public function __construct()
    {
        $this->bootstrap();
    }

    /**
     * @return void
     */
    protected function bootstrap(): void
    {
        $this->ctx = new Context;
        $this->container = Container::getInstance();
        $this->request = $this->container->make(Request::class);
        $this->skill = $this->container->make(Skill::class);
        $this->config = $this->container->make(Configuration::class);
    }

    /**
     * @param Closure $next
     * @param Request $request
     * @return void
     */
    abstract public function __invoke(Closure $next, Request $request);
}