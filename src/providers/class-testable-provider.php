<?php
/**
 * Testable_Provider class file
 *
 * @package wp-page-cache-control
 */

namespace Alley\WP\WP_Page_Cache_Control\Providers;

/**
 * Testable Cache Provider
 */
class Testable_Provider extends Provider {
	public ?int $default_ttl = null;
	public ?int $default_ttl_rest_api = null;
	public bool $disable_cache = false;
	public bool $disable_cache_for_user = false;

	/**
	 * Set the TTL for the cache for the current request.
	 *
	 * @param int $seconds TTL in seconds.
	 * @return void
	 */
	public function ttl( int $seconds ): void

	/**
	 * Disable the page cache for the current request.
	 *
	 * @return void
	 */
	public function disable_cache(): void {
		$this->disable_cache = true;
	}

	/**
	 * Disable the page cache for the user for this and all subsequent requests.
	 *
	 * @return void
	 */
	public function disable_cache_for_user(): void {
		$this->disable_cache_for_user = true;
	}

	/**
	 * Register a cache group.
	 *
	 * @param array<int, string> $groups The groups to register.
	 * @return void
	 */
	public function register_groups( array $groups ): void {

	}

	/**
	 * Register a cache group.
	 *
	 * @param string $group The group to register.
	 * @return void
	 */
	public function register_group( string $group ): void;

	/**
	 * Retrieve the registered cache groups.
	 *
	 * @return array<int, string> The registered groups.
	 */
	public function groups(): array;

	/**
	 * Assign the user to a group and segment within that group.
	 *
	 * @param string $group The group to assign the user to.
	 * @param string $segment The segment within the group to assign the user to.
	 * @return void
	 */
	public function set_group_for_user( string $group, string $segment ): void;

	/**
	 * Check if the user is in a group and any segment within that group.
	 *
	 * @param string $group The group to check.
	 * @return bool True if the user is in the group, false otherwise.
	 */
	public function is_user_in_group( string $group ): bool;

	/**
	 * Check if the user is in a group and specific segment within that group.
	 *
	 * @param string $group The group to check.
	 * @param string $segment The segment within the group to check.
	 * @return bool True if the user is in the group and segment, false otherwise.
	 */
	public function is_user_in_group_segment( string $group, string $segment ): bool;

	/**
	 * Block a user by IP address.
	 *
	 * @param array<int, string>|string $ip The IP address(es) to block.
	 * @return void
	 */
	public function block_ip( array|string $ip ): void;

	/**
	 * Block a user by user agent.
	 *
	 * @param array<int, string>|string $user_agent The user agent(s) to block.
	 * @return void
	 */
	public function block_user_agent( array|string $user_agent ): void;

	/**
	 * Purge a specific URL from the cache.
	 *
	 * @param string $url The URL to purge.
	 * @return void
	 */
	public function purge( string $url ): void;

	/**
	 * Purge a specific post from the cache.
	 *
	 * @param WP_Post|int $post The post to purge.
	 * @return void
	 */
	public function purge_post( WP_Post|int $post ): void;

	/**
	 * Purge a specific term from the cache.
	 *
	 * @param WP_Term|int $term The term to purge.
	 * @return void
	 */
	public function purge_term( WP_Term|int $term ): void;

	/**
	 * Flush the entire page cache.
	 */
	public function flush(): void;
}
