<?php

namespace RecaptchaIntegration\Captcha;

class ReCaptcha extends Captcha {

	/**
	 *	@var mixed Content of the last check request
	 */
	private $last_response = null;

	/**
	 *	@var mixed Content of the last check request
	 */
 	private $verify_url = 'https://www.google.com/recaptcha/api/siteverify';

	/**
	 *	@var mixed Content of the last check request
	 */
 	private $api_url = 'https://www.google.com/recaptcha/api.js';

	/**
	 *	@var int
	 */
 	private $counter = 0;


	/**
	 *	@var array
	 */
	private $supported_languages = array(
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

	/**
	 *	@inheritdoc
	 */
	protected function __construct() {

		if ( did_action( 'wp_enqueue_scripts' ) ) {
			$this->register_assets();
		} else {
			add_action( 'wp_enqueue_scripts', array( $this, 'register_assets') );
			add_action( 'admin_enqueue_scripts', array( $this, 'register_assets') );
			add_action( 'login_enqueue_scripts', array( $this, 'register_assets') );
		}

		parent::__construct();
	}

	/**
	 *	@return	array Options for a captcha instance
	 */
	public function get_style_options() {
		return array(
			'size'				=> array(
				'input'		=> 'radio',
				'type'		=> 'string',
				'sanitize_callback'	=> array( $this, 'sanitize_size' ),
				'label'		=> __( 'Size', 'wp-recaptcha-integration' ),
				'choices'	=> array(
					'normal'	=> __('Normal','wp-recaptcha-integration'),
					'compact'	=> __('Compact','wp-recaptcha-integration'),
					'invisible'	=> __('Invisible','wp-recaptcha-integration'),
				),
			),
			'theme'				=> array(
				'input'		=> 'radio',
				'type'		=> 'string',
				'sanitize_callback'	=> array( $this, 'sanitize_theme' ),
				'label'		=> __( 'Theme', 'wp-recaptcha-integration' ),
				'choices'	=> array(
					'light'	=> __('Light','wp-recaptcha-integration'),
					'dark'	=> __('Dark','wp-recaptcha-integration'),
				),
			),
			'type'				=> array(
				'input'		=> 'radio',
				'type'		=> 'string',
				'sanitize_callback'	=> array( $this, 'sanitize_type' ),
				'label'		=> __( 'Type', 'wp-recaptcha-integration' ),
				'choices'	=> array(
					'image'	=> __('Image','wp-recaptcha-integration'),
					'audio'	=> __('Audio','wp-recaptcha-integration'),
				),
			),
			'solved_callback'	=> array(
				'input'				=> 'select',
				'type'				=> 'string',
				'sanitize_callback'	=> array( $this, 'sanitize_solved_callback' ),
				'label'				=> __('When the captcha has been solved','wp-recaptcha-integration' ),
				'choices'			=> array(
					''			=> __( 'Do nothing', 'wp-recaptcha-integration' ),
					'enable'	=> __( 'Enable Submit Button', 'wp-recaptcha-integration' ),
					'submit'	=> __( 'Submit Form', 'wp-recaptcha-integration' ),
				),
			),
		);
 	}

	/**
	 *	@return	array Settings to be printed on the settings page.
	 */
	public function get_site_options() {
		return array(
			'language'				=> array(
				'input'				=> 'select',
				'type'				=> 'string',
				'sanitize_callback'	=> array( $this, 'sanitize_language' ),
				'label'		=> __('Language','wp-recaptcha-integration'),
				'choices'	=> array(
					''			=> __( 'Automatic', 'wp-recaptcha-integration' ),
					'WPLANG'	=> __( 'Site Language', 'wp-recaptcha-integration' ),
					array(
						'label'		=> __('Other','wp-recaptcha-integration' ),
						'choices'	=> $this->supported_languages,
					)
				),
			),
			'noscript'			=> array(
				'input'				=> 'checkbox',
				'type'				=> 'boolean',
				'label'				=> __('Noscript Fallback','wp-recaptcha-integration'),
				'sanitize_callback'	=> 'boolval',
				'description' 		=> __( 'Leave this unchecked when your site requires JavaScript anyway.','wp-recaptcha-integration' ),
			),
			'send_ip'			=> array(
				'input'				=> 'checkbox',
				'type'				=> 'boolean',
				'label'				=> __('Send Client IP','wp-recaptcha-integration'),
				'sanitize_callback'	=> 'boolval',
				'description' 		=> __( 'If checked the Users IP Address will be sent to Google when the captcha is verified.','wp-recaptcha-integration' ),
			),
		);
	}
	public function sanitize_language( $value ) {
		if ( $value === '' || $value === 'WPLANG' || in_array( $value, array_keys( $this->supported_languages ) ) ) {
			return $value;
		}
		return '';
	}
	public function sanitize_size( $value ) {
		if ( in_array( $value, array( 'normal', 'compact', 'invisible' ) ) ) {
			return $value;
		}
		return 'normal';
	}
	public function sanitize_theme( $value ) {
		if ( in_array( $value, array( 'light', 'dark' ) ) ) {
			return $value;
		}
		return 'light';
	}
	public function sanitize_type( $value ) {
		if ( in_array( $value, array( 'image', 'audio' ) ) ) {
			return $value;
		}
		return 'image';
	}
	public function sanitize_solved_callback( $value ) {
		if ( in_array( $value, array( '', 'enable', 'submit' ) ) ) {
			return $value;
		}
		return '';
	}
	/**
	 *	@action wp_enqueue_scripts
	 *	@action admin_enqueue_scripts
	 *	@action login_enqueue_scripts
	 */
	public function register_assets() {

		$is_login = current_filter() === 'login_enqueue_scripts';

		$api_url = add_query_arg( array(
				'onload' => 'wp_recaptcha_loaded',
				'render' => 'explicit',
			), $this->api_url );

		if ( $language_code = apply_filters( 'wp_recaptcha_language' , WPRecaptcha()->get_option( 'language' ) ) ) {
			$api_url = add_query_arg( 'hl', $language_code, $api_url );
		}

		$suffix = WP_DEBUG ? '' : '.min';

		wp_register_script( 'wp-recaptcha', WPRecaptcha()->get_asset_url( "js/wp-recaptcha{$suffix}.js" ), array( 'jquery' ), false, ! $is_login );
		wp_localize_script( 'wp-recaptcha', 'wp_recaptcha', array(
			'recaptcha_url'	=> $api_url,
			'site_key'		=> WPRecaptcha()->get_option( 'site_key' ),
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


	/**
	 * @inheritdoc
	 */
	public function get_captcha( $attr = array() ) {

		$output		= '';
		$inst		= \WPRecaptcha();
		$theme		= $inst->get_option( 'theme' );
		$size		= $inst->get_option( 'size' );
		$type		= $inst->get_option( 'type' );
		$callback	= $inst->get_option( 'solved_callback' );
		$noscript	= $inst->get_option( 'noscript' );

		$this->counter++;

		$default_attr = array(
			'id'					=> 'g-recaptcha-' . crc32($_SERVER['REQUEST_URI']) . '-' . $this->counter,
			'class'					=> 'wp-recaptcha' . ( $noscript ? ' g-recaptcha' : '' ),
			'data-theme'			=> $theme,
			'data-size'				=> $size,
			'data-sitekey'			=> null,
			'data-type'				=> $type,
			'data-tabindex'			=> null,
			'data-callback'			=> $callback,
			'data-expired-callback'	=> null,
		);

		$attr = wp_parse_args( $attr, $default_attr );
		$attr = array_intersect_key( $attr, $default_attr );

		$attr_str = '';

		foreach ( $attr as $attr_name => $attr_val ) {
			if ( ! is_null( $attr_val ) ) {
				$attr_str .= sprintf( ' %s="%s"' , $attr_name , esc_attr( $attr_val ) );
			}
		}

		$output .= "<div {$attr_str}></div>";
		$output .= '<noscript>';

		if ( $noscript ) {

			$output .= '<div style="width: 302px; height: 462px;">' .
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

			$output .= __('Please enable JavaScript to submit this form.','wp-recaptcha-integration');

		}

		$output .= '<br></noscript>';

		wp_enqueue_script( 'wp-recaptcha' );

		return $output;
	}

	/**
	 * @inheritdoc
	 */
	public function valid() {

		if ( ! $this->submitted() ) {
			return false;
		}

		if ( is_null( $this->last_response ) ) {
			$inst = \WPRecaptcha();

			$url = add_query_arg( array(
				'secret'	=> WPRecaptcha()->get_option( 'secret_key' ),
				'response'	=> $_REQUEST['g-recaptcha-response'],
			),$this->verify_url );

			if ( $inst->get_option( 'send_ip' ) ) {
				// proxy?
				foreach ( array( 'HTTP_X_FORWARDED_FOR', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_CLIENT_IP', 'HTTP_VIA', 'HTTP_X_REAL_IP', 'REMOTE_ADDR' ) as $ip_key ) {
					if ( isset( $_SERVER[ $ip_key ] ) ) {
						$url = add_query_arg( 'remoteip', $_SERVER[ $ip_key ], $url );
						break;
					}
				}
			}

			$response = wp_remote_get( $url );

			if ( ! is_wp_error( $response ) ) {
				if ( $response['response']['code'] === 200 ) {
					$response_data = wp_remote_retrieve_body( $response );
					$this->last_response = json_decode( $response_data );
				} else {
					$this->last_response = (object) array(
						'success' => false,
						'wp_error' => new WP_Error(
							'http_request_failed',
							sprintf(__('Status code %d', 'wp-recaptcha-integration' ), $response['response']['code'] )
						),
					);

				}
			} else {
				$this->last_response = (object) array(
					'success' => false,
					'wp_error' => $response,
				);
			}
			do_action( 'wp_recaptcha_checked', $this->last_response->success );
		}

		return $this->last_response->success;
	}

	/**
	 *	@return	array	error messages
	 */
	public function get_error_codes() {

		if ( ! is_null( $this->last_response ) ) {
			if ( isset( $this->last_response->{'error-codes'} ) ) {
				return $this->last_response->{'error-codes'};
			}
			if ( isset( $this->last_response->wp_error ) ) {
				return $this->last_response->wp_error->get_error_codes();
			}
		}
		return array();
	}
	/**
	 *	@return	array	error messages
	 */
	public function get_error_messages() {
		$errors = array();
		$g_recaptcha_errors = array(
			'missing-input-secret'		=> __( 'The secret parameter is missing.', 'wp-recaptcha-integration' ),
			'invalid-input-secret'		=> __( 'The secret parameter is invalid or malformed.', 'wp-recaptcha-integration' ),
			'missing-input-response'	=> __( 'The response parameter is missing.', 'wp-recaptcha-integration' ),
			'invalid-input-response'	=> __( 'The Captcha didn‘t verify', 'wp-recaptcha-integration' ),
			'bad-request'				=> __( 'The request is invalid or malformed', 'wp-recaptcha-integration' ),
		);
		if ( ! is_null( $this->last_response ) ) {
			if ( isset( $this->last_response->{'error-codes'} ) ) {
				foreach ( $this->last_response->{'error-codes'} as $code ) {
					if ( isset( $g_recaptcha_errors[$code] ) ) {
						$errors[] = $g_recaptcha_errors[$code];
					} else {
						$errors[] = __( 'Unkonwn Error', 'wp-recaptcha-integration' );
					}
				}
			}
			if ( isset( $this->last_response->wp_error ) ) {
				$errors = array_merge( $errors, $this->last_response->wp_error->get_error_messages() );
			}
		}
		return $errors;
	}

	/**
	 * @inheritdoc
	 */
	public function submitted() {
		return isset( $_REQUEST['g-recaptcha-response'] );
	}

}
