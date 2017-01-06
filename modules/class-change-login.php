<?php
/**
 * Change the /wp-login.php URL
 *
 * @author Code Parrots <support@codeparrots.com>
 *
 * @note The original code for this class/module was borrowed from Rename wp-login.php
 * @link https://wordpress.org/plugins/rename-wp-login/
 *
 * @since 1.0.0
 */
final class Invalid_Login_Redirect_Change_Login extends Invalid_Login_Redirect {

	private $options;

	private $wp_login_php;

	private $class_slug = 'change-login-url';

	public function __construct( $options ) {

		$this->options = $options;

		add_filter( 'ilr_options_nav_items', [ $this, 'option_nav_item' ] );
		add_action( 'ilr_options_section',   [ $this, 'option_section' ] );
		add_filter( 'ilr_sanitize_options',  [ $this, 'sanitize_options' ] );

		if ( 'wp-login.php' !== $this->redirect_slug() ) {

			$this->custom_login_url();

		}

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

		$nav_items[] = __( 'Change Login URL', 'invalid-login-redirect' );

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

						_e( 'Change the URL to your sites login page.', 'invalid-login-redirect' );

					?>

				</p>

			</div>

			<?php

			printf(
				'<div class="col ilr-notice">

					<div class="fields">

						<label for="invalid-login-redirect[addons][%1$s][options][url]">%2$s</label>
						%3$s<input type="text" id="invalid-login-redirect[addons][%1$s][options][url]" name="invalid-login-redirect[addons][%1$s][options][url]" class="login-url" value="%4$s" placeholder="%5$s" />

					</div>

				</div>',
				esc_attr( $this->class_slug ),
				__( 'Login URL', 'invalid-login-redirect' ),
				'<code>' . trailingslashit( home_url() ) . '</code>',
				esc_attr( $this->redirect_slug() ),
				'wp-login.php'
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

		if ( ! isset( $input['addons'][ $this->class_slug ]['options'] ) ) {

			return $input;

		}

		$input['addons'][ $this->class_slug ]['options']['url'] = ( isset( $input['addons'][ $this->class_slug ]['options']['url'] ) && ! empty( $input['addons'][ $this->class_slug ]['options']['url'] ) ) ? sanitize_text_field( $input['addons'][ $this->class_slug ]['options']['url'] ) : 'wp-login.php';

		return $input;

	}

	/**
	 * Initialize the hooks for the custom login URL
	 *
	 * @since 1.0.0
	 */
	public function custom_login_url() {

		add_action( 'wp_loaded',                 [ $this, 'wp_loaded' ] );

		add_filter( 'ilr_login_url',             [ $this, 'filter_wp_login_php' ] );
		add_filter( 'site_url',                  [ $this, 'site_url' ], 10, 4 );
		add_filter( 'network_site_url',          [ $this, 'network_site_url' ], 10, 3 );
		add_filter( 'wp_redirect',               [ $this, 'wp_redirect' ], 10, 2 );
		add_filter( 'site_option_welcome_email', [ $this, 'welcome_email' ] );

		remove_action( 'template_redirect', 'wp_redirect_admin_locations', 1000 );

	}

	/**
	 * Redirect the User to the appropriate page
	 *
	 * @return bool
	 *
	 * @since 1.0.0
	 */
	public function wp_loaded() {

		if ( is_admin() && ! is_user_logged_in() && ! defined( 'DOING_AJAX' ) ) {

			wp_die(
				__( 'You must log in to access the admin area.', 'invalid-login-redirect' ),
				'',
				[ 'response' => 403 ]
			);

		}

		$pagenow = $this->get_pagenow();

		$request = parse_url( $_SERVER['REQUEST_URI'] );

		if (
			'wp-login.php' === $pagenow &&
			$this->trailing_slash_url( $request['path'] ) !== $this->trailing_slash_url( $request['path'] ) &&
			get_option( 'permalink_structure' )
		) {

			wp_safe_redirect( $this->trailing_slash_url( site_url() ) );

			return;

		}

		if ( $this->wp_login_php ) {

			if (
				( $referer = wp_get_referer() ) &&
				strpos( $referer, 'wp-activate.php' ) !== false &&
				( $referer = parse_url( $referer ) ) &&
				! empty( $referer['query'] )
			) {

				parse_str( $referer['query'], $referer );

				if (
					! empty( $referer['key'] ) &&
					( $result = wpmu_activate_signup( $referer['key'] ) ) &&
					is_wp_error( $result ) && (
						$result->get_error_code() === 'already_active' ||
						$result->get_error_code() === 'blog_taken'
				) ) {

					wp_safe_redirect( $this->custom_login_url() . ( ! empty( $_SERVER['QUERY_STRING'] ) ? '?' . $_SERVER['QUERY_STRING'] : '' ) );

					die;

				} // @codingStandardsIgnoreLine

			}

			$this->template_loader();

			return;

		}

		if ( 'wp-login.php' === $pagenow ) {

			global $error, $interim_login, $action, $user_login;

			require_once ABSPATH . 'wp-login.php';

			die;

		}

	}

	/**
	 * Set the $pagenow global
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public function get_pagenow() {

		global $pagenow;

		$request = parse_url( $_SERVER['REQUEST_URI'] );

		/**
		 * - If URL contains wp-login.php OR
		 * - URL path (eg: /$this->redirect_slug()) equals /wp-login.php AND
		 * - is not dashboard
		 */
		if ( (
				strpos( $_SERVER['REQUEST_URI'], 'wp-login.php' ) !== false ||
				untrailingslashit( $request['path'] ) === site_url( 'wp-login', 'relative' )
			) &&
			! is_admin()
		) {

			$this->wp_login_php     = true;
			$_SERVER['REQUEST_URI'] = $this->trailing_slash_url( '/' . str_repeat( '-/', 10 ) );
			$pagenow                = 'index.php';

		}

		/**
		* - URL path (eg: /$this->redirect_slug()) equals /wp-login.php OR
		* - Permalink === 'plain' AND is not admin
		 */
		if (
			untrailingslashit( $request['path'] ) === home_url( $this->redirect_slug(), 'relative' ) ||
			(
				! get_option( 'permalink_structure' ) &&
				! is_admin()
			)
		) {

			$pagenow = 'wp-login.php';

		}

		return $pagenow;

	}

	/**
	 * Filter the login URL
	 *
	 * @param  string $url    The login URL
	 * @param  string $scheme The URL scheme type
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public function filter_wp_login_php( $url, $scheme = false ) {

		if ( strpos( $url, 'wp-login.php' ) !== false ) {

			$scheme = is_ssl() ? 'https' : 'http';

			$args = explode( '?', $url );

			if ( isset( $args[1] ) ) {

				parse_str( $args[1], $args );

				$url = add_query_arg( $args, $this->custom_login_url( $scheme ) );

			} else {

				$url = $this->custom_login_url( $scheme );

			} // @codingStandardsIgnoreLine

		}

		return $url;

	}

	/**
	 * Decide if URL needs trailing slash
	 *
	 * @param  string $string String to alter
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	private function trailing_slash_url( $string ) {

		$trailing_slases = '/' === substr( get_option( 'permalink_structure' ), -1, 1 );

		return $trailing_slases ? trailingslashit( $string ) : untrailingslashit( $string );

	}

	/**
	 * Include the WordPress template loader
	 *
	 * @return mixed
	 *
	 * @since 1.0.0
	 */
	private function template_loader() {

		$pagenow = $this->get_pagenow();

		if ( ! defined( 'WP_USE_THEMES' ) ) {

			define( 'WP_USE_THEMES', true );

		}

		if ( $_SERVER['REQUEST_URI'] === $this->trailing_slash_url( str_repeat( '-/', 10 ) ) ) {

			$_SERVER['REQUEST_URI'] = $this->trailing_slash_url( '/wp-login-php/' );

		}

		wp();

		require_once ABSPATH . WPINC . '/template-loader.php';

		exit;

	}

	/**
	 * Setup the new login URL
	 *
	 * @param  string $scheme URL scheme
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public function custom_login_url( $scheme = null ) {

		return $this->trailing_slash_url( home_url( '/', $scheme ) . $this->redirect_slug() );

	}

	/**
	 * Filter the site URL
	 *
	 * @param  string  $url     Site URL
	 * @param  string  $path    Path string
	 * @param  string  $scheme  URL scheme
	 * @param  integer $blog_id Blog ID
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public function site_url( $url, $path, $scheme, $blog_id ) {

		return $this->filter_wp_login_php( $url, $scheme );

	}

	/**
	 * Filter the network site URL
	 *
	 * @param  string  $url     Site URL
	 * @param  string  $path    Path string
	 * @param  string  $scheme  URL scheme
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public function network_site_url( $url, $path, $scheme ) {

		return $this->filter_wp_login_php( $url, $scheme );

	}

	/**
	 * Filter the redirect location
	 *
	 * @param  string $location Location to redirect to
	 * @param  string $status   HTTP status code
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public function wp_redirect( $location, $status ) {

		return $this->filter_wp_login_php( $location );

	}

	/**
	 * Return the redirect slug
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public function redirect_slug() {

		return ( isset( $this->options['addons'][ $this->class_slug ]['options']['url'] ) && ! empty( $this->options['addons'][ $this->class_slug ]['options']['url'] ) ) ? sanitize_text_field( $this->options['addons'][ $this->class_slug ]['options']['url'] ) : 'wp-login.php';

	}

	/**
	 * Filter the welcome email login URL
	 *
	 * @param  string $vlaue The welcome email text
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public function welcome_email( $value ) {

		return str_replace( 'wp-login.php', $this->trailing_slash_url( $this->redirect_slug() ), $value );

	}

}

$invalid_login_redirect_change_login = new Invalid_Login_Redirect_Change_Login( $this->options );
