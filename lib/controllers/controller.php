<?php
/**
 * Base controller.
 *
 * Gives every action: request params, nonce verification, capability checks,
 * a view/layout renderer, and one method (`format_render`) that emits either a
 * full HTML page or a JSON envelope depending on how the route was called.
 *
 * @package MiniDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MdController
 */
class MdController {

	/**
	 * Request params.
	 *
	 * @var array
	 */
	protected $params = array();

	/**
	 * Uploaded files.
	 *
	 * @var array
	 */
	protected $files = array();

	/**
	 * Layout used for HTML responses.
	 *
	 * @var string
	 */
	protected $layout = 'admin';

	/**
	 * Folder the controller's views live in.
	 *
	 * @var string
	 */
	protected $views_folder = MINIDOCS_VIEWS_ABSPATH;

	/**
	 * `html` or `json`.
	 *
	 * @var string
	 */
	protected $return_format = 'html';

	/**
	 * Classes added to the page wrapper.
	 *
	 * @var array
	 */
	protected $extra_css_classes = array( 'minidocs' );

	/**
	 * Actions reachable without the controller's capabilities.
	 *
	 * @var array
	 */
	public $action_access = array(
		'public' => array(),
	);

	/**
	 * Variables passed to views.
	 *
	 * @var array
	 */
	public $vars = array();

	/**
	 * Route currently being rendered.
	 *
	 * @var string
	 */
	public $route_name = '';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->params = MdParamsHelper::get_params();
		$this->files  = MdParamsHelper::get_files();

		$this->vars['page_header'] = MdSettingsHelper::get_brand_name();
		$this->vars['breadcrumbs'] = array(
			array(
				'label' => MdSettingsHelper::get_brand_name(),
				'link'  => MdRouterHelper::build_link( array( 'dashboard', 'index' ) ),
			),
		);
	}

	/* --------------------------------------------------------------------- */
	/* Access control                                                        */
	/* --------------------------------------------------------------------- */

	/**
	 * May the current user run this action?
	 *
	 * @param string $action Action name.
	 *
	 * @return bool
	 */
	public function can_current_user_access_action( string $action ): bool {
		if ( in_array( $action, (array) $this->action_access['public'], true ) ) {
			$can = true;
		} else {
			$can = MdRolesHelper::current_user_can( $this->get_capabilities_required_for_action( $action ) );
		}

		/**
		 * Filters the access decision for a controller action.
		 *
		 * @since 1.0.0
		 * @hook minidocs_can_current_user_access_action
		 *
		 * @param bool         $can        Decision.
		 * @param string       $action     Action name.
		 * @param MdController $controller Controller instance.
		 */
		return (bool) apply_filters( 'minidocs_can_current_user_access_action', $can, $action, $this );
	}

	/**
	 * Capabilities the given action requires.
	 *
	 * @param string $action Action name.
	 *
	 * @return array
	 */
	public function get_capabilities_required_for_action( string $action ): array {
		return MdRolesHelper::get_capabilities_required_for_controller_action( get_class( $this ), $action );
	}

	/**
	 * Verify a nonce or abort the request.
	 *
	 * @param string $action       Nonce action.
	 * @param string $custom_nonce Nonce value; defaults to the `_wpnonce` param.
	 */
	public function check_nonce( string $action, string $custom_nonce = '' ) {
		$nonce = $custom_nonce ? $custom_nonce : (string) ( $this->params['_wpnonce'] ?? '' );

		if ( wp_verify_nonce( $nonce, $action ) ) {
			return;
		}

		if ( 'json' === $this->get_return_format() ) {
			$this->send_json(
				array(
					'status'  => MINIDOCS_STATUS_ERROR,
					'message' => __( 'Invalid request. Please reload the page and try again.', 'minidocs' ),
				)
			);
		}

		wp_die( esc_html__( 'Invalid request.', 'minidocs' ), '', array( 'response' => 403 ) );
	}

	/* --------------------------------------------------------------------- */
	/* Response format                                                       */
	/* --------------------------------------------------------------------- */

	/**
	 * Set the response format.
	 *
	 * @param string $format `html` or `json`.
	 */
	public function set_return_format( string $format = 'html' ) {
		$this->return_format = ( 'json' === $format ) ? 'json' : 'html';
	}

	/**
	 * Current response format.
	 *
	 * @return string
	 */
	public function get_return_format(): string {
		return $this->return_format;
	}

	/**
	 * Set the layout, honouring an explicit `layout` request param.
	 *
	 * @param string $layout Layout name.
	 */
	public function set_layout( string $layout = 'admin' ) {
		$this->layout = isset( $this->params['layout'] ) ? (string) $this->params['layout'] : $layout;
	}

	/**
	 * Current layout.
	 *
	 * @return string
	 */
	public function get_layout(): string {
		return $this->layout;
	}

	/**
	 * Emit a JSON response and stop.
	 *
	 * @param array    $data        Payload.
	 * @param int|null $status_code HTTP status.
	 */
	public function send_json( array $data, $status_code = null ) {
		wp_send_json( $data, $status_code );
	}

	/* --------------------------------------------------------------------- */
	/* Rendering                                                             */
	/* --------------------------------------------------------------------- */

	/**
	 * Render a view for the current response format.
	 *
	 * `$view_name` may be a string, or an array with `html_view_name` and
	 * `json_view_name` when the two formats need different partials (a full
	 * table page vs just the refreshed rows).
	 *
	 * @param string|array $view_name        View name(s).
	 * @param array        $extra_vars       Extra view variables.
	 * @param array        $json_return_vars Extra keys merged into the JSON envelope.
	 */
	public function format_render( $view_name, array $extra_vars = array(), array $json_return_vars = array() ) {
		echo $this->format_render_return( $view_name, $extra_vars, $json_return_vars ); // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped
	}

	/**
	 * Same as `format_render()` but returns the markup.
	 *
	 * @param string|array $view_name        View name(s).
	 * @param array        $extra_vars       Extra view variables.
	 * @param array        $json_return_vars Extra keys merged into the JSON envelope.
	 *
	 * @return string
	 */
	public function format_render_return( $view_name, array $extra_vars = array(), array $json_return_vars = array() ): string {
		if ( 'json' === $this->get_return_format() ) {
			$name = is_array( $view_name ) ? ( $view_name['json_view_name'] ?? '' ) : $view_name;
			$html = $this->render( $this->get_view_uri( $name ), 'none', $extra_vars );

			$this->send_json(
				array_merge(
					array(
						'status'  => MINIDOCS_STATUS_SUCCESS,
						'message' => $html,
					),
					$json_return_vars
				)
			);
		}

		$name = is_array( $view_name ) ? ( $view_name['html_view_name'] ?? '' ) : $view_name;

		$this->extra_css_classes[]       = $this->generate_css_class( $name );
		$this->vars['extra_css_classes'] = $this->extra_css_classes;

		return $this->render( $this->get_view_uri( $name ), $this->get_layout(), $extra_vars );
	}

	/**
	 * Include a view, optionally wrapped in a layout.
	 *
	 * The layout receives the view path in `$view` and includes it.
	 *
	 * @param string $view       Absolute view path.
	 * @param string $layout     Layout name, or `none`.
	 * @param array  $extra_vars Extra view variables.
	 *
	 * @return string
	 */
	public function render( string $view, string $layout = 'none', array $extra_vars = array() ): string {
		$this->vars['route_name'] = $this->route_name;

		// Views address their data as plain local variables.
		extract( $this->vars );  // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		extract( $extra_vars );  // phpcs:ignore WordPress.PHP.DontExtract.extract_extract

		ob_start();

		if ( 'none' !== $layout ) {
			$layout_path = $this->get_safe_layout_path( $layout );

			if ( $layout_path ) {
				include $layout_path;
			} else {
				echo '<div class="md-empty-state"><h2>' . esc_html__( 'Invalid layout', 'minidocs' ) . '</h2></div>';
			}
		} elseif ( is_readable( $view ) ) {
			include $view;
		}

		return (string) ob_get_clean();
	}

	/**
	 * Absolute path of a view file.
	 *
	 * @param string $view_name View name.
	 *
	 * @return string
	 */
	public function get_view_uri( string $view_name ): string {
		$view_name = str_replace( array( '..', "\0" ), '', $view_name );

		return $this->views_folder . $view_name . '.php';
	}

	/**
	 * Resolve a layout name to a path inside the layouts folder, refusing
	 * anything that escapes it.
	 *
	 * @param string $layout Layout name.
	 *
	 * @return string|false
	 */
	private function get_safe_layout_path( string $layout ) {
		$layout = preg_replace( '/[^a-zA-Z0-9_-]/', '', $layout );

		if ( '' === $layout ) {
			return false;
		}

		$full_path = MINIDOCS_VIEWS_LAYOUTS_ABSPATH . $layout . '.php';
		$real_path = realpath( $full_path );
		$base_path = realpath( MINIDOCS_VIEWS_LAYOUTS_ABSPATH );

		if ( $real_path && $base_path && 0 === strpos( $real_path, $base_path ) ) {
			return $real_path;
		}

		return false;
	}

	/**
	 * Per-screen CSS class, e.g. `md-view-books-index`.
	 *
	 * @param string $view_name View name.
	 *
	 * @return string
	 */
	protected function generate_css_class( string $view_name ): string {
		$controller = strtolower( preg_replace( '/^Mp(\w+)Controller$/', '$1', static::class ) );

		return sanitize_html_class( 'md-view-' . $controller . '-' . $view_name );
	}

	/* --------------------------------------------------------------------- */
	/* Shared response helpers                                               */
	/* --------------------------------------------------------------------- */

	/**
	 * Standard success/error JSON envelope used by write actions.
	 *
	 * @param string $status  Status constant.
	 * @param mixed  $message Message or list of messages.
	 * @param array  $extra   Extra payload keys.
	 */
	protected function respond( string $status, $message, array $extra = array() ) {
		if ( 'json' !== $this->get_return_format() ) {
			return;
		}

		$this->send_json(
			array_merge(
				array(
					'status'  => $status,
					'message' => $message,
				),
				$extra
			)
		);
	}

	/**
	 * Pagination maths shared by every index action.
	 *
	 * @param int $total_records Total row count.
	 *
	 * @return array
	 */
	protected function build_pagination( int $total_records ): array {
		$per_page    = MdSettingsHelper::get_per_page();
		$page_number = max( 1, (int) ( $this->params['page_number'] ?? 1 ) );
		$total_pages = max( 1, (int) ceil( $total_records / $per_page ) );
		$page_number = min( $page_number, $total_pages );
		$offset      = ( $page_number - 1 ) * $per_page;

		return array(
			'per_page'            => $per_page,
			'current_page_number' => $page_number,
			'total_pages'         => $total_pages,
			'offset'              => $offset,
			'total_records'       => $total_records,
			'showing_from'        => $total_records ? $offset + 1 : 0,
			'showing_to'          => min( $offset + $per_page, $total_records ),
		);
	}
}
