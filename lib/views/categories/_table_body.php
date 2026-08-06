<?php
/**
 * Category table rows.
 *
 * @package KnowlioDocs
 *
 * @var KnowlioCategoryModel[] $categories
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $categories ) ) {
	?>
	<tr class="knowlio-no-results-row">
		<td colspan="6">
			<div class="knowlio-empty-state">
				<i class="dashicons dashicons-category"></i>
				<h2><?php esc_html_e( 'No categories yet', 'minidocs' ); ?></h2>
				<p><?php esc_html_e( 'Categories are the cards readers see on the knowledge base landing page.', 'minidocs' ); ?></p>
			</div>
		</td>
	</tr>
	<?php
	return;
}

foreach ( $categories as $knowlio_category ) {
	?>
	<tr class="knowlio-clickable-row" <?php echo KnowlioCategoriesHelper::quick_edit_btn_atts( $knowlio_category->id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<td class="knowlio-col-narrow knowlio-col-faded"><?php echo esc_html( $knowlio_category->order_number ); ?></td>
		<td class="text-left">
			<span class="knowlio-cat-cell">
				<i class="dashicons <?php echo esc_attr( $knowlio_category->get_icon_class() ); ?>"></i>
				<span>
					<span class="knowlio-cell-strong"><?php echo esc_html( $knowlio_category->name ); ?></span>
					<?php if ( $knowlio_category->description ) { ?>
						<span class="knowlio-cell-sub"><?php echo esc_html( wp_trim_words( $knowlio_category->description, 14 ) ); ?></span>
					<?php } ?>
				</span>
			</span>
		</td>
		<td class="text-left knowlio-col-faded"><?php echo esc_html( $knowlio_category->slug ); ?></td>
		<td><?php echo esc_html( $knowlio_category->get_articles_count() ); ?></td>
		<td class="knowlio-col-faded"><?php echo esc_html( $knowlio_category->get_articles_count( true ) ); ?></td>
		<td class="knowlio-col-narrow">
			<?php if ( KnowlioRolesHelper::current_user_can( array( 'category__delete' ) ) ) { ?>
				<button type="button"
					class="knowlio-row-delete"
					data-knowlio-delete="<?php echo esc_attr( KnowlioRouterHelper::build_route_name( 'categories', 'destroy' ) ); ?>"
					data-knowlio-id="<?php echo esc_attr( $knowlio_category->id ); ?>"
					data-knowlio-nonce="<?php echo esc_attr( wp_create_nonce( 'destroy_category_' . $knowlio_category->id ) ); ?>"
					aria-label="<?php esc_attr_e( 'Delete', 'minidocs' ); ?>">
					<i class="dashicons dashicons-trash"></i>
				</button>
			<?php } ?>
		</td>
	</tr>
	<?php
}
