<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\Participant;
use App\Models\Game;
use App\Http\Controllers\GameController;

class TelegramBotCommand extends Command
{
    protected $signature = 'telegram:run';
    protected $description = 'Run the Secret Santa Telegram Bot';

    private string $token;
    private int $offset = 0;

    private array $menuButtons = [
        ['🎁 Створити гру', '🎅 Кому я дарую?'],
        ['📝 Мій Wishlist', '📢 Сповістити всіх'],
        ['⚙️ Налаштування гри']
    ];

    public function handle()
    {
        $this->token = config('services.telegram.bot_token') ?? env('TELEGRAM_BOT_TOKEN');

        if (!$this->token) {
            $this->error('TELEGRAM_BOT_TOKEN not found in environment or config.');
            return 1;
        }

        $this->info('Secret Santa Bot is running...');
        $this->setBotCommands();

        while (true) {
            try {
                // Heartbeat for health checking
                Cache::put('telegram_bot_last_seen', now()->toDateTimeString(), 60);

                $response = Http::timeout(60)->get("https://api.telegram.org/bot{$this->token}/getUpdates", [
                    'offset' => $this->offset,
                    'timeout' => 30, // Telegram side long polling timeout
                ]);

                if ($response->successful()) {
                    $updates = $response->json('result', []);
                    foreach ($updates as $update) {
                        $this->processUpdate($update);
                        $this->offset = $update['update_id'] + 1;
                    }
                }
            } catch (\Exception $e) {
                // Ignore client-side timeouts to avoid cluttered logs
                if (!str_contains($e->getMessage(), 'timed out')) {
                    $this->error('Error: ' . $e->getMessage());
                    sleep(5);
                }
            }
            usleep(100000); // 100ms
        }
    }

    private function setBotCommands()
    {
        Http::post("https://api.telegram.org/bot{$this->token}/setMyCommands", [
            'commands' => [
                ['command' => 'start', 'description' => 'Запустити бота'],
                ['command' => 'newgame', 'description' => 'Створити нову гру'],
                ['command' => 'who', 'description' => 'Кому я дарую?'],
                ['command' => 'wishlist', 'description' => 'Оновити мій wishlist'],
                ['command' => 'notify', 'description' => 'Сповістити учасників (для організатора)'],
                ['command' => 'settings', 'description' => 'Налаштування гри (для організатора)'],
                ['command' => 'cancel', 'description' => 'Скасувати поточну дію'],
            ]
        ]);
    }

    private function processUpdate(array $update)
    {
        if (!isset($update['message'])) return;

        $message = $update['message'];
        $chatId = $message['chat']['id'] ?? null;
        $text = $message['text'] ?? '';
        $from = $message['from'] ?? [];
        $username = strtolower(ltrim($from['username'] ?? '', '@'));

        if (!$chatId || !$username) return;

        if ($text == '🎁 Створити гру' || $text == '/newgame') {
            $this->handleNewGame($chatId);
        } elseif ($text == '🎅 Кому я дарую?' || $text == '/who') {
            $this->handleWho($chatId);
        } elseif ($text == '📝 Мій Wishlist' || $text == '/wishlist') {
            $this->handleWishlist($chatId, $text);
        } elseif ($text == '📢 Сповістити всіх' || $text == '/notify') {
            $this->handleNotify($chatId);
        } elseif ($text == '⚙️ Налаштування гри' || $text == '/settings') {
            $this->handleSettings($chatId);
        } elseif ($text == '/cancel' || $text == '🔙 Назад' || $text == '❌ Скасувати') {
            $this->handleCancel($chatId);
        } elseif (str_starts_with($text, '/start')) {
            $payload = trim(str_replace('/start', '', $text));
            if (str_starts_with($payload, 'auth_')) {
                $this->handleAuthToken($chatId, $payload, $username);
            } else {
                $this->handleStart($chatId, $username);
            }
        } else {
            $this->handleState($chatId, $text);
        }
    }

    private function handleCancel($chatId)
    {
        Cache::forget("bot_state_$chatId");
        Cache::forget("bot_game_title_$chatId");
        Cache::forget("bot_game_description_$chatId");
        Cache::forget("bot_wishlist_p_id_$chatId");
        Cache::forget("bot_edit_game_id_$chatId");
        $this->sendMessage($chatId, "Повернулися до головного меню. Що робимо далі?");
    }

    private function handleState($chatId, $text)
    {
        $state = Cache::get("bot_state_$chatId");
        if (!$state) return;

        if ($state === 'waiting_for_title') {
            Cache::put("bot_game_title_$chatId", $text, 3600);
            Cache::put("bot_state_$chatId", 'waiting_for_description', 3600);
            $this->sendMessage($chatId, "Гарна назва! Тепер напиши короткий опис для гри (наприклад, бюджет подарунка).\n\nЯкщо опис не потрібен — напиши «-».");
        } elseif ($state === 'waiting_for_description') {
            $description = ($text === '-') ? null : $text;
            Cache::put("bot_game_description_$chatId", $description, 3600);
            Cache::put("bot_state_$chatId", 'waiting_for_participants', 3600);
            $this->sendMessage($chatId, "Чудово! Тепер надішли список учасників. Кожен учасник з нового рядка.\n\nПриклад:\nПавло @durov\nАліса @alice\n@bob");
        } elseif ($state === 'waiting_for_participants') {
            $this->createGameFromBot($chatId, $text);
        } elseif ($state === 'waiting_for_game_selection') {
            $this->processGameSelection($chatId, $text);
        } elseif ($state === 'waiting_for_wishlist') {
            $this->updateWishlistFromBot($chatId, $text);
        } elseif ($state === 'waiting_for_settings_game_selection') {
            $this->processSettingsGameSelection($chatId, $text);
        } elseif ($state === 'waiting_for_settings_action') {
            $this->processSettingsAction($chatId, $text);
        } elseif ($state === 'waiting_for_edit_title') {
            $this->updateGameField($chatId, 'title', $text);
        } elseif ($state === 'waiting_for_edit_description') {
            $this->updateGameField($chatId, 'description', $text);
        }
    }

    private function handleNewGame($chatId)
    {
        Cache::put("bot_state_$chatId", 'waiting_for_title', 3600);
        $this->sendMessage($chatId, "Ок, створюємо нову гру! Як назвемо?\n\n(Або /cancel для скасування)", [["❌ Скасувати"]]);
    }

    private function createGameFromBot($chatId, $text)
    {
        $title = Cache::get("bot_game_title_$chatId", "Secret Santa");
        $description = Cache::get("bot_game_description_$chatId");
        $lines = array_filter(array_map('trim', explode("\n", $text)));

        if (count($lines) < 3) {
            $this->sendMessage($chatId, "Потрібно мінімум 3 учасники. Пришли список ще раз.");
            return;
        }

        $game = DB::transaction(function () use ($lines, $title, $description, $chatId) {
            $game = Game::create([
                'title' => $title,
                'description' => $description,
                'expires_at' => now()->addMonths(3),
                'organizer_chat_id' => $chatId,
            ]);

            foreach ($lines as $line) {
                $telegramUsername = null;
                $name = $line;

                if (preg_match('/(@[\w\d_]+)$/i', $line, $matches)) {
                    $rawUsername = $matches[1];
                    $telegramUsername = strtolower(ltrim($rawUsername, '@'));
                    $name = trim(str_replace($rawUsername, '', $line));
                    $name = trim($name, " \t\n\r\0\x0B;,");
                }

                $game->participants()->create([
                    'name' => $name ?: ($telegramUsername ? "@$telegramUsername" : $line),
                    'telegram_username' => $telegramUsername,
                ]);
            }
            return $game;
        });

        Cache::forget("bot_state_$chatId");
        Cache::forget("bot_game_title_$chatId");
        Cache::forget("bot_game_description_$chatId");
        
        $controller = new GameController();
        $controller->assign($game);

        $this->sendMessage($chatId, "Гра «$title» створена і пари розподілені! 🎄\n\nТепер ти можеш натиснути «📢 Сповістити всіх», щоб кожен отримав своє посилання.");
    }

    private function handleNotify($chatId)
    {
        $game = Game::where('organizer_chat_id', $chatId)->latest()->first();

        if (!$game) {
            $this->sendMessage($chatId, "Ви ще не створювали ігор у боті.");
            return;
        }

        $count = 0;
        foreach ($game->participants as $participant) {
            if ($participant->telegram_chat_id) {
                $link = route('reveal.show', [
                    'gameId' => $game->id,
                    'participantId' => $participant->id,
                    'token' => $participant->reveal_token
                ]);
                $this->sendMessage($participant->telegram_chat_id, "Хо-хо-хо! 🎅 Гра «{$game->title}» почалася!\n\nТвоє посилання для перегляду: $link\n\nМожеш також натиснути «🎅 Кому я дарую?» прямо тут.");
                $count++;
            }
        }

        $this->sendMessage($chatId, "Сповіщення відправлено $count учасникам (тим, хто вже запустив бота).");
    }

    private function handleStart($chatId, $username)
    {
        $participants = Participant::where('telegram_username', $username)->get();

        foreach ($participants as $participant) {
            $participant->update(['telegram_chat_id' => $chatId]);
        }

        $msg = "Привіт! Я бот для Таємного Санти. 🎅\n\nЯ допоможу тобі організувати обмін подарунками або дізнатися, кому ти даруєш подарунок.";
        
        if ($participants->isEmpty()) {
            $msg .= "\n\nНаразі ти не доданий до жодної гри. Коли тебе додадуть за твоїм @username, я зможу тобі про це повідомити.";
        }

        $this->sendMessage($chatId, $msg);
    }

    private function handleWho($chatId)
    {
        $participants = Participant::where('telegram_chat_id', $chatId)->get();

        if ($participants->isEmpty()) {
            $this->sendMessage($chatId, "Ти не береш участі в жодній грі.");
            return;
        }

        foreach ($participants as $participant) {
            $assignment = $participant->assignmentAsSanta;
            $game = $participant->game;
            $gameTitle = $game->title ?? 'Secret Santa';

            if (!$assignment) {
                $this->sendMessage($chatId, "У грі «$gameTitle» пари ще не сформовані.");
                continue;
            }

            $recipient = $assignment->recipient;
            $wishlist = $recipient->wishlist_text ? "\n\nПобажання (wishlist):\n" . $recipient->wishlist_text : "\n\n(У отримувача немає побажань)";
            $description = $game->description ? "\n\nОпис гри: " . $game->description : "";

            $this->sendMessage($chatId, "Гра: $gameTitle$description\n\nВи даруєте подарунок: " . $recipient->name . $wishlist);
        }
    }

    private function handleWishlist($chatId, $text)
    {
        $participants = Participant::where('telegram_chat_id', $chatId)->with('game')->get();

        if ($participants->isEmpty()) {
            $this->sendMessage($chatId, "Ти не береш участі в жодній грі.");
            return;
        }

        $summary = "📋 Твої поточні побажання за іграми:\n\n";
        foreach ($participants as $p) {
            $title = $p->game->title ?? "Без назви";
            $wish = $p->wishlist_text ?? "_(не вказано)_";
            $summary .= "▫️ *{$title}*:\n   {$wish}\n\n";
        }
        
        $this->sendMessage($chatId, $summary);

        if ($participants->count() === 1) {
            $p = $participants->first();
            Cache::put("bot_wishlist_p_id_$chatId", $p->id, 3600);
            $this->askForWishlist($chatId, $p);
            return;
        }

        $buttons = [];
        foreach ($participants as $p) {
            $title = $p->game->title ?? "Гра #{$p->game_id}";
            $buttons[] = ["🎮 $title"];
        }
        $buttons[] = ["🔙 Назад"];

        Cache::put("bot_state_$chatId", 'waiting_for_game_selection', 3600);
        $this->sendMessage($chatId, "Оберіть гру, для якої хочете оновити Wishlist:", $buttons);
    }

    private function processGameSelection($chatId, $text)
    {
        if ($text == '🔙 Назад') return $this->handleCancel($chatId);
        $gameTitle = str_replace('🎮 ', '', $text);
        $participants = Participant::where('telegram_chat_id', $chatId)->get();
        
        $participant = null;
        foreach ($participants as $p) {
            $title = $p->game->title ?? "Гра #{$p->game_id}";
            if ($title === $gameTitle) {
                $participant = $p;
                break;
            }
        }

        if (!$participant) {
            $this->sendMessage($chatId, "Гру не знайдено. Оберіть зі списку або натисніть «🔙 Назад».");
            return;
        }

        Cache::put("bot_wishlist_p_id_$chatId", $participant->id, 3600);
        $this->askForWishlist($chatId, $participant);
    }

    private function askForWishlist($chatId, $participant)
    {
        $currentText = $participant->wishlist_text ? "\n\nПоточні побажання: " . $participant->wishlist_text : "";
        Cache::put("bot_state_$chatId", 'waiting_for_wishlist', 3600);
        $this->sendMessage($chatId, "Напишіть ваші побажання одним повідомленням (що ви хочете отримати)$currentText\n\nАбо «🔙 Назад» для скасування.", [["🔙 Назад"]]);
    }

    private function updateWishlistFromBot($chatId, $text)
    {
        if ($text == '🔙 Назад') return $this->handleCancel($chatId);
        $participantId = Cache::get("bot_wishlist_p_id_$chatId");
        
        if (!$participantId) {
            $this->sendMessage($chatId, "Сталася помилка. Почніть спочатку.");
            Cache::forget("bot_state_$chatId");
            return;
        }

        $participant = Participant::find($participantId);
        if ($participant) {
            $participant->update(['wishlist_text' => $text]);
            $this->sendMessage($chatId, "Вішліст оновлено! ✅ Твій Санта побачить це.");
        } else {
            $this->sendMessage($chatId, "Учасника не знайдено.");
        }

        Cache::forget("bot_state_$chatId");
        Cache::forget("bot_wishlist_p_id_$chatId");
    }

    private function handleSettings($chatId)
    {
        $games = Game::where('organizer_chat_id', $chatId)->latest()->get();

        if ($games->isEmpty()) {
            $this->sendMessage($chatId, "Ви ще не створювали ігор у цьому боті.");
            return;
        }

        if ($games->count() === 1) {
            $this->showGameSettings($chatId, $games->first());
            return;
        }

        $buttons = [];
        foreach ($games as $game) {
            $title = $game->title ?? "Гра #{$game->id}";
            $buttons[] = ["⚙️ $title"];
        }
        $buttons[] = ["🔙 Назад"];

        Cache::put("bot_state_$chatId", 'waiting_for_settings_game_selection', 3600);
        $this->sendMessage($chatId, "Оберіть гру для налаштування:", $buttons);
    }

    private function processSettingsGameSelection($chatId, $text)
    {
        if ($text == '🔙 Назад') return $this->handleCancel($chatId);
        $gameTitle = str_replace('⚙️ ', '', $text);
        $game = Game::where('organizer_chat_id', $chatId)->where(function($q) use ($gameTitle) {
            $q->where('title', $gameTitle)->orWhere(DB::raw("'Гра #' || id"), $gameTitle);
        })->first();

        if (!$game) {
            $this->sendMessage($chatId, "Гру не знайдено.");
            return;
        }

        $this->showGameSettings($chatId, $game);
    }

    private function showGameSettings($chatId, Game $game)
    {
        Cache::put("bot_edit_game_id_$chatId", $game->id, 3600);
        Cache::put("bot_state_$chatId", 'waiting_for_settings_action', 3600);

        $msg = "⚙️ *Налаштування гри*\n\n";
        $msg .= "*Назва:* " . ($game->title ?? 'Secret Santa') . "\n";
        $msg .= "*Опис:* " . ($game->description ?? '_(відсутній)_') . "\n";

        $buttons = [
            ['✏️ Змінити назву'],
            ['📝 Змінити опис'],
            ['🔙 Назад']
        ];

        $this->sendMessage($chatId, $msg, $buttons);
    }

    private function processSettingsAction($chatId, $text)
    {
        if ($text == '🔙 Назад') return $this->handleCancel($chatId);
        
        $gameId = Cache::get("bot_edit_game_id_$chatId");
        if (!$gameId) return $this->handleCancel($chatId);

        if ($text == '✏️ Змінити назву') {
            Cache::put("bot_state_$chatId", 'waiting_for_edit_title', 3600);
            $this->sendMessage($chatId, "Напишіть нову назву гри:", [["❌ Скасувати"]]);
        } elseif ($text == '📝 Змінити опис') {
            Cache::put("bot_state_$chatId", 'waiting_for_edit_description', 3600);
            $this->sendMessage($chatId, "Напишіть новий опис гри або «-» щоб видалити його:", [["❌ Скасувати"]]);
        }
    }

    private function updateGameField($chatId, $field, $text)
    {
        if ($text == '❌ Скасувати') return $this->handleCancel($chatId);
        
        $gameId = Cache::get("bot_edit_game_id_$chatId");
        $game = Game::find($gameId);

        if ($game && $game->organizer_chat_id == $chatId) {
            if ($field === 'description' && $text === '-') $text = null;
            $game->update([$field => $text]);
            $this->sendMessage($chatId, "Дані гри оновлено! ✅");
        } else {
            $this->sendMessage($chatId, "Сталася помилка.");
        }

        $this->handleCancel($chatId);
    }

    private function sendMessage($chatId, $text, $buttons = null)
    {
        $replyMarkup = [
            'keyboard' => $buttons ?? $this->menuButtons,
            'resize_keyboard' => true,
            'persistent' => true
        ];

        Http::post("https://api.telegram.org/bot{$this->token}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
            'reply_markup' => $replyMarkup
        ]);
    }

    private function handleAuthToken($chatId, $payload, $username)
    {
        $authToken = str_replace('auth_', '', $payload);
        $cacheKey = 'telegram_reveal_token:' . $authToken;
        
        $data = Cache::get($cacheKey);
        
        if (!$data) {
            $this->sendMessage($chatId, "❌ Посилання недійсне.");
            return;
        }
        
        $participantId = $data['participant_id'];
        $participant = Participant::find($participantId);
        
        if (!$participant) {
            $this->sendMessage($chatId, "❌ Учасника не знайдено.");
            return;
        }
        
        if (!$participant->telegram_chat_id) {
            $participant->telegram_chat_id = $chatId;
            $participant->telegram_username = $username;
            $participant->save();
        }
        
        Cache::put('telegram_auth:' . $participantId, true, now()->addHour());
        Cache::forget($cacheKey);
        
        $this->sendMessage($chatId, "✅ Авторизація успішна!");
    }
}
