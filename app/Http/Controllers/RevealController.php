<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Participant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
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

        // Check if already authenticated via Telegram
        $telegramAuthKey = 'telegram_auth:' . $participant->id;
        $isTelegramAuthed = Cache::get($telegramAuthKey) === true;
        
        if ($isTelegramAuthed) {
            // Auto-reveal if Telegram authenticated
            return $this->showRevealResult($participant, $gameId, $token);
        }

        // Generate Telegram auth token for this session
        $authToken = Str::random(32);
        Cache::put('telegram_reveal_token:' . $authToken, [
            'participant_id' => $participant->id,
            'game_id' => $gameId,
        ], now()->addMinutes(10));

        $botUsername = config('services.telegram.bot_username', 'YourBotUsername');

        return view('reveal.show', compact('participant', 'gameId', 'token', 'authToken', 'botUsername'));
    }

    public function reveal(Request $request, string $gameId, string $participantId, string $token)
    {
        $participant = Participant::where('id', $participantId)
            ->where('game_id', $gameId)
            ->where('reveal_token', $token)
            ->firstOrFail();

        $key = 'pin-attempt:' . $participant->id;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['pin' => 'Забагато спроб. Будь ласка, спробуйте пізніше.']);
        }

        $request->validate(['pin' => 'required|digits:4']);

        if (!Hash::check($request->pin, $participant->pin_hash)) {
            RateLimiter::hit($key, 300); // 5 minute lockout after 5 fails
            return back()->withErrors(['pin' => 'Невірний PIN-код.']);
        }

        RateLimiter::clear($key);

        return $this->showRevealResult($participant, $gameId, $token);
    }

    protected function showRevealResult(Participant $participant, string $gameId, string $token)
    {
        // Success. Get Assignment.
        $assignment = $participant->assignmentAsSanta; // Relation defined in Participant

        if (!$assignment) {
            return back()->withErrors(['general' => 'Призначення не знайдено. Можливо, гра ще не розпочалася.']);
        }

        // Clean output
        $recipient = $assignment->recipient;

        // Store revealed state in session to allow wishlist updates without re-entering PIN immediately
        session(['revealed_participant_' . $participant->id => true]);

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
        ]);

        $participant->wishlist_text = $request->input('wishlist');
        $participant->save();

        return back()->with('status', 'Побажання успішно оновлено!');
    }
}
