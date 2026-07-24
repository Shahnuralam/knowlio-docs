<?php
/**
 * One category's article list.
 *
 * @package MiniDocs
 *
 * @var MdCategoryModel   $category
 * @var MdArticleModel[]  $articles
 * @var MdCategoryModel[] $categories
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="md-front md-front-category <?php echo esc_attr( $layout_class ?? '' ); ?>" id="minidocs">
	<div class="md-front-inner" style="<?php echo esc_attr( $inner_style ?? '' ); ?>">

	<nav class="md-crumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'minidocs' ); ?>">
		<a href="<?php echo esc_url( MdShortcodesHelper::base_url() ); ?>#minidocs"><?php esc_html_e( 'Documentation', 'minidocs' ); ?></a>
		<span class="md-crumbs-sep">/</span>
		<span class="md-crumbs-current"><?php echo esc_html( $category->name ); ?></span>
	</nav>

	<header class="md-cat-header">
		<span class="md-cat-header-icon"><i class="dashicons <?php echo esc_attr( $category->get_icon_class() ); ?>"></i></span>
		<div class="md-cat-header-text">
			<h2 class="md-cat-header-title"><?php echo esc_html( $category->name ); ?></h2>
			<?php if ( $category->description ) { ?>
				<p class="md-cat-header-desc"><?php echo esc_html( $category->description ); ?></p>
			<?php } ?>
		</div>
	</header>

	<?php if ( empty( $articles ) ) { ?>
		<div class="md-front-notice"><?php esc_html_e( 'No published articles in this category yet.', 'minidocs' ); ?></div>
	<?php } else { ?>
		<ul class="md-article-list">
			<?php foreach ( $articles as $md_article ) { ?>
				<li>
					<a href="<?php echo esc_url( MdShortcodesHelper::article_url( $md_article->slug ) ); ?>">
						<span class="md-article-list-title"><?php echo esc_html( $md_article->title ); ?></span>
						<?php if ( $md_article->get_summary( 20 ) ) { ?>
							<span class="md-article-list-desc"><?php echo esc_html( $md_article->get_summary( 20 ) ); ?></span>
						<?php } ?>
						<span class="md-article-list-meta">
							<?php
							printf(
								/* translators: %d: minutes. */
								esc_html( _n( '%d min read', '%d min read', $md_article->get_reading_time(), 'minidocs' ) ),
								(int) $md_article->get_reading_time()
							);
							?>
						</span>
					</a>
				</li>
			<?php } ?>
		</ul>
	<?php } ?>

	<?php if ( count( $categories ) > 1 ) { ?>
		<section class="md-kb-section md-other-cats">
			<h3 class="md-kb-section-title"><?php esc_html_e( 'Other topics', 'minidocs' ); ?></h3>
			<div class="md-cat-chips">
				<?php
				foreach ( $categories as $md_other ) {
					if ( (int) $md_other->id === (int) $category->id ) {
						continue;
					}
					?>
					<a class="md-cat-chip" href="<?php echo esc_url( MdShortcodesHelper::category_url( $md_other->slug ) ); ?>">
						<i class="dashicons <?php echo esc_attr( $md_other->get_icon_class() ); ?>"></i>
						<span><?php echo esc_html( $md_other->name ); ?></span>
					</a>
					<?php
				}
				?>
			</div>
		</section>
	<?php } ?>
	</div>
</div>
