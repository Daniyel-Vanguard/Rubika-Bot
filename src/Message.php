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

    public bool $has_metadata = false;
    public array $meta_types = [];
    public bool $is_bold = false;
    public bool $is_italic = false;
    public bool $is_strike = false;
    public bool $is_underline = false;
    public bool $is_quote = false;
    public bool $is_spoiler = false;
    public bool $is_pre = false;
    public bool $is_mono = false;
    public bool $is_link_meta = false;
    public array $meta_links = [];
    public bool $has_link = false;
    public bool $is_formatted = false;


    public ?array $metadata = null;
    public ?array $meta_data_parts = null;

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


        $this->metadata = $updateData['update']['new_message']['metadata'] ?? $updateData['inline_message']['metadata'] ?? null;
        $this->meta_data_parts = $this->metadata['meta_data_parts'] ?? null;

        $this->chat_type = null;
        $this->first_name = null;
        $this->user_name = null;


        $this->analyzeMetadata();
    }


    private function analyzeMetadata(): void
    {

        $this->analyzeExplicitMetadata();
        

        $this->analyzeMarkdownFormats();


        $this->has_metadata = !empty($this->meta_data_parts) || 
                             $this->is_bold || $this->is_italic || 
                             $this->is_strike || $this->is_underline ||
                             $this->is_quote || $this->is_spoiler ||
                             $this->is_pre || $this->is_mono || $this->is_link_meta;

        $this->has_link = $this->is_link_meta || !empty($this->meta_links);
        $this->is_formatted = $this->has_metadata;
    }


    private function analyzeExplicitMetadata(): void
    {
        if (empty($this->meta_data_parts)) {
            return;
        }

        $this->has_metadata = true;

        foreach ($this->meta_data_parts as $part) {
            if (isset($part['type'])) {
                $this->meta_types[] = $part['type'];
                
                switch ($part['type']) {
                    case 'Bold':
                        $this->is_bold = true;
                        break;
                    case 'Italic':
                        $this->is_italic = true;
                        break;
                    case 'Strike':
                        $this->is_strike = true;
                        break;
                    case 'Underline':
                        $this->is_underline = true;
                        break;
                    case 'Quote':
                        $this->is_quote = true;
                        break;
                    case 'Spoiler':
                        $this->is_spoiler = true;
                        break;
                    case 'Pre':
                        $this->is_pre = true;
                        break;
                    case 'Mono':
                        $this->is_mono = true;
                        break;
                    case 'Link':
                        $this->is_link_meta = true;
                        if (isset($part['link_url'])) {
                            $this->meta_links[] = $part['link_url'];
                        }
                        break;
                }
            }
        }


        $this->meta_types = array_unique($this->meta_types);
    }


    private function analyzeMarkdownFormats(): void
    {
        if (empty($this->text)) {
            return;
        }

        $text = $this->text;

        if (!$this->has_metadata) {
            if (preg_match('/\*\*(.*?)\*\*/', $text)) {
                $this->is_bold = true;
                $this->meta_types[] = 'Bold';
            }

            if (preg_match('/__(.*?)__/', $text)) {
                $this->is_italic = true;
                $this->meta_types[] = 'Italic';
            }

            if (preg_match('/--(.*?)--/', $text)) {
                $this->is_underline = true;
                $this->meta_types[] = 'Underline';
            }

            if (preg_match('/~~(.*?)~~/', $text)) {
                $this->is_strike = true;
                $this->meta_types[] = 'Strike';
            }

            if (preg_match('/`(.*?)`/', $text)) {
                $this->is_mono = true;
                $this->meta_types[] = 'Mono';
            }

            if (preg_match('/```([\s\S]*?)```/', $text)) {
                $this->is_pre = true;
                $this->meta_types[] = 'Pre';
            }

            if (preg_match('/\|\|(.*?)\|\|/', $text)) {
                $this->is_spoiler = true;
                $this->meta_types[] = 'Spoiler';
            }

            if (preg_match('/##([\s\S]*?)##/', $text)) {
                $this->is_quote = true;
                $this->meta_types[] = 'Quote';
            }

            if (preg_match_all('/\[(.*?)\]\((.*?)\)/', $text, $matches)) {
                $this->is_link_meta = true;
                $this->meta_types[] = 'Link';
                foreach ($matches[2] as $link) {
                    $this->meta_links[] = $link;
                }
            }


            $this->meta_types = array_unique($this->meta_types);
        }
    }


    public function getMetaTypesString(): string
    {
        return empty($this->meta_types) ? 'None' : implode(', ', $this->meta_types);
    }

    public function getMetaLinksString(): string
    {
        return empty($this->meta_links) ? 'None' : implode(', ', $this->meta_links);
    }

    public function getMetadataInfo(): array
    {
        return [
            'has_metadata' => $this->has_metadata,
            'meta_types' => $this->meta_types,
            'is_bold' => $this->is_bold,
            'is_italic' => $this->is_italic,
            'is_strike' => $this->is_strike,
            'is_underline' => $this->is_underline,
            'is_quote' => $this->is_quote,
            'is_spoiler' => $this->is_spoiler,
            'is_pre' => $this->is_pre,
            'is_mono' => $this->is_mono,
            'is_link_meta' => $this->is_link_meta,
            'meta_links' => $this->meta_links,
            'has_link' => $this->has_link,
            'is_formatted' => $this->is_formatted
        ];
    }


    public function reply(Bot $bot, ?string $parse_mode = null): array
    {
        $bot->chat($this->chat_id);
        $bot->replyTo($this->message_id);
        if ($parse_mode) {
            $bot->setParseMode($parse_mode);
        }
        return $bot->send();
    }

    public function replyFile(Bot $bot, ?string $parse_mode = null): array
    {
        $bot->chat($this->chat_id);
        $bot->replyTo($this->message_id);
        if ($parse_mode) {
            $bot->setParseMode($parse_mode);
        }
        return $bot->sendFile();
    }

    public function replyContact(Bot $bot, ?string $parse_mode = null): array
    {
        $bot->chat($this->chat_id);
        $bot->replyTo($this->message_id);
        if ($parse_mode) {
            $bot->setParseMode($parse_mode);
        }
        return $bot->sendContact();
    }

    public function replyLocation(Bot $bot, ?string $parse_mode = null): array
    {
        $bot->chat($this->chat_id);
        $bot->replyTo($this->message_id);
        if ($parse_mode) {
            $bot->setParseMode($parse_mode);
        }
        return $bot->sendLocation();
    }

    public function editText(Bot $bot, ?string $parse_mode = null): array
    {
        $bot->chat($this->chat_id);
        $bot->messageId($this->message_id);
        if ($parse_mode) {
            $bot->setParseMode($parse_mode);
        }
        return $bot->sendEditText();
    }

    public function delete(Bot $bot): array
    {
        $bot->chat($this->chat_id);
        $bot->messageId($this->message_id);
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
