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

	private $log_defaults;

	public function __construct( $options ) {

		$this->options = $options;

		$this->log_defaults = [
			'username'   => '',
			'attempt'    => '-',
			'timestamp'  => current_time( 'timestamp' ),
			'ip_address' => $this->get_user_ip(),
			'type'       => '',
		];

		include_once( ILR_PATH . 'lib/partials/ilr-log-cpt.php' );

		add_filter( 'ilr_options_nav_items',    [ $this, 'option_nav_item' ] );

		add_action( 'ilr_options_section',      [ $this, 'option_section' ] );

		add_action( 'ilr_handle_invalid_login', [ $this, 'handle_login_attempt' ], 10, 4 );

		add_action( 'wp_login',                 [ $this, 'handle_successful_login' ], 10, 2 );

		add_action( 'wp_dashboard_setup',       [ $this, 'ilr_admin_widget' ] );

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

			<?php

				$this->get_log_option_notice();

				$this->get_log_table();

			?>

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
	public function handle_login_attempt( $username, $attempt_number, $error_object, $user_data ) {

		$error_type = key( $error_object->errors );

		if ( ! parent::$helpers->is_option_enabled( 'logging', $error_type ) ) {

			return;

		}

		switch ( $error_type ) {

			default:
			case 'incorrect_password':

				$attempt = (int) $attempt_number;

				break;

			case 'invalid_username':

				$attempt = '-';

				break;

		}

		if ( 'admin' === $username ) {

			$error_type = [
				$error_type,
				'admin_username',
			];

		}

		$this->log_attempt( [
			'username' => $username,
			'attempt'  => $attempt,
			'type'     => $error_type,
		] );

	}

	/**
	 * Handle a successful login
	 *
	 * @param  string $username    The username
	 * @param  obj    $user_object The user object
	 *
	 * @return bool
	 *
	 * @since 0.0.1
	 */
	public function handle_successful_login( $username, $user_object ) {

		if ( ! parent::$helpers->is_option_enabled( 'logging', 'successful_login' ) ) {

			return;

		}

		$user_obj = parent::$helpers->get_login_user_data( $username );

		$attempt = ( ! $user_obj ) ? 1 : ( false !== parent::$helpers->get_login_user_transient( $user_obj->ID ) ? absint( parent::$helpers->get_login_user_transient( $user_obj->ID ) + 1 ) : 1 );

		$this->log_attempt( [
			'username' => $username,
			'attempt'  => $attempt,
			'type'     => 'successful_login',
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

		$data = wp_parse_args( $data, $this->log_defaults );

		$post_id = wp_insert_post( [
			'post_title'  => $data['username'],
			'post_type'   => 'ilr_log',
			'post_status' => 'publish',
		] );

		if ( 0 < $post_id ) {

			foreach ( $data as $name => $value ) {

				update_post_meta( $post_id, "ilr_log_{$name}", $value );

			} // @codingStandardsIgnoreLine

			do_action( 'ilr_log_update_meta', $post_id );

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
	 * Generate the logging notice
	 *
	 * @return mixed
	 *
	 * @since 1.0.0
	 */
	public function get_log_option_notice() {

		if ( ! parent::$helpers->is_option_enabled( 'logging' ) ) {

			return;

		}

		unset( $this->options['addons']['logging']['options']['dashboard_widget'] );

		$notice_text = __( 'You are currently not tracking anything. To start tracking actions, click on the "Add-Ons" tab in the navigation and enable some sub-options under the "Logging" add-on.', 'invalid-login-redirect' );

		if ( ! empty( $this->options['addons']['logging']['options'] ) ) {

			$notice_text = __( 'You are currently tracking the following user actions:', 'invalid-login-redirect' ) . '<ul class="tracking-list">';

			foreach ( $this->options['addons']['logging']['options'] as $option => $value ) {

				$notice_text .= '<li><span class="dashicons dashicons-arrow-right"></span> ' . ucwords( str_replace( '_', ' ', $option ) ) . '</li>';

			}

			$notice_text .= '</ul>';

		}

		printf(
			'<div class="ilr-notice tracking-notice">
				<div class="icon"><span class="dashicons dashicons-chart-area"></span></div>
				<div class="content">
					<div class="text">%s</div>
				</div>
			</div>',
			$notice_text
		);

	}

	/**
	 * Generate the log table
	 *
	 * @return mixed
	 */
	public function get_log_table() {

		include_once( ILR_MODULES . 'partials/class-log-table.php' );

		$log_table = new Invalid_Login_Redirect_Log_Table( parent::$helpers );

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

		if ( ! parent::$helpers->is_option_enabled( 'logging', 'dashboard_widget' ) ) {

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
	public static function ilr_get_table_badge( $type, $item ) {

		$item_type = array_map( function( $type ) use ( $item ) {

			switch ( $type ) {

				default:
				case 'incorrect_password':

					return [
						'class' => 'invalid-password',
						'text'  => __( 'Invalid Password', 'invalid-login-redirect' ),
					];

					break;

				case 'invalid_username':

					return [
						'class' => 'invalid-username',
						'text'  => __( 'Invalid Username', 'invalid-login-redirect' ),
					];

					break;

				case 'admin_username':

					return [
						'class'   => 'admin-username',
						'tooltip' => sprintf(
							_x( 'A user tried to login to the site using the username "%s".', 'Username used to log in', 'invalid-login-redirect' ),
							esc_html( $item['username'] )
						),
						'text'    => sprintf(
							_x( '%s Admin Username', 'Dashicon warning icon.', 'invalid-login-redirect' ),
							'<span class="dashicons dashicons-warning"></span>'
						),
					];

					break;

				case 'successful_login':

					return [
						'class'   => 'successful-login',
						'text'    => sprintf(
							_x( '%s Login', 'Dashicon yes icon.', 'invalid-login-redirect' ),
							'<span class="dashicons dashicons-yes"></span>'
						),
					];

					break;

			} // @codingStandardsIgnoreLine

		}, (array) $item['type'] );

		//print_r($item_type);
		foreach ( $item_type as $type ) {

			$tooltip = isset( $type['tooltip'] ) ? sprintf(
				'<span class="tip-content">%s</span>',
				esc_html( $type['tooltip'] )
			) : '';

			printf(
				'<div class="badge %1$s">%2$s</div>%3$s',
				esc_attr( $type['class'] ),
				wp_kses_post( $type['text'] ),
				$tooltip
			);

		}

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

}

$ilr_logging = new Invalid_Login_Redirect_Logging( $this->options );
