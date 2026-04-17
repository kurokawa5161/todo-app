# Laravel ToDoアプリ 学習計画

Laravel実務スキル習得のための段階的学習プラン

---

## 📊 進捗状況

```
基礎フェーズ    ✅ 完了
フェーズ1      ✅ 完了
フェーズ2      ✅ 完了
フェーズ3      ✅ 完了
フェーズ4      ✅ 完了
フェーズ5      ✅ 完了
フェーズ6      ✅ 完了
フェーズ7      ✅ 完了
フェーズ8      ⬜ 未着手
フェーズ9      ⬜ 未着手
フェーズ10     ⬜ 未着手
```

---

## ✅ 完了したフェーズ

### 基礎フェーズ（初回実装）
**コミット**: `6fdfd1c 初回コミット：Todoアプリ完成`

**実装内容**:
- ✅ Laravel Breeze認証（ログイン・登録・パスワードリセット）
- ✅ Todo CRUD（作成・読取・更新・削除）
- ✅ カテゴリ管理（CRUD）
- ✅ ユーザーごとのTodo管理（リレーション）
- ✅ 日付管理（開始日・終了日）
- ✅ 完了/未完了の切り替え

**学んだ技術**:
- MVC アーキテクチャ
- Eloquent ORM（基本）
- マイグレーション
- Blade テンプレート
- ルーティング
- FormRequest バリデーション
- リレーション（belongsTo, hasMany）

---

### フェーズ1：基本機能強化
**コミット**: `2d1d1b3 feat: フェーズ1の機能追加（優先度/並び替え/カウント/ログアウト）`

**実装内容**:
- ✅ 優先度機能（高・中・低、バッジ表示）
- ✅ 並び替え機能（締切・作成日・優先度・タイトル）
- ✅ カウント表示（全て・未完了・完了済）
- ✅ ログアウト機能
- ✅ 期限切れアラート（視覚的フィードバック）

**学んだ技術**:
- クエリビルダー（orderBy, where）
- 集計クエリ（COUNT, CASE WHEN）
- 条件分岐ロジック
- UI/UX設計

---

### フェーズ2：協働・組織化機能
**コミット**: `dbb61fb feat: フェーズ2の機能追加（サブタスク/コメント/ピン留め/カテゴリ色）`

**実装内容**:
- ✅ サブタスク機能（親子関係、階層表示）
- ✅ コメント機能（1対多リレーション）
- ✅ ピン留め機能（重要タスクの固定）
- ✅ カテゴリ色分け機能（視認性向上）

**学んだ技術**:
- 自己参照リレーション（parent_id）
- hasMany/belongsTo の深い理解
- データモデリング
- UX改善（視認性・操作性）

---

### フェーズ3：ファイル・UI強化
**コミット**: `367acc2 feat: フェーズ3の機能追加（画像アップロード）`

**実装内容**:
- ✅ 画像アップロード機能（新規作成・編集）
- ✅ ファイルバリデーション（形式・サイズ）
- ✅ ストレージ管理（public disk）
- ✅ 画像の自動削除（更新時・削除時）
- ✅ サムネイル表示（80px × 80px）
- ✅ エラーハンドリング

**学んだ技術**:
- ファイルアップロード（store, hasFile）
- Storage Facade
- シンボリックリンク（storage:link）
- ファイル削除処理
- 画像表示（asset ヘルパー）
- バリデーション（image, mimes, max）

---

### フェーズ4：Ajax・非同期処理
**コミット**: `033bc0f feat: フェーズ4-C完了（サブタスク追加のAjax化）`

**実装内容**:
- ✅ 完了/未完了の非同期切り替え（4-A）
- ✅ ピン留めの非同期化（4-B）
- ✅ サブタスク追加の非同期化（4-C）
- ✅ ページリロードなしのUI更新
- ✅ エラーハンドリング

**学んだ技術**:
- Ajax（Fetch API）
- async/await 構文
- JSON レスポンス
- JavaScript DOM操作（querySelector, closest, createElement）
- FormData の扱い
- CSRF トークン処理
- XSS対策（HTMLエスケープ）
- レスポンシブなUI更新

---

### フェーズ5：検索・フィルター・タグ機能
**コミット**: `1f2437a feat: フェーズ5-C完了（保存済み検索条件機能）`

**実装内容**:
- ✅ 複数条件検索（タイトル・内容、カテゴリ、優先度、期間）
- ✅ スコープによるコード整理（search, category, priority, dateRange, completedFilter）
- ✅ タグ機能（多対多リレーション）
  - tags, todo_tag テーブル作成
  - Tag モデルとリレーション設定
  - タグ管理（作成・一覧・削除）
  - Todoへのタグ付与・更新
  - Todo一覧でのタグ表示
- ✅ 保存済み検索条件機能
  - saved_searches テーブル作成
  - SavedSearch モデル作成
  - 検索条件の保存・呼び出し機能
  - ワンクリックで検索条件適用
- ✅ Eager Loading（N+1問題回避）

**学んだ技術**:
- belongsToMany（多対多リレーション）
- 中間テーブル（Pivot Table）の設計と操作
- attach/sync/detach の使い分け
- スコープ（Eloquent）による再利用可能なクエリ
- 動的クエリビルダー
- Eager Loading（with()メソッド）
- 複数選択フォーム（チェックボックス）
- contains()メソッドによる選択状態判定
- JSON カラムの活用（検索条件の保存）

---

### フェーズ6：通知・バッチ処理
**コミット**: 
- `1e692de feat: フェーズ6-A,B完了（期限通知・スケジューラー）`
- `(最新) feat: フェーズ6-C完了（リマインダー設定機能）`

**実装内容**:
- ✅ 期限切れメール通知（パートA）
  - TodoDeadlineNotification クラス作成
  - メールテンプレート作成（Markdown）
  - Queue で非同期送信
  - MAIL_MAILER=log で開発環境テスト
- ✅ 毎日の期限チェック（パートB）
  - SendDeadlineNotifications コマンド作成
  - Task Scheduler 設定（毎朝9時自動実行）
  - ログ記録機能
- ✅ リマインダー設定（パートC）
  - マイグレーション作成（reminder_days_before カラム追加）
  - プロフィール画面に設定フォーム追加
  - 通知タイミングを選択可能（1日前/2日前/3日前/7日前/通知なし）
  - SendDeadlineNotifications コマンド修正（ユーザーごとの設定に対応）
  - 専用コントローラーメソッド作成（updateReminder）

**学んだ技術**:
- Laravel Notification（通知システム）
- Queue（キュー・非同期処理）
- Job（ジョブ）
- Artisan Command（カスタムコマンド作成）
- Task Scheduler（スケジューラー）
- Carbon（日付操作、動的な日付計算）
- whereDate, whereNull（クエリメソッド）
- マイグレーション（カラム追加）
- Mass Assignment Protection（$fillable）
- カスタムコントローラーメソッド
- ユーザーリレーション（$user->todos()）
- Blade コンポーネント（セレクトボックス）

---

### フェーズ7：パフォーマンス最適化
**コミット**: `(最新) feat: フェーズ7完了（パフォーマンス最適化）`

**実装内容**:
- ✅ N+1問題の解決（パートA）
  - Laravel Debugbar で現状確認（クエリ10回、5.55ms）
  - Eager Loading 実装済み（`with(['category', 'children', 'tags'])`）
  - クエリ数・実行時間の可視化
- ✅ キャッシング（パートB）
  - カテゴリ一覧をキャッシュ（1時間）
  - タグ一覧をキャッシュ（1時間）
  - 保存済み検索をキャッシュ（1時間）
  - データ変更時に自動キャッシュ削除（CategoryController, TagController, SavedSearchController）
  - キャッシュドライバーを file に設定
  - クエリ削減：10回 → 7回（30%削減）
- ✅ データベース最適化（パートC）
  - 単一カラムインデックス追加（completed_at, end_date, is_pinned）
  - 複合インデックス追加（[user_id, parent_id], [is_pinned, end_date]）
  - マイグレーション作成（add_index_to_todos_table）
  - 実行時間短縮：3.39ms → 2.94ms（さらに13%高速化）

**学んだ技術**:
- Eager Loading（with()メソッド、N+1問題解決）
- Laravel Debugbar（クエリ数・実行時間の可視化）
- Cache Facade（remember, forget）
- キャッシュドライバー（database, file）
- キャッシュ無効化（データ変更時）
- データベースインデックス（単一・複合）
- マイグレーション（インデックス追加・削除）
- パフォーマンス測定・分析

**パフォーマンス改善成果**:
- クエリ数：10回 → 7回（30%削減）
- 実行時間：5.55ms → 2.94ms（47%高速化）

---

## 🎯 今後の学習フェーズ

### フェーズ8：セキュリティ・認可【実務必須】
**目標**: セキュアなアプリケーション開発

**学べる技術**:
- Policy（ポリシー）
- Gate（ゲート）
- 認可（Authorization）
- ミドルウェア
- セキュリティベストプラクティス

**実装する機能**:

#### 機能⑭-A: Todo編集・削除の権限制御
- [ ] TodoPolicy 作成
- [ ] 自分のTodoのみ編集可能
- [ ] 他人のTodo閲覧時は403エラー
- [ ] Blade で @can ディレクティブ使用

#### 機能⑭-B: ロール管理
- [ ] 管理者・一般ユーザーの区別
- [ ] 管理者は全Todoを閲覧可能
- [ ] ロールごとの機能制限

#### 機能⑭-C: セキュリティ強化
- [ ] XSS対策の確認（{{}} vs {!! !!}）
- [ ] CSRF対策の理解
- [ ] SQL Injection対策（Eloquent）
- [ ] 入力値のサニタイズ

**実装ステップ**:
```
1. php artisan make:policy TodoPolicy
2. authorize() メソッド実装
3. AuthServiceProvider に登録
4. コントローラーで $this->authorize('update', $todo)
5. Blade で @can('update', $todo)
6. マイグレーションで role カラム追加
```

**実務での重要性**: ⭐⭐⭐⭐⭐
セキュリティは最優先事項。必ず理解が必要。

---

### フェーズ9：テスト【実務必須】
**目標**: 自動テストで品質保証

**学べる技術**:
- PHPUnit / Pest
- Feature Test（機能テスト）
- Unit Test（単体テスト）
- データベーステスト
- ファクトリー・シーダー

**実装する機能**:

#### 機能⑮-A: Feature Test
- [ ] Todo作成のテスト
- [ ] Todo更新のテスト
- [ ] Todo削除のテスト
- [ ] 認証が必要なことのテスト
- [ ] 他人のTodo編集を防ぐテスト

#### 機能⑮-B: Unit Test
- [ ] モデルのメソッドテスト
- [ ] リレーションのテスト
- [ ] バリデーションのテスト

#### 機能⑮-C: テストデータ管理
- [ ] Factory でダミーデータ生成
- [ ] Seeder でテストデータ投入
- [ ] テスト用データベース設定

**実装ステップ**:
```
1. php artisan make:test TodoTest
2. tests/Feature/TodoTest.php に記述
3. $this->actingAs($user) で認証
4. $response = $this->post('/todos', [...])
5. $response->assertStatus(200)
6. php artisan test で実行
```

**実務での重要性**: ⭐⭐⭐⭐⭐
CI/CDで必須。チーム開発では必ず求められる。

---

### フェーズ10：API開発【モダン開発必須】
**目標**: RESTful API でフロントエンド分離

**学べる技術**:
- RESTful API 設計
- Laravel Sanctum（トークン認証）
- API Resource（整形）
- Postman / Insomnia（API テスト）
- CORS 設定

**実装する機能**:

#### 機能⑯-A: Todo API
- [ ] GET /api/todos（一覧取得）
- [ ] POST /api/todos（作成）
- [ ] PUT /api/todos/{id}（更新）
- [ ] DELETE /api/todos/{id}（削除）
- [ ] JSON レスポンス統一

#### 機能⑯-B: トークン認証
- [ ] Laravel Sanctum 導入
- [ ] ログイン時にトークン発行
- [ ] API リクエスト時にトークン検証
- [ ] トークンのリフレッシュ

#### 機能⑯-C: API Resource
- [ ] TodoResource 作成
- [ ] レスポンスの整形
- [ ] リレーションデータの含め方
- [ ] ペジネーション対応

**実装ステップ**:
```
1. routes/api.php にルート定義
2. composer require laravel/sanctum
3. php artisan vendor:publish --provider="Laravel\Sanctum\..."
4. php artisan make:resource TodoResource
5. API コントローラー作成
6. Postman でテスト
```

**実務での重要性**: ⭐⭐⭐⭐⭐
モダン開発では必須。React/Vue との連携で使う。

---

## 📝 学習の進め方

### 推奨手順
1. **各フェーズを順番に実装**（フェーズ4 → 5 → ... → 10）
2. **実装前に要件を理解**（何を作るか、なぜ必要か）
3. **実装中にコード理解**（なぜこう書くのか）
4. **実装後にコミット**（きれいなコミットメッセージ）
5. **次のフェーズへ**

### コミットルール
```
feat: フェーズN の機能追加（機能名）

- 実装した内容1
- 実装した内容2
- 実装した内容3

Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>
```

### 困ったときは
- エラーメッセージをよく読む
- Laravel ドキュメントを確認
- Google で検索（英語推奨）
- Claude に相談

---

## 🎓 このプランで習得できるスキル

### Laravel
- MVC アーキテクチャ
- Eloquent ORM（基礎〜応用）
- クエリビルダー
- マイグレーション
- バリデーション
- 認証・認可
- ファイル操作
- キュー・ジョブ
- メール送信
- タスクスケジューリング
- API 開発
- テスト

### 実務スキル
- データベース設計
- セキュリティ対策
- パフォーマンス最適化
- エラーハンドリング
- ユーザー体験設計
- Git の使い方
- チーム開発の基礎

### モダン開発
- Ajax / 非同期処理
- RESTful API
- トークン認証
- フロントエンド分離
- CI/CD の基礎

---

## 📅 学習期間の目安

- **各フェーズ**: 2〜5日
- **全体**: 3〜4週間

※ 個人のペースに合わせて調整してください

---

## 🚀 完走後のステップ

このプランを完走したら：

1. **ポートフォリオに追加**
   - GitHub にコードを公開
   - README を充実させる
   - デプロイして URL を公開

2. **次のプロジェクト**
   - SNS アプリ
   - ECサイト
   - ブログシステム

3. **実務へ**
   - Laravel の仕事に応募
   - フリーランス案件に挑戦
   - 自分のサービスを作る

---

**最終更新**: 2026-04-17
**現在のフェーズ**: フェーズ7 完了 → フェーズ8へ
