<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 言伝（メモ）モデル
 *
 * 親から子へのメッセージを管理
 * タイトル、本文、感情タグ、送受信者情報などを保存
 */
class Memo extends Model
{
    use SoftDeletes;

    /**
     * 複数代入可能な属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'body',
        'sender',
        'recipient',
        'memo_date',
        'published_at',
    ];

    /**
     * 日付としてキャストする属性
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'memo_date' => 'date',
            'published_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * この言伝の投稿者
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * この言伝に付与された感情タグ
     *
     * @return BelongsToMany<EmotionTag, $this>
     */
    public function emotionTags(): BelongsToMany
    {
        return $this->belongsToMany(EmotionTag::class, 'memo_emotion_tag')
            ->withTimestamps();
    }

    /**
     * この言伝の受信者（フェーズ2）
     *
     * @return BelongsToMany<User, $this>
     */
    public function recipients(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'memo_recipients')
            ->withPivot('is_favorite', 'read_at')
            ->withTimestamps();
    }

    /**
     * 公開済みの言伝のみを取得するスコープ
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Memo>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Memo>
     */
    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at');
    }

    /**
     * 下書きの言伝のみを取得するスコープ
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Memo>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Memo>
     */
    public function scopeDraft($query)
    {
        return $query->whereNull('published_at');
    }

    /**
     * 言伝が公開済みかどうか
     */
    public function isPublished(): bool
    {
        return $this->published_at !== null;
    }

    /**
     * 言伝が下書きかどうか
     */
    public function isDraft(): bool
    {
        return $this->published_at === null;
    }
}
