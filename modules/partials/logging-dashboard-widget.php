<?php
/**
 * The Logging dashboard widget markup
 *
 * @since 1.0.0
 */

wp_enqueue_style( 'ilr-login-style', ILR_URL . '/lib/css/ilr-admin.css' );

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

<<<<<<< HEAD
$logs = parent::$helpers->get_ilr_log( 10 );

if ( $logs->have_posts() ) {

	echo '<table class="ilr-widget-table wp-list-table widefat fixed striped logs" style="width: 100%;">
		<thead>
			<tr>
				<th class="ilr-widget-cell">Username</th>
				<th class="ilr-widget-cell">Date/Time</th>
				<th class="ilr-widget-cell" style="text-align:center!important;">Attempt</th>
				<th class="ilr-widget-cell">Type<br></th>
			</tr>
		</thead>';

	while ( $logs->have_posts() ) {

		$logs->the_post();
=======
$logs = parent::$helpers->get_ilr_log( [
	'posts_per_page' => 10,
] );
>>>>>>> ae136d92ea4a32db0a5267b9fc5d5b5fb94dd52a

		echo '<tr class="ilr-widget-row">
			<td class="ilr-widget-cell">' . get_post_meta( get_the_ID(), 'ilr_log_username', true ) . '</td>
			<td class="ilr-widget-cell">' . date( get_option( 'date_format' ), get_post_meta( get_the_ID(), 'ilr_log_timestamp', true ) ) . ' &ndash; ' . date( get_option( 'time_format' ), get_post_meta( get_the_ID(), 'ilr_log_timestamp', true ) ) . '</td>
			<td class="ilr-widget-cell" style="text-align:center!important;">' . get_post_meta( get_the_ID(), 'ilr_log_attempt', true ) . '</td>
			<td class="ilr-widget-cell">' . get_post_meta( get_the_ID(), 'ilr_log_type', true ) . '</td>
		</tr>';

	}

	echo '</table>';

	wp_reset_postdata();

}
