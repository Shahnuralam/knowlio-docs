<?php
/**
 * Side panel shell.
 *
 * Any element carrying `data-knowlio-target="side-panel"` has its response injected
 * here. The panel markup stays in the layout so every screen gets it for free.
 *
 * @package KnowlioDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="knowlio-side-panel-w" hidden>
	<div class="knowlio-side-panel-overlay" data-knowlio-close-panel></div>
	<div class="knowlio-side-panel" role="dialog" aria-modal="true">
		<div class="knowlio-side-panel-body"></div>
	</div>
</div>

<div class="knowlio-lightbox-w" hidden>
	<div class="knowlio-lightbox-overlay" data-knowlio-close-lightbox></div>
	<div class="knowlio-lightbox" role="dialog" aria-modal="true">
		<div class="knowlio-lightbox-body"></div>
	</div>
</div>
