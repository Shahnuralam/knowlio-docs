<?php
/**
 * String router.
 *
 * A route is a single string, `controller__action`, which maps to
 * `Mp{Controller}Controller::{action}()`. Three URL builders produce links for
 * the three entry points (admin page, admin-post, frontend).
 *
 * @package MiniDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MdRouterHelper
 */
class MdRouterHelper {

	/**
	 * Build a route name from its two parts.
	 *
	 * @param string $controller Controller slug, e.g. `books`.
	 * @param string $action     Action/method name, e.g. `index`.
	 *
	 * @return string
	 */
	public static function build_route_name( string $controller, string $action ): string {
		return $controller . '__' . $action;
	}

	/**
	 * Normalize a route passed either as a string or as `[controller, action]`.
	 *
	 * @param string|array $route Route.
	 *
	 * @return string
	 */
	private static function normalize_route( $route ): string {
		if ( is_array( $route ) && 2 === count( $route ) ) {
			return self::build_route_name( $route[0], $route[1] );
		}

		return (string) $route;
	}

	/**
	 * Link to an admin screen rendered by the single MiniDocs menu page.
	 *
	 * @param string|array $route  Route.
	 * @param array        $params Extra query args.
	 *
	 * @return string
	 */
	public static function build_link( $route, array $params = array() ): string {
		$args = array_merge(
			array(
				'page'       => MINIDOCS_ADMIN_PAGE_SLUG,
				'route_name' => self::normalize_route( $route ),
			),
			$params
		);

		return add_query_arg( $args, admin_url( 'admin.php' ) );
	}

	/**
	 * Link to `admin-post.php`, used for downloads and full page form posts.
	 *
	 * @param string|array $route  Route.
	 * @param array        $params Extra query args.
	 *
	 * @return string
	 */
	public static function build_admin_post_link( $route, array $params = array() ): string {
		$args = array_merge(
			array(
				'action'     => MINIDOCS_ROUTE_ACTION,
				'route_name' => self::normalize_route( $route ),
			),
			$params
		);

		return add_query_arg( $args, admin_url( 'admin-post.php' ) );
	}

	/**
	 * Frontend link handled through admin-ajax.
	 *
	 * @param string|array $route  Route.
	 * @param array        $params Extra query args.
	 *
	 * @return string
	 */
	public static function build_front_link( $route, array $params = array() ): string {
		$args = array_merge(
			array(
				'action'     => MINIDOCS_ROUTE_ACTION,
				'route_name' => self::normalize_route( $route ),
			),
			$params
		);

		return add_query_arg( $args, admin_url( 'admin-ajax.php' ) );
	}

	/**
	 * Whether a given link points at a given route. Used to highlight menu items.
	 *
	 * @param string $route_name Route currently being rendered.
	 * @param string $link       Link to test.
	 *
	 * @return bool
	 */
	public static function link_has_route( string $route_name, string $link ): bool {
		$parts = wp_parse_url( $link );
		if ( empty( $parts['query'] ) ) {
			return false;
		}

		parse_str( $parts['query'], $query );

		return isset( $query['route_name'] ) && $query['route_name'] === $route_name;
	}

	/**
	 * Resolve a route string into a controller instance and an action name.
	 *
	 * @param string $route_name Route.
	 *
	 * @return array Empty array when the route cannot be resolved.
	 */
	public static function convert_route_name_to_controller_and_action( string $route_name ): array {
		if ( ! preg_match( '/^[a-z0-9_]+__[a-z0-9_]+$/i', $route_name ) ) {
			return array();
		}

		list( $controller_slug, $action ) = explode( '__', $route_name, 2 );

		if ( empty( $controller_slug ) || empty( $action ) ) {
			return array();
		}

		$controller_class = 'Md' . str_replace( '_', '', ucwords( $controller_slug, '_' ) ) . 'Controller';

		if ( ! class_exists( $controller_class ) ) {
			return array();
		}

		$controller = new $controller_class();

		// Only public methods declared on the controller are routable; this keeps
		// inherited framework methods such as `render` off the public surface.
		if ( ! method_exists( $controller, $action ) ) {
			return array();
		}

		$reflection = new ReflectionMethod( $controller, $action );
		if ( ! $reflection->isPublic() || $reflection->isStatic() ) {
			return array();
		}
		if ( ! in_array( $reflection->getDeclaringClass()->getName(), array( $controller_class ), true ) ) {
			return array();
		}

		return array(
			'controller' => $controller,
			'action'     => $action,
		);
	}

	/**
	 * Dispatch a route.
	 *
	 * @param string $route_name    Route.
	 * @param string $return_format `html` or `json`.
	 */
	public static function call_by_route_name( string $route_name, string $return_format = 'html' ) {
		$route_data = self::convert_route_name_to_controller_and_action( $route_name );

		if ( empty( $route_data ) ) {
			if ( 'json' === $return_format ) {
				wp_send_json(
					array(
						'status'  => MINIDOCS_STATUS_ERROR,
						'message' => __( 'Page Not Found', 'minidocs' ),
					)
				);
			}
			echo '<div class="md-empty-state"><h2>' . esc_html__( 'Page Not Found', 'minidocs' ) . '</h2></div>';

			return;
		}

		$controller = $route_data['controller'];
		$action     = $route_data['action'];

		$controller->set_return_format( $return_format );
		$controller->route_name = $route_name;

		if ( ! $controller->can_current_user_access_action( $action ) ) {
			if ( 'json' === $controller->get_return_format() ) {
				$controller->send_json(
					array(
						'status'  => MINIDOCS_STATUS_ERROR,
						'message' => __( 'Not Authorized', 'minidocs' ),
					)
				);
			}
			echo '<div class="md-empty-state"><h2>' . esc_html__( 'Not Authorized', 'minidocs' ) . '</h2></div>';

			return;
		}

		$controller->$action();
	}

	/**
	 * Read a scalar param from GET or POST.
	 *
	 * @param string $name    Param name.
	 * @param mixed  $default Fallback.
	 *
	 * @return mixed
	 */
	public static function get_request_param( string $name, $default = false ) {
		// phpcs:disable WordPress.Security.NonceVerification
		if ( isset( $_GET[ $name ] ) && is_scalar( $_GET[ $name ] ) ) {
			return sanitize_text_field( wp_unslash( $_GET[ $name ] ) );
		}

		if ( isset( $_POST[ $name ] ) && is_scalar( $_POST[ $name ] ) ) {
			return sanitize_text_field( wp_unslash( $_POST[ $name ] ) );
		}
		// phpcs:enable

		return $default;
	}
}
