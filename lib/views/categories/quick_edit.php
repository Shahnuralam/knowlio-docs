<?php
/**
 * Category editor, rendered inside the side panel.
 *
 * @package MiniDocs
 *
 * @var MdCategoryModel $category
 * @var array           $icons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$md_is_new = $category->is_new_record();
$md_route  = $md_is_new
	? MdRouterHelper::build_route_name( 'categories', 'create' )
	: MdRouterHelper::build_route_name( 'categories', 'update' );
$md_nonce  = $md_is_new ? wp_create_nonce( 'new_category' ) : wp_create_nonce( 'edit_category_' . $category->id );
?>
<div class="md-form-w">
	<form action="#" class="md-form" data-route-name="<?php echo esc_attr( $md_route ); ?>">

		<div class="md-form-header">
			<h2><?php echo $md_is_new ? esc_html__( 'New Category', 'minidocs' ) : esc_html__( 'Edit Category', 'minidocs' ); ?></h2>
			<button type="button" class="md-side-panel-close" data-md-close-panel aria-label="<?php esc_attr_e( 'Close', 'minidocs' ); ?>">
				<i class="dashicons dashicons-no-alt"></i>
			</button>
		</div>

		<div class="md-form-content">

			<?php if ( ! $md_is_new ) { ?>
				<div class="md-record-meta">
					<span><?php esc_html_e( 'ID:', 'minidocs' ); ?></span>
					<strong>#<?php echo esc_html( $category->id ); ?></strong>
					<span class="md-record-meta-sep">&middot;</span>
					<span>
						<?php
						printf(
							/* translators: %d: number of articles. */
							esc_html( _n( '%d article', '%d articles', $category->get_articles_count(), 'minidocs' ) ),
							(int) $category->get_articles_count()
						);
						?>
					</span>
				</div>
			<?php } ?>

			<?php echo MdFormHelper::hidden_field( 'category[id]', $category->id ); // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped ?>
			<?php echo MdFormHelper::hidden_field( '_wpnonce', $md_nonce ); // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped ?>

			<?php
			echo MdFormHelper::text_field( // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped
				'category[name]',
				__( 'Name', 'minidocs' ),
				$category->name,
				array( 'placeholder' => __( 'e.g. Getting Started', 'minidocs' ) )
			);
			?>

			<?php
			echo MdFormHelper::textarea_field( // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped
				'category[description]',
				__( 'Description', 'minidocs' ),
				$category->description,
				array(
					'rows'        => 2,
					'description' => __( 'Shown under the name on the category card.', 'minidocs' ),
				)
			);
			?>

			<div class="md-form-group">
				<label><?php esc_html_e( 'Icon', 'minidocs' ); ?></label>
				<div class="md-icon-picker">
					<?php
					$md_current = $category->get_icon_class();

					foreach ( $icons as $md_icon_class => $md_icon_label ) {
						?>
						<label class="md-icon-option" title="<?php echo esc_attr( $md_icon_label ); ?>">
							<input type="radio"
								name="category[icon]"
								value="<?php echo esc_attr( $md_icon_class ); ?>"
								<?php checked( $md_current, $md_icon_class ); ?> />
							<i class="dashicons <?php echo esc_attr( $md_icon_class ); ?>"></i>
						</label>
						<?php
					}
					?>
				</div>
			</div>

			<div class="md-row">
				<div class="md-col-6">
					<?php
					echo MdFormHelper::number_field( // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped
						'category[order_number]',
						__( 'Order', 'minidocs' ),
						(int) $category->order_number,
						0,
						null,
						array( 'description' => __( 'Lower numbers come first.', 'minidocs' ) )
					);
					?>
				</div>
				<div class="md-col-6">
					<?php
					echo MdFormHelper::text_field( // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped
						'category[slug]',
						__( 'Slug', 'minidocs' ),
						$category->slug,
						array( 'description' => __( 'Derived from the name when blank.', 'minidocs' ) )
					);
					?>
				</div>
			</div>

			<?php if ( ! $md_is_new ) { ?>
				<div class="md-note-inline">
					<?php esc_html_e( 'Deleting this category will not delete its articles. They are moved to Uncategorised and stay published.', 'minidocs' ); ?>
				</div>
			<?php } ?>
		</div>

		<div class="md-form-footer">
			<button type="button" class="md-btn md-btn-outline" data-md-close-panel><?php esc_html_e( 'Cancel', 'minidocs' ); ?></button>
			<button type="submit" class="md-btn md-btn-primary">
				<?php echo $md_is_new ? esc_html__( 'Create Category', 'minidocs' ) : esc_html__( 'Save Changes', 'minidocs' ); ?>
			</button>
		</div>
	</form>
</div>
