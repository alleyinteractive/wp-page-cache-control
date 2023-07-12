<?php
/**
 * Contains functions for working with assets (primarily JavaScript).
 *
 * phpcs:disable phpcs:ignore Squiz.PHP.CommentedOutCode.Found
 *
 * @package wp-page-cache-control
 */

namespace Alley\WP\WP_Page_Cache_Control;

use Mantle\Support\Str;

// Register and enqueue assets.
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\action_wp_enqueue_scripts' );
add_action( 'wp_head', __NAMESPACE__ . '\action_wp_head' );

/**
 * A callback for the wp_enqueue_scripts hook.
 */
function action_wp_enqueue_scripts(): void {
	/**
	 * Allow the front-end script to be short-circuited.
	 *
	 * @param bool $enqueue_script Whether to enqueue the script. Default true.
	 */
	if ( ! apply_filters( 'wp_page_cache_control_enqueue_script', true ) ) {
		return;
	}

	wp_enqueue_script(
		'wp-page-cache-control',
		get_entry_asset_url( 'global' ),
		get_asset_dependency_array( 'global' ),
		get_asset_version( 'global' ),
		false,
	);

	wp_localize_script(
		'wp-page-cache-control',
		'wpPageCacheControlSettings',
		[
			'provider'         => Str::studly( Str::after_last( wp_page_cache_control()::class, '\\' ) ),
			'registeredGroups' => wp_page_cache_control()->groups(),
		],
	);
}

/**
 * Preload the script as early as possible.
 */
function action_wp_head(): void {
	/**
	 * Allow the script to be short-circuited.
	 *
	 * @param bool $preload_script Whether to preload the script. Default true.
	 */
	if ( ! apply_filters( 'wp_page_cache_control_preload_script', true ) ) {
		return;
	}

	// Bail if the script isn't enqueued.
	if ( ! wp_script_is( 'wp-page-cache-control', 'enqueued' ) ) {
		return;
	}

	printf(
		'<link rel="preload" href="%s" as="script" />',
		esc_url( add_query_arg( 'ver', get_asset_version( 'global' ), get_entry_asset_url( 'global' ) ) ),
	);
}

/**
 * Validate file paths to prevent a PHP error if a file doesn't exist.
 *
 * @param string $path The file path to validate.
 * @return bool        True if the path is valid and the file exists.
 */
function validate_path( string $path ) : bool {
	return 0 === validate_file( $path ) && file_exists( $path );
}

/**
 * Get the entry points directory path or public URL.
 *
 * @param string  $dir_entry_name The directory name where the entry point was defined.
 * @param boolean $dir            Optional. Whether to return the directory path or the plugin URL path. Defaults to false (returns URL).
 *
 * @return string
 */
function get_entry_dir_path( string $dir_entry_name, bool $dir = false ): string {
	// The relative path from the plugin root.
	$asset_build_dir = "/build/{$dir_entry_name}/";
	// Set the absolute file path from the root directory.
	$asset_dir_path = WP_PAGE_CACHE_CONTROL_DIR . $asset_build_dir;

	if ( validate_path( $asset_dir_path ) ) {
		// Negotiate the base path.
		return true === $dir
			? $asset_dir_path
			: plugins_url( $asset_build_dir, __DIR__ );
	}

	return '';
}

/**
 * Get the assets dependencies and version.
 *
 * @param string $dir_entry_name The entry point directory name.
 *
 * @return array{dependencies?: string[], version?: string}
 */
function get_entry_asset_map( string $dir_entry_name ): array {
	$base_path = get_entry_dir_path( $dir_entry_name, true );

	if ( ! empty( $base_path ) ) {
		$asset_file_path = trailingslashit( $base_path ) . 'index.asset.php';

		if ( validate_path( $asset_file_path ) ) {
			return include $asset_file_path; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.IncludingFile, WordPressVIPMinimum.Files.IncludingFile.UsingVariable
		}
	}

	return [];
}

/**
 * Get the dependency array for a given asset.
 *
 * @param string $dir_entry_name The entry point directory name.
 *
 * @return array<int, string> The asset's dependency array.
 */
function get_asset_dependency_array( string $dir_entry_name ) : array {
	return get_entry_asset_map( $dir_entry_name )['dependencies'] ?? [];
}

/**
 * Get the version hash for a given asset.
 *
 * @param string $dir_entry_name The entry point directory name.
 *
 * @return string The asset's version hash.
 */
function get_asset_version( string $dir_entry_name ) : string {
	return get_entry_asset_map( $dir_entry_name )['version'] ?? '1.0';
}

/**
 * Get the public url for the assets entry file.
 *
 * @param string $dir_entry_name The entry point directory name.
 * @param string $filename       The asset file name including the file type extension to get the public path for.
 * @return string                The public URL to the asset, empty string otherwise.
 */
function get_entry_asset_url( string $dir_entry_name, $filename = 'index.js' ) {
	if ( empty( $filename ) ) {
		return '';
	}

	if ( validate_path( trailingslashit( get_entry_dir_path( $dir_entry_name, true ) ) . $filename ) ) {
		$entry_base_url = get_entry_dir_path( $dir_entry_name );

		if ( ! empty( $entry_base_url ) ) {
			return trailingslashit( $entry_base_url ) . $filename;
		}
	}

	return '';
}
