<?php
/**
 * Knowledge base hero with search.
 *
 * @package KnowlioDocs
 *
 * @var string $hero_title
 * @var string $hero_subtitle
 * @var bool   $show_search
 * @var string $search_term
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<header class="knowlio-kb-hero">
	<div class="knowlio-kb-hero-inner">
		<h2 class="knowlio-kb-hero-title"><?php echo esc_html( $hero_title ); ?></h2>

		<?php if ( ! empty( $hero_subtitle ) ) { ?>
			<p class="knowlio-kb-hero-sub"><?php echo esc_html( $hero_subtitle ); ?></p>
		<?php } ?>

		<?php if ( ! empty( $show_search ) ) { ?>
			<form class="knowlio-kb-search" method="get" action="<?php echo esc_url( KnowlioShortcodesHelper::base_url() ); ?>">
				<?php
				// Preserve any query args the host page relies on.
				$knowlio_existing = wp_parse_url( KnowlioShortcodesHelper::base_url(), PHP_URL_QUERY );

				if ( $knowlio_existing ) {
					parse_str( $knowlio_existing, $knowlio_existing_args );

					foreach ( $knowlio_existing_args as $knowlio_key => $knowlio_value ) {
						if ( is_scalar( $knowlio_value ) ) {
							printf(
								'<input type="hidden" name="%s" value="%s" />',
								esc_attr( $knowlio_key ),
								esc_attr( $knowlio_value )
							);
						}
					}
				}
				?>
				<input type="search"
					name="knowlio_s"
					class="knowlio-kb-search-input"
					value="<?php echo esc_attr( $search_term ?? '' ); ?>"
					placeholder="<?php esc_attr_e( 'Search the documentation...', 'minidocs' ); ?>"
					autocomplete="off">
				<button type="submit" class="knowlio-kb-search-btn"><?php esc_html_e( 'Search', 'minidocs' ); ?></button>
			</form>
		<?php } ?>
	</div>
</header>
