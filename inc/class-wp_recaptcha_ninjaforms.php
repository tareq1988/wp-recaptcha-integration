<?php

/**
 *	Class to manage NinjaForms Support
 */
class WP_reCaptcha_NinjaForms {
	/**
	 *	Holding the singleton instance
	 */
	private static $_instance = null;

	/**
	 *	@return WP_reCaptcha
	 */
	public static function instance(){
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
		return self::$_instance;
	}

	/**
	 *	Prevent from creating more instances
	 */
	private function __clone() { }

	/**
	 *	Prevent from creating more than one instance
	 */
	private function __construct() {
		add_action('init', array( $this, 'update_nf_settings' ) );
	}

	function update_nf_settings( ) {
		if ( WP_reCaptcha::instance()->has_api_key() && function_exists( 'Ninja_Forms') ) {
			$nf = Ninja_Forms();
			if ( false === $nf->get_setting('recaptcha_site_key') && false === $nf->get_setting('recaptcha_secret_key') ) {
				$nf->update_setting( 'recaptcha_site_key', WP_reCaptcha::instance()->get_option( 'recaptcha_publickey' ) );
				$nf->update_setting( 'recaptcha_secret_key', WP_reCaptcha::instance()->get_option( 'recaptcha_privatekey' ) );
			}
		}
	}

}
