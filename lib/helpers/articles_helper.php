<?php
/**
 * Article domain helper.
 *
 * @package KnowlioDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class KnowlioArticlesHelper
 */
class KnowlioArticlesHelper {

	/**
	 * Status slug => label.
	 *
	 * @return array
	 */
	public static function get_statuses_list(): array {
		return array(
			KNOWLIO_ARTICLE_STATUS_DRAFT     => __( 'Draft', 'minidocs' ),
			KNOWLIO_ARTICLE_STATUS_PUBLISHED => __( 'Published', 'minidocs' ),
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

		return KnowlioUtilHelper::build_action_atts(
			KnowlioRouterHelper::build_route_name( 'articles', 'quick_edit' ),
			$params,
			'side-panel',
			'knowlioInitArticleForm'
		);
	}

	/**
	 * Counters for the dashboard.
	 *
	 * @return array
	 */
	public static function get_stats(): array {
		$published = new KnowlioArticleModel();
		$drafts    = new KnowlioArticleModel();
		$total     = new KnowlioArticleModel();
		$views     = new KnowlioArticleModel();

		return array(
			'published'   => $published->where( array( 'status' => KNOWLIO_ARTICLE_STATUS_PUBLISHED ) )->count(),
			'drafts'      => $drafts->where( array( 'status' => KNOWLIO_ARTICLE_STATUS_DRAFT ) )->count(),
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

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL -- Plugin-owned table, no user input, one aggregate read per dashboard render.
		return (int) $wpdb->get_var( 'SELECT COALESCE(SUM(views_count), 0) FROM ' . KNOWLIO_TABLE_ARTICLES );
	}

	/**
	 * Most-read published articles.
	 *
	 * @param int $limit Row limit.
	 *
	 * @return KnowlioArticleModel[]
	 */
	public static function get_popular( int $limit = 5 ): array {
		$articles = new KnowlioArticleModel();

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
	 * @return KnowlioArticleModel[]
	 */
	public static function get_featured( int $limit = 6 ): array {
		$articles = new KnowlioArticleModel();

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
	 * @return KnowlioArticleModel[]
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
			'SELECT * FROM ' . KNOWLIO_TABLE_ARTICLES . ' WHERE status = %s AND ( title LIKE %s OR excerpt LIKE %s OR content LIKE %s ) ORDER BY views_count DESC LIMIT %d', // phpcs:ignore WordPress.DB.PreparedSQL
			KNOWLIO_ARTICLE_STATUS_PUBLISHED,
			$like,
			$like,
			$like,
			max( 1, min( 100, $limit ) )
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
		$rows = $wpdb->get_results( $query, ARRAY_A );

		$articles = array();

		foreach ( (array) $rows as $row ) {
			$article = new KnowlioArticleModel();
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
			'getting_started' => array(
				'label' => __( 'Getting started', 'minidocs' ),
				'html'  => '<p>' . __( 'One sentence on what this product does, and what the reader will have working by the end of this page.', 'minidocs' ) . '</p>'
					. '<h2>' . __( 'What you need', 'minidocs' ) . '</h2>'
					. '<ul>'
					. '<li>' . __( 'Requirement one', 'minidocs' ) . '</li>'
					. '<li>' . __( 'Requirement two', 'minidocs' ) . '</li>'
					. '</ul>'
					. '<h2>' . __( 'Install', 'minidocs' ) . '</h2>'
					. '<ol>'
					. '<li>' . __( 'Download or install the package.', 'minidocs' ) . '</li>'
					. '<li>' . __( 'Activate it.', 'minidocs' ) . '</li>'
					. '</ol>'
					. '<h2>' . __( 'Set it up', 'minidocs' ) . '</h2>'
					. '<ol>'
					. '<li>' . __( 'Open the settings screen.', 'minidocs' ) . '</li>'
					. '<li>' . __( 'Fill in the required fields.', 'minidocs' ) . '</li>'
					. '<li>' . __( 'Save.', 'minidocs' ) . '</li>'
					. '</ol>'
					. '<h2>' . __( 'Check that it works', 'minidocs' ) . '</h2>'
					. '<p>' . __( 'How the reader confirms the setup succeeded.', 'minidocs' ) . '</p>'
					. '<h2>' . __( 'Where to go next', 'minidocs' ) . '</h2>'
					. '<ul><li>' . __( 'Link to the next article.', 'minidocs' ) . '</li></ul>',
			),
			'feature'         => array(
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
			'how_to'          => array(
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
			'reference'       => array(
				'label' => __( 'Settings reference', 'minidocs' ),
				'html'  => '<p>' . __( 'Every setting on this screen, what it changes, and the value it ships with.', 'minidocs' ) . '</p>'
					. '<h2>' . __( 'Settings', 'minidocs' ) . '</h2>'
					. '<table>'
					. '<thead><tr>'
					. '<th>' . __( 'Setting', 'minidocs' ) . '</th>'
					. '<th>' . __( 'Default', 'minidocs' ) . '</th>'
					. '<th>' . __( 'What it does', 'minidocs' ) . '</th>'
					. '</tr></thead>'
					. '<tbody>'
					. '<tr><td>' . __( 'First setting', 'minidocs' ) . '</td><td>' . __( 'Off', 'minidocs' ) . '</td><td>' . __( 'Describe the effect of turning it on.', 'minidocs' ) . '</td></tr>'
					. '<tr><td>' . __( 'Second setting', 'minidocs' ) . '</td><td>' . __( '20', 'minidocs' ) . '</td><td>' . __( 'Describe what the number controls.', 'minidocs' ) . '</td></tr>'
					. '</tbody>'
					. '</table>'
					. '<h2>' . __( 'Notes', 'minidocs' ) . '</h2>'
					. '<ul><li>' . __( 'Anything that only applies in certain conditions.', 'minidocs' ) . '</li></ul>',
			),
			'api'             => array(
				'label' => __( 'API endpoint', 'minidocs' ),
				'html'  => '<p>' . __( 'What this endpoint returns and when to call it.', 'minidocs' ) . '</p>'
					. '<h2>' . __( 'Request', 'minidocs' ) . '</h2>'
					. '<pre><code>GET /wp-json/example/v1/items</code></pre>'
					. '<h2>' . __( 'Parameters', 'minidocs' ) . '</h2>'
					. '<table>'
					. '<thead><tr>'
					. '<th>' . __( 'Name', 'minidocs' ) . '</th>'
					. '<th>' . __( 'Type', 'minidocs' ) . '</th>'
					. '<th>' . __( 'Required', 'minidocs' ) . '</th>'
					. '<th>' . __( 'Description', 'minidocs' ) . '</th>'
					. '</tr></thead>'
					. '<tbody>'
					. '<tr><td><code>id</code></td><td>' . __( 'integer', 'minidocs' ) . '</td><td>' . __( 'Yes', 'minidocs' ) . '</td><td>' . __( 'Which record to return.', 'minidocs' ) . '</td></tr>'
					. '<tr><td><code>per_page</code></td><td>' . __( 'integer', 'minidocs' ) . '</td><td>' . __( 'No', 'minidocs' ) . '</td><td>' . __( 'How many results per page.', 'minidocs' ) . '</td></tr>'
					. '</tbody>'
					. '</table>'
					. '<h2>' . __( 'Example response', 'minidocs' ) . '</h2>'
					. '<pre><code>{ "id": 1, "title": "Example", "status": "published" }</code></pre>'
					. '<h2>' . __( 'Errors', 'minidocs' ) . '</h2>'
					. '<table>'
					. '<thead><tr><th>' . __( 'Code', 'minidocs' ) . '</th><th>' . __( 'Meaning', 'minidocs' ) . '</th></tr></thead>'
					. '<tbody>'
					. '<tr><td><code>401</code></td><td>' . __( 'Not authenticated.', 'minidocs' ) . '</td></tr>'
					. '<tr><td><code>404</code></td><td>' . __( 'No record with that id.', 'minidocs' ) . '</td></tr>'
					. '</tbody>'
					. '</table>',
			),
			'integration'     => array(
				'label' => __( 'Integration guide', 'minidocs' ),
				'html'  => '<p>' . __( 'What connecting these two services lets the reader do.', 'minidocs' ) . '</p>'
					. '<h2>' . __( 'Before you connect', 'minidocs' ) . '</h2>'
					. '<ul>'
					. '<li>' . __( 'An account on the other service.', 'minidocs' ) . '</li>'
					. '<li>' . __( 'Permission to create API credentials there.', 'minidocs' ) . '</li>'
					. '</ul>'
					. '<h2>' . __( 'Step 1: create the credentials', 'minidocs' ) . '</h2>'
					. '<p>' . __( 'Where to find them in the other service.', 'minidocs' ) . '</p>'
					. '<h2>' . __( 'Step 2: add them here', 'minidocs' ) . '</h2>'
					. '<p>' . __( 'Which screen to paste them into, and what to save.', 'minidocs' ) . '</p>'
					. '<h2>' . __( 'Step 3: test the connection', 'minidocs' ) . '</h2>'
					. '<p>' . __( 'How the reader confirms data is flowing.', 'minidocs' ) . '</p>'
					. '<h2>' . __( 'What gets synced', 'minidocs' ) . '</h2>'
					. '<ul><li>' . __( 'List each thing that moves between the two, and in which direction.', 'minidocs' ) . '</li></ul>'
					. '<h2>' . __( 'If it stops working', 'minidocs' ) . '</h2>'
					. '<p>' . __( 'The first two things to check.', 'minidocs' ) . '</p>',
			),
			'troubleshooting' => array(
				'label' => __( 'Troubleshooting', 'minidocs' ),
				'html'  => '<p>' . __( 'Describe the problem in the words a reader would use when searching for it.', 'minidocs' ) . '</p>'
					. '<h2>' . __( 'Check these first', 'minidocs' ) . '</h2>'
					. '<table>'
					. '<thead><tr>'
					. '<th>' . __( 'What you see', 'minidocs' ) . '</th>'
					. '<th>' . __( 'Usual cause', 'minidocs' ) . '</th>'
					. '<th>' . __( 'Fix', 'minidocs' ) . '</th>'
					. '</tr></thead>'
					. '<tbody>'
					. '<tr><td>' . __( 'The first symptom', 'minidocs' ) . '</td><td>' . __( 'Why it happens', 'minidocs' ) . '</td><td>' . __( 'What to change', 'minidocs' ) . '</td></tr>'
					. '<tr><td>' . __( 'The second symptom', 'minidocs' ) . '</td><td>' . __( 'Why it happens', 'minidocs' ) . '</td><td>' . __( 'What to change', 'minidocs' ) . '</td></tr>'
					. '</tbody>'
					. '</table>'
					. '<h2>' . __( 'Still not working', 'minidocs' ) . '</h2>'
					. '<ol>'
					. '<li>' . __( 'Turn off other plugins and test again.', 'minidocs' ) . '</li>'
					. '<li>' . __( 'Switch to a default theme and test again.', 'minidocs' ) . '</li>'
					. '</ol>'
					. '<h2>' . __( 'What to send us', 'minidocs' ) . '</h2>'
					. '<ul><li>' . __( 'The exact error message, and what you did just before it appeared.', 'minidocs' ) . '</li></ul>',
			),
			'faq'             => array(
				'label' => __( 'FAQ', 'minidocs' ),
				'html'  => '<h2>' . __( 'Question one?', 'minidocs' ) . '</h2>'
					. '<p>' . __( 'Answer one.', 'minidocs' ) . '</p>'
					. '<h2>' . __( 'Question two?', 'minidocs' ) . '</h2>'
					. '<p>' . __( 'Answer two.', 'minidocs' ) . '</p>'
					. '<h2>' . __( 'Question three?', 'minidocs' ) . '</h2>'
					. '<p>' . __( 'Answer three.', 'minidocs' ) . '</p>',
			),
			'comparison'      => array(
				'label' => __( 'Comparison', 'minidocs' ),
				'html'  => '<p>' . __( 'The question this page settles, in one sentence.', 'minidocs' ) . '</p>'
					. '<h2>' . __( 'Side by side', 'minidocs' ) . '</h2>'
					. '<table>'
					. '<thead><tr>'
					. '<th>' . __( 'What matters', 'minidocs' ) . '</th>'
					. '<th>' . __( 'Option A', 'minidocs' ) . '</th>'
					. '<th>' . __( 'Option B', 'minidocs' ) . '</th>'
					. '</tr></thead>'
					. '<tbody>'
					. '<tr><td>' . __( 'First difference', 'minidocs' ) . '</td><td>' . __( 'How A behaves', 'minidocs' ) . '</td><td>' . __( 'How B behaves', 'minidocs' ) . '</td></tr>'
					. '<tr><td>' . __( 'Second difference', 'minidocs' ) . '</td><td>' . __( 'How A behaves', 'minidocs' ) . '</td><td>' . __( 'How B behaves', 'minidocs' ) . '</td></tr>'
					. '</tbody>'
					. '</table>'
					. '<h2>' . __( 'Choose A when', 'minidocs' ) . '</h2>'
					. '<ul><li>' . __( 'The situation that favours A.', 'minidocs' ) . '</li></ul>'
					. '<h2>' . __( 'Choose B when', 'minidocs' ) . '</h2>'
					. '<ul><li>' . __( 'The situation that favours B.', 'minidocs' ) . '</li></ul>'
					. '<h2>' . __( 'Our recommendation', 'minidocs' ) . '</h2>'
					. '<p>' . __( 'What most people should pick, and why.', 'minidocs' ) . '</p>',
			),
			'migration'       => array(
				'label' => __( 'Migration / upgrade', 'minidocs' ),
				'html'  => '<p>' . __( 'What is moving, and roughly how long it takes.', 'minidocs' ) . '</p>'
					. '<blockquote><p>' . __( 'Back up your database before you start. This cannot be undone from inside the plugin.', 'minidocs' ) . '</p></blockquote>'
					. '<h2>' . __( 'What changes', 'minidocs' ) . '</h2>'
					. '<table>'
					. '<thead><tr>'
					. '<th>' . __( 'Before', 'minidocs' ) . '</th>'
					. '<th>' . __( 'After', 'minidocs' ) . '</th>'
					. '<th>' . __( 'Action needed', 'minidocs' ) . '</th>'
					. '</tr></thead>'
					. '<tbody>'
					. '<tr><td>' . __( 'The old behaviour', 'minidocs' ) . '</td><td>' . __( 'The new behaviour', 'minidocs' ) . '</td><td>' . __( 'None, or what to update', 'minidocs' ) . '</td></tr>'
					. '</tbody>'
					. '</table>'
					. '<h2>' . __( 'Upgrade steps', 'minidocs' ) . '</h2>'
					. '<ol>'
					. '<li>' . __( 'Take a backup.', 'minidocs' ) . '</li>'
					. '<li>' . __( 'Update the package.', 'minidocs' ) . '</li>'
					. '<li>' . __( 'Run the migration and wait for it to finish.', 'minidocs' ) . '</li>'
					. '<li>' . __( 'Check the pages listed below.', 'minidocs' ) . '</li>'
					. '</ol>'
					. '<h2>' . __( 'If something goes wrong', 'minidocs' ) . '</h2>'
					. '<p>' . __( 'How to restore the backup and get back to a working state.', 'minidocs' ) . '</p>',
			),
			'glossary'        => array(
				'label' => __( 'Glossary', 'minidocs' ),
				'html'  => '<p>' . __( 'The terms used across this documentation, in plain language.', 'minidocs' ) . '</p>'
					. '<h2>' . __( 'First term', 'minidocs' ) . '</h2>'
					. '<p>' . __( 'What it means here, which is not always what it means elsewhere.', 'minidocs' ) . '</p>'
					. '<h2>' . __( 'Second term', 'minidocs' ) . '</h2>'
					. '<p>' . __( 'A one or two sentence definition.', 'minidocs' ) . '</p>'
					. '<h2>' . __( 'Third term', 'minidocs' ) . '</h2>'
					. '<p>' . __( 'A one or two sentence definition.', 'minidocs' ) . '</p>',
			),
			'release'         => array(
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
		 * @hook knowlio_content_templates
		 *
		 * @param array $templates Slug => [ label, html ].
		 */
		return (array) apply_filters( 'knowlio_content_templates', $templates );
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

				$anchor = 'knowlio-' . sanitize_title( $text );

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
