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
				callbacks = {
					enable : function(){
						$form.find('[type="submit"]').prop( 'disabled', false );
					},
				},
				cb = $(el).attr('data-callback'),
				submitInterval;

				if ( cb !== '' ) {
					opts.callback = callbacks.enable;
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

// var recaptcha_widgets={};
// function wp_recaptchaLoadCallback() {
// 	try {
// 		grecaptcha;
// 	} catch(err){
// 		return;
// 	}
// 	var e = document.querySelectorAll('.g-recaptcha.wp-recaptcha'),
// 		form_submits;
// 		console.log(e[i]);
//
// 	for (var i=0;i<e.length;i++) {
// 		(function(el){
// <?php if ( WP_reCaptcha::instance()->get_option( 'recaptcha_disable_submit' ) ) { ?>
// 			var form_submits = get_form_submits(el).setEnabled(false), wid;
// <?php } else { ?>
// 			var wid;
// <?php } ?>
// 			// check if captcha element is unrendered
// 			if ( ! el.childNodes.length) {
// 				wid = grecaptcha.render(el,{
// 					'sitekey':'<?php echo $sitekey ?>',
// 					'theme':el.getAttribute('data-theme') || '<?php echo WP_reCaptcha::instance()->get_option('recaptcha_theme'); ?>',
// 					'size':el.getAttribute('data-size') || '<?php echo WP_reCaptcha::instance()->get_option('recaptcha_size'); ?>'
// <?php if ( WP_reCaptcha::instance()->get_option( 'recaptcha_disable_submit' ) ) {
// ?>							,
// 					'callback' : function(r){ get_form_submits(el).setEnabled(true); /* enable submit buttons */ }
// <?php } ?>
// 				});
// 				el.setAttribute('data-widget-id',wid);
// 			} else {
// 				wid = el.getAttribute('data-widget-id');
// 				grecaptcha.reset(wid);
// 			}
// 		})(e[i]);
// 	}
// }
//
// // if jquery present re-render jquery/ajax loaded captcha elements
// if ( typeof jQuery !== 'undefined' )
// 	jQuery(document).ajaxComplete( function(evt,xhr,set){
// 		if( xhr.responseText && xhr.responseText.indexOf('<?php echo $sitekey ?>') !== -1)
// 			wp_recaptchaLoadCallback();
// 	} );
