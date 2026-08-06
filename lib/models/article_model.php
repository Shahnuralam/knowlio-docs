<?php
/**
 * Knowledge base article.
 *
 * @package KnowlioDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class KnowlioArticleModel
 */
class KnowlioArticleModel extends KnowlioModel {

	/**
	 * Primary key.
	 *
	 * @var int
	 */
	public $id;

	/**
	 * Article title.
	 *
	 * @var string
	 */
	public $title;

	/**
	 * URL slug.
	 *
	 * @var string
	 */
	public $slug;

	/**
	 * Short summary shown in listings and search results.
	 *
	 * @var string
	 */
	public $excerpt;

	/**
	 * Article body, stored as sanitized HTML.
	 *
	 * @var string
	 */
	public $content;

	/**
	 * Owning category id.
	 *
	 * @var int
	 */
	public $category_id;

	/**
	 * Status: draft or published.
	 *
	 * @var string
	 */
	public $status;

	/**
	 * Manual sort position within the category.
	 *
	 * @var int
	 */
	public $order_number;

	/**
	 * Pinned to the knowledge base landing page.
	 *
	 * @var int
	 */
	public $is_featured;

	/**
	 * Read counter.
	 *
	 * @var int
	 */
	public $views_count;

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
	 * Cached category instance.
	 *
	 * @var KnowlioCategoryModel|null
	 */
	private $category_object = null;

	/**
	 * Constructor.
	 *
	 * @param int|false $id Optional record id.
	 */
	public function __construct( $id = false ) {
		parent::__construct();

		$this->table_name = KNOWLIO_TABLE_ARTICLES;

		$this->nice_names = array(
			'title'        => __( 'Title', 'minidocs' ),
			'slug'         => __( 'Slug', 'minidocs' ),
			'excerpt'      => __( 'Excerpt', 'minidocs' ),
			'content'      => __( 'Content', 'minidocs' ),
			'category_id'  => __( 'Category', 'minidocs' ),
			'status'       => __( 'Status', 'minidocs' ),
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
		return array( 'id', 'title', 'slug', 'excerpt', 'content', 'category_id', 'status', 'order_number', 'is_featured' );
	}

	/**
	 * Columns written to the table.
	 *
	 * @param string $role Role.
	 *
	 * @return array
	 */
	protected function params_to_save( string $role = 'admin' ): array {
		return array( 'title', 'slug', 'excerpt', 'content', 'category_id', 'status', 'order_number', 'is_featured' );
	}

	/**
	 * Per-property sanitization rules.
	 *
	 * `content` uses the `html` rule (wp_kses_post) rather than `text`, because
	 * article bodies are meant to contain markup.
	 *
	 * @return array
	 */
	protected function params_to_sanitize(): array {
		return array(
			'title'        => 'text',
			'slug'         => 'slug',
			'excerpt'      => 'textarea',
			'content'      => 'html',
			'category_id'  => 'absint',
			'status'       => 'key',
			'order_number' => 'absint',
			'is_featured'  => 'bool',
		);
	}

	/**
	 * Declarative validations.
	 *
	 * @return array
	 */
	protected function properties_to_validate(): array {
		return array(
			'title'  => array( 'presence' ),
			'slug'   => array( 'presence', 'uniqueness' ),
			'status' => array( 'inclusion' ),
		);
	}

	/**
	 * Allowed values for the `inclusion` validation.
	 *
	 * @param string $property Property.
	 *
	 * @return array
	 */
	protected function allowed_values_for( string $property ): array {
		if ( 'status' === $property ) {
			return array_keys( KnowlioArticlesHelper::get_statuses_list() );
		}

		return array();
	}

	/**
	 * Fill in defaults before validation.
	 */
	protected function set_defaults() {
		if ( empty( $this->status ) ) {
			$this->status = KNOWLIO_ARTICLE_STATUS_DRAFT;
		}

		// 0 is the Uncategorised bucket. The column is NOT NULL, so a payload
		// that simply omits the category has to land here rather than on a
		// database error.
		if ( ! isset( $this->category_id ) || '' === $this->category_id ) {
			$this->category_id = 0;
		}

		if ( ! isset( $this->order_number ) || '' === $this->order_number ) {
			$this->order_number = 0;
		}

		if ( ! isset( $this->views_count ) ) {
			$this->views_count = 0;
		}

		if ( ! isset( $this->is_featured ) || '' === $this->is_featured ) {
			$this->is_featured = 0;
		}

		if ( empty( $this->slug ) && ! empty( $this->title ) ) {
			$this->slug = $this->generate_unique_slug( KnowlioUtilHelper::slugify( $this->title ) );
		}
	}

	/**
	 * Derive an excerpt when the author did not write one.
	 */
	protected function before_save() {
		if ( empty( $this->excerpt ) && ! empty( $this->content ) ) {
			$this->excerpt = wp_trim_words( wp_strip_all_tags( (string) $this->content ), 28 );
		}
	}

	/**
	 * Append a counter until the slug is free.
	 *
	 * @param string $base_slug Starting slug.
	 *
	 * @return string
	 */
	private function generate_unique_slug( string $base_slug ): string {
		if ( '' === $base_slug ) {
			$base_slug = 'article-' . KnowlioUtilHelper::random_text( 6 );
		}

		$slug    = $base_slug;
		$counter = 2;

		while ( $this->slug_exists( $slug ) ) {
			$slug = $base_slug . '-' . $counter;
			++$counter;

			if ( $counter > 200 ) {
				$slug = $base_slug . '-' . KnowlioUtilHelper::random_text( 6 );
				break;
			}
		}

		return $slug;
	}

	/**
	 * Does another record already use this slug?
	 *
	 * @param string $slug Slug.
	 *
	 * @return bool
	 */
	private function slug_exists( string $slug ): bool {
		$query = $this->is_new_record()
			? $this->db->prepare( 'SELECT id FROM ' . $this->table_name . ' WHERE slug = %s LIMIT 1', $slug ) // phpcs:ignore WordPress.DB.PreparedSQL
			: $this->db->prepare( 'SELECT id FROM ' . $this->table_name . ' WHERE slug = %s AND id != %d LIMIT 1', $slug, $this->id ); // phpcs:ignore WordPress.DB.PreparedSQL

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
		return (bool) $this->db->get_var( $query );
	}

	/* --------------------------------------------------------------------- */
	/* Scopes                                                                */
	/* --------------------------------------------------------------------- */

	/**
	 * Scope: published articles only.
	 *
	 * @return $this
	 */
	public function should_be_published() {
		return $this->where( array( 'status' => KNOWLIO_ARTICLE_STATUS_PUBLISHED ) );
	}

	/**
	 * Scope: featured articles only.
	 *
	 * @return $this
	 */
	public function should_be_featured() {
		return $this->where( array( 'is_featured' => 1 ) );
	}

	/**
	 * Load an article by slug.
	 *
	 * @param string $slug Slug.
	 *
	 * @return static|null
	 */
	public function load_by_slug( string $slug ) {
		return $this->where( array( 'slug' => $slug ) )->get_first();
	}

	/**
	 * Increment the read counter.
	 *
	 * Written with a single atomic UPDATE rather than read-modify-write, so
	 * concurrent readers cannot lose counts.
	 */
	public function register_view() {
		if ( $this->is_new_record() ) {
			return;
		}

		/**
		 * Filters whether this article view is counted.
		 *
		 * Returning false skips the write, which is what a full-page cache or a
		 * bot filter wants: the counter is the only thing that makes an article
		 * page non-idempotent.
		 *
		 * @since 1.0.0
		 * @hook knowlio_should_register_view
		 *
		 * @param bool           $should_register Whether to count the view.
		 * @param KnowlioArticleModel $article         Article being viewed.
		 */
		if ( ! apply_filters( 'knowlio_should_register_view', true, $this ) ) {
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
		$this->db->query(
			$this->db->prepare( 'UPDATE ' . $this->table_name . ' SET views_count = views_count + 1 WHERE id = %d', $this->id ) // phpcs:ignore WordPress.DB.PreparedSQL
		);

		$this->views_count = (int) $this->views_count + 1;
	}

	/* --------------------------------------------------------------------- */
	/* Presentation                                                          */
	/* --------------------------------------------------------------------- */

	/**
	 * The owning category, loaded once per instance.
	 *
	 * @return KnowlioCategoryModel
	 */
	public function get_category() {
		if ( is_null( $this->category_object ) ) {
			$this->category_object = new KnowlioCategoryModel( absint( $this->category_id ) );
		}

		return $this->category_object;
	}

	/**
	 * Category name, or a placeholder when uncategorised.
	 *
	 * @return string
	 */
	public function get_category_name(): string {
		$category = $this->get_category();

		return $category->is_new_record() ? __( 'Uncategorised', 'minidocs' ) : (string) $category->name;
	}

	/**
	 * Readable status label.
	 *
	 * @return string
	 */
	public function get_nice_status(): string {
		return KnowlioArticlesHelper::get_status_label( (string) $this->status );
	}

	/**
	 * Article body, filtered for output.
	 *
	 * @return string
	 */
	public function get_rendered_content(): string {
		$content = wpautop( wp_kses_post( (string) $this->content ) );

		/*
		 * The editor inserts the full-resolution image, so without this a reader
		 * downloads the original megabyte-sized file just to see it scaled down
		 * in an ~900px column. wp_filter_content_tags() adds srcset/sizes (the
		 * browser then fetches an appropriately sized crop), plus loading="lazy"
		 * and decoding="async". The sizes hint is capped to the reading column
		 * so the chosen source is not larger than it will ever be displayed.
		 */
		if ( function_exists( 'wp_filter_content_tags' ) ) {
			$sizes_hint = static function () {
				return '(max-width: 920px) 100vw, 920px';
			};

			add_filter( 'wp_calculate_image_sizes', $sizes_hint, 20 );
			$content = wp_filter_content_tags( $content, 'knowlio_article_body' );
			remove_filter( 'wp_calculate_image_sizes', $sizes_hint, 20 );
		}

		/**
		 * Filters an article body just before it is rendered.
		 *
		 * @since 1.0.0
		 * @hook knowlio_article_content
		 *
		 * @param string         $content Rendered HTML.
		 * @param KnowlioArticleModel $article Article.
		 */
		return (string) apply_filters( 'knowlio_article_content', $content, $this );
	}

	/**
	 * Excerpt, falling back to the body when empty.
	 *
	 * @param int $words Word count.
	 *
	 * @return string
	 */
	public function get_summary( int $words = 24 ): string {
		if ( ! empty( $this->excerpt ) ) {
			return wp_trim_words( (string) $this->excerpt, $words );
		}

		return wp_trim_words( wp_strip_all_tags( (string) $this->content ), $words );
	}

	/**
	 * Estimated reading time in minutes.
	 *
	 * Deliberately not `str_word_count()`: that function is byte-oriented and
	 * reports 0 for Bengali, Arabic, Cyrillic or Greek text, so every non-Latin
	 * article would claim to be a one-minute read.
	 *
	 * @return int
	 */
	public function get_reading_time(): int {
		$text = trim( wp_strip_all_tags( (string) $this->content ) );

		if ( '' === $text ) {
			return 1;
		}

		/*
		 * CJK scripts do not separate words with spaces, so a whole run of them
		 * would count as a single word. They are counted per character first and
		 * removed, then the remainder is counted the usual way.
		 */
		$cjk_pattern = '/[\x{3040}-\x{30FF}\x{3400}-\x{4DBF}\x{4E00}-\x{9FFF}\x{AC00}-\x{D7AF}\x{F900}-\x{FAFF}]/u';

		$cjk_characters = (int) preg_match_all( $cjk_pattern, $text );
		$remainder      = (string) preg_replace( $cjk_pattern, ' ', $text );

		// \p{L} letters, \p{N} digits, \p{M} the combining marks used by Indic
		// and Arabic scripts.
		$words = (int) preg_match_all( '/[\p{L}\p{N}\p{M}]+/u', $remainder );

		// Roughly two ideographs per word is the usual approximation.
		$words += (int) ceil( $cjk_characters / 2 );

		return max( 1, (int) ceil( $words / 200 ) );
	}

	/**
	 * Whether this article is pinned to the landing page.
	 *
	 * @return bool
	 */
	public function is_featured(): bool {
		return ! empty( $this->is_featured );
	}
}
