(function($){
	$(document).ready(function(){
		$('[name="recaptcha_flavor"]:checked').trigger('click');
	});
	$(document).on('click','[name="recaptcha_flavor"]',function(){
		$('.recaptcha-select-theme')
			.removeClass('flavor-recaptcha')
			.removeClass('flavor-grecaptcha')
			.addClass('flavor-'+$(this).val());
	});
	
	
	$(document).on('click','#test-api-key' , function(e){
		console.log( $(this).prop('href') );
		if ( ! $('#recaptcha-test-result').length )
			$(this).closest('div').append('<div id="recaptcha-test-result" />');
		$('#recaptcha-test-result').load( $(this).prop('href') ,{},function(e){console.log(e);});
		e.preventDefault();
		e.stopPropagation();
		$(this).remove();
		return false;
	});
	$(document).on('click','#recaptcha-test-verification' , function(e){
		var data = {
			'action' : 'recaptcha-test-verification',
			'_wpnonce' : $('[name="recaptcha-test-verification-nonce"]').val(),
			'g-recaptcha-response' : $('#g-recaptcha-response').val()
		};
		
		$.post( ajaxurl , 
			data , 
			function(response) {
				$('#recaptcha-test-result').html(response);
			});
		
		e.preventDefault();
		e.stopPropagation();
		return false;
	});
})(jQuery);