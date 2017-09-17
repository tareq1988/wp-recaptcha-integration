WordPress reCaptcha Integration
===============================

This is the official github repository of the [WP reCaptcha integration plugin](https://wordpress.org/plugins/wp-recaptcha-integration/) 
plugin. This repo might contain untested and possibly unstable or insecure code. So use it on your own risk. 

Features
--------
- Secures login, signup and comments with a recaptcha.
- Supports old as well as new reCaptcha.
- [Ninja Forms](http://ninjaforms.com/) integration
- Tested with up to WP 4.2-alpha, Ninja Forms 2.8.7, Contact Form 7 4.3, WooCommerce 2.3.5, cformsII 14.14
- As of version 4.3 Contact Form 7 comes with its own recaptcha. This plugin now makes sure 
  that both captchas will work together.
  If you already have configured CF7 with your API Keys WP ReCaptcha will try to get these 
  keys from the CF7 settings upon activation.

Limitations
-----------
- You can't have more than one old style reCaptcha on a single page. This is a limitiation 
  of reCaptcha itself. If that's an issue for you, you should use the no Captcha Form.

- NoCaptcha only works with JavaScript enabled.

- On a **Contact Form 7** when the reCaptcha is disabled (e.g. for logged in users) the 
  field label will be still visible. This is due to CF7 Shortcode architecture, and can't 
  be fixed.
  
  To handle this, there is a filter `recaptcha_disabled_html`. You can return a message for 
  your logged-in users here.
  
- CF7 4.3+ comes with its own recaptcha. The plugin just keeps its captcha rendering apart 
  from the CF7 rendering to make both work together. 

Plugin API
----------

#### Action `wp_recaptcha_checked`

Fires after a recaptcha has been checked.

##### Example

```

// will redirect to http://honeypot.org when captcha test fails.
function my_recaptcha_checked( $check_successful ) {
	if ( ! $check_successful ) 
		wp_redirect( 'http://honeypot.org' );
}
add_filter('wp_recaptcha_checked','my_recaptcha_checked');
```

##### Real World Example

Disable captcha if it has been solved once.
```
// safely start a session
function my_session_start( ) {
	$sid = session_id();
	if ( empty( $sid ) ) {
		session_start();
	}
}
add_action('init','my_session_start',1);

// don't requiere captcha, if session says so
function my_wp_recaptcha_required( $is_required ) {
	if ( isset( $_SESSION['recaptcha_solved'] ) && $_SESSION['recaptcha_solved'] )
		return false;
	return $is_required;
}
add_filter('wp_recaptcha_required' , 'my_wp_recaptcha_required');

// store in session if captcha solved
function my_wp_recaptcha_checked( $check_successful ) {
	if ( $check_successful )
		$_SESSION['recaptcha_solved'] = true;
}
add_action('wp_recaptcha_checked','my_wp_recaptcha_checked');
```


#### Filter `wp_recaptcha_required`

Returns whether to show a recaptcha or not.

##### Example
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
add_filter('wp_recaptcha_required','my_recaptcha_required');
```


#### Filter `wp_recaptcha_disabled_html`

HTML to be showed when entering a recaptcha is not required.

##### Example
```
// will disable recaptcha for nice spambots
function my_recaptcha_disabled_html( $html ) {
	return 'Not for you, my friend!';
}
add_filter('wp_recaptcha_disabled_html','my_recaptcha_disabled_html');
```

#### Filter `wp_recaptcha_language`

Override the recaptcha language attribute. Possible return values are depending on the 
languages supported by the current captcha flavor.

**Note:** This filter is used internally in order to set the language according to the 
current WP language. I cannot imagine a real-world use case, but for the sake of 
completeness I documented it here.

##### Example
```
// will set language to french if language is german
// (French is considered a very elegant and pleasing language in germany, 
// your vistors from DE will love you for it!)
function my_recaptcha_language( $lang ) {
	if ( $lang == 'de' )
		return 'fr';
	return $lang;
}
add_filter('wp_recaptcha_language','my_recaptcha_language');
```

#### Filter `wp_recaptcha_do_scripts`

Use this to disable loading of the recaptcha scripts.

##### Example
```
// Will disable script loading on the frontend
if ( ! is_admin() && ! in_array( $GLOBALS['pagenow'], array( 'wp-login.php', 'wp-register.php' ) ) )
	add_filter('wp_recaptcha_do_scripts','__return_false');
	
```

#### Filter `wp_recaptcha_print_login_css`

Use this to disable the css on the login page.

##### Example
```
// Will disable plugin css on the login page
add_filter('wp_recaptcha_print_login_css','__return_false');
	
```

#### Filter `{$feature}recaptcha_html`

I order to integrate recaptcha in your custom forms, there are six filters added in order 
to get the recaptcha HTML:
 - `recaptcha_html` 
 - `lostpassword_recaptcha_html`
 - `login_recaptcha_html`
 - `signup_recaptcha_html`
 - `comments_recaptcha_html`
 - `wc_checkout_recaptcha_html`

Use `apply_filters('feature_recaptcha_html','')` to retrieve the HTML.

##### Example

    // $recaptcha_html will hold the recaptcha HTML if 
    // a captcha validation is required AND recaptcha is enabled for comment forms
    $recaptcha_html = apply_filters( 'comments_recaptcha_html' , '' );
	

