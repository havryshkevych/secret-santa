<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Constraint;
use App\Models\Game;
use App\Models\Participant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GameController extends Controller
{
    public function create()
    {
        return view('game.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'participants' => 'nullable|string', // "Name\nName..." - now optional
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:2000',
        ]);

        $names = [];
        if ($request->filled('participants')) {
            $names = array_filter(array_map('trim', explode("\n", $request->input('participants'))));

            if (count($names) > 0 && count($names) < 3) {
                return back()->withErrors(['participants' => __('game.need_min_participants')]);
            }
        }

        $game = DB::transaction(function () use ($names, $request) {
            $game = Game::create([
                'title' => $request->input('title'),
                'description' => $request->input('description'),
                'expires_at' => now()->addMonths(3), // Default expiry
                'organizer_chat_id' => auth()->check() ? auth()->user()->telegram_id : null,
                'user_id' => auth()->check() ? auth()->id() : null,
            ]);

            foreach ($names as $line) {
                if (empty($line)) {
                    continue;
                }

                // Parse Name and @username
                // Supports "Name @username" or "Name; @username"
                $telegramUsername = null;
                $name = $line;

                // Trim line to handle trailing spaces that break the $ anchor
                $line = trim($line);
                if (preg_match('/(@[a-zA-Z0-9_]+)$/i', $line, $matches)) {
                    $rawUsername = $matches[1];
                    $telegramUsername = strtolower(ltrim($rawUsername, '@'));

                    // Remove only the trailing username from the name
                    $name = trim(preg_replace('/'.preg_quote($rawUsername, '/').'$/i', '', $line));
                    // Also clean up any trailing semicolons or commas if user used separator
                    $name = trim($name, " \t\n\r\0\x0B;,");
                }

                // Look up global shipping address for this participant
                $shippingAddress = null;
                if ($telegramUsername) {
                    $existingUser = \App\Models\User::where('telegram_username', $telegramUsername)
                        ->whereNotNull('shipping_address')
                        ->first();
                    if ($existingUser) {
                        $shippingAddress = $existingUser->shipping_address;
                    } else {
                        // Check other participants with same username
                        $prevPart = Participant::where('telegram_username', $telegramUsername)
                            ->whereNotNull('shipping_address')
                            ->latest()
                            ->first();
                        if ($prevPart) {
                            $shippingAddress = $prevPart->shipping_address;
                        }
                    }
                }

                $game->participants()->create([
                    'name' => $name ?: $line,
                    'telegram_username' => $telegramUsername,
                    'shipping_address' => $shippingAddress,
                ]);
            }

            return $game;
        });

        // Redirect to edit page to show join link and manage game
        return redirect()->route('game.edit', $game->id)->with('success', __('game.created_successfully'));
    }

    public function constraints(Game $game)
    {
        $game->load('participants');

        return view('game.constraints', compact('game'));
    }

    public function storeConstraints(Request $request, Game $game)
    {
        // specific structure expected: array of exclusions
        // e.g. input named 'constraints' [participant_id] => [bad_id, bad_id]

        $request->validate([
            'constraints' => 'array',
        ]);

        DB::transaction(function () use ($request, $game) {
            // clear old constraints if editing?
            $game->constraints()->delete();

            $rawConstraints = $request->input('constraints', []);
            foreach ($rawConstraints as $participantId => $badIds) {
                if (! is_array($badIds)) {
                    continue;
                }
                foreach ($badIds as $badId) {
                    Constraint::create([
                        'game_id' => $game->id,
                        'participant_id' => $participantId,
                        'cannot_receive_from_participant_id' => $badId, // Wait, logic check.
                    ]);
                }
            }
        });

        return redirect()->route('game.assign', $game->id); // Or straight to generate
    }

    public function assign(Game $game)
    {
        $game->load(['participants', 'constraints']);
        $participants = $game->participants;

        $maxAttempts = 1000;
        $attempt = 0;
        $validAssignment = null;

        $forbidden = [];
        foreach ($game->constraints as $c) {
            $forbidden[$c->cannot_receive_from_participant_id][] = $c->participant_id;
        }

        while ($attempt < $maxAttempts) {
            $attempt++;
            $shuffled = $participants->shuffle();

            $isValid = true;
            $pairs = [];

            for ($i = 0; $i < $participants->count(); $i++) {
                $santa = $participants[$i];
                $recipient = $shuffled[$i];

                if ($santa->id === $recipient->id) {
                    $isValid = false;
                    break;
                }

                if (isset($forbidden[$santa->id]) && in_array($recipient->id, $forbidden[$santa->id])) {
                    $isValid = false;
                    break;
                }

                $pairs[] = ['santa' => $santa, 'recipient' => $recipient];
            }

            if ($isValid) {
                $validAssignment = $pairs;
                break;
            }
        }

        if (! $validAssignment) {
            return back()->withErrors(['general' => 'Не вдалося згенерувати пари. Спробуйте змінити обмеження.']);
        }

        DB::transaction(function () use ($game, $validAssignment) {
            $game->assignments()->delete();

            foreach ($validAssignment as $pair) {
                $santa = $pair['santa'];
                $recipient = $pair['recipient'];

                // Regenerate reveal token for security
                $santa->reveal_token = (string) Str::uuid();
                $santa->save();

                Assignment::create([
                    'game_id' => $game->id,
                    'santa_id' => $santa->id,
                    'recipient_id' => $recipient->id,
                ]);
            }

            // Mark game as started
            $game->is_started = true;
            $game->save();
        });

        // Automatically notify all participants
        $this->sendNotificationsToParticipants($game);

        return redirect()->route('game.result', $game->id)->with('status', __('game.pairs_generated_and_notified'));
    }

    public function result(Game $game)
    {
        // Check authorization - only game organizer can see results
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Please log in to view game results');
        }

        if (!$this->isGameOrganizer($game)) {
            abort(403, 'Only the game organizer can view game results');
        }

        // Need to show PINs ONLY IF just generated (session flash).
        // If reloading, we shouldn't show PINs again for security? PRD says "Organizer copies...".
        // If organizer closes tab, they are lost?
        // PRD doesn't specify persistence of plain PINs.
        // "PIN stored as HASH". Meaning we CANNOT recover them.
        // So we must show them ONCE.

        $game->load('participants');

        // We need the plain pins. They are not in DB.
        // Option 1: Store plain pins in Session during 'assign' and retrieve here.
        // Option 2: Verify if we can just show them.

        // If the 'assign' method generates them, it should flash them to session or pass via view.
        // But 'redirect' loses the object state unless flashed.
        // I'll assume 'assign' logic above isn't quite right for passing data.
        // I should probably do the generation and view in same request OR flash data.

        // Let's refactor 'assign' to render the view directly or flash the data.

        return view('game.result', [
            'game' => $game,
            // 'participants' with 'plain_pin' attached if available in session?
        ]);

    }

    public function edit(Game $game)
    {
        // Check authorization
        if (!auth()->check()) {
            return redirect()->route('login.telegram')->with('error', 'Please log in to manage this game');
        }

        if (!$this->isGameOrganizer($game)) {
            abort(403, 'Only the game organizer can manage this game');
        }

        return view('game.edit', compact('game'));
    }

    private function isGameOrganizer(Game $game): bool
    {
        if (!auth()->check()) {
            return false;
        }

        $user = auth()->user();

        // Check if user created the game via web
        if ($game->user_id && $game->user_id === $user->id) {
            return true;
        }

        // Check if user created the game via bot (telegram_id match)
        // Use loose equality (==) because organizer_chat_id is bigInteger and telegram_id is string
        if ($game->organizer_chat_id && $user->telegram_id && $game->organizer_chat_id == $user->telegram_id) {
            return true;
        }

        return false;
    }

    public function update(Request $request, Game $game)
    {
        if (!$this->isGameOrganizer($game)) {
            abort(403, 'Only the game organizer can update this game');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
        ]);

        $game->update([
            'title' => $request->input('title'),
            'description' => $request->input('description'),
        ]);

        return redirect()->route('game.myGames')->with('status', 'Дані гри оновлено!');
    }

    public function addParticipant(Request $request, Game $game)
    {
        // Check authorization
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Please log in to add participants');
        }

        if (!$this->isGameOrganizer($game)) {
            abort(403, 'Only the game organizer can add participants');
        }

        // Cannot add participants after game has started
        if ($game->is_started) {
            return back()->withErrors(['error' => __('game.cannot_add_after_start')]);
        }

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        // Create participant without Telegram info
        $participant = Participant::create([
            'game_id' => $game->id,
            'name' => $request->input('name'),
            'reveal_token' => \Illuminate\Support\Str::uuid(),
            // telegram_chat_id and telegram_username are NULL
        ]);

        return back()->with('status', __('game.participant_added'));
    }

    public function destroy(Game $game)
    {
        if (!$this->isGameOrganizer($game)) {
            abort(403, 'Only the game organizer can delete this game');
        }

        $game->delete();

        return redirect()->route('game.myGames')->with('status', __('game.game_deleted'));
    }

    public function notifyPlayers(Game $game)
    {
        if (!$this->isGameOrganizer($game)) {
            abort(403, 'Only the game organizer can notify players');
        }

        if (!$game->is_started) {
            return back()->withErrors(['error' => __('game.game_not_started')]);
        }

        $result = $this->sendNotificationsToParticipants($game);

        return back()->with('status', __('game.players_notified', [
            'count' => $result['count'],
            'total' => $result['total']
        ]));
    }

    private function sendNotificationsToParticipants(Game $game)
    {
        $botToken = config('services.telegram.bot_token');
        if (!$botToken) {
            return ['count' => 0, 'total' => 0];
        }

        $count = 0;
        $total = 0;

        foreach ($game->participants as $participant) {
            $total++;

            if (!$participant->telegram_chat_id) {
                continue;
            }

            $assignment = $participant->assignmentAsSanta;
            if (!$assignment) {
                continue;
            }

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

            // Set locale for this participant
            $locale = $participant->language ?? 'uk';
            $prevLocale = app()->getLocale();
            app()->setLocale($locale);

            $gameTitle = str_replace('_', '\\_', $game->title ?? 'Secret Santa');
            $gameDesc = $game->description ? "\n\n📋 " . str_replace('_', '\\_', $game->description) : '';

            $msg = "🎉 " . __('bot.game.pairs_generated') . "\n\n".
                   "🎮 *{$gameTitle}*{$gameDesc}\n\n".
                   __('bot.game.click_to_reveal');

            \Illuminate\Support\Facades\Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                'chat_id' => $participant->telegram_chat_id,
                'text' => $msg,
                'parse_mode' => 'Markdown',
                'reply_markup' => [
                    'inline_keyboard' => [
                        [
                            ['text' => __('bot.btn.reveal_recipient'), 'web_app' => ['url' => $link]],
                        ],
                    ],
                ],
            ]);

            // Restore previous locale
            app()->setLocale($prevLocale);

            $count++;
        }

        return ['count' => $count, 'total' => $total];
    }

    public function myGames()
    {
        $user = auth()->user();

        $organizedGames = [];
        if ($user->telegram_id) {
            $organizedGames = Game::where('organizer_chat_id', $user->telegram_id)
                ->withCount('participants')
                ->latest()
                ->get();
        }

        $participations = [];
        if ($user->telegram_id || $user->telegram_username) {
            // Find participations by telegram_chat_id OR telegram_username
            $participations = Participant::where(function ($query) use ($user) {
                if ($user->telegram_id) {
                    $query->where('telegram_chat_id', $user->telegram_id);
                }
                if ($user->telegram_username) {
                    $query->orWhere('telegram_username', strtolower($user->telegram_username));
                }
            })
                ->with(['game.participants'])
                ->get();
        }

        return view('game.my_games', compact('organizedGames', 'participations'));
    }

    public function myWishlist()
    {
        $user = auth()->user();

        // Get all participations
        $participations = [];
        if ($user->telegram_id || $user->telegram_username) {
            $participations = Participant::where(function ($query) use ($user) {
                if ($user->telegram_id) {
                    $query->where('telegram_chat_id', $user->telegram_id);
                }
                if ($user->telegram_username) {
                    $query->orWhere('telegram_username', strtolower($user->telegram_username));
                }
            })
                ->with('game')
                ->get();
        }

        return view('game.my_wishlist', compact('participations'));
    }

    public function updateMyWishlist(Request $request)
    {
        $user = auth()->user();

        // Update global shipping address
        if ($request->has('shipping_address')) {
            $user->update(['shipping_address' => $request->input('shipping_address')]);

            // Also update all participants - use safe matching logic
            if ($user->telegram_id) {
                // If user has telegram_id, update by telegram_chat_id
                Participant::where('telegram_chat_id', $user->telegram_id)
                    ->update(['shipping_address' => $request->input('shipping_address')]);
            } elseif ($user->telegram_username) {
                // If no telegram_id, update by username but ONLY where telegram_chat_id is null
                Participant::where('telegram_username', strtolower($user->telegram_username))
                    ->whereNull('telegram_chat_id')
                    ->update(['shipping_address' => $request->input('shipping_address')]);
            }
        }

        // Update wishlists for each game
        if ($request->has('wishlists')) {
            foreach ($request->input('wishlists') as $participantId => $wishlistText) {
                $participant = Participant::find($participantId);
                if ($participant) {
                    // Verify this participant belongs to the user - strict matching
                    $belongsToUser = false;

                    if ($user->telegram_id) {
                        // If user has telegram_id, participant must have matching telegram_chat_id
                        $belongsToUser = $participant->telegram_chat_id == $user->telegram_id;
                    } elseif ($user->telegram_username) {
                        // If no telegram_id, match by username AND participant must NOT have telegram_chat_id
                        $belongsToUser = ($participant->telegram_username == strtolower($user->telegram_username) &&
                                         $participant->telegram_chat_id === null);
                    }

                    if ($belongsToUser) {
                        $participant->update(['wishlist_text' => $wishlistText]);
                    }
                }
            }
        }

        return back()->with('success', __('wishlist.updated_successfully'));
    }

    public function showJoin($token)
    {
        $game = Game::where('join_token', $token)->firstOrFail();

        // Check if already joined
        $alreadyJoined = false;
        $participant = null;

        if (auth()->check()) {
            $user = auth()->user();

            // Find participant - prefer telegram_chat_id match, fallback to username
            if ($user->telegram_id) {
                // If user has telegram_id, match by telegram_chat_id
                $participant = Participant::where('game_id', $game->id)
                    ->where('telegram_chat_id', $user->telegram_id)
                    ->first();
            } elseif ($user->telegram_username) {
                // Fallback: match by username (only if telegram_id is not set)
                $participant = Participant::where('game_id', $game->id)
                    ->where('telegram_username', $user->telegram_username)
                    ->whereNull('telegram_chat_id') // Only match participants without telegram_chat_id
                    ->first();
            }

            $alreadyJoined = $participant !== null;

            // Auto-join if logged in and not already joined
            // BUT skip if user just left the game (to prevent auto-rejoin)
            $justLeft = session()->pull('just_left_game_' . $game->id);

            if (!$alreadyJoined && !$game->is_started && !$justLeft) {
                $user = auth()->user();

                // Create participant
                $participant = Participant::create([
                    'game_id' => $game->id,
                    'name' => $user->telegram_username ?? $user->name,
                    'telegram_chat_id' => $user->telegram_id,
                    'telegram_username' => $user->telegram_username,
                    'shipping_address' => $user->shipping_address,
                    'language' => $user->language ?? 'uk',
                    'pin_hash' => Hash::make(str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT)),
                    'reveal_token' => bin2hex(random_bytes(16)),
                ]);

                $alreadyJoined = true;

                // Show success message
                session()->flash('success', __('game.joined_successfully'));
            }
        }

        return view('game.join', compact('game', 'alreadyJoined', 'participant'));
    }

    public function join(Request $request, $token)
    {
        $game = Game::where('join_token', $token)->firstOrFail();

        if ($game->is_started) {
            return back()->withErrors(['error' => __('game.already_started')]);
        }

        if (!auth()->check()) {
            return redirect()->route('game.join', $token)->withErrors(['error' => __('game.must_login')]);
        }

        $user = auth()->user();

        // Check if already joined - same logic as showJoin
        if ($user->telegram_id) {
            $existing = Participant::where('game_id', $game->id)
                ->where('telegram_chat_id', $user->telegram_id)
                ->first();
        } elseif ($user->telegram_username) {
            $existing = Participant::where('game_id', $game->id)
                ->where('telegram_username', $user->telegram_username)
                ->whereNull('telegram_chat_id')
                ->first();
        } else {
            $existing = null;
        }

        if ($existing) {
            return redirect()->route('game.join', $token)->with('success', __('game.already_joined'));
        }

        // Create participant
        Participant::create([
            'game_id' => $game->id,
            'name' => $user->telegram_username ?? $user->name,
            'telegram_chat_id' => $user->telegram_id,
            'telegram_username' => $user->telegram_username,
            'shipping_address' => $user->shipping_address,
            'language' => $user->language ?? 'uk',
            'pin_hash' => Hash::make(str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT)),
            'reveal_token' => bin2hex(random_bytes(16)),
        ]);

        return redirect()->route('game.join', $token)->with('success', __('game.joined_successfully'));
    }

    public function leaveGame(Game $game)
    {
        if (!auth()->check()) {
            return redirect()->route('game.join', $game->join_token)->withErrors(['error' => __('game.must_login')]);
        }

        // Can only leave if game hasn't started
        if ($game->is_started) {
            return back()->withErrors(['error' => __('game.cannot_leave_started')]);
        }

        $user = auth()->user();

        // Find participant - same logic as showJoin
        if ($user->telegram_id) {
            $participant = Participant::where('game_id', $game->id)
                ->where('telegram_chat_id', $user->telegram_id)
                ->first();
        } elseif ($user->telegram_username) {
            $participant = Participant::where('game_id', $game->id)
                ->where('telegram_username', $user->telegram_username)
                ->whereNull('telegram_chat_id')
                ->first();
        } else {
            $participant = null;
        }

        if ($participant) {
            $participant->delete();

            // Set flag to prevent auto-rejoin
            session()->flash('just_left_game_' . $game->id, true);

            return redirect()->route('game.join', $game->join_token)->with('success', __('game.left_successfully'));
        }

        return back()->withErrors(['error' => __('game.not_participant')]);
    }

    public function startGame(Game $game)
    {
        if (!$this->isGameOrganizer($game)) {
            abort(403, 'Only the game organizer can start the game');
        }

        if ($game->is_started) {
            return back()->withErrors(['error' => __('game.already_started')]);
        }

        $participantCount = $game->participants()->count();
        if ($participantCount < 3) {
            return back()->withErrors(['error' => __('game.need_min_participants')]);
        }

        // Generate assignments (copy logic from assign method)
        $participants = $game->participants()->get();
        $constraints = $game->constraints()->get();

        $forbidden = [];
        foreach ($constraints as $c) {
            if (!isset($forbidden[$c->cannot_receive_from_participant_id])) {
                $forbidden[$c->cannot_receive_from_participant_id] = [];
            }
            $forbidden[$c->cannot_receive_from_participant_id][] = $c->participant_id;
        }

        $maxAttempts = 1000;
        $validAssignment = null;

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $shuffled = $participants->pluck('id')->shuffle()->toArray();
            $valid = true;

            foreach ($participants as $i => $santa) {
                $recipientId = $shuffled[$i];
                if ($recipientId === $santa->id) {
                    $valid = false;
                    break;
                }
                if (isset($forbidden[$santa->id]) && in_array($recipientId, $forbidden[$santa->id])) {
                    $valid = false;
                    break;
                }
            }

            if ($valid) {
                $validAssignment = $shuffled;
                break;
            }
        }

        if (!$validAssignment) {
            return back()->withErrors(['error' => __('game.no_valid_assignment')]);
        }

        // Clear old assignments
        Assignment::where('game_id', $game->id)->delete();

        // Create new assignments and regenerate PINs
        foreach ($participants as $i => $santa) {
            $recipientId = $validAssignment[$i];
            Assignment::create([
                'game_id' => $game->id,
                'santa_id' => $santa->id,
                'recipient_id' => $recipientId,
            ]);

            // Regenerate PIN
            $newPin = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $santa->update([
                'pin_hash' => Hash::make($newPin),
                'pin' => $newPin,
            ]);
        }

        // Mark game as started
        $game->update(['is_started' => true]);

        return redirect()->route('game.result', $game)->with('success', __('game.started_successfully'));
    }
}
