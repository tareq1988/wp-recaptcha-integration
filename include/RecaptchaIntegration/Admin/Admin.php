<?php

namespace RecaptchaIntegration\Admin;

use RecaptchaIntegration\Core;

class Admin extends Core\Singleton {

	protected function __construct() {
		if ( get_transient( 'wp_recaptcha_api_key_lockout_reset' ) ) {
			add_action( 'admin_notices', array( $this, 'apikey_lockout_reset_message' ) );
		}
	}
	public function apikey_lockout_reset_message() {
		$settings = RecaptchaIntegration\Settings\SettingsPageRecaptcha::instance();
		?><div class="notice error above-h1 is-dismissible">
			<p><?php
				_e( 'Some errors occured while sending Date to Google. Your API-Key configuration seems to be broken.' , 'wp-recaptcha-integration' );
			?> <?php
			_e( 'The API-Keys have been reset,' , 'wp-recaptcha-integration' );
			?> <?php
			sprintf( __( 'Go to the <a href="%s">settings page</a> to update the configuration.' , 'wp-recaptcha-integration' ), $settings->get_url() );
			_e( 'The Error messages were:' , 'wp-recaptcha-integration' );
			?> <?php
			// validate!
			echo implode( '<br />', get_transient( 'wp_recaptcha_api_key_lockout_reset' ) );
			?></p>
		</div><?php

	}
}
