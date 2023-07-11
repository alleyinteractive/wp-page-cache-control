<?php
/**
 * Manages_Cookies trait file
 *
 * phpcs:disable WordPressVIPMinimum.Functions.RestrictedFunctions.cookies_setcookie, WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___COOKIE
 *
 * @package wp-page-cache-control
 */

namespace Alley\WP\WP_Page_Cache_Control\Providers\Concerns;

use function Mantle\Support\Helpers\collect;

/**
 * Trait to manage cookies.
 */
trait Manages_Cookies {
	/**
	 * Storage of cookies to be set.
	 *
	 * @var array<string, array{value: string, ttl?: int}>
	 */
	protected array $cookie_queue = [];

	/**
	 * Set a cookie.
	 *
	 * @param string $name The name of the cookie.
	 * @param string $value The value of the cookie.
	 * @return void
	 */
	public function set_cookie( string $name, string $value ): void {
		$this->cookie_queue[ $name ] = [
			'value' => $value,
		];
	}

	/**
	 * Remove a cookie.
	 *
	 * @param string $name The name of the cookie.
	 * @return void
	 */
	public function remove_cookie( string $name ): void {
		$this->cookie_queue[ $name ] = [
			'value' => '',
			'ttl'   => -1,
		];
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

	/**
	 * Send the cookies from the queue.
	 *
	 * @return void
	 */
	protected function send_cookies(): void {
		foreach ( $this->cookie_queue as $name => $cookie ) {
			$ttl = $cookie['ttl'] ?? time() + $this->cookie_ttl( $name );

			// Send the cookie if we're not testing.
			if ( ! headers_sent() && ( ! defined( 'WP_TESTS_DOMAIN' ) || ! WP_TESTS_DOMAIN ) ) {
				setcookie(
					$name,
					$cookie['value'],
					$ttl,
					COOKIEPATH,
					COOKIE_DOMAIN,
				);
			}

			// Update the global variable reference.
			if ( $ttl > time() ) {
				$_COOKIE[ $name ] = $cookie['value']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			} else {
				unset( $_COOKIE[ $name ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			}
		}

		$this->cookie_queue = [];
	}

	/**
	 * Retrieve the cookie queue.
	 *
	 * @return array<string, string>
	 */
	public function get_cookie_queue(): array {
		return collect( $this->cookie_queue )
			->map( fn( $cookie ) => $cookie['value'] )
			->all();
	}
}
