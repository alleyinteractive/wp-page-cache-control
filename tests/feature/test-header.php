<?php
namespace Alley\WP\WP_Page_Cache_Control\Tests\Feature;

use Alley\WP\WP_Page_Cache_Control\Tests\Test_Case;
use Alley\WP\WP_Page_Cache_Control\Header;

class Test_Header extends Test_Case {
	public function test_send_headers() {
		static::assertHeaderNotSent( 'x-example-header' );

		Header::send( 'x-example', 'example' );
		Header::send( 'X-Another-Example', 'another-example' );

		static::assertHeaderSent( 'x-example' );
		static::assertHeaderSent( 'x-example', 'example' );

		static::assertHeaderSent( 'X-ANOTHER-EXAMPLE' );
		static::assertHeaderSent( 'X-Another-Example', 'another-example' );

		static::assertHeaderNotSent( 'x-different-header' );
		static::assertHeaderNotSent( 'x-different-header', 'value' );
	}

	public function test_no_cache() {
		static::assertHeaderNotSent( 'cache-control' );

		Header::no_cache();

		static::assertHeaderSent( 'cache-control' );
		static::assertHeaderSent( 'cache-control', 'no-cache, must-revalidate, max-age=0' );
	}

	public function test_max_age() {
		static::assertHeaderNotSent( 'cache-control' );

		Header::max_age( 60 );

		static::assertHeaderSent( 'cache-control' );
		static::assertHeaderSent( 'cache-control', 'max-age=60' );
	}
}
