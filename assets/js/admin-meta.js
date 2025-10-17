/**
 * ANDW Notices Admin Meta Fields JavaScript
 * Select2統合型検索セレクトとメタフィールドの動的制御
 */
jQuery(document).ready(function($) {
	console.log("ANDW Notices: admin-meta.js 読み込み完了");

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

	// リンクタイプフィールドの表示切替
	function toggleLinkTypeFields() {
		var linkType = $("input[name=\"andw_notices_link_type\"]:checked").val();
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

		// 選択されたタイプのフィールドを確実に表示
		if (linkType) {
			var targetId = "#link-type-" + linkType;
			var $targetElement = $(targetId);
			console.log("ANDW Notices: 表示する要素:", targetId);

			// 強制的に表示（CSS競合対策）
			$targetElement.addClass("show").css({
				"display": "table-row",
				"visibility": "visible",
				"height": "auto",
				"opacity": "1"
			}).show();

			console.log("ANDW Notices: 表示後のスタイル:", $targetElement.attr("style"));

			// 代替表示方法（フォールバック）
			setTimeout(function() {
				if (!$targetElement.is(":visible")) {
					console.log("ANDW Notices: 標準方法で表示されないため、代替方法を試行");
					$targetElement.attr("style", "display:table-row!important;visibility:visible!important;height:auto!important;opacity:1!important;");
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

		// フィールド表示を更新
		toggleLinkTypeFields();

		// internalタイプの場合のみSelect2初期化
		if (linkType === "internal") {
			setTimeout(initSelect2, 100);
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
});