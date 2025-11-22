<?php
require_once 'vendor/autoload.php';

use RubikaBot\Bot;
use RubikaBot\Filters\Filters;
use RubikaBot\Keyboard\Button;
use RubikaBot\Keyboard\Keypad;

class GroupManagerBot {
    private $bot;
    private $adminId = 'YOUR_USER_GUID';//یوزر گوید ادمین اینجا قرار بگیره!
    private $dbFile = __DIR__ . '/bot_database.json';
    
    private $processedMessages = [];
    private $dbData = [];

    const BTN_BROADCAST = 'broadcast';
    const BTN_GROUP_MSG = 'group_message';
    const BTN_STATS = 'stats';
    const BTN_BACK = 'back';
    const BTN_CONFIRM_SEND = 'confirm_send';
    const BTN_CANCEL_SEND = 'cancel_send';

    public function __construct($token) {
        $this->bot = new Bot($token);
        $this->initDatabase();
        $this->setupHandlers();
    }

    private function initDatabase() {
        if (file_exists($this->dbFile)) {
            $content = file_get_contents($this->dbFile);
            $this->dbData = json_decode($content, true) ?: [];
        }
        
        // ساختار اولیه دیتابیس
        if (empty($this->dbData)) {
            $this->dbData = [
                'users' => [],
                'groups' => [],
                'user_states' => []
            ];
        }
        
        // اطمینان از وجود تمام کلیدهای لازم
        $this->dbData['users'] = $this->dbData['users'] ?? [];
        $this->dbData['groups'] = $this->dbData['groups'] ?? [];
        $this->dbData['user_states'] = $this->dbData['user_states'] ?? [];
        
        $this->saveDatabase();
        echo "✅ JSON database initialized successfully\n";
    }

    private function saveDatabase() {
        file_put_contents($this->dbFile, json_encode($this->dbData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function saveUser($userId, $firstName, $username, $chatId, $isBot = false) {
        $firstName = trim(preg_replace('/[\x00-\x1F\x7F]/u', '', $firstName));
        if (empty($firstName)) {
            $firstName = 'کاربر';
        }
        
        $username = $username ? trim($username) : null;
        
        $this->dbData['users'][$userId] = [
            'user_id' => $userId,
            'first_name' => $firstName,
            'username' => $username,
            'chat_id' => $chatId,
            'is_bot' => $isBot,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->saveDatabase();
    }

    private function saveGroup($groupId, $title, $memberCount = 0) {
        $this->dbData['groups'][$groupId] = [
            'group_id' => $groupId,
            'title' => $title,
            'member_count' => $memberCount,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->saveDatabase();
    }

    private function saveUserState($userId, $state, $data = null) {
        $this->dbData['user_states'][$userId] = [
            'user_id' => $userId,
            'state' => $state,
            'data' => $data,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $this->saveDatabase();
    }

    private function getUserState($userId) {
        return $this->dbData['user_states'][$userId] ?? null;
    }

    private function clearUserState($userId) {
        unset($this->dbData['user_states'][$userId]);
        $this->saveDatabase();
    }

    private function getStats() {
        $users = array_filter($this->dbData['users'], function($user) {
            return !($user['is_bot'] ?? false);
        });
        
        return [
            'users' => count($users),
            'groups' => count($this->dbData['groups'])
        ];
    }

    private function getAllUsers() {
        return array_filter($this->dbData['users'], function($user) {
            return !($user['is_bot'] ?? false);
        });
    }

    private function getAllGroups() {
        return array_values($this->dbData['groups']);
    }

    private function getMainKeypad() {
        $keypad = Keypad::make();
        $keypad->row()
            ->add(Button::simple('help', '📖 راهنما'));
        return $keypad;
    }

    private function getAdminKeypad() {
        $keypad = Keypad::make();
        $keypad->row()
            ->add(Button::simple(self::BTN_BROADCAST, '📢 ارسال به کاربران'))
            ->add(Button::simple(self::BTN_GROUP_MSG, '👥 ارسال به گروه‌ها'));
        $keypad->row()
            ->add(Button::simple(self::BTN_STATS, '📊 آمار ربات'));
        $keypad->row()
            ->add(Button::simple(self::BTN_BACK, '🔙 بازگشت'));
        return $keypad;
    }

    private function getConfirmKeypad() {
        $keypad = Keypad::make();
        $keypad->row()
            ->add(Button::simple(self::BTN_CONFIRM_SEND, '✅ تایید ارسال'))
            ->add(Button::simple(self::BTN_CANCEL_SEND, '❌ انصراف'));
        return $keypad;
    }

    private function getBackKeypad() {
        $keypad = Keypad::make();
        $keypad->row()
            ->add(Button::simple(self::BTN_BACK, '🔙 بازگشت'));
        return $keypad;
    }

    private function containsLink($text) {
        if (empty($text)) return false;
        $patterns = ['http://', 'https://', 'www.', '.ir', '.com', '.org', '.net', 't.me/', '@'];
        foreach ($patterns as $pattern) {
            if (stripos($text, $pattern) !== false) return true;
        }
        return false;
    }

    private function containsPhoneNumber($text) {
        return !empty($text) && (preg_match('/09[0-9]{9}/', $text) || preg_match('/0[0-9]{10}/', $text));
    }

    private function containsBadWords($text, $badWords = []) {
        if (empty($text)) return false;
        //لیست فحش ها قرار بگیره اینجا!
        $defaultBadWords = ["فحش۱" ,"فحش۲" ,"فحش۳"];
        $words = empty($badWords) ? $defaultBadWords : $badWords;
        
        foreach ($words as $word) {
            if (stripos($text, $word) !== false) return true;
        }
        return false;
    }

    private function isMessageProcessed($messageId) {
        if (isset($this->processedMessages[$messageId])) {
            return true;
        }
        
        if (count($this->processedMessages) > 1000) {
            array_shift($this->processedMessages);
        }
        
        $this->processedMessages[$messageId] = time();
        return false;
    }

    private function cleanupProcessedMessages() {
        $currentTime = time();
        $timeLimit = 300;
        
        foreach ($this->processedMessages as $messageId => $timestamp) {
            if ($currentTime - $timestamp > $timeLimit) {
                unset($this->processedMessages[$messageId]);
            }
        }
    }

    private function setupHandlers() {
        $this->cleanupProcessedMessages();

        $this->bot->onMessage(
            Filters::command('start'),
            function(Bot $bot, $message) {
                $messageId = $message->message_id ?? uniqid();
                if ($this->isMessageProcessed($messageId)) {
                    return;
                }
                
                $chatType = $message->chat_type ?? 'Unknown';
                $chatId = $message->chat_id;
                $userId = $message->sender_id;
                $firstName = $message->first_name ?? 'کاربر';
                
                $this->saveUser($userId, $firstName, $message->username ?? '', $chatId);
                
                if ($chatType === 'User') {
                    $keypad = $this->getMainKeypad();
                    
                    if ($userId === $this->adminId) {
                        $keypad = $this->getAdminKeypad();
                        $welcomeText = "👑 **پنل مدیریت ربات**\n\nبه پنل مدیریت خوش آمدید!";
                    } else {
                        $welcomeText = "🤖 **ربات مدیریت گروه**\n\nبه ربات خوش آمدید!";
                    }
                    
                    $bot->chat($chatId)
                        ->message($welcomeText)
                        ->chatKeypad($keypad->toArray())
                        ->send();
                } else if ($chatType === 'Group') {
                    $groupId = $message->group_id ?? $chatId;
                    $groupTitle = $message->group_title ?? 'گروه';
                    $this->saveGroup($groupId, $groupTitle);
                    
                    $bot->chat($chatId)->message("🤖 من فعالم! برای مدیریت گروه آماده‌ام.")->send();
                }
            }
        );

        $this->bot->onMessage(
            Filters::text('ربات'),
            function(Bot $bot, $message) {
                $messageId = $message->message_id ?? uniqid();
                if ($this->isMessageProcessed($messageId)) {
                    return;
                }
                
                $chatType = $message->chat_type ?? 'Unknown';
                if ($chatType === 'Group') {
                    $chatId = $message->chat_id;
                    $bot->chat($chatId)->message("✅ بله، من فعالم! برای کمک تایپ /help کنید.")->send();
                }
            }
        );

        $this->bot->onMessage(
            Filters::command('help'),
            function(Bot $bot, $message) {
                $messageId = $message->message_id ?? uniqid();
                if ($this->isMessageProcessed($messageId)) {
                    return;
                }
                
                $chatId = $message->chat_id;
                $chatType = $message->chat_type ?? 'Unknown';
                
                if ($chatType === 'User') {
                    $helpText = "📖 **راهنمای ربات مدیریت گروه**\n\n"
                              . "🔸 **مدیریت گروه:**\n"
                              . "• حذف خودکار لینک‌ها\n"
                              . "• فیلتر شماره تماس\n"
                              . "• فیلتر کلمات نامناسب\n\n"
                              . "🔹 **پنل مدیریت:**\n"
                              . "• آمار کاربران و گروه‌ها\n"
                              . "• ارسال پیام به کاربران\n"
                              . "• ارسال پیام به گروه‌ها";
                } else {
                    $helpText = "🤖 **راهنمای ربات در گروه**\n\n"
                              . "من به صورت خودکار:\n"
                              . "• لینک‌ها رو حذف می‌کنم\n"
                              . "• شماره تماس رو فیلتر می‌کنم\n"
                              . "• کلمات نامناسب رو مدیریت می‌کنم";
                }
                
                $bot->chat($chatId)->message($helpText)->send();
            }
        );

        $this->bot->onMessage(
            Filters::command('panel'),
            function(Bot $bot, $message) {
                $messageId = $message->message_id ?? uniqid();
                if ($this->isMessageProcessed($messageId)) {
                    return;
                }
                
                $chatId = $message->chat_id;
                $userId = $message->sender_id;
                
                if ($userId !== $this->adminId) {
                    $bot->chat($chatId)->message("❌ شما دسترسی به پنل مدیریت ندارید.")->send();
                    return;
                }
                
                $keypad = $this->getAdminKeypad();
                $bot->chat($chatId)
                    ->message("👑 **پنل مدیریت ربات**")
                    ->chatKeypad($keypad->toArray())
                    ->send();
            }
        );

        $this->bot->onMessage(
            Filters::text(),
            function(Bot $bot, $message) {
                $messageId = $message->message_id ?? uniqid();
                if ($this->isMessageProcessed($messageId)) {
                    return;
                }
                
                $chatType = $message->chat_type ?? 'Unknown';
                $userId = $message->sender_id;
                $text = $message->text ?? '';
                $chatId = $message->chat_id;
                
                if ($chatType !== 'User') {
                    $this->handleRegularMessage($bot, $message);
                    return;
                }
                
                if ($userId === $this->adminId) {
                    $userState = $this->getUserState($userId);
                    $currentState = $userState ? $userState['state'] : null;
                    $stateData = $userState ? $userState['data'] : null;
                    
                    switch ($text) {
                        case self::BTN_BROADCAST:
                        case '📢 ارسال به کاربران':
                            $this->saveUserState($userId, 'awaiting_broadcast');
                            $keypad = $this->getBackKeypad();
                            $bot->chat($chatId)
                                ->message("📢 **ارسال به کاربران**\n\nلطفاً متن پیام برای ارسال به کاربران را وارد کنید:")
                                ->chatKeypad($keypad->toArray())
                                ->send();
                            return;
                            
                        case self::BTN_GROUP_MSG:
                        case '👥 ارسال به گروه‌ها':
                            $this->saveUserState($userId, 'awaiting_group_message');
                            $keypad = $this->getBackKeypad();
                            $bot->chat($chatId)
                                ->message("👥 **ارسال به گروه‌ها**\n\nلطفاً متن پیام برای ارسال به گروه‌ها را وارد کنید:")
                                ->chatKeypad($keypad->toArray())
                                ->send();
                            return;
                            
                        case self::BTN_STATS:
                        case '📊 آمار ربات':
                            $stats = $this->getStats();
                            $groups = $this->getAllGroups();
                            $totalMembers = 0;
                            foreach ($groups as $group) {
                                $totalMembers += $group['member_count'] ?? 0;
                            }
                            
                            $statsText = "📊 **آمار کامل ربات**\n\n"
                                       . "👤 **کاربران:** " . $stats['users'] . "\n"
                                       . "👥 **گروه‌ها:** " . $stats['groups'] . "\n"
                                       . "📈 **کل اعضا:** " . $totalMembers . "\n\n"
                                       . "🕒 **آخرین بروزرسانی:** " . date('Y-m-d H:i:s');
                            
                            $keypad = $this->getAdminKeypad();
                            $bot->chat($chatId)
                                ->message($statsText)
                                ->chatKeypad($keypad->toArray())
                                ->send();
                            return;
                            
                        case self::BTN_BACK:
                        case '🔙 بازگشت':
                            $this->clearUserState($userId);
                            $keypad = $this->getAdminKeypad();
                            $bot->chat($chatId)
                                ->message("👑 **پنل مدیریت ربات**")
                                ->chatKeypad($keypad->toArray())
                                ->send();
                            return;
                            
                        case self::BTN_CONFIRM_SEND:
                        case '✅ تایید ارسال':
                            if ($currentState === 'confirm_broadcast' && $stateData) {
                                $messageText = $stateData['text'] ?? '';
                                $messageType = $stateData['type'] ?? '';
                                
                                if ($messageType === 'users') {
                                    $users = $this->getAllUsers();
                                    $success = 0;
                                    $failed = 0;
                                    
                                    foreach ($users as $user) {
                                        try {
                                            $bot->chat($user['chat_id'])->message("📢 **پیام از مدیریت:**\n\n" . $messageText)->send();
                                            $success++;
                                            usleep(300000);
                                        } catch (Exception $e) {
                                            $failed++;
                                        }
                                    }
                                    
                                    $this->clearUserState($userId);
                                    $keypad = $this->getAdminKeypad();
                                    $bot->chat($chatId)
                                        ->message("✅ ارسال به کاربران انجام شد!\n\n✅ موفق: {$success}\n❌ ناموفق: {$failed}")
                                        ->chatKeypad($keypad->toArray())
                                        ->send();
                                        
                                } elseif ($messageType === 'groups') {
                                    $groups = $this->getAllGroups();
                                    $success = 0;
                                    $failed = 0;
                                    
                                    foreach ($groups as $group) {
                                        try {
                                            $bot->chat($group['group_id'])->message("📢 **پیام از مدیریت:**\n\n" . $messageText)->send();
                                            $success++;
                                            usleep(300000);
                                        } catch (Exception $e) {
                                            $failed++;
                                        }
                                    }
                                    
                                    $this->clearUserState($userId);
                                    $keypad = $this->getAdminKeypad();
                                    $bot->chat($chatId)
                                        ->message("✅ ارسال به گروه‌ها انجام شد!\n\n✅ موفق: {$success}\n❌ ناموفق: {$failed}")
                                        ->chatKeypad($keypad->toArray())
                                        ->send();
                                }
                            }
                            return;
                            
                        case self::BTN_CANCEL_SEND:
                        case '❌ انصراف':
                            $this->clearUserState($userId);
                            $keypad = $this->getAdminKeypad();
                            $bot->chat($chatId)
                                ->message("❌ ارسال لغو شد.")
                                ->chatKeypad($keypad->toArray())
                                ->send();
                            return;
                    }
                    
                    if ($currentState === 'awaiting_broadcast') {
                        $keypad = $this->getConfirmKeypad();
                        $this->saveUserState($userId, 'confirm_broadcast', [
                            'text' => $text,
                            'type' => 'users'
                        ]);
                        $bot->chat($chatId)
                            ->message("📢 **پیش‌نمایش پیام برای کاربران:**\n\n" . $text . "\n\nآیا از ارسال این پیام به کاربران اطمینان دارید؟")
                            ->chatKeypad($keypad->toArray())
                            ->send();
                        return;
                    }
                    
                    if ($currentState === 'awaiting_group_message') {
                        $keypad = $this->getConfirmKeypad();
                        $this->saveUserState($userId, 'confirm_broadcast', [
                            'text' => $text,
                            'type' => 'groups'
                        ]);
                        $bot->chat($chatId)
                            ->message("👥 **پیش‌نمایش پیام برای گروه‌ها:**\n\n" . $text . "\n\nآیا از ارسال این پیام به گروه‌ها اطمینان دارید؟")
                            ->chatKeypad($keypad->toArray())
                            ->send();
                        return;
                    }
                }
                
                $keypad = $this->getMainKeypad();
                if ($userId === $this->adminId) {
                    $keypad = $this->getAdminKeypad();
                }
                
                $bot->chat($chatId)
                    ->message("لطفاً از منوی زیر انتخاب کنید:")
                    ->chatKeypad($keypad->toArray())
                    ->send();
            }
        );

        $this->bot->onMessage(Filters::any(), function(Bot $bot, $message) {
            $messageId = $message->message_id ?? uniqid();
            if ($this->isMessageProcessed($messageId)) {
                return;
            }
            
            $this->handleRegularMessage($bot, $message);
        });
    }

    private function handleRegularMessage(Bot $bot, $message) {
        $chatType = $message->chat_type ?? 'Unknown';
        $isGroup = ($chatType === 'Group');
        
        if ($isGroup) {
            $text = $message->text ?? '';
            $chatId = $message->chat_id ?? '';
            $groupId = $message->group_id ?? $chatId;
            $groupTitle = $message->group_title ?? 'گروه';
            
            $this->saveGroup($groupId, $groupTitle);
            
            if ($this->containsLink($text)) {
                echo "🔗 لینک شناسایی شد: " . $text . "\n";
                try {
                    $message->delete($bot);
                    $bot->chat($chatId)->message("🔗 ارسال لینک در گروه ممنوع است!")->send();
                } catch (Exception $e) {
                    echo "خطا در حذف پیام: " . $e->getMessage() . "\n";
                }
                return;
            }
            
            if ($this->containsPhoneNumber($text)) {
                echo "📞 شماره تماس شناسایی شد: " . $text . "\n";
                try {
                    $message->delete($bot);
                    $bot->chat($chatId)->message("📞 ارسال شماره تماس مجاز نیست!")->send();
                } catch (Exception $e) {
                    echo "خطا در حذف پیام: " . $e->getMessage() . "\n";
                }
                return;
            }
            
            $badWords = ["بکیرم", "کیر", "کیرم", "کص", "کیرت", "به کیرت", "به کیرم", "بکیرت",
            "ممه", "سکس", "کسکش", "کصکش", "کسخل", "کصخل", "گایید", "گایدمت",
            "گایدیی", "گاییدیی", "جنده", "جندس", "کون", "کونت", "کونش", "کصت",
            "ننت", "ننه", "ننتو", "ننتم", "ننش", "ننشو", "ننشن", "ننشون", "مادرجنده",
            "مادر قهبه", "پدرسوخته", "خواهرجنده", "خارکسه", "خوارکسه", "خوارمادر",
            "لاشی", "لاشي", "لاشى", "لاشیی", "بیشرف", "بی شرف", "بیناموس", "بی ناموس",
            "جندگي", "جندگى", "جندگيی", "داف", "دافا", "دافال", "دافالز",
            "پورن", "پورنو", "پورنوگرافی", "پورنوگرافى", "پورنوگرافيی",
            "حشیش", "هروئین", "هرويين", "هروئين", "شیشه", "شيشه", "کوکائین", "کوکايين",
            "ک*یر", "ک*ص", "ک*نی", "ج*نده", "ک*ونی", "ک*ونیم", "ک*ونیت",];
            if ($this->containsBadWords($text, $badWords)) {
                echo "🚫 کلمه نامناسب شناسایی شد: " . $text . "\n";
                try {
                    $message->delete($bot);
                    $bot->chat($chatId)->message("🚫 استفاده از کلمات نامناسب ممنوع!")->send();
                } catch (Exception $e) {
                    echo "خطا در حذف پیام: " . $e->getMessage() . "\n";
                }
                return;
            }
            
            echo "💬 پیام عادی در گروه: " . $text . "\n";
        } else {
            $userId = $message->sender_id;
            $firstName = $message->first_name ?? 'کاربر';
            $chatId = $message->chat_id;
            
            $this->saveUser($userId, $firstName, $message->username ?? '', $chatId);
            echo "👤 کاربر در پیوی: " . $firstName . "\n";
        }
    }

    public function run() {
        echo "🤖 ربات مدیریت گروه راه‌اندازی شد...\n";
        echo "✅ قابلیت‌های فعال:\n";
        echo "• فیلتر لینک در گروه‌ها\n";
        echo "• فیلتر شماره تماس\n";
        echo "• فیلتر کلمات نامناسب\n";
        echo "• پنل ادمین با ارسال به کاربران\n";
        echo "• پنل ادمین با ارسال به گروه‌ها\n";
        echo "• نمایش آمار\n";
        echo "• سیستم جلوگیری از ارسال دوتایی\n";
        echo "• دیتابیس JSON (بدون نیاز به MySQL)\n\n";
        
        echo "🔍 در حال گوش دادن به پیام‌ها...\n";
        $this->bot->run();
    }
}

$token = 'YOUR_BOT_TOKEN';//توکن بات اینجا قرار بگیره!
$botManager = new GroupManagerBot($token);
$botManager->run();
