<?php
namespace Alley\WP\WP_Page_Cache_Control\Tests;

use Alley\WP\WP_Page_Cache_Control\Concerns\Tests_Headers;
use Mantle\Testkit\TestCase as TestkitTestCase;
use Alley\WP\WP_Page_Cache_Control\Header;

/**
 * WP Page Cache Control Base Test Case
 */
abstract class Test_Case extends TestkitTestCase {
	use Tests_Headers;

	protected function setUp(): void {
		parent::setUp();

		$GLOBALS['wp_page_cache_control_provider'] = null;

		Header::fake();
	}

	protected function tearDown(): void {
		parent::tearDown();

		$GLOBALS['wp_page_cache_control_provider'] = null;

		Header::flush();
	}
}
