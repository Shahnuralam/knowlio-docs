<?php
/**
 * Single article: sidebar navigation, body, table of contents.
 *
 * @package KnowlioDocs
 *
 * @var KnowlioArticleModel   $article
 * @var KnowlioCategoryModel  $category
 * @var string           $body_html
 * @var array            $toc
 * @var KnowlioCategoryModel[] $categories
 * @var KnowlioArticleModel[] $siblings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="knowlio-front knowlio-front-article <?php echo esc_attr( $layout_class ?? '' ); ?>" id="knowlio-docs">
	<div class="knowlio-front-inner" style="<?php echo esc_attr( $inner_style ?? '' ); ?>">

	<nav class="knowlio-crumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'minidocs' ); ?>">
		<a href="<?php echo esc_url( KnowlioShortcodesHelper::base_url() ); ?>#knowlio-docs"><?php esc_html_e( 'Documentation', 'minidocs' ); ?></a>

		<?php if ( ! $category->is_new_record() ) { ?>
			<span class="knowlio-crumbs-sep">/</span>
			<a href="<?php echo esc_url( KnowlioShortcodesHelper::category_url( $category->slug ) ); ?>"><?php echo esc_html( $category->name ); ?></a>
		<?php } ?>

		<span class="knowlio-crumbs-sep">/</span>
		<span class="knowlio-crumbs-current"><?php echo esc_html( $article->title ); ?></span>
	</nav>

	<div class="knowlio-doc-layout">

		<aside class="knowlio-doc-nav">
			<div class="knowlio-doc-nav-scroll">
				<?php foreach ( $categories as $knowlio_category ) { ?>
					<div class="knowlio-doc-nav-group">
						<div class="knowlio-doc-nav-group-title">
							<i class="dashicons <?php echo esc_attr( $knowlio_category->get_icon_class() ); ?>"></i>
							<a href="<?php echo esc_url( KnowlioShortcodesHelper::category_url( $knowlio_category->slug ) ); ?>"><?php echo esc_html( $knowlio_category->name ); ?></a>
						</div>

						<ul class="knowlio-doc-nav-list">
							<?php foreach ( $knowlio_category->get_articles( true ) as $knowlio_sibling ) { ?>
								<li class="<?php echo ( (int) $knowlio_sibling->id === (int) $article->id ) ? 'knowlio-doc-nav-active' : ''; ?>">
									<a href="<?php echo esc_url( KnowlioShortcodesHelper::article_url( $knowlio_sibling->slug ) ); ?>">
										<?php echo esc_html( $knowlio_sibling->title ); ?>
									</a>
								</li>
							<?php } ?>
						</ul>
					</div>
				<?php } ?>
			</div>
		</aside>

		<main class="knowlio-doc-main">
			<article class="knowlio-doc-article">
				<?php if ( ! $category->is_new_record() ) { ?>
					<div class="knowlio-doc-eyebrow"><?php echo esc_html( $category->name ); ?></div>
				<?php } ?>

				<h1 class="knowlio-doc-title"><?php echo esc_html( $article->title ); ?></h1>

				<div class="knowlio-doc-meta">
					<span>
						<?php
						printf(
							/* translators: %d: minutes. */
							esc_html( _n( '%d min read', '%d min read', $article->get_reading_time(), 'minidocs' ) ),
							(int) $article->get_reading_time()
						);
						?>
					</span>
					<span class="knowlio-doc-meta-sep">&middot;</span>
					<span>
						<?php
						printf(
							/* translators: %s: formatted date. */
							esc_html__( 'Updated %s', 'minidocs' ),
							esc_html( $article->formatted_updated_date() )
						);
						?>
					</span>
				</div>

				<div class="knowlio-doc-body">
					<?php echo $body_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Passed through wp_kses_post on save and again on render. ?>
				</div>

				<?php
				/**
				 * Fires at the end of an article body on the frontend.
				 *
				 * @since 1.0.0
				 * @hook knowlio_after_article_content
				 *
				 * @param KnowlioArticleModel $article Article being displayed.
				 */
				do_action( 'knowlio_after_article_content', $article );
				?>

				<?php
				// Previous / next within the same category.
				$knowlio_prev = null;
				$knowlio_next = null;

				foreach ( array_values( $siblings ) as $knowlio_index => $knowlio_sibling ) {
					if ( (int) $knowlio_sibling->id !== (int) $article->id ) {
						continue;
					}

					$knowlio_prev = $siblings[ $knowlio_index - 1 ] ?? null;
					$knowlio_next = $siblings[ $knowlio_index + 1 ] ?? null;
					break;
				}
				?>

				<?php if ( $knowlio_prev || $knowlio_next ) { ?>
					<nav class="knowlio-doc-pager">
						<?php if ( $knowlio_prev ) { ?>
							<a class="knowlio-doc-pager-link knowlio-doc-pager-prev" href="<?php echo esc_url( KnowlioShortcodesHelper::article_url( $knowlio_prev->slug ) ); ?>">
								<span class="knowlio-doc-pager-label"><?php esc_html_e( 'Previous', 'minidocs' ); ?></span>
								<span class="knowlio-doc-pager-title"><?php echo esc_html( $knowlio_prev->title ); ?></span>
							</a>
						<?php } else { ?>
							<span></span>
						<?php } ?>

						<?php if ( $knowlio_next ) { ?>
							<a class="knowlio-doc-pager-link knowlio-doc-pager-next" href="<?php echo esc_url( KnowlioShortcodesHelper::article_url( $knowlio_next->slug ) ); ?>">
								<span class="knowlio-doc-pager-label"><?php esc_html_e( 'Next', 'minidocs' ); ?></span>
								<span class="knowlio-doc-pager-title"><?php echo esc_html( $knowlio_next->title ); ?></span>
							</a>
						<?php } ?>
					</nav>
				<?php } ?>
			</article>
		</main>

		<?php if ( ! empty( $toc ) ) { ?>
			<aside class="knowlio-doc-toc">
				<div class="knowlio-doc-toc-title"><?php esc_html_e( 'On this page', 'minidocs' ); ?></div>
				<ul class="knowlio-doc-toc-list">
					<?php foreach ( $toc as $knowlio_entry ) { ?>
						<li class="knowlio-toc-level-<?php echo esc_attr( $knowlio_entry['level'] ); ?>">
							<a href="#<?php echo esc_attr( $knowlio_entry['anchor'] ); ?>"><?php echo esc_html( $knowlio_entry['text'] ); ?></a>
						</li>
					<?php } ?>
				</ul>
			</aside>
		<?php } ?>
	</div>
	</div>
</div>
