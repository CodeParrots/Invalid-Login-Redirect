<?php
/**
 * Notifications Module
 *
 * @author Code Parrots <support@codeparrots.com>
 *
 * @since 1.0.0
 */
final class Invalid_Login_Redirect_Prevent_Logins extends Invalid_Login_Redirect {

	/**
	 * The options instance
	 *
	 * @var array
	 */
	private $options;

	private $class_slug = 'prevent-logins';

	public function __construct( $options ) {

		$this->options = $options;

		add_filter( 'ilr_options_nav_items', [ $this, 'option_nav_item' ] );
		add_action( 'ilr_options_section',   [ $this, 'option_section' ] );
		add_filter( 'ilr_sanitize_options',  [ $this, 'sanitize_options' ] );

		add_action( 'wp_login',   [ $this, 'check_ip_address' ], 8, 2 );
		add_action( 'admin_init', [ $this, 'check_ip_address' ], 10, 2 );

		add_action( 'wp_enqueue_scripts', [ $this, 'load_notice_scripts' ] );

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

		$nav_items[] = __( 'Prevent Logins', 'invalid-login-redirect' );

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

		<div class="<?php echo $this->class_slug; ?> add-on <?php if ( ( $tab && $this->class_slug !== $tab ) || ! $tab ) { echo 'hidden'; } ?>">

			<div class="ilr-notice col">

				<p class="description">

					<?php

						_e( 'Prevent logins.', 'invalid-login-redirect' );

					?>

				</p>

			</div>

			<?php

			printf(
				'<div class="col ilr-notice">

					<div class="fields">

						<label for="invalid-login-redirect[addons][%1$s][options][blacklist]">%2$s</label>
						<textarea id="invalid-login-redirect[addons][%1$s][options][blacklist]" name="invalid-login-redirect[addons][%1$s][options][blacklist]" class="widefat" placeholder="192.68.1.*" />%3$s</textarea>
						<p class="descipriton">%4$s</p>

					</div>

				</div>',
				esc_attr( $this->class_slug ),
				__( 'Blacklist', 'invalid-login-redirect' ),
				$this->prevent_login_option( 'blacklist' ),
				esc_html__( 'Some description here.', 'invalid-login-redirect' )
			);

			printf(
				'<div class="col ilr-notice">

					<div class="fields">

						<label for="invalid-login-redirect[addons][%1$s][options][whitelist]">%2$s</label>
						<textarea id="invalid-login-redirect[addons][%1$s][options][whitelist]" name="invalid-login-redirect[addons][%1$s][options][whitelist]" class="widefat" placeholder="192.68.1.*" />%3$s</textarea>
						<p class="descipriton">%4$s</p>

					</div>

				</div>',
				esc_attr( $this->class_slug ),
				__( 'Whitelist', 'invalid-login-redirect' ),
				$this->prevent_login_option( 'whitelist' ),
				esc_html__( 'Some description here.', 'invalid-login-redirect' )
			);

			submit_button( esc_html__( 'Save Settings', 'invalid-login-redirect' ) );

			?>

		</div>

		<?php

	}

	/**
	 * Sanitize our options
	 *
	 * @param array $input The submitted options array
	 *
	 * @return array
	 *
	 * @since 1.0.0
	 */
	public function sanitize_options( $input ) {

		foreach ( $input['addons'][ $this->class_slug ]['options'] as $option => $value ) {

			if ( in_array( $option, [ 'blacklist', 'whitelist' ] ) ) {

				$value = array_filter( array_map( 'sanitize_text_field', explode( ',', $value ) ), 'strlen' );

			}

			$input['addons'][ $this->class_slug ]['options'][ $option ] = ! empty( $value ) ? $value : '';

		}

		return $input;

	}

	/**
	 * Check the logged in user IP against our lists
	 *
	 * @param string $username The username entered to login
	 * @param stdObj $user     The user object
	 *
	 * @return bool
	 *
	 * @since 1.0.0
	 */
	public function check_ip_address( $username = false, $user = false ) {

		if ( ! $username && ! $user ) {

			$user = wp_get_current_user();

			$username = ( ! $user ) ? __( 'Not Logged', 'invalid-login-redirect' ) : $user->user_login;

		}

		if ( in_array( self::$helpers->get_user_ip(), $this->prevent_login_option( 'blacklist', true ) ) ) {

			if ( self::$helpers->is_addon_enabled( 'logging' ) ) {

				self::$helpers->log_entry( [
					'username' => $username,
					'type'     => 'blocked_login',
				] );

			}

			wp_logout();

			wp_redirect( add_query_arg( 'blocked', true, site_url() ) );

			exit;

		}

		if ( in_array( self::$helpers->get_user_ip(), $this->prevent_login_option( 'whitelist', true ) ) ) {

			wp_die( 'whitelist' );

		}

	}

	/**
	 * Enqueue our scripts to display a notice back to the user
	 *
	 * @since 1.0.0
	 */
	public function load_notice_scripts() {

		if ( ! filter_input( INPUT_GET, 'blocked', FILTER_SANITIZE_NUMBER_FLOAT ) ) {

			return;

		}

		wp_enqueue_style( 'ilr-notifications', ILR_URL . 'modules/partials/css/ilr-notifications' . ILR_SCRIPT_SUFFIX . '.css', [], ILR_VERSION );

		wp_enqueue_script( 'jquery-growl', ILR_URL . 'modules/partials/js/jquery-growl' . ILR_SCRIPT_SUFFIX . '.js', [ 'jquery' ], ILR_VERSION, true );

		wp_enqueue_script( 'ilr-notifications', ILR_URL . 'modules/partials/js/ilr-notifications' . ILR_SCRIPT_SUFFIX . '.js', array( 'jquery-growl' ), ILR_VERSION, true );

		wp_localize_script( 'ilr-notifications', 'prevent_login', [
			'title' => sprintf(
				__( '%s Blocked Entry', 'invalid-login-redirect' ),
				'&times;'
			),
			'text'  => sprintf(
				__( 'Your IP address <small>(%s)</small> has been blocked from entering the site. If you believe this is an error, please <a href="#" style="color:white;text-decoration:underline;">contact the site administrator</a>.', 'invalid-login-redirect' ),
				parent::$helpers->get_user_ip()
			),
		] );

	}

	/**
	 * Return an option from the prevent login options array
	 *
	 * @param  string $name Name of option to return
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public function prevent_login_option( $name = false, $array = false ) {

		if (
			! $name ||
			! isset( $this->options['addons'][ $this->class_slug ]['options'][ $name ] ) ||
			empty( $this->options['addons'][ $this->class_slug ]['options'][ $name ] )
		) {

			return ( $array ) ? [] : '';

		}

		if ( is_array( $this->options['addons'][ $this->class_slug ]['options'][ $name ] ) ) {

			if ( $array ) {

				return $this->options['addons'][ $this->class_slug ]['options'][ $name ];

			}

			return implode( ",\n", $this->options['addons'][ $this->class_slug ]['options'][ $name ] );

		}

		return $this->options['addons'][ $this->class_slug ]['options'][ $name ];

	}

}

$invalid_login_redirect_prevent_logins = new Invalid_Login_Redirect_Prevent_Logins( $this->options );
