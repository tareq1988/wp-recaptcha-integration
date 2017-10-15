<?php



/**
 *	Class to manage ContactForm 7 Support
 */
class WP_reCaptcha_ContactForm7 {
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
		add_action('init',array( $this, 'update_cf7_settings' ) );
	}
	public function update_cf7_settings() {
		if ( class_exists('WPCF7') ) {
			if ( ! $option = WPCF7::get_option( 'recaptcha' ) ) {
				$option = array();
				$option[ WP_reCaptcha::instance()->get_option( 'recaptcha_publickey' ) ] = WP_reCaptcha::instance()->get_option( 'recaptcha_privatekey' );
				WPCF7::update_option( 'recaptcha', $option );
			}
		}
	}

}
