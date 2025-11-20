<?php

require 'vendor/autoload.php';

use RubikaBot\Bot;
use RubikaBot\Filters\Filters;
use RubikaBot\Keyboard\Button;
use RubikaBot\Keyboard\Keypad;

class LogBot {
    private $bot;
    private $adminId;
    private $baseDir;
    private $logPath;
    private $banPath;
    private $userStates = [];
    
    const ID_ADMIN_PANEL = '👨‍💼 پنل ادمین';
    const ID_USER_COUNT = '📊 آمار کاربران';
    const ID_BROADCAST = '📢 ارسال پیام همگانی';
    const ID_SEARCH = '🔍 جستجو در لاگ';
    const ID_VIEW_DATA = '📁 مشاهده اطلاعات';
    const ID_VIEW_BANNED = '🚫 کاربران مسدود';
    const ID_CLEAR_LOGS = '🗑️ پاک کردن لاگ';
    const ID_KICK_USER = '👢 اخراج کاربر';
    const ID_BAN_USER = '🚫 مسدود کردن کاربر';
    const ID_UNBAN_USER = '✅ رفع مسدودیت کاربر';
    const ID_BACK = '↩️ بازگشت';

    // حالت‌های کاربر
    const STATE_BROADCAST = 'broadcast';
    const STATE_SEARCH = 'search';
    const STATE_KICK = 'kick';
    const STATE_BAN = 'ban';
    const STATE_UNBAN = 'unban';

    public function __construct($token) {
        $this->bot = new Bot($token);
        //یوزر گوید ادمین وارد بشه!
        $this->adminId = "YOUR_ADMIN_USER_GUID";
        $this->baseDir = "/storage/emulated/0/LogBot";
        $this->logPath = $this->baseDir . "/user_data.txt";
        $this->banPath = $this->baseDir . "/banned_users.txt";
        
        $this->createDirectories();
        $this->setupHandlers();
    }

    private function createDirectories() {
        if (!is_dir($this->baseDir)) {
            mkdir($this->baseDir, 0777, true);
        }
    }

    private function createMainKeypad($isAdmin = false) {
        $keypad = Keypad::make();
        if ($isAdmin) {
            $keypad->row()->add(Button::simple(self::ID_ADMIN_PANEL, self::ID_ADMIN_PANEL));
        }
        return $keypad;
    }

    private function createAdminKeypad() {
        $keypad = Keypad::make();
        $keypad->row()
            ->add(Button::simple(self::ID_USER_COUNT, self::ID_USER_COUNT))
            ->add(Button::simple(self::ID_BROADCAST, self::ID_BROADCAST));
        $keypad->row()
            ->add(Button::simple(self::ID_SEARCH, self::ID_SEARCH))
            ->add(Button::simple(self::ID_VIEW_DATA, self::ID_VIEW_DATA));
        $keypad->row()
            ->add(Button::simple(self::ID_VIEW_BANNED, self::ID_VIEW_BANNED))
            ->add(Button::simple(self::ID_CLEAR_LOGS, self::ID_CLEAR_LOGS));
        $keypad->row()
            ->add(Button::simple(self::ID_KICK_USER, self::ID_KICK_USER))
            ->add(Button::simple(self::ID_BAN_USER, self::ID_BAN_USER));
        $keypad->row()
            ->add(Button::simple(self::ID_UNBAN_USER, self::ID_UNBAN_USER))
            ->add(Button::simple(self::ID_BACK, self::ID_BACK));
        return $keypad;
    }

    private function createBackKeypad() {
        $keypad = Keypad::make();
        $keypad->row()->add(Button::simple(self::ID_BACK, self::ID_BACK));
        return $keypad;
    }

    private function saveLog($data) {
        file_put_contents($this->logPath, $data . "\n" . str_repeat("-", 50) . "\n", FILE_APPEND);
        echo "📂 لاگ ذخیره شد: " . $this->logPath . "\n";
    }

    private function getChatIds() {
        if (!file_exists($this->logPath)) {
            return [];
        }
        
        $chatIds = [];
        $content = file_get_contents($this->logPath);
        $lines = explode("\n", $content);
        
        foreach ($lines as $line) {
            if (strpos($line, "💬 چت آیدی: ") === 0) {
                $chatId = str_replace("💬 چت آیدی: ", "", trim($line));
                if (!empty($chatId)) {
                    $chatIds[] = $chatId;
                }
            }
        }
        
        return array_unique($chatIds);
    }

    private function getUserCount() {
        $chatIds = $this->getChatIds();
        return count($chatIds);
    }

    private function searchLogs($keyword) {
        $results = [];
        
        if (file_exists($this->logPath)) {
            $content = file_get_contents($this->logPath);
            $blocks = explode(str_repeat("-", 50), $content);
            
            foreach ($blocks as $block) {
                $block = trim($block);
                if (!empty($block) && stripos($block, $keyword) !== false) {
                    $results[] = $block;
                }
            }
        }
        
        return $results;
    }

    private function getAllData() {
        if (file_exists($this->logPath)) {
            return file_get_contents($this->logPath);
        }
        return null;
    }

    private function removeUser($chatId) {
        if (!file_exists($this->logPath)) {
            return;
        }
        
        $content = file_get_contents($this->logPath);
        $blocks = explode(str_repeat("-", 50), $content);
        $newBlocks = [];
        
        $skip = false;
        foreach ($blocks as $block) {
            $block = trim($block);
            if (empty($block)) {
                continue;
            }
            
            if (strpos($block, "💬 چت آیدی: " . $chatId) !== false) {
                $skip = true;
                continue;
            }
            
            if (!$skip) {
                $newBlocks[] = $block;
            } else {
                $skip = false;
            }
        }
        
        file_put_contents($this->logPath, implode("\n" . str_repeat("-", 50) . "\n", $newBlocks) . "\n" . str_repeat("-", 50) . "\n");
    }

    private function banUser($chatId) {
        $banned = [];
        if (file_exists($this->banPath)) {
            $banned = file($this->banPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        }
        
        if (!in_array($chatId, $banned)) {
            $banned[] = $chatId;
            file_put_contents($this->banPath, implode("\n", $banned) . "\n");
        }
    }

    private function unbanUser($chatId) {
        if (file_exists($this->banPath)) {
            $banned = file($this->banPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $key = array_search($chatId, $banned);
            
            if ($key !== false) {
                unset($banned[$key]);
                file_put_contents($this->banPath, implode("\n", $banned) . "\n");
            }
        }
    }

    private function getBannedUsers() {
        if (file_exists($this->banPath)) {
            return file($this->banPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        }
        return [];
    }

    private function isBanned($chatId) {
        if (file_exists($this->banPath)) {
            $banned = file($this->banPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            return in_array($chatId, $banned);
        }
        return false;
    }

    private function clearLogs() {
        if (file_exists($this->logPath)) {
            unlink($this->logPath);
        }
        return true;
    }

    private function splitMessage($text, $maxLength = 4000) {
        $parts = [];
        $length = strlen($text);
        
        for ($i = 0; $i < $length; $i += $maxLength) {
            $parts[] = substr($text, $i, $maxLength);
        }
        
        return $parts;
    }

    private function sendMessagesSafely($chatId, $messages) {
        foreach ($messages as $part) {
            try {
                if (!empty(trim($part))) {
                    $this->bot->chat($chatId)->message($part)->send();
                    usleep(500000); // تأخیر 0.5 ثانیه
                }
            } catch (Exception $e) {
                error_log("خطا در ارسال پیام به $chatId: " . $e->getMessage());
                continue;
            }
        }
    }

    private function setupHandlers() {
        // هندلر دستور /start
        $this->bot->onMessage(
            Filters::command('start'),
            function(Bot $bot, $message) {
                $chatId = $message->chat_id;
                $senderId = $message->sender_id;
                
                if ($this->isBanned($senderId)) {
                    $bot->chat($chatId)->message("🚫 شما مسدود شده‌اید!")->send();
                    return;
                }

                // پاک کردن حالت کاربر
                if (isset($this->userStates[$senderId])) {
                    unset($this->userStates[$senderId]);
                }

                $isAdmin = ($senderId === $this->adminId);
                $keypad = $this->createMainKeypad($isAdmin);
                
                $bot->chat($chatId)
                    ->message("🤖 به ربات لاگ خوش آمدید!\n\nپیام‌های شما به صورت خودکار ذخیره می‌شوند.")
                    ->chatKeypad($keypad->toArray())
                    ->send();
            }
        );

        // هندلر دستور /help
        $this->bot->onMessage(
            Filters::command('help'),
            function(Bot $bot, $message) {
                $chatId = $message->chat_id;
                $senderId = $message->sender_id;
                
                if ($senderId !== $this->adminId) {
                    $bot->chat($chatId)->message("❌ شما دسترسی به این دستور ندارید!")->send();
                    return;
                }

                $helpText = "
📜 دستورات ادمین:
✅ /to متن پیام
✅ /send chat_id متن
✅ /kick chat_id
✅ /ban chat_id
✅ /unban chat_id
✅ /search keyword
✅ /data
✅ /list
✅ /banned
✅ /path
✅ /help
                ";
                
                $bot->chat($chatId)->message($helpText)->send();
            }
        );

        // هندلر پیام‌های معمولی
        $this->bot->onMessage(
            Filters::text(),
            function(Bot $bot, $message) {
                $chatId = $message->chat_id;
                $senderId = $message->sender_id;
                $text = $message->text;
                
                if ($this->isBanned($senderId)) {
                    return;
                }

                // بارگذاری اطلاعات کاربر
                $message->loadChatInfo($bot);
                $firstName = $message->first_name ?? 'کاربر';
                $username = $message->user_name ?? 'ندارد';

                // بررسی حالت کاربر
                if (isset($this->userStates[$senderId])) {
                    $this->handleUserState($bot, $message, $senderId, $chatId, $text);
                    return;
                }

                // ذخیره لاگ
                $logData = 
                    "💬 پیام: " . $text . "\n" .
                    "👤 فرستنده: " . $senderId . "\n" .
                    "🔗 یوزرنیم: @" . $username . "\n" .
                    "💬 چت آیدی: " . $chatId . "\n" .
                    "⏰ زمان: " . date('Y-m-d H:i:s');
                
                $this->saveLog($logData);

                // پاسخ به کاربر عادی
                if ($senderId !== $this->adminId) {
                    $bot->chat($chatId)->message("✅ پیام شما ثبت شد.")->send();
                    return;
                }

                // دستورات ادمین
                $isAdmin = ($senderId === $this->adminId);

                switch ($text) {
                    case self::ID_ADMIN_PANEL:
                        $bot->chat($chatId)
                            ->message("👨‍💼 پنل مدیریت")
                            ->chatKeypad($this->createAdminKeypad()->toArray())
                            ->send();
                        break;

                    case self::ID_USER_COUNT:
                        $count = $this->getUserCount();
                        $bot->chat($chatId)
                            ->message("📊 تعداد کاربران ثبت شده: " . $count)
                            ->chatKeypad($this->createAdminKeypad()->toArray())
                            ->send();
                        break;

                    case self::ID_BROADCAST:
                        $this->userStates[$senderId] = self::STATE_BROADCAST;
                        $bot->chat($chatId)
                            ->message("📢 لطفاً پیام همگانی را ارسال کنید:")
                            ->chatKeypad($this->createBackKeypad()->toArray())
                            ->send();
                        break;

                    case self::ID_SEARCH:
                        $this->userStates[$senderId] = self::STATE_SEARCH;
                        $bot->chat($chatId)
                            ->message("🔍 لطفاً کلمه کلیدی برای جستجو وارد کنید:")
                            ->chatKeypad($this->createBackKeypad()->toArray())
                            ->send();
                        break;

                    case self::ID_VIEW_DATA:
                        $data = $this->getAllData();
                        if ($data) {
                            $messages = $this->splitMessage($data);
                            $this->sendMessagesSafely($chatId, $messages);
                            $bot->chat($chatId)
                                ->message("📂 مسیر فایل لاگ: " . $this->logPath)
                                ->chatKeypad($this->createAdminKeypad()->toArray())
                                ->send();
                        } else {
                            $bot->chat($chatId)
                                ->message("❌ هنوز اطلاعاتی ثبت نشده.")
                                ->chatKeypad($this->createAdminKeypad()->toArray())
                                ->send();
                        }
                        break;

                    case self::ID_VIEW_BANNED:
                        $banned = $this->getBannedUsers();
                        $messageText = "🚫 کاربران مسدود شده:\n";
                        $messageText .= empty($banned) ? "هیچ کاربری مسدود نیست" : implode("\n", $banned);
                        $bot->chat($chatId)
                            ->message($messageText)
                            ->chatKeypad($this->createAdminKeypad()->toArray())
                            ->send();
                        break;

                    case self::ID_CLEAR_LOGS:
                        if ($this->clearLogs()) {
                            $bot->chat($chatId)
                                ->message("✅ لاگ‌ها با موفقیت پاک شدند.")
                                ->chatKeypad($this->createAdminKeypad()->toArray())
                                ->send();
                        } else {
                            $bot->chat($chatId)
                                ->message("❌ خطا در پاک کردن لاگ‌ها.")
                                ->chatKeypad($this->createAdminKeypad()->toArray())
                                ->send();
                        }
                        break;

                    case self::ID_KICK_USER:
                        $this->userStates[$senderId] = self::STATE_KICK;
                        $bot->chat($chatId)
                            ->message("👢 لطفاً آیدی کاربر برای اخراج را وارد کنید:")
                            ->chatKeypad($this->createBackKeypad()->toArray())
                            ->send();
                        break;

                    case self::ID_BAN_USER:
                        $this->userStates[$senderId] = self::STATE_BAN;
                        $bot->chat($chatId)
                            ->message("🚫 لطفاً آیدی کاربر برای مسدود کردن را وارد کنید:")
                            ->chatKeypad($this->createBackKeypad()->toArray())
                            ->send();
                        break;

                    case self::ID_UNBAN_USER:
                        $this->userStates[$senderId] = self::STATE_UNBAN;
                        $bot->chat($chatId)
                            ->message("✅ لطفاً آیدی کاربر برای رفع مسدودیت را وارد کنید:")
                            ->chatKeypad($this->createBackKeypad()->toArray())
                            ->send();
                        break;

                    case self::ID_BACK:
                        if (isset($this->userStates[$senderId])) {
                            unset($this->userStates[$senderId]);
                        }
                        $bot->chat($chatId)
                            ->message("منوی اصلی")
                            ->chatKeypad($this->createMainKeypad(true)->toArray())
                            ->send();
                        break;

                    default:
                        // دستورات متنی ادمین
                        if (str_starts_with($text, '/to ')) {
                            $broadcastText = substr($text, 4);
                            $this->sendBroadcast($bot, $chatId, $broadcastText);
                        }
                        elseif (str_starts_with($text, '/send ')) {
                            $parts = explode(' ', $text, 3);
                            if (count($parts) === 3) {
                                $targetId = $parts[1];
                                $messageText = $parts[2];
                                
                                try {
                                    $bot->chat($targetId)->message($messageText)->send();
                                    $bot->chat($chatId)
                                        ->message("✅ پیام به " . $targetId . " ارسال شد")
                                        ->chatKeypad($this->createAdminKeypad()->toArray())
                                        ->send();
                                } catch (Exception $e) {
                                    $bot->chat($chatId)
                                        ->message("❌ خطا در ارسال پیام")
                                        ->chatKeypad($this->createAdminKeypad()->toArray())
                                        ->send();
                                }
                            }
                        }
                        elseif (str_starts_with($text, '/kick ')) {
                            $targetId = trim(substr($text, 6));
                            $this->removeUser($targetId);
                            $bot->chat($chatId)
                                ->message("✅ کاربر " . $targetId . " حذف شد")
                                ->chatKeypad($this->createAdminKeypad()->toArray())
                                ->send();
                        }
                        elseif (str_starts_with($text, '/ban ')) {
                            $targetId = trim(substr($text, 5));
                            $this->banUser($targetId);
                            $bot->chat($chatId)
                                ->message("🚫 کاربر " . $targetId . " مسدود شد")
                                ->chatKeypad($this->createAdminKeypad()->toArray())
                                ->send();
                        }
                        elseif (str_starts_with($text, '/unban ')) {
                            $targetId = trim(substr($text, 7));
                            $this->unbanUser($targetId);
                            $bot->chat($chatId)
                                ->message("✅ کاربر " . $targetId . " رفع مسدودیت شد")
                                ->chatKeypad($this->createAdminKeypad()->toArray())
                                ->send();
                        }
                        elseif (str_starts_with($text, '/search ')) {
                            $keyword = trim(substr($text, 8));
                            $this->handleSearch($bot, $chatId, $keyword);
                        }
                        elseif ($text == '/data') {
                            $data = $this->getAllData();
                            if ($data) {
                                $messages = $this->splitMessage($data);
                                $this->sendMessagesSafely($chatId, $messages);
                                $bot->chat($chatId)
                                    ->message("📂 مسیر فایل لاگ: " . $this->logPath)
                                    ->chatKeypad($this->createAdminKeypad()->toArray())
                                    ->send();
                            } else {
                                $bot->chat($chatId)
                           
