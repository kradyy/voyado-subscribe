<?php
if ( class_exists( 'Voyado_API' ) ) {
	return;
}

/**
 * Class Voyado_API
 */
class Voyado_API {

	/**
	 * @var mixed
	 */
	protected static $instance;
	/*
	 * Cache the user api_key so we only have to log in once per client instantiation
	 */
	/*
	 * @var mixed
	 */
	var $api_key;
	/*
	 * @var mixed
	 */
	/*
	 * @var mixed
	 */
	var $url;
	/*
	 * @var string
	 */
	/*
	 * @var string
	 */
	var $token = 'voyado_subscribe';
	/*
	 * @var string
	 */
	/*
	 * @var string
	 */
	var $version = 'v2';
	/*
	 * @var string
	 */
	/*
	 * @var string
	 */
	var $contactType = 'member';
	/*
	 * @var string
	 */
	/*
	 * @var string
	 */
	var $storeExternalId = 'J';
	/*
	 * @var mixed
	 */
	/*
	 * @var mixed
	 */
	var $errors = false;
	/*
	 * @var mixed
	 */
	/*
	 * @var mixed
	 */
	var $HTTP_Code;
	/*
	 * @var int
	 */
	/*
	 * @var int
	 */
	var $default_error_HTTP_Code = 400;
	/*
	 * @var mixed
	 */
	/*
	 * @var mixed
	 */
	var $errorMessage;
	/*
	 * @var string
	 */
	/*
	 * @var string
	 */
	var $show_all_params = ''; //count=100&offset=0';

	/**
	 * Connect to the Voyado API for a given list.
	 *
	 * @param string $apikey Your Voyado apikey $secure Whether or not this should use a secure connection
	 */
	public function __construct( $apikey = '' ) {
		$this->setup_api_key( $apikey );
	}

	/** Setup api key
	 *
	 * @param $apikey
	 */
	protected function setup_api_key( $apikey ) {
		$this->api_key = get_option( $this->token . '_api_key' );
		$this->url = "https://travsport.staging.voyado.com/api/{$this->version}/";
	}

	/**
	 * Set $apiKey
	 *
	 * @param $apiKey
	 */
	public function set_apikey( $apiKey ) {
		$this->api_key = get_option( $this->token . '_api_key' );
		$this->url = "https://travsport.staging.voyado.com/api/{$this->version}/";
	}

	/**
	 * @return Voyado_API
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get Voyado account lists by api_key
	 *
	 * @return array|mixed|object|string
	 */
	public function get_subscriber_count() {
		$response = wp_remote_request(
			$this->url . "contacts/count/" . "?" . $this->show_all_params, array(
				'headers' => array(
					'apikey' => $this->api_key,
					'Accept' => 'application/json',
				),
			) );

		// set transient live
		if ( !is_wp_error( $response ) ) {
			$this->HTTP_Code = wp_remote_retrieve_response_code( $response );
		} else {
			$this->HTTP_Code = $this->default_error_HTTP_Code;
		}

		if ( $this->HTTP_Code == 200 ) {
			$body = wp_remote_retrieve_body( $response );
			$body = json_decode( $body, true );

			return $body;
		} else {
			return false;
		}

		return $body;
	}

	/**
	 * Check if subscriber exists
	 *
	 * @return array|mixed|object|string
	 */
	public function get_subscriber_by_email( $email ) {
		
		if ( !$email ) return false;

		$email = sanitize_email( $email );

		$response = wp_remote_request(
			$this->url . "contactoverview/" . "?email=" . trim( $email ) . "&contactType=" . $this->contactType . $this->show_all_params, array(
				'headers' => array(
					'apikey' => $this->api_key,
					'Accept' => 'application/json',
				),
			) );

		// set transient live
		if ( !is_wp_error( $response ) ) {
			$this->HTTP_Code = wp_remote_retrieve_response_code( $response );
		} else {
			$this->HTTP_Code = $this->default_error_HTTP_Code;
		}

		if ( $this->HTTP_Code == 200 ) {
			$body = wp_remote_retrieve_body( $response );
			$body = json_decode( $body, true );

			return $body;
		} else {
			// 400	- InvalidEmailAddress, InvalidSocialSecurityNumber, InvalidPhoneNumber, InvalidContactId, InvalidSearchQuery, InvalidContactType
			// 404	- ContactNotFound
			// 409	- MultipleMatches
			// 500	- InvalidSystemConfiguration

			$body = wp_remote_retrieve_body( $response );
			$body = json_decode( $body, true );

			return false;
		}

		return false;

		wp_die();
	}

	/**
	 * Add new subscriber to the list
	 *
	 * @param $email
	 *
	 * @return array|bool|mixed
	 */
	public function add_new_subscriber( $email ) {
		if ( !$email ) return false;

		$email = sanitize_email( $email );

		$args = array(
			"email" => ( !empty( $email ) && is_email( $email ) ) ? $email : false,
			"storeExternalId" => $this->storeExternalId,
			"createAsUnapproved" => false,
			"preferences" => array(
				"acceptsEmail" => true,
				"acceptsPostal" => false,
				"acceptsSms" => false,
			),
		);

		$response = wp_remote_post( $this->url . "contacts/", array(
			'method' => 'POST',
			'timeout' => 15,
			'headers' => array(
				'apikey' => $this->api_key,
				'Accept' => 'application/json',
			),
			'body' => http_build_query( $args ),
		)
		);

		// set transient live
		if ( !is_wp_error( $response ) ) {
			$this->HTTP_Code = wp_remote_retrieve_response_code( $response );
		} else {
			$this->HTTP_Code = $this->default_error_HTTP_Code;
		}

		if ( $this->HTTP_Code == 201 ) {
			// 201 - Created
			return true;
		} else {
			// 400	- NoData
			// 409	- ApprovedContactWithKeyExists, ContactWithKeyIsBeingCreated
			// 422	- ValidationError

			return false;
		}

		return false;

		wp_die();
	}

	/**
	 * Get error request
	 *
	 * @param $request
	 *
	 * @return array('error' => 'message)
	 */
	private function get_errors( $request ) {

		if ( is_wp_error( $request ) ) {
			$this->errorMessage = $content = $request->get_error_message();
		} else {
			$content = json_decode( $request['body'], true );
			$this->errorMessage = $content = ( isset( $content['detail'] ) ) ? $content['detail'] : __( 'Data format error.', '' );
		}

		return array( 'error' => $content );
	}

	/**
	 * Get response message
	 *
	 * @return mixed|string
	 */
	public function get_response_message() {
		$default_message = __( 'Invalid API key', '' );

		$messages = array(
			'104' => __( 'Invalid API key', '' ),
			'106' => __( 'Invalid API key', '' ),
			'401' => $this->errorMessage,
			'403' => $this->errorMessage,
			'503' => __( 'Invalid API key', '' ),

		);

		if ( isset( $messages[$this->HTTP_Code] ) ) {
			$message = $messages[$this->HTTP_Code];
		}

		return empty( $message ) ? $default_message : $message;
	}

	/**
	 * Init options
	 *
	 * @param $Voyado_settings
	 *
	 * @return $this
	 */
	public function init_options( $Voyado_settings ) {
		$this->set_apikey( $Voyado_settings['apikey'] );
		$this->username = $Voyado_settings['user_name'];

		return $this;
	}

	/**
	 * Check response
	 *
	 * @param $response
	 */
	protected function check_response( $response ) {
		if ( is_wp_error( $response ) ) {
			$this->HTTP_Code = $this->default_error_HTTP_Code;
		} else {
			$this->HTTP_Code = wp_remote_retrieve_response_code( $response );
			$this->errorMessage = wp_remote_retrieve_response_message( $response );
		}
		$this->errors = ( $this->HTTP_Code !== 200 ) ? true : false;
	}
}