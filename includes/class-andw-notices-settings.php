<?php
/**
 * 管理設定ページ
 *
 * @package ANDW_Notices
 */

// このファイルに直接アクセスするのを防ぐ
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 設定ページクラス
 */
class ANDW_Notices_Settings {

	/**
	 * 設定グループ名
	 */
	const SETTINGS_GROUP = 'andw_notices_settings';

	/**
	 * 設定ページスラッグ
	 */
	const PAGE_SLUG = 'andw-notices-settings';

	/**
	 * 初期化
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_admin_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ) );
	}

	/**
	 * 管理メニューの追加
	 */
	public static function add_admin_menu() {
		add_submenu_page(
			'edit.php?post_type=notices',
			__( 'ANDW 新着お知らせ設定', 'andw-notices' ),
			__( '設定', 'andw-notices' ),
			'manage_options',
			self::PAGE_SLUG,
			array( __CLASS__, 'render_settings_page' )
		);
	}

	/**
	 * 設定の登録
	 */
	public static function register_settings() {
		register_setting(
			self::SETTINGS_GROUP,
			'andw_notices_settings',
			array(
				'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
				'default'           => self::get_default_settings(),
			)
		);

		add_settings_section(
			'andw_notices_general',
			__( '基本設定', 'andw-notices' ),
			array( __CLASS__, 'render_general_section' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			'default_excerpt_length',
			__( '抜粋の既定文字数', 'andw-notices' ),
			array( __CLASS__, 'render_excerpt_length_field' ),
			self::PAGE_SLUG,
			'andw_notices_general'
		);

		add_settings_field(
			'allowed_url_protocols',
			__( '許可するURLプロトコル', 'andw-notices' ),
			array( __CLASS__, 'render_url_protocols_field' ),
			self::PAGE_SLUG,
			'andw_notices_general'
		);

		add_settings_field(
			'date_format_override',
			__( '日付フォーマット上書き', 'andw-notices' ),
			array( __CLASS__, 'render_date_format_field' ),
			self::PAGE_SLUG,
			'andw_notices_general'
		);

		add_settings_section(
			'andw_notices_cache',
			__( 'キャッシュ設定', 'andw-notices' ),
			array( __CLASS__, 'render_cache_section' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			'cache_duration',
			__( 'キャッシュ有効期間（秒）', 'andw-notices' ),
			array( __CLASS__, 'render_cache_duration_field' ),
			self::PAGE_SLUG,
			'andw_notices_cache'
		);

		add_settings_field(
			'clear_cache',
			__( 'キャッシュクリア', 'andw-notices' ),
			array( __CLASS__, 'render_clear_cache_field' ),
			self::PAGE_SLUG,
			'andw_notices_cache'
		);

		// テンプレート設定セクション
		add_settings_section(
			'andw_notices_templates',
			__( 'テンプレート設定', 'andw-notices' ),
			array( __CLASS__, 'render_templates_section' ),
			self::PAGE_SLUG
		);

		// テンプレート管理は別途専用ページで行うため、ここでは説明のみ
		add_settings_field(
			'template_management',
			__( 'テンプレート管理', 'andw-notices' ),
			array( __CLASS__, 'render_template_management_field' ),
			self::PAGE_SLUG,
			'andw_notices_templates'
		);
	}

	/**
	 * 設定ページの表示
	 */
	public static function render_settings_page() {
		// nonce の確認とキャッシュクリア処理
		if ( isset( $_POST['clear_cache'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['andw_notices_clear_cache_nonce'] ) ), 'andw_notices_clear_cache' ) ) {
			if ( current_user_can( 'manage_options' ) ) {
				if ( class_exists( 'ANDW_Notices_Cache' ) ) {
					ANDW_Notices_Cache::clear_cache();
					add_settings_error( 'andw_notices_settings', 'cache_cleared', __( 'キャッシュをクリアしました。', 'andw-notices' ), 'updated' );
				}
			}
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php settings_errors( 'andw_notices_settings' ); ?>

			<form method="post" action="options.php">
				<?php
				settings_fields( self::SETTINGS_GROUP );
				do_settings_sections( self::PAGE_SLUG );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * 基本設定セクションの説明
	 */
	public static function render_general_section() {
		echo '<p>' . esc_html__( 'お知らせプラグインの基本設定を行います。', 'andw-notices' ) . '</p>';
	}

	/**
	 * キャッシュ設定セクションの説明
	 */
	public static function render_cache_section() {
		echo '<p>' . esc_html__( 'ブロック表示のキャッシュ設定を行います。', 'andw-notices' ) . '</p>';
	}

	/**
	 * 抜粋文字数フィールドの表示
	 */
	public static function render_excerpt_length_field() {
		$settings = self::get_settings();
		$value = $settings['default_excerpt_length'];
		?>
		<input type="number"
			   name="andw_notices_settings[default_excerpt_length]"
			   value="<?php echo esc_attr( $value ); ?>"
			   min="50"
			   max="500"
			   step="10"
			   class="small-text" />
		<p class="description">
			<?php esc_html_e( 'ブロックで抜粋を表示する際の既定文字数です。', 'andw-notices' ); ?>
		</p>
		<?php
	}

	/**
	 * URLプロトコルフィールドの表示
	 */
	public static function render_url_protocols_field() {
		$settings = self::get_settings();
		$protocols = $settings['allowed_url_protocols'];
		?>
		<fieldset>
			<legend class="screen-reader-text">
				<span><?php esc_html_e( '許可するURLプロトコル', 'andw-notices' ); ?></span>
			</legend>
			<label>
				<input type="checkbox"
					   name="andw_notices_settings[allowed_url_protocols][]"
					   value="http"
					   <?php checked( in_array( 'http', $protocols, true ) ); ?> />
				<?php esc_html_e( 'HTTP', 'andw-notices' ); ?>
			</label><br />
			<label>
				<input type="checkbox"
					   name="andw_notices_settings[allowed_url_protocols][]"
					   value="https"
					   <?php checked( in_array( 'https', $protocols, true ) ); ?> />
				<?php esc_html_e( 'HTTPS', 'andw-notices' ); ?>
			</label><br />
			<label>
				<input type="checkbox"
					   name="andw_notices_settings[allowed_url_protocols][]"
					   value="ftp"
					   <?php checked( in_array( 'ftp', $protocols, true ) ); ?> />
				<?php esc_html_e( 'FTP', 'andw-notices' ); ?>
			</label>
		</fieldset>
		<p class="description">
			<?php esc_html_e( '外部URLで許可するプロトコルを選択してください。', 'andw-notices' ); ?>
		</p>
		<?php
	}

	/**
	 * 日付フォーマットフィールドの表示
	 */
	public static function render_date_format_field() {
		$settings = self::get_settings();
		$value = $settings['date_format_override'];
		?>
		<input type="text"
			   name="andw_notices_settings[date_format_override]"
			   value="<?php echo esc_attr( $value ); ?>"
			   placeholder="<?php echo esc_attr( get_option( 'date_format' ) ); ?>"
			   class="regular-text" />
		<p class="description">
			<?php
			/* translators: %s: WordPress default date format */
			echo wp_kses_post( sprintf( __( '日付表示フォーマットを上書きします。空の場合はWordPressの設定（%s）を使用します。', 'andw-notices' ), '<code>' . get_option( 'date_format' ) . '</code>' ) );
			?>
			<br />
			<?php esc_html_e( '例: Y年n月j日、Y-m-d、j F Y', 'andw-notices' ); ?>
		</p>
		<?php
	}

	/**
	 * キャッシュ有効期間フィールドの表示
	 */
	public static function render_cache_duration_field() {
		$settings = self::get_settings();
		$value = $settings['cache_duration'];
		?>
		<input type="number"
			   name="andw_notices_settings[cache_duration]"
			   value="<?php echo esc_attr( $value ); ?>"
			   min="300"
			   max="86400"
			   step="300"
			   class="small-text" />
		<p class="description">
			<?php esc_html_e( 'ブロック表示のキャッシュ有効期間を秒単位で設定します。', 'andw-notices' ); ?>
		</p>
		<?php
	}

	/**
	 * キャッシュクリアフィールドの表示
	 */
	public static function render_clear_cache_field() {
		wp_nonce_field( 'andw_notices_clear_cache', 'andw_notices_clear_cache_nonce' );
		?>
		<input type="submit"
			   name="clear_cache"
			   value="<?php esc_attr_e( 'キャッシュをクリア', 'andw-notices' ); ?>"
			   class="button button-secondary" />
		<p class="description">
			<?php esc_html_e( 'お知らせブロックのキャッシュをすべてクリアします。', 'andw-notices' ); ?>
		</p>
		<?php
	}

	/**
	 * 設定のサニタイズ
	 *
	 * @param array $input 入力値
	 * @return array サニタイズされた値
	 */
	public static function sanitize_settings( $input ) {
		$sanitized = array();

		// 抜粋文字数
		$sanitized['default_excerpt_length'] = absint( $input['default_excerpt_length'] ?? 100 );
		if ( $sanitized['default_excerpt_length'] < 50 ) {
			$sanitized['default_excerpt_length'] = 50;
		}
		if ( $sanitized['default_excerpt_length'] > 500 ) {
			$sanitized['default_excerpt_length'] = 500;
		}

		// 許可プロトコル
		$allowed_protocols = array( 'http', 'https', 'ftp' );
		$sanitized['allowed_url_protocols'] = array();
		if ( isset( $input['allowed_url_protocols'] ) && is_array( $input['allowed_url_protocols'] ) ) {
			foreach ( $input['allowed_url_protocols'] as $protocol ) {
				if ( in_array( $protocol, $allowed_protocols, true ) ) {
					$sanitized['allowed_url_protocols'][] = $protocol;
				}
			}
		}

		// 日付フォーマット
		$sanitized['date_format_override'] = sanitize_text_field( $input['date_format_override'] ?? '' );

		// キャッシュ期間
		$sanitized['cache_duration'] = absint( $input['cache_duration'] ?? 3600 );
		if ( $sanitized['cache_duration'] < 300 ) {
			$sanitized['cache_duration'] = 300;
		}
		if ( $sanitized['cache_duration'] > 86400 ) {
			$sanitized['cache_duration'] = 86400;
		}

		return $sanitized;
	}

	/**
	 * デフォルト設定の取得
	 *
	 * @return array デフォルト設定
	 */
	public static function get_default_settings() {
		return array(
			'default_excerpt_length'  => 100,
			'allowed_url_protocols'   => array( 'http', 'https' ),
			'date_format_override'    => '',
			'cache_duration'          => 3600,
		);
	}

	/**
	 * 設定の取得
	 *
	 * @return array 設定値
	 */
	public static function get_settings() {
		$settings = get_option( 'andw_notices_settings', self::get_default_settings() );
		return wp_parse_args( $settings, self::get_default_settings() );
	}

	/**
	 * 個別設定の取得
	 *
	 * @param string $key 設定キー
	 * @param mixed  $default デフォルト値
	 * @return mixed 設定値
	 */
	public static function get_setting( $key, $default = null ) {
		$settings = self::get_settings();
		return $settings[ $key ] ?? $default;
	}

	/**
	 * テンプレートセクションの表示
	 */
	public static function render_templates_section() {
		?>
		<p><?php esc_html_e( 'お知らせブロックで使用するHTMLテンプレートを管理します。', 'andw-notices' ); ?></p>
		<?php
	}

	/**
	 * テンプレート管理フィールドの表示
	 */
	public static function render_template_management_field() {
		$templates = self::get_notice_templates();
		?>
		<div class="andw-template-management">
			<h4><?php esc_html_e( '利用可能なテンプレート', 'andw-notices' ); ?></h4>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'テンプレート名', 'andw-notices' ); ?></th>
						<th><?php esc_html_e( '説明', 'andw-notices' ); ?></th>
						<th><?php esc_html_e( '操作', 'andw-notices' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $templates as $key => $template ) : ?>
						<tr>
							<td><strong><?php echo esc_html( $template['name'] ); ?></strong></td>
							<td><?php echo esc_html( $template['description'] ); ?></td>
							<td>
								<?php if ( $template['builtin'] ) : ?>
									<span class="description"><?php esc_html_e( '組み込み', 'andw-notices' ); ?></span>
								<?php else : ?>
									<button type="button" class="button button-small" onclick="editTemplate('<?php echo esc_js( $key ); ?>')">
										<?php esc_html_e( '編集', 'andw-notices' ); ?>
									</button>
									<button type="button" class="button button-small button-link-delete" onclick="deleteTemplate('<?php echo esc_js( $key ); ?>')">
										<?php esc_html_e( '削除', 'andw-notices' ); ?>
									</button>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<h4><?php esc_html_e( '新しいテンプレートを作成', 'andw-notices' ); ?></h4>
			<form method="post" action="" class="andw-template-form">
				<?php wp_nonce_field( 'andw_notices_template_action', 'andw_notices_template_nonce' ); ?>

				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="template_name"><?php esc_html_e( 'テンプレート名', 'andw-notices' ); ?></label>
						</th>
						<td>
							<input type="text" id="template_name" name="template_name" class="regular-text" required />
							<p class="description"><?php esc_html_e( '英数字とアンダースコアのみ使用可能', 'andw-notices' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="template_description"><?php esc_html_e( '説明', 'andw-notices' ); ?></label>
						</th>
						<td>
							<input type="text" id="template_description" name="template_description" class="regular-text" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="template_html"><?php esc_html_e( 'HTMLテンプレート', 'andw-notices' ); ?></label>
						</th>
						<td>
							<textarea id="template_html" name="template_html" rows="10" cols="80" class="large-text code" required></textarea>
							<p class="description">
								<?php esc_html_e( '利用可能なプレースホルダー: {date}, {title}, {excerpt}, {event_date}, {link_url}, {link_target}', 'andw-notices' ); ?>
							</p>
						</td>
					</tr>
				</table>

				<p class="submit">
					<button type="submit" name="action" value="add_template" class="button button-primary">
						<?php esc_html_e( 'テンプレートを追加', 'andw-notices' ); ?>
					</button>
				</p>
			</form>
		</div>

		<script>
		function editTemplate(templateKey) {
			// テンプレート編集用のモーダルまたは別ページへの遷移
			alert('編集機能は今後実装予定です: ' + templateKey);
		}

		function deleteTemplate(templateKey) {
			if (confirm('このテンプレートを削除しますか？')) {
				// 削除処理
				var form = document.createElement('form');
				form.method = 'POST';
				form.innerHTML = '<input type="hidden" name="action" value="delete_template">' +
					'<input type="hidden" name="template_key" value="' + templateKey + '">' +
					'<input type="hidden" name="andw_notices_template_nonce" value="<?php echo esc_js( wp_create_nonce( 'andw_notices_template_action' ) ); ?>">';
				document.body.appendChild(form);
				form.submit();
			}
		}
		</script>
		<?php
	}

	/**
	 * お知らせテンプレートの取得
	 *
	 * @return array テンプレート配列
	 */
	public static function get_notice_templates() {
		$templates = get_option( 'andw_notices_templates', array() );

		// 組み込みテンプレートを追加
		$builtin_templates = array(
			'list' => array(
				'name' => __( 'リスト', 'andw-notices' ),
				'description' => __( '基本的なリスト表示', 'andw-notices' ),
				'html' => '<div class="andw-notice-item">{date}{event_date}<h3 class="andw-notice-title">{title}</h3><p class="andw-notice-excerpt">{excerpt}</p></div>',
				'builtin' => true,
			),
			'card' => array(
				'name' => __( 'カード', 'andw-notices' ),
				'description' => __( 'カード形式の表示', 'andw-notices' ),
				'html' => '<div class="andw-notice-card"><div class="andw-notice-header">{date}<h3 class="andw-notice-title">{title}</h3></div><div class="andw-notice-body">{event_date}<p class="andw-notice-excerpt">{excerpt}</p></div></div>',
				'builtin' => true,
			),
		);

		return array_merge( $builtin_templates, $templates );
	}

	/**
	 * テンプレートの保存
	 *
	 * @param string $key テンプレートキー
	 * @param array  $template テンプレートデータ
	 * @return bool 成功可否
	 */
	public static function save_template( $key, $template ) {
		$templates = get_option( 'andw_notices_templates', array() );
		$templates[ $key ] = $template;
		return update_option( 'andw_notices_templates', $templates );
	}

	/**
	 * テンプレートの削除
	 *
	 * @param string $key テンプレートキー
	 * @return bool 成功可否
	 */
	public static function delete_template( $key ) {
		$templates = get_option( 'andw_notices_templates', array() );
		if ( isset( $templates[ $key ] ) ) {
			unset( $templates[ $key ] );
			return update_option( 'andw_notices_templates', $templates );
		}
		return false;
	}

	/**
	 * REST APIルートの登録
	 */
	public static function register_rest_routes() {
		register_rest_route( 'wp/v2', '/andw-notices/templates', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'rest_get_templates' ),
			'permission_callback' => array( __CLASS__, 'rest_permission_check' ),
		) );
	}

	/**
	 * REST API権限チェック
	 *
	 * @return bool 権限の可否
	 */
	public static function rest_permission_check() {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * テンプレート一覧のREST APIレスポンス
	 *
	 * @param WP_REST_Request $request リクエスト
	 * @return WP_REST_Response レスポンス
	 */
	public static function rest_get_templates( $request ) {
		$templates = self::get_notice_templates();
		return rest_ensure_response( $templates );
	}
}