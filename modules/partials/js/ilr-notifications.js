jQuery( document ).ready( function() {

	function send_popup( notification ) {

		console.log( notification );

		var title       = '' !== notification['title'] ? notification['title'] : '',
		    text        = '' !== notification['ip_address'] ? notification['ip_address'] : 'The text is empty.',
		    growl_class = notification['class'] !== '' ? notification['class'] : 'notice';

		console.log( notification );
		console.log( growl_class );

		jQuery.growl.notice( {
			title:        title,
			message:      text,
			duration:     60000,
			location:     'br',
			size:         'small',
			delayOnHover: true,
			style:        growl_class,
		} );

	}

	//hook into heartbeat-send
	jQuery( document ).on( 'heartbeat-send', function( e, data ) {

		data['notify_status'] = 'ready';

	} );

	//hook into heartbeat-tick: client looks in data var for natifications
	jQuery( document ).on( 'heartbeat-tick.ravs_tick', function( e, data ) {

		if ( ! data['ravs_notify'] ) {

			return;

		}

		jQuery.each( data['ravs_notify'], function( index, notification ) {

			if ( index != 'blabla' ) {

				send_popup( notification );

			}

		} ) ;

	} );

	jQuery( document ).on( 'heartbeat-error', function( e, jqXHR, textStatus, error ) {

		console.log('BEGIN ERROR');
		console.log(textStatus);
		console.log(error);
		console.log('END ERROR');

	} );

} );
