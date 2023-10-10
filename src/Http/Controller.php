<?php

namespace Alisa\Http;

use Alisa\Alisa;
use Alisa\Configuration;
use Alisa\Skill;
use Alisa\Support\Container;

class Controller
{
    protected Container $container;

    protected Request $request;

    protected Skill $skill;

    protected Alisa $alisa;

    protected Configuration $config;

    public function __construct()
    {
        $this->bootstrap();
    }

    protected function bootstrap(): void
    {
        $this->alisa = new Alisa;
        $this->container = Container::getInstance();
        $this->request = $this->container->make(Request::class);
        $this->skill = $this->container->make(Skill::class);
        $this->config = $this->container->make(Configuration::class);
    }
}