<?php
/**
 * WP reCaptcha integration for Awesome Support
 *
 * @package   WP reCaptcha
 * @author    Julien Liabeuf <julien@liabeuf.fr>
 * @license   GPL-2.0+
 * @link      http://themeavenue.net
 * @copyright 2014 ThemeAvenue
 */
class WP_reCaptcha_Awesome_Support {
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
		add_action( 'init', array( $this , 'init' ), 11 );
	}

	/**
	 *	Init plugin component
	 *	set hooks
	 */
	function init() {

		$wp_recaptcha        = WP_reCaptcha::instance();
		$require_recaptcha   = $wp_recaptcha->is_required();
		$enable_login        = $wp_recaptcha->get_option( 'recaptcha_enable_login' );
		$enable_registration = $wp_recaptcha->get_option( 'recaptcha_enable_as_registration' );

		if ( $require_recaptcha ) {

			// Awesome Support support

			if ( $enable_login ) {
				add_action( 'wpas_after_login_fields', array( $wp_recaptcha, 'print_recaptcha_html' ), 10, 0 );
				add_filter( 'wpas_try_login', array( &$this, 'recaptcha_check' ), 10, 1 );
			}

			if ( $enable_registration ) {
				add_action( 'wpas_after_registration_fields', array( $wp_recaptcha, 'print_recaptcha_html' ), 10, 0 );
				add_filter( 'wpas_register_account_errors', array( $this, 'recaptcha_check' ), 10, 3 );
			}
		}

	}

	/**
	 * Check the Captcha after Awesome Support tried to log the user in
	 *
	 * If the user login failed we simply return the existing error.
	 *
	 * @param WP_Error|WP_User $signon The result of the login attempt
	 *
	 * @return WP_Error|WP_User
	 */
	function recaptcha_check( $signon ) {

		if ( is_wp_error( $signon ) ) {
			return $signon;
		}

		if ( ! WP_reCaptcha::instance()->recaptcha_check() ) {
			return new WP_Error( 'recaptcha_failed', __( 'The Captcha didn&#039;t verify', 'wp-recaptcha-integration' ) );
		}

		return $signon;

	}
}


