<?php
/**
 * Testable_Provider class file
 *
 * @package wp-page-cache-control
 */

namespace Alley\WP\WP_Page_Cache_Control\Providers;

use WP_Post;
use WP_Term;

/**
 * Testable Cache Provider for use in unit tests.
 */
class Testable_Provider implements Provider {
	/**
	 * The time-to-live (TTL) for the cache for the current request.
	 *
	 * @var int|null
	 */
	protected ?int $ttl = null;

	/**
	 * The default time-to-live (TTL) for the cache for all REST API requests.
	 *
	 * @var int|null
	 */
	protected ?int $ttl_rest_api = null;

	/**
	 * Whether the page cache is disabled for the current request.
	 *
	 * @var bool
	 */
	protected bool $cache_disabled = false;

	/**
	 * Whether the page cache is disabled for the user for this and all subsequent requests.
	 *
	 * @var bool
	 */
	protected bool $cache_disabled_for_user = false;

	/**
	 * The cache groups that have been registered.
	 *
	 * @var array<string, string>
	 */
	protected array $groups = [];

	/**
	 * The IP addresses that are blocked from accessing the cache.
	 *
	 * @var array<int, string>
	 */
	protected array $blocked_ips = [];

	/**
	 * The user agents that are blocked from accessing the cache.
	 *
	 * @var array<int, string>
	 */
	protected array $blocked_user_agents = [];

	/**
	 * The URLs that have been purged from the cache.
	 *
	 * @var array<int, string>
	 */
	protected array $purged_urls = [];

	/**
	 * Whether the cache has been flushed.
	 *
	 * @var bool
	 */
	protected bool $did_flush_cache = false;

	/**
	 * Set the TTL for the cache for the current request.
	 *
	 * @param int $seconds TTL in seconds.
	 * @return void
	 */
	public function ttl( int $seconds ): void {
		$this->ttl = $seconds;
	}

	/**
	 * Check if the cache has the given TTL for the current request.
	 *
	 * @param int $seconds TTL in seconds.
	 * @return bool
	 */
	public function has_ttl( int $seconds ): bool {
		return $this->ttl === $seconds;
	}

	/**
	 * Set the default TTL for the cache for all REST API requests.
	 *
	 * @param int $seconds TTL in seconds.
	 * @return void
	 */
	public function ttl_rest_api( int $seconds ): void {
		$this->ttl_rest_api = $seconds;
	}

	/**
	 * Check if the cache has the given TTL for all REST API requests.
	 *
	 * @param int $seconds TTL in seconds.
	 * @return bool
	 */
	public function has_ttl_rest_api( int $seconds ): bool {
		return $this->ttl_rest_api === $seconds;
	}

	/**
	 * Disable the page cache for the current request.
	 *
	 * @return void
	 */
	public function disable_cache(): void {
		$this->cache_disabled = true;
	}

	/**
	 * Disable the page cache for the user for this and all subsequent requests.
	 *
	 * @return void
	 */
	public function disable_cache_for_user(): void {
		$this->cache_disabled_for_user = true;
	}

	/**
	 * Enable the page cache for the user for this and all subsequent requests.
	 *
	 * @return void
	 */
	public function enable_cache_for_user(): void {
		$this->cache_disabled_for_user = false;
	}

	/**
	 * Check if the page cache is disabled for the current request.
	 *
	 * @return bool
	 */
	public function is_user_cache_disabled(): bool {
		return $this->cache_disabled;
	}

	/**
	 * Check if the page cache is disabled for the user for this and all subsequent requests.
	 *
	 * @return bool
	 */
	public function is_user_cache_disabled_for_user(): bool {
		return $this->cache_disabled_for_user;
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
	 * @param string $group The group to register.
	 * @return void
	 */
	public function register_group( string $group ): void {
		$this->groups[ $group ] = $group;
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
	 * @param string $group The group to assign the user to.
	 * @param string $segment The segment within the group to assign the user to.
	 * @return void
	 */
	public function set_group_for_user( string $group, string $segment ): void {
		$this->groups[ $group ] = $segment;
	}

	/**
	 * Check if the user is in a group and any segment within that group.
	 *
	 * @param string $group The group to check.
	 * @return bool True if the user is in the group, false otherwise.
	 */
	public function is_user_in_group( string $group ): bool {
		return isset( $this->groups[ $group ] );
	}

	/**
	 * Check if the user is in a group and specific segment within that group.
	 *
	 * @param string $group The group to check.
	 * @param string $segment The segment within the group to check.
	 * @return bool True if the user is in the group and segment, false otherwise.
	 */
	public function is_user_in_group_segment( string $group, string $segment ): bool {
		return isset( $this->groups[ $group ] ) && $this->groups[ $group ] === $segment;
	}

	/**
	 * Block a user by IP address.
	 *
	 * @param array<int, string>|string $ip The IP address(es) to block.
	 * @return void
	 */
	public function block_ip( array|string $ip ): void {
		$this->blocked_ips = array_merge( $this->blocked_ips, (array) $ip );
	}

	/**
	 * Block a user by user agent.
	 *
	 * @param array<int, string>|string $user_agent The user agent(s) to block.
	 * @return void
	 */
	public function block_user_agent( array|string $user_agent ): void {
		$this->blocked_user_agents = array_merge( $this->blocked_user_agents, (array) $user_agent );
	}

	/**
	 * Check if the user is blocked by IP address.
	 *
	 * @param string $ip The IP address to check.
	 * @return bool True if the user is blocked, false otherwise.
	 */
	public function is_ip_blocked( string $ip ): bool {
		return in_array( $ip, $this->blocked_ips, true );
	}

	/**
	 * Check if the user is blocked by user agent.
	 *
	 * @param string $user_agent The user agent to check.
	 * @return bool True if the user is blocked, false otherwise.
	 */
	public function is_user_agent_blocked( string $user_agent ): bool {
		return in_array( $user_agent, $this->blocked_user_agents, true );
	}

	/**
	 * Purge a specific URL from the cache.
	 *
	 * @param string $url The URL to purge.
	 * @return void
	 */
	public function purge( string $url ): void {
		$this->purged_urls[] = $url;
	}

	/**
	 * Check if a URL has been purged from the page cache.
	 *
	 * @param string $url The URL to check.
	 * @return bool True if the URL has been purged, false otherwise.
	 */
	public function is_purged( string $url ): bool {
		return in_array( $url, $this->purged_urls, true );
	}

	/**
	 * Purge a specific post from the cache.
	 *
	 * @param WP_Post|int $post The post to purge.
	 * @return void
	 */
	public function purge_post( WP_Post|int $post ): void {
		$post = get_post( $post );

		if ( ! $post instanceof WP_Post ) {
			return;
		}

		$this->purge( get_permalink( $post ) );
	}

	/**
	 * Purge a specific term from the cache.
	 *
	 * @param WP_Term|int $term The term to purge.
	 * @return void
	 */
	public function purge_term( WP_Term|int $term ): void {
		$term = get_term( $term );

		if ( ! $term instanceof WP_Term ) {
			return;
		}

		$link = get_term_link( $term );

		if ( ! is_wp_error( $link ) ) {
			$this->purge( $link );
		}
	}

	/**
	 * Flush the entire page cache.
	 */
	public function flush(): void {
		$this->did_flush_cache = true;
	}

	/**
	 * Check if the entire page cache was flushed.
	 *
	 * @return bool True if the entire page cache was flushed, false otherwise.
	 */
	public function is_site_cache_purged(): bool {
		return $this->did_flush_cache;
	}

	/**
	 * Send all headers and cookies for a provider on-demand.
	 */
	public function send_headers(): void {
		// No-op.
	}
}
