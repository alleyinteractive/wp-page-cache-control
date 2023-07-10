<?php
namespace Alley\WP\WP_Page_Cache_Control\Tests\Feature;

use Alley\WP\WP_Page_Cache_Control\Tests\Test_Case;
use Alley\WP\WP_Page_Cache_Control\Header;

class Test_Header extends Test_Case {
	public function test_send_headers() {
		Header::assertNotSent( 'x-example-header' );

		Header::send( 'x-example', 'example' );
		Header::send( 'X-Another-Example', 'another-example' );

		Header::assertSent( 'x-example' );
		Header::assertSent( 'x-example', 'example' );

		Header::assertSent( 'X-ANOTHER-EXAMPLE' );
		Header::assertSent( 'X-Another-Example', 'another-example' );

		Header::assertNotSent( 'x-different-header' );
		Header::assertNotSent( 'x-different-header', 'value' );
	}

	public function test_no_cache() {
		Header::assertNotSent( 'cache-control' );

		Header::no_cache();

		Header::assertSent( 'cache-control' );
		Header::assertSent( 'cache-control', 'no-cache, must-revalidate, max-age=0' );
	}

	public function test_max_age() {
		Header::assertNotSent( 'cache-control' );

		Header::max_age( 60 );

		Header::assertSent( 'cache-control' );
		Header::assertSent( 'cache-control', 'max-age=60' );
	}
}
