<?php
/**
 * Single article: sidebar navigation, body, table of contents.
 *
 * @package MiniDocs
 *
 * @var MdArticleModel   $article
 * @var MdCategoryModel  $category
 * @var string           $body_html
 * @var array            $toc
 * @var MdCategoryModel[] $categories
 * @var MdArticleModel[] $siblings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="md-front md-front-article <?php echo esc_attr( $layout_class ?? '' ); ?>" id="minidocs">
	<div class="md-front-inner" style="<?php echo esc_attr( $inner_style ?? '' ); ?>">

	<nav class="md-crumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'minidocs' ); ?>">
		<a href="<?php echo esc_url( MdShortcodesHelper::base_url() ); ?>#minidocs"><?php esc_html_e( 'Documentation', 'minidocs' ); ?></a>

		<?php if ( ! $category->is_new_record() ) { ?>
			<span class="md-crumbs-sep">/</span>
			<a href="<?php echo esc_url( MdShortcodesHelper::category_url( $category->slug ) ); ?>"><?php echo esc_html( $category->name ); ?></a>
		<?php } ?>

		<span class="md-crumbs-sep">/</span>
		<span class="md-crumbs-current"><?php echo esc_html( $article->title ); ?></span>
	</nav>

	<div class="md-doc-layout">

		<aside class="md-doc-nav">
			<div class="md-doc-nav-scroll">
				<?php foreach ( $categories as $md_category ) { ?>
					<div class="md-doc-nav-group">
						<div class="md-doc-nav-group-title">
							<i class="dashicons <?php echo esc_attr( $md_category->get_icon_class() ); ?>"></i>
							<a href="<?php echo esc_url( MdShortcodesHelper::category_url( $md_category->slug ) ); ?>"><?php echo esc_html( $md_category->name ); ?></a>
						</div>

						<ul class="md-doc-nav-list">
							<?php foreach ( $md_category->get_articles( true ) as $md_sibling ) { ?>
								<li class="<?php echo ( (int) $md_sibling->id === (int) $article->id ) ? 'md-doc-nav-active' : ''; ?>">
									<a href="<?php echo esc_url( MdShortcodesHelper::article_url( $md_sibling->slug ) ); ?>">
										<?php echo esc_html( $md_sibling->title ); ?>
									</a>
								</li>
							<?php } ?>
						</ul>
					</div>
				<?php } ?>
			</div>
		</aside>

		<main class="md-doc-main">
			<article class="md-doc-article">
				<?php if ( ! $category->is_new_record() ) { ?>
					<div class="md-doc-eyebrow"><?php echo esc_html( $category->name ); ?></div>
				<?php } ?>

				<h1 class="md-doc-title"><?php echo esc_html( $article->title ); ?></h1>

				<div class="md-doc-meta">
					<span>
						<?php
						printf(
							/* translators: %d: minutes. */
							esc_html( _n( '%d min read', '%d min read', $article->get_reading_time(), 'minidocs' ) ),
							(int) $article->get_reading_time()
						);
						?>
					</span>
					<span class="md-doc-meta-sep">&middot;</span>
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

				<div class="md-doc-body">
					<?php echo $body_html; // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped -- Passed through wp_kses_post on save and again on render. ?>
				</div>

				<?php
				/**
				 * Fires at the end of an article body on the frontend.
				 *
				 * @since 1.0.0
				 * @hook minidocs_after_article_content
				 *
				 * @param MdArticleModel $article Article being displayed.
				 */
				do_action( 'minidocs_after_article_content', $article );
				?>

				<?php
				// Previous / next within the same category.
				$md_prev = null;
				$md_next = null;

				foreach ( array_values( $siblings ) as $md_index => $md_sibling ) {
					if ( (int) $md_sibling->id !== (int) $article->id ) {
						continue;
					}

					$md_prev = $siblings[ $md_index - 1 ] ?? null;
					$md_next = $siblings[ $md_index + 1 ] ?? null;
					break;
				}
				?>

				<?php if ( $md_prev || $md_next ) { ?>
					<nav class="md-doc-pager">
						<?php if ( $md_prev ) { ?>
							<a class="md-doc-pager-link md-doc-pager-prev" href="<?php echo esc_url( MdShortcodesHelper::article_url( $md_prev->slug ) ); ?>">
								<span class="md-doc-pager-label"><?php esc_html_e( 'Previous', 'minidocs' ); ?></span>
								<span class="md-doc-pager-title"><?php echo esc_html( $md_prev->title ); ?></span>
							</a>
						<?php } else { ?>
							<span></span>
						<?php } ?>

						<?php if ( $md_next ) { ?>
							<a class="md-doc-pager-link md-doc-pager-next" href="<?php echo esc_url( MdShortcodesHelper::article_url( $md_next->slug ) ); ?>">
								<span class="md-doc-pager-label"><?php esc_html_e( 'Next', 'minidocs' ); ?></span>
								<span class="md-doc-pager-title"><?php echo esc_html( $md_next->title ); ?></span>
							</a>
						<?php } ?>
					</nav>
				<?php } ?>
			</article>
		</main>

		<?php if ( ! empty( $toc ) ) { ?>
			<aside class="md-doc-toc">
				<div class="md-doc-toc-title"><?php esc_html_e( 'On this page', 'minidocs' ); ?></div>
				<ul class="md-doc-toc-list">
					<?php foreach ( $toc as $md_entry ) { ?>
						<li class="md-toc-level-<?php echo esc_attr( $md_entry['level'] ); ?>">
							<a href="#<?php echo esc_attr( $md_entry['anchor'] ); ?>"><?php echo esc_html( $md_entry['text'] ); ?></a>
						</li>
					<?php } ?>
				</ul>
			</aside>
		<?php } ?>
	</div>
	</div>
</div>
