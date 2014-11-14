<?php
/*
Plugin Name: WordPress reCaptcha Integration
Plugin URI: https://github.com/mcguffin/wp-recaptcha-integration
Description: Integrate reCaptcha in your blog. Provides of the box integration for signup, login and comment forms as well as a plugin API for your own integrations.
Version: 0.0.6
Author: Jörn Lund
Author URI: https://github.com/mcguffin/
Text Domain: recaptcha
Domain Path: /lang/
*/




class WordPress_reCaptcha {
	private $last_error = '';

	function __construct( ) {
		add_option('recaptcha_publickey','');
		add_option('recaptcha_privatekey','');
		add_option('recaptcha_theme','white');
		
		add_option('recaptcha_enable_comments' , true);
		add_option('recaptcha_enable_signup' , true);
		add_option('recaptcha_enable_login' , false);
		add_option('recaptcha_enable_ninja_forms' , false);
		add_option('recaptcha_disable_for_known_users' , true);
		
		add_action( 'wp_head' , array($this,'recaptcha_script') );

		add_action('init' , array(&$this,'init') );
		add_action('plugins_loaded' , array(&$this,'plugins_loaded') );

		if ( get_option('recaptcha_enable_signup') || get_option('recaptcha_enable_login') )
			add_action( 'login_head' , array(&$this,'recaptcha_script') );
		

		register_activation_hook( __FILE__ , array( __CLASS__ , 'activate' ) );
		register_deactivation_hook( __FILE__ , array( __CLASS__ , 'deactivate' ) );
		register_uninstall_hook( __FILE__ , array( __CLASS__ , 'uninstall' ) );
	}
	function plugins_loaded(){
		// check if ninja forms is present
		if ( class_exists('Ninja_Forms') || function_exists('ninja_forms_register_field') )
			include_once dirname(__FILE__).'/inc/ninja_forms_field_recaptcha.php';

		// check if contact form 7 forms is present
		if ( function_exists('wpcf7') )
			include_once dirname(__FILE__).'/inc/contact_form_7_recaptcha.php';
	}
	function init() {
		load_plugin_textdomain( 'recaptcha', false , dirname( plugin_basename( __FILE__ ) ).'/lang/' );
		
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
			return new WP_Error( 'captcha_error' ,  __("<strong>Error:</strong> the Captcha didn’t verify.",'recaptcha') );
		} else {
			return $user;
		}
	}
	function login_errors( $errors ) {
		if ( isset( $_POST["log"] ) && ! $this->recaptcha_check() ) {
			$errors->add( 'captcha_error' ,  __("<strong>Error:</strong> the Captcha didn’t verify.",'recaptcha') );
		}
		return $errors;
	}
		
	function recaptcha_script() {
		$recaptcha_theme = get_option('recaptcha_theme');
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
 			wp_die( __("Sorry, the Captcha didn’t verify.",'recaptcha') );
 	}
 	
 	function print_recaptcha_html(){
 		echo $this->recaptcha_html();
 	}
 	
	function recaptcha_html() {
		$public_key = get_option( 'recaptcha_publickey' );
		$recaptcha_theme = get_option('recaptcha_theme');

		if ($recaptcha_theme == 'custom') 
			$return = $this->get_custom_html( $public_key );
		else
			$return = recaptcha_get_html( $public_key, $this->last_error );
		return $return;
	}
	
	function get_custom_html( $public_key ) {
		
		$return = '<div id="recaptcha_widget" style="display:none">';

			$return .= '<div id="recaptcha_image"></div>';
			$return .= sprintf('<div class="recaptcha_only_if_incorrect_sol" style="color:red">%s</div>',__('Incorrect please try again','recaptcha'));

			$return .= sprintf('<span class="recaptcha_only_if_image">%s</span>',__('Enter the words above:','recaptcha'));
			$return .= sprintf('<span class="recaptcha_only_if_audio">%s</span>',__('Enter the numbers you hear:','recaptcha'));

			$return .= '<input type="text" id="recaptcha_response_field" name="recaptcha_response_field" />';

			$return .= sprintf('<div><a href="javascript:Recaptcha.reload()"></a></div>',__('Get another CAPTCHA','recaptcha'));
			$return .= sprintf('<div class="recaptcha_only_if_image"><a href="javascript:Recaptcha.switch_type(\'audio\')">%s</a></div>',__('Get an audio CAPTCHA','recaptcha'));
			$return .= sprintf('<div class="recaptcha_only_if_audio"><a href="javascript:Recaptcha.switch_type(\'image\')">%s</a></div>',__('Get an image CAPTCHA','recaptcha'));

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
	public static function uninstall(){
		delete_option( 'recaptcha_publickey' );
		delete_option( 'recaptcha_privatekey' );
		delete_option( 'recaptcha_theme' );
		delete_option( 'recaptcha_enable_comments' );
		delete_option( 'recaptcha_enable_signup' );
		delete_option( 'recaptcha_enable_login' );
		delete_option( 'recaptcha_enable_ninja_forms' );
		delete_option( 'recaptcha_disable_for_known_users' );
	}

}

require_once dirname(__FILE__).'/recaptchalib.php';
require_once dirname(__FILE__).'/inc/recaptcha-options.php';


$recaptcha = new WordPress_reCaptcha();
