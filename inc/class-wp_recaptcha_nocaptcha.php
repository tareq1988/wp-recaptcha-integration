<?php



/**
 *	Class to manage the recaptcha options.
 */
class WP_reCaptcha_NoCaptcha extends WP_reCaptcha_Captcha {

	protected $supported_languages = array(
		'ar' => 'Arabic',
		'af' => 'Afrikaans',
		'am' => 'Amharic',
		'hy' => 'Armenian',
		'az' => 'Azerbaijani',
		'eu' => 'Basque',
		'bn' => 'Bengali',
		'bg' => 'Bulgarian',
		'ca' => 'Catalan',
		'zh-HK' => 'Chinese (Hong Kong)',
		'zh-CN' => 'Chinese (Simplified)',
		'zh-TW' => 'Chinese (Traditional)',
		'hr' => 'Croatian',
		'cs' => 'Czech',
		'da' => 'Danish',
		'nl' => 'Dutch',
		'en-GB' => 'English (UK)',
		'en' => 'English (US)',
		'et' => 'Estonian',
		'fil' => 'Filipino',
		'fi' => 'Finnish',
		'fr' => 'French',
		'fr-CA' => 'French (Canadian)',
		'gl' => 'Galician',
		'ka' => 'Georgian',
		'de' => 'German',
		'de-AT' => 'German (Austria)',
		'de-CH' => 'German (Switzerland)',
		'el' => 'Greek',
		'gu' => 'Gujarati',
		'iw' => 'Hebrew',
		'hi' => 'Hindi',
		'hu' => 'Hungarain',
		'is' => 'Icelandic',
		'id' => 'Indonesian',
		'it' => 'Italian',
		'ja' => 'Japanese',
		'kn' => 'Kannada',
		'ko' => 'Korean',
		'lo' => 'Laothian',
		'lv' => 'Latvian',
		'lt' => 'Lithuanian',
		'ms' => 'Malay',
		'ml' => 'Malayalam',
		'mr' => 'Marathi',
		'mn' => 'Mongolian',
		'no' => 'Norwegian',
		'fa' => 'Persian',
		'Value' => 'Language',
		'pl' => 'Polish',
		'pt' => 'Portuguese',
		'pt-BR' => 'Portuguese (Brazil)',
		'pt-PT' => 'Portuguese (Portugal)',
		'ro' => 'Romanian',
		'ru' => 'Russian',
		'sr' => 'Serbian',
		'si' => 'Sinhalese',
		'sk' => 'Slovak',
		'sl' => 'Slovenian',
		'es' => 'Spanish',
		'es-419' => 'Spanish (Latin America)',
		'sw' => 'Swahili',
		'sv' => 'Swedish',
		'ta' => 'Tamil',
		'te' => 'Telugu',
		'th' => 'Thai',
		'tr' => 'Turkish',
		'uk' => 'Ukrainian',
		'ur' => 'Urdu',
		'vi' => 'Vietnamese',
		'zu' => 'Zulu',
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

	public function get_supported_themes() {
		return array(
			'light' => array(
				'label' => __('Light','wp-recaptcha-integration') ,
			),
			'dark' => array(
				'label' => __('Dark','wp-recaptcha-integration') ,
			),
		);
	}
	/**
	 *	Override method
	 *	Get recaptcha language code that matches input language code
	 *	Sometimes WP uses different locales the the ones supported by nocaptcha.
	 *
	 *	@param	$lang	string language code
	 *	@return	string	recaptcha language code if supported by current flavor, empty string otherwise
	 */
	public function get_language( $lang ) {
		/*
		 	Map WP locale to recatcha locale.
		*/
		$mapping = array(
			'es_MX' => 'es-419',
			'es_PE' => 'es-419',
			'es_CL' => 'es-419',
			'he_IL' => 'iw',
		);
		if ( isset( $mapping[$lang] ) )
			$lang = $mapping[$lang];
		return parent::get_language( $lang );
	}
	/**
	 * @inheritdoc
	 */
	public function print_head() {}

	/**
	 * @inheritdoc
	 */
	public function print_login_head() {
		?><style type="text/css">
		#login {
			width:350px !important;
		}
		</style><?php
	}


	/**
	 * @inheritdoc
	 */
	public function print_foot() {
		$sitekey = WP_reCaptcha::instance()->get_option('recaptcha_publickey');
		$language_param = '';


		?><script type="text/javascript">
		var recaptcha_widgets={};
		function wp_recaptchaLoadCallback(){
			try {
				grecaptcha;
			} catch(err){
				return;
			}
			var e = document.querySelectorAll ? document.querySelectorAll('.g-recaptcha:not(.wpcf7-form-control)') : document.getElementsByClassName('g-recaptcha'),
				form_submits;

			for (var i=0;i<e.length;i++) {
				(function(el){
<?php if ( WP_reCaptcha::instance()->get_option( 'recaptcha_disable_submit' ) ) { ?>
					var form_submits = get_form_submits(el).setEnabled(false), wid;
<?php } else { ?>
					var wid;
<?php } ?>
					// check if captcha element is unrendered
					if ( ! el.childNodes.length) {
						wid = grecaptcha.render(el,{
							'sitekey':'<?php echo $sitekey ?>',
							'theme':el.getAttribute('data-theme') || '<?php echo WP_reCaptcha::instance()->get_option('recaptcha_theme'); ?>'
<?php if ( WP_reCaptcha::instance()->get_option( 'recaptcha_disable_submit' ) ) {
?>							,
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
		if ( typeof jQuery !== 'undefined' )
			jQuery(document).ajaxComplete( function(evt,xhr,set){
				if( xhr.responseText && xhr.responseText.indexOf('<?php echo $sitekey ?>') !== -1)
					wp_recaptchaLoadCallback();
			} );

		</script><?php
		$recaptcha_api_url = "https://www.google.com/recaptcha/api.js";
		$recaptcha_api_url = add_query_arg(array(
				'onload' => 'wp_recaptchaLoadCallback',
				'render' => 'explicit',
			),$recaptcha_api_url);
		if ( $language_code = apply_filters( 'wp_recaptcha_language' , WP_reCaptcha::instance()->get_option( 'recaptcha_language' ) ) )
			$recaptcha_api_url = add_query_arg('hl',$language_code,$recaptcha_api_url);

		?><script src="<?php echo esc_url( $recaptcha_api_url ) ?>" async defer></script><?php
	}



	/**
	 * @inheritdoc
	 */
	public function get_html( $attr = array() ) {
		$public_key = WP_reCaptcha::instance()->get_option( 'recaptcha_publickey' );
		$theme = WP_reCaptcha::instance()->get_option('recaptcha_theme');

		$default = array(
			'id'			=> 'g-recaptcha-'.$this->_counter++,
			'class'			=> "g-recaptcha",
			'data-sitekey'	=> $public_key,
			'data-theme' 	=> $theme,
		);
		$attr = wp_parse_args( $attr , $default );

		$attr_str = '';
		foreach ( $attr as $attr_name => $attr_val )
			$attr_str .= sprintf( ' %s="%s"' , $attr_name , esc_attr( $attr_val ) );
		$return = "<div {$attr_str}></div>";
		$return .= '<noscript>';
		if ( WP_reCaptcha::instance()->get_option('recaptcha_noscript') ) {
			$return .= '<div style="width: 302px; height: 462px;">' .
							'<div style="width: 302px; height: 422px; position: relative;">' .
								'<div style="width: 302px; height: 422px; position: absolute;">' .
									'<iframe src="https://www.google.com/recaptcha/api/fallback?k='.$attr['data-sitekey'].'"' .
											' frameborder="0" scrolling="no"' .
											' style="width: 302px; height:422px; border-style: none;">' .
									'</iframe>' .
								'</div>' .
							'</div>' .
							'<div style="width: 300px; height: 60px; border-style: none;' .
								' bottom: 12px; left: 25px; margin: 0px; padding: 0px; right: 25px;' .
								' background: #f9f9f9; border: 1px solid #c1c1c1; border-radius: 3px;">' .
								'<textarea id="g-recaptcha-response" name="g-recaptcha-response"' .
											' class="g-recaptcha-response"' .
											' style="width: 250px; height: 40px; border: 1px solid #c1c1c1;' .
													' margin: 10px 25px; padding: 0px; resize: none;" value="">' .
								'</textarea>' .
							'</div>' .
						'</div><br>';
		} else {
			$return .= __('Please enable JavaScript to submit this form.','wp-recaptcha-integration');
		}
		$return .= '<br></noscript>';
		return $return;
	}
	/**
	 * @inheritdoc
	 */
	public function check() {
		$private_key = WP_reCaptcha::instance()->get_option( 'recaptcha_privatekey' );
		$user_response = isset( $_REQUEST['g-recaptcha-response'] ) ? $_REQUEST['g-recaptcha-response'] : false;
		if ( $user_response !== false ) {
			if (  ! $this->_last_result ) {
				$remote_ip = $_SERVER['REMOTE_ADDR'];
				$url = "https://www.google.com/recaptcha/api/siteverify?secret=$private_key&response=$user_response&remoteip=$remote_ip";
				$response = wp_remote_get( $url );
				if ( ! is_wp_error($response) ) {
					$response_data = wp_remote_retrieve_body( $response );
					$this->_last_result = json_decode($response_data);
				} else {
					$this->_last_result = (object) array( 'success' => false , 'wp_error' => $response );
				}
			}
			do_action( 'wp_recaptcha_checked' , $this->_last_result->success );
			return $this->_last_result->success;
		}
		return false;
	}


}
