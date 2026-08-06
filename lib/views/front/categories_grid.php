<?php
/**
 * Standalone category grid, rendered by [knowlio_categories].
 *
 * @package KnowlioDocs
 *
 * @var KnowlioCategoryModel[] $categories
 * @var int               $columns
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $categories ) ) {
	return;
}
?>
<div class="knowlio-front knowlio-front-cat-grid">
	<div class="knowlio-cat-grid knowlio-cat-grid-<?php echo esc_attr( $columns ); ?>">
		<?php foreach ( $categories as $knowlio_category ) { ?>
			<a class="knowlio-cat-card" href="<?php echo esc_url( KnowlioShortcodesHelper::category_url( $knowlio_category->slug ) ); ?>">
				<span class="knowlio-cat-card-icon"><i class="dashicons <?php echo esc_attr( $knowlio_category->get_icon_class() ); ?>"></i></span>
				<span class="knowlio-cat-card-name"><?php echo esc_html( $knowlio_category->name ); ?></span>
				<?php if ( $knowlio_category->description ) { ?>
					<span class="knowlio-cat-card-desc"><?php echo esc_html( $knowlio_category->description ); ?></span>
				<?php } ?>
				<span class="knowlio-cat-card-count">
					<?php
					$knowlio_count = $knowlio_category->get_articles_count( true );
					printf(
						/* translators: %d: number of articles. */
						esc_html( _n( '%d article', '%d articles', $knowlio_count, 'minidocs' ) ),
						(int) $knowlio_count
					);
					?>
				</span>
			</a>
		<?php } ?>
	</div>
</div>
