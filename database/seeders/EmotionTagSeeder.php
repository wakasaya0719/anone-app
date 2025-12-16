<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\EmotionTag;
use Illuminate\Database\Seeder;

/**
 * 感情タグSeeder
 *
 * 8種類の固定感情タグをマスタデータとして投入
 */
class EmotionTagSeeder extends Seeder
{
    /**
     * 感情タグのマスタデータ
     *
     * @var array<int, array<string, mixed>>
     */
    private array $emotionTags = [
        [
            'name' => '嬉しい',
            'slug' => 'happy',
            'icon' => 'smile',
            'color' => 'yellow',
            'display_order' => 1,
        ],
        [
            'name' => '悲しい',
            'slug' => 'sad',
            'icon' => 'frown',
            'color' => 'blue',
            'display_order' => 2,
        ],
        [
            'name' => '感謝',
            'slug' => 'grateful',
            'icon' => 'heart',
            'color' => 'pink',
            'display_order' => 3,
        ],
        [
            'name' => '誇り',
            'slug' => 'proud',
            'icon' => 'star',
            'color' => 'gold',
            'display_order' => 4,
        ],
        [
            'name' => '後悔',
            'slug' => 'regret',
            'icon' => 'cloud',
            'color' => 'gray',
            'display_order' => 5,
        ],
        [
            'name' => '愛情',
            'slug' => 'love',
            'icon' => 'heart-fill',
            'color' => 'red',
            'display_order' => 6,
        ],
        [
            'name' => 'クセ',
            'slug' => 'quirky',
            'icon' => 'lightbulb',
            'color' => 'purple',
            'display_order' => 7,
        ],
        [
            'name' => '懐かしい',
            'slug' => 'nostalgic',
            'icon' => 'clock',
            'color' => 'teal',
            'display_order' => 8,
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->emotionTags as $tag) {
            EmotionTag::firstOrCreate(
                ['slug' => $tag['slug']],
                $tag
            );
        }

        $this->command->info('感情タグ8種類を投入しました。');
    }
}
