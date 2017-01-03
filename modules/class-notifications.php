<?php
/**
 * Notifications Module
 *
 * @author Code Parrots <support@codeparrots.com>
 *
 * @since 1.0.0
 */
final class Invalid_Login_Redirect_Notifications extends Invalid_Login_Redirect {

	/**
	 * The options instance
	 *
	 * @var array
	 */
	private $options;

	public function __construct( $options ) {

		$this->options = $options;

		add_action( 'ilr_log_store_meta', [ $this, 'ravs_notify_published_post' ] );

		add_filter( 'heartbeat_received', [ $this, 'ravs_heartbeat_received' ], 10, 2 );

		add_action( 'wp_enqueue_scripts', [ $this, 'ravs_notify_init' ] );

	}

	public function ravs_notify_published_post( $post_id ) {

		$post = get_post( $post_id );

		$popup_data = $this->get_notice_data( $post_id );

		if ( ! $popup_data ) {

			return;

		}

		set_transient( 'ravs' . '_' . mt_rand( 100000, 999999 ), $popup_data, 15 );

	}

	public function get_notice_data( $post_id ) {

		if ( ! $post_id ) {

			return;

		}

		$log_type = get_post_meta( $post_id, 'ilr_log_type', true );

		$growl_data = [
			'incorrect_password' => [
				'title' => __( 'Invalid Password Entered', 'invalid-login-redirect' ),
				'type'  => 'error',
			],
			'invalid_username'   => [
				'title' => __( 'Invalid Username Entered', 'invalid-login-redirect' ),
				'type'  => 'error',
			],
			'admin_username'     => [
				'title' => __( 'Admin Username Detected', 'invalid-login-redirect' ),
				'type'  => 'warning',
			],
			'successful_login'   => [
				'title' => sprintf(
					__( '%s Logged In', 'invalid-login-redirect' ),
					esc_html( get_post_meta( $post_id, 'ilr_log_username', true ) )
				),
				'type'  => 'notice',
			],
		];

		$growl = $growl_data[ is_array( $log_type ) ? $log_type[1] : $log_type ];

		return [
			'title'      => $growl['title'],
			'type'       => get_post_meta( $post_id, 'ilr_log_type', true ),
			'ip_address' => get_post_meta( $post_id, 'ilr_log_ip_address', true ),
			'username'   => get_post_meta( $post_id, 'ilr_log_username', true ),
			'attempt'    => get_post_meta( $post_id, 'ilr_log_attempt', true ),
			'timestamp'  => get_post_meta( $post_id, 'ilr_log_timestamp', true ),
			'class'      => $growl['type'],
		];

	}

	/**
	 * collect publish post trasient var from wp_options table and return it to front-end javascript( server response to heartbeat tick)
	 *
	 * @param  array $response  Response
	 * @param  array $data      Collection of publish post data
	 *
	 * @return array of notifications and others
	 *
	 * @since 1.0.0
	 */
	public function ravs_heartbeat_received( $response, $data ) {

		global $wpdb;

		$data['ravs_notify'] = array();

		if ( 'ready' !== $data['notify_status'] ) {

			return;

		}

		$sql = $wpdb->prepare(
			"SELECT * FROM $wpdb->options WHERE option_name LIKE %s",'_transient_'.'ravs'.'_%'
		);

		$notifications = $wpdb->get_results( $sql );

		if ( empty( $notifications ) )
			return $data;

		foreach ( $notifications as $db_notification ) {
			// set id of each notification
			$id = str_replace( '_transient_', '', $db_notification->option_name );

			if ( false !== ( $notification = get_transient( $id ) ) )
				$data['ravs_notify'][$id] = $notification;

		}

		return $data;

	}

	public function ravs_notify_init() {

		wp_enqueue_style( 'ilr-notifications', ILR_URL . 'modules/partials/css/ilr-notifications' . ILR_SCRIPT_SUFFIX . '.css' );

		wp_enqueue_script( 'heartbeat' );

		wp_enqueue_script( 'jquery-growl', ILR_URL . 'modules/partials/js/jquery-growl' . ILR_SCRIPT_SUFFIX . '.js', array( 'heartbeat' ) );

		wp_enqueue_script( 'ilr-notifications', ILR_URL . 'modules/partials/js/ilr-notifications' . ILR_SCRIPT_SUFFIX . '.js', array( 'jquery-growl' ) );

	}

}

$invalid_login_redirect_notifications = new Invalid_Login_Redirect_Notifications( $this->options );
