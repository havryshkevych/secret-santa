<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class TelegramBotHealthCheck extends Command
{
    protected $signature = 'telegram:check';
    protected $description = 'Check if the Telegram bot is alive';

    public function handle()
    {
        $lastSeen = Cache::get('telegram_bot_last_seen');

        if (!$lastSeen) {
            $this->error('Bot has never been seen.');
            return 1;
        }

        $lastSeenTime = Carbon::parse($lastSeen);
        
        if ($lastSeenTime->diffInMinutes(now()) > 2) {
            $this->error('Bot was last seen more than 2 minutes ago: ' . $lastSeen);
            return 1;
        }

        $this->info('Bot is healthy. Last seen: ' . $lastSeen);
        return 0;
    }
}
