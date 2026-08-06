<?php
/**
 * Admin side menu.
 *
 * @package KnowlioDocs
 *
 * @var string $route_name Route currently rendered.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$knowlio_menu_items = KnowlioMenuHelper::get_side_menu_items();
$knowlio_route_name = $route_name ?? '';
?>
<div class="knowlio-side-menu-w">
	<div class="knowlio-side-menu-top">
		<a href="<?php echo esc_url( KnowlioRouterHelper::build_link( array( 'dashboard', 'index' ) ) ); ?>" class="knowlio-logo-w">
			<span class="knowlio-logo-mark"><i class="dashicons dashicons-media-document"></i></span>
			<span class="knowlio-logo-text"><?php echo esc_html( KnowlioSettingsHelper::get_brand_name() ); ?></span>
		</a>
		<button type="button" class="knowlio-menu-fold-trigger" aria-label="<?php esc_attr_e( 'Toggle menu', 'minidocs' ); ?>">
			<span class="dashicons dashicons-menu-alt"></span>
		</button>
	</div>

	<ul class="knowlio-side-menu">
		<?php
		foreach ( $knowlio_menu_items as $knowlio_item ) {

			if ( ! empty( $knowlio_item['spacer'] ) ) {
				if ( ! empty( $knowlio_item['small_label'] ) ) {
					echo '<li class="knowlio-menu-spacer knowlio-menu-spacer-labelled"><span>' . esc_html( $knowlio_item['small_label'] ) . '</span></li>';
				} else {
					echo '<li class="knowlio-menu-spacer"></li>';
				}
				continue;
			}

			$knowlio_is_active = KnowlioMenuHelper::is_item_active( $knowlio_item, $knowlio_route_name );
			?>
			<li class="<?php echo $knowlio_is_active ? 'knowlio-menu-item-active' : ''; ?>">
				<a href="<?php echo esc_url( $knowlio_item['link'] ); ?>">
					<i class="dashicons <?php echo esc_attr( $knowlio_item['icon'] ?? 'dashicons-marker' ); ?>"></i>
					<span><?php echo esc_html( $knowlio_item['label'] ); ?></span>
					<?php if ( ! empty( $knowlio_item['badge'] ) ) { ?>
						<em class="knowlio-menu-badge"><?php echo esc_html( $knowlio_item['badge'] ); ?></em>
					<?php } ?>
				</a>
			</li>
			<?php
		}
		?>
	</ul>

	<a class="knowlio-back-to-wp" href="<?php echo esc_url( admin_url() ); ?>">
		<i class="dashicons dashicons-wordpress"></i>
		<span><?php esc_html_e( 'Back to WordPress', 'minidocs' ); ?></span>
	</a>
</div>
