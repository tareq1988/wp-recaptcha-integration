WordPress reCaptcha Integration TODO
====================================

- add settings link in plugin list table #75
- 
- select size (should support invisible captcha)
- make send client ip optional
- new lockout feature:
	- at login: "Captcha broken?"
	- form: "Enter Admin login or email"
	- admin gets reset token
	- Form: admin login credentials
	- Submit: delete_option('recaptcha_publickey'); delete_option('recaptcha_privatekey');
- documentation for any plugin integration

						v2			invisible
data-sitekey			x			x
data-theme				x			
data-badge							x				bottomright | bottomleft | inline
data-type				x			x				audio | image
data-size				x			x				compact/normal | invisible
data-tabindex			x			x				0
data-callback			x			x				fn
data-expired-callback	x							fn


js
grecaptcha.render		x			x
grecaptcha.execute					x
grecaptcha.reset		x			x
grecaptcha.getResponse	x			x
