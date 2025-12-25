<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Participant extends Model
{
    use HasFactory;

    protected $fillable = ['game_id', 'name', 'reveal_token', 'telegram_username', 'telegram_chat_id', 'wishlist_text', 'shipping_address', 'language'];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function assignmentAsSanta(): HasOne
    {
        return $this->hasOne(Assignment::class, 'santa_id');
    }

    public function assignmentAsRecipient(): HasOne
    {
        return $this->hasOne(Assignment::class, 'recipient_id');
    }
}
