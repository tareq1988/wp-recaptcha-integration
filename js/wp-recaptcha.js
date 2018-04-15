(function($){
	var $captchas = $('.wp-recaptcha'),
		$other_captchas = $('.g-recaptcha'),
		loadedInterval;

	function init() {
		$other_captchas.removeClass('g-recaptcha');
		$captchas = $('.wp-recaptcha').addClass('g-recaptcha');

		$('<script></script>')
			.attr( 'src', wp_recaptcha.recaptcha_url )
			.appendTo('body');
	}

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

		$other_captchas.addClass('g-recaptcha');
	}

	function captchas_rendered() {
		var rendered = true;
		$other_captchas.each( function(i,el) {
			rendered = captchas_rendered && $(el).html() !== '';
		});
		return rendered;
	}

	// load google recaptcha
	if ( $captchas.length ) {
		if ( $other_captchas.length || $('.nf-form-cont').length ) {
			if ( 'undefined' !== typeof grecaptcha ) {
				wp_recaptcha_loaded();
			} else {
				loadedInterval = setInterval(function(){
					if ( 'undefined' !== typeof grecaptcha ) {
						clearInterval( loadedInterval );
						wp_recaptcha_loaded();
					}
				},333);
			}
		} else {
			init();
		}
		// load recaptcha script
	} else {
		$(document).ready( init );
	}

})(jQuery);
