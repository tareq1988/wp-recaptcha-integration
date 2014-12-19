<?php
/*
Plugin Name: WordPress reCaptcha Integration
Plugin URI: https://github.com/mcguffin/wp-recaptcha-integration
Description: Integrate reCaptcha in your blog. Supports new style recaptcha. Provides of the box integration for signup, login, comment forms, Ninja Forms and contact form 7 as well as a plugin API for your own integrations.
Version: 0.9.0
Author: Jörn Lund
Author URI: https://github.com/mcguffin/
*/




class WordPress_reCaptcha {

	private $_has_api_key = false;

	private $last_error = '';
	
	/**
	 *	Holding the singleton instance
	 */
	private static $_instance = null;

	/**
	 *	@return WordPress_reCaptcha_Options The options manager instance
	 */
	public static function instance(){
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
		return self::$_instance;
	}

	/**
	 *	Prevent from creating more than one instance
	 */
	private function __clone() {
	}

	/**
	 *	Prevent from creating more than one instance
	 */
	private function __construct() {
		add_option('recaptcha_publickey','');
		add_option('recaptcha_privatekey','');

		add_option('recaptcha_flavor','grecaptcha');
		add_option('recaptcha_theme','light');
		add_option('recaptcha_enable_comments' , true);
		add_option('recaptcha_enable_signup' , true);
		add_option('recaptcha_enable_login' , false);
		add_option('recaptcha_disable_for_known_users' , true);
		
		$this->_has_api_key = get_option( 'recaptcha_publickey' ) && get_option( 'recaptcha_privatekey' );

		if ( $this->_has_api_key ) {
			add_action( 'wp_head' , array($this,'recaptcha_script') );

			add_action('init' , array(&$this,'init') );
			add_action('plugins_loaded' , array(&$this,'plugins_loaded') );

			if ( get_option('recaptcha_enable_signup') || get_option('recaptcha_enable_login') )
				add_action( 'login_head' , array(&$this,'recaptcha_script') );
		}

		register_activation_hook( __FILE__ , array( __CLASS__ , 'activate' ) );
		register_deactivation_hook( __FILE__ , array( __CLASS__ , 'deactivate' ) );
		register_uninstall_hook( __FILE__ , array( __CLASS__ , 'uninstall' ) );

	}
	function has_api_key() {
		return $this->_has_api_key;
	}
	
	function plugins_loaded() {
		if ( $this->_has_api_key ) {
			// check if ninja forms is present
			if ( class_exists('Ninja_Forms') || function_exists('ninja_forms_register_field') )
				include_once dirname(__FILE__).'/inc/ninja_forms_field_recaptcha.php';

			// check if contact form 7 forms is present
			if ( function_exists('wpcf7') )
				include_once dirname(__FILE__).'/inc/contact_form_7_recaptcha.php';
		}
	}
	function init() {
		load_plugin_textdomain( 'wp-recaptcha-integration', false , dirname( plugin_basename( __FILE__ ) ).'/languages/' );
		
		$require_recaptcha = $this->is_required();
		
		if ( get_option('recaptcha_enable_comments') && $require_recaptcha ) {
			add_action('comment_form',array($this,'print_recaptcha_html'));
			add_action('pre_comment_on_post',array($this,'recaptcha_check_or_die'));
			// add action @ comment approve
		}
		if ( get_option('recaptcha_enable_signup') && $require_recaptcha ) {
			add_action('register_form',array($this,'print_recaptcha_html'));
			add_filter('registration_errors',array(&$this,'login_errors'));
		}
		if ( get_option('recaptcha_enable_login') && $require_recaptcha ) {
			add_action('login_form',array($this,'print_recaptcha_html'));
			add_filter('wp_authenticate_user',array(&$this,'deny_login'),99 );
		}

	}
	
	function is_required() {
		$is_required = ! ( get_option('recaptcha_disable_for_known_users') && current_user_can( 'read' ) );
		return apply_filters( 'recaptcha_required' , $is_required );
	}
	
	function deny_login( $user ){
		if ( isset( $_POST["log"] ) && ! $this->recaptcha_check() ) {
			return new WP_Error( 'captcha_error' ,  __("<strong>Error:</strong> the Captcha didn’t verify.",'wp-recaptcha-integration') );
		} else {
			return $user;
		}
	}
	function login_errors( $errors ) {
		if ( isset( $_POST["log"] ) && ! $this->recaptcha_check() ) {
			$errors->add( 'captcha_error' ,  __("<strong>Error:</strong> the Captcha didn’t verify.",'wp-recaptcha-integration') );
		}
		return $errors;
	}
		
	function recaptcha_script() {
		$recaptcha_theme = get_option('recaptcha_theme');
		?><script src="https://www.google.com/recaptcha/api.js?hl=en" async defer></script><?php
		if ( $recaptcha_theme == 'custom' ) {
			?><script type="text/javascript">
			var RecaptchaOptions = {
				theme : '<?php echo $recaptcha_theme ?>',
				custom_theme_widget: 'recaptcha_widget'
			};
			</script><?php
		} else {
			?><script type="text/javascript">
			var RecaptchaOptions = {
				theme : '<?php echo $recaptcha_theme ?>'
			};
			</script><?php
		}
 	}
 	function recaptcha_check_or_die( ) {
 		if ( ! $this->recaptcha_check() )
 			wp_die( __("Sorry, the Captcha didn’t verify.",'wp-recaptcha-integration') );
 	}
 	
 	function print_recaptcha_html(){
 		echo $this->recaptcha_html();
 	}
 	
 	function recaptcha_html() {
 		switch ( get_option( 'recaptcha_flavor' ) ) {
 			case 'grecaptcha':
 				return $this->grecaptcha_html();
 			case 'recaptcha':
 				return $this->old_recaptcha_html();
 		}
 	}

 	function old_recaptcha_html() {
		require_once dirname(__FILE__).'/recaptchalib.php';
		$public_key = get_option( 'recaptcha_publickey' );
		$recaptcha_theme = get_option('recaptcha_theme');

		if ($recaptcha_theme == 'custom') 
			$return = $this->get_custom_html( $public_key );
		else
			$return = recaptcha_get_html( $public_key, $this->last_error );
		return $return;
 	}
 	
	function grecaptcha_html() {
		$public_key = get_option( 'recaptcha_publickey' );
		$theme = get_option('recaptcha_theme');
		$return = sprintf( '<div class="g-recaptcha" data-sitekey="%s" data-theme="%s"></div>',$public_key,$theme);
		return $return;
	}
	
	function get_custom_html( $public_key ) {
		
		$return = '<div id="recaptcha_widget" style="display:none">';

			$return .= '<div id="recaptcha_image"></div>';
			$return .= sprintf('<div class="recaptcha_only_if_incorrect_sol" style="color:red">%s</div>',__('Incorrect please try again','wp-recaptcha-integration'));

			$return .= sprintf('<span class="recaptcha_only_if_image">%s</span>',__('Enter the words above:','wp-recaptcha-integration'));
			$return .= sprintf('<span class="recaptcha_only_if_audio">%s</span>',__('Enter the numbers you hear:','wp-recaptcha-integration'));

			$return .= '<input type="text" id="recaptcha_response_field" name="recaptcha_response_field" />';

			$return .= sprintf('<div><a href="javascript:Recaptcha.reload()"></a></div>',__('Get another CAPTCHA','wp-recaptcha-integration'));
			$return .= sprintf('<div class="recaptcha_only_if_image"><a href="javascript:Recaptcha.switch_type(\'audio\')">%s</a></div>',__('Get an audio CAPTCHA','wp-recaptcha-integration'));
			$return .= sprintf('<div class="recaptcha_only_if_audio"><a href="javascript:Recaptcha.switch_type(\'image\')">%s</a></div>',__('Get an image CAPTCHA','wp-recaptcha-integration'));

			$return .= '<div><a href="javascript:Recaptcha.showhelp()">Help</a></div>';
		$return .= '</div>';

		$return .= sprintf('<script type="text/javascript" src="http://www.google.com/recaptcha/api/challenge?k=%s"></script>',$public_key);
		$return .= '<noscript>';
			$return .= sprintf('<iframe src="http://www.google.com/recaptcha/api/noscript?k=%s" height="300" width="500" frameborder="0"></iframe><br>',$public_key);
			$return .= '<textarea name="recaptcha_challenge_field" rows="3" cols="40">';
			$return .= '</textarea>';
			$return .= '<input type="hidden" name="recaptcha_response_field" value="manual_challenge">';
		$return .= '</noscript>';
		
		return $return;
 	}
	
	function recaptcha_check() {
 		switch ( get_option( 'recaptcha_flavor' ) ) {
 			case 'grecaptcha':
 				return $this->grecaptcha_check();
 			case 'recaptcha':
 				return $this->old_recaptcha_check();
 		}
	}
	function grecaptcha_check() {
		$private_key = get_option( 'recaptcha_privatekey' );
		$user_response = isset( $_REQUEST['g-recaptcha-response'] ) ? $_REQUEST['g-recaptcha-response'] : false;
		if ( $user_response ) {
			$remote_ip = $_SERVER['REMOTE_ADDR'];
			$url = "https://www.google.com/recaptcha/api/siteverify?secret=$private_key&response=$user_response&remoteip=$remote_ip";
			$response = wp_remote_get( $url );
			if ( ! is_wp_error($response) ) {
				$response_data = wp_remote_retrieve_body( $response );
				$result = json_decode($response_data);
				return $result->success;
			}
		}
		return false;
	}
	function old_recaptcha_check() {
		require_once dirname(__FILE__).'/recaptchalib.php';
		$private_key = get_option( 'recaptcha_privatekey' );
		$response = recaptcha_check_answer( $private_key,
			$_SERVER["REMOTE_ADDR"],
			$_POST["recaptcha_challenge_field"],
			$_POST["recaptcha_response_field"]);

		if ( ! $response->is_valid )
			$this->last_error = $response->error;

		return $response->is_valid;
	}
	/**
	 *	Fired on plugin activation
	 */
	public static function activate() {
	}

	/**
	 *	Fired on plugin deactivation
	 */
	public static function deactivate() {
	}
	/**
	 *
	 */
	public static function uninstall() {
		delete_option( 'recaptcha_publickey' );
		delete_option( 'recaptcha_privatekey' );
		
		delete_option( 'recaptcha_flavor' );
		delete_option( 'recaptcha_theme' );
		delete_option( 'recaptcha_enable_comments' );
		delete_option( 'recaptcha_enable_signup' );
		delete_option( 'recaptcha_enable_login' );
		delete_option( 'recaptcha_disable_for_known_users' );
	}
}


WordPress_reCaptcha::instance();

require_once dirname(__FILE__).'/inc/recaptcha-options.php';
