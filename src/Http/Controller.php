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
        $this->container = Container::getInstance();
        $this->alisa = new Alisa;
    }

    public function __get(mixed $name): mixed
    {
        switch ($name) {
            case 'request':
                if (!isset($this->request)) {
                    $this->request = $this->container->make(Request::class);
                }
                break;

            case 'skill':
                if (!isset($this->skill)) {
                    $this->skill = $this->container->make(Skill::class);
                }
                break;

            case 'config':
                if (!isset($this->config)) {
                    $this->config = $this->container->make(Configuration::class);
                }
                break;
        }

        return $this->{$name};
    }
}