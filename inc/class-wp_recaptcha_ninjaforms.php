<?php

/**
 *	Class to manage NinjaForms Support
 */
class WP_reCaptcha_NinjaForms {
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
		add_action( 'init', array(&$this,'register_field_recaptcha'));
		add_action( 'init' , array( &$this , 'late_init' ) , 99 );
		add_action( 'wp_footer' , array(&$this,'recaptcha_script'),9999 );
		add_filter( 'ninja_forms_field' , array(&$this,'recaptcha_field_data'), 10, 2 );
		add_filter( 'ninja_forms_settings' , array( &$this , 'nf_settings' ) );
	}
	function nf_settings( $settings ) {
		if ( ! isset($settings['wp_recaptcha_invalid']) )
			$settings['wp_recaptcha_invalid'] = __("The Captcha didn’t verify.",'wp-recaptcha-integration');
		return $settings;
	}
	function late_init() {
		global $ninja_forms_tabs_metaboxes;
		
		$ninja_forms_tabs_metaboxes['ninja-forms-settings']['label_settings']['label_labels']['settings'][] = array(
			'name' => 'wp_recaptcha_invalid',
			'type' => 'text',
			'label' => __( "Google reCaptcha does not validate.", 'wp-recaptcha-integration' ),
		);
	}
	
	function register_field_recaptcha(){
		$args = array(
			'name' => __( 'reCAPTCHA', 'wp-recaptcha-integration' ),
			'edit_function' => '',
			'display_function' => array( &$this , 'field_recaptcha_display' ),
			'group' => 'standard_fields',
			'edit_label' => true,
			'edit_label_pos' => true,
			'edit_req' => false,
			'edit_custom_class' => false,
			'edit_help' => true,
			'edit_meta' => false,
			'sidebar' => 'template_fields',
			'display_label' => true,
			'edit_conditional' => false,
			'conditional' => array(
				'value' => array(
					'type' => 'text',
				),
			),
			'pre_process' => array( &$this , 'field_recaptcha_pre_process' ),
			'process_field' => false,
			'limit' => 1,
			'edit_options' => array(
			),
			'req' => false,
		);
		if ( 'grecaptcha' === WP_reCaptcha::instance()->get_option('recaptcha_flavor') ) {
			$themes = WP_reCaptcha::instance()->captcha_instance()->get_supported_themes();
			$edit_options = array(
				array( 'name' => __( 'Use default' , 'wp-recaptcha-integration' ) , 'value' => '' ),
			);
			foreach ( $themes as $theme_name => $theme )
				$edit_options[] = array( 'name' => $theme['label'] , 'value' => $theme_name );
			$args['edit_options'] = array(
				array(
					'type'    => 'select',
					'name'    => 'theme',
					'label'   => __( 'Theme', 'wp-recaptcha-integration' ),
					'width'   => 'wide',
					'class'   => 'widefat',
					'options' => $edit_options,
				),
			);
		}
		
		ninja_forms_register_field('_recaptcha', $args);
	}
	
	function recaptcha_field_data( $data, $field_id ) {
		$field_row = ninja_forms_get_field_by_id($field_id);
		if ( $field_row['type'] == '_recaptcha' ) 
			$data['show_field'] = WP_reCaptcha::instance()->is_required();
		return $data;
	}

	function recaptcha_script($id) {
		if ( apply_filters( 'wp_recaptcha_do_scripts' , true ) ) {
			/*
			refresh captcha after form submission.
			*/
			$flavor = WP_reCaptcha::instance()->get_option( 'recaptcha_flavor' );
			switch ( $flavor ) {
				case 'recaptcha':
					$html = '<script type="text/javascript"> 
			// reload recaptcha after failed ajax form submit
			jQuery(document).on("submitResponse.default", function(e, response){
				Recaptcha.reload();
			});
		</script>';
					break;
				case 'grecaptcha':
					$html = '<script type="text/javascript"> 
			// reload recaptcha after failed ajax form submit
			(function($){
			$(document).on("submitResponse.default", function(e, response){
				if ( grecaptcha ) {
					var wid = $(\'#ninja_forms_form_\'+response.form_id).find(\'.g-recaptcha\').data(\'widget-id\');
					grecaptcha.reset(wid);
				}
			});
			})(jQuery);
		</script>';
					break;
			}
			WP_reCaptcha::instance()->begin_inject(false,', Ninja form integration');
			echo $html;
			WP_reCaptcha::instance()->end_inject();
		}
	}

	function field_recaptcha_display($field_id, $data){
		if ( WP_reCaptcha::instance()->is_required() ) {
			$attr = !empty($data['theme']) ? array( 'data-theme' => $data['theme'] ) : null;
			WP_reCaptcha::instance()->print_recaptcha_html( $attr );
		} else {
			echo apply_filters( 'wp_recaptcha_disabled_html' ,'');
		}
	}

	function field_recaptcha_pre_process( $field_id, $user_value ){
		global $ninja_forms_processing;
		$plugin_settings = nf_get_settings();
		
		$recaptcha_error = __("The Captcha didn’t verify.",'wp-recaptcha-integration');
		if ( isset( $plugin_settings['wp_recaptcha_invalid'] ) ) 
			$recaptcha_error = $plugin_settings['wp_recaptcha_invalid'];

		$field_row = ninja_forms_get_field_by_id($field_id);
		$field_data = $field_row['data'];
		$form_row = ninja_forms_get_form_by_field_id($field_id);
		$form_id = $form_row['id'];

		$require_recaptcha = WP_reCaptcha::instance()->is_required();
	
		if ( $ninja_forms_processing->get_action() != 'save' && $ninja_forms_processing->get_action() != 'mp_save' && $require_recaptcha && ! WP_reCaptcha::instance()->recaptcha_check() ){
			$ninja_forms_processing->add_error('recaptcha-general', $recaptcha_error, 'general');
			$ninja_forms_processing->add_error('recaptcha-'.$field_id, $recaptcha_error, $field_id);				
		}
	}
}
