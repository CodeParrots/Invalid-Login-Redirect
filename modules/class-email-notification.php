<?php
/**
 * Send an email notification when a user logs in
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

	/**
	 * Send the email notification
	 *
	 * @param  string $username    The username
	 * @param  stdObj $user_object User object
	 *
	 * @return mixed
	 */
	public function ilr_email_notification( $username, $user_object ) {

		$roles = parent::$helpers->get_ilr_user_role( $user_object );

		if ( in_array( 'administrator', $roles ) && ! INVALID_LOGIN_REDIRECT_DEVELOPER ) {

			return;

		}

		ob_start();

		$this->get_email( 'basic' );

		$email = ob_get_contents();

		if ( ! $email ) {

			return;

		}

		add_filter( 'wp_mail_content_type', function() {

			return 'text/html';

		} );

		wp_mail(
			get_option( 'admin_email' ),
			__( 'Successul Login Detected',
			'invalid-login-redirect' ),
			$email
		);

	}

	/**
	 * Get the email type
	 *
	 * @param  string $type The type of email to generate
	 *
	 * @return mixed
	 */
	public function get_email( $type ) {

		if ( ! file_exists( ILR_MODULES . "partials/emails/email-{$type}.php" ) ) {

			return false;

		}

		include_once( ILR_MODULES . "partials/emails/email-{$type}.php" );

	}

}

$invalid_login_redirect_email_notification = new Invalid_Login_Redirect_Email_Notification( $this->options );
