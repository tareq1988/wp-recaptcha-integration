<?php

function wpcf7_add_shortcode_recaptcha() {
	wpcf7_add_shortcode(
		array( 'recaptcha','recaptcha*'),
		'wpcf7_recaptcha_shortcode_handler', true );
}
add_action( 'wpcf7_init', 'wpcf7_add_shortcode_recaptcha' );


function wpcf7_recaptcha_shortcode_handler( $tag ) {
	if ( ! WordPress_reCaptcha::instance()->is_required() )
		return apply_filters( 'recaptcha_disabled_html' ,'');
	$tag = new WPCF7_Shortcode( $tag );
	if ( empty( $tag->name ) )
		return '';

	$recaptcha_html = WordPress_reCaptcha::instance()->recaptcha_html();
	$validation_error = wpcf7_get_validation_error( $tag->name );

	$html = sprintf(
		'<span class="wpcf7-form-control-wrap %1$s">%2$s %3$s</span>',
		$tag->name, $recaptcha_html, $validation_error );
	
	return $html;
}

function wpcf7_recaptcha_enqueue_script() {
	wp_enqueue_script('wpcf7-recaptcha-integration',plugins_url('/js/wpcf7.js',dirname(__FILE__)),array('contact-form-7'));
}
add_action('wp_enqueue_scripts','wpcf7_recaptcha_enqueue_script');

function wpcf7_add_tag_generator_recaptcha() {
	var_dump( function_exists( 'wpcf7_add_tag_generator' ) );
	if ( ! function_exists( 'wpcf7_add_tag_generator' ) )
		return;
	wpcf7_add_tag_generator( 'recaptcha', __( 'reCAPTCHA', 'recaptcha' ),
		'wpcf7-tg-pane-recaptcha', 'wpcf7_recaptcha_settings_callback' );
}
add_action( 'admin_init', 'wpcf7_add_tag_generator_recaptcha', 45 );


function wpcf7_recaptcha_settings_callback( $contact_form ) {
	$type = 'recaptcha';
	
	?>
	<div id="wpcf7-tg-pane-<?php echo $type; ?>" class="hidden">
		<form action="">
			<table>
				<tr><td><input type="checkbox" checked="checked" disabled="disabled" name="required" onclick="return false" />&nbsp;<?php echo esc_html( __( 'Required field?', 'contact-form-7' ) ); ?></td></tr>
				<tr><td><?php echo esc_html( __( 'Name', 'contact-form-7' ) ); ?><br /><input type="text" name="name" class="tg-name oneline" /></td><td></td></tr>
			</table>
			<div class="tg-tag">
			<?php echo esc_html( __( "Copy this code and paste it into the form left.", 'contact-form-7' ) ); ?><br />
			<input type="text" name="<?php echo $type; ?>" class="tag wp-ui-text-highlight code" readonly="readonly" onfocus="this.select()" />
			</div>
		</form>
	</div>
	<?php
}




function wpcf7_recaptcha_validation_filter( $result, $tag ) {
	if ( ! WordPress_reCaptcha::instance()->is_required() )
		return $result;
	
	$tag = new WPCF7_Shortcode( $tag );
	$name = $tag->name;

	if ( ! WordPress_reCaptcha::instance()->recaptcha_check() ) {
		$result['valid'] = false;
		$result['reason'][$name] = __("The Captcha didn’t verify.",'recaptcha');
	}
	return $result;
}
add_filter( 'wpcf7_validate_recaptcha', 'wpcf7_recaptcha_validation_filter', 10, 2 );
add_filter( 'wpcf7_validate_recaptcha*', 'wpcf7_recaptcha_validation_filter', 10, 2 );
