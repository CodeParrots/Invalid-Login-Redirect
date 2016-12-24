<?php
/**
 #_________________________________________________ PLUGIN
 Plugin Name: Invalid Login Redirect
 Plugin URI: http://www.codeparrots.com
 Description: Redirect users to a specific page after a specified number of invalid login attempts.
 Version: 0.0.1
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

final class Invalid_Login_Redirect {

	private $version;

	private $options;

	public function __construct() {

		$this->version = '0.0.1-alpha';

		$this->options = get_option( 'invalid-login-redirect', [
			'login_limit'      => 3,
			'redirect_url'     => site_url( 'wp-login.php?action=lostpassword' ),
			'error_text'       => esc_html__( 'You have tried to login unsuccessfully 3 times. Have you forgotten your password?', 'invalid-login-redirect' ),
			'error_text_color' => '#dc3232',
		] );

		foreach ( $this->options as $name => $value ) {

			/**
			 * Filter our options
			 * Sample Filter: ilr_login_limit
			 */
			$this->options[ $name ] = apply_filters( "ilr_{$name}", $value );

		}

		require_once( plugin_dir_path( __FILE__ ) . '/lib/options.php' );

		new Invalid_Login_Redirect_Settings( $this->options, $this->version );

		add_action( 'login_head', [ $this, 'check_invalid_login' ], 20 );

		add_action( 'ilr_invalid_login', [ $this, 'invalid_login_handler' ], 10, 2 );

		add_action( 'wp_login', [ $this, 'clear_login_transients' ], 10, 2 );

		if ( $this->username = filter_input( INPUT_GET, 'ilr_reset', FILTER_SANITIZE_STRING ) ) {

			add_action( 'login_enqueue_scripts', [ $this, 'ilr_login_styles' ] );

			add_filter( 'login_message', [ $this, 'generate_too_many_attempts_notice' ] );

		}

		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), [ $this, 'ilr_plugin_action_links' ] );

	}

	/**
	 * Check for invalid login and store username
	 *
	 * @return null
	 *
	 * @since 0.0.1
	 */
	public function check_invalid_login() {

		$user_name = filter_input( INPUT_POST, 'log', FILTER_SANITIZE_STRING );
		$password  = filter_input( INPUT_POST, 'pwd', FILTER_SANITIZE_STRING );

		if ( ! $user_name || ! $password ) {

			return;

		}

		$this->username = sanitize_text_field( sanitize_title( $user_name ) );

		$user = apply_filters( 'authenticate', null, $user_name, $password );

		if ( is_wp_error( $user ) ) {

			/**
			 * Trigger an invalid login
			 *
			 * @hooked invalid_login_handler - 10
			 */
			do_action( 'ilr_invalid_login', $this->username, $user );

		}

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
	public function invalid_login_handler( $username, $error_obj ) {

		if ( ! $username ) {

			return;

		}

		if ( false === ( $login_transient = get_transient( "invalid_login_{$username}" ) ) ) {

			$this->log_invalid_login_attempt( $username );

			return;

		}

		$transient_data = json_decode( $login_transient, true );

		$attempt = (int) $transient_data['attempts'] + 1;

		if ( $this->options['login_limit'] <= $attempt ) {

			set_transient( "password_reset_{$username}", 1, 1 * MINUTE_IN_SECONDS );

			delete_transient( "invalid_login_{$username}" );

			$redirect_url = ( site_url( 'wp-login.php?action=lostpassword' ) === $this->options['redirect_url'] ) ? add_query_arg( [
				'ilr_reset' => $username,
			], $this->options['redirect_url'] ) : $this->options['redirect_url'];

			wp_redirect( esc_url_raw( $redirect_url ) );

			exit;

		}

		$this->log_invalid_login_attempt( $username, $attempt );

	}

	/**
	 * Enqueue Invalid Login Redirect Styles
	 *
	 * @return bool
	 *
	 * @since 0.0.1
	 */
	public function ilr_login_styles() {

		if ( ! get_transient( "password_reset_{$this->username}" ) ) {

			wp_redirect( $this->options['redirect_url'] );

		}

		$rtl = is_rtl() ? '-rtl' : '';
		$min = WP_DEBUG ? '' : '.min';

		wp_enqueue_style( 'ilr-login-style', plugin_dir_url( __FILE__ ) . "/lib/css/ilr-styles{$rtl}{$min}.css", array(), $this->version );

		wp_add_inline_style( 'ilr-login-style', ".ilr_message.error {
			border-color: {$this->options['error_text_color']};
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

		if ( get_transient( "password_reset_{$this->username}" ) ) {

			delete_transient( "password_reset_{$this->username}" );

			$_POST['user_login'] = $this->username;

			$error_text = str_replace( '{attempts}', $this->options['login_limit'], $this->options['error_text'] );

			$message = sprintf(
				'<div class="ilr_message error">%1$s</div>%2$s',
				apply_filters( 'the_content', $error_text ),
				$message
			);

		}

		return $message;

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
	 * Clear the login transients for the user after a successful login
	 *
	 * @param  string $username    The username
	 * @param  obj    $user_object The user object
	 *
	 * @return bool
	 *
	 * @since 0.0.1
	 */
	public function clear_login_transients( $username, $user_object ) {

		delete_transient( "invalid_login_{$username}" );

	}

	/**
	 * Set or update the invalid login attempt transient
	 *
	 * @param  string  $username The username attempting to login
	 * @param  integer $attempt  Attempt number
	 *
	 * @return bool
	 *
	 * @since 0.0.1
	 */
	public function log_invalid_login_attempt( $username, $attempt = 1 ) {

		if ( ! $username ) {

			return;

		}

		set_transient( "invalid_login_{$username}", json_encode( [
			'username' => $username,
			'attempts' => (int) $attempt,
		] ), apply_filters( 'ilr_transient_duration', 1 * HOUR_IN_SECONDS ) );

	}

}

$invalid_login_redirect = new Invalid_Login_Redirect();
