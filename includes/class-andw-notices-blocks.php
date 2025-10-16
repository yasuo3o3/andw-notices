<?php
/**
 * Gutenbergブロックの登録と管理
 *
 * @package ANDW_Notices
 */

// このファイルに直接アクセスするのを防ぐ
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ブロッククラス
 */
class ANDW_Notices_Blocks {

	/**
	 * 初期化
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_blocks' ) );
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'enqueue_editor_assets' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_frontend_assets' ) );
	}

	/**
	 * ブロックの登録
	 */
	public static function register_blocks() {
		register_block_type(
			ANDW_NOTICES_PLUGIN_DIR . 'blocks/notices-list/block.json',
			array(
				'render_callback' => array( __CLASS__, 'render_notices_list_block' ),
			)
		);
	}

	/**
	 * エディターアセットの読み込み
	 */
	public static function enqueue_editor_assets() {
		$asset_file = ANDW_NOTICES_PLUGIN_DIR . 'build/blocks/notices-list/index.asset.php';
		$editor_css_file = ANDW_NOTICES_PLUGIN_DIR . 'build/blocks/notices-list/index.css';

		if ( file_exists( $asset_file ) ) {
			$asset = include $asset_file;

			wp_enqueue_script(
				'andw-notices-blocks',
				ANDW_NOTICES_PLUGIN_URL . 'build/blocks/notices-list/index.js',
				$asset['dependencies'],
				$asset['version'],
				true
			);

			wp_set_script_translations(
				'andw-notices-blocks',
				'andw-notices',
				ANDW_NOTICES_PLUGIN_DIR . 'languages'
			);
		}

		// エディター用CSSの読み込み
		if ( file_exists( $editor_css_file ) ) {
			wp_enqueue_style(
				'andw-notices-blocks-editor',
				ANDW_NOTICES_PLUGIN_URL . 'build/blocks/notices-list/index.css',
				array(),
				filemtime( $editor_css_file )
			);
		}
	}

	/**
	 * フロントエンドアセットの読み込み
	 */
	public static function enqueue_frontend_assets() {
		// ブロックが使用されているページでのみCSSを読み込み
		if ( ! is_admin() && has_block( 'andw/notices-list' ) ) {
			$frontend_css_file = ANDW_NOTICES_PLUGIN_DIR . 'build/blocks/notices-list/style-index.css';

			// フロントエンド用CSSの読み込み
			if ( file_exists( $frontend_css_file ) ) {
				wp_enqueue_style(
					'andw-notices-blocks-style',
					ANDW_NOTICES_PLUGIN_URL . 'build/blocks/notices-list/style-index.css',
					array(),
					filemtime( $frontend_css_file )
				);
			}
		}
	}

	/**
	 * お知らせ一覧ブロックのレンダリング
	 *
	 * @param array    $attributes ブロック属性
	 * @param string   $content ブロックコンテンツ
	 * @param WP_Block $block ブロックインスタンス
	 * @return string レンダリング結果
	 */
	public static function render_notices_list_block( $attributes, $content, $block ) {
		// デバッグ: フロントエンド表示確認
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'ANDW Notices Debug: render_notices_list_block called on frontend' );
			error_log( 'ANDW Notices Debug: is_admin = ' . ( is_admin() ? 'true' : 'false' ) );
		}

		// デフォルト属性
		$default_attributes = array(
			'count'             => 5,
			'order'             => 'desc',
			'orderby'           => 'display_date',
			'includeExternal'   => true,
			'includeInternal'   => true,
			'showDate'          => true,
			'showTitle'         => true,
			'showExcerpt'       => true,
			'excerptLength'     => 100,
			'forceLinkOverride' => 'item',
			'openInNewTab'      => null,
			'layout'            => 'list',
		);

		$attributes = wp_parse_args( $attributes, $default_attributes );

		// キャッシュキーの生成
		$cache_key = self::generate_cache_key( $attributes );
		$cached_content = wp_cache_get( $cache_key, 'andw_notices_blocks' );

		if ( false !== $cached_content && ! is_preview() ) {
			return $cached_content;
		}

		// お知らせの取得
		$notices = self::get_notices_for_block( $attributes );

		// デバッグ情報（開発時のみ）
		$debug_info = '';
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$debug_info = sprintf(
				'<!-- デバッグ情報: 取得されたお知らせ数: %d, クエリ属性: %s -->',
				count( $notices ),
				wp_json_encode( $attributes )
			);
		}

		if ( empty( $notices ) ) {
			$no_content_message = '<p class="andw-notices-no-content">' . esc_html__( 'お知らせが見つかりませんでした。', 'andw-notices' ) . '</p>';
			// デバッグ情報付きで返す
			$no_content_message = $debug_info . $no_content_message;
			wp_cache_set( $cache_key, $no_content_message, 'andw_notices_blocks', HOUR_IN_SECONDS );
			return $no_content_message;
		}

		// HTMLの生成
		$html = self::generate_notices_html( $notices, $attributes );

		// デバッグ情報を先頭に追加
		$html = $debug_info . $html;

		// フロントエンドでCSSが読み込まれていない場合の強制読み込み
		if ( ! is_admin() && ! wp_style_is( 'andw-notices-blocks-style', 'enqueued' ) ) {
			$frontend_css_file = ANDW_NOTICES_PLUGIN_DIR . 'build/blocks/notices-list/style-index.css';
			if ( file_exists( $frontend_css_file ) ) {
				wp_enqueue_style(
					'andw-notices-blocks-style',
					ANDW_NOTICES_PLUGIN_URL . 'build/blocks/notices-list/style-index.css',
					array(),
					filemtime( $frontend_css_file )
				);
			}
		}

		// キャッシュに保存
		wp_cache_set( $cache_key, $html, 'andw_notices_blocks', HOUR_IN_SECONDS );

		return $html;
	}

	/**
	 * ブロック用お知らせデータの取得
	 *
	 * @param array $attributes ブロック属性
	 * @return array お知らせデータ
	 */
	private static function get_notices_for_block( $attributes ) {
		$args = array(
			'post_type'      => 'notices',
			'post_status'    => 'publish',
			'posts_per_page' => absint( $attributes['count'] ),
			'order'          => strtoupper( $attributes['order'] ),
		);

		// 並び順の設定
		if ( 'display_date' === $attributes['orderby'] ) {
			// より安全な並び順設定：display_dateがない場合は投稿日でフォールバック
			$args['orderby'] = array(
				'meta_value' => 'DESC',
				'date'       => 'DESC',
			);
			$args['meta_key'] = 'andw_notices_display_date';
			$args['meta_type'] = 'DATE';
		} else {
			$args['orderby'] = 'date';
		}

		// デバッグ情報（開発時のみ）
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'ANDW Notices Debug: WP_Query args: ' . print_r( $args, true ) );

			// 簡単なクエリでnotices投稿タイプの投稿数を確認
			$simple_query = new WP_Query( array(
				'post_type'      => 'notices',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			) );
			error_log( 'ANDW Notices Debug: 総notices投稿数: ' . $simple_query->found_posts );

			// 投稿タイプが正しく登録されているか確認
			$post_types = get_post_types( array(), 'names' );
			error_log( 'ANDW Notices Debug: 登録済み投稿タイプ: ' . implode( ', ', $post_types ) );
		}

		// フィルタリング
		if ( ! $attributes['includeExternal'] || ! $attributes['includeInternal'] ) {
			$meta_query = array( 'relation' => 'OR' );

			if ( $attributes['includeExternal'] ) {
				$meta_query[] = array(
					'key'   => 'andw_notices_link_type',
					'value' => 'external',
				);
			}

			if ( $attributes['includeInternal'] ) {
				$meta_query[] = array(
					'key'   => 'andw_notices_link_type',
					'value' => array( 'self', 'internal' ),
					'compare' => 'IN',
				);
			}

			$args['meta_query'] = isset( $args['meta_query'] )
				? array( 'relation' => 'AND', $args['meta_query'], $meta_query )
				: $meta_query;
		}

		$query = new WP_Query( $args );

		// デバッグ情報（開発時のみ）
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'ANDW Notices Debug: クエリ結果: 見つかった投稿数: ' . $query->found_posts );
			error_log( 'ANDW Notices Debug: 取得した投稿数: ' . count( $query->posts ) );
			if ( ! empty( $query->posts ) ) {
				$post_ids = array_map( function( $post ) { return $post->ID; }, $query->posts );
				error_log( 'ANDW Notices Debug: 投稿ID: ' . implode( ', ', $post_ids ) );
			}
		}

		// もし複雑なクエリで結果が空の場合、シンプルなクエリでフォールバック
		if ( empty( $query->posts ) && ( ! empty( $args['meta_query'] ) || ! empty( $args['meta_key'] ) ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'ANDW Notices Debug: 複雑なクエリで結果が空だったため、シンプルなクエリにフォールバック' );
			}

			$fallback_args = array(
				'post_type'      => 'notices',
				'post_status'    => 'publish',
				'posts_per_page' => absint( $attributes['count'] ),
				'orderby'        => 'date',
				'order'          => strtoupper( $attributes['order'] ),
			);

			$fallback_query = new WP_Query( $fallback_args );

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'ANDW Notices Debug: フォールバッククエリ結果: ' . $fallback_query->found_posts . '件' );
			}

			return $fallback_query->posts;
		}

		return $query->posts;
	}

	/**
	 * お知らせHTMLの生成
	 *
	 * @param array $notices お知らせデータ
	 * @param array $attributes ブロック属性
	 * @return string HTML
	 */
	private static function generate_notices_html( $notices, $attributes ) {
		$layout_class = 'list' === $attributes['layout'] ? 'andw-notices' : 'andw-notices andw-notices-card';
		$html = '<ul class="' . esc_attr( $layout_class ) . '">';

		foreach ( $notices as $notice ) {
			$html .= '<li class="andw-notice-item">';

			// 日付の表示
			if ( $attributes['showDate'] ) {
				$display_date = ANDW_Notices_Post_Type::get_notice_display_date( $notice->ID );
				$iso_date = ANDW_Notices_Post_Type::get_notice_display_date_iso( $notice->ID );
				$formatted_date = mysql2date( get_option( 'date_format' ), $display_date );

				$html .= '<time class="andw-notice-date" datetime="' . esc_attr( $iso_date ) . '">';
				$html .= esc_html( $formatted_date );
				$html .= '</time>';
			}

			// タイトルの表示
			if ( $attributes['showTitle'] ) {
				$title = get_the_title( $notice->ID );
				$link_url = self::get_notice_link_url( $notice->ID, $attributes );
				$link_target = self::get_notice_link_target( $notice->ID, $attributes );

				$html .= '<h3 class="andw-notice-title">';
				if ( $link_url && 'null' !== $attributes['forceLinkOverride'] ) {
					$html .= '<a href="' . esc_url( $link_url ) . '"' . $link_target . '>';
					$html .= esc_html( $title );
					$html .= '</a>';
				} else {
					$html .= esc_html( $title );
				}
				$html .= '</h3>';
			}

			// 抜粋の表示
			if ( $attributes['showExcerpt'] ) {
				$excerpt = get_the_excerpt( $notice->ID );
				if ( $excerpt ) {
					$trimmed_excerpt = self::trim_excerpt( $excerpt, $attributes['excerptLength'] );
					$html .= '<p class="andw-notice-excerpt">' . esc_html( $trimmed_excerpt ) . '</p>';
				}
			}

			$html .= '</li>';
		}

		$html .= '</ul>';

		return $html;
	}

	/**
	 * お知らせのリンクURLを取得
	 *
	 * @param int   $post_id 投稿ID
	 * @param array $attributes ブロック属性
	 * @return string リンクURL
	 */
	private static function get_notice_link_url( $post_id, $attributes ) {
		// 強制リンクオーバーライドの処理
		switch ( $attributes['forceLinkOverride'] ) {
			case 'self':
				return get_permalink( $post_id );

			case 'external':
				$external_url = get_post_meta( $post_id, 'andw_notices_external_url', true );
				return $external_url ? esc_url( $external_url ) : get_permalink( $post_id );

			case 'internal':
				$target_post_id = get_post_meta( $post_id, 'andw_notices_target_post_id', true );
				return $target_post_id ? get_permalink( $target_post_id ) : get_permalink( $post_id );

			case 'null':
				return '';

			default:
				return ANDW_Notices_Post_Type::get_notice_link_url( $post_id );
		}
	}

	/**
	 * お知らせのリンクターゲットを取得
	 *
	 * @param int   $post_id 投稿ID
	 * @param array $attributes ブロック属性
	 * @return string リンクターゲット属性
	 */
	private static function get_notice_link_target( $post_id, $attributes ) {
		// 一括設定が優先
		if ( null !== $attributes['openInNewTab'] ) {
			return $attributes['openInNewTab'] ? ' target="_blank" rel="noopener"' : '';
		}

		// 個別設定を使用
		return ANDW_Notices_Post_Type::get_notice_link_target( $post_id );
	}

	/**
	 * 抜粋をトリム
	 *
	 * @param string $excerpt 抜粋
	 * @param int    $length 文字数
	 * @return string トリムされた抜粋
	 */
	private static function trim_excerpt( $excerpt, $length ) {
		$excerpt = wp_strip_all_tags( $excerpt );
		if ( mb_strlen( $excerpt ) > $length ) {
			$excerpt = mb_substr( $excerpt, 0, $length ) . '...';
		}
		return $excerpt;
	}

	/**
	 * キャッシュキーの生成
	 *
	 * @param array $attributes ブロック属性
	 * @return string キャッシュキー
	 */
	private static function generate_cache_key( $attributes ) {
		return 'andw_notices_block_' . md5( wp_json_encode( $attributes ) );
	}
}