<?php
/**
 * One category's article list.
 *
 * @package KnowlioDocs
 *
 * @var KnowlioCategoryModel   $category
 * @var KnowlioArticleModel[]  $articles
 * @var KnowlioCategoryModel[] $categories
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="knowlio-front knowlio-front-category <?php echo esc_attr( $layout_class ?? '' ); ?>" id="knowlio-docs">
	<div class="knowlio-front-inner" style="<?php echo esc_attr( $inner_style ?? '' ); ?>">

	<nav class="knowlio-crumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'minidocs' ); ?>">
		<a href="<?php echo esc_url( KnowlioShortcodesHelper::base_url() ); ?>#knowlio-docs"><?php esc_html_e( 'Documentation', 'minidocs' ); ?></a>
		<span class="knowlio-crumbs-sep">/</span>
		<span class="knowlio-crumbs-current"><?php echo esc_html( $category->name ); ?></span>
	</nav>

	<header class="knowlio-cat-header">
		<span class="knowlio-cat-header-icon"><i class="dashicons <?php echo esc_attr( $category->get_icon_class() ); ?>"></i></span>
		<div class="knowlio-cat-header-text">
			<h2 class="knowlio-cat-header-title"><?php echo esc_html( $category->name ); ?></h2>
			<?php if ( $category->description ) { ?>
				<p class="knowlio-cat-header-desc"><?php echo esc_html( $category->description ); ?></p>
			<?php } ?>
		</div>
	</header>

	<?php
	if ( empty( $articles ) ) {
		?>
		<div class="knowlio-front-notice"><?php esc_html_e( 'No published articles in this category yet.', 'minidocs' ); ?></div>
		<?php
	} else {
		$knowlio_list_articles      = $articles;
		$knowlio_list_show_category = false;

		include KNOWLIO_VIEWS_ABSPATH . 'front/_article_list.php';
	}
	?>

	<?php if ( count( $categories ) > 1 ) { ?>
		<section class="knowlio-kb-section knowlio-other-cats">
			<h3 class="knowlio-kb-section-title"><?php esc_html_e( 'Other topics', 'minidocs' ); ?></h3>
			<div class="knowlio-cat-chips">
				<?php
				foreach ( $categories as $knowlio_other ) {
					if ( (int) $knowlio_other->id === (int) $category->id ) {
						continue;
					}
					?>
					<a class="knowlio-cat-chip" href="<?php echo esc_url( KnowlioShortcodesHelper::category_url( $knowlio_other->slug ) ); ?>">
						<i class="dashicons <?php echo esc_attr( $knowlio_other->get_icon_class() ); ?>"></i>
						<span><?php echo esc_html( $knowlio_other->name ); ?></span>
					</a>
					<?php
				}
				?>
			</div>
		</section>
	<?php } ?>
	</div>
</div>
