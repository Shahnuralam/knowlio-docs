<?php
/**
 * Admin top bar.
 *
 * @package MiniDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$md_current_user = wp_get_current_user();
$md_kb_page_id   = (int) MdSettingsHelper::get_setting( 'kb_page_id', 0 );
$md_kb_url       = $md_kb_page_id ? get_permalink( $md_kb_page_id ) : '';
?>
<div class="minidocs-top-bar-w">
	<button type="button" class="md-top-iconed-link md-mobile-menu-trigger" aria-label="<?php esc_attr_e( 'Menu', 'minidocs' ); ?>">
		<i class="dashicons dashicons-menu-alt"></i>
	</button>

	<form class="md-top-search-w" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
		<input type="hidden" name="page" value="<?php echo esc_attr( MINIDOCS_ADMIN_PAGE_SLUG ); ?>">
		<input type="hidden" name="route_name" value="<?php echo esc_attr( MdRouterHelper::build_route_name( 'articles', 'index' ) ); ?>">
		<i class="dashicons dashicons-search"></i>
		<input type="search"
			class="md-top-search"
			name="filter[title]"
			placeholder="<?php esc_attr_e( 'Search articles...', 'minidocs' ); ?>"
			autocomplete="off">
	</form>

	<?php
	/**
	 * Fires before the top bar action buttons.
	 *
	 * @since 1.0.0
	 * @hook minidocs_top_bar_before_actions
	 */
	do_action( 'minidocs_top_bar_before_actions' );
	?>

	<?php if ( $md_kb_url ) { ?>
		<a href="<?php echo esc_url( $md_kb_url ); ?>"
			class="md-view-site-link"
			target="_blank" rel="noopener">
			<i class="dashicons dashicons-external"></i>
			<span><?php esc_html_e( 'View Knowledge Base', 'minidocs' ); ?></span>
		</a>
	<?php } else { ?>
		<a href="<?php echo esc_url( MdRouterHelper::build_link( array( 'settings', 'index' ) ) ); ?>" class="md-setup-hint">
			<i class="dashicons dashicons-warning"></i>
			<span><?php esc_html_e( 'Set your docs page', 'minidocs' ); ?></span>
		</a>
	<?php } ?>

	<?php
	/**
	 * Fires after the top bar action buttons.
	 *
	 * @since 1.0.0
	 * @hook minidocs_top_bar_after_actions
	 */
	do_action( 'minidocs_top_bar_after_actions' );
	?>

	<?php if ( MdRolesHelper::current_user_can( array( 'article__create' ) ) ) { ?>
		<a href="#" class="md-btn md-btn-primary md-top-new-btn" <?php echo MdArticlesHelper::quick_edit_btn_atts(); // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped ?>>
			<i class="dashicons dashicons-plus-alt2"></i>
			<span><?php esc_html_e( 'New Article', 'minidocs' ); ?></span>
		</a>
	<?php } ?>

	<div class="md-top-user-w">
		<span class="md-top-user-avatar"><?php echo get_avatar( $md_current_user->ID, 32 ); // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped ?></span>
		<span class="md-top-user-name"><?php echo esc_html( $md_current_user->display_name ); ?></span>
	</div>
</div>
