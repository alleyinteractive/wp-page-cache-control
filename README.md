# WP Page Cache Control

[![Coding Standards](https://github.com/alleyinteractive/wp-page-cache-control/actions/workflows/coding-standards.yml/badge.svg)](https://github.com/alleyinteractive/wp-page-cache-control/actions/workflows/coding-standards.yml)
[![Testing Suite](https://github.com/alleyinteractive/wp-page-cache-control/actions/workflows/unit-test.yml/badge.svg)](https://github.com/alleyinteractive/wp-page-cache-control/actions/workflows/unit-test.yml)

Control and modify the page cache for multiple hosting providers.

## Installation

You can install the package via Composer:

```bash
composer require alleyinteractive/wp-page-cache-control
```

The plugin supports the following hosting providers and their respective page
caching systems:

- [Pantheon](https://pantheon.io/) via [their `Pantheon Advanced Cache`
  plugin](https://github.com/pantheon-systems/pantheon-advanced-page-cache):
  (`Alley\WP\WP_Page_Cache_Control\Providers\Pantheon_Provider`)
- [WordPress VIP](https://vip.wordpress.com/) via [their `mu-plugins` repository](https://github.com/automattic/vip-go-mu-plugins/): (`Alley\WP\WP_Page_Cache_Control\Providers\VIP_Provider`)

The plugin will attempt to detect the caching system in use and will load the
appropriate provider class. It can also be controlled by the
`wp_page_cache_control_provider` hook which should return a provider class
string.

The main goal of the plugin is to solve common page cache control needs across
multiple hosting providers. It is not meant to be a complete solution for all
page cache control needs. If you have a need that is not met by the plugin,
please open an issue or pull request.

## Usage

The plugin supports back-end page cache control including TTL, bypassing the
page cache, user segmentation, and purging from the page cache. It also
supports front-end segmentation.

## Usage: Back-end

Activate the plugin in WordPress and use the following methods as needed:

### Controlling the Time-to-live (TTL) of the Current Request

```php
wp_page_cache_control()->ttl( 3600 );
```

### Disabling the Page Cache for the Current Request

```php
wp_page_cache_control()->disable_cache();
```
### Disabling the Page Cache for the Current User

Disabling the page cache for the current user will cause the user to bypass the
page cache for the current and subsequent requests. This is useful for testing
or for logged-in users.

```php
wp_page_cache_control()->disable_cache_for_user();

// enabling it again via:
wp_page_cache_control()->enable_cache_for_user();
```

### Segmenting the Page Cache

See [Page Cache Segmentation](#page-cache-segmentation) for more information.

```php
wp_page_cache_control()->register_group( 'special-user-group' );

// Add the current user to the group (only needs to be done once).
wp_page_cache_control()->set_group_for_user( 'special-user-group', 'segment' );
```

### Purging a Specific URL

```php
wp_page_cache_control()->purge( home_url( '/example/' );
```

### Purging for a Post or Term

```php
wp_page_cache_control()->purge_post( $post_id );

wp_page_cache_control()->purge_term( $term_id );
```

### Purging the Entire Page Cache

**Warning:** This will purge the entire page cache. This is a dangerous operation and should be used with caution.

```php
wp_page_cache_control()->flush();
```

## Page Cache Segmentation

Page Cache Segmentation is used when you want to vary or differ the page
response to different users. For example, you may want to show a different
version of a page to logged-in users than to logged-out users. Or you may want
to hide ads for users from a specific country. Segmenting the page cache allows
you to do this in a performant way.

### Registering a Group

To register a group, use the `register_group()` method:

```php
wp_page_cache_control()->register_group( 'special-user-group' );
```

Group names must be unique and must contain alphanumeric characters, dashes, and
underscores only.

### Adding a User to a Group

To add a user to a group, use the `set_group_for_user()` method:

```php
wp_page_cache_control()->set_group_for_user( 'special-user-group', 'segment' );
```

The second parameter allows you to specify a segment within a group. For
example, the group could be "logged-in" and the segment could be "digital
subscriber". You could also have a different user in the "logged-in" group with
the segment "print subscriber" to show a different version of the page to print
subscribers.

**Note:** A user cannot be removed from a group once added at this time. If you need
to remove a user from a group, you can add them to a different segment of the
same group.

### Checking if a User is in a Group or Segment

To check if a user is in a group or segment, use the `is_user_in_group()` method:

```php
wp_page_cache_control()->is_user_in_group( 'special-user-group' );

wp_page_cache_control()->is_user_in_group( 'special-user-group', 'segment' );
```

### Testing Headers

The plugin supports faking the sending of headers sent through the plugin for testing purposes. To enable this, call the following code:

```php
use Alley\WP\WP_Page_Cache_Control\Header;

Header::fake();
```

Once enabled, you can use the following methods to test headers being sent with
the `Alley\WP\WP_Page_Cache_Control\Concerns\Tests_Headers` trait:

```php
namespace Alley\WP\My_Plugin\Tests;

use Alley\WP\WP_Page_Cache_Control\Concerns\Tests_Headers;
use Alley\WP\WP_Page_Cache_Control\Header;
use Mantle\Testkit\Test_Case;

class Example_Test extends Test_Case {
	use Tests_Headers;

	protected function setUp(): void {
		parent::setUp();

		Header::fake();
	}

	public function test_example() {
		// Perform some action that should send a header.

		static::assertHeaderSent( 'X-My-Header', 'optional value' );
		static::assertHeaderNotSent( 'X-My-Other-Header', 'optional value' );

		// static::assertAnyHeadersSent() and static::assertNoHeadersSent()
		// are also available to assert that any headers were sent or not sent.
	}
}
```

## Usage: Front-end

The package has a front-end integration to allow for segmenting the page cache
in-browser. This is enabled by default but can be disabled by using the
`wp_page_cache_control_enqueue_script` filter.

Cache segmentation groups must be registered on the back end before they can be
used on the front end. See [Registering a Group](#registering-a-group) for more
information.

```js
wpPageCacheControl.setGroupForUser('logged-in-group', 'segment-name');

// To remove a user from a group, you have to set them to a different segment.
wpPageCacheControl.setGroupForUser('logged-in-group', 'different-name');

// To check if a user is in a group or segment, use the following:
wpPageCacheControl.isUserInGroup('logged-in-group');
wpPageCacheControl.isUserInGroupSegment('logged-in-group', 'segment-name');

// You can read the groups and segments from the page cache control object:\
//
//   wpPageCacheControl.groups;
```

Types are available for TypeScript users:

	npm install --save-dev @alleyinteractive/wp-page-cache-control

## Testing

Run `npm run test` to run Jest tests against JavaScript files. Run
`npm run test:watch` to keep the test runner open and watching for changes.

Run `npm run lint` to run ESLint against all JavaScript files. Linting will also
happen when running development or production builds.

Run `composer test` to run tests against PHPUnit and the PHP code in the plugin.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

This project is actively maintained by [Alley
Interactive](https://github.com/alleyinteractive). Like what you see? [Come work
with us](https://alley.com/careers/).

- [Sean Fisher](https://github.com/srtfisher)
- [All Contributors](../../contributors)

## License

The GNU General Public License (GPL) license. Please see [License File](LICENSE) for more information.
