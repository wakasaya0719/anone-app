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
 *
 * テストユーザーと言伝のサンプルデータを投入
 */
class DevelopmentSeeder extends Seeder
{
    /**
     * データベースシーディングを実行
     */
    public function run(): void
    {
        $this->command->info('開発用データの投入を開始します...');

        // テストユーザー1: 管理者（データ豊富）
        $this->command->info('テストユーザー1（管理者）を作成中...');
        $admin = $this->createAdminUser();
        $this->createAdminMemos($admin);

        // テストユーザー2: 一般ユーザー（データ少なめ）
        $this->command->info('テストユーザー2（一般ユーザー）を作成中...');
        $user = $this->createRegularUser();
        $this->createUserMemos($user);

        // 一般ユーザー（10名）
        $this->command->info('一般ユーザー（10名）を作成中...');
        $this->createGeneralUsers();

        $this->command->newLine();
        $this->command->info('✅ 開発用データの投入が完了しました！');
        $this->command->newLine();
        $this->command->info('📧 テストユーザー:');
        $this->command->info('  • 管理者: admin@example.com / Admin@2025!');
        $this->command->info('  • 一般ユーザー: user@example.com / User@2025!');
        $this->command->info('  • その他ユーザー: user1-10@example.com / Test@2025!');
        $this->command->newLine();
        $this->command->info('📊 投入データ統計:');
        $this->command->info('  • ユーザー: ' . User::count() . '名');
        $this->command->info('  • 言伝: ' . Memo::count() . '件');
        $this->command->info('  • 公開済み: ' . Memo::published()->count() . '件');
        $this->command->info('  • 下書き: ' . Memo::draft()->count() . '件');
    }

    /**
     * 管理者ユーザーを作成
     */
    private function createAdminUser(): User
    {
        return User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => '山田 太郎',
                'password' => Hash::make('Admin@2025!'),
                'email_verified_at' => now(),
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
            ]
        );
    }

    /**
     * 管理者の言伝を作成
     */
    private function createAdminMemos(User $admin): void
    {
        // 既にメモが存在する場合はスキップ
        if ($admin->memos()->count() > 0) {
            $this->command->warn('  管理者のメモは既に存在するためスキップします');
            return;
        }

        // 各感情タグ×3件の公開済み言伝（合計24件）
        foreach (EmotionTag::cases() as $tag) {
            Memo::factory(3)
                ->published()
                ->withEmotionTag($tag)
                ->create([
                    'user_id' => $admin->id,
                    'published_at' => now()->subDays(rand(1, 30)),
                    'sender' => $admin->name,
                    'recipient' => $this->getRandomRecipientName(),
                ]);
        }

        // 下書き（5件）
        Memo::factory(5)
            ->draft()
            ->create([
                'user_id' => $admin->id,
                'sender' => $admin->name,
                'recipient' => $this->getRandomRecipientName(),
            ]);
    }

    /**
     * 一般ユーザーを作成
     */
    private function createRegularUser(): User
    {
        return User::firstOrCreate(
            ['email' => 'user@example.com'],
            [
                'name' => '佐藤 花子',
                'password' => Hash::make('User@2025!'),
                'email_verified_at' => now(),
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
            ]
        );
    }

    /**
     * 一般ユーザーの言伝を作成
     */
    private function createUserMemos(User $user): void
    {
        // 既にメモが存在する場合はスキップ
        if ($user->memos()->count() > 0) {
            $this->command->warn('  一般ユーザーのメモは既に存在するためスキップします');
            return;
        }

        // 公開済み言伝（10件）
        Memo::factory(10)
            ->published()
            ->create([
                'user_id' => $user->id,
                'published_at' => now()->subDays(rand(1, 60)),
                'sender' => $user->name,
                'recipient' => $this->getRandomRecipientName(),
            ]);

        // 下書き（3件）
        Memo::factory(3)
            ->draft()
            ->create([
                'user_id' => $user->id,
                'sender' => $user->name,
                'recipient' => $this->getRandomRecipientName(),
            ]);
    }

    /**
     * 一般ユーザー（10名）を作成
     */
    private function createGeneralUsers(): void
    {
        $japaneseNames = [
            '田中 一郎',
            '鈴木 二郎',
            '高橋 三郎',
            '伊藤 四郎',
            '渡辺 五郎',
            '中村 美咲',
            '小林 愛子',
            '加藤 さくら',
            '吉田 桜',
            '山本 花',
        ];

        foreach ($japaneseNames as $index => $name) {
            $email = 'user' . ($index + 1) . '@example.com';

            $generalUser = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make('Test@2025!'),
                    'email_verified_at' => now(),
                    'two_factor_secret' => null,
                    'two_factor_recovery_codes' => null,
                    'two_factor_confirmed_at' => null,
                ]
            );

            // 既にメモが存在する場合はスキップ
            if ($generalUser->memos()->count() > 0) {
                continue;
            }

            // 各ユーザーに10〜30件の言伝を作成
            $memoCount = rand(10, 30);
            $publishedCount = (int) ($memoCount * 0.8); // 80%は公開済み
            $draftCount = $memoCount - $publishedCount;

            // 公開済み言伝
            Memo::factory($publishedCount)
                ->published()
                ->create([
                    'user_id' => $generalUser->id,
                    'published_at' => now()->subDays(rand(1, 90)),
                    'sender' => $generalUser->name,
                    'recipient' => $this->getRandomRecipientName(),
                ]);

            // 下書き言伝
            if ($draftCount > 0) {
                Memo::factory($draftCount)
                    ->draft()
                    ->create([
                        'user_id' => $generalUser->id,
                        'sender' => $generalUser->name,
                        'recipient' => $this->getRandomRecipientName(),
                    ]);
            }
        }
    }

    /**
     * ランダムな受信者名を生成
     *
     * @return string
     */
    private function getRandomRecipientName(): string
    {
        $recipients = [
            '太郎',
            '花子',
            '健一',
            'さくら',
            '翔太',
            '美咲',
            '大輝',
            '結衣',
            '蓮',
            '陽菜',
            '悠人',
            '葵',
            '颯太',
            '莉子',
            '隼人',
        ];

        return fake()->randomElement($recipients);
    }
}
