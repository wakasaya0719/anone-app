<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FamilyMember>
 */
class FamilyMemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $roles = ['father', 'mother', 'son', 'daughter', 'grandfather', 'grandmother', 'other'];
        $roleNames = [
            'father' => ['お父さん', 'パパ', '父'],
            'mother' => ['お母さん', 'ママ', '母'],
            'son' => ['太郎', '次郎', '三郎', '息子'],
            'daughter' => ['花子', '美咲', '由美', '娘'],
            'grandfather' => ['おじいちゃん', 'じいじ', '祖父'],
            'grandmother' => ['おばあちゃん', 'ばあば', '祖母'],
            'other' => ['家族', 'あなた'],
        ];

        $role = fake()->randomElement($roles);

        return [
            'user_id' => User::factory(),
            'name' => fake()->randomElement($roleNames[$role]),
            'role' => $role,
            'birth_date' => fake()->optional(0.7)->dateTimeBetween('-80 years', '-1 year'),
            'is_default_sender' => false,
            'display_order' => fake()->numberBetween(0, 100),
        ];
    }

    /**
     * デフォルト送信者として設定
     */
    public function defaultSender(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default_sender' => true,
        ]);
    }
}
