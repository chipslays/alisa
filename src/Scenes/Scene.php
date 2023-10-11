<?php

namespace Alisa\Scenes;

use Alisa\Http\Request;
use Alisa\Routing\Router;
use Alisa\Support\Container;

class Scene
{
    use Router;

    protected Request $request;

    public function __construct(protected string $name)
    {
        $this->request = Container::getInstance()->make(Request::class);
    }

    public function getName(): string
    {
        return $this->name;
    }
}