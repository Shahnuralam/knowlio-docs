<?php
/**
 * Knowledge base category.
 *
 * @package MiniDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MdCategoryModel
 */
class MdCategoryModel extends MdModel {

	/**
	 * Primary key.
	 *
	 * @var int
	 */
	public $id;

	/**
	 * Category name.
	 *
	 * @var string
	 */
	public $name;

	/**
	 * URL slug.
	 *
	 * @var string
	 */
	public $slug;

	/**
	 * Dashicon class shown on the category card.
	 *
	 * @var string
	 */
	public $icon;

	/**
	 * Short description shown under the name.
	 *
	 * @var string
	 */
	public $description;

	/**
	 * Manual sort position.
	 *
	 * @var int
	 */
	public $order_number;

	/**
	 * Creation timestamp.
	 *
	 * @var string
	 */
	public $created_at;

	/**
	 * Update timestamp.
	 *
	 * @var string
	 */
	public $updated_at;

	/**
	 * Constructor.
	 *
	 * @param int|false $id Optional record id.
	 */
	public function __construct( $id = false ) {
		parent::__construct();

		$this->table_name = MINIDOCS_TABLE_CATEGORIES;

		$this->nice_names = array(
			'name'         => __( 'Category Name', 'minidocs' ),
			'slug'         => __( 'Slug', 'minidocs' ),
			'icon'         => __( 'Icon', 'minidocs' ),
			'order_number' => __( 'Order', 'minidocs' ),
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
		return array( 'id', 'name', 'slug', 'icon', 'description', 'order_number' );
	}

	/**
	 * Columns written to the table.
	 *
	 * @param string $role Role.
	 *
	 * @return array
	 */
	protected function params_to_save( string $role = 'admin' ): array {
		return array( 'name', 'slug', 'icon', 'description', 'order_number' );
	}

	/**
	 * Per-property sanitization rules.
	 *
	 * @return array
	 */
	protected function params_to_sanitize(): array {
		return array(
			'name'         => 'text',
			'slug'         => 'slug',
			'icon'         => 'text',
			'description'  => 'textarea',
			'order_number' => 'absint',
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
			'slug' => array( 'presence', 'uniqueness' ),
		);
	}

	/**
	 * Fill in defaults before validation.
	 */
	protected function set_defaults() {
		if ( empty( $this->slug ) && ! empty( $this->name ) ) {
			$this->slug = MdUtilHelper::slugify( $this->name );
		}

		if ( empty( $this->icon ) ) {
			$this->icon = 'dashicons-media-document';
		}

		if ( ! isset( $this->order_number ) || '' === $this->order_number ) {
			$this->order_number = 0;
		}
	}

	/**
	 * Only allow icons from the curated list, so the value is always a safe
	 * CSS class even though it is printed into a class attribute.
	 */
	protected function before_save() {
		if ( ! array_key_exists( (string) $this->icon, MdCategoriesHelper::get_icons_list() ) ) {
			$this->icon = 'dashicons-media-document';
		}
	}

	/**
	 * Move this category's articles to Uncategorised, then delete it.
	 *
	 * @param int|false $id Record id.
	 *
	 * @return bool
	 */
	public function delete( $id = false ): bool {
		$id = $id ? absint( $id ) : absint( $this->id ?? 0 );

		if ( ! parent::delete( $id ) ) {
			return false;
		}

		// Orphaned articles stay readable rather than disappearing with the category.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$this->db->update(
			MINIDOCS_TABLE_ARTICLES,
			array( 'category_id' => 0 ),
			array( 'category_id' => $id ),
			array( '%d' ),
			array( '%d' )
		);

		return true;
	}

	/**
	 * Load a category by slug.
	 *
	 * @param string $slug Slug.
	 *
	 * @return static|null
	 */
	public function load_by_slug( string $slug ) {
		return $this->where( array( 'slug' => $slug ) )->set_limit( 1 )->get_results_as_models();
	}

	/**
	 * How many articles this category holds.
	 *
	 * @param bool $published_only Count only published articles.
	 *
	 * @return int
	 */
	public function get_articles_count( bool $published_only = false ): int {
		$articles = new MdArticleModel();
		$articles->where( array( 'category_id' => (int) $this->id ) );

		if ( $published_only ) {
			$articles->should_be_published();
		}

		return $articles->count();
	}

	/**
	 * Articles belonging to this category.
	 *
	 * @param bool $published_only Restrict to published articles.
	 * @param int  $limit          Optional row limit.
	 *
	 * @return MdArticleModel[]
	 */
	public function get_articles( bool $published_only = true, int $limit = 0 ): array {
		$articles = new MdArticleModel();
		$articles->where( array( 'category_id' => (int) $this->id ) );

		if ( $published_only ) {
			$articles->should_be_published();
		}

		$articles->order_by( 'order_number asc, title asc' );

		if ( $limit > 0 ) {
			$articles->set_limit( $limit );
		}

		return (array) $articles->get_results_as_models();
	}

	/**
	 * Icon class, guaranteed to be one of the curated options.
	 *
	 * @return string
	 */
	public function get_icon_class(): string {
		$icons = MdCategoriesHelper::get_icons_list();

		return array_key_exists( (string) $this->icon, $icons ) ? (string) $this->icon : 'dashicons-media-document';
	}
}
