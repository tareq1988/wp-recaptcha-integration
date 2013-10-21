<?php
/*
Plugin Name: WordPress reCaptcha Integration
Plugin URI: https://github.com/mcguffin/wp-recaptcha-integration
Description: Integrate reCaptcha in Your blog. Provides of the box integration for Signup, login and comment forms as well as a plugin API for your own integrations.
Version: 0.0.1
Author: Jörn Lund
Author URI: https://github.com/mcguffin/
Text Domain: recaptcha
*/



/*
settings:
- pubkey
- privkey
- theme: red | white | blackglass | clean
*/



class WordPress_reCaptcha {
	private $last_error='';

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

		if ( get_option('recaptcha_enable_signup') || get_option('recaptcha_enable_login') )
			add_action( 'login_head' , array(&$this,'recaptcha_script') );
		
		
		if ( function_exists('ninja_forms_register_field') )
			include_once dirname(__FILE__).'/inc/ninja_forms_field_recaptcha.php';

	}
	
	function init() {
		$require_recaptcha = ! ( get_option('recaptcha_disable_for_known_users') && current_user_can( 'read' ) );
		
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
		?><script type="text/javascript">
		var RecaptchaOptions = {
		theme : '<?php echo get_option('recaptcha_theme') ?>'
		};
		</script><?php
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
		return recaptcha_get_html( $public_key, $this->last_error );
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
}

require_once dirname(__FILE__).'/recaptchalib.php';
require_once dirname(__FILE__).'/inc/recaptcha-options.php';


$recaptcha = new WordPress_reCaptcha();
