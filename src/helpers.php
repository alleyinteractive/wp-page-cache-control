<?php
/**
 * WP Page Cache Control Helpers
 *
 * @package wp-page-cache-control
 */

namespace Alley\WP\WP_Page_Cache_Control;

use Automattic\VIP\Cache\Vary_Cache;

/**
 * Detect the default provider class.
 *
 * @return class-string<\WP_Page_Cache_Control\Providers\Provider>
 */
function detect_provider(): string {
	if (
		( defined( 'WPCOM_IS_VIP_ENV' ) && WPCOM_IS_VIP_ENV )
		|| class_exists( \VIP_Request_Block::class )
	) {
		return Providers\VIP_Provider::class;
	}

	return \WP_Page_Cache_Control\Providers\Testable_Provider::class;
}
