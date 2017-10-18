(function($){
	var options = wp_recaptcha_options;

	function test_captcha() {
		var $container = $('#wp-recaptcha-api-test').addClass('loading-content').append('<span class="spinner"></span>'),
			size = $('[name="recaptcha_size"]:checked').val(),
			response;
		function post_result() {
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

		wp_recaptcha_options.test_callback = function( result ) {
			$container.find('[name="g-recaptcha-response"]').val( result );
			post_result();
		}

		if ( ! $container.find('.g-recaptcha').length ) {
			$.get( {
				url: options.ajax_url,
				data: {
					action: 'recaptcha_render',
					'data-size'		: size,
					'data-type'		: $('[name="recaptcha_type"]:checked').val(),
					'data-theme'	: $('[name="recaptcha_theme"]:checked').val(),
					'data-callback'	: '',
				},
				success: function(response) {
					$container.html( response );
					window.wp_recaptcha_init();
				}
			} );
		} else {
			if ( size === 'invisible' ) {
				grecaptcha.execute( $container.find('.g-recaptcha').attr('data-grecaptcha-id') );
			} else {
				post_result();
			}

		}
	}

	$(document)
		.on('click','#wp-recaptcha-api-test-button',function(e){
			e.preventDefault();
			test_captcha();
//
		});
	$(document).on('change','[name="recaptcha_size"],[name="recaptcha_theme"]', function(e){
		if ( '' !== $('#wp-recaptcha-api-test').html() ) {
			$('#wp-recaptcha-api-test').html('');
			test_captcha();
		}
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
