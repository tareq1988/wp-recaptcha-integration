=== WordPress ReCaptcha Integration ===
Contributors: podpirate
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=F8NKC6TCASUXE
Tags: security, captcha, recaptcha, no captcha, login, signup, contact form 7, ninja forms
Requires at least: 3.8
Tested up to: 4.1
Stable tag: 1.0.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

reCaptcha for login, signup, comment forms, Ninja Forms and Contact Form 7.

== Description ==

Integrate reCaptcha in your blog. Supports no Captcha as well as old style recaptcha. 
Provides of the box integration for signup, login, comment forms, Ninja Forms and contact 
form 7 as well as a plugin API for your own integrations.

= Features: =
- Secures login, signup and comments with a recaptcha.
- Supports old as well as new reCaptcha.
- Multisite Support
- BuddyPress Support
- [Ninja Forms](http://ninjaforms.com/) integration
- [Contact Form 7](https://wordpress.org/plugins/contact-form-7/) integration

Latest Files on GitHub: [https://github.com/mcguffin/wp-recaptcha-integration](https://github.com/mcguffin/wp-recaptcha-integration)

= Multisite support =

On a WP Multisite support you can either activate the plugin network wide or on a single site.

Activated on a single site everything works as usual.

With network activation entering the API key and setting up where a recaptcha is required 
is up to the network admin. A blog admin can only select a theme and override the API key 
if necessary.


= Known Limitations =
- You can't have more than one old style reCaptcha on a page. This is a limitiation of 
  reCaptcha itself. If that's an issue for you, you should use the no Captcha Form.

- On a Contact Form 7 when the reCaptcha is disabled (e.g. for logged in users) the field
  label will be still visible. This is due to CF7 Shortcode architecture, and can't be fixed.

  To handle this there is a filter `recaptcha_disabled_html`. You can return a message for your logged-in 
  users here. Check out the [GitHub Repo](https://github.com/mcguffin/wp-recaptcha-integration) for details.

== Installation ==

First follow the standard [WordPress plugin installation procedere](http://codex.wordpress.org/Managing_Plugins).

Then go to the [Google Recaptcha Site](http://www.google.com/recaptcha), sign up your site and enter your API-Keys on the configuration page.

== Frequently asked questions ==

= The captcha does not show up. What’s wrong? =

On the plugin settings page check out if the option “Disable for known users” is activated (it is by default).
Then log out (or open your page in a private browser window) and try again. 
If the problem still persist, Houson really has a problem, and you are welcome to post a support request. 

= Disabled submit buttons should be grey! Why aren't they? =

Very likely the Author of your Theme didn't care that a non functinal form element should 
look different than a functional one. This how you can overcome that issue: 

- Go to (https://gist.github.com/mcguffin/7cbfb0dab73eb32cb4a2)
- Click the "Download Gist" button
- Unpack the `.tar.gz` file.
- Create a zip Archive out of the included file `grey-out-disabled.php` and name it `grey-out-disabled.zip`.
- Install and activate the zip like any other WordPress plugin

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

Either post it on [GitHub](https://github.com/mcguffin/wp-recaptcha-integration) or—if you are working on a cloned repository—send me a pull request.

= Will you accept translations? =

Yep sure! (And a warm thankyou in advance.) It might take some time until your localization 
will appear in an official plugin release, and it is not unlikely that I will have added 
or removed some strings in the meantime. 

As soon as there is a [public centralized repository for WordPress plugin translations](https://translate.wordpress.org/projects/wp-plugins) 
I will migrate all the translation stuff there.


== Screenshots ==

1. Plugin Settings
2. Ninja Form Integration
3. Contact Form 7 Integration

== Changelog ==

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
