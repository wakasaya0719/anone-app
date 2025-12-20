<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FamilyMember extends Model
{
    /** @use HasFactory<\Database\Factories\FamilyMemberFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'role',
        'birth_date',
        'is_default_sender',
        'display_order',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'is_default_sender' => 'boolean',
        ];
    }

    /**
     * 所有ユーザー
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 年齢を取得
     */
    public function age(): ?int
    {
        if (! $this->birth_date) {
            return null;
        }

        return (int) $this->birth_date->diffInYears(now());
    }
}
