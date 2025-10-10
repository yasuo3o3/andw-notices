# ANDW Notices - クイックスタート

## 🚀 最初にやること（開発者向け）

### 1. ビルド実行（必須）
```bash
# プラグインディレクトリに移動
cd andw-notices/

# 依存関係インストール
npm install

# ビルド実行
npm run build
```

### 2. WordPressに設置
```bash
# プラグインフォルダを WordPress に配置
cp -r andw-notices/ /path/to/wordpress/wp-content/plugins/
```

### 3. プラグイン有効化
WordPress管理画面 → プラグイン → ANDW Notices → 有効化

## 📝 基本的な使い方

### 1. お知らせ作成
1. 管理画面「お知らせ」→「新規追加」
2. タイトル・本文・抜粋を入力
3. お知らせ設定で表示日・リンク先を設定
4. 公開

### 2. 一覧表示
1. ページ・投稿の編集画面
2. ブロック追加「お知らせ一覧」を挿入
3. ブロック設定で表示オプションを調整
4. 保存・公開

## ⚠️ 重要な注意点

- **ビルドなしでは動作しません**
- `build/` フォルダが必要
- 配布時は `build/` を含める
- エンドユーザーはビルド不要

## 🔧 開発コマンド

```bash
# 開発モード（ファイル監視）
npm run dev

# 本番ビルド
npm run build

# コード品質チェック
npm run lint:js
npm run lint:css
phpcs
```