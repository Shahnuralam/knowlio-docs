<?php
/**
 * Category table rows.
 *
 * @package MiniDocs
 *
 * @var MdCategoryModel[] $categories
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $categories ) ) {
	?>
	<tr class="md-no-results-row">
		<td colspan="6">
			<div class="md-empty-state">
				<i class="dashicons dashicons-category"></i>
				<h2><?php esc_html_e( 'No categories yet', 'minidocs' ); ?></h2>
				<p><?php esc_html_e( 'Categories are the cards readers see on the knowledge base landing page.', 'minidocs' ); ?></p>
			</div>
		</td>
	</tr>
	<?php
	return;
}

foreach ( $categories as $md_category ) {
	?>
	<tr class="md-clickable-row" <?php echo MdCategoriesHelper::quick_edit_btn_atts( $md_category->id ); // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped ?>>
		<td class="md-col-narrow md-col-faded"><?php echo esc_html( $md_category->order_number ); ?></td>
		<td class="text-left">
			<span class="md-cat-cell">
				<i class="dashicons <?php echo esc_attr( $md_category->get_icon_class() ); ?>"></i>
				<span>
					<span class="md-cell-strong"><?php echo esc_html( $md_category->name ); ?></span>
					<?php if ( $md_category->description ) { ?>
						<span class="md-cell-sub"><?php echo esc_html( wp_trim_words( $md_category->description, 14 ) ); ?></span>
					<?php } ?>
				</span>
			</span>
		</td>
		<td class="text-left md-col-faded"><?php echo esc_html( $md_category->slug ); ?></td>
		<td><?php echo esc_html( $md_category->get_articles_count() ); ?></td>
		<td class="md-col-faded"><?php echo esc_html( $md_category->get_articles_count( true ) ); ?></td>
		<td class="md-col-narrow">
			<?php if ( MdRolesHelper::current_user_can( array( 'category__delete' ) ) ) { ?>
				<button type="button"
					class="md-row-delete"
					data-md-delete="<?php echo esc_attr( MdRouterHelper::build_route_name( 'categories', 'destroy' ) ); ?>"
					data-md-id="<?php echo esc_attr( $md_category->id ); ?>"
					data-md-nonce="<?php echo esc_attr( wp_create_nonce( 'destroy_category_' . $md_category->id ) ); ?>"
					aria-label="<?php esc_attr_e( 'Delete', 'minidocs' ); ?>">
					<i class="dashicons dashicons-trash"></i>
				</button>
			<?php } ?>
		</td>
	</tr>
	<?php
}
