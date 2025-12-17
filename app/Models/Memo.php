<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EmotionTag;
use App\Enums\MemoStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 言伝（メモ）モデル
 *
 * 親から子へのメッセージを管理
 * タイトル、本文、感情タグ、送受信者情報などを保存
 */
class Memo extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * 複数代入可能な属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'content',
        'emotion_tag',
        'memo_date',
        'sender',
        'recipient',
        'recipient_age',
        'status',
        'published_at',
    ];

    /**
     * 属性のキャスト
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'emotion_tag' => EmotionTag::class,
            'status' => MemoStatus::class,
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
     * この言伝に添付された画像（フェーズ2）
     *
     * @return HasMany<Image, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(Image::class);
    }

    /**
     * この言伝の配信スケジュール（フェーズ2）
     *
     * @return HasMany<ScheduledDelivery, $this>
     */
    public function scheduledDeliveries(): HasMany
    {
        return $this->hasMany(ScheduledDelivery::class);
    }

    /**
     * この言伝をお気に入り登録したユーザー（フェーズ2）
     *
     * @return BelongsToMany<User, $this>
     */
    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites')
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
        return $query->where('status', MemoStatus::PUBLISHED);
    }

    /**
     * 下書きの言伝のみを取得するスコープ
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Memo>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Memo>
     */
    public function scopeDraft($query)
    {
        return $query->where('status', MemoStatus::DRAFT);
    }

    /**
     * 言伝が公開済みかどうか
     */
    public function isPublished(): bool
    {
        return $this->status === MemoStatus::PUBLISHED;
    }

    /**
     * 言伝が下書きかどうか
     */
    public function isDraft(): bool
    {
        return $this->status === MemoStatus::DRAFT;
    }
}
