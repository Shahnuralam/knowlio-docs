<?php
/**
 * Knowledge base hero with search.
 *
 * @package MiniDocs
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
<header class="md-kb-hero">
	<div class="md-kb-hero-inner">
		<h2 class="md-kb-hero-title"><?php echo esc_html( $hero_title ); ?></h2>

		<?php if ( ! empty( $hero_subtitle ) ) { ?>
			<p class="md-kb-hero-sub"><?php echo esc_html( $hero_subtitle ); ?></p>
		<?php } ?>

		<?php if ( ! empty( $show_search ) ) { ?>
			<form class="md-kb-search" method="get" action="<?php echo esc_url( MdShortcodesHelper::base_url() ); ?>">
				<?php
				// Preserve any query args the host page relies on.
				$md_existing = wp_parse_url( MdShortcodesHelper::base_url(), PHP_URL_QUERY );

				if ( $md_existing ) {
					parse_str( $md_existing, $md_existing_args );

					foreach ( $md_existing_args as $md_key => $md_value ) {
						if ( is_scalar( $md_value ) ) {
							printf(
								'<input type="hidden" name="%s" value="%s" />',
								esc_attr( $md_key ),
								esc_attr( $md_value )
							);
						}
					}
				}
				?>
				<input type="search"
					name="md_s"
					class="md-kb-search-input"
					value="<?php echo esc_attr( $search_term ?? '' ); ?>"
					placeholder="<?php esc_attr_e( 'Search the documentation...', 'minidocs' ); ?>"
					autocomplete="off">
				<button type="submit" class="md-kb-search-btn"><?php esc_html_e( 'Search', 'minidocs' ); ?></button>
			</form>
		<?php } ?>
	</div>
</header>
