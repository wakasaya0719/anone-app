# ER図（エンティティ・リレーションシップ図）

**プロジェクト**: 言伝（ことづて）アプリケーション  
**作成日**: 2024年12月16日  
**最終更新**: 2024年12月16日  
**バージョン**: 1.0

---

## 概要

本ドキュメントは、言伝アプリケーションの完全なER図（Entity-Relationship Diagram）を含みます。
全エンティティとそれらの間のリレーションシップを視覚化しています。

---

## 完全版ER図

```mermaid
erDiagram
    %% ========================================
    %% リレーションシップ定義
    %% ========================================
    
    %% R-1: Users → Memos (1対多)
    Users ||--o{ Memos : "投稿する"
    
    %% R-2: Users → Recipients (1対多)
    Users ||--o{ Recipients : "登録する"
    
    %% R-3: Users ↔ Memos (多対多: お気に入り) ※中間テーブル経由で表現
    Users ||--o{ Favorites : "登録する"
    Memos ||--o{ Favorites : "お気に入りされる"
    
    %% R-4: Users → Notifications (Polymorphic 1対多)
    Users ||--o{ Notifications : "受け取る"
    
    %% R-5: Memos → ScheduledDeliveries (1対多)
    Memos ||--o{ ScheduledDeliveries : "配信設定する"
    
    %% R-6: Memos ↔ Recipients (多対多) ※R-5とR-8で表現済み
    %% 注: ScheduledDeliveriesが中間テーブルとして機能
    
    %% R-7: Memos → Images (1対多)
    Memos ||--o{ Images : "添付する"
    
    %% R-8: Recipients → ScheduledDeliveries (1対多)
    Recipients ||--o{ ScheduledDeliveries : "受信する"
    
    %% Memos → EmotionTags (多対1: Enum参照)
    Memos }o--|| EmotionTags : "持つ"
    
    %% Sessions (ログイン管理)
    Users ||--o{ Sessions : "持つ"
    
    %% ========================================
    %% エンティティ定義
    %% ========================================
    
    Users {
        bigint id PK "主キー"
        varchar name "ユーザー名"
        varchar email UK "メールアドレス(一意)"
        timestamp email_verified_at "メール認証日時"
        varchar password "パスワード(bcrypt)"
        text two_factor_secret "2FA秘密鍵"
        text two_factor_recovery_codes "2FAリカバリーコード"
        timestamp two_factor_confirmed_at "2FA有効化日時"
        varchar remember_token "ログイン保持トークン"
        bigint current_team_id "現在のチームID"
        varchar profile_photo_path "プロフィール画像パス"
        timestamp created_at "作成日時"
        timestamp updated_at "更新日時"
    }
    
    Memos {
        bigint id PK "主キー"
        bigint user_id FK "投稿者ユーザーID"
        varchar title "タイトル"
        text content "本文(最大10000文字)"
        varchar emotion_tag "感情タグ(Enum値)"
        date memo_date "投稿日(思い出の日付)"
        varchar sender "送信者(From)"
        varchar recipient "受信者(To)"
        enum status "ステータス(draft/published)"
        timestamp published_at "公開日時"
        timestamp created_at "作成日時"
        timestamp updated_at "更新日時"
        timestamp deleted_at "削除日時(ソフトデリート)"
    }
    
    EmotionTags {
        string JOY "嬉しい 😊 yellow"
        string SADNESS "悲しい 😢 blue"
        string GRATITUDE "感謝 🙏 green"
        string PRIDE "誇り 🏆 purple"
        string REGRET "後悔 😔 gray"
        string LOVE "愛情 ❤️ pink"
        string HABIT "クセ 🔁 orange"
        string NOSTALGIA "懐かしい 🕰️ amber"
    }
    
    Recipients {
        bigint id PK "主キー"
        bigint user_id FK "投稿者ユーザーID"
        varchar name "受信者名"
        varchar email "メールアドレス"
        varchar relationship "続柄(例:息子、娘)"
        timestamp created_at "作成日時"
        timestamp updated_at "更新日時"
    }
    
    ScheduledDeliveries {
        bigint id PK "主キー"
        bigint memo_id FK "対象の言伝ID"
        bigint recipient_id FK "受信者ID(NULL許可)"
        timestamp scheduled_at "配信予定日時"
        timestamp delivered_at "実際の配信日時"
        enum status "配信ステータス(pending/delivered/cancelled)"
        timestamp created_at "作成日時"
        timestamp updated_at "更新日時"
    }
    
    Favorites {
        bigint id PK "主キー(中間テーブル)"
        bigint user_id FK "ユーザーID"
        bigint memo_id FK "お気に入りの言伝ID"
        timestamp created_at "お気に入り追加日時"
        timestamp updated_at "更新日時"
    }
    
    Images {
        bigint id PK "主キー"
        bigint memo_id FK "対象の言伝ID"
        varchar file_path "ファイルパス"
        varchar file_name "ファイル名"
        varchar mime_type "MIMEタイプ"
        int size "ファイルサイズ(バイト)"
        timestamp created_at "作成日時"
        timestamp updated_at "更新日時"
    }
    
    Notifications {
        char id PK "主キー(UUID)"
        varchar type "通知タイプ(クラス名)"
        varchar notifiable_type "通知先のモデルタイプ"
        bigint notifiable_id "通知先のモデルID"
        text data "通知内容(JSON)"
        timestamp read_at "既読日時"
        timestamp created_at "作成日時"
        timestamp updated_at "更新日時"
    }
    
    Sessions {
        varchar id PK "主キー(セッションID)"
        bigint user_id FK "ユーザーID"
        varchar ip_address "IPアドレス"
        text user_agent "ユーザーエージェント"
        longtext payload "セッションデータ"
        int last_activity "最終アクティビティ(UNIXタイムスタンプ)"
    }
```

---

## フェーズ別ER図

### 🎯 フェーズ1: コアMVP（実装最優先）

```mermaid
erDiagram
    %% フェーズ1のリレーションシップ
    Users ||--o{ Memos : "R-1: 投稿する(1対多)"
    Memos }o--|| EmotionTags : "感情タグを持つ"
    
    Users {
        bigint id PK
        varchar name
        varchar email UK
        varchar password
        timestamp created_at
        timestamp updated_at
    }
    
    Memos {
        bigint id PK
        bigint user_id FK "ON DELETE CASCADE"
        varchar title
        text content
        varchar emotion_tag "Enum値"
        date memo_date
        varchar sender
        varchar recipient
        enum status "draft/published"
        timestamp published_at
        timestamp deleted_at "ソフトデリート"
    }
    
    EmotionTags {
        string JOY "嬉しい 😊"
        string SADNESS "悲しい 😢"
        string GRATITUDE "感謝 🙏"
        string PRIDE "誇り 🏆"
        string REGRET "後悔 😔"
        string LOVE "愛情 ❤️"
        string HABIT "クセ 🔁"
        string NOSTALGIA "懐かしい 🕰️"
    }
```

### 🚀 フェーズ2: 機能強化（追加実装）

```mermaid
erDiagram
    %% フェーズ2で追加されるリレーションシップ
    Users ||--o{ Memos : "R-1: 投稿"
    Users ||--o{ Recipients : "R-2: 登録"
    Users ||--o{ Favorites : "R-3: お気に入り登録"
    Users ||--o{ Notifications : "R-4: 通知"
    
    Memos ||--o{ ScheduledDeliveries : "R-5: 配信設定"
    Memos ||--o{ Images : "R-7: 添付画像"
    Memos ||--o{ Favorites : "R-3: お気に入りされる"
    
    Recipients ||--o{ ScheduledDeliveries : "R-8: 受信"
    
    Users {
        bigint id PK
        varchar name
        varchar email UK
    }
    
    Memos {
        bigint id PK
        bigint user_id FK
        varchar title
        text content
        timestamp deleted_at
    }
    
    Recipients {
        bigint id PK
        bigint user_id FK "CASCADE"
        varchar name
        varchar email
        varchar relationship
    }
    
    ScheduledDeliveries {
        bigint id PK
        bigint memo_id FK "CASCADE"
        bigint recipient_id FK "CASCADE(NULL許可)"
        timestamp scheduled_at
        timestamp delivered_at
        enum status "pending/delivered/cancelled"
    }
    
    Favorites {
        bigint id PK
        bigint user_id FK "CASCADE"
        bigint memo_id FK "CASCADE"
        timestamp created_at
    }
    
    Images {
        bigint id PK
        bigint memo_id FK "CASCADE"
        varchar file_path
        varchar file_name
        int size
    }
    
    Notifications {
        char id PK "UUID"
        varchar notifiable_type
        bigint notifiable_id
        text data "JSON"
        timestamp read_at
    }
```

---

## リレーションシップ一覧

| # | 親エンティティ | カーディナリティ | 子エンティティ | リレーション種別 | 削除制約 | 実装フェーズ |
|---|---|---|---|---|---|---|
| **R-1** | Users | 1 → 多 | Memos | 1対多 | CASCADE | フェーズ1 |
| **R-2** | Users | 1 → 多 | Recipients | 1対多 | CASCADE | フェーズ2 |
| **R-3** | Users | 多 ↔ 多 | Memos（お気に入り） | 多対多 | CASCADE | フェーズ2 |
| **R-4** | Users | 1 → 多 | Notifications | Polymorphic | 手動 | フェーズ2 |
| **R-5** | Memos | 1 → 多 | ScheduledDeliveries | 1対多 | CASCADE | フェーズ2 |
| **R-6** | Memos | 多 ↔ 多 | Recipients | 多対多 | CASCADE | フェーズ2 |
| **R-7** | Memos | 1 → 多 | Images | 1対多 | CASCADE | フェーズ2 |
| **R-8** | Recipients | 1 → 多 | ScheduledDeliveries | 1対多 | CASCADE | フェーズ2 |

---

## 削除制約（CASCADE）の関係図

```mermaid
graph TD
    A[Users削除] -->|CASCADE| B[Memos削除]
    A -->|CASCADE| C[Recipients削除]
    A -->|CASCADE| D[Favorites削除]
    A -->|CASCADE| E[Sessions削除]
    A -->|イベント| F[Notifications削除]
    
    B -->|CASCADE| G[ScheduledDeliveries削除]
    B -->|CASCADE| H[Images削除]
    B -->|CASCADE| I[Favorites削除]
    
    C -->|CASCADE| J[ScheduledDeliveries削除]
    
    H -->|モデルイベント| K[物理ファイル削除]
    
    style A fill:#ff6b6b
    style B fill:#ffa500
    style C fill:#ffa500
    style G fill:#4ecdc4
    style H fill:#4ecdc4
    style K fill:#95e1d3
```

---

## 注意事項

### 多対多リレーションシップの表現

本ER図では、多対多リレーションシップを**中間テーブル経由で明示的に表現**しています：

1. **R-3（お気に入り）**: `Users` ↔ `Memos`
   - 中間テーブル: `Favorites`
   - `Users ||--o{ Favorites` + `Memos ||--o{ Favorites`

2. **R-6（配信スケジュール）**: `Memos` ↔ `Recipients`
   - 中間テーブル: `ScheduledDeliveries`
   - `Memos ||--o{ ScheduledDeliveries` + `Recipients ||--o{ ScheduledDeliveries`

この表現方法により、データベース設計との対応が明確になります。

### NULL許可について

- `ScheduledDeliveries.recipient_id`: NULL許可
  - 受信者未指定の配信スケジュールに対応

---

## 📝 変更履歴

| 日付 | バージョン | 変更内容 | 担当者 |
|------|-----------|---------|--------|
| 2024-12-16 | 1.0 | 初版作成 | AI Assistant |

---

## 📌 関連ドキュメント

- [entities.md](./entities.md) - エンティティ定義
- [relationships.md](./relationships.md) - リレーションシップ詳細設計
- [requirements.md](./requirements.md) - プロジェクト要件定義

---

**文書管理情報**:
- ファイル名: er_diagram.md
- 保存場所: プロジェクトルート
- 最終更新者: AI Assistant
- 次回レビュー: フェーズ1実装完了後

