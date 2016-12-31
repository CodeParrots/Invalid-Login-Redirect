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

	private $version;

	private $reset_user_key;

	private $options;

	private $script_suffix;

	private static $user_data;

	public function __construct() {

		$this->version = '1.0.0';

		$this->reset_user_key = apply_filters( 'ilr_reset_user_key', 'reset_user' );

		$this->options = get_option( 'invalid-login-redirect', [
			'login_limit'       => 3,
			'redirect_url'      => site_url( 'wp-login.php?action=lostpassword' ),
			'error_text'        => esc_html__( 'You have tried to login unsuccessfully 3 times. Have you forgotten your password?', 'invalid-login-redirect' ),
			'error_text_border' => '#dc3232',
			'addons'            => [],
		] );

		$this->script_suffix = ( is_rtl() ? '-rtl' : '' ) . ( WP_DEBUG ? '' : '.min' );

		foreach ( $this->options as $name => $value ) {

			/**
			 * Filter our options
			 * Sample Filter: ilr_login_limit
			 */
			$this->options[ $name ] = apply_filters( "ilr_{$name}", $value );

		}

		if ( ! defined( 'ILR_MODULES' ) ) {

			define( 'ILR_MODULES', plugin_dir_path( __FILE__ ) . 'modules/' );

		}

		if ( ! defined( 'ILR_IMAGES' ) ) {

			define( 'ILR_IMAGES', plugin_dir_url( __FILE__ ) . 'lib/images/' );

		}

		require_once( plugin_dir_path( __FILE__ ) . '/lib/options.php' );

		new Invalid_Login_Redirect_Settings( $this->options, $this->version, $this->script_suffix );

		add_action( 'init', [ $this, 'instantiate_addons' ] );

		add_action( 'wp_login_failed',       [ $this, 'failed_login_attempt' ] );

		add_action( 'ilr_invalid_login',     [ $this, 'handle_invalid_login' ], 10, 2 );

		add_action( 'wp_login',              [ $this, 'clear_invalid_login_transients' ], 10, 2 );

		add_action( 'login_enqueue_scripts', [ $this, 'ilr_login_styles' ] );

		add_filter( 'login_message',         [ $this, 'generate_too_many_attempts_notice' ] );

		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), [ $this, 'ilr_plugin_action_links' ] );

	}

	public function instantiate_addons() {

		if ( empty( $this->options['addons'] ) ) {

			return;

		}

		foreach ( $this->options['addons'] as $addon_name => $addon_file ) {

			// Temp fix
			if ( is_array( $addon_file ) ) {

				continue;

			}

			include_once( ILR_MODULES . $addon_file );

		}

	}

	/**
	 * Failed login attempt
	 *
	 * @return null
	 *
	 * @since 0.0.1
	 */
	public function failed_login_attempt( $username ) {

		self::$user_data = $this->get_login_user_data( $username );

		/**
		 * Trigger an invalid login
		 *
		 * @hooked handle_invalid_login - 10
		 */
		do_action( 'ilr_invalid_login', $username, $_POST );

	}

	/**
	 * Invalid login action handler
	 *
	 * @param  string $username The username
	 *
	 * @return bool
	 *
	 * @since 0.0.1
	 */
	public function handle_invalid_login( $username, $login_data ) {

		$error_obj = apply_filters( 'authenticate', null, $username, $login_data['pwd'] );
		$attempts  = $this->log_invalid_login_attempt( $username );

		/**
		 * Log an invalid password attempt
		 *
		 * @hooked log_invalid_password - 10
		 */
		do_action( 'ilr_handle_invalid_login', $username, $attempts, $error_obj, self::$user_data );

		if (
			'invalid_username' === key( $error_obj->errors ) ||
			! $attempts ||
			$this->options['login_limit'] > $attempts
		) {

			return;

		}

		delete_transient( 'invalid_login_' . self::$user_data->ID );

		wp_redirect( esc_url_raw( $this->build_redirect_url( $username ) ) );

		exit;

	}

	/**
	 * Enqueue Invalid Login Redirect Styles
	 *
	 * @return bool
	 *
	 * @since 0.0.1
	 */
	public function ilr_login_styles() {

		if ( ! isset( $_GET[ $this->reset_user_key ] ) ) {

			return;

		}

		wp_enqueue_style( 'ilr-login-style', plugin_dir_url( __FILE__ ) . "/lib/css/ilr-styles{$this->script_suffix}.css", [], $this->version );

		wp_add_inline_style( 'ilr-login-style', ".ilr_message.error {
			border-color: {$this->options['error_text_border']};
		}" );

	}

	/**
	 * Generate our "Too many attempts" notice
	 *
	 * @param  string $message The original login message.
	 *
	 * @return string
	 *
	 * @since 0.0.1
	 */
	public function generate_too_many_attempts_notice( $message ) {

		$username = filter_input( INPUT_GET, $this->reset_user_key, FILTER_SANITIZE_STRING );

		if ( ! $username || empty( $this->options['error_text'] ) ) {

			return $message;

		}

		$_POST['user_login'] = $username;

		return sprintf(
			'<div class="ilr_message error">%1$s</div>%2$s',
			apply_filters( 'the_content', str_replace( '{attempts}', $this->options['login_limit'], $this->options['error_text'] ) ),
			$message
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
			admin_url( 'options-general.php?page=invalid-login-redirect' ),
			esc_html__( 'Settings', 'invalid-login-redirect' )
		);

		return array_merge( $links, $custom_links );

	}

	/**
	 * Generate the redirect URL
	 *
	 * @return string
	 *
	 * @since 0.0.1
	 */
	public function build_redirect_url( $username = false ) {

		if ( $username && site_url( 'wp-login.php?action=lostpassword' ) === $this->options['redirect_url'] ) {

			$query_args = apply_filters( 'ilr_redirect_query_args', [
				$this->reset_user_key => $username,
			] );

			return add_query_arg( $query_args, $this->options['redirect_url'] );

		}

		return $this->options['redirect_url'];

	}

	/**
	 * Clear the login transients for the user after a successful login
	 *
	 * @param  string $username    The username
	 * @param  obj    $user_object The user object
	 *
	 * @return bool
	 *
	 * @since 0.0.1
	 */
	public function clear_invalid_login_transients( $username, $user_object ) {

		delete_transient( "invalid_login_{$user_object->ID}" );

	}

	/**
	 * Log an invalid login attempt, update user meta
	 *
	 * @param  string  $username The username used to login
	 *
	 * @return int
	 */
	public function log_invalid_login_attempt( $username ) {

		if ( ! self::$user_data ) {

			return;

		}

		$user_id = self::$user_data->ID;

		if ( false === ( $attempts = get_transient( "invalid_login_{$user_id}" ) ) ) {

			$attempt = 1;

		}

		$attempt = isset( $attempt ) ? $attempt : (int) $attempts + 1;

		set_transient(
			"invalid_login_{$user_id}",
			$attempt,
			apply_filters( 'ilr_transient_duration', 1 * HOUR_IN_SECONDS )
		);

		return absint( $attempt );

	}

	/**
	 * Return the user object
	 *
	 * @param  string $username The username/email to retreive
	 *
	 * @return array
	 */
	public function get_login_user_data( $username ) {

		if ( empty( $user_obj = get_user_by( ( is_email( $username ) ? 'email' : 'login' ), $username ) ) ) {

			return;

		}

		return $user_obj;

	}

}

$invalid_login_redirect = new Invalid_Login_Redirect();
