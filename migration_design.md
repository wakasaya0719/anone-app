# マイグレーション設計書

**プロジェクト**: 言伝（ことづて）アプリケーション  
**作成日**: 2024年12月16日  
**最終更新**: 2024年12月16日  
**バージョン**: 1.0

---

## 📋 目次

1. [概要](#概要)
2. [マイグレーション一覧](#マイグレーション一覧)
3. [マイグレーション詳細](#マイグレーション詳細)
   - [Phase 1: コアMVP](#phase-1-コアmvp)
   - [Phase 2: 機能強化](#phase-2-機能強化)
   - [Phase 3: Laravel標準機能](#phase-3-laravel標準機能)
4. [Enum定義](#enum定義)
5. [シーダー設計](#シーダー設計)
6. [実行コマンド](#実行コマンド)
7. [トラブルシューティング](#トラブルシューティング)
8. [変更履歴](#変更履歴)

---

## 概要

本ドキュメントは、言伝アプリケーションのマイグレーション実装の完全な仕様書です。
各マイグレーションの作成順序、詳細なカラム定義、シーダー設計を含みます。

### 技術要件

- **Laravel**: 12.x
- **PHP**: 8.5+
- **データベース**: MySQL 8.0+ / MariaDB 10.5+
- **文字コード**: utf8mb4
- **照合順序**: utf8mb4_unicode_ci

### 設計原則

1. **依存関係の順守**: 外部キー制約を考慮した順序
2. **段階的実装**: フェーズ分けによる段階的構築
3. **ロールバック対応**: 安全な`down()`メソッド実装
4. **インデックス最適化**: パフォーマンスを考慮したインデックス設計

---

## マイグレーション一覧

### 実行順序一覧

| 順序 | マイグレーション名 | テーブル名 | 目的 | フェーズ | 依存 |
|-----|------------------|----------|------|---------|------|
| 0-1 | `0001_01_01_000000_create_users_table` | users | ユーザー管理 | 既存 | なし |
| 0-2 | `0001_01_01_000001_create_cache_table` | cache, cache_locks | キャッシュ管理 | 既存 | なし |
| 0-3 | `0001_01_01_000002_create_jobs_table` | jobs, job_batches, failed_jobs | ジョブ管理 | 既存 | なし |
| 1 | `2024_12_16_000001_create_memos_table` | memos | 言伝投稿管理 | Phase 1 | users |
| 2 | `2024_12_16_000002_create_recipients_table` | recipients | 受信者管理 | Phase 2 | users |
| 3 | `2024_12_16_000003_create_images_table` | images | 画像添付管理 | Phase 2 | memos |
| 4 | `2024_12_16_000004_create_favorites_table` | favorites | お気に入り管理 | Phase 2 | users, memos |
| 5 | `2024_12_16_000005_create_scheduled_deliveries_table` | scheduled_deliveries | 配信スケジュール管理 | Phase 2 | memos, recipients |
| 6 | `2024_12_16_000006_create_notifications_table` | notifications | 通知管理 | Phase 3 | users (Polymorphic) |

### 依存関係図

```
【既存】users
          │
          ├─→【1】memos ★中核テーブル
          │       │
          │       ├─→【3】images
          │       │
          │       ├─→【4】favorites ←─┐
          │       │                   │
          │       └─→【5】scheduled_deliveries
          │                   │        │
          ├─→【2】recipients ──┘        │
          │                            │
          ├─→【4】favorites ────────────┘
          │
          └─→【6】notifications (Polymorphic)
```

---

## マイグレーション詳細

### Phase 0: Laravel標準（既存）

#### 0-1. users テーブル

**ファイル名**: `0001_01_01_000000_create_users_table.php`  
**目的**: ユーザー認証とプロフィール管理  
**状態**: ✅ Laravel Fortifyで自動作成済み

##### カラム定義

```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->timestamp('email_verified_at')->nullable();
    $table->string('password');
    $table->text('two_factor_secret')->nullable();
    $table->text('two_factor_recovery_codes')->nullable();
    $table->timestamp('two_factor_confirmed_at')->nullable();
    $table->rememberToken();
    $table->foreignId('current_team_id')->nullable();
    $table->string('profile_photo_path', 2048)->nullable();
    $table->timestamps();
});
```

##### インデックス

- `PRIMARY KEY` on `id`
- `UNIQUE KEY` on `email`

---

### Phase 1: コアMVP

#### 1. memos テーブル

**ファイル名**: `2024_12_16_000001_create_memos_table.php`  
**目的**: 言伝（投稿）の管理、ソフトデリート対応  
**依存**: users

##### 作成コマンド

```bash
sail artisan make:migration create_memos_table
```

##### マイグレーション実装

```php
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
```

##### インデックス詳細

| インデックス名 | 種別 | カラム | 目的 |
|--------------|------|--------|------|
| PRIMARY | 主キー | id | レコード一意識別 |
| memos_user_id_foreign | FOREIGN KEY | user_id | CASCADE削除、整合性保証 |
| memos_user_id_status_index | 複合INDEX | user_id, status | ユーザーの公開済み/下書き一覧 |
| memos_user_id_created_at_index | 複合INDEX | user_id, created_at | ユーザーの投稿一覧（新着順） |
| memos_emotion_tag_index | INDEX | emotion_tag | 感情タグフィルタリング |
| memos_memo_date_index | INDEX | memo_date | 日付範囲検索 |
| memos_published_at_index | INDEX | published_at | タイムライン表示 |
| memos_deleted_at_index | INDEX | deleted_at | ソフトデリート対応 |
| memos_title_fulltext | FULLTEXT | title | タイトル全文検索 |
| memos_content_fulltext | FULLTEXT | content | 本文全文検索 |

---

### Phase 2: 機能強化

#### 2. recipients テーブル

**ファイル名**: `2024_12_16_000002_create_recipients_table.php`  
**目的**: 受信者情報の管理  
**依存**: users

##### 作成コマンド

```bash
sail artisan make:migration create_recipients_table
```

##### マイグレーション実装

```php
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
        Schema::create('recipients', function (Blueprint $table) {
            // 主キー
            $table->id();
            
            // 外部キー
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->cascadeOnUpdate()
                ->comment('登録者ユーザーID');
            
            // 受信者情報
            $table->string('name', 100)->comment('受信者名');
            $table->string('email', 255)->comment('メールアドレス');
            $table->string('relationship', 50)->nullable()->comment('続柄');
            
            // タイムスタンプ
            $table->timestamps();
            
            // インデックス
            $table->unique(['user_id', 'email'], 'recipients_user_id_email_unique');
            $table->index('user_id', 'recipients_user_id_index');
            $table->index('email', 'recipients_email_index');
        });
    }

    /**
     * マイグレーションをロールバック
     */
    public function down(): void
    {
        Schema::dropIfExists('recipients');
    }
};
```

##### インデックス詳細

| インデックス名 | 種別 | カラム | 目的 |
|--------------|------|--------|------|
| PRIMARY | 主キー | id | レコード一意識別 |
| recipients_user_id_foreign | FOREIGN KEY | user_id | CASCADE削除、整合性保証 |
| recipients_user_id_email_unique | UNIQUE複合 | user_id, email | 同一ユーザーの重複登録防止 |
| recipients_user_id_index | INDEX | user_id | ユーザーの受信者一覧取得 |
| recipients_email_index | INDEX | email | メールアドレス検索 |

---

#### 3. images テーブル

**ファイル名**: `2024_12_16_000003_create_images_table.php`  
**目的**: 言伝への画像添付管理  
**依存**: memos

##### 作成コマンド

```bash
sail artisan make:migration create_images_table
```

##### マイグレーション実装

```php
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
        Schema::create('images', function (Blueprint $table) {
            // 主キー
            $table->id();
            
            // 外部キー
            $table->foreignId('memo_id')
                ->constrained('memos')
                ->cascadeOnDelete()
                ->cascadeOnUpdate()
                ->comment('対象の言伝ID');
            
            // ファイル情報
            $table->string('file_path', 2048)->comment('ファイルパス（storage相対パス）');
            $table->string('file_name', 255)->comment('元のファイル名');
            $table->string('mime_type', 100)->comment('MIMEタイプ');
            $table->unsignedInteger('size')->comment('ファイルサイズ（バイト）');
            
            // 画像メタデータ
            $table->unsignedInteger('width')->nullable()->comment('画像幅（ピクセル）');
            $table->unsignedInteger('height')->nullable()->comment('画像高さ（ピクセル）');
            
            // タイムスタンプ
            $table->timestamps();
            
            // インデックス
            $table->index('memo_id', 'images_memo_id_index');
            $table->index('created_at', 'images_created_at_index');
        });
    }

    /**
     * マイグレーションをロールバック
     */
    public function down(): void
    {
        Schema::dropIfExists('images');
    }
};
```

##### インデックス詳細

| インデックス名 | 種別 | カラム | 目的 |
|--------------|------|--------|------|
| PRIMARY | 主キー | id | レコード一意識別 |
| images_memo_id_foreign | FOREIGN KEY | memo_id | CASCADE削除、整合性保証 |
| images_memo_id_index | INDEX | memo_id | メモの添付画像一覧取得 |
| images_created_at_index | INDEX | created_at | アップロード日時ソート |

---

#### 4. favorites テーブル

**ファイル名**: `2024_12_16_000004_create_favorites_table.php`  
**目的**: お気に入り登録の管理（中間テーブル）  
**依存**: users, memos

##### 作成コマンド

```bash
sail artisan make:migration create_favorites_table
```

##### マイグレーション実装

```php
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
        Schema::create('favorites', function (Blueprint $table) {
            // 主キー
            $table->id();
            
            // 外部キー
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->cascadeOnUpdate()
                ->comment('ユーザーID');
            
            $table->foreignId('memo_id')
                ->constrained('memos')
                ->cascadeOnDelete()
                ->cascadeOnUpdate()
                ->comment('お気に入りの言伝ID');
            
            // タイムスタンプ
            $table->timestamps();
            
            // インデックス
            $table->unique(['user_id', 'memo_id'], 'favorites_user_id_memo_id_unique');
            $table->index('memo_id', 'favorites_memo_id_index');
            $table->index(['user_id', 'created_at'], 'favorites_user_id_created_at_index');
        });
    }

    /**
     * マイグレーションをロールバック
     */
    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};
```

##### インデックス詳細

| インデックス名 | 種別 | カラム | 目的 |
|--------------|------|--------|------|
| PRIMARY | 主キー | id | レコード一意識別 |
| favorites_user_id_foreign | FOREIGN KEY | user_id | CASCADE削除、整合性保証 |
| favorites_memo_id_foreign | FOREIGN KEY | memo_id | CASCADE削除、整合性保証 |
| favorites_user_id_memo_id_unique | UNIQUE複合 | user_id, memo_id | 重複お気に入り防止 |
| favorites_memo_id_index | INDEX | memo_id | メモのお気に入り数カウント |
| favorites_user_id_created_at_index | 複合INDEX | user_id, created_at | お気に入り一覧（追加日順） |

---

#### 5. scheduled_deliveries テーブル

**ファイル名**: `2024_12_16_000005_create_scheduled_deliveries_table.php`  
**目的**: 配信スケジュールの管理  
**依存**: memos, recipients

##### 作成コマンド

```bash
sail artisan make:migration create_scheduled_deliveries_table
```

##### マイグレーション実装

```php
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
        Schema::create('scheduled_deliveries', function (Blueprint $table) {
            // 主キー
            $table->id();
            
            // 外部キー
            $table->foreignId('memo_id')
                ->constrained('memos')
                ->cascadeOnDelete()
                ->cascadeOnUpdate()
                ->comment('対象の言伝ID');
            
            $table->foreignId('recipient_id')
                ->nullable()
                ->constrained('recipients')
                ->cascadeOnDelete()
                ->cascadeOnUpdate()
                ->comment('受信者ID');
            
            // 配信情報
            $table->timestamp('scheduled_at')->comment('配信予定日時');
            $table->timestamp('delivered_at')->nullable()->comment('実際の配信日時');
            
            // ステータス
            $table->enum('status', ['pending', 'delivered', 'cancelled', 'failed'])
                ->default('pending')
                ->comment('配信ステータス');
            
            $table->text('error_message')->nullable()->comment('エラーメッセージ');
            
            // タイムスタンプ
            $table->timestamps();
            
            // インデックス
            $table->index(['status', 'scheduled_at'], 'scheduled_deliveries_status_scheduled_at_index');
            $table->index('memo_id', 'scheduled_deliveries_memo_id_index');
            $table->index('recipient_id', 'scheduled_deliveries_recipient_id_index');
            $table->index('delivered_at', 'scheduled_deliveries_delivered_at_index');
        });
    }

    /**
     * マイグレーションをロールバック
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_deliveries');
    }
};
```

##### インデックス詳細

| インデックス名 | 種別 | カラム | 目的 |
|--------------|------|--------|------|
| PRIMARY | 主キー | id | レコード一意識別 |
| scheduled_deliveries_memo_id_foreign | FOREIGN KEY | memo_id | CASCADE削除、整合性保証 |
| scheduled_deliveries_recipient_id_foreign | FOREIGN KEY | recipient_id | CASCADE削除、整合性保証 |
| scheduled_deliveries_status_scheduled_at_index | 複合INDEX | status, scheduled_at | バッチ処理用（配信待ち取得） |
| scheduled_deliveries_memo_id_index | INDEX | memo_id | メモの配信スケジュール一覧 |
| scheduled_deliveries_recipient_id_index | INDEX | recipient_id | 受信者の配信履歴取得 |
| scheduled_deliveries_delivered_at_index | INDEX | delivered_at | 配信完了日時ソート |

---

### Phase 3: Laravel標準機能

#### 6. notifications テーブル

**ファイル名**: `2024_12_16_000006_create_notifications_table.php`  
**目的**: 通知管理（Laravel標準、Polymorphic対応）  
**依存**: users (Polymorphic)

##### 作成コマンド

```bash
sail artisan make:notifications-table
# または
sail artisan notifications:table
```

##### マイグレーション実装

```php
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
        Schema::create('notifications', function (Blueprint $table) {
            // 主キー（UUID）
            $table->uuid('id')->primary();
            
            // Polymorphic関連
            $table->string('type');
            $table->morphs('notifiable');
            
            // 通知内容
            $table->text('data');
            
            // 既読管理
            $table->timestamp('read_at')->nullable();
            
            // タイムスタンプ
            $table->timestamps();
            
            // インデックス
            $table->index('read_at', 'notifications_read_at_index');
            $table->index('created_at', 'notifications_created_at_index');
        });
    }

    /**
     * マイグレーションをロールバック
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
```

##### インデックス詳細

| インデックス名 | 種別 | カラム | 目的 |
|--------------|------|--------|------|
| PRIMARY | 主キー | id (UUID) | レコード一意識別 |
| notifications_notifiable_type_notifiable_id_index | 複合INDEX | notifiable_type, notifiable_id | Polymorphic関連（自動生成） |
| notifications_read_at_index | INDEX | read_at | 未読通知抽出 |
| notifications_created_at_index | INDEX | created_at | 通知一覧日時ソート |

---

## Enum定義

### 作成順序

Enumはマイグレーション実行前に作成する必要があります。

```bash
# Phase 1
sail artisan make:enum EmotionTag
sail artisan make:enum MemoStatus

# Phase 2
sail artisan make:enum DeliveryStatus
```

### 1. EmotionTag

**ファイル名**: `app/Enums/EmotionTag.php`

```php
<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 感情タグ
 */
enum EmotionTag: string
{
    case JOY = 'joy';             // 嬉しい 😊
    case SADNESS = 'sadness';     // 悲しい 😢
    case GRATITUDE = 'gratitude'; // 感謝 🙏
    case PRIDE = 'pride';         // 誇り 🏆
    case REGRET = 'regret';       // 後悔 😔
    case LOVE = 'love';           // 愛情 ❤️
    case HABIT = 'habit';         // クセ 🔁
    case NOSTALGIA = 'nostalgia'; // 懐かしい 🕰️

    /**
     * ラベルを取得
     */
    public function label(): string
    {
        return match ($this) {
            self::JOY => '嬉しい',
            self::SADNESS => '悲しい',
            self::GRATITUDE => '感謝',
            self::PRIDE => '誇り',
            self::REGRET => '後悔',
            self::LOVE => '愛情',
            self::HABIT => 'クセ',
            self::NOSTALGIA => '懐かしい',
        };
    }

    /**
     * 絵文字を取得
     */
    public function emoji(): string
    {
        return match ($this) {
            self::JOY => '😊',
            self::SADNESS => '😢',
            self::GRATITUDE => '🙏',
            self::PRIDE => '🏆',
            self::REGRET => '😔',
            self::LOVE => '❤️',
            self::HABIT => '🔁',
            self::NOSTALGIA => '🕰️',
        };
    }

    /**
     * カラークラスを取得
     */
    public function color(): string
    {
        return match ($this) {
            self::JOY => 'yellow',
            self::SADNESS => 'blue',
            self::GRATITUDE => 'green',
            self::PRIDE => 'purple',
            self::REGRET => 'gray',
            self::LOVE => 'pink',
            self::HABIT => 'orange',
            self::NOSTALGIA => 'amber',
        };
    }
}
```

### 2. MemoStatus

**ファイル名**: `app/Enums/MemoStatus.php`

```php
<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 言伝ステータス
 */
enum MemoStatus: string
{
    case DRAFT = 'draft';           // 下書き
    case PUBLISHED = 'published';   // 公開済み

    /**
     * ラベルを取得
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => '下書き',
            self::PUBLISHED => '公開済み',
        };
    }

    /**
     * カラークラスを取得
     */
    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::PUBLISHED => 'green',
        };
    }
}
```

### 3. DeliveryStatus

**ファイル名**: `app/Enums/DeliveryStatus.php`

```php
<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 配信ステータス
 */
enum DeliveryStatus: string
{
    case PENDING = 'pending';       // 配信待ち
    case DELIVERED = 'delivered';   // 配信完了
    case CANCELLED = 'cancelled';   // キャンセル
    case FAILED = 'failed';         // 配信失敗

    /**
     * ラベルを取得
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => '配信待ち',
            self::DELIVERED => '配信完了',
            self::CANCELLED => 'キャンセル',
            self::FAILED => '配信失敗',
        };
    }

    /**
     * カラークラスを取得
     */
    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'blue',
            self::DELIVERED => 'green',
            self::CANCELLED => 'gray',
            self::FAILED => 'red',
        };
    }
}
```

---

## シーダー設計

### シーダー一覧

| 順序 | シーダー名 | 対象テーブル | データ件数 | 目的 | 実行環境 |
|-----|-----------|------------|-----------|------|---------|
| 1 | DatabaseSeeder | - | - | メインシーダー | すべて |
| 2 | DevelopmentSeeder | users, recipients, memos, images, favorites, scheduled_deliveries | 約300件 | 開発・テスト用データ | local, testing |

### 1. DatabaseSeeder

**ファイル名**: `database/seeders/DatabaseSeeder.php`

```php
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
```

### 2. DevelopmentSeeder

**ファイル名**: `database/seeders/DevelopmentSeeder.php`

#### データ構成

##### テストユーザー1: 管理者（データ豊富）

- **Email**: admin@example.com
- **Password**: password
- **Name**: 山田 太郎
- **データ量**:
  - 受信者: 5名
  - 公開済み言伝: 24件（各感情タグ×3件）
  - 下書き言伝: 5件
  - 画像付き言伝: 3件（各1～3枚）
  - 配信スケジュール: 10件
  - お気に入り: 10件

##### テストユーザー2: 一般ユーザー（データ少なめ）

- **Email**: user@example.com
- **Password**: password
- **Name**: 佐藤 花子
- **データ量**:
  - 受信者: 2名
  - 公開済み言伝: 10件
  - 下書き言伝: 3件
  - お気に入り: 5件

##### 一般ユーザー（10名）

- ランダムな名前とメールアドレス
- 各ユーザー:
  - 受信者: 2～5名
  - 言伝: 10～30件
  - 画像: 一部に1～5枚
  - 配信スケジュール: 5件程度
  - お気に入り: 5件程度

#### データ統計

| エンティティ | 合計件数 |
|-------------|---------|
| Users | 12名 |
| Recipients | 約50名 |
| Memos | 約250件 |
| Images | 約100枚 |
| ScheduledDeliveries | 約60件 |
| Favorites | 約70件 |

#### シーダー実装

```php
<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\DeliveryStatus;
use App\Enums\EmotionTag;
use App\Enums\MemoStatus;
use App\Models\Favorite;
use App\Models\Image;
use App\Models\Memo;
use App\Models\Recipient;
use App\Models\ScheduledDelivery;
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
        // テストユーザー1: 管理者（データ豊富）
        $admin = User::factory()->create([
            'name' => '山田 太郎',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // 管理者の受信者
        $adminRecipients = Recipient::factory(5)->create([
            'user_id' => $admin->id,
        ]);

        // 管理者の言伝: 各感情タグ×3件
        foreach (EmotionTag::cases() as $tag) {
            Memo::factory(3)->create([
                'user_id' => $admin->id,
                'emotion_tag' => $tag->value,
                'status' => MemoStatus::PUBLISHED->value,
                'published_at' => now()->subDays(rand(1, 30)),
            ]);
        }

        // 管理者の下書き
        Memo::factory(5)->create([
            'user_id' => $admin->id,
            'status' => MemoStatus::DRAFT->value,
            'published_at' => null,
        ]);

        // 管理者の配信スケジュール
        $adminMemos = $admin->memos()->published()->take(10)->get();
        foreach ($adminMemos as $memo) {
            ScheduledDelivery::factory()->create([
                'memo_id' => $memo->id,
                'recipient_id' => $adminRecipients->random()->id,
                'status' => DeliveryStatus::PENDING->value,
                'scheduled_at' => now()->addDays(rand(1, 30)),
            ]);
        }

        // テストユーザー2: 一般ユーザー（データ少なめ）
        $user = User::factory()->create([
            'name' => '佐藤 花子',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // 一般ユーザーの受信者
        Recipient::factory(2)->create([
            'user_id' => $user->id,
        ]);

        // 一般ユーザーの言伝
        Memo::factory(10)->create([
            'user_id' => $user->id,
            'status' => MemoStatus::PUBLISHED->value,
            'published_at' => now()->subDays(rand(1, 60)),
        ]);

        Memo::factory(3)->create([
            'user_id' => $user->id,
            'status' => MemoStatus::DRAFT->value,
        ]);

        // 一般ユーザー10名
        $generalUsers = User::factory(10)->create([
            'email_verified_at' => now(),
        ]);

        foreach ($generalUsers as $generalUser) {
            // 受信者
            $recipients = Recipient::factory(rand(2, 5))->create([
                'user_id' => $generalUser->id,
            ]);

            // 言伝
            $memoCount = rand(10, 30);
            $memos = Memo::factory($memoCount)->create([
                'user_id' => $generalUser->id,
                'status' => MemoStatus::PUBLISHED->value,
                'published_at' => now()->subDays(rand(1, 90)),
            ]);

            // 画像添付（一部の言伝に）
            $memosWithImages = $memos->random(min(5, $memos->count()));
            foreach ($memosWithImages as $memo) {
                Image::factory(rand(1, 5))->create([
                    'memo_id' => $memo->id,
                ]);
            }

            // 配信スケジュール
            $memosForDelivery = $memos->random(min(5, $memos->count()));
            foreach ($memosForDelivery as $memo) {
                ScheduledDelivery::factory()->create([
                    'memo_id' => $memo->id,
                    'recipient_id' => $recipients->random()->id,
                    'status' => DeliveryStatus::PENDING->value,
                    'scheduled_at' => now()->addDays(rand(1, 60)),
                ]);
            }

            // お気に入り（他人の言伝）
            $otherUsersMemos = Memo::where('user_id', '!=', $generalUser->id)
                ->published()
                ->inRandomOrder()
                ->limit(5)
                ->get();

            foreach ($otherUsersMemos as $otherMemo) {
                Favorite::factory()->create([
                    'user_id' => $generalUser->id,
                    'memo_id' => $otherMemo->id,
                ]);
            }
        }

        // 配信完了データ（過去の配信）
        ScheduledDelivery::factory(20)->create([
            'status' => DeliveryStatus::DELIVERED->value,
            'scheduled_at' => now()->subDays(rand(1, 60)),
            'delivered_at' => now()->subDays(rand(1, 60)),
        ]);

        $this->command->info('開発用データの投入が完了しました！');
        $this->command->info('テストユーザー:');
        $this->command->info('  - admin@example.com / password');
        $this->command->info('  - user@example.com / password');
    }
}
```

---

## 実行コマンド

### 基本コマンド

```bash
# 1. Enum作成
sail artisan make:enum EmotionTag
sail artisan make:enum MemoStatus
sail artisan make:enum DeliveryStatus

# 2. マイグレーション作成（順序通り）
sail artisan make:migration create_memos_table
sail artisan make:migration create_recipients_table
sail artisan make:migration create_images_table
sail artisan make:migration create_favorites_table
sail artisan make:migration create_scheduled_deliveries_table
sail artisan notifications:table

# 3. マイグレーション実行
sail artisan migrate

# 4. シーダー実行
sail artisan db:seed

# または一括実行
sail artisan migrate:fresh --seed
```

### 段階的実行

```bash
# Phase 1のみ実行
sail artisan migrate --path=/database/migrations/2024_12_16_000001_create_memos_table.php

# Phase 2のみ実行
sail artisan migrate --path=/database/migrations/2024_12_16_000002_create_recipients_table.php
sail artisan migrate --path=/database/migrations/2024_12_16_000003_create_images_table.php
sail artisan migrate --path=/database/migrations/2024_12_16_000004_create_favorites_table.php
sail artisan migrate --path=/database/migrations/2024_12_16_000005_create_scheduled_deliveries_table.php
```

### ロールバック

```bash
# 最後のバッチをロールバック
sail artisan migrate:rollback

# すべてロールバック
sail artisan migrate:reset

# すべてロールバック＋再実行
sail artisan migrate:refresh

# すべて削除＋再実行＋シーダー
sail artisan migrate:fresh --seed
```

### 特定のシーダーのみ実行

```bash
sail artisan db:seed --class=DevelopmentSeeder
```

### マイグレーション状態確認

```bash
# 実行済みマイグレーション一覧
sail artisan migrate:status
```

---

## トラブルシューティング

### 1. 外部キー制約エラー

**エラー**: `Cannot add foreign key constraint`

**原因**: 参照先テーブルが存在しない、または参照先カラムの型が一致しない

**解決策**:
```bash
# マイグレーションの実行順序を確認
sail artisan migrate:status

# 依存テーブルを先に作成
sail artisan migrate --path=/database/migrations/2024_12_16_000001_create_memos_table.php
```

### 2. FULLTEXT インデックスエラー

**エラー**: `The used table type doesn't support FULLTEXT indexes`

**原因**: InnoDBエンジンでMySQL 5.6未満を使用している

**解決策**:
```php
// マイグレーションファイルでエンジンを明示
Schema::create('memos', function (Blueprint $table) {
    $table->engine = 'InnoDB';
    // ...
});
```

### 3. シーダーでのN+1問題

**症状**: シーダー実行が非常に遅い

**解決策**:
```php
// Eloquentイベントを無効化
Model::unguard();
Model::preventLazyLoading(false);

// バルクインサート使用
DB::table('memos')->insert($data);
```

### 4. メモリ不足エラー

**エラー**: `Allowed memory size exhausted`

**解決策**:
```bash
# PHPメモリ上限を一時的に増やす
php -d memory_limit=512M artisan db:seed
```

### 5. 重複エラー

**エラー**: `Duplicate entry for key 'users_email_unique'`

**解決策**:
```bash
# データベースをリセット
sail artisan migrate:fresh --seed
```

---

## 実装チェックリスト

### Phase 1: コアMVP

- [ ] **Enum作成**
  - [ ] EmotionTag
  - [ ] MemoStatus
  
- [ ] **マイグレーション作成**
  - [ ] create_memos_table
  
- [ ] **マイグレーション実行**
  - [ ] `sail artisan migrate`
  
- [ ] **動作確認**
  - [ ] テーブルが作成されたか確認
  - [ ] 外部キー制約が正しく設定されているか確認
  - [ ] インデックスが作成されているか確認

### Phase 2: 機能強化

- [ ] **Enum作成**
  - [ ] DeliveryStatus
  
- [ ] **マイグレーション作成**
  - [ ] create_recipients_table
  - [ ] create_images_table
  - [ ] create_favorites_table
  - [ ] create_scheduled_deliveries_table
  
- [ ] **マイグレーション実行**
  - [ ] `sail artisan migrate`
  
- [ ] **動作確認**
  - [ ] すべてのテーブルが作成されたか確認
  - [ ] 外部キー制約のCASCADEが機能するか確認

### Phase 3: Laravel標準機能

- [ ] **マイグレーション作成**
  - [ ] create_notifications_table
  
- [ ] **マイグレーション実行**
  - [ ] `sail artisan notifications:table && sail artisan migrate`

### シーダー

- [ ] **シーダー作成**
  - [ ] DevelopmentSeeder
  
- [ ] **シーダー実行**
  - [ ] `sail artisan db:seed`
  
- [ ] **動作確認**
  - [ ] テストユーザーでログインできるか確認
  - [ ] データが正しく投入されているか確認

---

## 📝 変更履歴

| 日付 | バージョン | 変更内容 | 担当者 |
|------|-----------|---------|--------|
| 2024-12-16 | 1.0 | 初版作成 | AI Assistant |

---

## 📌 関連ドキュメント

- [database_design.md](./database_design.md) - データベース設計書
- [er_diagram.md](./er_diagram.md) - ER図
- [entities.md](./entities.md) - エンティティ定義
- [relationships.md](./relationships.md) - リレーションシップ詳細設計
- [requirements.md](./requirements.md) - プロジェクト要件定義

---

**文書管理情報**:
- ファイル名: migration_design.md
- 保存場所: プロジェクトルート
- 最終更新者: AI Assistant
- 次回レビュー: Phase 1実装完了後

