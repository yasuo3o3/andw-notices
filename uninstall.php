<?php
/**
 * プラグインアンインストール処理
 *
 * このファイルは、プラグインが削除された際に実行されます。
 * プラグイン停止時ではなく、完全削除時のみ実行されます。
 *
 * @package ANDW_Notices
 */

// アンインストール処理が正式に呼び出されたかチェック
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * プラグインデータのクリーンアップ
 */
function andw_notices_uninstall_cleanup() {
	global $wpdb;

	// 1. お知らせ投稿の削除
	$notice_posts = get_posts(
		array(
			'post_type'      => 'notices',
			'post_status'    => 'any',
			'numberposts'    => -1,
			'fields'         => 'ids',
		)
	);

	foreach ( $notice_posts as $post_id ) {
		// アタッチメントも一緒に削除
		wp_delete_post( $post_id, true );
	}

	// 2. メタデータの削除（念のため、残存メタデータをクリーンアップ）
	$meta_keys = array(
		'andw_notices_display_date',
		'andw_notices_event_data',
		'andw_notices_link_type',
		'andw_notices_target_post_id',
		'andw_notices_external_url',
		'andw_notices_target_blank',
	);

	foreach ( $meta_keys as $meta_key ) {
		delete_post_meta_by_key( $meta_key );
	}

	// 3. プラグイン設定オプションの削除
	$options_to_delete = array(
		'andw_notices_settings',
		'andw_notices_templates',
		'andw_notices_cache_keys',
		'andw_notices_version',
	);

	foreach ( $options_to_delete as $option ) {
		delete_option( $option );
	}

	// 4. Transientキャッシュの削除
	$recorded_keys = get_option( 'andw_notices_cache_keys', array() );
	foreach ( $recorded_keys as $cache_key ) {
		delete_transient( 'andw_notices_' . $cache_key );
	}

	// 5. オブジェクトキャッシュのクリア（関数存在チェック）
	if ( function_exists( 'wp_cache_flush_group' ) ) {
		wp_cache_flush_group( 'andw_notices_blocks' );
	}

	// 6. WordPress標準のキャッシュクリア
	wp_cache_delete( 'alloptions', 'options' );

	// 注意: wp_cache_flush() は使用しない（サイト全体に影響するため）
}

// アンインストール処理の実行
andw_notices_uninstall_cleanup();