WordPress reCaptcha Integration
===============================

Features
--------
- Secures login, signup and comments with a recaptcha.
- Supports old as well as new reCaptcha.
- [Ninja Forms](http://ninjaforms.com/) integration
- [Contact Form 7](https://wordpress.org/plugins/contact-form-7/) integration
- Tested with up to WP 4.2-alpha, Ninja Forms 2.8.7, Contact Form 7 4.0.1

Limitations
-----------
- Currently you can't have more than one reCaptcha on a page. 
  This may affect you for example when you have a contact page with a comment form. 
  With old style recaptcha reCaptcha this cant be fixed. For the new Style reCaptchas 
  this is due to be implemented in the near future.

- On a Contact Form 7 when the reCaptcha is disabled (e.g. for logged in users) the field
  label will be still visible. This is due to CF7 Shortcode architecture, and can't be fixed.

  To handle this there is a filter `recaptcha_disabled_html`. You can return a message for your logged-in 
  users here.

Plugin API
----------

#### Filter `recaptcha_required`

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


#### Filter `recaptcha_disabled_html`

HTML to be showed when entering a recaptcha is not required.

Example:
```
// will disable recaptcha for nice spambots
function my_recaptcha_disabled_html( $html ) {
	return 'Not four you, my friend!';
}
add_filter('recaptcha_disabled_html','my_recaptcha_disabled_html');
```

Support
-------
You like what you see? Maybe you already make some money with it? 
Here are two ways to keep me rocking:

[![Flattr this git repo](http://api.flattr.com/button/flattr-badge-large.png)](https://flattr.com/submit/auto?user_id=joern.lund&url=https://github.com/mcguffin/wp-recaptcha-integration&title=WP%20Recaptcha%20Integration&language=php&tags=github&category=software)
<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=F8NKC6TCASUXE"><img src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!" /></a>
