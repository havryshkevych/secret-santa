<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Assignment extends Model
{
    use HasFactory;

    protected $fillable = ['game_id', 'santa_id', 'recipient_id'];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function santa(): BelongsTo
    {
        return $this->belongsTo(Participant::class, 'santa_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(Participant::class, 'recipient_id');
    }
}
