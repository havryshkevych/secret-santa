<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Game;
use App\Models\Participant;

class AdminController extends Controller
{
    public function index()
    {
        $stats = [
            'total_games' => Game::count(),
            'total_participants' => Participant::count(),
            'total_assignments' => Assignment::count(),
            'bot_games' => Game::whereNotNull('organizer_chat_id')->count(),
        ];

        $games = Game::withCount('participants')
            ->latest()
            ->paginate(20);

        return view('admin.index', compact('stats', 'games'));
    }

    public function destroyGame(Game $game)
    {
        $game->delete();

        return back()->with('status', 'Game deleted successfully.');
    }
}
