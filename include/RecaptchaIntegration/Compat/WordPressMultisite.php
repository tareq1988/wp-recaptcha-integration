<?php

namespace RecaptchaIntegration\Compat;

use RecaptchaIntegration\Core;


class WordPressMultisite extends WordPress {


	/**
	 *	@inheritdoc
	 */
	protected $init_priority = 10;

	/**
	 *	@var array
	 */
	protected $network_options = array(
		'recaptcha_site_key',
		'recaptcha_secret_key',
		'recaptcha_enable_comments',
		'recaptcha_enable_signup',
		'recaptcha_enable_login',
		'recaptcha_enable_lostpw',
		'recaptcha_disable_for_known_users',
//		'recaptcha_enable_wc_order',
	);

	/**
	 *	@var bool
	 */
	private $is_network_activated = null;

	/**
	 *	@inheritdoc
	 */
	public function register_forms( $forms ) {
		$forms = parent::register_forms( $forms );
		if ( $this->is_network_activated() ) {
			if ( is_network_admin() ) {
				unset( $forms['comment'] );
			} else {
				unset( $forms['signup'] );
				unset( $forms['lostpw'] );
				unset( $forms['login'] );
			}
		}
		return $forms;
	}

	/**
	 *	@inheritdoc
	 */
	public function init() {

		$inst = \WPRecaptcha();

		if ( $inst->get_option('enable_signup') ) {
			add_action( 'signup_extra_fields', 'wp_recaptcha_print');
			add_filter( 'wpmu_validate_user_signup', array( $this, 'validate_user_signup' ) );
		}
		parent::init();
	}

	/**
	 *	check recaptcha WPMU signup
	 *	filter function for `wpmu_validate_user_signup`
	 *
	 *	@see filter hook `wpmu_validate_user_signup`
	 */
	function validate_user_signup( $result ) {
		if ( isset( $_POST['stage'] ) && $_POST['stage'] == 'validate-user-signup' ) {
			$result['errors'] = apply_filters('wp_recaptcha_wp_error', $result['errors'] , 'generic' );//$this->wp_error_add(  );
		}
		return $result;
	}


	/**
	 *	Get plugin option by name.
	 *
	 *	@return bool true if plugin is activated on network
	 */
	public function is_network_activated() {
		if ( is_null( $this->is_network_activated ) ) {
			if ( ! is_multisite() ) {
				return false;
			}
			if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
				require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
			}
			$this->is_network_activated = is_plugin_active_for_network( WP_RECAPTCHA_PLUGIN_FILE );
		}
		return $this->is_network_activated;
	}


	public function is_network_option( $option ) {
		return $this->is_network_activated() && in_array( $option, $this->network_options );
	}

	public function get_option( $option, $default = null ) {

		if ( $this->is_network_option( $option ) ) {
			return get_site_option( $option, $default );
		}
		return parent::get_option( $option, $default );
	}

	public function update_option( $option, $value ) {
		if ( $this->is_network_option( $option ) ) {
			error_log(sprintf('update site option %s %s',$option, $value));
			return update_site_option( $option, $value );
		}
		error_log(sprintf('update option %s %s',$option, $value));
		return parent::update_option( $option, $value );
	}
	public function delete_option( $option ) {
		if ( $this->is_network_option( $option ) ) {
			return delete_site_option( $option );
		}
		return parent::delete_option( $option );
	}

}
