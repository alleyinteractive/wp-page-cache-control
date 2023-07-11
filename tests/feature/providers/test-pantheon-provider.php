<?php
namespace Alley\WP\WP_Page_Cache_Control\Tests\Feature\Providers;

use Alley\WP\WP_Page_Cache_Control\Header;
use Alley\WP\WP_Page_Cache_Control\Providers\Pantheon_Provider;
use Alley\WP\WP_Page_Cache_Control\Tests\Test_Case;

/**
 * Pantheon Advanced Page Cache Provider
 */
class Test_Pantheon_Provider extends Test_Case {
	protected function setUp(): void {
		parent::setUp();

		// TODO: setup CI to run tests with Pantheon Provider.
		if ( ! function_exists( 'pantheon_wp_clear_edge_all' ) ) {
			$this->markTestSkipped( 'Pantheon Provider not loaded' );
		}

		add_filter( 'wp_page_cache_control_provider', fn () => Pantheon_Provider::class );

		$this->assertInstanceOf( Pantheon_Provider::class, wp_page_cache_control() );
	}

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
		wp_page_cache_control()->send_headers();

		Header::assertNoneSent();

		$this->assertNotEmpty( $_COOKIE[ Pantheon_Provider::COOKIE_NO_CACHE ] ?? null );

		wp_page_cache_control()->enable_cache_for_user();
		wp_page_cache_control()->send_headers();

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

	/**
	 * @dataProvider dataprovider_set_cookies_from_groups
	 */
	public function test_set_cookies_from_groups( array $group_segments, array $cookies ) {
		$plugin = wp_page_cache_control();

		if ( ! ( $plugin instanceof Pantheon_Provider ) ) {
			return;
		}

		foreach ( $group_segments as $group => $segment ) {
			$plugin->register_group( $group );
			$plugin->set_group_for_user( $group, $segment );
		}

		$plugin->set_group_cookies();

		$this->assertEquals( $cookies, $plugin->get_cookie_queue() );
	}

	public static function dataprovider_set_cookies_from_groups() {
		return [
			'no groups' => [
				// Group -> segment pairs.
				[],
				// Expected cookies.
				[],
			],
			'one group' => [
				[
					'example' => 'segment',
				],
				[
					Pantheon_Provider::COOKIE_SEGMENT_PREFIX . 'example' => 'segment',
				],
			],
			// The groups should be sorted alphabetically.
			'multiple groups' => [
				[
					'other'   => 'other-segment',
					'example' => 'segment',
				],
				[
					Pantheon_Provider::COOKIE_SEGMENT_PREFIX . 'example' => 'segment',
					Pantheon_Provider::COOKIE_SEGMENT_PREFIX . 'other'   => 'other-segment',
				],
			],
		];
	}

	public function test_dont_set_cookies_if_unchanged() {
		$plugin = wp_page_cache_control();

		if ( ! ( $plugin instanceof Pantheon_Provider ) ) {
			return;
		}

		$plugin->register_group( 'example' );

		$this
			->with_cookie( Pantheon_Provider::COOKIE_SEGMENT_PREFIX . 'example', 'segment' )
			->get( '/' );

		$plugin->set_group_cookies();

		$this->assertEmpty( $plugin->get_cookie_queue() );
	}

	public function test_purge() {
		$this->expectApplied( 'pantheon_wp_clear_edge_paths' )->with( [ home_url( '/example/' ) ] )->once();

		wp_page_cache_control()->purge( home_url( '/example/' ) );
	}

	public function test_purge_post() {
		$this->expectApplied( 'pantheon_purge_post_with_related' );

		wp_page_cache_control()->purge_post( static::factory()->post->create() );
	}

	public function test_purge_term() {
		$this->expectApplied( 'pantheon_purge_term' );

		wp_page_cache_control()->purge_term( static::factory()->term->create() );
	}

	public function test_purge_site_cache() {
		$this->expectApplied( 'pantheon_wp_clear_edge_all' )->once();

		wp_page_cache_control()->flush();
	}
}
