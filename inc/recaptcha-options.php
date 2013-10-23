<?php

/*
settings:
- pubkey
- privkey
- theme: red | white | blackglass | clean
*/



class WordPress_reCaptcha_Options {
	function __construct() {
		add_action('admin_init', array(&$this,'admin_init') );
		add_action('admin_menu', array(__CLASS__,'add_options_page') );
	}
	function admin_init( ) {
		register_setting( 'recaptcha_options', 'recaptcha_publickey' );
		register_setting( 'recaptcha_options', 'recaptcha_privatekey' );
		register_setting( 'recaptcha_options', 'recaptcha_theme' );

		register_setting( 'recaptcha_options', 'recaptcha_enable_comments' , 'intval');
		register_setting( 'recaptcha_options', 'recaptcha_enable_signup', 'intval' );
		register_setting( 'recaptcha_options', 'recaptcha_enable_login' , 'intval');
		register_setting( 'recaptcha_options', 'recaptcha_disable_for_known_users' , 'intval');

		add_settings_section('recaptcha', __( 'ReCaptcha Settings' , 'recaptcha' ), array(&$this,'explain_recaptcha'), 'recaptcha');
		add_settings_section('recaptcha_modules', __( 'ReCaptcha Modules' , 'recaptcha' ), array(&$this,'explain_recaptcha'), 'recaptcha');

		add_settings_field('recaptcha_publickey', __('Public Key','recaptcha'), array(&$this,'input_text'), 'recaptcha', 'recaptcha' , array('name'=>'recaptcha_publickey') );
		add_settings_field('recaptcha_privatekey', __('Private Key','recaptcha'), array(&$this,'input_text'), 'recaptcha', 'recaptcha', array('name'=>'recaptcha_privatekey'));
		add_settings_field('recaptcha_theme', __('Theme','recaptcha'), array(&$this,'select_theme'), 'recaptcha', 'recaptcha');


		add_settings_field('recaptcha_enable_comments', __('Protect Comments','recaptcha'), 
			array(&$this,'input_checkbox'), 'recaptcha', 'recaptcha_modules' , 
			array('name'=>'recaptcha_enable_comments','label'=>__( 'Protect comment forms with recaptcha.' ,'recaptcha' ) ) 
		);
			
		add_settings_field('recaptcha_enable_signup', __('Protect Signup','recaptcha'), 
			array(&$this,'input_checkbox'), 'recaptcha', 'recaptcha_modules',      
			array('name'=>'recaptcha_enable_signup','label'=>__( 'Protect signup form with recaptcha.','recaptcha' ) )
		);
			
		add_settings_field('recaptcha_enable_login', __('Protect Login','recaptcha'), 
			array(&$this,'input_checkbox'), 'recaptcha', 'recaptcha_modules' ,
			array('name'=>'recaptcha_enable_login','label'=>__( 'Protect Login form with recaptcha.','recaptcha' )) 
		);

		add_settings_field('recaptcha_disable_for_known_users', __('Disable for known users','recaptcha'), 
			array(&$this,'input_checkbox'), 'recaptcha', 'recaptcha_modules' ,
			array('name'=>'recaptcha_disable_for_known_users','label'=>__( 'Disable reCaptcha verification for logged in users.','recaptcha' )) 
		);

		if ( ! get_option( 'recaptcha_publickey' ) || ! get_option( 'recaptcha_privatekey' ) )
			add_settings_error('recaptcha',1,__('Please configure the public and private key. <a href="http://www.google.com/recaptcha/whyrecaptcha">What are you trying to tell me?</a>','recaptcha'),'updated');
	}

	public function explain_recaptcha( ) {
		?><p class="description"><?php 
			_e('Please enter the public and private key that you got from <a href="http://www.google.com/recaptcha">Google raCAPTCHA</a>.','recaptcha');
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
		extract($args);
		$value = get_option($name);
		?><input type="text" class="regular-text ltr" name="<?php echo $name ?>" value="<?php echo $value ?>" /><?php
	}
	
	public function select_theme() {
		$themes = array(
			'red' => __('Red','recaptcha'),
			'white' => __('White','recaptcha'),
			'blackglass' => __('Black Glass','recaptcha'),
			'clean' => __('Clean','recaptcha'),
		);
		$theme = get_option('recaptcha_theme');
		
		?><select name="recaptcha_theme"><?php
			foreach ( $themes as $value => $label ) {
				?><option <?php selected( $value , $theme , true) ?> value="<?php echo $value ?>"><?php echo $label ?></option><?php
			}
		?></select><?php
	}
	public static function add_options_page() {
		add_options_page( 
			__('ReCaptcha','recaptcha'), __('Recaptcha Settings','recaptcha'), 
			'manage_options', 'recaptcha', 
			array(__CLASS__,'render_options_page')
		);
	}
	public static function render_options_page() {
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


$recaptcha = new WordPress_reCaptcha_Options();

