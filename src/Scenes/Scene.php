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

    /**
     * @param string $name
     */
    public function __construct(protected string $name)
    {
        $this->container = Container::getInstance();
        $this->request = $this->container->make(Request::class);
    }

    /**
     * Получить название сцены.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Получить экземпляр контейнера.
     *
     * @return Container
     */
    public function container(): Container
    {
        return $this->container;
    }

    /**
     * Получить экземпляр запроса.
     *
     * @return Request
     */
    public function request(): Request
    {
        return $this->request;
    }

    /**
     * Получить экземпляр конфига.
     *
     * @return Configuration
     */
    public function config(): Configuration
    {
        if (!isset($this->config)) {
            $this->config = $this->container->make(Configuration::class);
        }

        return $this->config;
    }

    /**
     * Получить экземпляр локального хранилища.
     *
     * @return Storage
     */
    public function storage(): Storage
    {
        if (!isset($this->storage)) {
            $this->storage = $this->container->make(Storage::class);
        }

        return $this->storage;
    }
}