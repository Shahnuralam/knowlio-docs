<?php
/**
 * Uninstall routine.
 *
 * Content is destroyed only when the site owner opted in. Uninstalling a plugin
 * to troubleshoot a problem should not also delete every article.
 *
 * @package MiniDocs
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

if ( 'on' === get_option( 'minidocs_remove_data_on_uninstall' ) ) {
	$minidocs_tables = array(
		$wpdb->prefix . 'minidocs_articles',
		$wpdb->prefix . 'minidocs_categories',
		$wpdb->prefix . 'minidocs_settings',
	);

	foreach ( $minidocs_tables as $minidocs_table ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( 'DROP TABLE IF EXISTS ' . $minidocs_table );
	}
}

// Options are always removed: they are configuration, not the user's content.
$minidocs_options = array(
	'minidocs_db_version',
	'minidocs_role_capabilities',
	'minidocs_remove_data_on_uninstall',
);

foreach ( $minidocs_options as $minidocs_option ) {
	delete_option( $minidocs_option );
}
