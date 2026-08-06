<?php
/**
 * Knowledge base landing page.
 *
 * @package KnowlioDocs
 *
 * @var KnowlioCategoryModel[] $categories
 * @var KnowlioArticleModel[]  $featured
 * @var KnowlioArticleModel[]  $popular
 * @var int               $columns
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="knowlio-front knowlio-front-kb <?php echo esc_attr( $layout_class ?? '' ); ?>" id="knowlio-docs">
	<div class="knowlio-front-inner" style="<?php echo esc_attr( $inner_style ?? '' ); ?>">

	<?php include KNOWLIO_VIEWS_ABSPATH . 'front/_hero.php'; ?>

	<?php if ( empty( $categories ) ) { ?>
		<div class="knowlio-front-notice">
			<?php esc_html_e( 'No published documentation yet.', 'minidocs' ); ?>
		</div>
	<?php } else { ?>

		<section class="knowlio-kb-section">
			<h3 class="knowlio-kb-section-title"><?php esc_html_e( 'Browse by topic', 'minidocs' ); ?></h3>

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
		</section>

		<?php if ( ! empty( $featured ) || ! empty( $popular ) ) { ?>
			<div class="knowlio-kb-columns">

				<?php if ( ! empty( $featured ) ) { ?>
					<section class="knowlio-kb-section">
						<h3 class="knowlio-kb-section-title"><?php esc_html_e( 'Start here', 'minidocs' ); ?></h3>
						<ul class="knowlio-article-list">
							<?php foreach ( $featured as $knowlio_article ) { ?>
								<li>
									<a href="<?php echo esc_url( KnowlioShortcodesHelper::article_url( $knowlio_article->slug ) ); ?>">
										<span class="knowlio-article-list-title"><?php echo esc_html( $knowlio_article->title ); ?></span>
										<?php if ( $knowlio_article->get_summary( 16 ) ) { ?>
											<span class="knowlio-article-list-desc"><?php echo esc_html( $knowlio_article->get_summary( 16 ) ); ?></span>
										<?php } ?>
									</a>
								</li>
							<?php } ?>
						</ul>
					</section>
				<?php } ?>

				<?php if ( ! empty( $popular ) ) { ?>
					<section class="knowlio-kb-section">
						<h3 class="knowlio-kb-section-title"><?php esc_html_e( 'Most read', 'minidocs' ); ?></h3>
						<ul class="knowlio-article-list knowlio-article-list-compact">
							<?php foreach ( $popular as $knowlio_article ) { ?>
								<li>
									<a href="<?php echo esc_url( KnowlioShortcodesHelper::article_url( $knowlio_article->slug ) ); ?>">
										<span class="knowlio-article-list-title"><?php echo esc_html( $knowlio_article->title ); ?></span>
										<span class="knowlio-article-list-meta">
											<?php
											printf(
												/* translators: %s: view count. */
												esc_html__( '%s views', 'minidocs' ),
												esc_html( number_format_i18n( (int) $knowlio_article->views_count ) )
											);
											?>
										</span>
									</a>
								</li>
							<?php } ?>
						</ul>
					</section>
				<?php } ?>
			</div>
		<?php } ?>
	<?php } ?>
	</div>
</div>
