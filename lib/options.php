<?php
/**
 * Invalid Login Redirect Settings
 *
 * @since 0.0.1
 */
class Invalid_Login_Redirect_Settings {

	private $options;

	private $version;

	private $style_suffix;

	private $script_suffix;

	public function __construct( $options, $version, $suffix ) {

		if ( ! is_admin() ) {

			return;

		}

		$this->options        = $options;
		$this->version        = $version;
		$this->style_suffix   = $suffix;
		$this->script_suffix  = WP_DEBUG ? '' : '.min';

		add_action( 'admin_menu', [ $this, 'add_plugin_page' ] );

		add_action( 'admin_init', [ $this, 'page_init' ] );

	}


	/**
	 * Add our custom options page
	 *
	 * @since 0.0.1
	*/
	public function add_plugin_page() {

		add_options_page(
			__( 'Invalid Login Redirect Options', 'invalid-login-redirect' ),
			__( 'Invalid Login Redirect', 'invalid-login-redirect' ),
			'manage_options',
			'invalid-login-redirect',
			[ $this, 'create_admin_page' ]
		);

	}

	/**
	 * Options page callback
	 *
	 * @return mixed
	 *
	 * @since 0.0.1
	*/
	public function create_admin_page() {

		wp_enqueue_style( 'ilr-admin', plugin_dir_url( __FILE__ ) . "/css/ilr-admin{$this->style_suffix}.css" );

		wp_enqueue_style( 'wp-color-picker' );

		wp_enqueue_script( 'ilr-admin', plugin_dir_url( __FILE__ ) . "/js/ilr-admin{$script_suffix}.js", array( 'wp-color-picker' ), true, $this->version );

		wp_add_inline_style( 'ilr-admin', ".ilr_message.invalid {
			border-color: {$this->options['error_text_border']};
		}" );

		?>

		<div class="wrap">

			<h1><?php esc_html_e( 'Invalid Login Redirect Options', 'invalid-login-redirect' ); ?> <small style="float:right; font-weight:200; font-size:10px;"><em><?php printf( esc_html_x( 'version %s', 'plugin version number', 'invalid-login-redirect' ), $this->version ); ?></em></small></h1>

			<form method="post" action="options.php">

				<?php

					settings_fields( 'ilr_options' );

					do_settings_sections( 'invalid-login-redirect' );

					submit_button();

				?>

			</form>

		</div>

		<?php
	}

	/**
	 * Register and add settings
	 *
	 * @since 0.0.1
	*/
	public function page_init() {

		register_setting(
			'ilr_options',
			'invalid-login-redirect',
			[ $this, 'sanitize' ]
		);

		add_settings_section(
			'invalid_login_redirect_section',
			'',
			[ $this, 'print_section_info' ],
			'invalid-login-redirect'
		);

		add_settings_field(
			'redirect_url',
			__( 'Redirect To', 'invalid-login-redirect' ),
			[ $this, 'redirect_url_callback' ],
			'invalid-login-redirect',
			'invalid_login_redirect_section'
		);

		add_settings_field(
			'login_limit',
			__( 'Login Attempts', 'invalid-login-redirect' ),
			[ $this, 'login_limit_callback' ],
			'invalid-login-redirect',
			'invalid_login_redirect_section'
		);

		add_settings_field(
			'error_text',
			__( 'Error Text', 'invalid-login-redirect' ),
			[ $this, 'error_text_callback' ],
			'invalid-login-redirect',
			'invalid_login_redirect_section'
		);

		add_settings_field(
			'error_text_border',
			__( 'Error Text Border Color', 'invalid-login-redirect' ),
			[ $this, 'error_text_border_callback' ],
			'invalid-login-redirect',
			'invalid_login_redirect_section'
		);

		add_settings_field(
			'error_text_preview',
			__( 'Error Text Preview', 'invalid-login-redirect' ),
			[ $this, 'error_text_preview_callback' ],
			'invalid-login-redirect',
			'invalid_login_redirect_section'
		);

	}

	/**
	 * Sanitize each setting field as needed
	 *
	 * @param array $input Contains all settings fields as array keys
	 *
	 * @return array
	 *
	 * @since 0.0.1
	 */
	public function sanitize( $input ) {

		$new_input = [];

		$new_input['redirect_url']     = ! empty( $input['redirect_url'] ) ? sanitize_url( $input['redirect_url'] ) : site_url( 'wp-login.php?action=lostpassword' );
		$new_input['login_limit']      = ! empty( $input['login_limit'] ) ? absint( $input['login_limit'] ) : $this->options['login_limit'];
		$new_input['error_text']       = isset( $input['error_text'] ) ? trim( $input['error_text'] ) : $this->options['error_text'];
		$new_input['error_text_border'] = isset( $input['error_text_border'] ) ? sanitize_text_field( $input['error_text_border'] ) : $this->options['error_text_border'];

		return $new_input;

	}

	/**
	* Print the Section text
	*
	* @return mixed
	*
	* @since 0.0.1
	*/
	public function print_section_info() {

		printf(
			'<p class="description">%s</p>',
			esc_html__( 'Edit the options for the invalid login redirect plugin below.', 'invalid-login-redirect' )
		);

	}

	/**
	* Generate the URL
	*
	* @return mixed
	*
	* @since 0.0.1
	*/
	public function redirect_url_callback() {

		printf(
			'<input type="text" placeholder="' . site_url( 'wp-login.php?action=lostpassword' ) . '" class="widefat" id="redirect_url" name="invalid-login-redirect[redirect_url]" value="%1$s" /><p class="description">%2$s</p>',
			isset( $this->options['redirect_url'] ) ? esc_attr( $this->options['redirect_url'] ) : '',
			sprintf( esc_html_x( 'Enter the URL of the page that users should be redirected to after %s attempts.', 'The login limit set on the options page.', 'invalid-login-redirect' ), $this->options['login_limit'] )
		);

	}

	/**
	* Generate the login limit field
	*
	* @return mixed
	*
	* @since 0.0.1
	*/
	public function login_limit_callback() {

		printf(
			'<input type="number" min="2" step="1" id="login_limit" name="invalid-login-redirect[login_limit]" value="%1$s" /><p class="description">%2$s</p>',
			isset( $this->options['login_limit'] ) ? esc_attr( $this->options['login_limit'] ) : '',
			esc_html__( 'Set the number of times a user can enter incorrect details before being redirected.', 'invalid-login-redirect' )
		);

	}

	/**
	* Generate the WYSIWYG error text field
	*
	* @return mixed
	*
	* @since 0.0.1
	*/
	public function error_text_callback() {

		wp_editor(
			$this->options['error_text'],
			'error_text',
			[
				'textarea_name' => 'invalid-login-redirect[error_text]',
				'editor_height' => '125',
			]
		);

		printf(
			'<p class="description">%s</p>',
			sprintf(
				esc_html__( 'Enter the text that will appear back to the user on the lost password page. You can use %s as a placeholder to display the number of attempts in your message.', 'invalid-login-redirect' ),
				'<code><strong>{attempts}</strong></code>'
			)
		);

		printf(
			'<p class="description">%s</p>',
			sprintf(
				esc_html_x( '%1$s This message will only display on the page if you redirec to a page that contains a login form created with %2$s.', '1: "Note" wrapped in <strong> tags. 2: wp_login_form function.', 'invalid-login-redirect' ),
				'<strong>' . __( 'Note:', 'invalid-login-redirect' ) . '</strong>',
				'<code>wp_login_form()</code>'
			)
		);

	}

	/**
	* Generate the color picker field
	*
	* @return mixed
	*
	* @since 0.0.1
	*/
	public function error_text_border_callback() {

		printf(
			'<input type="text" name="invalid-login-redirect[error_text_border]" value="%1$s" class="js_error_text_border" data-default-color="%1$s" /><p class="description">%2$s</p>',
			$this->options['error_text_border'],
			sprintf(
				esc_html__( 'Enter the text that will appear back to the user on the lost password page. You can use %s as a placeholder to display the number of attempts in your message.', 'invalid-login-redirect' ),
				'<code><strong>{attempts}</strong></code>'
			)
		);

	}

	/**
	* Generate a preview of the error message
	*
	* @return mixed
	*
	* @since 0.0.1
	*/
	public function error_text_preview_callback() {

		printf(
			'<div class="ilr_message invalid">%1$s</div>',
			apply_filters( 'the_content', str_replace( '{attempts}', $this->options['login_limit'], $this->options['error_text'] ) )
		);

	}

}
