<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\EmotionTag;
use App\Enums\MemoStatus;
use App\Models\Memo;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * 開発・テスト環境用シーダー
 */
class DevelopmentSeeder extends Seeder
{
    /**
     * データベースシーディングを実行
     */
    public function run(): void
    {
        $this->command->info('🌱 開発用データの投入を開始します...');

        // テストユーザー1: 管理者（データ豊富）
        $this->command->info('👤 テストユーザー1（管理者）を作成中...');
        $admin = User::factory()->create([
            'name' => '山田 太郎',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // 管理者の言伝: 各感情タグ×3件（公開済み）
        $this->command->info('📝 管理者の言伝を作成中（各感情タグ×3件）...');
        foreach (EmotionTag::cases() as $tag) {
            Memo::factory(3)->create([
                'user_id' => $admin->id,
                'emotion_tag' => $tag,
                'status' => MemoStatus::PUBLISHED,
                'published_at' => now()->subDays(rand(1, 30)),
            ]);
        }

        // 管理者の下書き
        $this->command->info('📝 管理者の下書きを作成中（5件）...');
        Memo::factory(5)->draft()->create([
            'user_id' => $admin->id,
        ]);

        // テストユーザー2: 一般ユーザー（データ少なめ）
        $this->command->info('👤 テストユーザー2（一般ユーザー）を作成中...');
        $user = User::factory()->create([
            'name' => '佐藤 花子',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // 一般ユーザーの言伝
        $this->command->info('📝 一般ユーザーの言伝を作成中（公開10件、下書き3件）...');
        Memo::factory(10)->published()->create([
            'user_id' => $user->id,
        ]);

        Memo::factory(3)->draft()->create([
            'user_id' => $user->id,
        ]);

        // 一般ユーザー10名
        $this->command->info('👥 一般ユーザー10名を作成中...');
        $generalUsers = User::factory(10)->create([
            'email_verified_at' => now(),
        ]);

        foreach ($generalUsers as $index => $generalUser) {
            $this->command->info("  📝 ユーザー{$generalUser->name}の言伝を作成中...");

            // 言伝作成（10～30件）
            $memoCount = rand(10, 30);
            Memo::factory($memoCount)->create([
                'user_id' => $generalUser->id,
                'status' => MemoStatus::PUBLISHED,
                'published_at' => now()->subDays(rand(1, 90)),
            ]);

            // 下書きも少し作成
            Memo::factory(rand(1, 3))->draft()->create([
                'user_id' => $generalUser->id,
            ]);
        }

        // データ統計を表示
        $this->displayStatistics();
    }

    /**
     * データ統計を表示
     */
    private function displayStatistics(): void
    {
        $this->command->newLine();
        $this->command->info('✅ 開発用データの投入が完了しました！');
        $this->command->newLine();
        $this->command->info('📊 データ統計:');
        $this->command->table(
            ['エンティティ', '件数'],
            [
                ['ユーザー', User::count().'名'],
                ['言伝（全体）', Memo::withTrashed()->count().'件'],
                ['├─ 公開済み', Memo::published()->count().'件'],
                ['├─ 下書き', Memo::draft()->count().'件'],
                ['└─ 削除済み', Memo::onlyTrashed()->count().'件'],
            ]
        );

        $this->command->newLine();
        $this->command->info('🔑 テストユーザー:');
        $this->command->line('  📧 admin@example.com / password （データ豊富）');
        $this->command->line('  📧 user@example.com / password （データ少なめ）');
        $this->command->newLine();
    }
}
