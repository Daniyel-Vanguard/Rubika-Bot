<?php

namespace RubikaBot\Entities;

use RubikaBot\Core\Bot;

class Message
{
    public ?string $update_type;
    public ?string $chat_id;
    public ?string $sender_id;
    public ?string $text;
    public ?string $button_id;
    public ?string $file_name;
    public ?string $file_id;
    public ?string $file_size;
    public ?string $message_id;
    public ?string $chat_type;
    public ?string $first_name;
    public ?string $user_name;

    private Bot $bot;

    public function __construct(Bot $bot, array $update)
    {
        $this->bot = $bot;
        $this->update_type = $update['update']['type'] ?? $update['inline_message']['type'] ?? null;
        $this->chat_id = $update['update']['chat_id'] ?? $update['inline_message']['chat_id'] ?? null;
        $this->sender_id = $update['update']['new_message']['sender_id'] ?? $update['inline_message']['sender_id'] ?? null;
        $this->text = $update['update']['new_message']['text'] ?? $update['inline_message']['text'] ?? null;
        $this->button_id = $update['inline_message']['aux_data']['button_id'] ?? null;
        $this->file_name = $update['update']['new_message']['file']['file_name'] ?? null;
        $this->file_id = $update['update']['new_message']['file']['file_id'] ?? null;
        $this->file_size = $update['update']['new_message']['file']['size'] ?? null;
        $this->message_id = $update['update']['new_message']['message_id'] ?? $update['inline_message']['message_id'] ?? null;
        $this->chat_type = $bot->getChat(['chat_id' => $this->chat_id])['chat']['chat_type'] ?? null;
        $this->first_name = $bot->getChat(['chat_id' => $this->chat_id])['chat']['first_name'] ?? null;
        $this->user_name = $bot->getChat(['chat_id' => $this->chat_id])['chat']['username'] ?? null;
    }

    public function reply(): array
    {
        return $this->bot->message()->chat($this->chat_id)->replyTo($this->message_id)->send();
    }

    public function replyFile(): array
    {
        return $this->bot->file()->chat($this->chat_id)->replyTo($this->message_id)->send();
    }

    public function replyContact(): array
    {
        return $this->bot->contact()->chat($this->chat_id)->replyTo($this->message_id)->send();
    }

    public function replyLocation(): array
    {
        return $this->bot->location()->chat($this->chat_id)->replyTo($this->message_id)->send();
    }

    public function editText(): array
    {
        return $this->bot->message()->chat($this->chat_id)->messageId($this->message_id)->edit();
    }

    public function delete(): array
    {
        return $this->bot->message()->chat($this->chat_id)->messageId($this->message_id)->delete();
    }
}