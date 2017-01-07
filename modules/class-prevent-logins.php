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

		add_action( 'show_user_profile', [ $this, 'user_blacklist_reason_field' ] );
		add_action( 'edit_user_profile', [ $this, 'user_blacklist_reason_field' ] );

		add_action( 'personal_options_update',  [ $this, 'sanitize_user_meta' ] );
		add_action( 'edit_user_profile_update', [ $this, 'sanitize_user_meta' ] );

		add_action( 'wp_login',   [ $this, 'check_ip_address' ], 8, 2 );
		add_action( 'admin_init', [ $this, 'check_ip_address' ], 10, 2 );

		add_action( 'wp_enqueue_scripts', [ $this, 'load_notice_scripts' ] );

		add_action( 'ilr_username_column_actions', [ $this, 'log_table_user_actions' ], 10, 2 );

		add_action( 'admin_init', [ $this, 'toggle_blacklist_user' ] );

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

				$temp_value = is_array( $value ) ? $value : explode( ',', $value );

				$value = array_filter( array_map( 'sanitize_text_field', $temp_value ), 'strlen' );

				$input['addons'][ $this->class_slug ]['options'][ $option ] = $value;

				continue;

			}

			$input['addons'][ $this->class_slug ]['options'][ $option ] = ! empty( $value ) ? $value : '';

		}

		return $input;

	}

	/**
	 * Render custom fields on the user profile pages
	 *
	 * @param  stdObject $user User object
	 *
	 * @return mixed
	 *
	 * @since 1.0.0
	 */
	public function user_blacklist_reason_field( $user ) {

		$ilr_user_data = get_user_meta( $user->ID, 'invalid-login-redirect', true );

		$blacklist_reason = isset( $ilr_user_data['blacklist-reason'] ) ? $ilr_user_data['blacklist-reason'] : '';

		?>

		<h3><?php esc_html_e( 'Invalid Login Redirect', 'invalid-login-redirect' ); ?></h3>

		<table class="form-table">

			<tr>
				<th><label for="invalid-login-redirect[blacklist-reason]"><?php esc_html_e( 'Blacklist Reason', 'invalid-login-redirect' ); ?></label></th>

				<td>
					<textarea class="widefat" name="invalid-login-redirect[blacklist-reason]" id="invalid-login-redirect[blacklist-reason]" style="min-height:150px;"><?php echo esc_attr( $blacklist_reason ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Enter the reason that this user has been blocked. This is displayed back to the user when they attempt to login to the site.', 'invalid-login-redirect' ); ?></p>
				</td>
			</tr>

		</table>

		<?php

	}

	/**
	 * Save the custom user profile field data
	 *
	 * @param  integer $user_id The user ID to assing the meta to
	 *
	 * @return bool
	 *
	 * @since 1.0.0
	 */
	public function sanitize_user_meta( $user_id ) {

		if ( ! current_user_can( 'edit_user', $user_id ) ) {

			return;

		}

		$data = array_map( 'sanitize_text_field', filter_input( INPUT_POST, 'invalid-login-redirect', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY ) );

		update_user_meta( $user_id, 'invalid-login-redirect', $data );

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

			wp_redirect(
				add_query_arg( [
					'blocked' => true,
					'user'    => $username,
				], site_url() )
			);

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

		$notice = false;

		$user   = parent::$helpers->get_login_user_data( filter_input( INPUT_GET, 'user', FILTER_SANITIZE_STRING ) );

		if ( $user ) {

			$user_meta = get_user_meta( $user->ID, 'invalid-login-redirect', true );

			if ( ! isset( $user_meta['blacklist-reason'] ) ) {

				return;

			}

			$notice = $user_meta['blacklist-reason'];

		}

		if ( ! $notice ) {

			$notice = sprintf(
				__( 'Your IP address <small>(%s)</small> has been blocked from entering the site. If you believe this is an error, please <a href="#" style="color:white;text-decoration:underline;">contact the site administrator</a>.', 'invalid-login-redirect' ),
				parent::$helpers->get_user_ip()
			);

		}

		wp_localize_script( 'ilr-notifications', 'prevent_login', [
			'title' => sprintf(
				__( '%s Blocked Entry', 'invalid-login-redirect' ),
				'&times;'
			),
			'text'  => $notice,
		] );

	}

	/**
	 * Add additional links to the logging table
	 *
	 * @param  array  $links  Array of link data
	 * @param  stdObj $item   Current table item object
	 *
	 * @since 1.0.0
	 */
	public function log_table_user_actions( $links, $item ) {

		$user_obj = parent::$helpers->get_login_user_data( $item['username'] );

		if ( ! $user_obj ) {

			return $links;

		}

		$blacklist_url = wp_nonce_url(
			add_query_arg(
				'blacklist_user',
				$item['ID']
			),
			'blacklist_user_nonce',
			'blacklist_user_' . (int) $item['ID'] . '_nonce'
		);

		$blacklist_text = __( 'Blacklist User', 'invalid-login-redirect' );

		if ( in_array( self::$helpers->get_log_data( $item['ID'], 'ip_address' ), $this->prevent_login_option( 'blacklist', true ) ) ) {

			$blacklist_text = __( 'Remove from Blacklist', 'invalid-login-redirect' );

		}

		$links['blacklist'] = $actions['view'] = sprintf(
			'<small><a href="%1$s">%2$s</a></small>',
			esc_attr( $blacklist_url ),
			esc_html( $blacklist_text )
		);

		return $links;

	}

	/**
	 * Blacklist the user on page load
	 *
	 * @return bool
	 *
	 * @since 1.0.0
	 */
	public function toggle_blacklist_user() {

		$entry_id = filter_input( INPUT_GET, 'blacklist_user', FILTER_SANITIZE_NUMBER_INT );

		if ( ! $entry_id ) {

			return;

		}

		$nonce = filter_input( INPUT_GET, "blacklist_user_{$entry_id}_nonce", FILTER_SANITIZE_STRING );

		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'blacklist_user_nonce' ) ) {

			return;

		}

		add_action( 'ilr_before_options', function() use ( $entry_id ) {

			$username   = self::$helpers->get_log_data( $entry_id, 'username' );
			$user_obj   = self::$helpers->get_login_user_data( $username );
			$ip_address = self::$helpers->get_log_data( $entry_id, 'ip_address' );
			$blacklist  = $this->prevent_login_option( 'blacklist', true );

			$view_user_link = ! $user_obj ? '' : sprintf(
				'<p><a href="%1$s">%2$s</a></p>',
				admin_url( "user-edit.php?user_id={$user_obj->ID}" ),
				__( 'View User', 'invalid-login-redirect' )
			);

			$remove = false;

			if ( in_array( $ip_address, $blacklist ) ) {

				$remove = true;

				$key = array_search( $ip_address, $blacklist );

				if ( $key > -1 ) {

					unset( $this->options['addons']['prevent-logins']['options']['blacklist'][ $key ] );
					update_option( 'invalid-login-redirect', $this->options );

				} // @codingStandardsIgnoreLine

			}

			if ( $remove ) {

				$notice_text = esc_html_x( '%1$s is now removed from the blacklist. (%2$s)', '1st: Username; 2nd: IP Address;', 'invalid-login-redirect' );

			} else {

				$this->options['addons']['prevent-logins']['options']['blacklist'][] = $ip_address;
				update_option( 'invalid-login-redirect', $this->options );

				$notice_text = esc_html_x( '%1$s is now removed from the blacklist. (%2$s)', '1st: Username; 2nd: IP Address;', 'invalid-login-redirect' );

			}

			printf(
				'<div class="notice notice-success is-dismissible">
					<p>%1$s</p>
					%2$s
				</div>',
				sprintf( $notice_text, $username, $ip_address ),
				$view_user_link
			);

		} );

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
