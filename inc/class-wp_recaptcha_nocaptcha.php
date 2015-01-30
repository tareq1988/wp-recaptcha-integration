<?php



/**
 *	Class to manage the recaptcha options.
 */
class WP_reCaptcha_NoCaptcha extends WP_reCaptcha_Captcha {
	
	protected $supported_languages = array(
		'ar' =>	'Arabic',
		'bg' =>	'Bulgarian',
		'ca' =>	'Catalan',
		'zh-CN' =>	'Chinese (Simplified)',
		'zh-TW' =>	'Chinese (Traditional)',
		'hr' =>	'Croatian',
		'cs' =>	'Czech',
		'da' =>	'Danish',
		'nl' =>	'Dutch',
		'en-GB' =>	'English (UK)',
		'en' =>	'English (US)',
		'fil' =>	'Filipino',
		'fi' =>	'Finnish',
		'fr' =>	'French',
		'fr-CA' =>	'French (Canadian)',
		'de' =>	'German',
		'de-AT' =>	'German (Austria)',
		'de-CH' =>	'German (Switzerland)',
		'el' =>	'Greek',
		'iw' =>	'Hebrew',
		'hi' =>	'Hindi',
		'hu' =>	'Hungarain',
		'id' =>	'Indonesian',
		'it' =>	'Italian',
		'ja' =>	'Japanese',
		'ko' =>	'Korean',
		'lv' =>	'Latvian',
		'lt' =>	'Lithuanian',
		'no' =>	'Norwegian',
		'fa' =>	'Persian',
		'pl' =>	'Polish',
		'pt' =>	'Portuguese',
		'pt-BR' =>	'Portuguese (Brazil)',
		'pt-PT' =>	'Portuguese (Portugal)',
		'ro' =>	'Romanian',
		'ru' =>	'Russian',
		'sr' =>	'Serbian',
		'sk' =>	'Slovak',
		'sl' =>	'Slovenian',
		'es' =>	'Spanish',
		'es-419' =>	'Spanish (Latin America)',
		'sv' =>	'Swedish',
		'th' =>	'Thai',
		'tr' =>	'Turkish',
		'uk' =>	'Ukrainian',
		'vi' =>	'Vietnamese',
	);

	private $_counter = 0;
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

	}

	public function print_head() {
		?><style type="text/css">
		#login {
			width:350px !important;
		}
		</style><?php
	}


	public function print_foot() {
		$language_param = '';
		if ( $language_code = apply_filters( 'wp_recaptcha_language' , WP_reCaptcha::instance()->get_option( 'recaptcha_language' ) ) )
			$language_param = "&hl=$language_code";

		?><script type="text/javascript">
		var recaptcha_widgets={};
		function recaptchaLoadCallback(){ 
			try {
				grecaptcha;
			} catch(err){
				return;
			}
			var e=document.getElementsByClassName('g-recaptcha'),form_submits;

			for (var i=0;i<e.length;i++) {
				(function(el){
<?php if ( WP_reCaptcha::instance()->get_option( 'recaptcha_disable_submit' ) ) { ?>
					var form_submits = get_form_submits(el).setEnabled(false),wid;
<?php } ?>
					// check if captcha element is unrendered
					if ( ! el.childNodes.length) {
						wid = grecaptcha.render(el,{
							'sitekey':'<?php echo WP_reCaptcha::instance()->get_option('recaptcha_publickey'); ?>',
							'theme':'<?php echo WP_reCaptcha::instance()->get_option('recaptcha_theme'); ?>'
<?php if ( WP_reCaptcha::instance()->get_option( 'recaptcha_disable_submit' ) ) { ?>
							,
							'callback' : function(r){ get_form_submits(el).setEnabled(true); /* enable submit buttons */ }
<?php } ?>
						});
						el.setAttribute('data-widget-id',wid);
					} else {
						wid = el.getAttribute('data-widget-id');
						grecaptcha.reset(wid);
					}
				})(e[i]);
			}
		}
		
		// if jquery present re-render jquery/ajax loaded captcha elements 
		if ( !!jQuery )
			jQuery(document).ajaxComplete( recaptchaLoadCallback );
		
		</script><?php
		?><script src="https://www.google.com/recaptcha/api.js?onload=recaptchaLoadCallback&render=explicit<?php echo $language_param ?>" async defer></script><?php
	}
	
	
	
	public function get_html() {
		$public_key = WP_reCaptcha::instance()->get_option( 'recaptcha_publickey' );
		$theme = WP_reCaptcha::instance()->get_option('recaptcha_theme');
		$return = sprintf( '<div id="g-recaptcha-%d" class="g-recaptcha" data-sitekey="%s" data-theme="%s"></div>' , $this->_counter++ , $public_key , $theme );
		$return .= '<noscript>'.__('Please enable JavaScript to submit this form.','wp-recaptcha-integration').'</noscript>';
		return $return;
	}
	public function check() {
		$private_key = WP_reCaptcha::instance()->get_option( 'recaptcha_privatekey' );
		$user_response = isset( $_REQUEST['g-recaptcha-response'] ) ? $_REQUEST['g-recaptcha-response'] : false;
		if ( $user_response ) {
			if ( ! $this->_last_result->success ) {
				$remote_ip = $_SERVER['REMOTE_ADDR'];
				$url = "https://www.google.com/recaptcha/api/siteverify?secret=$private_key&response=$user_response&remoteip=$remote_ip";
				$response = wp_remote_get( $url );
				if ( ! is_wp_error($response) ) {
					$response_data = wp_remote_retrieve_body( $response );
					$this->_last_result = json_decode($response_data);
				} else {
					$this->_last_result = (object) array( 'success' => false );
				}
			}
			do_action( 'wp_recaptcha_checked' , $this->_last_result->success );
			return $this->_last_result->success;
		}
		return false;
	}

	
}


WP_reCaptcha_Options::instance();

