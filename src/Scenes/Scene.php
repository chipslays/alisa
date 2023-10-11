<?php

namespace Alisa\Scenes;

use Alisa\Configuration;
use Alisa\Http\Request;
use Alisa\Routing\Router;
use Alisa\Support\Container;
use Alisa\Support\Storage;

class Scene
{
    use Router;

    protected Request $request;

    protected Storage $storage;

    protected Configuration $config;

    protected Container $container;

    public function __construct(protected string $name)
    {
        $this->container = Container::getInstance();
        $this->request = $this->container->make(Request::class);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function container(): Container
    {
        return $this->container;
    }

    public function request(): Request
    {
        return $this->request;
    }

    public function config(): Configuration
    {
        if (!isset($this->config)) {
            $this->config = $this->container->make(Configuration::class);
        }

        return $this->config;
    }

    public function storage(): Storage
    {
        if (!isset($this->storage)) {
            $this->storage = $this->container->make(Storage::class);
        }

        return $this->storage;
    }
}