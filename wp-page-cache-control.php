<?php
/**
 * Plugin Name: WP Page Cache Control
 * Plugin URI: https://github.com/alleyinteractive/wp-page-cache-control
 * Description: Control and modify the page cache for multiple hosting providers.
 * Version: 0.1.2
 * Author: Sean Fisher
 * Author URI: https://github.com/alleyinteractive/wp-page-cache-control
 * Requires at least: 5.9
 * Tested up to: 6.2
 *
 * Text Domain: wp-page-cache-control
 * Domain Path: /languages/
 *
 * @package wp-page-cache-control
 */

use Alley\WP\WP_Page_Cache_Control\Header;
use Alley\WP\WP_Page_Cache_Control\Providers\Provider;

use function Alley\WP\WP_Page_Cache_Control\detect_provider;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Root directory to this plugin.
 */
define( 'WP_PAGE_CACHE_CONTROL_DIR', __DIR__ );

// Check if Composer is installed (remove if Composer is not required for your plugin).
if ( ! file_exists( __DIR__ . '/vendor/wordpress-autoload.php' ) ) {
	// Check if we can resolve a Composer dependency before loading the plugin.
	if ( ! class_exists( \Mantle\Support\Str::class ) ) {
		\add_action(
			'admin_notices',
			function () {
				?>
				<div class="notice notice-error">
					<p><?php esc_html_e( 'Composer is not installed and wp-page-cache-control cannot load. Try using a `*-built` branch if the plugin is being loaded as a submodule.', 'wp-page-cache-control' ); ?></p>
				</div>
				<?php
			}
		);

		return;
	}
} else {
	// Load Composer dependencies.
	require_once __DIR__ . '/vendor/wordpress-autoload.php';
}

// Load the plugin's main files.
require_once __DIR__ . '/src/assets.php';

/**
 * The cache provider instance.
 *
 * @var Provider|null
 */
static $wp_page_cache_control_provider = null;

/**
 * Retrieve the cache provider instance.
 *
 * @throws InvalidArgumentException If the provider class does not exist.
 *
 * @return Provider
 */
function wp_page_cache_control(): Provider {
	global $wp_page_cache_control_provider;

	if ( ! isset( $wp_page_cache_control_provider ) ) {
		/**
		 * Filter the cache provider class.
		 *
		 * @param string $wp_page_cache_control_provider The cache provider class.
		 */
		$wp_page_cache_control_provider = apply_filters( 'wp_page_cache_control_provider', detect_provider() );

		if ( empty( $wp_page_cache_control_provider ) || ! class_exists( $wp_page_cache_control_provider ) ) {
			throw new InvalidArgumentException(
				esc_html( "Invalid provider class provided. Expected class to exist: {$wp_page_cache_control_provider}" )
			);
		}

		$wp_page_cache_control_provider = new $wp_page_cache_control_provider();
	}

	return $wp_page_cache_control_provider;
}
add_action( 'muplugins_loaded', __NAMESPACE__ . '\\wp_page_cache_control' ); // @phpstan-ignore-line should not return anything

/**
 * Setup the header handler to send the headers on the 'send_headers' action.
 * Also, setup the cache provider to fire their headers as well. Runs late to
 * catch any changes that may happen earlier in 'send_headers'.
 */
function wp_page_cache_control_send_headers(): void {
	wp_page_cache_control()->send_headers();

	Header::send_headers();
}
add_action( 'send_headers', __NAMESPACE__ . '\\wp_page_cache_control_send_headers', PHP_INT_MAX );
