# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.0.1] - 2024-10-10

### Added
- 初回リリース
- カスタム投稿タイプ「notices」の実装
- 日本語ラベル対応
- title、editor、excerpt、thumbnail のサポート
- メタフィールド機能
  - display_date: 表示日設定
  - link_type: リンクタイプ（self/internal/external）
  - target_post_id: 内部リンク対象投稿ID
  - external_url: 外部URL
  - target_blank: 新規タブで開く設定
- 管理画面カスタムカラム
  - 表示日カラム
  - リンク先カラム
  - クイック編集対応
- Gutenbergブロック「andw/notices-list」
  - SSRレンダリング
  - 豊富な表示オプション
  - プレビュー機能
  - レスポンシブ対応
- 管理設定ページ
  - 抜粋文字数設定
  - URLプロトコル制限
  - 日付フォーマット上書き
  - キャッシュ設定
- キャッシュ機能
  - ブロック表示の高速化
  - 自動キャッシュクリア
  - 手動クリア機能
- 完全日本語対応
  - 翻訳ファイル（POT/PO）
  - 日本語UI
- セキュリティ機能
  - nonce検証
  - 権限チェック
  - 入力サニタイズ
  - 出力エスケープ
- アンインストール処理
  - クリーンなデータ削除
  - 設定オプション削除
  - キャッシュクリア
- WordPress Coding Standards 準拠
- Plugin Check 対応

### Security
- すべての入力にサニタイズ処理を実装
- すべての出力にエスケープ処理を実装
- nonce による CSRF 対策
- 権限チェックによるアクセス制御
- URLプロトコル制限機能