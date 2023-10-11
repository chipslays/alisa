<?php

namespace Alisa\Yandex;

use Alisa\Configuration;
use Alisa\Exceptions\ImageException;
use Alisa\Support\Container;
use CURLFile;
use CurlHandle;

class Image
{
    protected CurlHandle $httpClient;

    protected Configuration $config;

    protected string $host = 'https://dialogs.yandex.net';

    protected ?string $token = null;

    protected ?string $skillId = null;

    public function __construct()
    {
        $this->config = Container::getInstance()->make(Configuration::class);

        if (!$this->token = $this->config->get('token')) {
            throw new ImageException;
        }

        $this->skillId = $this->config->get('skill_id');

        $this->httpClient = curl_init();
        curl_setopt($this->httpClient, CURLOPT_RETURNTRANSFER, true);
    }

    public function status(): array
    {
        curl_setopt($this->httpClient, CURLOPT_URL, $this->host . '/api/v1/status');
        curl_setopt($this->httpClient, CURLOPT_HTTPHEADER, [
            'Authorization: OAuth ' . $this->token,
        ]);

        $response = curl_exec($this->httpClient);

        return $this->handle($response);
    }

    public function all(): array
    {
        curl_setopt($this->httpClient, CURLOPT_URL, $this->host . '/api/v1/skills/' . $this->skillId . '/images');
        curl_setopt($this->httpClient, CURLOPT_HTTPHEADER, [
            'Authorization: OAuth ' . $this->token,
        ]);

        $response = curl_exec($this->httpClient);

        return $this->handle($response);
    }

    public function delete(string $id): array
    {
        curl_setopt($this->httpClient, CURLOPT_URL, $this->host . '/api/v1/skills/' . $this->skillId . '/images/' . $id);
        curl_setopt($this->httpClient, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($this->httpClient, CURLOPT_HTTPHEADER, [
            'Authorization: OAuth ' . $this->token,
        ]);

        $response = curl_exec($this->httpClient);

        return $this->handle($response);
    }

    public function upload(string $image): array
    {
        if (!$this->skillId) {
            throw new ImageException('Заполните в конфиге идентификатор навыка (skill_id)');
        }

        if (!file_exists($image)) {
            return $this->uploadByUrl($image);
        }

        return $this->uploadByFile($image);
    }

    protected function uploadByUrl(string $url): array
    {
        $payload = json_encode(compact('url'));

        curl_setopt($this->httpClient, CURLOPT_URL, $this->host . '/api/v1/skills/' . $this->skillId . '/images');
        curl_setopt($this->httpClient, CURLOPT_POST, true);
        curl_setopt($this->httpClient, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($this->httpClient, CURLOPT_HTTPHEADER, [
            'Authorization: OAuth ' . $this->token,
            'Content-Type: application/json',
        ]);

        $response = curl_exec($this->httpClient);

        return $this->handle($response);
    }

    protected function uploadByFile(string $file): array
    {
        $payload = ['file' => new CURLFile($file)];

        curl_setopt($this->httpClient, CURLOPT_URL, $this->host . '/api/v1/skills/' . $this->skillId . '/images');
        curl_setopt($this->httpClient, CURLOPT_POST, true);
        curl_setopt($this->httpClient, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($this->httpClient, CURLOPT_HTTPHEADER, [
            'Authorization: OAuth ' . $this->token,
            'Content-Type: multipart/form-data',
        ]);

        $response = curl_exec($this->httpClient);

        return $this->handle($response);
    }

    protected function handle(string $response): array
    {
        return json_decode($response, true);
    }
}