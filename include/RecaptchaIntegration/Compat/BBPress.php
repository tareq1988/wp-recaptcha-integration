<?php

namespace RecaptchaIntegration\Compat;

use RecaptchaIntegration\Core;


class BBPress extends Compat {

	/**
	 *	@inheritdoc
	 */
	protected $init_priority = 0;

	/**
	 *	@inheritdoc
	 */
	public function init() {
		$inst = \WPRecaptcha();

		$enable_topic      = $inst->get_option('enable_bbp_topic') ;
		$enable_reply      = $inst->get_option('enable_bbp_reply') ;


		// BBPress support
		if ( $enable_topic ) {
			add_action( 'bbp_theme_before_topic_form_submit_wrapper', 'wp_recaptcha_print' );
			add_action( 'bbp_new_topic_pre_extras', array( $this , 'recaptcha_check' ) );
		}

		if ( $enable_reply ) {
			add_action( 'bbp_theme_before_reply_form_submit_wrapper', array( $inst, 'print_recaptcha_html' ) );
			add_filter( 'bbp_new_reply_pre_extras', array( $this , 'recaptcha_check' ) );
		}

	}

	/**
	 * bbP recaptcha Check
	 *
	 * @return void
	 */
	function recaptcha_check() {
		if ( ! apply_filters( 'wp_recaptcha_valid', true ) ) {
			bbp_add_error( 'bbp-recaptcha-error', __('<strong>Error:</strong> the Captcha didn’t verify.', 'wp-recaptcha-integration'), 'error' );
		}
	}

}
