<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * 感情タグモデル
 *
 * 言伝に付与する感情のマスタデータ
 * 8種類の固定タグ（嬉しい、悲しい、感謝、誇り、後悔、愛情、クセ、懐かしい）
 */
class EmotionTag extends Model
{
    /**
     * 複数代入可能な属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'icon',
        'color',
        'display_order',
    ];

    /**
     * このタグが付与されている言伝
     *
     * @return BelongsToMany<Memo, $this>
     */
    public function memos(): BelongsToMany
    {
        return $this->belongsToMany(Memo::class, 'memo_emotion_tag')
            ->withTimestamps();
    }
}
