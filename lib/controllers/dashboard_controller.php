<?php
/**
 * Landing screen.
 *
 * @package KnowlioDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class KnowlioDashboardController
 */
class KnowlioDashboardController extends KnowlioController {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->views_folder        = KNOWLIO_VIEWS_ABSPATH . 'dashboard/';
		$this->vars['page_header'] = __( 'Dashboard', 'minidocs' );
	}

	/**
	 * Overview: counters, recent edits, most-read articles, empty categories.
	 */
	public function index() {
		$recent = new KnowlioArticleModel();

		$this->vars['stats']            = KnowlioArticlesHelper::get_stats();
		$this->vars['category_count']   = KnowlioCategoriesHelper::count();
		$this->vars['recent_articles']  = $recent->order_by( 'updated_at desc, id desc' )->set_limit( 6 )->get_results_as_models();
		$this->vars['popular_articles'] = KnowlioArticlesHelper::get_popular( 5 );
		$this->vars['categories']       = KnowlioCategoriesHelper::get_all();

		$this->format_render( 'index' );
	}
}
