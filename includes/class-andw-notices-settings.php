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
}