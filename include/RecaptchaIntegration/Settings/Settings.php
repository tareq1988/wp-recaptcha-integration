<?php

namespace RecaptchaIntegration\Settings;
use RecaptchaIntegration\Core;

abstract class Settings extends Core\Singleton {

	protected $option_prefix = '';

	/**
	 *	Constructor
	 */
	protected function __construct(){

		add_action( 'admin_init' , array( $this, 'register_settings' ) );

		parent::__construct();

	}


	abstract function register_settings();

	/**
	 *	Render single checkbox
	 *
	 *	@param	array	$args
	 */
	public function input_checkbox( $args ) {
		$args = wp_parse_args( $args, array(
			'name'			=> '',
			'label'			=> '',
			'description'	=> '',
		));
		extract($args);
		$value = WPRecaptcha()->get_option( $name );

		printf( '<input type="hidden" name="%s" value="0" />', $this->option_prefix . $name );
		printf( '<input id="%1$s" type="checkbox" name="%1$s" value="1" %2$s>',
			$this->option_prefix . $name,
			checked( $value, true, false )
		);
		printf( '<label for="%s">', $this->option_prefix . $name );
		echo $label;
		echo '</label>';

		if ( $description ) {
			?><p class="description"><?php echo $description ?></p><?php
		}
	}

	/**
	 *	Render Radio input group
	 *
	 *	@param	array	$args
	 */
	public function input_radio( $args ) {
		$args = wp_parse_args( $args, array(
			'name'			=> '',
			'description'	=> '',
			'class' 		=> '',
			'horizontal'	=> false,
			'choices'		=> array(),
			'before'		=> '',
			'after'			=> '',
		));
		extract($args); // name, items

		$selected = WPRecaptcha()->get_option( $name );
		echo $before;
		echo '<div class="radio-group wp-clearfix">';
		foreach ( $choices as $value => $label ) {
			echo '<div class="option">';
			printf('<input id="%1$s-%2$s" type="radio" name="%1$s" value="%2$s" %3$s>',
				$this->option_prefix . $name,
				$value,
				checked( $value, $selected, false )
			);
			printf('<label for="%s-%s">', $this->option_prefix . $name, $value );
			echo $label;
			echo '</label>';
			echo '</div>';
		}
		echo '</div>';
		if ( $description ) {
			?><p class="description"><?php echo $description ?></p><?php
		}
		echo $after;
	}

	/**
	 *	Render Secret text input
	 *
	 *	@param	array	$args
	 */
	public function input_secret_text( $args ) {
		$args = wp_parse_args( $args, array(
			'name'			=> '',
			'description'	=> '',
		));
		extract( $args ); // name, value

		$value = WPRecaptcha()->get_option( $name );

		printf('<input type="text" class="regular-text ltr" name="%s" value="" />', $this->option_prefix . $name );

		if ( $description ) {
			?><p class="description"><?php echo $description ?></p><?php
		}
	}

	/**
	 *	Render Secret text input
	 *
	 *	@param	array	$args
	 */
	public function input_button( $args ) {
		$args = wp_parse_args( $args, array(
			'name'			=> '',
			'label'			=> '',
			'value'			=> '',
			'description'	=> '',
		));
		extract( $args ); // name, value

		printf('<button class="button button-secondary" name="%s" value="%s">%s</button>', $this->option_prefix . $name, $value, $label );

		if ( $description ) {
			?><p class="description"><?php echo $description ?></p><?php
		}
	}

	/**
	 *	Render Select input
	 *
	 *	@param	array	$args
	 */
	public function input_select( $args ) {
		$args = wp_parse_args( $args, array(
			'name'			=> '',
			'choices'		=> array(),
			'description'	=> '',
			'before'		=> '',
			'after'			=> '',
		));
		extract($args); // name, items

		$selected = WPRecaptcha()->get_option( $name );
		echo $before;

		printf( '<select name="%s">', $this->option_prefix . $name );
		$this->render_options( $choices, $selected );
		echo '</select>';
		if ( $description ) {
			?><p class="description"><?php echo $description ?></p><?php
		}
		echo $after;
	}
	private function render_options( $options, $selected ) {
		var_dump($options);
		foreach ( $options as $value => $option ) {
			if ( is_scalar($option) ) {
				printf('<option value="%s" %s>%s</option>',
					$value,
					selected( $value, $selected, false ),
					$option
				);

			} else {
				$option = wp_parse_args( $option, array('label'=> '', 'choices' => array() ) );
				if ( ! count( $option['choices' ] ) ) {
					continue;
				}
				printf( '<optgroup label="%s">', $option['label'] );
				$this->render_options( $option['choices' ], $selected );
				echo '</optgroup>';
			}
		}

	}
	protected function register_setting( $optionset, $name, $args = array() ) {
		return register_setting( $optionset, $this->option_prefix . $name, $args );
	}
	protected function add_settings_field( $id, $title, $callback, $page, $section = 'default', $args = array() ) {
		return add_settings_field( $this->option_prefix .$id, $title, $callback, $page, $section, $args );
	}


}
