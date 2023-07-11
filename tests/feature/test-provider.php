<?php
namespace Alley\WP\WP_Page_Cache_Control\Tests\Feature;

use Alley\WP\WP_Page_Cache_Control\Providers\Testable_Provider;
use Alley\WP\WP_Page_Cache_Control\Tests\Test_Case;

class Test_Provider extends Test_Case {
	protected Testable_Provider $provider;

	protected function setUp(): void {
		parent::setUp();

		add_filter( 'wp_page_cache_control_provider', fn () => Testable_Provider::class );

		$this->assertInstanceOf( Testable_Provider::class, wp_page_cache_control() );

		$this->provider = wp_page_cache_control();
	}

	public function test_ttl() {
		wp_page_cache_control()->ttl( 1800 );

		$this->assertTrue( $this->provider->has_ttl( 1800 ) );
		$this->assertFalse( $this->provider->has_ttl( 123 ) );
	}

	public function test_disable_cache() {
		wp_page_cache_control()->disable_cache();

		$this->assertTrue( $this->provider->is_cache_disabled() );
	}

	public function test_disable_cache_for_user() {
		wp_page_cache_control()->disable_cache_for_user();

		$this->assertTrue( $this->provider->is_cache_disabled_for_user() );
	}

	public function test_register_groups() {
		wp_page_cache_control()->register_group( 'test-group' );
		wp_page_cache_control()->register_groups( [ 'test-group-1', 'test-group-2' ] );

		$this->assertEquals(
			[ 'test-group', 'test-group-1', 'test-group-2' ],
			wp_page_cache_control()->groups(),
		);
	}

	public function test_user_groups_and_segments() {
		wp_page_cache_control()->register_group( 'test-group' );
		wp_page_cache_control()->set_group_for_user( 'test-group', 'segment' );

		$this->assertTrue( wp_page_cache_control()->is_user_in_group( 'test-group' ) );
		$this->assertTrue( wp_page_cache_control()->is_user_in_group_segment( 'test-group', 'segment' ) );
		$this->assertFalse( wp_page_cache_control()->is_user_in_group_segment( 'test-group', 'other-segment' ) );
	}

	public function test_purge() {
		wp_page_cache_control()->purge( home_url( '/example/' ) );

		$this->assertTrue(
			$this->provider->is_purged( home_url( '/example/' ) ),
		);
	}

	public function test_purge_post() {
		$post_id = static::factory()->post->create();

		wp_page_cache_control()->purge_post( $post_id );

		$this->assertTrue(
			$this->provider->is_purged( get_permalink( $post_id ) ),
		);
	}

	public function test_purge_term() {
		$term_id = static::factory()->term->create();

		wp_page_cache_control()->purge_term( $term_id );

		$this->assertTrue(
			$this->provider->is_purged( get_term_link( $term_id ) ),
		);
	}

	public function test_purge_site_cache() {
		wp_page_cache_control()->flush();

		$this->assertTrue( $this->provider->is_site_cache_purged() );
	}
}
