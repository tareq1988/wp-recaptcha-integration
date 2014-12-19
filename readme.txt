=== WordPress ReCaptcha Integration ===
Contributors: podpirate
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=F8NKC6TCASUXE
Tags: security, captcha, recaptcha, 
Requires at least: 3.8
Tested up to: 4.1
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

reCaptcha for login, signup, comment forms, Ninja Forms and Contact Form 7.

== Description ==

Integrate reCaptcha in your blog. Supports new style recaptcha. Provides of the box integration 
for signup, login, comment forms, Ninja Forms and contact form 7 as well as a plugin API for 
your own integrations.

= Features: =
- Secures login, signup and comments with a recaptcha.
- Supports old as well as new reCaptcha.
- [Ninja Forms](http://ninjaforms.com/) integration
- [Contact Form 7](https://wordpress.org/plugins/contact-form-7/) integration

Latest Files on GitHub: [https://github.com/mcguffin/wp-recaptcha-integration](https://github.com/mcguffin/wp-recaptcha-integration)

== Installation ==

First follow the standard [WordPress plugin installation procedere](http://codex.wordpress.org/Managing_Plugins).

Then goto the [Google Recaptcha Site](http://www.google.com/recaptcha), sign up your site and enter your API-Keys on the configuration page.

== Frequently asked questions ==

= I found a bug. Where should I post it? =

I personally prefer GitHub but you can post it in the forum as well. The plugin code is here: [GitHub](https://github.com/mcguffin/wp-recaptcha-integration)

= I want to use the latest files. How can I do this? =

Use the GitHub Repo rather than the WordPress Plugin. Do as follows:

1. If you haven't already done: [Install git](https://help.github.com/articles/set-up-git)

2. in the console cd into Your 'wp-content/plugins´ directory

3. type `git clone git@github.com:mcguffin/wp-recaptcha-integration.git`

4. If you want to update to the latest files (be careful, might be untested on Your WP-Version) type git pull´.

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

= 1.0.0 =
Initial Release

== Plugin API ==

The plugin offers some filters to allow themes and other plugins to hook in.

See [GitHub-Repo](https://github.com/mcguffin/wp-recaptcha-integration) for details.