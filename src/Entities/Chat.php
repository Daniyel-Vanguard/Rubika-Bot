<?php

namespace RubikaBot\Entities;

use RubikaBot\Core\Bot;

class Chat
{
    public ?string $chat_id;
    public ?string $chat_type;
    public ?string $first_name;
    public ?string $user_name;

    public function __construct(Bot $bot, array $chat_data)
    {
        $this->chat_id = $chat_data['chat_id'] ?? null;
        $this->chat_type = $chat_data['chat_type'] ?? null;
        $this->first_name = $chat_data['first_name'] ?? null;
        $this->user_name = $chat_data['username'] ?? null;
    }
}