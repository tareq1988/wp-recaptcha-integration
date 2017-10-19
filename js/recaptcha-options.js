(function($){
	$(document).ready(function(){
		$('[name="recaptcha_flavor"]:checked').trigger('click');
	});
	$(document).on('click','[name="recaptcha_flavor"]',function(){
		$(this).closest('.wrap')
			.removeClass('flavor-recaptcha')
			.removeClass('flavor-grecaptcha')
			.addClass('flavor-'+$(this).val());
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