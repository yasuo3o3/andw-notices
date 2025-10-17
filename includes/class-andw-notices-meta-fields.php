<?php
/**
 * メタフィールドとメタボックスの管理
 *
 * @package ANDW_Notices
 */

// このファイルに直接アクセスするのを防ぐ
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * メタフィールドクラス
 */
class ANDW_Notices_Meta_Fields {

	/**
	 * メタキーのプレフィックス
	 */
	const META_PREFIX = 'andw_notices_';

	/**
	 * 初期化
	 */
	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post', array( __CLASS__, 'save_meta_fields' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_admin_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_scripts' ) );
		add_action( 'init', array( __CLASS__, 'register_meta_fields' ) );
		// add_action( 'wp_ajax_andw_notices_search_posts', array( __CLASS__, 'ajax_search_posts' ) );
	}

	/**
	 * メタボックスの追加
	 */
	public static function add_meta_boxes() {
		add_meta_box(
			'andw-notices-settings',
			__( 'お知らせ設定', 'andw-notices' ),
			array( __CLASS__, 'render_meta_box' ),
			'notices',
			'normal',
			'high'
		);
	}

	/**
	 * 管理画面スクリプトの読み込み
	 */
	public static function enqueue_admin_scripts( $hook ) {
		global $post_type;

		if ( 'notices' !== $post_type ) {
			return;
		}

		if ( 'post.php' === $hook || 'post-new.php' === $hook ) {
			wp_enqueue_script( 'jquery-ui-datepicker' );
			wp_enqueue_style( 'jquery-ui-datepicker', 'https://code.jquery.com/ui/1.12.1/themes/ui-lightness/jquery-ui.css', array(), '1.12.1' );

			// Select2 for searchable dropdowns - with fallback
			if ( wp_script_is( 'select2', 'registered' ) ) {
				wp_enqueue_script( 'select2' );
				wp_enqueue_style( 'select2' );
			} else {
				// Fallback to CDN if WordPress Select2 is not available
				wp_enqueue_script(
					'select2-cdn',
					'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
					array( 'jquery' ),
					'4.1.0',
					true
				);
				wp_enqueue_style(
					'select2-cdn',
					'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
					array(),
					'4.1.0'
				);
			}

			// Add cache-busting version
			$script_version = '1.0.2-' . time();

			// CSS for meta box styling
			wp_add_inline_style( 'jquery-ui-datepicker', '
				.link-type-field {
					display: none !important;
				}
				.link-type-field.show {
					display: table-row !important;
					visibility: visible !important;
					height: auto !important;
					opacity: 1 !important;
					position: relative !important;
				}
				tr.link-type-field.show {
					display: table-row !important;
				}
				#link-type-internal.show,
				#link-type-external.show {
					display: table-row !important;
					visibility: visible !important;
				}
				.select2-container {
					max-width: 100%;
				}
				.select2-container--default .select2-selection--single {
					height: 30px;
					line-height: 28px;
				}
				.select2-container--default .select2-selection--single .select2-selection__rendered {
					padding-left: 8px;
					padding-right: 20px;
				}

				/* Select2統合型検索セレクト専用スタイル */
				.andw-notices-select2 {
					width: 100% !important;
				}
				.select2-container--default .select2-selection--single {
					height: 32px;
					line-height: 30px;
					border: 1px solid #8c8f94;
					border-radius: 4px;
				}
				.select2-container--default .select2-selection--single .select2-selection__rendered {
					padding-left: 8px;
					padding-right: 20px;
					color: #50575e;
				}
				.select2-container--default .select2-selection--single .select2-selection__arrow {
					height: 30px;
					right: 6px;
				}
				.select2-dropdown {
					border: 1px solid #8c8f94;
					border-radius: 4px;
					box-shadow: 0 2px 5px rgba(0,0,0,0.1);
				}
				.select2-search--dropdown .select2-search__field {
					border: 1px solid #8c8f94;
					border-radius: 4px;
					padding: 6px 8px;
				}
				.select2-container--default .select2-results__option--highlighted[aria-selected] {
					background-color: #2271b1;
					color: #fff;
				}
			' );

			wp_add_inline_script(
				'jquery-ui-datepicker',
				'
				jQuery(document).ready(function($) {
					// デバッグ用ログ
					console.log("ANDW Notices: JavaScript初期化開始");

					// Datepicker初期化
					$("#andw_notices_display_date").datepicker({
						dateFormat: "yy-mm-dd",
						changeMonth: true,
						changeYear: true
					});

					// リンクタイプ選択の処理
					function toggleLinkTypeFields() {
						var $selectedRadio = $("input[name=\"andw_notices_link_type\"]:checked");
						var linkType = $selectedRadio.val();

						console.log("ANDW Notices: リンクタイプ変更:", linkType);

						// すべてのリンクタイプフィールドを非表示
						$(".link-type-field").removeClass("show").hide();

						// 選択されたタイプのフィールドを表示
						if (linkType) {
							var targetId = "#link-type-" + linkType;
							var $targetElement = $(targetId);
							console.log("ANDW Notices: 表示する要素:", targetId);
							console.log("ANDW Notices: 要素の存在:", $targetElement.length);
							console.log("ANDW Notices: 要素の現在のスタイル:", $targetElement.attr("style"));

							// 強制的に表示（CSS競合対策）
							$targetElement.addClass("show").css({
								"display": "block",
								"visibility": "visible",
								"height": "auto",
								"opacity": "1"
							}).show();

							console.log("ANDW Notices: 表示後のスタイル:", $targetElement.attr("style"));
							console.log("ANDW Notices: クラス:", $targetElement.attr("class"));

							// 最終手段：親要素も確認・修正
							$targetElement.parents("tr").show();

							// 代替表示方法をテスト
							setTimeout(function() {
								if (!$targetElement.is(":visible")) {
									console.log("ANDW Notices: 標準方法で表示されないため、代替方法を試行");

									// より強力な表示方法
									$targetElement.attr("style", "display:table-row!important;visibility:visible!important;height:auto!important;opacity:1!important;").removeClass().addClass("show");

									// さらなる確認
									setTimeout(function() {
										if (!$targetElement.is(":visible")) {
											console.log("ANDW Notices: テーブル行でも表示されない、ブロック表示に変更");
											$targetElement.attr("style", "display:block!important;visibility:visible!important;height:auto!important;opacity:1!important;position:relative!important;");
										}
									}, 100);
								}
							}, 200);
						}
					}

					// ラジオボタンの変更イベント
					$("input[name=\"andw_notices_link_type\"]").on("change", toggleLinkTypeFields);

					// Select2統合型検索セレクトの初期化
					function initSelect2() {
						console.log("ANDW Notices: Select2初期化開始");

						// Select2の利用可能性をチェック
						if (typeof $ === "undefined" || typeof $.fn === "undefined" || typeof $.fn.select2 === "undefined") {
							console.warn("ANDW Notices: Select2またはjQueryが利用できません。通常のセレクトボックスとして動作します。");
							return;
						}

						var $select = $("#andw_notices_target_post_id");

						// 要素の存在確認
						if ($select.length === 0) {
							console.warn("ANDW Notices: セレクトボックス要素が見つかりません。");
							return;
						}

						// Select2で初期化（エラーハンドリング付き）
						try {
							$select.select2({
							placeholder: "投稿・ページを選択または検索...",
							allowClear: true,
							width: "100%",
							dropdownAutoWidth: false,
							language: {
								noResults: function() {
									return "該当する投稿・ページが見つかりません";
								},
								searching: function() {
									return "検索中...";
								},
								inputTooShort: function(args) {
									return "文字を入力して検索してください";
								},
								loadingMore: function() {
									return "読み込み中...";
								}
							},
							// 安全なmatcher関数（シンプル版）
							matcher: function(params, data) {
								// 検索語が空の場合は全て表示
								if ($.trim(params.term) === "") {
									return data;
								}

								// 検索語を小文字に変換
								var term = params.term.toLowerCase();

								// オプションのテキストを取得
								var text = (data.text || "").toLowerCase();

								// テキストにマッチするかチェック
								if (text.indexOf(term) > -1) {
									return data;
								}

								return null;
							}
						});

						// 選択イベント
						$select.on("select2:select", function(e) {
							var selectedValue = e.params.data.id;
							console.log("ANDW Notices: Select2で選択されたID:", selectedValue);
						});

						console.log("ANDW Notices: Select2初期化完了");

						} catch (error) {
							console.error("ANDW Notices: Select2初期化エラー:", error);
							console.warn("ANDW Notices: 通常のセレクトボックスとして動作します。");
						}
					}

					// 初期表示（少し遅延させて確実に実行）
					setTimeout(function() {
						toggleLinkTypeFields();

						// Select2初期化（internal選択時のみ）
						if ($("input[name=\"andw_notices_link_type\"]:checked").val() === "internal") {
							initSelect2();
						}

						console.log("ANDW Notices: 初期化完了");
					}, 100);

					// リンクタイプ変更時にSelect2を再初期化
					$("input[name=\"andw_notices_link_type\"]").on("change", function() {
						var linkType = $(this).val();

						// 既存のSelect2を破棄（エラーハンドリング付き）
						var $select = $("#andw_notices_target_post_id");
						if ($select.length > 0 && $select.hasClass("select2-hidden-accessible")) {
							try {
								$select.select2("destroy");
							} catch (error) {
								console.warn("ANDW Notices: Select2破棄時にエラー:", error);
							}
						}

						// internalタイプの場合のみSelect2初期化
						if (linkType === "internal") {
							setTimeout(initSelect2, 100);
						}
					});
				});
				'
			);
		}
	}

	/**
	 * メタボックスの表示
	 *
	 * @param WP_Post $post 投稿オブジェクト
	 */
	public static function render_meta_box( $post ) {
		wp_nonce_field( 'andw_notices_meta_nonce', 'andw_notices_meta_nonce' );

		$display_date = get_post_meta( $post->ID, self::META_PREFIX . 'display_date', true );
		$link_type = get_post_meta( $post->ID, self::META_PREFIX . 'link_type', true );
		$target_post_id = get_post_meta( $post->ID, self::META_PREFIX . 'target_post_id', true );
		$external_url = get_post_meta( $post->ID, self::META_PREFIX . 'external_url', true );
		$target_blank = get_post_meta( $post->ID, self::META_PREFIX . 'target_blank', true );

		// デフォルト値
		if ( empty( $link_type ) ) {
			$link_type = 'self';
		}
		?>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="andw_notices_display_date"><?php esc_html_e( '表示日', 'andw-notices' ); ?></label>
				</th>
				<td>
					<input type="text"
						   id="andw_notices_display_date"
						   name="andw_notices_display_date"
						   value="<?php echo esc_attr( $display_date ); ?>"
						   placeholder="<?php esc_attr_e( 'YYYY-MM-DD または YYYY-MM-DD HH:MM:SS', 'andw-notices' ); ?>"
						   class="regular-text" />
					<p class="description">
						<?php esc_html_e( '未設定の場合は公開日が使用されます。', 'andw-notices' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'リンクタイプ', 'andw-notices' ); ?></th>
				<td>
					<fieldset>
						<label>
							<input type="radio" name="andw_notices_link_type" value="self" <?php checked( $link_type, 'self' ); ?> />
							<?php esc_html_e( '自身のページ', 'andw-notices' ); ?>
						</label><br />
						<label>
							<input type="radio" name="andw_notices_link_type" value="internal" <?php checked( $link_type, 'internal' ); ?> />
							<?php esc_html_e( '内部ページ', 'andw-notices' ); ?>
						</label><br />
						<label>
							<input type="radio" name="andw_notices_link_type" value="external" <?php checked( $link_type, 'external' ); ?> />
							<?php esc_html_e( '外部URL', 'andw-notices' ); ?>
						</label>
					</fieldset>
				</td>
			</tr>
			<tr id="link-type-internal" class="link-type-field" style="display: none;">
				<th scope="row">
					<label for="andw_notices_target_post_id"><?php esc_html_e( '対象投稿・固定ページ', 'andw-notices' ); ?></label>
				</th>
				<td>
					<?php
					// カスタムセレクトボックスを作成（投稿と固定ページの両方を含む）
					$posts_and_pages = get_posts( array(
						'post_type'      => array( 'post', 'page' ),
						'post_status'    => 'publish',
						'numberposts'    => -1,
						'orderby'        => 'title',
						'order'          => 'ASC'
					) );

					// 現在選択されている投稿のタイトルを取得
					$selected_post_title = '';
					if ( $target_post_id ) {
						$selected_post = get_post( $target_post_id );
						if ( $selected_post ) {
							$post_type_label = $selected_post->post_type === 'page' ? __( '固定ページ', 'andw-notices' ) : __( '投稿', 'andw-notices' );
							$selected_post_title = $selected_post->post_title . ' (' . $post_type_label . ') - ' . $selected_post->post_name;
						}
					}
					?>

					<!-- Select2統合型検索セレクトボックス -->
					<select name="andw_notices_target_post_id"
							id="andw_notices_target_post_id"
							class="regular-text andw-notices-select2">
						<option value=""><?php esc_html_e( '投稿・ページを選択または検索...', 'andw-notices' ); ?></option>
						<?php foreach ( $posts_and_pages as $post_item ) :
							$post_type_label = $post_item->post_type === 'page' ? __( '固定ページ', 'andw-notices' ) : __( '投稿', 'andw-notices' );
							$display_text = $post_item->post_title . ' (' . $post_type_label . ') - ' . $post_item->post_name;
						?>
							<option value="<?php echo esc_attr( $post_item->ID ); ?>"
									<?php selected( $target_post_id, $post_item->ID ); ?>
									data-search-text="<?php echo esc_attr( strtolower( $display_text ) ); ?>"
									data-post-title="<?php echo esc_attr( $post_item->post_title ); ?>"
									data-post-slug="<?php echo esc_attr( $post_item->post_name ); ?>"
									data-post-type="<?php echo esc_attr( $post_type_label ); ?>">
								<?php echo esc_html( $display_text ); ?>
							</option>
						<?php endforeach; ?>
					</select>

					<p class="description">
						<?php esc_html_e( 'セレクトボックスをクリックして投稿・ページを選択するか、タイトル・スラッグで検索してください。', 'andw-notices' ); ?>
					</p>
				</td>
			</tr>
			<tr id="link-type-external" class="link-type-field" style="display: none;">
				<th scope="row">
					<label for="andw_notices_external_url"><?php esc_html_e( '外部URL', 'andw-notices' ); ?></label>
				</th>
				<td>
					<input type="url"
						   id="andw_notices_external_url"
						   name="andw_notices_external_url"
						   value="<?php echo esc_attr( $external_url ); ?>"
						   placeholder="https://example.com"
						   class="regular-text" />
					<p class="description">
						<?php esc_html_e( 'リンク先の外部URLを入力してください。', 'andw-notices' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( '新規タブで開く', 'andw-notices' ); ?></th>
				<td>
					<label>
						<input type="checkbox"
							   name="andw_notices_target_blank"
							   value="1"
							   <?php checked( $target_blank, '1' ); ?> />
						<?php esc_html_e( '新規タブで開く（target="_blank"）', 'andw-notices' ); ?>
					</label>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * メタフィールドの保存
	 *
	 * @param int $post_id 投稿ID
	 */
	public static function save_meta_fields( $post_id ) {
		// 自動保存の場合はスキップ
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// 投稿タイプが notices でない場合はスキップ
		if ( get_post_type( $post_id ) !== 'notices' ) {
			return;
		}

		// nonce の確認
		if ( ! isset( $_POST['andw_notices_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['andw_notices_meta_nonce'] ) ), 'andw_notices_meta_nonce' ) ) {
			return;
		}

		// 権限の確認
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// 表示日の保存
		if ( isset( $_POST['andw_notices_display_date'] ) ) {
			$display_date = sanitize_text_field( wp_unslash( $_POST['andw_notices_display_date'] ) );
			$display_date = self::validate_datetime( $display_date );
			update_post_meta( $post_id, self::META_PREFIX . 'display_date', $display_date );
		}

		// リンクタイプの保存
		if ( isset( $_POST['andw_notices_link_type'] ) ) {
			$link_type = sanitize_text_field( wp_unslash( $_POST['andw_notices_link_type'] ) );
			$link_type = self::validate_link_type( $link_type );
			update_post_meta( $post_id, self::META_PREFIX . 'link_type', $link_type );
		}

		// 対象投稿IDの保存
		if ( isset( $_POST['andw_notices_target_post_id'] ) ) {
			$target_post_id = absint( $_POST['andw_notices_target_post_id'] );
			update_post_meta( $post_id, self::META_PREFIX . 'target_post_id', $target_post_id );
		}

		// 外部URLの保存
		if ( isset( $_POST['andw_notices_external_url'] ) ) {
			$external_url = esc_url_raw( wp_unslash( $_POST['andw_notices_external_url'] ) );
			$external_url = self::validate_external_url( $external_url );
			update_post_meta( $post_id, self::META_PREFIX . 'external_url', $external_url );
		}

		// 新規タブ設定の保存
		$target_blank = isset( $_POST['andw_notices_target_blank'] ) ? '1' : '';
		update_post_meta( $post_id, self::META_PREFIX . 'target_blank', $target_blank );

		// キャッシュをクリア
		if ( class_exists( 'ANDW_Notices_Cache' ) ) {
			ANDW_Notices_Cache::clear_cache();
		}
	}

	/**
	 * 日時の検証
	 *
	 * @param string $datetime 日時文字列
	 * @return string 検証済み日時文字列
	 */
	private static function validate_datetime( $datetime ) {
		if ( empty( $datetime ) ) {
			return '';
		}

		// Y-m-d または Y-m-d H:i:s 形式の検証
		$patterns = array(
			'/^\d{4}-\d{2}-\d{2}$/',          // Y-m-d
			'/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', // Y-m-d H:i:s
		);

		foreach ( $patterns as $pattern ) {
			if ( preg_match( $pattern, $datetime ) ) {
				$timestamp = strtotime( $datetime );
				if ( $timestamp !== false ) {
					return $datetime;
				}
			}
		}

		return '';
	}

	/**
	 * リンクタイプの検証
	 *
	 * @param string $link_type リンクタイプ
	 * @return string 検証済みリンクタイプ
	 */
	private static function validate_link_type( $link_type ) {
		$allowed_types = array( 'self', 'internal', 'external' );
		return in_array( $link_type, $allowed_types, true ) ? $link_type : 'self';
	}

	/**
	 * 外部URLの検証
	 *
	 * @param string $url URL
	 * @return string 検証済みURL
	 */
	private static function validate_external_url( $url ) {
		if ( empty( $url ) ) {
			return '';
		}

		// 設定から許可プロトコルを取得
		$default_protocols = array( 'http', 'https' );
		if ( class_exists( 'ANDW_Notices_Settings' ) ) {
			$settings = ANDW_Notices_Settings::get_settings();
			$allowed_protocols = $settings['allowed_url_protocols'] ?? $default_protocols;
		} else {
			$allowed_protocols = $default_protocols;
		}

		// フィルターで最終調整可能
		$allowed_protocols = apply_filters( 'andw_notices_allowed_url_protocols', $allowed_protocols );
		$parsed_url = wp_parse_url( $url );

		if ( ! isset( $parsed_url['scheme'] ) || ! in_array( $parsed_url['scheme'], $allowed_protocols, true ) ) {
			return '';
		}

		return $url;
	}

	/**
	 * メタフィールドをREST APIで利用可能にするため登録
	 */
	public static function register_meta_fields() {
		// 表示日メタフィールド
		register_meta( 'post', 'andw_notices_display_date', array(
			'object_subtype'    => 'notices',
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'sanitize_text_field',
		) );

		// リンクタイプメタフィールド
		register_meta( 'post', 'andw_notices_link_type', array(
			'object_subtype'    => 'notices',
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'sanitize_text_field',
		) );

		// 外部URLメタフィールド
		register_meta( 'post', 'andw_notices_external_url', array(
			'object_subtype'    => 'notices',
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => array( __CLASS__, 'sanitize_external_url' ),
		) );

		// 内部投稿IDメタフィールド
		register_meta( 'post', 'andw_notices_target_post_id', array(
			'object_subtype'    => 'notices',
			'type'              => 'integer',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'absint',
		) );

		// 新規タブ設定メタフィールド
		register_meta( 'post', 'andw_notices_target_blank', array(
			'object_subtype'    => 'notices',
			'type'              => 'boolean',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'rest_sanitize_boolean',
		) );
	}

	/**
	 * AJAX投稿・ページ検索ハンドラ（一時的に無効化）
	 */
	/*
	public static function ajax_search_posts() {
		// nonce確認
		if ( ! wp_verify_nonce( $_POST['nonce'], 'andw_notices_search_nonce' ) ) {
			wp_die( 'Security check failed' );
		}

		$term = sanitize_text_field( $_POST['term'] );
		$results = array();

		if ( strlen( $term ) >= 1 ) {
			$posts = get_posts( array(
				'post_type'      => array( 'post', 'page' ),
				'post_status'    => 'publish',
				'numberposts'    => 20,
				's'              => $term,
				'orderby'        => 'title',
				'order'          => 'ASC'
			) );

			foreach ( $posts as $post ) {
				$post_type_label = $post->post_type === 'page' ? __( '固定ページ', 'andw-notices' ) : __( '投稿', 'andw-notices' );
				$results[] = array(
					'id'    => $post->ID,
					'label' => $post->post_title . ' (' . $post_type_label . ') - ' . $post->post_name,
					'value' => $post->post_title
				);
			}
		}

		wp_send_json( $results );
	}
	*/
}