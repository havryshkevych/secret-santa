<?php

namespace Database\Factories;

use App\Models\Game;
use App\Models\Participant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class ParticipantFactory extends Factory
{
    protected $model = Participant::class;

    public function definition(): array
    {
        return [
            'game_id' => Game::factory(),
            'name' => fake()->userName(),
            'telegram_chat_id' => fake()->randomNumber(9),
            'telegram_username' => fake()->userName(),
            'shipping_address' => fake()->address(),
            'wishlist_text' => fake()->sentence(),
            'language' => fake()->randomElement(['uk', 'en']),
            'reveal_token' => bin2hex(random_bytes(16)),
        ];
    }

    public function withoutTelegramChatId(): static
    {
        return $this->state(fn (array $attributes) => [
            'telegram_chat_id' => null,
        ]);
    }
}
