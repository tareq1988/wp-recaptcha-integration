WordPress reCaptcha Integration TODO
====================================

- select size (should support invisible captcha)
- make send client ip optional
- new lockout feature:
	- at login: "Captcha broken?"
	- form: "Enter Admin login or email"
	- admin gets reset token
	- Form: admin login credentials
	- Submit: delete_option('recaptcha_publickey'); delete_option('recaptcha_privatekey');
- documentation for any plugin integration
