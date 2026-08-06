<?php
/**
 * Request parameter collection.
 *
 * Every controller reads request data through this helper instead of touching
 * the superglobals, which gives one place to unslash and whitelist input.
 *
 * @package KnowlioDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class KnowlioParamsHelper
 */
class KnowlioParamsHelper {

	/**
	 * Cached params for the current request.
	 *
	 * @var array|null
	 */
	private static $params = null;

	/**
	 * Collect and sanitize the request.
	 *
	 * Everything is unslashed and sanitized here, as soon as it is read, rather
	 * than later on at the point of use: `sanitize_textarea_field()` is applied
	 * to every value of both superglobals, so nothing reaches a controller,
	 * a hook or the query builder in its raw form.
	 *
	 * The article body is the one exception. It is HTML by design, and the
	 * plain-text sanitizer would strip every tag out of it, so it takes
	 * `wp_kses_post()` instead -- the correct sanitizer for that content, and
	 * the same one the model applies again before the value is persisted.
	 *
	 * GET is merged last so that it wins, which is what the admin screens
	 * expect: the page URL decides the route, a form post decides the payload.
	 */
	private static function load_params() {
		/*
		 * Nonce verification belongs to the action that acts on these params,
		 * not to the code that reads them: see KnowlioController::check_nonce(),
		 * which every write action calls before it touches the database.
		 */
		// phpcs:disable WordPress.Security.NonceVerification

		$post = map_deep( wp_unslash( $_POST ), 'sanitize_textarea_field' );
		$get  = map_deep( wp_unslash( $_GET ), 'sanitize_textarea_field' );

		if ( isset( $_POST['article']['content'] ) && is_string( $_POST['article']['content'] ) ) {
			$post['article']['content'] = wp_kses_post( wp_unslash( $_POST['article']['content'] ) );
		}

		// phpcs:enable

		self::$params = self::deep_trim( array_merge( $post, $get ) );
	}

	/**
	 * Recursively trim surrounding whitespace. Sanitization has already
	 * happened in load_params(); this only tidies the values.
	 *
	 * @param mixed $value Sanitized value.
	 *
	 * @return mixed
	 */
	private static function deep_trim( $value ) {
		if ( is_array( $value ) ) {
			return array_map( array( __CLASS__, 'deep_trim' ), $value );
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
		/*
		 * Every rule below produces a scalar. A crafted payload can post an
		 * array where a field is expected (`article[title][]=x`), and casting
		 * that to string would yield the literal "Array" plus a PHP warning,
		 * so it is rejected outright instead.
		 */
		if ( is_array( $value ) || is_object( $value ) ) {
			$value = '';
		}

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
