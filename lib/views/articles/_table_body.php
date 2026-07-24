<?php
/**
 * Article table rows.
 *
 * Rendered inside the full page for HTML requests, and returned on its own for
 * JSON requests when the filters refresh the table.
 *
 * @package MiniDocs
 *
 * @var MdArticleModel[] $articles
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $articles ) ) {
	?>
	<tr class="md-no-results-row">
		<td colspan="7">
			<div class="md-empty-state">
				<i class="dashicons dashicons-media-document"></i>
				<h2><?php esc_html_e( 'No articles found', 'minidocs' ); ?></h2>
				<p><?php esc_html_e( 'Adjust the filters above, or write your first article.', 'minidocs' ); ?></p>
			</div>
		</td>
	</tr>
	<?php
	return;
}

foreach ( $articles as $md_article ) {
	?>
	<tr class="md-clickable-row" <?php echo MdArticlesHelper::quick_edit_btn_atts( $md_article->id ); // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped ?>>
		<td class="md-col-narrow md-col-faded"><?php echo esc_html( $md_article->id ); ?></td>
		<td class="text-left">
			<span class="md-cell-strong">
				<?php if ( $md_article->is_featured() ) { ?>
					<i class="dashicons dashicons-star-filled md-featured-star" title="<?php esc_attr_e( 'Featured', 'minidocs' ); ?>"></i>
				<?php } ?>
				<?php echo esc_html( $md_article->title ); ?>
			</span>
			<span class="md-cell-sub"><?php echo esc_html( $md_article->get_summary( 12 ) ); ?></span>
		</td>
		<td class="text-left"><?php echo esc_html( $md_article->get_category_name() ); ?></td>
		<td>
			<span class="md-status md-status-<?php echo esc_attr( $md_article->status ); ?>">
				<?php echo esc_html( $md_article->get_nice_status() ); ?>
			</span>
		</td>
		<td class="md-col-faded"><?php echo esc_html( number_format_i18n( (int) $md_article->views_count ) ); ?></td>
		<td class="md-col-faded"><?php echo esc_html( $md_article->formatted_updated_date() ); ?></td>
		<td class="md-col-narrow">
			<?php if ( MdRolesHelper::current_user_can( array( 'article__delete' ) ) ) { ?>
				<button type="button"
					class="md-row-delete"
					data-md-delete="<?php echo esc_attr( MdRouterHelper::build_route_name( 'articles', 'destroy' ) ); ?>"
					data-md-id="<?php echo esc_attr( $md_article->id ); ?>"
					data-md-nonce="<?php echo esc_attr( wp_create_nonce( 'destroy_article_' . $md_article->id ) ); ?>"
					aria-label="<?php esc_attr_e( 'Delete', 'minidocs' ); ?>">
					<i class="dashicons dashicons-trash"></i>
				</button>
			<?php } ?>
		</td>
	</tr>
	<?php
}
