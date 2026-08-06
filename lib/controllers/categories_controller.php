<?php
/**
 * Category CRUD.
 *
 * @package KnowlioDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class KnowlioCategoriesController
 */
class KnowlioCategoriesController extends KnowlioController {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->views_folder = KNOWLIO_VIEWS_ABSPATH . 'categories/';

		$this->vars['page_header']   = __( 'Categories', 'minidocs' );
		$this->vars['breadcrumbs'][] = array(
			'label' => __( 'Categories', 'minidocs' ),
			'link'  => KnowlioRouterHelper::build_link( array( 'categories', 'index' ) ),
		);
	}

	/**
	 * Listing screen.
	 */
	public function index() {
		global $wpdb;

		$query_args = array();
		$filter     = (array) ( $this->params['filter'] ?? array() );

		if ( ! empty( $filter['name'] ) ) {
			// esc_like() so that a name containing % or _ is searched for
			// literally rather than acting as a wildcard.
			$query_args['name LIKE'] = '%' . $wpdb->esc_like( sanitize_text_field( $filter['name'] ) ) . '%';
		}

		$counter = new KnowlioCategoryModel();
		$total   = $counter->where( $query_args )->count();

		$pagination = $this->build_pagination( $total );

		$categories = new KnowlioCategoryModel();
		$categories = $categories->where( $query_args )
			->order_by( 'order_number asc, name asc' )
			->set_limit( $pagination['per_page'] )
			->set_offset( $pagination['offset'] )
			->get_results_as_models();

		$this->vars = array_merge( $this->vars, $pagination );

		$this->vars['categories'] = $categories;
		$this->vars['filter']     = $filter;

		$this->format_render(
			array(
				'html_view_name' => 'index',
				'json_view_name' => '_table_body',
			),
			array(),
			array(
				'total_pages'   => $pagination['total_pages'],
				'showing_from'  => $pagination['showing_from'],
				'showing_to'    => $pagination['showing_to'],
				'total_records' => $pagination['total_records'],
			)
		);
	}

	/**
	 * Editor panel for a new or existing category.
	 */
	public function quick_edit() {
		$category_id = absint( $this->params['category_id'] ?? 0 );
		$category    = new KnowlioCategoryModel( $category_id );

		if ( $category_id && $category->is_new_record() ) {
			$this->respond( KNOWLIO_STATUS_ERROR, __( 'Category not found.', 'minidocs' ) );

			return;
		}

		$this->vars['category'] = $category;
		$this->vars['icons']    = KnowlioCategoriesHelper::get_icons_list();

		$this->set_layout( 'none' );
		$this->format_render( 'quick_edit' );
	}

	/**
	 * Create. Shares the write path with update.
	 */
	public function create() {
		$this->update();
	}

	/**
	 * Create or update.
	 */
	public function update() {
		$category_params = (array) ( $this->params['category'] ?? array() );
		$category_id     = absint( $category_params['id'] ?? 0 );

		if ( $category_id ) {
			$this->check_nonce( 'edit_category_' . $category_id );
		} else {
			$this->check_nonce( 'new_category' );
		}

		if ( $category_id && ! KnowlioRolesHelper::current_user_can( array( 'category__edit' ) ) ) {
			$this->respond( KNOWLIO_STATUS_ERROR, __( 'Not Authorized', 'minidocs' ) );

			return;
		}

		$category      = new KnowlioCategoryModel( $category_id );
		$is_new_record = $category->is_new_record();

		if ( $category_id && $is_new_record ) {
			$this->respond( KNOWLIO_STATUS_ERROR, __( 'Category not found.', 'minidocs' ) );

			return;
		}

		$category->set_data( $category_params );

		if ( ! $category->save() ) {
			$this->respond( KNOWLIO_STATUS_ERROR, $category->get_error_messages() );

			return;
		}

		$message = $is_new_record
			/* translators: %s: category name. */
			? sprintf( __( 'Category "%s" created', 'minidocs' ), $category->name )
			/* translators: %s: category name. */
			: sprintf( __( 'Category "%s" updated', 'minidocs' ), $category->name );

		$this->respond(
			KNOWLIO_STATUS_SUCCESS,
			$message,
			array(
				'record_id' => $category->id,
				'reload'    => true,
			)
		);
	}

	/**
	 * Delete a category. Its articles are moved to Uncategorised.
	 */
	public function destroy() {
		$category_id = absint( $this->params['id'] ?? 0 );

		$this->check_nonce( 'destroy_category_' . $category_id );

		if ( ! $category_id ) {
			$this->respond( KNOWLIO_STATUS_ERROR, __( 'Invalid category.', 'minidocs' ) );

			return;
		}

		$category = new KnowlioCategoryModel( $category_id );

		if ( $category->is_new_record() || ! $category->delete() ) {
			$this->respond( KNOWLIO_STATUS_ERROR, __( 'Error deleting category.', 'minidocs' ) );

			return;
		}

		$this->respond(
			KNOWLIO_STATUS_SUCCESS,
			__( 'Category deleted. Its articles were moved to Uncategorised.', 'minidocs' ),
			array( 'reload' => true )
		);
	}
}
