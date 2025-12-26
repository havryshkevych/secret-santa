<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Game extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'expires_at',
        'organizer_chat_id',
        'user_id',
        'budget',
        'result_format',
        'registration_open',
        'group_chat_id',
        'join_token',
        'is_started',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($game) {
            if (empty($game->join_token)) {
                $game->join_token = bin2hex(random_bytes(16));
            }
        });
    }

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    public function constraints(): HasMany
    {
        return $this->hasMany(Constraint::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    /**
     * Get Telegram Mini App join link for this game
     */
    public function getTelegramJoinLink(): string
    {
        $botUsername = config('services.telegram.bot_username', env('TELEGRAM_BOT_USERNAME', 'little_santa_bot'));
        // Remove @ if present
        $botUsername = ltrim($botUsername, '@');

        // Format: https://t.me/bot_username?start=join_TOKEN
        // This will open the bot and pass the join token as a start parameter
        return "https://t.me/{$botUsername}?start=join_{$this->join_token}";
    }
}
