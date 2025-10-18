<?php
/**
 * Plugin Name: andW 新着お知らせ (andW Notices)
 * Description: 日本語UI対応の新着お知らせ管理プラグイン。カスタム投稿タイプとGutenbergブロックで、お知らせの一覧表示が簡単に行えます。
 * Version: 0.0.7
 * Author: yasuo3o3
 * Author URI: https://yasuo-o.xyz/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: andw-notices
 * Domain Path: /languages
 * Requires at least: 6.2
 * Requires PHP: 7.4
 */

// このファイルに直接アクセスするのを防ぐ
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// プラグインの定数を定義
define( 'ANDW_NOTICES_VERSION', '0.0.7' );
define( 'ANDW_NOTICES_PLUGIN_FILE', __FILE__ );
define( 'ANDW_NOTICES_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ANDW_NOTICES_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ANDW_NOTICES_TEXT_DOMAIN', 'andw-notices' );

/**
 * メインプラグインクラス
 */
class ANDW_Notices {

	/**
	 * プラグインの初期化
	 */
	public static function init() {
		// 翻訳ファイルの読み込み（WP 4.6+ では自動読み込みのため不要）

		// プラグインの有効化・無効化フック
		register_activation_hook( ANDW_NOTICES_PLUGIN_FILE, array( __CLASS__, 'activate' ) );
		register_deactivation_hook( ANDW_NOTICES_PLUGIN_FILE, array( __CLASS__, 'deactivate' ) );

		// 各機能モジュールの読み込み
		self::include_files();

		// 初期化アクション
		add_action( 'init', array( __CLASS__, 'init_features' ) );
	}

	/**
	 * 翻訳ファイルの読み込み（WP 4.6以降では自動読み込みのため空実装）
	 */
	public static function load_textdomain() {
		// WP 4.6以降では自動読み込みされるため処理不要
	}

	/**
	 * 必要なファイルの読み込み
	 */
	private static function include_files() {
		$includes = [
			'includes/class-andw-notices-post-type.php',
			'includes/class-andw-notices-meta-fields.php',
			'includes/class-andw-notices-admin-columns.php',
			'includes/class-andw-notices-blocks.php',
			'includes/class-andw-notices-settings.php',
			'includes/class-andw-notices-cache.php',
		];

		foreach ( $includes as $file ) {
			$file_path = ANDW_NOTICES_PLUGIN_DIR . $file;
			if ( file_exists( $file_path ) ) {
				require_once $file_path;
			}
		}
	}

	/**
	 * 各機能の初期化
	 */
	public static function init_features() {
		// カスタム投稿タイプの登録
		if ( class_exists( 'ANDW_Notices_Post_Type' ) ) {
			ANDW_Notices_Post_Type::init();
		}

		// メタフィールドの初期化
		if ( class_exists( 'ANDW_Notices_Meta_Fields' ) ) {
			ANDW_Notices_Meta_Fields::init();
		}

		// 管理画面カラムのカスタマイズ
		if ( class_exists( 'ANDW_Notices_Admin_Columns' ) ) {
			ANDW_Notices_Admin_Columns::init();
		}

		// ブロックの登録
		if ( class_exists( 'ANDW_Notices_Blocks' ) ) {
			ANDW_Notices_Blocks::init();
		}

		// 設定ページの初期化
		if ( class_exists( 'ANDW_Notices_Settings' ) ) {
			ANDW_Notices_Settings::init();
		}

		// キャッシュ機能の初期化
		if ( class_exists( 'ANDW_Notices_Cache' ) ) {
			ANDW_Notices_Cache::init();
		}
	}

	/**
	 * プラグイン有効化時の処理
	 */
	public static function activate() {
		// カスタム投稿タイプを登録（書き換えルールの更新のため）
		if ( class_exists( 'ANDW_Notices_Post_Type' ) ) {
			ANDW_Notices_Post_Type::register_post_type();
		}

		// 書き換えルールをフラッシュ
		flush_rewrite_rules();

		// プラグインバージョンを保存（autoload無効）
		update_option( 'andw_notices_version', ANDW_NOTICES_VERSION, false );
	}

	/**
	 * プラグイン無効化時の処理
	 */
	public static function deactivate() {
		// 書き換えルールをフラッシュ
		flush_rewrite_rules();
	}
}

// プラグインの初期化
ANDW_Notices::init();