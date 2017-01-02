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
			    referer     = location.pathname + '?page=invalid-login-redirect&tab=' + clicked_tab;

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

	var roleRedirects = {

		applyToAll: function( e ) {

			e.preventDefault();

			var $containers    = $( '.role-redirects.add-on' ).find( '.ilr-notice' ).not( '.administrator' );
			    $clicked       = $( e.currentTarget ),
			    valid_login    = $clicked.next().find( 'input.valid-login' ).val(),
			    invalid_login  = $clicked.next().find( 'input.invalid-login' ).val();

			$containers.find( '.valid-login' ).val( valid_login );
			$containers.find( '.invalid-login' ).val( invalid_login );

		}

	};

	$( document ).on( 'click', '.option-tab-link', optionsToggle.switchTab );

	$( document ).on( 'change', '.has-sub-options_js', optionsToggle.toggleSubOption );

	$( document ).on( 'click', '.apply-to-all', roleRedirects.applyToAll );

} )( jQuery );
