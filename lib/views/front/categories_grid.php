<?php
/**
 * Standalone category grid, rendered by [minidocs_categories].
 *
 * @package MiniDocs
 *
 * @var MdCategoryModel[] $categories
 * @var int               $columns
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $categories ) ) {
	return;
}
?>
<div class="md-front md-front-cat-grid">
	<div class="md-cat-grid md-cat-grid-<?php echo esc_attr( $columns ); ?>">
		<?php foreach ( $categories as $md_category ) { ?>
			<a class="md-cat-card" href="<?php echo esc_url( MdShortcodesHelper::category_url( $md_category->slug ) ); ?>">
				<span class="md-cat-card-icon"><i class="dashicons <?php echo esc_attr( $md_category->get_icon_class() ); ?>"></i></span>
				<span class="md-cat-card-name"><?php echo esc_html( $md_category->name ); ?></span>
				<?php if ( $md_category->description ) { ?>
					<span class="md-cat-card-desc"><?php echo esc_html( $md_category->description ); ?></span>
				<?php } ?>
				<span class="md-cat-card-count">
					<?php
					$md_count = $md_category->get_articles_count( true );
					printf(
						/* translators: %d: number of articles. */
						esc_html( _n( '%d article', '%d articles', $md_count, 'minidocs' ) ),
						(int) $md_count
					);
					?>
				</span>
			</a>
		<?php } ?>
	</div>
</div>
