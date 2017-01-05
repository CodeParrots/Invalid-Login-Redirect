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

		if ( 'wp-login.php' === $this->redirect_slug() ) {

			return;

		}

		add_action( 'wp_loaded', array( $this, 'plugins_loaded' ), 1 );

		add_action( 'wp_loaded', array( $this, 'wp_loaded' ) );

		add_filter( 'ilr_login_url', array( $this, 'filter_login_url' ) );

		add_filter( 'site_url', array( $this, 'site_url' ), 10, 4 );

		add_filter( 'network_site_url', array( $this, 'network_site_url' ), 10, 3 );

		add_filter( 'wp_redirect', array( $this, 'wp_redirect' ), 10, 2 );

		add_filter( 'site_option_welcome_email', array( $this, 'welcome_email' ) );

		remove_action( 'template_redirect', 'wp_redirect_admin_locations', 1000 );

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
	 * @return array
	 */
	public function sanitize_options( $input ) {

		if ( ! isset( $input['addons'][ $this->class_slug ]['options'] ) ) {

			return $input;

		}

		$input['addons'][ $this->class_slug ]['options']['url'] = ( isset( $input['addons'][ $this->class_slug ]['options']['url'] ) && ! empty( $input['addons'][ $this->class_slug ]['options']['url'] ) ) ? sanitize_text_field( $input['addons'][ $this->class_slug ]['options']['url'] ) : 'wp-login.php';

		return $input;

	}

	private function use_trailing_slashes() {

		return '/' === substr( get_option( 'permalink_structure' ), -1, 1 );

	}

	private function user_trailingslashit( $string ) {

		return $this->use_trailing_slashes() ? trailingslashit( $string ) : untrailingslashit( $string );

	}

	private function wp_template_loader() {

		global $pagenow;

		$pagenow = 'index.php';

		if ( ! defined( 'WP_USE_THEMES' ) ) {

			define( 'WP_USE_THEMES', true );

		}

		wp();

		if ( $_SERVER['REQUEST_URI'] === $this->user_trailingslashit( str_repeat( '-/', 10 ) ) ) {

			$_SERVER['REQUEST_URI'] = $this->user_trailingslashit( '/wp-login-php/' );

		}

		require_once ABSPATH . WPINC . '/template-loader.php';

		die;

	}

	private function new_login_slug() {

		if (
			( $slug = $this->redirect_slug() ) ||
			( 'login' === $slug )
		) {

			return $slug;

		}

	}

	public function new_login_url( $scheme = null ) {

		return home_url( '/', $scheme ) . $this->new_login_slug();

	}

	public function plugins_loaded() {

		global $pagenow;

		$request = parse_url( $_SERVER['REQUEST_URI'] );

		if ( (
				strpos( $_SERVER['REQUEST_URI'], 'wp-login.php' ) !== false ||
				untrailingslashit( $request['path'] ) === site_url( 'wp-login', 'relative' )
			) &&
			! is_admin()
		) {

			$this->wp_login_php = true;

			$_SERVER['REQUEST_URI'] = $this->user_trailingslashit( '/' . str_repeat( '-/', 10 ) );

			$pagenow = 'index.php';

		}

		if (
			untrailingslashit( $request['path'] ) === home_url( $this->new_login_slug(), 'relative' ) || (
				! get_option( 'permalink_structure' ) &&
				isset( $_GET[ $this->new_login_slug() ] ) &&
				empty( $_GET[ $this->new_login_slug() ] )
			)
		) {

			$pagenow = 'wp-login.php';

		}

	}

	public function wp_loaded() {

		global $pagenow;

		if ( is_admin() && ! is_user_logged_in() && ! defined( 'DOING_AJAX' ) ) {

			wp_die( __( 'You must log in to access the admin area.', 'rename-wp-login' ), '', array( 'response' => 403 ) );

		}

		$request = parse_url( $_SERVER['REQUEST_URI'] );

		if (
			'wp-login.php' === $pagenow &&
			$this->user_trailingslashit( $request['path'] ) !== $this->user_trailingslashit( $request['path'] ) &&
			get_option( 'permalink_structure' )
		) {

			wp_safe_redirect( $this->user_trailingslashit( site_url() ) );

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

					wp_safe_redirect( $this->new_login_url() . ( ! empty( $_SERVER['QUERY_STRING'] ) ? '?' . $_SERVER['QUERY_STRING'] : '' ) );

					die;

				} // @codingStandardsIgnoreLine

			}

			$this->wp_template_loader();

			return;

		}

		if ( 'wp-login.php' === $pagenow ) {

			global $error, $interim_login, $action, $user_login;

			@require_once ABSPATH . 'wp-login.php';

			die;

		}

	}

	public function filter_login_url( $url ) {

		return $this->filter_wp_login_php( $url );

	}

	/**
	 * Filter the site URL
	 *
	 * @param  string  $url     [description]
	 * @param  string  $path    [description]
	 * @param  string  $scheme  [description]
	 * @param  integer $blog_id [description]
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
	 * @param  string $url    [description]
	 * @param  string $path   [description]
	 * @param  string $scheme [description]
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
	 * @param  string $location [description]
	 * @param  string $status   [description]
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public function wp_redirect( $location, $status ) {

		return $this->filter_wp_login_php( $location );

	}

	/**
	 * Filter the login URL
	 *
	 * @param  string $url    [description]
	 * @param  string $scheme [description]
	 *
	 * @return string
	 */
	public function filter_wp_login_php( $url, $scheme = false ) {

		if ( strpos( $url, 'wp-login.php' ) !== false ) {

			$scheme = is_ssl() ? 'https' : 'http';

			$args = explode( '?', $url );

			if ( isset( $args[1] ) ) {

				parse_str( $args[1], $args );

				$url = add_query_arg( $args, $this->new_login_url( $scheme ) );

			} else {

				$url = $this->new_login_url( $scheme );

			} // @codingStandardsIgnoreLine

		}

		return $url;

	}

	public function redirect_slug() {

		return ( isset( $this->options['addons'][ $this->class_slug ]['options']['url'] ) && ! empty( $this->options['addons'][ $this->class_slug ]['options']['url'] ) ) ? sanitize_text_field( $this->options['addons'][ $this->class_slug ]['options']['url'] ) : 'wp-login.php';

	}

	public function welcome_email( $value ) {

		return str_replace( 'wp-login.php', trailingslashit( $this->redirect_slug() ), $value );

	}

}

$invalid_login_redirect_change_login = new Invalid_Login_Redirect_Change_Login( $this->options );
