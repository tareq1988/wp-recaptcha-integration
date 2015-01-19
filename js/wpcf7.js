/* *
 *	Override Contact form 7 Ajax success callback.
 */
(function($){
	var wpcf7AjaxSuccess = $.wpcf7AjaxSuccess;
	$.wpcf7AjaxSuccess = function(data) {
		wpcf7AjaxSuccess.apply( this , arguments );
		// reload recaptcha on invalid form.
		if ( data.invalids ) {
			( typeof Recaptcha != 'undefined' && Recaptcha.reload() );
			( typeof grecaptcha != 'undefined' && grecaptcha.reset() );
		}
	}
})(jQuery);