<?php
/*
Plugin Name: WP reCaptcha Integration
Plugin URI: https://wordpress.org/plugins/wp-recaptcha-integration/
Description: Integrate reCaptcha in your blog. Supports no Captcha (new style recaptcha) as well as the old style reCaptcha. Provides of the box integration for signup, login, comment forms and lost password.
Version: 2.0.0
Author: Jörn Lund
Author URI: https://github.com/mcguffin/
Text Domain: wp-recaptcha-integration
*/

/*  Copyright 2014  Jörn Lund  (email : joern AT podpirate DOT org)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if ( version_compare( PHP_VERSION, '5.3', '<') ) {

	// add admin notice, disable plugin
	function wp_recaptcha_php_warning() {
		?>
		<div class="error">
			<p><?php
				_e( 'WP Recaptcha Integration requires at least PHP 5.4', 'wp-recaptcha-integration' );
			?></p>
		</div>
		<?php
	}

	add_action( 'admin notices', 'wp_recaptcha_php_warning' );

	return;
}

define( 'WP_RECAPTCHA_FILE', __FILE__ );
define( 'WP_RECAPTCHA_PATH', dirname(__FILE__) );
define( 'WP_RECAPTCHA_PLUGIN_FILE', basename( __DIR__ ) . '/' . basename( __FILE__ ) );

require_once WP_RECAPTCHA_PATH . '/include/autoload.php';
require_once WP_RECAPTCHA_PATH . '/include/api/api.php';

RecaptchaIntegration\Core\Core::instance();


if ( is_admin() || defined( 'DOING_AJAX' ) ) {

	RecaptchaIntegration\Admin\Admin::instance();
	RecaptchaIntegration\Settings\SettingsPageRecaptcha::instance();

}
