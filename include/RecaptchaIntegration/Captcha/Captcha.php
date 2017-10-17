<?php

namespace RecaptchaIntegration\Captcha;

use RecaptchaIntegration\Core;

abstract class Captcha extends Core\Singleton {

	/**
	 * Get the captcha HTML
	 *
	 * @param	$attr	array	HTML attributes as key => value association
	 * @return	string	The Captcha HTML
	 */
	abstract function get_captcha( $attr = array() );

	/**
	 * Check the users resonse.
	 * Performs a HTTP request to the google captcha service.
	 *
	 * @return	bool	true when the captcha test verifies.
	 */
	abstract function valid();

	/**
	 * Check if there is captcha data in the current HTTP Request
	 *
	 * @return	bool	true if there is captcha data to be checked
	 */
	abstract function submitted();

}
