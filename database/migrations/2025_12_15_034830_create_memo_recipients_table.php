<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * memo_recipients中間テーブルを作成（フェーズ2）
     *
     * 言伝の共有機能を管理
     * 投稿者から受信者へのメッセージ共有、既読管理、お気に入り機能
     */
    public function up(): void
    {
        Schema::create('memo_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('memo_id')
                ->constrained()
                ->cascadeOnDelete()
                ->comment('言伝ID');
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete()
                ->comment('受信者ID');
            $table->boolean('is_favorite')->default(false)->comment('お気に入りフラグ');
            $table->timestamp('read_at')->nullable()->comment('既読日時');
            $table->timestamps();

            // 一意制約（同じ言伝を同じユーザーに重複共有できない）
            $table->unique(['memo_id', 'user_id']);

            // インデックス
            $table->index('user_id');
            $table->index('is_favorite');
            $table->index('read_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('memo_recipients');
    }
};
