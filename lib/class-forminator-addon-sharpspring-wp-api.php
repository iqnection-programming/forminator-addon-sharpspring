<?php

require_once dirname(__FILE__).'/class-forminator-addon-sharpspring-wp-api-exception.php';
require_once dirname(__FILE__).'/class-forminator-addon-sharpspring-wp-api-not-found-exception.php';

/**
 * Class Forminator_Addon_SharpSpring_Wp_Api
 */
class Forminator_Addon_SharpSpring_Wp_Api {

	/**
	 * Instances of sharpspring api
	 *
	 * @var array
	 */
	private static $_instances = array();

	/**
	 * SharpSprint endpoint
	 *
	 * @var string
	 */
	private $_endpoint = 'https://api.sharpspring.com/pubapi/v1/';

	/**
	 * Last data sent to sharpspring
	 *
	 * @since 1.0 SharpSprint Addon
	 * @var array
	 */
	private $_last_data_sent = array();

	/**
	 * Last data received from sharpspring
	 *
	 * @since 1.0 SharpSpring Addon
	 * @var array
	 */
	private $_last_data_received = array();

	/**
	 * Last URL requested
	 *
	 * @since 1.0 SharpSpring Addon
	 * @var string
	 */
	private $_last_url_request = '';

	private $_account_id = '';
	private $_secret_key = '';

	/**
	 * Forminator_Addon_SharpSpring_Wp_Api constructor.
	 *
	 * @since 1.0 SharpSpring Addon
	 *
	 * @param $_token
	 *
	 * @throws Forminator_Addon_SharpSpring_Wp_Api_Exception
	 */
	public function __construct( $account_id, $secret_key ) {
		//prerequisites
		if ( ! $account_id || ! $secret_key ) {
			throw new Forminator_Addon_SharpSpring_Wp_Api_Exception( __( 'Missing required Token', 'forminator' ) );
		}

		$this->_account_id = $account_id;
		$this->_secret_key = $secret_key;
	}

	/**
	 * Get singleton
	 *
	 * @since 1.0 SharpSpring Addon
	 *
	 * @param $_token
	 *
	 * @return Forminator_Addon_SharpSpring_Wp_Api|null
	 * @throws Forminator_Addon_SharpSpring_Wp_Api_Exception
	 */
	public static function get_instance( $account_id, $secret_key ) {
		$hash = md5( $account_id.$secret_key );
		if ( ! isset( self::$_instances[ $hash ] ) ) {
			self::$_instances[ $hash ] = new self( $account_id, $secret_key );
		}

		return self::$_instances[ $hash ];
	}

	/**
	 * Add custom user agent on request
	 *
	 * @since 1.0 SharpSpring Addon
	 *
	 * @param $user_agent
	 *
	 * @return string
	 */
	public function filter_user_agent( $user_agent ) {
		$user_agent .= ' ForminatorSharpSpring/' . FORMINATOR_ADDON_SHARPSPRING_VERSION;

		/**
		 * Filter user agent to be used by sharpspring api
		 *
		 * @since 1.1
		 *
		 * @param string $user_agent current user agent
		 */
		$user_agent = apply_filters( 'forminator_addon_sharpspring_api_user_agent', $user_agent );

		return $user_agent;
	}

	/**
	 * HTTP Request
	 *
	 * @since 1.0 SharpSpring Addon
	 *
	 * @param string $verb
	 * @param        $path
	 * @param array $args
	 * @param string $access_token
	 * @param bool $json
	 *
	 * @return array|mixed|object
	 * @throws Forminator_Addon_SharpSpring_Wp_Api_Exception
	 * @throws Forminator_Addon_SharpSpring_Wp_Api_Not_Found_Exception
	 */
	private function request( $method, $args = [] ) {
		if ( ! is_array( $args ) ) {
			$args = array();
		}
		// Adding extra user agent for wp remote request
		add_filter( 'http_headers_useragent', array( $this, 'filter_user_agent' ) );

		$url  = trailingslashit( $this->_endpoint );
		$verb = 'POST';

		/**
		 * Filter sharpspring url to be used on sending api request
		 *
		 * @since 1.1
		 *
		 * @param string $url full url with scheme
		 * @param string $verb `GET` `POST` `PUT` `DELETE` `PATCH`
		 * @param string $path requested path resource
		 * @param array $args argument sent to this function
		 */
		$queryParams = [
			'accountID' => $this->_account_id,
			'secretKey' => $this->_secret_key
		];

		$url = apply_filters( 'forminator_addon_sharpspring_api_url', $url, $method, $args, $queryParams );
		$url .= '?'.http_build_query($queryParams);

		$this->_last_url_request = $url;

		$headers = array();

		/**
		 * Filter sharpspring headers to sent on api request
		 *
		 * @since 1.1
		 *
		 * @param array $headers
		 * @param string $verb `GET` `POST` `PUT` `DELETE` `PATCH`
		 * @param string $path requested path resource
		 * @param array $args argument sent to this function
		 */
		$headers = apply_filters( 'forminator_addon_sharpspring_api_request_headers', $headers, $method, $args );

		if (!is_array($headers))
		{
			$headers = [];
		}
		$headers['Content-Type'] = 'application/json';
		$requestId = uniqid();

		$body = array(
			'method'  => $method,
			'params' => $args,
			'id' => $requestId
		);

		$request_body = wp_json_encode( $body );

		$_args = [
			'method' => $verb,
			'headers' => $headers,
			'body' => $request_body,
		];

		$this->_last_data_sent = $_args;

		$res                   = wp_remote_request( $url, $_args );
		$wp_response           = $res;

		$this->_last_data_received = $res;

		remove_filter( 'http_headers_useragent', array( $this, 'filter_user_agent' ) );

		if ( is_wp_error( $res ) || ! $res ) {
			throw new Forminator_Addon_SharpSpring_Wp_Api_Exception(
				__( 'Failed to process request, make sure your API URL is correct and your server has internet connection.', 'forminator' )
			);
		}

		if ( isset( $res['response']['code'] ) ) {
			$status_code = $res['response']['code'];
			$msg         = '';
			if ( $status_code > 400 ) {
				if ( isset( $res['response']['message'] ) ) {
					$msg = $res['response']['message'];
				}

				if ( 404 === $status_code ) {
					/* translators: ... */
					throw new Forminator_Addon_SharpSpring_Wp_Api_Not_Found_Exception( sprintf( __( 'Failed to process request : %s', 'forminator' ), $msg ) );
				}
				/* translators: ... */
				throw new Forminator_Addon_SharpSpring_Wp_Api_Exception( sprintf( __( 'Failed to process request : %s', 'forminator' ), $msg ) );
			}
		}

		$body = wp_remote_retrieve_body( $res );

		// probably silent mode
		if ( ! empty( $body ) ) {
			$res = json_decode( $body );

			$this->_last_data_received = $res;
			if ( isset( $res->status ) && 'error' === $res->status ) {
				$message = isset( $res->message ) ? $res->message : __( 'Invalid', 'forminator' );
				/* translators: ... */
				throw new Forminator_Addon_SharpSpring_Wp_Api_Not_Found_Exception( sprintf( __( 'Failed to process request : %s', 'forminator' ), $message ) );
			}
			if ( isset( $res->ok ) && false === $res->ok ) {
				$msg = '';
				if ( isset( $res->error ) ) {
					$msg = $res->error;
				}
				/* translators: ... */
				throw new Forminator_Addon_SharpSpring_Wp_Api_Exception( sprintf( __( 'Failed to process request : %s', 'forminator' ), $msg ) );
			}
		}

		$response = $res;
		/**
		 * Filter sharpspring api response returned to addon
		 *
		 * @since 1.1
		 *
		 * @param mixed $response original wp remote request response or decoded body if available
		 * @param string $body original content of http response's body
		 * @param array|WP_Error $wp_response original wp remote request response
		 */
		$res = apply_filters( 'forminator_addon_sharpspring_api_response', $response, $body, $wp_response );

		$this->_last_data_received = $res;

		return $res;
	}

	/**
	 * Get last data sent
	 *
	 * @since 1.0 SharpSpring Addon
	 *
	 * @return array
	 */
	public function get_last_data_sent() {
		return $this->_last_data_sent;
	}

	/**
	 * Get last data received
	 *
	 * @since 1.0 SharpSpring Addon
	 *
	 * @return array
	 */
	public function get_last_data_received() {
		return $this->_last_data_received;
	}

	/**
	 * Get last data received
	 *
	 * @since 1.0 SharpSpring Addon
	 *
	 * @return string
	 */
	public function get_last_url_request() {
		return $this->_last_url_request;
	}

	/**
	 * Retrieve a list of clients for which the owner of the API keys manages in SharpSpring.
	 *
	 * @return array
	 */
	public function get_clients() {
		return $this->request('getClients' );
	}

	/**
	 * Retrieve available fields from SharpSpring.
	 *
	 * @return array
	 */
	public function get_fields() {
		$args = [];
		$response = $this->request('getFields', ['where' => $args ] );
		return $response->result->field;
	}

	/**
	 * Add or update contact subscriber to SharpSpring.
	 *
	 * @param $data
	 *
	 * @return array|mixed|object
	 * @throws Forminator_Addon_SharpSpring_Wp_Api_Exception
	 * @throws Forminator_Addon_SharpSpring_Wp_Api_Not_Found_Exception
	 */
	public function add_update_contact( $data ) {
		// see if the contact already exists in the CRM
		$property = $this->get_properties([
			'where' => [
				'emailAddress' => $data['emailAddress']
			]
		]);
		$method = 'createLeads';
		if ($trackingId = sharpspring_user_tracking_id())
		{
			$data['trackingID'] = $trackingId;
		}
		if ( (is_array($property->result->lead)) && (count($property->result->lead)) )
		{
			$method = 'updateLeads';
			$propertyData = array_shift($property->result->lead);
			$data['id'] = $propertyData->id;
		}
		$params = [
			'objects' => [$data]
		];
		$response = $this->request( $method, $params);

		return $response;
	}

	/**
	 * Remove contact subscriber from SharpSpring.
	 *
	 * @param $data
	 *
	 * @return array|mixed|object
	 * @throws Forminator_Addon_SharpSpring_Wp_Api_Exception
	 * @throws Forminator_Addon_SharpSpring_Wp_Api_Not_Found_Exception
	 */
	public function delete_contact( $data ) {
		$property = $this->get_properties([
			'where' => [
				'emailAddress' => $data['emailAddress']
			]
		]);
		if ( (!is_array($property->result->lead)) || (!count($property->result->lead)) )
		{
			return null;
		}
		$propertyData = array_shift($property->result->lead);
		$leadId = $propertyData->id;

		$response = $this->request( 'deleteLeads', [
			'objects' => [
				'id' => [$leadId]
			]
		]);

		return $response;
	}

	/**
	 * Get one or more lead's Properties
	 *
	 * @param array $args
	 *
	 * @return array|mixed|object
	 * @throws Forminator_Addon_SharpSpring_Wp_Api_Exception
	 * @throws Forminator_Addon_SharpSpring_Wp_Api_Not_Found_Exception
	 */
	public function get_properties( $args = array() ) {
		$response = $this->request('getLeads', $args);
		return $response;
	}

}
