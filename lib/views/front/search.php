<?php
/**
 * Search results.
 *
 * @package KnowlioDocs
 *
 * @var KnowlioArticleModel[]  $results
 * @var KnowlioCategoryModel[] $categories
 * @var string            $search_term
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="knowlio-front knowlio-front-search <?php echo esc_attr( $layout_class ?? '' ); ?>" id="knowlio-docs">
	<div class="knowlio-front-inner" style="<?php echo esc_attr( $inner_style ?? '' ); ?>">

	<?php include KNOWLIO_VIEWS_ABSPATH . 'front/_hero.php'; ?>

	<div class="knowlio-search-summary">
		<?php
		printf(
			/* translators: 1: result count, 2: search term. */
			esc_html( _n( '%1$s result for "%2$s"', '%1$s results for "%2$s"', count( $results ), 'minidocs' ) ),
			'<strong>' . esc_html( number_format_i18n( count( $results ) ) ) . '</strong>', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_html( $search_term )
		);
		?>
		<a class="knowlio-search-clear" href="<?php echo esc_url( KnowlioShortcodesHelper::base_url() ); ?>#knowlio-docs"><?php esc_html_e( 'Clear search', 'minidocs' ); ?></a>
	</div>

	<?php if ( empty( $results ) ) { ?>
		<div class="knowlio-front-notice">
			<?php esc_html_e( 'Nothing matched that search. Try a different word, or browse the topics below.', 'minidocs' ); ?>
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
	<?php
	} else {
		$knowlio_list_articles      = $results;
		$knowlio_list_show_category = true;

		include KNOWLIO_VIEWS_ABSPATH . 'front/_article_list.php';
	}
	?>
	</div>
</div>
