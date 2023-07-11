<?php
namespace Alley\WP\WP_Page_Cache_Control\Tests\Feature\Providers;

use Alley\WP\WP_Page_Cache_Control\Header;
use Alley\WP\WP_Page_Cache_Control\Providers\Pantheon_Provider;
use Alley\WP\WP_Page_Cache_Control\Providers\VIP_Provider;
use Alley\WP\WP_Page_Cache_Control\Tests\Test_Case;
use Automattic\VIP\Cache\Vary_Cache;
use WPCOM_VIP_Cache_Manager;

/**
 * Pantheon Advanced Page Cache Provider
 */
class Test_Pantheon_Provider extends Test_Case {
	protected function setUp(): void {
		parent::setUp();

		add_filter( 'wp_page_cache_control_provider', fn () => Pantheon_Provider::class );

		$this->assertInstanceOf( Pantheon_Provider::class, wp_page_cache_control() );
	}

	// protected function tearDown(): void {
	// 	parent::tearDown();

	// 	Vary_Cache::unload();

	// 	// phpcs:ignore WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___SERVER__HTTP_USER_AGENT__
	// 	unset( $_SERVER['HTTP_TRUE_CLIENT_IP'], $_SERVER['HTTP_X_FORWARDED_FOR'], $_SERVER['HTTP_USER_AGENT'] );
	// }

	public function test_ttl() {
		wp_page_cache_control()->ttl( 1800 );

		Header::assertSent( 'Cache-Control', 'max-age=1800' );
	}

	public function test_disable_cache() {
		wp_page_cache_control()->disable_cache();

		Header::assertSent( 'Cache-Control', 'no-cache, must-revalidate, max-age=0' );

		$this->assertEmpty( $_COOKIE[ Pantheon_Provider::COOKIE_NO_CACHE ] ?? null );
	}

	public function test_disable_cache_for_user() {
		wp_page_cache_control()->disable_cache_for_user();

		Header::assertNoneSent();

		$this->assertNotEmpty( $_COOKIE[ Pantheon_Provider::COOKIE_NO_CACHE ] ?? null );

		wp_page_cache_control()->enable_cache_for_user();

		$this->assertEmpty( $_COOKIE[ Pantheon_Provider::COOKIE_NO_CACHE ] ?? null );
	}

	public function test_register_groups() {
		wp_page_cache_control()->register_group( 'test-group' );
		wp_page_cache_control()->register_groups( [ 'test-group-1', 'test-group-2' ] );

		$this->assertEquals(
			[ 'test-group', 'test-group-1', 'test-group-2' ],
			wp_page_cache_control()->groups(),
		);
	}

	/**
	 * @dataProvider invalid_groups
	 */
	public function test_register_invalid_group( $group ) {
		$this->expectException( \InvalidArgumentException::class );

		wp_page_cache_control()->register_group( $group );
	}

	public static function invalid_groups() {
		return [
			'group with space'             => [ 'test group' ],
			'group with invalid character' => [ '@nother-example' ],
			'empty group name'             => [ '' ],
		];
	}

	public function test_user_groups_and_segments() {
		wp_page_cache_control()->register_group( 'test-group' );
		wp_page_cache_control()->set_group_for_user( 'test-group', 'segment' );

		$this->assertTrue( wp_page_cache_control()->is_user_in_group( 'test-group' ) );
		$this->assertTrue( wp_page_cache_control()->is_user_in_group_segment( 'test-group', 'segment' ) );
		$this->assertFalse( wp_page_cache_control()->is_user_in_group_segment( 'test-group', 'other-segment' ) );

		// Test setting cookie from value.
	}

	/**
	 * @dataProvider dataprovider_read_from_cookies
	 */
	public function test_read_from_cookie_and_assign_groups( array $cookies, array $expected_groups ) {
		$plugin = wp_page_cache_control();

		if ( ! ( $plugin instanceof Pantheon_Provider ) ) {
			return;
		}

		foreach ( $expected_groups as $group => $segment ) {
			$plugin->register_group( $group );
		}

		$this
			->flush_cookies()
			->with_cookies( $cookies )
			->get( '/' );

		$plugin->read_cookies();

		foreach ( $expected_groups as $group => $segment ) {
			$this->assertTrue( $plugin->is_user_in_group( $group ), "User should be in group $group" );
			$this->assertTrue( $plugin->is_user_in_group_segment( $group, $segment ), "User should be in group $group with segment $segment" );
		}
	}

	public static function dataprovider_read_from_cookies() {
		return [
			'no groups' => [
				[], // Cookies.
				[], // Expected groups.
			],
			'one group' => [
				[
					Pantheon_Provider::COOKIE_SEGMENT_PREFIX . 'example' => 'segment',
				],
				[
					'example' => 'segment',
				],
			],
			'multiple groups' => [
				[
					Pantheon_Provider::COOKIE_SEGMENT_PREFIX . 'example' => 'segment',
					Pantheon_Provider::COOKIE_SEGMENT_PREFIX . 'other'   => 'another',
				],
				[
					'example' => 'segment',
					'other'   => 'another',
				],
			],
			'one valid one invalid' => [
				[
					Pantheon_Provider::COOKIE_SEGMENT_PREFIX . 'example' => 'segment',
					'invalid'                                             =>'segment',
				],
				[
					'example' => 'segment',
				],
			],
			'ignore unregistered group' => [
				[
					Pantheon_Provider::COOKIE_SEGMENT_PREFIX . 'unregistered' => 'segment',
					Pantheon_Provider::COOKIE_SEGMENT_PREFIX . 'other'   => 'another',
				],
				[
					'other' => 'another',
				],
			],
		];
	}

	// public function test_purge() {
	// 	wp_page_cache_control()->purge( home_url( '/example/' ) );

	// 	$this->assertContains(
	// 		home_url( '/example/' ),
	// 		WPCOM_VIP_Cache_Manager::instance()->get_queued_purge_urls(),
	// 	);
	// }

	// public function test_purge_post() {
	// 	$post_id = static::factory()->post->create();

	// 	wp_page_cache_control()->purge_post( $post_id );

	// 	$this->assertContains(
	// 		get_permalink( $post_id ),
	// 		WPCOM_VIP_Cache_Manager::instance()->get_queued_purge_urls(),
	// 	);
	// }

	// public function test_purge_term() {
	// 	$term_id = static::factory()->term->create();

	// 	wp_page_cache_control()->purge_term( $term_id );

	// 	$this->assertContains(
	// 		get_term_link( $term_id ),
	// 		WPCOM_VIP_Cache_Manager::instance()->get_queued_purge_urls(),
	// 	);
	// }

	// public function test_purge_site_cache() {
	// 	wp_page_cache_control()->flush();

	// 	$post_id = static::factory()->post->create();

	// 	wp_page_cache_control()->purge_post( $post_id );

	// 	// It shouldn't have the post URL in the queue because the site cache
	// 	// was flushed. This is a workaround since the flag is a private
	// 	// property.
	// 	$this->assertNotContains(
	// 		get_permalink( $post_id ),
	// 		WPCOM_VIP_Cache_Manager::instance()->get_queued_purge_urls(),
	// 	);
	// }
}
