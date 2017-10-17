<?php

namespace RecaptchaIntegration\Compat;


class AwesomeSupport extends Compat {

	/**
	 *	@inheritdoc
	 */
	protected $init_priority = 11,

	/**
	 *	@inheritdoc
	 */
	function init() {

		$inst        = WPRecaptcha();

		$enable_login        = $inst->get_option( 'recaptcha_enable_login' );
		$enable_registration = $inst->get_option( 'recaptcha_enable_as_registration' );


		// Awesome Support support

		if ( $enable_login ) {
			add_action( 'wpas_after_login_fields', array( $inst, 'print_recaptcha_html' ), 10, 0 );
			add_filter( 'wpas_try_login', array( $this, 'recaptcha_check' ), 10, 1 );
		}

		if ( $enable_registration ) {
			add_action( 'wpas_after_registration_fields', array( $inst, 'print_recaptcha_html' ), 10, 0 );
			add_filter( 'wpas_register_account_errors', array( $this, 'recaptcha_check' ), 10, 3 );
		}
	}

	/**
	 * Check the Captcha after Awesome Support tried to log the user in
	 *
	 * If the user login failed we simply return the existing error.
	 *
	 * @param WP_Error|WP_User $signon The result of the login attempt
	 *
	 * @return WP_Error|WP_User
	 */
	function recaptcha_check( $signon ) {

		if ( is_wp_error( $signon ) ) {
			return $signon;
		}

		if ( ! apply_filters( 'wp_recaptcha_valid', true ) ) {
			return new WP_Error( 'recaptcha_failed', __( 'The Captcha didn&#039;t verify', 'wp-recaptcha-integration' ) );
		}

		return $signon;

	}


}
