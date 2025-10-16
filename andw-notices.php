<?php
/**
 * Plugin Name: ANDW 新着お知らせ (ANDW Notices)
 * Description: 日本語UI対応の新着お知らせ管理プラグイン。カスタム投稿タイプとGutenbergブロックで、お知らせの一覧表示が簡単に行えます。
 * Version: 0.0.1
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
define( 'ANDW_NOTICES_VERSION', '0.0.1' );
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
		// 翻訳ファイルの読み込み（WP 4.6+ では自動読み込み）
		add_action( 'init', array( __CLASS__, 'load_textdomain' ) );

		// プラグインの有効化・無効化フック
		register_activation_hook( ANDW_NOTICES_PLUGIN_FILE, array( __CLASS__, 'activate' ) );
		register_deactivation_hook( ANDW_NOTICES_PLUGIN_FILE, array( __CLASS__, 'deactivate' ) );

		// 各機能モジュールの読み込み
		self::include_files();

		// 初期化アクション
		add_action( 'init', array( __CLASS__, 'init_features' ) );
	}

	/**
	 * 翻訳ファイルの読み込み
	 */
	public static function load_textdomain() {
		load_plugin_textdomain(
			ANDW_NOTICES_TEXT_DOMAIN,
			false,
			dirname( plugin_basename( ANDW_NOTICES_PLUGIN_FILE ) ) . '/languages'
		);
	}

	/**
	 * 必要なファイルの読み込み
	 */
	private static function include_files() {
		// 【CRITICAL】ファイル読み込み開始デバッグ
		error_log( '=== ANDW Notices INCLUDE FILES START ===' );

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
			error_log( 'ANDW Notices: Checking file: ' . $file_path );

			if ( file_exists( $file_path ) ) {
				error_log( 'ANDW Notices: Loading file: ' . $file );
				require_once $file_path;
				error_log( 'ANDW Notices: File loaded successfully: ' . $file );

				// ブロッククラスの場合は特別にクラス存在確認
				if ( $file === 'includes/class-andw-notices-blocks.php' ) {
					error_log( 'ANDW Notices: After loading blocks file, class_exists = ' . ( class_exists( 'ANDW_Notices_Blocks' ) ? 'YES' : 'NO' ) );
				}
			} else {
				error_log( 'ANDW Notices: ERROR - File not found: ' . $file_path );
			}
		}

		error_log( '=== ANDW Notices INCLUDE FILES END ===' );
	}

	/**
	 * 各機能の初期化
	 */
	public static function init_features() {
		// 【CRITICAL】初期化開始デバッグ
		error_log( '=== ANDW Notices INIT FEATURES START ===' );
		error_log( 'ANDW Notices: Current action hook: ' . current_action() );
		error_log( 'ANDW Notices: did_action(init) = ' . did_action( 'init' ) );

		// カスタム投稿タイプの登録
		error_log( 'ANDW Notices: ANDW_Notices_Post_Type class_exists = ' . ( class_exists( 'ANDW_Notices_Post_Type' ) ? 'YES' : 'NO' ) );
		if ( class_exists( 'ANDW_Notices_Post_Type' ) ) {
			error_log( 'ANDW Notices: Calling ANDW_Notices_Post_Type::init()' );
			ANDW_Notices_Post_Type::init();
			error_log( 'ANDW Notices: ANDW_Notices_Post_Type::init() completed' );
		} else {
			error_log( 'ANDW Notices: ERROR - ANDW_Notices_Post_Type class not found!' );
		}

		// メタフィールドの初期化
		error_log( 'ANDW Notices: ANDW_Notices_Meta_Fields class_exists = ' . ( class_exists( 'ANDW_Notices_Meta_Fields' ) ? 'YES' : 'NO' ) );
		if ( class_exists( 'ANDW_Notices_Meta_Fields' ) ) {
			error_log( 'ANDW Notices: Calling ANDW_Notices_Meta_Fields::init()' );
			ANDW_Notices_Meta_Fields::init();
			error_log( 'ANDW Notices: ANDW_Notices_Meta_Fields::init() completed' );
		} else {
			error_log( 'ANDW Notices: ERROR - ANDW_Notices_Meta_Fields class not found!' );
		}

		// 管理画面カラムのカスタマイズ
		error_log( 'ANDW Notices: ANDW_Notices_Admin_Columns class_exists = ' . ( class_exists( 'ANDW_Notices_Admin_Columns' ) ? 'YES' : 'NO' ) );
		if ( class_exists( 'ANDW_Notices_Admin_Columns' ) ) {
			error_log( 'ANDW Notices: Calling ANDW_Notices_Admin_Columns::init()' );
			ANDW_Notices_Admin_Columns::init();
			error_log( 'ANDW Notices: ANDW_Notices_Admin_Columns::init() completed' );
		} else {
			error_log( 'ANDW Notices: ERROR - ANDW_Notices_Admin_Columns class not found!' );
		}

		// ブロックの登録
		error_log( 'ANDW Notices: ANDW_Notices_Blocks class_exists = ' . ( class_exists( 'ANDW_Notices_Blocks' ) ? 'YES' : 'NO' ) );
		if ( class_exists( 'ANDW_Notices_Blocks' ) ) {
			error_log( 'ANDW Notices: Calling ANDW_Notices_Blocks::init()' );
			try {
				ANDW_Notices_Blocks::init();
				error_log( 'ANDW Notices: ANDW_Notices_Blocks::init() completed successfully' );
			} catch ( Exception $e ) {
				error_log( 'ANDW Notices: ERROR in ANDW_Notices_Blocks::init() - ' . $e->getMessage() );
			}
		} else {
			error_log( 'ANDW Notices: ERROR - ANDW_Notices_Blocks class not found!' );
		}

		// 設定ページの初期化
		error_log( 'ANDW Notices: ANDW_Notices_Settings class_exists = ' . ( class_exists( 'ANDW_Notices_Settings' ) ? 'YES' : 'NO' ) );
		if ( class_exists( 'ANDW_Notices_Settings' ) ) {
			error_log( 'ANDW Notices: Calling ANDW_Notices_Settings::init()' );
			ANDW_Notices_Settings::init();
			error_log( 'ANDW Notices: ANDW_Notices_Settings::init() completed' );
		}

		// キャッシュ機能の初期化
		error_log( 'ANDW Notices: ANDW_Notices_Cache class_exists = ' . ( class_exists( 'ANDW_Notices_Cache' ) ? 'YES' : 'NO' ) );
		if ( class_exists( 'ANDW_Notices_Cache' ) ) {
			error_log( 'ANDW Notices: Calling ANDW_Notices_Cache::init()' );
			ANDW_Notices_Cache::init();
			error_log( 'ANDW Notices: ANDW_Notices_Cache::init() completed' );
		}

		error_log( '=== ANDW Notices INIT FEATURES END ===' );
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

		// プラグインバージョンを保存
		update_option( 'andw_notices_version', ANDW_NOTICES_VERSION );
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