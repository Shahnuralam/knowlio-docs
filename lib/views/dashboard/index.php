<?php
/**
 * Dashboard.
 *
 * @package MiniDocs
 *
 * @var array             $stats
 * @var int               $category_count
 * @var MdArticleModel[]  $recent_articles
 * @var MdArticleModel[]  $popular_articles
 * @var MdCategoryModel[] $categories
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="md-dashboard">

	<div class="md-stat-row">
		<div class="md-stat">
			<div class="md-stat-label"><?php esc_html_e( 'Published', 'minidocs' ); ?></div>
			<div class="md-stat-value"><?php echo esc_html( number_format_i18n( (int) $stats['published'] ) ); ?></div>
			<div class="md-stat-foot"><?php esc_html_e( 'live on the frontend', 'minidocs' ); ?></div>
		</div>
		<div class="md-stat">
			<div class="md-stat-label"><?php esc_html_e( 'Drafts', 'minidocs' ); ?></div>
			<div class="md-stat-value"><?php echo esc_html( number_format_i18n( (int) $stats['drafts'] ) ); ?></div>
			<div class="md-stat-foot"><?php esc_html_e( 'hidden from readers', 'minidocs' ); ?></div>
		</div>
		<div class="md-stat">
			<div class="md-stat-label"><?php esc_html_e( 'Categories', 'minidocs' ); ?></div>
			<div class="md-stat-value"><?php echo esc_html( number_format_i18n( (int) $category_count ) ); ?></div>
			<div class="md-stat-foot"><?php esc_html_e( 'topics in the knowledge base', 'minidocs' ); ?></div>
		</div>
		<div class="md-stat">
			<div class="md-stat-label"><?php esc_html_e( 'Total Reads', 'minidocs' ); ?></div>
			<div class="md-stat-value"><?php echo esc_html( number_format_i18n( (int) $stats['total_views'] ) ); ?></div>
			<div class="md-stat-foot"><?php esc_html_e( 'across all articles', 'minidocs' ); ?></div>
		</div>
	</div>

	<div class="md-dash-grid">

		<div class="md-panel">
			<div class="md-panel-head">
				<h2><?php esc_html_e( 'Recently Edited', 'minidocs' ); ?></h2>
				<a href="<?php echo esc_url( MdRouterHelper::build_link( array( 'articles', 'index' ) ) ); ?>" class="md-panel-link">
					<?php esc_html_e( 'All articles', 'minidocs' ); ?>
				</a>
			</div>
			<div class="md-panel-body md-panel-body-flush">
				<?php if ( empty( $recent_articles ) ) { ?>
					<div class="md-empty-state md-empty-state-sm">
						<p><?php esc_html_e( 'No articles yet.', 'minidocs' ); ?></p>
					</div>
				<?php } else { ?>
					<ul class="md-record-list">
						<?php foreach ( $recent_articles as $md_article ) { ?>
							<li <?php echo MdArticlesHelper::quick_edit_btn_atts( $md_article->id ); // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped ?>>
								<div class="md-record-main">
									<span class="md-record-title"><?php echo esc_html( $md_article->title ); ?></span>
									<span class="md-record-sub"><?php echo esc_html( $md_article->get_category_name() ); ?></span>
								</div>
								<span class="md-status md-status-<?php echo esc_attr( $md_article->status ); ?>">
									<?php echo esc_html( $md_article->get_nice_status() ); ?>
								</span>
							</li>
						<?php } ?>
					</ul>
				<?php } ?>
			</div>
		</div>

		<div class="md-panel">
			<div class="md-panel-head">
				<h2><?php esc_html_e( 'Most Read', 'minidocs' ); ?></h2>
			</div>
			<div class="md-panel-body md-panel-body-flush">
				<?php if ( empty( $popular_articles ) ) { ?>
					<div class="md-empty-state md-empty-state-sm">
						<p><?php esc_html_e( 'No reads recorded yet.', 'minidocs' ); ?></p>
					</div>
				<?php } else { ?>
					<ul class="md-record-list">
						<?php foreach ( $popular_articles as $md_article ) { ?>
							<li <?php echo MdArticlesHelper::quick_edit_btn_atts( $md_article->id ); // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped ?>>
								<div class="md-record-main">
									<span class="md-record-title"><?php echo esc_html( $md_article->title ); ?></span>
									<span class="md-record-sub"><?php echo esc_html( $md_article->get_category_name() ); ?></span>
								</div>
								<span class="md-read-count"><?php echo esc_html( number_format_i18n( (int) $md_article->views_count ) ); ?></span>
							</li>
						<?php } ?>
					</ul>
				<?php } ?>
			</div>
		</div>

		<div class="md-panel">
			<div class="md-panel-head">
				<h2><?php esc_html_e( 'Categories', 'minidocs' ); ?></h2>
				<a href="<?php echo esc_url( MdRouterHelper::build_link( array( 'categories', 'index' ) ) ); ?>" class="md-panel-link">
					<?php esc_html_e( 'Manage', 'minidocs' ); ?>
				</a>
			</div>
			<div class="md-panel-body">
				<?php if ( empty( $categories ) ) { ?>
					<p class="md-panel-intro"><?php esc_html_e( 'No categories yet. Create one to give the knowledge base its shape.', 'minidocs' ); ?></p>
				<?php } else { ?>
					<div class="md-cat-mini-grid">
						<?php foreach ( $categories as $md_category ) { ?>
							<a class="md-cat-mini" href="<?php echo esc_url( MdRouterHelper::build_link( array( 'articles', 'index' ), array( 'filter' => array( 'category_id' => $md_category->id ) ) ) ); ?>">
								<i class="dashicons <?php echo esc_attr( $md_category->get_icon_class() ); ?>"></i>
								<span class="md-cat-mini-name"><?php echo esc_html( $md_category->name ); ?></span>
								<span class="md-cat-mini-count">
									<?php
									$md_published = $md_category->get_articles_count( true );

									printf(
										/* translators: %d: number of published articles. */
										esc_html( _n( '%d published', '%d published', $md_published, 'minidocs' ) ),
										(int) $md_published
									);
									?>
								</span>
							</a>
						<?php } ?>
					</div>
				<?php } ?>
			</div>
		</div>

		<div class="md-panel">
			<div class="md-panel-head">
				<h2><?php esc_html_e( 'Publish on Your Site', 'minidocs' ); ?></h2>
			</div>
			<div class="md-panel-body">
				<p class="md-panel-intro">
					<?php esc_html_e( 'Add this shortcode to any page. It renders the landing screen, every category and every article from that one page.', 'minidocs' ); ?>
				</p>

				<div class="md-shortcode-row">
					<code>[minidocs]</code>
					<button type="button" class="md-btn md-btn-outline md-btn-sm" data-md-copy-text="[minidocs]"><?php esc_html_e( 'Copy', 'minidocs' ); ?></button>
				</div>

				<p class="md-panel-intro md-panel-intro-tight"><?php esc_html_e( 'Optional building blocks for other pages:', 'minidocs' ); ?></p>

				<div class="md-shortcode-row">
					<code>[minidocs_categories columns="3"]</code>
					<button type="button" class="md-btn md-btn-outline md-btn-sm" data-md-copy-text='[minidocs_categories columns="3"]'><?php esc_html_e( 'Copy', 'minidocs' ); ?></button>
				</div>

				<div class="md-shortcode-row">
					<code>[minidocs_articles featured="yes" limit="5"]</code>
					<button type="button" class="md-btn md-btn-outline md-btn-sm" data-md-copy-text='[minidocs_articles featured="yes" limit="5"]'><?php esc_html_e( 'Copy', 'minidocs' ); ?></button>
				</div>
			</div>
		</div>

	</div>
</div>
