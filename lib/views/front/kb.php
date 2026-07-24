<?php
/**
 * Knowledge base landing page.
 *
 * @package MiniDocs
 *
 * @var MdCategoryModel[] $categories
 * @var MdArticleModel[]  $featured
 * @var MdArticleModel[]  $popular
 * @var int               $columns
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="md-front md-front-kb <?php echo esc_attr( $layout_class ?? '' ); ?>" id="minidocs">
	<div class="md-front-inner" style="<?php echo esc_attr( $inner_style ?? '' ); ?>">

	<?php include MINIDOCS_VIEWS_ABSPATH . 'front/_hero.php'; ?>

	<?php if ( empty( $categories ) ) { ?>
		<div class="md-front-notice">
			<?php esc_html_e( 'No published documentation yet.', 'minidocs' ); ?>
		</div>
	<?php } else { ?>

		<section class="md-kb-section">
			<h3 class="md-kb-section-title"><?php esc_html_e( 'Browse by topic', 'minidocs' ); ?></h3>

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
		</section>

		<?php if ( ! empty( $featured ) || ! empty( $popular ) ) { ?>
			<div class="md-kb-columns">

				<?php if ( ! empty( $featured ) ) { ?>
					<section class="md-kb-section">
						<h3 class="md-kb-section-title"><?php esc_html_e( 'Start here', 'minidocs' ); ?></h3>
						<ul class="md-article-list">
							<?php foreach ( $featured as $md_article ) { ?>
								<li>
									<a href="<?php echo esc_url( MdShortcodesHelper::article_url( $md_article->slug ) ); ?>">
										<span class="md-article-list-title"><?php echo esc_html( $md_article->title ); ?></span>
										<?php if ( $md_article->get_summary( 16 ) ) { ?>
											<span class="md-article-list-desc"><?php echo esc_html( $md_article->get_summary( 16 ) ); ?></span>
										<?php } ?>
									</a>
								</li>
							<?php } ?>
						</ul>
					</section>
				<?php } ?>

				<?php if ( ! empty( $popular ) ) { ?>
					<section class="md-kb-section">
						<h3 class="md-kb-section-title"><?php esc_html_e( 'Most read', 'minidocs' ); ?></h3>
						<ul class="md-article-list md-article-list-compact">
							<?php foreach ( $popular as $md_article ) { ?>
								<li>
									<a href="<?php echo esc_url( MdShortcodesHelper::article_url( $md_article->slug ) ); ?>">
										<span class="md-article-list-title"><?php echo esc_html( $md_article->title ); ?></span>
										<span class="md-article-list-meta">
											<?php
											printf(
												/* translators: %s: view count. */
												esc_html__( '%s views', 'minidocs' ),
												esc_html( number_format_i18n( (int) $md_article->views_count ) )
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
