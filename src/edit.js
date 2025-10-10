import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import {
	InspectorControls,
	PanelColorSettings
} from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	SelectControl,
	ToggleControl,
	Spinner,
	Placeholder
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { megaphone } from '@wordpress/icons';
import { useEntityRecords } from '@wordpress/core-data';

const Edit = ({ attributes, setAttributes }) => {
	const {
		count,
		order,
		orderby,
		includeExternal,
		includeInternal,
		showDate,
		showTitle,
		showExcerpt,
		excerptLength,
		forceLinkOverride,
		openInNewTab,
		layout
	} = attributes;

	const blockProps = useBlockProps();

	// お知らせ投稿を取得
	const { records: notices, isResolving } = useEntityRecords('postType', 'notices', {
		per_page: count,
		status: 'publish',
		_embed: true,
		order,
		orderby: orderby === 'display_date' ? 'meta_value' : orderby,
		meta_key: orderby === 'display_date' ? 'andw_notices_display_date' : undefined
	});

	// プレビュー用のサンプルデータ（投稿が存在しない場合）
	const getSampleNotices = () => {
		return [
			{
				id: 1,
				title: { rendered: __('サンプルお知らせ1', 'andw-notices') },
				excerpt: { rendered: __('これはサンプルのお知らせです。実際の投稿を作成してください。', 'andw-notices') },
				date: new Date().toISOString(),
				meta: { andw_notices_display_date: new Date().toISOString().split('T')[0] }
			},
			{
				id: 2,
				title: { rendered: __('サンプルお知らせ2', 'andw-notices') },
				excerpt: { rendered: __('お知らせの内容がここに表示されます。', 'andw-notices') },
				date: new Date().toISOString(),
				meta: { andw_notices_display_date: new Date().toISOString().split('T')[0] }
			}
		];
	};

	const displayNotices = notices && notices.length > 0 ? notices : getSampleNotices();

	// 抜粋をトリムする関数
	const trimExcerpt = (excerpt, length) => {
		const stripped = excerpt.replace(/<[^>]+>/g, '');
		return stripped.length > length ? stripped.substring(0, length) + '...' : stripped;
	};

	// 日付をフォーマットする関数
	const formatDate = (dateString) => {
		const date = new Date(dateString);
		return date.toLocaleDateString('ja-JP', {
			year: 'numeric',
			month: '2-digit',
			day: '2-digit'
		});
	};

	return (
		<>
			<InspectorControls>
				<PanelBody title={__('表示設定', 'andw-notices')} initialOpen={true}>
					<RangeControl
						label={__('表示件数', 'andw-notices')}
						value={count}
						onChange={(value) => setAttributes({ count: value })}
						min={1}
						max={20}
					/>
					<SelectControl
						label={__('並び順', 'andw-notices')}
						value={order}
						options={[
							{ label: __('降順（新しい順）', 'andw-notices'), value: 'desc' },
							{ label: __('昇順（古い順）', 'andw-notices'), value: 'asc' }
						]}
						onChange={(value) => setAttributes({ order: value })}
					/>
					<SelectControl
						label={__('並び基準', 'andw-notices')}
						value={orderby}
						options={[
							{ label: __('表示日', 'andw-notices'), value: 'display_date' },
							{ label: __('公開日', 'andw-notices'), value: 'date' }
						]}
						onChange={(value) => setAttributes({ orderby: value })}
					/>
					<SelectControl
						label={__('レイアウト', 'andw-notices')}
						value={layout}
						options={[
							{ label: __('リスト', 'andw-notices'), value: 'list' },
							{ label: __('カード', 'andw-notices'), value: 'card' }
						]}
						onChange={(value) => setAttributes({ layout: value })}
					/>
				</PanelBody>

				<PanelBody title={__('フィルター設定', 'andw-notices')} initialOpen={false}>
					<ToggleControl
						label={__('外部リンクを含める', 'andw-notices')}
						checked={includeExternal}
						onChange={(value) => setAttributes({ includeExternal: value })}
					/>
					<ToggleControl
						label={__('内部リンクを含める', 'andw-notices')}
						checked={includeInternal}
						onChange={(value) => setAttributes({ includeInternal: value })}
					/>
				</PanelBody>

				<PanelBody title={__('表示要素', 'andw-notices')} initialOpen={false}>
					<ToggleControl
						label={__('日付を表示', 'andw-notices')}
						checked={showDate}
						onChange={(value) => setAttributes({ showDate: value })}
					/>
					<ToggleControl
						label={__('タイトルを表示', 'andw-notices')}
						checked={showTitle}
						onChange={(value) => setAttributes({ showTitle: value })}
					/>
					<ToggleControl
						label={__('抜粋を表示', 'andw-notices')}
						checked={showExcerpt}
						onChange={(value) => setAttributes({ showExcerpt: value })}
					/>
					{showExcerpt && (
						<RangeControl
							label={__('抜粋の文字数', 'andw-notices')}
							value={excerptLength}
							onChange={(value) => setAttributes({ excerptLength: value })}
							min={50}
							max={300}
							step={10}
						/>
					)}
				</PanelBody>

				<PanelBody title={__('リンク設定', 'andw-notices')} initialOpen={false}>
					<SelectControl
						label={__('リンク動作の上書き', 'andw-notices')}
						value={forceLinkOverride}
						options={[
							{ label: __('アイテム設定を尊重', 'andw-notices'), value: 'item' },
							{ label: __('すべて自身のページ', 'andw-notices'), value: 'self' },
							{ label: __('すべて外部URL', 'andw-notices'), value: 'external' },
							{ label: __('すべて内部ページ', 'andw-notices'), value: 'internal' },
							{ label: __('リンクなし', 'andw-notices'), value: 'null' }
						]}
						onChange={(value) => setAttributes({ forceLinkOverride: value })}
					/>
					<ToggleControl
						label={__('新規タブで開く（一括設定）', 'andw-notices')}
						checked={openInNewTab}
						onChange={(value) => setAttributes({ openInNewTab: value })}
						help={__('チェックしない場合は各アイテムの設定を使用', 'andw-notices')}
					/>
				</PanelBody>
			</InspectorControls>

			<div {...blockProps}>
				<div className="andw-notices-block-preview">
					<h3 className="andw-notices-block-title">
						{__('お知らせ一覧プレビュー', 'andw-notices')}
					</h3>

					{isResolving && (
						<Placeholder icon={megaphone} label={__('お知らせを読み込み中...', 'andw-notices')}>
							<Spinner />
						</Placeholder>
					)}

					{!isResolving && (
						<ul className={`andw-notices andw-notices-${layout}`}>
							{displayNotices.slice(0, count).map((notice) => (
								<li key={notice.id} className="andw-notice-item">
									{showDate && (
										<time className="andw-notice-date">
											{formatDate(notice.meta?.andw_notices_display_date || notice.date)}
										</time>
									)}
									{showTitle && (
										<h4 className="andw-notice-title">
											{notice.title.rendered}
										</h4>
									)}
									{showExcerpt && notice.excerpt && (
										<p className="andw-notice-excerpt">
											{trimExcerpt(notice.excerpt.rendered, excerptLength)}
										</p>
									)}
								</li>
							))}
						</ul>
					)}

					{!isResolving && (!notices || notices.length === 0) && (
						<p className="andw-notices-no-content">
							{__('お知らせが見つかりませんでした。投稿を作成してください。', 'andw-notices')}
						</p>
					)}
				</div>
			</div>
		</>
	);
};

export default Edit;