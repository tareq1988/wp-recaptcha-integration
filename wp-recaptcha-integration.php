<?php
/*
Plugin Name: WP reCaptcha Integration
Plugin URI: https://wordpress.org/plugins/wp-recaptcha-integration/
Description: Integrate reCaptcha in your blog. Supports no Captcha (new style recaptcha). Provides of the box integration for signup, login, comment forms and lost password.
Version: 1.2.4
Author: weDevs
Author URI: https://wedevs.com/
Text Domain: wp-recaptcha-integration
Domain Path: /languages
*/

/*  Copyright 2020  weDevs  (email : info AT wedevs DOT com)
	Copyright 2014  Jörn Lund  (email : joern AT podpirate DOT org)

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


define( 'WP_RECAPTCHA_INTEGRATION_FILE', __FILE__ );
define( 'WP_RECAPTCHA_INTEGRATION_DIRECTORY', plugin_dir_path(__FILE__) );

/**
 * Autoload Classes
 *
 * @param string $classname
 */
function wp_recaptcha_integration_autoload( $classname ) {
	$class_path = dirname(__FILE__). sprintf('/inc/class-%s.php' , strtolower( $classname ) ) ;

	if ( file_exists($class_path) ) {
		require_once $class_path;
	}
}

spl_autoload_register( 'wp_recaptcha_integration_autoload' );


// disable 2.0.0 updates on php < 5.4
function wp_recaptcha_disable_updates($value) {
	if ( version_compare(PHP_VERSION, '5.4', '<') ) {
		$plugin_basename = plugin_basename(__FILE__);

		if ( isset( $value->response[ $plugin_basename ] ) && version_compare( $value->response[ $plugin_basename ]->new_version , '2.0.0', '>=' ) ) {
			unset( $value->response[ plugin_basename(__FILE__) ] );
		}
	}

	return $value;
}

add_filter('site_transient_update_plugins', 'wp_recaptcha_disable_updates');

WP_reCaptcha::instance();

if ( is_admin() ) {
	WP_reCaptcha_Options::instance();
}
