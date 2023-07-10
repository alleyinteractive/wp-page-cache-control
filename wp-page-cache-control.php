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

use WP_Page_Cache_Control\Providers\Provider;

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
 * Retrieve the cache provider.
 */
function wp_page_cache_control(): Provider {
	if ( ! isset( Provider::$instance ) ) {
		$provider = apply_filters( 'wp_page_cache_control_provider', detect_provider() );

		if ( ! class_exists( $provider ) ) {
			throw new InvalidArgumentException(
				"Invalid provider class provided. Expected class to exist: {$provider}",
			);
		}

		dd($provider);
	}
}
add_action( 'muplugins_loaded', __NAMESPACE__ . '\\wp_page_cache_control' );


/**
 * Detect the default provider class.
 *
 * @return class-string<\WP_Page_Cache_Control\Providers\Provider>
 */
function detect_provider(): string {
	if ( defined( 'WPCOM_IS_VIP_ENV' ) && WPCOM_IS_VIP_ENV ) {
		// return \WP_Page_Cache_Control\Providers\WPCOM::class;
	}

	return \WP_Page_Cache_Control\Providers\Testable_Provider::class;
}
