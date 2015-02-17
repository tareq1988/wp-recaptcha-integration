<?php



/**
 *	Class to manage ContactForm 7 Support
 */
class WP_reCaptcha_Lockout {
	
	private $action = 'wp-recaptcha-lockout';

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
		add_action( 'init'  , array( &$this , 'early_init' ) , 5 );
		add_action( 'init'  , array( &$this , 'init' )  );
	}
	
	public function early_init(){
		if ( $this->is_lockout_page() ) {
			add_filter( 'wp_recaptcha_required' , '__return_false' );
// 			add_action( 'login_form_'.$this->action , array( &$this , 'login_form_action' ) );
		}
	}
	
	public function init() {
		$rec = WP_reCaptcha::instance();
		if ( $rec->get_option( 'recaptcha_enable_login' ) ) {
			add_action('login_form',array(&$this,'print_lockout_link'),10,0);
		}
		
		if ( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'] , $this->action ) ) {
			remove_filter( 'wp_authenticate_user' , array( WP_reCaptcha::instance() , 'deny_login' ) , 99 );
			add_action( 'wp_login' , array( &$this , 'login_send_email' ) , 10 , 2 );
		} else {
			add_action( 'wp_login' , array( &$this , 'login_check_code' ) , 10 , 2 );
		}
	}
	
	public function print_lockout_link( ) {
		if ( $this->is_lockout_page() ) {
			if ( isset( $_REQUEST['step'] ) && $_REQUEST['step'] == 'mail-sent' ) {
				
			} else {
				?><p><?php 
					_e( 'Very likely the public and private key you entered during the recaptcha integration plugin setup are not valid anymore. <em>Please follow these steps to reset the keys.</em>' , 'wp-recaptcha-integration' );
				 ?></p><?php
				?><ol><?php
					?><li><?php _e( '<strong>Log in</strong> with your administrator account using the form above.' , 'wp-recaptcha-integration' ); ?></li><?php
					?><li><?php _e( 'You will recieve a <strong>confirmation email</strong>. Follow the link, log in again and the keys will be reset.' , 'wp-recaptcha-integration' ); ?></li><?php
					?><li><?php _e( 'To make the plugin work again, <strong>set up a new keypair</strong>.' , 'wp-recaptcha-integration' ); ?></li><?php
				?></ol><?php
				wp_nonce_field( $this->action );
			}
		} else {
			$lockedout_url =  add_query_arg( 'action' , $this->action , wp_login_url( ) );
			?><p><a href="<?php echo $lockedout_url ?>"><?php 
				_e( 'I locked myself out.' , 'wp-recaptcha-integration' );
			?></a></p><?php
		}
	}
	
	private function is_lockout_page() {
		return $GLOBALS['pagenow'] === 'wp-login.php' && isset( $_REQUEST['action'] ) && $_REQUEST['action'] === $this->action;
	}
	
	function login_send_email( $user_id , $user ) {
		if ( current_user_can( 'manage_options' ) ) {
			

			$code = md5( rand(0,0x0fffffff ) . time() );
			update_user_meta( 'wp_recaptcha_lockout' , $code );
		
			// send confirmation email...
			$mail_to = $user->email;
			$mail_subject = sprintf( __( '[%1$s] captcha key reset' ) , wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ) );
			$mail_body = '';;
			$mail_body .= __( "Please follow this link, to reset the recaptcha keypair." , 'wp-recaptcha-integration' );
			$mail_body .= "\n\n" . add_query_arg( array( $this->action , $code ) , wp_login_url( ) );
			$mail_body .= "\n\n" . __( "Don‘t forget to set up a new keypair!" , 'wp-recaptcha-integration' );
			$mail_headers;
			wp_mail( $mail_to, wp_specialchars_decode( $mail_subject ), $mail_body, '' );

			wp_logout();

			$redirect_to = add_query_arg( array( 'action' => $this->action , 'step' => 'mail-sent' ) , wp_login_url( ) );// wp_login_url();
			wp_safe_redirect( $redirect_to );
			exit();
		}
// 		if ( current_user_can( 'manage_options' ) )
// 		$a = func_get_args();
// 		var_dump($a);
		exit();
	}
	
	function login_check_code() {
		if ( isset( $_REQUEST[ $this->action ] ) && $_REQUEST[ $this->action ] == get_user_meta( $user_id , 'wp_recaptcha_lockout' , true ) ) {
			if ( is_multisite() && current_user_can( 'manage_network' ) ) {
				delete_site_option( 'recaptcha_publickey' );
				delete_site_option( 'recaptcha_privatekey' );
			}
			delete_option( 'recaptcha_publickey' );
			delete_option( 'recaptcha_privatekey' );
		}
	}
}