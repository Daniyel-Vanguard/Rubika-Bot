<?php

namespace RubikaBot\Builders;

use RubikaBot\Core\ApiClient;
use RubikaBot\Exceptions\ValidationException;

class MessageBuilder
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

    public function text(string $text): self
    {
        $this->params['text'] = $text;
        return $this;
    }

    public function replyTo(string $message_id): self
    {
        $this->params['reply_to_message_id'] = $message_id;
        return $this;
    }

    public function inlineKeypad(array $keypad): self
    {
        $this->params['inline_keypad'] = $keypad;
        return $this;
    }

    public function chatKeypad(array $keypad, ?string $keypad_type = 'New'): self
    {
        $this->params['chat_keypad'] = $keypad;
        $this->params['chat_keypad_type'] = $keypad_type;
        return $this;
    }

    public function send(): array
    {
        if (empty($this->params['chat_id']) || empty($this->params['text'])) {
            throw new ValidationException("chat_id and text are required");
        }
        return $this->apiClient->request('sendMessage', $this->params);
    }

    public function edit(): array
    {
        if (empty($this->params['chat_id']) || empty($this->params['message_id']) || empty($this->params['text'])) {
            throw new ValidationException("chat_id, message_id, and text are required for edit");
        }
        return $this->apiClient->request('editMessageText', $this->params);
    }

    public function delete(): array
    {
        if (empty($this->params['chat_id']) || empty($this->params['message_id'])) {
            throw new ValidationException("chat_id and message_id are required for delete");
        }
        return $this->apiClient->request('deleteMessage', $this->params);
    }

    public function messageId(string $message_id): self
    {
        $this->params['message_id'] = $message_id;
        return $this;
    }
}