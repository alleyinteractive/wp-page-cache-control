<?php
namespace Alley\WP\WP_Page_Cache_Control\Tests;

use Mantle\Testkit\Test_Case as TestkitTest_Case;
use Alley\WP\WP_Page_Cache_Control\Header;

/**
 * WP Page Cache Control Base Test Case
 */
abstract class Test_Case extends TestkitTest_Case {
	protected function setUp(): void {
		parent::setUp();

		Header::fake();
	}
}
