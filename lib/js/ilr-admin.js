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
			    clicked_url = $( e.currentTarget ).attr( 'href' ),
			    referer     = location.pathname + location.search;

			history.pushState( null, null, clicked_url );

			$( '.ilr-settings-container' ).find( 'input[name="_wp_http_referer"]' ).val( referer );

			$( '.nav-tab-list li' ).removeClass( 'is-selected' );
			$( e.currentTarget ).parent().addClass( 'is-selected' );

			$( 'div.add-on' ).hide();
			$( 'div.' + clicked_tab ).show();

		},

		toggleSubOption: function( e ) {

			var $toggle_switch = $( e.currentTarget );

			$toggle_switch.parents( '.ilr-notice' ).find( '.sub-options' ).slideToggle();

		}

	};

	$( document ).on( 'click', '.option-tab-link', optionsToggle.switchTab );

	$( document ).on( 'change', '.has-sub-options_js', optionsToggle.toggleSubOption );

} )( jQuery );
