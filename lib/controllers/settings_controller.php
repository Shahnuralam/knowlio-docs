<?php
/**
 * Settings screen.
 *
 * @package KnowlioDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class KnowlioSettingsController
 */
class KnowlioSettingsController extends KnowlioController {

	/**
	 * Settings persisted by this screen, with their sanitization rule.
	 *
	 * A setting not listed here cannot be written, however it is posted.
	 *
	 * @var array
	 */
	private $known_settings = array(
		'brand_name'               => 'text',
		'kb_title'                 => 'text',
		'kb_subtitle'              => 'text',
		'kb_page_id'               => 'absint',
		'docs_layout'              => 'key',
		'records_per_page'         => 'absint',
		'disable_csv_export'       => 'bool',
		'remove_data_on_uninstall' => 'bool',
	);

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->views_folder = KNOWLIO_VIEWS_ABSPATH . 'settings/';

		$this->vars['page_header']   = __( 'Settings', 'minidocs' );
		$this->vars['breadcrumbs'][] = array(
			'label' => __( 'Settings', 'minidocs' ),
			'link'  => KnowlioRouterHelper::build_link( array( 'settings', 'index' ) ),
		);
	}

	/**
	 * Settings form.
	 */
	public function index() {
		$this->vars['settings'] = array(
			'brand_name'               => KnowlioSettingsHelper::get_brand_name(),
			'kb_title'                 => KnowlioSettingsHelper::get_setting( 'kb_title', __( 'How can we help?', 'minidocs' ) ),
			'kb_subtitle'              => KnowlioSettingsHelper::get_setting( 'kb_subtitle', __( 'Search the documentation, or browse by topic below.', 'minidocs' ) ),
			'kb_page_id'               => (int) KnowlioSettingsHelper::get_setting( 'kb_page_id', 0 ),
			'docs_layout'              => KnowlioSettingsHelper::get_docs_layout(),
			'records_per_page'         => KnowlioSettingsHelper::get_per_page(),
			'disable_csv_export'       => KnowlioSettingsHelper::is_on( 'disable_csv_export' ),
			'remove_data_on_uninstall' => KnowlioSettingsHelper::is_on( 'remove_data_on_uninstall' ),
		);

		$this->vars['layouts']           = KnowlioSettingsHelper::get_docs_layouts();
		$this->vars['pages']             = KnowlioSettingsHelper::get_pages_for_select();
		$this->vars['roles']             = KnowlioRolesHelper::get_wp_roles_list();
		$this->vars['capability_groups'] = KnowlioRolesHelper::get_all_capabilities();
		$this->vars['role_map']          = KnowlioRolesHelper::get_role_capability_map();

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

			$value = KnowlioParamsHelper::sanitize_param( $submitted[ $name ], $rule );

			if ( 'bool' === $rule ) {
				$value = $value ? 'on' : 'off';
			}

			KnowlioSettingsHelper::save_setting( $name, $value );
		}

		if ( isset( $this->params['role_capabilities'] ) ) {
			$role_map = array();

			foreach ( (array) $this->params['role_capabilities'] as $role => $capabilities ) {
				$role_map[ $role ] = array_filter( (array) $capabilities );
			}

			KnowlioRolesHelper::save_role_capability_map( $role_map );
		}

		/**
		 * Fires after the settings form is saved.
		 *
		 * @since 1.0.0
		 * @hook knowlio_settings_saved
		 *
		 * @param array $submitted Submitted settings.
		 */
		do_action( 'knowlio_settings_saved', $submitted );

		$this->respond( KNOWLIO_STATUS_SUCCESS, __( 'Settings saved', 'minidocs' ) );
	}
}
