<?php
/**
 * Admin top bar.
 *
 * @package KnowlioDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$knowlio_current_user = wp_get_current_user();
$knowlio_kb_page_id   = (int) KnowlioSettingsHelper::get_setting( 'kb_page_id', 0 );
$knowlio_kb_url       = $knowlio_kb_page_id ? get_permalink( $knowlio_kb_page_id ) : '';
?>
<div class="knowlio-top-bar-w">
	<button type="button" class="knowlio-top-iconed-link knowlio-mobile-menu-trigger" aria-label="<?php esc_attr_e( 'Menu', 'minidocs' ); ?>">
		<i class="dashicons dashicons-menu-alt"></i>
	</button>

	<form class="knowlio-top-search-w" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
		<input type="hidden" name="page" value="<?php echo esc_attr( KNOWLIO_ADMIN_PAGE_SLUG ); ?>">
		<input type="hidden" name="route_name" value="<?php echo esc_attr( KnowlioRouterHelper::build_route_name( 'articles', 'index' ) ); ?>">
		<i class="dashicons dashicons-search"></i>
		<input type="search"
			class="knowlio-top-search"
			name="filter[title]"
			placeholder="<?php esc_attr_e( 'Search articles...', 'minidocs' ); ?>"
			autocomplete="off">
	</form>

	<?php
	/**
	 * Fires before the top bar action buttons.
	 *
	 * @since 1.0.0
	 * @hook knowlio_top_bar_before_actions
	 */
	do_action( 'knowlio_top_bar_before_actions' );
	?>

	<?php if ( $knowlio_kb_url ) { ?>
		<a href="<?php echo esc_url( $knowlio_kb_url ); ?>"
			class="knowlio-view-site-link"
			target="_blank" rel="noopener">
			<i class="dashicons dashicons-external"></i>
			<span><?php esc_html_e( 'View Knowledge Base', 'minidocs' ); ?></span>
		</a>
	<?php } else { ?>
		<a href="<?php echo esc_url( KnowlioRouterHelper::build_link( array( 'settings', 'index' ) ) ); ?>" class="knowlio-setup-hint">
			<i class="dashicons dashicons-warning"></i>
			<span><?php esc_html_e( 'Set your docs page', 'minidocs' ); ?></span>
		</a>
	<?php } ?>

	<?php
	/**
	 * Fires after the top bar action buttons.
	 *
	 * @since 1.0.0
	 * @hook knowlio_top_bar_after_actions
	 */
	do_action( 'knowlio_top_bar_after_actions' );
	?>

	<?php if ( KnowlioRolesHelper::current_user_can( array( 'article__create' ) ) ) { ?>
		<a href="#" class="knowlio-btn knowlio-btn-primary knowlio-top-new-btn" <?php echo KnowlioArticlesHelper::quick_edit_btn_atts(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<i class="dashicons dashicons-plus-alt2"></i>
			<span><?php esc_html_e( 'New Article', 'minidocs' ); ?></span>
		</a>
	<?php } ?>

	<div class="knowlio-top-user-w">
		<span class="knowlio-top-user-avatar"><?php echo get_avatar( $knowlio_current_user->ID, 32 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		<span class="knowlio-top-user-name"><?php echo esc_html( $knowlio_current_user->display_name ); ?></span>
	</div>
</div>
