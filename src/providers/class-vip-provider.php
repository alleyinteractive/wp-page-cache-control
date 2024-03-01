<?php
/**
 * VIP_Provider class file
 *
 * @package wp-page-cache-control
 */

namespace Alley\WP\WP_Page_Cache_Control\Providers;

use Alley\WP\WP_Page_Cache_Control\Header;
use Automattic\VIP\Cache\Vary_Cache;
use InvalidArgumentException;
use VIP_Request_Block;
use WP_Post;
use WP_Term;
use WPCOM_VIP_Cache_Manager;

/**
 * WordPress VIP Cache Provider
 */
class VIP_Provider implements Provider {
	/**
	 * Constructor.
	 *
	 * @throws InvalidArgumentException If the Vary_Cache class does not exist.
	 */
	public function __construct() {
		// Ensure Vary_Cache is loaded if it exists.
		if ( ! class_exists( Vary_Cache::class ) && file_exists( WPMU_PLUGIN_DIR . '/cache/class-vary-cache.php' ) ) {
			require_once WPMU_PLUGIN_DIR . '/cache/class-vary-cache.php';
		} elseif ( ! class_exists( Vary_Cache::class ) ) {
			throw new InvalidArgumentException( 'Vary_Cache class not found.' );
		}
	}

	/**
	 * Set the TTL for the cache for the current request.
	 *
	 * @param int $seconds TTL in seconds.
	 */
	public function ttl( int $seconds ): void {
		Header::max_age( $seconds );
	}

	/**
	 * Set the default TTL for the cache for all REST API requests.
	 *
	 * @param int $seconds TTL in seconds.
	 */
	public function ttl_rest_api( int $seconds ): void {
		Header::rest_max_age( $seconds );
	}

	/**
	 * Disable the page cache for the current request.
	 */
	public function disable_cache(): void {
		Header::no_cache();
	}

	/**
	 * Disable the page cache for the user for this and all subsequent requests.
	 */
	public function disable_cache_for_user(): void {
		Vary_Cache::set_nocache_for_user();
	}

	/**
	 * Enable the page cache for the user for this and all subsequent requests.
	 */
	public function enable_cache_for_user(): void {
		Vary_Cache::remove_nocache_for_user();
	}

	/**
	 * Check if the page cache is disabled for the current request.
	 *
	 * @return bool
	 */
	public function is_user_cache_disabled(): bool {
		return Vary_Cache::is_user_in_nocache();
	}

	/**
	 * Check if the page cache is disabled for the current request.
	 *
	 * @return bool
	 */
	public function is_cache_disabled(): bool {
		return Vary_Cache::is_user_in_nocache();
	}

	/**
	 * Register a cache group.
	 *
	 * @param array<int, string> $groups The groups to register.
	 */
	public function register_groups( array $groups ): void {
		Vary_Cache::register_groups( $groups );
	}

	/**
	 * Register a cache group.
	 *
	 * @param string $group The group to register.
	 */
	public function register_group( string $group ): void {
		Vary_Cache::register_group( $group );
	}

	/**
	 * Retrieve the registered cache groups.
	 *
	 * @return array<int, string> The registered groups.
	 */
	public function get_groups(): array {
		return array_keys( Vary_Cache::get_groups() );
	}

	/**
	 * Assign the user to a group and segment within that group.
	 *
	 * @throws InvalidArgumentException If the group or segment is invalid.
	 *
	 * @param string $group The group to assign the user to.
	 * @param string $segment The segment within the group to assign the user to.
	 */
	public function set_group_for_user( string $group, string $segment ): void {
		$response = Vary_Cache::set_group_for_user( $group, $segment );

		if ( is_wp_error( $response ) ) {
			throw new InvalidArgumentException(
				esc_html( $response->get_error_message() )
			);
		}
	}

	/**
	 * Check if the user is in a group and any segment within that group.
	 *
	 * @param string $group The group to check.
	 * @return bool True if the user is in the group, false otherwise.
	 */
	public function is_user_in_group( string $group ): bool {
		return Vary_Cache::is_user_in_group( $group );
	}

	/**
	 * Check if the user is in a group and specific segment within that group.
	 *
	 * @param string $group The group to check.
	 * @param string $segment The segment within the group to check.
	 * @return bool True if the user is in the group and segment, false otherwise.
	 */
	public function is_user_in_group_segment( string $group, string $segment ): bool {
		return Vary_Cache::is_user_in_group_segment( $group, $segment );
	}

	/**
	 * Block a user by IP address.
	 *
	 * @param array<int, string>|string $ip The IP address(es) to block.
	 */
	public function block_ip( array|string $ip ): void {
		if ( is_array( $ip ) ) {
			foreach ( $ip as $single_ip ) {
				static::block_ip( $single_ip );
			}

			return;
		}

		VIP_Request_Block::ip( $ip );
	}

	/**
	 * Block a user by user agent.
	 *
	 * @param array<int, string>|string $user_agent The user agent(s) to block.
	 */
	public function block_user_agent( array|string $user_agent ): void {
		if ( is_array( $user_agent ) ) {
			foreach ( $user_agent as $single_user_agent ) {
				static::block_user_agent( $single_user_agent );
			}

			return;
		}

		VIP_Request_Block::ua( $user_agent );
	}

	/**
	 * Purge a specific URL from the cache.
	 *
	 * @param string $url The URL to purge.
	 * @return boolean|\WP_Error
	 */
	public function purge( string $url ): mixed {
		return wpcom_vip_purge_edge_cache_for_url( $url );
	}

	/**
	 * Purge a specific post from the cache.
	 *
	 * @param WP_Post|int $post The post to purge.
	 */
	public function purge_post( WP_Post|int $post ): void {
		wpcom_vip_purge_edge_cache_for_post( $post );
	}

	/**
	 * Purge a specific term from the cache.
	 *
	 * @param WP_Term|int $term The term to purge.
	 */
	public function purge_term( WP_Term|int $term ): void {
		wpcom_vip_purge_edge_cache_for_term( $term );
	}

	/**
	 * Flush the entire page cache for a site.
	 */
	public function flush(): void {
		WPCOM_VIP_Cache_Manager::instance()->purge_site_cache();
	}

	/**
	 * Send all headers and cookies for a provider on-demand.
	 */
	public function send_headers(): void {
		Vary_Cache::send_headers();
	}
}
