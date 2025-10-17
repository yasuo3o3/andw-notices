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
		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_fields' ) );
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

	/**
	 * お知らせのイベントデータを取得
	 *
	 * @param int $post_id 投稿ID
	 * @return array イベントデータ配列
	 */
	public static function get_notice_event_data( $post_id ) {
		$event_data = get_post_meta( $post_id, 'andw_notices_event_data', true );

		// デフォルト値を設定
		if ( empty( $event_data ) || ! is_array( $event_data ) ) {
			$event_data = array(
				'type' => 'none',
				'label' => '',
				'single_date' => '',
				'start_date' => '',
				'end_date' => '',
				'free_text' => ''
			);
		}

		return $event_data;
	}

	/**
	 * お知らせのイベント日付をフォーマット済みで取得
	 *
	 * @param int   $post_id 投稿ID
	 * @param array $options 表示オプション
	 * @return string フォーマット済みのイベント日付HTML
	 */
	public static function get_notice_event_output( $post_id, $options = array() ) {
		$event_data = self::get_notice_event_data( $post_id );

		// イベント日付なしの場合は空文字を返す
		if ( 'none' === $event_data['type'] ) {
			return '';
		}

		// デフォルトオプション
		$default_options = array(
			// 基本設定
			'container_class' => 'andw_notices_event',
			'label_class'     => 'andw_notices_event_label',
			'date_class'      => 'andw_notices_event_date',
			'separator'       => '：',
			'date_format'     => get_option( 'date_format' ),
			'period_separator' => ' ～ ',

			// レイアウト設定
			'layout' => array(
				'type' => 'horizontal', // horizontal|vertical|grid
				'alignment' => 'start', // start|center|end
				'gap' => '0.5rem',
				'wrap' => false,
			),

			// 表示設定
			'display' => array(
				'show_label' => true,
				'show_icon' => false,
				'label_position' => 'before', // before|after|above|below
				'priority' => 'label-first', // label-first|date-first
			),

			// スタイル設定
			'style' => 'default', // default|compact|badge|card|timeline
			'preset' => null, // プリセット名（指定時は他設定を上書き）
		);

		$options = wp_parse_args( $options, $default_options );

		// プリセット処理
		if ( ! empty( $options['preset'] ) ) {
			$options = self::apply_event_preset( $options['preset'], $options );
		}

		$output = '';
		$label = '';
		$date_content = '';

		// ラベルの準備
		if ( ! empty( $event_data['label'] ) ) {
			$label = '<span class="' . esc_attr( $options['label_class'] ) . '">' .
					 esc_html( $event_data['label'] ) .
					 esc_html( $options['separator'] ) .
					 '</span>';
		}

		// タイプ別の日付コンテンツ生成
		switch ( $event_data['type'] ) {
			case 'single':
				if ( ! empty( $event_data['single_date'] ) ) {
					$formatted_date = mysql2date( $options['date_format'], $event_data['single_date'] );
					$date_content = '<time datetime="' . esc_attr( $event_data['single_date'] ) . '">' .
								   esc_html( $formatted_date ) .
								   '</time>';
				}
				break;

			case 'period':
				if ( ! empty( $event_data['start_date'] ) && ! empty( $event_data['end_date'] ) ) {
					$start_formatted = mysql2date( $options['date_format'], $event_data['start_date'] );
					$end_formatted = mysql2date( $options['date_format'], $event_data['end_date'] );

					$date_content = '<time datetime="' . esc_attr( $event_data['start_date'] ) . '">' .
								   esc_html( $start_formatted ) .
								   '</time>' .
								   esc_html( $options['period_separator'] ) .
								   '<time datetime="' . esc_attr( $event_data['end_date'] ) . '">' .
								   esc_html( $end_formatted ) .
								   '</time>';
				} elseif ( ! empty( $event_data['start_date'] ) ) {
					$start_formatted = mysql2date( $options['date_format'], $event_data['start_date'] );
					$date_content = '<time datetime="' . esc_attr( $event_data['start_date'] ) . '">' .
								   esc_html( $start_formatted ) .
								   '</time>';
				}
				break;

			case 'text':
				if ( ! empty( $event_data['free_text'] ) ) {
					$date_content = esc_html( $event_data['free_text'] );
				}
				break;
		}

		// 出力の組み立て
		if ( ! empty( $date_content ) ) {
			// ラベル表示制御
			if ( ! $options['display']['show_label'] ) {
				$label = '';
			}

			// データ属性の生成
			$data_attributes = array(
				'data-layout' => esc_attr( $options['layout']['type'] ),
				'data-style' => esc_attr( $options['style'] ),
				'data-priority' => esc_attr( $options['display']['priority'] ),
				'data-type' => esc_attr( $event_data['type'] ),
				'data-alignment' => esc_attr( $options['layout']['alignment'] ),
			);

			// CSS変数の生成
			$css_vars = array(
				'--layout-gap' => esc_attr( $options['layout']['gap'] ),
			);

			$data_attr_string = '';
			foreach ( $data_attributes as $attr => $value ) {
				$data_attr_string .= ' ' . $attr . '="' . $value . '"';
			}

			$style_attr = '';
			if ( ! empty( $css_vars ) ) {
				$style_parts = array();
				foreach ( $css_vars as $var => $value ) {
					$style_parts[] = $var . ':' . $value;
				}
				$style_attr = ' style="' . esc_attr( implode( ';', $style_parts ) ) . '"';
			}

			// フィルターでカスタマイズ可能にする
			$layout_type = apply_filters( 'andw_notices_event_layout', $options['layout']['type'], $event_data, $post_id );
			$container_class = apply_filters( 'andw_notices_event_container_class', $options['container_class'], $event_data, $post_id );

			// 要素の順序制御
			$elements = array();
			if ( 'date-first' === $options['display']['priority'] ) {
				if ( ! empty( $date_content ) ) {
					$elements[] = '<span class="' . esc_attr( $options['date_class'] ) . '" data-component="date">' . $date_content . '</span>';
				}
				if ( ! empty( $label ) ) {
					$elements[] = '<span class="' . esc_attr( $options['label_class'] ) . '" data-component="label">' . $label . '</span>';
				}
			} else {
				if ( ! empty( $label ) ) {
					$elements[] = '<span class="' . esc_attr( $options['label_class'] ) . '" data-component="label">' . $label . '</span>';
				}
				if ( ! empty( $date_content ) ) {
					$elements[] = '<span class="' . esc_attr( $options['date_class'] ) . '" data-component="date">' . $date_content . '</span>';
				}
			}

			$output = '<div class="' . esc_attr( $container_class ) . '"' . $data_attr_string . $style_attr . '>' .
					  implode( '', $elements ) .
					  '</div>';
		}

		return apply_filters( 'andw_notices_event_output', $output, $event_data, $options, $post_id );
	}

	/**
	 * イベント表示プリセットの適用
	 *
	 * @param string $preset_name プリセット名
	 * @param array  $options 現在のオプション
	 * @return array マージされたオプション
	 */
	private static function apply_event_preset( $preset_name, $options ) {
		$presets = self::get_event_presets();

		if ( ! isset( $presets[ $preset_name ] ) ) {
			return $options;
		}

		$preset = $presets[ $preset_name ];

		// 深いマージ（再帰的配列マージ）
		return array_replace_recursive( $options, $preset );
	}

	/**
	 * イベント表示プリセットの定義を取得
	 *
	 * @return array プリセット定義
	 */
	private static function get_event_presets() {
		$presets = array(
			'compact' => array(
				'layout' => array(
					'type' => 'horizontal',
					'gap' => '0.25rem',
					'alignment' => 'center',
				),
				'style' => 'compact',
				'container_class' => 'andw_notices_event compact',
				'separator' => ' ',
			),

			'badge' => array(
				'layout' => array(
					'type' => 'horizontal',
					'gap' => '0.125rem',
					'alignment' => 'center',
				),
				'style' => 'badge',
				'container_class' => 'andw_notices_event badge',
				'separator' => '',
				'display' => array(
					'priority' => 'date-first',
				),
			),

			'card' => array(
				'layout' => array(
					'type' => 'vertical',
					'gap' => '0.5rem',
					'alignment' => 'start',
				),
				'style' => 'card',
				'container_class' => 'andw_notices_event card',
				'display' => array(
					'label_position' => 'above',
				),
			),

			'timeline' => array(
				'layout' => array(
					'type' => 'horizontal',
					'gap' => '0.75rem',
					'alignment' => 'center',
				),
				'style' => 'timeline',
				'container_class' => 'andw_notices_event timeline',
				'display' => array(
					'show_icon' => true,
					'priority' => 'date-first',
				),
			),

			'minimal' => array(
				'layout' => array(
					'type' => 'horizontal',
					'gap' => '0',
					'alignment' => 'start',
				),
				'style' => 'minimal',
				'container_class' => 'andw_notices_event minimal',
				'separator' => '',
				'display' => array(
					'show_label' => false,
				),
			),
		);

		// フィルターでカスタムプリセットを追加可能
		return apply_filters( 'andw_notices_event_presets', $presets );
	}

	/**
	 * 利用可能なプリセット一覧を取得（公開API）
	 *
	 * @return array プリセット名の配列
	 */
	public static function get_available_presets() {
		$presets = self::get_event_presets();
		return array_keys( $presets );
	}

	/**
	 * プリセットの詳細情報を取得（公開API）
	 *
	 * @param string $preset_name プリセット名
	 * @return array|null プリセット設定
	 */
	public static function get_preset_details( $preset_name ) {
		$presets = self::get_event_presets();
		return isset( $presets[ $preset_name ] ) ? $presets[ $preset_name ] : null;
	}

	/**
	 * REST APIフィールドの登録
	 */
	public static function register_rest_fields() {
		// セキュリティチェック - 編集権限のあるユーザーのみ
		$permission_callback = function() {
			return current_user_can( 'edit_posts' );
		};

		// display_dateメタフィールドをREST APIで利用可能にする
		register_rest_field( self::POST_TYPE, 'andw_display_date', array(
			'get_callback'    => function( $object ) {
				return get_post_meta( $object['id'], 'andw_notices_display_date', true );
			},
			'update_callback' => function( $value, $object ) {
				if ( current_user_can( 'edit_post', $object->ID ) ) {
					return update_post_meta( $object->ID, 'andw_notices_display_date', sanitize_text_field( $value ) );
				}
				return false;
			},
			'schema'          => array(
				'description' => __( 'お知らせの表示日', 'andw-notices' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
		) );

		// link_typeメタフィールド
		register_rest_field( self::POST_TYPE, 'andw_link_type', array(
			'get_callback'    => function( $object ) {
				return get_post_meta( $object['id'], 'andw_notices_link_type', true );
			},
			'update_callback' => function( $value, $object ) {
				if ( current_user_can( 'edit_post', $object->ID ) ) {
					return update_post_meta( $object->ID, 'andw_notices_link_type', sanitize_text_field( $value ) );
				}
				return false;
			},
			'schema'          => array(
				'description' => __( 'リンクタイプ', 'andw-notices' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
		) );

		// イベントデータメタフィールド
		register_rest_field( self::POST_TYPE, 'andw_event_data', array(
			'get_callback'    => function( $object ) {
				return self::get_notice_event_data( $object['id'] );
			},
			'update_callback' => function( $value, $object ) {
				if ( current_user_can( 'edit_post', $object->ID ) ) {
					return update_post_meta( $object->ID, 'andw_notices_event_data', $value );
				}
				return false;
			},
			'schema'          => array(
				'description' => __( 'イベント日付データ', 'andw-notices' ),
				'type'        => 'object',
				'context'     => array( 'view', 'edit' ),
				'properties'  => array(
					'type'        => array( 'type' => 'string' ),
					'label'       => array( 'type' => 'string' ),
					'single_date' => array( 'type' => 'string' ),
					'start_date'  => array( 'type' => 'string' ),
					'end_date'    => array( 'type' => 'string' ),
					'free_text'   => array( 'type' => 'string' ),
				),
			),
		) );

		// イベント出力（HTML）メタフィールド
		register_rest_field( self::POST_TYPE, 'andw_event_output', array(
			'get_callback'    => function( $object ) {
				return self::get_notice_event_output( $object['id'] );
			},
			'schema'          => array(
				'description' => __( 'イベント日付HTML出力', 'andw-notices' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
		) );
	}
}