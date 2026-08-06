<?php
/**
 * Articles listing.
 *
 * @package KnowlioDocs
 *
 * @var KnowlioArticleModel[] $articles
 * @var array            $statuses
 * @var array            $categories
 * @var array            $filter
 * @var int              $total_records
 * @var int              $total_pages
 * @var int              $current_page_number
 * @var int              $showing_from
 * @var int              $showing_to
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="knowlio-table-w" data-knowlio-table-route="<?php echo esc_attr( KnowlioRouterHelper::build_route_name( 'articles', 'index' ) ); ?>">

	<div class="knowlio-table-toolbar">
		<div class="knowlio-table-heading">
			<h2><?php esc_html_e( 'Articles', 'minidocs' ); ?></h2>
			<div class="knowlio-pagination-info">
				<?php
				printf(
					/* translators: 1: first row number, 2: last row number, 3: total rows. */
					esc_html__( 'Showing %1$s-%2$s of %3$s', 'minidocs' ),
					'<span class="knowlio-pagination-from">' . esc_html( $showing_from ) . '</span>',
					'<span class="knowlio-pagination-to">' . esc_html( $showing_to ) . '</span>',
					'<span class="knowlio-pagination-total">' . esc_html( $total_records ) . '</span>'
				);
				?>
			</div>
		</div>

		<div class="knowlio-table-actions">
			<?php if ( KnowlioSettingsHelper::can_download_csv() ) { ?>
				<a href="<?php echo esc_url( KnowlioRouterHelper::build_admin_post_link( array( 'articles', 'index' ), array( 'download' => 'csv', '_wpnonce' => wp_create_nonce( 'export_articles' ) ) ) ); ?>"
					class="knowlio-btn knowlio-btn-outline" target="_blank" rel="noopener">
					<i class="dashicons dashicons-download"></i>
					<span><?php esc_html_e( 'Export .csv', 'minidocs' ); ?></span>
				</a>
			<?php } ?>

			<?php if ( KnowlioRolesHelper::current_user_can( array( 'article__create' ) ) ) { ?>
				<a href="#" class="knowlio-btn knowlio-btn-primary" <?php echo KnowlioArticlesHelper::quick_edit_btn_atts(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Attribute string built and escaped by KnowlioUtilHelper::build_action_atts(). ?>>
					<i class="dashicons dashicons-plus-alt2"></i>
					<span><?php esc_html_e( 'New Article', 'minidocs' ); ?></span>
				</a>
			<?php } ?>
		</div>
	</div>

	<div class="knowlio-table-scroll">
		<table class="knowlio-table">
			<thead>
				<tr>
					<th class="knowlio-col-narrow"><?php esc_html_e( 'ID', 'minidocs' ); ?></th>
					<th class="text-left"><?php esc_html_e( 'Title', 'minidocs' ); ?></th>
					<th class="text-left"><?php esc_html_e( 'Category', 'minidocs' ); ?></th>
					<th><?php esc_html_e( 'Status', 'minidocs' ); ?></th>
					<th><?php esc_html_e( 'Views', 'minidocs' ); ?></th>
					<th><?php esc_html_e( 'Updated', 'minidocs' ); ?></th>
					<th class="knowlio-col-narrow"></th>
				</tr>
				<tr class="knowlio-filter-row">
					<th></th>
					<th>
						<?php
						echo wp_kses(
							KnowlioFormHelper::text_field(
								'filter[title]',
								'',
								$filter['title'] ?? '',
								array(
									'class'       => 'knowlio-table-filter',
									'placeholder' => __( 'Search by title...', 'minidocs' ),
								)
							),
							KnowlioFormHelper::allowed_html()
						);
						?>
					</th>
					<th>
						<?php
						echo wp_kses(
							KnowlioFormHelper::select_field(
								'filter[category_id]',
								'',
								$categories,
								$filter['category_id'] ?? '',
								array(
									'class'       => 'knowlio-table-filter',
									'placeholder' => __( 'All categories', 'minidocs' ),
								)
							),
							KnowlioFormHelper::allowed_html()
						);
						?>
					</th>
					<th>
						<?php
						echo wp_kses(
							KnowlioFormHelper::select_field(
								'filter[status]',
								'',
								$statuses,
								$filter['status'] ?? '',
								array(
									'class'       => 'knowlio-table-filter',
									'placeholder' => __( 'Any status', 'minidocs' ),
								)
							),
							KnowlioFormHelper::allowed_html()
						);
						?>
					</th>
					<th colspan="3"></th>
				</tr>
			</thead>
			<tbody class="knowlio-table-body">
				<?php include '_table_body.php'; ?>
			</tbody>
		</table>
	</div>

	<div class="knowlio-pagination-w">
		<div class="knowlio-pagination-info">
			<?php
			printf(
				/* translators: 1: first row number, 2: last row number, 3: total rows. */
				esc_html__( 'Showing %1$s-%2$s of %3$s', 'minidocs' ),
				'<span class="knowlio-pagination-from">' . esc_html( $showing_from ) . '</span>',
				'<span class="knowlio-pagination-to">' . esc_html( $showing_to ) . '</span>',
				'<span class="knowlio-pagination-total">' . esc_html( $total_records ) . '</span>'
			);
			?>
		</div>
		<div class="knowlio-pagination-pages">
			<label for="knowlioArticlePagePicker"><?php esc_html_e( 'Page:', 'minidocs' ); ?></label>
			<select id="knowlioArticlePagePicker" class="knowlio-input knowlio-page-picker">
				<?php for ( $knowlio_i = 1; $knowlio_i <= $total_pages; $knowlio_i++ ) { ?>
					<option value="<?php echo esc_attr( $knowlio_i ); ?>" <?php selected( $current_page_number, $knowlio_i ); ?>><?php echo esc_html( $knowlio_i ); ?></option>
				<?php } ?>
			</select>
		</div>
	</div>
</div>
