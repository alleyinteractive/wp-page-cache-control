<?php
/**
 * Interacts_IP_Addresses trait file
 *
 * @package wp-page-cache-control
 */

namespace Alley\WP\WP_Page_Cache_Control\Providers\Concerns;

/**
 * Trait to interact with IP addresses.
 */
trait Interacts_With_IP_Addresses {
	/**
	 * Check if the current user's IP address matches the passed value.
	 *
	 * Mirror's WordPress VIP's VIP_Request_Block class.
	 *
	 * @param string $value The value to check.
	 * @return string|false False if the IP address doesn't match, otherwise the criteria that matched against.
	 */
	public function is_current_ip( string $value ): string|false {
		$value = strtolower( $value );
		$ip    = inet_pton( $value );

		// Don't try to block if the passed value is not a valid IP.
		if ( ! filter_var( $value, FILTER_VALIDATE_IP ) ) {
			return false;
		}

		// In case this is an IPv6 address and PHP is compiled without the IPV6 support, make sure `false`'s won't match.
		if ( false === $ip ) {
			$ip = '';
		}

		// phpcs:disable WordPressVIPMinimum.Variables.ServerVariables.UserControlledHeaders, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		// This is explicit because we only want to try x-forwarded-for if the
		// true-client-ip is not set.
		if ( ! empty( $_SERVER['HTTP_TRUE_CLIENT_IP'] ) ) {
			$hdr = strtolower( $_SERVER['HTTP_TRUE_CLIENT_IP'] );
			$bin = inet_pton( $hdr );
			if ( $bin === $ip || $hdr === $value ) {
				return 'true-client-ip';
			}
		}

		if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ips = array_map(
				fn ( string $s ): string => strtolower( trim( $s ) ),
				explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] ),
			);
			$bin = array_map( 'inet_pton', $ips );

			if ( in_array( $value, $ips, true ) || in_array( $ip, $bin, true ) ) {
				return 'x-forwarded-for';
			}
		}

		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$hdr = strtolower( $_SERVER['REMOTE_ADDR'] );
			$bin = inet_pton( $hdr );
			if ( $bin === $ip || $hdr === $value ) {
				return 'remote-addr';
			}
		}

		return false;
	}
}
