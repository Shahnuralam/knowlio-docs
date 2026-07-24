<?php
/**
 * Settings screen.
 *
 * @package MiniDocs
 *
 * @var array $settings
 * @var array $pages
 * @var array $roles
 * @var array $capability_groups
 * @var array $role_map
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="md-settings">
	<form action="#" class="md-form md-settings-form" data-route-name="<?php echo esc_attr( MdRouterHelper::build_route_name( 'settings', 'update' ) ); ?>">

		<?php echo MdFormHelper::hidden_field( '_wpnonce', wp_create_nonce( 'update_settings' ) ); // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped ?>

		<div class="md-panel">
			<div class="md-panel-head">
				<h2><?php esc_html_e( 'Knowledge Base', 'minidocs' ); ?></h2>
			</div>
			<div class="md-panel-body">
				<?php
				echo MdFormHelper::text_field( // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped
					'setting[kb_title]',
					__( 'Hero Heading', 'minidocs' ),
					$settings['kb_title'],
					array( 'description' => __( 'The large heading above the search box on the frontend.', 'minidocs' ) )
				);
				?>

				<?php
				echo MdFormHelper::text_field( // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped
					'setting[kb_subtitle]',
					__( 'Hero Sub-heading', 'minidocs' ),
					$settings['kb_subtitle']
				);
				?>

				<?php
				echo MdFormHelper::select_field( // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped
					'setting[kb_page_id]',
					__( 'Knowledge Base Page', 'minidocs' ),
					$pages,
					$settings['kb_page_id'],
					array(
						'placeholder' => __( 'Not set', 'minidocs' ),
						'description' => __( 'The page containing the [minidocs] shortcode. Used for the "View" links in the editor.', 'minidocs' ),
					)
				);
				?>

				<div class="md-form-group">
					<label><?php esc_html_e( 'Frontend Layout', 'minidocs' ); ?></label>
					<div class="md-layout-picker">
						<?php
						$md_layout_art = array(
							'sidebar'  => array( 'nav', 'body', 'toc' ),
							'wide'     => array( 'nav', 'bodyw', 'toc' ),
							'boxed'    => array( 'boxbody' ),
							'magazine' => array( 'nav', 'body', 'toc' ),
						);

						foreach ( $layouts as $md_slug => $md_label ) {
							$md_checked = ( $settings['docs_layout'] === $md_slug );
							$md_name    = strtok( (string) $md_label, '—' );
							?>
							<label class="md-layout-option <?php echo $md_checked ? 'md-is-selected' : ''; ?>">
								<input type="radio" name="setting[docs_layout]" value="<?php echo esc_attr( $md_slug ); ?>" <?php checked( $md_checked ); ?> />
								<span class="md-layout-diagram md-layout-diagram-<?php echo esc_attr( $md_slug ); ?>">
									<?php foreach ( $md_layout_art[ $md_slug ] as $md_col ) { ?>
										<span class="md-ld md-ld-<?php echo esc_attr( $md_col ); ?>"></span>
									<?php } ?>
								</span>
								<span class="md-layout-option-name"><?php echo esc_html( trim( $md_name ) ); ?></span>
							</label>
							<?php
						}
						?>
					</div>
					<div class="md-form-description">
						<?php esc_html_e( 'How every documentation page is arranged on the frontend. A page can still override this with the width and max attributes on the shortcode.', 'minidocs' ); ?>
					</div>
				</div>
			</div>
		</div>

		<div class="md-panel">
			<div class="md-panel-head">
				<h2><?php esc_html_e( 'Admin', 'minidocs' ); ?></h2>
			</div>
			<div class="md-panel-body">
				<div class="md-row">
					<div class="md-col-6">
						<?php
						echo MdFormHelper::text_field( // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped
							'setting[brand_name]',
							__( 'Brand Name', 'minidocs' ),
							$settings['brand_name'],
							array( 'description' => __( 'Shown in the WordPress menu and the side menu.', 'minidocs' ) )
						);
						?>
					</div>
					<div class="md-col-6">
						<?php
						echo MdFormHelper::number_field( // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped
							'setting[records_per_page]',
							__( 'Rows Per Page', 'minidocs' ),
							$settings['records_per_page'],
							1,
							200
						);
						?>
					</div>
				</div>

				<?php
				echo MdFormHelper::toggle_field( // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped
					'setting[disable_csv_export]',
					__( 'Disable CSV export', 'minidocs' ),
					'on',
					(bool) $settings['disable_csv_export']
				);
				?>
			</div>
		</div>

		<div class="md-panel">
			<div class="md-panel-head">
				<h2><?php esc_html_e( 'Roles & Permissions', 'minidocs' ); ?></h2>
				<span class="md-panel-note"><?php esc_html_e( 'Administrators always have full access.', 'minidocs' ); ?></span>
			</div>
			<div class="md-panel-body">
				<?php if ( empty( $roles ) ) { ?>
					<p><?php esc_html_e( 'No additional roles found on this site.', 'minidocs' ); ?></p>
				<?php } else { ?>
					<div class="md-role-grid">
						<?php foreach ( $roles as $md_role_slug => $md_role_name ) { ?>
							<div class="md-role-card">
								<div class="md-role-name"><?php echo esc_html( $md_role_name ); ?></div>

								<?php
								$md_granted = (array) ( $role_map[ $md_role_slug ] ?? array() );

								foreach ( $capability_groups as $md_group_name => $md_capabilities ) {
									$md_options = array();

									foreach ( $md_capabilities as $md_capability ) {
										$md_options[ $md_capability ] = MdRolesHelper::get_capability_label( $md_capability );
									}

									echo MdFormHelper::multi_checkbox_field( // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped
										'role_capabilities[' . $md_role_slug . ']',
										ucfirst( $md_group_name ),
										$md_options,
										$md_granted
									);
								}
								?>
							</div>
						<?php } ?>
					</div>
				<?php } ?>
			</div>
		</div>

		<div class="md-form-actions-bar">
			<button type="submit" class="md-btn md-btn-primary"><?php esc_html_e( 'Save Settings', 'minidocs' ); ?></button>
		</div>
	</form>
</div>
