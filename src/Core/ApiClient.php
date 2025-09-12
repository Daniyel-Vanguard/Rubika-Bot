<?php

namespace RubikaBot\Core;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use RubikaBot\Exceptions\ApiException;

class ApiClient
{
    private Client $client;
    private string $baseUrl;
    private array $config;
    private array $lastResponse = [];

    public function __construct(string $token, array $config)
    {
        $this->baseUrl = "https://botapi.rubika.ir/v3/{$token}/";
        $this->config = $config;
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => $config['timeout'] ?? 30,
        ]);
    }

    public function request(string $method, array $params = []): array
    {
        $retry = 0;
        while ($retry < ($this->config['max_retries'] ?? 3)) {
            try {
                $response = $this->client->post($method, [
                    'json' => $params,
                    'headers' => ['Content-Type' => 'application/json'],
                ]);
                $this->lastResponse = json_decode($response->getBody()->getContents(), true) ?? [];
                return $this->lastResponse;
            } catch (RequestException $e) {
                $retry++;
                if ($retry === $this->config['max_retries']) {
                    throw new ApiException("API request failed: {$e->getMessage()}", $e->getCode(), $e);
                }
                sleep(1);
            }
        }
        return ['ok' => false, 'error' => 'Request failed'];
    }

    public function uploadFile(string $url, string $file_path): string
    {
        $mime_type = mime_content_type($file_path);
        $filename = basename($file_path);
        try {
            $response = $this->client->post($url, [
                'multipart' => [
                    [
                        'name' => 'file',
                        'contents' => fopen($file_path, 'r'),
                        'filename' => $filename,
                        'headers' => ['Content-Type' => $mime_type],
                    ],
                ],
            ]);
            $data = json_decode($response->getBody()->getContents(), true);
            if (!isset($data['data']['file_id'])) {
                throw new ApiException("No file_id returned from upload: " . json_encode($data));
            }
            return $data['data']['file_id'];
        } catch (RequestException $e) {
            throw new ApiException("File upload failed: {$e->getMessage()}", $e->getCode(), $e);
        }
    }

    public function getLastResponse(): array
    {
        return $this->lastResponse;
    }
}
