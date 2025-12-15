<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * memo_emotion_tag中間テーブルを作成
     *
     * memosとemotion_tagsの多対多リレーションを管理
     * 1つの言伝に複数の感情タグを付与可能
     */
    public function up(): void
    {
        Schema::create('memo_emotion_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('memo_id')
                ->constrained()
                ->cascadeOnDelete()
                ->comment('言伝ID');
            $table->foreignId('emotion_tag_id')
                ->constrained()
                ->restrictOnDelete()
                ->comment('感情タグID');
            $table->timestamps();

            // 一意制約（同じ言伝に同じ感情タグを重複付与できない）
            $table->unique(['memo_id', 'emotion_tag_id']);

            // インデックス
            $table->index('emotion_tag_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('memo_emotion_tag');
    }
};
