<?php

namespace Database\Factories;

use App\Models\Game;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class GameFactory extends Factory
{
    protected $model = Game::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'description' => fake()->sentence(),
            'organizer_chat_id' => fake()->randomNumber(9),
            'join_token' => Str::random(32),
            'is_started' => false,
            'budget' => fake()->randomElement([null, fake()->randomNumber(4)]),
        ];
    }

    public function started(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_started' => true,
        ]);
    }
}
