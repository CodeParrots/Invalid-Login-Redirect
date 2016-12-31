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

	private $tab;

	public function __construct( $options, $version, $suffix ) {

		if ( ! is_admin() ) {

			return;

		}

		$this->options        = $options;
		$this->version        = $version;
		$this->style_suffix   = $suffix;
		$this->script_suffix  = WP_DEBUG ? '' : '.min';
		$this->tab            = filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_STRING );

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

		wp_enqueue_script( 'ilr-admin', plugin_dir_url( __FILE__ ) . "/js/ilr-admin{$this->script_suffix}.js", array( 'wp-color-picker' ), true, $this->version );

		wp_add_inline_style( 'ilr-admin', ".ilr_message.invalid {
			border-color: {$this->options['error_text_border']};
		}" );

		?>

		<div class="ilr-banner">

			<div class="inner-container">

				<div class="logo-container">

					<a class="logo-link"><img src="<?php echo ILR_IMAGES . 'logo.png'; ?>" class="logo" /></a>

				</div>

				<ul class="links">
					<li><a href="#">Need Help?</a></li>
					<li><a href="#">Leave a Review</a></li>
				</ul>

			</div>

		</div>

		<div class="wrap">

			<div class="ilr-settings-container">

				<form method="post" action="options.php">

					<?php

						settings_fields( 'ilr_options' );

						$this->print_options_nav();

						?>

						<div class="general add-on <?php if ( $this->tab && 'general' !== $this->tab ) { echo 'hidden'; } ?>">

							<?php do_settings_sections( 'invalid-login-redirect' ); ?>

						</div>

						<div class="add-ons add-on <?php if ( ( $this->tab && 'add-ons' !== $this->tab ) || ! $this->tab ) { echo 'hidden'; } ?>">

							<?php do_settings_sections( 'invalid-login-redirect-addons' ); ?>

						</div>

						<?php

						submit_button();

					?>

				</form>

			</div>

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
			false,
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

		register_setting(
			'ilr_options_addons',
			'invalid-login-redirect-addons',
			[ $this, 'sanitize' ]
		);

		add_settings_section(
			'invalid_login_redirect_addons_section',
			'',
			false,
			'invalid-login-redirect-addons'
		);

		add_settings_field(
			'ilr_addons',
			__( 'Add-Ons', 'invalid-login-redirect' ),
			[ $this, 'ilr_addons_callback' ],
			'invalid-login-redirect-addons',
			'invalid_login_redirect_addons_section'
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

		$new_input['redirect_url']      = ! empty( $input['redirect_url'] ) ? sanitize_url( $input['redirect_url'] ) : site_url( 'wp-login.php?action=lostpassword' );
		$new_input['login_limit']       = ! empty( $input['login_limit'] ) ? absint( $input['login_limit'] ) : $this->options['login_limit'];
		$new_input['error_text']        = isset( $input['error_text'] ) ? trim( $input['error_text'] ) : $this->options['error_text'];
		$new_input['error_text_border'] = isset( $input['error_text_border'] ) ? sanitize_text_field( $input['error_text_border'] ) : $this->options['error_text_border'];
		$new_input['addons']            = isset( $input['addons'] ) ? (array) $input['addons'] : [];

		return $new_input;

	}

	/**
	* Print the options navigation
	*
	* @return mixed
	*
	* @since 0.0.1
	*/
	public function print_options_nav() {

		printf(
			'<div class="ilr-notice ilr-navigation">
				<ul class="nav-tab-list">
					<li class="%1$s option-nav-tab"><a class="option-tab-link" data-tab="general" href="?page=invalid-login-redirect&tab=general">%2$s</a></li>
					<li class="%3$s option-nav-tab"><a class="option-tab-link" data-tab="add-ons" href="?page=invalid-login-redirect&tab=add-ons">%4$s</a></li>
				</ul>
			</div>',
			( ( $this->tab && 'general' === $this->tab ) || ! $this->tab ) ? 'is-selected' : '',
			__( 'General', 'invalid-login-redirect' ),
			( $this->tab && 'add-ons' === $this->tab ) ? 'is-selected' : '',
			__( 'Add-Ons', 'invalid-login-redirect' )
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

	/**
	 * Render the add-ons section
	 *
	 * @return mixed
	 *
	 * @since 1.0.0
	 */
	public function ilr_addons_callback() {

		$iteration = 1;

		foreach ( $this->get_ilr_addons() as $addon_name => $addon_data ) {

			if ( 1 === $iteration ) {

				print( '<div class="section group">' );

			}

			printf(
				'<div class="col %1$s">
					<div class="checkbox-toggle">
						<input class="tgl tgl-skewed" name="invalid-login-redirect[addons][%2$s]" id="invalid-login-redirect[addons][]" type="checkbox" value="%3$s" %4$s />
						<label class="tgl-btn" data-tg-off="OFF" data-tg-on="ON" for="invalid-login-redirect[addons][]"></label>
					</div>
					<h4>%5$s</h4>
					<p>%6$s</p>
				</div>',
				esc_attr( "span_{$iteration}_of_4" ),
				esc_attr( sanitize_title( $addon_name ) ),
				esc_attr( $addon_data['file'] ),
				checked( array_key_exists( sanitize_title( $addon_name ), $this->options['addons'] ), 1, false ),
				esc_html( $addon_name ),
				esc_html( $addon_data['description'] )
			);

			if ( 4 === $iteration ) {

				print( '</div>' );

			}

			$iteration++;

		}

	}

	/**
	 * Get the list of add-ons that a user can activate
	 *
	 * @return array
	 *
	 * @since 1.0.0
	 */
	public function get_ilr_addons() {

		return [
			__( 'Logging', 'invalid-login-redirect' ) => [
				'banner'      => '',
				'file'        => 'class-logging.php',
				'description' => __( 'Start logging each time an invalid user attempts to login.', 'invalid-login-redirect' ),
			],
		];

	}

}
