<?php

namespace RecaptchaIntegration\Compat;

use RecaptchaIntegration\Core;


class NinjaForms extends Compat {

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

		$nf = Ninja_Forms();

		$wr_configured = $inst->has_api_key();

		$nf_configured = false !== $nf->get_setting('recaptcha_site_key') && false !== $nf->get_setting('recaptcha_secret_key');

		if ( $wr_configured && ! $nf_configured ) {
			$nf->update_setting( 'recaptcha_site_key', $inst->get_option( 'site_key' ) );
			$nf->update_setting( 'recaptcha_secret_key', $inst->get_option( 'secret_key' ) );
		} else if ( ! $wr_configured && $nf_configured ) {
			$inst->update_option( 'site_key' , $nf->get_setting('recaptcha_site_key') );
			$inst->update_option( 'secret_key' , $nf->get_setting('recaptcha_secret_key') );
		}

	}

}
