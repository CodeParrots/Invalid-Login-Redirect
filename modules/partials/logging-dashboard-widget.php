<?php
/**
 * The Logging dashboard widget markup
 *
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

wp_enqueue_style( 'ilr-login-style', ILR_URL . '/lib/css/ilr-admin.css' );

$logs = parent::$helpers->get_ilr_log( [
	'posts_per_page' => 10,
] );

ob_start();

if ( $logs->have_posts() ) {

	?>

	<table class="ilr-widget-table wp-list-table widefat fixed striped logs" style="width: 100%;">
		<thead>
			<tr>
				<th class="ilr-widget-cell">Username</th>
				<th class="ilr-widget-cell">Date/Time</th>
				<th class="ilr-widget-cell" style="text-align:center!important;">Attempt</th>
				<th class="ilr-widget-cell">Type<br></th>
			</tr>
		</thead>

	<?php

	while ( $logs->have_posts() ) {

		$logs->the_post();

		$data = [
			'username'  => get_post_meta( get_the_ID(), 'ilr_log_username', true ),
			'timestamp' => get_post_meta( get_the_ID(), 'ilr_log_timestamp', true ),
			'attempt'   => get_post_meta( get_the_ID(), 'ilr_log_attempt', true ),
			'type'      => get_post_meta( get_the_ID(), 'ilr_log_type', true ),
		];

		?>

		<tr class="ilr-widget-row">
			<td class="ilr-widget-cell"><?php echo esc_html( $data['username'] ); ?></td>
			<td class="ilr-widget-cell"><?php echo esc_html( date( get_option( 'date_format' ), $data['timestamp'] ) . ' &ndash; ' . date( get_option( 'time_format' ), $data['timestamp'] ) ); ?></td>
			<td class="ilr-widget-cell" style="text-align:center!important;"><?php echo esc_html( $data['attempt'] ); ?></td>
			<td class="ilr-widget-cell"><?php echo wp_kses_post( Invalid_Login_Redirect_Logging::ilr_get_table_badge( $data['type'], $data ) ); ?></td>
		</tr>

		<?php

	}

	?>

	</table>


	<?php

	wp_reset_postdata();

}

return ob_get_contents();
