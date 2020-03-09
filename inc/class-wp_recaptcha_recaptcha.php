<?php



/**
 *	Class to manage the recaptcha options.
 */
class WP_reCaptcha_ReCaptcha extends WP_reCaptcha_Captcha {

	protected $supported_languages = array(
		'en' =>	'English',
		'nl' =>	'Dutch',
		'fr' =>	'French',
		'de' =>	'German',
		'pt' =>	'Portuguese',
		'ru' =>	'Russian',
		'es' =>	'Spanish',
		'tr' =>	'Turkish',
	);
	

	/**
	 *	Holding the singleton instance
	 */
	private static $_instance = null;

	/**
	 *	Last error message.
	 */
	private $last_error = null;

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
		if ( ! function_exists( 'recaptcha_get_html' ) )
			require_once dirname(__FILE__).'/recaptchalib.php';
	}
	
	/**
	 * @inheritdoc
	 */
	public function get_supported_themes() {
		return array(
			'red' => array(
				'label' => __('Red','wp-recaptcha-integration') ,
			),
			'white' => array(
				'label' => __('White','wp-recaptcha-integration') ,
			),
			'blackglass' => array(
				'label' => __('Black Glass','wp-recaptcha-integration') ,
			),
			'clean' => array(
				'label' => __('Clean','wp-recaptcha-integration') ,
			),
			'custom' => array(
				'label' => __('Custom','wp-recaptcha-integration') ,
			),
		);
	}
	/**
	 * @inheritdoc
	 */
	public function print_login_head() {
		?><style type="text/css">
		#login {
			width:360px !important;
		}
		</style><?php
	}
	/**
	 * @inheritdoc
	 */
	public function print_head() {
		$recaptcha_theme = WP_reCaptcha::instance()->get_option('recaptcha_theme');
		if ( $recaptcha_theme == 'custom' ) {
			?><script type="text/javascript">
			var RecaptchaOptions = {
				theme : '<?php echo $recaptcha_theme ?>',
				custom_theme_widget: 'recaptcha_widget'
			};
			</script><?php
		} else {
			?><script type="text/javascript">
			var RecaptchaOptions = {
<?php
			$language_code = apply_filters( 'wp_recaptcha_language' , WP_reCaptcha::instance()->get_option( 'recaptcha_language' ) );
			if ( $language_code ) { ?>
				lang : '<?php echo $language_code ?>',
<?php		} ?>
				theme : '<?php echo $recaptcha_theme ?>'
				
			};
			</script><?php
			?><style type="text/css">
			.recaptchatable {
				table-layout:auto;
			}
			</style><?php
		}
	}
	/**
	 * @inheritdoc
	 */
	public function print_foot() {
		if ( WP_reCaptcha::instance()->get_option( 'recaptcha_disable_submit' ) ) { 

			?><script type="text/javascript">
			document.addEventListener('keyup',function(e){
				if (e.target && typeof e.target.getAttribute=='function' && e.target.getAttribute('ID')=='recaptcha_response_field') {
					get_form_submits(e.target).setEnabled(!!e.target.value);
				}
			});
			document.addEventListener('DOMContentLoaded',function(e){
				try {
					get_form_submits(document.getElementById('wp-recaptcha-integration-marker')).setEnabled(false);
				} catch(e){};
			});
			</script><?php
		}
	}
	/**
	 * @inheritdoc
	 */
	public function get_html( $attr = array() ) {
		$public_key = WP_reCaptcha::instance()->get_option( 'recaptcha_publickey' );
		$recaptcha_theme = WP_reCaptcha::instance()->get_option('recaptcha_theme');

		if ($recaptcha_theme == 'custom') 
			$return = $this->get_custom_html( $public_key );
		else
			$return = recaptcha_get_html( $public_key, $this->last_error );
		if ( WP_reCaptcha::instance()->get_option( 'recaptcha_disable_submit' ) ) {
			$return .= '<span id="wp-recaptcha-integration-marker"></span>';
		}
		return $return;
	}
	/**
	 * @inheritdoc
	 */
	public function check() {
		if ( ! $this->_last_result ) {
			$private_key = WP_reCaptcha::instance()->get_option( 'recaptcha_privatekey' );
			$this->_last_result = recaptcha_check_answer( $private_key,
				$_SERVER["REMOTE_ADDR"],
				$_POST["recaptcha_challenge_field"],
				$_POST["recaptcha_response_field"]);

			if ( ! $this->_last_result->is_valid )
				$this->last_error = $this->_last_result->error;
		}
		do_action( 'wp_recaptcha_checked' , $this->_last_result->is_valid );
		return $this->_last_result->is_valid;
	}
	

	/**
	 *	Get un-themed old style recaptcha HTML.
	 *	@return string recaptcha html
	 */
	private function get_custom_html( $public_key ) {
		
		$return = '<div id="recaptcha_widget" style="display:none">';

			$return .= '<div id="recaptcha_image"></div>';
			$return .= sprintf('<div class="recaptcha_only_if_incorrect_sol" style="color:red">%s</div>',__('Incorrect please try again','wp-recaptcha-integration'));

			$return .= sprintf('<span class="recaptcha_only_if_image">%s</span>',__('Enter the words above:','wp-recaptcha-integration'));
			$return .= sprintf('<span class="recaptcha_only_if_audio">%s</span>',__('Enter the numbers you hear:','wp-recaptcha-integration'));

			$return .= '<input type="text" id="recaptcha_response_field" name="recaptcha_response_field" />';

			$return .= sprintf('<div><a href="javascript:Recaptcha.reload()"></a></div>',__('Get another CAPTCHA','wp-recaptcha-integration'));
			$return .= sprintf('<div class="recaptcha_only_if_image"><a href="javascript:Recaptcha.switch_type(\'audio\')">%s</a></div>',__('Get an audio CAPTCHA','wp-recaptcha-integration'));
			$return .= sprintf('<div class="recaptcha_only_if_audio"><a href="javascript:Recaptcha.switch_type(\'image\')">%s</a></div>',__('Get an image CAPTCHA','wp-recaptcha-integration'));

			$return .= '<div><a href="javascript:Recaptcha.showhelp()">Help</a></div>';
		$return .= '</div>';

		$return .= sprintf('<script type="text/javascript" src="http://www.google.com/recaptcha/api/challenge?k=%s"></script>',$public_key);
		$return .= '<noscript>';
			$return .= sprintf('<iframe src="http://www.google.com/recaptcha/api/noscript?k=%s" height="300" width="500" frameborder="0"></iframe><br>',$public_key);
			$return .= '<textarea name="recaptcha_challenge_field" rows="3" cols="40">';
			$return .= '</textarea>';
			$return .= '<input type="hidden" name="recaptcha_response_field" value="manual_challenge">';
		$return .= '</noscript>';
		
		return $return;
 	}
	


}



