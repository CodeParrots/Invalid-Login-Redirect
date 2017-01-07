<?php
/**
 * Admin Notices Class
 *
 * @author Code Parrots <codeparrots@gmail.com>
 *
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

class Invalid_Login_Redirect_Notice {

	private $text;

	private $type;

	public function __construct( $text = '', $type = 'success' ) {

		$this->text = $text;
		$this->type = $type;

		$this->init();

	}

	public function init() {

		add_action( 'admin_notices', [ $this, 'generate_admin_notice' ] );

	}

	/**
	 * Generate the HTML markup for the admin notice
	 *
	 * @return mixed HTML markup for the admin notice.
	 */
	public function generate_admin_notice() {

		printf( '<div class="%1$s"><p>%2$s</p></div>',
			esc_html( 'notice notice-' . $this->type ),
			wp_kses_post( $this->text )
		);

	}

}
