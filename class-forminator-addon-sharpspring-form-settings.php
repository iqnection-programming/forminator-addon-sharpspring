<?php

require_once dirname( __FILE__ ) . '/class-forminator-addon-sharpspring-form-settings-exception.php';


/**
 * Class Forminator_Addon_SharpSpring_Form_Settings
 * Handle how form settings displayed and saved
 *
 * @since 1.0 SharpSpring Addon
 */
class Forminator_Addon_SharpSpring_Form_Settings extends Forminator_Addon_Form_Settings_Abstract {

	/**
	 * @var Forminator_Addon_SharpSpring
	 * @since 1.0 SharpSpring Addon
	 */
	protected $addon;

	public $target_types = array();

	/**
	 * Forminator_Addon_SharpSpring_Form_Settings constructor.
	 *
	 * @since 1.0 SharpSpring Addon
	 *
	 * @param Forminator_Addon_Abstract $addon
	 * @param                           $form_id
	 *
	 * @throws Forminator_Addon_Exception
	 */
	public function __construct( Forminator_Addon_Abstract $addon, $form_id ) {
		parent::__construct( $addon, $form_id );

		$this->_update_form_settings_error_message = __(
			'The update to your settings for this form failed, check the form input and try again.',
			'forminator'
		);
	}

	/**
	 * SharpSpring Form Settings wizard
	 *
	 * @since 1.0 SharpSpring Addon
	 * @return array
	 */
	public function form_settings_wizards() {
		// numerical array steps
		return array(
			array(
				'callback'     => array( $this, 'map_fields' ),
				'is_completed' => array( $this, 'map_fields_is_completed' ),
			)
		);
	}


	/**
	 * Setup Connection Name
	 *
	 * @param $submitted_data
	 *
	 * @return array
	 * @throws Forminator_Addon_SharpSpring_Exception
	 * @throws Forminator_Addon_SharpSpring_Wp_Api_Exception
	 * @throws Forminator_Addon_SharpSpring_Wp_Api_Not_Found_Exception
	 */
	public function map_fields( $submitted_data ) {
		$template = forminator_addon_sharpspring_dir() . 'views/form-settings/create-contact.php';

		$multi_id = $this->generate_multi_id();
		if ( isset( $submitted_data['multi_id'] ) ) {
			$multi_id = $submitted_data['multi_id'];
		}

		$lists                        = array();
		$property                     = array();
		$email_fields                 = array();
		$forminator_field_element_ids = array();
		$is_close 					= false;
		foreach ( $this->form_fields as $form_field ) {
			// collect element ids
			$forminator_field_element_ids[] = $form_field['element_id'];
			if ( 'email' === $form_field['type'] ) {
				$email_fields[] = $form_field;
			}
		}

		$template_params = array(
			'fields_map'        => $this->get_multi_id_form_settings_value( $multi_id, 'fields_map', array() ),
			'error_message'     => '',
			'multi_id'          => $multi_id,
			'fields'            => array(),
			'form_fields'       => $this->form_fields,
			'email_fields'      => $email_fields,
			'custom_fields_map' => $this->get_multi_id_form_settings_value( $multi_id, 'custom_fields_map', array() ),
		);

		unset( $submitted_data['multi_id'] );

		$fields                    = array(
			'emailAddress'     => __( 'Email Address', 'forminator' ),
			'firstName' => __( 'First Name', 'forminator' ),
			'lastName'  => __( 'Last Name', 'forminator' )
		);
		$template_params['fields'] = $fields;
		try {
			$api           = $this->addon->get_api();
			$contactFields = $api->get_fields();
			if ( ! empty( $contactFields ) ) {
				foreach ( $contactFields as $fieldData ) {
					if ( ( isset( $fieldData->systemName ) ) && ($fieldData->systemName != 'trackingID') ) {
						$property[ $fieldData->systemName ] = $fieldData->label;
					}
				}
			}

		} catch ( Forminator_Addon_SharpSpring_Form_Settings_Exception $e ) {
			$template_params['error_message'] = $e->getMessage();
			$has_errors                       = true;
		}

		$template_params['properties'] = $property;
		$is_submit                = ! empty( $submitted_data );
		$has_errors               = false;
		if ( $is_submit ) {
			$custom_property               = isset( $submitted_data['custom_property'] ) ? $submitted_data['custom_property'] : array();
			$custom_field                  = isset( $submitted_data['custom_field'] ) ? $submitted_data['custom_field'] : array();
			$custom_field_map              = array_combine( $custom_property, $custom_field );
			$fields_map                    = isset( $submitted_data['fields_map'] ) ? $submitted_data['fields_map'] : array();
			$template_params['fields_map'] = $fields_map;
			$template_params['custom_fields_map'] = $custom_field_map;

			try {
				$input_exceptions = new Forminator_Addon_SharpSpring_Form_Settings_Exception();
				if ( ! isset( $fields_map['emailAddress'] ) || empty( $fields_map['emailAddress'] ) ) {
					$input_exceptions->add_input_exception( 'Please assign field for Email Address', 'email_error' );
				}
				$fields_map_to_save = array();
				foreach ( $fields as $key => $title ) {
					if ( isset( $fields_map[ $key ] ) && ! empty( $fields_map[ $key ] ) ) {
						$element_id = $fields_map[ $key ];
						if ( ! in_array( $element_id, $forminator_field_element_ids, true ) ) {
							$input_exceptions->add_input_exception(/* translators: ... */
								sprintf( __( 'Please assign valid field for %s', 'forminator' ), $title ),
								$key . '_error'
							);
							continue;
						}

						$fields_map_to_save[ $key ] = $fields_map[ $key ];
					}
				}

				if ( $input_exceptions->input_exceptions_is_available() ) {
					throw $input_exceptions;
				}

				$this->save_multi_id_form_setting_values(
					$multi_id,
					array(
						'fields_map'        => $fields_map,
						'custom_fields_map' => $custom_field_map
					)
				);
				$is_close = true;

			} catch ( Forminator_Addon_SharpSpring_Form_Settings_Exception $e ) {
				$template_params = array_merge( $template_params, $e->get_input_exceptions() );
				$has_errors      = true;
			} catch ( Forminator_Addon_SharpSpring_Exception $e ) {
				$template_params['error_message'] = $e->getMessage();
				$has_errors                       = true;
			}
		}

		$buttons = array();
		$buttonText = 'Activate';
		if ( $this->map_fields_is_completed( array( 'multi_id' => $multi_id ) ) ) {
			$buttons['disconnect']['markup'] = Forminator_Addon_Abstract::get_button_markup(
				esc_html__( 'Deactivate', 'forminator' ),
				'sui-button-ghost sui-tooltip sui-tooltip-top-center forminator-addon-form-disconnect',
				esc_html__( 'Deactivate this SharpSpring Integration from this Form.', 'forminator' )
			);
			$buttonText = 'Update';
		}

		$buttons['next']['markup'] = '<div class="sui-actions-right">' .
			Forminator_Addon_Abstract::get_button_markup( $buttonText, 'forminator-addon-next sui-button-blue' ) .
		                             '</div>';

		return array(
			'html'       => Forminator_Addon_Abstract::get_template( $template, $template_params ),
			'buttons'    => $buttons,
			'is_close'     => $is_close,
			'size'       => 'large',
			'redirect'   => false,
			'has_errors' => $has_errors,
		);
	}

	/**
	 * Check if pick name step completed
	 *
	 * @since 1.0 SharpSpring Addon
	 *
	 * @param $submitted_data
	 *
	 * @return bool
	 */
	public function map_fields_is_completed( $submitted_data ) {
		$multi_id = '';
		if ( isset( $submitted_data['multi_id'] ) ) {
			$multi_id = $submitted_data['multi_id'];
		}

		if ( empty( $multi_id ) ) {
			return false;
		}

		$fields_map = $this->get_multi_id_form_settings_value( $multi_id, 'fields_map', array() );

		if ( empty( $fields_map ) || ! is_array( $fields_map ) || count( $fields_map ) < 1 ) {
			return false;
		}

		if ( ! isset( $fields_map['emailAddress'] ) || empty( $fields_map['emailAddress'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if multi_id form settings values completed
	 *
	 * @since 1.0 SharpSpring Added
	 *
	 * @param $multi_id
	 *
	 * @return bool
	 */
	public function is_multi_form_settings_complete( $multi_id ) {
		$data = array( 'multi_id' => $multi_id );

		if ( ! $this->map_fields_is_completed( $data ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Generate multi id for multiple connection
	 *
	 * @since 1.0 SharpSpring Addon
	 * @return string
	 */
	public function generate_multi_id() {
		return uniqid( 'sharpspring_', true );
	}


	/**
	 * Override how multi connection displayed
	 *
	 * @since 1.0 SharpSpring Addon
	 * @return array
	 */
	public function get_multi_ids() {
		$multi_ids = array();
		foreach ( $this->get_form_settings_values() as $key => $value ) {
			$multi_ids[] = array(
				'id'    => $key,
				// use name that was added by user on creating connection
				'label' => isset( $value['name'] ) ? $value['name'] : $key,
			);
		}

		return $multi_ids;
	}

	/**
	 * Disconnect a connection from current form
	 *
	 * @since 1.0 SharpSpring Addon
	 *
	 * @param array $submitted_data
	 */
	public function disconnect_form( $submitted_data ) {
		// only execute if multi_id provided on submitted data
		if ( isset( $submitted_data['multi_id'] ) && ! empty( $submitted_data['multi_id'] ) ) {
			$addon_form_settings = $this->get_form_settings_values();
			unset( $addon_form_settings[ $submitted_data['multi_id'] ] );
			$this->save_form_settings_values( $addon_form_settings );
		}
	}
}
