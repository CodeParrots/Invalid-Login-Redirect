/**
 * Initialize the color picker on the options page
 *
 * @since 0.0.1
 */
( function( $ ) {

	$( '.js_error_text_color' ).wpColorPicker( {

		change: function( event, ui ) {

			$( '.ilr_message.invalid' ).css( 'border-color', ui.color.toString() );

		},

	} );

} )( jQuery );
