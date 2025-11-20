<?php

require 'vendor/autoload.php';

use RubikaBot\Bot;
use RubikaBot\Filters\Filters;

$bot = new Bot('YOUR_BOT_TOKEN');

$bot->onMessage(Filters::command('start'), function(Bot $bot, $message) {
    $chatId = $message->chat_id;

    $message->loadChatInfo($bot);

    $chatType  = $message->chat_type ?? 'نامشخص';
    $userId = $message->sender_id ?? 'نامشخص';
    $firstName = $message->first_name ?? 'نامشخص';
    $username = $message->user_name ?? 'نامشخص';

    $text = "اطلاعات کاربر \n\n"
        . "User ID 
$userId\n"
        . "first Name : $firstName\n"
        . "Username : @$username\n"
        . "chat ID : $chatId\n"
        . "type : $chatType\n";

    $bot->chat($chatId)->message($text)->send();
});

$bot->run();
