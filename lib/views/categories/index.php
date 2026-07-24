<?php
/**
 * Categories listing.
 *
 * @package MiniDocs
 *
 * @var MdCategoryModel[] $categories
 * @var array             $filter
 * @var int               $total_records
 * @var int               $total_pages
 * @var int               $current_page_number
 * @var int               $showing_from
 * @var int               $showing_to
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="md-table-w" data-md-table-route="<?php echo esc_attr( MdRouterHelper::build_route_name( 'categories', 'index' ) ); ?>">

	<div class="md-table-toolbar">
		<div class="md-table-heading">
			<h2><?php esc_html_e( 'Categories', 'minidocs' ); ?></h2>
			<div class="md-pagination-info">
				<?php
				printf(
					/* translators: 1: first row number, 2: last row number, 3: total rows. */
					esc_html__( 'Showing %1$s-%2$s of %3$s', 'minidocs' ),
					'<span class="md-pagination-from">' . esc_html( $showing_from ) . '</span>',
					'<span class="md-pagination-to">' . esc_html( $showing_to ) . '</span>',
					'<span class="md-pagination-total">' . esc_html( $total_records ) . '</span>'
				);
				?>
			</div>
		</div>

		<div class="md-table-actions">
			<?php if ( MdRolesHelper::current_user_can( array( 'category__create' ) ) ) { ?>
				<a href="#" class="md-btn md-btn-primary" <?php echo MdCategoriesHelper::quick_edit_btn_atts(); // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped ?>>
					<i class="dashicons dashicons-plus-alt2"></i>
					<span><?php esc_html_e( 'New Category', 'minidocs' ); ?></span>
				</a>
			<?php } ?>
		</div>
	</div>

	<div class="md-table-scroll">
		<table class="md-table">
			<thead>
				<tr>
					<th class="md-col-narrow"><?php esc_html_e( 'Order', 'minidocs' ); ?></th>
					<th class="text-left"><?php esc_html_e( 'Category', 'minidocs' ); ?></th>
					<th class="text-left"><?php esc_html_e( 'Slug', 'minidocs' ); ?></th>
					<th><?php esc_html_e( 'Articles', 'minidocs' ); ?></th>
					<th><?php esc_html_e( 'Published', 'minidocs' ); ?></th>
					<th class="md-col-narrow"></th>
				</tr>
				<tr class="md-filter-row">
					<th></th>
					<th>
						<?php
						echo MdFormHelper::text_field( // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped
							'filter[name]',
							'',
							$filter['name'] ?? '',
							array(
								'class'       => 'md-table-filter',
								'placeholder' => __( 'Search by name...', 'minidocs' ),
							)
						);
						?>
					</th>
					<th colspan="4"></th>
				</tr>
			</thead>
			<tbody class="md-table-body">
				<?php include '_table_body.php'; ?>
			</tbody>
		</table>
	</div>

	<div class="md-pagination-w">
		<div class="md-pagination-info">
			<?php
			printf(
				/* translators: 1: first row number, 2: last row number, 3: total rows. */
				esc_html__( 'Showing %1$s-%2$s of %3$s', 'minidocs' ),
				'<span class="md-pagination-from">' . esc_html( $showing_from ) . '</span>',
				'<span class="md-pagination-to">' . esc_html( $showing_to ) . '</span>',
				'<span class="md-pagination-total">' . esc_html( $total_records ) . '</span>'
			);
			?>
		</div>
		<div class="md-pagination-pages">
			<label for="mdCategoryPagePicker"><?php esc_html_e( 'Page:', 'minidocs' ); ?></label>
			<select id="mdCategoryPagePicker" class="md-input md-page-picker">
				<?php for ( $md_i = 1; $md_i <= $total_pages; $md_i++ ) { ?>
					<option value="<?php echo esc_attr( $md_i ); ?>" <?php selected( $current_page_number, $md_i ); ?>><?php echo esc_html( $md_i ); ?></option>
				<?php } ?>
			</select>
		</div>
	</div>
</div>
