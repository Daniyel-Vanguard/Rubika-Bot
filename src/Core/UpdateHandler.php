<?php

namespace RubikaBot\Core;

use RubikaBot\Entities\Message;
use RubikaBot\Filters\Filter;
use Symfony\Component\EventDispatcher\EventDispatcher;

class UpdateHandler
{
    private Bot $bot;
    private EventDispatcher $dispatcher;
    private array $handlers = [];
    private array $update = [];

    public function __construct(Bot $bot)
    {
        $this->bot = $bot;
        $this->dispatcher = new EventDispatcher();
    }

    public function captureUpdate(): void
    {
        $input = @file_get_contents("php://input");
        $this->update = $input ? json_decode($input, true) ?? [] : [];
    }

    public function getUpdate(): array
    {
        return $this->update;
    }

    public function onMessage($filter, callable $callback): void
    {
        if (!($filter instanceof Filter)) {
            $filter = Filters::filter($filter);
        }
        $this->handlers[] = [
            'filter' => $filter,
            'callback' => $callback
        ];
    }

    public function process(): void
    {
        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->captureUpdate();
            $message = new Message($this->bot, $this->update);
            if ($message->sender_id && $this->bot->isUserSpamDetected($message->sender_id)) {
                return;
            }
            if ($message->sender_id && $this->bot->isUserSpamming($message->sender_id)) {
                foreach ($this->handlers as $handler) {
                    if ($handler['filter'] instanceof Filter && $handler['filter']->isSpamHandler()) {
                        $handler['callback']($this->bot);
                    }
                }
                return;
            }
            foreach ($this->handlers as $handler) {
                if ($handler['filter']($message)) {
                    $handler['callback']($message);
                }
            }
        } else {
            $offset_id = file_exists($this->bot->getHashedToken() . '.txt') ? file_get_contents($this->bot->getHashedToken() . '.txt') : null;
            while (true) {
                try {
                    $params = ['limit' => 100];
                    if ($offset_id) {
                        $params['offset_id'] = $offset_id;
                    }
                    $updates = $this->bot->getUpdates($params);
                    if (empty($updates['data']['updates'])) {
                        sleep(2);
                        continue;
                    }
                    if (isset($updates['data']['next_offset_id'])) {
                        $offset_id = $updates['data']['next_offset_id'];
                        file_put_contents($this->bot->getHashedToken() . '.txt', $offset_id);
                    }
                    foreach ($updates['data']['updates'] as $update) {
                        $this->update = ['update' => $update];
                        $message = new Message($this->bot, $this->update);
                        $this->bot->getChat(['chat_id' => $message->chat_id]);
                        if ($message->sender_id && $this->bot->isUserSpamDetected($message->sender_id)) {
                            continue;
                        }
                        if ($message->sender_id && $this->bot->isUserSpamming($message->sender_id)) {
                            foreach ($this->handlers as $handler) {
                                if ($handler['filter'] instanceof Filter && $handler['filter']->isSpamHandler()) {
                                    $handler['callback']($this->bot);
                                }
                            }
                            continue;
                        }
                        foreach ($this->handlers as $handler) {
                            if ($handler['filter']($message)) {
                                $handler['callback']($message);
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
}