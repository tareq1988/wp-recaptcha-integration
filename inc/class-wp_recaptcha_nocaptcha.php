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

		if ( did_action( 'wp_enqueue_scripts' ) ) {
			$this->register_assets();
		} else {
			add_action( 'wp_enqueue_scripts', array( $this, 'register_assets') );
			add_action( 'admin_enqueue_scripts', array( $this, 'register_assets') );
			add_action( 'login_enqueue_scripts', array( $this, 'register_assets') );
		}
	}
	public function register_assets() {
		$is_login = current_filter() === 'login_enqueue_scripts';

		$recaptcha_api_url = "https://www.google.com/recaptcha/api.js";
		$recaptcha_api_url = add_query_arg( array(
			'onload' => 'wp_recaptcha_loaded',
			'render' => 'explicit',
		),$recaptcha_api_url);

		if ( $language_code = apply_filters( 'wp_recaptcha_language' , WP_reCaptcha::instance()->get_option( 'recaptcha_language' ) ) ) {
			$recaptcha_api_url = add_query_arg( 'hl', $language_code, $recaptcha_api_url );
		}
		$suffix = WP_DEBUG ? '' : '.min';
		wp_register_script( 'wp-recaptcha', plugins_url( "js/wp-recaptcha{$suffix}.js" , dirname(__FILE__)) , array( 'jquery' ), false, ! $is_login );
		wp_localize_script( 'wp-recaptcha', 'wp_recaptcha', array(
			'recaptcha_url'	=> $recaptcha_api_url,
			'site_key'		=> WP_reCaptcha::instance()->get_option( 'recaptcha_publickey' ),
		) );

		if ( $is_login ) {
			wp_enqueue_script( 'wp-recaptcha' );
			?>
			<style type="text/css">
			.wp-recaptcha[data-size="normal"] {
				width:304px;
				margin-left:-15px;
				margin-bottom:15px;
			}
			.wp-recaptcha[data-size="compact"] {
				width:164px;
				margin-bottom:15px;
			}
			</style>
			<?php
		}
	}

	public function get_supported_themes() {
		return array(
			'light' => __('Light','wp-recaptcha-integration'),
			'dark' => __('Dark','wp-recaptcha-integration'),
		);
	}

	public function get_supported_sizes() {
		return array(
			'normal'	=> __('Normal','wp-recaptcha-integration'),
			'compact'	=> __('Compact','wp-recaptcha-integration'),
//			'invisible'	=> __('Invisible','wp-recaptcha-integration'),
		);
	}

	/**
	 *	Override method
	 *	Get recaptcha language code that matches input language code
	 *	Sometimes WP uses different locales the the ones supported by nocaptcha.
	 *
	 *	@param	$lang	string language code
	 *	@return	string	recaptcha language code if supported, empty string otherwise
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
	public function get_html( $attr = array() ) {
		$inst	= WP_reCaptcha::instance();
		$theme	= $inst->get_option('recaptcha_theme');
		$size	= $inst->get_option('recaptcha_size');

		$default = array(
			'id'			=> 'g-recaptcha-'.$this->_counter++,
			'class'			=> "wp-recaptcha",
			'data-theme' 	=> $theme,
			'data-size' 	=> $size,
			'data-callback'	=> $inst->get_option( 'recaptcha_solved_callback'),
		);
		$attr = wp_parse_args( $attr , $default );
		if ( WP_reCaptcha::instance()->get_option('recaptcha_noscript') ) {
			$attr['class'] .= ' g-recaptcha';
		}

		$attr_str = '';
		foreach ( $attr as $attr_name => $attr_val ) {
			$attr_str .= sprintf( ' %s="%s"' , $attr_name , esc_attr( $attr_val ) );

		}

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

		wp_enqueue_script( 'wp-recaptcha' );

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
//				$remote_ip = $_SERVER['REMOTE_ADDR'];
				$url = "https://www.google.com/recaptcha/api/siteverify?secret=$private_key&response=$user_response";
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
