<?php



/**
 *	Class to manage WooCommerce Support
 */
class WP_reCaptcha_WooCommerce {
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
		add_action('init' , array( &$this , 'init' ) , 0 );
	}

	/**
	 *	Init plugin component
	 *	set hooks
	 */
	function init() {
		$wp_recaptcha = WP_reCaptcha::instance();
		$require_recaptcha = $wp_recaptcha->is_required();
		
		if ( $require_recaptcha ) {
			// WooCommerce support
			if ( $wp_recaptcha->get_option('recaptcha_flavor') == 'grecaptcha' && function_exists( 'wc_add_notice' ) ) {
				if ( $wp_recaptcha->get_option('recaptcha_enable_wc_order') ) {
					add_action('woocommerce_review_order_before_submit' , array($wp_recaptcha,'print_recaptcha_html'),10,0);
					add_action('woocommerce_checkout_process', array( &$this , 'recaptcha_check' ) );
				}
				if ( $wp_recaptcha->get_option('recaptcha_enable_login') ) {
					add_action('woocommerce_login_form' , array($wp_recaptcha,'print_recaptcha_html'),10,0);
					add_filter('woocommerce_process_login_errors', array( &$this , 'login_errors' ) , 10 , 3 );
					
					/*
					SIGNUP: woocommerce_registration_errors
					
					LOSTPW: not possible!
					*/
				}
				if ( $wp_recaptcha->get_option('recaptcha_enable_signup') ) {
					// displaying the captcha at hook 'registration_form' already done by core plugin
					add_filter('woocommerce_registration_errors', array( &$this , 'login_errors' ) , 10 , 3 );
				}
			}
		}
	}
	/**
	 *	WooCommerce recaptcha Check
	 *	hooks into action `woocommerce_checkout_process`
	 */
	function recaptcha_check() {
		if ( ! $this->recaptcha_check() ) 
			wc_add_notice( __("<strong>Error:</strong> the Captcha didn’t verify.",'wp-recaptcha-integration'), 'error' );
	}
	
	/**
	 *	WooCommerce recaptcha Check
	 *	hooks into actions `woocommerce_process_login_errors` and `woocommerce_registration_errors`
	 */
	function login_errors( $validation_error ) {
		if ( ! WP_reCaptcha::instance()->recaptcha_check() ) 
			$validation_error->add( 'captcha_error' ,  __("<strong>Error:</strong> the Captcha didn’t verify.",'wp-recaptcha-integration') );
		return $validation_error;
	}
}

WP_reCaptcha_WooCommerce::instance();
