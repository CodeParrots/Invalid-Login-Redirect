<?php
/**
 #_________________________________________________ PLUGIN
 Plugin Name: Invalid Login Redirect
 Plugin URI: http://www.codeparrots.com
 Description: Redirect users to a specific page after a specified number of invalid login attempts.
 Version: 1.0.0
 Author: Code Parrots
 Text Domain: invalid-login-redirect
 Author URI: http://www.codeparrots.com
 License: GPL2
 #_________________________________________________ LICENSE
 Copyright 2012-16 Code Parrots (email : codeparrots@gmail.com)

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License, version 2, as
 published by the Free Software Foundation.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 #_________________________________________________ CONSTANTS
**/

class Invalid_Login_Redirect {

	/**
	 * Plugin Options
	 *
	 * @var array
	 */
	private $options;

	public static $helpers;

	public static $login_url;

	public function __construct() {

		require_once( plugin_dir_path( __FILE__ ) . '/constants.php' );

		self::$login_url = apply_filters( 'ilr_login_url', site_url( 'wp-login.php' ) );

		$this->options = get_option( 'invalid-login-redirect', [
			'login_limit'       => 3,
			'redirect_url'      => site_url( self::$login_url . '?action=lostpassword' ),
			'error_text'        => esc_html__( 'You have tried to login unsuccessfully 3 times. Have you forgotten your password?', 'invalid-login-redirect' ),
			'error_text_border' => '#dc3232',
			'addons'            => [],
		] );

		foreach ( $this->options as $name => $value ) {

			/**
			 * Filter our options
			 *
			 * Sample Filter: ilr_login_limit
			 */
			$this->options[ $name ] = apply_filters( "ilr_{$name}", $value );

		}

		require_once( plugin_dir_path( __FILE__ ) . '/lib/class-helpers.php' );

		self::$helpers = new ILR_Helpers( $this->options );

		add_action( 'init', [ $this, 'requirements' ] );

		add_action( 'admin_notices', [ $this, 'ilr_admin_notices' ] );

		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), [ $this, 'ilr_plugin_action_links' ] );

	}

	/**
	 * Load our addons
	 *
	 * @since 1.0.0
	 */
	public function requirements() {

		require_once( plugin_dir_path( __FILE__ ) . '/lib/class-options.php' );

		new Invalid_Login_Redirect_Settings( $this->options );

		require_once( plugin_dir_path( __FILE__ ) . '/lib/class-admin-notice.php' );

		// Base module, invalid login redirects
		include_once( ILR_MODULES . 'class-login-redirect.php' );

		$this->load_modules();

	}

	/**
	 * Load the modules
	 *
	 * @return null
	 *
	 * @since 1.0.0
	 */
	private function load_modules() {

		if ( empty( $this->options['addons'] ) ) {

			return;

		}

		foreach ( $this->options['addons'] as $addon => $data ) {

			if ( ! isset( $data['file'] ) ) {

				continue;

			}

			if ( file_exists( ILR_MODULES . $data['file'] ) ) {

				include_once( ILR_MODULES . $data['file'] );

			} // @codingStandardsIgnoreLine

		}

	}

	/**
	 * Admin notices
	 *
	 * @param  string $wp  Minimum WordPress version number allowed
	 * @param  string $php Minimum PHP version allowed
	 *
	 * @return mixed
	 *
	 * @since 1.0.0
	 */
	public function ilr_admin_notices( $wp = '4.5', $php = '5.6' ) {

		global $wp_version;

		$flag = ( version_compare( PHP_VERSION, $php, '<' ) ) ? 'PHP' : ( ( version_compare( $wp_version, $wp, '<' ) ) ? 'WordPress' : false );

		if ( ! $flag ) {

			return;

		}

		$version = 'PHP' == $flag ? $php : $wp;

		printf(
			'<div class="error">
				<p>%1$s</p>
			</div>',
			sprintf(
				__( '%1$s requires %2$s version %3$s or greater. Please update to a later version of %2$s before using this plugin.', 'invalid-login-redirect' ),
				'<strong>' . __( 'Invalid Login Redirect', 'invalid-login-redirect' ) . '</strong>',
				esc_html( $flag ),
				esc_html( $version )
			)
		);

	}

	/**
	 * Generate plugin action links
	 *
	 * @param  array $links Action links array.
	 *
	 * @return array
	 *
	 * @since 0.0.1
	 */
	public function ilr_plugin_action_links( $links ) {

		$custom_links[] = sprintf(
			'<a href="%1$s">%2$s</a>',
			admin_url( 'tools.php?page=invalid-login-redirect&tab=general' ),
			esc_html__( 'Settings', 'invalid-login-redirect' )
		);

		return $custom_links + $links;

	}

}

$invalid_login_redirect = new Invalid_Login_Redirect();
