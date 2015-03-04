<?php



/**
 *	Class to manage the recaptcha options.
 */
abstract class WP_reCaptcha_Captcha {
	
	protected $_last_result = false;
	
	abstract function print_head();
	abstract function print_login_head();
	abstract function print_foot();
	abstract function get_html( $attr = array() );
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
	 *	Get recaptcha language code that matches input language code
	 *	
	 *	@param	$lang	string language code
	 *	@return	string	recaptcha language code if supported by current flavor, empty string otherwise
	 */
	public function get_language( $lang ) {
		$lang = str_replace( '_' , '-' , $lang );
		
		// direct hit: return it.
		if ( isset($this->supported_languages[$lang]) )
			return $lang;
		
		// remove countrycode, try again
		$lang = preg_replace('/-(.*)$/','',$lang);
		if ( isset($this->supported_languages[$lang]) )
			return $lang;
		
		// lang does not exist.
		return '';
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

