<?php
/**
 * Pantheon_Provider class file
 *
 * @package wp-page-cache-control
 */

namespace Alley\WP\WP_Page_Cache_Control\Providers;

use Alley\WP\WP_Page_Cache_Control\Header;
use InvalidArgumentException;
use Mantle\Support\Str;
use WP_Post;
use WP_Term;

use function Mantle\Support\Helpers\collect;

/**
 * Pantheon Advanced Page Cache Provider
 *
 * @link https://docs.pantheon.io/cookies#cache-varying-cookies
 */
class Pantheon_Provider implements Provider {
	use Concerns\Interacts_With_IP_Addresses,
		Concerns\Manages_Cookies;

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
	 * Determine if the user is not being cached (bypassing the page cache with
	 * a cookie).
	 *
	 * @var bool
	 */
	protected bool $is_user_no_cache = false;

	/**
	 * Storage of cache groups.
	 *
	 * @var array<string, string>
	 */
	protected array $groups = [];

	/**
	 * Flag if we should update the group cookies.
	 *
	 * @var bool
	 */
	protected bool $should_update_group_cookies = false;

	/**
	 * Constructor.
	 *
	 * @throws InvalidArgumentException If the Pantheon Advanced Page Cache plugin is not loaded.
	 */
	public function __construct() {
		if ( ! function_exists( 'pantheon_wp_clear_edge_keys' ) ) {
			throw new InvalidArgumentException(
				'Pantheon Advanced Page Cache is not installed <https://github.com/pantheon-systems/pantheon-advanced-page-cache>'
			);
		}

		add_action( 'init', [ $this, 'read_cookies' ] );
		add_action( 'send_headers', [ $this, 'send_headers' ] );
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
		$this->set_cookie( self::COOKIE_NO_CACHE, '1' );
	}

	/**
	 * Enable the page cache for the user for this and all subsequent requests.
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
	public function get_groups(): array {
		return array_keys( $this->groups );
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
		if ( ! isset( $this->groups[ $group ] ) ) {
			throw new InvalidArgumentException(
				"Unknown cache group: {$group}"
			);
		}

		$this->should_update_group_cookies = true;

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
	 */
	public function block_ip( array|string $ip ): void {
		if ( is_array( $ip ) ) {
			foreach ( $ip as $single_ip ) {
				static::block_ip( $single_ip );
			}

			return;
		}

		if ( $criteria = $this->is_current_ip( $ip ) ) { // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.Found, Squiz.PHP.DisallowMultipleAssignments.FoundInControlStructure
			$this->block_and_log( $ip, $criteria );
		}
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

		if ( isset( $_SERVER['HTTP_USER_AGENT'] ) && $user_agent === $_SERVER['HTTP_USER_AGENT'] ) { // phpcs:ignore WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___SERVER__HTTP_USER_AGENT__
			$this->block_and_log( $user_agent, 'user-agent' );
		}
	}

	/**
	 * Purge a specific URL from the cache.
	 *
	 * @param string $url The URL to purge.
	 */
	public function purge( string $url ): void {
		pantheon_wp_clear_edge_paths( [ $url ] );
	}

	/**
	 * Purge a specific post from the cache.
	 *
	 * @todo Work with Pantheon to move this into the Pantheon Advanced Page Cache plugin.
	 *
	 * @param WP_Post|int $post The post to purge.
	 */
	public function purge_post( WP_Post|int $post ): void {
		$post = get_post( $post );

		if ( ! ( $post instanceof WP_Post ) ) {
			return;
		}

		// Ignore revisions, which aren't ever displayed on the site.
		if ( 'revision' === $post->post_type ) {
			return;
		}

		$keys = [
			'home',
			'front',
			$post->post_type . '-archive',
			'404',
			'feed',
			'post-' . $post->ID,
			'post-huge',
		];

		$keys[] = 'rest-' . $post->post_type . '-collection';

		if ( post_type_supports( $post->post_type, 'author' ) ) {
			$keys[] = 'user-' . $post->post_author;
			$keys[] = 'user-huge';
		}

		if ( post_type_supports( $post->post_type, 'comments' ) ) {
			$keys[] = 'rest-comment-post-' . $post->ID;
			$keys[] = 'rest-comment-post-huge';
		}

		$taxonomies = wp_list_filter(
			get_object_taxonomies( $post->post_type, 'objects' ),
			[ 'public' => true ]
		);

		foreach ( $taxonomies as $taxonomy ) {
			$terms = get_the_terms( $post, $taxonomy->name );
			if ( is_array( $terms ) ) {
				foreach ( $terms as $term ) {
					$keys[] = 'term-' . $term->term_id;
				}
				$keys[] = 'term-huge';
			}
		}

		$keys = pantheon_wp_prefix_surrogate_keys_with_blog_id( $keys );

		/**
		 * Related surrogate keys purged when purging a post.
		 *
		 * @param array   $keys Surrogate keys.
		 * @param WP_Post $post Post object.
		 */
		$keys = apply_filters( 'pantheon_purge_post_with_related', $keys, $post ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

		pantheon_wp_clear_edge_keys( $keys );
	}

	/**
	 * Purge a specific term from the cache.
	 *
	 * @todo Work with Pantheon to move this into the Pantheon Advanced Page Cache plugin.
	 *
	 * @param WP_Term|int $term The term to purge.
	 */
	public function purge_term( WP_Term|int $term ): void {
		$term = get_term( $term );

		if ( ! ( $term instanceof WP_Term ) ) {
			return;
		}

		// Mirror the logic in Pantheon's Purger::purge_term() method.
		$keys = [
			'term-' . $term->term_id,
			'rest-term-' . $term->term_id,
			'post-term-' . $term->term_id,
			'term-huge',
			'rest-term-huge',
			'post-term-huge',
		];

		$keys = pantheon_wp_prefix_surrogate_keys_with_blog_id( $keys );

		/**
		 * Surrogate keys purged when purging a term.
		 *
		 * @param array   $keys    Surrogate keys.
		 * @param integer $term_id Term ID.
		 */
		$keys = apply_filters( 'pantheon_purge_term', $keys, $term->term_id ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

		pantheon_wp_clear_edge_keys( $keys );
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
	 * @param string $value The group name to check.
	 * @return bool True if the group name is valid, false otherwise.
	 */
	public function is_valid_group_name( string $value ): bool {
		return ! empty( $value ) && 1 === preg_match( '/^[a-zA-Z0-9_-]+$/', $value );
	}

	/**
	 * Read the cookies from the request and assign the user to groups.
	 */
	public function read_cookies(): void {
		if ( $this->get_cookie( static::COOKIE_NO_CACHE ) ) {
			$this->is_user_no_cache = true;
		}

		if ( empty( $_COOKIE ) ) {
			return;
		}

		collect( $_COOKIE ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___COOKIE
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
	 * Send the cookies for the groups and segments.
	 */
	public function set_group_cookies(): void {
		if ( ! $this->should_update_group_cookies ) {
			return;
		}

		collect( $this->groups )
			->map_with_keys(
				fn ( $segment, string $group ) => [
					self::COOKIE_SEGMENT_PREFIX . $group => $segment,
				]
			)
			->sort_keys()
			->each(
				fn ( string $value, string $name ) => $this->set_cookie( $name, $value ),
			);

		$this->should_update_group_cookies = false;
	}

	/**
	 * Send the headers and cookies.
	 */
	public function send_headers(): void {
		$this->set_group_cookies();
		$this->send_cookies();
	}

	/**
	 * Log a request and block it.
	 *
	 * @param string $value The value that triggered the block.
	 * @param string $criteria The criteria that triggered the block.
	 */
	protected function block_and_log( string $value, string $criteria ): void {
		if ( defined( 'WP_CLI' ) && constant( 'WP_CLI' ) ) {
			return;
		}

		if ( extension_loaded( 'newrelic' ) && function_exists( 'newrelic_ignore_transaction' ) ) {
			newrelic_ignore_transaction();
		}

		if ( ! defined( 'WP_TESTS_DOMAIN' ) ) {
			http_response_code( 403 );
			header( 'Expires: Wed, 11 Jan 1984 05:00:00 GMT' );
			header( 'Cache-Control: no-cache, must-revalidate, max-age=0' );

			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( sprintf( 'WP Page Cache Control Block: request was blocked based on "%s" with value of "%s"', $criteria, $value ) );
			exit;
		}
	}
}
