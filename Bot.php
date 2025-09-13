<?php

namespace RubikaBot;

require_once 'Message.php';
require_once 'Filters/Filter.php';
require_once 'Filters/Filters.php';
require_once 'Types/ChatType.php';
require_once 'Keyboard/Button.php';
require_once 'Keyboard/ButtonLink.php';
require_once 'Keyboard/Keypad.php';
require_once 'Keyboard/KeypadRow.php';

class Bot
{
    private string $token;
    private string $hashedToken;
    private string $baseUrl;
    private array $config = [
        'timeout' => 30,
        'max_retries' => 3,
        'parse_mode' => 'Markdown',
    ];

    protected array $update = [];
    protected array $chat = [];
    private array $handlers = [];

    private array $updateTypes = ['ReceiveUpdate', 'ReceiveInlineMessage', 'ReceiveQuery', 'GetSelectionItem', 'SearchSelectionItems'];
    protected ?string $builder_chat_id = null;
    protected ?string $builder_text = null;
    protected ?string $builder_reply_to = null;
    protected ?string $builder_file_path = null;
    protected ?string $builder_caption = null;
    protected ?string $builder_file_id = null;
    protected ?string $builder_file_type = null;
    protected ?string $builder_message_id = null;
    protected ?string $builder_from_chat_id = null;
    protected ?string $builder_to_chat_id = null;
    protected ?string $builder_question = null;
    protected array  $builder_options = [];
    protected ?float  $builder_lat = null;
    protected ?float  $builder_lng = null;
    protected ?string $builder_contact_first = null;
    protected ?string $builder_contact_phone = null;
    protected ?array $builder_inline_keypad = null;
    protected ?array $builder_chat_keypad = null;
    protected ?string $builder_chat_keypad_type = null;
    protected array  $lastResponse = [];

    protected array $spamDetectedUsers = [];
    protected array $userMessageCounters = [];
    protected array $userLastMessageTime = [];
    protected int $maxMessages = 10;
    protected int $timeWindow = 15;
    protected int $cooldown = 120;

    public function __construct(string $token, array $config = [])
    {
        $this->token = $token;
        $salt = $config['salt'] ?? 'RubikaBot';
        $this->hashedToken = hash('sha256', $token.$salt);
        $this->baseUrl = "https://botapi.rubika.ir/v3/{$token}/";
        $this->config = array_merge($this->config ?? [], $config);

        $spamDataFile = $this->hashedToken . '_SPAM_DATA.json';
        if (file_exists($spamDataFile)) {
            $data = json_decode(file_get_contents($spamDataFile), true);
            $this->spamDetectedUsers = $data['spamDetectedUsers'] ?? [];
            $this->userMessageCounters = $data['userMessageCounters'] ?? [];
            $this->userLastMessageTime = $data['userLastMessageTime'] ?? [];
        } else {
            $this->spamDetectedUsers = [];
            $this->userMessageCounters = [];
            $this->userLastMessageTime = [];
            file_put_contents($spamDataFile, json_encode([
                'spamDetectedUsers' => [],
                'userMessageCounters' => [],
                'userLastMessageTime' => []
            ]));
        }

        $this->captureUpdate();
    }

    public function chat(string $chat_id): static
    {
        $this->builder_chat_id = $chat_id;
        return $this;
    }

    public function message(string $text): static
    {
        $this->builder_text = $text;
        return $this;
    }

    public function replyTo(string $message_id): static
    {
        $this->builder_reply_to = $message_id;
        return $this;
    }

    public function file(string $path): static
    {
        $this->builder_file_path = $path;
        $this->builder_file_id = null;
        $this->builder_file_type = null;
        return $this;
    }
    
    public function file_id(string $file_id): static
    {
        $this->builder_file_id = $file_id;
        return $this;
    }
    
    public function file_type(string $file_type): static
    {
        $this->builder_file_type = $file_type;
        return $this;
    }
    
    public function caption(string $caption): static
    {
        $this->builder_caption = $caption;
        return $this;
    }

    public function poll(string $question, array $options): static
    {
        $this->builder_question = $question;
        $this->builder_options = $options;
        return $this;
    }

    public function location(float $lat, float $lng): static
    {
        $this->builder_lat = $lat;
        $this->builder_lng = $lng;
        return $this;
    }

    public function contact(string $first_name, string $phone_number): static
    {
        $this->builder_contact_first = $first_name;
        $this->builder_contact_phone = $phone_number;
        return $this;
    }
    
    public function inlineKeypad(array $keypad): static
    {
        $this->builder_inline_keypad = $keypad;
        return $this;
    }
    
    public function chatKeypad(array $keypad, ?string $keypad_type = 'New'): static
    {
        $this->builder_chat_keypad = $keypad;
        $this->builder_chat_keypad_type = $keypad_type;
        return $this;
    }
    
    public function forwardFrom(string $from_chat_id): static
    {
        $this->builder_from_chat_id = $from_chat_id;
        return $this;
    }

    public function forwardTo(string $to_chat_id): static
    {
        $this->builder_to_chat_id = $to_chat_id;
        return $this;
    }
    
    public function messageId(string $message_id): static
    {
        $this->builder_message_id = $message_id;
        return $this;
    }
    
    public function setMaxMessages(int $maxMessages): void
{
    $this->maxMessages = $maxMessages;
}

public function setTimeWindow(int $timeWindow): void
{
    $this->timeWindow = $timeWindow;
}

public function setCooldown(int $cooldown): void
{
    $this->cooldown = $cooldown;
}

public function getMaxMessages(): int
{
    return $this->maxMessages;
}

public function getTimeWindow(): int
{
    return $this->timeWindow;
}

public function getCooldown(): int
{
    return $this->cooldown;
}
    
    private function resetBuilder(): void
    {
        $this->builder_text = null;
        $this->builder_reply_to = null;
        $this->builder_file_path = null;
        $this->builder_caption = null;
        $this->builder_file_id = null;
        $this->builder_file_type = null;
        $this->builder_message_id = null;
        $this->builder_from_chat_id = null;
        $this->builder_to_chat_id = null;
        $this->builder_question = null;
        $this->builder_options = [];
        $this->builder_lat = null;
        $this->builder_lng = null;
        $this->builder_contact_first = null;
        $this->builder_contact_phone = null;
        $this->builder_inline_keypad = null;
        $this->builder_chat_keypad = null;
        $this->builder_chat_keypad_type = null;
    }

    public function send(): array
    {
        if (!$this->builder_chat_id) {
            throw new \InvalidArgumentException("chat_id is required");
        }
        if ($this->builder_text === null) {
            throw new \InvalidArgumentException("text is required for send()");
        }

        $params = [
            'chat_id' => $this->builder_chat_id,
            'text' => $this->builder_text,
        ];
        if ($this->builder_reply_to) {
            $params['reply_to_message_id'] = $this->builder_reply_to;
        }
        if ($this->builder_chat_keypad) {
            $params['chat_keypad'] = $this->builder_chat_keypad;
            $params['chat_keypad_type'] = $this->builder_chat_keypad_type;
        }
        if ($this->builder_inline_keypad) {
            $params['inline_keypad'] = $this->builder_inline_keypad;
        }
        $res = $this->apiRequest('sendMessage', $params);
        $this->lastResponse = $res;
        $this->resetBuilder();
        return $res;
    }

    public function sendFile(): array
    {
        if (!$this->builder_chat_id) {
            throw new \InvalidArgumentException("chat_id is required");
        }
        if (!$this->builder_file_path && !isset($this->builder_file_id)) {
            throw new \InvalidArgumentException("file path is required");
        }
        if (!file_exists($this->builder_file_path) && !isset($this->builder_file_id)) {
            throw new \InvalidArgumentException("File not found: {$this->builder_file_path}");
        }
        if (!isset($this->builder_file_id)) {
            $mime_type = mime_content_type($this->builder_file_path);
            $file_type = $this->detectFileType($mime_type);
            $upload_url = $this->requestSendFile($file_type);
            $file_id = $this->uploadFileToUrl($upload_url, $this->builder_file_path);
        } else {
            $file_type = $this->builder_file_type ?? 'Image';
            $file_id = $this->builder_file_id ?? null;
        }
        // sendFile
        $params = [
            'chat_id' => $this->builder_chat_id,
            'file_id' => $file_id,
            'type' => $file_type,
        ];
        if ($this->builder_reply_to) {
            $params['reply_to_message_id'] = $this->builder_reply_to;
        }
        if ($this->builder_caption) {
            $params['text'] = $this->builder_caption;
        }
        if ($this->builder_chat_keypad) {
            $params['chat_keypad'] = $this->builder_chat_keypad;
            $params['chat_keypad_type'] = $this->builder_chat_keypad_type;
        }
        if ($this->builder_inline_keypad) {
            $params['inline_keypad'] = $this->builder_inline_keypad;
        }
        $res = $this->apiRequest('sendFile', $params);
        $this->lastResponse = $res;
        $this->resetBuilder();
        return ['api' => $res, 'file_id' => $file_id, 'type' => $file_type];
    }
    
    public function sendPoll(): array
    {
        if (!$this->builder_chat_id) {
            throw new \InvalidArgumentException("chat_id is required");
        }
        if (!$this->builder_question || !is_array($this->builder_options) || count($this->builder_options) < 2) {
            throw new \InvalidArgumentException("Poll requires question and at least 2 options");
        }
        $params = [
            'chat_id' => $this->builder_chat_id,
            'question' => $this->builder_question,
            'options' => $this->builder_options,
        ];
        $res = $this->apiRequest('sendPoll', $params);
        $this->lastResponse = $res;
        $this->resetBuilder();
        return $res;
    }
    
    public function sendLocation(): array
    {
        if (!$this->builder_chat_id) {
            throw new \InvalidArgumentException("chat_id is required");
        }
        if ($this->builder_lat === null || $this->builder_lng === null) {
            throw new \InvalidArgumentException("latitude and longitude are required");
        }
        $params = [
            'chat_id' => $this->builder_chat_id,
            'latitude' => $this->builder_lat,
            'longitude' => $this->builder_lng,
        ];
        if ($this->builder_reply_to) {
            $params['reply_to_message_id'] = $this->builder_reply_to;
        }
        if ($this->builder_chat_keypad) {
            $params['chat_keypad'] = $this->builder_chat_keypad;
            $params['chat_keypad_type'] = $this->builder_chat_keypad_type;
        }
        if ($this->builder_inline_keypad) {
            $params['inline_keypad'] = $this->builder_inline_keypad;
        }
        $res = $this->apiRequest('sendLocation', $params);
        $this->lastResponse = $res;
        $this->resetBuilder();
        return $res;
    }

    public function sendContact(): array
    {
        if (!$this->builder_chat_id) {
            throw new \InvalidArgumentException("chat_id is required");
        }
        if (!$this->builder_contact_first || !$this->builder_contact_phone) {
            throw new \InvalidArgumentException("first_name and phone_number are required");
        }
        $params = [
            'chat_id' => $this->builder_chat_id,
            'first_name' => $this->builder_contact_first,
            'phone_number' => $this->builder_contact_phone,
        ];
        if ($this->builder_reply_to) {
            $params['reply_to_message_id'] = $this->builder_reply_to;
        }
        if ($this->builder_chat_keypad) {
            $params['chat_keypad'] = $this->builder_chat_keypad;
            $params['chat_keypad_type'] = $this->builder_chat_keypad_type;
        }
        if ($this->builder_inline_keypad) {
            $params['inline_keypad'] = $this->builder_inline_keypad;
        }
        $res = $this->apiRequest('sendContact', $params);
        $this->lastResponse = $res;
        $this->resetBuilder();
        return $res;
    }
    
    public function forward(): array
    {
        if (!$this->builder_from_chat_id || !$this->builder_message_id || !$this->builder_to_chat_id) {
            throw new \InvalidArgumentException("from_chat_id, message_id and to_chat_id are required for forward()");
        }
        $params = [
            'from_chat_id' => $this->builder_from_chat_id,
            'message_id' => $this->builder_message_id,
            'to_chat_id' => $this->builder_to_chat_id,
        ];
        $res = $this->apiRequest('forwardMessage', $params);
        $this->lastResponse = $res;
        $this->resetBuilder();
        return $res;
    }

    private function sendEditText(): array
    {
        if (!$this->builder_chat_id || !$this->builder_message_id || $this->builder_text === null) {
            throw new \InvalidArgumentException("chat_id, message_id and text are required for edit");
        }
        $params = [
            'chat_id' => $this->builder_chat_id,
            'message_id' => $this->builder_message_id,
            'text' => $this->builder_text,
        ];
        $res = $this->apiRequest('editMessageText', $params);
        return $res;
    }
    
    private function sendEditChatKeypad(): array
    {
        if (!$this->builder_chat_keypad || !$this->builder_chat_id) {
                    throw new \InvalidArgumentException("inline keypad or message_id | chat id are required for edit inline keypad");
        }
        $params = [
            'chat_id' => $this->builder_chat_id,
            'message_id' => $this->builder_message_id,
            'inline_keypad' => $this->builder_inline_keypad,
        ];
        $res = $this->apiRequest('editMessageKeypad', $params);
        return $res;
    }
    
    public function editMessage(): array {
        if (!$this->builder_chat_id || !$this->builder_message_id) {
            throw new \InvalidArgumentException("chat_id, message_id and text are required for edit");
        }
        $arr = [];
        if ($this->builder_text) $arr = array_merge($this->sendEditText(), $arr);
        if ($this->builder_chat_keypad) $arr = array_merge($this->sendEditChatKeypad(), $arr);
        if ($this->builder_inline_keypad) $arr = array_merge($this->sendEditInlineKeypad(), $arr);
        $this->lastResponse = $arr;
        $this->resetBuilder();
        return $arr;
    }
    
    public function sendDelete(): array
    {
        if (!$this->builder_chat_id || !$this->builder_message_id) {
            throw new \InvalidArgumentException("chat_id and message_id are required for delete");
        }
        $params = [
            'chat_id' => $this->builder_chat_id,
            'message_id' => $this->builder_message_id,
        ];
        $res = $this->apiRequest('deleteMessage', $params);
        $this->lastResponse = $res;
        $this->resetBuilder();
        return $res;
    }
    
    private function uploadFileToUrl(string $url, string $file_path): string
    {
        $mime_type = mime_content_type($file_path);
        $filename = basename($file_path);
        $curl_file = new \CURLFile($file_path, $mime_type, $filename);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => ['file' => $curl_file],
            CURLOPT_HTTPHEADER => ['Content-Type: multipart/form-data'],
            CURLOPT_TIMEOUT => 30,
        ]);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($response, true);
        if ($http_code !== 200 || !is_array($data)) {
            throw new \RuntimeException("Upload failed: HTTP $http_code - " . ($response ?: 'No response'));
        }
        if (!isset($data['data']['file_id'])) {
            throw new \RuntimeException("No file_id returned from upload: " . json_encode($data));
        }
        return $data['data']['file_id'];
    }
    
    private function apiRequest(string $method, array $params = []): array
    {
        $url = $this->baseUrl . $method;
        $retry = 0;

        while ($retry < $this->config['max_retries']) {
            $ch = curl_init($url);
            try {
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                    CURLOPT_POSTFIELDS => json_encode($params),
                    CURLOPT_TIMEOUT => $this->config['timeout'],
                ]);
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                if ($response === false) {
                    $err = curl_error($ch);
                    throw new \Exception("cURL error: {$err}");
                }

                if ($httpCode >= 200 && $httpCode < 300) {
                    curl_close($ch);
                    return json_decode($response, true) ?? [];
                }

                throw new \Exception("API Error: HTTP {$httpCode} - " . ($response ?: 'No response'));
            } catch (\Exception $e) {
                curl_close($ch);
                $retry++;
                if ($retry === $this->config['max_retries']) {
                    throw $e;
                }
                sleep(1);
            }
        }

        return ['ok' => false, 'error' => 'Request failed'];
    }

    public function getMe(): array
    {
        return $this->apiRequest('getMe');
    }

    public function getChat(array $data): array
    {
        $this->validateParams($data, ['chat_id']);
        $res = $this->apiRequest('getChat', $data);
        $this->chat = $res['data'] ?? [];
        return $res;
    }

    public function getUpdates(array $data = []): array
    {
        return $this->apiRequest('getUpdates', $data);
    }
    
    public function requestSendFile(string $type): string
    {
        $validTypes = ['File', 'Image', 'Voice', 'Music', 'Gif', 'Video'];
        if (!in_array($type, $validTypes)) {
            throw new \InvalidArgumentException("Invalid file type: {$type}");
        }
        $response = $this->apiRequest('requestSendFile', ['type' => $type]);
        if (!isset($response['status']) || $response['status'] !== 'OK' || empty($response['data']['upload_url'])) {
            throw new \RuntimeException("No upload_url returned: " . json_encode($response));
        }
        return $response['data']['upload_url'];
    }

    public function isUserSpamming(string $userId): bool
    {
        $now = time();

        if (!isset($this->userMessageCounters[$userId])) {
            $this->userMessageCounters[$userId] = 1;
            $this->userLastMessageTime[$userId] = $now;
        } elseif ($now - $this->userLastMessageTime[$userId] > $this->timeWindow) {
            $this->userMessageCounters[$userId] = 1;
            $this->userLastMessageTime[$userId] = $now;
        } else {
            $this->userMessageCounters[$userId]++;
            $this->userLastMessageTime[$userId] = $now;
        }

        $isSpamming = false;
        if ($this->userMessageCounters[$userId] > $this->maxMessages) {
            $this->spamDetectedUsers[$userId] = $now;
            $isSpamming = true;
        }

        $this->saveSpamData();
        return $isSpamming;
    }

    public function isUserSpamDetected(string $userId): bool
    {
        if (!isset($this->spamDetectedUsers[$userId])) {
            return false;
        }

        if (time() - $this->spamDetectedUsers[$userId] > $this->cooldown) {
            unset($this->spamDetectedUsers[$userId]);
            unset($this->userMessageCounters[$userId]);
            unset($this->userLastMessageTime[$userId]);
            $this->saveSpamData();
            return false;
        }

        return true;
    }

    public function resetUserSpamState(string $userId): void
    {
        unset($this->spamDetectedUsers[$userId]);
        unset($this->userMessageCounters[$userId]);
        unset($this->userLastMessageTime[$userId]);
        $this->saveSpamData();
    }

    public function getUserMessageCount(string $userId): int
    {
        return $this->userMessageCounters[$userId] ?? 0;
    }
    
    public function cleanupSpamData(int $expireTime = 86400): void
    {
        $now = time();
        foreach ($this->userLastMessageTime as $userId => $lastTime) {
            if ($now - $lastTime > $expireTime) {
                unset($this->userMessageCounters[$userId]);
                unset($this->userLastMessageTime[$userId]);
                unset($this->spamDetectedUsers[$userId]);
            }
        }
        $this->saveSpamData();
    }

    private function saveSpamData(): void
    {
        $data = [
            'spamDetectedUsers' => $this->spamDetectedUsers,
            'userMessageCounters' => $this->userMessageCounters,
            'userLastMessageTime' => $this->userLastMessageTime
        ];
        file_put_contents($this->hashedToken . '_SPAM_DATA.json', json_encode($data));
    }

    public function getFile(string $file_id): string
    {
        $res = $this->apiRequest('getFile', ['file_id' => $file_id]);
        return $res['data']['download_url'] ?? '';
    }

    public function downloadFile(string $file_id, string $to): void
    {
        $url = $this->getFile($file_id);
        if (!$url) {
            throw new \RuntimeException("Download URL not found for file_id: {$file_id}");
        }
        $content = @file_get_contents($url);
        if ($content === false) {
            throw new \RuntimeException("Failed to download file from: {$url}");
        }
        file_put_contents($to, $content);
    }

    public function setCommands(array $data): array
    {
        $this->validateParams($data, ['bot_commands']);
        return $this->apiRequest('setCommands', $data);
    }

    public function updateBotEndpoints(string $url, string $type): array
    {
        $data = [
            'url' => $url ?? throw new \RuntimeException('set url endpoint'),
            'type' => $type ?? throw new \RuntimeException('set type endpoint')
        ];
        return $this->apiRequest('updateBotEndpoints', $data);
    }
    
    public function setEndpoint(string $url): array
    {
        $data = [];
        foreach ($this->updateTypes as $type) {
            $data[] = $this->updateBotEndpoints($url, $type);
        }
        return $data;
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

    private function validateParams(array $params, array $required): void
    {
        foreach ($required as $field) {
            if (!isset($params[$field])) {
                throw new \InvalidArgumentException("Missing required parameter: {$field}");
            }
        }
    }
    
    private function captureUpdate(): void
    {
        $input = @file_get_contents("php://input");
        if ($input) {
            $this->update = json_decode($input, true) ?? [];
        } else {
            $this->update = [];
        }
    }

    public function getUpdate(): array
    {
        return $this->update;
    }
    
    public function onMessage($filter, callable $callback): void
    {
        if (!($filter instanceof Filters\Filter)) {
            $filter = Filters\Filters::filter($filter);
        }

        $this->handlers[] = [
            'filter' => $filter,
            'callback' => $callback
        ];
    }

    public function run(): void
    {
        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $message = new Message($this->update);
            $message->loadChatInfo($this);
            
            $senderId = $message->sender_id;
            if ($senderId) {
                if ($this->isUserSpamDetected($senderId)) {
                    return;
                }

                if ($this->isUserSpamming($senderId)) {
                    foreach ($this->handlers as $handler) {
                        if (
                            $handler['filter'] instanceof Filters\Filter &&
                            $handler['filter']->isSpamHandler()
                        ) {
                            $handler['callback']($this, $message);
                        }
                    }
                    return;
                }
            }
            
            foreach ($this->handlers as $handler) {
                if ($handler['filter']($this)) {
                    $handler['callback']($this, $message);
                }
            }
        } else {
            $offset_id = null;
            if (file_exists($this->hashedToken . '.txt')) {
                $offset_id = file_get_contents($this->hashedToken . '.txt');
            }
            
            while (true) {
                try {
                    $params = ['limit' => 100];
                    if ($offset_id) {
                        $params['offset_id'] = $offset_id;
                    }

                    $updates = $this->getUpdates($params);
                    if (empty($updates['data']['updates'])) {
                        sleep(2);
                        continue;
                    }

                    if (isset($updates['data']['next_offset_id'])) {
                        $offset_id = $updates['data']['next_offset_id'];
                        file_put_contents($this->hashedToken . '.txt', $updates['data']['next_offset_id']);
                    }

                    foreach ($updates['data']['updates'] as $update) {
                        $this->update = ['update' => $update];
                        $message = new Message($this->update);
                        $message->loadChatInfo($this);
                        
                        $this->chat($message->chat_id ?? '');
                        
                        $senderId = $message->sender_id;
                        if ($senderId) {
                            if ($this->isUserSpamDetected($senderId)) {
                                continue;
                            }

                            if ($this->isUserSpamming($senderId)) {
                                foreach ($this->handlers as $handler) {
                                    if (
                                        $handler['filter'] instanceof Filters\Filter &&
                                        $handler['filter']->isSpamHandler()
                                    ) {
                                        $handler['callback']($this, $message);
                                    }
                                }
                                continue;
                            }
                        }

                        foreach ($this->handlers as $handler) {
                            if ($handler['filter']($this)) {
                                $handler['callback']($this, $message);
                            }
                            sleep(0.5);
                        }
                    }
                } catch (\Exception $e) {
                    error_log("Polling error: " . $e->getMessage());
                    sleep(1);
                }
            }
        }
    }
    
    public function getLastResponse(): array
    {
        return $this->lastResponse;
    }
}