<?php
/**
 * Article or category not found.
 *
 * @package KnowlioDocs
 *
 * @var KnowlioCategoryModel[] $categories
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="knowlio-front knowlio-front-not-found <?php echo esc_attr( $layout_class ?? '' ); ?>" id="knowlio-docs">
	<div class="knowlio-front-inner" style="<?php echo esc_attr( $inner_style ?? '' ); ?>">
	<div class="knowlio-front-notice">
		<strong><?php esc_html_e( 'That page could not be found.', 'minidocs' ); ?></strong>
		<p><?php esc_html_e( 'It may have been renamed, unpublished, or removed.', 'minidocs' ); ?></p>
		<a class="knowlio-kb-search-btn" href="<?php echo esc_url( KnowlioShortcodesHelper::base_url() ); ?>#knowlio-docs"><?php esc_html_e( 'Back to documentation', 'minidocs' ); ?></a>
	</div>

	<?php if ( ! empty( $categories ) ) { ?>
		<div class="knowlio-cat-chips">
			<?php foreach ( $categories as $knowlio_category ) { ?>
				<a class="knowlio-cat-chip" href="<?php echo esc_url( KnowlioShortcodesHelper::category_url( $knowlio_category->slug ) ); ?>">
					<i class="dashicons <?php echo esc_attr( $knowlio_category->get_icon_class() ); ?>"></i>
					<span><?php echo esc_html( $knowlio_category->name ); ?></span>
				</a>
			<?php } ?>
		</div>
	<?php } ?>
	</div>
</div>
