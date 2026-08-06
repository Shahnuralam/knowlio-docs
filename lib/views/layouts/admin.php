<?php
/**
 * Admin layout.
 *
 * The layout is included instead of the view; it wraps the chrome around
 * `$view`, which it includes at the end.
 *
 * @package KnowlioDocs
 *
 * @var string $view              Absolute path of the view being rendered.
 * @var string $route_name        Route currently rendered.
 * @var array  $extra_css_classes Wrapper classes.
 * @var mixed  $page_header       Heading string, or a list of tabs.
 * @var array  $breadcrumbs       Breadcrumb trail.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="knowlio-all-wrapper <?php echo esc_attr( implode( ' ', (array) ( $extra_css_classes ?? array() ) ) ); ?>">
	<div class="knowlio-content-and-menu-w">

		<?php include KNOWLIO_VIEWS_PARTIALS_ABSPATH . '_side_menu.php'; ?>

		<div class="knowlio-content-w">

			<?php include KNOWLIO_VIEWS_PARTIALS_ABSPATH . '_top_bar.php'; ?>

			<?php if ( ! empty( $page_header ) ) { ?>
				<div class="knowlio-page-header-w">
					<div class="knowlio-page-header-main">
						<?php if ( is_array( $page_header ) ) { ?>
							<div class="knowlio-page-tabs-w">
								<ul class="knowlio-page-tabs">
									<?php foreach ( $page_header as $tab ) { ?>
										<li class="<?php echo KnowlioRouterHelper::link_has_route( $route_name, $tab['link'] ) ? 'knowlio-page-tab-active' : ''; ?>">
											<a href="<?php echo esc_url( $tab['link'] ); ?>"><?php echo esc_html( $tab['label'] ); ?></a>
										</li>
									<?php } ?>
								</ul>
							</div>
						<?php } else { ?>
							<h1 class="knowlio-page-title"><?php echo esc_html( $page_header ); ?></h1>
						<?php } ?>

						<?php if ( ! empty( $breadcrumbs ) && count( $breadcrumbs ) > 1 ) { ?>
							<nav class="knowlio-breadcrumbs-w" aria-label="<?php esc_attr_e( 'Breadcrumb', 'minidocs' ); ?>">
								<ul class="knowlio-breadcrumbs">
									<?php foreach ( $breadcrumbs as $knowlio_crumb ) { ?>
										<li>
											<?php if ( ! empty( $knowlio_crumb['link'] ) ) { ?>
												<a href="<?php echo esc_url( $knowlio_crumb['link'] ); ?>"><?php echo esc_html( $knowlio_crumb['label'] ); ?></a>
											<?php } else { ?>
												<span><?php echo esc_html( $knowlio_crumb['label'] ); ?></span>
											<?php } ?>
										</li>
									<?php } ?>
								</ul>
							</nav>
						<?php } ?>
					</div>

					<?php
					/**
					 * Fires at the right-hand end of the page header, for screen actions.
					 *
					 * @since 1.0.0
					 * @hook knowlio_page_header_actions
					 *
					 * @param string $route_name Route currently rendered.
					 */
					do_action( 'knowlio_page_header_actions', $route_name );
					?>
				</div>
			<?php } ?>

			<div class="knowlio-content">
				<?php
				if ( is_readable( $view ) ) {
					include $view;
				}
				?>
			</div>
		</div>
	</div>

	<?php include KNOWLIO_VIEWS_PARTIALS_ABSPATH . '_side_panel.php'; ?>
	<?php include KNOWLIO_VIEWS_PARTIALS_ABSPATH . '_notifications.php'; ?>
</div>
