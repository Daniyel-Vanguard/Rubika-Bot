<?php

require 'vendor/autoload.php';

use RubikaBot\Bot;
use RubikaBot\Filters\Filters;
use RubikaBot\Types\ChatType;
use RubikaBot\Keyboard\Button;
use RubikaBot\Keyboard\Keypad;
use RubikaBot\Keyboard\KeypadRow;

$bot = new Bot('YOUR_BOT_TOKEN_HERE');

// هندلر شروع
$bot->onMessage(Filters::command('start'), function(Bot $bot, $message) {
    $keypad = Keypad::make();
    
    $row1 = $keypad->row();
    $row1->add(Button::simple('products', '🛍️ محصولات'));
    $row1->add(Button::simple('services', '🎯 خدمات'));
    
    $row2 = $keypad->row();
    $row2->add(Button::simple('support', '📞 پشتیبانی'));
    $row2->add(Button::simple('about', 'ℹ️ درباره ما'));
    
    $row3 = $keypad->row();
    $row3->add(Button::simple('contact', '📩 تماس با ما'));

    $bot->chat($message->chat_id)
        ->message('به ربات خوش آمدید! 🌟' . PHP_EOL . 'لطفا یک گزینه انتخاب کنید:')
        ->inlineKeypad($keypad->toArray())
        ->send();
});

// منوی محصولات
$bot->onMessage(Filters::button('products'), function(Bot $bot, $message) {
    $keypad = Keypad::make();
    
    $row1 = $keypad->row();
    $row1->add(Button::simple('product1', '📱 محصول ۱'));
    $row1->add(Button::simple('product2', '💻 محصول ۲'));
    
    $row2 = $keypad->row();
    $row2->add(Button::simple('product3', '⌚ محصول ۳'));
    $row2->add(Button::simple('back_main', '🔙 بازگشت'));

    $bot->chat($message->chat_id)
        ->message('📦 *محصولات ما:*' . PHP_EOL . PHP_EOL .
                 '• محصول ۱ - توضیحات مختصر' . PHP_EOL .
                 '• محصول ۲ - توضیحات مختصر' . PHP_EOL .
                 '• محصول ۳ - توضیحات مختصر')
        ->inlineKeypad($keypad->toArray())
        ->send();
});

// منوی خدمات
$bot->onMessage(Filters::button('services'), function(Bot $bot, $message) {
    $keypad = Keypad::make();
    
    $row1 = $keypad->row();
    $row1->add(Button::simple('service1', '🎨 طراحی'));
    $row1->add(Button::simple('service2', '💻 توسعه'));
    
    $row2 = $keypad->row();
    $row2->add(Button::simple('service3', '📱 پشتیبانی'));
    $row2->add(Button::simple('back_main', '🔙 بازگشت'));

    $bot->chat($message->chat_id)
        ->message('🎯 *خدمات ما:*' . PHP_EOL . PHP_EOL .
                 '• طراحی وبسایت و اپلیکیشن' . PHP_EOL .
                 '• توسعه نرم‌افزارهای اختصاصی' . PHP_EOL .
                 '• پشتیبانی و نگهداری')
        ->inlineKeypad($keypad->toArray())
        ->send();
});

// پشتیبانی
$bot->onMessage(Filters::button('support'), function(Bot $bot, $message) {
    $keypad = Keypad::make();
    
    $row1 = $keypad->row();
    $row1->add(Button::simple('back_main', '🔙 بازگشت'));

    $bot->chat($message->chat_id)
        ->message('📞 *پشتیبانی:*' . PHP_EOL . PHP_EOL .
                 '• شماره تماس: ۰۹۱۲XXXXXXX' . PHP_EOL .
                 '• ایمیل: support@example.com' . PHP_EOL .
                 '• ساعت کاری: ۹ صبح تا ۵ عصر')
        ->inlineKeypad($keypad->toArray())
        ->send();
});

// درباره ما
$bot->onMessage(Filters::button('about'), function(Bot $bot, $message) {
    $keypad = Keypad::make();
    
    $row1 = $keypad->row();
    $row1->add(Button::simple('back_main', '🔙 بازگشت'));

    $bot->chat($message->chat_id)
        ->message('🏢 *درباره ما:*' . PHP_EOL . PHP_EOL .
                 'ما یک تیم متخصص در زمینه توسعه ربات‌های روبیکا هستیم. ' .
                 'با سال‌ها تجربه در زمینه برنامه‌نویسی و طراحی.')
        ->inlineKeypad($keypad->toArray())
        ->send();
});

// تماس با ما
$bot->onMessage(Filters::button('contact'), function(Bot $bot, $message) {
    $keypad = Keypad::make();
    
    $row1 = $keypad->row();
    $row1->add(Button::simple('back_main', '🔙 بازگشت'));

    $bot->chat($message->chat_id)
        ->message('📩 *تماس با ما:*' . PHP_EOL . PHP_EOL .
                 '• آدرس: تهران، خیابان مثال' . PHP_EOL .
                 '• تلفن: ۰۲۱-XXXXXXX' . PHP_EOL .
                 '• موبایل: ۰۹۱۲XXXXXXX')
        ->inlineKeypad($keypad->toArray())
        ->send();
});

// بازگشت به منوی اصلی
$bot->onMessage(Filters::button('back_main'), function(Bot $bot, $message) {
    $keypad = Keypad::make();
    
    $row1 = $keypad->row();
    $row1->add(Button::simple('products', '🛍️ محصولات'));
    $row1->add(Button::simple('services', '🎯 خدمات'));
    
    $row2 = $keypad->row();
    $row2->add(Button::simple('support', '📞 پشتیبانی'));
    $row2->add(Button::simple('about', 'ℹ️ درباره ما'));
    
    $row3 = $keypad->row();
    $row3->add(Button::simple('contact', '📩 تماس با ما'));

    $bot->chat($message->chat_id)
        ->message('منوی اصلی:')
        ->inlineKeypad($keypad->toArray())
        ->send();
});

// هندلر برای محصولات فردی
$bot->onMessage(Filters::button('product1'), function(Bot $bot, $message) {
    $keypad = Keypad::make();
    
    $row1 = $keypad->row();
    $row1->add(Button::simple('buy_product1', '🛒 خرید'));
    $row1->add(Button::simple('back_products', '🔙 بازگشت'));

    $bot->chat($message->chat_id)
        ->message('📱 *محصول ۱*' . PHP_EOL . PHP_EOL .
                 'توضیحات کامل محصول ۱...' . PHP_EOL .
                 '💰 قیمت: ۱۰۰,۰۰۰ تومان')
        ->inlineKeypad($keypad->toArray())
        ->send();
});

// هندلر پیش فرض برای پیام‌های متنی
$bot->onMessage(Filters::any(), function(Bot $bot, $message) {
    if ($message->text && !str_starts_with($message->text, '/')) {
        $bot->chat($message->chat_id)
            ->message('برای شروع از دستور /start استفاده کنید.')
            ->send();
    }
});

$bot->run();
