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
- You can't have more than one reCaptcha on a page. This may affect you for example when 
  you have a contact page with a comment form. This is a limitation of reCaptcha itself.
- On a Contact Form 7 when the reCaptcha is disabled (e.g. for logged in users) the field
  label will be still visible. This is due to CF7 Shortcode architecture, and can't be fixed.

  To handle this there is a filter `recaptcha_disabled_html`. You can return a message for your logged-in 
  users here.

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
	else
		return $is_required;
}
add_filter('recaptcha_required','my_recaptcha_required');
```


Filter `recaptcha_disabled_html`

HTML to be showed when entering a recaptcha is not required.

Example:
```
// will disable recaptcha for nice spambots
function my_recaptcha_disabled_html( $html ) {
	return 'Not four you, my friend!';
}
add_filter('recaptcha_disabled_html','my_recaptcha_disabled_html');
```
