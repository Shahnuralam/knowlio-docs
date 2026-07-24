<?php
/**
 * Admin side menu.
 *
 * @package MiniDocs
 *
 * @var string $route_name Route currently rendered.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$md_menu_items = MdMenuHelper::get_side_menu_items();
$md_route_name = $route_name ?? '';
?>
<div class="minidocs-side-menu-w">
	<div class="md-side-menu-top">
		<a href="<?php echo esc_url( MdRouterHelper::build_link( array( 'dashboard', 'index' ) ) ); ?>" class="md-logo-w">
			<span class="md-logo-mark"><i class="dashicons dashicons-media-document"></i></span>
			<span class="md-logo-text"><?php echo esc_html( MdSettingsHelper::get_brand_name() ); ?></span>
		</a>
		<button type="button" class="md-menu-fold-trigger" aria-label="<?php esc_attr_e( 'Toggle menu', 'minidocs' ); ?>">
			<span class="dashicons dashicons-menu-alt"></span>
		</button>
	</div>

	<ul class="md-side-menu">
		<?php
		foreach ( $md_menu_items as $md_item ) {

			if ( ! empty( $md_item['spacer'] ) ) {
				if ( ! empty( $md_item['small_label'] ) ) {
					echo '<li class="md-menu-spacer md-menu-spacer-labelled"><span>' . esc_html( $md_item['small_label'] ) . '</span></li>';
				} else {
					echo '<li class="md-menu-spacer"></li>';
				}
				continue;
			}

			$md_is_active = MdMenuHelper::is_item_active( $md_item, $md_route_name );
			?>
			<li class="<?php echo $md_is_active ? 'md-menu-item-active' : ''; ?>">
				<a href="<?php echo esc_url( $md_item['link'] ); ?>">
					<i class="dashicons <?php echo esc_attr( $md_item['icon'] ?? 'dashicons-marker' ); ?>"></i>
					<span><?php echo esc_html( $md_item['label'] ); ?></span>
					<?php if ( ! empty( $md_item['badge'] ) ) { ?>
						<em class="md-menu-badge"><?php echo esc_html( $md_item['badge'] ); ?></em>
					<?php } ?>
				</a>
			</li>
			<?php
		}
		?>
	</ul>

	<a class="md-back-to-wp" href="<?php echo esc_url( admin_url() ); ?>">
		<i class="dashicons dashicons-wordpress"></i>
		<span><?php esc_html_e( 'Back to WordPress', 'minidocs' ); ?></span>
	</a>
</div>
