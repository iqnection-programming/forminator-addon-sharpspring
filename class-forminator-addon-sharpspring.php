<?php

require_once dirname( __FILE__ ) . '/class-forminator-addon-sharpspring-exception.php';
require_once dirname( __FILE__ ) . '/lib/class-forminator-addon-sharpspring-wp-api.php';

/**
 * Class Forminator_Addon_SharpSpring
 * SharpSpring Addon Main Class
 *
 * @since 1.0 SharpSpring Addon
 */
final class Forminator_Addon_SharpSpring extends Forminator_Addon_Abstract {

	/**
	 * @var self|null
	 */
	private static $_instance = null;

	protected $_slug = 'sharpspring';
	protected $_version = FORMINATOR_ADDON_SHARPSPRING_VERSION;
	protected $_min_forminator_version = '1.1';
	protected $_short_title = 'SharpSpring';
	protected $_title = 'SharpSpring';
	protected $_url = 'https://iqnection.com';
	protected $_full_path = __FILE__;

	protected $_form_settings = 'Forminator_Addon_SharpSpring_Form_Settings';
	protected $_form_hooks = 'Forminator_Addon_SharpSpring_Form_Hooks';

//	protected $_quiz_settings = 'Forminator_Addon_SharpSpring_Quiz_Settings';
//	protected $_quiz_hooks = 'Forminator_Addon_SharpSpring_Quiz_Hooks';

	private $_auth_error_message = '';

	const TARGET_TYPE_PUBLIC_CHANNEL = 'public_channel';
	const TARGET_TYPE_PRIVATE_CHANNEL = 'private_channel';
	const TARGET_TYPE_DIRECT_MESSAGE = 'direct_message';

	/**
	 * @var null|Forminator_Addon_SharpSpring_Wp_Api
	 */
	private static $_api = null;

	protected $_position = 4;

	/**
	 * Forminator_Addon_SharpSpring constructor.
	 *
	 * @since 1.0 SharpSpring Addon
	 */
	public function __construct() {
		// late init to allow translation
		$this->_description                = __( 'Get awesome by your form.', 'forminator' );
		$this->_activation_error_message   = __( 'Sorry but we failed to activate SharpSpring Integration, don\'t hesitate to contact us', 'forminator' );
		$this->_deactivation_error_message = __( 'Sorry but we failed to deactivate SharpSpring Integration, please try again', 'forminator' );

		$this->_update_settings_error_message = __(
			'Sorry, we failed to update settings, please check your form and try again',
			'forminator'
		);

		$this->_icon     = forminator_addon_sharpspring_assets_url() . 'icons/sharpspring.jpg';
//		$this->_icon_x2  = forminator_addon_sharpspring_assets_url() . 'icons/sharpspring@2x.png';
		$this->_image    = forminator_addon_sharpspring_assets_url() . 'img/sharpspring.jpg';
//		$this->_image_x2 = forminator_addon_sharpspring_assets_url() . 'img/sharpspring@2x.png';

	}

	/**
	 * Get Instance
	 *
	 * @since 1.0 SharpSpring Addon
	 * @return self|null
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Override on is_connected
	 *
	 * @since 1.0 SharpSpring Addon
	 *
	 * @return bool
	 */
	public function is_connected() {
		try {
			// check if its active
			if ( ! $this->is_active() ) {
				throw new Forminator_Addon_SharpSpring_Exception( __( 'SharpSpring is not active', 'forminator' ) );
			}

			// if user completed api setup
			$is_connected = false;

			$setting_values = $this->get_settings_values();
			// if user completed api setup
			if ( isset( $setting_values['account_id'] ) && ! empty( $setting_values['account_id'] ) ) {
				if ( isset( $setting_values['secret_key'] ) && ! empty( $setting_values['secret_key'] ) ) {
					$is_connected = true;
				}
			}
		} catch ( Forminator_Addon_SharpSpring_Exception $e ) {
			$is_connected = false;
		}

		/**
		 * Filter connected status of SharpSpring
		 *
		 * @since 1.0
		 *
		 * @param bool $is_connected
		 */
		$is_connected = apply_filters( 'forminator_addon_sharpspring_is_connected', $is_connected );

		return $is_connected;
	}

	/**
	 * Check if SharpSpring is connected with current form
	 *
	 * @since 1.0 SharpSpring Addon
	 *
	 * @param $form_id
	 *
	 * @return bool
	 */
	public function is_form_connected( $form_id ) {
		try {
			$form_settings_instance = null;
			if ( ! $this->is_connected() ) {
				throw new Forminator_Addon_SharpSpring_Exception( __( 'SharpSpring is not connected', 'forminator' ) );
			}

			$form_settings_instance = $this->get_addon_form_settings( $form_id );
			if ( ! $form_settings_instance instanceof Forminator_Addon_SharpSpring_Form_Settings ) {
				throw new Forminator_Addon_SharpSpring_Exception( __( 'Invalid Form Settings of SharpSpring', 'forminator' ) );
			}

			// Mark as active when there is at least one active connection
			if ( false === $form_settings_instance->find_one_active_connection() ) {
				throw new Forminator_Addon_SharpSpring_Exception( __( 'No active SharpSpring connection found in this form', 'forminator' ) );
			}

			$is_form_connected = true;

		} catch ( Forminator_Addon_SharpSpring_Exception $e ) {
			$is_form_connected = false;
		}

		/**
		 * Filter connected status of SharpSpring with the form
		 *
		 * @since 1.0
		 *
		 * @param bool $is_form_connected
		 * @param int $form_id Current Form ID
		 * @param Forminator_Addon_SharpSpring_Form_Settings|null $form_settings_instance Instance of form settings, or null when unavailable
		 *
		 */
		$is_form_connected = apply_filters( 'forminator_addon_sharpspring_is_form_connected', $is_form_connected, $form_id, $form_settings_instance );

		return $is_form_connected;
	}

	/**
	 * Override settings available,
	 *
	 * @since 1.0 SharpSpring Addon
	 * @return bool
	 */
	public function is_settings_available() {
		return true;
	}

	/**
	 * Flag show full log on entries
	 *
	 * @since 1.0 SharpSpring Addon
	 * @return bool
	 */
	public static function is_show_full_log() {
		$show_full_log = false;
		if ( defined( 'FORMINATOR_ADDON_SHARPSPRING_SHOW_FULL_LOG' ) && FORMINATOR_ADDON_SHARPSPRING_SHOW_FULL_LOG ) {
			$show_full_log = true;
		}

		/**
		 * Filter Flag show full log on entries
		 *
		 * @since  1.2
		 *
		 * @params bool $show_full_log
		 */
		$show_full_log = apply_filters( 'forminator_addon_sharpspring_show_full_log', $show_full_log );

		return $show_full_log;
	}

	/**
	 * Allow multiple connection on one form
	 *
	 * @since 1.0 SharpSpring Addon
	 * @return bool
	 */
	public function is_allow_multi_on_form() {
		return true;
	}

	/**
	 * Build settings help on settings
	 *
	 * @since 1.0 SharpSpring Addon
	 * @return string
	 */
	public function settings_help() {

		// Display how to get sharpspring API Key by default
		$help = sprintf( 'To retrieve your API keys, login to your SharpSpring account, open your account menu at the top right of the page, and select Settings. 
		In the left sidebar navigation, API Settings under the SharpSpring API section.' );

		$help = '<span class="sui-description" style="margin-top: 20px;">' . $help . '</span>';

		$setting_values = $this->get_settings_values();

		if (
			isset( $setting_values['account_id'] )
			&& $setting_values['account_id']
			&& isset( $setting_values['secret_key'] )
			&& ! empty( $setting_values['secret_key'] )
		) {
			// Show currently connected account if its already connected
			/* translators:  placeholder is Name and Email of Connected Account */
			$help = '<span class="sui-description" style="margin-top: 20px;">' . __( 'Change your API Key or disconnect this Integration below.' ) . '</span>';

		}

		return $help;

	}

	/**
	 * Settings wizard
	 *
	 * @since 1.0 SharpSpring Addon
	 * @return array
	 */
	public function settings_wizards() {
		return array(
			array(
				'callback'     => array( $this, 'configure_api_key' ),
				'is_completed' => array( $this, 'settings_is_complete' ),
			),
		);
	}

	private function get_account_id() {
		/** @var array $setting_values */
		$setting_values = $this->get_settings_values();
		if ( isset( $setting_values['account_id'] ) ) {
			return $setting_values['account_id'];
		}

		return null;
	}

	private function get_secret_key() {
		/** @var array $setting_values */
		$setting_values = $this->get_settings_values();
		if ( isset( $setting_values['secret_key'] ) ) {
			return $setting_values['secret_key'];
		}

		return null;
	}

	private function settings_is_complete() {
		$setting_values = $this->get_settings_values();

		// check api_key and connected_account exists and not empty
		return isset( $setting_values['account_id'] ) && $setting_values['account_id']
			&& isset( $setting_values['secred_key'] ) && ! empty( $setting_values['secred_key'] );
	}

	protected function validate_api_keys( $account_id, $secred_key ) {
		if ( empty( $account_id ) || empty($secred_key) ) {
			$this->_update_settings_error_message = __( 'Please add valid API Keys.', 'forminator' );

			return false;
		}

		try {
			// Check API Key by validating it on get_info request
			$api = $this->get_api( $account_id, $secred_key );
			$info = $api->get_clients();
			if (!$info)
			{
				throw new Forminator_Addon_SharpSpring_Wp_Api_Exception('Could not connect to SharpSpring API');
			}
			forminator_addon_maybe_log( __METHOD__, $info );

		} catch ( Forminator_Addon_SharpSpring_Wp_Api_Exception $e ) {
			$this->_update_settings_error_message = $e->getMessage();

			return false;
		}

		return true;
	}

	/**
	 * Wizard of configure_api_key
	 *
	 * @since 1.0 SharpSpring Addon
	 *
	 * @param     $submitted_data
	 * @param int $form_id
	 *
	 * @return array
	 */
	public function configure_api_key( $submitted_data, $form_id = 0 ) {
		$error_message         = '';
		$api_key_error_message = '';
		$account_id            = $this->get_account_id();
		$secret_key            = $this->get_secret_key();
		// ON Submit
		if( ( isset( $submitted_data['account_id'] ) ) && ( isset( $submitted_data['secret_key'] ) ) ) {
			$account_id           = $submitted_data['account_id'];
			$secret_key           = $submitted_data['secret_key'];
			$api_key_validated = $this->validate_api_keys( $account_id, $secret_key );

			/**
			 * Filter validating api key result
			 *
			 * @since 1.1
			 *
			 * @param bool   $api_key_validated
			 * @param string $api_key API Key to be validated
			 */
			$api_key_validated = apply_filters( 'forminator_addon_sharpspring_validate_api_key', $api_key_validated, $account_id, $secret_key );

			if ( ! $api_key_validated ) {
				$api_key_error_message = $this->_update_settings_error_message;
			} else {
				$show_success = true;
				if ( ! forminator_addon_is_active( $this->_slug ) ) {
					$activated = Forminator_Addon_Loader::get_instance()->activate_addon( $this->_slug );
					if ( ! $activated ) {
						$error_message = '<div class="sui-notice sui-notice-error"><p>' . Forminator_Addon_Loader::get_instance()->get_last_error_message() . '</p></div>';
						$show_success  = false;
					} else {
						$this->save_settings_values( array( 'account_id' => $account_id, 'secret_key' => $secret_key ) );
					}
				} else {
					$this->save_settings_values( array( 'account_id' => $account_id, 'secret_key' => $secret_key ) );
				}

				if ( $show_success ) {
					if ( ! empty( $form_id ) ) {
						// initiate form settings wizard
						return $this->get_form_settings_wizard( array(), $form_id, 0, 0 );
					}

					return array(
						'html'         => '<div class="integration-header"><h3 class="sui-box-title" id="dialogTitle2">' .
							/* translators: ... */
							sprintf( __( '%1$s Added', 'forminator' ), 'SharpSpring' ) .
							'</h3></div>
						<div class="sui-block-content-center">
							<p><small style="color: #666;">' . __( 'You can now go to your forms and assign them to this integration.' ) . '</small></p>
						</div>',
						'buttons'      => array(
							'close' => array(
								'markup' => self::get_button_markup( esc_html__( 'Close', 'forminator' ), 'forminator-addon-close' ),
							),
						),
						'redirect'     => false,
						'has_errors'   => false,
						'notification' => array(
							'type' => 'success',
							'text' => '<strong>' . $this->get_title() . '</strong> ' . __( 'is connected successfully.' ),
						),
					);
				}
			}
		}

		$buttons = array();

		$is_edit = false;
		if ( $this->is_connected() ) {
			$is_edit = true;
		}

		if ( $is_edit ) {
			$buttons['disconnect'] = array(
				'markup' => self::get_button_markup( esc_html__( 'Disconnect', 'forminator' ), 'sui-button-ghost forminator-addon-disconnect' ),
			);

			$buttons['submit'] = array(
				'markup' => '<div class="sui-actions-right">' .
					self::get_button_markup( esc_html__( 'Save', 'forminator' ), 'forminator-addon-connect' ) .
					'</div>',
			);
		} else {
			$buttons['submit'] = array(
				'markup' => self::get_button_markup( esc_html__( 'Connect', 'forminator' ), 'forminator-addon-connect' ),
			);
		}

		return array(
			'html'       => '<div class="integration-header">
					<h3 class="sui-box-title" id="dialogTitle2">' .
				/* translators: ... */
				sprintf( __( 'Configure %1$s', 'forminator' ), 'SharpSpring' ) .
				'</h3>
					' . $this->settings_help() . '
					' . $error_message . '
				</div>
				<form>
					<div class="sui-form-field ' . ( ! empty( $api_key_error_message ) ? 'sui-form-field-error' : '' ) . '">
						<label class="sui-label">' . __( 'Account ID', 'forminator' ) . '</label>
						<div class="sui-control-with-icon">
							<input name="account_id"
								placeholder="' .
				/* translators: ... */
				sprintf( __( 'Enter %1$s Account ID', 'forminator' ), 'SharpSpring' ) .
				'"
								value="' . esc_attr( $account_id ) . '"
								class="sui-form-control" />
							<i class="sui-icon-key" aria-hidden="true"></i>
						</div>
					</div>
					<div class="sui-form-field ' . ( ! empty( $api_key_error_message ) ? 'sui-form-field-error' : '' ) . '">
						<label class="sui-label">' . __( 'Secret Key', 'forminator' ) . '</label>
						<div class="sui-control-with-icon">
							<input name="secret_key"
								placeholder="' .
				/* translators: ... */
				sprintf( __( 'Enter %1$s Secret Key', 'forminator' ), 'SharpSpring' ) .
				'"
								value="' . esc_attr( $secret_key ) . '"
								class="sui-form-control" />
							<i class="sui-icon-key" aria-hidden="true"></i>
						</div>
						' . ( ! empty( $api_key_error_message ) ? '<span class="sui-error-message">' . esc_html( $api_key_error_message ) . '</span>' : '' ) . '
						' . /*$this->settings_description() .*/ '
					</div>
				</form>',
			'buttons'    => $buttons,
			'redirect'   => false,
			'has_errors' => ! empty( $error_message ) || ! empty( $api_key_error_message ),
		);
	}

	/**
	 * Get API Instance
	 *
	 * @param null $access_token
	 *
	 * @return Forminator_Addon_SharpSpring_Wp_Api|null
	 * @throws Forminator_Addon_SharpSpring_Wp_Api_Exception
	 */
	public function get_api( $account_id = null, $secret_key = null ) {
		if ( is_null( self::$_api ) ) {
			if ( is_null( $account_id ) ) {
				$account_id = $this->get_account_id();
			}
			if ( is_null( $secret_key ) ) {
				$secret_key = $this->get_secret_key();
			}

			$api        = Forminator_Addon_SharpSpring_Wp_Api::get_instance( $account_id, $secret_key );
			self::$_api = $api;
		}

		return self::$_api;
	}

	/**
	 * Before get Setting Values
	 *
	 * @since 1.0 SharpSpring Addon
	 *
	 * @param $values
	 *
	 * @return mixed
	 */
	public function before_get_settings_values( $values ) {
		if ( isset( $values['account_id'] ) ) {
			$this->account_id = $values['account_id'];
		}
		if ( isset( $values['secret_key'] ) ) {
			$this->secret_key = $values['secret_key'];
		}

		if ( isset( $values['auth_error_message'] ) ) {
			$this->_auth_error_message = $values['auth_error_message'];
		}

		return $values;
	}

	public static function is_enable_delete_member() {
		if ( defined( 'FORMINATOR_ADDON_MAILCHIMP_ENABLE_DELETE_MEMBER' ) && FORMINATOR_ADDON_MAILCHIMP_ENABLE_DELETE_MEMBER ) {
			return true;
		}

		return false;
	}

}
