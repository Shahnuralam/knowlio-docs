<?php
/**
 * Article list with a grid/list view switcher.
 *
 * Shared by the category and search screens so both offer the same control and
 * remember the same preference.
 *
 * Grid is the default because a single column of full-width rows wastes most of
 * the page on a wide screen. The chosen view is stored per reader in
 * localStorage by front.js; without JavaScript the grid still renders and the
 * switcher stays hidden rather than sitting there dead.
 *
 * @package KnowlioDocs
 *
 * @var KnowlioArticleModel[] $knowlio_list_articles Articles to render.
 * @var bool             $knowlio_list_show_category Show each article's category in the meta line.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$knowlio_list_articles      = $knowlio_list_articles ?? array();
$knowlio_list_show_category = $knowlio_list_show_category ?? false;

if ( empty( $knowlio_list_articles ) ) {
	return;
}
?>
<div class="knowlio-list-head" data-knowlio-list-head hidden>
	<div class="knowlio-view-switch" role="group" aria-label="<?php esc_attr_e( 'Choose how articles are displayed', 'minidocs' ); ?>">
		<button type="button" class="knowlio-view-btn is-active" data-knowlio-view="grid" aria-pressed="true">
			<i class="dashicons dashicons-grid-view" aria-hidden="true"></i>
			<span><?php esc_html_e( 'Grid', 'minidocs' ); ?></span>
		</button>
		<button type="button" class="knowlio-view-btn" data-knowlio-view="list" aria-pressed="false">
			<i class="dashicons dashicons-list-view" aria-hidden="true"></i>
			<span><?php esc_html_e( 'List', 'minidocs' ); ?></span>
		</button>
	</div>
</div>

<ul class="knowlio-article-list knowlio-article-list-compact knowlio-view-grid" data-knowlio-article-list>
	<?php foreach ( $knowlio_list_articles as $knowlio_article ) { ?>
		<li>
			<a href="<?php echo esc_url( KnowlioShortcodesHelper::article_url( $knowlio_article->slug ) ); ?>">
				<span class="knowlio-article-list-title"><?php echo esc_html( $knowlio_article->title ); ?></span>

				<?php if ( $knowlio_article->get_summary( 18 ) ) { ?>
					<span class="knowlio-article-list-desc"><?php echo esc_html( $knowlio_article->get_summary( 18 ) ); ?></span>
				<?php } ?>

				<span class="knowlio-article-list-meta">
					<?php if ( $knowlio_list_show_category ) { ?>
						<span class="knowlio-article-list-cat"><?php echo esc_html( $knowlio_article->get_category_name() ); ?></span>
						<span class="knowlio-article-list-sep">&middot;</span>
					<?php } ?>
					<?php
					printf(
						/* translators: %d: minutes. */
						esc_html( _n( '%d min read', '%d min read', $knowlio_article->get_reading_time(), 'minidocs' ) ),
						(int) $knowlio_article->get_reading_time()
					);
					?>
				</span>
			</a>
		</li>
	<?php } ?>
</ul>
