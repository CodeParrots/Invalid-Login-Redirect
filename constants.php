<?php
/**
 * Constants for Invalid Login Redirect
 *
 * @since 1.0.0
 */

if ( ! defined( 'ILR_VERSION' ) ) {

	define( 'ILR_VERSION', '1.0.0' );

}

if ( ! defined( 'ILR_PATH' ) ) {

	define( 'ILR_PATH', plugin_dir_path( __FILE__ ) );

}

if ( ! defined( 'ILR_URL' ) ) {

	define( 'ILR_URL', plugin_dir_url( __FILE__ ) );

}

if ( ! defined( 'ILR_MODULES' ) ) {

	define( 'ILR_MODULES', plugin_dir_path( __FILE__ ) . 'modules/' );

}

if ( ! defined( 'ILR_IMAGES' ) ) {

	define( 'ILR_IMAGES', plugin_dir_url( __FILE__ ) . 'lib/images/' );

}

if ( ! defined( 'ILR_STYLE_SUFFIX' ) ) {

	define( 'ILR_STYLE_SUFFIX', ( ( is_rtl() ? '-rtl' : '' ) . ( WP_DEBUG ? '' : '.min' ) ) );

}

if ( ! defined( 'ILR_SCRIPT_SUFFIX' ) ) {

	define( 'ILR_SCRIPT_SUFFIX', ( WP_DEBUG ? '' : '.min' ) );

}


if ( ! defined( 'INVALID_LOGIN_REDIRECT_DEVELOPER' ) ) {

	define( 'INVALID_LOGIN_REDIRECT_DEVELOPER', false );

}
