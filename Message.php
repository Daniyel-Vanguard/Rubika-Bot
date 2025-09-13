<?php

namespace RubikaBot;

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

    public function __construct(array $updateData)
    {
        $this->update_type = $updateData['update']['type'] ?? $updateData['inline_message']['type'] ?? null;
        $this->chat_id = $updateData['chat_id'] ?? $updateData['update']['chat_id'] ?? $updateData['inline_message']['chat_id'] ?? null;
        $this->sender_id = $updateData['update']['new_message']['sender_id'] ?? $updateData['inline_message']['sender_id'] ?? null;
        $this->text = $updateData['update']['new_message']['text'] ?? $updateData['inline_message']['text'] ?? null;
        $this->button_id = $updateData['inline_message']['aux_data']['button_id'] ?? null;
        $this->file_name = $updateData['update']['new_message']['file']['file_name'] ?? null;
        $this->file_id = $updateData['update']['new_message']['file']['file_id'] ?? null;
        $this->file_size = $updateData['update']['new_message']['file']['size'] ?? null;
        $this->message_id = $updateData['update']['new_message']['message_id'] ?? $updateData['inline_message']['message_id'] ?? null;
        
        // این فیلدها نیاز به فراخوانی getChat دارند
        $this->chat_type = null;
        $this->first_name = null;
        $this->user_name = null;
    }

    public function reply(Bot $bot): array
    {
        if (!$bot->builder_chat_id) {
            $bot->chat($this->chat_id);
        }
        if (!$bot->builder_reply_to) {
            $bot->replyTo($this->message_id);
        }
        return $bot->send();
    }
    
    public function replyFile(Bot $bot): array
    {
        if (!$bot->builder_chat_id) {
            $bot->chat($this->chat_id);
        }
        if (!$bot->builder_reply_to) {
            $bot->replyTo($this->message_id);
        }
        return $bot->sendFile();
    }
    
    public function replyContact(Bot $bot): array
    {
        if (!$bot->builder_chat_id) {
            $bot->chat($this->chat_id);
        }
        if (!$bot->builder_reply_to) {
            $bot->replyTo($this->message_id);
        }
        return $bot->sendContact();
    }
    
    public function replyLocation(Bot $bot): array
    {
        if (!$bot->builder_chat_id) {
            $bot->chat($this->chat_id);
        }
        if (!$bot->builder_reply_to) {
            $bot->replyTo($this->message_id);
        }
        return $bot->sendLocation();
    }
    
    public function editText(Bot $bot): array
    {
        if (!$bot->builder_chat_id) {
            $bot->chat($this->chat_id);
        }
        if (!$bot->builder_message_id) {
            $bot->messageId($this->message_id);
        }
        return $bot->editMessage();
    }
    
    public function delete(Bot $bot): array
    {
        if (!$bot->builder_chat_id) {
            $bot->chat($this->chat_id);
        }
        if (!$bot->builder_message_id) {
            $bot->messageId($this->message_id);
        }
        return $bot->sendDelete();
    }
    
    public function loadChatInfo(Bot $bot): void
    {
        if ($this->chat_id && (!$this->chat_type || !$this->first_name)) {
            $chatData = $bot->getChat(['chat_id' => $this->chat_id]);
            if (isset($chatData['data']['chat'])) {
                $this->chat_type = $chatData['data']['chat']['chat_type'] ?? null;
                $this->first_name = $chatData['data']['chat']['first_name'] ?? null;
                $this->user_name = $chatData['data']['chat']['username'] ?? null;
            }
        }
    }
}