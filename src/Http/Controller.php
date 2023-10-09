<?php

namespace Alisa\Http;

use Alisa\Skill;
use Alisa\Support\Container;

class Controller
{
    protected Request $request;

    protected Skill $skill;

    public function __construct()
    {
        $this->bootstrap();
    }

    protected function bootstrap(): void
    {
        $container = Container::getInstance();

        $this->request = $container->make(Request::class);
        $this->skill = $container->make(Skill::class);
    }
}