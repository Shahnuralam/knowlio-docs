<?php
/**
 * Uninstall routine.
 *
 * Content is destroyed only when the site owner opted in under
 * Knowlio Docs -> Settings. Uninstalling a plugin to troubleshoot a problem should
 * not also delete every article.
 *
 * The opt-in flag is mirrored into wp_options by KnowlioSettingsHelper precisely so
 * that it can be read here, where the plugin's classes are not loaded and the
 * settings table may be about to disappear.
 *
 * @package KnowlioDocs
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Remove Knowlio Docs data from the current site.
 */
function knowlio_uninstall_current_site() {
	global $wpdb;

	if ( 'on' === get_option( 'knowlio_remove_data_on_uninstall' ) ) {
		$knowlio_tables = array(
			$wpdb->prefix . 'knowlio_articles',
			$wpdb->prefix . 'knowlio_categories',
			$wpdb->prefix . 'knowlio_settings',
		);

		foreach ( $knowlio_tables as $knowlio_table ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL -- Table names are built from $wpdb->prefix and plugin constants; identifiers cannot be bound as placeholders.
			$wpdb->query( 'DROP TABLE IF EXISTS `' . esc_sql( $knowlio_table ) . '`' );
		}
	}

	// Options are always removed: they are configuration, not the user's content.
	$knowlio_options = array(
		'knowlio_db_version',
		'knowlio_role_capabilities',
		'knowlio_remove_data_on_uninstall',
	);

	foreach ( $knowlio_options as $knowlio_option ) {
		delete_option( $knowlio_option );
	}
}

if ( is_multisite() ) {
	/*
	 * Tables are created per site (the schema check runs on every site's `init`),
	 * so they have to be removed per site too. Walked in pages so that a large
	 * network does not exhaust memory building one big site list.
	 */
	$knowlio_paged = 1;

	do {
		$knowlio_site_ids = get_sites(
			array(
				'fields'                 => 'ids',
				'number'                 => 100,
				'offset'                 => ( $knowlio_paged - 1 ) * 100,
				'update_site_meta_cache' => false,
			)
		);

		foreach ( $knowlio_site_ids as $knowlio_site_id ) {
			switch_to_blog( $knowlio_site_id );
			knowlio_uninstall_current_site();
			restore_current_blog();
		}

		++$knowlio_paged;
	} while ( count( $knowlio_site_ids ) === 100 );
} else {
	knowlio_uninstall_current_site();
}
