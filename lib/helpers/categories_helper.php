<?php
/**
 * Category domain helper.
 *
 * @package MiniDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MdCategoriesHelper
 */
class MdCategoriesHelper {

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
		 * @hook minidocs_category_icons
		 *
		 * @param array $icons Dashicon class => label.
		 */
		return (array) apply_filters( 'minidocs_category_icons', $icons );
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

		return MdUtilHelper::build_action_atts(
			MdRouterHelper::build_route_name( 'categories', 'quick_edit' ),
			$params,
			'side-panel'
		);
	}

	/**
	 * Every category, ordered for display.
	 *
	 * @return MdCategoryModel[]
	 */
	public static function get_all(): array {
		$categories = new MdCategoryModel();

		return (array) $categories->order_by( 'order_number asc, name asc' )->get_results_as_models();
	}

	/**
	 * Categories that have at least one published article.
	 *
	 * @return MdCategoryModel[]
	 */
	public static function get_populated(): array {
		return array_values(
			array_filter(
				self::get_all(),
				static function ( $category ) {
					return $category->get_articles_count( true ) > 0;
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
		$categories = new MdCategoryModel();

		return $categories->count();
	}
}
