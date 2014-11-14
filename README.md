WordPress reCaptcha Integration
===============================

Features
--------
- Secures login, signup and comments with a recaptcha.
- [Ninja Forms](http://ninjaforms.com/) integration
- [Contact Form 7](https://wordpress.org/plugins/contact-form-7/) integration
- Tested with up to WP 4.1-alpha, Ninja Forms 2.8.7, Contact Form 7 4.0.1

Limitations
-----------
- You cannot have more than one reCaptcha on a page. This may affect you for example when 
  you have a contact page with open comments somewhere.

Plugin API
----------
Filter `recaptcha_required`

Returns whether to show a recaptcha or not.

Example:
```
// will disable recaptcha for nice spambots
function my_recaptcha_required( $is_required ) {
	if ( is_nice_spambot() )
		return false;
	else if ( is_ugly_spambot() )
		return true;
}
add_filter('recaptcha_required','my_recaptcha_required');
```
