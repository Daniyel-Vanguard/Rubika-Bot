# مستندات کامل کتابخانه RubikaBot PHP
 <img align="center" width="200" height="200" src="https://rubika.ir/static/images/logo.svg"/>
 
### فهرست مطالب

· معرفی
· نصب و راه‌اندازی
· کلاس Bot
· کلاس Message
· فیلترها (Filters)
· کیبوردها (Keyboards)
· فرمت‌بندی متن (Metadata)
· انواع داده‌ها (Types)
· مثال‌های کاربردی
· مدیریت اسپم
· آپلود فایل

## معرفی

کتابخانه RubikaBot یک پکیج PHP برای ساخت ربات‌های روبیکا است. این کتابخانه با معماری شیءگرا و امکانات پیشرفته، توسعه ربات‌ها را بسیار ساده می‌کند.

## ویژگی‌های اصلی:

· ✅ پشتیبانی از Markdown و HTML
· ✅ مدیریت پیشرفته کیبوردها
· ✅ سیستم فیلترینگ قدرتمند
· ✅ مدیریت خودکار اسپم
· ✅ آپلود و ارسال فایل
· ✅ پشتیبانی از انواع پیام‌ها

### نصب و راه‌اندازی

```text
composer require rubikabot/rubikabot:dev-main
```
### راه‌اندازی اولیه:

```php
<?php
require_once 'RubikaBot/Bot.php';
require_once 'RubikaBot/Message.php';
// سایر فایل‌های مورد نیاز...

use RubikaBot\Bot;
use RubikaBot\Filters\Filters;

$token = "YOUR_BOT_TOKEN";
$bot = new Bot($token);

// تعریف هندلرها
$bot->onMessage(Filters::command('start'), function(Bot $bot, Message $msg) {
    $bot->chat($msg->chat_id)
        ->message("سلام! به ربات خوش آمدید 👋")
        ->send();
});

// اجرای ربات
$bot->run();
```

## کلاس Bot

کلاس اصلی برای مدیریت ربات و ارسال پیام‌ها.

### متدهای اصلی:

# سازنده (Daniyel Vanguard)

```php
$bot = new Bot(string $token, array $config = []);
```

#### ارسال پیام متنی

```php
$bot->chat('CHAT_ID')
    ->message('متن پیام')
    ->replyTo('MESSAGE_ID') // اختیاری
    ->send();
```

#### ارسال فایل

```php
$bot->chat('CHAT_ID')
    ->file('/path/to/file.jpg')
    ->caption('توضیح فایل')
    ->sendFile();
```

#### ارسال موقعیت

```php
$bot->chat('CHAT_ID')
    ->location(35.6892, 51.3890) // عرض و طول جغرافیایی
    ->sendLocation();
```

#### ارسال مخاطب

```php
$bot->chat('CHAT_ID')
    ->contact('نام', '09123456789')
    ->sendContact();
```

#### ارسال نظرسنجی

```php
$bot->chat('CHAT_ID')
    ->poll('سوال نظرسنجی', ['گزینه ۱', 'گزینه ۲', 'گزینه ۳'])
    ->sendPoll();
```

#### ویرایش پیام

```php
$bot->chat('CHAT_ID')
    ->messageId('MESSAGE_ID')
    ->message('متن جدید')
    ->editMessage();
```

#### حذف پیام

```php
$bot->chat('CHAT_ID')
    ->messageId('MESSAGE_ID')
    ->delete();
```

#### فوروارد پیام

```php
$bot->forwardFrom('FROM_CHAT_ID')
    ->messageId('MESSAGE_ID')
    ->forwardTo('TO_CHAT_ID')
    ->forward();
```

#### متدهای کمکی:

```php
// دریافت اطلاعات ربات
$bot->getMe();

// دریافت اطلاعات چت
$bot->getChat(['chat_id' => 'CHAT_ID']);

// تنظیم دستورات
$bot->setCommands(['bot_commands' => [...]]);

// تنظیم وب‌هوک
$bot->setEndpoint('https://your-domain.com/webhook');
```

## کلاس Message

کلاس برای مدیریت و آنالیز پیام‌های دریافتی.

ویژگی‌ها:

```php
$message = new Message($updateData);

// دسترسی به ویژگی‌ها
$message->chat_id;      // آیدی چت
$message->sender_id;    // آیدی فرستنده
$message->text;         // متن پیام
$message->message_id;   // آیدی پیام
$message->file_id;      // آیدی فایل
$message->button_id;    // آیدی دکمه
$message->chat_type;    // نوع چت
```

#### متدهای پاسخ:

```php
// پاسخ متنی
$message->reply($bot, 'Markdown');

// پاسخ با فایل
$message->replyFile($bot);

// پاسخ با موقعیت
$message->replyLocation($bot);

// پاسخ با مخاطب
$message->replyContact($bot);

// ویرایش پیام
$message->editText($bot);

// حذف پیام
$message->delete($bot);
```

#### آنالیز متادیتا:

```php
// بررسی فرمت‌بندی متن
if ($message->is_bold) {
    // متن بولد است
}

if ($message->is_italic) {
    // متن ایتالیک است
}

if ($message->has_link) {
    // متن حاوی لینک است
}

// دریافت اطلاعات کامل متادیتا
$metadataInfo = $message->getMetadataInfo();
```

### فیلترها (Filters)

سیستم فیلترینگ پیشرفته برای مدیریت هندلرها.

فیلترهای پایه:

```php
use RubikaBot\Filters\Filters;
use RubikaBot\Types\ChatType;

// فیلتر متن
$bot->onMessage(Filters::text('سلام'), $callback);

// فیلتر دستور
$bot->onMessage(Filters::command('start'), $callback);

// فیلتر دکمه
$bot->onMessage(Filters::button('button_id'), $callback);

// فیلتر نوع چت
$bot->onMessage(Filters::chatType(ChatType::GROUP), $callback);

// فیلتر آیدی چت
$bot->onMessage(Filters::chatId('CHAT_ID'), $callback);

// فیلتر آیدی فرستنده
$bot->onMessage(Filters::senderId('USER_ID'), $callback);

// فیلتر فایل
$bot->onMessage(Filters::file(), $callback);

// فیلتر عکس
$bot->onMessage(Filters::photo(), $callback);

// فیلتر هر پیام
$bot->onMessage(Filters::any(), $callback);
```

#### ترکیب فیلترها:

```php
// AND منطقی
$filter = Filters::command('start')->and(Filters::chatType(ChatType::USER));

// OR منطقی
$filter = Filters::text('سلام')->or(Filters::text('hello'));

$bot->onMessage($filter, $callback);
```

#### فیلتر اسپم:

```php
$bot->onMessage(Filters::spam(5, 10, 120), function(Bot $bot, Message $msg) {
    // کاربر اسپم کرده است
    $bot->chat($msg->chat_id)
        ->message('لطفاً سرعت ارسال پیام خود را کاهش دهید!')
        ->send();
});
```

### کیبوردها (Keyboards)

سیستم قدرتمند برای ساخت کیبوردهای اینلاین و معمولی.

ساخت کیبورد اینلاین:

```php
use RubikaBot\Keyboard\Keypad;
use RubikaBot\Keyboard\Button;

$keypad = Keypad::make()
    ->row()
        ->add(Button::simple('btn1', 'دکمه ۱'))
        ->add(Button::simple('btn2', 'دکمه ۲'))
    ->row()
        ->add(Button::simple('btn3', 'دکمه ۳'));

$bot->chat('CHAT_ID')
    ->message('پیام با کیبورد')
    ->inlineKeypad($keypad->toArray())
    ->send();
```

#### انواع دکمه‌ها:

```php
// دکمه ساده
Button::simple('id', 'متن');

// دکمه انتخابی
Button::selection('id', 'عنوان', ['گزینه۱', 'گزینه۲']);

// دکمه تقویم
Button::calendar('id', 'انتخاب تاریخ', 'DatePicker');

// دکمه انتخاب عدد
Button::numberPicker('id', 'انتخاب عدد', 1, 100);

// دکمه انتخاب رشته
Button::stringPicker('id', 'انتخاب', ['آیتم۱', 'آیتم۲']);

// دکمه موقعیت
Button::location('id', 'ارسال موقعیت');

// دکمه لینک
Button::link('id', 'باز کردن لینک', 'url', $linkObject);

// دکمه پرداخت
Button::payment('id', 'پرداخت');

// و انواع دیگر...
```

#### کیبورد چت (Reply Keyboard):

```php
$chatKeypad = Keypad::make()
    ->setResize(true)
    ->setOnetime(false)
    ->row()
        ->add(Button::simple('menu', 'منو'))
    ->row()
        ->add(Button::simple('help', 'راهنما'));

$bot->chat('CHAT_ID')
    ->message('پیام با کیبورد چت')
    ->chatKeypad($chatKeypad->toArray(), 'New')
    ->send();
```

### فرمت‌بندی متن (Metadata)

پشتیبانی از Markdown و HTML برای فرمت‌بندی متن.

استفاده از Markdown:

```php
$bot->chat('CHAT_ID')
    ->message('متن **بولد** و __ایتالیک__ و `کد`')
    ->setParseMode('Markdown')
    ->send();
```

#### استفاده از HTML:

```php
$bot->chat('CHAT_ID')
    ->message('متن <b>بولد</b> و <i>ایتالیک</i>')
    ->setParseMode('HTML')
    ->send();
```

#### ابزارهای کمکی فرمت‌بندی:

```php
use RubikaBot\Metadata\Utils;

$text = Utils::Bold('متن بولد') . "\n" .
        Utils::Italic('متن ایتالیک') . "\n" .
        Utils::Hyperlink('متن لینک', 'https://example.com');

$bot->chat('CHAT_ID')
    ->message($text)
    ->send();
```

### انواع فرمت‌بندی موجود:

· Bold: **متن**
· Italic: __متن__
· Underline: --متن--
· Strike: ~~متن~~
· Mono:  `متن` 
· Spoiler: ||متن||
· Code:  ```متن``` 
· Quote: ##متن##
· Link: [متن](URL)

### انواع داده‌ها (Types)

انواع چت:

```php
use RubikaBot\Types\ChatType;

ChatType::USER;     // کاربر
ChatType::GROUP;    // گروه
ChatType::CHANNEL;  // کانال
ChatType::BOT;      // ربات
```

#### انواع آپدیت:

```php
use RubikaBot\Types\UpdateType;

UpdateType::MESSAGE;           // پیام جدید
UpdateType::EDIT_MESSAGE;      // ویرایش پیام
UpdateType::DELETE_MESSAGE;    // حذف پیام
UpdateType::CALLBACK_QUERY;    // کلیک دکمه
UpdateType::INLINE_QUERY;      // جستجوی اینلاین
```

#### انواع لینک دکمه:

```php
use RubikaBot\Types\ButtonLinkType;

ButtonLinkType::URL;           // لینک وب
ButtonLinkType::JoinChannel;   // پیوستن به کانال
```

### مثال‌های کاربردی

ربات ساده:

```php
<?php
require_once 'RubikaBot/Bot.php';
require_once 'RubikaBot/Message.php';
require_once 'RubikaBot/Filters/Filters.php';

use RubikaBot\Bot;
use RubikaBot\Filters\Filters;

$bot = new Bot('YOUR_TOKEN');

// دستور start
$bot->onMessage(Filters::command('start'), function(Bot $bot, Message $msg) {
    $bot->chat($msg->chat_id)
        ->message('به ربات خوش آمدید! 🎉')
        ->send();
});

// پاسخ به متن
$bot->onMessage(Filters::text('سلام'), function(Bot $bot, Message $msg) {
    $bot->chat($msg->chat_id)
        ->message('سلام! چطور می‌تونم کمک کنم؟')
        ->send();
});

// مدیریت فایل
$bot->onMessage(Filters::file(), function(Bot $bot, Message $msg) {
    $bot->chat($msg->chat_id)
        ->message('فایل شما دریافت شد! 📁')
        ->send();
});

$bot->run();
```

### ربات پیشرفته با کیبورد:

```php
<?php
require_once 'RubikaBot/Bot.php';
require_once 'RubikaBot/Message.php';
require_once 'RubikaBot/Filters/Filters.php';
require_once 'RubikaBot/Keyboard/Keypad.php';
require_once 'RubikaBot/Keyboard/Button.php';

use RubikaBot\Bot;
use RubikaBot\Filters\Filters;
use RubikaBot\Keyboard\Keypad;
use RubikaBot\Keyboard\Button;

$bot = new Bot('YOUR_TOKEN');

// منوی اصلی
$mainMenu = Keypad::make()
    ->row()
        ->add(Button::simple('profile', '👤 پروفایل'))
        ->add(Button::simple('settings', '⚙️ تنظیمات'))
    ->row()
        ->add(Button::simple('help', '📖 راهنما'))
        ->add(Button::simple('about', 'ℹ️ درباره ما'));

$bot->onMessage(Filters::command('start'), function(Bot $bot, Message $msg) use ($mainMenu) {
    $bot->chat($msg->chat_id)
        ->message('منوی اصلی:')
        ->inlineKeypad($mainMenu->toArray())
        ->send();
});

// مدیریت کلیک دکمه‌ها
$bot->onMessage(Filters::button('profile'), function(Bot $bot, Message $msg) {
    $bot->chat($msg->chat_id)
        ->message('اطلاعات پروفایل شما...')
        ->send();
});

$bot->run();
```

### مدیریت اسپم

کتابخانه دارای سیستم مدیریت اسپم داخلی است:

تنظیمات پیش‌فرض:

· حداکثر ۱۰ پیام در ۱۵ ثانیه
· زمان سرد شدن: ۱۲۰ ثانیه

#### تنظیمات سفارشی:

```php
$bot->setMaxMessages(5);      // 5 پیام در بازه زمانی
$bot->setTimeWindow(10);      // بازه 10 ثانیه
$bot->setCooldown(60);        // 60 ثانیه محرومیت
```

#### مدیریت دستی:

```php
// بررسی اسپم کاربر
if ($bot->isUserSpamming($userId)) {
    // کاربر در حال اسپم است
}

// بررسی محرومیت
if ($bot->isUserSpamDetected($userId)) {
    // کاربر محروم شده است
}

// بازنشانی وضعیت اسپم
$bot->resetUserSpamState($userId);

// دریافت تعداد پیام‌های کاربر
$count = $bot->getUserMessageCount($userId);
```

### آپلود فایل

#### ارسال فایل از مسیر محلی:

```php
$result = $bot->chat('CHAT_ID')
    ->file('/path/to/image.jpg')
    ->caption('توضیح عکس')
    ->sendFile();

$fileId = $result['file_id']; // ذخیره برای استفاده بعدی
```

#### ارسال فایل با file_id:

```php
$bot->chat('CHAT_ID')
    ->file_id('FILE_ID_FROM_PREVIOUS_UPLOAD')
    ->file_type('Image')
    ->sendFile();
```

#### دانلود فایل:

```php
// دریافت لینک دانلود
$downloadUrl = $bot->getFile('FILE_ID');

// دانلود و ذخیره فایل
$bot->downloadFile('FILE_ID', '/path/to/save/file.jpg');
```

### تشخیص خودکار نوع فایل:

کتابخانه به طور خودکار نوع فایل را بر اساس MIME type تشخیص می‌دهد:

· image/jpeg, image/png → Image
· image/gif → Gif
· video/mp4 → Video
· audio/mpeg → File
· و سایر فرمت‌ها → File
## آموزش صفر تا صد در یوتیوب:
<div align="center">

[![learn RubikaBot](https://img.shields.io/badge/YouTube-ویدیوهای_آموزشی-FF0000?style=for-the-badge&logo=youtube&logoColor=white)](https://youtube.com/playlist?list=PLPF5RMxQ-2_gUJL-RpbPj2bm4gMMTNlHd&si=rILoxjFIsoR8zYdG)

</div>

## و نحوه کار کردن با گوشی اندروید:
<div align="center">

[![ویدیوهای آموزشی](https://img.shields.io/badge/YouTube-ویدیوهای_آموزشی-FF0000?style=for-the-badge&logo=youtube&logoColor=white)](https://youtube.com/playlist?list=PLPF5RMxQ-2_j1N325MV7yrHOsl-fxyLOF&si=PIms3U5ljXjOwUBK)

</div>
