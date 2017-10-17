<?php

namespace RecaptchaIntegration\Compat;

use RecaptchaIntegration\Core;


class WooCommerce extends Compat {

	/**
	 *	@inheritdoc
	 */
	protected $init_priority = 0;

	/**
	 *	@inheritdoc
	 */
	public function register_forms( $forms ) {
		$forms['wc_order']	= __( 'WooCommerce Checkout', 'wp-recaptcha-integration' );
		return $forms;
	}
	/**
	 *	@inheritdoc
	 */
	public function init() {
		$inst = \WPRecaptcha();

		$enable_order  = $inst->get_option('enable_wc_order') ;
		$enable_signup = $inst->get_option('enable_signup') ;
		$enable_login  = $inst->get_option('enable_login');
		$enable_lostpw = $inst->get_option('enable_lostpw');

		if ( apply_filters( 'wp_recaptcha_required', true ) ) {
			// WooCommerce support
			if ( function_exists( 'wc_add_notice' ) ) {
				if ( $enable_order ) {
					add_action('woocommerce_review_order_before_submit' , 'wp_recaptcha_print', 10, 0 );
					add_action('woocommerce_checkout_process', array( $this , 'recaptcha_check' ) );
					add_filter( 'wc_checkout_recaptcha_html' , array( $this , 'recaptcha_html' ) );
					add_action( 'wp_footer', array( $this, 'wp_footer' ) );
				} else if ( $enable_signup ) {
					add_filter( 'wp_recaptcha_required' , array( $this , 'disable_on_checkout' ) );
				}

				if ( $enable_login ) {
					add_action('woocommerce_login_form', 'wp_recaptcha_print', 10, 0 );
					add_filter('woocommerce_process_login_errors', array( $this , 'login_errors' ), 10 , 3 );
				}

				if ( $enable_signup ) {
					// Injects recaptcha in Register for WooCommerce >= 3.0
					// For WooCommerce < 3.0 displaying the captcha at hook 'registration_form' already done by core plugin
					if ( class_exists( 'WooCommerce' ) ) {
						global $woocommerce;
						if ( version_compare( $woocommerce->version, '3.0', ">=" ) ) {
							add_action('woocommerce_register_form', 'wp_recaptcha_print', 10, 0 );
						}
					}


					add_filter('woocommerce_registration_errors', array( $this , 'login_errors' ) , 10 , 3 );
				}
				add_filter('woocommerce_form_field_recaptcha', array( $inst , 'recaptcha_html' ) , 10 , 3 );

				/*
				LOSTPW: Not possible yet. Needs https://github.com/woothemes/woocommerce/pull/7786 being applied.
				*/
				if ( $enable_lostpw ) {
					add_action( 'woocommerce_lostpassword_form', 'wp_recaptcha_print', 10, 0 );
				}
				if( version_compare( WC()->version , '2.4.0' ) === -1 ){
					add_filter( 'woocommerce_locate_template', array($this,'locate_template'), 10, 3 );
				}
			}
		}
	}

	function plugin_path() {

		// gets the absolute path to this plugin directory

		return untrailingslashit( plugin_dir_path( __FILE__ ) );

	}

	function locate_template( $template, $template_name, $template_path ) {

		global $woocommerce;


		$_template = $template;

		if ( ! $template_path )
			$template_path = $woocommerce->template_url;

		$base_path = WP_RECAPTCHA_PATH . '/include/RecaptchaIntegration/Compat/WooCommerce/templates/';


		// Look within passed path within the theme - this is priority

		$template = locate_template(

			array(

				$template_path . $template_name,

				$template_name

			)

		);


		// Modification: Get the template from this plugin, if it exists

		if ( ! $template && file_exists( $base_path . $template_name ) )

			$template = $base_path . $template_name;


		// Use default template

		if ( ! $template )

			$template = $_template;


		// Return what we found

		return $template;

	}

	/**
	 *	WooCommerce recaptcha Check
	 *	hooks into action `woocommerce_checkout_process`
	 */
	function recaptcha_check() {
		if ( ! apply_filters( 'wp_recaptcha_valid', true ) ) {
			wc_add_notice( __("<strong>Error:</strong> the Captcha didn’t verify.",'wp-recaptcha-integration'), 'error' );
		}
	}

	/**
	 *	WooCommerce recaptcha Check
	 *	hooks into actions `woocommerce_process_login_errors` and `woocommerce_registration_errors`
	 */
	function login_errors( $validation_error ) {
		if ( ! apply_filters( 'wp_recaptcha_valid', true ) ) {
			$validation_error->add( 'captcha_error' ,  __("<strong>Error:</strong> the Captcha didn’t verify.",'wp-recaptcha-integration') );
		}
		return $validation_error;
	}

	/**
	 *	WooCommerce recaptcha Check
	 *	hooks into actions `woocommerce_process_login_errors` and `woocommerce_registration_errors`
	 */
	function disable_on_checkout( $enabled ) {
		if ( defined( 'WOOCOMMERCE_CHECKOUT' ) )
			return false;
		return $enabled;
	}

	public function wp_footer() {
		?>
		<script type="text/javascript">
		(function($){
			$( document.body ).on( 'updated_checkout', function(e){
				if ( !! window.wp_recaptcha_init ) {
					window.wp_recaptcha_init();
				}
				console.log(e,this)
			} );
		})(jQuery);
		</script>
		<?php
	}
}
