<?php

namespace RubikaBot\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RubikaBot\Core\Bot;
use RubikaBot\Exceptions\ValidationException;

class BotTest extends TestCase
{
    public function testSendMessage()
    {
        $bot = new Bot('test_token');
        $this->expectException(ValidationException::class);
        $bot->message()->send(); // Should throw because chat_id and text are missing
    }

    public function testGetMe()
    {
        $bot = new Bot('test_token');
        // Mock ApiClient for testing
        $this->assertIsArray($bot->getMe());
    }
}
