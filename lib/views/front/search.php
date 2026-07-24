<?php
/**
 * Search results.
 *
 * @package MiniDocs
 *
 * @var MdArticleModel[]  $results
 * @var MdCategoryModel[] $categories
 * @var string            $search_term
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="md-front md-front-search <?php echo esc_attr( $layout_class ?? '' ); ?>" id="minidocs">
	<div class="md-front-inner" style="<?php echo esc_attr( $inner_style ?? '' ); ?>">

	<?php include MINIDOCS_VIEWS_ABSPATH . 'front/_hero.php'; ?>

	<div class="md-search-summary">
		<?php
		printf(
			/* translators: 1: result count, 2: search term. */
			esc_html( _n( '%1$s result for "%2$s"', '%1$s results for "%2$s"', count( $results ), 'minidocs' ) ),
			'<strong>' . esc_html( number_format_i18n( count( $results ) ) ) . '</strong>', // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped
			esc_html( $search_term )
		);
		?>
		<a class="md-search-clear" href="<?php echo esc_url( MdShortcodesHelper::base_url() ); ?>#minidocs"><?php esc_html_e( 'Clear search', 'minidocs' ); ?></a>
	</div>

	<?php if ( empty( $results ) ) { ?>
		<div class="md-front-notice">
			<?php esc_html_e( 'Nothing matched that search. Try a different word, or browse the topics below.', 'minidocs' ); ?>
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
	<?php } else { ?>
		<ul class="md-article-list">
			<?php foreach ( $results as $md_article ) { ?>
				<li>
					<a href="<?php echo esc_url( MdShortcodesHelper::article_url( $md_article->slug ) ); ?>">
						<span class="md-article-list-title"><?php echo esc_html( $md_article->title ); ?></span>
						<span class="md-article-list-desc"><?php echo esc_html( $md_article->get_summary( 26 ) ); ?></span>
						<span class="md-article-list-meta"><?php echo esc_html( $md_article->get_category_name() ); ?></span>
					</a>
				</li>
			<?php } ?>
		</ul>
	<?php } ?>
	</div>
</div>
