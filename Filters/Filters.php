<?php

namespace RubikaBot\Filters;

use RubikaBot\Bot;
use RubikaBot\Types\ChatType;
use RubikaBot\Message;

class Filters
{
    public static function filter($condition): Filter
    {
        if ($condition instanceof Filter) {
            return $condition;
        }

        if (is_callable($condition)) {
            return Filter::make($condition);
        }

        throw new \InvalidArgumentException('Condition must be callable or Filter instance');
    }

    public static function text(?string $match = null): Filter
    {
        return Filter::make(function (Bot $bot) use ($match) {
            $message = new Message($bot->getUpdate());
            $text = $message->text;
            if ($text === null) return false;
            return $match === null ? true : trim($text) === trim($match);
        });
    }
    
    public static function command(string $command): Filter
    {
        return Filter::make(function (Bot $bot) use ($command) {
            $message = new Message($bot->getUpdate());
            $text = $message->text;
            if (!$text) return false;
            $text = trim($text);
            $command = trim($command);
            return ($text === "/$command") ||
                (strpos($text, "/$command ") === 0);
        });
    }

    public static function button(string $button): Filter
    {
        return Filter::make(function (Bot $bot) use ($button) {
            $message = new Message($bot->getUpdate());
            $buttonId = $message->button_id;
            if ($buttonId === null) return false;
            return strpos(trim($buttonId), $button) !== false;
        });
    }

    public static function chatType(ChatType $chat): Filter
    {
        return Filter::make(function (Bot $bot) use ($chat) {
            $message = new Message($bot->getUpdate());
            $chatType = $message->chat_type;
            return $chatType === $chat->value;
        });
    }

    public static function chatId(string $chat_id): Filter
    {
        return Filter::make(function (Bot $bot) use ($chat_id) {
            $message = new Message($bot->getUpdate());
            $c = $message->chat_id;
            if ($c === null) return false;
            return strpos(trim($c), $chat_id) !== false;
        });
    }

    public static function senderId(string $sender_id): Filter
    {
        return Filter::make(function (Bot $bot) use ($sender_id) {
            $message = new Message($bot->getUpdate());
            $s = $message->sender_id;
            if ($s === null) return false;
            return strpos(trim($s), $sender_id) !== false;
        });
    }

public static function spam(int $maxMessages = 5, int $timeWindow = 10, int $cooldown = 120): Filter
{
    return Filter::make(function (Bot $bot) use ($maxMessages, $timeWindow, $cooldown) {
        $message = new Message($bot->getUpdate());
        $senderId = $message->sender_id;
        if (!$senderId) {
            return false;
        }

        // استفاده از متدهای setter به جای دسترسی مستقیم
        $bot->setMaxMessages($maxMessages);
        $bot->setTimeWindow($timeWindow);
        $bot->setCooldown($cooldown);

        return $bot->isUserSpamming($senderId);
    })->markAsSpamHandler();
}
    public static function any(): Filter
    {
        return Filter::make(function () {
            return true;
        });
    }
    
    public static function file(): Filter
    {
        return Filter::make(function (Bot $bot) {
            $message = new Message($bot->getUpdate());
            return $message->file_id !== null;
        });
    }
    
    public static function photo(): Filter
    {
        return Filter::make(function (Bot $bot) {
            $message = new Message($bot->getUpdate());
            return $message->file_id !== null && strpos($message->file_name ?? '', '.jpg') !== false;
        });
    }
}