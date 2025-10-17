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

		// 生成されたキーをオプションに記録（削除時のため）
		$recorded_keys = get_option( 'andw_notices_cache_keys', array() );
		$recorded_keys[] = $cache_key;
		// 重複削除と上限設定（100キーまで）
		$recorded_keys = array_unique( array_slice( $recorded_keys, -100 ) );
		update_option( 'andw_notices_cache_keys', $recorded_keys, false );

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
			// フォールバック: 記録されたキーを使用した確実な削除
			$recorded_keys = get_option( 'andw_notices_cache_keys', array() );
			foreach ( $recorded_keys as $cache_key ) {
				// オブジェクトキャッシュから削除
				wp_cache_delete( $cache_key, self::CACHE_GROUP );
				// Transientからも削除
				$transient_key = self::TRANSIENT_PREFIX . $cache_key;
				delete_transient( $transient_key );
			}

			// 記録されたキーをクリア
			delete_option( 'andw_notices_cache_keys' );

			// 念のため、パターンマッチングでも削除（WordPress API使用）
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Core transient cleanup, batch delete required
			self::cleanup_transients_by_prefix( self::TRANSIENT_PREFIX );
		}

		// Transient を検索して削除（wp_cache_flush_group利用時のみ）
		if ( function_exists( 'wp_cache_flush_group' ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Core transient cleanup, batch delete required
			self::cleanup_transients_by_prefix( self::TRANSIENT_PREFIX );
		}

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
		// 記録されたキーの数を使用（概算値）
		$recorded_keys = get_option( 'andw_notices_cache_keys', array() );
		$transient_count = count( $recorded_keys );

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
		// WordPress の Transient API は自動的に期限切れを処理するため、
		// 記録されたキーから期限切れを手動チェック
		$recorded_keys = get_option( 'andw_notices_cache_keys', array() );
		$cleaned_count = 0;

		foreach ( $recorded_keys as $index => $cache_key ) {
			$transient_key = self::TRANSIENT_PREFIX . $cache_key;
			$transient_value = get_transient( $transient_key );

			// Transientが存在しない（期限切れ）場合は記録から削除
			if ( false === $transient_value ) {
				unset( $recorded_keys[ $index ] );
				$cleaned_count++;
			}
		}

		// 更新された記録を保存
		if ( $cleaned_count > 0 ) {
			update_option( 'andw_notices_cache_keys', array_values( $recorded_keys ), false );
		}

		return $cleaned_count;
	}

	/**
	 * プレフィックス付きTransientの一括削除
	 *
	 * @param string $prefix Transientプレフィックス
	 * @return int 削除件数
	 */
	private static function cleanup_transients_by_prefix( $prefix ) {
		// 記録されたキーから該当プレフィックスを抽出
		$recorded_keys = get_option( 'andw_notices_cache_keys', array() );
		$deleted_count = 0;

		foreach ( $recorded_keys as $index => $cache_key ) {
			$transient_key = $prefix . $cache_key;
			if ( delete_transient( $transient_key ) ) {
				unset( $recorded_keys[ $index ] );
				$deleted_count++;
			}
		}

		// 更新されたキーリストを保存
		if ( $deleted_count > 0 ) {
			update_option( 'andw_notices_cache_keys', array_values( $recorded_keys ), false );
		}

		return $deleted_count;
	}
}