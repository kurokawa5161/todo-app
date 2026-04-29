# フェーズ21A 実装チェックリスト

## 進捗状況
✅ **3 / 4 完了**（年間PDFエクスポート除く）

---

## ✅ タスク一覧

### 1. Chart.js導入・基本グラフ実装 ✅ 完了
- [x] Chart.js CDN追加（layouts/app.blade.php の `<head>` セクション）
- [x] カテゴリ別円グラフ実装
- [x] 優先度別棒グラフ実装
- [x] タグ別棒グラフ実装
- [x] ダークモード対応（グラフカラー固定 #f3f4f6）

**成果物:**
- ✅ `resources/views/dashboard.blade.php` にグラフ表示エリア追加
- ✅ JavaScriptコードでChart.js初期化
- ✅ レスポンシブ対応（モバイル・タブレット）

---

### 2. 週次サマリー強化 ✅ 完了
- [x] `DashboardController::getWeeklyCompletionData()` メソッド追加
- [x] 週次完了数グラフ（過去4週間・折れ線グラフ）
- [x] 生産性スコア算出（完了率 = 完了数÷予定数）

**成果物:**
- ✅ `app/Http/Controllers/DashboardController.php` にメソッド追加
- ✅ ビューに週次完了数推移グラフ追加（height: 16rem）
- ✅ Cache::remember() で1時間キャッシュ

---

### 3. 月次レポート強化 ✅ 完了
- [x] `DashboardController::getMonthlyCompletionData()` メソッド追加
- [x] 月次完了数グラフ（過去6ヶ月・折れ線グラフ）
- [x] カテゴリ別月次分析（既存データ活用）
- [x] タグ別月次分析（既存データ活用）
- [x] now()->startOfMonth()->subMonths($i) で日付バグ修正

**成果物:**
- ✅ 月次グラフ表示
- ✅ Cache::remember() で1時間キャッシュ
- ✅ カテゴリ別・タグ別の集計テーブル（既存機能）

---

### 4. 年間サマリー（新規） ⚠️ 部分完了
- [x] `DashboardController::getYearlyCompletionData()` メソッド追加
- [x] 年間完了数の月別グラフ（過去12ヶ月・折れ線グラフ）
- [x] now()->startOfMonth()->subMonths($i) で日付バグ修正
- [ ] 年間レポートPDFエクスポート機能（未実装）
- [ ] ルート追加: `GET /dashboard/export/pdf/yearly`（未実装）

**成果物:**
- ✅ 年間グラフ表示セクション（height: 20rem）
- ✅ Cache::remember() で1時間キャッシュ
- ❌ `DashboardController::exportYearlyPdf()` メソッド（未実装）
- ❌ `resources/views/reports/yearly.blade.php` テンプレート（未実装）
- ❌ PDFダウンロードボタン（未実装）

---

## 🎨 UI/UX要件

### レスポンシブ対応
- **デスクトップ:** グラフ2列表示（`lg:grid-cols-2`）
- **タブレット:** グラフ2列表示（`md:grid-cols-2`）
- **モバイル:** グラフ1列表示（`grid-cols-1`）

### ダークモード対応
- Chart.js のテキスト・グリッド色を動的切替
- Tailwind の `dark:` クラス活用
- グラフ背景色は透明（カードの背景色を使用）

### アクセシビリティ
- `aria-label` 属性追加（グラフキャンバス）
- キーボードナビゲーション対応（タブインデックス）

---

## 🧪 テスト項目

### 機能テスト
- [ ] グラフが正しくデータを表示
- [ ] ダークモード切替でグラフ色が変わる
- [ ] データがない場合も正常表示（空グラフ）
- [ ] 年間PDFエクスポートが動作

### ブラウザ互換性
- [ ] Chrome（最新版）
- [ ] Edge（最新版）
- [ ] Firefox（最新版）
- [ ] Safari（最新版・Mac/iOS）

### パフォーマンス
- [ ] ページ読み込み速度（3秒以内）
- [ ] グラフ描画速度（1秒以内）
- [ ] 大量データ（1000件以上）でも快適

---

## 📦 依存関係

### 必須ライブラリ
- ✅ Chart.js 4.4.0以上（CDN経由）
- ✅ Tailwind CSS（既存）
- ✅ Laravel 11.x（既存）
- ✅ Barryvdh/Laravel-DomPDF（既存）

### オプション
- Chartjs-plugin-datalabels（グラフにラベル表示）
- Moment.js（日付フォーマット・不要ならLaravel側で処理）

---

## 🚀 デプロイ前チェック

### コミット前
- [ ] コード整形（PSR-12準拠）
- [ ] 未使用のコメント削除
- [ ] `dd()`, `dump()` 削除
- [ ] JavaScript コンソールログ削除

### デプロイ前
- [ ] ローカル環境で動作確認
- [ ] ダークモード動作確認
- [ ] モバイル表示確認（DevTools）
- [ ] PDFエクスポート動作確認

### デプロイ後
- [ ] 本番環境でグラフ表示確認
- [ ] パフォーマンス確認（Chrome DevTools）
- [ ] エラーログ確認（Laravel Log）

---

## 📝 コミットメッセージ例

```bash
# Chart.js導入
git commit -m "feat: Chart.js導入・基本グラフ実装"

# 週次サマリー強化
git commit -m "feat: 週次サマリー強化（生産性グラフ・前週比較）"

# 月次レポート強化
git commit -m "feat: 月次レポート強化（過去6ヶ月グラフ）"

# 年間サマリー
git commit -m "feat: 年間サマリー実装（月別グラフ・PDFエクスポート）"

# フェーズ完了
git commit -m "feat: フェーズ21A完了（高度な統計レポート）"
```

---

## 🔗 関連ファイル

### 編集が必要なファイル
- `app/Http/Controllers/DashboardController.php`
- `resources/views/dashboard.blade.php`
- `resources/views/reports/yearly.blade.php`（新規作成）
- `routes/web.php`（年間PDFルート追加）

### 参考ファイル
- `resources/views/reports/weekly.blade.php`
- `resources/views/reports/monthly.blade.php`
- `PHASE_21A_IMPLEMENTATION.md`（詳細実装ガイド）

---

最終更新: 2026-04-29
