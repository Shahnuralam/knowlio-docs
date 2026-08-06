<?php
/**
 * Article table rows.
 *
 * Rendered inside the full page for HTML requests, and returned on its own for
 * JSON requests when the filters refresh the table.
 *
 * @package KnowlioDocs
 *
 * @var KnowlioArticleModel[] $articles
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $articles ) ) {
	?>
	<tr class="knowlio-no-results-row">
		<td colspan="7">
			<div class="knowlio-empty-state">
				<i class="dashicons dashicons-media-document"></i>
				<h2><?php esc_html_e( 'No articles found', 'minidocs' ); ?></h2>
				<p><?php esc_html_e( 'Adjust the filters above, or write your first article.', 'minidocs' ); ?></p>
			</div>
		</td>
	</tr>
	<?php
	return;
}

foreach ( $articles as $knowlio_article ) {
	?>
	<tr class="knowlio-clickable-row" <?php echo KnowlioArticlesHelper::quick_edit_btn_atts( $knowlio_article->id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<td class="knowlio-col-narrow knowlio-col-faded"><?php echo esc_html( $knowlio_article->id ); ?></td>
		<td class="text-left">
			<span class="knowlio-cell-strong">
				<?php if ( $knowlio_article->is_featured() ) { ?>
					<i class="dashicons dashicons-star-filled knowlio-featured-star" title="<?php esc_attr_e( 'Featured', 'minidocs' ); ?>"></i>
				<?php } ?>
				<?php echo esc_html( $knowlio_article->title ); ?>
			</span>
			<span class="knowlio-cell-sub"><?php echo esc_html( $knowlio_article->get_summary( 12 ) ); ?></span>
		</td>
		<td class="text-left"><?php echo esc_html( $knowlio_article->get_category_name() ); ?></td>
		<td>
			<span class="knowlio-status knowlio-status-<?php echo esc_attr( $knowlio_article->status ); ?>">
				<?php echo esc_html( $knowlio_article->get_nice_status() ); ?>
			</span>
		</td>
		<td class="knowlio-col-faded"><?php echo esc_html( number_format_i18n( (int) $knowlio_article->views_count ) ); ?></td>
		<td class="knowlio-col-faded"><?php echo esc_html( $knowlio_article->formatted_updated_date() ); ?></td>
		<td class="knowlio-col-narrow">
			<?php if ( KnowlioRolesHelper::current_user_can( array( 'article__delete' ) ) ) { ?>
				<button type="button"
					class="knowlio-row-delete"
					data-knowlio-delete="<?php echo esc_attr( KnowlioRouterHelper::build_route_name( 'articles', 'destroy' ) ); ?>"
					data-knowlio-id="<?php echo esc_attr( $knowlio_article->id ); ?>"
					data-knowlio-nonce="<?php echo esc_attr( wp_create_nonce( 'destroy_article_' . $knowlio_article->id ) ); ?>"
					aria-label="<?php esc_attr_e( 'Delete', 'minidocs' ); ?>">
					<i class="dashicons dashicons-trash"></i>
				</button>
			<?php } ?>
		</td>
	</tr>
	<?php
}
