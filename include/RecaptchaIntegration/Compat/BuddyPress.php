<?php

namespace RecaptchaIntegration\Compat;

use RecaptchaIntegration\Core;


class BuddyPress extends Compat {

	/**
	 *	@inheritdoc
	 */
	protected $init_priority = 0;

	/**
	 *	@inheritdoc
	 */
	public function init() {

		$inst = WPRecaptcha();

		if ( $inst->get_option('enable_signup') ) {
			add_action( 'bp_account_details_fields', 'wp_recaptcha_print' );
			add_action( 'bp_signup_pre_validate', 'wp_recaptcha_die', 99 );
		}
	}

}
