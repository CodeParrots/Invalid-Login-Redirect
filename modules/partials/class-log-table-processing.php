<?php
/**
 * Error log table
 *
 * @since 1.0.0
 */
class Invalid_Login_Redirect_Log_Table_Processing extends Invalid_Login_Redirect {

	function __construct() {

		add_action( 'admin_init', [ $this, 'delete_log_entry' ] );

		add_action( 'ilr_before_options', [ $this, 'display_admin_notices' ] );

	}

	/**
	 * Delete log entry
	 *
	 * @return bool
	 */
	public function delete_log_entry() {

		$entry_id = filter_input( INPUT_GET, 'delete_log_entry', FILTER_SANITIZE_NUMBER_INT );

		if ( ! $entry_id ) {

			return;

		}

		$nonce = filter_input( INPUT_GET, "delete_log_entry_{$entry_id}_nonce", FILTER_SANITIZE_STRING );

		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'delete_log_entry_nonce' ) ) {

			return;

		}

		if ( wp_delete_post( $entry_id, true ) ) {

			wp_redirect(
				esc_url_raw(
					add_query_arg(
						'deleted',
						1,
						admin_url( 'tools.php?page=invalid-login-redirect&tab=logging' )
					)
				)
			);

			exit;

		}

	}

	/**
	 * Display admin notices specific to certain actions
	 *
	 * @return mixed
	 */
	public function display_admin_notices() {

		if ( filter_input( INPUT_GET, 'deleted' ) ) {

			printf(
				'<div class="notice notice-success is-dismissible">
					<p>%1$s</p>
				</div>',
				__( 'Log entry deleted.', 'invalid-login-redirect' )
			);

			return;

		}

	}

}

$invalid_login_redirect_log_table_processing = new Invalid_Login_Redirect_Log_Table_Processing();
