<?php
/**
 * Change the /wp-login.php URL
 *
 * @author Code Parrots <support@codeparrots.com>
 *
 * @since 1.0.0
 */
final class Invalid_Login_Redirect_Email_Notification extends Invalid_Login_Redirect {

	private $options;

	public function __construct( $options ) {

		$this->options = $options;

		add_action( 'wp_login', [ $this, 'ilr_email_notification' ], 5, 2 );

	}

	public function ilr_email_notification( $username, $user_object ) {

			$recipient = get_option( 'admin_email' );

			$website = get_option( 'siteurl' );

			$subject = $website . ' • Successful login: ' . $username;

			$message = 'A login attept was successfully made to ' . $website . ' by ' . $username . ' at ' . date( get_option( 'time_format' ), current_time( 'timestamp' ) ) . ' on ' . date( get_option( 'date_format' ), current_time( 'timestamp' ) ) . '.';

			wp_mail( $recipient,  $subject , $message );

	}

}

$invalid_login_redirect_email_notification = new Invalid_Login_Redirect_Email_Notification( $this->options );
