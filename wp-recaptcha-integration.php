<?php
/*
Plugin Name: ABANDONED WP reCaptcha Integration
Plugin URI: https://wordpress.org/plugins/wp-recaptcha-integration/
Description: Integrate reCaptcha in your blog. Supports no Captcha (new style recaptcha). Provides of the box integration for signup, login, comment forms and lost password.
Version: 1.2.3
Author: Jörn Lund
Author URI: https://github.com/mcguffin/
Text Domain: wp-recaptcha-integration
Domain Path: /languages
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


define( 'WP_RECAPTCHA_INTEGRATION_FILE', __FILE__ );
define( 'WP_RECAPTCHA_INTEGRATION_DIRECTORY', plugin_dir_path(__FILE__) );

/**
 * Autoload Classes
 *
 * @param string $classname
 */
function wp_recaptcha_integration_autoload( $classname ) {
	$class_path = dirname(__FILE__). sprintf('/inc/class-%s.php' , strtolower( $classname ) ) ;
	if ( file_exists($class_path) )
		require_once $class_path;
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

function wp_recaptcha_deprecation_plugin_row( $file, $plugin_data ) {
	
	$wp_list_table = _get_list_table( 'WP_Plugins_List_Table' );
	printf(
		'<tr class="plugin-update-tr active" id="wp-recaptcha-integration" data-slug="wp-recaptcha-integration" data-plugin="wp-recaptcha-integration/wp-recaptcha-integration.php">' .
		'<td colspan="%1$d" class="plugin-update colspanchange">' .
		'<div class="update-message notice inline error notice-alt"><p><strong>%2$s</strong> %3$s</p></div></td></tr>',
		esc_attr( $wp_list_table->get_column_count() ),
		esc_html__( 'WP Recaptcha Integration is no longer maintained.', 'wp-recaptcha-integration' ),
		esc_html__( 'It will likely vanish from the WordPress plugin repository by September 2020.', 'wp-recaptcha-integration' )
	);
}

function wp_recaptcha_deprecation_admin_notice() {
	?>
	<div class="notice notice-error">
		<p>
			<strong>
				<?php esc_html_e( 'WP Recaptcha Integration is no longer maintained.', 'wp-recaptcha-integration' ); ?>
			</strong>
			<?php esc_html_e( 'It will likely vanish from the WordPress plugin repository by September 2020.', 'wp-recaptcha-integration' ); ?>
			<?php 
			global $pagenow;
			if ( 'plugins.php' !== $pagenow && current_user_can('install_plugins') ) {
				printf( 
					'<a href="%s">%s</a>',
					admin_url('plugins.php'),
					esc_html__( 'Disable it on the plugins page' )
				);
			}
			?>
		</p>
	</div>
	<?php
}

add_action( 'after_plugin_row_wp-recaptcha-integration/wp-recaptcha-integration.php', 'wp_recaptcha_deprecation_plugin_row', 10, 2 );
add_action( 'admin_notices', 'wp_recaptcha_deprecation_admin_notice' );

WP_reCaptcha::instance();


if ( is_admin() )
	WP_reCaptcha_Options::instance();
