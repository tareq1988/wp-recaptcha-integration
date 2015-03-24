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
		$enable_order  = $wp_recaptcha->get_option('recaptcha_enable_wc_order') ;
		$enable_signup = $wp_recaptcha->get_option('recaptcha_enable_signup') ;
		$enable_login  = $wp_recaptcha->get_option('recaptcha_enable_login');
		$enable_lostpw = $wp_recaptcha->get_option('recaptcha_enable_lostpw');
		if ( $require_recaptcha ) {
			// WooCommerce support
			if ( $wp_recaptcha->get_option('recaptcha_flavor') == 'grecaptcha' && function_exists( 'wc_add_notice' ) ) {
				if ( $enable_order ) {
					add_action('woocommerce_review_order_before_submit' , array($wp_recaptcha,'print_recaptcha_html'),10,0);
					add_action('woocommerce_checkout_process', array( &$this , 'recaptcha_check' ) );
					add_filter( 'wc_checkout_recaptcha_html' , array( &$this , 'recaptcha_html' ) );
				} else if ( $enable_signup ) {
					add_filter( 'wp_recaptcha_required' , array( &$this , 'disable_on_checkout' ) );
				}
				if ( $enable_login ) {
					add_action('woocommerce_login_form' , array($wp_recaptcha,'print_recaptcha_html'),10,0);
					add_filter('woocommerce_process_login_errors', array( &$this , 'login_errors' ) , 10 , 3 );
				}
				if ( $enable_signup ) {
					// displaying the captcha at hook 'registration_form' already done by core plugin
					add_filter('woocommerce_registration_errors', array( &$this , 'login_errors' ) , 10 , 3 );
// 					if ( ! $enable_order )
// 						add_filter('woocommerce_checkout_fields', array( &$this , 'checkout_fields' ) , 10 , 3 );
				}
				add_filter('woocommerce_form_field_recaptcha', array( $wp_recaptcha , 'recaptcha_html' ) , 10 , 3 );
				/*
				LOSTPW: Not possible yet. Needs https://github.com/woothemes/woocommerce/pull/7786 being applied.
				*/
				if ( $enable_lostpw ) {
					add_action( 'woocommerce_lostpassword_form' , array($wp_recaptcha,'print_recaptcha_html'),10,0);
				}
			}
		}
	}
	/*
	function checkout_fields( $checkout_fields ) {
		$checkout_fields['account']['recaptcha'] = array(
			'type' => 'recaptcha',
			'label' => __( 'Are you human', 'wp-recaptcha-integration' ),
		);
		return $checkout_fields;
	}
	*/
	/**
	 *	WooCommerce recaptcha Check
	 *	hooks into action `woocommerce_checkout_process`
	 */
	function recaptcha_check() {
		if ( ! WP_reCaptcha::instance()->recaptcha_check() ) 
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
	
	/**
	 *	WooCommerce recaptcha Check
	 *	hooks into actions `woocommerce_process_login_errors` and `woocommerce_registration_errors`
	 */
	function disable_on_checkout( $enabled ) {
		if ( defined( 'WOOCOMMERCE_CHECKOUT' ) )
			return false;
		return $enabled;
	}
}


