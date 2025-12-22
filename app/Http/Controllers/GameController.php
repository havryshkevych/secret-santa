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
            'participants' => 'required|string', // "Name\nName..."
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:2000',
        ]);

        $names = array_filter(array_map('trim', explode("\n", $request->input('participants'))));

        if (count($names) < 3) { 
            return back()->withErrors(['participants' => 'Потрібно мінімум 3 учасники.']);
        }

        $game = DB::transaction(function () use ($names, $request) {
            $game = Game::create([
                'title' => $request->input('title'),
                'description' => $request->input('description'),
                'expires_at' => now()->addMonths(3), // Default expiry
            ]);

            foreach ($names as $line) {
                if (empty($line)) continue;
                
                // Parse Name and @username
                // Supports "Name @username" or "Name; @username"
                $telegramUsername = null;
                $name = $line;

                // Simple regex to find an ending @username
                if (preg_match('/(@[\w\d_]+)$/i', $line, $matches)) {
                    $rawUsername = $matches[1];
                    $telegramUsername = strtolower(ltrim($rawUsername, '@'));
                    
                    // Remove the username from the name line
                    $name = trim(str_replace($rawUsername, '', $line));
                    // Also clean up any trailing semicolons or commas if user used separator
                    $name = trim($name, " \t\n\r\0\x0B;,");
                }

                $game->participants()->create([
                    'name' => $name ?: $line, // Fallback if name becomes empty (unlikely with valid input)
                    'telegram_username' => $telegramUsername,
                ]);
            }
            return $game;
        });

        return redirect()->route('game.constraints', $game->id);
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
                if (!is_array($badIds)) continue;
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

        if (!$validAssignment) {
            return back()->withErrors(['general' => 'Не вдалося згенерувати пари. Спробуйте змінити обмеження.']);
        }

        $pins = [];

        DB::transaction(function () use ($game, $validAssignment, &$pins) {
            $game->assignments()->delete();
            
            foreach ($validAssignment as $pair) {
                $santa = $pair['santa'];
                $recipient = $pair['recipient'];
                
                // Always regenerate PINs on re-roll to ensure organizer has current ones
                $pin = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
                $santa->pin_hash = Hash::make($pin);
                $santa->reveal_token = (string) Str::uuid();
                $santa->save();
                
                $pins[$santa->id] = $pin;

                Assignment::create([
                    'game_id' => $game->id,
                    'santa_id' => $santa->id,
                    'recipient_id' => $recipient->id,
                ]);
            }
        });

        return redirect()->route('game.result', $game->id)->with('pins', $pins);
    }
    
    public function result(Game $game)
    {
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
    public function update(Request $request, Game $game)
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:2000',
        ]);

        $game->update([
            'title' => $request->input('title'),
            'description' => $request->input('description'),
        ]);

        return back()->with('status', 'Дані гри оновлено!');
    }
}
