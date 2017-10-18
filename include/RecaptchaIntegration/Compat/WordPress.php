<?php

namespace RecaptchaIntegration\Compat;

use RecaptchaIntegration\Core;


class WordPress extends Compat {

	/**
	 *	@inheritdoc
	 */
	protected $init_priority = 10;


	/**
	 *	@inheritdoc
	 */
	public function register_forms( $forms ) {
		$forms['signup']	= __( 'Signup Form', 'wp-recaptcha-integration' );
		$forms['login']		= __( 'Login Form', 'wp-recaptcha-integration' );
		$forms['lostpw']	= __( 'Lost Password Form', 'wp-recaptcha-integration' );
		$forms['comment']	= __( 'Comment Form', 'wp-recaptcha-integration' );
		return $forms;
	}

	/**
	 *	Get plugin option by name.
	 *
	 *	@return bool true if plugin is activated on network
	 */
	public function is_network_activated() {
		return false;
	}

	/**
	 *	@inheritdoc
	 */
	public function init() {

		$inst = \WPRecaptcha();

		$require_recaptcha = $inst->required();

		if ( ! $require_recaptcha ) {
			return;
		}

		if ( $inst->get_option('enable_comments') ) {
			/*
			add_filter('comment_form_defaults',array($this,'comment_form_defaults'),10);
			/*/
			// WP 4.2 introduced `comment_form_submit_button` filter
			// which is much more likely to work
			global $wp_version;
			add_filter('comment_form_submit_button', array( $this, 'comment_form_submit_button' ), 10, 2 );

			add_action( 'pre_comment_on_post', 'wp_recaptcha_die' );

			add_action( 'print_comments_recaptcha' , 'wp_recaptcha_print' );
			add_filter( 'comments_recaptcha_html' , 'wp_recaptcha_get' );
		}

		if ( $inst->get_option('enable_signup') ) {

			// buddypress suuport.
			add_action( 'register_form', 'wp_recaptcha_print' );
			add_filter( 'registration_errors', 'wp_recaptcha_error' );

			add_filter( 'signup_recaptcha_html' , 'wp_recaptcha_get' );

		}

		if ( $inst->get_option('enable_login') ) {

			add_action( 'login_form', 'wp_recaptcha_print' );
			add_action( 'wp_authenticate', array( $this, 'deny_login' ), 10, 2 );
			add_filter( 'login_recaptcha_html', 'wp_recaptcha_get' );

		}

		if ( $inst->get_option('enable_lostpw') ) {

			add_action( 'lostpassword_form', 'wp_recaptcha_print' );
			add_action( 'lostpassword_post', 'wp_recaptcha_die' , 99 );
			add_filter( 'lostpassword_recaptcha_html', 'wp_recaptcha_get' );

		}

	}

	public function get_option( $option, $default = null ) {
		return get_option( $option, $default );
	}

	public function update_option( $option, $value ) {
		return update_option( $option, $value );
	}

	public function delete_option( $option ) {
		return delete_option( $option );
	}

	/**
	 *	check recaptcha on login
	 *	filter function for `wp_authenticate_user`
	 *
	 *	@param $user WP_User
	 *	@return object user or wp_error
	 */
	function deny_login( $username, $passsword ) {
		if ( is_null( $username ) ) {
			return;
		}
		do_action( 'wp_recaptcha_die' );
	}

	/**
	 *	Display recaptcha on comments form.
	 *
	 *	@filter comment_form_submit_button
	 */
	public function comment_form_submit_button( $submit_button, $args ) {
		return '<div class="wp-recaptcha-wrap">' . apply_filters('wp_recaptcha_get','') . '</div>' . $submit_button;
	}

	/**
	 *	check recaptcha on registration
	 *	filter function for `registration_errors`
	 *
	 *	@param $errors WP_Error
	 *	@return WP_Error with captcha error added if test fails.
	 */
	function registration_errors( $errors ) {
		if ( isset( $_POST['user_login']) )
			$errors = $this->wp_error_add( $errors );
		return $errors;
	}


}
