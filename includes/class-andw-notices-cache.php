<?php
/**
 * キャッシュ機能の管理
 *
 * @package ANDW_Notices
 */

// このファイルに直接アクセスするのを防ぐ
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * キャッシュクラス
 */
class ANDW_Notices_Cache {

	/**
	 * キャッシュグループ名
	 */
	const CACHE_GROUP = 'andw_notices_blocks';

	/**
	 * transient プレフィックス
	 */
	const TRANSIENT_PREFIX = 'andw_notices_';

	/**
	 * 初期化
	 */
	public static function init() {
		add_action( 'save_post_notices', array( __CLASS__, 'clear_cache_on_save' ) );
		add_action( 'delete_post', array( __CLASS__, 'clear_cache_on_delete' ) );
		add_action( 'wp_trash_post', array( __CLASS__, 'clear_cache_on_delete' ) );
		add_action( 'untrash_post', array( __CLASS__, 'clear_cache_on_save' ) );
		add_action( 'transition_post_status', array( __CLASS__, 'clear_cache_on_status_change' ), 10, 3 );
	}

	/**
	 * ブロックキャッシュの取得
	 *
	 * @param array $attributes ブロック属性
	 * @return string|false キャッシュされたHTML、または false
	 */
	public static function get_block_cache( $attributes ) {
		$cache_key = self::generate_cache_key( $attributes );

		// Object Cache API を使用
		$cached_content = wp_cache_get( $cache_key, self::CACHE_GROUP );
		if ( false !== $cached_content ) {
			return $cached_content;
		}

		// Transient API も確認（フォールバック）
		$transient_key = self::TRANSIENT_PREFIX . $cache_key;
		return get_transient( $transient_key );
	}

	/**
	 * ブロックキャッシュの保存
	 *
	 * @param array  $attributes ブロック属性
	 * @param string $content HTMLコンテンツ
	 * @return bool 保存成功時は true
	 */
	public static function set_block_cache( $attributes, $content ) {
		$cache_key = self::generate_cache_key( $attributes );
		$duration = self::get_cache_duration();

		// Object Cache API で保存
		$object_cache_result = wp_cache_set( $cache_key, $content, self::CACHE_GROUP, $duration );

		// Transient API でも保存（永続化）
		$transient_key = self::TRANSIENT_PREFIX . $cache_key;
		$transient_result = set_transient( $transient_key, $content, $duration );

		return $object_cache_result || $transient_result;
	}

	/**
	 * すべてのキャッシュをクリア
	 */
	public static function clear_cache() {
		// Object Cache のグループをフラッシュ（関数存在チェック）
		if ( function_exists( 'wp_cache_flush_group' ) ) {
			wp_cache_flush_group( self::CACHE_GROUP );
		} else {
			// フォールバック: 個別のキャッシュキーを削除
			$cache_keys = array(
				'notices_list_',
				'notices_count_',
				'notices_meta_'
			);
			foreach ( $cache_keys as $key_prefix ) {
				// 可能な限りのキーパターンを削除
				for ( $i = 0; $i < 100; $i++ ) {
					wp_cache_delete( $key_prefix . $i, self::CACHE_GROUP );
				}
			}
		}

		// Transient を検索して削除
		global $wpdb;

		$transient_pattern = $wpdb->esc_like( '_transient_' . self::TRANSIENT_PREFIX ) . '%';
		$timeout_pattern = $wpdb->esc_like( '_transient_timeout_' . self::TRANSIENT_PREFIX ) . '%';

		// Transient とそのタイムアウトを削除
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$transient_pattern,
				$timeout_pattern
			)
		);

		// オプションキャッシュをクリア
		wp_cache_delete( 'alloptions', 'options' );

		do_action( 'andw_notices_cache_cleared' );
	}

	/**
	 * お知らせ保存時にキャッシュをクリア
	 *
	 * @param int $post_id 投稿ID
	 */
	public static function clear_cache_on_save( $post_id ) {
		if ( get_post_type( $post_id ) !== 'notices' ) {
			return;
		}

		self::clear_cache();
	}

	/**
	 * お知らせ削除時にキャッシュをクリア
	 *
	 * @param int $post_id 投稿ID
	 */
	public static function clear_cache_on_delete( $post_id ) {
		if ( get_post_type( $post_id ) !== 'notices' ) {
			return;
		}

		self::clear_cache();
	}

	/**
	 * 投稿ステータス変更時にキャッシュをクリア
	 *
	 * @param string  $new_status 新しいステータス
	 * @param string  $old_status 古いステータス
	 * @param WP_Post $post 投稿オブジェクト
	 */
	public static function clear_cache_on_status_change( $new_status, $old_status, $post ) {
		if ( $post->post_type !== 'notices' ) {
			return;
		}

		// 公開状態に関わる変更の場合のみキャッシュクリア
		$publish_statuses = array( 'publish', 'private' );
		if ( in_array( $new_status, $publish_statuses, true ) || in_array( $old_status, $publish_statuses, true ) ) {
			self::clear_cache();
		}
	}

	/**
	 * キャッシュキーの生成
	 *
	 * @param array $attributes ブロック属性
	 * @return string キャッシュキー
	 */
	private static function generate_cache_key( $attributes ) {
		// 属性をソートして一意性を保証
		ksort( $attributes );

		// 現在の言語も考慮
		$locale = get_locale();

		// 最終更新時刻も考慮（24時間ごとに自動更新）
		$time_factor = floor( time() / DAY_IN_SECONDS );

		$key_data = array(
			'attributes'  => $attributes,
			'locale'      => $locale,
			'time_factor' => $time_factor,
			'version'     => ANDW_NOTICES_VERSION,
		);

		return 'block_' . md5( wp_json_encode( $key_data ) );
	}

	/**
	 * キャッシュ有効期間の取得
	 *
	 * @return int キャッシュ有効期間（秒）
	 */
	private static function get_cache_duration() {
		if ( class_exists( 'ANDW_Notices_Settings' ) ) {
			return ANDW_Notices_Settings::get_setting( 'cache_duration', HOUR_IN_SECONDS );
		}

		return HOUR_IN_SECONDS;
	}

	/**
	 * キャッシュ統計の取得
	 *
	 * @return array キャッシュ統計情報
	 */
	public static function get_cache_stats() {
		global $wpdb;

		$transient_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( '_transient_' . self::TRANSIENT_PREFIX ) . '%'
			)
		);

		return array(
			'transient_count' => $transient_count,
			'cache_group'     => self::CACHE_GROUP,
			'cache_duration'  => self::get_cache_duration(),
		);
	}

	/**
	 * 期限切れキャッシュのクリーンアップ
	 */
	public static function cleanup_expired_cache() {
		// WordPress の Transient API は自動的に期限切れを処理するが、
		// 明示的にクリーンアップを実行する場合
		global $wpdb;

		$time = time();
		$expired_transients = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT REPLACE(option_name, '_transient_timeout_', '') FROM {$wpdb->options}
				 WHERE option_name LIKE %s AND option_value < %d",
				$wpdb->esc_like( '_transient_timeout_' . self::TRANSIENT_PREFIX ) . '%',
				$time
			)
		);

		foreach ( $expired_transients as $transient ) {
			delete_transient( $transient );
		}

		return count( $expired_transients );
	}
}