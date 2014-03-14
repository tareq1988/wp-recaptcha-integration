<?php

add_action( 'wpcf7_init', 'wpcf7_add_shortcode_recaptcha' );

function wpcf7_add_shortcode_recaptcha() {
	wpcf7_add_shortcode(
		array( 'recaptcha','recaptcha*'),
		'wpcf7_recaptcha_shortcode_handler', true );
}

function wpcf7_recaptcha_shortcode_handler( $tag ) {
	global $recaptcha;


	$tag = new WPCF7_Shortcode( $tag );
	if ( empty( $tag->name ) )
		return '';

	$recaptcha_html = $recaptcha->recaptcha_html();
	$validation_error = wpcf7_get_validation_error( $tag->name );

	$html = sprintf(
		'<span class="wpcf7-form-control-wrap %1$s">%2$s %3$s</span>',
		$tag->name, $recaptcha_html, $validation_error );
	
	return $html;
}


add_action( 'admin_init', 'wpcf7_add_tag_generator_recaptcha', 45 );

function wpcf7_add_tag_generator_recaptcha() {
	if ( ! function_exists( 'wpcf7_add_tag_generator' ) )
		return;
	wpcf7_add_tag_generator( 'recaptcha', __( 'reCAPTCHA', 'recaptcha' ),
		'wpcf7-tg-pane-recaptcha', 'wpcf7_recaptcha_settings_callback' );
}


function wpcf7_recaptcha_settings_callback( &$contact_form ) {
	$type = 'recaptcha';
	
	?>
	<div id="wpcf7-tg-pane-<?php echo $type; ?>" class="hidden">
		<form action="">
			<table>
				<tr><td><input type="checkbox" checked="checked" name="required" onclick="return false" />&nbsp;<?php echo esc_html( __( 'Required field?', 'contact-form-7' ) ); ?></td></tr>
				<tr><td><?php echo esc_html( __( 'Name', 'contact-form-7' ) ); ?><br /><input type="text" name="name" class="tg-name oneline" /></td><td></td></tr>
			</table>
			<div class="tg-tag"><?php echo esc_html( __( "Copy this code and paste it into the form left.", 'contact-form-7' ) ); ?><br /><input type="text" name="<?php echo $type; ?>" class="tag wp-ui-text-highlight code" readonly="readonly" onfocus="this.select()" /></div>
		</form>
	</div>
	<?php
}



add_filter( 'wpcf7_validate_recaptcha', 'wpcf7_recaptcha_validation_filter', 10, 2 );
add_filter( 'wpcf7_validate_recaptcha*', 'wpcf7_recaptcha_validation_filter', 10, 2 );

function wpcf7_recaptcha_validation_filter( $result, $tag ) {
	global $recaptcha;

	$tag = new WPCF7_Shortcode( $tag );
	$name = $tag->name;

	if ( ! $recaptcha->recaptcha_check() ) {
		$result['valid'] = false;
		$result['reason'][$name] = __("The Captcha didn’t verify.",'recaptcha');
	}
	return $result;
}