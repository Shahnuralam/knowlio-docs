<?php
/**
 * Frontend shortcodes.
 *
 * `[knowlio]` is the whole public knowledge base. It renders one of three
 * states, chosen from query args, so a single page serves the landing screen,
 * every category and every article without rewrite rules.
 *
 * @package KnowlioDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class KnowlioShortcodesHelper
 */
class KnowlioShortcodesHelper {

	/**
	 * Register every shortcode.
	 */
	public static function register() {
		add_shortcode( 'knowlio', array( __CLASS__, 'shortcode_knowledge_base' ) );
		add_shortcode( 'knowlio_categories', array( __CLASS__, 'shortcode_categories' ) );
		add_shortcode( 'knowlio_articles', array( __CLASS__, 'shortcode_articles' ) );

		/**
		 * Fires after the core shortcodes are registered.
		 *
		 * @since 1.0.0
		 * @hook knowlio_register_shortcodes
		 */
		do_action( 'knowlio_register_shortcodes' );
	}

	/**
	 * Enqueue the frontend bundle on demand, never site-wide.
	 */
	private static function enqueue_assets() {
		wp_enqueue_style( 'knowlio-front' );
		wp_enqueue_script( 'knowlio-front' );
	}

	/**
	 * Render a template from `lib/views/front/`.
	 *
	 * @param string $template Template name.
	 * @param array  $vars     Template variables.
	 *
	 * @return string
	 */
	private static function render_front( string $template, array $vars = array() ): string {
		$template = preg_replace( '/[^a-z0-9_-]/', '', $template );

		// Held in a name the template variables cannot collide with: extract()
		// below would otherwise let a `path` key decide what gets included.
		$knowlio_template_path = KNOWLIO_VIEWS_ABSPATH . 'front/' . $template . '.php';

		if ( ! is_readable( $knowlio_template_path ) ) {
			return '';
		}

		extract( $vars ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract

		ob_start();
		include $knowlio_template_path;

		return (string) ob_get_clean();
	}

	/**
	 * Base URL of the page hosting the knowledge base, with our args stripped.
	 *
	 * @return string
	 */
	public static function base_url(): string {
		return remove_query_arg( array( 'knowlio_cat', 'knowlio_article', 'knowlio_s' ) );
	}

	/**
	 * Link to a category.
	 *
	 * @param string $slug Category slug.
	 *
	 * @return string
	 */
	public static function category_url( string $slug ): string {
		return add_query_arg( 'knowlio_cat', $slug, self::base_url() ) . '#knowlio-docs';
	}

	/**
	 * Link to an article.
	 *
	 * @param string $slug Article slug.
	 *
	 * @return string
	 */
	public static function article_url( string $slug ): string {
		return add_query_arg( 'knowlio_article', $slug, self::base_url() ) . '#knowlio-docs';
	}

	/**
	 * Link to a search result page.
	 *
	 * @param string $term Search term.
	 *
	 * @return string
	 */
	public static function search_url( string $term ): string {
		return add_query_arg( 'knowlio_s', rawurlencode( $term ), self::base_url() ) . '#knowlio-docs';
	}

	/**
	 * `[knowlio]` — the knowledge base.
	 *
	 * Attributes:
	 *   title    - hero heading
	 *   subtitle - hero sub-heading
	 *   search   - `yes` to show the hero search box
	 *   columns  - category grid columns, 2-4
	 *
	 * @param array $atts Shortcode attributes.
	 *
	 * @return string
	 */
	public static function shortcode_knowledge_base( $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'title'    => '',
				'subtitle' => '',
				'search'   => 'yes',
				'columns'  => '',
				'width'    => '',
				'max'      => '',
			),
			$atts,
			'knowlio'
		);

		self::enqueue_assets();

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$article_slug = isset( $_GET['knowlio_article'] ) ? sanitize_title( wp_unslash( $_GET['knowlio_article'] ) ) : '';
		$category_slug = isset( $_GET['knowlio_cat'] ) ? sanitize_title( wp_unslash( $_GET['knowlio_cat'] ) ) : '';
		$search_term   = isset( $_GET['knowlio_s'] ) ? sanitize_text_field( wp_unslash( $_GET['knowlio_s'] ) ) : '';
		// phpcs:enable

		// The admin's chosen layout preset sets the width, the reading measure and
		// the column arrangement. Shortcode attributes, when present, override it
		// so an individual page can still be tuned.
		$preset      = KnowlioSettingsHelper::get_docs_layout();
		$preset_max  = array(
			'sidebar'  => 1800,
			'wide'     => 1800,
			'boxed'    => 1000,
			'magazine' => 1500,
		);
		$preset_cols = array(
			'sidebar'  => 3,
			'wide'     => 3,
			'boxed'    => 2,
			'magazine' => 3,
		);

		// Themes wrap page content in a narrow column; being "full" breaks the
		// knowledge base out of it. Only `width="contained"` opts back in.
		$width     = '' !== $atts['width'] ? $atts['width'] : 'full';
		$is_full   = ( 'contained' !== $width );
		$max       = '' !== $atts['max'] ? (int) $atts['max'] : ( $preset_max[ $preset ] ?? 1460 );
		$max_width = max( 720, min( 2400, $max ) );
		$columns   = '' !== $atts['columns'] ? (int) $atts['columns'] : ( $preset_cols[ $preset ] ?? 3 );

		$shared = array(
			'hero_title'    => $atts['title'] ? $atts['title'] : KnowlioSettingsHelper::get_setting( 'kb_title', __( 'How can we help?', 'minidocs' ) ),
			'hero_subtitle' => $atts['subtitle'] ? $atts['subtitle'] : KnowlioSettingsHelper::get_setting( 'kb_subtitle', __( 'Search the documentation, or browse by topic below.', 'minidocs' ) ),
			'show_search'   => ( 'no' !== $atts['search'] ),
			'columns'       => max( 2, min( 4, $columns ) ),
			'search_term'   => $search_term,
			'layout_class'  => trim( ( $is_full ? 'knowlio-front-full ' : '' ) . 'knowlio-layout-' . $preset ),
			'inner_style'   => 'max-width:' . $max_width . 'px',
		);

		// State 1: a single article.
		if ( $article_slug ) {
			return self::render_article( $article_slug, $shared );
		}

		// State 2: search results.
		if ( '' !== $search_term ) {
			return self::render_front(
				'search',
				array_merge(
					$shared,
					array(
						'results'    => KnowlioArticlesHelper::search( $search_term, 30 ),
						'categories' => KnowlioCategoriesHelper::get_populated(),
					)
				)
			);
		}

		// State 3: one category's article list.
		if ( $category_slug ) {
			return self::render_category( $category_slug, $shared );
		}

		// State 4: the landing page.
		return self::render_front(
			'kb',
			array_merge(
				$shared,
				array(
					'categories' => KnowlioCategoriesHelper::get_populated(),
					'featured'   => KnowlioArticlesHelper::get_featured( 6 ),
					'popular'    => KnowlioArticlesHelper::get_popular( 5 ),
				)
			)
		);
	}

	/**
	 * Render a single article, or a not-found notice.
	 *
	 * @param string $slug   Article slug.
	 * @param array  $shared Shared template vars.
	 *
	 * @return string
	 */
	private static function render_article( string $slug, array $shared ): string {
		$finder  = new KnowlioArticleModel();
		$article = $finder->load_by_slug( $slug );

		// Draft articles are invisible to readers, whatever the URL says.
		if ( ! $article || KNOWLIO_ARTICLE_STATUS_PUBLISHED !== $article->status ) {
			return self::render_front(
				'not_found',
				array_merge( $shared, array( 'categories' => KnowlioCategoriesHelper::get_populated() ) )
			);
		}

		$article->register_view();

		$prepared = KnowlioArticlesHelper::extract_toc( $article->get_rendered_content() );

		$category = $article->get_category();

		return self::render_front(
			'article',
			array_merge(
				$shared,
				array(
					'article'      => $article,
					'category'     => $category,
					'body_html'    => $prepared['html'],
					'toc'          => $prepared['toc'],
					'categories'   => KnowlioCategoriesHelper::get_populated(),
					'siblings'     => $category->is_new_record() ? array() : $category->get_articles( true ),
				)
			)
		);
	}

	/**
	 * Render one category's article list, or a not-found notice.
	 *
	 * @param string $slug   Category slug.
	 * @param array  $shared Shared template vars.
	 *
	 * @return string
	 */
	private static function render_category( string $slug, array $shared ): string {
		$finder   = new KnowlioCategoryModel();
		$category = $finder->load_by_slug( $slug );

		if ( ! $category ) {
			return self::render_front(
				'not_found',
				array_merge( $shared, array( 'categories' => KnowlioCategoriesHelper::get_populated() ) )
			);
		}

		return self::render_front(
			'category',
			array_merge(
				$shared,
				array(
					'category'   => $category,
					'articles'   => $category->get_articles( true ),
					'categories' => KnowlioCategoriesHelper::get_populated(),
				)
			)
		);
	}

	/**
	 * `[knowlio_categories]` — just the category grid, for a homepage section.
	 *
	 * @param array $atts Shortcode attributes.
	 *
	 * @return string
	 */
	public static function shortcode_categories( $atts = array() ): string {
		$atts = shortcode_atts( array( 'columns' => 3 ), $atts, 'knowlio_categories' );

		self::enqueue_assets();

		return self::render_front(
			'categories_grid',
			array(
				'categories' => KnowlioCategoriesHelper::get_populated(),
				'columns'    => max( 2, min( 4, (int) $atts['columns'] ) ),
			)
		);
	}

	/**
	 * `[knowlio_articles]` — a plain article list, optionally scoped.
	 *
	 * Attributes:
	 *   category - category slug
	 *   limit    - maximum articles
	 *   featured - `yes` to show only featured articles
	 *
	 * @param array $atts Shortcode attributes.
	 *
	 * @return string
	 */
	public static function shortcode_articles( $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'category' => '',
				'limit'    => 10,
				'featured' => 'no',
			),
			$atts,
			'knowlio_articles'
		);

		self::enqueue_assets();

		$limit = max( 1, min( 50, (int) $atts['limit'] ) );

		if ( 'yes' === $atts['featured'] ) {
			$articles = KnowlioArticlesHelper::get_featured( $limit );
		} elseif ( $atts['category'] ) {
			$finder   = new KnowlioCategoryModel();
			$category = $finder->load_by_slug( sanitize_title( $atts['category'] ) );
			$articles = $category ? $category->get_articles( true, $limit ) : array();
		} else {
			$model    = new KnowlioArticleModel();
			$articles = (array) $model->should_be_published()
				->order_by( 'order_number asc, title asc' )
				->set_limit( $limit )
				->get_results_as_models();
		}

		return self::render_front( 'articles_list', array( 'articles' => $articles ) );
	}
}
