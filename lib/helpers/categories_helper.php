<?php
/**
 * Category domain helper.
 *
 * @package KnowlioDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class KnowlioCategoriesHelper
 */
class KnowlioCategoriesHelper {

	/**
	 * Per-request article counts, keyed by scope then category id.
	 *
	 * @var array
	 */
	private static $count_cache = array();

	/**
	 * Article counts for every category, in one query.
	 *
	 * The landing page asks each category how many articles it holds, and the
	 * card markup asks again. Done naively that is two queries per category;
	 * this collapses all of them into a single grouped count.
	 *
	 * @param bool $published_only Count only published articles.
	 *
	 * @return array Category id => count.
	 */
	public static function get_article_counts( bool $published_only = true ): array {
		$scope = $published_only ? 'published' : 'all';

		if ( isset( self::$count_cache[ $scope ] ) ) {
			return self::$count_cache[ $scope ];
		}

		global $wpdb;

		$select = 'SELECT category_id, COUNT(*) AS total FROM ' . KNOWLIO_TABLE_ARTICLES;

		if ( $published_only ) {
			$query = $wpdb->prepare(
				$select . ' WHERE status = %s GROUP BY category_id', // phpcs:ignore WordPress.DB.PreparedSQL -- Only the table name is interpolated, from a plugin constant; MySQL cannot bind an identifier as a placeholder.
				KNOWLIO_ARTICLE_STATUS_PUBLISHED
			);
		} else {
			$query = $select . ' GROUP BY category_id';
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Plugin-owned table; cached for the request in self::$count_cache and invalidated on save/delete.
		$rows = $wpdb->get_results( $query, ARRAY_A );

		$counts = array();

		foreach ( (array) $rows as $row ) {
			$counts[ (int) $row['category_id'] ] = (int) $row['total'];
		}

		self::$count_cache[ $scope ] = $counts;

		return $counts;
	}

	/**
	 * Drop the cached counts. Hooked to the model save/delete actions, so a
	 * screen that writes and then re-renders never shows a stale number.
	 *
	 * @param mixed $model Unused; present because this is an action callback.
	 */
	public static function flush_count_cache( $model = null ) {
		unset( $model );

		self::$count_cache = array();
	}

	/**
	 * Curated icon list.
	 *
	 * Restricting the choice keeps the stored value safe to print into a class
	 * attribute, and keeps the knowledge base looking consistent.
	 *
	 * @return array Dashicon class => label.
	 */
	public static function get_icons_list(): array {
		$icons = array(
			'dashicons-media-document' => __( 'Document', 'minidocs' ),
			'dashicons-book'           => __( 'Book', 'minidocs' ),
			'dashicons-lightbulb'      => __( 'Getting Started', 'minidocs' ),
			'dashicons-admin-generic'  => __( 'Settings', 'minidocs' ),
			'dashicons-admin-plugins'  => __( 'Integrations', 'minidocs' ),
			'dashicons-rest-api'       => __( 'API', 'minidocs' ),
			'dashicons-sos'            => __( 'Troubleshooting', 'minidocs' ),
			'dashicons-cart'           => __( 'Billing', 'minidocs' ),
			'dashicons-groups'         => __( 'Accounts', 'minidocs' ),
			'dashicons-shield'         => __( 'Security', 'minidocs' ),
			'dashicons-performance'    => __( 'Performance', 'minidocs' ),
			'dashicons-editor-code'    => __( 'Developers', 'minidocs' ),
		);

		/**
		 * Filters the category icon choices.
		 *
		 * @since 1.0.0
		 * @hook knowlio_category_icons
		 *
		 * @param array $icons Dashicon class => label.
		 */
		return (array) apply_filters( 'knowlio_category_icons', $icons );
	}

	/**
	 * Attribute string that opens the category editor panel.
	 *
	 * @param int|false $category_id Record id, or false for a new category.
	 *
	 * @return string
	 */
	public static function quick_edit_btn_atts( $category_id = false ): string {
		$params = array();

		if ( $category_id ) {
			$params['category_id'] = (int) $category_id;
		}

		return KnowlioUtilHelper::build_action_atts(
			KnowlioRouterHelper::build_route_name( 'categories', 'quick_edit' ),
			$params,
			'side-panel'
		);
	}

	/**
	 * Every category, ordered for display.
	 *
	 * @return KnowlioCategoryModel[]
	 */
	public static function get_all(): array {
		$categories = new KnowlioCategoryModel();

		return (array) $categories->order_by( 'order_number asc, name asc' )->get_results_as_models();
	}

	/**
	 * Categories that have at least one published article.
	 *
	 * @return KnowlioCategoryModel[]
	 */
	public static function get_populated(): array {
		$counts = self::get_article_counts( true );

		return array_values(
			array_filter(
				self::get_all(),
				static function ( $category ) use ( $counts ) {
					return ! empty( $counts[ (int) $category->id ] );
				}
			)
		);
	}

	/**
	 * Options for a category select field.
	 *
	 * @param string $placeholder_label Label for the "no category" option.
	 *
	 * @return array
	 */
	public static function get_options_for_select( string $placeholder_label = '' ): array {
		$options = array();

		if ( '' !== $placeholder_label ) {
			$options[0] = $placeholder_label;
		}

		foreach ( self::get_all() as $category ) {
			$options[ $category->id ] = $category->name;
		}

		return $options;
	}

	/**
	 * Total category count.
	 *
	 * @return int
	 */
	public static function count(): int {
		$categories = new KnowlioCategoryModel();

		return $categories->count();
	}
}
