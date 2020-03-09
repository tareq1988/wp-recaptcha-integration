<?php

/**
 * Class to manage bbPress Support
 */
class WP_reCaptcha_bbPress {
	/**
	 * Holding the singleton instance
	 */
	private static $_instance = null;

	/**
	 * @return WP_reCaptcha
	 */
	public static function instance(){
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 *	Prevent from creating more instances
	 */
	private function __clone() { }

	/**
	 * Prevent from creating more than one instance
	 */
	private function __construct() {
		add_action('init' , array( &$this , 'init' ) , 0 );
	}

	/**
	 * Init plugin component
	 *
	 * set hooks
	 */
	function init() {
		$wp_recaptcha      = WP_reCaptcha::instance();
		$require_recaptcha = $wp_recaptcha->is_required();
		$enable_topic      = $wp_recaptcha->get_option('recaptcha_enable_bbp_topic') ;
		$enable_reply      = $wp_recaptcha->get_option('recaptcha_enable_bbp_reply') ;

		if ( $require_recaptcha ) {

			// WooCommerce support
			if ( $wp_recaptcha->get_option('recaptcha_flavor') == 'grecaptcha' ) {

				if ( $enable_topic ) {
					add_action( 'bbp_theme_before_topic_form_submit_wrapper' , array( $wp_recaptcha, 'print_recaptcha_html' ) );
					add_action( 'bbp_new_topic_pre_extras', array( &$this , 'recaptcha_check' ) );
				}

				if ( $enable_reply ) {
					add_action( 'bbp_theme_before_reply_form_submit_wrapper', array( $wp_recaptcha, 'print_recaptcha_html' ) );
					add_filter( 'bbp_new_reply_pre_extras', array( &$this , 'recaptcha_check' ) );
				}
			}
		}
	}

	/**
	 * bbP recaptcha Check
	 *
	 * @return void
	 */
	function recaptcha_check() {
		if ( ! WP_reCaptcha::instance()->recaptcha_check() ) {
			bbp_add_error( 'bbp-recaptcha-error', __('<strong>Error:</strong> the Captcha didn’t verify.', 'wp-recaptcha-integration'), 'error' );
		}
	}
}
