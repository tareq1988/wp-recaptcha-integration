<?php



/**
 *	Class to manage the recaptcha options.
 */
abstract class WP_reCaptcha_Captcha {
	
	protected $_last_result = false;
	
	/**
	 * Print Head scripts.
	 */
	abstract function print_head();

	/**
	 * Print Head scripts on login page.
	 */
	abstract function print_login_head();

	/**
	 * Print footer scripts
	 */
	abstract function print_foot();
	/**
	 * Get the captcha HTML
	 * 
	 * @param	$attr	array	HTML attributes as key => value association
	 * @return	string	The Captcha HTML
	 */
	abstract function get_html( $attr = array() );

	/**
	 * Check the users resonse.
	 * Performs a HTTP request to the google captcha service.
	 * 
	 * @return	bool	true when the captcha test verifies.
	 */
	abstract function check();
	/**
	 * Get supported theme names
	 * 
	 * @return	array	array(
	 *						theme_slug => array( 
	 *							'label' => string // Human readable Theme Name
	 *						)
	 * 					)
	 */
	abstract function get_supported_themes();

	/**
	 *	Get languages supported by current recaptcha flavor.
	 *
	 *	@return array languages supported by this recaptcha as language_code => Language Name association. 
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
	 *	Get last response of recaptcha check as returned by the google recaptcha service.
	 *
	 *	@return mixed
	 */
	function get_last_result() {
		return $this->_last_result;
	}
	

}

