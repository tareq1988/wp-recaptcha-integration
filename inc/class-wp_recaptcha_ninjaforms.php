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
		$nf = Ninja_Forms();

		if ( ! method_exists( $nf, 'get_setting' ) ) {
			return;
		}

		$wr = WP_reCaptcha::instance();
		$wr_configured = $wr->has_api_key();
		$nf_configured = false !== $nf->get_setting('recaptcha_site_key') && false !== $nf->get_setting('recaptcha_secret_key');
		if ( $wr_configured && ! $nf_configured ) {
			$nf->update_setting( 'recaptcha_site_key', $wr->get_option( 'recaptcha_publickey' ) );
			$nf->update_setting( 'recaptcha_secret_key', $wr->get_option( 'recaptcha_privatekey' ) );
		} else if ( ! $wr_configured && $nf_configured ) {
			$wr->update_option( 'recaptcha_publickey' , $nf->get_setting('recaptcha_site_key') );
			$wr->update_option( 'recaptcha_privatekey' , $nf->get_setting('recaptcha_secret_key') );
		}
	}

}
