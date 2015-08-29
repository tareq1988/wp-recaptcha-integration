<?php



/**
 *	Class to manage the recaptcha options.
 */
class WP_reCaptcha_Options {
	private $enter_api_key;
	/**
	 *	Holding the singleton instance
	 */
	private static $_instance = null;

	/**
	 *	@return WP_reCaptcha_Options The options manager instance
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

		if ( WP_reCaptcha::is_network_activated() ) {
			$page_hook = 'settings_page_racaptcha-settings';
			add_action( "load-{$page_hook}", array( &$this , 'enqueue_styles' ));
			add_action( "load-{$page_hook}", array( &$this , 'process_network_settings' ));
			add_action( 'network_admin_menu', array( &$this , 'network_settings_menu' ));
		}

		add_action( 'pre_update_option_recaptcha_publickey' , array( &$this , 'update_option_recaptcha_apikey' ) , 10 , 2 );
		add_action( 'pre_update_option_recaptcha_privatekey' , array( &$this , 'update_option_recaptcha_apikey' ) , 10 , 2 );
		add_action( 'add_option_recaptcha_publickey' , array( &$this , 'add_option_recaptcha_apikey' ) , 10 , 2 );
		add_action( 'add_option_recaptcha_privatekey' , array( &$this , 'add_option_recaptcha_apikey' ) , 10 , 2 );
	}


	/**
	 *	Process network options
	 */
	function process_network_settings() {
		if ( current_user_can('manage_network') ) {
			$opts = array(
				'recaptcha_publickey' => 'trim',
				'recaptcha_privatekey' => 'trim',
				'recaptcha_enable_comments' => 'intval',
				'recaptcha_enable_signup' => 'intval',
				'recaptcha_enable_login' => 'intval',
				'recaptcha_enable_lostpw' => 'intval',
				'recaptcha_enable_wc_order' => 'intval',
				'recaptcha_disable_for_known_users' => 'intval',
				'recaptcha_noscript' => 'intval',
				'recaptcha_comment_use_42_filter' => 'intval',
			);
			if ( array_intersect( array_keys( $_POST ) , array_keys( $opts ) ) )
				check_admin_referer( 'recaptcha-network-settings' );

			$updated = false;
			foreach ( $opts as $option_name => $sanitize_cb ) {
				if ( isset( $_POST[ $option_name ] ) ) {
					$option_value = sanitize_text_field( $_POST[ $option_name ] );
					if ( is_callable($sanitize_cb) )
						$option_value = call_user_func($sanitize_cb,$_POST[$option_name]);
					update_site_option( $option_name , $option_value );
					$updated = true;
				}
			}
			if ( $updated ) {
				$redirect = $this->remove_new_apikey_url(add_query_arg(array('updated'=>'true') ) );
				wp_redirect( $redirect );
			}
		} else {
			wp_die('Cheating, uh?');
		}
		// expecting api keys,
	}

	/**
	 *	Network menu hook
	 */
	function network_settings_menu(){
		add_submenu_page(
			'settings.php',
			__( 'reCaptcha Settings' , 'wp-recaptcha-integration' ),
			__( 'reCaptcha' , 'wp-recaptcha-integration' ),
			'manage_network', 'racaptcha-settings',
			array(&$this , 'network_settings_page' ) );
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
			do_settings_sections('recaptcha');
			submit_button();
			?></form><?php
		?></div><?php
	}



	/**
	 *	Update option hook. Remove url params for redirect
	 *
	 *	@param new updated option value
	 *	@param old old option value
	 */
	function update_option_recaptcha_apikey( $new , $old ){
		delete_transient('recaptcha_keys_okay');
		add_filter( 'wp_redirect' , array( &$this , 'remove_new_apikey_url' ) );
		return $new;
	}
	/**
	 *	Add option hook. See update_option_recaptcha_apikey()
	 *
	 *	@param option option name
	 *	@param value option value
	 */
	function add_option_recaptcha_apikey( $option , $value ){
		delete_transient('recaptcha_keys_okay');
		if ( in_array( $option , array('recaptcha_publickey','recaptcha_privatekey') ) )
			add_filter( 'wp_redirect' , array( &$this , 'remove_new_apikey_url' ) );
	}

	/**
	 *	Removes Update api key url params
	 *	@param url URL
	 *	@return string URL without params removed
	 */
	function remove_new_apikey_url( $url = null ) {
		return remove_query_arg( array('_wpnonce' , 'recaptcha-action' , 'settings-updated' ) , $url );
	}

	/**
	 *	Admin Notices hook to show up when the api keys heve not been entered.
	 */
	function api_key_notice() {
		?><div class="notice error above-h1"><p><?php
			printf(
				__( '<strong>reCaptcha needs your attention:</strong> To make it work You need to enter an api key. <br />You can do so at the <a href="%s">reCaptcha settings page</a>.' , 'wp-recaptcha-integration' ),
				admin_url( add_query_arg( 'page' , 'recaptcha' , 'options-general.php' ) )
			);
		?></p></div><?php
	}

	/**
	 *	admin init hook. Setup settings according.
	 */
	function admin_init( ) {
		$has_api_key = WP_reCaptcha::instance()->has_api_key();
		if ( ! $has_api_key && current_user_can( 'manage_options' ) ) {
			add_action('admin_notices',array( &$this , 'api_key_notice'));
		}

		$this->enter_api_key = ! $has_api_key || ( isset($_REQUEST['recaptcha-action']) && $_REQUEST['recaptcha-action'] == 'recaptcha-set-api-key');

		if ( ! $this->enter_api_key ) {
			add_settings_section( 'recaptcha_protection' , __( 'Protect' , 'wp-recaptcha-integration' ), array( &$this , 'explain_protection' ), 'recaptcha');
			add_settings_section( 'recaptcha_styling' ,  __( 'Style' , 'wp-recaptcha-integration' ), array( &$this , 'explain_styling' ), 'recaptcha');
		}
		add_settings_section( 'recaptcha_apikey' , __( 'Connect' , 'wp-recaptcha-integration' ), array( &$this , 'explain_apikey' ), 'recaptcha');
		if ( ! $this->enter_api_key ) {
			add_settings_section( 'recaptcha_advanced' ,  __( 'Advanced' , 'wp-recaptcha-integration' ), array( &$this , 'explain_advanced' ), 'recaptcha');
		}
		if ( $this->enter_api_key ) {
			// no API Key. Let the user enter it.
			register_setting( 'recaptcha_options', 'recaptcha_publickey' , 'trim' );
			register_setting( 'recaptcha_options', 'recaptcha_privatekey' , 'trim' );
			add_settings_field('recaptcha_publickey', __('Site key','wp-recaptcha-integration'), array(&$this,'secret_input_text'), 'recaptcha', 'recaptcha_apikey' , array('name'=>'recaptcha_publickey') );
			add_settings_field('recaptcha_privatekey', __('Secret key','wp-recaptcha-integration'), array(&$this,'secret_input_text'), 'recaptcha', 'recaptcha_apikey', array('name'=>'recaptcha_privatekey'));
			if ( $has_api_key ) {
				add_settings_field('cancel', '' , array(&$this,'cancel_enter_api_key'), 'recaptcha', 'recaptcha_apikey' );
			}
		} else {
			// API Key. Add test tool.
			add_action('wp_ajax_recaptcha-init-test-api-key' , array( &$this , 'ajax_init_test_api_key' ) );
			add_action('wp_ajax_recaptcha-test-api-key' , array( &$this , 'ajax_test_api_key' ) );
			add_action('wp_ajax_recaptcha-test-verification' , array( &$this , 'ajax_test_api_key_verification' ) );
		}

		if ( $has_api_key ) {
			if (  ! WP_reCaptcha::is_network_activated() ||  ! is_network_admin()  ) {
				// local options
				register_setting( 'recaptcha_options', 'recaptcha_language'  , array( &$this , 'sanitize_language' ) );
				register_setting( 'recaptcha_options', 'recaptcha_flavor' , array( &$this , 'sanitize_flavor' ) );
				register_setting( 'recaptcha_options', 'recaptcha_theme'  , array( &$this , 'sanitize_theme' ) );
				register_setting( 'recaptcha_options', 'recaptcha_disable_submit' , 'intval');

				register_setting( 'recaptcha_options', 'recaptcha_noscript' , 'intval');


				add_settings_field('recaptcha_flavor', __('Flavor','wp-recaptcha-integration'),
					array(&$this,'input_radio'), 'recaptcha', 'recaptcha_styling',
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

				add_settings_field('recaptcha_language', __( 'Language Settings' ), array(&$this,'select_language'), 'recaptcha', 'recaptcha_styling');

				add_settings_field('recaptcha_theme', __('Theme','wp-recaptcha-integration'), array(&$this,'select_theme'), 'recaptcha', 'recaptcha_styling');

				add_settings_field('recaptcha_disable_submit', __('Disable Submit Button','wp-recaptcha-integration'),
					array(&$this,'input_checkbox'), 'recaptcha', 'recaptcha_styling' ,
					array('name'=>'recaptcha_disable_submit','label'=>__( 'Disable Form Submit Button until no-captcha is entered.' ,'wp-recaptcha-integration' ) )
				);
				add_settings_field('recaptcha_noscript', __('Noscript Fallback','wp-recaptcha-integration'),
					array(&$this,'input_checkbox'), 'recaptcha', 'recaptcha_advanced' ,
					array(
						'name'=>'recaptcha_noscript',
						'label'=>__( 'Provide a fallback for non javascript capable browsers.','wp-recaptcha-integration' ),
						'description' => __( 'Leave this unchecked when your site requires JavaScript anyway.','wp-recaptcha-integration' ),
						'class' => 'flavor-grecaptcha',
					)
				);

				global $wp_version;
				if ( version_compare( $wp_version , '4.2' ) >= 0 ) {
					register_setting( 'recaptcha_options', 'recaptcha_comment_use_42_filter' , 'intval');
					add_settings_field('recaptcha_comment_use_42_filter', __('Comment Form rendering','wp-recaptcha-integration'),
						array(&$this,'input_checkbox'), 'recaptcha', 'recaptcha_advanced' ,
						array(
							'name'=>'recaptcha_comment_use_42_filter',
							'label'=>__( 'My Comment Form is WordPress 4.2 compatible.','wp-recaptcha-integration' ),
							'description' => __( 'Enable this when your comment form uses the <code>comment_form_submit_button</code> filter. (Or just try if it works.)','wp-recaptcha-integration' ),
							'class' => 'flavor-grecaptcha',
						)
					);
				}
			}

			if ( ! WP_reCaptcha::is_network_activated() || is_network_admin() ) {
				// network options
				register_setting( 'recaptcha_options', 'recaptcha_enable_comments' , 'intval');
				register_setting( 'recaptcha_options', 'recaptcha_enable_signup', 'intval' );
				register_setting( 'recaptcha_options', 'recaptcha_enable_login' , 'intval');
				register_setting( 'recaptcha_options', 'recaptcha_enable_lostpw' , 'intval');
				register_setting( 'recaptcha_options', 'recaptcha_lockout' , 'intval');

				if ( function_exists('WC') ) {
					register_setting( 'recaptcha_options', 'recaptcha_enable_wc_order' , 'intval');
				}
				if ( class_exists( 'Awesome_Support' ) ) {
					register_setting( 'recaptcha_options', 'recaptcha_enable_as_registration' , 'intval');
				}
				if ( class_exists( 'bbPress' ) ) {
					register_setting( 'recaptcha_options', 'recaptcha_enable_bbp_topic' , 'intval');
					register_setting( 'recaptcha_options', 'recaptcha_enable_bbp_reply' , 'intval');
				}

				register_setting( 'recaptcha_options', 'recaptcha_disable_for_known_users' , 'intval');

				$protect_settings = array();
				$protect_settings[] = array(
					'name' 			=>	'recaptcha_enable_comments',
					'label'			=>	__('Comment Form','wp-recaptcha-integration'),
					'class' 		=> 	'',
				);
				$protect_settings[] = array(
					'name' 			=>	'recaptcha_enable_signup',
					'label'			=>	__('Signup Form','wp-recaptcha-integration'),
					'class' 		=> 	'',
				);
				$protect_settings[] = array(
					'name' 			=>	'recaptcha_enable_login',
					'label'			=>	__('Login Form','wp-recaptcha-integration'),
					'class' 		=> 	'',
				);
				$protect_settings[] = array(
					'name' 			=>	'recaptcha_enable_lostpw',
					'label'			=>	__('Lost Password Form','wp-recaptcha-integration'),
					'class' 		=> 	'',
				);

				if ( function_exists('WC') ) {
					$wc_warn =  WP_reCaptcha::instance()->get_option('recaptcha_enable_wc_order') && WP_reCaptcha::instance()->get_option('recaptcha_flavor') !== 'grecaptcha';
					$protect_settings[] = array(
						'name'=>'recaptcha_enable_wc_order',
						'label'=>__( 'woocommerce Checkout','wp-recaptcha-integration' ),
						'class'	=> 'flavor-grecaptcha',
					);
				}

				if ( class_exists( 'Awesome_Support' ) ) {

					$protect_settings[] = array(
						'name'=>'recaptcha_enable_as_registration',
						'label'=>__( 'Awesome Support Registration','wp-recaptcha-integration' ),
						'class'	=> '',
					);
				}

				if ( class_exists( 'bbPress' ) ) {
					$protect_settings[] = array(
						'name'  => 'recaptcha_enable_bbp_topic',
						'label' => __( 'bbPress New Topic','wp-recaptcha-integration' ),
						'class' => 'flavor-grecaptcha',
					);

					$protect_settings[] = array(
						'name'  => 'recaptcha_enable_bbp_reply',
						'label' => __( 'bbPress New Reply','wp-recaptcha-integration' ),
						'class' => 'flavor-grecaptcha',
					);
				}

				add_settings_field('recaptcha_protection', __('Forms to protect','wp-recaptcha-integration'),
					array(&$this,'input_protect'), 'recaptcha', 'recaptcha_protection' ,
					$protect_settings
				);

				add_settings_field('recaptcha_disable_for_known_users', __('Disable for known users','wp-recaptcha-integration'),
					array(&$this,'input_checkbox'), 'recaptcha', 'recaptcha_protection' ,
					array('name'=>'recaptcha_disable_for_known_users','label'=>__( 'Disable reCaptcha verification for logged in users.','wp-recaptcha-integration' ))
				);

				add_settings_field('recaptcha_lockout', __('Prevent lockout','wp-recaptcha-integration'),
					array(&$this,'input_checkbox'), 'recaptcha', 'recaptcha_protection' ,
					array(
						'name'=>'recaptcha_lockout',
						'label'=>__( 'Allow administrator to log in if API keys do not work.','wp-recaptcha-integration' ),
						'description' => __( 'When the captcha verification fails, and the private or public API key does not work the plugin will let you in anyway. Please note that with this option checked plus a broken keypair your site will be open to brute force attacks, that try to guess an administrator password.','wp-recaptcha-integration' ),
					)
				);
			}

			if ( ! WP_reCaptcha::instance()->get_option( 'recaptcha_publickey' ) || ! WP_reCaptcha::instance()->get_option( 'recaptcha_privatekey' ) )
				add_settings_error('recaptcha',1,__('Please configure the public and private key. <a href="http://www.google.com/recaptcha/whyrecaptcha">What are you trying to tell me?</a>','wp-recaptcha-integration'),'updated');
		}
	}

	/**
	 *	Intro text for the api key setting
	 */
	public function explain_apikey( ) {
		if ( $this->enter_api_key ) {
			?><p class="description"><?php
				$info_url = 'https://developers.google.com/recaptcha/intro';
				$admin_url = 'https://www.google.com/recaptcha/admin';
				printf(
					__( 'Please register your blog through the <a href="%s">Google reCAPTCHA admin page</a> and enter the public and private key in the fields below. <a href="%s">What is this all about?</a>', 'wp-recaptcha-integration' ) ,
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

				$action = 'recaptcha-init-test-api-key';
				$nonce = wp_create_nonce( $action );
				$init_test_url = add_query_arg( array('_wpnonce' => $nonce , 'action' => $action ) , admin_url( 'admin-ajax.php' ) );

				?><p class="submit"><?php
					?><a class="button" href="<?php echo $new_url ?>"><?php _e('New API Key' , 'wp-recaptcha-integration') ?></a><?php
					?><a id="test-api-key" class="button" data-init-href="<?php echo $init_test_url ?>" href="<?php echo $test_url ?>"><?php _e('Test API Key' , 'wp-recaptcha-integration') ?></a><?php
				?></p><?php
			?></div><?php
		}
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
	 *	Intro text for the Protection setting
	 */
	public function explain_advanced() {
		// nothing to say here, huh?
	}
	/**
	 *	Intro text for the Styling setting
	 */
	public function explain_styling() {
		?><div class="recaptcha-explain"><?php
			?><p class="description"><?php
				_e( 'Choose a flavor and theme for your Captcha. Please note that with the old style reCaptcha you cannot have more than one captcha per page.' , 'wp-recaptcha-integration' );
			?></p><?php
		?></div><?php
	}


	/**
	 *	Test api key ajax response.
	 *	Returns reCaptcha HTML
	 */
	public function ajax_init_test_api_key() {
		if ( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'] , $_REQUEST['action'] ) ) {
			header('Content-Type: text/html');
			add_filter('option_recaptcha_disable_submit','__return_false');
			WP_reCaptcha_noCaptcha::instance()->print_head( 'grecaptcha' );
			WP_reCaptcha_noCaptcha::instance()->print_foot( 'grecaptcha' );
		}
		exit(0);
	}
	/**
	 *	Test api key ajax response.
	 *	Returns reCaptcha HTML
	 */
	public function ajax_test_api_key() {
		if ( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'] , $_REQUEST['action'] ) ) {
			header('Content-Type: text/html');
// 			WP_reCaptcha::instance()->recaptcha_head( 'grecaptcha' );
// 			WP_reCaptcha::instance()->recaptcha_foot( 'grecaptcha' );
			echo WP_reCaptcha_noCaptcha::instance()->get_html( 'grecaptcha' );
			$action = 'recaptcha-test-verification';
			$nonce = wp_create_nonce( $action );
			?><input type="hidden" name="<?php echo $action ?>-nonce" value="<?php echo $nonce ?>" /><?php
			?><button id="<?php echo $action ?>" name="action" class="button-primary" value="<?php echo $action ?>"><?php _e('Test verfication','wp-recaptcha-integration') ?></button><?php
		}
		exit(0);
	}
	/**
	 *	Test api key ajax response.
	 *	checks the verification process and shows an error on fail.
	 */
	public function ajax_test_api_key_verification() {
		if ( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'] , $_REQUEST['action'] ) ) {
			header('Content-Type: text/html');
			if ( ! WP_reCaptcha_noCaptcha::instance()->check( 'grecaptcha' ) ) {
				$errs = array(
					'missing-input-secret' => __('The secret Key is missing.','wp-recaptcha-integration'),
					'invalid-input-secret' => __('The secret Key is invalid. You better check your domain configuration and enter it again.','wp-recaptcha-integration'),
					'missing-input-response' => __('The user response was missing','wp-recaptcha-integration'),
					'invalid-input-response' => __('Invalid user response','wp-recaptcha-integration'),
				);
				$result = (array) WP_reCaptcha_noCaptcha::instance()->get_last_result();
				if ( isset( $result['error-codes'] ) ) {
					foreach ( $result['error-codes'] as $err ) {
						?><div class="error"><p><?php echo $errs[$err]; ?></p></div><?php
					}
				}
			} else {
				?><div class="updated"><p><?php _e('Works! All good!','wp-recaptcha-integration') ?></p></div><?php
			}
		}
		exit(0);
	}

	/**
	 *	Link for canceling api key entering
	 *	checks the verification process and shows an error on fail.
	 */
	public function cancel_enter_api_key() {
		$url = $this->remove_new_apikey_url( add_query_arg(null,null) );
		?><a class="button" href="<?php echo $url ?>"><?php _e( 'Cancel' ) ?></a><?php
	}

	/**
	 *	Radio buttons
	 *
	 *	@param args array(
	 *					'name' => name of the input field,
	 *					'items' => array(
	 *						array(
	 *							'value' => '', // the radio button value, must match an option name
	 *							'label' => '', // the button label
	 *						),
	 *						...
	 *					)
	 *				)
	 */
	public function input_radio( $args ) {
		extract($args); // name, items
		$option = WP_reCaptcha::instance()->get_option( $name );
		foreach ( $items as $item ) {
			extract( $item ); // value, label
			?><label for="<?php echo "$name-$value" ?>"><?php
				?><input id="<?php echo "$name-$value" ?>" type="radio" name="<?php echo $name ?>" value="<?php echo $value ?>" <?php checked($value,$option,true) ?> />
				<?php
				echo $label;
			?></label><br /><?php
		}
	}

	/**
	 *	A Checkbox
	 *
	 *	@param args array(
	 *					'name' => '', // checkbox field name, must match an option name
	 *					'label' => '', // button label
	 *				)
	 */
	public function input_checkbox($args) {
		$args = wp_parse_args($args,array(
			'name' => '',
			'label' => '',
			'description' => '',
			'class' => '',
		));
		extract($args);
		$value = WP_reCaptcha::instance()->get_option( $name );
		$class_attr = $class ? "class=\"{$class}\"" : '';
		?><label <?php echo $class_attr ?> for="<?php echo $name ?>"><?php
			?><input type="hidden" name="<?php echo $name ?>" value="0" /><?php
			?><input id="<?php echo $name ?>" type="checkbox" name="<?php echo $name ?>" value="1" <?php checked($value,1,true) ?> />
			<?php
			echo $label;
			if ( $description ) {
				?><p class="description"><?php echo $description ?></p><?php
			}
		?></label><?php
	}

	/**
	 *	On/off switch. Basically A Checkbox
	 *
	 *	@param args array(
	 *					'name' => '', // checkbox field name, must match an option name
	 *					'label' => '', // button label
	 *					'description' => '', // button label
	 *					'class' => '', // button label
	 *				)
	 */
	public function input_check_onoff($args) {
		$args = wp_parse_args($args,array(
			'name' => '',
			'label' => '',
			'class' => '',
		));
		extract($args);
		$value = WP_reCaptcha::instance()->get_option( $name );

		?><div class="recaptcha-onoff <?php echo $class ?>"><?php
			?><input type="hidden" name="<?php echo $name ?>" value="0" /><?php
			?><input id="<?php echo $name ?>" type="checkbox" name="<?php echo $name ?>" value="1" <?php checked($value,1,true) ?> /><?php
			?><label for="<?php echo $name ?>"><?php
				?><span class="dashicons dashicons-lock"></span><?php
				?><span class="title"><?php echo $label ?></span><?php
			?></label><?php
		?></div><?php
	}

	public function input_protect( $switches ) {
		foreach ( $switches as $switch )
			$this->input_check_onoff( $switch );
	}

	/**
	 *	A Text field.
	 *	Used for api key input.
	 *
	 *	@param args array(
	 *					'name' => checkbox field name, must match an option name
	 *				)
	 */
	public function secret_input_text( $args ) {
		extract( $args );
		$value = WP_reCaptcha::instance()->get_option( $name );
		?><input type="text" class="regular-text ltr" name="<?php echo $name ?>" value="<?php //echo $value ?>" /><?php
	}
	/**
	 *	Selector for recaptcha theme
	 */
	public function select_language() {
		$option_name = 'recaptcha_language';
		$option_value = WP_reCaptcha::instance()->get_option( $option_name );

		$all_available_langs = array(
			'recaptcha'		=>	WP_reCaptcha_ReCaptcha::instance()->get_supported_languages(),
			'grecaptcha'	=>	WP_reCaptcha_NoCaptcha::instance()->get_supported_languages(),
		);

		?><div class="recaptcha-select-language flavor-<?php echo $option_flavor ?>"><?php
			foreach( $all_available_langs as $flavor => $available_langs ) {
				?><select class="flavor-<?php echo $flavor ?>" name="<?php echo $option_name ?>[<?php echo $flavor ?>]"><?php
					?><option <?php selected($option_value,'',true); ?> value=""><?php _e( 'Automatic','wp-recaptcha-integration' ); ?></option><?php
					?><option <?php selected($option_value,'WPLANG',true); ?> value="WPLANG"><?php _e( 'Site Language' ); ?></option><?php
					?><optgroup label="<?php _e('Other') ?>"><?php
					foreach ( $available_langs as $lang => $lang_name ) {
						?><option <?php selected($option_value,$lang,true); ?> value="<?php echo $lang; ?>"><?php _e( $lang_name ); ?></option><?php
					}
					?></optgroup><?php
				?></select><?php
			}
		?></div><?php
	}
	/**
	 *	Selector for recaptcha theme
	 */
	public function select_theme() {
		$option_name = 'recaptcha_theme';

		$grecaptcha_themes = WP_reCaptcha_NoCaptcha::instance()->get_supported_themes();
		$grecaptcha_themes = array_map( array( $this , '_map_grecaptcha' ) , $grecaptcha_themes );

		$recaptcha_themes  = WP_reCaptcha_ReCaptcha::instance()->get_supported_themes();
		$recaptcha_themes = array_map( array( $this , '_map_recaptcha' ) , $recaptcha_themes );

		$themes = $grecaptcha_themes + $recaptcha_themes;

		$option_theme = WP_reCaptcha::instance()->get_option($option_name);
		$option_flavor = WP_reCaptcha::instance()->get_option( 'recaptcha_flavor' );

		?><div class="recaptcha-select-theme"><?php

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

	/**
	 *	@usage select_theme()
	 */
	private function _map_grecaptcha( $val ) {
		$val['flavor'] = 'grecaptcha';
		return $val;
	}
	/**
	 *	@usage select_theme()
	 */
	private function _map_recaptcha( $val ) {
		$val['flavor'] = 'recaptcha';
		return $val;
	}

	/**
	 *	Check if language is supported by current recaptcha flavor
	 */
	public function sanitize_language( $language ) {
		$flavor = isset($_REQUEST['recaptcha_flavor']) ? $_REQUEST['recaptcha_flavor'] : WP_reCaptcha::instance()->get_option('recaptcha_flavor');

		if ( isset( $language[$flavor] ) )
			$language = $language[$flavor];

		if ( $language != 'WPLANG' )
			$language = WP_reCaptcha::instance()->captcha_instance_by_flavor( $flavor )->get_language( $language );
		return $language;
	}
	/**
	 *	Check valid recaptcha theme, check if theme fits to flavor
	 */
	public function sanitize_theme( $theme ) {
		$themes_available = array(
			'recaptcha' => array( 'white','red','blackglass','clean','custom' ),
			'grecaptcha' => array( 'light','dark' ),
		);
		$flavor = WP_reCaptcha::instance()->get_option('recaptcha_flavor');

		if ( isset($themes_available[$flavor] ) && in_array($theme,$themes_available[$flavor]) )
			return $theme;
		else if ( isset($themes_available[$flavor] ) )
			return $themes_available[$flavor][0];
		return 'light';
	}

	/**
	 *	Check valid flavor
	 */
	public function sanitize_flavor( $flavor ) {
		if ( in_array($flavor,array('recaptcha','grecaptcha')) )
			return $flavor;
		return 'grecaptcha';
	}

	/**
	 *	Admin menu hook, adds blogs local options page
	 */
	public function add_options_page() {
		$page_slug = add_options_page(
			__('ReCaptcha','wp-recaptcha-integration'), __('ReCaptcha','wp-recaptcha-integration'),
			'manage_options', 'recaptcha',
			array(&$this,'render_options_page')
		);

		add_action( "load-$page_slug" , array( &$this , 'enqueue_styles' ) );
	}

	/**
	 *	Enqueue script and css for options page.
	 */
	public function enqueue_styles() {
		wp_enqueue_style( 'recaptcha-options' , plugins_url( "css/recaptcha-options.css" , dirname(__FILE__)) );
		wp_enqueue_script( 'recaptcha-options' , plugins_url( "js/recaptcha-options.js" , dirname(__FILE__)) , array( 'jquery' ) );
		remove_action('admin_notices',array( &$this , 'api_key_notice'));
	}
	/**
	 *	Rendering the options page
	 */
	public function render_options_page() {
		$option_flavor = WP_reCaptcha::instance()->get_option( 'recaptcha_flavor' );
		?><div class="wrap flavor-<?php echo $option_flavor ?>"><?php
			?><h2><?php /*icon*/
				_e('Settings');
				echo ' › ';
				_e( 'ReCaptcha' , 'wp-recaptcha-integration' );
			?></h2><?php
		/*	?><p><?php _e( '...' , 'googlefont' ); ?></p><?php */
			?><form action="options.php" method="post"><?php
				settings_fields( 'recaptcha_options' );
				do_settings_sections( 'recaptcha' );
				submit_button( null , 'primary' , null, null, array('autocomplete'=>'off'));
			?></form><?php
		?></div><?php
	}

}


