<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 言伝ステータス
 */
enum MemoStatus: string
{
    case DRAFT = 'draft';           // 下書き
    case PUBLISHED = 'published';   // 公開済み

    /**
     * ラベルを取得
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => '下書き',
            self::PUBLISHED => '公開済み',
        };
    }

    /**
     * カラークラスを取得
     */
    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::PUBLISHED => 'green',
        };
    }
}
