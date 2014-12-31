<?php



function wp_recaptcha_show() {
	WP_reCaptcha::instance()->print_recaptcha_html();
}

function wp_recaptcha_check_or_die( ) {
	WP_reCaptcha::instance()->recaptcha_check_or_die();
}

function wp_recaptcha_check() {
	return WP_reCaptcha::instance()->recaptcha_check();
}

add_action( 'wp_recaptcha_show' , 'wp_recaptcha_show' );

add_action( 'wp_recaptcha_check' , 'wp_recaptcha_check_or_die' );

/*
In Theme:
do_action('wp_recaptcha_show');

During form validation.
do_action('wp_recaptcha_check_or_die');


In Plugins:
if ( function_exists( 'wp_recaptcha_show' ) ):
	add_action( 'some_action_inside_a_form' , 'wp_recaptcha_show' );
endif;


if ( function_exists( 'wp_recaptcha_show' ) ):
	or just: wp_recaptcha_show();
endif;


*/