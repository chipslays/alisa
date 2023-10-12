<?php

namespace Alisa\Yandex;

use Alisa\Configuration;
use Alisa\Exceptions\ImageException;
use Alisa\Http\Request;
use Alisa\Skill;
use Alisa\Support\Container;
use \CURLFile;
use \CurlHandle;

class Image
{
    protected string $host = 'https://dialogs.yandex.net';

    protected ?string $token;

    protected ?string $skillId;

    protected string $path;

    protected CurlHandle $httpClient;

    public function __construct()
    {
        $config = Container::getInstance()->make(Configuration::class);

        if (!$this->token = $config->get('token')) {
            throw new ImageException('Заполните в конфиге OAuth-токен (token)');
        }

        if (!$this->skillId = $config->get('skill_id')) {
            /** @var Request */
            $request = Container::getInstance()->make(Request::class);

            if (!$this->skillId = $request->get('session.skill_id')) {
                throw new ImageException('Заполните в конфиге идентификатор навыка (skill_id)');
            }
        }

        $this->path = rtrim($config->get('images', sys_get_temp_dir() . '/alisa/' . $this->skillId . '/images'), '\/');

        if (!file_exists($this->path)) {
            mkdir($this->path, recursive: true);
        }

        $this->httpClient = curl_init();
        curl_setopt($this->httpClient, CURLOPT_RETURNTRANSFER, true);
    }

    public function status(): array
    {
        $endpoint = $this->host . '/api/v1/status';

        curl_setopt($this->httpClient, CURLOPT_URL, $endpoint);
        curl_setopt($this->httpClient, CURLOPT_HTTPHEADER, [
            'Authorization: OAuth ' . $this->token,
        ]);

        $response = curl_exec($this->httpClient);

        return $this->handle($response);
    }

    public function all(): array
    {
        $endpoint = $this->host . '/api/v1/skills/' . $this->skillId . '/images';

        curl_setopt($this->httpClient, CURLOPT_URL, $endpoint);
        curl_setopt($this->httpClient, CURLOPT_HTTPHEADER, [
            'Authorization: OAuth ' . $this->token,
        ]);

        $response = curl_exec($this->httpClient);

        return $this->handle($response);
    }

    public function delete(string $id): array
    {
        $endpoint = $this->host . '/api/v1/skills/' . $this->skillId . '/images/' . $id;

        curl_setopt($this->httpClient, CURLOPT_URL, $endpoint);
        curl_setopt($this->httpClient, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($this->httpClient, CURLOPT_HTTPHEADER, [
            'Authorization: OAuth ' . $this->token,
        ]);

        $response = curl_exec($this->httpClient);

        return $this->handle($response);
    }

    public function upload(string $image, bool $cache = true): ?string
    {
        // Пробуем достать картинку из кеша
        if ($cache && $response = $this->retrieve($image)) {
            return $response;
        }

        if (!file_exists($image)) {
            $response = $this->uploadByUrl($image);
        } else {
            $response = $this->uploadByFile($image);
        }

        if (!isset($response['image']['id'])) {
            return null;
        }

        $imageId = $response['image']['id'];

        // Кешируем картинку
        if ($cache) {
            file_put_contents($this->path . '/' . md5($image), $imageId, LOCK_EX);
        }

        return $imageId;
    }

    public function once(string $image, bool $cache = true): ?string
    {
        $id = $this->upload($image, $cache);

        if (!$id) {
            return null;
        }

        /** @var Skill $skill */
        $skill = Container::getInstance()->make(Skill::class);

        $skill->onAfterRun(function () use ($id, $image, $cache) {
            $this->delete($id);

            if ($cache) {
                $this->forget($image);
            }
        });

        return $id;
    }

    public function uploadByUrl(string $url): array
    {
        $endpoint = $this->host . '/api/v1/skills/' . $this->skillId . '/images';

        $payload = json_encode(compact('url'));

        curl_setopt($this->httpClient, CURLOPT_URL, $endpoint);
        curl_setopt($this->httpClient, CURLOPT_POST, true);
        curl_setopt($this->httpClient, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($this->httpClient, CURLOPT_HTTPHEADER, [
            'Authorization: OAuth ' . $this->token,
            'Content-Type: application/json',
        ]);

        $response = curl_exec($this->httpClient);

        return $this->handle($response);
    }

    public function uploadByFile(string $file): array
    {
        $endpoint = $this->host . '/api/v1/skills/' . $this->skillId . '/images';

        $payload = ['file' => new CURLFile($file)];

        curl_setopt($this->httpClient, CURLOPT_URL, $endpoint);
        curl_setopt($this->httpClient, CURLOPT_POST, true);
        curl_setopt($this->httpClient, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($this->httpClient, CURLOPT_HTTPHEADER, [
            'Authorization: OAuth ' . $this->token,
            'Content-Type: multipart/form-data',
        ]);

        $response = curl_exec($this->httpClient);

        return $this->handle($response);
    }

    public function retrieve(string $image): ?string
    {
        return @file_get_contents($this->path . '/' . md5($image));
    }

    public function forget(string $image): self
    {
        unlink($this->path . '/' . md5($image));

        return $this;
    }

    protected function handle(string $response, ?string $image = null): array
    {
        return json_decode($response, true);
    }
}