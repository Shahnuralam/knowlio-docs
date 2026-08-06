<?php
/**
 * Dashboard.
 *
 * @package KnowlioDocs
 *
 * @var array             $stats
 * @var int               $category_count
 * @var KnowlioArticleModel[]  $recent_articles
 * @var KnowlioArticleModel[]  $popular_articles
 * @var KnowlioCategoryModel[] $categories
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="knowlio-dashboard">

	<div class="knowlio-stat-row">
		<div class="knowlio-stat">
			<div class="knowlio-stat-label"><?php esc_html_e( 'Published', 'minidocs' ); ?></div>
			<div class="knowlio-stat-value"><?php echo esc_html( number_format_i18n( (int) $stats['published'] ) ); ?></div>
			<div class="knowlio-stat-foot"><?php esc_html_e( 'live on the frontend', 'minidocs' ); ?></div>
		</div>
		<div class="knowlio-stat">
			<div class="knowlio-stat-label"><?php esc_html_e( 'Drafts', 'minidocs' ); ?></div>
			<div class="knowlio-stat-value"><?php echo esc_html( number_format_i18n( (int) $stats['drafts'] ) ); ?></div>
			<div class="knowlio-stat-foot"><?php esc_html_e( 'hidden from readers', 'minidocs' ); ?></div>
		</div>
		<div class="knowlio-stat">
			<div class="knowlio-stat-label"><?php esc_html_e( 'Categories', 'minidocs' ); ?></div>
			<div class="knowlio-stat-value"><?php echo esc_html( number_format_i18n( (int) $category_count ) ); ?></div>
			<div class="knowlio-stat-foot"><?php esc_html_e( 'topics in the knowledge base', 'minidocs' ); ?></div>
		</div>
		<div class="knowlio-stat">
			<div class="knowlio-stat-label"><?php esc_html_e( 'Total Reads', 'minidocs' ); ?></div>
			<div class="knowlio-stat-value"><?php echo esc_html( number_format_i18n( (int) $stats['total_views'] ) ); ?></div>
			<div class="knowlio-stat-foot"><?php esc_html_e( 'across all articles', 'minidocs' ); ?></div>
		</div>
	</div>

	<div class="knowlio-dash-grid">

		<div class="knowlio-panel">
			<div class="knowlio-panel-head">
				<h2><?php esc_html_e( 'Recently Edited', 'minidocs' ); ?></h2>
				<a href="<?php echo esc_url( KnowlioRouterHelper::build_link( array( 'articles', 'index' ) ) ); ?>" class="knowlio-panel-link">
					<?php esc_html_e( 'All articles', 'minidocs' ); ?>
				</a>
			</div>
			<div class="knowlio-panel-body knowlio-panel-body-flush">
				<?php if ( empty( $recent_articles ) ) { ?>
					<div class="knowlio-empty-state knowlio-empty-state-sm">
						<p><?php esc_html_e( 'No articles yet.', 'minidocs' ); ?></p>
					</div>
				<?php } else { ?>
					<ul class="knowlio-record-list">
						<?php foreach ( $recent_articles as $knowlio_article ) { ?>
							<li <?php echo KnowlioArticlesHelper::quick_edit_btn_atts( $knowlio_article->id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
								<div class="knowlio-record-main">
									<span class="knowlio-record-title"><?php echo esc_html( $knowlio_article->title ); ?></span>
									<span class="knowlio-record-sub"><?php echo esc_html( $knowlio_article->get_category_name() ); ?></span>
								</div>
								<span class="knowlio-status knowlio-status-<?php echo esc_attr( $knowlio_article->status ); ?>">
									<?php echo esc_html( $knowlio_article->get_nice_status() ); ?>
								</span>
							</li>
						<?php } ?>
					</ul>
				<?php } ?>
			</div>
		</div>

		<div class="knowlio-panel">
			<div class="knowlio-panel-head">
				<h2><?php esc_html_e( 'Most Read', 'minidocs' ); ?></h2>
			</div>
			<div class="knowlio-panel-body knowlio-panel-body-flush">
				<?php if ( empty( $popular_articles ) ) { ?>
					<div class="knowlio-empty-state knowlio-empty-state-sm">
						<p><?php esc_html_e( 'No reads recorded yet.', 'minidocs' ); ?></p>
					</div>
				<?php } else { ?>
					<ul class="knowlio-record-list">
						<?php foreach ( $popular_articles as $knowlio_article ) { ?>
							<li <?php echo KnowlioArticlesHelper::quick_edit_btn_atts( $knowlio_article->id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
								<div class="knowlio-record-main">
									<span class="knowlio-record-title"><?php echo esc_html( $knowlio_article->title ); ?></span>
									<span class="knowlio-record-sub"><?php echo esc_html( $knowlio_article->get_category_name() ); ?></span>
								</div>
								<span class="knowlio-read-count"><?php echo esc_html( number_format_i18n( (int) $knowlio_article->views_count ) ); ?></span>
							</li>
						<?php } ?>
					</ul>
				<?php } ?>
			</div>
		</div>

		<div class="knowlio-panel">
			<div class="knowlio-panel-head">
				<h2><?php esc_html_e( 'Categories', 'minidocs' ); ?></h2>
				<a href="<?php echo esc_url( KnowlioRouterHelper::build_link( array( 'categories', 'index' ) ) ); ?>" class="knowlio-panel-link">
					<?php esc_html_e( 'Manage', 'minidocs' ); ?>
				</a>
			</div>
			<div class="knowlio-panel-body">
				<?php if ( empty( $categories ) ) { ?>
					<p class="knowlio-panel-intro"><?php esc_html_e( 'No categories yet. Create one to give the knowledge base its shape.', 'minidocs' ); ?></p>
				<?php } else { ?>
					<div class="knowlio-cat-mini-grid">
						<?php foreach ( $categories as $knowlio_category ) { ?>
							<a class="knowlio-cat-mini" href="<?php echo esc_url( KnowlioRouterHelper::build_link( array( 'articles', 'index' ), array( 'filter' => array( 'category_id' => $knowlio_category->id ) ) ) ); ?>">
								<i class="dashicons <?php echo esc_attr( $knowlio_category->get_icon_class() ); ?>"></i>
								<span class="knowlio-cat-mini-name"><?php echo esc_html( $knowlio_category->name ); ?></span>
								<span class="knowlio-cat-mini-count">
									<?php
									$knowlio_published = $knowlio_category->get_articles_count( true );

									printf(
										/* translators: %d: number of published articles. */
										esc_html( _n( '%d published', '%d published', $knowlio_published, 'minidocs' ) ),
										(int) $knowlio_published
									);
									?>
								</span>
							</a>
						<?php } ?>
					</div>
				<?php } ?>
			</div>
		</div>

		<div class="knowlio-panel">
			<div class="knowlio-panel-head">
				<h2><?php esc_html_e( 'Publish on Your Site', 'minidocs' ); ?></h2>
			</div>
			<div class="knowlio-panel-body">
				<p class="knowlio-panel-intro">
					<?php esc_html_e( 'Add this shortcode to any page. It renders the landing screen, every category and every article from that one page.', 'minidocs' ); ?>
				</p>

				<div class="knowlio-shortcode-row">
					<code>[knowlio]</code>
					<button type="button" class="knowlio-btn knowlio-btn-outline knowlio-btn-sm" data-knowlio-copy-text="[knowlio]"><?php esc_html_e( 'Copy', 'minidocs' ); ?></button>
				</div>

				<p class="knowlio-panel-intro knowlio-panel-intro-tight"><?php esc_html_e( 'Optional building blocks for other pages:', 'minidocs' ); ?></p>

				<div class="knowlio-shortcode-row">
					<code>[knowlio_categories columns="3"]</code>
					<button type="button" class="knowlio-btn knowlio-btn-outline knowlio-btn-sm" data-knowlio-copy-text='[knowlio_categories columns="3"]'><?php esc_html_e( 'Copy', 'minidocs' ); ?></button>
				</div>

				<div class="knowlio-shortcode-row">
					<code>[knowlio_articles featured="yes" limit="5"]</code>
					<button type="button" class="knowlio-btn knowlio-btn-outline knowlio-btn-sm" data-knowlio-copy-text='[knowlio_articles featured="yes" limit="5"]'><?php esc_html_e( 'Copy', 'minidocs' ); ?></button>
				</div>
			</div>
		</div>

	</div>
</div>
