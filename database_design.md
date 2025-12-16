# データベース設計書

**プロジェクト**: 言伝（ことづて）アプリケーション  
**作成日**: 2024年12月16日  
**最終更新**: 2024年12月16日  
**バージョン**: 1.0

---

## 📋 目次

1. [概要](#概要)
2. [テーブル一覧](#テーブル一覧)
3. [テーブル定義詳細](#テーブル定義詳細)
   - [フェーズ1: コアMVP](#フェーズ1-コアmvp)
   - [フェーズ2: 機能強化](#フェーズ2-機能強化)
4. [インデックス設計](#インデックス設計)
5. [バリデーションルール](#バリデーションルール)
6. [シーダーデータ](#シーダーデータ)
7. [Enum定義](#enum定義)
8. [変更履歴](#変更履歴)

---

## 概要

本ドキュメントは、言伝アプリケーションのデータベース設計の完全な仕様書です。
各テーブルのカラム定義、インデックス、バリデーションルール、テストデータを含みます。

### 技術スタック

- **データベース**: MySQL 8.0+ / MariaDB 10.5+
- **ORM**: Eloquent ORM（Laravel 12.x）
- **マイグレーション**: Laravel Migration
- **文字コード**: utf8mb4
- **照合順序**: utf8mb4_unicode_ci

---

## テーブル一覧

| # | テーブル名 | 用途 | フェーズ | 備考 |
|---|-----------|------|---------|------|
| 1 | users | ユーザー管理 | 既存 | Laravel Fortify標準 |
| 2 | memos | 言伝投稿 | フェーズ1 | ソフトデリート対応 |
| 3 | recipients | 受信者管理 | フェーズ2 | - |
| 4 | scheduled_deliveries | 配信スケジュール | フェーズ2 | - |
| 5 | favorites | お気に入り | フェーズ2 | 中間テーブル |
| 6 | images | 画像添付 | フェーズ2 | - |
| 7 | notifications | 通知管理 | フェーズ2 | Laravel標準（Polymorphic） |
| 8 | sessions | セッション管理 | 既存 | Laravel標準 |

---

## テーブル定義詳細

### フェーズ1: コアMVP

#### 1. users テーブル

ユーザー情報を管理するテーブル（Laravel Fortifyで自動作成）

##### カラム定義

| カラム名 | データ型 | NULL | デフォルト値 | 制約 | 説明 |
|---------|---------|------|------------|------|------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | PK | 主キー |
| name | VARCHAR(255) | NO | - | - | ユーザー名 |
| email | VARCHAR(255) | NO | - | UNIQUE | メールアドレス（一意） |
| email_verified_at | TIMESTAMP | YES | NULL | - | メール認証日時 |
| password | VARCHAR(255) | NO | - | - | パスワード（bcrypt） |
| two_factor_secret | TEXT | YES | NULL | - | 2FA秘密鍵（暗号化） |
| two_factor_recovery_codes | TEXT | YES | NULL | - | 2FAリカバリーコード（暗号化） |
| two_factor_confirmed_at | TIMESTAMP | YES | NULL | - | 2FA有効化日時 |
| remember_token | VARCHAR(100) | YES | NULL | - | ログイン保持トークン |
| current_team_id | BIGINT UNSIGNED | YES | NULL | - | 現在のチームID（将来拡張用） |
| profile_photo_path | VARCHAR(2048) | YES | NULL | - | プロフィール画像パス |
| created_at | TIMESTAMP | YES | NULL | - | 作成日時 |
| updated_at | TIMESTAMP | YES | NULL | - | 更新日時 |

##### インデックス

| インデックス名 | 種別 | カラム | 目的 |
|--------------|------|--------|------|
| PRIMARY | 主キー | id | レコード一意識別 |
| users_email_unique | UNIQUE | email | ログイン認証、重複防止 |

##### 関連

- **1対多**: Memos（投稿した言伝）
- **1対多**: Recipients（登録した受信者）
- **1対多**: Favorites（お気に入り登録）
- **1対多**: Notifications（受け取った通知）

---

#### 2. memos テーブル

言伝（投稿）を管理するテーブル

##### カラム定義

| カラム名 | データ型 | NULL | デフォルト値 | 制約 | 説明 |
|---------|---------|------|------------|------|------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | PK | 主キー |
| user_id | BIGINT UNSIGNED | NO | - | FK | 投稿者ユーザーID |
| title | VARCHAR(255) | NO | - | - | タイトル（必須） |
| content | TEXT | NO | - | - | 本文（最大10000文字） |
| emotion_tag | VARCHAR(50) | YES | NULL | - | 感情タグ（Enum値） |
| memo_date | DATE | YES | NULL | - | 投稿日（思い出の日付） |
| sender | VARCHAR(100) | YES | NULL | - | 送信者名（From） |
| recipient | VARCHAR(100) | YES | NULL | - | 受信者名（To） |
| status | ENUM('draft', 'published') | NO | 'draft' | - | ステータス |
| published_at | TIMESTAMP | YES | NULL | - | 公開日時 |
| created_at | TIMESTAMP | YES | NULL | - | 作成日時 |
| updated_at | TIMESTAMP | YES | NULL | - | 更新日時 |
| deleted_at | TIMESTAMP | YES | NULL | - | 削除日時（ソフトデリート） |

##### インデックス

| インデックス名 | 種別 | カラム | 目的 |
|--------------|------|--------|------|
| PRIMARY | 主キー | id | レコード一意識別 |
| memos_user_id_foreign | FOREIGN KEY | user_id | リレーションシップ整合性、CASCADE削除 |
| memos_user_id_status_index | 複合INDEX | user_id, status | ユーザーの公開済み/下書き一覧表示 |
| memos_user_id_created_at_index | 複合INDEX | user_id, created_at | ユーザーの投稿一覧（新着順） |
| memos_emotion_tag_index | INDEX | emotion_tag | 感情タグでのフィルタリング |
| memos_memo_date_index | INDEX | memo_date | 日付範囲検索（カレンダー表示） |
| memos_published_at_index | INDEX | published_at | タイムライン表示、新着順ソート |
| memos_deleted_at_index | INDEX | deleted_at | ソフトデリート対応（削除済み除外） |
| memos_title_fulltext | FULLTEXT | title | タイトル全文検索 |
| memos_content_fulltext | FULLTEXT | content | 本文全文検索 |

##### 外部キー制約

| カラム | 参照先 | ON DELETE | ON UPDATE |
|--------|--------|-----------|-----------|
| user_id | users(id) | CASCADE | CASCADE |

##### 関連

- **多対1**: Users（投稿者）
- **1対多**: ScheduledDeliveries（配信スケジュール）
- **1対多**: Images（添付画像）
- **多対多**: Users（お気に入り）via Favorites

---

### フェーズ2: 機能強化

#### 3. recipients テーブル

受信者情報を管理するテーブル

##### カラム定義

| カラム名 | データ型 | NULL | デフォルト値 | 制約 | 説明 |
|---------|---------|------|------------|------|------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | PK | 主キー |
| user_id | BIGINT UNSIGNED | NO | - | FK | 投稿者ユーザーID |
| name | VARCHAR(100) | NO | - | - | 受信者名 |
| email | VARCHAR(255) | NO | - | - | メールアドレス |
| relationship | VARCHAR(50) | YES | NULL | - | 続柄（例：息子、娘、孫） |
| created_at | TIMESTAMP | YES | NULL | - | 作成日時 |
| updated_at | TIMESTAMP | YES | NULL | - | 更新日時 |

##### インデックス

| インデックス名 | 種別 | カラム | 目的 |
|--------------|------|--------|------|
| PRIMARY | 主キー | id | レコード一意識別 |
| recipients_user_id_foreign | FOREIGN KEY | user_id | リレーションシップ整合性 |
| recipients_user_id_email_unique | UNIQUE複合 | user_id, email | 同一ユーザーの重複登録防止 |
| recipients_user_id_index | INDEX | user_id | ユーザーの受信者一覧取得 |
| recipients_email_index | INDEX | email | メールアドレスでの検索 |

##### 外部キー制約

| カラム | 参照先 | ON DELETE | ON UPDATE |
|--------|--------|-----------|-----------|
| user_id | users(id) | CASCADE | CASCADE |

##### 関連

- **多対1**: Users（登録者）
- **1対多**: ScheduledDeliveries（配信スケジュール）

---

#### 4. scheduled_deliveries テーブル

配信スケジュールを管理するテーブル

##### カラム定義

| カラム名 | データ型 | NULL | デフォルト値 | 制約 | 説明 |
|---------|---------|------|------------|------|------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | PK | 主キー |
| memo_id | BIGINT UNSIGNED | NO | - | FK | 対象の言伝ID |
| recipient_id | BIGINT UNSIGNED | YES | NULL | FK | 受信者ID（NULL許可） |
| scheduled_at | TIMESTAMP | NO | - | - | 配信予定日時 |
| delivered_at | TIMESTAMP | YES | NULL | - | 実際の配信日時 |
| status | ENUM('pending', 'delivered', 'cancelled', 'failed') | NO | 'pending' | - | 配信ステータス |
| error_message | TEXT | YES | NULL | - | エラーメッセージ（配信失敗時） |
| created_at | TIMESTAMP | YES | NULL | - | 作成日時 |
| updated_at | TIMESTAMP | YES | NULL | - | 更新日時 |

##### インデックス

| インデックス名 | 種別 | カラム | 目的 |
|--------------|------|--------|------|
| PRIMARY | 主キー | id | レコード一意識別 |
| scheduled_deliveries_memo_id_foreign | FOREIGN KEY | memo_id | リレーションシップ整合性 |
| scheduled_deliveries_recipient_id_foreign | FOREIGN KEY | recipient_id | リレーションシップ整合性 |
| scheduled_deliveries_status_scheduled_at_index | 複合INDEX | status, scheduled_at | 配信待ちレコードの取得（バッチ処理用） |
| scheduled_deliveries_memo_id_index | INDEX | memo_id | 特定メモの配信スケジュール一覧 |
| scheduled_deliveries_recipient_id_index | INDEX | recipient_id | 受信者の配信履歴取得 |
| scheduled_deliveries_delivered_at_index | INDEX | delivered_at | 配信完了日時でのソート |

##### 外部キー制約

| カラム | 参照先 | ON DELETE | ON UPDATE |
|--------|--------|-----------|-----------|
| memo_id | memos(id) | CASCADE | CASCADE |
| recipient_id | recipients(id) | CASCADE | CASCADE |

##### 関連

- **多対1**: Memos（配信する言伝）
- **多対1**: Recipients（配信先受信者）

---

#### 5. favorites テーブル（中間テーブル）

お気に入り登録を管理する中間テーブル

##### カラム定義

| カラム名 | データ型 | NULL | デフォルト値 | 制約 | 説明 |
|---------|---------|------|------------|------|------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | PK | 主キー |
| user_id | BIGINT UNSIGNED | NO | - | FK | ユーザーID |
| memo_id | BIGINT UNSIGNED | NO | - | FK | お気に入りの言伝ID |
| created_at | TIMESTAMP | YES | NULL | - | お気に入り追加日時 |
| updated_at | TIMESTAMP | YES | NULL | - | 更新日時 |

##### インデックス

| インデックス名 | 種別 | カラム | 目的 |
|--------------|------|--------|------|
| PRIMARY | 主キー | id | レコード一意識別 |
| favorites_user_id_foreign | FOREIGN KEY | user_id | リレーションシップ整合性 |
| favorites_memo_id_foreign | FOREIGN KEY | memo_id | リレーションシップ整合性 |
| favorites_user_id_memo_id_unique | UNIQUE複合 | user_id, memo_id | 重複お気に入り防止 |
| favorites_memo_id_index | INDEX | memo_id | メモのお気に入り数カウント |
| favorites_user_id_created_at_index | 複合INDEX | user_id, created_at | ユーザーのお気に入り一覧（追加日順） |

##### 外部キー制約

| カラム | 参照先 | ON DELETE | ON UPDATE |
|--------|--------|-----------|-----------|
| user_id | users(id) | CASCADE | CASCADE |
| memo_id | memos(id) | CASCADE | CASCADE |

##### 関連

- **多対1**: Users
- **多対1**: Memos

---

#### 6. images テーブル

画像添付を管理するテーブル

##### カラム定義

| カラム名 | データ型 | NULL | デフォルト値 | 制約 | 説明 |
|---------|---------|------|------------|------|------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | PK | 主キー |
| memo_id | BIGINT UNSIGNED | NO | - | FK | 対象の言伝ID |
| file_path | VARCHAR(2048) | NO | - | - | ファイルパス（storage相対パス） |
| file_name | VARCHAR(255) | NO | - | - | 元のファイル名 |
| mime_type | VARCHAR(100) | NO | - | - | MIMEタイプ（例：image/jpeg） |
| size | INT UNSIGNED | NO | - | - | ファイルサイズ（バイト） |
| width | INT UNSIGNED | YES | NULL | - | 画像幅（ピクセル） |
| height | INT UNSIGNED | YES | NULL | - | 画像高さ（ピクセル） |
| created_at | TIMESTAMP | YES | NULL | - | 作成日時 |
| updated_at | TIMESTAMP | YES | NULL | - | 更新日時 |

##### インデックス

| インデックス名 | 種別 | カラム | 目的 |
|--------------|------|--------|------|
| PRIMARY | 主キー | id | レコード一意識別 |
| images_memo_id_foreign | FOREIGN KEY | memo_id | リレーションシップ整合性 |
| images_memo_id_index | INDEX | memo_id | メモに添付された画像一覧取得 |
| images_created_at_index | INDEX | created_at | アップロード日時でのソート |

##### 外部キー制約

| カラム | 参照先 | ON DELETE | ON UPDATE |
|--------|--------|-----------|-----------|
| memo_id | memos(id) | CASCADE | CASCADE |

##### 関連

- **多対1**: Memos（添付先の言伝）

##### ストレージ設計

- **保存先**: `storage/app/public/memos/{user_id}/{memo_id}/{filename}`
- **公開URL**: `/storage/memos/{user_id}/{memo_id}/{filename}`
- **シンボリックリンク**: `php artisan storage:link`

---

#### 7. notifications テーブル

通知を管理するテーブル（Laravel標準、Polymorphic対応）

##### カラム定義

| カラム名 | データ型 | NULL | デフォルト値 | 制約 | 説明 |
|---------|---------|------|------------|------|------|
| id | CHAR(36) | NO | UUID | PK | 主キー（UUID） |
| type | VARCHAR(255) | NO | - | - | 通知タイプ（完全修飾クラス名） |
| notifiable_type | VARCHAR(255) | NO | - | - | 通知先のモデルタイプ |
| notifiable_id | BIGINT UNSIGNED | NO | - | - | 通知先のモデルID |
| data | TEXT | NO | - | - | 通知内容（JSON形式） |
| read_at | TIMESTAMP | YES | NULL | - | 既読日時 |
| created_at | TIMESTAMP | YES | NULL | - | 作成日時 |
| updated_at | TIMESTAMP | YES | NULL | - | 更新日時 |

##### インデックス

| インデックス名 | 種別 | カラム | 目的 |
|--------------|------|--------|------|
| PRIMARY | 主キー | id | レコード一意識別（UUID） |
| notifications_notifiable_type_notifiable_id_index | 複合INDEX | notifiable_type, notifiable_id | Polymorphic関連（通知先のレコード取得） |
| notifications_read_at_index | INDEX | read_at | 未読通知の抽出 |
| notifications_created_at_index | INDEX | created_at | 通知一覧の日時順ソート |

##### 関連

- **Polymorphic多対1**: Notifiable（通常はUsers）

---

#### 8. sessions テーブル

セッション情報を管理するテーブル（Laravel標準）

##### カラム定義

| カラム名 | データ型 | NULL | デフォルト値 | 制約 | 説明 |
|---------|---------|------|------------|------|------|
| id | VARCHAR(255) | NO | - | PK | 主キー（セッションID） |
| user_id | BIGINT UNSIGNED | YES | NULL | FK | ユーザーID |
| ip_address | VARCHAR(45) | YES | NULL | - | IPアドレス（IPv6対応） |
| user_agent | TEXT | YES | NULL | - | ユーザーエージェント |
| payload | LONGTEXT | NO | - | - | セッションデータ（シリアライズ） |
| last_activity | INT | NO | - | - | 最終アクティビティ（UNIXタイムスタンプ） |

##### インデックス

| インデックス名 | 種別 | カラム | 目的 |
|--------------|------|--------|------|
| PRIMARY | 主キー | id | セッションID検索 |
| sessions_user_id_index | INDEX | user_id | ユーザーのアクティブセッション取得 |
| sessions_last_activity_index | INDEX | last_activity | セッションガベージコレクション |

##### 関連

- **多対1**: Users（セッション所有者）

---

## インデックス設計

### インデックス一覧（優先度順）

#### 最優先（フェーズ1）

| テーブル | インデックス | 種別 | カラム | 目的 |
|---------|------------|------|--------|------|
| users | PRIMARY | 主キー | id | レコード一意識別 |
| users | users_email_unique | UNIQUE | email | ログイン認証 |
| memos | PRIMARY | 主キー | id | レコード一意識別 |
| memos | memos_user_id_foreign | FOREIGN KEY | user_id | CASCADE削除 |
| memos | memos_user_id_status_index | 複合INDEX | user_id, status | 一覧表示 |
| memos | memos_deleted_at_index | INDEX | deleted_at | ソフトデリート |

#### 高優先（フェーズ2）

| テーブル | インデックス | 種別 | カラム | 目的 |
|---------|------------|------|--------|------|
| favorites | favorites_user_id_memo_id_unique | UNIQUE複合 | user_id, memo_id | 重複防止 |
| scheduled_deliveries | scheduled_deliveries_status_scheduled_at_index | 複合INDEX | status, scheduled_at | バッチ処理 |
| notifications | notifications_notifiable_type_notifiable_id_index | 複合INDEX | notifiable_type, notifiable_id | Polymorphic関連 |
| recipients | recipients_user_id_email_unique | UNIQUE複合 | user_id, email | 重複防止 |

#### 中優先（必要に応じて）

| テーブル | インデックス | 種別 | カラム | 目的 |
|---------|------------|------|--------|------|
| memos | memos_title_fulltext | FULLTEXT | title | 全文検索 |
| memos | memos_content_fulltext | FULLTEXT | content | 全文検索 |
| memos | memos_emotion_tag_index | INDEX | emotion_tag | フィルタリング |
| images | images_memo_id_index | INDEX | memo_id | 画像一覧 |

### インデックス設計の原則

#### 複合インデックスのカラム順序

**原則**: カーディナリティが高い順 → 検索条件で使われる順

```sql
-- ✅ 良い例: user_id, status, published_at
INDEX (user_id, status, published_at)

-- ❌ 悪い例: published_at, user_id
INDEX (published_at, user_id)
```

#### インデックスが効く条件

**効く条件**:
- `WHERE user_id = ?`
- `WHERE user_id IN (?, ?, ?)`
- `WHERE created_at >= ?`
- `ORDER BY created_at DESC`（WHERE句と組み合わせ時）

**効かない条件**:
- `WHERE YEAR(created_at) = 2024`（関数使用）
- `WHERE title LIKE '%keyword%'`（前方一致以外）
- `WHERE user_id != ?`（否定条件）
- `OR`条件（複数カラム）

---

## バリデーションルール

### フェーズ1: Memos

#### StoreMemoRequest（新規作成）

| フィールド | ルール | エラーメッセージ | 理由 |
|-----------|--------|-----------------|------|
| title | required, string, max:255, regex:/\S/ | タイトルは必須です。 | データベース制約に合わせて必須 |
| content | required, string, max:10000, regex:/\S/ | 本文は必須です。 | 仕様書で定義された最大文字数 |
| emotion_tag | nullable, Rule::enum(EmotionTag::class) | 有効な感情タグを選択してください。 | 定義済みの値のみ許可 |
| memo_date | nullable, date, before_or_equal:today | 投稿日は今日以前の日付を指定してください。 | 未来の「思い出」は存在しない |
| sender | nullable, string, max:100 | 送信者名は100文字以内で入力してください。 | データベース制約 |
| recipient | nullable, string, max:100 | 受信者名は100文字以内で入力してください。 | データベース制約 |
| status | required, Rule::enum(MemoStatus::class) | 有効なステータスを選択してください。 | 定義済みのステータスのみ |

#### UpdateMemoRequest（更新）

基本的にStoreMemoRequestと同じだが、以下の追加ルール：

- すべてのフィールドに`sometimes`を追加（送信された場合のみバリデーション）
- 公開済み→下書きへの変更を防ぐカスタムバリデーション

### フェーズ2: Recipients

#### StoreRecipientRequest

| フィールド | ルール | エラーメッセージ | 理由 |
|-----------|--------|-----------------|------|
| name | required, string, max:100, regex:/\S/ | 受信者名は必須です。 | データベース制約 |
| email | required, email:rfc,dns, max:255, unique(recipients):user_id | このメールアドレスは既に登録されています。 | RFC準拠+DNS検証+重複防止 |
| relationship | nullable, string, max:50 | 続柄は50文字以内で入力してください。 | 任意項目 |

### フェーズ2: ScheduledDeliveries

#### StoreScheduledDeliveryRequest

| フィールド | ルール | エラーメッセージ | 理由 |
|-----------|--------|-----------------|------|
| memo_id | required, integer, exists:memos,id,user_id | 指定された言伝が見つかりません。 | 他人の言伝を配信できない |
| recipient_id | nullable, integer, exists:recipients,id,user_id | 指定された受信者が見つかりません。 | 他人の受信者を指定できない |
| scheduled_at | required, date, after:now | 配信予定日時は現在より未来の日時を指定してください。 | 過去の日時には配信できない |

### フェーズ2: Images

#### StoreImageRequest

| フィールド | ルール | エラーメッセージ | 理由 |
|-----------|--------|-----------------|------|
| memo_id | required, integer, exists:memos,id,user_id | 指定された言伝が見つかりません。 | 他人の言伝に添付できない |
| image | required, image, mimes:jpeg,jpg,png,gif,webp, max:10240 | 画像ファイルのサイズは10MB以内にしてください。 | ストレージ容量節約 |
| image | dimensions:max_width=4096,max_height=4096 | 画像のサイズは4096x4096ピクセル以内にしてください。 | 処理負荷軽減 |

カスタムバリデーション：1つの言伝に添付できる画像は最大10枚まで

### フェーズ2: Favorites

#### StoreFavoriteRequest

| フィールド | ルール | エラーメッセージ | 理由 |
|-----------|--------|-----------------|------|
| memo_id | required, integer, exists:memos,id,status,published | 指定された言伝が見つかりません。 | 公開済みのみお気に入り可能 |
| memo_id | unique(favorites):user_id | この言伝は既にお気に入りに登録されています。 | 重複防止 |

カスタムバリデーション：自分の言伝はお気に入り登録できない

---

## シーダーデータ

### データ分類

#### マスタデータ

- **EmotionTag（感情タグ）**: Enumとして実装（DBレコード不要）
  - joy（嬉しい 😊）
  - sadness（悲しい 😢）
  - gratitude（感謝 🙏）
  - pride（誇り 🏆）
  - regret（後悔 😔）
  - love（愛情 ❤️）
  - habit（クセ 🔁）
  - nostalgia（懐かしい 🕰️）

#### テストデータ

開発・テスト環境でのみ投入されるダミーデータ

### テストデータ構成

#### テストユーザー1: 管理者（データ豊富）

- **メールアドレス**: admin@example.com
- **パスワード**: password
- **名前**: 山田 太郎
- **データ量**:
  - 受信者: 5名
  - 公開済み言伝: 24件（各感情タグ×3件）
  - 下書き言伝: 5件
  - 画像付き言伝: 3件（各1～3枚の画像）
  - 配信スケジュール: 10件（配信待ち5件、配信完了5件）

#### テストユーザー2: 一般ユーザー（データ少なめ）

- **メールアドレス**: user@example.com
- **パスワード**: password
- **名前**: 佐藤 花子
- **データ量**:
  - 受信者: 2名
  - 公開済み言伝: 10件
  - 下書き言伝: 3件

#### 一般ユーザー（10名）

- ランダムな名前とメールアドレス
- 各ユーザー:
  - 受信者: 2～5名
  - 言伝: 10～30件
  - 画像: 一部の言伝に1～5枚
  - 配信スケジュール: 5件程度
  - お気に入り: 他人の言伝を5件程度

### 投入データ統計

| エンティティ | 合計 |
|-------------|------|
| Users | 12名 |
| Recipients | 27～57名 |
| Memos | 142～342件 |
| Images | 33～159枚 |
| ScheduledDeliveries | 60件程度 |
| Favorites | 60件程度 |

### 実行コマンド

```bash
# マイグレーション実行
sail artisan migrate:fresh

# シーダー実行
sail artisan db:seed

# または一括実行
sail artisan migrate:fresh --seed

# 特定のシーダーのみ
sail artisan db:seed --class=DevelopmentSeeder
```

---

## Enum定義

### EmotionTag（感情タグ）

```php
enum EmotionTag: string
{
    case JOY = 'joy';           // 嬉しい 😊 (yellow)
    case SADNESS = 'sadness';   // 悲しい 😢 (blue)
    case GRATITUDE = 'gratitude'; // 感謝 🙏 (green)
    case PRIDE = 'pride';       // 誇り 🏆 (purple)
    case REGRET = 'regret';     // 後悔 😔 (gray)
    case LOVE = 'love';         // 愛情 ❤️ (pink)
    case HABIT = 'habit';       // クセ 🔁 (orange)
    case NOSTALGIA = 'nostalgia'; // 懐かしい 🕰️ (amber)
}
```

### MemoStatus（言伝ステータス）

```php
enum MemoStatus: string
{
    case DRAFT = 'draft';           // 下書き
    case PUBLISHED = 'published';   // 公開済み
}
```

### DeliveryStatus（配信ステータス）

```php
enum DeliveryStatus: string
{
    case PENDING = 'pending';       // 配信待ち (blue)
    case DELIVERED = 'delivered';   // 配信完了 (green)
    case CANCELLED = 'cancelled';   // キャンセル (gray)
    case FAILED = 'failed';         // 配信失敗 (red)
}
```

---

## 実装チェックリスト

### フェーズ1（最優先）

- [ ] usersテーブル（既存）
- [ ] memosテーブル
  - [ ] マイグレーション作成
  - [ ] モデル作成（リレーションシップ定義）
  - [ ] ファクトリー作成
  - [ ] バリデーション（StoreMemoRequest, UpdateMemoRequest）
  - [ ] ポリシー作成
- [ ] Enum定義
  - [ ] EmotionTag
  - [ ] MemoStatus
- [ ] シーダー（基本版）
  - [ ] DatabaseSeeder
  - [ ] DevelopmentSeeder

### フェーズ2（追加実装）

- [ ] recipientsテーブル
  - [ ] マイグレーション作成
  - [ ] モデル作成
  - [ ] ファクトリー作成
  - [ ] バリデーション（StoreRecipientRequest, UpdateRecipientRequest）
  - [ ] ポリシー作成
- [ ] scheduled_deliveriesテーブル
  - [ ] マイグレーション作成
  - [ ] モデル作成
  - [ ] ファクトリー作成
  - [ ] バリデーション（StoreScheduledDeliveryRequest）
- [ ] favoritesテーブル
  - [ ] マイグレーション作成
  - [ ] モデル作成
  - [ ] ファクトリー作成
  - [ ] バリデーション（StoreFavoriteRequest）
- [ ] imagesテーブル
  - [ ] マイグレーション作成
  - [ ] モデル作成
  - [ ] ファクトリー作成
  - [ ] バリデーション（StoreImageRequest）
  - [ ] ストレージ設定
- [ ] notificationsテーブル（Laravel標準）
  - [ ] マイグレーション実行
  - [ ] 通知クラス作成
- [ ] DeliveryStatus Enum
- [ ] シーダー（完全版）

---

## 削除制約（CASCADE）の関係図

```
Users削除
  ├─ CASCADE → Memos削除
  │             ├─ CASCADE → ScheduledDeliveries削除
  │             ├─ CASCADE → Images削除（物理ファイルも削除）
  │             └─ CASCADE → Favorites削除
  ├─ CASCADE → Recipients削除
  │             └─ CASCADE → ScheduledDeliveries削除
  ├─ CASCADE → Favorites削除
  ├─ CASCADE → Sessions削除
  └─ イベント → Notifications削除（手動）
```

---

## セキュリティ対策

### 1. Mass Assignment対策

- `$fillable`または`$guarded`プロパティを全モデルに定義
- `$request->validated()`を使用して許可されたフィールドのみ取得

### 2. SQL Injection対策

- Eloquent ORMとバリデーション済みデータを使用
- 生のSQLクエリ使用禁止

### 3. 認可チェック

- 各アクション実行前にPolicyで認可チェック
- `$this->authorize()`メソッド使用

### 4. XSS対策

- Bladeテンプレートの`{{ }}`構文で自動エスケープ
- ユーザー入力データは必ずエスケープ

### 5. CSRF対策

- Laravelの`@csrf`ディレクティブ使用
- フォーム送信時のトークン検証

---

## パフォーマンス最適化

### 1. N+1問題対策

```php
// ❌ 悪い例: N+1問題発生
$memos = Memo::all();
foreach ($memos as $memo) {
    echo $memo->user->name; // 各メモごとにクエリ実行
}

// ✅ 良い例: 先読み込み
$memos = Memo::with('user')->get();
foreach ($memos as $memo) {
    echo $memo->user->name; // クエリ1回のみ
}
```

### 2. ページネーション

```php
// 大量データの場合は必ずページネーション
$memos = Memo::where('user_id', $userId)
    ->orderBy('published_at', 'desc')
    ->paginate(20);
```

### 3. キャッシュ活用

```php
// お気に入り数などの集計値はキャッシュ
$favoritesCount = Cache::remember(
    "memo.{$memoId}.favorites_count",
    3600,
    fn() => Favorite::where('memo_id', $memoId)->count()
);
```

---

## 📝 変更履歴

| 日付 | バージョン | 変更内容 | 担当者 |
|------|-----------|---------|--------|
| 2024-12-16 | 1.0 | 初版作成 | AI Assistant |

---

## 📌 関連ドキュメント

- [er_diagram.md](./er_diagram.md) - ER図
- [entities.md](./entities.md) - エンティティ定義
- [relationships.md](./relationships.md) - リレーションシップ詳細設計
- [requirements.md](./requirements.md) - プロジェクト要件定義
- [features.md](./features.md) - 機能仕様

---

**文書管理情報**:
- ファイル名: database_design.md
- 保存場所: プロジェクトルート
- 最終更新者: AI Assistant
- 次回レビュー: フェーズ1実装完了後

