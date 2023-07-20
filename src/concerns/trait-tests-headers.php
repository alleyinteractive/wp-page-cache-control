<?php
/**
 * Tests_Headers class file
 *
 * phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid, WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
 *
 * @package wp-page-cache-control
 */

namespace Alley\WP\WP_Page_Cache_Control\Concerns;

use Alley\WP\WP_Page_Cache_Control\Header;
use PHPUnit\Framework\Assert;

/**
 * Trait to assist in testing headers.
 */
trait Tests_Headers {
	/**
	 * Assert if a header was sent.
	 *
	 * @param string $header The header to check.
	 * @param string $value The value of the header to check, optional.
	 */
	public static function assertHeaderSent( string $header, ?string $value = null ): void {
		if ( ! class_exists( Assert::class ) ) {
			trigger_error(
				'Assert class not found. Please install phpunit/phpunit to use this method.',
				E_USER_WARNING
			);

			return;
		}

		$header = strtolower( $header );

		if ( empty( Header::$record[ $header ] ) ) {
			Assert::fail( 'Header ' . $header . ' was not sent' );
		}

		if ( is_null( $value ) ) {
			return;
		}

		foreach ( Header::$record[ $header ] as $sent_value ) {
			if ( $value === $sent_value ) {
				Assert::assertTrue( true, "Header $header was sent with value '$value'" );
				return;
			}
		}

		Assert::fail( "Header $header was not sent with value '$value'" );
	}

	/**
	 * Assert if a header was not sent.
	 *
	 * @param string $header The header to check.
	 * @param string $value The value of the header to check, optional.
	 */
	public static function assertHeaderNotSent( string $header, ?string $value = null ): void {
		if ( ! class_exists( Assert::class ) ) {
			trigger_error(
				'Assert class not found. Please install phpunit/phpunit to use this method.',
				E_USER_WARNING
			);

			return;
		}

		$header = strtolower( $header );

		if ( empty( Header::$record[ $header ] ) ) {
			Assert::assertTrue( true, 'Header ' . $header . ' was not sent' );
			return;
		}

		if ( is_null( $value ) ) {
			return;
		}

		foreach ( Header::$record[ $header ] as $sent_value ) {
			if ( $value === $sent_value ) {
				Assert::fail( "Header $header was sent with value $value" );
			}
		}

		Assert::assertTrue( true, "Header $header was not sent with value $value" );
	}

	/**
	 * Assert that any header was sent.
	 */
	public static function assertAnyHeadersSent(): void {
		if ( ! class_exists( Assert::class ) ) {
			trigger_error(
				'Assert class not found. Please install phpunit/phpunit to use this method.',
				E_USER_WARNING
			);

			return;
		}

		Assert::assertNotEmpty( Header::$record, 'No headers were sent' );
	}

	/**
	 * Assert that no headers were sent.
	 */
	public static function assertNoHeadersSent(): void {
		if ( ! class_exists( Assert::class ) ) {
			trigger_error(
				'Assert class not found. Please install phpunit/phpunit to use this method.',
				E_USER_WARNING
			);

			return;
		}

		Assert::assertEmpty( Header::$record, 'Headers were sent' );
	}
}
