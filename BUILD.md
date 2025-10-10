# ANDW Notices - ビルド・インストール手順

## 開発環境の要件

- **Node.js**: 16.x 以上
- **npm**: 8.x 以上
- **PHP**: 7.4 以上
- **WordPress**: 6.2 以上

## 開発環境のセットアップ

### 1. リポジトリのクローン
```bash
git clone <repository-url>
cd andw-notices
```

### 2. 依存関係のインストール
```bash
npm install
```

### 3. ビルドの実行
```bash
# 開発ビルド（ウォッチモード）
npm run dev

# 本番ビルド
npm run build
```

### 4. コード品質チェック
```bash
# JavaScript のリント
npm run lint:js

# CSS のリント
npm run lint:css

# PHP のコーディング規約チェック（PHPCS必要）
phpcs

# すべてのチェックを実行
npm run lint:js && npm run lint:css && phpcs
```

## ビルド成果物

ビルド実行後、以下のファイルが `build/` ディレクトリに生成されます：

```
build/
├── index.js          # ブロックエディター用JavaScript
├── index.asset.php   # 依存関係とバージョン情報
└── style-index.css   # フロントエンド用CSS
```

## 配布用パッケージの作成

### 1. ビルドの実行
```bash
npm run build
```

### 2. 配布ZIPの作成
```bash
git archive --format=zip --output=../andw-notices.zip --prefix=andw-notices/ HEAD
```

または、以下のファイル・ディレクトリを含むZIPファイルを手動作成：

**含めるファイル**
```
andw-notices/
├── andw-notices.php          # メインプラグインファイル
├── uninstall.php             # アンインストール処理
├── readme.txt                # WordPress.org用README
├── includes/                 # PHPクラスファイル
├── build/                    # ビルド済みアセット
├── blocks/                   # ブロック定義
├── languages/                # 翻訳ファイル
└── assets/                   # 画像等の静的ファイル
```

**除外するファイル**（.gitattributes で自動除外）
```
src/                   # ソースファイル
node_modules/          # Node.js依存関係
package.json           # 開発設定
webpack.config.js      # ビルド設定
phpcs.xml              # コーディング規約設定
.gitignore             # Git設定
.gitattributes         # Git属性設定
BUILD.md               # このファイル
USER-GUIDE.md          # ユーザーガイド
CHANGELOG.md           # 変更ログ
debug-tools/           # デバッグツール
docs/                  # ドキュメント
```

## WordPressへのインストール

### 方法1: 管理画面からアップロード
1. WordPress管理画面にログイン
2. 「プラグイン」→「新規追加」→「プラグインのアップロード」
3. 作成したZIPファイルを選択してアップロード
4. プラグインを有効化

### 方法2: FTPでアップロード
1. ZIPファイルを展開
2. `andw-notices/` フォルダを `/wp-content/plugins/` にアップロード
3. WordPress管理画面でプラグインを有効化

### 方法3: WP-CLI（開発者向け）
```bash
wp plugin install andw-notices.zip --activate
```

## 開発時の注意点

### ファイル構成
- **PHPファイル**: `includes/` ディレクトリ内で整理
- **JSファイル**: `src/` ディレクトリで開発→ `build/` にビルド
- **CSSファイル**: ブロック用は `blocks/*/` 内で管理

### バージョン管理
1. `andw-notices.php` の Version ヘッダーを更新
2. `readme.txt` の Stable tag を Version と一致させる
3. `package.json` の version を更新
4. `CHANGELOG.md` に変更内容を記載

### コーディング規約
- **PHP**: WordPress Coding Standards (WPCS) に準拠
- **JavaScript**: WordPress Scripts の ESLint 設定に準拠
- **CSS**: WordPress Scripts の StyleLint 設定に準拠

### テスト環境
プラグインテスト用の推奨環境：
- WordPress 6.2、6.3、6.4（最新版）
- PHP 7.4、8.0、8.1、8.2
- 主要テーマ（Twenty Twenty-Three、Astra等）での動作確認

## トラブルシューティング

### ビルドエラー
```bash
# node_modules を削除して再インストール
rm -rf node_modules package-lock.json
npm install
npm run build
```

### PHPCSエラー
```bash
# 自動修正可能なエラーを修正
phpcbf

# WordPress Coding Standards のインストール（初回のみ）
composer global require "squizlabs/php_codesniffer=*"
composer global require wp-coding-standards/wpcs
phpcs --config-set installed_paths ~/.composer/vendor/wp-coding-standards/wpcs
```

### 翻訳ファイルの更新
```bash
# POTファイルの生成（WP-CLI必要）
wp i18n make-pot . languages/andw-notices.pot

# POファイルからMOファイルの生成
msgfmt languages/andw-notices-ja.po -o languages/andw-notices-ja.mo
```

## リリースチェックリスト

- [ ] すべてのPHPファイルが `php -l` をパス
- [ ] PHPCS でエラーなし
- [ ] JavaScript/CSS リントでエラーなし
- [ ] プラグイン有効化・無効化が正常動作
- [ ] カスタム投稿タイプが正しく登録される
- [ ] ブロックがエディターで利用可能
- [ ] フロントエンドで正しく表示される
- [ ] 設定ページが正常動作
- [ ] アンインストール処理が正常動作
- [ ] バージョン番号の整合性確認
- [ ] readme.txt の内容確認
- [ ] 翻訳ファイルの整合性確認

## サポート

開発に関する質問や問題は、プロジェクトのイシュートラッカーまたは開発者に直接お問い合わせください。