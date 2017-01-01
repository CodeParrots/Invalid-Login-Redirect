<?php
/**
 * Redirection Module
 *
 * On by default - always active
 *
 * @author Code Parrots <support@codeparrots.com>
 *
 * @since 1.0.0
 */
final class Invalid_Login_Redirect_Redirection extends Invalid_Login_Redirect {

	private $options;

	private $reset_user_key;

	private static $user_data;

	public function __construct( $options ) {

		$this->options = $options;

		$this->reset_user_key = apply_filters( 'ilr_reset_user_key', 'reset_user' );

		add_action( 'wp_login_failed',       [ $this, 'failed_login_attempt' ] );

		add_action( 'ilr_invalid_login',     [ $this, 'handle_invalid_login' ], 10, 2 );

		add_action( 'wp_login',              [ $this, 'clear_invalid_login_transients' ], 10, 2 );

		add_action( 'login_enqueue_scripts', [ $this, 'ilr_login_styles' ] );

		add_filter( 'login_message',         [ $this, 'generate_too_many_attempts_notice' ] );

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

		wp_enqueue_style( 'ilr-login-style', plugin_dir_url( __FILE__ ) . '/lib/css/ilr-styles' . ILR_STYLE_SUFFIX . '.css', [], ILR_VERSION );

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

$invalid_login_redirect_redirection = new Invalid_Login_Redirect_Redirection( $this->options );
