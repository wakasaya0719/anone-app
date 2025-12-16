# エンティティ一覧

**プロジェクト**: 言伝（ことづて）アプリケーション  
**作成日**: 2024年12月16日  
**最終更新**: 2024年12月16日

---

## 目次

1. [マスタデータ](#1-マスタデータ)
2. [トランザクションデータ](#2-トランザクションデータ)
3. [エンティティリレーションシップ](#3-エンティティリレーションシップ)
4. [実装優先度](#4-実装優先度)

---

## 1. マスタデータ

マスタデータは、システムの基準となる比較的静的なデータです。

| # | エンティティ名 | 種類 | 主キー | 説明 | 実装フェーズ | 実装状況 |
|---|--------------|------|--------|------|------------|---------|
| M-1 | **Users** | テーブル | `id` (BIGINT) | システムを利用するユーザー（投稿者・受信者） | 実装済み | ✅ 完了 |
| M-2 | **EmotionTags** | Enumクラス | - | 投稿に付与する感情の分類（8種類固定） | フェーズ1 | ⏳ 未実装 |
| M-3 | **Recipients** | テーブル | `id` (BIGINT) | メッセージを受け取る相手の情報 | フェーズ2 | ⏳ 未実装 |

### M-1: Users（ユーザー）

**説明**: システムを利用するユーザーアカウント

**属性**:

| カラム名 | データ型 | NULL | デフォルト | 説明 |
|---------|---------|------|-----------|------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | 主キー |
| name | VARCHAR(255) | NO | - | ユーザー名 |
| email | VARCHAR(255) | NO | - | メールアドレス（一意） |
| email_verified_at | TIMESTAMP | YES | NULL | メール認証日時 |
| password | VARCHAR(255) | NO | - | パスワード（bcrypt） |
| two_factor_secret | TEXT | YES | NULL | 2FA秘密鍵 |
| two_factor_recovery_codes | TEXT | YES | NULL | 2FAリカバリーコード |
| two_factor_confirmed_at | TIMESTAMP | YES | NULL | 2FA有効化日時 |
| remember_token | VARCHAR(100) | YES | NULL | ログイン保持トークン |
| current_team_id | BIGINT UNSIGNED | YES | NULL | 現在のチームID |
| profile_photo_path | VARCHAR(2048) | YES | NULL | プロフィール画像パス |
| created_at | TIMESTAMP | YES | NULL | 作成日時 |
| updated_at | TIMESTAMP | YES | NULL | 更新日時 |

**インデックス**:
- PRIMARY KEY: `id`
- UNIQUE: `email`

**実装状況**: ✅ Laravel Fortifyで自動作成済み

---

### M-2: EmotionTags（感情タグ）

**説明**: 投稿に付与する感情の分類（Enumクラスとして実装）

**実装方法**: PHP Enumクラス（テーブルではない）

**値の定義**:

| 値 | ラベル（日本語） | アイコン | カラー | 説明 |
|----|---------------|---------|--------|------|
| `joy` | 嬉しい | 😊 | yellow | 喜びや楽しさを表現 |
| `sadness` | 悲しい | 😢 | blue | 悲しみや寂しさを表現 |
| `gratitude` | 感謝 | 🙏 | green | 感謝の気持ちを表現 |
| `pride` | 誇り | 🏆 | purple | 誇りや達成感を表現 |
| `regret` | 後悔 | 😔 | gray | 後悔や反省を表現 |
| `love` | 愛情 | ❤️ | pink | 愛情や慈しみを表現 |
| `habit` | クセ | 🔁 | orange | 習慣や特徴を記録 |
| `nostalgia` | 懐かしい | 🕰️ | amber | 懐かしい思い出を表現 |

**Enum実装例**:

```php
enum EmotionTag: string
{
    case JOY = 'joy';
    case SADNESS = 'sadness';
    case GRATITUDE = 'gratitude';
    case PRIDE = 'pride';
    case REGRET = 'regret';
    case LOVE = 'love';
    case HABIT = 'habit';
    case NOSTALGIA = 'nostalgia';
    
    public function label(): string;
    public function color(): string;
    public function icon(): string;
}
```

**実装状況**: ⏳ フェーズ1で実装予定

---

### M-3: Recipients（受信者）

**説明**: メッセージを受け取る相手の情報

**属性**:

| カラム名 | データ型 | NULL | デフォルト | 説明 |
|---------|---------|------|-----------|------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | 主キー |
| user_id | BIGINT UNSIGNED | NO | - | 投稿者のユーザーID（外部キー） |
| name | VARCHAR(100) | NO | - | 受信者名 |
| email | VARCHAR(255) | YES | NULL | メールアドレス |
| relationship | VARCHAR(50) | YES | NULL | 続柄（例: 息子、娘） |
| created_at | TIMESTAMP | YES | NULL | 作成日時 |
| updated_at | TIMESTAMP | YES | NULL | 更新日時 |

**インデックス**:
- PRIMARY KEY: `id`
- INDEX: `user_id`

**外部キー制約**:
- `user_id` → `users.id` (ON DELETE CASCADE)

**実装状況**: ⏳ フェーズ2で実装予定

**備考**: フェーズ1では`memos.recipient`カラムで簡易対応

---

## 2. トランザクションデータ

トランザクションデータは、ユーザーの活動により変化する動的なデータです。

| # | エンティティ名 | 種類 | 主キー | 説明 | 実装フェーズ | 実装状況 |
|---|--------------|------|--------|------|------------|---------|
| T-1 | **Memos** | テーブル | `id` (BIGINT) | ユーザーが投稿する心のメッセージ | フェーズ1 | ⏳ 未実装 |
| T-2 | **ScheduledDeliveries** | テーブル | `id` (BIGINT) | 指定日時に自動公開するメッセージの設定 | フェーズ2 | ⏳ 未実装 |
| T-3 | **Favorites** | テーブル（中間） | `id` (BIGINT) | ユーザーが特定の投稿をお気に入りに追加 | フェーズ2 | ⏳ 未実装 |
| T-4 | **Images** | テーブル | `id` (BIGINT) | 投稿に添付する画像ファイル | フェーズ2 | ⏳ 未実装 |
| T-5 | **Notifications** | テーブル | `id` (UUID) | ユーザーへの通知 | フェーズ2 | ⏳ 未実装 |
| T-6 | **Sessions** | テーブル | `id` (VARCHAR) | ログインセッションの管理 | 実装済み | ✅ 完了 |

---

### T-1: Memos（言伝）

**説明**: ユーザーが投稿する心のメッセージ（アプリのメインエンティティ）

**属性**:

| カラム名 | データ型 | NULL | デフォルト | 説明 |
|---------|---------|------|-----------|------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | 主キー |
| user_id | BIGINT UNSIGNED | NO | - | 投稿者のユーザーID（外部キー） |
| title | VARCHAR(100) | NO | - | タイトル |
| content | TEXT | NO | - | 本文（最大10,000文字） |
| emotion_tag | VARCHAR(20) | NO | - | 感情タグ（Enum値） |
| memo_date | DATE | YES | NULL | 投稿日（思い出の日付） |
| sender | VARCHAR(50) | YES | NULL | 送信者（From） |
| recipient | VARCHAR(50) | YES | NULL | 受信者（To） |
| status | ENUM('draft', 'published') | NO | 'draft' | ステータス |
| published_at | TIMESTAMP | YES | NULL | 公開日時 |
| created_at | TIMESTAMP | YES | NULL | 作成日時 |
| updated_at | TIMESTAMP | YES | NULL | 更新日時 |
| deleted_at | TIMESTAMP | YES | NULL | 削除日時（ソフトデリート） |

**インデックス**:
- PRIMARY KEY: `id`
- INDEX: `user_id`
- INDEX: `emotion_tag`
- INDEX: `memo_date`
- INDEX: `status`
- INDEX: `deleted_at`
- COMPOSITE INDEX: `(user_id, emotion_tag, memo_date)`

**外部キー制約**:
- `user_id` → `users.id` (ON DELETE CASCADE)

**実装状況**: ⏳ フェーズ1で実装予定（最優先）

**備考**: 
- `memo_date`は投稿の対象日（思い出の日付など）、未入力時は現在日
- `sender` / `recipient`はフェーズ1では簡易テキスト入力で対応
- フェーズ2以降は`Recipients`テーブルと連携

---

### T-2: ScheduledDeliveries（配信スケジュール）

**説明**: 指定日時に自動公開するメッセージの設定（Memos ↔ Recipients の中間テーブル兼独立エンティティ）

**属性**:

| カラム名 | データ型 | NULL | デフォルト | 説明 |
|---------|---------|------|-----------|------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | 主キー |
| memo_id | BIGINT UNSIGNED | NO | - | 対象の言伝ID（外部キー） |
| recipient_id | BIGINT UNSIGNED | YES | NULL | 受信者ID（外部キー） |
| scheduled_at | TIMESTAMP | NO | - | 配信予定日時 |
| delivered_at | TIMESTAMP | YES | NULL | 実際の配信日時 |
| status | ENUM('pending', 'delivered', 'cancelled') | NO | 'pending' | 配信ステータス |
| created_at | TIMESTAMP | YES | NULL | 作成日時 |
| updated_at | TIMESTAMP | YES | NULL | 更新日時 |

**インデックス**:
- PRIMARY KEY: `id`
- INDEX: `memo_id`
- INDEX: `recipient_id`
- INDEX: `scheduled_at`
- INDEX: `status`
- UNIQUE: `(memo_id, recipient_id)`

**外部キー制約**:
- `memo_id` → `memos.id` (ON DELETE CASCADE)
- `recipient_id` → `recipients.id` (ON DELETE CASCADE)

**実装状況**: ⏳ フェーズ2で実装予定

---

### T-3: Favorites（お気に入り）

**説明**: ユーザーが特定の投稿をお気に入りに追加（Users ↔ Memos の中間テーブル）

**属性**:

| カラム名 | データ型 | NULL | デフォルト | 説明 |
|---------|---------|------|-----------|------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | 主キー |
| user_id | BIGINT UNSIGNED | NO | - | ユーザーID（外部キー） |
| memo_id | BIGINT UNSIGNED | NO | - | お気に入りの言伝ID（外部キー） |
| created_at | TIMESTAMP | YES | NULL | 作成日時 |
| updated_at | TIMESTAMP | YES | NULL | 更新日時 |

**インデックス**:
- PRIMARY KEY: `id`
- INDEX: `user_id`
- INDEX: `memo_id`
- UNIQUE: `(user_id, memo_id)`

**外部キー制約**:
- `user_id` → `users.id` (ON DELETE CASCADE)
- `memo_id` → `memos.id` (ON DELETE CASCADE)

**実装状況**: ⏳ フェーズ2で実装予定

---

### T-4: Images（画像添付）

**説明**: 投稿に添付する画像ファイル

**属性**:

| カラム名 | データ型 | NULL | デフォルト | 説明 |
|---------|---------|------|-----------|------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | 主キー |
| memo_id | BIGINT UNSIGNED | NO | - | 対象の言伝ID（外部キー） |
| file_path | VARCHAR(255) | NO | - | ファイルパス |
| file_name | VARCHAR(255) | NO | - | ファイル名 |
| mime_type | VARCHAR(100) | NO | - | MIMEタイプ |
| size | INTEGER UNSIGNED | NO | - | ファイルサイズ（バイト） |
| created_at | TIMESTAMP | YES | NULL | 作成日時 |
| updated_at | TIMESTAMP | YES | NULL | 更新日時 |

**インデックス**:
- PRIMARY KEY: `id`
- INDEX: `memo_id`

**外部キー制約**:
- `memo_id` → `memos.id` (ON DELETE CASCADE)

**実装状況**: ⏳ フェーズ2で実装予定

---

### T-5: Notifications（通知）

**説明**: ユーザーへの通知（Laravel標準のNotificationテーブル）

**属性**:

| カラム名 | データ型 | NULL | デフォルト | 説明 |
|---------|---------|------|-----------|------|
| id | CHAR(36) (UUID) | NO | - | 主キー |
| type | VARCHAR(255) | NO | - | 通知タイプ（クラス名） |
| notifiable_type | VARCHAR(255) | NO | - | 通知先のモデルタイプ |
| notifiable_id | BIGINT UNSIGNED | NO | - | 通知先のモデルID |
| data | TEXT | NO | - | 通知内容（JSON） |
| read_at | TIMESTAMP | YES | NULL | 既読日時 |
| created_at | TIMESTAMP | YES | NULL | 作成日時 |
| updated_at | TIMESTAMP | YES | NULL | 更新日時 |

**インデックス**:
- PRIMARY KEY: `id`
- INDEX: `(notifiable_type, notifiable_id)`

**実装状況**: ⏳ フェーズ2で実装予定（Laravel標準機能使用）

---

### T-6: Sessions（セッション）

**説明**: ログインセッションの管理（Laravel標準のSessionテーブル）

**属性**:

| カラム名 | データ型 | NULL | デフォルト | 説明 |
|---------|---------|------|-----------|------|
| id | VARCHAR(255) | NO | - | 主キー（セッションID） |
| user_id | BIGINT UNSIGNED | YES | NULL | ユーザーID（外部キー） |
| ip_address | VARCHAR(45) | YES | NULL | IPアドレス |
| user_agent | TEXT | YES | NULL | ユーザーエージェント |
| payload | LONGTEXT | NO | - | セッションデータ |
| last_activity | INTEGER | NO | - | 最終アクティビティ（UNIXタイムスタンプ） |

**インデックス**:
- PRIMARY KEY: `id`
- INDEX: `user_id`
- INDEX: `last_activity`

**実装状況**: ✅ Laravel標準で実装済み

---

## 3. エンティティリレーションシップ

### 3.1 リレーションシップ一覧

| # | エンティティA | 関係 | エンティティB | 種類 | 外部キー | フェーズ |
|---|-------------|------|-------------|------|---------|---------|
| 1 | Users | 1 → 多 | Memos | 1対多 | `memos.user_id` | フェーズ1 |
| 2 | Memos | 多 → 1 | EmotionTag | 多対1 | `memos.emotion_tag` | フェーズ1 |
| 3 | Users | 1 → 多 | Recipients | 1対多 | `recipients.user_id` | フェーズ2 |
| 4 | Memos | 多 ↔ 多 | Recipients | 多対多 | 中間: `scheduled_deliveries` | フェーズ2 |
| 5 | Memos | 1 → 多 | ScheduledDeliveries | 1対多 | `scheduled_deliveries.memo_id` | フェーズ2 |
| 6 | Recipients | 1 → 多 | ScheduledDeliveries | 1対多 | `scheduled_deliveries.recipient_id` | フェーズ2 |
| 7 | Users | 多 ↔ 多 | Memos（お気に入り） | 多対多 | 中間: `favorites` | フェーズ2 |
| 8 | Memos | 1 → 多 | Images | 1対多 | `images.memo_id` | フェーズ2 |
| 9 | Users | 1 → 多 | Notifications | 1対多 | `notifications.notifiable_id` | フェーズ2 |

### 3.2 ER図（テキスト形式）

```
┌─────────────────┐
│     Users       │
│  (実装済み)      │
├─────────────────┤
│ id (PK)         │
│ name            │
│ email           │
│ password        │
└────────┬────────┘
         │
         │ 1
         │
         │ 多
         ▼
┌─────────────────┐         ┌──────────────────┐
│     Memos       │ 多      │   EmotionTag     │
│  (フェーズ1)     │────────▶│   (Enumクラス)    │
├─────────────────┤  1      ├──────────────────┤
│ id (PK)         │         │ JOY              │
│ user_id (FK)    │         │ SADNESS          │
│ title           │         │ GRATITUDE        │
│ content         │         │ PRIDE            │
│ emotion_tag     │         │ REGRET           │
│ memo_date       │         │ LOVE             │
│ sender          │         │ HABIT            │
│ recipient       │         │ NOSTALGIA        │
│ status          │         └──────────────────┘
│ published_at    │
│ deleted_at      │
└─────────────────┘
```

**フェーズ2以降の拡張**:

```
Users ─── 1:多 ─── Recipients
  │                    │
  │                    │
  └── 多:多 ── Memos ──┘
  │           (中間: scheduled_deliveries)
  │
  └── 多:多 ── Memos
  │           (中間: favorites)
  │
  └── 1:多 ─── Notifications

Memos ─── 1:多 ─── Images
```

---

## 4. 実装優先度

### 🎯 フェーズ1: コアMVP（最優先）

**実装対象エンティティ**:

1. ✅ **Users**（実装済み）
2. ⏳ **EmotionTags**（Enumクラス）
3. ⏳ **Memos**（メインエンティティ）

**実装対象リレーションシップ**:

- Users → Memos（1対多）
- Memos → EmotionTag（多対1、Enum Cast）

**マイグレーション順序**:

1. `EmotionTag` Enumクラス作成
2. `create_memos_table` マイグレーション
3. `Memo`モデル作成（リレーション・Cast設定）

---

### 🚀 フェーズ2: 機能強化

**実装対象エンティティ**:

1. **Recipients**（受信者管理）
2. **ScheduledDeliveries**（配信スケジュール）
3. **Favorites**（お気に入り）
4. **Images**（画像添付）
5. **Notifications**（通知）

---

### 🤖 フェーズ3: AI・高度機能

**実装対象エンティティ**:

- フェーズ3では新規エンティティ追加なし
- 既存エンティティへのカラム追加（AI解析結果など）を検討

---

## 📝 変更履歴

| 日付 | バージョン | 変更内容 | 担当者 |
|------|-----------|---------|--------|
| 2024-12-16 | 1.0 | 初版作成 | AI Assistant |

---

## 📌 次のステップ

1. [ ] EmotionTag Enumクラスの実装
2. [ ] Memosテーブルのマイグレーション作成
3. [ ] Memoモデルの実装（リレーション・バリデーション）
4. [ ] MemoPolicyの作成（認可制御）
5. [ ] MemoFactoryの作成（テストデータ生成）

---

**文書管理情報**:
- ファイル名: entities.md
- 保存場所: プロジェクトルート
- 関連文書: requirements.md
- 最終更新者: AI Assistant

