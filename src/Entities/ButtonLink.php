<?php

namespace RubikaBot\Entities;

class ButtonLink
{
    public ?string $type = null;
    public ?string $link_url = null;
    public ?JoinChannelData $joinchannel_data = null;
    public ?OpenChatData $open_chat_data = null;

    public function __construct() {}

    public static function make(?string $link_url = null, ?string $type = null, ?JoinChannelData $joinchannel_data = null, ?OpenChatData $open_chat_data = null): self
    {
        $obj = new self();
        $obj->type = $type;
        $obj->link_url = $link_url;
        $obj->joinchannel_data = $joinchannel_data;
        $obj->open_chat_data = $open_chat_data;
        $obj->normalizeLink();
        return $obj;
    }

    private function normalizeLink(): void
    {
        if (!$this->link_url) {
            return;
        }

        $mappings = [
            "https://rubika.ir/joing/" => "rubika://g.rubika.ir/",
            "https://rubika.ir/joinc/" => "rubika://c.rubika.ir/",
            "https://rubika.ir/post/"  => "rubika://p.rubika.ir/"
        ];

        foreach ($mappings as $prefix => $deep_prefix) {
            if (strpos($this->link_url, $prefix) === 0) {
                $code = substr($this->link_url, strlen($prefix));
                $this->link_url = $deep_prefix . $code;
                break;
            }
        }
    }
}