<?php
/**
 * Clean layout: the content with no chrome.
 *
 * Useful for print views, embeds and anything opened in a modal window.
 *
 * @package MiniDocs
 *
 * @var string $view              Absolute path of the view being rendered.
 * @var array  $extra_css_classes Wrapper classes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="minidocs-all-wrapper minidocs-clean <?php echo esc_attr( implode( ' ', (array) ( $extra_css_classes ?? array() ) ) ); ?>">
	<div class="minidocs-content">
		<?php
		if ( is_readable( $view ) ) {
			include $view;
		}
		?>
	</div>

	<?php include MINIDOCS_VIEWS_PARTIALS_ABSPATH . '_notifications.php'; ?>
</div>
