<?php
/**
 * Settings screen.
 *
 * @package MiniDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MdSettingsController
 */
class MdSettingsController extends MdController {

	/**
	 * Settings persisted by this screen, with their sanitization rule.
	 *
	 * A setting not listed here cannot be written, however it is posted.
	 *
	 * @var array
	 */
	private $known_settings = array(
		'brand_name'         => 'text',
		'kb_title'           => 'text',
		'kb_subtitle'        => 'text',
		'kb_page_id'         => 'absint',
		'docs_layout'        => 'key',
		'records_per_page'   => 'absint',
		'disable_csv_export' => 'bool',
	);

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->views_folder = MINIDOCS_VIEWS_ABSPATH . 'settings/';

		$this->vars['page_header']   = __( 'Settings', 'minidocs' );
		$this->vars['breadcrumbs'][] = array(
			'label' => __( 'Settings', 'minidocs' ),
			'link'  => MdRouterHelper::build_link( array( 'settings', 'index' ) ),
		);
	}

	/**
	 * Settings form.
	 */
	public function index() {
		$this->vars['settings'] = array(
			'brand_name'         => MdSettingsHelper::get_brand_name(),
			'kb_title'           => MdSettingsHelper::get_setting( 'kb_title', __( 'How can we help?', 'minidocs' ) ),
			'kb_subtitle'        => MdSettingsHelper::get_setting( 'kb_subtitle', __( 'Search the documentation, or browse by topic below.', 'minidocs' ) ),
			'kb_page_id'         => (int) MdSettingsHelper::get_setting( 'kb_page_id', 0 ),
			'docs_layout'        => MdSettingsHelper::get_docs_layout(),
			'records_per_page'   => MdSettingsHelper::get_per_page(),
			'disable_csv_export' => MdSettingsHelper::is_on( 'disable_csv_export' ),
		);

		$this->vars['layouts']           = MdSettingsHelper::get_docs_layouts();
		$this->vars['pages']             = MdSettingsHelper::get_pages_for_select();
		$this->vars['roles']             = MdRolesHelper::get_wp_roles_list();
		$this->vars['capability_groups'] = MdRolesHelper::get_all_capabilities();
		$this->vars['role_map']          = MdRolesHelper::get_role_capability_map();

		$this->format_render( 'index' );
	}

	/**
	 * Persist the settings form.
	 */
	public function update() {
		$this->check_nonce( 'update_settings' );

		$submitted = (array) ( $this->params['setting'] ?? array() );

		foreach ( $this->known_settings as $name => $rule ) {
			if ( ! array_key_exists( $name, $submitted ) ) {
				continue;
			}

			$value = MdParamsHelper::sanitize_param( $submitted[ $name ], $rule );

			if ( 'bool' === $rule ) {
				$value = $value ? 'on' : 'off';
			}

			MdSettingsHelper::save_setting( $name, $value );
		}

		if ( isset( $this->params['role_capabilities'] ) ) {
			$role_map = array();

			foreach ( (array) $this->params['role_capabilities'] as $role => $capabilities ) {
				$role_map[ $role ] = array_filter( (array) $capabilities );
			}

			MdRolesHelper::save_role_capability_map( $role_map );
		}

		/**
		 * Fires after the settings form is saved.
		 *
		 * @since 1.0.0
		 * @hook minidocs_settings_saved
		 *
		 * @param array $submitted Submitted settings.
		 */
		do_action( 'minidocs_settings_saved', $submitted );

		$this->respond( MINIDOCS_STATUS_SUCCESS, __( 'Settings saved', 'minidocs' ) );
	}
}
