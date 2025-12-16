<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * マイグレーションを実行
     */
    public function up(): void
    {
        Schema::create('memos', function (Blueprint $table) {
            // 主キー
            $table->id();

            // 外部キー
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->cascadeOnUpdate()
                ->comment('投稿者ユーザーID');

            // 基本情報
            $table->string('title', 255)->comment('タイトル');
            $table->text('content')->comment('本文（最大10000文字）');

            // メタデータ
            $table->string('emotion_tag', 50)->nullable()->comment('感情タグ');
            $table->date('memo_date')->nullable()->comment('投稿日（思い出の日付）');

            // 送受信者情報
            $table->string('sender', 100)->nullable()->comment('送信者名');
            $table->string('recipient', 100)->nullable()->comment('受信者名');

            // ステータス
            $table->enum('status', ['draft', 'published'])->default('draft')->comment('ステータス');
            $table->timestamp('published_at')->nullable()->comment('公開日時');

            // タイムスタンプ
            $table->timestamps();
            $table->softDeletes()->comment('削除日時（ソフトデリート）');

            // インデックス
            $table->index(['user_id', 'status'], 'memos_user_id_status_index');
            $table->index(['user_id', 'created_at'], 'memos_user_id_created_at_index');
            $table->index('emotion_tag', 'memos_emotion_tag_index');
            $table->index('memo_date', 'memos_memo_date_index');
            $table->index('published_at', 'memos_published_at_index');
            $table->index('deleted_at', 'memos_deleted_at_index');

            // 全文検索インデックス（MySQL 5.7+）
            $table->fullText('title', 'memos_title_fulltext');
            $table->fullText('content', 'memos_content_fulltext');
        });
    }

    /**
     * マイグレーションをロールバック
     */
    public function down(): void
    {
        Schema::dropIfExists('memos');
    }
};
