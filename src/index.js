import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { megaphone as icon } from '@wordpress/icons';
import Edit from './edit';

// ブロックのメタデータをインポート
import metadata from '../blocks/notices-list/block.json';

// ブロックを登録
registerBlockType(metadata.name, {
	...metadata,
	icon,
	edit: Edit,
	// save コンポーネントは不要（SSRを使用）
	save: () => null,
});