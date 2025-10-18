/**
 * ANDW Notices Admin Meta Fields JavaScript
 * Select2統合型検索セレクトとメタフィールドの動的制御
 */
console.log("ANDW Notices: admin-meta.js ファイル開始 - " + new Date().toISOString());

// 最低限のデバッグオブジェクトを作成（エラー時でも利用可能）
window.andwNoticesDebug = window.andwNoticesDebug || {
	status: "initializing",
	error: null,
	// 即座に利用可能な基本機能
	getStatus: function() {
		console.log("ANDW Notices Debug Status:", this.status);
		console.log("Available functions:", Object.keys(this));
		return this.status;
	}
};

jQuery(document).ready(function($) {
	console.log("ANDW Notices: jQuery ready - admin-meta.js 読み込み完了 - " + new Date().toISOString());

	// Datepicker初期化（イベント日付フィールド用）
	$(".datepicker").datepicker({
		dateFormat: "yy-mm-dd",
		changeMonth: true,
		changeYear: true
	});

	// Select2統合型検索セレクトの初期化
	function initSelect2() {
		console.log("ANDW Notices: Select2初期化開始");

		var $select = $("#andw_notices_target_post_id");

		// 要素の存在確認
		if ($select.length === 0) {
			console.warn("ANDW Notices: セレクトボックス要素が見つかりません。");
			return;
		}

		// Select2ライブラリの存在確認
		if (typeof $.fn.select2 === 'undefined') {
			console.error("ANDW Notices: Select2ライブラリが読み込まれていません");
			console.log("ANDW Notices: フォールバック処理を試行します...");
			tryLoadSelect2Fallback();
			return;
		}

		// 重複初期化チェック
		if ($select.hasClass('select2-hidden-accessible')) {
			console.warn("ANDW Notices: Select2は既に初期化されています。一度破棄してから再初期化します。");
			try {
				$select.select2('destroy');
				console.log("ANDW Notices: 既存のSelect2インスタンスを破棄しました");
			} catch (error) {
				console.error("ANDW Notices: Select2破棄エラー:", error);
			}
		}

		// 要素の状態をログ出力
		console.log("ANDW Notices: Select2初期化前の要素状態:");
		console.log("  要素の可視性:", $select.is(':visible'));
		console.log("  要素の親要素:", $select.parent().attr('id'));
		console.log("  オプション数:", $select.find('option').length);
		console.log("  Select2バージョン:", $.fn.select2.defaults ? 'loaded' : 'unknown');

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
				// 改善されたmatcher関数（data属性活用）
				matcher: function(params, data) {
					// 検索語が空の場合は全て表示
					if ($.trim(params.term) === "") {
						return data;
					}

					// 検索語を小文字に変換
					var term = params.term.toLowerCase();

					// オプションのテキストとdata属性を検索対象にする
					var text = (data.text || "").toLowerCase();
					var element = data.element;
					var searchText = text;

					if (element) {
						// data属性の取得
						var searchTextAttr = $(element).data('search-text') || '';
						var postTitleAttr = $(element).data('post-title') || '';
						var postSlugAttr = $(element).data('post-slug') || '';

						// data-search-text, data-post-title, data-post-slug属性も検索対象
						searchText = (
							searchTextAttr + ' ' +
							postTitleAttr + ' ' +
							postSlugAttr + ' ' +
							text
						).toLowerCase();
					}

					// 部分一致検索
					return searchText.indexOf(term) > -1 ? data : null;
				}
			});

			// 選択イベント
			$select.on("select2:select", function(e) {
				var selectedValue = e.params.data.id;
				console.log("ANDW Notices: Select2で選択されたID:", selectedValue);
			});

			// 初期化後の状態確認
			console.log("ANDW Notices: Select2初期化完了");
			console.log("ANDW Notices: 初期化後の要素状態:");
			console.log("  Select2クラス:", $select.hasClass('select2-hidden-accessible'));
			console.log("  Select2コンテナ:", $select.next('.select2-container').length > 0);

			// デバッグ用：検索フィールドのイベントリスナーを追加
			$select.on('select2:opening', function () {
				console.log("ANDW Notices: Select2ドロップダウンを開いています");
			});

			$select.on('select2:open', function () {
				console.log("ANDW Notices: Select2ドロップダウンが開きました");
				// 検索フィールドの存在確認
				var $searchField = $('.select2-search__field');
				console.log("ANDW Notices: 検索フィールド要素数:", $searchField.length);
				if ($searchField.length > 0) {
					$searchField.on('input', function() {
						console.log("ANDW Notices: 検索フィールドに入力:", $(this).val());
					});
				}
			});

		} catch (error) {
			console.error("ANDW Notices: Select2初期化エラー:", error);
			console.warn("ANDW Notices: 通常のセレクトボックスとして動作します。");
		}
	}

	// リンクタイプフィールドの表示切替
	function toggleLinkTypeFields() {
		var linkType = $("input[name=\"andw_notices_link_type\"]:checked").val();
		console.log("ANDW Notices: リンクタイプ変更:", linkType);

		// デバッグ情報：利用可能なリンクタイプフィールドを確認
		console.log("ANDW Notices: 利用可能なlink-type-fieldクラスの要素:", $(".link-type-field").length);
		$(".link-type-field").each(function(index, element) {
			console.log("  " + (index + 1) + ":", $(element).attr("id"));
		});

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

		// 選択されたタイプのフィールドを確実に表示
		if (linkType) {
			var targetId = "#link-type-" + linkType;
			var $targetElement = $(targetId);
			console.log("ANDW Notices: 表示する要素:", targetId);
			console.log("ANDW Notices: 要素の存在:", $targetElement.length > 0);

			if ($targetElement.length === 0) {
				console.warn("ANDW Notices: 要素が見つかりません:", targetId);
				// すべてのIDに"link-type-"が含まれる要素を探す
				$('[id*="link-type-"]').each(function() {
					console.log("ANDW Notices: 発見された要素:", $(this).attr("id"));
				});
				return;
			}

			// 強制的に表示（CSS競合対策）
			$targetElement.addClass("show").css({
				"display": "table-row !important",
				"visibility": "visible !important",
				"height": "auto !important",
				"opacity": "1 !important"
			}).show();

			console.log("ANDW Notices: 表示後のスタイル:", $targetElement.attr("style"));

			// 内部ページの場合、Select2の初期化も実行
			if (linkType === "internal") {
				console.log("ANDW Notices: 内部ページ選択のためSelect2初期化を実行");
				setTimeout(function() {
					retrySelect2Init();
				}, 200);
			}

			// 代替表示方法（フォールバック）
			setTimeout(function() {
				if (!$targetElement.is(":visible")) {
					console.log("ANDW Notices: 標準方法で表示されないため、代替方法を試行");
					$targetElement.attr("style", "display:table-row!important;visibility:visible!important;height:auto!important;opacity:1!important;");

					// さらに強制的な方法
					$targetElement.removeClass("hidden").removeClass("hide").removeClass("d-none");
					$targetElement.parent().removeClass("hidden").removeClass("hide").removeClass("d-none");
				}
			}, 100);
		}
	}

	// イベント日付フィールドの表示切替
	function toggleEventFields() {
		var eventType = $("input[name=\"andw_notices_event_data[type]\"]:checked").val();

		$(".event-field").hide();

		if (eventType === "single") {
			$("#event-single-date").show();
		} else if (eventType === "period") {
			$("#event-period-dates").show();
		} else if (eventType === "free") {
			$("#event-free-text").show();
		}

		updateEventPreview();
	}

	// イベント日付プレビューの更新
	function updateEventPreview() {
		var eventType = $("input[name=\"andw_notices_event_data[type]\"]:checked").val();
		var previewText = "";

		switch (eventType) {
			case "single":
				var singleDate = $("#andw_notices_event_single_date").val();
				if (singleDate) {
					previewText = singleDate;
				}
				break;
			case "period":
				var startDate = $("#andw_notices_event_start_date").val();
				var endDate = $("#andw_notices_event_end_date").val();
				if (startDate && endDate) {
					previewText = startDate + " 〜 " + endDate;
				} else if (startDate) {
					previewText = startDate + " 〜";
				}
				break;
			case "free":
				var freeText = $("#andw_notices_event_free_text").val();
				if (freeText) {
					previewText = freeText;
				}
				break;
			default:
				previewText = "";
		}

		$("#event-preview").text(previewText || "プレビューなし");
	}

	// 新規タブ設定の手動変更を追跡
	$("#andw_notices_target_blank").on("change", function() {
		$(this).data("user-modified", true);
		console.log("ANDW Notices: ユーザーが新規タブ設定を手動で変更しました");
	});

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

	// Select2破棄処理の独立化
	function destroySelect2() {
		var $select = $("#andw_notices_target_post_id");
		if ($select.length > 0 && $select.hasClass("select2-hidden-accessible")) {
			try {
				$select.select2("destroy");
				console.log("ANDW Notices: Select2破棄完了");
			} catch (error) {
				console.warn("ANDW Notices: Select2破棄時にエラー:", error);
			}
		}
	}

	// Select2初期化の再試行処理
	function retrySelect2Init() {
		var retryCount = 0;
		var retryInterval = setInterval(function() {
			if ($("#link-type-internal").is(":visible") || retryCount >= 3) {
				clearInterval(retryInterval);
				if ($("#link-type-internal").is(":visible")) {
					initSelect2();
				} else {
					console.warn("ANDW Notices: Select2初期化を諦めました（フィールドが表示されない）");
				}
			}
			retryCount++;
		}, 100);
	}

	// リンクタイプ変更時にSelect2を再初期化
	$("input[name=\"andw_notices_link_type\"]").on("change", function() {
		var linkType = $(this).val();

		// 既存のSelect2を安全に破棄
		destroySelect2();

		// フィールド表示を更新
		toggleLinkTypeFields();

		// internalタイプの場合のSelect2初期化（遅延実行）
		if (linkType === "internal") {
			setTimeout(function() {
				if ($("#link-type-internal").is(":visible")) {
					initSelect2();
				} else {
					// フォールバック再試行（最大3回）
					console.log("ANDW Notices: フィールドが未表示のため再試行します");
					retrySelect2Init();
				}
			}, 200);
		}
	});

	// イベント表示プレビュー機能
	function updateEventPreview() {
		console.log("ANDW Notices: プレビュー更新開始");

		try {
			var $eventTypeRadio = $("input[name=\"andw_notices_event_data[type]\"]:checked");
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

	// イベント日付関連のイベントハンドラ
	$("input[name=\"andw_notices_event_data[type]\"]").on("change", toggleEventFields);
	$("#andw_notices_event_single_date, #andw_notices_event_start_date, #andw_notices_event_end_date, #andw_notices_event_free_text").on("input change", updateEventPreview);

	// プレビュー更新のイベントリスナー
	$("#andw_notices_display_preset").on("change", updateEventPreview);
	$("#andw_notices_event_label").on("input", updateEventPreview);
	$("input[name=\"andw_notices_event_type\"]").on("change", updateEventPreview);

	// ===== デバッグ用機能（本番時は削除） =====

	// デフォルトmatcherでのテスト機能
	function testWithDefaultMatcher() {
		console.log("ANDW Notices: デフォルトmatcherでのテスト開始");

		var $select = $("#andw_notices_target_post_id");
		if ($select.length === 0) {
			console.warn("ANDW Notices: テスト対象要素が見つかりません");
			return;
		}

		// 既存のSelect2を破棄
		if ($select.hasClass('select2-hidden-accessible')) {
			$select.select2('destroy');
		}

		// デフォルトmatcherで初期化
		$select.select2({
			placeholder: "投稿・ページを選択または検索...",
			allowClear: true,
			width: "100%",
			language: {
				noResults: function() {
					return "該当する投稿・ページが見つかりません";
				},
				searching: function() {
					return "検索中...";
				}
			}
			// カスタムmatcherを削除してデフォルトmatcherを使用
		});

		console.log("ANDW Notices: デフォルトmatcherでの初期化完了");
	}

	// ライブラリ情報の確認
	function checkLibraryInfo() {
		console.log("=== ANDW Notices: ライブラリ情報確認 ===");

		// jQuery情報
		console.log("jQuery:");
		console.log("  バージョン:", $ ? $.fn.jquery : 'not loaded');

		// Select2情報
		if (typeof $.fn.select2 !== 'undefined') {
			console.log("Select2:");
			console.log("  読み込み状態: loaded");
			console.log("  defaults存在:", !!$.fn.select2.defaults);

			// Select2のバージョン情報（可能な場合）
			if ($.fn.select2.amd && $.fn.select2.amd.require) {
				try {
					var version = $.fn.select2.amd.require('select2/core');
					console.log("  core情報:", version);
				} catch (e) {
					console.log("  core情報: 取得失敗");
				}
			}
		} else {
			console.log("Select2: not loaded");
		}

		// 読み込まれているスクリプト確認
		console.log("読み込まれているスクリプト:");
		$('script[src*="select2"]').each(function(index, element) {
			console.log("  " + (index + 1) + ":", $(element).attr('src'));
		});

		// JavaScript読み込み順序の検証
		console.log("JavaScript読み込み順序検証:");
		var scripts = [];
		$('script[src]').each(function(index, element) {
			var src = $(element).attr('src');
			if (src.indexOf('jquery') > -1 || src.indexOf('select2') > -1 || src.indexOf('admin-meta') > -1) {
				scripts.push({
					order: index + 1,
					src: src,
					async: $(element).attr('async') !== undefined,
					defer: $(element).attr('defer') !== undefined
				});
			}
		});

		scripts.forEach(function(script) {
			console.log("  " + script.order + ":", script.src);
			if (script.async) console.log("    → async属性あり");
			if (script.defer) console.log("    → defer属性あり");
		});

		// wp_enqueue_script依存関係の確認
		console.log("wp_enqueue_script依存関係:");
		if (window.wp_enqueue_data && window.wp_enqueue_data.scripts) {
			var relevantScripts = ['jquery', 'select2', 'select2-local', 'andw-notices-meta'];
			relevantScripts.forEach(function(handle) {
				if (window.wp_enqueue_data.scripts[handle]) {
					var script = window.wp_enqueue_data.scripts[handle];
					console.log("  " + handle + ":", script.deps || 'no deps');
				}
			});
		} else {
			console.log("  wp_enqueue_data.scripts: 利用不可");
		}

		console.log("=== End Library Info ===");
	}

	// ===== デバッグ用機能 =====

	// デバッグ用matcher関数（詳細ログ付き）
	function createDebugMatcher() {
		return function(params, data) {
			console.log("=== ANDW Notices: Select2 Matcher Debug ===");
			console.log("検索語:", params.term);
			console.log("データ:", data);

			// 検索語が空の場合は全て表示
			if ($.trim(params.term) === "") {
				console.log("検索語が空のため全件表示");
				return data;
			}

			// 検索語を小文字に変換
			var term = params.term.toLowerCase();

			// オプションのテキストとdata属性を検索対象にする
			var text = (data.text || "").toLowerCase();
			var element = data.element;
			var searchText = text;

			if (element) {
				// data属性の取得
				var searchTextAttr = $(element).data('search-text') || '';
				var postTitleAttr = $(element).data('post-title') || '';
				var postSlugAttr = $(element).data('post-slug') || '';

				// data-search-text, data-post-title, data-post-slug属性も検索対象
				searchText = (
					searchTextAttr + ' ' +
					postTitleAttr + ' ' +
					postSlugAttr + ' ' +
					text
				).toLowerCase();

				console.log("要素のdata属性:");
				console.log("  search-text:", searchTextAttr);
				console.log("  post-title:", postTitleAttr);
				console.log("  post-slug:", postSlugAttr);
				console.log("  element text:", text);
				console.log("  結合後searchText:", searchText);
			} else {
				console.log("要素なし、textのみ:", text);
			}

			// 部分一致検索
			var isMatch = searchText.indexOf(term) > -1;
			console.log("マッチ結果:", isMatch);
			console.log("=== End Matcher Debug ===");

			return isMatch ? data : null;
		};
	}

	// Select2をデバッグ用matcherで再初期化
	function initSelect2WithDebugMatcher() {
		console.log("ANDW Notices: デバッグ用matcherでSelect2初期化開始");

		var $select = $("#andw_notices_target_post_id");
		if ($select.length === 0) {
			console.warn("ANDW Notices: セレクトボックス要素が見つかりません");
			return;
		}

		// 既存のSelect2を破棄
		if ($select.hasClass('select2-hidden-accessible')) {
			$select.select2('destroy');
		}

		// デバッグ用matcherで初期化
		$select.select2({
			placeholder: "投稿・ページを選択または検索...",
			allowClear: true,
			width: "100%",
			language: {
				noResults: function() {
					return "該当する投稿・ページが見つかりません";
				},
				searching: function() {
					return "検索中...";
				}
			},
			matcher: createDebugMatcher()
		});

		console.log("ANDW Notices: デバッグ用matcher初期化完了");
	}

	// ===== フォールバック機能 =====

	// Select2ライブラリの動的読み込み（フォールバック）
	function tryLoadSelect2Fallback() {
		console.log("ANDW Notices: Select2フォールバック読み込みを開始");

		// 既にスクリプトタグが存在するかチェック
		var existingScript = $('script[src*="select2"]');
		if (existingScript.length > 0) {
			console.log("ANDW Notices: Select2スクリプトタグは存在しますが、ライブラリが未読み込みです");
			console.log("ANDW Notices: ページリロードを推奨します");

			// 3秒後に再試行
			setTimeout(function() {
				if (typeof $.fn.select2 !== 'undefined') {
					console.log("ANDW Notices: Select2が遅延読み込みされました。初期化を再実行します");
					initSelect2();
				} else {
					console.warn("ANDW Notices: Select2の遅延読み込みに失敗しました");
					showSelect2LoadError();
				}
			}, 3000);
			return;
		}

		// 手動でSelect2を読み込み
		var pluginUrl = extractPluginUrl();
		if (!pluginUrl) {
			console.error("ANDW Notices: プラグインURLの取得に失敗しました");
			showSelect2LoadError();
			return;
		}

		// CSSの動的読み込み
		$('<link>')
			.attr('type', 'text/css')
			.attr('rel', 'stylesheet')
			.attr('href', pluginUrl + 'assets/css/select2-4.1.0.min.css?fallback=1')
			.appendTo('head');

		// JSの動的読み込み
		$.getScript(pluginUrl + 'assets/js/select2-4.1.0.min.js?fallback=1')
			.done(function() {
				console.log("ANDW Notices: Select2フォールバック読み込み成功");
				setTimeout(function() {
					if (typeof $.fn.select2 !== 'undefined') {
						initSelect2();
					} else {
						console.error("ANDW Notices: Select2読み込み後も利用できません");
						showSelect2LoadError();
					}
				}, 500);
			})
			.fail(function() {
				console.error("ANDW Notices: Select2フォールバック読み込み失敗");
				showSelect2LoadError();
			});
	}

	// プラグインURLを現在のスクリプトタグから抽出
	function extractPluginUrl() {
		var scriptSrc = $('script[src*="admin-meta.js"]').attr('src');
		if (scriptSrc) {
			// assets/js/admin-meta.js部分を削除してプラグインのベースURLを取得
			return scriptSrc.replace(/assets\/js\/admin-meta\.js.*$/, '');
		}
		return null;
	}

	// Select2読み込みエラー時の処理
	function showSelect2LoadError() {
		console.warn("ANDW Notices: Select2が利用できません。通常のセレクトボックスとして動作します");
		var $select = $("#andw_notices_target_post_id");
		if ($select.length > 0) {
			$select.after('<p style="color: #d63638; font-size: 12px; margin: 5px 0;">⚠ 検索機能が無効になっています。ページをリロードしてください。</p>');
		}
	}

	// ページ読み込み完了時にライブラリ情報を確認
	checkLibraryInfo();

	// デバッグ用：コンソールからテスト実行可能にする
	window.andwNoticesDebug = {
		testWithDefaultMatcher: testWithDefaultMatcher,
		checkLibraryInfo: checkLibraryInfo,
		initSelect2: initSelect2,
		tryLoadSelect2Fallback: tryLoadSelect2Fallback,
		extractPluginUrl: extractPluginUrl,
		toggleLinkTypeFields: toggleLinkTypeFields,
		retrySelect2Init: retrySelect2Init,
		initSelect2WithDebugMatcher: initSelect2WithDebugMatcher,
		createDebugMatcher: createDebugMatcher
	};

	console.log("ANDW Notices: デバッグ機能が利用可能です");
	console.log("  window.andwNoticesDebug.testWithDefaultMatcher() - デフォルトmatcherテスト");
	console.log("  window.andwNoticesDebug.checkLibraryInfo() - ライブラリ情報確認");
	console.log("  window.andwNoticesDebug.initSelect2() - Select2手動初期化");
	console.log("  window.andwNoticesDebug.tryLoadSelect2Fallback() - Select2手動読み込み");
	console.log("  window.andwNoticesDebug.extractPluginUrl() - プラグインURL抽出");
	console.log("  window.andwNoticesDebug.toggleLinkTypeFields() - フィールド表示切替");
	console.log("  window.andwNoticesDebug.retrySelect2Init() - Select2初期化リトライ");
	console.log("  window.andwNoticesDebug.initSelect2WithDebugMatcher() - デバッグ用matcherで初期化");

	// デバッグオブジェクトのステータス更新
	window.andwNoticesDebug.status = "ready";
	console.log("ANDW Notices: デバッグオブジェクト初期化完了 - " + new Date().toISOString());
});