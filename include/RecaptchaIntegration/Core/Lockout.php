<?php

namespace RecaptchaIntegration\Core;

class Lockout extends Singleton {

	/**
	 *	@inheritdoc
	 */
	protected function __construct() {
		add_filter( 'wp_recaptcha_will_die', array( $this, 'will_die') );
		add_action( 'login_form_captchalockout', array( $this, 'captcha_lockout' ) );
	}

	public function will_die( $will_die ) {
		if ( ! isset( $_POST['log'] ) ) {
			return $will_die;
		}
		if ( ! $user = get_user_by('login',$_POST['log'] ) ) {
			$user = get_user_by( 'email', $_POST['log'] );
		}
		if ( ! $user instanceof \WP_User ) {
			return $will_die;
		}

		// check login Credentials
		$auth = wp_authenticate( $_POST['log'], $_POST['pwd'] );

		// login failed anyway
		if ( is_wp_error( $auth ) ) {
			return $will_die;
		}
		$inst = \WPRecaptcha();

		$catcha_errors = $inst->get_captcha_object()->get_error_codes();
		$config_errors = array( 'invalid-input-secret','missing-input-secret','bad-request','http_request_failed' );

		// captcha error might be due to configuration error
		if ( count( array_intersect( $catcha_errors, $config_errors ) ) ) {
			// add admin notice
			\WPRecaptcha()->delete_option( 'site_key' );
			\WPRecaptcha()->delete_option( 'secret_key' );
			update_site_transient( 'wp_recaptcha_api_key_lockout_reset', $inst->get_captcha_object()->get_error_messages() );
			return false;
		}
		return $will_die;
	}


}
