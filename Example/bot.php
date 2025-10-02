<?php

require 'vendor/autoload.php';

use RubikaBot\Bot;
use RubikaBot\Filters\Filters;
use RubikaBot\Types\ChatType;
use RubikaBot\Keyboard\Button;
use RubikaBot\Keyboard\Keypad;
use RubikaBot\Keyboard\KeypadRow;

$bot = new Bot('YOUR_BOT_TOKEN');

// پاسخ به پیام "سلام"
$bot->onMessage(Filters::text('سلام'), function(Bot $bot, $message) {
    $keypad = Keypad::make();
    
    // ایجاد ردیف اول
    $row1 = new KeypadRow();
    $row1->add(Button::simple('help', 'راهنما'));
    $row1->add(Button::simple('about', 'درباره ما'));
    $keypad->addRow($row1);
    
    // ایجاد ردیف دوم
    $row2 = new KeypadRow();
    $row2->add(Button::simple('contact', 'تماس با ما'));
    $keypad->addRow($row2);
        
    $bot->chat($message->chat_id)
        ->message('سلام! چطور می‌تونم کمک کنم؟')
        ->chatKeypad($keypad->toArray())
        ->send();
});

// یا به روش ساده‌تر:
$bot->onMessage(Filters::text('راهنما'), function(Bot $bot, $message) {
    $keypad = Keypad::make();
    
    // استفاده از متد row() که روی Keypad وجود دارد
    $keypad->row()->add(Button::simple('back', 'بازگشت'));
        
    $bot->chat($message->chat_id)
        ->message('این راهنمای ربات است...')
        ->chatKeypad($keypad->toArray())
        ->send();
});

// پاسخ به کامند /start
$bot->onMessage(Filters::command('start'), function(Bot $bot, $message) {
    $keypad = Keypad::make();
    $keypad->row()->add(Button::simple('help', 'راهنما'));
    
    $bot->chat($message->chat_id)
        ->message('به ربات خوش آمدید!')
        ->chatKeypad($keypad->toArray())
        ->send();
});

// پاسخ به فایل‌های ارسالی
$bot->onMessage(Filters::file(), function(Bot $bot, $message) {
    $bot->chat($message->chat_id)
        ->message('فایل شما دریافت شد!')
        ->replyTo($message->message_id)
        ->send();
});

// مدیریت اسپم
$bot->onMessage(Filters::spam(5, 10), function(Bot $bot, $message) {
    $bot->chat($message->chat_id)
        ->message('لطفاً پیام‌های خود را با سرعت کمتر ارسال کنید.')
        ->send();
});

$bot->run();
