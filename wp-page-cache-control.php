<?php
/**
 * Plugin Name: WP Page Cache Control
 * Plugin URI: https://github.com/alleyinteractive/wp-page-cache-control
 * Description: Control and modify the page cache for multiple hosting providers.
 * Version: 0.1.0
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
if ( ! file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	// Will also check for the presence of an already loaded Composer autoloader
	// to see if the Composer dependencies have been installed in a parent
	// folder. This is useful for when the plugin is loaded as a Composer
	// dependency in a larger project.
	if ( ! class_exists( \Composer\InstalledVersions::class ) ) {
		\add_action(
			'admin_notices',
			function() {
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
	require_once __DIR__ . '/vendor/autoload.php';
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
		$wp_page_cache_control_provider = apply_filters( 'wp_page_cache_control_provider', detect_provider() );

		if ( ! class_exists( $wp_page_cache_control_provider ) ) {
			throw new InvalidArgumentException(
				"Invalid provider class provided. Expected class to exist: {$wp_page_cache_control_provider}",
			);
		}

		$wp_page_cache_control_provider = new $wp_page_cache_control_provider();
	}

	return $wp_page_cache_control_provider;
}
add_action( 'muplugins_loaded', __NAMESPACE__ . '\\wp_page_cache_control' ); // @phpstan-ignore-line should not return anything
