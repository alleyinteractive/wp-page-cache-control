<?php
namespace Alley\WP\WP_Page_Cache_Control\Tests\Feature\Providers;

use Alley\WP\WP_Page_Cache_Control\Header;
use Alley\WP\WP_Page_Cache_Control\Providers\VIP_Provider;
use Alley\WP\WP_Page_Cache_Control\Tests\Test_Case;
use Automattic\VIP\Cache\Vary_Cache;
use WPCOM_VIP_Cache_Manager;

/**
 * WordPress VIP Provider
 */
class Test_VIP_Provider extends Test_Case {
	protected VIP_Provider $provider;

	protected function setUp(): void {
		parent::setUp();

		if ( ! class_exists( \Vary_Cache::class ) ) {
			$this->markTestSkipped( 'VIP Provider not loaded' );
		}

		add_filter( 'wp_page_cache_control_provider', fn () => VIP_Provider::class );

		$this->assertInstanceOf( VIP_Provider::class, wp_page_cache_control() );

		$this->provider = wp_page_cache_control();
	}

	protected function tearDown(): void {
		parent::tearDown();

		Vary_Cache::unload();

		// phpcs:ignore WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___SERVER__HTTP_USER_AGENT__
		unset( $_SERVER['HTTP_TRUE_CLIENT_IP'], $_SERVER['HTTP_X_FORWARDED_FOR'], $_SERVER['HTTP_USER_AGENT'] );
	}

	public function test_ttl() {
		$this->provider->ttl( 1800 );

		static::assertHeaderSent( 'Cache-Control', 'max-age=1800' );
	}

	public function test_disable_cache() {
		$this->provider->disable_cache();

		static::assertHeaderSent( 'Cache-Control', 'no-cache, must-revalidate, max-age=0' );

		$this->assertFalse( Vary_Cache::is_user_in_nocache() );
	}

	public function test_disable_cache_for_user() {
		$this->provider->disable_cache_for_user();

		static::assertNoHeadersSent();

		$this->assertTrue( Vary_Cache::is_user_in_nocache() );

		$this->provider->enable_cache_for_user();

		$this->assertFalse( Vary_Cache::is_user_in_nocache() );
	}

	public function test_register_groups() {
		$this->provider->register_group( 'test-group' );
		$this->provider->register_groups( [ 'test-group-1', 'test-group-2' ] );

		$this->assertEquals(
			[ 'test-group', 'test-group-1', 'test-group-2' ],
			array_keys( Vary_Cache::get_groups() ),
		);

		$this->assertEquals(
			[ 'test-group', 'test-group-1', 'test-group-2' ],
			$this->provider->get_groups(),
		);
	}

	public function test_user_groups_and_segments() {
		$this->provider->register_group( 'test-group' );
		$this->provider->set_group_for_user( 'test-group', 'segment' );

		$this->assertTrue( $this->provider->is_user_in_group( 'test-group' ) );
		$this->assertTrue( $this->provider->is_user_in_group_segment( 'test-group', 'segment' ) );
		$this->assertFalse( $this->provider->is_user_in_group_segment( 'test-group', 'other-segment' ) );
	}

	public function test_purge() {
		$this->provider->purge( home_url( '/example/' ) );

		$this->assertContains(
			home_url( '/example/' ),
			WPCOM_VIP_Cache_Manager::instance()->get_queued_purge_urls(),
		);
	}

	public function test_purge_post() {
		$post_id = static::factory()->post->create();

		$this->provider->purge_post( $post_id );

		$this->assertContains(
			get_permalink( $post_id ),
			WPCOM_VIP_Cache_Manager::instance()->get_queued_purge_urls(),
		);
	}

	public function test_purge_term() {
		$term_id = static::factory()->term->create();

		$this->provider->purge_term( $term_id );

		$this->assertContains(
			get_term_link( $term_id ),
			WPCOM_VIP_Cache_Manager::instance()->get_queued_purge_urls(),
		);
	}

	public function test_purge_site_cache() {
		$this->provider->flush();

		$post_id = static::factory()->post->create();

		$this->provider->purge_post( $post_id );

		// It shouldn't have the post URL in the queue because the site cache
		// was flushed. This is a workaround since the flag is a private
		// property.
		$this->assertNotContains(
			get_permalink( $post_id ),
			WPCOM_VIP_Cache_Manager::instance()->get_queued_purge_urls(),
		);
	}
}
