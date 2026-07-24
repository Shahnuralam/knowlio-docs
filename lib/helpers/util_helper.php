<?php
/**
 * Misc utilities.
 *
 * @package MiniDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MdUtilHelper
 */
class MdUtilHelper {

	/**
	 * Build the `data-md-*` attribute string that turns any element into an
	 * ajax trigger handled by the generic JS delegator.
	 *
	 * @param string $route      Route name (`controller__action`).
	 * @param array  $params     Params sent with the request.
	 * @param string $target     Output target: `side-panel`, `lightbox`, or a CSS selector.
	 * @param string $after_call Optional JS callback name executed after the response.
	 *
	 * @return string Escaped attribute string ready to be printed inside a tag.
	 */
	public static function build_action_atts( string $route, array $params = array(), string $target = 'side-panel', string $after_call = '' ): string {
		$atts = ' data-md-action="' . esc_attr( $route ) . '"';
		$atts .= ' data-md-params="' . esc_attr( http_build_query( $params ) ) . '"';
		$atts .= ' data-md-target="' . esc_attr( $target ) . '"';

		if ( $after_call ) {
			$atts .= ' data-md-after-call="' . esc_attr( $after_call ) . '"';
		}

		return $atts;
	}

	/**
	 * Current UTC datetime in the database format.
	 *
	 * @return string
	 */
	public static function now_db_datetime(): string {
		return gmdate( MINIDOCS_DATETIME_DB_FORMAT );
	}

	/**
	 * Format a stored UTC datetime using the site's date format.
	 *
	 * @param string|null $datetime Datetime string from the database.
	 * @param string      $default  Fallback when the value is empty/invalid.
	 *
	 * @return string
	 */
	public static function format_datetime( $datetime, string $default = 'n/a' ): string {
		if ( empty( $datetime ) ) {
			return $default;
		}

		$timestamp = strtotime( $datetime . ' UTC' );
		if ( ! $timestamp ) {
			return $default;
		}

		return wp_date( get_option( 'date_format' ), $timestamp );
	}

	/**
	 * Slugify an arbitrary string.
	 *
	 * @param string $value Raw string.
	 *
	 * @return string
	 */
	public static function slugify( string $value ): string {
		return sanitize_title( $value );
	}

	/**
	 * Random alphanumeric string, used for export filenames and form ids.
	 *
	 * @param int $length Desired length.
	 *
	 * @return string
	 */
	public static function random_text( int $length = 8 ): string {
		return substr( str_shuffle( 'abcdefghijklmnopqrstuvwxyz0123456789' ), 0, max( 1, $length ) );
	}

	/**
	 * Stream an array of rows as a CSV download.
	 *
	 * @param array  $rows     Rows, each an array of scalar values.
	 * @param string $filename Filename without extension.
	 */
	public static function array_to_csv( array $rows, string $filename = 'export' ) {
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename . '.csv' );

		$output = fopen( 'php://output', 'w' );
		foreach ( $rows as $row ) {
			fputcsv( $output, (array) $row );
		}
		fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
	}

	/**
	 * Escape a value for output inside an HTML attribute list.
	 *
	 * @param array $atts Attribute map.
	 *
	 * @return string
	 */
	public static function atts_string_from_array( array $atts = array() ): string {
		$pairs = array();
		foreach ( $atts as $key => $value ) {
			if ( is_bool( $value ) ) {
				if ( $value ) {
					$pairs[] = esc_attr( $key );
				}
				continue;
			}
			$pairs[] = esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
		}

		return implode( ' ', $pairs );
	}
}
