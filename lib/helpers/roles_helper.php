<?php
/**
 * Capability resolution.
 *
 * Controllers never call `current_user_can()` directly. They declare which
 * capability each action needs in `lib/config/capabilities_for_controllers.php`,
 * and this helper maps those plugin capabilities onto WordPress roles.
 *
 * @package MiniDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MdRolesHelper
 */
class MdRolesHelper {

	/**
	 * Cached capability map.
	 *
	 * @var array|null
	 */
	private static $map = null;

	/**
	 * The controller => capability configuration.
	 *
	 * @return array
	 */
	public static function get_capabilities_for_all_controllers(): array {
		if ( is_null( self::$map ) ) {
			$map = require MINIDOCS_LIB_ABSPATH . 'config/capabilities_for_controllers.php';

			/**
			 * Filters the controller capability map.
			 *
			 * @since 1.0.0
			 * @hook minidocs_capabilities_for_controllers
			 *
			 * @param array $map Controller class => capability config.
			 */
			self::$map = apply_filters( 'minidocs_capabilities_for_controllers', (array) $map );
		}

		return self::$map;
	}

	/**
	 * Capabilities required for one controller action.
	 *
	 * @param string $controller_class Controller class name.
	 * @param string $action           Action name.
	 *
	 * @return array
	 */
	public static function get_capabilities_required_for_controller_action( string $controller_class, string $action ): array {
		$map = self::get_capabilities_for_all_controllers();

		if ( ! isset( $map[ $controller_class ] ) ) {
			// Unknown controller: fall back to the most restrictive capability.
			return array( 'settings__edit' );
		}

		$config = $map[ $controller_class ];

		if ( isset( $config['per_action'][ $action ] ) ) {
			return (array) $config['per_action'][ $action ];
		}

		return (array) ( $config['default'] ?? array() );
	}

	/**
	 * All capabilities the plugin knows about, grouped for the settings screen.
	 *
	 * @return array
	 */
	public static function get_all_capabilities(): array {
		$capabilities = array(
			'articles'   => array( 'article__view', 'article__create', 'article__edit', 'article__delete' ),
			'categories' => array( 'category__view', 'category__create', 'category__edit', 'category__delete' ),
			'settings'   => array( 'settings__edit' ),
		);

		/**
		 * Filters the list of known capabilities.
		 *
		 * @since 1.0.0
		 * @hook minidocs_all_capabilities
		 *
		 * @param array $capabilities Group => capability names.
		 */
		return apply_filters( 'minidocs_all_capabilities', $capabilities );
	}

	/**
	 * Readable label for a capability slug.
	 *
	 * @param string $capability Capability name.
	 *
	 * @return string
	 */
	public static function get_capability_label( string $capability ): string {
		$parts = explode( '__', $capability );
		$verb  = $parts[1] ?? $capability;

		$labels = array(
			'view'   => __( 'View', 'minidocs' ),
			'create' => __( 'Create', 'minidocs' ),
			'edit'   => __( 'Edit', 'minidocs' ),
			'delete' => __( 'Delete', 'minidocs' ),
		);

		return $labels[ $verb ] ?? ucfirst( $verb );
	}

	/**
	 * Does the current WordPress user hold every given plugin capability?
	 *
	 * @param array $capabilities Capability names.
	 *
	 * @return bool
	 */
	public static function current_user_can( array $capabilities ): bool {
		if ( empty( $capabilities ) ) {
			return true;
		}

		// Administrators always pass, so nobody can lock themselves out.
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		$granted = self::get_granted_capabilities_for_current_user();

		foreach ( $capabilities as $capability ) {
			if ( ! in_array( $capability, $granted, true ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Capabilities granted to the current user by their WordPress roles.
	 *
	 * @return array
	 */
	public static function get_granted_capabilities_for_current_user(): array {
		$user = wp_get_current_user();

		if ( ! $user || ! $user->exists() ) {
			return array();
		}

		$role_map = self::get_role_capability_map();
		$granted  = array();

		foreach ( (array) $user->roles as $role ) {
			if ( ! empty( $role_map[ $role ] ) ) {
				$granted = array_merge( $granted, (array) $role_map[ $role ] );
			}
		}

		/**
		 * Filters the capabilities granted to the current user.
		 *
		 * @since 1.0.0
		 * @hook minidocs_granted_capabilities
		 *
		 * @param array   $granted Capability names.
		 * @param WP_User $user    Current user.
		 */
		return array_values( array_unique( apply_filters( 'minidocs_granted_capabilities', $granted, $user ) ) );
	}

	/**
	 * Stored role => capabilities map.
	 *
	 * @return array
	 */
	public static function get_role_capability_map(): array {
		$stored = get_option( 'minidocs_role_capabilities', null );

		if ( is_array( $stored ) ) {
			return $stored;
		}

		// Sensible defaults so the plugin is usable straight after activation.
		return array(
			'editor'      => array(
				'article__view',
				'article__create',
				'article__edit',
				'article__delete',
				'category__view',
				'category__create',
				'category__edit',
			),
			'author'      => array( 'article__view', 'article__create', 'article__edit', 'category__view' ),
			'contributor' => array( 'article__view', 'category__view' ),
		);
	}

	/**
	 * Persist the role => capabilities map.
	 *
	 * @param array $map Role => capability names.
	 *
	 * @return bool
	 */
	public static function save_role_capability_map( array $map ): bool {
		$all_capabilities = array();

		foreach ( self::get_all_capabilities() as $group ) {
			$all_capabilities = array_merge( $all_capabilities, (array) $group );
		}

		$clean = array();

		foreach ( $map as $role => $capabilities ) {
			$role = sanitize_key( $role );

			if ( ! $role ) {
				continue;
			}

			$clean[ $role ] = array_values( array_intersect( (array) $capabilities, $all_capabilities ) );
		}

		return (bool) update_option( 'minidocs_role_capabilities', $clean );
	}

	/**
	 * WordPress roles offered on the settings screen.
	 *
	 * @return array Role slug => display name.
	 */
	public static function get_wp_roles_list(): array {
		$roles = array();

		if ( ! function_exists( 'wp_roles' ) ) {
			return $roles;
		}

		foreach ( wp_roles()->roles as $slug => $role ) {
			if ( 'administrator' === $slug ) {
				continue; // Administrators are always allowed.
			}

			$roles[ $slug ] = translate_user_role( $role['name'] );
		}

		return $roles;
	}
}
