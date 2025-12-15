<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Memo>
 */
class MemoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(),
            'body' => fake()->paragraphs(3, true),
            'sender' => fake()->optional(0.7)->name(),
            'recipient' => fake()->optional(0.7)->firstName(),
            'memo_date' => fake()->optional(0.5)->dateTimeBetween('-2 years', 'now'),
            'published_at' => fake()->optional(0.8)->dateTimeBetween('-1 year', 'now'),
        ];
    }

    /**
     * 下書き状態の言伝
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'published_at' => null,
        ]);
    }

    /**
     * 公開済みの言伝
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'published_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    /**
     * 送受信者情報なしの言伝
     */
    public function withoutParticipants(): static
    {
        return $this->state(fn (array $attributes) => [
            'sender' => null,
            'recipient' => null,
        ]);
    }

    /**
     * 記録日なしの言伝
     */
    public function withoutMemoDate(): static
    {
        return $this->state(fn (array $attributes) => [
            'memo_date' => null,
        ]);
    }
}
