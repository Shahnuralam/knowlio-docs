<?php
/**
 * Standalone article list, rendered by [knowlio_articles].
 *
 * @package KnowlioDocs
 *
 * @var KnowlioArticleModel[] $articles
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $articles ) ) {
	return;
}
?>
<div class="knowlio-front knowlio-front-article-list">
	<ul class="knowlio-article-list">
		<?php foreach ( $articles as $knowlio_article ) { ?>
			<li>
				<a href="<?php echo esc_url( KnowlioShortcodesHelper::article_url( $knowlio_article->slug ) ); ?>">
					<span class="knowlio-article-list-title"><?php echo esc_html( $knowlio_article->title ); ?></span>
					<?php if ( $knowlio_article->get_summary( 18 ) ) { ?>
						<span class="knowlio-article-list-desc"><?php echo esc_html( $knowlio_article->get_summary( 18 ) ); ?></span>
					<?php } ?>
				</a>
			</li>
		<?php } ?>
	</ul>
</div>
