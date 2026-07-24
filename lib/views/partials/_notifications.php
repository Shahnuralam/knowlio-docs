<?php
/**
 * Toast notification stack.
 *
 * @package MiniDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="md-notifications-w" aria-live="polite"></div>

<div class="md-confirm-w" hidden>
	<div class="md-confirm-overlay" data-md-confirm-cancel></div>
	<div class="md-confirm" role="alertdialog" aria-modal="true">
		<div class="md-confirm-title"><?php esc_html_e( 'Are you sure?', 'minidocs' ); ?></div>
		<div class="md-confirm-message"></div>
		<div class="md-confirm-actions">
			<button type="button" class="md-btn md-btn-outline" data-md-confirm-cancel><?php esc_html_e( 'Cancel', 'minidocs' ); ?></button>
			<button type="button" class="md-btn md-btn-danger" data-md-confirm-ok><?php esc_html_e( 'Delete', 'minidocs' ); ?></button>
		</div>
	</div>
</div>
