<?php
/**
 * Article CRUD.
 *
 * @package KnowlioDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class KnowlioArticlesController
 */
class KnowlioArticlesController extends KnowlioController {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->views_folder = KNOWLIO_VIEWS_ABSPATH . 'articles/';

		$this->vars['page_header']   = __( 'Documentation', 'minidocs' );
		$this->vars['breadcrumbs'][] = array(
			'label' => __( 'Articles', 'minidocs' ),
			'link'  => KnowlioRouterHelper::build_link( array( 'articles', 'index' ) ),
		);
	}

	/**
	 * Listing screen.
	 */
	public function index() {
		$query_args = $this->build_query_args_from_filters();

		if ( 'csv' === ( $this->params['download'] ?? '' ) ) {
			$this->export_csv( $query_args );

			return;
		}

		$counter = new KnowlioArticleModel();
		$total   = $counter->where( $query_args )->count();

		$pagination = $this->build_pagination( $total );

		$articles = new KnowlioArticleModel();
		$articles = $articles->where( $query_args )
			->order_by( $this->get_order_by() )
			->set_limit( $pagination['per_page'] )
			->set_offset( $pagination['offset'] )
			->get_results_as_models();

		$this->vars = array_merge( $this->vars, $pagination );

		$this->vars['articles']   = $articles;
		$this->vars['filter']     = (array) ( $this->params['filter'] ?? array() );
		$this->vars['statuses']   = KnowlioArticlesHelper::get_statuses_list();
		$this->vars['categories'] = KnowlioCategoriesHelper::get_options_for_select();

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
	 * Editor panel for a new or existing article.
	 */
	public function quick_edit() {
		$article_id = absint( $this->params['article_id'] ?? 0 );
		$article    = new KnowlioArticleModel( $article_id );

		if ( $article_id && $article->is_new_record() ) {
			$this->respond( KNOWLIO_STATUS_ERROR, __( 'Article not found.', 'minidocs' ) );

			return;
		}

		$this->vars['article']    = $article;
		$this->vars['categories'] = KnowlioCategoriesHelper::get_options_for_select( __( 'Uncategorised', 'minidocs' ) );

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
		$article_params = (array) ( $this->params['article'] ?? array() );
		$article_id     = absint( $article_params['id'] ?? 0 );

		if ( $article_id ) {
			$this->check_nonce( 'edit_article_' . $article_id );
		} else {
			$this->check_nonce( 'new_article' );
		}

		// An id turns a create into an edit, so re-derive the capability from
		// what the payload actually does rather than from the route called.
		if ( $article_id && ! KnowlioRolesHelper::current_user_can( array( 'article__edit' ) ) ) {
			$this->respond( KNOWLIO_STATUS_ERROR, __( 'Not Authorized', 'minidocs' ) );

			return;
		}

		$article       = new KnowlioArticleModel( $article_id );
		$is_new_record = $article->is_new_record();

		if ( $article_id && $is_new_record ) {
			$this->respond( KNOWLIO_STATUS_ERROR, __( 'Article not found.', 'minidocs' ) );

			return;
		}

		$article->set_data( $article_params );

		if ( ! $article->save() ) {
			$this->respond( KNOWLIO_STATUS_ERROR, $article->get_error_messages() );

			return;
		}

		if ( $is_new_record ) {
			/**
			 * Fires after an article is created.
			 *
			 * @since 1.0.0
			 * @hook knowlio_article_created
			 *
			 * @param KnowlioArticleModel $article Created article.
			 */
			do_action( 'knowlio_article_created', $article );
		} else {
			/**
			 * Fires after an article is updated.
			 *
			 * @since 1.0.0
			 * @hook knowlio_article_updated
			 *
			 * @param KnowlioArticleModel $article Updated article.
			 */
			do_action( 'knowlio_article_updated', $article );
		}

		$message = $is_new_record
			/* translators: %s: article title. */
			? sprintf( __( 'Article "%s" created', 'minidocs' ), $article->title )
			/* translators: %s: article title. */
			: sprintf( __( 'Article "%s" updated', 'minidocs' ), $article->title );

		$this->respond(
			KNOWLIO_STATUS_SUCCESS,
			$message,
			array(
				'record_id' => $article->id,
				'reload'    => true,
			)
		);
	}

	/**
	 * Delete an article.
	 */
	public function destroy() {
		$article_id = absint( $this->params['id'] ?? 0 );

		$this->check_nonce( 'destroy_article_' . $article_id );

		if ( ! $article_id ) {
			$this->respond( KNOWLIO_STATUS_ERROR, __( 'Invalid article.', 'minidocs' ) );

			return;
		}

		$article = new KnowlioArticleModel( $article_id );

		if ( $article->is_new_record() || ! $article->delete() ) {
			$this->respond( KNOWLIO_STATUS_ERROR, __( 'Error deleting article.', 'minidocs' ) );

			return;
		}

		$this->respond(
			KNOWLIO_STATUS_SUCCESS,
			__( 'Article deleted', 'minidocs' ),
			array( 'reload' => true )
		);
	}

	/* --------------------------------------------------------------------- */
	/* Internals                                                             */
	/* --------------------------------------------------------------------- */

	/**
	 * Translate the table filter row into ORM conditions.
	 *
	 * Only known filter keys become conditions, so a crafted `filter[...]` key
	 * can never reach the query builder.
	 *
	 * @return array
	 */
	private function build_query_args_from_filters(): array {
		global $wpdb;

		$filter = (array) ( $this->params['filter'] ?? array() );
		$args   = array();

		if ( ! empty( $filter['title'] ) ) {
			// esc_like() so that a title containing % or _ is searched for
			// literally rather than acting as a wildcard.
			$args['title LIKE'] = '%' . $wpdb->esc_like( sanitize_text_field( $filter['title'] ) ) . '%';
		}

		if ( ! empty( $filter['category_id'] ) ) {
			$args['category_id'] = absint( $filter['category_id'] );
		}

		if ( ! empty( $filter['status'] ) && array_key_exists( $filter['status'], KnowlioArticlesHelper::get_statuses_list() ) ) {
			$args['status'] = sanitize_key( $filter['status'] );
		}

		/**
		 * Filters the ORM conditions built from the article table filters.
		 *
		 * @since 1.0.0
		 * @hook knowlio_articles_query_args
		 *
		 * @param array $args   Conditions.
		 * @param array $filter Raw filter params.
		 */
		return (array) apply_filters( 'knowlio_articles_query_args', $args, $filter );
	}

	/**
	 * Whitelisted ORDER BY built from the request.
	 *
	 * @return string
	 */
	private function get_order_by(): string {
		$sortable = array( 'id', 'title', 'status', 'views_count', 'order_number', 'created_at', 'updated_at' );

		$column    = (string) ( $this->params['order_by'] ?? 'updated_at' );
		$direction = strtolower( (string) ( $this->params['order_dir'] ?? 'desc' ) );

		if ( ! in_array( $column, $sortable, true ) ) {
			$column = 'updated_at';
		}

		if ( ! in_array( $direction, array( 'asc', 'desc' ), true ) ) {
			$direction = 'desc';
		}

		return $column . ' ' . $direction;
	}

	/**
	 * Stream the filtered result set as CSV.
	 *
	 * @param array $query_args ORM conditions.
	 */
	private function export_csv( array $query_args ) {
		if ( ! KnowlioSettingsHelper::can_download_csv() ) {
			wp_die( esc_html__( 'CSV export is disabled.', 'minidocs' ) );
		}

		$this->check_nonce( 'export_articles' );

		$rows = array(
			array(
				__( 'ID', 'minidocs' ),
				__( 'Title', 'minidocs' ),
				__( 'Slug', 'minidocs' ),
				__( 'Category', 'minidocs' ),
				__( 'Status', 'minidocs' ),
				__( 'Views', 'minidocs' ),
				__( 'Updated', 'minidocs' ),
			),
		);

		$articles = new KnowlioArticleModel();
		$articles = $articles->where( $query_args )->order_by( 'id desc' )->get_results_as_models();

		foreach ( (array) $articles as $article ) {
			$rows[] = array(
				$article->id,
				$article->title,
				$article->slug,
				$article->get_category_name(),
				$article->get_nice_status(),
				$article->views_count,
				$article->updated_at,
			);
		}

		KnowlioUtilHelper::array_to_csv( $rows, 'articles_' . KnowlioUtilHelper::random_text() );
		exit;
	}
}
