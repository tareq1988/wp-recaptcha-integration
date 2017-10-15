(function($){
	$(document).on('change','[name="recaptcha_theme"],[name="recaptcha_size"]',function(e){
		$('.recaptcha-preview').attr('data-size',$('[name="recaptcha_size"]:checked').first().val())
		$('.recaptcha-preview').attr('data-theme',$('[name="recaptcha_theme"]:checked').first().val())
	});

	$(document).on('click','#test-api-key' , function(e){
		if ( ! $('#recaptcha-test-head').length )
			$(this).closest('div').append('<div id="recaptcha-test-head" />');
		if ( ! $('#recaptcha-test-result').length )
			$(this).closest('div').append('<div id="recaptcha-test-result" />');

		if ( ! $('#recaptcha-test-head').html() )
			$('#recaptcha-test-head').load( $(this).data('init-href') );

		$('#recaptcha-test-result').load( $(this).prop('href') );
		e.preventDefault();
		e.stopPropagation();
		$(this).hide();

		return false;
	});
	$(document).on('click','#recaptcha-test-verification' , function(e){
		var data = {
			'action' : 'recaptcha-test-verification',
			'_wpnonce' : $('[name="recaptcha-test-verification-nonce"]').val(),
			'g-recaptcha-response' : $('.g-recaptcha-response:first').val()
		};
		$('#recaptcha-test-result').addClass('loading').html('<span class="spinner"></span>');
		$.post( ajaxurl ,
			data ,
			function(response) {
				$('#recaptcha-test-result').html(response);
				$('#test-api-key').show();
			});

		e.preventDefault();
		e.stopPropagation();
		return false;
	});
})(jQuery);
