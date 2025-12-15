<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * emotion_tagsテーブルを作成
     *
     * 感情タグのマスタデータを管理するテーブル
     * 8種類の固定タグ（嬉しい、悲しい、感謝、誇り、後悔、愛情、クセ、懐かしい）
     */
    public function up(): void
    {
        Schema::create('emotion_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique()->comment('感情タグ名（日本語）');
            $table->string('slug', 50)->unique()->comment('スラッグ（英語識別子）');
            $table->string('icon', 50)->nullable()->comment('アイコン識別子');
            $table->string('color', 20)->nullable()->comment('表示色');
            $table->unsignedInteger('display_order')->comment('表示順序');
            $table->timestamps();

            // インデックス
            $table->index('display_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emotion_tags');
    }
};
