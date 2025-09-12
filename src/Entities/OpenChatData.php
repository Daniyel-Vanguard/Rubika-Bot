<?php

namespace RubikaBot\Entities;

class OpenChatData
{
    public string $chat_id;

    public function __construct() {}

    public static function make(string $chat_id): self
    {
        $obj = new self();
        $obj->chat_id = $chat_id;
        return $obj;
    }
}