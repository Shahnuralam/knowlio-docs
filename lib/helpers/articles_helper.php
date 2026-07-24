<?php
/**
 * Article domain helper.
 *
 * @package MiniDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MdArticlesHelper
 */
class MdArticlesHelper {

	/**
	 * Status slug => label.
	 *
	 * @return array
	 */
	public static function get_statuses_list(): array {
		return array(
			MINIDOCS_ARTICLE_STATUS_DRAFT     => __( 'Draft', 'minidocs' ),
			MINIDOCS_ARTICLE_STATUS_PUBLISHED => __( 'Published', 'minidocs' ),
		);
	}

	/**
	 * Readable label for a status slug.
	 *
	 * @param string $status Status slug.
	 *
	 * @return string
	 */
	public static function get_status_label( string $status ): string {
		$statuses = self::get_statuses_list();

		return $statuses[ $status ] ?? $status;
	}

	/**
	 * Attribute string that opens the article editor panel.
	 *
	 * @param int|false $article_id Record id, or false for a new article.
	 *
	 * @return string
	 */
	public static function quick_edit_btn_atts( $article_id = false ): string {
		$params = array();

		if ( $article_id ) {
			$params['article_id'] = (int) $article_id;
		}

		return MdUtilHelper::build_action_atts(
			MdRouterHelper::build_route_name( 'articles', 'quick_edit' ),
			$params,
			'side-panel',
			'minidocsInitArticleForm'
		);
	}

	/**
	 * Counters for the dashboard.
	 *
	 * @return array
	 */
	public static function get_stats(): array {
		$published = new MdArticleModel();
		$drafts    = new MdArticleModel();
		$total     = new MdArticleModel();
		$views     = new MdArticleModel();

		return array(
			'published'   => $published->where( array( 'status' => MINIDOCS_ARTICLE_STATUS_PUBLISHED ) )->count(),
			'drafts'      => $drafts->where( array( 'status' => MINIDOCS_ARTICLE_STATUS_DRAFT ) )->count(),
			'total'       => $total->count(),
			'total_views' => self::get_total_views(),
		);
	}

	/**
	 * Sum of every article's read counter.
	 *
	 * @return int
	 */
	public static function get_total_views(): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( 'SELECT COALESCE(SUM(views_count), 0) FROM ' . MINIDOCS_TABLE_ARTICLES );
	}

	/**
	 * Most-read published articles.
	 *
	 * @param int $limit Row limit.
	 *
	 * @return MdArticleModel[]
	 */
	public static function get_popular( int $limit = 5 ): array {
		$articles = new MdArticleModel();

		return (array) $articles->should_be_published()
			->order_by( 'views_count desc, id desc' )
			->set_limit( max( 1, $limit ) )
			->get_results_as_models();
	}

	/**
	 * Articles pinned to the knowledge base landing page.
	 *
	 * @param int $limit Row limit.
	 *
	 * @return MdArticleModel[]
	 */
	public static function get_featured( int $limit = 6 ): array {
		$articles = new MdArticleModel();

		return (array) $articles->should_be_published()
			->should_be_featured()
			->order_by( 'order_number asc, title asc' )
			->set_limit( max( 1, $limit ) )
			->get_results_as_models();
	}

	/**
	 * Search published articles by title, excerpt and body.
	 *
	 * @param string $term  Search term.
	 * @param int    $limit Row limit.
	 *
	 * @return MdArticleModel[]
	 */
	public static function search( string $term, int $limit = 20 ): array {
		$term = trim( $term );

		if ( '' === $term ) {
			return array();
		}

		global $wpdb;

		$like = '%' . $wpdb->esc_like( $term ) . '%';

		// A three-column OR is beyond what the condition builder expresses, so
		// this one query is written by hand -- still fully prepared.
		$query = $wpdb->prepare(
			'SELECT * FROM ' . MINIDOCS_TABLE_ARTICLES . ' WHERE status = %s AND ( title LIKE %s OR excerpt LIKE %s OR content LIKE %s ) ORDER BY views_count DESC LIMIT %d', // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			MINIDOCS_ARTICLE_STATUS_PUBLISHED,
			$like,
			$like,
			$like,
			max( 1, min( 100, $limit ) )
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $query, ARRAY_A );

		$articles = array();

		foreach ( (array) $rows as $row ) {
			$article = new MdArticleModel();
			$article->load_from_row_data( $row );
			$articles[] = $article;
		}

		return $articles;
	}

	/**
	 * Starter content templates offered in the editor.
	 *
	 * Each returns a professional heading structure the author fills in, so a
	 * long feature write-up starts from a consistent, well-organised skeleton
	 * rather than a blank box.
	 *
	 * @return array Slug => [ label, html ].
	 */
	public static function get_content_templates(): array {
		$templates = array(
			'feature'  => array(
				'label' => __( 'Feature guide', 'minidocs' ),
				'html'  => '<p>' . __( 'A one-paragraph summary of what this feature does and who it is for.', 'minidocs' ) . '</p>'
					. '<h2>' . __( 'Overview', 'minidocs' ) . '</h2>'
					. '<p>' . __( 'Explain the feature in a few sentences.', 'minidocs' ) . '</p>'
					. '<h2>' . __( 'How it works', 'minidocs' ) . '</h2>'
					. '<p>' . __( 'Describe the behaviour step by step.', 'minidocs' ) . '</p>'
					. '<h2>' . __( 'Setup', 'minidocs' ) . '</h2>'
					. '<ol><li>' . __( 'First step', 'minidocs' ) . '</li><li>' . __( 'Second step', 'minidocs' ) . '</li></ol>'
					. '<h2>' . __( 'Options', 'minidocs' ) . '</h2>'
					. '<h3>' . __( 'Option name', 'minidocs' ) . '</h3>'
					. '<p>' . __( 'What it does and the default value.', 'minidocs' ) . '</p>'
					. '<h2>' . __( 'Frequently asked questions', 'minidocs' ) . '</h2>'
					. '<h3>' . __( 'A common question?', 'minidocs' ) . '</h3>'
					. '<p>' . __( 'The answer.', 'minidocs' ) . '</p>',
			),
			'how_to'   => array(
				'label' => __( 'How-to (steps)', 'minidocs' ),
				'html'  => '<p>' . __( 'What the reader will achieve by the end, and anything they need first.', 'minidocs' ) . '</p>'
					. '<h2>' . __( 'Before you start', 'minidocs' ) . '</h2>'
					. '<ul><li>' . __( 'Prerequisite one', 'minidocs' ) . '</li><li>' . __( 'Prerequisite two', 'minidocs' ) . '</li></ul>'
					. '<h2>' . __( 'Steps', 'minidocs' ) . '</h2>'
					. '<ol>'
					. '<li>' . __( 'Do the first thing.', 'minidocs' ) . '</li>'
					. '<li>' . __( 'Do the second thing.', 'minidocs' ) . '</li>'
					. '<li>' . __( 'Do the third thing.', 'minidocs' ) . '</li>'
					. '</ol>'
					. '<h2>' . __( 'Next steps', 'minidocs' ) . '</h2>'
					. '<p>' . __( 'Where to go from here.', 'minidocs' ) . '</p>',
			),
			'faq'      => array(
				'label' => __( 'FAQ', 'minidocs' ),
				'html'  => '<h2>' . __( 'Question one?', 'minidocs' ) . '</h2>'
					. '<p>' . __( 'Answer one.', 'minidocs' ) . '</p>'
					. '<h2>' . __( 'Question two?', 'minidocs' ) . '</h2>'
					. '<p>' . __( 'Answer two.', 'minidocs' ) . '</p>'
					. '<h2>' . __( 'Question three?', 'minidocs' ) . '</h2>'
					. '<p>' . __( 'Answer three.', 'minidocs' ) . '</p>',
			),
			'release'  => array(
				'label' => __( 'Release notes', 'minidocs' ),
				'html'  => '<h2>' . __( 'Version 1.0.0', 'minidocs' ) . '</h2>'
					. '<h3>' . __( 'Added', 'minidocs' ) . '</h3>'
					. '<ul><li>' . __( 'New capability.', 'minidocs' ) . '</li></ul>'
					. '<h3>' . __( 'Changed', 'minidocs' ) . '</h3>'
					. '<ul><li>' . __( 'Adjusted behaviour.', 'minidocs' ) . '</li></ul>'
					. '<h3>' . __( 'Fixed', 'minidocs' ) . '</h3>'
					. '<ul><li>' . __( 'Resolved issue.', 'minidocs' ) . '</li></ul>',
			),
		);

		/**
		 * Filters the article starter templates.
		 *
		 * @since 1.0.0
		 * @hook minidocs_content_templates
		 *
		 * @param array $templates Slug => [ label, html ].
		 */
		return (array) apply_filters( 'minidocs_content_templates', $templates );
	}

	/**
	 * Build a table of contents from an article body by finding its headings.
	 *
	 * Anchors are injected into the returned HTML so the links resolve.
	 *
	 * @param string $html Rendered article HTML.
	 *
	 * @return array `['html' => string, 'toc' => array]`.
	 */
	public static function extract_toc( string $html ): array {
		$toc  = array();
		$used = array();

		$html = preg_replace_callback(
			'/<h([23])([^>]*)>(.*?)<\/h\1>/is',
			static function ( $matches ) use ( &$toc, &$used ) {
				$level = (int) $matches[1];
				$text  = trim( wp_strip_all_tags( $matches[3] ) );

				if ( '' === $text ) {
					return $matches[0];
				}

				$anchor = 'md-' . sanitize_title( $text );

				// Two headings with the same text would otherwise share an id.
				if ( isset( $used[ $anchor ] ) ) {
					++$used[ $anchor ];
					$anchor .= '-' . $used[ $anchor ];
				} else {
					$used[ $anchor ] = 1;
				}

				$toc[] = array(
					'text'   => $text,
					'anchor' => $anchor,
					'level'  => $level,
				);

				return '<h' . $level . ' id="' . esc_attr( $anchor ) . '"' . $matches[2] . '>' . $matches[3] . '</h' . $level . '>';
			},
			$html
		);

		return array(
			'html' => (string) $html,
			'toc'  => $toc,
		);
	}
}
