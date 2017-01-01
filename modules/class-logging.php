<?php
/**
 * Logging Module
 *
 * @author Code Parrots <support@codeparrots.com>
 *
 * @since 1.0.0
 */
final class Invalid_Login_Redirect_Logging extends Invalid_Login_Redirect {

	private $options;

	public function __construct( $options ) {

		$this->options = $options;

		include_once( ILR_MODULES . 'partials/logging-cpt.php' );

		add_filter( 'ilr_options_nav_items', [ $this, 'option_nav_item' ] );

		add_action( 'ilr_options_section', [ $this, 'option_section' ] );

		add_action( 'ilr_handle_invalid_login', [ $this, 'log_invalid_password' ], 10, 4 );

		add_action( 'wp_dashboard_setup', [ $this, 'ilr_admin_widget' ] );

	}

	/**
	 * Add a options nav menu item
	 *
	 * @param  array $nav_items Array of option navigation items
	 *
	 * @return array
	 *
	 * @since 1.0.0
	 */
	public function option_nav_item( $nav_items ) {

		$nav_items[] = __( 'Logging', 'invalid-login-redirect' );

		return $nav_items;

	}

	/**
	 * Add the options section
	 *
	 * @param  string $tab The active options tab
	 *
	 * @return mixed
	 *
	 * @since 1.0.0
	 */
	public function option_section( $tab ) {

		?>

		<div class="logging add-on <?php if ( ( $tab && 'logging' !== $tab ) || ! $tab ) { echo 'hidden'; } ?>">

			<?php $this->get_log_table(); ?>

		</div>

		<?php

	}

	/**
	 * Log an invalid login attempt (invalid username)
	 *
	 * @param  string  $username       Username used in attempt
	 * @param  integer $attempt_number Number of attempts
	 * @param  object  $error_object   Error data
	 * @param  object  $user_data      User data
	 *
	 * @return bool
	 *
	 * @since 1.0.0
	 */
	public function log_invalid_password( $username, $attempt_number, $error_object, $user_data ) {

		if ( ! $this->is_option_enabled( 'logging', 'invalid_password' ) ) {

			return;

		}

		$this->log_attempt( [
			'username'   => $username,
			'attempt'    => (int) $attempt_number,
			'timestamp'  => current_time( 'timestamp' ),
			'ip_address' => $this->get_user_ip(),
			'type'       => 'invalid_password',
		] );

	}

	/**
	 * Insert ilr_log post type
	 *
	 * @param  array $data Data to be used in post
	 *
	 * @return null
	 */
	public function log_attempt( $data ) {

		$post_id = wp_insert_post( [
			'post_title'  => $data['username'],
			'post_type'   => 'ilr_log',
			'post_status' => 'publish',
		] );

		if ( 0 < $post_id ) {

			foreach ( $data as $name => $value ) {

				update_post_meta( $post_id, "ilr_log_{$name}", $value );

			}

		} else {

			wp_die( $post_id );

		}

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
	 * Generate the log table
	 *
	 * @return mixed
	 */
	public function get_log_table() {

		include_once( ILR_MODULES . 'partials/class-log-table.php' );

		$log_table = new Invalid_Login_Redirect_Log_Table();

		$log_table->prepare_items();

		$log_table->display();

	}

	/**
	 * Register the admin widget
	 *
	 * @return null
	 *
	 * @since 1.0.0
	 */
	public function ilr_admin_widget() {

		if ( ! $this->is_option_enabled( 'logging', 'dashboard_widget' ) ) {

			return;

		}

		global $wp_meta_boxes;

		wp_add_dashboard_widget(
			'ilr_logging_widget',
			sprintf(
				__( 'Invalid Login Attempts', 'invalid-login-redirect' ) . '%s',
				'<small style="float: right; margin-top: 2px;"><a href="' . admin_url( 'tools.php?page=invalid-login-redirect&tab=logging' ) . '">' . esc_html__( 'view full log', 'invalid-login-redirect' ) . '</a></small>'
			),
			[ $this, 'ilr_admin_Widget_content' ]
		);

	}

	/**
	 * Content for admin widget
	 *
	 * @return mixed
	 *
	 * @since 1.0.0
	 */
	public function ilr_admin_Widget_content() {

		include_once( ILR_MODULES . 'partials/logging-dashboard-widget.php' );

	}

	/**
	 * Helper function to check if the dashboard widget option is active
	 *
	 * @return boolean
	 *
	 * @since 1.0.0
	 */
	private function is_option_enabled( $addon, $option = false ) {

		$addon_name = sanitize_title( $addon );

		if ( $addon && ! $option ) {

			return isset( $this->options['addons'][ $addon_name ]['options'] );

		}

		return ( isset( $this->options['addons'][ $addon_name ]['options'] ) && $this->options['addons'][ $addon_name ]['options'][ $option ] );

	}

}

$ilr_logging = new Invalid_Login_Redirect_Logging( $this->options );
