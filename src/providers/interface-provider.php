<?php
/**
 * Provider interface file
 *
 * @package wp-page-cache-control
 */

namespace Alley\WP\WP_Page_Cache_Control\Providers;

use WP_Post;
use WP_Term;

/**
 * Base provider contract.
 *
 * @todo Allow URLs to be registered that will be purged when a post/term is purged.
 * @todo Add method to perform the purge of the queued URLs.
 */
interface Provider {
	/**
	 * Set the TTL for the cache for the current request.
	 *
	 * @param int $seconds TTL in seconds.
	 */
	public function ttl( int $seconds ): void;

	/**
	 * Set the default TTL for the cache for all REST API requests.
	 *
	 * @param int $seconds TTL in seconds.
	 */
	public function ttl_rest_api( int $seconds ): void;

	/**
	 * Disable the page cache for the current request.
	 */
	public function disable_cache(): void;

	/**
	 * Disable the page cache for the user for this and all subsequent requests.
	 */
	public function disable_cache_for_user(): void;

	/**
	 * Enable the page cache for the user for this and all subsequent requests.
	 */
	public function enable_cache_for_user(): void;

	/**
	 * Check if the page cache is disabled for the current request.
	 *
	 * @return bool
	 */
	public function is_user_cache_disabled(): bool;

	/**
	 * Register a cache group.
	 *
	 * @param array<int, string> $groups The groups to register.
	 */
	public function register_groups( array $groups ): void;

	/**
	 * Register a cache group.
	 *
	 * @param string $group The group to register.
	 */
	public function register_group( string $group ): void;

	/**
	 * Retrieve the registered cache groups.
	 *
	 * @return array<int, string> The registered groups.
	 */
	public function get_groups(): array;

	/**
	 * Assign the user to a group and segment within that group.
	 *
	 * @param string $group The group to assign the user to.
	 * @param string $segment The segment within the group to assign the user to.
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
	 */
	public function block_ip( array|string $ip ): void;

	/**
	 * Block a user by user agent.
	 *
	 * @param array<int, string>|string $user_agent The user agent(s) to block.
	 */
	public function block_user_agent( array|string $user_agent ): void;

	/**
	 * Purge a specific URL from the cache.
	 *
	 * @param string $url The URL to purge.
	 * @return mixed
	 */
	public function purge( string $url ): mixed;

	/**
	 * Purge a specific post from the cache.
	 *
	 * @param WP_Post|int $post The post to purge.
	 */
	public function purge_post( WP_Post|int $post ): void;

	/**
	 * Purge a specific term from the cache.
	 *
	 * @param WP_Term|int $term The term to purge.
	 */
	public function purge_term( WP_Term|int $term ): void;

	/**
	 * Flush the entire page cache.
	 *
	 * **WARNING:** This will purge the entire page cache. Use with caution.
	 */
	public function flush(): void;

	/**
	 * Send all headers and cookies for a provider on-demand.
	 */
	public function send_headers(): void;
}
