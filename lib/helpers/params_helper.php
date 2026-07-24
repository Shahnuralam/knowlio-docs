<?php
/**
 * Request parameter collection.
 *
 * Every controller reads request data through this helper instead of touching
 * the superglobals, which gives one place to unslash and whitelist input.
 *
 * @package MiniDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MdParamsHelper
 */
class MdParamsHelper {

	/**
	 * Cached params for the current request.
	 *
	 * @var array|null
	 */
	private static $params = null;

	/**
	 * Load params from the request.
	 *
	 * The JS layer posts a url-encoded `params` string (the serialized form), so
	 * that is parsed first, then merged with plain POST and GET data.
	 */
	private static function load_params() {
		$post_params = array();

		// phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
		if ( isset( $_POST['params'] ) ) {
			if ( is_string( $_POST['params'] ) ) {
				parse_str( wp_unslash( $_POST['params'] ), $post_params );
			} elseif ( is_array( $_POST['params'] ) ) {
				$post_params = wp_unslash( $_POST['params'] );
			}
		}

		$params = array_merge(
			(array) $post_params,
			wp_unslash( (array) $_POST ),
			wp_unslash( (array) $_GET )
		);
		// phpcs:enable

		unset( $params['params'] );

		self::$params = self::deep_clean( $params );
	}

	/**
	 * Recursively trim and strip control characters. Field-level sanitization
	 * still happens in the model, this is only a first pass.
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return mixed
	 */
	private static function deep_clean( $value ) {
		if ( is_array( $value ) ) {
			return array_map( array( __CLASS__, 'deep_clean' ), $value );
		}

		if ( is_string( $value ) ) {
			return trim( $value );
		}

		return $value;
	}

	/**
	 * Get every param of the current request.
	 *
	 * @return array
	 */
	public static function get_params(): array {
		if ( is_null( self::$params ) ) {
			self::load_params();
		}

		return self::$params;
	}

	/**
	 * Get a single param.
	 *
	 * @param string $name    Param name.
	 * @param mixed  $default Fallback value.
	 *
	 * @return mixed
	 */
	public static function get_param( string $name, $default = null ) {
		$params = self::get_params();

		return $params[ $name ] ?? $default;
	}

	/**
	 * Uploaded files for the current request.
	 *
	 * @return array
	 */
	public static function get_files(): array {
		return isset( $_FILES ) ? (array) $_FILES : array();
	}

	/**
	 * Keep only the whitelisted keys of an array. Used before mass assignment.
	 *
	 * @param array $params       Incoming params.
	 * @param array $allowed_keys Whitelisted keys.
	 *
	 * @return array
	 */
	public static function permit_params( array $params, array $allowed_keys ): array {
		return array_intersect_key( $params, array_flip( $allowed_keys ) );
	}

	/**
	 * Apply a named sanitization rule to a value.
	 *
	 * @param mixed  $value Raw value.
	 * @param string $rule  Rule name.
	 *
	 * @return mixed
	 */
	public static function sanitize_param( $value, string $rule ) {
		switch ( $rule ) {
			case 'text':
				return sanitize_text_field( (string) $value );

			case 'textarea':
				return sanitize_textarea_field( (string) $value );

			case 'html':
				return wp_kses_post( (string) $value );

			case 'email':
				return sanitize_email( (string) $value );

			case 'url':
				return esc_url_raw( (string) $value );

			case 'slug':
				return sanitize_title( (string) $value );

			case 'key':
				return sanitize_key( (string) $value );

			case 'int':
				return (int) $value;

			case 'absint':
				return absint( $value );

			case 'money':
				return round( (float) preg_replace( '/[^0-9.\-]/', '', (string) $value ), 2 );

			case 'float':
				return (float) $value;

			case 'bool':
				return ( 'on' === $value || '1' === (string) $value || true === $value ) ? 1 : 0;

			case 'date':
				$value = sanitize_text_field( (string) $value );
				return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ? $value : '';

			default:
				return $value;
		}
	}
}
