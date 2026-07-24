<?php
/**
 * Standalone article list, rendered by [minidocs_articles].
 *
 * @package MiniDocs
 *
 * @var MdArticleModel[] $articles
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $articles ) ) {
	return;
}
?>
<div class="md-front md-front-article-list">
	<ul class="md-article-list">
		<?php foreach ( $articles as $md_article ) { ?>
			<li>
				<a href="<?php echo esc_url( MdShortcodesHelper::article_url( $md_article->slug ) ); ?>">
					<span class="md-article-list-title"><?php echo esc_html( $md_article->title ); ?></span>
					<?php if ( $md_article->get_summary( 18 ) ) { ?>
						<span class="md-article-list-desc"><?php echo esc_html( $md_article->get_summary( 18 ) ); ?></span>
					<?php } ?>
				</a>
			</li>
		<?php } ?>
	</ul>
</div>
