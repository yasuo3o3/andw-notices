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
			wp_enqueue_style(
				'jquery-ui-datepicker',
				plugins_url( 'assets/css/jquery-ui-1.12.1.css', ANDW_NOTICES_PLUGIN_FILE ),
				array(),
				'1.12.1'
			);

			// Select2 for searchable dropdowns - with fallback
			if ( wp_script_is( 'select2', 'registered' ) ) {
				wp_enqueue_script( 'select2' );
				wp_enqueue_style( 'select2' );
			} else {
				// Local Select2 resources (WordPress.org compliant)
				wp_enqueue_script(
					'select2-local',
					plugins_url( 'assets/js/select2-4.1.0.min.js', ANDW_NOTICES_PLUGIN_FILE ),
					array( 'jquery' ),
					'4.1.0',
					true
				);
				wp_enqueue_style(
					'select2-local',
					plugins_url( 'assets/css/select2-4.1.0.min.css', ANDW_NOTICES_PLUGIN_FILE ),
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

				/* イベント日付フィールド用スタイル（管理画面） */
				#andw-notices-settings .andw_notices_event {
					border: 1px solid #ddd;
					padding: 15px;
					background-color: #f9f9f9;
					border-radius: 4px;
					/* フロントエンド用Flexレイアウトを無効化 */
					display: block !important;
					flex-direction: unset !important;
					gap: unset !important;
					align-items: unset !important;
					justify-content: unset !important;
					flex-wrap: unset !important;
				}

				/* ラジオボタン選択部分のスタイル */
				.event-type-selection {
					margin-bottom: 20px;
					padding-bottom: 15px;
					border-bottom: 1px solid #ddd;
				}
				.event-type-selection fieldset {
					border: none;
					padding: 0;
					margin: 0;
				}
				.event-type-selection fieldset legend {
					padding: 0;
					margin: 0;
				}
				.event-type-selection fieldset label {
					display: inline-block;
					margin: 5px 15px 5px 0;
					font-weight: normal;
					white-space: nowrap;
				}

				/* 入力フィールド部分のスタイル */
				.andw_notices_event fieldset {
					border: none;
					padding: 0;
					margin: 0 0 15px 0;
				}
				.andw_notices_event fieldset legend {
					padding: 0;
					margin: 0;
				}
				.andw_notices_event fieldset label {
					display: block;
					margin: 5px 0;
					font-weight: normal;
				}
				.event-field {
					padding: 15px 0 10px 0;
					border-top: 1px solid #eee;
					margin-top: 10px;
				}
				.event-field:first-child {
					border-top: none;
					padding-top: 0;
					margin-top: 0;
				}
				.event-field label {
					font-weight: 600;
					margin-bottom: 5px;
					display: block;
				}
				.event-field input[type="text"] {
					margin-top: 5px;
				}
				.event-field .description {
					margin-top: 5px;
					color: #666;
					font-style: italic;
				}

				/* レスポンシブ対応 */
				@media (max-width: 768px) {
					.event-type-selection fieldset label {
						display: block;
						margin: 5px 0;
					}
				}

				/* フロントエンド用イベント日付スタイル基盤 */
				.andw_notices_event {
					/* CSS変数（カスタマイズ可能） */
					--layout-gap: 0.5rem;
					--layout-direction: row;
					--layout-align: flex-start;
					--layout-justify: flex-start;

					/* 基本レイアウト */
					display: flex;
					gap: var(--layout-gap);
					align-items: var(--layout-align);
					justify-content: var(--layout-justify);
					flex-wrap: nowrap;
				}

				/* レイアウトタイプ別 */
				.andw_notices_event[data-layout="horizontal"] {
					flex-direction: row;
				}

				.andw_notices_event[data-layout="vertical"] {
					flex-direction: column;
				}

				.andw_notices_event[data-layout="grid"] {
					display: grid;
					grid-template-columns: auto 1fr;
					gap: var(--layout-gap);
				}

				/* 配置（アライメント）別 */
				.andw_notices_event[data-alignment="start"] {
					align-items: flex-start;
				}

				.andw_notices_event[data-alignment="center"] {
					align-items: center;
				}

				.andw_notices_event[data-alignment="end"] {
					align-items: flex-end;
				}

				/* スタイル別プリセット */
				.andw_notices_event.compact {
					font-size: 0.875rem;
					gap: 0.25rem;
				}

				.andw_notices_event.badge {
					display: inline-flex;
					padding: 0.25rem 0.5rem;
					background-color: #f0f0f0;
					border-radius: 0.25rem;
					font-size: 0.8rem;
					gap: 0.125rem;
				}

				.andw_notices_event.card {
					flex-direction: column;
					padding: 1rem;
					border: 1px solid #e0e0e0;
					border-radius: 0.5rem;
					background-color: #fff;
					box-shadow: 0 1px 3px rgba(0,0,0,0.1);
				}

				.andw_notices_event.timeline {
					position: relative;
					padding-left: 2rem;
				}

				.andw_notices_event.timeline::before {
					content: "📅";
					position: absolute;
					left: 0;
					top: 0;
				}

				.andw_notices_event.minimal {
					gap: 0;
				}

				/* コンポーネント別スタイル */
				.andw_notices_event_label {
					font-weight: 600;
					color: #666;
				}

				.andw_notices_event_date {
					color: #333;
				}

				.andw_notices_event_date time {
					font-family: inherit;
				}

				/* 優先順位別 */
				.andw_notices_event[data-priority="date-first"] [data-component="date"] {
					order: 1;
				}

				.andw_notices_event[data-priority="date-first"] [data-component="label"] {
					order: 2;
				}

				.andw_notices_event[data-priority="label-first"] [data-component="label"] {
					order: 1;
				}

				.andw_notices_event[data-priority="label-first"] [data-component="date"] {
					order: 2;
				}

				/* イベントタイプ別カスタマイズ */
				.andw_notices_event[data-type="period"] .andw_notices_event_date {
					white-space: nowrap;
				}

				.andw_notices_event[data-type="text"] .andw_notices_event_date {
					font-style: italic;
				}

				/* レスポンシブ対応 */
				@media (max-width: 768px) {
					.andw_notices_event[data-layout="horizontal"] {
						flex-direction: column;
						align-items: flex-start;
					}

					.andw_notices_event.timeline {
						padding-left: 1.5rem;
					}
				}
			' );

			wp_add_inline_script(
				'jquery-ui-datepicker',
				'
				jQuery(document).ready(function($) {
					// デバッグ用ログ
					console.log("ANDW Notices: JavaScript初期化開始");

					// Datepicker初期化（イベント日付フィールド用）
					$(".datepicker").datepicker({
						dateFormat: "yy-mm-dd",
						changeMonth: true,
						changeYear: true
					});

					// イベント日付タイプ選択の処理
					function toggleEventFields() {
						var $selectedRadio = $("input[name=\"andw_notices_event_type\"]:checked");
						var eventType = $selectedRadio.val();

						console.log("ANDW Notices: イベントタイプ変更:", eventType);

						// すべてのイベントフィールドを非表示
						$(".event-field").hide();

						// 選択されたタイプに応じてフィールドを表示
						if (eventType && eventType !== "none") {
							// イベントラベルは全タイプで表示
							$("#event-label-field").show();

							// タイプ別のフィールドを表示
							if (eventType === "single") {
								$("#event-single-field").show();
								// datepickerの再初期化
								$("#andw_notices_event_single_date").datepicker({
									dateFormat: "yy-mm-dd",
									changeMonth: true,
									changeYear: true
								});
							} else if (eventType === "period") {
								$("#event-period-field").show();
								// datepickerの再初期化
								$("#andw_notices_event_start_date, #andw_notices_event_end_date").datepicker({
									dateFormat: "yy-mm-dd",
									changeMonth: true,
									changeYear: true
								});
							} else if (eventType === "text") {
								$("#event-text-field").show();
							}
						}
					}

					// イベントタイプラジオボタンの変更イベント
					$("input[name=\"andw_notices_event_type\"]").on("change", toggleEventFields);

					// イベント表示プレビュー機能
					function updateEventPreview() {
						console.log("ANDW Notices: プレビュー更新開始");

						try {
							var $eventTypeRadio = $("input[name=\"andw_notices_event_type\"]:checked");
							var eventType = $eventTypeRadio.length > 0 ? $eventTypeRadio.val() : "none";
							var eventLabel = $("#andw_notices_event_label").val() || "";
							var displayPreset = $("#andw_notices_display_preset").val() || "default";
							var $previewContainer = $("#event-preview");

							console.log("ANDW Notices: イベントタイプ =", eventType, "プリセット =", displayPreset);

							if ($previewContainer.length === 0) {
								console.warn("ANDW Notices: プレビューコンテナが見つかりません");
								return;
							}

							// イベント日付なしの場合
							if (eventType === "none" || !eventType) {
								$previewContainer.html("<em>イベント日付が設定されていません</em>");
								return;
							}

						// データを取得
						var eventData = {
							type: eventType,
							label: eventLabel || "イベント",
							single_date: $("#andw_notices_event_single_date").val() || "2024-03-15",
							start_date: $("#andw_notices_event_start_date").val() || "2024-03-15",
							end_date: $("#andw_notices_event_end_date").val() || "2024-03-20",
							free_text: $("#andw_notices_event_free_text").val() || "近日公開"
						};

							// プレビューHTMLを生成
							var previewHtml = generatePreviewHtml(eventData, displayPreset);
							$previewContainer.html(previewHtml);

							console.log("ANDW Notices: プレビュー更新完了");
						} catch (error) {
							console.error("ANDW Notices: プレビュー更新エラー:", error);
							$("#event-preview").html("<em>プレビューエラーが発生しました</em>");
						}
					}

					function generatePreviewHtml(eventData, preset) {
						var dateContent = "";
						var separator = "：";
						var containerClass = "andw_notices_event";
						var priority = "label-first";

						// プリセット別の設定
						switch (preset) {
							case "compact":
								containerClass += " compact";
								separator = " ";
								break;
							case "badge":
								containerClass += " badge";
								separator = "";
								priority = "date-first";
								break;
							case "card":
								containerClass += " card";
								break;
							case "timeline":
								containerClass += " timeline";
								priority = "date-first";
								break;
							case "minimal":
								containerClass += " minimal";
								separator = "";
								eventData.label = ""; // ラベルを非表示
								break;
						}

						// 日付コンテンツの生成
						switch (eventData.type) {
							case "single":
								dateContent = eventData.single_date ? "2024年3月15日" : "";
								break;
							case "period":
								if (eventData.start_date && eventData.end_date) {
									dateContent = "2024年3月15日 ～ 2024年3月20日";
								} else if (eventData.start_date) {
									dateContent = "2024年3月15日";
								}
								break;
							case "text":
								dateContent = eventData.free_text || "近日公開";
								break;
						}

						// HTML生成
						var labelHtml = eventData.label ?
							"<span class=\"andw_notices_event_label\" data-component=\"label\">" +
							eventData.label + separator + "</span>" : "";
						var dateHtml = dateContent ?
							"<span class=\"andw_notices_event_date\" data-component=\"date\">" +
							dateContent + "</span>" : "";

						var elements = [];
						if (priority === "date-first") {
							if (dateHtml) elements.push(dateHtml);
							if (labelHtml) elements.push(labelHtml);
						} else {
							if (labelHtml) elements.push(labelHtml);
							if (dateHtml) elements.push(dateHtml);
						}

						return "<div class=\"" + containerClass + "\" data-layout=\"horizontal\" data-style=\"" +
							   preset + "\" data-priority=\"" + priority + "\" data-type=\"" + eventData.type + "\">" +
							   elements.join("") + "</div>";
					}

					// プレビュー更新のイベントリスナー
					$("#andw_notices_display_preset").on("change", updateEventPreview);
					$("#andw_notices_event_label").on("input", updateEventPreview);
					$("#andw_notices_event_single_date").on("change", updateEventPreview);
					$("#andw_notices_event_start_date").on("change", updateEventPreview);
					$("#andw_notices_event_end_date").on("change", updateEventPreview);
					$("#andw_notices_event_free_text").on("input", updateEventPreview);
					$("input[name=\"andw_notices_event_type\"]").on("change", updateEventPreview);

					// リンクタイプ選択の処理
					function toggleLinkTypeFields() {
						var $selectedRadio = $("input[name=\"andw_notices_link_type\"]:checked");
						var linkType = $selectedRadio.val();

						console.log("ANDW Notices: リンクタイプ変更:", linkType);

						// すべてのリンクタイプフィールドを非表示
						$(".link-type-field").removeClass("show").hide();

						// 新規タブのデフォルト状態を設定（ユーザーが手動で変更していない場合のみ）
						var $targetBlankCheckbox = $("#andw_notices_target_blank");
						var isUserModified = $targetBlankCheckbox.data("user-modified");

						if (!isUserModified) {
							if (linkType === "external") {
								// 外部URLの場合はデフォルトでチェック
								$targetBlankCheckbox.prop("checked", true);
								console.log("ANDW Notices: 外部URLのため新規タブをON");
							} else if (linkType === "self" || linkType === "internal") {
								// 内部ページの場合はデフォルトでチェック解除
								$targetBlankCheckbox.prop("checked", false);
								console.log("ANDW Notices: 内部ページのため新規タブをOFF");
							}
						}

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

					// 新規タブチェックボックスの手動変更を追跡
					$("#andw_notices_target_blank").on("change", function() {
						$(this).data("user-modified", true);
						console.log("ANDW Notices: ユーザーが新規タブ設定を手動で変更しました");
					});

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
						// ページ読み込み時は自動設定として扱う（user-modifiedをfalseに設定）
						$("#andw_notices_target_blank").data("user-modified", false);

						toggleEventFields();
						toggleLinkTypeFields();
						updateEventPreview(); // 初期プレビューを表示

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

		// デバッグ情報（開発時のみ表示）
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			echo '<div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; margin-bottom: 10px;">';
			echo '<strong>デバッグ情報:</strong> メタボックスが正常に読み込まれました。投稿ID: ' . absint( $post->ID );
			echo '</div>';
		}

		$event_data = get_post_meta( $post->ID, self::META_PREFIX . 'event_data', true );
		$link_type = get_post_meta( $post->ID, self::META_PREFIX . 'link_type', true );
		$target_post_id = get_post_meta( $post->ID, self::META_PREFIX . 'target_post_id', true );
		$external_url = get_post_meta( $post->ID, self::META_PREFIX . 'external_url', true );
		$target_blank = get_post_meta( $post->ID, self::META_PREFIX . 'target_blank', true );

		// イベントデータの初期化（JSON形式）
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

		// デフォルト値
		if ( empty( $link_type ) ) {
			$link_type = 'self';
		}
		?>
		<table class="form-table">
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
							   id="andw_notices_target_blank"
							   name="andw_notices_target_blank"
							   value="1"
							   <?php checked( $target_blank, '1' ); ?> />
						<?php esc_html_e( '新規タブで開く（target="_blank"）', 'andw-notices' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'イベント日付', 'andw-notices' ); ?></th>
				<td>
					<div class="andw_notices_event">
						<!-- ラジオボタン選択部分 -->
						<div class="event-type-selection">
							<fieldset>
								<legend class="screen-reader-text"><?php esc_html_e( 'イベント日付の種類', 'andw-notices' ); ?></legend>
								<label>
									<input type="radio" name="andw_notices_event_type" value="none" <?php checked( $event_data['type'], 'none' ); ?> />
									<?php esc_html_e( 'イベント日付なし', 'andw-notices' ); ?>
								</label><br />
								<label>
									<input type="radio" name="andw_notices_event_type" value="single" <?php checked( $event_data['type'], 'single' ); ?> />
									<?php esc_html_e( '単一日付', 'andw-notices' ); ?>
								</label><br />
								<label>
									<input type="radio" name="andw_notices_event_type" value="period" <?php checked( $event_data['type'], 'period' ); ?> />
									<?php esc_html_e( '期間', 'andw-notices' ); ?>
								</label><br />
								<label>
									<input type="radio" name="andw_notices_event_type" value="text" <?php checked( $event_data['type'], 'text' ); ?> />
									<?php esc_html_e( '自由記述', 'andw-notices' ); ?>
								</label>
							</fieldset>
						</div>

						<!-- 入力フィールド部分 -->

						<!-- イベントラベル -->
						<div id="event-label-field" class="event-field" style="margin-top: 15px; display: none;">
							<label for="andw_notices_event_label">
								<?php esc_html_e( 'イベントラベル', 'andw-notices' ); ?>
							</label><br />
							<input type="text"
								   id="andw_notices_event_label"
								   name="andw_notices_event_label"
								   value="<?php echo esc_attr( $event_data['label'] ); ?>"
								   placeholder="<?php esc_attr_e( 'オープン日、開催日、など', 'andw-notices' ); ?>"
								   class="regular-text" />
							<p class="description">
								<?php esc_html_e( 'イベント日付の前に表示されるラベル（例：「開催日：」）', 'andw-notices' ); ?>
							</p>
						</div>

						<!-- 単一日付フィールド -->
						<div id="event-single-field" class="event-field" style="margin-top: 15px; display: none;">
							<label for="andw_notices_event_single_date">
								<?php esc_html_e( '日付', 'andw-notices' ); ?>
							</label><br />
							<input type="text"
								   id="andw_notices_event_single_date"
								   name="andw_notices_event_single_date"
								   value="<?php echo esc_attr( $event_data['single_date'] ); ?>"
								   placeholder="<?php esc_attr_e( 'YYYY-MM-DD', 'andw-notices' ); ?>"
								   class="regular-text datepicker" />
						</div>

						<!-- 期間フィールド -->
						<div id="event-period-field" class="event-field" style="margin-top: 15px; display: none;">
							<label for="andw_notices_event_start_date">
								<?php esc_html_e( '開始日', 'andw-notices' ); ?>
							</label><br />
							<input type="text"
								   id="andw_notices_event_start_date"
								   name="andw_notices_event_start_date"
								   value="<?php echo esc_attr( $event_data['start_date'] ); ?>"
								   placeholder="<?php esc_attr_e( 'YYYY-MM-DD', 'andw-notices' ); ?>"
								   class="regular-text datepicker" />
							<br /><br />
							<label for="andw_notices_event_end_date">
								<?php esc_html_e( '終了日', 'andw-notices' ); ?>
							</label><br />
							<input type="text"
								   id="andw_notices_event_end_date"
								   name="andw_notices_event_end_date"
								   value="<?php echo esc_attr( $event_data['end_date'] ); ?>"
								   placeholder="<?php esc_attr_e( 'YYYY-MM-DD', 'andw-notices' ); ?>"
								   class="regular-text datepicker" />
						</div>

						<!-- 自由記述フィールド -->
						<div id="event-text-field" class="event-field" style="margin-top: 15px; display: none;">
							<label for="andw_notices_event_free_text">
								<?php esc_html_e( '自由記述', 'andw-notices' ); ?>
							</label><br />
							<input type="text"
								   id="andw_notices_event_free_text"
								   name="andw_notices_event_free_text"
								   value="<?php echo esc_attr( $event_data['free_text'] ); ?>"
								   placeholder="<?php esc_attr_e( '例：2024年春頃、近日公開、など', 'andw-notices' ); ?>"
								   class="regular-text" />
						</div>

					</div>
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
		$nonce = isset( $_POST['andw_notices_meta_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['andw_notices_meta_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'andw_notices_meta_nonce' ) ) {
			return;
		}

		// 権限の確認
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// イベントデータの保存
		$event_data = array(
			'type' => 'none',
			'label' => '',
			'single_date' => '',
			'start_date' => '',
			'end_date' => '',
			'free_text' => ''
		);

		if ( isset( $_POST['andw_notices_event_type'] ) ) {
			$event_type = sanitize_text_field( wp_unslash( $_POST['andw_notices_event_type'] ) );
			$event_type = self::validate_event_type( $event_type );
			$event_data['type'] = $event_type;

			// イベントラベルの保存
			if ( isset( $_POST['andw_notices_event_label'] ) ) {
				$event_data['label'] = sanitize_text_field( wp_unslash( $_POST['andw_notices_event_label'] ) );
			}

			// タイプ別のデータ保存
			if ( $event_type === 'single' && isset( $_POST['andw_notices_event_single_date'] ) ) {
				$single_date = sanitize_text_field( wp_unslash( $_POST['andw_notices_event_single_date'] ) );
				$event_data['single_date'] = self::validate_date( $single_date );
			} elseif ( $event_type === 'period' ) {
				if ( isset( $_POST['andw_notices_event_start_date'] ) ) {
					$start_date = sanitize_text_field( wp_unslash( $_POST['andw_notices_event_start_date'] ) );
					$event_data['start_date'] = self::validate_date( $start_date );
				}
				if ( isset( $_POST['andw_notices_event_end_date'] ) ) {
					$end_date = sanitize_text_field( wp_unslash( $_POST['andw_notices_event_end_date'] ) );
					$event_data['end_date'] = self::validate_date( $end_date );
				}
			} elseif ( $event_type === 'text' && isset( $_POST['andw_notices_event_free_text'] ) ) {
				$event_data['free_text'] = sanitize_text_field( wp_unslash( $_POST['andw_notices_event_free_text'] ) );
			}
		}

		update_post_meta( $post_id, self::META_PREFIX . 'event_data', $event_data );

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
	 * イベントタイプの検証
	 *
	 * @param string $event_type イベントタイプ
	 * @return string 検証済みイベントタイプ
	 */
	private static function validate_event_type( $event_type ) {
		$allowed_types = array( 'none', 'single', 'period', 'text' );
		return in_array( $event_type, $allowed_types, true ) ? $event_type : 'none';
	}


	/**
	 * 日付の検証（Y-m-d形式）
	 *
	 * @param string $date 日付文字列
	 * @return string 検証済み日付文字列
	 */
	private static function validate_date( $date ) {
		if ( empty( $date ) ) {
			return '';
		}

		// Y-m-d 形式の検証
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			$timestamp = strtotime( $date );
			if ( $timestamp !== false ) {
				return $date;
			}
		}

		return '';
	}

	/**
	 * 日時の検証（従来の関数、下位互換のため保持）
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
	 * 外部URLの検証（register_meta用公開メソッド）
	 *
	 * @param string $url URL
	 * @return string 検証済みURL
	 */
	public static function validate_external_url( $url ) {
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
	 * イベントデータのサニタイゼーション
	 *
	 * @param array $event_data イベントデータ配列
	 * @return array サニタイズされたイベントデータ
	 */
	public static function sanitize_event_data( $event_data ) {
		if ( ! is_array( $event_data ) ) {
			return array(
				'type' => 'none',
				'label' => '',
				'single_date' => '',
				'start_date' => '',
				'end_date' => '',
				'free_text' => ''
			);
		}

		$sanitized = array(
			'type' => self::validate_event_type( $event_data['type'] ?? 'none' ),
			'label' => sanitize_text_field( $event_data['label'] ?? '' ),
			'single_date' => self::validate_date( $event_data['single_date'] ?? '' ),
			'start_date' => self::validate_date( $event_data['start_date'] ?? '' ),
			'end_date' => self::validate_date( $event_data['end_date'] ?? '' ),
			'free_text' => sanitize_text_field( $event_data['free_text'] ?? '' ),
		);

		return $sanitized;
	}

	/**
	 * メタフィールドをREST APIで利用可能にするため登録
	 */
	public static function register_meta_fields() {
		// イベントデータメタフィールド（新規）
		register_meta( 'post', 'andw_notices_event_data', array(
			'object_subtype'    => 'notices',
			'type'              => 'object',
			'single'            => true,
			'show_in_rest'      => array(
				'schema' => array(
					'type'       => 'object',
					'properties' => array(
						'type'        => array( 'type' => 'string' ),
						'label'       => array( 'type' => 'string' ),
						'single_date' => array( 'type' => 'string' ),
						'start_date'  => array( 'type' => 'string' ),
						'end_date'    => array( 'type' => 'string' ),
						'free_text'   => array( 'type' => 'string' ),
					),
				),
			),
			'sanitize_callback' => array( __CLASS__, 'sanitize_event_data' ),
		) );

		// 表示日メタフィールド（下位互換のため保持）
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
			'sanitize_callback' => array( __CLASS__, 'validate_external_url' ),
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