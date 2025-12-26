<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\Participant;
use App\Models\Game;
use App\Http\Controllers\GameController;
use Illuminate\Support\Facades\App;

class TelegramBotCommand extends Command
{
    protected $signature = 'telegram:run';
    protected $description = 'Run the Secret Santa Telegram Bot';

    private string $token;
    private int $offset = 0;

    public function handle()
    {
        $this->token = config('services.telegram.bot_token') ?? env('TELEGRAM_BOT_TOKEN');

        if (!$this->token) {
            $this->error('TELEGRAM_BOT_TOKEN not found in environment or config.');
            return 1;
        }

        $this->info('Secret Santa Bot is running...');

        try {
            $this->setBotCommands();
        } catch (\Exception $e) {
            $this->warn('Could not set bot commands: ' . $e->getMessage());
            $this->warn('Bot will continue running without custom commands.');
        }

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
                        try {
                            $this->processUpdate($update);
                        } catch (\Exception $e) {
                            \Log::error("Error processing update", [
                                'update_id' => $update['update_id'] ?? 'unknown',
                                'error' => $e->getMessage()
                            ]);
                        } finally {
                            // ALWAYS increment offset, even if there was an error
                            $this->offset = $update['update_id'] + 1;
                        }
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

    /**
     * Helper method for making Telegram API calls with timeout
     */
    private function telegramApi($method, $params = [])
    {
        return Http::timeout(10)->post("https://api.telegram.org/bot{$this->token}/{$method}", $params);
    }

    private function setBotCommands()
    {
        // Clear commands list to remove the / menu
        $this->telegramApi('deleteMyCommands');

        // Set the Menu Button to open the Mini App (only if HTTPS)
        $appUrl = config('app.url');
        if (str_starts_with($appUrl, 'https://')) {
            $this->telegramApi('setChatMenuButton', [
                'menu_button' => [
                    'type' => 'web_app',
                    'text' => '🎁 Open',
                    'web_app' => [
                        'url' => $appUrl
                    ]
                ]
            ]);
        }
    }

    private function matchCommand($text, $key)
    {
        // Check current locale
        if ($text === __($key)) return true;

        // Check explicit locales
        foreach (['uk', 'en'] as $lang) {
            // We use trans() with locale to check if the user sent a button from a different language's menu
            if ($text === trans($key, [], $lang)) {
                return true;
            }
        }
        return false;
    }

    private function processUpdate(array $update)
    {
        // Handle Callback Queries (e.g. language selection)
        if (isset($update['callback_query'])) {
            $this->handleCallbackQuery($update['callback_query']);
            return;
        }

        if (!isset($update['message'])) return;

        $message = $update['message'];
        $chatId = $message['chat']['id'] ?? null;
        $text = $message['text'] ?? '';
        $from = $message['from'] ?? [];
        $username = strtolower(ltrim($from['username'] ?? '', '@'));

        if (!$chatId || !$username) return;
        
        // Resolve and set locale
        $lang = $this->resolveLocale($chatId, $username);
        App::setLocale($lang);

        Cache::put("bot_user_username_$chatId", $username, 86400);

        // Sync chat ID for all participant records with this username
        Participant::where('telegram_username', $username)
            ->where(function($q) use ($chatId) {
                $q->whereNull('telegram_chat_id')->orWhere('telegram_chat_id', '!=', $chatId);
            })
            ->update(['telegram_chat_id' => $chatId]);

        if ($this->matchCommand($text, 'bot.menu.new_game') || $text == '/newgame') {
            $this->handleNewGame($chatId);
        } elseif ($this->matchCommand($text, 'bot.menu.my_games') || $text == '/mygames') {
            \Log::info("Matched my_games command for text: $text");
            $this->handleMyGames($chatId);
        } elseif ($this->matchCommand($text, 'bot.menu.who') || $text == '/who') {
            \Log::info("Matched who command for text: $text");
            $this->handleWho($chatId);
        } elseif ($this->matchCommand($text, 'bot.menu.wishlist') || $text == '/wishlist' || $text == '📝 Мій Wishlist') {
            $this->handleWishlist($chatId, $text);
        } elseif ($this->matchCommand($text, 'bot.menu.address') || $text == '/address' || $text == '📍 Моя адреса') {
            $this->handleAddress($chatId);
        } elseif ($this->matchCommand($text, 'bot.menu.settings') || $text == '/settings') {
            $this->handleSettings($chatId);
        } elseif ($text == '/start_santa') {
            $this->handleStartSanta($message);
        } elseif ($text == '/cancel' ||
                  $this->matchCommand($text, 'bot.menu.back') ||
                  $this->matchCommand($text, 'bot.menu.cancel') ||
                  $this->matchCommand($text, 'bot.settings.main_menu')) {
            $this->handleCancel($chatId);
        } elseif (str_starts_with($text, '/start')) {
            $payload = trim(str_replace('/start', '', $text));
            if (str_starts_with($payload, 'auth_')) {
                $this->handleAuthToken($chatId, $payload, $username);
            } elseif (str_starts_with($payload, 'join_')) {
                $this->handleJoinGame($chatId, $username, $payload);
            } else {
                $this->handleStart($chatId, $username);
            }
        } else {
            $this->handleState($chatId, $text);
        }
    }

    private function resolveLocale($chatId, $username = null)
    {
        // Try Cache first (for speed)
        $cached = Cache::get("bot_user_lang_$chatId");
        if ($cached) return $cached;

        // Try to find user by telegram_id (chatId)
        $user = \App\Models\User::where('telegram_id', $chatId)->first();
        if (!$user && $username) {
            $user = \App\Models\User::where('telegram_username', $username)->first();
        }
        
        if ($user && $user->language) {
            Cache::put("bot_user_lang_$chatId", $user->language, 86400);
            return $user->language;
        }

        // Check participants
        $participant = Participant::where('telegram_chat_id', $chatId)->first();
        if (!$participant && $username) {
            $participant = Participant::where('telegram_username', $username)->first();
        }

        if ($participant && $participant->language) {
            Cache::put("bot_user_lang_$chatId", $participant->language, 86400);
            return $participant->language;
        }

        return 'uk'; // Default
    }

    private function handleCallbackQuery($query)
    {
        $chatId = $query['message']['chat']['id'];
        $data = $query['data'];
        $from = $query['from'] ?? [];
        $username = strtolower(ltrim($from['username'] ?? '', '@'));

        if (str_starts_with($data, 'set_lang_')) {
            $lang = str_replace('set_lang_', '', $data);

            // Update User
            $user = \App\Models\User::where('telegram_id', $chatId)->first();
            if ($user) {
                $user->update(['language' => $lang]);
            }

            // Update all participants with this chat_id
            Participant::where('telegram_chat_id', $chatId)->update(['language' => $lang]);

            Cache::put("bot_user_lang_$chatId", $lang, 86400);
            App::setLocale($lang);

            // Answer query
            $this->telegramApi('answerCallbackQuery', [
                'callback_query_id' => $query['id'],
                'text' => $lang == 'uk' ? 'Мову змінено!' : 'Language updated!'
            ]);

            // Delete selection message
            $this->telegramApi('deleteMessage', [
                'chat_id' => $chatId,
                'message_id' => $query['message']['message_id']
            ]);

            // Check if user was in the middle of joining a game
            $pendingJoin = Cache::get("bot_pending_join_$chatId");
            if ($pendingJoin) {
                Cache::forget("bot_pending_join_$chatId");
                $this->handleJoinGame($chatId, $username, $pendingJoin);
            } elseif (Cache::get("bot_editing_lang_$chatId")) {
                Cache::forget("bot_editing_lang_$chatId");
                $this->handleSettings($chatId);
            } else {
                $this->handleStart($chatId, $username);
            }
        } elseif (str_starts_with($data, 'join_game_')) {
            $this->handleJoinGameCallback($query, $username);
        } elseif (str_starts_with($data, 'organizer_')) {
            $this->handleOrganizerCallback($query, $username);
        }
    }

    private function handleCancel($chatId)
    {
        Cache::forget("bot_state_$chatId");
        Cache::forget("bot_game_title_$chatId");
        Cache::forget("bot_game_description_$chatId");
        Cache::forget("bot_wishlist_p_id_$chatId");
        Cache::forget("bot_edit_game_id_$chatId");
        Cache::forget("bot_broadcast_p_id_$chatId");
        $this->sendMessage($chatId, __('bot.back_to_menu'));
    }

    private function handleState($chatId, $text)
    {
        $state = Cache::get("bot_state_$chatId");
        if (!$state) return;

        if ($state === 'waiting_for_title') {
            Cache::put("bot_game_title_$chatId", $text, 3600);
            Cache::put("bot_state_$chatId", 'waiting_for_description', 3600);
            $this->sendMessage($chatId, __('bot.create.title_success'), [[__('bot.menu.skip')], [__('bot.menu.cancel')]]);
        } elseif ($state === 'waiting_for_description') {
            $description = ($text === '-' || $this->matchCommand($text, 'bot.menu.skip')) ? null : $text;
            Cache::put("bot_game_description_$chatId", $description, 3600);
            Cache::put("bot_state_$chatId", 'waiting_for_participants', 3600);
            $this->sendMessage($chatId, __('bot.create.description_success'), [[__('bot.create.generate_invite')], [__('bot.menu.cancel')]]);
        } elseif ($state === 'waiting_for_participants') {
            if ($this->matchCommand($text, 'bot.create.generate_invite')) {
                $this->createGameWithInviteLink($chatId);
            } else {
                $this->createGameFromBot($chatId, $text);
            }
        } elseif ($state === 'waiting_for_game_selection') {
            $this->processGameSelection($chatId, $text);
        } elseif ($state === 'waiting_for_wishlist') {
            $this->updateWishlistFromBot($chatId, $text);
        } elseif ($state === 'waiting_for_settings_game_selection') {
            $this->processSettingsGameSelection($chatId, $text);
        } elseif ($state === 'waiting_for_notify_game_selection') {
            $this->processNotifyGameSelection($chatId, $text);
        } elseif ($state === 'waiting_for_settings_action') {
            $this->processSettingsAction($chatId, $text);
        } elseif ($state === 'waiting_for_shipping_address') {
            $this->updateShippingAddressFromBot($chatId, $text);
        } elseif ($state === 'waiting_for_broadcast_player_selection') {
            $this->processBroadcastPlayerSelection($chatId, $text);
        } elseif ($state === 'waiting_for_broadcast_message') {
            $this->processBroadcastMessage($chatId, $text);
        } elseif ($state === 'waiting_for_edit_title') {
            $this->updateGameField($chatId, 'title', $text);
        } elseif ($state === 'waiting_for_edit_description') {
            $this->updateGameField($chatId, 'description', $text);
        } elseif ($state === 'waiting_for_main_settings_selection') {
            $this->processMainSettingsSelection($chatId, $text);
        } elseif ($state === 'waiting_for_budget') {
            $this->processBudgetInput($chatId, $text);
        }
    }

    private function handleNewGame($chatId)
    {
        Cache::put("bot_state_$chatId", 'waiting_for_title', 3600);
        $this->sendMessage($chatId, __('bot.create.ask_title'), [[__('bot.menu.cancel')]]);
    }

    private function createGameWithInviteLink($chatId)
    {
        $title = Cache::get("bot_game_title_$chatId", "Secret Santa");
        $description = Cache::get("bot_game_description_$chatId");

        $game = Game::create([
            'title' => $title,
            'description' => $description,
            'expires_at' => now()->addMonths(3),
            'organizer_chat_id' => $chatId,
        ]);

        Cache::forget("bot_state_$chatId");
        Cache::forget("bot_game_title_$chatId");
        Cache::forget("bot_game_description_$chatId");

        $escapedTitle = str_replace('_', '\\_', $title);
        $joinLink = config('app.url') . "/game/join/{$game->join_token}";

        $msg = str_replace('{title}', $escapedTitle, __('bot.game_created'));
        $msg .= "\n\n" . __('bot.game.join_link_info');
        $msg .= "\n" . $joinLink;
        $msg .= "\n\n📤 Поділіться цим посиланням з учасниками! Вони зможуть приєднатися через веб-інтерфейс або бота.";

        $buttons = [
            'inline_keyboard' => [
                [
                    ['text' => __('bot.btn.view_participants'), 'web_app' => ['url' => config('app.url') . "/game/{$game->id}/edit"]]
                ],
                [
                    ['text' => __('bot.btn.setup_constraints'), 'web_app' => ['url' => config('app.url') . "/game/{$game->id}/constraints"]]
                ]
            ]
        ];

        $this->sendMessage($chatId, $msg, $buttons);

        // Send main menu after creating game
        $this->sendMessage($chatId, __('bot.back_to_menu'));
    }

    private function createGameFromBot($chatId, $text)
    {
        $title = Cache::get("bot_game_title_$chatId", "Secret Santa");
        $description = Cache::get("bot_game_description_$chatId");
        $lines = array_filter(array_map('trim', explode("\n", $text)));

        if (count($lines) < 3) {
            $this->sendMessage($chatId, __('bot.create.min_participants'));
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

                // Trim line to handle trailing spaces that break the $ anchor
                $line = trim($line);
                if (preg_match('/(@[a-zA-Z0-9_]+)$/i', $line, $matches)) {
                    $rawUsername = $matches[1];
                    $telegramUsername = strtolower(ltrim($rawUsername, '@'));
                    $name = trim(preg_replace('/' . preg_quote($rawUsername, '/') . '$/i', '', $line));
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

        $escapedTitle = str_replace('_', '\\_', $title);
        $joinLink = config('app.url') . "/game/join/{$game->join_token}";

        $msg = str_replace('{title}', $escapedTitle, __('bot.game_created'));
        $msg .= "\n\n" . __('bot.game.join_link_info');
        $msg .= "\n" . $joinLink;

        $buttons = [
            'inline_keyboard' => [
                [
                    ['text' => __('bot.btn.view_participants'), 'web_app' => ['url' => config('app.url') . "/game/{$game->id}/edit"]]
                ],
                [
                    ['text' => __('bot.btn.setup_constraints'), 'web_app' => ['url' => config('app.url') . "/game/{$game->id}/constraints"]]
                ]
            ]
        ];

        $this->sendMessage($chatId, $msg, $buttons);

        // Send main menu after creating game
        $this->sendMessage($chatId, __('bot.back_to_menu'));
    }

    private function handleNotify($chatId, $gameId = null)
    {
        if (!$gameId) {
            $gameId = Cache::get("bot_edit_game_id_$chatId");
        }

        if (!$gameId) {
            $games = Game::where('organizer_chat_id', $chatId)->latest()->get();
            if ($games->isEmpty()) {
                $this->sendMessage($chatId, __('bot.no_created_games_notify'));
                return;
            }
            if ($games->count() > 1) {
                $buttons = [];
                foreach ($games as $game) {
                    $buttons[] = ["📢 " . ($game->title ?? "Game #" . $game->id)];
                }
                $buttons[] = ["🔙 " . __('bot.menu.back')];
                Cache::put("bot_state_$chatId", 'waiting_for_notify_game_selection', 3600);
                $this->sendMessage($chatId, __('bot.select_game_to_notify'), $buttons);
                return;
            }
            $game = $games->first();
        } else {
            $game = Game::find($gameId);
        }

        if (!$game) {
            $this->sendMessage($chatId, __('bot.game_not_found'));
            return;
        }

        $count = 0;
        $total = $game->participants()->count();
        
        foreach ($game->participants as $participant) {
            if ($participant->telegram_chat_id) {
                // Ensure token exists
                $token = $participant->reveal_token;
                if (!$token) {
                    $token = bin2hex(random_bytes(16));
                    $participant->update(['reveal_token' => $token]);
                }

                $link = route('reveal.show', [
                    'gameId' => $game->id,
                    'participantId' => $participant->id,
                    'token' => $token
                ]);

                $escapedTitle = str_replace('_', '\\_', $game->title ?? 'Secret Santa');
                $pinInfo = $participant->pin ? "\n" . str_replace('{pin}', $participant->pin, __('bot.game.pin_info')) : "";

                $msg = str_replace('{title}', $escapedTitle, __('bot.game.notification')) . $pinInfo;

                $buttons = [
                    'inline_keyboard' => [
                        [
                            ['text' => '🎁 Відкрити результат', 'web_app' => ['url' => $link]]
                        ]
                    ]
                ];

                $this->sendMessage($participant->telegram_chat_id, $msg, $buttons);
                $count++;
            }
        }

        $msg = "📢 Сповіщення відправлено $count із $total учасників.";
        if ($count < $total) {
            $msg .= "\n\nДеякі учасники ще не отримали повідомлення, бо вони не запустили бота. Вони зможуть дізнатися свою пару за посиланням або коли запустять бота.";
        }
        
        $this->sendMessage($chatId, $msg);
    }

    private function handleStart($chatId, $username)
    {
        // Force refresh locale if we have it
        $lang = $this->resolveLocale($chatId, $username);
        App::setLocale($lang);

        $participants = Participant::where('telegram_username', $username)->get();

        foreach ($participants as $participant) {
            $participant->update(['telegram_chat_id' => $chatId]);
        }

        // If language is not set in DB, ask for it
        $user = \App\Models\User::where('telegram_id', $chatId)->first();
        if (!$user && $username) {
            $user = \App\Models\User::where('telegram_username', $username)->first();
        }
        
        $currentLang = $user->language ?? Participant::where('telegram_chat_id', $chatId)->value('language');

        if (!$currentLang) {
            $this->askLanguage($chatId);
            return;
        }

        $msg = __('bot.welcome');
        
        if ($participants->isEmpty()) {
            $msg .= __('bot.not_added');
        }

        $this->sendMessage($chatId, $msg);
    }

    private function askLanguage($chatId)
    {
        $buttons = [
            'inline_keyboard' => [
                [
                    ['text' => '🇺🇦 Українська', 'callback_data' => 'set_lang_uk'],
                    ['text' => '🇺🇸 English', 'callback_data' => 'set_lang_en']
                ]
            ]
        ];

        $this->sendMessage($chatId, "Будь ласка, оберіть мову / Please select your language:", $buttons);
    }

    private function getMenuButtons()
    {
        return [
            [__('bot.menu.new_game'), __('bot.menu.my_games')],
            [__('bot.menu.who'), __('bot.menu.wishlist')],
            [__('bot.menu.address'), __('bot.menu.settings')]
        ];
    }

    private function handleMyGames($chatId)
    {
        \Log::info("handleMyGames called for chatId: $chatId");
        $organizedGames = Game::where('organizer_chat_id', $chatId)->get();
        $participatingIn = Participant::where('telegram_chat_id', $chatId)->get();
        \Log::info("Organized games: " . $organizedGames->count() . ", Participating: " . $participatingIn->count());

        if ($organizedGames->isEmpty() && $participatingIn->isEmpty()) {
            \Log::info("No games found, sending no_games message");
            $this->sendMessage($chatId, __('bot.no_games'));
            return;
        }

        $msg = __('bot.my_games.title') . "\n\n";

        if ($organizedGames->isNotEmpty()) {
            $msg .= __('bot.my_games.organized') . "\n";
            foreach ($organizedGames as $game) {
                $count = $game->participants()->count();
                $title = str_replace('_', '\\_', $game->title);
                $participants = str_replace('{count}', $count, __('bot.my_games.participants'));
                $msg .= "▫️ *$title* ($participants)\n";
            }
            $msg .= "\n";
        }

        if ($participatingIn->isNotEmpty()) {
            $msg .= __('bot.my_games.participating') . "\n";
            foreach ($participatingIn as $p) {
                $game = $p->game;
                // Escape underscores in names for proper Markdown rendering
                $santaFor = $p->assignmentAsSanta ? str_replace('_', '\\_', $p->assignmentAsSanta->recipient->name) : __('bot.my_games.not_assigned');
                $title = str_replace('_', '\\_', $game->title);
                $giftText = str_replace('{santaFor}', $santaFor, __('bot.my_games.you_gift'));
                $msg .= "▫️ *$title* ($giftText)\n";
            }
        }

        $msg .= "\n\n" . __('bot.my_games.open_app_info');

        $buttons = [
            'inline_keyboard' => [
                [
                    ['text' => '📋 ' . __('bot.my_games.open_list'), 'web_app' => ['url' => config('app.url') . '/my-games']]
                ]
            ]
        ];

        \Log::info("About to send my_games message with open list button");
        $this->sendMessage($chatId, $msg, $buttons);
    }

    private function handleWho($chatId)
    {
        \Log::info("handleWho called for chatId: $chatId");
        $participants = Participant::where('telegram_chat_id', $chatId)->get();
        \Log::info("Found participants by chat_id: " . $participants->count());

        if ($participants->isEmpty()) {
            // Try by username as a backup
            $username = Cache::get("bot_user_username_$chatId");
            \Log::info("Trying by username: $username");
            if ($username) {
                $participants = Participant::where('telegram_username', $username)->get();
                \Log::info("Found participants by username: " . $participants->count());
            }
        }

        if ($participants->isEmpty()) {
            \Log::info("No participants found, sending no games message");
            $this->sendMessage($chatId, __("bot.not_in_any_game_extended"));
            return;
        }

        \Log::info("Starting loop through " . $participants->count() . " participants");
        foreach ($participants as $participant) {
            \Log::info("Processing participant ID: " . $participant->id);
            $assignment = $participant->assignmentAsSanta;
            $game = $participant->game;
            $gameTitle = str_replace('_', '\\_', $game->title ?? 'Secret Santa');

            if (!$assignment) {
                \Log::info("No assignment found for participant " . $participant->id);
                $msg = str_replace('{title}', $gameTitle, __('bot.game.pairs_pending'));
                \Log::info("Sending pairs pending message");
                $this->sendMessage($chatId, $msg);
                continue;
            }

            \Log::info("Assignment found, recipient ID: " . $assignment->recipient_id);
            $recipient = $assignment->recipient;
            $wishlist = $recipient->wishlist_text ? "\n\n" . __('bot.game.wishlist_label') . "\n" . $recipient->wishlist_text : "\n\n" . __('bot.game.no_wishlist');
            $address = $recipient->shipping_address ? "\n\n📍 " . __('reveal_result.address_label') . "\n" . $recipient->shipping_address : "\n\n" . __('reveal_result.no_address');
            $description = $game->description ? "\n\n" . __('game.description_label') . ": " . $game->description : "";

            $token = $participant->reveal_token;
            // Ensure we have a token
            if (!$token) {
                $token = bin2hex(random_bytes(16));
                $participant->update(['reveal_token' => $token]);
            }

            $link = config('app.url') . "/reveal/{$game->id}/{$participant->id}/{$token}";

            $buttons = [
                'inline_keyboard' => [
                    [
                        ['text' => __('bot.btn.open_card'), 'web_app' => ['url' => $link]]
                    ]
                ]
            ];

            // Don't escape underscores in recipient name to preserve @username links
            $recipientName = $recipient->name;
            $msg = str_replace(['{gameTitle}', '{description}', '{recipientName}', '{wishlist}', '{address}'],
                              [$gameTitle, $description, $recipientName, $wishlist, $address],
                              __('bot.who_you_gift'));
            \Log::info("About to send message for participant " . $participant->id);
            $this->sendMessage($chatId, $msg, $buttons);
            \Log::info("Message sent for participant " . $participant->id);
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
        $buttons[] = [__('bot.menu.back')];

        Cache::put("bot_state_$chatId", 'waiting_for_game_selection', 3600);
        $this->sendMessage($chatId, __("bot.select_game_for_wishlist"), $buttons);
    }

    private function processGameSelection($chatId, $text)
    {
        if ($this->matchCommand($text, 'bot.menu.back')) return $this->handleCancel($chatId);
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
            $this->sendMessage($chatId, __("bot.game_not_found_from_list"));
            return;
        }

        Cache::put("bot_wishlist_p_id_$chatId", $participant->id, 3600);
        $this->askForWishlist($chatId, $participant);
    }

    private function handleAddress($chatId)
    {
        $participant = Participant::where('telegram_chat_id', $chatId)->first();
        $currentAddress = $participant->shipping_address ?? null;
        
        if (!$currentAddress) {
            $user = \App\Models\User::where('telegram_id', $chatId)->first();
            $currentAddress = $user->shipping_address ?? null;
        }

        $msg = "📍 *Ваші дані для доставки:*\n\n";
        $msg .= $currentAddress ?: "_(не вказано)_";
        $msg .= "\n\nНапишіть нову адресу (ПІБ, телефон, місто, НП) або натисніть «🔙 Назад»:";

        Cache::put("bot_state_$chatId", 'waiting_for_shipping_address', 3600);
        $this->sendMessage($chatId, $msg, [[__('bot.menu.back')]]);
    }

    private function updateShippingAddressFromBot($chatId, $text)
    {
        if ($this->matchCommand($text, 'bot.menu.back')) return $this->handleCancel($chatId);
        
        // Update User if exists
        \App\Models\User::where('telegram_id', $chatId)->update(['shipping_address' => $text]);
        
        // Update all Participant occurrences for this person
        $participants = Participant::where('telegram_chat_id', $chatId)->get();
        if ($participants->isEmpty()) {
            // If they are not in any games by chat_id yet, try by username
            $from = Cache::get("bot_user_username_$chatId"); // Need to store this
            if ($from) {
                Participant::where('telegram_username', $from)->update(['shipping_address' => $text]);
            }
        } else {
            foreach ($participants as $p) {
                $p->update(['shipping_address' => $text]);
            }
        }

        $this->sendMessage($chatId, __("bot.address.updated"));
        $this->handleCancel($chatId);
    }

    private function askForWishlist($chatId, $participant)
    {
        $currentText = $participant->wishlist_text ? "\n\n" . __('bot.wishlist.current') . ": " . $participant->wishlist_text : "";
        Cache::put("bot_state_$chatId", 'waiting_for_wishlist', 3600);
        $msg = str_replace('{currentText}', $currentText, __('bot.wishlist.prompt'));
        $this->sendMessage($chatId, $msg, [[__('bot.menu.back')]]);
    }

    private function updateWishlistFromBot($chatId, $text)
    {
        if ($this->matchCommand($text, 'bot.menu.back')) return $this->handleCancel($chatId);
        $participantId = Cache::get("bot_wishlist_p_id_$chatId");
        
        if (!$participantId) {
            $this->sendMessage($chatId, __("bot.wishlist.error"));
            Cache::forget("bot_state_$chatId");
            return;
        }

        $participant = Participant::find($participantId);
        if ($participant) {
            $participant->update(['wishlist_text' => $text]);
            $this->sendMessage($chatId, __("bot.wishlist.updated"));
        } else {
            $this->sendMessage($chatId, __("bot.participant_not_found"));
        }

        Cache::forget("bot_state_$chatId");
        Cache::forget("bot_wishlist_p_id_$chatId");
    }

    private function handleSettings($chatId)
    {
        $buttons = [
            [__('bot.settings.manage_games')],
            [__('bot.settings.change_lang')],
            [__('bot.menu.back')]
        ];

        Cache::put("bot_state_$chatId", 'waiting_for_main_settings_selection', 3600);
        $this->sendMessage($chatId, __('bot.settings_title'), $buttons);
    }

    private function processMainSettingsSelection($chatId, $text)
    {
        if ($this->matchCommand($text, 'bot.menu.back')) return $this->handleCancel($chatId);

        if ($this->matchCommand($text, 'bot.settings.manage_games')) {
            $this->handleGamesList($chatId);
        } elseif ($this->matchCommand($text, 'bot.settings.change_lang')) {
            Cache::put("bot_editing_lang_$chatId", true, 300);
            $this->askLanguage($chatId);
        } else {
            $this->sendMessage($chatId, __("bot.select_menu_option"));
        }
    }

    private function handleGamesList($chatId)
    {
        $games = Game::where('organizer_chat_id', $chatId)->latest()->get();

        if ($games->isEmpty()) {
            $this->sendMessage($chatId, __("bot.no_created_games"));
            return;
        }

        if ($games->count() === 1) {
            $this->showGameSettings($chatId, $games->first());
            return;
        }

        $buttons = [];
        foreach ($games as $game) {
            $title = $game->title ?? "Game #" . $game->id;
            $buttons[] = ["⚙️ $title"];
        }
        $buttons[] = [__('bot.menu.back')];

        Cache::put("bot_state_$chatId", 'waiting_for_settings_game_selection', 3600);
        $this->sendMessage($chatId, __("bot.select_game_for_settings"), $buttons);
    }

    private function processSettingsGameSelection($chatId, $text)
    {
        if ($this->matchCommand($text, 'bot.menu.back')) return $this->handleSettings($chatId);
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
        $msg .= __("bot.settings.name") . ($game->title ?? 'Secret Santa') . "\n";
        $msg .= __("bot.settings.desc") . ($game->description ?? '_(missing)_') . "\n";
        $msg .= __("bot.settings.participants") . $game->participants()->count() . "\n\n";
        
        $msg .= "*Список учасників:*\n";
        foreach ($game->participants as $p) {
            $status = $p->telegram_chat_id ? "✅" : "⏳";
            // Don't escape underscores to preserve @username links
            $cleanUsername = $p->telegram_username ? $p->telegram_username : '---';
            $cleanName = $p->name;
            $msg .= "{$status} {$cleanName} (@{$cleanUsername})\n";
        }

        $buttons = [
            [__('bot.settings.change_name'), __('bot.settings.change_desc')],
            [__('bot.settings.notify_player')],
            [__('bot.settings.main_menu'), __('bot.menu.back')]
        ];

        $this->sendMessage($chatId, $msg, $buttons);
    }

    private function processSettingsAction($chatId, $text)
    {
        if ($this->matchCommand($text, 'bot.menu.back')) return $this->handleGamesList($chatId);
        
        $gameId = Cache::get("bot_edit_game_id_$chatId");
        if (!$gameId) return $this->handleCancel($chatId);

        if ($this->matchCommand($text, 'bot.settings.main_menu')) {
            Cache::forget("bot_edit_game_id_$chatId");
            return $this->handleCancel($chatId);
        }

        if ($this->matchCommand($text, 'bot.settings.change_name')) {
            Cache::put("bot_state_$chatId", 'waiting_for_edit_title', 3600);
            $this->sendMessage($chatId, __('bot.settings.change_name') . ":", [[__('bot.menu.cancel')]]);
        } elseif ($this->matchCommand($text, 'bot.settings.change_desc')) {
            Cache::put("bot_state_$chatId", 'waiting_for_edit_description', 3600);
            $this->sendMessage($chatId, __('bot.settings.change_desc') . ":", [[__('bot.menu.cancel')]]);
        } elseif ($this->matchCommand($text, 'bot.settings.notify_player')) {
            $this->handleBroadcastSelection($chatId, $gameId);
        }
    }

    private function processNotifyGameSelection($chatId, $text)
    {
        if ($this->matchCommand($text, 'bot.menu.back')) return $this->handleCancel($chatId);
        $gameTitle = str_replace('📢 ', '', $text);
        $game = Game::where('organizer_chat_id', $chatId)->where('title', $gameTitle)->first();

        if (!$game) {
            $this->sendMessage($chatId, "Гру не знайдено.");
            return;
        }

        Cache::forget("bot_state_$chatId");
        $this->handleNotify($chatId, $game->id);
    }

    private function handleBroadcastSelection($chatId, $gameId)
    {
        $game = Game::find($gameId);
        if (!$game) return;

        $buttons = [];
        foreach ($game->participants as $p) {
            $buttons[] = ["👤 " . $p->name];
        }
        $buttons[] = ["🔙 Назад"];

        Cache::put("bot_state_$chatId", 'waiting_for_broadcast_player_selection', 3600);
        $this->sendMessage($chatId, __("bot.broadcast.select_player"), $buttons);
    }

    private function processBroadcastPlayerSelection($chatId, $text)
    {
        if ($this->matchCommand($text, 'bot.menu.back') || $this->matchCommand($text, 'bot.menu.cancel')) return $this->handleSettings($chatId);
        
        $name = str_replace('👤 ', '', $text);
        $gameId = Cache::get("bot_edit_game_id_$chatId");
        $participant = Participant::where('game_id', $gameId)->where('name', $name)->first();

        if (!$participant) {
            $this->sendMessage($chatId, __("bot.broadcast.player_not_found"));
            return;
        }

        Cache::put("bot_broadcast_p_id_$chatId", $participant->id, 3600);
        Cache::put("bot_state_$chatId", 'waiting_for_broadcast_message', 3600);
        $msg = str_replace('{name}', $participant->name, __('bot.broadcast.enter_message'));
        $this->sendMessage($chatId, $msg, [[__('bot.menu.cancel')]]);
    }

    private function processBroadcastMessage($chatId, $text)
    {
        if ($text == '❌ Скасувати') return $this->handleSettings($chatId);

        $participantId = Cache::get("bot_broadcast_p_id_$chatId");
        $participant = Participant::find($participantId);

        if (!$participant || !$participant->telegram_chat_id) {
            $this->sendMessage($chatId, __("bot.broadcast.send_failed"));
            return;
        }

        $game = $participant->game;
        $msg = "✉️ *Повідомлення від організатора гри «{$game->title}»:*\n\n{$text}";
        
        $this->sendMessage($participant->telegram_chat_id, $msg);
        $this->sendMessage($chatId, __("bot.broadcast.sent"));
        
        Cache::forget("bot_broadcast_p_id_$chatId");
        $this->handleSettings($chatId);
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
        \Log::info("sendMessage called for chatId: $chatId");
        \Log::info("Message text length: " . strlen($text));

        $replyMarkup = [];

        if ($buttons && isset($buttons['inline_keyboard'])) {
            $replyMarkup = [
                'inline_keyboard' => $buttons['inline_keyboard']
            ];
        } else {
            $replyMarkup = [
                'keyboard' => $buttons ?? $this->getMenuButtons(),
                'resize_keyboard' => true,
                'persistent' => true
            ];
        }

        // Just in case, ensure we don't have unescaped underscores that break everything
        // but we've already tried to escape them where they occur.

        $response = $this->telegramApi('sendMessage', [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
            'reply_markup' => $replyMarkup
        ]);

        \Log::info("Telegram API response status: " . $response->status());
        if (!$response->successful()) {
            \Log::error("Telegram API error: " . $response->body());
        }

        return $response;
    }

    private function handleAuthToken($chatId, $payload, $username)
    {
        $authToken = str_replace('auth_', '', $payload);
        $cacheKey = 'telegram_reveal_token:' . $authToken;
        
        $data = Cache::get($cacheKey);
        
        if (!$data) {
            $this->sendMessage($chatId, __("bot.auth.invalid_token"));
            return;
        }
        
        $participantId = $data['participant_id'];
        $participant = Participant::find($participantId);
        
        if (!$participant) {
            $this->sendMessage($chatId, __("bot.auth.participant_not_found"));
            return;
        }
        
        if (!$participant->telegram_chat_id) {
            $participant->telegram_chat_id = $chatId;
            $participant->telegram_username = $username;
            $participant->save();
        }
        
        Cache::put('telegram_auth:' . $participantId, true, now()->addHour());
        Cache::forget($cacheKey);

        $this->sendMessage($chatId, __("bot.auth.success"));
    }

    private function handleStartSanta(array $message)
    {
        $chat = $message['chat'];
        $chatType = $chat['type'] ?? 'private';
        $chatId = $chat['id'];
        $from = $message['from'] ?? [];

        // Only allow in group chats
        if (!in_array($chatType, ['group', 'supergroup'])) {
            $this->sendMessage($chatId, "Ця команда працює тільки в групових чатах. Додайте бота в групу і напишіть `/start_santa` там.");
            return;
        }

        // Check if game already exists for this group
        $existingGame = Game::where('group_chat_id', (string)$chatId)
            ->where('registration_open', true)
            ->first();

        if ($existingGame) {
            $this->showOrganizerPanel($chatId, $existingGame, $from['id'] ?? null);
            return;
        }

        // Create new game
        $game = Game::create([
            'title' => $chat['title'] ?? 'Secret Santa',
            'description' => null,
            'organizer_chat_id' => $from['id'] ?? $chatId,
            'group_chat_id' => (string)$chatId,
            'registration_open' => true,
            'expires_at' => now()->addMonths(3),
        ]);

        $this->showJoinButton($chatId, $game);
    }

    private function showJoinButton($chatId, Game $game)
    {
        $msg = __('bot.group.start_title') . "\n\n";
        $msg .= __('bot.group.join_prompt');

        $buttons = [
            'inline_keyboard' => [
                [
                    ['text' => __('bot.group.btn_join'), 'callback_data' => "join_game_{$game->id}"]
                ],
                [
                    ['text' => '⚙️ Панель організатора', 'callback_data' => "organizer_panel_{$game->id}"]
                ]
            ]
        ];

        $this->sendMessage($chatId, $msg, $buttons);
    }

    private function handleJoinGameCallback($query, $username)
    {
        $gameId = str_replace('join_game_', '', $query['data']);
        $game = Game::find($gameId);
        $chatId = $query['message']['chat']['id'];
        $from = $query['from'];

        if (!$game || !$game->registration_open) {
            Http::post("https://api.telegram.org/bot{$this->token}/answerCallbackQuery", [
                'callback_query_id' => $query['id'],
                'text' => 'Реєстрація закрита або гру не знайдено.',
                'show_alert' => true
            ]);
            return;
        }

        // Check if already joined
        $existing = Participant::where('game_id', $gameId)
            ->where('telegram_chat_id', $from['id'])
            ->first();

        if ($existing) {
            Http::post("https://api.telegram.org/bot{$this->token}/answerCallbackQuery", [
                'callback_query_id' => $query['id'],
                'text' => 'Ви вже приєдналися до цієї гри!',
                'show_alert' => true
            ]);
            return;
        }

        // Add participant
        $name = trim(($from['first_name'] ?? '') . ' ' . ($from['last_name'] ?? ''));
        if (empty($name)) {
            $name = $username ? "@$username" : "Учасник";
        }

        $participant = $game->participants()->create([
            'name' => $name,
            'telegram_username' => $username,
            'telegram_chat_id' => $from['id'],
        ]);

        Http::post("https://api.telegram.org/bot{$this->token}/answerCallbackQuery", [
            'callback_query_id' => $query['id'],
            'text' => 'Ви успішно приєдналися до гри! 🎉'
        ]);

        // Update message to show current count
        $this->updateJoinMessage($chatId, $game, $query['message']['message_id']);
    }

    private function updateJoinMessage($chatId, Game $game, $messageId)
    {
        $count = $game->participants()->count();
        $msg = __('bot.group.start_title') . "\n\n";
        $msg .= str_replace('{count}', $count, __('bot.group.participants_count')) . "\n\n";
        $msg .= __('bot.group.join_prompt');

        $buttons = [
            'inline_keyboard' => [
                [
                    ['text' => '__("bot.group.btn_join")', 'callback_data' => "join_game_{$game->id}"]
                ],
                [
                    ['text' => '⚙️ Панель організатора', 'callback_data' => "organizer_panel_{$game->id}"]
                ]
            ]
        ];

        Http::post("https://api.telegram.org/bot{$this->token}/editMessageText", [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $msg,
            'parse_mode' => 'Markdown',
            'reply_markup' => $buttons
        ]);
    }

    private function handleOrganizerCallback($query, $username)
    {
        $data = $query['data'];
        $chatId = $query['message']['chat']['id'];
        $from = $query['from'];

        if (str_starts_with($data, 'organizer_panel_')) {
            $gameId = str_replace('organizer_panel_', '', $data);
            $game = Game::find($gameId);

            if (!$game) {
                Http::post("https://api.telegram.org/bot{$this->token}/answerCallbackQuery", [
                    'callback_query_id' => $query['id'],
                    'text' => 'Гру не знайдено.',
                    'show_alert' => true
                ]);
                return;
            }

            $this->showOrganizerPanel($chatId, $game, $from['id'], $query['message']['message_id']);
        } elseif (str_starts_with($data, 'organizer_finish_')) {
            $gameId = str_replace('organizer_finish_', '', $data);
            $this->finishRegistrationAndAssign($gameId, $chatId, $from['id'], $query);
        } elseif (str_starts_with($data, 'organizer_set_budget_')) {
            $gameId = str_replace('organizer_set_budget_', '', $data);
            $this->setGameBudget($gameId, $chatId, $from['id'], $query);
        } elseif (str_starts_with($data, 'organizer_set_format_')) {
            $gameId = str_replace('organizer_set_format_', '', $data);
            $format = str_replace('organizer_set_format_' . $gameId . '_', '', $data);
            $this->setResultFormat($gameId, $format, $chatId, $from['id'], $query);
        } elseif (str_starts_with($data, 'organizer_constraints_')) {
            $gameId = str_replace('organizer_constraints_', '', $data);
            $this->showConstraintsSetup($gameId, $chatId, $from['id'], $query);
        }
    }

    private function showOrganizerPanel($chatId, Game $game, $organizerId = null, $messageId = null)
    {
        $participants = $game->participants;
        $count = $participants->count();

        $msg = "⚙️ *Панель організатора*\n\n";
        $msg .= __("bot.settings.name") . ($game->title ?? 'Secret Santa') . "\n";
        $msg .= str_replace('{count}', $count, __("bot.organizer.participants")) . "\n";
        $msg .= "*Бюджет:* " . ($game->budget ?? 'не вказано') . "\n";
        $msg .= "*Формат результатів:* " . ($game->result_format === 'group' ? 'Груповий чат' : 'Личні повідомлення') . "\n\n";

        if ($count > 0) {
            $msg .= "*Учасники:*\n";
            foreach ($participants as $p) {
                $status = $p->telegram_chat_id ? "✅" : "⏳";
                // Don't escape underscores to preserve @username links
                $name = $p->name;
                $username = $p->telegram_username ? "@" . $p->telegram_username : '';
                $msg .= "{$status} {$name} {$username}\n";
            }
        }

        $buttons = [
            'inline_keyboard' => [
                [
                    ['text' => '💰 Встановити бюджет', 'callback_data' => "organizer_set_budget_{$game->id}"]
                ],
                [
                    ['text' => '📤 Личні повідомлення', 'callback_data' => "organizer_set_format_{$game->id}_private"],
                    ['text' => '👥 Груповий чат', 'callback_data' => "organizer_set_format_{$game->id}_group"]
                ],
                [
                    ['text' => '⚠️ Обмеження (хто кому не дарує)', 'callback_data' => "organizer_constraints_{$game->id}"]
                ],
                [
                    ['text' => '✅ Завершити реєстрацію та розподілити', 'callback_data' => "organizer_finish_{$game->id}"]
                ]
            ]
        ];

        $method = $messageId ? 'editMessageText' : 'sendMessage';
        $params = [
            'chat_id' => $chatId,
            'text' => $msg,
            'parse_mode' => 'Markdown',
            'reply_markup' => $buttons
        ];

        if ($messageId) {
            $params['message_id'] = $messageId;
        }

        Http::post("https://api.telegram.org/bot{$this->token}/{$method}", $params);
    }

    private function setGameBudget($gameId, $chatId, $userId, $query)
    {
        $game = Game::find($gameId);
        if (!$game || $game->organizer_chat_id != $userId) {
            Http::post("https://api.telegram.org/bot{$this->token}/answerCallbackQuery", [
                'callback_query_id' => $query['id'],
                'text' => 'Тільки організатор може налаштовувати гру.',
                'show_alert' => true
            ]);
            return;
        }

        Cache::put("bot_setting_budget_{$userId}_{$gameId}", true, 300);
        Cache::put("bot_state_$userId", 'waiting_for_budget', 3600);

        Http::post("https://api.telegram.org/bot{$this->token}/answerCallbackQuery", [
            'callback_query_id' => $query['id'],
            'text' => 'Напишіть бюджет у чаті'
        ]);

        $this->sendMessage($userId, "Напишіть бюджет подарунка (наприклад: \"до 500 грн\", \"500-1000 грн\", або \"-\" щоб пропустити):");
    }

    private function setResultFormat($gameId, $format, $chatId, $userId, $query)
    {
        $game = Game::find($gameId);
        if (!$game || $game->organizer_chat_id != $userId) {
            Http::post("https://api.telegram.org/bot{$this->token}/answerCallbackQuery", [
                'callback_query_id' => $query['id'],
                'text' => 'Тільки організатор може налаштовувати гру.',
                'show_alert' => true
            ]);
            return;
        }

        $game->update(['result_format' => $format]);

        Http::post("https://api.telegram.org/bot{$this->token}/answerCallbackQuery", [
            'callback_query_id' => $query['id'],
            'text' => $format === 'group' ? 'Результати будуть в груповому чаті' : 'Результати будуть в личних повідомленнях'
        ]);
    }

    private function showConstraintsSetup($gameId, $chatId, $userId, $query)
    {
        $game = Game::find($gameId);
        if (!$game || $game->organizer_chat_id != $userId) {
            Http::post("https://api.telegram.org/bot{$this->token}/answerCallbackQuery", [
                'callback_query_id' => $query['id'],
                'text' => 'Тільки організатор може налаштовувати гру.',
                'show_alert' => true
            ]);
            return;
        }

        $link = config('app.url') . "/game/{$gameId}/constraints";

        Http::post("https://api.telegram.org/bot{$this->token}/answerCallbackQuery", [
            'callback_query_id' => $query['id'],
            'text' => 'Відкриваємо налаштування обмежень...'
        ]);

        $buttons = [
            'inline_keyboard' => [
                [
                    ['text' => '⚙️ Налаштувати обмеження', 'web_app' => ['url' => $link]]
                ]
            ]
        ];

        $this->sendMessage($userId, "Натисни кнопку нижче, щоб налаштувати обмеження (хто кому не може дарувати):", $buttons);
    }

    private function finishRegistrationAndAssign($gameId, $chatId, $userId, $query)
    {
        $game = Game::find($gameId);
        if (!$game || $game->organizer_chat_id != $userId) {
            Http::post("https://api.telegram.org/bot{$this->token}/answerCallbackQuery", [
                'callback_query_id' => $query['id'],
                'text' => 'Тільки організатор може завершувати реєстрацію.',
                'show_alert' => true
            ]);
            return;
        }

        $participants = $game->participants;
        if ($participants->count() < 3) {
            Http::post("https://api.telegram.org/bot{$this->token}/answerCallbackQuery", [
                'callback_query_id' => $query['id'],
                'text' => 'Потрібно мінімум 3 учасники для розподілу!',
                'show_alert' => true
            ]);
            return;
        }

        // Close registration
        $game->update(['registration_open' => false]);

        Http::post("https://api.telegram.org/bot{$this->token}/answerCallbackQuery", [
            'callback_query_id' => $query['id'],
            'text' => 'Розподіляємо пари...'
        ]);

        $this->sendMessage($chatId, "⏳ Генеруємо пари...");

        // Generate assignments
        try {
            $controller = new GameController();
            $result = $controller->assign($game->id);

            if ($result['status'] === 'error') {
                $this->sendMessage($chatId, "❌ " . $result['message']);
                return;
            }

            // Send results
            if ($game->result_format === 'group') {
                $this->sendResultsToGroup($game, $chatId);
            } else {
                $this->sendResultsToPrivate($game);
            }

            $this->sendMessage($chatId, "✅ Пари успішно сформовані! Всі учасники отримають повідомлення.");

        } catch (\Exception $e) {
            $this->sendMessage($chatId, "❌ Сталася помилка: " . $e->getMessage());
        }
    }

    private function sendResultsToPrivate(Game $game, $groupChatId = null)
    {
        $count = 0;
        foreach ($game->participants as $participant) {
            if ($participant->telegram_chat_id) {
                $assignment = $participant->assignmentAsSanta;
                if (!$assignment) continue;

                $recipient = $assignment->recipient;
                $wishlist = $recipient->wishlist_text ? "\n\n📝 *Побажання:*\n" . $recipient->wishlist_text : "";
                $address = $recipient->shipping_address ? "\n\n📍 *Адреса:*\n" . $recipient->shipping_address : "";

                $msg = "🎅 *Твій отримувач для Secret Santa:*\n\n";
                // Don't escape underscores to preserve @username links
                $msg .= "*" . $recipient->name . "*" . $wishlist . $address;

                $this->sendMessage($participant->telegram_chat_id, $msg);
                $count++;
            }
        }

        if ($groupChatId) {
            $this->sendMessage($groupChatId, "📢 Результати відправлено $count учасникам в особисті повідомлення!");
        }
    }

    private function sendResultsToGroup(Game $game, $groupChatId)
    {
        $msg = "🎅 *Результати Secret Santa:*\n\n";

        foreach ($game->participants as $participant) {
            $assignment = $participant->assignmentAsSanta;
            if ($assignment) {
                // Don't escape underscores to preserve @username links
                $santaName = $participant->name;
                $recipientName = $assignment->recipient->name;
                $msg .= "{$santaName} → {$recipientName}\n";
            }
        }

        $this->sendMessage($game->group_chat_id, $msg);
    }

    private function processBudgetInput($chatId, $text)
    {
        // Find which game is being set
        $gameId = null;
        foreach (Cache::getPrefix() . '*' as $key) {
            if (str_contains($key, "bot_setting_budget_{$chatId}_")) {
                $parts = explode('_', $key);
                $gameId = end($parts);
                break;
            }
        }

        if (!$gameId) {
            $this->sendMessage($chatId, "Сталася помилка. Спробуйте знову.");
            Cache::forget("bot_state_$chatId");
            return;
        }

        $game = Game::find($gameId);
        if (!$game) {
            $this->sendMessage($chatId, "Гру не знайдено.");
            Cache::forget("bot_state_$chatId");
            Cache::forget("bot_setting_budget_{$chatId}_{$gameId}");
            return;
        }

        $budget = ($text === '-' || $text === 'Пропустити') ? null : $text;
        $game->update(['budget' => $budget]);

        Cache::forget("bot_state_$chatId");
        Cache::forget("bot_setting_budget_{$chatId}_{$gameId}");

        $this->sendMessage($chatId, "✅ Бюджет оновлено!");
    }

    private function handleJoinGame($chatId, $username, $payload)
    {
        try {
            \Log::info("handleJoinGame called", ['chatId' => $chatId, 'payload' => $payload]);

            // Extract join token from payload (format: join_TOKEN)
            $joinToken = str_replace('join_', '', $payload);

            if (empty($joinToken)) {
                $this->handleStart($chatId, $username);
                return;
            }

            // Check if user has language set, if not - ask for it first
            $user = \App\Models\User::where('telegram_id', $chatId)->first();
            if (!$user && $username) {
                $user = \App\Models\User::where('telegram_username', $username)->first();
            }

            $currentLang = $user->language ?? Participant::where('telegram_chat_id', $chatId)->value('language');

            if (!$currentLang) {
                // Save the pending join to continue after language selection
                Cache::put("bot_pending_join_$chatId", $payload, 3600);
                $this->askLanguage($chatId);
                return;
            }

            // Check if we've already processed this join request (prevent duplicates)
            $cacheKey = "bot_join_processed_{$chatId}_{$joinToken}";
            if (Cache::has($cacheKey)) {
                \Log::info("Join request already processed, skipping", ['chatId' => $chatId, 'joinToken' => $joinToken]);
                return;
            }

            // Mark this join request as processed (TTL: 60 seconds)
            Cache::put($cacheKey, true, 60);

            // Find game by join token
            $game = Game::where('join_token', $joinToken)->first();

        if (!$game) {
            $this->sendMessage($chatId, __('bot.game_not_found'));
            $this->handleStart($chatId, $username);
            return;
        }

        // Check if game already started
        if ($game->is_started) {
            $this->sendMessage($chatId, __('game.already_started'));
            $this->handleStart($chatId, $username);
            return;
        }

        // Check if user already joined
        $existing = Participant::where('game_id', $game->id)
            ->where('telegram_chat_id', $chatId)
            ->first();

        if ($existing) {
            $gameTitle = $game->title ?? 'Secret Santa';
            $msg = "✅ " . __('bot.game.already_participant') . "\n\n";
            $msg .= "🎮 *" . $gameTitle . "*\n\n";

            if ($game->is_started && $existing->assignmentAsSanta) {
                $msg .= __('bot.game.can_view_recipient');
            } else {
                $msg .= __('bot.game.waiting_for_start');
            }

            // Add WebApp button to open game
            $inlineKeyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => __('bot.btn.open_game'), 'web_app' => ['url' => route('game.join', $game->join_token)]]
                    ]
                ]
            ];

            $this->sendMessage($chatId, $msg, $inlineKeyboard);

            // Send main menu in a separate message to avoid conflicts
            $this->sendMessage($chatId, __('bot.back_to_menu'), $this->getMainMenu());
            return;
        }

        // Find or create User record
        $user = \App\Models\User::where('telegram_id', $chatId)->first();
        if (!$user && $username) {
            $user = \App\Models\User::firstOrCreate(
                ['telegram_username' => $username],
                [
                    'telegram_id' => $chatId,
                    'language' => $this->resolveLocale($chatId, $username),
                ]
            );
        } elseif ($user && !$user->telegram_id) {
            $user->update(['telegram_id' => $chatId]);
        }

        // Create participant
        $participant = Participant::create([
            'game_id' => $game->id,
            'name' => $username,
            'telegram_chat_id' => $chatId,
            'telegram_username' => $username,
            'shipping_address' => $user->shipping_address ?? null,
            'language' => $user->language ?? $this->resolveLocale($chatId, $username),
            'reveal_token' => bin2hex(random_bytes(16)),
        ]);

        $gameTitle = $game->title ?? 'Secret Santa';
        $msg = "🎉 " . __('game.joined_successfully') . "\n\n";
        $msg .= "🎮 *" . $gameTitle . "*\n\n";
        $msg .= __('bot.game.joined_info');

        // Add WebApp button to open game
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => __('bot.btn.open_game'), 'web_app' => ['url' => route('game.join', $game->join_token)]]
                ]
            ]
        ];

        $this->sendMessage($chatId, $msg, $keyboard);

        // Send main menu in a separate message
        $this->sendMessage($chatId, __('bot.back_to_menu'), $this->getMainMenu());

        } catch (\Exception $e) {
            \Log::error("Error in handleJoinGame", [
                'chatId' => $chatId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Silently log the error, don't spam the user
        }
    }
}
