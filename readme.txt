=== WordPress ReCaptcha Integration ===
Contributors: podpirate
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=F8NKC6TCASUXE
Tags: security, captcha, recaptcha, no captcha, login, signup, contact form 7, ninja forms, woocommerce
Requires at least: 3.8
Tested up to: 4.4
Stable tag: 1.1.10
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

reCaptcha for login, signup, comment forms, Ninja Forms and woocommerce.

== Description ==

Integrate reCaptcha in your blog. Supports no Captcha as well as old style recaptcha. 
Provides of the box integration for signup, login, comment formsand Ninja Forms as well 
as a plugin API for your own integrations.

= Features: =
- Secures login, signup und comments with a recaptcha.
- Supports old as well as new reCaptcha.
- Works together with
	- WP Multisite
	- bbPress (thanks to [Tareq Hasan](http://tareq.wedevs.com/)
	- BuddyPress
	- AwesomeSupport (thanks to [Julien Liabeuf](http://julienliabeuf.com/)
	- WooCommerce (Only checkout, registration and login form. Not password reset)
	- [Ninja Forms](http://ninjaforms.com/)

- For integration in your self-coded forms see this [wiki article](https://github.com/mcguffin/wp-recaptcha-integration/wiki/Custom-Themes-and-Forms) for details.

= Localizations =
- Brazilian Portuguese (thanks to [Vinícius Ferraz](http://www.viniciusferraz.com))
- Spanish (thanks to [Ivan Yivoff](https://github.com/yivi))
- Italian (thanks to [Salaros](http://blog.salaros.com/))
- German

Latest Files on GitHub: [https://github.com/mcguffin/wp-recaptcha-integration](https://github.com/mcguffin/wp-recaptcha-integration)

= Compatibility =

On a **WP Multisite** you can either activate the plugin network wide or on a single site.

Activated on a single site everything works as usual.

With network activation entering the API key and setting up where a captcha is required 
is up to the network admin. A blog admin can override the API key e.g. when his blog is 
running under his/her own domain name. 


= Known Limitations =
- You can't have more than one old style reCaptcha on a page. This is a limitiation of 
  reCaptcha itself. If that's an issue for you, you should use the no Captcha Form.

- A No Captcha definitely requires client side JavaScript enabled. That's how it does its 
  sophisticated bot detection magic. There is no fallback. If your visitor does not have 
  JS enabled the captcha test will not let him through.

- On a **Contact Form 7** when the reCaptcha is disabled (e.g. for logged in users) the field
  label will be still visible. This is due to CF7 Shortcode architecture, and can't be fixed.

  To handle this there is a filter `recaptcha_disabled_html`. You can return a message for your logged-in 
  users here. Check out the [GitHub Repo](https://github.com/mcguffin/wp-recaptcha-integration) for details.

- As of version 4.3 CF7 comes with its own recaptcha. Both are supposed to work together.
  I you want to keep the WP ReCaptcha functionality, e.g. if you want to hide the captcha 
  from known users, leave the integration in the CF7 settings unconfigured.

- Old style reCaptcha does not work together with **WooCommerce**. 

- In **WooCommerce** the reset password form can not be protected by a captcha. Woocommerce does 
  not fire any action in the lost password form, so there is no way for the plugin to hook in.
  Take a look at [this thread](https://wordpress.org/support/topic/captcha-not-showing-on-lost-password-page?replies=7) for a workaround.

- Due to a lack of filters there is no (and as far as one can see, there will never be) 
  support for the **MailPoet** subscription form.

== Installation ==

First follow the standard [WordPress plugin installation procedere](http://codex.wordpress.org/Managing_Plugins).

Then go to the [Google Recaptcha Site](http://www.google.com/recaptcha), register your site and enter your API-Keys on the configuration page.

== Frequently asked questions ==

= The login captcha says 'ERROR: (something somthing)'. What can I do? =

If it says 'Invalid sitekey' and you checked the 'Prevent lockout' option on the plugin 
settings (it's on by default) you can log in with an administrator account and ignore the 
captcha. If the keys are really invalid, the plugin will let you in, so you can set up a 
new keypair. 

When you see "Invalid domain for site key", then the key is okay in general, but not for 
your domain. The server can not test this case, so an effective lockout prevention is not 
possible.

You will either need one of the following:
- access to the settings for your sitekey on [reCaptcha API key administration](https://www.google.com/recaptcha/admin#list)
- access to your WordPress installation (via SSH or FTP) or database access 
- database access


**With API key admin**

1. Look at source code of the login page.

2. Find the part saying <code>data-sitekey="XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX"</code>
   (The XXX-part should be your sitekey.)

3. Go to the [Google reCaptcha API key administration](https://www.google.com/recaptcha/admin#list)

4. Find the list entry with the sitekey from step 2

5. If lockout prevention is enabled you can simply delete the key set up a new one.
   If not enter your domain name at "Domains" in a new line and wait up to 30 minutes. 


**With FTP Access:**

1. Add this line of Code somewhere at the end of your theme functions.php: 
   <code>add_filter('wp_recaptcha_required','__return_false');</code>
   
   This will disable the chaptcha everywhere.
   
2. Set up a new keypair and test it.

3. Remove the line above from your theme functions.php.


**If you have Database access**

1. Execute the following SQL-Commands in your Database: 
   <code>DELETE FROM wp_options WHERE option_name = 'recaptcha_publickey';</code> 
   <code>DELETE FROM wp_options WHERE option_name = 'recaptcha_privatekey';</code>

   (Please note that `wp_options` might have a different prefix in your installation.)
   
2. After the login you will see a message asking you to set up the API keys.

3. Set up a new keypair on Google and test it.


**If none of these works for you**

That's too bad...


= I can't get it to work with my custom comments form. Will you fix for me? =

Nope. I cannot give support on your individual projects for free, no matter how many one 
star reviews you will give me. Have a look at the project wiki or find a WordPress coder. 


= Privacy: Will the captcha send the visitors IP address to google? =

Yes and no. The captcha verification process, comming into effect after the user has solved 
the challenge does not require the disclosure of the visitors IP address, so it is omitted.

But everything related to the displaying of the captcha widget like the challenge image, 
the JavaScripts and so on is loaded directly from Google and is very likely to be logged, 
evaluated and stored forever.

In other words: Google knows which (recaptcha protected) website is accessed from which IP. 

If that's an issue for you, you better use a self hosted solution. 


= Will you support plugin XYZ? =

If XYZ stands for a widely used free and OpenSource plugin in active development with some 
100k+ downloads I will give it a try. Just ask. 

If XYZ is some rarely used plugin (about 1k+ active installs or so), I will accept pull 
requests on github and push it to the WP repository. Please note that in such cases I will 
not feel responsible for code maintainance.


= The captcha does not show up. What’s wrong? =

On the plugin settings page check out if the option “Disable for known users” is activated (it is by default).
Then log out (or open your page in a private browser window) and try again. 

If only the comment form is affected, it is very likely that your Theme does not use the 
`comment_form_defaults` filter. (That‘s where I add the captcha HTML, to make it appear 
right before the submit button.) You will have to use another hook, e.g. `comment_form_after_fields`.

Here is some code that will fix it:

- Go to (https://gist.github.com/mcguffin/97d7f442ee3e92b7412e)
- Click the "Download Gist" button
- Unpack the `.tar.gz` file.
- Create a zip Archive out of the included file `recaptcha-comment-form-fix.php` and name it `recaptcha-comment-form-fix.zip`.
- Install and activate it like any other WordPress plugin

If the problem still persist, Houston really has a problem, and you are welcome to post a support request. 


= Disabled submit buttons should be grey! Why aren't they? =

Very likely the Author of your Theme didn't care that a non functinal form element should 
look different than a functional one. This how you can overcome that issue: 

- Go to (https://gist.github.com/mcguffin/7cbfb0dab73eb32cb4a2)
- Click the "Download Gist" button
- Unpack the `.tar.gz` file.
- Create a zip Archive out of the included file `grey-out-disabled.php` and name it `grey-out-disabled.zip`.
- Install and activate it like any other WordPress plugin


= I want my visitors to solve only one Captcha and then never again. Is that possible? =

Yes. You can store in a session if a captcha was solved, and use the `wp_recaptcha_required` 
filter to supress further captchas. See (https://github.com/mcguffin/wp-recaptcha-integration#real-world-example) 
for a code example.


= I found a bug. Where should I post it? =

I personally prefer GitHub but you can post it in the forum as well. The plugin code is here: [GitHub](https://github.com/mcguffin/wp-recaptcha-integration)


= I want to use the latest files. How can I do this? =

Use the GitHub Repo rather than the WordPress Plugin. Do as follows:

1. If you haven't already done: [Install git](https://help.github.com/articles/set-up-git)

2. in the console cd into Your 'wp-content/plugins´ directory

3. type `git clone git@github.com:mcguffin/wp-recaptcha-integration.git`

4. If you want to update to the latest files (be careful, might be untested with your WP-Version) type `git pull.

Please note that the GitHub repository is more likely to contain unstable and untested code. Urgent fixes 
concerning stability or security (like crashes, vulnerabilities and alike) are more likely to be fixed in 
the official WP plugin repository first.


= I found a bug and fixed it. How can I contribute? =

Either post it on [GitHub](https://github.com/mcguffin/wp-recaptcha-integration) or—if you are working on a forked repository—send me a pull request.


= Will you accept translations? =

Since late 2015 WordPress.org offers a plugin translation API. Just use the 
"Translate this plugin" button in the right sidebar.


== Screenshots ==

1. Plugin Settings (v 1.1.4)
2. Ninja Form Integration
3. Contact Form 7 Integration


== Changelog ==

= 1.1.10 =
- Feature: Changed Contact Form 7 support: As of version 4.3 CF7 comes with its own recaptcha. The plugin now just makes sure both captchas work together. It also keeps the API keys in sync.
- Fix: Disable Captcha for logged in users now respects custom roles without read capability. (Thanks to [@lainme](https://github.com/lainme))

= 1.1.9 =
- Fix: Layout issues on recaptcha nojs fallback (thanks to [nurupo](https://github.com/nurupo))
- Metadata: add plugin textdomain

= 1.1.8 =
- Feature: Support AwesomeSupport
- Feature: Support bbPress new Topics and posts
- L10n: italian
- Fix: Layout issue on theme twenty fifteen (recaptcha)

= 1.1.7 =
- Fix: Compatibility with CF7 4.2 User Interface

= 1.1.6 =
- Fix: Skip Ninja Forms required check

= 1.1.5 =
- Feature: Noscript fallback option for noCaptcha
- Feature: Option for WP 4.2 compatible hook on comment form.
- Fix: Remove automatic key testing in Backend.
- L10n: Improved de_DE ([thx @quassy](https://github.com/quassy))
- L10n: Updated pt_BR ([thx again man](http://www.viniciusferraz.com))

= 1.1.4 =
- Comments: get back to `comment_form_defaults` filter (was introduced in 1.1.3)
- Fix: Get key option
- Fix: Key testing return value

= 1.1.3 =
- Comments: use filter `comment_form_submit_button` in WP >= 4.2
- WooCommerce: Add action listener to `woocommerce_lostpassword_form` (probably functional in WC 2.3.8).
- Introduce `{$feature}recaptcha_html` filters for custom form integration.
- Introduce filter `wp_recaptcha_cf7_shortcode_wrap`.

= 1.1.2 =
- Fix: Was not possible to uncheck lockout setting.
- Fix: Potential JS error when 'Disable Submit Buttons' was enabled.

= 1.1.1 =
- Filter: `wp_recaptcha_do_scripts` allow disabling recaptcha scripts on certain pages.
- Filter: `wp_recaptcha_print_login_css`, allow disabling login CSS.
- Fix: Didn't render with Submit Button Disabling checked
- Fix: Use `add_query_arg()` to generate recaptcha API URL

= 1.1.0 =
- Feature: Prevent Lockout - Admins can still log in when the API keys are invalid
- Feature: Customize error message on contact form 7 and ninja forms
- Filters: add actions `recaptcha_print`, `print_comments_recaptcha` and filters `recaptcha_valid`, `recaptcha_error` for custom forms.
- Redesign: settings page
- Fix: woocommerce checkout form: fix unnecessary captcha test on new customer registration
- Fix: settings: testing keys for multiple times
- Fix: settings: key setup -> cancel button target
- Fix: settings: test keys only with a nocaptcha

= 1.0.9 =
- Fix: Preserve PHP 5.2 compatibility

= 1.0.8 =
- Feature: Individually set captcha theme in CF7 and Ninja forms (NoCaptcha only, old recaptcha not supported)
- Fix: PHP Warning in settings.
- Fix: PHP Fatal when check a old reCaptcha.
- Fix: js error with jQuery not present
- Fix: woocommerce checkout
- L10n: add Spanish

= 1.0.7 =
- Fix: Fatal error in settings
- Fix: messed up HTML comments
- Code: Put NinjaForms + CF7 handling into singletons

= 1.0.6 =
- Code: separate classes for recaptcha / nocaptcha
- Code: Class autoloader
- Fix: avoid double verification
- Fix: CF7 4.1 validation

= 1.0.5 =
- Add Language option
- Brasilian Portuguese localization
- Fix: conditionally load recaptcha lib.
- Fix: js error after cf7 validation error.

= 1.0.4 =
- Add WooCommerce Support (checkout page)
- Multisite: protect signup form as well.
- Reset noCaptcha after ajax calls (enhance compatibility with Comment Form Ajax plugin)
- Fix: incorrect redirect after saving Network settings

= 1.0.3 =
- Add BuddyPress support
- Action hook for wp_recaptcha_checked
- NoCaptcha: add non-js fallback.
- Code: pass `WP_Error` to `wp_die()` when comment captcha fails.
- Code: Rename filters recaptcha_required &gt; wp_recaptcha_required and recaptcha_disabled_html &gt; wp_recaptcha_disabled_html 
- Happy New Year!

= 1.0.2 =
- Feature: option to disable submit button, until the captcha is solved
- Rearrange comment form (put captcha above submit button)
- Fix: NoCaptcha did not refresh after submitting invalid ninja form via ajax

= 1.0.1 =
- Fix API Key test
- Fix theme select

= 1.0.0 =
- Allow more than one no Captcha per page
- Test captcha verification in Settings
- Multisite support.

= 0.9.1 =
- Add testing tool for checking the api key.
- Fixes

= 0.9.0 =
Initial Release

== Plugin API ==

The plugin offers some filters to allow themes and other plugins to hook in.

See [GitHub-Repo](https://github.com/mcguffin/wp-recaptcha-integration) for details.
