<?php
/**
 * Article or category not found.
 *
 * @package MiniDocs
 *
 * @var MdCategoryModel[] $categories
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="md-front md-front-not-found <?php echo esc_attr( $layout_class ?? '' ); ?>" id="minidocs">
	<div class="md-front-inner" style="<?php echo esc_attr( $inner_style ?? '' ); ?>">
	<div class="md-front-notice">
		<strong><?php esc_html_e( 'That page could not be found.', 'minidocs' ); ?></strong>
		<p><?php esc_html_e( 'It may have been renamed, unpublished, or removed.', 'minidocs' ); ?></p>
		<a class="md-kb-search-btn" href="<?php echo esc_url( MdShortcodesHelper::base_url() ); ?>#minidocs"><?php esc_html_e( 'Back to documentation', 'minidocs' ); ?></a>
	</div>

	<?php if ( ! empty( $categories ) ) { ?>
		<div class="md-cat-chips">
			<?php foreach ( $categories as $md_category ) { ?>
				<a class="md-cat-chip" href="<?php echo esc_url( MdShortcodesHelper::category_url( $md_category->slug ) ); ?>">
					<i class="dashicons <?php echo esc_attr( $md_category->get_icon_class() ); ?>"></i>
					<span><?php echo esc_html( $md_category->name ); ?></span>
				</a>
			<?php } ?>
		</div>
	<?php } ?>
	</div>
</div>
