<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Constraint extends Model
{
    use HasFactory;

    protected $fillable = ['game_id', 'participant_id', 'cannot_receive_from_participant_id'];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class, 'participant_id');
    }

    public function forbiddenSanta(): BelongsTo
    {
        return $this->belongsTo(Participant::class, 'cannot_receive_from_participant_id');
    }
}
