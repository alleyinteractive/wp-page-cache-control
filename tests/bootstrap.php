<?php
/**
 * wp-page-cache-control Test Bootstrap
 */

/**
 * Visit {@see https://mantle.alley.co/testing/test-framework.html} to learn more.
 */
\Mantle\Testing\manager()
	->maybe_rsync_plugin()
	->with_vip_mu_plugins()
	->with_sqlite()
	// Load Pantheon's Advanced Page Cache.
	// TODO: set this up with CI.
	->loaded( fn () => require_once WP_CONTENT_DIR . '/plugins/pantheon-advanced-page-cache/pantheon-advanced-page-cache.php' )
	// Load the main file of the plugin.
	->loaded( fn () => require_once __DIR__ . '/../wp-page-cache-control.php' )
	->install();
