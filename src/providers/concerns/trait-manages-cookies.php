<?php
/**
 * Manages_Cookies trait file
 *
 * phpcs:disable WordPressVIPMinimum.Functions.RestrictedFunctions.cookies_setcookie, WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___COOKIE
 *
 * @package wp-page-cache-control
 */

namespace Alley\WP\WP_Page_Cache_Control\Providers\Concerns;

/**
 * Trait to manage cookies.
 */
trait Manages_Cookies {
	/**
	 * Set a cookie.
	 *
	 * @param string $name The name of the cookie.
	 * @param string $value The value of the cookie.
	 * @return void
	 */
	public function set_cookie( string $name, string $value ): void {
		$_COOKIE[ $name ] = $value;

		// Set the cookie if we're not running tests.
		if ( ! defined( 'WP_TESTS_DOMAIN' ) ) {
			setcookie( $name, $value, $this->cookie_ttl( $name ), COOKIEPATH, COOKIE_DOMAIN );
		}
	}

	/**
	 * Remove a cookie.
	 *
	 * @param string $name The name of the cookie.
	 * @return void
	 */
	public function remove_cookie( string $name ): void {
		unset( $_COOKIE[ $name ] );

		// Remove the cookie if we're not running tests.
		if ( ! defined( 'WP_TESTS_DOMAIN' ) ) {
			setcookie( $name, '', time() - YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );
		}
	}

	/**
	 * Get the value of a cookie.
	 *
	 * @param string $name The name of the cookie.
	 */
	public function get_cookie( string $name ): ?string {
		return $_COOKIE[ $name ] ?? null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	}

	/**
	 * Determine the TTL of the cookie.
	 *
	 * @param string $name The name of the cookie.
	 * @return int The TTL of the cookie.
	 */
	public function cookie_ttl( string $name ): int {
		return (int) apply_filters( 'wp_page_cache_control_cookie_ttl', MONTH_IN_SECONDS, $name );
	}
}
