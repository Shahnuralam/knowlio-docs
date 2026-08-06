<?php
/**
 * Schema installation and versioned migrations.
 *
 * @package KnowlioDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class KnowlioDatabaseHelper
 */
class KnowlioDatabaseHelper {

	/**
	 * Re-run the install when the stored schema version is behind.
	 *
	 * Activation hooks do not fire on plugin update, so the check runs on every
	 * request. Once the versions match it costs one option read.
	 */
	public static function check_db_version() {
		$installed = KnowlioSettingsHelper::get_db_version();

		if ( ! $installed || version_compare( $installed, KNOWLIO_DB_VERSION, '<' ) ) {
			self::install_database();
		}
	}

	/**
	 * CREATE TABLE statements.
	 *
	 * dbDelta parses these with regular expressions and is strict: each field on
	 * its own line, two spaces after PRIMARY KEY, a name on every KEY, and no
	 * backticks around the table name.
	 *
	 * @return array
	 */
	public static function get_table_queries(): array {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$sqls = array();

		$sqls[] = 'CREATE TABLE ' . KNOWLIO_TABLE_CATEGORIES . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			slug varchar(191) NOT NULL DEFAULT '',
			icon varchar(64) NOT NULL DEFAULT 'dashicons-media-document',
			description text,
			order_number int(11) NOT NULL DEFAULT 0,
			created_at datetime DEFAULT NULL,
			updated_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY slug_index (slug),
			KEY order_index (order_number)
		) $charset_collate;";

		$sqls[] = 'CREATE TABLE ' . KNOWLIO_TABLE_ARTICLES . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			title varchar(255) NOT NULL,
			slug varchar(191) NOT NULL DEFAULT '',
			excerpt text,
			content longtext,
			category_id bigint(20) unsigned NOT NULL DEFAULT 0,
			status varchar(20) NOT NULL DEFAULT 'draft',
			order_number int(11) NOT NULL DEFAULT 0,
			is_featured tinyint(1) NOT NULL DEFAULT 0,
			views_count bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime DEFAULT NULL,
			updated_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY slug_index (slug),
			KEY status_index (status),
			KEY category_index (category_id),
			KEY featured_index (is_featured)
		) $charset_collate;";

		$sqls[] = 'CREATE TABLE ' . KNOWLIO_TABLE_SETTINGS . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(191) NOT NULL,
			value longtext,
			PRIMARY KEY  (id),
			UNIQUE KEY name_unique (name)
		) $charset_collate;";

		return $sqls;
	}

	/**
	 * Install or upgrade the schema, then seed starter content on first install.
	 */
	public static function install_database() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$is_fresh_install = ! KnowlioSettingsHelper::get_db_version();

		foreach ( self::get_table_queries() as $sql ) {
			dbDelta( $sql );
		}

		update_option( 'knowlio_db_version', KNOWLIO_DB_VERSION );

		if ( $is_fresh_install ) {
			self::seed_starter_content();
		}

		/**
		 * Fires after the schema is installed or upgraded.
		 *
		 * @since 1.0.0
		 * @hook knowlio_database_installed
		 */
		do_action( 'knowlio_database_installed' );
	}

	/**
	 * Create a small starter knowledge base so the first screen is not empty.
	 */
	public static function seed_starter_content() {
		if ( KnowlioSettingsHelper::is_on( 'is_database_seeded' ) ) {
			return;
		}

		$categories = array(
			array(
				'name'         => __( 'Getting Started', 'minidocs' ),
				'icon'         => 'dashicons-lightbulb',
				'description'  => __( 'Install, configure and publish your first article.', 'minidocs' ),
				'order_number' => 1,
			),
			array(
				'name'         => __( 'Using the Editor', 'minidocs' ),
				'icon'         => 'dashicons-media-document',
				'description'  => __( 'Writing articles, organising them, and controlling what readers see.', 'minidocs' ),
				'order_number' => 2,
			),
			array(
				'name'         => __( 'Troubleshooting', 'minidocs' ),
				'icon'         => 'dashicons-sos',
				'description'  => __( 'Answers to the questions that come up most often.', 'minidocs' ),
				'order_number' => 3,
			),
		);

		$category_ids = array();

		foreach ( $categories as $data ) {
			$category = new KnowlioCategoryModel();
			$category->set_data( $data );

			if ( $category->save() ) {
				$category_ids[] = $category->id;
			}
		}

		$articles = array(
			array(
				'title'        => __( 'Welcome to your knowledge base', 'minidocs' ),
				'category_id'  => $category_ids[0] ?? 0,
				'status'       => KNOWLIO_ARTICLE_STATUS_PUBLISHED,
				'is_featured'  => 1,
				'order_number' => 1,
				'excerpt'      => __( 'What this plugin does, and the three things to do first.', 'minidocs' ),
				'content'      => '<p>This is a starter article. Open it from the admin, edit the text, and it updates here immediately.</p>
					<h2>Publish your first article</h2>
					<p>Go to <strong>Articles</strong> and press <em>New Article</em>. Give it a title, pick a category, write the body, and set the status to Published. Draft articles stay hidden from readers.</p>
					<h2>Organise with categories</h2>
					<p>Categories are the cards readers see on the knowledge base landing page. Each one has an icon, a description and a sort position, all editable under <strong>Categories</strong>.</p>
					<h2>Put it on your site</h2>
					<p>Create a page and add the shortcode <code>[knowlio]</code>. That single shortcode renders the landing page, the category pages and the individual articles.</p>',
			),
			array(
				'title'        => __( 'Writing and formatting articles', 'minidocs' ),
				'category_id'  => $category_ids[1] ?? 0,
				'status'       => KNOWLIO_ARTICLE_STATUS_PUBLISHED,
				'is_featured'  => 1,
				'order_number' => 1,
				'excerpt'      => __( 'Headings, lists, code and links, and how the table of contents is built.', 'minidocs' ),
				'content'      => '<p>Article bodies accept the same HTML that WordPress posts do. Anything unsafe is stripped when the article is saved.</p>
					<h2>Headings build the table of contents</h2>
					<p>Every <code>h2</code> and <code>h3</code> in an article automatically becomes an entry in the sidebar table of contents, with its own anchor link. You do not have to maintain that list by hand.</p>
					<h2>What you can use</h2>
					<ul>
						<li>Headings, paragraphs, bold and italic</li>
						<li>Ordered and unordered lists</li>
						<li>Links, images and blockquotes</li>
						<li>Inline <code>code</code> and preformatted blocks</li>
					</ul>
					<h3>A note on drafts</h3>
					<p>An article stays invisible to readers until its status is Published, so you can write over several sittings without anyone seeing an unfinished page.</p>',
			),
			array(
				'title'        => __( 'Organising categories', 'minidocs' ),
				'category_id'  => $category_ids[1] ?? 0,
				'status'       => KNOWLIO_ARTICLE_STATUS_PUBLISHED,
				'order_number' => 2,
				'excerpt'      => __( 'Ordering, icons, and what happens when you delete a category.', 'minidocs' ),
				'content'      => '<p>Categories group articles and give the knowledge base its shape.</p>
					<h2>Ordering</h2>
					<p>The <strong>Order</strong> field controls position: lower numbers come first, and ties fall back to alphabetical order. The same applies to articles within a category.</p>
					<h2>Deleting a category</h2>
					<p>Deleting a category does not delete its articles. They are moved to <em>Uncategorised</em> and stay published, so you can never lose content by reorganising.</p>',
			),
			array(
				'title'        => __( 'My articles are not showing on the site', 'minidocs' ),
				'category_id'  => $category_ids[2] ?? 0,
				'status'       => KNOWLIO_ARTICLE_STATUS_PUBLISHED,
				'order_number' => 1,
				'excerpt'      => __( 'The three usual causes, in the order worth checking them.', 'minidocs' ),
				'content'      => '<p>If a page shows an empty knowledge base, work through these in order.</p>
					<h2>1. Check the status</h2>
					<p>Only articles set to <strong>Published</strong> appear on the frontend. Filter the article list by status to see what is still in draft.</p>
					<h2>2. Check the shortcode</h2>
					<p>The page needs the <code>[knowlio]</code> shortcode. If you pasted it into a code block, WordPress renders it as text instead of running it.</p>
					<h2>3. Check the category</h2>
					<p>The landing page only shows categories that contain at least one published article. An empty category is hidden rather than shown as a dead end.</p>',
			),
			array(
				'title'        => __( 'Controlling who can edit documentation', 'minidocs' ),
				'category_id'  => $category_ids[0] ?? 0,
				'status'       => KNOWLIO_ARTICLE_STATUS_DRAFT,
				'order_number' => 2,
				'excerpt'      => __( 'Mapping the plugin capabilities onto your WordPress roles.', 'minidocs' ),
				'content'      => '<p>This article is a draft, so it does not appear on the frontend. That makes it a live demonstration of the status field.</p>
					<h2>Roles and capabilities</h2>
					<p>Under <strong>Settings</strong> you can grant each WordPress role the ability to view, create, edit or delete articles and categories. Administrators always have full access and cannot be locked out.</p>',
			),
		);

		foreach ( $articles as $data ) {
			$article = new KnowlioArticleModel();
			$article->set_data( $data );
			$article->save();
		}

		KnowlioSettingsHelper::save_setting( 'is_database_seeded', 'on' );
	}

}
