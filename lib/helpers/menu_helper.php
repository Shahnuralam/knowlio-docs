<?php
/**
 * Side menu construction.
 *
 * The plugin registers exactly one WordPress menu page; everything else lives
 * in this in-app side menu.
 *
 * @package KnowlioDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class KnowlioMenuHelper
 */
class KnowlioMenuHelper {

	/**
	 * Build the side menu.
	 *
	 * @return array
	 */
	public static function get_side_menu_items(): array {
		$items = array();

		$items[] = array(
			'label'        => __( 'Dashboard', 'minidocs' ),
			'link'         => KnowlioRouterHelper::build_link( array( 'dashboard', 'index' ) ),
			'icon'         => 'dashicons-chart-pie',
			'capabilities' => array( 'article__view' ),
		);

		$items[] = array( 'spacer' => true, 'small_label' => __( 'Knowledge Base', 'minidocs' ) );

		$items[] = array(
			'label'        => __( 'Articles', 'minidocs' ),
			'link'         => KnowlioRouterHelper::build_link( array( 'articles', 'index' ) ),
			'icon'         => 'dashicons-media-document',
			'capabilities' => array( 'article__view' ),
		);

		$items[] = array(
			'label'        => __( 'Categories', 'minidocs' ),
			'link'         => KnowlioRouterHelper::build_link( array( 'categories', 'index' ) ),
			'icon'         => 'dashicons-category',
			'capabilities' => array( 'category__view' ),
		);

		$items[] = array( 'spacer' => true, 'small_label' => __( 'Configuration', 'minidocs' ) );

		$items[] = array(
			'label'        => __( 'Settings', 'minidocs' ),
			'link'         => KnowlioRouterHelper::build_link( array( 'settings', 'index' ) ),
			'icon'         => 'dashicons-admin-generic',
			'capabilities' => array( 'settings__edit' ),
		);

		/**
		 * Filters the side menu items.
		 *
		 * @since 1.0.0
		 * @hook knowlio_side_menu_items
		 *
		 * @param array $items Menu items.
		 */
		$items = (array) apply_filters( 'knowlio_side_menu_items', $items );

		return self::filter_by_capabilities( $items );
	}

	/**
	 * Drop items the current user may not see, and collapse orphaned spacers.
	 *
	 * @param array $items Menu items.
	 *
	 * @return array
	 */
	private static function filter_by_capabilities( array $items ): array {
		$visible = array();

		foreach ( $items as $item ) {
			if ( ! empty( $item['spacer'] ) ) {
				$visible[] = $item;
				continue;
			}

			if ( ! empty( $item['capabilities'] ) && ! KnowlioRolesHelper::current_user_can( (array) $item['capabilities'] ) ) {
				continue;
			}

			$visible[] = $item;
		}

		// A spacer that ended up first, last, or next to another is noise.
		$clean = array();

		foreach ( $visible as $item ) {
			$is_spacer = ! empty( $item['spacer'] );

			if ( $is_spacer && ( empty( $clean ) || ! empty( end( $clean )['spacer'] ) ) ) {
				continue;
			}

			$clean[] = $item;
		}

		while ( ! empty( $clean ) && ! empty( end( $clean )['spacer'] ) ) {
			array_pop( $clean );
		}

		return $clean;
	}

	/**
	 * Is a menu item the one currently being viewed?
	 *
	 * @param array  $item       Menu item.
	 * @param string $route_name Route currently rendered.
	 *
	 * @return bool
	 */
	public static function is_item_active( array $item, string $route_name ): bool {
		if ( empty( $item['link'] ) || empty( $route_name ) ) {
			return false;
		}

		if ( KnowlioRouterHelper::link_has_route( $route_name, $item['link'] ) ) {
			return true;
		}

		// Keep the parent highlighted while any action of the same controller is open.
		$parts = wp_parse_url( $item['link'] );

		if ( empty( $parts['query'] ) ) {
			return false;
		}

		parse_str( $parts['query'], $query );

		if ( empty( $query['route_name'] ) ) {
			return false;
		}

		$item_controller  = strstr( $query['route_name'], '__', true );
		$route_controller = strstr( $route_name, '__', true );

		return $item_controller && $item_controller === $route_controller;
	}
}
