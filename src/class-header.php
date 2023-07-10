<?php
/**
 * Header class file
 *
 * @package wp-page-cache-control
 */

namespace Alley\WP\WP_Page_Cache_Control;

use PHPUnit\Framework\Assert;
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
	 * Flag to indicate whether to fake headers and not send them for testing.
	 *
	 * @var bool
	 */
	public static bool $fake = false;

	/**
	 * Flag to fake the headers being sent.
	 *
	 * @param bool $fake Whether to fake the headers being sent.
	 * @return void
	 */
	public static function fake( bool $fake = true ): void {
		if ( $fake ) {
			static::$record = [];
		}

		static::$fake = $fake;
	}

	/**
	 * Send a response header.
	 *
	 * @param string|array<string, string> $header The header(s) to send.
	 * @param string $value                        The value of the header.
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

		if ( ! static::$fake ) {
			if ( headers_sent() ) {
				_doing_it_wrong(
					__CLASS__ . '::' . __FUNCTION__,
					'Headers already sent, unable to send header ' . $header . ' with value ' . $value,
					'1.0.0',
				);

				return;
			}

			header( $header . ': ' . $value );
		}
	}

	/**
	 * Send a max-age header.
	 *
	 * @param int $seconds The number of seconds to set the max-age to.
	 * @return void
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
			function ( $response, $server, $request ) use ( $seconds ): WP_REST_Response {
				if ( ! ( $response instanceof WP_REST_Response ) || ! ( $request instanceof WP_REST_Request ) ) {
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
		);
	}

	/**
	 * Send no-cache headers.
	 *
	 * @return void
	 */
	public static function no_cache(): void {
		$headers = wp_get_nocache_headers();

		unset( $headers['Last-Modified'] );

		foreach ( $headers as $name => $field_value ) {
			static::send( $name, $field_value );
		}
	}

	/**
	 * Assert if a header was sent.
	 *
	 * @param string $header The header to check.
	 * @param string $value The value of the header to check, optional.
	 */
	public static function assertSent( string $header, ?string $value = null ): void {
		if ( ! class_exists( Assert::class ) ) {
			return;
		}

		$header = strtolower( $header );

		if ( empty( static::$record[ $header ] ) ) {
			Assert::fail( 'Header ' . $header . ' was not sent' );
			return;
		}

		if ( is_null( $value ) ) {
			return;
		}

		foreach ( static::$record[ $header ] as $sent_value ) {
			if ( $value === $sent_value ) {
				Assert::assertTrue( true, "Header $header was sent with value $value" );
				return;
			}
		}

		Assert::fail( "Header $header was not sent with value $value" );
	}

	/**
	 * Assert if a header was not sent.
	 *
	 * @param string $header The header to check.
	 * @param string $value The value of the header to check, optional.
	 */
	public static function assertNotSent( string $header, ?string $value = null ): void {
		if ( ! class_exists( Assert::class ) ) {
			return;
		}

		$header = strtolower( $header );

		if ( empty( static::$record[ $header ] ) ) {
			Assert::assertTrue( true, 'Header ' . $header . ' was not sent' );
			return;
		}

		if ( is_null( $value ) ) {
			return;
		}

		foreach ( static::$record[ $header ] as $sent_value ) {
			if ( $value === $sent_value ) {
				Assert::fail( "Header $header was sent with value $value" );
				return;
			}
		}

		Assert::assertTrue( true, "Header $header was not sent with value $value" );
	}
}
