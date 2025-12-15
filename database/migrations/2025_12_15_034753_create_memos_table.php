<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * memosテーブルを作成
     *
     * 言伝（メモ）のデータを管理するメインテーブル
     * タイトル、本文、送受信者情報、公開状態などを保存
     */
    public function up(): void
    {
        Schema::create('memos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete()
                ->comment('投稿者ID');
            $table->string('title')->comment('タイトル');
            $table->text('body')->comment('本文');
            $table->string('sender', 50)->nullable()->comment('送信者名');
            $table->string('recipient', 50)->nullable()->comment('受信者名');
            $table->date('memo_date')->nullable()->comment('記録日（任意設定可能）');
            $table->timestamp('published_at')->nullable()->comment('公開日時（NULLなら下書き）');
            $table->timestamps();
            $table->softDeletes();

            // インデックス
            $table->index('user_id');
            $table->index('published_at');
            $table->index('memo_date');
            $table->index('deleted_at');
            $table->index(['user_id', 'published_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('memos');
    }
};
