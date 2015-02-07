<?php



/**
 *	Class to manage the recaptcha options.
 */
abstract class WP_reCaptcha_Captcha {
	
	protected $_last_result = false;
	
	abstract function print_head();
	abstract function print_foot();
	abstract function get_html();
	abstract function check();
	abstract function get_supported_themes();

	/**
	 *	Get languages supported by current recaptcha flavor.
	 *
	 *	@return array languages supported by this recaptcha.
	 */
	public function get_supported_languages() {
		return $this->supported_languages;
	}

	/**
	 *	Get last result of recaptcha check
	 *	@return string recaptcha html
	 */
	function get_last_result() {
		return $this->_last_result;
	}
	

}


WP_reCaptcha_Options::instance();

