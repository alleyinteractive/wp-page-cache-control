<?php
/**
 * Pantheon_Provider class file
 *
 * @package wp-page-cache-control
 */

namespace Alley\WP\WP_Page_Cache_Control\Providers;

use Alley\WP\WP_Page_Cache_Control\Header;
use Automattic\VIP\Cache\Vary_Cache;
use InvalidArgumentException;
use Mantle\Support\Str;
use VIP_Request_Block;
use WP_Post;
use WP_Term;
use WPCOM_VIP_Cache_Manager;

use function Mantle\Support\Helpers\collect;

/**
 * Pantheon Advanced Page Cache Provider
 *
 * @link https://docs.pantheon.io/cookies#cache-varying-cookies
 */
class Pantheon_Provider implements Provider {
	use Concerns\Manages_Cookies;

	/**
	 * Cookie name for the no-cache cookie.
	 *
	 * @var string
	 */
	const COOKIE_NO_CACHE = 'NO_CACHE';

	/**
	 * Cookie prefix for cache groups.
	 *
	 * @var string
	 */
	const COOKIE_SEGMENT_PREFIX = 'STYXKEY-';

	/**
	 * Storage of cache groups.
	 *
	 * @var array<string, string>
	 */
	protected array $groups = [];

	/**
	 * Constructor.
	 *
	 * @throws InvalidArgumentException If the Vary_Cache class does not exist.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'read_cookies' ] );
		add_action( 'send_headers', [ $this, 'send_headers' ] );
	}

	/**
	 * Set the TTL for the cache for the current request.
	 *
	 * @param int $seconds TTL in seconds.
	 * @return void
	 */
	public function ttl( int $seconds ): void {
		Header::max_age( $seconds );
	}

	/**
	 * Set the default TTL for the cache for all REST API requests.
	 *
	 * @param int $seconds TTL in seconds.
	 * @return void
	 */
	public function ttl_rest_api( int $seconds ): void {
		Header::rest_max_age( $seconds );
	}

	/**
	 * Disable the page cache for the current request.
	 *
	 * @return void
	 */
	public function disable_cache(): void {
		Header::no_cache();
	}

	/**
	 * Disable the page cache for the user for this and all subsequent requests.
	 *
	 * @return void
	 */
	public function disable_cache_for_user(): void {
		$this->set_cookie( self::COOKIE_NO_CACHE, '1' );
	}

	/**
	 * Enable the page cache for the user for this and all subsequent requests.
	 *
	 * @return void
	 */
	public function enable_cache_for_user(): void {
		$this->remove_cookie( self::COOKIE_NO_CACHE );
	}

	/**
	 * Check if the page cache is disabled for the current request.
	 *
	 * @return bool
	 */
	public function is_user_cache_disabled(): bool {
		return ! empty( $this->get_cookie( self::COOKIE_NO_CACHE ) );
	}

	/**
	 * Register a cache group.
	 *
	 * @param array<int, string> $groups The groups to register.
	 * @return void
	 */
	public function register_groups( array $groups ): void {
		foreach ( $groups as $group ) {
			$this->register_group( $group );
		}
	}

	/**
	 * Register a cache group.
	 *
	 * @throws InvalidArgumentException If the group name is invalid.
	 *
	 * @param string $group The group to register.
	 * @return void
	 */
	public function register_group( string $group ): void {
		if ( ! isset( $this->groups[ $group ] ) ) {
			if ( ! $this->is_valid_group_name( $group ) ) {
				throw new InvalidArgumentException(
					"Invalid group name: {$group}"
				);
			}

			$this->groups[ $group ] = '';
		}
	}

	/**
	 * Retrieve the registered cache groups.
	 *
	 * @return array<int, string> The registered groups.
	 */
	public function groups(): array {
		return array_keys( $this->groups );
	}

	/**
	 * Assign the user to a group and segment within that group.
	 *
	 * @throws InvalidArgumentException If the group or segment is invalid.
	 *
	 * @param string $group The group to assign the user to.
	 * @param string $segment The segment within the group to assign the user to.
	 * @return void
	 */
	public function set_group_for_user( string $group, string $segment ): void {
		if ( ! isset( $this->groups[ $group ] ) ) {
			throw new InvalidArgumentException(
				"Unknown cache group: {$group}"
			);
		}

		$this->groups[ $group ] = $segment;
	}

	/**
	 * Check if the user is in a group and any segment within that group.
	 *
	 * @param string $group The group to check.
	 * @return bool True if the user is in the group, false otherwise.
	 */
	public function is_user_in_group( string $group ): bool {
		return ! empty( $this->groups[ $group ] );
	}

	/**
	 * Check if the user is in a group and specific segment within that group.
	 *
	 * @param string $group The group to check.
	 * @param string $segment The segment within the group to check.
	 * @return bool True if the user is in the group and segment, false otherwise.
	 */
	public function is_user_in_group_segment( string $group, string $segment ): bool {
		return ! empty( $this->groups[ $group ] ) && $this->groups[ $group ] === $segment;
	}

	/**
	 * Block a user by IP address.
	 *
	 * @param array<int, string>|string $ip The IP address(es) to block.
	 * @return void
	 */
	public function block_ip( array|string $ip ): void {
		if ( is_array( $ip ) ) {
			foreach ( $ip as $single_ip ) {
				static::block_ip( $single_ip );
			}

			return;
		}

		// VIP_Request_Block::ip( $ip );
	}

	/**
	 * Block a user by user agent.
	 *
	 * @param array<int, string>|string $user_agent The user agent(s) to block.
	 * @return void
	 */
	public function block_user_agent( array|string $user_agent ): void {
		if ( is_array( $user_agent ) ) {
			foreach ( $user_agent as $single_user_agent ) {
				static::block_user_agent( $single_user_agent );
			}

			return;
		}

		// VIP_Request_Block::ua( $user_agent );
	}

	/**
	 * Purge a specific URL from the cache.
	 *
	 * @param string $url The URL to purge.
	 * @return void
	 */
	public function purge( string $url ): void {
		// wpcom_vip_purge_edge_cache_for_url( $url );
	}

	/**
	 * Purge a specific post from the cache.
	 *
	 * @param WP_Post|int $post The post to purge.
	 * @return void
	 */
	public function purge_post( WP_Post|int $post ): void {
		// wpcom_vip_purge_edge_cache_for_post( $post );
	}

	/**
	 * Purge a specific term from the cache.
	 *
	 * @param WP_Term|int $term The term to purge.
	 * @return void
	 */
	public function purge_term( WP_Term|int $term ): void {
		// wpcom_vip_purge_edge_cache_for_term( $term );
	}

	/**
	 * Flush the entire page cache for a site.
	 */
	public function flush(): void {
		pantheon_wp_clear_edge_all();
	}

	/**
	 * Check if a group name is valid.
	 *
	 * @param string $name The group name to check.
	 * @return bool True if the group name is valid, false otherwise.
	 */
	public function is_valid_group_name( string $value ): bool {
		return ! empty( $value ) && 1 === preg_match( '/^[a-zA-Z0-9_-]+$/', $value );
	}

	/**
	 * Read the cookies from the request and assign the user to groups.
	 */
	public function read_cookies() {
		if ( empty( $_COOKIE ) ) {
			return;
		}

		collect( $_COOKIE ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			->map_with_keys(
				fn ( $value, $key ) => Str::starts_with( $key, self::COOKIE_SEGMENT_PREFIX )
					? [ Str::after( $key, self::COOKIE_SEGMENT_PREFIX ) => $value ]
					: []
			)
			->filter()
			->each(
				function ( $value, $key ) {
					if ( isset( $this->groups[ $key ] ) ) {
						$this->groups[ $key ] = $value;
					}
				}
			);
	}

	/**
	 * Send the headers and cookies.
	 */
	public function send_headers() {

	}
}
