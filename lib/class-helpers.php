<?php
/**
 * Helper functions
 *
 * @author Code Parrots <support@codeparrots.com>
 *
 * @since 1.0.0
 */
final class ILR_Helpers extends Invalid_Login_Redirect {

	/**
	 * Plugin options
	 *
	 * @var array
	 */
	private $options;

	public function __construct( $options ) {

		$this->options = $options;

	}

	/**
	 * Check if a specific add-on is enabled
	 *
	 * @return boolean
	 *
	 * @since 1.0.0
	 */
	public function is_addon_enabled( $addon = false ) {

		if ( ! $addon ) {

			if ( ! INVALID_LOGIN_REDIRECT_DEVELOPER ) {

				return;

			} // @codingStandardsIgnoreLine

			new Invalid_Login_Redirect_Notice( 'Error: You forgot to specify an add-on name in <code>is_addon_enabled()</code>.', 'error' );

		}

		$addon_name = sanitize_title( $addon );

		return isset( $this->options['addons'][ $addon_name ] );

	}

	/**
	 * Check if an option for a specific addon is enabled
	 *
	 * @return boolean
	 *
	 * @since 1.0.0
	 */
	public function is_option_enabled( $addon = false, $option = false ) {

		if ( ! $addon || ! $option ) {

			if ( ! INVALID_LOGIN_REDIRECT_DEVELOPER ) {

				return;

			} // @codingStandardsIgnoreLine

			new Invalid_Login_Redirect_Notice( 'Error: You forgot to specify an add-on or option name or  in <code>is_option_enabled()</code>.', 'error' );

			return;

		}

		$addon_name = sanitize_title( $addon );

		return ( isset( $this->options['addons'][ $addon_name ]['options'] ) && isset( $this->options['addons'][ $addon_name ]['options'][ $option ] ) );

	}

	/**
	 * Log entry into log
	 *
	 * @param stdObj $user User object
	 *
	 * @since 1.0.0
	 */
	public function log_entry( $data ) {

		$log_class = new Invalid_Login_Redirect_Logging( $this->options );

		$log_class->log_attempt( $data );

	}

	/**
	 * Return a user object
	 *
	 * @param  string $username The username/email to retreive
	 *
	 * @return array
	 *
	 * @since 1.0.0
	 */
	public function get_login_user_data( $username ) {

		if ( empty( $user_obj = get_user_by( ( is_email( $username ) ? 'email' : 'login' ), $username ) ) ) {

			return;

		}

		return $user_obj;

	}

	/**
	 * Get the user transient
	 *
	 * @param integer $user_id The user ID.
	 *
	 * @return bool/array
	 *
	 * @since 1.0.0
	 */
	public function get_login_user_transient( $user_id ) {

		return get_transient( "invalid_login_{$user_id}" );

	}

	/**
	 * Query the logs
	 *
	 * @param integer $post_count number of posts displayed
	 *
	 * @return object
	 *
	 * @since 1.0.0
	 */
	public function get_ilr_log( $query_args = [] ) {

		$default_args = [
			'post_type'      => 'ilr_log',
			'meta_key'       => 'ilr_log_timestamp',
			'orderby'        => 'meta_value_num',
			'order'          => 'DESC',
			'posts_per_page' => -1,
		];

		$args = wp_parse_args( $query_args, $default_args );

		return new WP_Query( $args );

	}

	/**
	 * Get entry data
	 *
	 * @param integer Entry ID to retreive
	 * @param string  meta_key value to retreive
	 *
	 * @return object
	 *
	 * @since 1.0.0
	 */
	public function get_log_data( $log_id, $meta_key, $prefix = 'ilr_log_' ) {

		return get_post_meta( $log_id, $prefix . $meta_key, true );

	}

	/**
	 * Return the users IP address
	 *
	 * @return string
	 */
	public function get_user_ip() {

		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {

			$ip = $_SERVER['HTTP_CLIENT_IP'];

		} else if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {

			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];

		} else {

			$ip = $_SERVER['REMOTE_ADDR'];

		}

		return $ip;

	}

	/**
	 * Get the user role(s)
	 *
	 * @param object $user User object
	 *
	 * @return array
	 *
	 * @since 1.0.0
	 */
	public function get_ilr_user_role( $user = null ) {

		$user = $user ? new WP_User( $user ) : wp_get_current_user();

		return $user->roles ? $user->roles : false;

	}

}

$ilr_helpers = new ILR_Helpers( $this->options );
