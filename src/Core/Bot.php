<?php

namespace RubikaBot\Core;

use RubikaBot\Builders\MessageBuilder;
use RubikaBot\Builders\FileBuilder;
use RubikaBot\Builders\PollBuilder;
use RubikaBot\Exceptions\ApiException;
use RubikaBot\Exceptions\ValidationException;

class Bot
{
    private ApiClient $apiClient;
    private UpdateHandler $updateHandler;
    private SpamDetector $spamDetector;
    private array $config;

    public function __construct(string $token, array $config = [])
    {
        $this->config = array_merge([
            'timeout' => 30,
            'max_retries' => 3,
            'parse_mode' => 'Markdown',
            'redis_host' => '127.0.0.1',
            'redis_port' => 6379,
        ], $config);

        $this->apiClient = new ApiClient($token, $this->config);
        $this->updateHandler = new UpdateHandler($this);
        $this->spamDetector = new SpamDetector($this->config);
    }

    public function message(): MessageBuilder
    {
        return new MessageBuilder($this->apiClient);
    }

    public function file(): FileBuilder
    {
        return new FileBuilder($this->apiClient);
    }

    public function poll(): PollBuilder
    {
        return new PollBuilder($this->apiClient);
    }

    public function getMe(): array
    {
        return $this->apiClient->request('getMe');
    }

    public function run(): void
    {
        $this->updateHandler->process();
    }

    public function isUserSpamming(string $userId): bool
    {
        return $this->spamDetector->isSpamming($userId);
    }

    public function resetUserSpamState(string $userId): void
    {
        $this->spamDetector->resetUserSpamState($userId);
    }
}