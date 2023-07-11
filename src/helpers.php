<?php
/**
 * WP Page Cache Control Helpers
 *
 * @package wp-page-cache-control
 */

namespace Alley\WP\WP_Page_Cache_Control;

/**
 * Detect the default provider class.
 *
 * @return class-string<\Alley\WP\WP_Page_Cache_Control\Providers\Provider>
 */
function detect_provider(): string {
	// Use the test provider by default if we're in testing mode.
	if ( defined( 'WP_TESTS_DOMAIN' ) ) {
		return Providers\Testable_Provider::class;
	}

	if (
		( defined( 'WPCOM_IS_VIP_ENV' ) && WPCOM_IS_VIP_ENV ) || class_exists( \VIP_Request_Block::class )
	) {
		return Providers\VIP_Provider::class;
	}

	if (
		( defined( 'PANTHEON_ENVIRONMENT' ) && PANTHEON_ENVIRONMENT )
		|| function_exists( 'pantheon_wp_clear_edge_paths' )
	) {
		return Providers\Pantheon_Provider::class;
	}

	return Providers\Testable_Provider::class;
}
