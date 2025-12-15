<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * notificationsテーブルを作成（フェーズ2）
     *
     * Laravel標準の通知テーブル
     * 新着言伝の通知、既読管理などに使用
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type')->comment('通知クラス名');
            $table->morphs('notifiable');
            $table->text('data')->comment('通知データ（JSON）');
            $table->timestamp('read_at')->nullable()->comment('既読日時');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
