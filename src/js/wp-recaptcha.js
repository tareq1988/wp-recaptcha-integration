(function($){
	var $captchas = $('.wp-recaptcha'),
		$other_captchas = $('.g-recaptcha'),
		loadedInterval;

	window.wp_recaptcha_init = function(){
		$other_captchas.removeClass('g-recaptcha');
		$captchas = $('.wp-recaptcha').addClass('g-recaptcha');

		$('<script></script>')
			.attr( 'src', wp_recaptcha.recaptcha_url )
			.appendTo('body');
	}
	window.wp_recaptcha_submit_form = function(){
		console.log(this,arguments)
	}

	window.wp_recaptcha_loaded = function(){
		if ( ! grecaptcha ) {
			return;
		}

		$captchas.each(function(i,el){
			if ( '' !== $(el).html() ) {
				return;
			}
			var $form = $(el).closest('form'),
				$submit = $form.find('[type="submit"]');
			var opts = {
					sitekey		: wp_recaptcha.site_key,
					theme		: $(el).attr('data-theme'),
					size		: $(el).attr('data-size'),
				},
				cb = $(el).attr('data-callback'),
				submitInterval,
				captcha_inst_id;

			if ( opts.size === 'invisible' ) {
				if ( cb === 'test' && !! window.wp_recaptcha_options) {
					opts.callback = wp_recaptcha_options.test_callback;
				} else {
					$form.on('submit',function(e){
						console.log('form.onsubmit');
						e.preventDefault();
						grecaptcha.execute( captcha_inst_id );
					});
					opts.callback = function(result) {
						$form.find('[name="g-recaptcha-response"]').val( result );
						if ( $form.get(0).submit ) {
							$form.get(0).submit();
						} else {
							$submit.trigger('click');
						}
					};
				}
			} else if ( cb !== '' && cb !== 'test' ) {

				opts.callback = function() {
					$submit.prop( 'disabled', false );
				};
				opts.expiredCallback = function() {
					$submit.prop( 'disabled', true );
				}

				if ( ! $submit.prop( 'disabled', true ).length ) {
					$form.append('<input type="submit" style="visibilit:hidden;width:1px;height;1px;" />')
				}

				if ( cb == 'submit' && $submit.length ) {
					submitInterval = setInterval(function(){
						if ( ! $submit.prop( 'disabled' ) ) {
							clearInterval( submitInterval );
							// form.submit() does not work
							$submit.trigger('click');
						}
					}, 100 );
				}
			}

			captcha_inst_id = grecaptcha.render( el, opts );
			$(el).attr('data-grecaptcha-id', captcha_inst_id );
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
			window.wp_recaptcha_init();
		}
		// load recaptcha script
	} else {
		$(document).ready( window.wp_recaptcha_init );
	}

})(jQuery);
