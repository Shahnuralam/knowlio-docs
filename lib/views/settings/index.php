<?php
/**
 * Settings screen.
 *
 * @package KnowlioDocs
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
<div class="knowlio-settings">
	<form action="#" class="knowlio-form knowlio-settings-form" data-route-name="<?php echo esc_attr( KnowlioRouterHelper::build_route_name( 'settings', 'update' ) ); ?>">

		<?php echo wp_kses( KnowlioFormHelper::hidden_field( '_wpnonce', wp_create_nonce( 'update_settings' )  ), KnowlioFormHelper::allowed_html() ); ?>

		<div class="knowlio-panel">
			<div class="knowlio-panel-head">
				<h2><?php esc_html_e( 'Knowledge Base', 'minidocs' ); ?></h2>
			</div>
			<div class="knowlio-panel-body">
				<?php
				echo wp_kses(
					KnowlioFormHelper::text_field(
						'setting[kb_title]',
						__( 'Hero Heading', 'minidocs' ),
						$settings['kb_title'],
						array( 'description' => __( 'The large heading above the search box on the frontend.', 'minidocs' ) )
					),
					KnowlioFormHelper::allowed_html()
				);
				?>

				<?php
				echo wp_kses(
					KnowlioFormHelper::text_field(
						'setting[kb_subtitle]',
						__( 'Hero Sub-heading', 'minidocs' ),
						$settings['kb_subtitle']
					),
					KnowlioFormHelper::allowed_html()
				);
				?>

				<?php
				echo wp_kses(
					KnowlioFormHelper::select_field(
						'setting[kb_page_id]',
						__( 'Knowledge Base Page', 'minidocs' ),
						$pages,
						$settings['kb_page_id'],
						array(
							'placeholder' => __( 'Not set', 'minidocs' ),
							'description' => __( 'The page containing the [knowlio] shortcode. Used for the "View" links in the editor.', 'minidocs' ),
						)
					),
					KnowlioFormHelper::allowed_html()
				);
				?>

				<div class="knowlio-form-group">
					<label><?php esc_html_e( 'Frontend Layout', 'minidocs' ); ?></label>
					<div class="knowlio-layout-picker">
						<?php
						$knowlio_layout_art = array(
							'sidebar'  => array( 'nav', 'body', 'toc' ),
							'wide'     => array( 'nav', 'bodyw', 'toc' ),
							'boxed'    => array( 'boxbody' ),
							'magazine' => array( 'nav', 'body', 'toc' ),
						);

						foreach ( $layouts as $knowlio_slug => $knowlio_label ) {
							$knowlio_checked = ( $settings['docs_layout'] === $knowlio_slug );
							$knowlio_name    = strtok( (string) $knowlio_label, '—' );
							?>
							<label class="knowlio-layout-option <?php echo $knowlio_checked ? 'knowlio-is-selected' : ''; ?>">
								<input type="radio" name="setting[docs_layout]" value="<?php echo esc_attr( $knowlio_slug ); ?>" <?php checked( $knowlio_checked ); ?> />
								<span class="knowlio-layout-diagram knowlio-layout-diagram-<?php echo esc_attr( $knowlio_slug ); ?>">
									<?php foreach ( $knowlio_layout_art[ $knowlio_slug ] as $knowlio_col ) { ?>
										<span class="knowlio-ld knowlio-ld-<?php echo esc_attr( $knowlio_col ); ?>"></span>
									<?php } ?>
								</span>
								<span class="knowlio-layout-option-name"><?php echo esc_html( trim( $knowlio_name ) ); ?></span>
							</label>
							<?php
						}
						?>
					</div>
					<div class="knowlio-form-description">
						<?php esc_html_e( 'How every documentation page is arranged on the frontend. A page can still override this with the width and max attributes on the shortcode.', 'minidocs' ); ?>
					</div>
				</div>
			</div>
		</div>

		<div class="knowlio-panel">
			<div class="knowlio-panel-head">
				<h2><?php esc_html_e( 'Admin', 'minidocs' ); ?></h2>
			</div>
			<div class="knowlio-panel-body">
				<div class="knowlio-row">
					<div class="knowlio-col-6">
						<?php
						echo wp_kses(
							KnowlioFormHelper::text_field(
								'setting[brand_name]',
								__( 'Brand Name', 'minidocs' ),
								$settings['brand_name'],
								array( 'description' => __( 'Shown in the WordPress menu and the side menu.', 'minidocs' ) )
							),
							KnowlioFormHelper::allowed_html()
						);
						?>
					</div>
					<div class="knowlio-col-6">
						<?php
						echo wp_kses(
							KnowlioFormHelper::number_field(
								'setting[records_per_page]',
								__( 'Rows Per Page', 'minidocs' ),
								$settings['records_per_page'],
								1,
								200
							),
							KnowlioFormHelper::allowed_html()
						);
						?>
					</div>
				</div>

				<?php
				echo wp_kses(
					KnowlioFormHelper::toggle_field(
						'setting[disable_csv_export]',
						__( 'Disable CSV export', 'minidocs' ),
						'on',
						(bool) $settings['disable_csv_export']
					),
					KnowlioFormHelper::allowed_html()
				);
				?>

				<?php
				echo wp_kses(
					KnowlioFormHelper::toggle_field(
						'setting[remove_data_on_uninstall]',
						__( 'Delete all data when the plugin is uninstalled', 'minidocs' ),
						'on',
						(bool) $settings['remove_data_on_uninstall'],
						array(
							'description' => __( 'Off by default. When off, deleting the plugin leaves your articles and categories in the database, so uninstalling to troubleshoot never costs you your documentation.', 'minidocs' ),
						)
					),
					KnowlioFormHelper::allowed_html()
				);
				?>
			</div>
		</div>

		<div class="knowlio-panel">
			<div class="knowlio-panel-head">
				<h2><?php esc_html_e( 'Roles & Permissions', 'minidocs' ); ?></h2>
				<span class="knowlio-panel-note"><?php esc_html_e( 'Administrators always have full access.', 'minidocs' ); ?></span>
			</div>
			<div class="knowlio-panel-body">
				<?php if ( empty( $roles ) ) { ?>
					<p><?php esc_html_e( 'No additional roles found on this site.', 'minidocs' ); ?></p>
				<?php } else { ?>
					<div class="knowlio-role-grid">
						<?php foreach ( $roles as $knowlio_role_slug => $knowlio_role_name ) { ?>
							<div class="knowlio-role-card">
								<div class="knowlio-role-name"><?php echo esc_html( $knowlio_role_name ); ?></div>

								<?php
								$knowlio_granted = (array) ( $role_map[ $knowlio_role_slug ] ?? array() );

								foreach ( $capability_groups as $knowlio_group_name => $knowlio_capabilities ) {
									$knowlio_options = array();

									foreach ( $knowlio_capabilities as $knowlio_capability ) {
										$knowlio_options[ $knowlio_capability ] = KnowlioRolesHelper::get_capability_label( $knowlio_capability );
									}

									echo wp_kses(
										KnowlioFormHelper::multi_checkbox_field(
											'role_capabilities[' . $knowlio_role_slug . ']',
											ucfirst( $knowlio_group_name ),
											$knowlio_options,
											$knowlio_granted
										),
										KnowlioFormHelper::allowed_html()
									);
								}
								?>
							</div>
						<?php } ?>
					</div>
				<?php } ?>
			</div>
		</div>

		<div class="knowlio-form-actions-bar">
			<button type="submit" class="knowlio-btn knowlio-btn-primary"><?php esc_html_e( 'Save Settings', 'minidocs' ); ?></button>
		</div>
	</form>
</div>
