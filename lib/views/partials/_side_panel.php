<?php
/**
 * Side panel shell.
 *
 * Any element carrying `data-md-target="side-panel"` has its response injected
 * here. The panel markup stays in the layout so every screen gets it for free.
 *
 * @package MiniDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="md-side-panel-w" hidden>
	<div class="md-side-panel-overlay" data-md-close-panel></div>
	<div class="md-side-panel" role="dialog" aria-modal="true">
		<div class="md-side-panel-body"></div>
	</div>
</div>

<div class="md-lightbox-w" hidden>
	<div class="md-lightbox-overlay" data-md-close-lightbox></div>
	<div class="md-lightbox" role="dialog" aria-modal="true">
		<div class="md-lightbox-body"></div>
	</div>
</div>
