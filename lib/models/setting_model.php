<?php
/**
 * Key/value settings row.
 *
 * Included mostly to show that the same base class serves a trivial two-column
 * table as well as it serves a full entity. Reads and writes normally go
 * through MdSettingsHelper, which caches.
 *
 * @package MiniDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MdSettingModel
 */
class MdSettingModel extends MdModel {

	/**
	 * Primary key.
	 *
	 * @var int
	 */
	public $id;

	/**
	 * Setting name.
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Setting value.
	 *
	 * @var string
	 */
	public $value;

	/**
	 * Constructor.
	 *
	 * @param int|false $id Optional record id.
	 */
	public function __construct( $id = false ) {
		parent::__construct();

		$this->table_name = MINIDOCS_TABLE_SETTINGS;

		$this->nice_names = array(
			'name'  => __( 'Setting Name', 'minidocs' ),
			'value' => __( 'Setting Value', 'minidocs' ),
		);

		if ( $id ) {
			$this->load_by_id( $id );
		}
	}

	/**
	 * Params accepted from a form submission.
	 *
	 * @param string $role Role.
	 *
	 * @return array
	 */
	protected function allowed_params( string $role = 'admin' ): array {
		return array( 'id', 'name', 'value' );
	}

	/**
	 * Columns written to the table.
	 *
	 * @param string $role Role.
	 *
	 * @return array
	 */
	protected function params_to_save( string $role = 'admin' ): array {
		return array( 'name', 'value' );
	}

	/**
	 * Per-property sanitization rules.
	 *
	 * @return array
	 */
	protected function params_to_sanitize(): array {
		return array(
			'name'  => 'key',
			'value' => 'text',
		);
	}

	/**
	 * Declarative validations.
	 *
	 * @return array
	 */
	protected function properties_to_validate(): array {
		return array(
			'name' => array( 'presence', 'uniqueness' ),
		);
	}
}
