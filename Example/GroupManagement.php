<?php

require_once 'Rubika-Bot/Bot.php';
require_once 'Rubika-Bot/Message.php';
require_once 'Rubika-Bot/Filters/Filter.php';
require_once 'Rubika-Bot/Filters/Filters.php';

use RubikaBot\Bot;
use RubikaBot\Filters\Filters;

$bot = new Bot('YOUR_BOT_TOKEN');

echo "🤖 ربات مدیریت گروه راه‌اندازی شد...\n";

$bot->onMessage(Filters::any(), function(Bot $bot, $message) {
    
    $chatType = $message->chat_type ?? 'Unknown';
    $isGroup = ($chatType === 'Group');
    
    if ($isGroup) {
        $text = $message->text ?? '';
        $chatId = $message->chat_id ?? '';
        
        if (containsLink($text)) {
            $message->delete($bot);
            $bot->chat($chatId)->message("🔗 ارسال لینک در گروه ممنوع است!")->send();
            return;
        }
        
        if (containsPhoneNumber($text)) {
            $message->delete($bot);
            $bot->chat($chatId)->message("📞 ارسال شماره تماس مجاز نیست!")->send();
            return;
        }
        
        $badWords = ['فحش۱', 'فحش۲', 'توهین'];
        if (containsBadWords($text, $badWords)) {
            $message->delete($bot);
            $bot->chat($chatId)->message("🚫 استفاده از کلمات نامناسب ممنوع!")->send();
            return;
        }
    }
});

function containsLink($text) {
    if (empty($text)) return false;
    $patterns = ['http://', 'https://', 'www.', '.ir', '.com', '.org', '.net', 't.me/', '@'];
    foreach ($patterns as $pattern) {
        if (stripos($text, $pattern) !== false) return true;
    }
    return false;
}

function containsPhoneNumber($text) {
    return !empty($text) && (preg_match('/09[0-9]{9}/', $text) || preg_match('/0[0-9]{10}/', $text));
}

function containsBadWords($text, $badWords) {
    if (empty($text)) return false;
    foreach ($badWords as $word) {
        if (stripos($text, $word) !== false) return true;
    }
    return false;
}

$bot->run();
