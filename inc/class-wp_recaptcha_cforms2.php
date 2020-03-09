<?php

/**
 * Manages cformsII support based on its pluggable captcha api
 */
class WP_reCaptcha_cforms2 extends cforms2_captcha {
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
	 * Prevent from creating more instances
	 */
	private function __clone() { }

	/**
	 * Prevent from creating more than one instance
	 */
	private function __construct() {
		add_filter('cforms2_add_captcha', array($this, 'add_instance'));
	}

	public function get_id() {
		return get_class($this);
	}

	public function get_name() {
		return __('reCAPTCHA', 'wp-recaptcha-integration');
	}

	public function check_authn_users() {
		return WP_reCaptcha::instance()->is_required();
	}

	public function check_response($post) {
		return WP_reCaptcha::instance()->recaptcha_check();
	}

	public function get_request($input_id, $input_classes, $input_title) {
		$wp_recaptcha = WP_reCaptcha::instance();
		$request = $wp_recaptcha->begin_inject(true);
 		$request.= $wp_recaptcha->recaptcha_html( $attr );
		$request.= $wp_recaptcha->end_inject(true);
		return $request;
	}

	public function render_settings() {
	 	echo '<a href="' . admin_url('options-general.php?page=recaptcha') . '">';
		_e('Please check WordPress reCaptcha integration plugin settings.', 'wp-recaptcha-integration');
		echo '</a>';
	}

}
