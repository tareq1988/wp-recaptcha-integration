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
		$wr = WP_reCaptcha::instance();
		$wr_configured = $wr->has_api_key();
		if ( ! method_exists( 'WPCF7', 'get_option' ) ) {
			return;
		}
		$cf7_opt = WPCF7::get_option( 'recaptcha' );
		$cf7_configured = is_array( $cf7_opt );

		if ( $wr_configured && ! $cf7_configured ) {
			$cf7_opt = array();
			$cf7_opt[ $wr->get_option( 'recaptcha_publickey' ) ] = $wr->get_option( 'recaptcha_privatekey' );
			WPCF7::update_option( 'recaptcha', $cf7_opt );
		} else if ( ! $wr_configured && $cf7_configured ) {
			foreach ( $cf7_opt as $pub => $priv ) {
				$wr->update_option( 'recaptcha_publickey' , $pub );
				$wr->update_option( 'recaptcha_privatekey' , $priv );
				break;
			}
		}
	}

}
