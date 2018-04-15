<?php

/**
 *	Plugin base Class
 */
class WP_reCaptcha {

	private static $_is_network_activated = null;

	private $_has_api_key = false;

	private $last_error = '';
	private $_last_result;

	private $_captcha_instance = null;

	/**
	 *	Holding the singleton instance
	 */
	private static $_instance = null;

	/**
	 *	@return WP_reCaptcha
	 */
	public static function instance(){
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
		return self::$_instance;
	}

	/**
	 *	Prevent from creating more instances
	 */
	private function __clone() { }

	/**
	 *	Prevent from creating more than one instance
	 */
	private function __construct() {

		add_option('recaptcha_theme','light'); // local
		add_option('recaptcha_disable_submit',false); // local
		add_option('recaptcha_noscript',false); // local
		add_option('recaptcha_comment_use_42_filter',false); // local
		add_option('recaptcha_publickey',''); // 1st global -> then local
		add_option('recaptcha_privatekey',''); // 1st global -> then local
		add_option('recaptcha_language',''); // 1st global -> then local

		if ( WP_reCaptcha::is_network_activated() ) {
			add_site_option('recaptcha_publickey',''); // 1st global -> then local
			add_site_option('recaptcha_privatekey',''); // 1st global -> then local

			add_site_option('recaptcha_enable_comments' , true); // global
			add_site_option('recaptcha_enable_signup' , true); // global
			add_site_option('recaptcha_enable_login' , false); // global
			add_site_option('recaptcha_enable_lostpw' , false); // global
			add_site_option('recaptcha_disable_for_known_users' , true); // global
			add_site_option( 'recaptcha_lockout' , true );
		} else {
			add_option( 'recaptcha_enable_comments' , true); // global
			add_option( 'recaptcha_enable_signup' , true); // global
			add_option( 'recaptcha_enable_login' , false); // global
			add_option( 'recaptcha_enable_lostpw' , false); // global
			add_option( 'recaptcha_disable_for_known_users' , true); // global
			add_option( 'recaptcha_lockout' , true );
		}
		$this->_has_api_key = $this->get_option( 'recaptcha_publickey' ) && $this->get_option( 'recaptcha_privatekey' );

		if ( $this->has_api_key() ) {

			add_action('init' , array($this,'init') , 9 );
			add_action('plugins_loaded' , array($this,'plugins_loaded'), 9 );

		}
		add_action('plugins_loaded', array( $this,'load_textdomain') );

		register_activation_hook( __FILE__ , array( __CLASS__ , 'activate' ) );
		register_deactivation_hook( __FILE__ , array( __CLASS__ , 'deactivate' ) );
		register_uninstall_hook( __FILE__ , array( __CLASS__ , 'uninstall' ) );
	}

	/**
	 *	Load ninja/cf7 php files if necessary
	 *	@action plugins_loaded
	 */
	function load_textdomain() {
		load_plugin_textdomain( 'wp-recaptcha-integration', false, basename( WP_RECAPTCHA_INTEGRATION_DIRECTORY ) . '/languages' );
	}
	/**
	 *	Load ninja/cf7 php files if necessary
	 *	Hooks into 'plugins_loaded'
	 */
	function plugins_loaded() {


		if ( $this->has_api_key() ) {
			// NinjaForms support
			// check if ninja forms is present
			if ( class_exists('Ninja_Forms') || function_exists('ninja_forms_register_field') )
				WP_reCaptcha_NinjaForms::instance();

			// CF7 support
			// check if contact form 7 forms is present
			if ( function_exists('wpcf7') )
				WP_reCaptcha_ContactForm7::instance();

			// WooCommerce support
			// check if woocommerce is present
			if ( function_exists('WC') || class_exists('WooCommerce') )
				WP_reCaptcha_WooCommerce::instance();

			if ( class_exists( 'Awesome_Support' ) )
				WP_reCaptcha_Awesome_Support::instance();

			if ( class_exists( 'bbPress' ) ) {
				WP_reCaptcha_bbPress::instance();
			}

			if ( class_exists( 'cforms2_captcha' ) ) {
				WP_reCaptcha_cforms2::instance();
			}

		}
	}

	/**
	 *	Init plugin
	 *	set hooks
	 */
	function init() {
		$require_recaptcha = $this->is_required();

		if ( $require_recaptcha ) {
			add_action( 'wp_head' , array($this,'recaptcha_head') );
			add_action( 'wp_footer' , array($this,'recaptcha_foot') );

			if ( $this->get_option('recaptcha_enable_signup') || $this->get_option('recaptcha_enable_login')  || $this->get_option('recaptcha_enable_lostpw') ) {
				add_action( 'login_head' , array($this,'recaptcha_head') );
				add_action( 'login_head' , array($this,'recaptcha_login_head') );
				add_action( 'login_footer' , array($this,'recaptcha_foot') );
			}
			if ( $this->get_option('recaptcha_enable_comments') ) {
				/*
				add_filter('comment_form_defaults',array($this,'comment_form_defaults'),10);
				/*/
				// WP 4.2 introduced `comment_form_submit_button` filter
				// which is much more likely to work
				global $wp_version;
				add_filter('comment_form_submit_button',array($this,'prepend_recaptcha_html'),10,2);

				//*/
				add_action('pre_comment_on_post',array($this,'recaptcha_check_or_die'));

				add_action( 'print_comments_recaptcha' , array( $this , 'print_recaptcha_html' ) );
				add_filter( 'comments_recaptcha_html' , array( $this , 'recaptcha_html' ) );
			}
			if ( $this->get_option('recaptcha_enable_signup') ) {
				// buddypress suuport.
				if ( function_exists('buddypress') ) {
					add_action('bp_account_details_fields',array($this,'print_recaptcha_html'));
					add_action('bp_signup_pre_validate',array($this,'recaptcha_check_or_die'),99 );
				} else {
					add_action('register_form',array($this,'print_recaptcha_html'));
					add_filter('registration_errors',array($this,'registration_errors'));
				}
				if ( is_multisite() ) {
					add_action( 'signup_extra_fields' , array($this,'print_recaptcha_html'));
					add_filter('wpmu_validate_user_signup',array($this,'wpmu_validate_user_signup'));
				}
				add_filter( 'signup_recaptcha_html' , array( $this , 'recaptcha_html' ) );

			}
			if ( $this->get_option('recaptcha_enable_login') ) {
				add_action('login_form',array($this,'print_recaptcha_html'));
				if ( $this->get_option('recaptcha_lockout') ) {
					add_filter( 'wp_authenticate_user', array( $this, 'deny_login' ), 99 );
				} else {
					add_filter( 'authenticate', array( $this, 'wp_error' ), 99 );
				}

				add_filter( 'login_recaptcha_html' , array( $this , 'recaptcha_html' ) );
			}
			if ( $this->get_option('recaptcha_enable_lostpw') ) {
				add_action('lostpassword_form' , array($this,'print_recaptcha_html') );
//*
				add_action('lostpassword_post' , array($this,'recaptcha_check_or_die') , 99 );
/*/ // switch this when pull request accepted and included in official WC release.
				add_filter('allow_password_reset' , array($this,'wp_error') );
//*/
				add_filter( 'lostpassword_recaptcha_html' , array( $this , 'recaptcha_html' ) );
			}
			if ( 'WPLANG' === $this->get_option( 'recaptcha_language' ) )
				add_filter( 'wp_recaptcha_language' , array( $this,'recaptcha_wplang' ) , 5 );

			add_action( 'recaptcha_print' , array( $this , 'print_recaptcha_html' ) );
			add_filter( 'recaptcha_error' , array( $this , 'wp_error' ) );
			add_filter( 'recaptcha_html' , array( $this , 'recaptcha_html' ) );
		}
		add_filter( 'recaptcha_valid' , array( $this , 'recaptcha_check' ) );
	}

	/**
	 *	Set current captcha instance and return it.
	 *
	 *	@return	object	WP_reCaptcha_Captcha
	 */
	public function captcha_instance() {
		if ( is_null( $this->_captcha_instance ) )
			$this->_captcha_instance = WP_reCaptcha_NoCaptcha::instance();
		return $this->_captcha_instance;
	}

	/**
	 *	returns if recaptcha is required.
	 *
	 *	@return bool
	 */
	function is_required() {
		$is_required = ! ( $this->get_option('recaptcha_disable_for_known_users') && is_user_logged_in() );
		return apply_filters( 'wp_recaptcha_required' , $is_required );
	}




	//////////////////////////////////
	// 	Displaying
	//

	/**
	 *	print recaptcha stylesheets
	 *	hooks into `wp_head`
	 */
	function recaptcha_head( ) {
		if ( apply_filters( 'wp_recaptcha_do_scripts' , true ) ) {
			$this->begin_inject( );
			$this->captcha_instance()->print_head();
			$this->end_inject( );
		}
 	}

	/**
	 *	print recaptcha login form stylesheets
	 *	hooks into `wp_head`
	 */
	function recaptcha_login_head( ) {
		if ( apply_filters( 'wp_recaptcha_print_login_css' , true ) ) {
			$this->begin_inject( );
			$this->captcha_instance()->print_login_head();
			$this->end_inject( );
		}
 	}

	/**
	 *	Print recaptcha scripts
	 *	hooks into `wp_footer`
	 *
	 */
	function recaptcha_foot( ) {
		if ( apply_filters( 'wp_recaptcha_do_scripts' , true ) ) {
			$this->begin_inject( );

			// getting submit buttons of an elements form
			if ( $this->get_option( 'recaptcha_disable_submit' ) ) {
				?><script type="text/javascript">
				function get_form_submits(el){
					var form,current=el,ui,type,slice = Array.prototype.slice,self=this;
					this.submits=[];
					this.form=false;

					this.setEnabled=function(e){
						for ( var s=0;s<self.submits.length;s++ ) {
							if (e) self.submits[s].removeAttribute('disabled');
							else  self.submits[s].setAttribute('disabled','disabled');
						}
						return this;
					};
					while ( current && current.nodeName != 'BODY' && current.nodeName != 'FORM' ) {
						current = current.parentNode;
					}
					if ( !current || current.nodeName != 'FORM' )
						return false;
					this.form=current;
					ui=slice.call(this.form.getElementsByTagName('input')).concat(slice.call(this.form.getElementsByTagName('button')));
					for (var i = 0; i < ui.length; i++) if ( (type=ui[i].getAttribute('TYPE')) && type=='submit' ) this.submits.push(ui[i]);
					return this;
				}
				</script><?php
			}
			$this->captcha_instance()->print_foot();

			$this->end_inject( );
		}
	}

	/**
	 *	Print recaptcha HTML. Use inside a form.
	 *
	 */
 	function print_recaptcha_html( $attr = array() ) {
		echo $this->begin_inject( );
 		echo $this->recaptcha_html( $attr );
		echo $this->end_inject( );
 	}

	/**
	 *	Get recaptcha HTML.
	 *
	 *	@return string recaptcha html
	 */
 	function recaptcha_html( $attr = array() ) {
		return $this->captcha_instance()->get_html( $attr );
 	}


	/**
	 *	Get recaptcha HTML.
	 *
	 *	@param $html string
	 *	@return string recaptcha html prepended to first parameter.
	 */
	function prepend_recaptcha_html( $html ) {
		return $this->recaptcha_html() . $html;
	}

	/**
	 *	HTML comment with some notes (beginning)
	 *
	 *	@param $return bool Whether to print or to return the comment
	 *	@param $moretext string Additional information being included in the comment
	 *	@return null|string HTML-Comment
	 */
	function begin_inject($return = false,$moretext='') {
		$html = "\n<!-- BEGIN recaptcha, injected by plugin wp-recaptcha-integration $moretext -->\n";
		if ( $return ) return $html;
		echo $html;
	}
	/**
	 *	HTML comment with some notes (ending)
	 *
	 *	@param $return bool Whether to print or to return the comment
	 *	@return null|string HTML-Comment
	 */
	function end_inject( $return = false ) {
		$html = "\n<!-- END recaptcha -->\n";
		if ( $return ) return $html;
		echo $html;
	}

	/**
	 *	Display recaptcha on comments form.
	 *	filter function for `comment_form_defaults`
	 *
	 *	@see filter doc `comment_form_defaults`
	 */
	function comment_form_defaults( $defaults ) {
		$defaults['comment_notes_after'] .= '<p>' . $this->recaptcha_html() . '</p>';
		return $defaults;
	}

	//////////////////////////////////
	// 	Verification
	//

	/**
	 *	Get last result of recaptcha check
	 *	@return string recaptcha html
	 */
	function get_last_result() {
		return $this->captcha_instance()->get_last_result();
	}

	/**
	 *	Check recaptcha
	 *
	 *	@return bool false if check does not validate
	 */
	function recaptcha_check( $valid=null ) {
		if ( $this->is_required() )
			return $this->captcha_instance()->check();
		return true;
	}

	/**
	 *	check recaptcha on login
	 *	filter function for `wp_authenticate_user`
	 *
	 *	@param $user null|WP_User|WP_Error
	 *	@return object user or wp_error
	 */
	function deny_login( $user ) {
		$result = $this->wp_error( $user );
		if ( is_wp_error( $result ) && in_array( 'administrator', $user->roles ) && ! $this->test_keys() ) {
			return $user;
		}
		return $result;
	}

	/**
	 *	check recaptcha on registration
	 *	filter function for `registration_errors`, `authenticate`
	 *
	 *	@param $errors WP_Error
	 *	@return WP_Error with captcha error added if test fails.
	 */
	function registration_errors( $errors ) {
		if ( isset( $_POST["user_login"]) )
			$errors = $this->wp_error_add( $errors );
		return $errors;
	}

	/**
	 *	check recaptcha WPMU signup
	 *	filter function for `wpmu_validate_user_signup`
	 *
	 *	@see filter hook `wpmu_validate_user_signup`
	 */
	function wpmu_validate_user_signup( $result ) {
		if ( isset( $_POST['stage'] ) && $_POST['stage'] == 'validate-user-signup' )
			$result['errors'] = $this->wp_error_add( $result['errors'] , 'generic' );
		return $result;
	}


	/**
	 *	check recaptcha and return WP_Error on failure.
	 *	filter function for `allow_password_reset`
	 *
	 *	@param $param mixed return value of funtion when captcha validates
	 *	@return mixed will return argument $param an success, else WP_Error
	 */
	function wp_error( $param , $error_code = 'captcha_error' ) {
		if ( ! $this->recaptcha_check() ) {
			return new WP_Error( $error_code ,  __("<strong>Error:</strong> the Captcha didn’t verify.",'wp-recaptcha-integration') );
		}
		return $param;
	}
	/**
	 *	check recaptcha and return WP_Error on failure.
	 *	filter function for `allow_password_reset`
	 *
	 *	@param $param mixed return value of funtion when captcha validates
	 *	@return mixed will return argument $param an success, else WP_Error
	 */
	function wp_error_add( $param , $error_code = 'captcha_error' ) {
		if ( ! $this->recaptcha_check() ) {
			return new WP_Error( $error_code ,  __("<strong>Error:</strong> the Captcha didn’t verify.",'wp-recaptcha-integration') );
		} else {
			return $param;
		}
	}

	/**
	 *	Check recaptcha and wp_die() on fail
	 *	hooks into `pre_comment_on_post`, `lostpassword_post`
	 */
 	function recaptcha_check_or_die( ) {
 		if ( ! $this->recaptcha_check() ) {
 			$err = new WP_Error('comment_err',  __("<strong>Error:</strong> the Captcha didn’t verify.",'wp-recaptcha-integration') );
 			wp_die( $err );
 		}
 	}


	//////////////////////////////////
	// 	Options
	//

	/**
	 *	Get plugin option by name.
	 *
	 *	@param $option_name string
	 *	@return bool false if check does not validate
	 */
	public function get_option( $option_name ) {
		switch ( $option_name ) {
			case 'recaptcha_publickey': // first try local, then global
			case 'recaptcha_privatekey':
				$option_value = get_option($option_name);
				if ( ! $option_value && WP_reCaptcha::is_network_activated() )
					$option_value = get_site_option( $option_name );
				return $option_value;
			case 'recaptcha_enable_comments': // global on network. else local
			case 'recaptcha_enable_signup':
			case 'recaptcha_enable_login':
			case 'recaptcha_enable_lostpw':
			case 'recaptcha_disable_for_known_users':
			case 'recaptcha_enable_wc_order':
				if ( WP_reCaptcha::is_network_activated() )
					return get_site_option($option_name);
				return get_option( $option_name );
			default: // always local
				return get_option($option_name);
		}
	}

	/**
	 *	@return bool return if google api is configured
	 */
	function has_api_key() {
		return $this->_has_api_key;
	}

	/**
	 *	Test public and private key
	 *
	 *	@return bool
	 */
	public function test_keys() {
// 		if ( ! ( $keys_okay = get_transient( 'recaptcha_keys_okay' ) ) ) {
			$pub_okay = $this->test_public_key();
			$prv_okay = $this->test_private_key();

// 			$keys_okay = ( $prv_okay && $pub_okay ) ? 'yes' : 'no';

			//cache the result
// 			set_transient( 'recaptcha_keys_okay' , $keys_okay , 15 * MINUTE_IN_SECONDS );
// 		}
		return $prv_okay && $pub_okay;
	}

	/**
	 *	Test public key
	 *
	 *	@return bool
	 */
	public function test_public_key( $key = null ) {
		if ( is_null( $key ) )
			$key = $this->get_option('recaptcha_publickey');
		$rec = WP_reCaptcha::instance();
		$pub_key_url = sprintf( "http://www.google.com/recaptcha/api/challenge?k=%s" , $key );
		$pub_response = wp_remote_get( $pub_key_url );
		$pub_response_body = wp_remote_retrieve_body( $pub_response );
		return ! is_wp_error( $pub_response ) && ! strpos( $pub_response_body ,'Format of site key was invalid');
	}

	/**
	 *	Test private key
	 *
	 *	@return bool
	 */
	public function test_private_key( $key = null ) {
		if ( is_null( $key ) )
			$key = $this->get_option('recaptcha_privatekey');
		$prv_key_url = sprintf( "http://www.google.com/recaptcha/api/verify?privatekey=%s" , $key );
		$prv_response = wp_remote_get( $prv_key_url );
		$prv_rspbody = wp_remote_retrieve_body( $prv_response );
		return ! is_wp_error( $prv_response ) && ! strpos(wp_remote_retrieve_body( $prv_response ),'invalid-site-private-key');
	}


	//////////////////////////////////
	// 	Activation
	//

	/**
	 *	Fired on plugin activation
	 */
	public static function activate() {

		if ( function_exists('wpcf7') ) {
			// IF CF7 is active, try to configure plugin from cf7 options
			if ( $wpcf7_options = get_option('wpcf7') ) {
				if ( isset( $wpcf7_options['recaptcha'] ) && !self::instance()->has_api_key() ) {
					foreach ( $wpcf7_options['recaptcha'] as $sitekey => $secretkey ) {
						update_option('recaptcha_publickey',$sitekey);
						update_option('recaptcha_privatekey',$secretkey);
						break;
					}
				}
			}
		}
	}

	/**
	 *	Fired on plugin deactivation
	 */
	public static function deactivate() {
	}
	/**
	 *	Uninstall
	 */
	public static function uninstall() {
		if ( is_multisite() ) {
			delete_site_option( 'recaptcha_publickey' );
			delete_site_option( 'recaptcha_privatekey' );
			delete_site_option( 'recaptcha_enable_comments' );
			delete_site_option( 'recaptcha_enable_signup' );
			delete_site_option( 'recaptcha_enable_login' );
			delete_site_option( 'recaptcha_enable_wc_checkout' );
			delete_site_option( 'recaptcha_disable_for_known_users' );
			delete_site_option( 'recaptcha_lockout' );

			foreach ( wp_get_sites() as $site) {
				switch_to_blog( $site["blog_id"] );
				delete_option( 'recaptcha_publickey' );
				delete_option( 'recaptcha_privatekey' );
				delete_option( 'recaptcha_flavor' );
				delete_option( 'recaptcha_theme' );
				delete_option( 'recaptcha_language' );
				delete_option( 'recaptcha_comment_use_42_filter' );
				delete_option( 'recaptcha_noscript' );
				restore_current_blog();
			}
		} else {
			delete_option( 'recaptcha_publickey' );
			delete_option( 'recaptcha_privatekey' );

			delete_option( 'recaptcha_flavor' );
			delete_option( 'recaptcha_theme' );
			delete_option( 'recaptcha_language' );
			delete_option( 'recaptcha_enable_comments' );
			delete_option( 'recaptcha_enable_signup' );
			delete_option( 'recaptcha_enable_login' );
			delete_option( 'recaptcha_enable_wc_checkout' );
			delete_option( 'recaptcha_disable_for_known_users' );
			delete_option( 'recaptcha_lockout' );
		}
	}

	/**
	 *	Get plugin option by name.
	 *
	 *	@return bool true if plugin is activated on network
	 */
	static function is_network_activated() {
		if ( is_null(self::$_is_network_activated) ) {
			if ( ! is_multisite() )
				return false;
			if ( ! function_exists( 'is_plugin_active_for_network' ) )
				require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

			self::$_is_network_activated = is_plugin_active_for_network( basename(dirname(__FILE__)).'/'.basename(__FILE__) );
		}
		return self::$_is_network_activated;
	}




	//////////////////////////////////
	// 	Language
	//

	/**
	 *	Rewrite WP get_locale() to recaptcha lang param.
	 *
	 *	@return string recaptcha language
	 */
	function recaptcha_wplang( ) {
		$locale = get_locale();
		return $this->captcha_instance()->get_language( $locale );
	}
	/**
	 *	Get recaptcha language code that matches input language code
	 *
	 *	@param	$lang	string language code
	 *	@return	string	recaptcha language code if supported, empty string otherwise
	 */
	function recaptcha_language( $lang ) {
		return $this->captcha_instance()->get_language( $lang );
	}

	/**
	 *	Get languages supported.
	 *
	 *	@return array languages supported by recaptcha.
	 */
	function get_supported_languages( ) {
		return $this->captcha_instance()->get_supported_languages();
	}

}
