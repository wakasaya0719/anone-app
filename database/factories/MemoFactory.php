<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EmotionTag;
use App\Enums\MemoStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Memo>
 */
class MemoFactory extends Factory
{
    /**
     * モデルのデフォルト状態を定義
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = fake()->randomElement(MemoStatus::cases());

        return [
            'user_id' => User::factory(),
            'title' => fake()->realText(50),
            'content' => fake()->realText(500),
            'emotion_tag' => fake()->optional(0.7)->randomElement(EmotionTag::cases()),
            'memo_date' => fake()->optional(0.5)->dateTimeBetween('-2 years', 'now'),
            'sender' => fake()->optional(0.7)->name(),
            'recipient' => fake()->optional(0.7)->firstName(),
            'status' => $status,
            'published_at' => $status === MemoStatus::PUBLISHED
                ? fake()->dateTimeBetween('-1 year', 'now')
                : null,
        ];
    }

    /**
     * 下書き状態の言伝
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MemoStatus::DRAFT,
            'published_at' => null,
        ]);
    }

    /**
     * 公開済みの言伝
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MemoStatus::PUBLISHED,
            'published_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    /**
     * 特定の感情タグを持つ言伝
     */
    public function withEmotionTag(EmotionTag $tag): static
    {
        return $this->state(fn (array $attributes) => [
            'emotion_tag' => $tag,
        ]);
    }

    /**
     * 感情タグなしの言伝
     */
    public function withoutEmotionTag(): static
    {
        return $this->state(fn (array $attributes) => [
            'emotion_tag' => null,
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
