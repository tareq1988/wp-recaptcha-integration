<?php

namespace RecaptchaIntegration\Compat;

use RecaptchaIntegration\Core;


abstract class Compat extends Core\PluginComponent {


	/**
	 *	@var int Filter Priority for init hook
	 */
	protected $init_priority = 10;

	/**
	 *	@var int Filter Priority for init hook
	 */
	protected $core = null;

	protected function __construct() {

		add_filter( 'wp_recaptcha_forms', array( $this, 'register_forms' ) );


		if ( 'plugins_loaded' === current_filter() ) {
			$this->plugins_loaded();
		} else {
			add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
		}

		if ( 'init' === current_filter() ) {
			$this->init();
		} else {
			add_action( 'init', array( $this, 'init' ), $this->init_priority );
		}
		parent::__construct();
	}

	/**
	 *	plugins loaded action callback
	 *
	 *	@action plugins_loaded
	 */
	public function plugins_loaded() {
	}

	/**
	 *	plugins loaded action callback
	 *
	 *	@filter wp_recaptcha_forms
	 */
	public function register_forms( $forms ) {
		return $forms;
	}

	/**
	 *	Init plugin component
	 *	set hooks
	 *
	 *	@action init
	 */
	abstract function init();


	/**
	 *	@inheritdoc
	 */
	 public function activate(){

	 }

	 /**
	  *	@inheritdoc
	  */
	 public function deactivate(){

	 }

	 /**
	  *	@inheritdoc
	  */
	 public function uninstall() {
		 // remove content and settings
	 }

	/**
 	 *	@inheritdoc
	 */
	public function upgrade( $new_version, $old_version ) {
	}

}
