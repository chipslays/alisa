<?php

namespace Alisa\Http;

use Alisa\Configuration;
use Alisa\Context;
use Alisa\Skill;
use Alisa\Support\Container;

class Controller
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
}