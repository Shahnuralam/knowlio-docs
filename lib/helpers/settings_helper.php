<?php
/**
 * Settings storage, backed by a dedicated key/value table.
 *
 * @package KnowlioDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class KnowlioSettingsHelper
 */
class KnowlioSettingsHelper {

	/**
	 * In-request cache of every setting.
	 *
	 * @var array|null
	 */
	private static $cache = null;

	/**
	 * Settings mirrored into wp_options as `knowlio_{name}`.
	 *
	 * `uninstall.php` runs with the plugin's classes unloaded and is the code
	 * that decides whether to drop the very table these settings live in, so
	 * anything it needs has to be readable without them.
	 *
	 * @var array
	 */
	private static $mirrored_to_options = array( 'remove_data_on_uninstall' );

	/**
	 * Load all settings once per request.
	 *
	 * @return array
	 */
	private static function all(): array {
		global $wpdb;

		if ( ! is_null( self::$cache ) ) {
			return self::$cache;
		}

		self::$cache = array();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL -- Plugin-owned table, no user input, read once per request into self::$cache.
		$rows = $wpdb->get_results( 'SELECT name, value FROM ' . KNOWLIO_TABLE_SETTINGS, ARRAY_A );

		if ( $rows ) {
			foreach ( $rows as $row ) {
				self::$cache[ $row['name'] ] = $row['value'];
			}
		}

		return self::$cache;
	}

	/**
	 * Read a setting.
	 *
	 * @param string $name    Setting name.
	 * @param mixed  $default Fallback.
	 *
	 * @return mixed
	 */
	public static function get_setting( string $name, $default = false ) {
		$settings = self::all();

		$value = $settings[ $name ] ?? null;

		if ( is_null( $value ) || '' === $value ) {
			return $default;
		}

		/**
		 * Filters a setting value as it is read.
		 *
		 * @since 1.0.0
		 * @hook knowlio_setting_value
		 *
		 * @param mixed  $value Stored value.
		 * @param string $name  Setting name.
		 */
		return apply_filters( 'knowlio_setting_value', $value, $name );
	}

	/**
	 * Write a setting.
	 *
	 * @param string $name  Setting name.
	 * @param mixed  $value Value.
	 *
	 * @return bool
	 */
	public static function save_setting( string $name, $value ): bool {
		global $wpdb;

		$name = sanitize_key( $name );
		if ( ! $name ) {
			return false;
		}

		$value = is_array( $value ) ? wp_json_encode( $value ) : (string) $value;

		if ( in_array( $name, self::$mirrored_to_options, true ) ) {
			update_option( 'knowlio_' . $name, $value );
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
		$exists = $wpdb->get_var(
			$wpdb->prepare( 'SELECT id FROM ' . KNOWLIO_TABLE_SETTINGS . ' WHERE name = %s LIMIT 1', $name )
		);

		if ( $exists ) {
			$result = $wpdb->update( KNOWLIO_TABLE_SETTINGS, array( 'value' => $value ), array( 'id' => $exists ) );
		} else {
			$result = $wpdb->insert(
				KNOWLIO_TABLE_SETTINGS,
				array(
					'name'  => $name,
					'value' => $value,
				)
			);
		}
		// phpcs:enable

		self::$cache = null;

		return false !== $result;
	}

	/**
	 * Whether a boolean-ish setting is enabled.
	 *
	 * @param string $name Setting name.
	 *
	 * @return bool
	 */
	public static function is_on( string $name ): bool {
		return in_array( (string) self::get_setting( $name, '' ), array( 'on', '1', 'yes', 'true' ), true );
	}

	/**
	 * Brand name shown in the menu, side menu and headings.
	 *
	 * @return string
	 */
	public static function get_brand_name(): string {
		$name = (string) self::get_setting( 'brand_name', KNOWLIO_BRAND_NAME );

		/**
		 * Filters the brand name, for white-labelling.
		 *
		 * @since 1.0.0
		 * @hook knowlio_brand_name
		 *
		 * @param string $name Brand name.
		 */
		return apply_filters( 'knowlio_brand_name', $name );
	}

	/**
	 * Rows per page for admin tables.
	 *
	 * @return int
	 */
	public static function get_per_page(): int {
		$per_page = (int) self::get_setting( 'records_per_page', KNOWLIO_DEFAULT_PER_PAGE );

		return $per_page > 0 ? min( $per_page, 200 ) : KNOWLIO_DEFAULT_PER_PAGE;
	}

	/**
	 * Whether CSV export links are shown.
	 *
	 * @return bool
	 */
	public static function can_download_csv(): bool {
		return ! self::is_on( 'disable_csv_export' );
	}

	/**
	 * Schema version currently installed.
	 *
	 * @return string
	 */
	public static function get_db_version() {
		return get_option( 'knowlio_db_version', '' );
	}

	/**
	 * The frontend layout presets the admin can choose between.
	 *
	 * @return array Preset slug => label.
	 */
	public static function get_docs_layouts(): array {
		return array(
			'sidebar'  => __( 'Sidebar — left navigation, article, right table of contents', 'minidocs' ),
			'wide'     => __( 'Wide — the sidebar layout with a wider reading column', 'minidocs' ),
			'boxed'    => __( 'Boxed — a centred single column, no left navigation', 'minidocs' ),
			'magazine' => __( 'Magazine — bold landing hero and larger topic cards', 'minidocs' ),
		);
	}

	/**
	 * The selected frontend layout preset, always a valid slug.
	 *
	 * @return string
	 */
	public static function get_docs_layout(): string {
		$layout = (string) self::get_setting( 'docs_layout', 'sidebar' );

		return array_key_exists( $layout, self::get_docs_layouts() ) ? $layout : 'sidebar';
	}

	/**
	 * Published pages, for the "knowledge base page" select.
	 *
	 * @return array Page id => title.
	 */
	public static function get_pages_for_select(): array {
		$options = array();

		$pages = get_pages(
			array(
				'sort_column' => 'post_title',
				'number'      => 200,
			)
		);

		foreach ( (array) $pages as $page ) {
			$options[ $page->ID ] = $page->post_title ? $page->post_title : __( '(no title)', 'minidocs' );
		}

		return $options;
	}

	/**
	 * Public URL of an article, if the knowledge base page has been set.
	 *
	 * @param string $slug Article slug.
	 *
	 * @return string Empty string when no page is configured.
	 */
	public static function get_article_preview_url( string $slug ): string {
		$page_id = (int) self::get_setting( 'kb_page_id', 0 );

		if ( ! $page_id ) {
			return '';
		}

		$permalink = get_permalink( $page_id );

		if ( ! $permalink ) {
			return '';
		}

		return add_query_arg( 'knowlio_article', $slug, $permalink ) . '#knowlio-docs';
	}
}
