<?php
namespace Alley\WP\WP_Page_Cache_Control\Tests\Feature\Providers;

use Alley\WP\WP_Page_Cache_Control\Providers\Testable_Provider;
use Alley\WP\WP_Page_Cache_Control\Tests\Test_Case;

class Test_Testable_Provider extends Test_Case {
	protected Testable_Provider $provider;

	protected function setUp(): void {
		parent::setUp();

		add_filter( 'wp_page_cache_control_provider', fn () => Testable_Provider::class );

		$this->assertInstanceOf( Testable_Provider::class, wp_page_cache_control() );

		$this->provider = wp_page_cache_control();
	}

	public function test_ttl() {
		$this->provider->ttl( 1800 );

		$this->assertTrue( $this->provider->has_ttl( 1800 ) );
		$this->assertFalse( $this->provider->has_ttl( 123 ) );
	}

	public function test_disable_cache() {
		$this->provider->disable_cache();

		$this->assertTrue( $this->provider->is_user_cache_disabled() );
	}

	public function test_disable_cache_for_user() {
		$this->provider->disable_cache_for_user();

		$this->assertTrue( $this->provider->is_user_cache_disabled_for_user() );
	}

	public function test_register_groups() {
		$this->provider->register_group( 'test-group' );
		$this->provider->register_groups( [ 'test-group-1', 'test-group-2' ] );

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

		$this->assertTrue(
			$this->provider->is_purged( home_url( '/example/' ) ),
		);
	}

	public function test_purge_post() {
		$post_id = static::factory()->post->create();

		$this->provider->purge_post( $post_id );

		$this->assertTrue(
			$this->provider->is_purged( get_permalink( $post_id ) ),
		);
	}

	public function test_purge_term() {
		$term_id = static::factory()->term->create();

		$this->provider->purge_term( $term_id );

		$this->assertTrue(
			$this->provider->is_purged( get_term_link( $term_id ) ),
		);
	}

	public function test_purge_site_cache() {
		$this->provider->flush();

		$this->assertTrue( $this->provider->is_site_cache_purged() );
	}
}
