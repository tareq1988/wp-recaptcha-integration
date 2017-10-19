<?php



/**
 *	Class to manage ContactForm 7 Support
 */
class WP_reCaptcha_ContactForm7 {
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

		$wpcf7_version = defined('WPCF7_VERSION') ? WPCF7_VERSION : '0';
		$wpcf7_recaptcha_configured = class_exists('WPCF7_RECAPTCHA') && ($cf7_sitekey = WPCF7_RECAPTCHA::get_instance()->get_sitekey()) && WPCF7_RECAPTCHA::get_instance()->get_secret( $cf7_sitekey );
		
		// Skip WPCF7 reCaptcha 
		if ( $wpcf7_recaptcha_configured || version_compare( $wpcf7_version , '4.3' , '<' ) ) {
			add_action( 'wpcf7_init', array( &$this , 'add_shortcode_recaptcha' ) );
			add_action( 'wp_enqueue_scripts' , array( &$this , 'recaptcha_enqueue_script') );
			add_action( 'admin_init', array( &$this , 'add_tag_generator_recaptcha' ), 45 );
			add_filter( 'wpcf7_validate_recaptcha', array( &$this , 'recaptcha_validation_filter' ) , 10, 2 );
			add_filter( 'wpcf7_validate_recaptcha*', array( &$this , 'recaptcha_validation_filter' ) , 10, 2 );
			add_filter( 'wpcf7_messages' , array( &$this , 'add_error_message' ) );
		}
	}
	
	
	
	function add_error_message( $messages ) {
		$messages['wp_recaptcha_invalid'] = array(
			'description'	=> __( "Google reCaptcha does not validate.", 'wp-recaptcha-integration' ),
			'default'		=> __("The Captcha didn’t verify.",'wp-recaptcha-integration')
		);
		return $messages;
	}
	
	function add_shortcode_recaptcha() {
		wpcf7_add_shortcode(
			array( 'recaptcha','recaptcha*'),
			array(&$this,'recaptcha_shortcode_handler'), true );
	}



	function recaptcha_shortcode_handler( $tag ) {
		if ( ! WP_reCaptcha::instance()->is_required() )
			return apply_filters( 'wp_recaptcha_disabled_html' ,'');
		$tag = new WPCF7_Shortcode( $tag );
		if ( empty( $tag->name ) )
			return '';

		$atts = null;
		if ( $theme = $tag->get_option('theme','',true) )
			$atts = array( 'data-theme' => $theme );

		$recaptcha_html = WP_reCaptcha::instance()->recaptcha_html( $atts );
		$validation_error = wpcf7_get_validation_error( $tag->name );

		$html = sprintf(
			apply_filters( 'wp_recaptcha_cf7_shortcode_wrap' ,'<span class="wpcf7-form-control-wrap %1$s">%2$s %3$s</span>' ),
			$tag->name, $recaptcha_html, $validation_error );
	
		return $html;
	}

	function recaptcha_enqueue_script() {
		if ( apply_filters( 'wp_recaptcha_do_scripts' , true ) ) {
			wp_enqueue_script('wpcf7-recaptcha-integration',plugins_url('/js/wpcf7.js',dirname(__FILE__)),array('contact-form-7'));
		}
	}



	function add_tag_generator_recaptcha() {
		if ( ! function_exists( 'wpcf7_add_tag_generator' ) )
			return;
		wpcf7_add_tag_generator( 'recaptcha', __( 'reCAPTCHA', 'wp-recaptcha-integration' ),
			'wpcf7-tg-pane-recaptcha', array(&$this,'recaptcha_settings_callback') );
	}



	function recaptcha_settings_callback( $contact_form , $args = '' ) {
		$args = wp_parse_args( $args, array() );
		$type = 'recaptcha';
		if ( defined( 'WPCF7_VERSION') && version_compare( WPCF7_VERSION , '4.2' ) >= 0 ) {
			?>
			<div class="control-box">
				<fieldset>
					<legend><?php _e( 'reCAPTCHA', 'wp-recaptcha-integration' ) ?></legend>

					<table class="form-table">
						<tbody>
							
							<tr>
								<th scope="row"><?php echo esc_html( __( 'Field type', 'contact-form-7' ) ); ?></th>
								<td>
									<fieldset>
										<legend class="screen-reader-text"><?php echo esc_html( __( 'Field type', 'contact-form-7' ) ); ?></legend>
										<label><input type="checkbox" checked="checked" disabled="disabled" name="required" onclick="return false" /> <?php echo esc_html( __( 'Required field', 'contact-form-7' ) ); ?></label>
									</fieldset>
								</td>
							</tr>
							
							<tr>
								<th scope="row"><?php esc_html_e( __( 'Name', 'contact-form-7' ) ); ?></th>
								<td>
									<fieldset>
										<legend class="screen-reader-text"><?php esc_html_e( __( 'Name', 'contact-form-7' ) ); ?></legend>
										<label><input type="text" name="name" class="tg-name oneline" /></label>
									</fieldset>
								</td>
							</tr><?php
							
							if ( 'grecaptcha' === WP_reCaptcha::instance()->get_option('recaptcha_flavor') ) {
								?><tr>
									<th scope="row"><?php esc_html_e( __( 'Theme', 'wp-recaptcha-integration' ) ); ?></th>
									<td>
										<fieldset>
											<legend class="screen-reader-text"><?php esc_html_e( __( 'Theme', 'wp-recaptcha-integration' ) ); ?></legend>
											<label><?php
												$this->_theme_select();
											?></label>
										</fieldset>
									</td>
								</tr><?php
							}
						?></tbody>
					</table>
				</fieldset>
			</div>
			<div class="insert-box">
				<input type="text" name="<?php echo $type; ?>" class="tag code" readonly="readonly" onfocus="this.select()" />

				<div class="submitbox">
				<input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr( __( 'Insert Tag', 'contact-form-7' ) ); ?>" />
				</div>

				<br class="clear" />

				<p class="description recaptcha-tag">
					<label for="<?php echo esc_attr( $args['content'] . '-recaptchatag' ); ?>">
						<?php /* esc_html_e( __( "Foobar", 'contact-form-7' ), '<strong><span class="recaptcha-tag"></span></strong>' );*/ ?>
						<input type="text" class="recaptcha-tag code hidden" readonly="readonly" id="<?php echo esc_attr( $args['content'] . '-recaptchatag' ); ?>" />
					</label>
				</p>
			</div>
			<?php
		} else {
			?>
			<div id="wpcf7-tg-pane-<?php echo $type; ?>" class="_hidden">
				<form action="">
					<table>
						<tr><td><input type="checkbox" checked="checked" disabled="disabled" name="required" onclick="return false" />&nbsp;<?php echo esc_html( __( 'Required field?', 'contact-form-7' ) ); ?></td></tr>
						<tr><td>
							<?php echo esc_html( __( 'Name', 'contact-form-7' ) ); ?><br />
							<input type="text" name="name" class="tg-name oneline" />
						</td><td><?php
							if ( 'grecaptcha' === WP_reCaptcha::instance()->get_option('recaptcha_flavor') ) {
							
								esc_html_e( __( 'Theme', 'wp-recaptcha-integration' ) ); ?><br /><?php
								$this->_theme_select();
								// cf7 does only allow literal <input> 
							}
						?></td></tr>
					</table>
					<div class="tg-tag">
					<?php echo esc_html( __( "Copy this code and paste it into the form left.", 'contact-form-7' ) ); ?><br />
					<input type="text" name="<?php echo $type; ?>" class="tag wp-ui-text-highlight code" readonly="readonly" onfocus="this.select()" />
					</div>
				</form>
			</div>
			<?php
		}
		?><script type="text/javascript">
	(function($){
		$(document).on('change','[name="recaptcha-theme-ui"]',function(){
			$(this).next('[name="theme"]').val( $(this).val() ).trigger('change');
		});
	})(jQuery)
		</script><?php
	}

	private function _theme_select() {
		$themes = WP_reCaptcha::instance()->captcha_instance()->get_supported_themes();
		?><select name="recaptcha-theme-ui"><?php
			?><option value=""><?php _e('Use default','wp-recaptcha-integration') ?></option><?php
		foreach ( $themes as $theme_name => $theme ) {
			?><option value="<?php echo $theme_name; ?>"><?php echo $theme['label'] ?></option><?php
		}
		?></select><?php
		?><input type="hidden" name="theme" class="idvalue option" value="" /><?php
	}

	function recaptcha_validation_filter( $result, $tag ) {
		if ( ! WP_reCaptcha::instance()->is_required() )
			return $result;

		$tag = new WPCF7_Shortcode( $tag );
		$name = $tag->name;

		if ( ! WP_reCaptcha::instance()->recaptcha_check() ) {
			$message = wpcf7_get_message( 'wp_recaptcha_invalid' );
			if ( ! $message ) 
				$message = __("The Captcha didn’t verify.",'wp-recaptcha-integration');
			 
			if ( method_exists($result, 'invalidate' ) ) { // since CF7 4.1
				$result->invalidate( $tag , $message );
			} else {
				$result['valid'] = false;
				$result['reason'][$name] = $message;
			}
		}
		return $result;
	}
	

}

