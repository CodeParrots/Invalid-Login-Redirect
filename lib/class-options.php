<?php
/**
 * Invalid Login Redirect Settings
 *
 * @since 0.0.1
 */
class Invalid_Login_Redirect_Settings {

	private $options;

	private $tab;

	public function __construct( $options ) {

		if ( ! is_admin() ) {

			return;

		}

		$this->options = $options;
		$this->tab     = filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_STRING );

		add_action( 'admin_menu', [ $this, 'add_plugin_page' ] );

		add_action( 'admin_init', [ $this, 'page_init' ] );

		add_action( 'ilr_before_options', [ $this, 'options_notice' ] );

	}


	/**
	 * Add our custom options page
	 *
	 * @since 0.0.1
	*/
	public function add_plugin_page() {

		add_submenu_page(
			'tools.php',
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

		wp_enqueue_style( 'ilr-admin', ILR_URL . '/lib/css/ilr-admin' . ILR_STYLE_SUFFIX . '.css' );

		wp_enqueue_style( 'wp-color-picker' );

		wp_enqueue_script( 'ilr-admin', ILR_URL . '/lib/js/ilr-admin' . ILR_SCRIPT_SUFFIX . '.js', array( 'wp-color-picker' ), true, ILR_VERSION );

		wp_add_inline_style( 'ilr-admin', ".ilr_message.invalid {
			border-color: {$this->options['error_text_border']};
		}" );

		?>

		<div class="ilr-banner">

			<div class="inner-container">

				<div class="logo-container">

					<a class="logo-link"><img src="<?php echo ILR_IMAGES . 'logo.png'; ?>" class="logo" /></a>

					<?php

					printf(
						'<span class="badge version">v%s</span>',
						ILR_VERSION
					);

					if ( INVALID_LOGIN_REDIRECT_DEVELOPER ) {

						printf(
							'<span class="badge developer">%s</span>',
							esc_html__( 'Developer', 'invalid-login-redirect' )
						);

					}

					?>

				</div>

				<ul class="links">
					<li><a href="#"><?php esc_html_e( 'Need Help?', 'invalid-login-redirect' ); ?></a></li>
					<li><a href="#"><?php esc_html_e( 'Leave a Review', 'invalid-login-redirect' ); ?></a></li>
				</ul>

			</div>

		</div>

		<div class="wrap">

			<div class="ilr-settings-container">

				<form method="post" action="options.php">

					<?php

						if ( INVALID_LOGIN_REDIRECT_DEVELOPER ) { // @codingStandardsIgnoreLine

							printf(
								'<div class="ilr-notice developer-notice">
									<div class="icon"><span class="dashicons dashicons-admin-tools"></span></div>
									<div class="content">
										<div class="text">%s</div>
									</div>
								</div>',
								sprintf(
									__( 'Currently in %1$s via the %2$s constant.', 'invalid-login-redirect' ),
									'<em>' . __( 'Developer Mode', 'invalid-login-redirect' ) . '</em>',
									'<code>INVALID_LOGIN_REDIRECT_DEVELOPER</code>'
								)
							);

						} // @codingStandardsIgnoreLine

						settings_fields( 'ilr_options' );

						$this->print_options_nav();

						do_action( 'ilr_before_options' );

						?>

						<div class="general add-on <?php if ( $this->tab && 'general' !== $this->tab ) { echo 'hidden'; } ?>">

							<?php do_settings_sections( 'invalid-login-redirect' ); ?>

							<?php submit_button( esc_html__( 'Save Settings', 'invalid-login-redirect' ) ); ?>

						</div>

						<div class="add-ons add-on <?php if ( ( $this->tab && 'add-ons' !== $this->tab ) || ! $this->tab ) { echo 'hidden'; } ?>">

							<?php do_settings_sections( 'invalid-login-redirect-addons' ); ?>

							<?php submit_button( esc_html__( 'Save Settings', 'invalid-login-redirect' ) ); ?>

						</div>

						<?php

						do_action( 'ilr_options_section', $this->tab );

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
	 * Display the success/error messages
	 *
	 * @return mixed
	 *
	 * @since 1.0.0
	 */
	public function options_notice() {

		$settings_updated = filter_input( INPUT_GET, 'settings-updated', FILTER_SANITIZE_STRING );

		if ( $settings_updated ) {

			printf(
				'<div class="notice notice-success is-dismissible">
					<p>%s</p>
				</div>',
				esc_html__( 'Settings saved.', 'invalid-login-redirect' )
			);

		}

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

		$new_input['redirect_url']      = ! empty( $input['redirect_url'] ) ? esc_url_raw( $input['redirect_url'] ) : site_url( Invalid_Login_Redirect::$login_url . '?action=lostpassword' );
		$new_input['login_limit']       = ! empty( $input['login_limit'] ) ? absint( $input['login_limit'] ) : $this->options['login_limit'];
		$new_input['error_text']        = isset( $input['error_text'] ) ? trim( $input['error_text'] ) : $this->options['error_text'];
		$new_input['error_text_border'] = isset( $input['error_text_border'] ) ? sanitize_text_field( $input['error_text_border'] ) : $this->options['error_text_border'];

		foreach ( $input['addons'] as $addon => $data ) {

			if ( ! isset( $data['file'] ) ) {

				unset( $input['addons'][ $addon ] );

			} // @codingStandardsIgnoreLine

		}

		$new_input['addons'] = (array) $input['addons'];

		return apply_filters( 'ilr_sanitize_options', $new_input );

	}

	/**
	* Print the options navigation
	*
	* @return mixed
	*
	* @since 0.0.1
	*/
	public function print_options_nav() {

		$nav_items = apply_filters( 'ilr_options_nav_items', [
			__( 'General', 'invalid-login-redirect' ),
			__( 'Add-Ons', 'invalid-login-redirect' ),
		] );

		?>

		<div class="ilr-notice ilr-navigation">

			<ul class="nav-tab-list">

				<?php

				foreach ( $nav_items as $item ) {

					$slug = sanitize_title( $item );

					printf(
						'<li class="%1$s option-nav-tab"><a class="option-tab-link" data-tab="%2$s" href="?page=invalid-login-redirect&tab=%2$s">%3$s</a></li>',
						( ( $this->tab && $slug === $this->tab ) || ( ! $this->tab && __( 'General', 'invalid-login-redirect' ) === $item ) ) ? 'is-selected' : '',
						esc_attr( $slug ),
						esc_html( $item )
					);

				}

				?>

			</ul>

		</div>

		<?php

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
			'<input type="text" placeholder="' . site_url( Invalid_Login_Redirect::$login_url . '?action=lostpassword' ) . '" class="widefat" id="redirect_url" name="invalid-login-redirect[redirect_url]" value="%1$s" /><p class="description">%2$s</p>',
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

		foreach ( $this->get_ilr_addons() as $addon_name => $addon_data ) {

			$sub_options = '';

			if ( isset( $addon_data['sub_options'] ) && is_array( $addon_data['sub_options'] ) ) {

				$sub_options .= sprintf(
					'<div class="sub-options %s">',
					esc_attr( array_key_exists( sanitize_title( $addon_name ), $this->options['addons'] ) ? '' : 'hidden' )
				);

				foreach ( $addon_data['sub_options'] as $label => $option_data ) {

					$checked = '';

					if ( isset( $this->options['addons'][ sanitize_title( $addon_name ) ]['options'] ) ) {

						if (
							isset( $this->options['addons'][ sanitize_title( $addon_name ) ]['options'][ $option_data['id'] ] ) &&
							$this->options['addons'][ sanitize_title( $addon_name ) ]['options'][ $option_data['id'] ]
						) {

							$checked = 'checked="checked"';

						} // @codingStandardsIgnoreLine

					}

					$sub_options .= sprintf(
						'<div class="row">
							%1$s
							<div class="checkbox-toggle">
								<input class="tgl tgl-skewed" name="invalid-login-redirect[addons][%2$s][options][%3$s]" id="invalid-login-redirect[addons][%2$s][options][%3$s]" type="checkbox" value="1" %4$s />
								<label class="tgl-btn" data-tg-off="OFF" data-tg-on="ON" for="invalid-login-redirect[addons][%2$s][options][%3$s]"></label>
							</div>
							<p class="description">%5$s</p>
						</div>',
						$label,
						esc_attr( sanitize_title( $addon_name ) ),
						esc_attr( $option_data['id'] ),
						$checked,
						$option_data['description']
					);

				}

				$sub_options .= '</div>';

			}

			$in_progress = ( ( isset( $addon_data['in_progress'] ) && $addon_data['in_progress'] ) );

			if ( INVALID_LOGIN_REDIRECT_DEVELOPER ) {

				$in_progress = false;

			}

			printf(
				'<div class="col ilr-notice %1$s">
					<div class="checkbox-toggle">
						<input class="tgl tgl-skewed %2$s" name="invalid-login-redirect[addons][%3$s][file]" id="invalid-login-redirect[addons][%3$s][file]" type="checkbox" value="%4$s" %5$s %6$s />
						<label class="tgl-btn" data-tg-off="OFF" data-tg-on="ON" for="invalid-login-redirect[addons][%3$s][file]"></label>
					</div>
					<h3>%7$s</h3>
					<p class="description">%8$s</p>
					%9$s
					%10$s
				</div>',
				$in_progress ? 'in-progress' : '',
				empty( $sub_options ) ? '' : 'has-sub-options_js',
				esc_attr( sanitize_title( $addon_name ) ),
				esc_attr( $addon_data['file'] ),
				checked( array_key_exists( sanitize_title( $addon_name ), $this->options['addons'] ), 1, false ),
				$in_progress ? 'disabled="disabled"' : '',
				esc_html( $addon_name ) . ( $in_progress ? '<span class="badge in-progress">' . __( 'in progress', 'invalid-login-redirect' ) . '</span>' : '' ),
				esc_html( $addon_data['description'] ),
				! empty( $addon_data['notice'] ) ? '<p class="description notice">' . $addon_data['notice'] . '</p>' : '',
				$sub_options
			);

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
				'file'        => 'class-logging.php',
				'description' => __( 'Start logging each time an invalid user attempts to login.', 'invalid-login-redirect' ),
				'notice'      => ! username_exists( 'admin' ) ? sprintf(
					__( '%1$s Warning: The username %2$s is registered on your site.', 'invalid-login-redirect' ),
					'<span class="dashicons dashicons-warning"></span>',
					'<strong>admin</strong>'
				) : '',
				'sub_options' => [
					__( 'Invalid Passwords', 'invalid-login-redirect' ) => [
						'id'          => 'incorrect_password',
						'description' => __( 'Log invalid password entries. This will only log entries for registered users who enter invalid passwords.', 'invalid-login-redirect' ),
					],
					__( 'Invalid Usernames', 'invalid-login-redirect' ) => [
						'id'          => 'invalid_username',
						'description' => __( 'Log etnries for users who try and login with usernames that have not yet been registered on the site.', 'invalid-login-redirect' ),
					],
					__( 'Successful Logins', 'invalid-login-redirect' ) => [
						'id'          => 'successful_login',
						'description' => __( 'Log entries each time users successfully log in to the site.', 'invalid-login-redirect' ),
					],
					sprintf( __( 'Dashboard Widget %s', 'invalid-login-redirect' ), '<span class="badge widget">' . __( 'widget', 'invalid-login-redirect' ) . '</span>' ) => [
						'id'          => 'dashboard_widget',
						'description' => __( 'This activates the dashboard widget so that users can easily see login attempts as soon as they enter the site.', 'invalid-login-redirect' ),
					],
				],
			],
			__( 'User Role Redirects', 'invalid-login-redirect' ) => [
				'file'        => 'class-user-role-redirects.php',
				'description' => __( 'Redirect specific user roles to certain areas of your site after a successful login.', 'invalid-login-redirect' ),
			],
			__( 'Notifications', 'invalid-login-redirect' ) => [
				'file'        => 'class-notifications.php',
				'description' => __( 'Display a notice to the admin users whenever a logged action occurs.', 'invalid-login-redirect' ),
			],
			__( 'Email Notifications', 'invalid-login-redirect' ) => [
				'file'        => 'class-email-notification.php',
				'description' => __( 'Emails the admin user whenever a login  occurs.', 'invalid-login-redirect' ),
			],
			__( 'Change Login URL', 'invalid-login-redirect' ) => [
				'file'        => 'class-change-login.php',
				'description' => __( 'Alter the default WordPress login URL. Create a more memorable URL to make logging in hassle free.', 'invalid-login-redirect' ),
				'in_progress' => true,
			],
			__( 'Prevent Logins', 'invalid-login-redirect' ) => [
				'file'        => 'class-prevent-logins.php',
				'description' => __( 'Prevent specific users and IP addresses from logging into the site.', 'invalid-login-redirect' ),
				'in_progress' => true,
			],
		];

	}

}
