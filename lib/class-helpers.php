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

	private $option_field_defaults;

	public function __construct( $options ) {

		$this->options = $options;

		$this->option_field_defaults = [
			'type'        => 'text',
			'label'       => '',
			'name'        => '',
			'value'       => '',
			'array_value' => false,
			'before'      => '',
			'after'       => '',
			'placeholder' => '',
			'class'       => '',
			'callback'    => '',
		];

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

	/**
	 * Generate the markup for the option field
	 *
	 * @return mixed
	 *
	 * @since 1.0.0
	 */
	public function ilr_option_markup( $fields ) {

		if ( ! $fields || empty( $fields ) ) {

			return;

		}

		if ( isset( $fields[ key( $fields ) ]['fields'] ) ) {

			$x = 0;

			foreach ( $fields[ key( $fields ) ]['fields'] as $field ) {

				$fields[ key( $fields ) ]['fields'][ $x ] = wp_parse_args( $fields[ key( $fields ) ]['fields'][ $x ], $this->option_field_defaults );

				$x++;

			} // @codingStandardsIgnoreLine

		} else {

			$fields[ key( $fields ) ] = wp_parse_args( $fields[ key( $fields ) ], $this->option_field_defaults );

		}

		if ( ! empty( $fields[ key( $fields ) ]['callback'] ) ) {

			$callback = $fields[ key( $fields ) ]['callback'];

			if ( is_callable( [ $this, $callback ] ) ) {

				$this->$callback( $fields );

				return;

			} // @codingStandardsIgnoreLine

		}

		$this->render_text_field( $fields );

	}

	/**
	 * Render standard text field
	 *
	 * @param  array  $fields     Field data array
	 * @param  bool   $sub_option Is a nested option
	 * @param  string $title      The title of the option
	 *
	 * @return mixed
	 *
	 * @since 1.0.0
	 */
	private function render_text_field( $fields, $sub_option = false, $title = '' ) {

		if ( isset( $fields[ key( $fields ) ]['fields'] ) ) {

			printf(
				'<div class="col ilr-notice">%s<div class="fields">',
				esc_html( $title )
			);

			$x = 1;

			foreach ( $fields[ key( $fields ) ]['fields'] as $field ) {

				echo ( 1 === $x ) ? wp_kses_post( $fields[ key( $fields ) ]['title'] ) : '';

				$this->render_text_field( [ key( $fields ) => $field ], true, $fields[ key( $fields ) ]['title'] );

				$x++;

			} // @codingStandardsIgnoreLine

			print( '</div></div>' );

			return;

		}

		if ( ! $sub_option ) {

			print( '<div class="col ilr-notice"><div class="fields">' );

		}

		foreach ( $fields as $module_slug => $data ) {

			$data          = wp_parse_args( $data, $this->option_field_defaults );
			$data['value'] = isset( $data['value'] ) ? $data['value'] : ( ( isset( $this->options['addons'][ $module_slug ]['options'][ $data['name'] ] ) && ! empty( $this->options['addons'][ $module_slug ]['options'][ $data['name'] ] ) ) ? sanitize_text_field( $this->options['addons'][ $module_slug ]['options'][ $data['name'] ] ) : '' );
			$field_name    = isset( $data['name'] ) ? $data['name'] : "invalid-login-redirect[addons][{$module_slug}][options][{$data['name']}]";

			printf(
				'<label for="%1$s">%2$s</label>
				%3$s<input type="%4$s" id="%1$s" name="%1$s" class="%5$s" value="%6$s" placeholder="%7$s" />',
				esc_attr( $field_name ),
				wp_kses_post( $data['label'] ),
				wp_kses_post( $data['before'] ),
				esc_attr( $data['type'] ),
				! empty( $data['class'] ) ? $data['class'] : sanitize_title( $data['name'] ),
				esc_attr( $data['value'] ),
				esc_attr( $data['placeholder'] )
			);

		}

		if ( ! $sub_option ) {

			print( '</div></div>' );

		}

	}

	private function ilr_textarea( $fields ) {

		foreach ( $fields[ key( $fields ) ] as $module_slug => $field ) {

			$value = isset( $field['value'] ) ? $field['value'] : ( ( isset( $this->options['addons'][ $module_slug ]['options'][ $field['name'] ] ) && ! empty( $this->options['addons'][ $module_slug ]['options'][ $field['name'] ] ) ) ? sanitize_text_field( $this->options['addons'][ $module_slug ]['options'][ $field['name'] ] ) : ( ( $field['array_value'] ) ? [] : '' ) );

			print( '<div class="col ilr-notice"><div class="fields">' );

			printf(
				'<label for="%1$s">%2$s</label>
				%3$s<textarea id="%1$s" class="%4$s" placeholder="%5$s">%6$s</textarea>',
				esc_attr( "invalid-login-redirect[addons][{$module_slug}][options][{$field['name']}]" ),
				wp_kses_post( $field['label'] ),
				wp_kses_post( $field['before'] ),
				! empty( $field['class'] ) ? $field['class'] : sanitize_title( $field['name'] ),
				esc_attr( $field['placeholder'] ),
				esc_attr( $field['value'] )
			);

			print( '</div></div>' );

		}

		print( '<h2>Testing</h2>' );

	}

}

$ilr_helpers = new ILR_Helpers( $this->options );
