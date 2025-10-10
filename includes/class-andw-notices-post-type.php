<?php
/**
 * カスタム投稿タイプ「notices」の登録と管理
 *
 * @package ANDW_Notices
 */

// このファイルに直接アクセスするのを防ぐ
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * カスタム投稿タイプクラス
 */
class ANDW_Notices_Post_Type {

	/**
	 * 投稿タイプ名
	 */
	const POST_TYPE = 'notices';

	/**
	 * 初期化
	 */
	public static function init() {
		if ( did_action( 'init' ) ) {
			self::register_post_type();
		} else {
			add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		}
	}

	/**
	 * カスタム投稿タイプの登録
	 */
	public static function register_post_type() {
		$labels = array(
			'name'                  => __( 'お知らせ', 'andw-notices' ),
			'singular_name'         => __( 'お知らせ', 'andw-notices' ),
			'menu_name'             => __( 'お知らせ', 'andw-notices' ),
			'name_admin_bar'        => __( 'お知らせ', 'andw-notices' ),
			'add_new'               => __( '新規追加', 'andw-notices' ),
			'add_new_item'          => __( '新しいお知らせを追加', 'andw-notices' ),
			'new_item'              => __( '新しいお知らせ', 'andw-notices' ),
			'edit_item'             => __( 'お知らせを編集', 'andw-notices' ),
			'view_item'             => __( 'お知らせを表示', 'andw-notices' ),
			'all_items'             => __( 'すべてのお知らせ', 'andw-notices' ),
			'search_items'          => __( 'お知らせを検索', 'andw-notices' ),
			'parent_item_colon'     => __( '親のお知らせ:', 'andw-notices' ),
			'not_found'             => __( 'お知らせが見つかりませんでした。', 'andw-notices' ),
			'not_found_in_trash'    => __( 'ゴミ箱にお知らせは見つかりませんでした。', 'andw-notices' ),
			'featured_image'        => __( 'アイキャッチ画像', 'andw-notices' ),
			'set_featured_image'    => __( 'アイキャッチ画像を設定', 'andw-notices' ),
			'remove_featured_image' => __( 'アイキャッチ画像を削除', 'andw-notices' ),
			'use_featured_image'    => __( 'アイキャッチ画像として使用', 'andw-notices' ),
			'archives'              => __( 'お知らせアーカイブ', 'andw-notices' ),
			'insert_into_item'      => __( 'お知らせに挿入', 'andw-notices' ),
			'uploaded_to_this_item' => __( 'このお知らせにアップロード', 'andw-notices' ),
			'filter_items_list'     => __( 'お知らせ一覧をフィルタ', 'andw-notices' ),
			'items_list_navigation' => __( 'お知らせ一覧ナビゲーション', 'andw-notices' ),
			'items_list'            => __( 'お知らせ一覧', 'andw-notices' ),
		);

		$args = array(
			'labels'             => $labels,
			'description'        => __( '新着お知らせの管理', 'andw-notices' ),
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'notices' ),
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => 25,
			'menu_icon'          => 'dashicons-megaphone',
			'supports'           => array( 'title', 'editor', 'excerpt', 'thumbnail' ),
			'show_in_rest'       => true,
			'rest_base'          => 'notices',
		);

		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * 投稿タイプ名を取得
	 *
	 * @return string 投稿タイプ名
	 */
	public static function get_post_type() {
		return self::POST_TYPE;
	}

	/**
	 * お知らせ投稿を取得
	 *
	 * @param array $args WP_Query の引数
	 * @return WP_Query
	 */
	public static function get_notices( $args = array() ) {
		$default_args = array(
			'post_type'      => self::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => 5,
			'orderby'        => 'meta_value',
			'meta_key'       => 'andw_notices_display_date',
			'order'          => 'DESC',
			'meta_query'     => array(
				'relation' => 'OR',
				array(
					'key'     => 'andw_notices_display_date',
					'compare' => 'EXISTS',
				),
				array(
					'key'     => 'andw_notices_display_date',
					'compare' => 'NOT EXISTS',
				),
			),
		);

		$args = wp_parse_args( $args, $default_args );

		return new WP_Query( $args );
	}

	/**
	 * お知らせのリンクURLを取得
	 *
	 * @param int $post_id 投稿ID
	 * @return string リンクURL
	 */
	public static function get_notice_link_url( $post_id ) {
		$link_type = get_post_meta( $post_id, 'andw_notices_link_type', true );

		switch ( $link_type ) {
			case 'external':
				$external_url = get_post_meta( $post_id, 'andw_notices_external_url', true );
				return $external_url ? esc_url( $external_url ) : get_permalink( $post_id );

			case 'internal':
				$target_post_id = get_post_meta( $post_id, 'andw_notices_target_post_id', true );
				return $target_post_id ? get_permalink( $target_post_id ) : get_permalink( $post_id );

			default:
				return get_permalink( $post_id );
		}
	}

	/**
	 * お知らせのリンクターゲットを取得
	 *
	 * @param int $post_id 投稿ID
	 * @return string リンクターゲット属性
	 */
	public static function get_notice_link_target( $post_id ) {
		$target_blank = get_post_meta( $post_id, 'andw_notices_target_blank', true );
		return $target_blank ? ' target="_blank" rel="noopener"' : '';
	}

	/**
	 * お知らせの表示日を取得
	 *
	 * @param int $post_id 投稿ID
	 * @return string 表示日（フォーマット済み）
	 */
	public static function get_notice_display_date( $post_id ) {
		$display_date = get_post_meta( $post_id, 'andw_notices_display_date', true );

		if ( empty( $display_date ) ) {
			$display_date = get_the_date( 'Y-m-d H:i:s', $post_id );
		}

		return $display_date;
	}

	/**
	 * お知らせの表示日をISO8601形式で取得
	 *
	 * @param int $post_id 投稿ID
	 * @return string ISO8601形式の日時
	 */
	public static function get_notice_display_date_iso( $post_id ) {
		$display_date = self::get_notice_display_date( $post_id );
		$timestamp = strtotime( $display_date );

		return $timestamp ? gmdate( 'c', $timestamp ) : '';
	}
}