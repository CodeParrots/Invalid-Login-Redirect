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

$logs = parent::$helpers->get_ilr_log( 10 );

if ( $logs->have_posts() ) {

	echo '<table class="ilr-widget-table wp-list-table widefat fixed striped logs" style="width: 100%;">
		<thead>
		  <tr>
		    <th class="tg-yw4l">Date/Time</th>
		    <th class="tg-yw4l">Username</th>
		    <th class="tg-yw4l">Attempt</th>
		    <th class="tg-yw4l">Type<br></th>
		  </tr>
		</thead>';

	while ( $logs->have_posts() ) {

		$logs->the_post();

		echo '<tr class="ilr-widget-row">
						<td class="ilr-widget-cell">' . get_post_meta( get_the_ID(), 'ilr_log_username', true ) . '</td>
				    <td class="ilr-widget-cell">' . date( get_option( 'date_format' ), get_post_meta( get_the_ID(), 'ilr_log_timestamp', true ) ) . ' &ndash; ' . date( get_option( 'time_format' ), get_post_meta( get_the_ID(), 'ilr_log_timestamp', true ) ) . '</td>
				    <td class="ilr-widget-cell">' . get_post_meta( get_the_ID(), 'ilr_log_attempt', true ) . '</td>
				    <td class="ilr-widget-cell">' . get_post_meta( get_the_ID(), 'ilr_log_type', true ) . '</td>
		    </tr>';

	}

	echo '</table>';

	wp_reset_postdata();

}
