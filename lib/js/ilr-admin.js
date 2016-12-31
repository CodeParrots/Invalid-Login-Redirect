/**
 * Initialize the color picker on the options page
 *
 * @since 0.0.1
 */
( function( $ ) {

	$( '.js_error_text_border' ).wpColorPicker( {

		change: function( event, ui ) {

			$( '.ilr_message.invalid' ).css( 'border-color', ui.color.toString() );

		},

	} );

	var optionsToggle = {

		switchTab: function( e ) {

			e.preventDefault();

			var clicked_tab = $( e.currentTarget ).data( 'tab' ),
			    clicked_url = $( e.currentTarget ).attr( 'href' );

			console.log( clicked_url );
			history.pushState( null, null, clicked_url );

			$( '.nav-tab-list li' ).removeClass( 'is-selected' );
			$( e.currentTarget ).parent().addClass( 'is-selected' );

			$( 'div.add-on' ).hide();
			$( 'div.' + clicked_tab ).show();

		}

	};

	$( document ).on( 'click', '.option-tab-link', optionsToggle.switchTab );

} )( jQuery );
