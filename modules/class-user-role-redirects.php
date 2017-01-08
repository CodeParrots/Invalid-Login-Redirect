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

	private $class_slug = 'user-role-redirects';

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

		$nav_items[] = __( 'User Role Redirects', 'invalid-login-redirect' );

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

		<div class="<?php echo esc_attr( $this->class_slug ); ?> add-on <?php if ( ( $tab && $this->class_slug !== $tab ) || ! $tab ) { echo 'hidden'; } ?>">

			<div class="ilr-notice col">

				<p class="description">

					<?php
					printf(
						__( 'Specify a custom URL to redirect each user role to on your site. The "Invalid Login" field is where registered users will be redirected to after %s attempts logging in with an invalid password. Leave the field blank to use the plugin defaults.', 'invalid-login-redirect' ),
						'<strong>' . absint( $this->options['login_limit'] ) . '</strong>'
					); ?>

				</p>

			</div>

			<?php

			$roles = $this->get_site_roles();

			$fields = [
				$this->class_slug => [],
			];

			$first = key( array_slice( $roles, 1, 1 ) );

			foreach ( $roles as $role => $role_data ) {

				$apply_to_all = ( $first === $role ) ? '<a href="#" class="apply-to-all">' . __( 'Apply to All Below', 'invalid-login-redirect' ) . '</a>' : '';

				$fields = [
					$this->class_slug => [
						'title'  => ucwords( $role_data['name'] ) . $apply_to_all,
						'fields' => [
							[
								'label'       => sprintf( __( '%s Successful Login', 'invalid-login-redirect' ), '<span class="dashicons dashicons-yes"></span>' ),
								'name'        => "invalid-login-redirect[addons][{$this->class_slug}][options][{$role}][valid-login]",
								'value'       => isset( $this->options['addons'][ $this->class_slug ]['options'][ $role ]['valid-login'] ) ? $this->options['addons'][ $this->class_slug ]['options'][ $role ]['valid-login'] : '',
								'placeholder' => site_url( 'wp-login.php', 'login' ),
								'class'       => 'valid-login widefat',
							],
							[
								'label'       => sprintf( __( '%s Invalid Login', 'invalid-login-redirect' ), '<span class="dashicons dashicons-no-alt"></span>' ),
								'name'        => "invalid-login-redirect[addons][user-role-redirects][options][{$role}][invalid-login]",
								'value'       => isset( $this->options['addons'][ $this->class_slug ]['options'][ $role ]['invalid-login'] ) ? $this->options['addons'][ $this->class_slug ]['options'][ $role ]['invalid-login'] : '',
								'placeholder' => site_url( 'wp-login.php', 'login' ),
								'class'       => 'invalid-login widefat',
							],
						],
					],
				];

				parent::$helpers->ilr_option_markup( $fields );

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

		if ( ! isset( $input['addons'][ $this->class_slug ]['options'] ) ) {

			return $input;

		}

		foreach ( $input['addons'][ $this->class_slug ]['options'] as $role => $redirects ) {

			if ( empty( $redirects['invalid-login'] ) && empty( $redirects['valid-login'] ) ) {

				unset( $input['addons'][ $this->class_slug ]['options'][ $role ] );

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

			if ( ! isset( $this->options['addons'][ $this->class_slug ]['options'][ $role ][ $type ] ) ) {

				return;

			}

			wp_redirect( $this->options['addons'][ $this->class_slug ]['options'][ $role ][ $type ] );

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

}

$invalid_login_redirect_user_role_redirects = new Invalid_Login_Redirect_User_Role_Redirects( $this->options );
