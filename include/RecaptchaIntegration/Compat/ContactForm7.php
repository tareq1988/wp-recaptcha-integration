<?php

namespace RecaptchaIntegration\Compat;

use RecaptchaIntegration\Core;


class ContactForm7 extends Compat {

	/**
	 *	@inheritdoc
	 */
	protected $init_priority = 10;

	/**
	 *	@inheritdoc
	 */
	public function init() {
		$inst = \WPRecaptcha();
		if ( $inst->is_network_activated() ) {
			return;
		}
		$wr_configured = $inst->has_api_key();
		$cf7_opt = WPCF7::get_option( 'recaptcha' );
		$cf7_configured = is_array( $cf7_opt );

		if ( $wr_configured && ! $cf7_configured ) {
			// Auto-Setup CF7 api keys ...
			$cf7_opt = array();
			$cf7_opt[ $inst->get_option( 'site_key' ) ] = $inst->get_option( 'secret_key' );
			WPCF7::update_option( 'recaptcha', $cf7_opt );
		} else if ( ! $wr_configured && $cf7_configured ) {
			// ... or get api keys from CF7
			foreach ( $cf7_opt as $pub => $priv ) {
				$inst->update_option( 'site_key' , $pub );
				$inst->update_option( 'secret_key' , $priv );
				break;
			}
		}

	}

}
