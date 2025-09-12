<?php

namespace RubikaBot\Builders;

use RubikaBot\Core\ApiClient;
use RubikaBot\Exceptions\ValidationException;

class FileBuilder
{
    private array $params = [];
    private ApiClient $apiClient;
    private ?string $file_id = null;
    private ?string $file_type = null;

    public function __construct(ApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    public function chat(string $chat_id): self
    {
        $this->params['chat_id'] = $chat_id;
        return $this;
    }

    public function file(string $path): self
    {
        $this->params['file_path'] = $path;
        return $this;
    }

    public function fileId(string $file_id): self
    {
        $this->file_id = $file_id;
        return $this;
    }

    public function fileType(string $file_type): self
    {
        $this->file_type = $file_type;
        return $this;
    }

    public function caption(string $caption): self
    {
        $this->params['text'] = $caption;
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
        if (empty($this->params['chat_id']) || (empty($this->params['file_path']) && !$this->file_id)) {
            throw new ValidationException("chat_id and either file path or file_id are required");
        }
        if (!isset($this->file_id) && !file_exists($this->params['file_path'])) {
            throw new ValidationException("File not found: {$this->params['file_path']}");
        }

        if (!isset($this->file_id)) {
            $mime_type = mime_content_type($this->params['file_path']);
            $file_type = $this->detectFileType($mime_type);
            $upload_url = $this->apiClient->request('requestSendFile', ['type' => $file_type])['data']['upload_url'];
            $this->file_id = $this->apiClient->uploadFile($upload_url, $this->params['file_path']);
        } else {
            $file_type = $this->file_type ?? 'Image';
        }

        $params = array_merge($this->params, [
            'file_id' => $this->file_id,
            'type' => $file_type,
        ]);

        return $this->apiClient->request('sendFile', $params);
    }

    private function detectFileType(string $mime_type): string
    {
        $map = [
            'image/jpeg' => 'Image',
            'image/png' => 'Image',
            'image/gif' => 'Gif',
            'video/mp4' => 'Video',
            'video/quicktime' => 'Video',
            'audio/mpeg' => 'File',
            'audio/wav' => 'File',
            'application/pdf' => 'File',
            'application/msword' => 'File',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'File',
            'application/zip' => 'File',
            'application/x-rar-compressed' => 'File',
        ];
        return $map[strtolower($mime_type)] ?? 'File';
    }
}
