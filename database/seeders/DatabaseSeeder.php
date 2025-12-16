<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * メインシーダー
 */
class DatabaseSeeder extends Seeder
{
    /**
     * データベースシーディングを実行
     */
    public function run(): void
    {
        // 開発環境のみテストデータを投入
        if (app()->environment(['local', 'testing'])) {
            $this->call([
                DevelopmentSeeder::class,
            ]);
        }

        // 本番環境では何もしない（マスタデータはEnumで管理）
    }
}
