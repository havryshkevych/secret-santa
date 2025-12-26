<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class RevealController extends Controller
{
    public function show(string $gameId, string $participantId, string $token)
    {
        // Validation of route params vs DB
        $participant = Participant::where('id', $participantId)
            ->where('game_id', $gameId)
            ->where('reveal_token', $token)
            ->firstOrFail();

        // Check if participant has Telegram (chat_id or username)
        $hasTelegram = !empty($participant->telegram_chat_id) || !empty($participant->telegram_username);

        // If participant does NOT have Telegram, allow direct access via token
        if (!$hasTelegram) {
            return $this->showRevealResult($participant, $gameId, $token);
        }

        // If participant HAS Telegram, require authentication
        // Check if already authenticated via Telegram session (Redirect from bot)
        $telegramAuthKey = 'telegram_auth:'.$participant->id;
        $isTelegramAuthed = Cache::get($telegramAuthKey) === true;

        // Check if authenticated via Web Telegram Login
        $isWebAuthed = false;
        if (Auth::check()) {
            $user = Auth::user();
            $userUsername = strtolower($user->telegram_username ?? '');
            $partUsername = strtolower($participant->telegram_username ?? '');

            if (($user->telegram_id && $user->telegram_id == $participant->telegram_chat_id) ||
                ($userUsername && $userUsername == $partUsername)) {

                // If matched by username but chat_id is missing, sync it
                if (! $participant->telegram_chat_id && $user->telegram_id) {
                    $participant->update(['telegram_chat_id' => $user->telegram_id]);
                }

                $isWebAuthed = true;
            }
        }

        if ($isTelegramAuthed || $isWebAuthed) {
            // Auto-reveal if authenticated
            return $this->showRevealResult($participant, $gameId, $token);
        }

        // Not authenticated but has Telegram - show login page
        // Generate Telegram auth token for this session (for linking)
        $authToken = Str::random(32);
        Cache::put('telegram_reveal_token:'.$authToken, [
            'participant_id' => $participant->id,
            'game_id' => $gameId,
        ], now()->addMinutes(10));

        $botUsername = config('services.telegram.bot_username', 'YourBotUsername');

        return view('reveal.show', compact('participant', 'gameId', 'token', 'authToken', 'botUsername'));
    }

    protected function showRevealResult(Participant $participant, string $gameId, string $token)
    {
        // Success. Get Assignment.
        $assignment = $participant->assignmentAsSanta; // Relation defined in Participant

        if (! $assignment) {
            return back()->withErrors(['general' => 'Призначення не знайдено. Можливо, гра ще не розпочалася.']);
        }

        // Clean output
        $recipient = $assignment->recipient;

        // Store revealed state in session to allow wishlist updates without re-entering PIN immediately
        session(['revealed_participant_'.$participant->id => true]);

        return view('reveal.result', compact('participant', 'recipient', 'gameId', 'token'));
    }

    public function updateWishlist(Request $request, string $gameId, string $participantId, string $token)
    {
        $participant = Participant::where('id', $participantId)
            ->where('game_id', $gameId)
            ->where('reveal_token', $token)
            ->firstOrFail();

        // Optional: Check session if we want to enforce PIN entry was done recently
        // if (!session('revealed_participant_' . $participant->id)) { abort(403); }

        $request->validate([
            'wishlist' => 'nullable|string|max:5000',
            'shipping_address' => 'nullable|string|max:5000',
        ]);

        $participant->wishlist_text = $request->input('wishlist');
        $address = $request->input('shipping_address');
        $participant->shipping_address = $address;
        $participant->save();

        if ($address) {
            // Update User if exists
            if (Auth::check()) {
                Auth::user()->update(['shipping_address' => $address]);
            }

            // Sync address to ALL participants with same chat_id or username
            if ($participant->telegram_chat_id) {
                Participant::where('telegram_chat_id', $participant->telegram_chat_id)
                    ->update(['shipping_address' => $address]);
            }
            if ($participant->telegram_username) {
                Participant::where('telegram_username', $participant->telegram_username)
                    ->update(['shipping_address' => $address]);
            }
        }

        return back()->with('status', 'Дані успішно оновлено!');
    }

    public function resendNotification(string $gameId, string $participantId, string $token)
    {
        $participant = Participant::where('id', $participantId)
            ->where('game_id', $gameId)
            ->where('reveal_token', $token)
            ->firstOrFail();

        $chatId = $participant->telegram_chat_id;

        if (! $chatId && Auth::check() && Auth::user()->telegram_id) {
            $chatId = Auth::user()->telegram_id;
        }

        if (! $chatId) {
            return back()->withErrors(['general' => 'Telegram ID не знайдено. Будь ласка, запустіть бота.']);
        }

        $assignment = $participant->assignmentAsSanta;
        if (! $assignment) {
            return back()->withErrors(['general' => 'Пари ще не сформовані.']);
        }

        $recipient = $assignment->recipient;
        $gameTitle = str_replace('_', '\\_', $participant->game->title ?? 'Secret Santa');
        $link = route('reveal.show', ['gameId' => $gameId, 'participantId' => $participant->id, 'token' => $token]);

        $msg = "Привіт! 🎅 Ось твої дані для гри «{$gameTitle}».\n\n".
               "Ти даруєш: *{$recipient->name}*\n\n".
               'Тисни кнопку нижче, щоб відкрити картку отримувача:';

        // Call Telegram API directly as we are in a controller
        $botToken = config('services.telegram.bot_token');
        if ($botToken) {
            \Illuminate\Support\Facades\Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $msg,
                'parse_mode' => 'Markdown',
                'reply_markup' => [
                    'inline_keyboard' => [
                        [
                            ['text' => '🎁 Відкрити результат', 'web_app' => ['url' => $link]],
                        ],
                    ],
                ],
            ]);
        }

        return back()->with('status', 'Сповіщення успішно надіслано в Telegram! ✅');
    }
}
