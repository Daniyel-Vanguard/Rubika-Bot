<?php

namespace RubikaBot\Entities;

class JoinChannelData
{
    public string $username;
    public bool $ask_join;

    public function __construct() {}

    public static function make(string $username, bool $ask_join = true): self
    {
        $obj = new self();
        $obj->username = $username;
        $obj->ask_join = $ask_join;
        return $obj;
    }
}