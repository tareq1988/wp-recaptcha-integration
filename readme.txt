=== ABANDONED WordPress ReCaptcha Integration ===
Contributors: podpirate
Donate link: https://noyb.eu/en/support-us
Tags: security, captcha, recaptcha, no captcha, login, signup, contact form 7, ninja forms, woocommerce
Requires at least: 3.8
Tested up to: 4.9
Stable tag: 1.2.3
Requires PHP: 5.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

*ABANDONED* reCaptcha for login, signup, comment forms, Ninja Forms and woocommerce.

== Description ==

**This plugin is no longer maintained.** It will likely vanish from the WordPress plugin repository by September 2020.
Thanks to everyone who contributed or used it.

Used to integrate reCaptcha in your blog. Supported no Captcha as well as old style recaptcha.
Provided of the box integration for signup, login, comment forms and Ninja Forms as well
as a plugin API for your own integrations.

= The Features were: =
- Secure login, signup und comments with a recaptcha.
- Supported old as well as new reCaptcha.
- Worked together with
	- WP Multisite
	- bbPress (thanks to [Tareq Hasan](http://tareq.wedevs.com/)
	- BuddyPress
	- AwesomeSupport (thanks to [Julien Liabeuf](http://julienliabeuf.com/)
	- WooCommerce (Only checkout, registration and login form. Not password reset)
	- [Ninja Forms](http://ninjaforms.com/)
	- cformsII

- For integration in your self-coded forms see this [wiki article](https://github.com/mcguffin/wp-recaptcha-integration/wiki/Custom-Themes-and-Forms) for details.

= Localizations =
- Brazilian Portuguese (thanks to [Vinícius Ferraz](http://www.viniciusferraz.com))
- Spanish (thanks to [Ivan Yivoff](https://github.com/yivi))
- Italian (thanks to [Salaros](http://blog.salaros.com/))
- German

Latest Files on GitHub: [https://github.com/mcguffin/wp-recaptcha-integration](https://github.com/mcguffin/wp-recaptcha-integration)

= Compatibility =

On a **WP Multisite** you could either activate the plugin network wide or on a single site.

Activated on a single site everything worked as usual.

With network activation entering the API key and setting up where a captcha was required
was up to the network admin. A blog admin could override the API key e.g. when his blog is
running under his/her own domain name.


= Known Limitations =
- You couldn't have more than one old style reCaptcha on a page. This was a limitiation of
  reCaptcha itself. If that was an issue for you, you should have used the no Captcha Form.

- A No Captcha definitely required client side JavaScript enabled. That was how it did its
  sophisticated bot detection magic. There have been no fallbacks. If your visitor hadn't
  JS enabled the captcha test was not letting him through.

- On a **Contact Form 7** when the reCaptcha was disabled (e.g. for logged in users) the field
  label has been be still visible. This was due to CF7 Shortcode architecture, and couldn't be fixed.

  To handle this there was a filter `recaptcha_disabled_html`. You could return a message for your logged-in
  users here. Check out the [GitHub Repo](https://github.com/mcguffin/wp-recaptcha-integration) for details.

- As of version 4.3 CF7 came with its own recaptcha. Both were supposed to work together.
  I you want to keep the WP ReCaptcha functionality, e.g. if you wanted to hide the captcha
  from known users, you could leave the integration in the CF7 settings unconfigured.

- Old style reCaptcha did not work together with **WooCommerce**.

- In **WooCommerce** the reset password form could not be protected by a captcha. Woocommerce did
  not fire any action in the lost password form, so there was no way for the plugin to hook in.
  Take a look at [this thread](https://wordpress.org/support/topic/captcha-not-showing-on-lost-password-page?replies=7) for a workaround.

- Due to a lack of filters there was no (and as far as one could see, there will never would have been)
  support for the **MailPoet** subscription form.

== Installation ==

As th edevelpoment of plugin has stopped quite some time ago, you really should not install it.

== Frequently asked questions ==

= Why did you abandon the plugin? =

**The short answer:** Privacy concerns.

**The long answer:** Googles (and others) business model is to record as much as 
possible of your behaviour and to turn it into a model of your future behaviour 
in order to sell it to who ever is willing to pay for it. every little bit you do 
on the internet is a small stroke in the big picture which is showing you – your 
fears and desires, your likes and dislikes, your days and nights – you name it.

As long as you only see some tailor-made ads, you may think this is not be a big 
problem. In risk assessment it may become one. In politics it definetly is. In 
2020 we see personality profiles being used in dubious political campaigns, 
asymmetric warfare and as a suppression technique in numerous dictatorships. 
Your benevolent despot from the future knows what comes next...

Like hydrogen bombs personality profiles generated from behavioural data should 
not exists in the first place. The least I can do as a developer, is to not help 
collecting it.

Thanks for reading that far.

= The login captcha sayed 'ERROR: (something somthing)'. What could I do? =

If it sayed 'Invalid sitekey' and you checked the 'Prevent lockout' option on the plugin
settings (it's on by default) you could log in with an administrator account and ignore the
captcha. If the keys were really invalid, the plugin would have been letting you in, so you could set up a
new keypair.

When you've seen "Invalid domain for site key", then the key was okay in general, but not for
your domain. The server could not test this case, so an effective lockout prevention was not
possible.

You would either needed one of the following:
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


**If you had Database access**

1. Execute the following SQL-Commands in your Database:
   <code>DELETE FROM wp_options WHERE option_name = 'recaptcha_publickey';</code>
   <code>DELETE FROM wp_options WHERE option_name = 'recaptcha_privatekey';</code>

   (Please note that `wp_options` might have a different prefix in your installation.)

2. After the login you would have seen a message asking you to set up the API keys.

3. Set up a new keypair on Google and test it.


**If none of these worked for you**

That was too bad...

= Privacy: Did the captcha send the visitors IP address to google? =

Yes and no. The captcha verification process, comming into effect after the user has solved
the challenge does not require the disclosure of the visitors IP address, so it was omitted.

But everything related to the displaying of the captcha widget like the challenge image,
the JavaScripts and so on is loaded directly from Google and is very likely to be logged,
evaluated and stored forever.

In other words: Google knows which (recaptcha protected) website is accessed from which IP.

If that's an issue for you, you better use a self hosted solution.

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


== Screenshots ==

1. Plugin Settings (v 1.1.4)
2. Ninja Form Integration
3. Contact Form 7 Integration


== Changelog ==

= 1.3.0 =
- Drop support for legacy recaptcha
- Drop support for WP < 4.2
- Fix: WooCommerce checkout Error (thanks to [ywatt](https://github.com/ywatt))
- Fix: Textdomain loading (Thanks, [Bajoras](https://github.com/Bajoras) for bringing this to my attetnion)

= 1.2.0 =
- Support [cformsII](https://wordpress.org/plugins/cforms2/) (thanks to [Bastian Germann](https://github.com/bgermann))
- Support for Password Reset Protection for older woocommerce Versions [ingomarent](https://github.com/ingomarent)
- L10n: Czech (thanks to [František Zatloukal](https://github.com/frantisekz))
- Fix potential PHP Warnngs (thanks to [Gennady Kovshenin](https://github.com/soulseekah))

= 1.1.11 =
- Code: Move plugin main class to include directory
- Update: Disable 2.0 updates on PHP < 5.4

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

== Upgrade notice ==

Version 1.3.2 only brings a deprecation notice to the wp admin.
