<?php

namespace Alisa\Scenes;

use Alisa\Http\Request;
use Alisa\Routing\Router;
use Alisa\Support\Container;

class Scene
{
    use Router;

    protected Request $request;

    public function __construct()
    {
        $this->request = Container::getInstance()->make(Request::class);
    }
}