<?php

namespace RecaptchaIntegration\Settings;

use RecaptchaIntegration\Ajax;
use RecaptchaIntegration\Core;

class SettingsPageRecaptcha extends Settings {

	private $optionset = 'recaptcha';

	private $network_optionset = 'recaptcha_network';

	private $ajax_test_render = null;

	private $ajax_test_verify = null;

	protected $option_prefix = 'recaptcha_';

	/**
	 *	Constructor
	 */
	protected function __construct() {
		$inst = WPRecaptcha();

		$this->ajax_test_render = new Ajax\AjaxHandler( 'recaptcha_render', array(
			'public'		=> false,
			'use_nonce'		=> false,
			'capability'	=> 'manage_options',
			'callback'		=> array( $this, 'ajax_test_render' ),
		));

		$this->ajax_test_verify = new Ajax\AjaxHandler( 'recaptcha_verify', array(
			'public'		=> false,
			'use_nonce'		=> false,
			'capability'	=> 'manage_options',
			'callback'		=> array( $this, 'ajax_test_verify' ),
		));

		add_action( 'admin_menu', array( $this, 'admin_menu' ) );

		if ( $inst->is_network_activated() ) {
			$page_hook = 'settings_page_racaptcha-settings';
			add_action( "load-{$page_hook}", array( $this , 'enqueue_styles' ));
			add_action( "load-{$page_hook}", array( $this , 'process_network_settings' ));
			add_action( 'network_admin_menu', array( $this , 'network_settings_menu' ));
		}

		add_action( 'pre_update_option_recaptcha_publickey' , array( $this , 'update_option_recaptcha_apikey' ) , 10 , 2 );
		add_action( 'pre_update_option_recaptcha_privatekey' , array( $this , 'update_option_recaptcha_apikey' ) , 10 , 2 );
		add_action( 'add_option_recaptcha_publickey' , array( $this , 'add_option_recaptcha_apikey' ) , 10 , 2 );
		add_action( 'add_option_recaptcha_privatekey' , array( $this , 'add_option_recaptcha_apikey' ) , 10 , 2 );

		add_option( 'recaptcha_integration_setting_1' , 'Default Value' , '' , False );

		add_filter( 'plugin_action_links_'.WP_RECAPTCHA_PLUGIN_FILE, array( $this, 'plugin_action_links' ), 10, 4 );
		// add network_admin_plugin_action_links_

		parent::__construct();

	}

	/**
	 *	@action plugin_action_links_{$plugin_file}
	 */
	public function plugin_action_links( $actions, $plugin_file, $plugin_data, $context ) {
		if ( current_user_can( 'manage_options' ) ) {
			$actions = array(
				'settings' => sprintf('<a href="%s">%s</a>', admin_url('options-general.php?page=' . $this->optionset), __('Settings','wp-recaptcha-integration') )
			) + $actions;
		}
		return $actions;
	}

	/**
	 *	Enqueue script and css for options page.
	 */
	public function enqueue_styles() {
		$inst = \WPRecaptcha();
		$suffix = WP_DEBUG ? '' : '.min';
		wp_enqueue_style( 'recaptcha-options', $inst->get_asset_url( "css/recaptcha-options{$suffix}.css" ) );
		wp_enqueue_script( 'recaptcha-options', $inst->get_asset_url( "js/recaptcha-options{$suffix}.js") , array( 'jquery', 'wp-recaptcha' ) );
		wp_localize_script( 'recaptcha-options', 'wp_recaptcha_options', array(
			'ajax_url'		=> admin_url( 'admin-ajax.php' ),
		) );

//		remove_action('admin_notices',array( $this , 'api_key_notice'));
	}


	/**
	 *	Network menu hook
	 */
	function network_settings_menu(){
		add_submenu_page(
			'settings.php',
			__( 'Recaptcha Settings' , 'wp-recaptcha-integration' ),
			__( 'Recaptcha' , 'wp-recaptcha-integration' ),
			'manage_network', 'racaptcha-settings',
			array( $this , 'network_settings_page' ) );
	}

	/**
	 *	Network Settings page
	 */
	function network_settings_page() {
		// h1, form, nonce, sanitize, process
		?><div class="wrap"><?php
			?><h2><?php _e( 'reCaptcha Settings' , 'wp-recaptcha-integration' ) ?></h2><?php
			?><form method="post"><?php
			wp_nonce_field( 'recaptcha-network-settings'  );
			do_settings_sections( $this->network_optionset );
			submit_button();
			?></form><?php
		?></div><?php
	}



	/**
	 *	Add Settings page
	 *
	 *	@action admin_menu
	 */
	public function admin_menu() {
		$page_slug = add_options_page( __('Recaptcha Integration' , 'wp-recaptcha-integration' ),__('Recaptcha' , 'wp-recaptcha-integration'),'manage_options',$this->optionset, array( $this, 'settings_page' ) );
		add_action( "load-$page_slug" , array( $this , 'enqueue_styles' ) );
	}

	/**
	 *	Render Settings page
	 */
	public function settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		?>
		<div class="wrap">
			<h2><?php _e('WP Recaptcha Integration Settings', 'wp-recaptcha-integration') ?></h2>

			<form action="options.php" method="post">
				<?php
				settings_fields(  $this->optionset );
				do_settings_sections( $this->optionset );
				submit_button( __('Save Settings' , 'wp-recaptcha-integration' ) );
				?>
			</form>
		</div><?php
	}




	/**
	 *	Setup options.
	 *
	 *	@action admin_init
	 */
	public function register_settings() {

		$inst = \WPRecaptcha();

		if ( ! $inst->has_api_key() ) {
			$this->register_api_settings( );
			return;
		}

		$this->register_protect_settings();

		$this->register_site_settings();

		$this->register_style_settings();

		$this->register_test_api_key_settings();
	}

	private function register_api_settings() {
		$inst = \WPRecaptcha();
		$optionset = $inst->is_network_activated() ? $this->network_optionset : $this->optionset;

		$section = 'recaptcha_apikey';

		add_settings_section( $section, __( 'Connect' , 'wp-recaptcha-integration' ), array( $this , 'explain_apikey' ), $optionset );

		$this->register_setting( $optionset, 'site_key' , 'trim' );
		$this->register_setting( $optionset, 'secret_key' , 'trim' );

		$this->add_settings_field( 'site_key',
			__('Site key','wp-recaptcha-integration'),
			array( $this, 'input_secret_text' ),
			$optionset,
			$section,
			array(
				'name'	=>'site_key',
			)
		);
		$this->add_settings_field( 'secret_key',
			__('Secret key','wp-recaptcha-integration'),
			array( $this, 'input_secret_text' ),
			$optionset,
			$section,
			array(
				'name'	=> 'secret_key'
			)
		);
	}

	/**
	 *	Intro text for the api key setting
	 */
	public function explain_api( ) {
		?><p class="description"><?php
			$info_url = 'https://developers.google.com/recaptcha/intro';
			$admin_url = 'https://www.google.com/recaptcha/admin';
			printf(
				__( 'Please register your blog through the <a href="%s">Google reCAPTCHA admin page</a> and enter the public and private key in the fields below. <a href="%s">What is this all about?</a>', 'wp-recaptcha-integration' ) ,
					$admin_url , $info_url
				);
		?></p><?php
		?><input type="hidden" name="recaptcha-action" value="recaptcha-set-api-key" /><?php
	}

	/**
	 *	Register settings section
	 */
	private function register_protect_settings() {
		$inst = \WPRecaptcha();
		$optionset = $inst->is_network_activated() ? $this->network_optionset : $this->optionset;

		$section = 'recaptcha_protect';

		add_settings_section( $section, __( 'Protection' , 'wp-recaptcha-integration' ), array( $this, 'explain_protection' ), $optionset );

		$this->register_setting( $optionset, 'lockout' , 'intval');
		$this->register_setting( $optionset, 'disable_for_known_users' , 'intval');

		foreach ( apply_filters( 'wp_recaptcha_forms', array() ) as $form_slug => $form_label ) {
			$this->register_setting( $optionset, 'enable_' . $form_slug, 'intval' );
		}

		$this->add_settings_field( 'protection',
			__('Forms to protect','wp-recaptcha-integration'),
			array( $this, 'input_enable' ),
			$optionset,
			$section
		);

		$this->add_settings_field('disable_for_known_users',
			__('Disable for known users','wp-recaptcha-integration'),
			array( $this, 'input_checkbox' ),
			$optionset,
			$section,
			array(
				'name'=>'disable_for_known_users',
				'label'=>__( 'Disable reCaptcha verification for logged in users.','wp-recaptcha-integration' )
			)
		);

		$this->add_settings_field('lockout',
			__( 'Prevent lockout', 'wp-recaptcha-integration' ),
			array($this,'input_checkbox'),
			$optionset,
			$section,
			array(
				'name'			=> 'lockout',
				'label'			=> __( 'Allow administrator to log in if API keys do not work.','wp-recaptcha-integration' ),
				'description'	=> __( 'When the captcha verification fails, and the private or public API key does not work the plugin will let you in anyway. Please note that with this option checked plus a broken keypair your site will be open to brute force attacks, that try to guess an administrator password.','wp-recaptcha-integration' ),
			)
		);

	}

	/**
	 *	Intro text for the Protection setting
	 */
	public function explain_protection() {
		?><div class="recaptcha-explain"><?php
			?><p class="description"><?php
				_e( 'Select which forms you want to protect with a captcha.' , 'wp-recaptcha-integration' );
			?></p><?php
		?></div><?php
	}


	/**
	 *	Register settings section
	 */
	private function register_site_settings( ) {
		$inst = \WPRecaptcha();
		$captcha = $inst->get_captcha_object();
		$optionset = $this->optionset;

		$section = 'recaptcha_site';

		add_settings_section( $section, __( 'General' , 'wp-recaptcha-integration' ), '', $optionset );

		$settings = $captcha->get_site_options();

		foreach ( $settings as $option_name => $option_args ) {
			$this->register_setting( $optionset, $option_name, $option_args ); // sanitze!
			$cb = array( $this, 'input_' . $option_args['input'] );

			if ( ! is_callable( $cb ) ) {
				$cb = array($this,'input_text');
			}

			$this->add_settings_field( $option_name,
				$option_args['label'],
				$cb,
				$optionset,
				$section,
				$option_args + array( 'name' => $option_name )
			);
		}

	}

	/**
	 *	Register settings section
	 */
	private function register_style_settings() {
		$inst = \WPRecaptcha();
		$captcha = $inst->get_captcha_object();
		$optionset = $this->optionset;

		$section = 'recaptcha_style';

		add_settings_section( $section, __( 'Style' , 'wp-recaptcha-integration' ), '', $optionset );

		$settings = $captcha->get_style_options();

		foreach ( $settings as $option_name => $option_args ) {
			$this->register_setting( $optionset, $option_name, $option_args ); // sanitze!
			$cb = array( $this, 'input_' . $option_args['input'] );
			if ( ! is_callable( $cb ) ) {
				$cb = array($this,'input_text');
			}

			$this->add_settings_field( $option_name,
				$option_args['label'],
				$cb,
				$optionset,
				$section,
				$option_args + array( 'name' => $option_name )
			);
		}
	}

	/**
	 *	Register settings section
	 */
	private function register_test_api_key_settings() {

		$inst = \WPRecaptcha();
		$optionset = $inst->is_network_activated() ? $this->network_optionset : $this->optionset;

		$section = 'recaptcha_apikey';

		add_settings_section( $section, __( 'API Credentials' , 'wp-recaptcha-integration' ), array( $this , 'explain_test_api_key' ), $optionset );

		$this->register_setting( $optionset, 'site_key' , 'trim' );
		$this->register_setting( $optionset, '__dummy0' , 'trim' );

		$this->add_settings_field( 'test',
			__('Test'),
			array( $this, 'input_test' ),
			$optionset,
			$section,
			array(
				'label'			=> __('Test','wp-recaptcha-integration'),
			)
		);

		$this->add_settings_field( 'site_key',
			__( 'Reset', 'wp-recaptcha-integration' ),
			array( $this, 'input_button' ),
			$optionset,
			$section,
			array(
				'name' 			=> 'site_key',
				'label'			=> __( 'Reset API Keys', 'wp-recaptcha-integration' ),
				'description'	=> __( 'After reset you are asked to enter your API-Crendentials again', 'wp-recaptcha-integration' ),
			)
		);

	}

	/**
	 *	Captcha Testing
	 */
	public function input_test($args) {

		?>
		<p class="description">
			<?php _e('Hit the Button below to render the captcha. Solve the captcha. Hit the button again to test verification.', 'wp-recaptcha-integration' ); ?>
		</p>
		<?php

		echo '<div id="wp-recaptcha-api-test"></div>';

		printf( '<button id="wp-recaptcha-api-test-button" class="button-secondary">%s</button>', __('Test Captcha','wp-recaptcha-integration' ) );


//		printf('<button id="wp-recaptcha-api-test-verify" class="button-secondary">%s</button>', __('Test Captcha','wp-recaptcha-integration' ) );

	}

	/**
	 *	Ajax callback
	 */
	public function ajax_test_render() {
		header( 'Content-Type: text/html' );
		$inst = \WPRecaptcha();
		echo $inst->get_captcha();
		exit();
	}

	/**
	 *	Ajax callback
	 */
	public function ajax_test_verify() {
		header( 'Content-Type: text/html' );
		$inst = \WPRecaptcha();
		if ( $inst->valid() ) {
			printf( '<div class="updated notice is-dismissible"><p>%s</p></div>', __('Works!','wp-recaptcha-integration' ) );
		} else {
			$errors = $inst->get_captcha_object()->get_errors();
			printf( '<div class="error notice is-dismissible"><p><strong>%s</strong> %s</p></div>',
				__('Error:</strong> ','wp-recaptcha-integration' ),
				implode( '<br />', $errors )
			);
		}
		exit();
	}

	/**
	 *	Introtext
	 */
	public function explain_test_api_key() {
		?><div class="recaptcha-explain"><?php
			?><p class="description"><?php
				_e( 'You already entered an API Key.','wp-recaptcha-integration');
			?></p><?php
		?></div><?php
		/*
		- enter another api key.
		- test: load
		*/
	}

	/**
	 *	Render forms
	 *
	 *	@param	array	$args
	 */
	public function input_enable( ) {
		echo '<div class="wp-recaptcha-enable">';
		foreach ( apply_filters( 'wp_recaptcha_forms', array() ) as $form_slug => $form_label ) {
			echo '<div class="option">';
			$this->input_checkbox( array(
				'name'	=> 'enable_' . $form_slug,
				'label'	=> $form_label,
			) );
			echo '</div>';
		}
		echo '</div>';
	}


}
