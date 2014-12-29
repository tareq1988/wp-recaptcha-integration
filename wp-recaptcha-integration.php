<?php
/*
Plugin Name: WP reCaptcha Integration
Plugin URI: https://wordpress.org/plugins/wp-recaptcha-integration/
Description: Integrate reCaptcha in your blog. Supports no Captcha (new style recaptcha) as well as the old stle reCaptcha. Provides of the box integration for signup, login, comment forms, lost password, Ninja Forms and contact form 7.
Version: 1.0.2
Author: Jörn Lund
Author URI: https://github.com/mcguffin/
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



class WP_reCaptcha {

	private static $_is_network_activated = null;

	private $_has_api_key = false;

	private $last_error = '';
	private $_last_result;
	
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
		add_option('recaptcha_flavor','grecaptcha'); // local
		add_option('recaptcha_theme','light'); // local
		add_option('recaptcha_disable_submit',false); // local
		add_option('recaptcha_publickey',''); // 1st global -> then local
		add_option('recaptcha_privatekey',''); // 1st global -> then local

		if ( WP_reCaptcha::is_network_activated() ) {
			add_site_option('recaptcha_publickey',''); // 1st global -> then local
			add_site_option('recaptcha_privatekey',''); // 1st global -> then local
			
			add_site_option('recaptcha_enable_comments' , true); // global
			add_site_option('recaptcha_enable_signup' , true); // global
			add_site_option('recaptcha_enable_login' , false); // global
			add_site_option('recaptcha_enable_lostpw' , false); // global
			add_site_option('recaptcha_disable_for_known_users' , true); // global
			$this->_has_api_key = get_site_option( 'recaptcha_publickey' ) && get_site_option( 'recaptcha_privatekey' );
		} else {
			add_option('recaptcha_enable_comments' , true); // global
			add_option('recaptcha_enable_signup' , true); // global
			add_option('recaptcha_enable_login' , false); // global
			add_option('recaptcha_enable_lostpw' , false); // global
			add_option('recaptcha_disable_for_known_users' , true); // global
			$this->_has_api_key = get_option( 'recaptcha_publickey' ) && get_option( 'recaptcha_privatekey' );
		}

		if ( $this->_has_api_key ) {
			add_action( 'wp_head' , array($this,'recaptcha_head') );
			add_action( 'wp_footer' , array($this,'recaptcha_foot') );

			add_action('init' , array(&$this,'init') );
			add_action('plugins_loaded' , array(&$this,'plugins_loaded') );

			if ( $this->get_option('recaptcha_enable_signup') || $this->get_option('recaptcha_enable_login')  || $this->get_option('recaptcha_enable_lostpw') ) {
				add_action( 'login_head' , array(&$this,'recaptcha_head') );
				add_action( 'login_footer' , array(&$this,'recaptcha_foot') );
			}
		}

		register_activation_hook( __FILE__ , array( __CLASS__ , 'activate' ) );
		register_deactivation_hook( __FILE__ , array( __CLASS__ , 'deactivate' ) );
		register_uninstall_hook( __FILE__ , array( __CLASS__ , 'uninstall' ) );

	}

	/**
	 *	@return bool return if google api is configured
	 */
	function has_api_key() {
		return $this->_has_api_key;
	}
	
	/**
	 *	Load ninja/cf7 php files if necessary
	 *	Hooks into 'plugins_loaded'
	 */
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
	/**
	 *	Init plugin
	 *	set hooks
	 */
	function init() {
		load_plugin_textdomain( 'wp-recaptcha-integration', false , dirname( plugin_basename( __FILE__ ) ).'/languages/' );
		
		$require_recaptcha = $this->is_required();
		
		if ( $this->get_option('recaptcha_enable_comments') && $require_recaptcha ) {
			/*
			add_action('comment_form_after_fields',array($this,'print_recaptcha_html'),10,0);
			/*/
			add_filter('comment_form_defaults',array($this,'comment_form_defaults'),10);
			//*/
			add_action('pre_comment_on_post',array($this,'recaptcha_check_or_die'));
		}
		if ( $this->get_option('recaptcha_enable_signup') && $require_recaptcha ) {
			add_action('register_form',array($this,'print_recaptcha_html'),10,0);
			add_filter('registration_errors',array(&$this,'login_errors'));
		}
		if ( $this->get_option('recaptcha_enable_login') && $require_recaptcha ) {
			add_action('login_form',array($this,'print_recaptcha_html'),10,0);
			add_filter('wp_authenticate_user',array(&$this,'deny_login'),99 );
		}
		if ( $this->get_option('recaptcha_enable_lostpw') && $require_recaptcha ) {
			add_action('lostpassword_form' , array($this,'print_recaptcha_html'),10,0);
			add_filter('lostpassword_post' , array(&$this,'recaptcha_check_or_die'),99 );
		}
	}
	/**
	 *	Display recaptcha on comments form.
	 *	filter function for `comment_form_defaults`
	 *	set hooks
	 */
	function comment_form_defaults( $defaults ) {
		$defaults['comment_notes_after'] .= '<p>' . $this->recaptcha_html() . '</p>';
		return $defaults;
	}
	/**
	 *	returns if recaptcha is required.
	 *	@return bool
	 */
	function is_required() {
		$is_required = ! ( $this->get_option('recaptcha_disable_for_known_users') && current_user_can( 'read' ) );
		return apply_filters( 'recaptcha_required' , $is_required );
	}
	
	/**
	 *	check recaptcha on login
	 *	filter function for `wp_authenticate_user`
	 *	@return object user or wp error
	 */
	function deny_login( $user ) {
		if ( isset( $_POST["log"] ) && ! $this->recaptcha_check() ) {
			return new WP_Error( 'captcha_error' ,  __("<strong>Error:</strong> the Captcha didn’t verify.",'wp-recaptcha-integration') );
		} else {
			return $user;
		}
	}
	/**
	 *	check recaptcha on registration
	 *	filter function for `registration_errors`
	 *	@return object errors
	 */
	function login_errors( $errors ) {
		if ( isset( $_POST["log"] ) && ! $this->recaptcha_check() ) {
			$errors->add( 'captcha_error' ,  __("<strong>Error:</strong> the Captcha didn’t verify.",'wp-recaptcha-integration') );
		}
		return $errors;
	}

	/**
	 *	print recaptcha stylesheets
	 *	hooks into `wp_head`
	 */
	function recaptcha_head( $flavor = '' ) {
		if ( empty( $flavor ) )
			$flavor = $this->get_option( 'recaptcha_flavor' );
		$this->begin_inject( );
 		switch ( $flavor ) {
 			case 'grecaptcha':
				?><style type="text/css">
				#login {
					width:350px !important;
				}
				</style><?php
				break;
 			case 'recaptcha':
				$recaptcha_theme = $this->get_option('recaptcha_theme');
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
				break;
		}
		$this->end_inject( );
 	}
	/**
	 *	Print recaptcha scripts
	 *	hooks into `wp_footer`
	 */
	function recaptcha_foot( $flavor = '' ) {
		if ( empty( $flavor ) )
			$flavor = $this->get_option( 'recaptcha_flavor' );
		
		$this->begin_inject( );
		// getting submit buttons of an elements form
		if ( $this->get_option( 'recaptcha_disable_submit' ) ) { 
			?><script type="text/javascript">
			function get_form_submits(el){
				var form,current=el,ui,type,slice = Array.prototype.slice,self=this;
				this.submits=[];
				this.form=false;
				
				this.setEnabled=function(e){
					for ( var s in self.submits ) {
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
				for (var i in ui ) if ( (type=ui[i].getAttribute('TYPE')) && type=='submit' ) this.submits.push(ui[i]);
				return this;
			}
			</script><?php
		}
 		switch ( $flavor ) {
 			case 'grecaptcha':
				?><script type="text/javascript">
				function recaptchaLoadCallback(){ 
					var e=document.getElementsByClassName('g-recaptcha'),form_submits;
					for (var i=0;i<e.length;i++) {
						(function(el){
<?php if ( $this->get_option( 'recaptcha_disable_submit' ) ) { ?>
							var form_submits = get_form_submits(el).setEnabled(false);
<?php } ?>
							grecaptcha.render(el,{
								'sitekey':'<?php echo $this->get_option('recaptcha_publickey'); ?>',
								'theme':'<?php echo $this->get_option('recaptcha_theme'); ?>'
<?php if ( $this->get_option( 'recaptcha_disable_submit' ) ) { ?>
								,
								'callback' : function(r){ form_submits.setEnabled(true); /* enable submit buttons */ }
<?php } ?>
							});
						})(e[i]);
					}
				}
				</script><?php
				?><script src="https://www.google.com/recaptcha/api.js?onload=recaptchaLoadCallback&render=explicit" async defer></script><?php
				break;
 			case 'recaptcha':
				if ( $this->get_option( 'recaptcha_disable_submit' ) ) { 
					?><script type="text/javascript">
					document.addEventListener('keyup',function(e){
						if (e.target && typeof e.target.getAttribute=='function' && e.target.getAttribute('ID')=='recaptcha_response_field') {
							get_form_submits(e.target).setEnabled(!!e.target.value);
						}
					});
					document.addEventListener('DOMContentLoaded',function(e){
						try {
							get_form_submits(document.getElementById('wp-recaptcha-integration-marker')).setEnabled(false);
						} catch(e){};
					});
					</script><?php
 				}
				break;
		}
		$this->end_inject( );
	}
	/**
	 *	Check recaptcha and wp_die() on fail
	 *	hooks into `pre_comment_on_post`, `lostpassword_post`
	 */
 	function recaptcha_check_or_die( ) {
 		if ( ! $this->recaptcha_check() )
 			wp_die( __("Sorry, the Captcha didn’t verify.",'wp-recaptcha-integration') );
 	}
 	
	/**
	 *	Print recaptcha HTML. Use inside a form.
	 */
 	function print_recaptcha_html( $flavor = '' ) {
 		echo $this->recaptcha_html( $flavor );
 	}
 	
	/**
	 *	Get recaptcha HTML.
	 *	@return string recaptcha html
	 */
 	function recaptcha_html( $flavor = '' ) {
		
		if ( empty( $flavor ) )
			$flavor = $this->get_option( 'recaptcha_flavor' );
		$return = $this->begin_inject( true );
			
 		switch ( $flavor ) {
 			case 'grecaptcha':
 				$return .= $this->grecaptcha_html();
 				break;
 			case 'recaptcha':
 				$return .= $this->old_recaptcha_html();
 				break;
 		}
		$return .= $this->end_inject( true );
		return $return;
 	}

	/**
	 *	Get old style recaptcha HTML.
	 *	@return string recaptcha html
	 */
 	function old_recaptcha_html() {
		require_once dirname(__FILE__).'/recaptchalib.php';
		$public_key = $this->get_option( 'recaptcha_publickey' );
		$recaptcha_theme = $this->get_option('recaptcha_theme');

		if ($recaptcha_theme == 'custom') 
			$return = $this->get_custom_html( $public_key );
		else
			$return = recaptcha_get_html( $public_key, $this->last_error );
		if ( $this->get_option( 'recaptcha_disable_submit' ) ) {
			$return .= '<span id="wp-recaptcha-integration-marker"></span>';
		}
		return $return;
 	}
 	
	/**
	 *	Get no captcha (new style recaptcha) HTML.
	 *	@return string recaptcha html
	 */
	function grecaptcha_html() {
		$public_key = $this->get_option( 'recaptcha_publickey' );
		$theme = $this->get_option('recaptcha_theme');
		$return = sprintf( '<div class="g-recaptcha" data-sitekey="%s" data-theme="%s"></div>',$public_key,$theme);
		return $return;
	}
	
	/**
	 *	Get un-themed old style recaptcha HTML.
	 *	@return string recaptcha html
	 */
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
	
	/**
	 *	Get last result of recaptcha check
	 *	@return string recaptcha html
	 */
	function get_last_result() {
		return $this->_last_result;
	}
	
	/**
	 *	Check recaptcha
	 *	@return bool false if check does not validate
	 */
	function recaptcha_check( $flavor = '' ) {
		if ( empty( $flavor ) )
			$flavor = $this->get_option( 'recaptcha_flavor' );
 		switch ( $flavor ) {
 			case 'grecaptcha':
 				return $this->grecaptcha_check();
 			case 'recaptcha':
 				return $this->old_recaptcha_check();
 		}
	}
	/**
	 *	Check no captcha
	 *	@return bool false if check does not validate
	 */
	function grecaptcha_check() {
		$private_key = $this->get_option( 'recaptcha_privatekey' );
		$user_response = isset( $_REQUEST['g-recaptcha-response'] ) ? $_REQUEST['g-recaptcha-response'] : false;
		if ( $user_response ) {
			$remote_ip = $_SERVER['REMOTE_ADDR'];
			$url = "https://www.google.com/recaptcha/api/siteverify?secret=$private_key&response=$user_response&remoteip=$remote_ip";
			$response = wp_remote_get( $url );
			if ( ! is_wp_error($response) ) {
				$response_data = wp_remote_retrieve_body( $response );
				$this->_last_result = json_decode($response_data);
				return $this->_last_result->success;
			}
		}
		return false;
	}
	/**
	 *	Check old style recaptcha
	 *	@return bool false if check does not validate
	 */
	function old_recaptcha_check() {
		require_once dirname(__FILE__).'/recaptchalib.php';
		$private_key = $this->get_option( 'recaptcha_privatekey' );
		$this->_last_result = recaptcha_check_answer( $private_key,
			$_SERVER["REMOTE_ADDR"],
			$_POST["recaptcha_challenge_field"],
			$_POST["recaptcha_response_field"]);

		if ( ! $this->_last_result->is_valid )
			$this->last_error = $this->_last_result->error;

		return $this->_last_result->is_valid;
	}
	
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
				if ( WP_reCaptcha::is_network_activated() )
					return get_site_option($option_name);
				return get_option( $option_name );
			default: // always local
				return get_option($option_name);
		}
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
	 *	Uninstall
	 */
	public static function uninstall() {
		if ( is_multisite() ) {
			delete_site_option( 'recaptcha_publickey' );
			delete_site_option( 'recaptcha_privatekey' );
			delete_site_option( 'recaptcha_enable_comments' );
			delete_site_option( 'recaptcha_enable_signup' );
			delete_site_option( 'recaptcha_enable_login' );
			delete_site_option( 'recaptcha_disable_for_known_users' );
			
			foreach ( wp_get_sites() as $site) {
				switch_to_blog( $site["blog_id"] );
				delete_option( 'recaptcha_publickey' );
				delete_option( 'recaptcha_privatekey' );
				delete_option( 'recaptcha_flavor' );
				delete_option( 'recaptcha_theme' );
				restore_current_blog();
			}
		} else {
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
}


WP_reCaptcha::instance();

if ( is_admin() )
	require_once dirname(__FILE__).'/inc/class-wp-recaptcha-options.php';
