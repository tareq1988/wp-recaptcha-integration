(function($){
	var options = wp_recaptcha_options;

	$(document)
		.on('click','#wp-recaptcha-api-test-button',function(e){
			var $container = $('#wp-recaptcha-api-test');
			e.preventDefault();
			if ( ! $container.find('.g-recaptcha').length ) {
				$.get( {
					url: options.ajax_url,
					data: {
						action: 'recaptcha_render',
					},
					success: function(response) {
						$container.html( response );
						window.wp_recaptcha_init();
					}
				} );
			} else {
				$.post( {
					url: options.ajax_url,
					data: {
						action: 'recaptcha_verify',
						'g-recaptcha-response' : $container.find('[name="g-recaptcha-response"]').val()
					},
					success: function(response) {
						$container.html( response );
					}
				} );

			}
//
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
