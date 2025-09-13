مستندات کامل کتابخانه RubikaBot

📚 RubikaBot PHP Library

یک کتابخانه قدرتمند و ساده برای ساخت ربات‌های روبیکا با PHP.

📦 نصب و راه‌اندازی

نیازمندی‌ها

· PHP 7.4 یا بالاتر
· فعال بودن extension curl
· توکن ربات روبیکا

نصب

```php
// شامل کردن فایل‌های کتابخانه
require_once 'RubikaBot/Bot.php';
require_once 'RubikaBot/Message.php';
require_once 'RubikaBot/Filters/Filter.php';
require_once 'RubikaBot/Filters/Filters.php';
require_once 'RubikaBot/Types/ChatType.php';
require_once 'RubikaBot/Keyboard/Button.php';
require_once 'RubikaBot/Keyboard/Keypad.php';
require_once 'RubikaBot/Keyboard/KeypadRow.php';
```

🚀 شروع سریع

```php
use RubikaBot\Bot;
use RubikaBot\Filters\Filters;

$bot = new Bot('YOUR_BOT_TOKEN');

$bot->onMessage(Filters::command('start'), function(Bot $bot, $message) {
    $bot->chat($message->chat_id)
        ->message('به ربات خوش آمدید! 🎉')
        ->send();
});

$bot->run();
```

📋 کلاس Bot

متدهای اصلی

__construct(string $token, array $config = [])

ساخت نمونه ربات

```php
$bot = new Bot('your_bot_token', [
    'timeout' => 30,
    'max_retries' => 3,
    'parse_mode' => 'Markdown'
]);
```

onMessage($filter, callable $callback)

ثبت هندلر برای پیام‌ها

```php
$bot->onMessage(Filters::text('سلام'), function(Bot $bot, $message) {
    // پردازش پیام
});
```

run()

اجرای ربات

```php
$bot->run();
```

متدهای ارسال پیام

chat(string $chat_id)

تنظیم چت ID

```php
$bot->chat('123456789');
```

message(string $text)

تنظیم متن پیام

```php
$bot->message('سلام دنیا!');
```

send()

ارسال پیام متنی

```php
$bot->chat('123456789')->message('سلام!')->send();
```

sendFile()

ارسال فایل

```php
$bot->chat('123456789')
    ->file('/path/to/file.jpg')
    ->caption('توضیح تصویر')
    ->sendFile();
```

sendPoll()

ارسال نظرسنجی

```php
$bot->chat('123456789')
    ->poll('نظر شما چیست؟', ['گزینه ۱', 'گزینه ۲', 'گزینه ۳'])
    ->sendPoll();
```

مدیریت کیبورد

chatKeypad(array $keypad, ?string $keypad_type = 'New')

تنظیم کیبورد معمولی

```php
$keypad = Keypad::make()->row()->add(Button::simple('btn1', 'دکمه ۱'));
$bot->chatKeypad($keypad->toArray());
```

inlineKeypad(array $keypad)

تنظیم inline کیبورد

```php
$bot->inlineKeypad($keypad->toArray());
```

🎛️ کلاس Filters

فیلترهای موجود

text(?string $match = null)

فیلتر متن

```php
Filters::text('سلام') // متن دقیق
Filters::text() // هر متنی
```

command(string $command)

فیلتر دستور

```php
Filters::command('start') // /start
```

button(string $button)

فیلتر دکمه

```php
Filters::button('btn1') // دکمه با ID btn1
```

chatType(ChatType $chat)

فیلتر نوع چت

```php
use RubikaBot\Types\ChatType;
Filters::chatType(ChatType::GROUP);
```

file()

فیلتر فایل

```php
Filters::file();
```

any()

هر نوع پیام

```php
Filters::any();
```

spam(int $maxMessages = 5, int $timeWindow = 10, int $cooldown = 120)

فیلتر تشخیص اسپم

```php
Filters::spam(5, 10, 120); // 5 پیام در 10 ثانیه
```

⌨️ کلاس‌های کیبورد

Keypad

```php
use RubikaBot\Keyboard\Keypad;
use RubikaBot\Keyboard\KeypadRow;
use RubikaBot\Keyboard\Button;

$keypad = Keypad::make();
$row = $keypad->row();
$row->add(Button::simple('btn1', 'دکمه ۱'));
$row->add(Button::simple('btn2', 'دکمه ۲'));
```

Button انواع دکمه‌ها

دکمه ساده

```php
Button::simple('id', 'متن دکمه');
```

دکمه انتخاب

```php
Button::selection('id', 'عنوان', ['آیتم ۱', 'آیتم ۲'], true, 2);
```

دکمه تقویم

```php
Button::calendar('id', 'عنوان', 'type', 'min', 'max');
```

دکمه شماره

```php
Button::numberPicker('id', 'عنوان', 1, 100, 50);
```

دکمه لینک

```php
Button::link('id', 'عنوان', 'url', $linkObject);
```

💬 کلاس Message

ویژگی‌ها

```php
$message->chat_id; // ID چت
$message->sender_id; // ID فرستنده
$message->text; // متن پیام
$message->message_id; // ID پیام
$message->file_id; // ID فایل
$message->file_name; // نام فایل
$message->button_id; // ID دکمه
```

متدهای پاسخ

```php
$message->reply($bot); // پاسخ به پیام
$message->replyFile($bot); // پاسخ با فایل
$message->delete($bot); // حذف پیام
```

🛡️ مدیریت اسپم

```php
// تنظیم محدودیت اسپم
$bot->maxMessages = 5; // حداکثر 5 پیام
$bot->timeWindow = 10; // در 10 ثانیه
$bot->cooldown = 120; // تحریم 120 ثانیه

// بررسی اسپم
if ($bot->isUserSpamming($userId)) {
    // کاربر در حال اسپم است
}

// ریست وضعیت اسپم
$bot->resetUserSpamState($userId);
```

🌐 متدهای API

getMe()

دریافت اطلاعات ربات

```php
$botInfo = $bot->getMe();
```

getChat(array $data)

دریافت اطلاعات چت

```php
$chatInfo = $bot->getChat(['chat_id' => '123456789']);
```

getUpdates(array $data = [])

دریافت آپدیت‌ها

```php
$updates = $bot->getUpdates(['limit' => 100]);
```

setCommands(array $data)

تنظیم دستورات

```php
$bot->setCommands([
    'bot_commands' => [
        ['command' => 'start', 'description' => 'شروع ربات'],
        ['command' => 'help', 'description' => 'راهنما']
    ]
]);
```

📁 مدیریت فایل

requestSendFile(string $type)

درخواست آپلود فایل

```php
$uploadUrl = $bot->requestSendFile('Image');
```

getFile(string $file_id)

دریافت لینک دانلود فایل

```php
$downloadUrl = $bot->getFile('file_id_here');
```

downloadFile(string $file_id, string $to)

دانلود فایل

```php
$bot->downloadFile('file_id_here', '/path/to/save.jpg');
```

🔧 کانفیگ

تنظیمات پیش‌فرض

```php
$bot = new Bot('token', [
    'timeout' => 30,           // timeout برای درخواست‌ها
    'max_retries' => 3,        // حداکثر تلاش مجدد
    'parse_mode' => 'Markdown', // حالت تجزیه متن
    'salt' => 'RubikaBot'      // نمک برای هش توکن
]);
```

🚨 مدیریت خطا

هندلینگ خطا

```php
try {
    $bot->run();
} catch (Exception $e) {
    error_log('Error: ' . $e->getMessage());
    // مدیریت خطا
}
```

لاگ‌گیری

```php
// فعال کردن لاگ
error_log('Bot started: ' . date('Y-m-d H:i:s'));

// لاگ پاسخ API
$response = $bot->send();
error_log('API Response: ' . json_encode($response));
```

📝 مثال کامل

```php
<?php

require_once 'RubikaBot/Bot.php';
require_once 'RubikaBot/Message.php';
require_once 'RubikaBot/Filters/Filter.php';
require_once 'RubikaBot/Filters/Filters.php';
require_once 'RubikaBot/Types/ChatType.php';
require_once 'RubikaBot/Keyboard/Button.php';
require_once 'RubikaBot/Keyboard/Keypad.php';
require_once 'RubikaBot/Keyboard/KeypadRow.php';

use RubikaBot\Bot;
use RubikaBot\Filters\Filters;
use RubikaBot\Types\ChatType;
use RubikaBot\Keyboard\Button;
use RubikaBot\Keyboard\Keypad;
use RubikaBot\Keyboard\KeypadRow;

$bot = new Bot('YOUR_BOT_TOKEN');

// منوی اصلی
$bot->onMessage(Filters::command('start'), function(Bot $bot, $message) {
    $keypad = Keypad::make();
    
    $row1 = $keypad->row();
    $row1->add(Button::simple('help', '📖 راهنما'));
    $row1->add(Button::simple('about', 'ℹ️ درباره'));
    
    $row2 = $keypad->row();
    $row2->add(Button::simple('contact', '📞 تماس'));
    
    $bot->chat($message->chat_id)
        ->message('به ربات خوش آمدید! 🌟')
        ->chatKeypad($keypad->toArray())
        ->send();
});

// مدیریت تمام پیام‌ها
$bot->onMessage(Filters::any(), function(Bot $bot, $message) {
    if ($message->text && !str_starts_with($message->text, '/')) {
        $bot->chat($message->chat_id)
            ->message('از منوی زیر انتخاب کنید:')
            ->send();
    }
});

$bot->run();
```

📊 ساختار دایرکتوری

```
RubikaBot/
├── Bot.php              # کلاس اصلی ربات
├── Message.php          # کلاس مدیریت پیام
├── Filters/
│   ├── Filter.php       # کلاس پایه فیلتر
│   └── Filters.php      # فیلترهای آماده
├── Types/
│   └── ChatType.php     # انواع چت
└── Keyboard/
    ├── Button.php       # کلاس دکمه
    ├── ButtonLink.php   # دکمه لینک
    ├── Keypad.php       # کلاس کیبورد
    └── KeypadRow.php    # کلاس ردیف کیبورد
```

🎯 بهترین practices

1. همیشه از try-catch استفاده کنید
2. لاگ‌گیری مناسب پیاده‌سازی کنید
3. مدیریت اسپم را فعال نگه دارید
4. از فیلترهای مناسب استفاده کنید
5. ربات را روی وب‌سرور با SSL اجرا کنید

📞 پشتیبانی

برای گزارش باگ یا پیشنهاد ویژگی‌های جدید:

· ایجاد Issue در GitHub
· ارسال Pull Request
· تماس از طریق ایمیل

📜 لایسنس

این پروژه تحت لایسنس MIT منتشر شده است.

---

📖 مستندات کامل با مثال‌های کاربردی و بهترین practices
