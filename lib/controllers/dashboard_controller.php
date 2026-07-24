<?php
/**
 * Landing screen.
 *
 * @package MiniDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MdDashboardController
 */
class MdDashboardController extends MdController {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->views_folder        = MINIDOCS_VIEWS_ABSPATH . 'dashboard/';
		$this->vars['page_header'] = __( 'Dashboard', 'minidocs' );
	}

	/**
	 * Overview: counters, recent edits, most-read articles, empty categories.
	 */
	public function index() {
		$recent = new MdArticleModel();

		$this->vars['stats']            = MdArticlesHelper::get_stats();
		$this->vars['category_count']   = MdCategoriesHelper::count();
		$this->vars['recent_articles']  = $recent->order_by( 'updated_at desc, id desc' )->set_limit( 6 )->get_results_as_models();
		$this->vars['popular_articles'] = MdArticlesHelper::get_popular( 5 );
		$this->vars['categories']       = MdCategoriesHelper::get_all();

		$this->format_render( 'index' );
	}
}
