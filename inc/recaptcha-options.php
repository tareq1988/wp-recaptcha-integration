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
		
		add_action( 'pre_update_option_recaptcha_publickey' , array( &$this , 'update_option_recaptcha_apikey' ) , 10 , 2 );
		add_action( 'pre_update_option_recaptcha_privatekey' , array( &$this , 'update_option_recaptcha_apikey' ) , 10 , 2 );
		add_action( 'add_option_recaptcha_publickey' , array( &$this , 'add_option_recaptcha_apikey' ) , 10 , 2 );
		add_action( 'add_option_recaptcha_privatekey' , array( &$this , 'add_option_recaptcha_apikey' ) , 10 , 2 );
	}
	
	function update_option_recaptcha_apikey( $new , $old ){
		add_filter( 'wp_redirect' , array( &$this , 'remove_new_apikey_url' ) );
		return $new;
	}
	function add_option_recaptcha_apikey( $option , $value ){
		if ( in_array( $option , array('recaptcha_publickey','recaptcha_privatekey') ) )
			add_filter( 'wp_redirect' , array( &$this , 'remove_new_apikey_url' ) );
	}
	
	function remove_new_apikey_url( $url = null ) {
		return remove_query_arg( array('_wpnonce' , 'recaptcha-action' , 'settings-updated' ) , $url );
	}
	
	function api_key_notice() {
		?><div class="notice error above-h1"><p><?php 
			printf( 
				__( '<strong>reCaptcha needs your attention:</strong> To make it work You need to enter an api key. <br />You can do so at the <a href="%s">reCaptcha settings page</a>.' , 'wp-recaptcha-integration' ),
				admin_url( add_query_arg( 'page' , 'recaptcha' , 'options-general.php' ) )
			);
		?></p></div><?php
	}
	function admin_init( ) {

		$has_api_key = WordPress_reCaptcha::instance()->has_api_key();
		if ( ! $has_api_key && current_user_can( 'manage_options' ) ) {
			add_action('admin_notices',array( &$this , 'api_key_notice'));
		}

		$this->enter_api_key = ! $has_api_key || ( isset($_REQUEST['recaptcha-action']) && $_REQUEST['recaptcha-action'] == 'recaptcha-set-api-key');
		if ( $this->enter_api_key ) {
			// no API Key. Let the user enter it.
			register_setting( 'recaptcha_options', 'recaptcha_publickey' , 'trim' );
			register_setting( 'recaptcha_options', 'recaptcha_privatekey' , 'trim' );
			add_settings_field('recaptcha_publickey', __('Public Key','wp-recaptcha-integration'), array(&$this,'input_text'), 'recaptcha', 'recaptcha_apikey' , array('name'=>'recaptcha_publickey') );
			add_settings_field('recaptcha_privatekey', __('Private Key','wp-recaptcha-integration'), array(&$this,'input_text'), 'recaptcha', 'recaptcha_apikey', array('name'=>'recaptcha_privatekey'));
			add_settings_section('recaptcha_apikey', __( 'Connecting' , 'wp-recaptcha-integration' ), array(&$this,'explain_apikey'), 'recaptcha');
			if ( $has_api_key ) {
				add_settings_field('cancel', '' , array(&$this,'cancel_enter_api_key'), 'recaptcha', 'recaptcha_apikey' );
			}
		} else if ( @$nonce_valid === false) {
			wp_die('Security Check');
		} else {
			// API Key. Add test tool.
			add_settings_section('recaptcha_apikey', __( 'Connecting' , 'wp-recaptcha-integration' ), array(&$this,'explain_apikey'), 'recaptcha');
			add_action('wp_ajax_recaptcha-test-api-key' , array( &$this , 'ajax_test_api_key' ) );
		}

		if ( $has_api_key ) {
			register_setting( 'recaptcha_options', 'recaptcha_flavor' , array( &$this , 'sanitize_flavor' ) );
			register_setting( 'recaptcha_options', 'recaptcha_theme'  , array( &$this , 'sanitize_theme' ) );
			register_setting( 'recaptcha_options', 'recaptcha_enable_comments' , 'intval');
			register_setting( 'recaptcha_options', 'recaptcha_enable_signup', 'intval' );
			register_setting( 'recaptcha_options', 'recaptcha_enable_login' , 'intval');
			register_setting( 'recaptcha_options', 'recaptcha_enable_lostpw' , 'intval');
			register_setting( 'recaptcha_options', 'recaptcha_disable_for_known_users' , 'intval');

			add_settings_section('recaptcha_options', __( 'Features' , 'wp-recaptcha-integration' ), '__return_false', 'recaptcha');

			add_settings_field('recaptcha_flavor', __('Flavor','wp-recaptcha-integration'), 
				array(&$this,'input_radio'), 'recaptcha', 'recaptcha_options',
				array( 
					'name' => 'recaptcha_flavor',
					'items' => array(
						array(
							'value' => 'grecaptcha',
							'label' => __( 'No Captcha where you just click a button' , 'wp-recaptcha-integration' ),
						),
						array(
							'value' => 'recaptcha',
							'label' => __( 'Old style reCAPTCHA where you type some cryptic text' , 'wp-recaptcha-integration' ),
						),
					),
				 ) );

			add_settings_field('recaptcha_theme', __('Theme','wp-recaptcha-integration'), array(&$this,'select_theme'), 'recaptcha', 'recaptcha_options');


			add_settings_field('recaptcha_enable_comments', __('Protect Comments','wp-recaptcha-integration'), 
				array(&$this,'input_checkbox'), 'recaptcha', 'recaptcha_options' , 
				array('name'=>'recaptcha_enable_comments','label'=>__( 'Protect comment forms with recaptcha.' ,'wp-recaptcha-integration' ) ) 
			);
			
			add_settings_field('recaptcha_enable_signup', __('Protect Signup','wp-recaptcha-integration'), 
				array(&$this,'input_checkbox'), 'recaptcha', 'recaptcha_options',      
				array('name'=>'recaptcha_enable_signup','label'=>__( 'Protect signup form with recaptcha.','wp-recaptcha-integration' ) )
			);
			
			add_settings_field('recaptcha_enable_login', __('Protect Login','wp-recaptcha-integration'), 
				array(&$this,'input_checkbox'), 'recaptcha', 'recaptcha_options' ,
				array('name'=>'recaptcha_enable_login','label'=>__( 'Protect Login form with recaptcha.','wp-recaptcha-integration' )) 
			);

			add_settings_field('recaptcha_enable_lostpw', __('Protect Lost Password','wp-recaptcha-integration'), 
				array(&$this,'input_checkbox'), 'recaptcha', 'recaptcha_options' ,
				array('name'=>'recaptcha_enable_lostpw','label'=>__( 'Protect Lost Password form with recaptcha.','wp-recaptcha-integration' )) 
			);

			add_settings_field('recaptcha_disable_for_known_users', __('Disable for known users','wp-recaptcha-integration'), 
				array(&$this,'input_checkbox'), 'recaptcha', 'recaptcha_options' ,
				array('name'=>'recaptcha_disable_for_known_users','label'=>__( 'Disable reCaptcha verification for logged in users.','wp-recaptcha-integration' )) 
			);

			if ( ! get_option( 'recaptcha_publickey' ) || ! get_option( 'recaptcha_privatekey' ) )
				add_settings_error('recaptcha',1,__('Please configure the public and private key. <a href="http://www.google.com/recaptcha/whyrecaptcha">What are you trying to tell me?</a>','wp-recaptcha-integration'),'updated');
		}
	}

	public function explain_apikey( ) {
		if ( $this->enter_api_key ) {
			?><p class="description"><?php 
				$info_url = 'https://developers.google.com/recaptcha/intro';
				$admin_url = 'https://www.google.com/recaptcha/admin';
				printf(
					__( 'Please register your blog through the <a href="%s">Google reCAPTCHA admin page</a> and enter the public and private key in the fields below. <a href="%s">What is this all about</a>', 'wp-recaptcha-integration' ) ,
						$admin_url , $info_url 
					);
			?></p><?php
			?><input type="hidden" name="recaptcha-action" value="recaptcha-set-api-key" /><?php
		} else {
			?><div class="recaptcha-explain"><?php
				?><p class="description"><?php 
					_e( 'You already entered an API Key. Use the button below to enter it again.','wp-recaptcha-integration');
				?></p><?php
				$action = 'recaptcha-set-api-key';
				$nonce = wp_create_nonce( $action );
				$new_url = add_query_arg( array('_wpnonce' => $nonce , 'recaptcha-action' => $action ) );
			
				$action = 'recaptcha-test-api-key';
				$nonce = wp_create_nonce( $action );
				$test_url = add_query_arg( array('_wpnonce' => $nonce , 'action' => $action ) , admin_url( 'admin-ajax.php' ) );
			
				?><p class="submit"><?php 
					?><a class="button" href="<?php echo $new_url ?>"><?php _e('New API Key' , 'wp-recaptcha-integration') ?></a><?php
					?><a id="test-api-key" class="button" href="<?php echo $test_url ?>"><?php _e('Test API Key' , 'wp-recaptcha-integration') ?></a><?php
				?></p><?php
			?></div><?php
		}
	}
	
	public function ajax_test_api_key() {
		if ( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'] , $_REQUEST['action'] ) ) {
			header('Content-Type: text/html');
			WordPress_reCaptcha::instance()->recaptcha_script( 'grecaptcha' );
			WordPress_reCaptcha::instance()->print_recaptcha_html( 'grecaptcha' );
			$action = 'recaptcha-test-verification';
			$nonce = wp_create_nonce( $action );
			?><input type="hidden" name="<?php echo $action ?>-nonce" value="<?php echo $nonce ?>" /><?php
			?><button id="<?php echo $action ?>" name="action" class="button-primary" value="<?php echo $action ?>"><?php _e('Test verfication','wp-recaptcha-integration') ?></button><?php
		}
		exit(0);
	}
	public function ajax_test_api_key_verification() {
		if ( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'] , $_REQUEST['action'] ) ) {
			header('Content-Type: text/html');
			if ( ! WordPress_reCaptcha::instance()->recaptcha_check( 'grecaptcha' ) ) {
				$errs = array(
					'missing-input-secret' => __('The secret Key is missing.','wp-recaptcha-integration'),
					'invalid-input-secret' => __('The secret Key is invalid. You better check your domain configuration and enter it again.','wp-recaptcha-integration'),
					'missing-input-response' => __('The user response was missing ','wp-recaptcha-integration'),
					'invalid-input-response' => __('Invalid user response','wp-recaptcha-integration'),
				);
				$result = WordPress_reCaptcha::instance()->get_last_result();
				if ( isset( $result['error-codes'] ) ) {
					foreach ( $result['error-codes'] as $err ) {
					}
				}
			} else {
				?><p><?php _e('Works! All good!','wp-recaptcha-integration') ?></p><?php
			}
		}
		exit(0);
	}
	
	public function cancel_enter_api_key(){
		$url = $this->remove_new_apikey_url( );
		?><a class="button" href="<?php echo $url ?>"><?php _e( 'Cancel' ) ?></a><?php
	}
	
	public function input_radio( $args ) {
		extract($args); // name, items
		$option = get_option( $name );
		foreach ( $items as $item ) {
			extract( $item ); // value, label
			?><label for="<?php echo "$name-$value" ?>"><?php
				?><input id="<?php echo "$name-$value" ?>" type="radio" name="<?php echo $name ?>" value="<?php echo $value ?>" <?php checked($value,$option,true) ?> />
				<?php
				echo $label;
			?></label><br /><?php
		}
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
				'light' => array(
					'label' => __('Light','wp-recaptcha-integration') ,
					'flavor' => 'grecaptcha',
				),
				'dark' => array(
					'label' => __('Dark','wp-recaptcha-integration') ,
					'flavor' => 'grecaptcha',
				),

				'red' => array(
					'label' => __('Red','wp-recaptcha-integration') ,
					'flavor' => 'recaptcha',
				),
				'white' => array(
					'label' => __('White','wp-recaptcha-integration') ,
					'flavor' => 'recaptcha',
				),
				'blackglass' => array(
					'label' => __('Black Glass','wp-recaptcha-integration') ,
					'flavor' => 'recaptcha',
				),
				'clean' => array(
					'label' => __('Clean','wp-recaptcha-integration') ,
					'flavor' => 'recaptcha',
				),
				'custom' => array(
					'label' => __('Custom','wp-recaptcha-integration') ,
					'flavor' => 'recaptcha',
				),
			);

			$option_theme = get_option($option_name);
			$option_flavor = get_option( 'recaptcha_flavor' );
		
			?><div class="recaptcha-select-theme flavor-<?php echo $option_flavor ?>"><?php
		
			foreach ( $themes as $value => $theme ) {
				extract( $theme ); // label, flavor
				?><div class="theme-item flavor-<?php echo $flavor ?>"><?php
					?><input <?php checked($value,$option_theme,true); ?> id="<?php echo "$option_name-$value" ?>" type="radio" name="<?php echo $option_name ?>" value="<?php echo $value ?>" /><?php
					?><label for="<?php echo "$option_name-$value" ?>"><?php
						?><span class="title"><?php 
							echo $label;
						?></span><?php
						if ( $value == 'custom' ) {
							?><span class="visual"><?php
								_e( 'Unstyled HTML to apply your own Stylesheets.' , 'wp-recaptcha-integration' );
							?></span><?php
						} else {
							$src = plugins_url( "images/{$flavor}-theme-{$value}.png" , dirname(__FILE__));
							printf( '<img src="%s" alt="%s" />' , $src , $label );
						}
					?></label><?php
				?></div><?php
			
			}
			?></div><?php
		?></div><?php
	}
	public function add_options_page() {
		$page_slug = add_options_page( 
			__('ReCaptcha','wp-recaptcha-integration'), __('ReCaptcha','wp-recaptcha-integration'), 
			'manage_options', 'recaptcha', 
			array(&$this,'render_options_page')
		);
		add_action( "load-$page_slug" , array( &$this , 'enqueue_styles' ) );
	}
	public function enqueue_styles() {
		wp_enqueue_style( 'recaptcha-options' , plugins_url( "css/recaptcha-options.css" , dirname(__FILE__)) );
		wp_enqueue_script( 'recaptcha-options' , plugins_url( "js/recaptcha-options.js" , dirname(__FILE__)) , array( 'jquery' ) );
		remove_action('admin_notices',array( &$this , 'api_key_notice'));
	}
	public function render_options_page() {
		?><div class="wrap"><?php
			?><h2><?php /*icon*/ 
				_e('Settings');
				echo ' › '; 
				_e( 'ReCaptcha' , 'wp-recaptcha-integration' ); 
			?></h2><?php
		/*	?><p><?php _e( '...' , 'googlefont' ); ?></p><?php */
			?><form action="options.php" method="post"><?php
				settings_fields( 'recaptcha_options' );
				do_settings_sections( 'recaptcha' ); 
				?><input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" /><?php
			?></form><?php
		?></div><?php
	}
	public function sanitize_flavor( $flavor ) {
		if ( in_array($flavor,array('recaptcha','grecaptcha')) )
			return $flavor;
		return 'grecaptcha';
	}
	public function sanitize_theme( $theme ) {
		$themes_available = array(
			'recaptcha' => array( 'white','red','blackglass','clean','custom' ),
			'grecaptcha' => array( 'light','dark' ),
		);
		$flavor = get_option('recaptcha_flavor');
		
		if ( isset($themes_available[$flavor] ) && in_array($theme,$themes_available[$flavor]) )
			return $theme;
		else if ( isset($themes_available[$flavor] ) )
			return $themes_available[$flavor][0];
		return 'light';
	}
	
}


WordPress_reCaptcha_Options::instance();

