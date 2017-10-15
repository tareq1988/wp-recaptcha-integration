(function($){
	var $captchas = $('.wp-recaptcha');
	window.wp_recaptcha_loaded = function(){
		if ( ! grecaptcha ) {
			return;
		}

		$captchas.each(function(i,el){
			var $form = $(el).closest('form');
			var opts = {
					sitekey		: wp_recaptcha.site_key,
					theme		: $(el).attr('data-theme'),
					size		: $(el).attr('data-size'),
				},
				cb = $(el).attr('data-callback'),
				submitInterval;

				if ( cb !== '' ) {

					opts.callback = function() {
						$form.find('[type="submit"]').prop( 'disabled', false );
					};
					opts.expiredCallback = function() {
						$form.find('[type="submit"]').prop( 'disabled', true );
					}

					if ( ! $form.find('[type="submit"]').prop( 'disabled', true ).length ) {
						$form.append('<input type="submit" style="visibilit:hidden;width:1px;height;1px;" />')
					}

					if ( cb == 'submit' && $form.find('[type="submit"]').length ) {
						submitInterval = setInterval(function(){
							if ( ! $form.find('[type="submit"]').prop( 'disabled' ) ) {
								clearInterval(submitInterval);
								// form.submit() does not work
								$form.find('[type="submit"]').trigger('click');
							}
						}, 100 );
					}
				}

			grecaptcha.render( el, opts );
		});
	}
	// load google recaptcha
	if ( $captchas.length ) {

		// load recaptcha script
		$('<script></script>')
			.attr( 'src', wp_recaptcha.recaptcha_url )
			.appendTo('body');
	}


})(jQuery);
