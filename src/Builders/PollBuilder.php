<?php

namespace RubikaBot\Builders;

use RubikaBot\Core\ApiClient;
use RubikaBot\Exceptions\ValidationException;

class PollBuilder
{
    private array $params = [];
    private ApiClient $apiClient;

    public function __construct(ApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    public function chat(string $chat_id): self
    {
        $this->params['chat_id'] = $chat_id;
        return $this;
    }

    public function poll(string $question, array $options): self
    {
        $this->params['question'] = $question;
        $this->params['options'] = $options;
        return $this;
    }

    public function send(): array
    {
        if (empty($this->params['chat_id']) || empty($this->params['question']) || !is_array($this->params['options']) || count($this->params['options']) < 2) {
            throw new ValidationException("Poll requires chat_id, question, and at least 2 options");
        }
        return $this->apiClient->request('sendPoll', $this->params);
    }
}