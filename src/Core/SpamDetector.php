<?php

namespace RubikaBot\Core;

use Redis;
use RubikaBot\Exceptions\ApiException;

class SpamDetector
{
    private Redis $redis;
    private int $maxMessages;
    private int $timeWindow;
    private int $cooldown;
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->redis = new Redis();
        $this->redis->connect($config['redis_host'] ?? '127.0.0.1', $config['redis_port'] ?? 6379);
        $this->maxMessages = $config['max_messages'] ?? 10;
        $this->timeWindow = $config['time_window'] ?? 15;
        $this->cooldown = $config['cooldown'] ?? 120;
    }

    public function isSpamming(string $userId): bool
    {
        $now = time();
        $key = "spam:user:{$userId}";
        $count = (int) $this->redis->get($key);

        if (!$count) {
            $this->redis->setEx($key, $this->timeWindow, 1);
            return false;
        }

        $count++;
        $this->redis->setEx($key, $this->timeWindow, $count);

        if ($count > $this->maxMessages) {
            $this->redis->setEx("spam:cooldown:{$userId}", $this->cooldown, 1);
            return true;
        }

        return false;
    }

    public function isUserSpamDetected(string $userId): bool
    {
        if (!$this->redis->exists("spam:cooldown:{$userId}")) {
            return false;
        }

        if (time() - $this->redis->get("spam:cooldown:{$userId}") > $this->cooldown) {
            $this->redis->del("spam:cooldown:{$userId}", "spam:user:{$userId}");
            return false;
        }

        return true;
    }

    public function resetUserSpamState(string $userId): void
    {
        $this->redis->del("spam:user:{$userId}", "spam:cooldown:{$userId}");
    }

    public function getUserMessageCount(string $userId): int
    {
        return (int) $this->redis->get("spam:user:{$userId}");
    }

    public function cleanupSpamData(int $expireTime = 86400): void
    {
        $now = time();
        $keys = $this->redis->keys("spam:user:*");
        foreach ($keys as $key) {
            $lastTime = (int) $this->redis->get($key);
            if ($now - $lastTime > $expireTime) {
                $userId = str_replace("spam:user:", "", $key);
                $this->redis->del($key, "spam:cooldown:{$userId}");
            }
        }
    }
}