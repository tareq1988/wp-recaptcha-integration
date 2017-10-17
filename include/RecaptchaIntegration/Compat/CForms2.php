<?php

namespace RecaptchaIntegration\Compat;

use RecaptchaIntegration\Core;

/**
 *
 */
class CForms2 extends Compat {

	/**
	 *	@inheritdoc
	 */
	protected $init_priority = 10;

	/**
	 * Prevent from creating more than one instance
	 */
	protected function __construct() {

		parent::__construct();

		add_filter('cforms2_add_captcha', array( $this, 'add_instance') );

	}

	/**
	 *	@inheritdoc
	 */
	public function init() {

	}

	public function get_id() {
		return sanitize_title(get_class( $this ));
	}

	public function get_name() {
		return __('reCAPTCHA', 'wp-recaptcha-integration');
	}

	public function check_authn_users() {
		return $this->core->is_required();
	}

	public function check_response($post) {
		return $this->core->recaptcha_check();
	}

	public function get_request($input_id, $input_classes, $input_title) {
		$wp_recaptcha = WP_reCaptcha::instance();
		$request = $this->core->begin_inject(true);
 		$request.= $this->core->recaptcha_html( $attr );
		$request.= $this->core->end_inject(true);
		return $request;
	}

	public function render_settings() {
	 	echo '<a href="' . admin_url('options-general.php?page=recaptcha') . '">';
		_e('Please check WordPress reCaptcha integration plugin settings.', 'wp-recaptcha-integration');
		echo '</a>';
	}

}
