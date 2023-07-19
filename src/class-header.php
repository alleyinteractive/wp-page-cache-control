<?php
/**
 * Header class file
 *
 * phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
 *
 * @package wp-page-cache-control
 */

namespace Alley\WP\WP_Page_Cache_Control;

use WP_REST_Request;
use WP_REST_Response;
use WPCOM_VIP_Cache_Manager;

/**
 * Manage the sending and assertions of HTTP headers.
 */
class Header {
	/**
	 * Record of all headers set by the plugin.
	 *
	 * @var array<string, array<int, string>>
	 */
	public static array $record = [];

	/**
	 * Storage of the sent headers.
	 *
	 * @var array<string, array<int, string>>
	 */
	public static array $sent = [];

	/**
	 * Flag to indicate whether to fake headers and not send them for testing.
	 *
	 * @var bool
	 */
	public static bool $fake = false;

	/**
	 * Flag to fake the headers being sent.
	 *
	 * @param bool $fake Whether to fake the headers being sent.
	 */
	public static function fake( bool $fake = true ): void {
		static::$fake = $fake;
	}

	/**
	 * Flush the record and sent headers.
	 */
	public static function flush(): void {
		static::$record = [];
		static::$sent   = [];
	}

	/**
	 * Determine if any/a specific header has been sent.
	 *
	 * @param string|null $header The header to check.
	 */
	public static function sent( string|null $header = null ): bool {
		if ( is_null( $header ) ) {
			return ! empty( static::$sent );
		}

		return isset( static::$sent[ strtolower( $header ) ] );
	}

	/**
	 * Send a response header.
	 *
	 * @param string|array<string, string> $header The header(s) to send.
	 * @param string                       $value                        The value of the header.
	 */
	public static function send( array|string $header, string $value = '' ): void {
		if ( is_array( $header ) ) {
			foreach ( $header as $single_header ) {
				static::send( $single_header, $value );
			}

			return;
		}

		$header = strtolower( $header );

		static::$record[ $header ][] = $value;
	}

	/**
	 * Send all queued headers.
	 */
	public static function send_headers(): void {
		// Prevent the headers from being sent if they have already been sent or
		// if we are faking them.
		if ( headers_sent() || static::$fake ) {
			return;
		}

		foreach ( static::$record as $header => $values ) {
			foreach ( $values as $value ) {
				// Check if the header has already been sent.
				if ( in_array( $value, static::$sent[ $header ] ?? [], true ) ) {
					continue;
				}

				header( $header . ': ' . $value );

				static::$sent[ $header ][] = $value;
			}
		}
	}

	/**
	 * Send a max-age header.
	 *
	 * @param int $seconds The number of seconds to set the max-age to.
	 */
	public static function max_age( int $seconds ): void {
		static::send( 'Cache-Control', 'max-age=' . $seconds );
	}

	/**
	 * Setup a max-age header for GET/HEAD requests to the REST API.
	 *
	 * @param int $seconds The number of seconds to set the max-age to.
	 */
	public static function rest_max_age( int $seconds ): void {
		// Use the VIP cache manager method if it exists.
		if ( class_exists( WPCOM_VIP_Cache_Manager::class ) ) {
			add_filter( 'wpcom_vip_rest_read_response_ttl', fn () => $seconds );

			return;
		}

		add_action(
			'rest_post_dispatch',
			function ( $response, $server, $request ) use ( $seconds ) {
				if ( ! ( $response instanceof WP_REST_Response ) || ! ( $request instanceof WP_REST_Request ) ) {
					return $response;
				}

				if ( headers_sent() ) {
					return $response;
				}

				$method = $request->get_method();

				if ( ! in_array( $method, [ 'GET', 'HEAD' ], true ) ) {
					return $response;
				}

				$headers = $response->get_headers();

				// Don't override the header if it's already set.
				if ( isset( $headers['Cache-Control'] ) ) {
					return $response;
				}

				$response->header( 'Cache-Control', 'max-age=' . $seconds, true );

				return $response;
			},
			99,
			3,
		);
	}

	/**
	 * Send no-cache headers.
	 *
	 */
	public static function no_cache(): void {
		$headers = wp_get_nocache_headers();

		unset( $headers['Last-Modified'] );

		foreach ( $headers as $name => $field_value ) {
			static::send( $name, $field_value );
		}
	}
}
