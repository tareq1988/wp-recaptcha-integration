<?php


if ( ! function_exists('WPRecaptcha') ) :
function WPRecaptcha() {
	return RecaptchaIntegration\Core\Core::instance();
}
endif;

if ( ! function_exists( 'wp_recaptcha_get' ) ) :
function wp_recaptcha_get( $captcha = '', $attr = array() ) {
	// get it
	return WPRecaptcha()->get_captcha( $attr );
}
add_filter( 'wp_recaptcha_get', 'wp_recaptcha_get', 10, 2 );
endif;


if ( ! function_exists( 'wp_recaptcha_print' ) ) :
function wp_recaptcha_print( $attr = array() ){
	// print it
	echo apply_filters('wp_recaptcha_get', '', $attr );
}
add_action( 'wp_recaptcha_print', 'wp_recaptcha_print' );
endif;



if ( ! function_exists( 'wp_recaptcha_valid' ) ) :
function wp_recaptcha_valid( $valid ) {
	return apply_filters( 'wp_recaptcha_submitted', null ) && WPRecaptcha()->valid();
}
add_filter( 'wp_recaptcha_valid', 'wp_recaptcha_valid' );
endif;


if ( ! function_exists( 'wp_recaptcha_submitted' ) ) :
function wp_recaptcha_submitted( $submitted ) {
	return WPRecaptcha()->submitted();
}
add_filter( 'wp_recaptcha_submitted', 'wp_recaptcha_submitted' );
endif;


if ( ! function_exists( 'wp_recaptcha_wp_error' ) ) :
function wp_recaptcha_wp_error( $wp_error = null, $error_code = 'captcha_error' ) {
	if ( ! apply_filters('wp_recaptcha_valid', true ) ) {
		if ( ! is_wp_error( $wp_error ) ) {
			$wp_error = new WP_Error();
		}
		$wp_error->add( $error_code, __( 'The Captcha didn’t verify.', 'wp-recaptcha-integration' ) );
	}
	return $wp_error;
}
add_filter( 'wp_recaptcha_wp_error', 'wp_recaptcha_wp_error', 10, 2 );
endif;


if ( ! function_exists( 'wp_recaptcha_die' ) ) :
function wp_recaptcha_die() {
	if ( ! apply_filters( 'wp_recaptcha_valid', true ) ) {
		wp_die( __( 'The Captcha didn’t verify.', 'wp-recaptcha-integration' ) );
	}
}
add_action( 'wp_recaptcha_die', 'wp_recaptcha_die' );
endif;
