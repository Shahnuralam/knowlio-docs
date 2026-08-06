<?php
/**
 * Toast notification stack.
 *
 * @package KnowlioDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="knowlio-notifications-w" aria-live="polite"></div>

<div class="knowlio-confirm-w" hidden>
	<div class="knowlio-confirm-overlay" data-knowlio-confirm-cancel></div>
	<div class="knowlio-confirm" role="alertdialog" aria-modal="true">
		<div class="knowlio-confirm-title"><?php esc_html_e( 'Are you sure?', 'minidocs' ); ?></div>
		<div class="knowlio-confirm-message"></div>
		<div class="knowlio-confirm-actions">
			<button type="button" class="knowlio-btn knowlio-btn-outline" data-knowlio-confirm-cancel><?php esc_html_e( 'Cancel', 'minidocs' ); ?></button>
			<button type="button" class="knowlio-btn knowlio-btn-danger" data-knowlio-confirm-ok><?php esc_html_e( 'Delete', 'minidocs' ); ?></button>
		</div>
	</div>
</div>
