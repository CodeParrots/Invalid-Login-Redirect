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
	 * Check if an option, or add-on, is enabled
	 *
	 * @return boolean
	 *
	 * @since 1.0.0
	 */
	public function is_option_enabled( $addon, $option = false ) {

		$addon_name = sanitize_title( $addon );

		if ( $addon && ! $option ) {

			return isset( $this->options['addons'][ $addon_name ] );

		}

		return ( isset( $this->options['addons'][ $addon_name ]['options'] ) && $this->options['addons'][ $addon_name ]['options'][ $option ] );

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
	 */
	public function get_login_user_transient( $user_id ) {

		return get_transient( "invalid_login_{$user_id}" );

	}

}

$ilr_helpers = new ILR_Helpers( $this->options );
