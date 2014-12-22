<?php



function ninja_forms_register_field_recaptcha(){
	$args = array(
		'name' => __( 'reCAPTCHA', 'wp-recaptcha-integration' ),
		'edit_function' => '',
		'display_function' => 'ninja_forms_field_recaptcha_display',
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
		'pre_process' => 'ninja_forms_field_recaptcha_pre_process',
		'process_field' => false,
		'limit' => 1,
		'edit_options' => array(
		),
		'req' => true,
	);

	ninja_forms_register_field('_recaptcha', $args);
}
if ( function_exists('ninja_forms_register_field') ) {
	add_action('init', 'ninja_forms_register_field_recaptcha');
	add_action('wp_footer','ninja_forms_recaptcha_script');
	add_filter('ninja_forms_field','ninja_forms_recaptcha_field_data',10,2);
}

function ninja_forms_recaptcha_field_data( $data, $field_id ) {
	$field_row = ninja_forms_get_field_by_id($field_id);
	if ( $field_row['type'] == '_recaptcha' ) 
		$data['show_field'] = WP_reCaptcha::instance()->is_required();
	return $data;
}

function ninja_forms_recaptcha_script($id) {
	// print script
	$html = '<script type="text/javascript"> 
		jQuery(document).on("submitResponse.default", function(e, response){
			Recaptcha.reload();
		});
	</script>';
	echo $html;
}

function ninja_forms_field_recaptcha_display($field_id, $data){
	if ( WP_reCaptcha::instance()->is_required() )
		WP_reCaptcha::instance()->print_recaptcha_html();
	else 
		echo apply_filters( 'recaptcha_disabled_html' ,'');
}

function ninja_forms_field_recaptcha_pre_process( $field_id, $user_value ){
	global $ninja_forms_processing;
	$recaptcha_error = __("<strong>Error:</strong> the Captcha didn’t verify.",'wp-recaptcha-integration');

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