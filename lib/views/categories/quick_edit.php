<?php
/**
 * Category editor, rendered inside the side panel.
 *
 * @package KnowlioDocs
 *
 * @var KnowlioCategoryModel $category
 * @var array           $icons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


$knowlio_is_new = $category->is_new_record();
$knowlio_route  = $knowlio_is_new
	? KnowlioRouterHelper::build_route_name( 'categories', 'create' )
	: KnowlioRouterHelper::build_route_name( 'categories', 'update' );
$knowlio_nonce  = $knowlio_is_new ? wp_create_nonce( 'new_category' ) : wp_create_nonce( 'edit_category_' . $category->id );
?>
<div class="knowlio-form-w">
	<form action="#" class="knowlio-form" data-route-name="<?php echo esc_attr( $knowlio_route ); ?>">

		<div class="knowlio-form-header">
			<h2><?php echo $knowlio_is_new ? esc_html__( 'New Category', 'minidocs' ) : esc_html__( 'Edit Category', 'minidocs' ); ?></h2>
			<button type="button" class="knowlio-side-panel-close" data-knowlio-close-panel aria-label="<?php esc_attr_e( 'Close', 'minidocs' ); ?>">
				<i class="dashicons dashicons-no-alt"></i>
			</button>
		</div>

		<div class="knowlio-form-content">

			<?php if ( ! $knowlio_is_new ) { ?>
				<div class="knowlio-record-meta">
					<span><?php esc_html_e( 'ID:', 'minidocs' ); ?></span>
					<strong>#<?php echo esc_html( $category->id ); ?></strong>
					<span class="knowlio-record-meta-sep">&middot;</span>
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

			<?php echo wp_kses( KnowlioFormHelper::hidden_field( 'category[id]', $category->id  ), KnowlioFormHelper::allowed_html() ); ?>
			<?php echo wp_kses( KnowlioFormHelper::hidden_field( '_wpnonce', $knowlio_nonce  ), KnowlioFormHelper::allowed_html() ); ?>

			<?php
			echo wp_kses(
				KnowlioFormHelper::text_field(
					'category[name]',
					__( 'Name', 'minidocs' ),
					$category->name,
					array( 'placeholder' => __( 'e.g. Getting Started', 'minidocs' ) )
				),
				KnowlioFormHelper::allowed_html()
			);
			?>

			<?php
			echo wp_kses(
				KnowlioFormHelper::textarea_field(
					'category[description]',
					__( 'Description', 'minidocs' ),
					$category->description,
					array(
						'rows'        => 2,
						'description' => __( 'Shown under the name on the category card.', 'minidocs' ),
					)
				),
				KnowlioFormHelper::allowed_html()
			);
			?>

			<div class="knowlio-form-group">
				<label><?php esc_html_e( 'Icon', 'minidocs' ); ?></label>
				<div class="knowlio-icon-picker">
					<?php
					$knowlio_current = $category->get_icon_class();

					foreach ( $icons as $knowlio_icon_class => $knowlio_icon_label ) {
						?>
						<label class="knowlio-icon-option" title="<?php echo esc_attr( $knowlio_icon_label ); ?>">
							<input type="radio"
								name="category[icon]"
								value="<?php echo esc_attr( $knowlio_icon_class ); ?>"
								<?php checked( $knowlio_current, $knowlio_icon_class ); ?> />
							<i class="dashicons <?php echo esc_attr( $knowlio_icon_class ); ?>"></i>
						</label>
						<?php
					}
					?>
				</div>
			</div>

			<div class="knowlio-row">
				<div class="knowlio-col-6">
					<?php
					echo wp_kses(
						KnowlioFormHelper::number_field(
							'category[order_number]',
							__( 'Order', 'minidocs' ),
							(int) $category->order_number,
							0,
							null,
							array( 'description' => __( 'Lower numbers come first.', 'minidocs' ) )
						),
						KnowlioFormHelper::allowed_html()
					);
					?>
				</div>
				<div class="knowlio-col-6">
					<?php
					echo wp_kses(
						KnowlioFormHelper::text_field(
							'category[slug]',
							__( 'Slug', 'minidocs' ),
							$category->slug,
							array( 'description' => __( 'Derived from the name when blank.', 'minidocs' ) )
						),
						KnowlioFormHelper::allowed_html()
					);
					?>
				</div>
			</div>

			<?php if ( ! $knowlio_is_new ) { ?>
				<div class="knowlio-note-inline">
					<?php esc_html_e( 'Deleting this category will not delete its articles. They are moved to Uncategorised and stay published.', 'minidocs' ); ?>
				</div>
			<?php } ?>
		</div>

		<div class="knowlio-form-footer">
			<button type="button" class="knowlio-btn knowlio-btn-outline" data-knowlio-close-panel><?php esc_html_e( 'Cancel', 'minidocs' ); ?></button>
			<button type="submit" class="knowlio-btn knowlio-btn-primary">
				<?php echo $knowlio_is_new ? esc_html__( 'Create Category', 'minidocs' ) : esc_html__( 'Save Changes', 'minidocs' ); ?>
			</button>
		</div>
	</form>
</div>
