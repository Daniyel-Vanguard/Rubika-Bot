<?php

require_once 'vendor/autoload.php';

use RubikaBot\Bot;
use RubikaBot\Filters\Filters;
use RubikaBot\Message;

$BOT_TOKEN = "YOUR_TOKEN_BOT";//توکن بات شما اینجا قرار میگیره!
$GROUP_CHAT_IDS = ["GROUP_CHAT_ID"];//چت ایدی گروه اینجا قرار میگیره و میتونید گروه های بیشتری هم اضافه کنید!
$ADMIN_SENDER_ID = "ADMIN_USER_GUID";//یوزر گوید ادمین اینجا قرار بگیره!

$app = new Bot($BOT_TOKEN);

$processedMessages = [];

$app->onMessage(
    Filters::any(), 
    function(Bot $client, Message $update) use ($ADMIN_SENDER_ID, $GROUP_CHAT_IDS, &$processedMessages) {
        
        if ($update->update_type !== 'NewMessage') {
            return;
        }
        
        $messageKey = $update->chat_id . '_' . $update->message_id;
        if (in_array($messageKey, $processedMessages)) {
            return;
        }
        
        $processedMessages[] = $messageKey;
        
        if (count($processedMessages) > 100) {
            array_shift($processedMessages);
        }
        
        if (!in_array($update->chat_id, $GROUP_CHAT_IDS)) {
            return;
        }
        
        if ($update->sender_id != $ADMIN_SENDER_ID) {
            return;
        }
        
        if (empty($update->text) || empty(trim($update->text))) {
            return;
        }
        
        try {
            $rawUpdate = $client->getUpdate();
            $replyToMessageId = null;
            
            if (isset($rawUpdate['update']['new_message']['reply_to_message_id'])) {
                $replyToMessageId = $rawUpdate['update']['new_message']['reply_to_message_id'];
            }
            
            usleep(100000);
            
            $update->delete($client);
            
            usleep(200000);
            
            $messageBuilder = $client->chat($update->chat_id)
                  ->message($update->text);
                  
            if ($replyToMessageId) {
                $messageBuilder->replyTo($replyToMessageId);
            }
            
            $messageBuilder->send();
            
        } catch (Exception $e) {
        }
    }
);

$app->run();
