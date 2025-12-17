<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 感情タグ
 */
enum EmotionTag: string
{
    case JOY = 'joy';             // 嬉しい 😊
    case SADNESS = 'sadness';     // 悲しい 😢
    case GRATITUDE = 'gratitude'; // 感謝 🙏
    case PRIDE = 'pride';         // 誇り 🏆
    case REGRET = 'regret';       // 後悔 😔
    case LOVE = 'love';           // 愛情 ❤️
    case HABIT = 'habit';         // クセ 🔁
    case NOSTALGIA = 'nostalgia'; // 懐かしい 🕰️

    /**
     * ラベルを取得
     */
    public function label(): string
    {
        return match ($this) {
            self::JOY => '嬉しい',
            self::SADNESS => '悲しい',
            self::GRATITUDE => '感謝',
            self::PRIDE => '誇り',
            self::REGRET => '後悔',
            self::LOVE => '愛情',
            self::HABIT => 'クセ',
            self::NOSTALGIA => '懐かしい',
        };
    }

    /**
     * 絵文字を取得
     */
    public function emoji(): string
    {
        return match ($this) {
            self::JOY => '😊',
            self::SADNESS => '😢',
            self::GRATITUDE => '🙏',
            self::PRIDE => '🏆',
            self::REGRET => '😔',
            self::LOVE => '❤️',
            self::HABIT => '🔁',
            self::NOSTALGIA => '🕰️',
        };
    }

    /**
     * カラークラスを取得
     */
    public function color(): string
    {
        return match ($this) {
            self::JOY => 'yellow',
            self::SADNESS => 'blue',
            self::GRATITUDE => 'green',
            self::PRIDE => 'purple',
            self::REGRET => 'gray',
            self::LOVE => 'pink',
            self::HABIT => 'orange',
            self::NOSTALGIA => 'amber',
        };
    }
}
