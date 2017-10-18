<?php

namespace RecaptchaIntegration\Core;

use RecaptchaIntegration\Captcha;
use RecaptchaIntegration\Compat;

class Core extends Plugin {

	protected $wp_compat = null;

	protected $captcha = null;

	private $option_prefix = 'recaptcha_';

	/**
	 *	Private constructor
	 */
	protected function __construct() {

		add_action( 'plugins_loaded' , array( $this, 'load_textdomain' ) );
		add_action( 'plugins_loaded' , array( $this, 'init_compat' ), 0 );
		add_action( 'init' , array( $this, 'init' ) );

		// WP Multisite support
		if ( is_multisite() ) { // && active for network
			$this->wp_compat = Compat\WordPressMultisite::instance();
		} else {
			$this->wp_compat = Compat\WordPress::instance();
		}


		parent::__construct();

	}

	/**
	 *	Load Compatibility classes
	 *
	 *  @action plugins_loaded
	 */
	public function init_compat() {

		$this->captcha = Captcha\ReCaptcha::instance();

		// lockout mechanics
		if ( $this->get_option( 'lockout' ) ) {
			Lockout::instance();
		}

		// Awesome support
		if ( class_exists( 'Awesome_Support' ) ) {
			Compat\AwesomeSupport::instance();
		}

		// BBPress
		if ( class_exists( 'bbPress' ) ) {
			Compat\BBPress::instance();
		}

		// BuddyPress
		if ( function_exists('buddypress') ) {
			Compat\BuddyPress::instance();
		}

		// CForms2
		if ( class_exists( 'cforms2_captcha' ) ) {
			Compat\CForms2::instance();
		}

		// CF7 support
		if ( class_exists('WPCF7') ) {
			Compat\ContactForm7::instance();
		}

		// Ninja Forms support
		if ( function_exists( 'Ninja_Forms') ) {
			Compat\NinjaForms::instance();
		}

		// WooCommerce support
		if ( function_exists('WC') || class_exists('WooCommerce') ) {
			Compat\WooCommerce::instance();
		}

	}

	/**
	 *	Load text domain
	 *
	 *  @action plugins_loaded
	 */
	public function load_textdomain() {
		$path = pathinfo( dirname( WP_RECAPTCHA_FILE ), PATHINFO_FILENAME );
		load_plugin_textdomain( 'wp-recaptcha-integration' , false, $path . '/languages' );
	}

	/**
	 *	Init hook.
	 *
	 *  @action init
	 */
	public function init() {
	}

	/**
	 *  @return bool
	 */
	public function is_network_activated() {
		return $this->wp_compat->is_network_activated();
	}

	/**
	 *	Get the captcha HTML
	 *
	 *	@return string
	 */
	public function get_captcha( $attr ) {
		return $this->captcha->get_captcha( $attr );
	}

	public function get_captcha_object() {
		return $this->captcha;
	}

	/**
	 *	Whether the captcha has been entered correctly
	 *
	 *	@return bool
	 */
	public function required() {
		$is_required = ! ( $this->get_option('disable_for_known_users') && is_user_logged_in() );
		return apply_filters( 'wp_recaptcha_required' , $is_required );
	}

	/**
	 *	Whether the captcha has been entered correctly
	 *
	 *	@return bool
	 */
	public function valid() {
		return $this->captcha->valid();
	}

	/**
	 *	Whether there is captch data in the current request
	 *
	 *	@return bool
	 */
	public function submitted() {
		return $this->captcha->submitted();
	}

	/**
	 *	Whether there is captch data in the current request
	 *
	 *	@return bool
	 */
	public function has_api_key() {
//		var_dump($this->get_option('site_key') , $this->get_option('secret_key'));exit();
		return $this->get_option('site_key') && $this->get_option('secret_key');
	}

	/**
	 *	Get asset url for this plugin
	 *
	 *	@param	string	$asset	URL part relative to plugin class
	 *	@return wp_enqueue_editor
	 */
	public function get_asset_url( $asset ) {
		return plugins_url( $asset, WP_RECAPTCHA_FILE );
	}

	/**
	 *	Get asset url for this plugin
	 *
	 *	@param	string	$option
	 *	@param	mixed	$default
	 *	@return mixed
	 */
	public function get_option( $option, $default = null ) {
		return $this->wp_compat->get_option( $this->option_prefix . $option, $default );
	}

	/**
	 *	Update option
	 *
	 *	@param	string	$option
	 *	@param	mixed	$value
	 *	@return bool
	 */
	public function update_option( $option, $value ) {
		return $this->wp_compat->update_option( $this->option_prefix . $option, $value );
	}

	/**
	 *	Delete an option
	 *
	 *	@param	string	$option
	 *	@param	mixed	$value
	 *	@return bool
	 */
	public function delete_option( $option ) {
		return $this->wp_compat->delete_option( $this->option_prefix . $option );
	}


	/**
	 *	Delete an option
	 *
	 *	@param	string	$old_version
	 *	@param	string	$new_version
	 *	@return array(
	 *		'success'	=> bool,
	 *		'message'	=> string,
	 *	)
	 */
	public function upgrade( $new_version, $old_version ) {

		$inst = \WPRecaptcha();

		if ( version_compare( $old_version, '2.0.0', '<' ) ) {
			// api key naming changed in 2.0.0
			if ( ( $site_key = $this->get_option( 'publickey' ) ) && ( $secret_key = $this->get_option( 'privatekey' ) ) ) {
				$this->update_option( 'site_key', $site_key );
				$this->update_option( 'secret_key', $secret_key );
				$this->delete_option( 'publickey' );
				$this->delete_option( 'privatekey' );
			}
		}
		if ( version_compare( $old_version, '1.3.1', '<' ) ) {
			// flavor option is deprecated
			if ( $this->get_option('flavor') === 'recaptcha' ) {
				$this->delete_option( 'flavor' );
				$this->update_option( 'theme', 'light' );
				$this->update_option( 'language', '' );
				$this->update_option( 'enable_login', 0 );
				$this->update_option( 'enable_lostpw', 0 );

				add_action( 'admin_notices', array( $this, 'deprecated_v1_notice' ) );
			}

			// disable submit option deprecated in favor of recaptcha_solved_callback
			if ( $this->get_option('disable_submit') ) {
				$this->update_option( 'solved_callback', 'enable' );
				$this->delete_option( 'disable_submit' );
			}
		}
		return array(
			'success'	=> true,
			'message'	=> '',
		);
	}

	/**
	 *	Admin Notices hook to show up when the api keys heve not been entered.
	 *
	 *	@action admin_notices
	 */
	public function deprecated_v1_notice() {
		?><div class="notice error above-h1">
			<p><?php
			printf(
				__( 'Google no longer supports the old-style reCaptcha. The <a href="%s">plugin settings</a> have been updated accordingly.' , 'wp-recaptcha-integration' ),
				admin_url( add_query_arg( 'page' , 'recaptcha' , 'options-general.php' ) )
			);
			?></p>
			<p><?php
				_e( 'The Login and Lost password protection have been disabled. Please test if the captcha still works, an re-enable it, if you like.' , 'wp-recaptcha-integration' );
			?></p>

		</div><?php
	}


}
