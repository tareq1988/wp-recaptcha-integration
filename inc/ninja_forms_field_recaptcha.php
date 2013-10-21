<?php



function ninja_forms_register_field_recaptcha(){
	$args = array(
		'name' => __( 'reCAPTCHA', 'ninja-forms' ),
		'edit_function' => '',
		'display_function' => 'ninja_forms_field_recaptcha_display',
		'group' => 'standard_fields',
		'edit_label' => true,
		'edit_label_pos' => true,
		'edit_req' => false,
		'edit_custom_class' => true,
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
if ( function_exists('ninja_forms_register_field') )
	add_action('init', 'ninja_forms_register_field_recaptcha');

function ninja_forms_field_recaptcha_edit($field_id, $data){

}

function ninja_forms_field_recaptcha_display($field_id, $data){
	if(isset($data['default_value'])){
		$default_value = $data['default_value'];
	}else{
		$default_value = '';
	}

	if(isset($data['show_field'])){
		$show_field = $data['show_field'];
	}else{
		$show_field = true;
	}

	$field_class = ninja_forms_get_field_class($field_id);
	if(isset($data['label_pos'])){
		$label_pos = $data['label_pos'];
	}else{
		$label_pos = "left";
	}

	if(isset($data['label'])){
		$label = $data['label'];
	}else{
		$label = '';
	}

	if($label_pos == 'inside'){
		$default_value = $label;
	}

	global $recaptcha;
	$recaptcha->print_recaptcha_html();
}

function ninja_forms_field_recaptcha_pre_process( $field_id, $user_value ){
	global $ninja_forms_processing;

	$field_row = ninja_forms_get_field_by_id($field_id);
	$field_data = $field_row['data'];
	$form_row = ninja_forms_get_form_by_field_id($field_id);
	$form_id = $form_row['id'];

	$recaptcha_error = __("<strong>Error:</strong> the Captcha didn’t verify.",'recaptcha');

	global $recaptcha;
	$require_recaptcha = !( get_option('recaptcha_disable_for_known_users') && current_user_can( 'read' ) );
	
	if ( $ninja_forms_processing->get_action() != 'save' && $ninja_forms_processing->get_action() != 'mp_save' && $require_recaptcha && !$recaptcha->recaptcha_check() ){
		$ninja_forms_processing->add_error('recaptcha-general', $recaptcha_error, 'general');
		$ninja_forms_processing->add_error('recaptcha-'.$field_id, $recaptcha_error, $field_id);				
	}
}