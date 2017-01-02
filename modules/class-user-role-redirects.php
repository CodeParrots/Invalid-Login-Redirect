<?php
/**
 * User Role Redirects Module
 *
 * Redirect users by role to certain areas of your site
 *
 * @author Code Parrots <support@codeparrots.com>
 *
 * @since 1.0.0
 */
final class Invalid_Login_Redirect_User_Role_Redirects extends Invalid_Login_Redirect {

	private $options;

	public function __construct( $options ) {

		$this->options = $options;

		add_filter( 'ilr_options_nav_items',      [ $this, 'option_nav_item' ] );

		add_action( 'ilr_options_section',        [ $this, 'option_section' ] );

		add_filter( 'ilr_sanitize_options',       [ $this, 'sanitize_options' ] );

		add_action( 'wp_login',                   [ $this, 'handle_login' ], 10, 2 );

		add_filter( 'ilr_invalid_login_redirect', [ $this, 'invalid_login_redirect' ], 10, 2 );

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

		$nav_items[] = __( 'Role Redirects', 'invalid-login-redirect' );

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

		<div class="role-redirects add-on <?php if ( ( $tab && 'role-redirects' !== $tab ) || ! $tab ) { echo 'hidden'; } ?>">

			<div class="ilr-notice col">

				<p class="description">

					<?php
					printf(
						__( 'Specify a custom URL to redirect each user role to on your site. The "Invalid Login" field is where registered users will be redirected to after %s attempts lof ogging in with an invalid password. Leave the field blank to use the plugin defaults.', 'invalid-login-redirect' ),
						'<strong>' . absint( $this->options['login_limit'] ) . '</strong>'
					); ?>

				</p>

			</div>

			<?php

			$roles = $this->get_site_roles();

			foreach ( $roles as $role => $role_data ) {

				$this->get_field_markup( $role, $role_data, key( array_slice( $roles, 1, 1 ) ) );

			}

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

		if ( ! isset( $input['addons']['user-role-redirects']['options'] ) ) {

			return $input;

		}

		foreach ( $input['addons']['user-role-redirects']['options'] as $role => $redirects ) {

			if ( empty( $redirects['invalid-login'] ) && empty( $redirects['valid-login'] ) ) {

				unset( $input['addons']['user-role-redirects']['options'][ $role ] );

			} // @codingStandardsIgnoreLine

		}

		return $input;

	}

	/**
	 * Handle a successful login, and redirect the user
	 *
	 * @param  string $username    Username entered at login
	 * @param  object $user_object User object
	 *
	 * @return bool
	 *
	 * @since 1.0.0
	 */
	public function handle_login( $username, $user_object ) {

		$role = (array) parent::$helpers->get_ilr_user_role( $user_object );

		if ( ! $role || empty( $role ) ) {

			return;

		}

		$this->handle_redirect( $role, 'valid-login' );

	}

	/**
	 * Handle an invalid login redirect
	 *
	 * @param  string $url  Redirect URL
	 * @param  object $user User Object
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public function invalid_login_redirect( $url, $user ) {

		$role = (array) parent::$helpers->get_ilr_user_role( $user );

		if ( ! $role || empty( $role ) ) {

			return;

		}

		$this->handle_redirect( $role, 'invalid-login' );

	}

	/**
	 * Handle the redirect
	 *
	 * @param  array  $roles User role(s)
	 * @param  string $type  Option to return
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	private function handle_redirect( $roles, $type ) {

		foreach ( $roles as $role ) {

			if ( ! isset( $this->options['addons']['user-role-redirects']['options'][ $role ][ $type ] ) ) {

				return;

			}

			wp_redirect( $this->options['addons']['user-role-redirects']['options'][ $role ][ $type ] );

			exit;

		}

	}

	/**
	 * Get the roles registered on the site
	 *
	 * @return array
	 *
	 * @since 1.0.0
	 */
	private function get_site_roles() {

		global $wp_roles;

		$all_roles = $wp_roles->roles;

		$editable_roles = apply_filters( 'editable_roles', $all_roles );

		return $editable_roles;

	}

	/**
	 * Generate the field markup for the redirect inputs
	 *
	 * @param  string $role      User role
	 * @param  object $role_data User role data
	 * @param  string $first     First item in the roles array (string).
	 *
	 * @return mixed
	 *
	 * @since 1.0.0
	 */
	private function get_field_markup( $role, $role_data, $first ) {

		return printf(
			'<div class="col ilr-notice %3$s">
				%1$s %2$s
				<div class="fields">

					<label for="invalid-login-redirect[addons][user-role-redirects][options][%3$s][valid-login]">%7$s</label>
					<input type="text" id="invalid-login-redirect[addons][user-role-redirects][options][%3$s][valid-login]" name="invalid-login-redirect[addons][user-role-redirects][options][%3$s][valid-login]" class="widefat invalid-login" value="%8$s" placeholder="%9$s" />

					<label for="invalid-login-redirect[addons][user-role-redirects][options][%3$s][invalid-login]">%4$s</label>
					<input type="text" id="invalid-login-redirect[addons][user-role-redirects][options][%3$s][invalid-login]" name="invalid-login-redirect[addons][user-role-redirects][options][%3$s][invalid-login]" class="widefat valid-login" value="%5$s" placeholder="%6$s" />

				</div>
			</div>',
			ucwords( $role_data['name'] ),
			( $first === $role ) ? '<a href="#" class="apply-to-all">' . __( 'Apply to All Below', 'invalid-login-redirect' ) . '</a>' : '',
			sanitize_title( $role ),
			sprintf( __( '%s Invalid Login', 'invalid-login-redirect' ), '<span class="dashicons dashicons-no-alt"></span>' ),
			isset( $this->options['addons']['user-role-redirects']['options'][ $role ]['invalid-login'] ) ? esc_url( $this->options['addons']['user-role-redirects']['options'][ $role ]['invalid-login'] ) : '',
			esc_attr( $this->options['redirect_url'] ),
			sprintf( __( '%s Successful Login', 'invalid-login-redirect' ), '<span class="dashicons dashicons-yes"></span>' ),
			isset( $this->options['addons']['user-role-redirects']['options'][ $role ]['valid-login'] ) ? esc_url( $this->options['addons']['user-role-redirects']['options'][ $role ]['valid-login'] ) : '',
			esc_attr( admin_url() )
		);

	}

}

$invalid_login_redirect_user_role_redirects = new Invalid_Login_Redirect_User_Role_Redirects( $this->options );
