<?php



class WordPress_reCaptcha_Options {
	private $enter_api_key;
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
		add_action('admin_init', array(&$this,'admin_init') );
		add_action('admin_menu', array(&$this,'add_options_page') );

	}
	function api_key_notice() {
		?><div class="notice error above-h1"><p><?php 
			printf( 
				__( '<strong>reCaptcha needs your attention:</strong> To make it work You need to enter an api key. <br />You can do so at the <a href="%s">reCaptcha settings page</a>.' , 'recaptcha' ),
				admin_url( add_query_arg( 'page' , 'recaptcha' , 'options-general.php' ) )
			);
		?></p></div><?php
	}
	function admin_init( ) {

		$has_api_key = WordPress_reCaptcha::instance()->has_api_key();
		if ( ! $has_api_key && current_user_can( 'manage_options' ) ) {
			add_action('admin_notices',array( &$this , 'api_key_notice'));
		}

		$this->enter_api_key = ! $has_api_key || ( isset($_REQUEST['action']) && $_REQUEST['action'] == 'recaptcha-api-key' && isset($_REQUEST['_wpnonce']) && $nonce_valid = wp_verify_nonce($_REQUEST['_wpnonce'],$_REQUEST['action']) );
		if ( $this->enter_api_key ) {
			register_setting( 'recaptcha_options', 'recaptcha_publickey' );
			register_setting( 'recaptcha_options', 'recaptcha_privatekey' );
			add_settings_field('recaptcha_publickey', __('Public Key','recaptcha'), array(&$this,'input_text'), 'recaptcha', 'recaptcha_apikey' , array('name'=>'recaptcha_publickey') );
			add_settings_field('recaptcha_privatekey', __('Private Key','recaptcha'), array(&$this,'input_text'), 'recaptcha', 'recaptcha_apikey', array('name'=>'recaptcha_privatekey'));
			add_settings_section('recaptcha_apikey', __( 'Connecting' , 'recaptcha' ), array(&$this,'explain_apikey'), 'recaptcha');
			if ( $has_api_key ) {
				add_settings_field('cancel', '' , array(&$this,'cancel_enter_api_key'), 'recaptcha', 'recaptcha_apikey' );
			}
		} else if ( $nonce_valid === false) {
			wp_die('Security Check');
		} else {
			add_settings_section('recaptcha_apikey', __( 'Connecting' , 'recaptcha' ), array(&$this,'explain_apikey'), 'recaptcha');
		}
		
		if ( $has_api_key ) {

			register_setting( 'recaptcha_options', 'recaptcha_theme' );
			register_setting( 'recaptcha_options', 'recaptcha_enable_comments' , 'intval');
			register_setting( 'recaptcha_options', 'recaptcha_enable_signup', 'intval' );
			register_setting( 'recaptcha_options', 'recaptcha_enable_login' , 'intval');
			register_setting( 'recaptcha_options', 'recaptcha_disable_for_known_users' , 'intval');

			add_settings_section('recaptcha_options', __( 'Features' , 'recaptcha' ), array(&$this,'explain_options'), 'recaptcha');

			add_settings_field('recaptcha_theme', __('Theme','recaptcha'), array(&$this,'select_theme'), 'recaptcha', 'recaptcha_options');


			add_settings_field('recaptcha_enable_comments', __('Protect Comments','recaptcha'), 
				array(&$this,'input_checkbox'), 'recaptcha', 'recaptcha_options' , 
				array('name'=>'recaptcha_enable_comments','label'=>__( 'Protect comment forms with recaptcha.' ,'recaptcha' ) ) 
			);
			
			add_settings_field('recaptcha_enable_signup', __('Protect Signup','recaptcha'), 
				array(&$this,'input_checkbox'), 'recaptcha', 'recaptcha_options',      
				array('name'=>'recaptcha_enable_signup','label'=>__( 'Protect signup form with recaptcha.','recaptcha' ) )
			);
			
			add_settings_field('recaptcha_enable_login', __('Protect Login','recaptcha'), 
				array(&$this,'input_checkbox'), 'recaptcha', 'recaptcha_options' ,
				array('name'=>'recaptcha_enable_login','label'=>__( 'Protect Login form with recaptcha.','recaptcha' )) 
			);

			add_settings_field('recaptcha_disable_for_known_users', __('Disable for known users','recaptcha'), 
				array(&$this,'input_checkbox'), 'recaptcha', 'recaptcha_options' ,
				array('name'=>'recaptcha_disable_for_known_users','label'=>__( 'Disable reCaptcha verification for logged in users.','recaptcha' )) 
			);

			if ( ! get_option( 'recaptcha_publickey' ) || ! get_option( 'recaptcha_privatekey' ) )
				add_settings_error('recaptcha',1,__('Please configure the public and private key. <a href="http://www.google.com/recaptcha/whyrecaptcha">What are you trying to tell me?</a>','recaptcha'),'updated');
		}
	}

	public function explain_apikey( ) {
		if ( $this->enter_api_key ) {
			?><p class="description"><?php 
				$info_url = 'https://developers.google.com/recaptcha/intro';
				$admin_url = 'https://www.google.com/recaptcha/admin';
				printf(
					__( 'Please register your blog through the <a href="%s">Google reCAPTCHA admin page</a> and enter the public and private key in the fields below. <a href="%s">What is this all about</a>', 'recaptcha' ) ,
						$admin_url , $info_url 
					);
			?></p><?php
		} else {
			?><p class="description"><?php 
				_e( 'You already entered an API Key. Use the button below to enter it again.','recaptcha');
			?></p><?php
			$action = 'recaptcha-api-key';
			$nonce = wp_create_nonce( $action );
			$url = add_query_arg( array('_wpnonce' => $nonce , 'action' => $action ) );
			?><p class="submit"><?php 
				?><a class="button" href="<?php echo $url ?>"><?php _e('New API Key' , 'recaptcha') ?></a><?php
			?></p><?php
			
		}
	}
	public function cancel_enter_api_key(){
		$url = remove_query_arg( array('_wpnonce' , 'action' , 'settings-updated' ) );
		?><a class="button" href="<?php echo $url ?>"><?php _e('Cancel' ) ?></a><?php
	}
	public function explain_options( ) {
		?><p class="description"><?php 
			_e('','recaptcha');
		?></p><?php
	}
	
	public function input_checkbox($args) {
		extract($args);
		$value = get_option( $name );
		?><label for="<?php echo $name ?>"><?php
			?><input id="<?php echo $name ?>" type="checkbox" name="<?php echo $name ?>" value="1" <?php checked($value,1,true) ?> />
			<?php
			echo $label;
		?></label><?php
	}
	public function input_text( $args ) {
		extract( $args );
		$value = get_option( $name );
		?><input type="text" class="regular-text ltr" name="<?php echo $name ?>" value="<?php //echo $value ?>" /><?php
	}
	
	public function select_theme() {
		$option_name = 'recaptcha_theme';
		$themes = array(
			'red' => __('Red','recaptcha'),
			'white' => __('White','recaptcha'),
			'blackglass' => __('Black Glass','recaptcha'),
			'clean' => __('Clean','recaptcha'),
			'custom' => __('Custom','recaptcha'),
		);
		$theme = get_option($option_name);
		
		?><div class="recaptcha-select-theme"><?php
		
		foreach ( $themes as $value => $label ) {
			?><input <?php checked($value,$theme,true); ?> id="<?php echo "$option_name-$value" ?>" type="radio" name="<?php echo $option_name ?>" value="<?php echo $value ?>" /><?php
			?><label for="<?php echo "$option_name-$value" ?>"><?php
				?><span class="title"><?php 
					echo $label;
				?></span><?php
				if ( $value == 'custom' ) {
					?><span class="visual"><?php
						_e( 'Unstyled HTML so you can apply the Stylesheets yourself.' );
					?></span><?php
				} else {
					$src = plugins_url( "images/recaptcha-theme-{$value}.png" , dirname(__FILE__));
					printf( '<img src="%s" alt="%s" />' , $src , $label );
				}
			?></label><?php
			
		}
		?></div><?php
	}
	public function add_options_page() {
		$page_slug = add_options_page( 
			__('ReCaptcha','recaptcha'), __('ReCaptcha','recaptcha'), 
			'manage_options', 'recaptcha', 
			array(&$this,'render_options_page')
		);
		add_action( "load-$page_slug" , array( &$this , 'enqueue_styles' ) );
	}
	public function enqueue_styles() {
		wp_enqueue_style( 'recaptcha-options' , plugins_url( "css/recaptcha-options.css" , dirname(__FILE__)) );
		remove_action('admin_notices',array( &$this , 'api_key_notice'));
	}
	public function render_options_page() {
		?><div class="wrap"><?php
			?><h2><?php /*icon*/ 
				_e('Settings');
				echo ' › '; 
				_e( 'ReCaptcha' , 'recaptcha' ); 
			?></h2><?php
		/*	?><p><?php _e( '...' , 'googlefont' ); ?></p><?php */
			?><form action="options.php" method="post"><?php
				settings_fields( 'recaptcha_options' );
				do_settings_sections( 'recaptcha' ); 
				?><input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" /><?php
			?></form><?php
		?></div><?php
	}
	
	
}


WordPress_reCaptcha_Options::instance();

