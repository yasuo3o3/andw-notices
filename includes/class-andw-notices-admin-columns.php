<?php
/**
 * 管理画面のカスタムカラムとクイック編集
 *
 * @package ANDW_Notices
 */

// このファイルに直接アクセスするのを防ぐ
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 管理画面カラムクラス
 */
class ANDW_Notices_Admin_Columns {

	/**
	 * 初期化
	 */
	public static function init() {
		add_filter( 'manage_notices_posts_columns', array( __CLASS__, 'add_custom_columns' ) );
		add_action( 'manage_notices_posts_custom_column', array( __CLASS__, 'display_custom_columns' ), 10, 2 );
		add_filter( 'manage_edit-notices_sortable_columns', array( __CLASS__, 'add_sortable_columns' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'handle_column_sorting' ) );

		// クイック編集
		add_action( 'quick_edit_custom_box', array( __CLASS__, 'add_quick_edit_fields' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_quick_edit_script' ) );
		add_action( 'wp_ajax_get_notice_quick_edit_data', array( __CLASS__, 'ajax_get_quick_edit_data' ) );
	}

	/**
	 * カスタムカラムの追加
	 *
	 * @param array $columns カラム配列
	 * @return array 更新されたカラム配列
	 */
	public static function add_custom_columns( $columns ) {
		$new_columns = array();

		foreach ( $columns as $key => $title ) {
			$new_columns[ $key ] = $title;

			// タイトルの後に表示日カラムを挿入
			if ( 'title' === $key ) {
				$new_columns['display_date'] = __( '表示日', 'andw-notices' );
			}

			// 日付の前にリンク先カラムを挿入
			if ( 'date' === $key ) {
				$new_columns['link_destination'] = __( 'リンク先', 'andw-notices' );
			}
		}

		return $new_columns;
	}

	/**
	 * カスタムカラムの表示
	 *
	 * @param string $column カラム名
	 * @param int    $post_id 投稿ID
	 */
	public static function display_custom_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'display_date':
				self::display_display_date_column( $post_id );
				break;

			case 'link_destination':
				self::display_link_destination_column( $post_id );
				break;
		}
	}

	/**
	 * 表示日カラムの表示
	 *
	 * @param int $post_id 投稿ID
	 */
	private static function display_display_date_column( $post_id ) {
		$display_date = get_post_meta( $post_id, 'andw_notices_display_date', true );

		if ( empty( $display_date ) ) {
			echo '<span class="na">' . esc_html__( '公開日を使用', 'andw-notices' ) . '</span>';
		} else {
			$formatted_date = mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $display_date );
			echo esc_html( $formatted_date );
		}
	}

	/**
	 * リンク先カラムの表示
	 *
	 * @param int $post_id 投稿ID
	 */
	private static function display_link_destination_column( $post_id ) {
		$link_type = get_post_meta( $post_id, 'andw_notices_link_type', true );
		$target_blank = get_post_meta( $post_id, 'andw_notices_target_blank', true );

		$link_text = '';
		$target_icon = $target_blank ? ' <span class="dashicons dashicons-external" title="' . esc_attr__( '新規タブで開く', 'andw-notices' ) . '"></span>' : '';

		switch ( $link_type ) {
			case 'external':
				$external_url = get_post_meta( $post_id, 'andw_notices_external_url', true );
				if ( $external_url ) {
					$link_text = '<a href="' . esc_url( $external_url ) . '" target="_blank" rel="noopener">' . esc_html( $external_url ) . '</a>';
				} else {
					$link_text = __( '外部URL（未設定）', 'andw-notices' );
				}
				break;

			case 'internal':
				$target_post_id = get_post_meta( $post_id, 'andw_notices_target_post_id', true );
				if ( $target_post_id ) {
					$target_post = get_post( $target_post_id );
					if ( $target_post ) {
						$link_text = '<a href="' . esc_url( get_permalink( $target_post_id ) ) . '">' . esc_html( get_the_title( $target_post_id ) ) . '</a>';
					} else {
						$link_text = __( '内部ページ（削除済み）', 'andw-notices' );
					}
				} else {
					$link_text = __( '内部ページ（未設定）', 'andw-notices' );
				}
				break;

			default:
				$link_text = '<a href="' . esc_url( get_permalink( $post_id ) ) . '">' . esc_html__( '自身のページ', 'andw-notices' ) . '</a>';
				break;
		}

		echo wp_kses_post( $link_text . $target_icon );
	}

	/**
	 * ソート可能カラムの追加
	 *
	 * @param array $sortable_columns ソート可能カラム配列
	 * @return array 更新されたカラム配列
	 */
	public static function add_sortable_columns( $sortable_columns ) {
		$sortable_columns['display_date'] = 'display_date';
		return $sortable_columns;
	}

	/**
	 * カラムソートの処理
	 *
	 * @param WP_Query $query クエリオブジェクト
	 */
	public static function handle_column_sorting( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		$orderby = $query->get( 'orderby' );

		if ( 'display_date' === $orderby ) {
			$query->set( 'meta_key', 'andw_notices_display_date' );
			$query->set( 'orderby', 'meta_value' );
		}
	}

	/**
	 * クイック編集フィールドの追加
	 *
	 * @param string $column_name カラム名
	 * @param string $post_type 投稿タイプ
	 */
	public static function add_quick_edit_fields( $column_name, $post_type ) {
		if ( 'notices' !== $post_type ) {
			return;
		}

		if ( 'display_date' === $column_name ) {
			?>
			<fieldset class="inline-edit-col-right">
				<div class="inline-edit-col">
					<label>
						<span class="title"><?php esc_html_e( '表示日', 'andw-notices' ); ?></span>
						<span class="input-text-wrap">
							<input type="text" name="andw_notices_display_date" class="ptitle" value="" placeholder="YYYY-MM-DD または YYYY-MM-DD HH:MM:SS" />
						</span>
					</label>
					<label>
						<input type="checkbox" name="andw_notices_target_blank" value="1" />
						<span class="checkbox-title"><?php esc_html_e( '新規タブで開く', 'andw-notices' ); ?></span>
					</label>
				</div>
			</fieldset>
			<?php
		}
	}

	/**
	 * クイック編集用スクリプトの読み込み
	 *
	 * @param string $hook 現在のページフック
	 */
	public static function enqueue_quick_edit_script( $hook ) {
		if ( 'edit.php' !== $hook ) {
			return;
		}

		global $post_type;
		if ( 'notices' !== $post_type ) {
			return;
		}

		wp_add_inline_script(
			'inline-edit-post',
			'
			(function($) {
				var wp_inline_edit = inlineEditPost.edit;
				inlineEditPost.edit = function(id) {
					wp_inline_edit.apply(this, arguments);

					var postId = 0;
					if (typeof(id) == "object") {
						postId = parseInt(this.getId(id));
					}

					if (postId > 0) {
						$.post(ajaxurl, {
							action: "get_notice_quick_edit_data",
							post_id: postId,
							nonce: "' . wp_create_nonce( 'andw_notices_quick_edit_nonce' ) . '"
						}, function(response) {
							if (response.success) {
								$("input[name=\"andw_notices_display_date\"]").val(response.data.display_date);
								$("input[name=\"andw_notices_target_blank\"]").prop("checked", response.data.target_blank == "1");
							}
						});
					}
				};
			})(jQuery);
			'
		);
	}

	/**
	 * AJAX: クイック編集データの取得
	 */
	public static function ajax_get_quick_edit_data() {
		check_ajax_referer( 'andw_notices_quick_edit_nonce', 'nonce' );

		$post_id = absint( $_POST['post_id'] );

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( -1, 403 );
		}

		$display_date = get_post_meta( $post_id, 'andw_notices_display_date', true );
		$target_blank = get_post_meta( $post_id, 'andw_notices_target_blank', true );

		wp_send_json_success(
			array(
				'display_date' => $display_date,
				'target_blank' => $target_blank,
			)
		);
	}
}