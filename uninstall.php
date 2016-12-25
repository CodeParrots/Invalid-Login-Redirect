<?php
/**
 * Uninstall our plugin options, and all of our Announcements
 *
 * @package Invalid_Login_Redirect
 *
 * @since 0.0.1
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {

	exit;

}

global $wpdb;

$wpdb->query(
	$wpdb->prepare(
		'DELETE FROM ' . $wpdb->options . ' WHERE option_name LIKE "%s"',
		$wpdb->esc_like( '_transient_invalid_login_' ) . '%'
	)
);
