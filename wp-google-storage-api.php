<?php
/**
 * WP Google Storage API
 *
 * @link https://cloud.google.com/storage/docs/json_api/
 * @package WP-API-Libraries\WP-Google-Stroage-API
 */
/*
* Plugin Name: WP Google Storage API
* Plugin URI: https://github.com/wp-api-libraries/wp-google-storage-api
* Description: Perform API requests to Google Storage in WordPress.
* Author: WP API Libraries
* Version: 1.0.0
* Author URI: https://wp-api-libraries.com
* GitHub Plugin URI: https://github.com/wp-api-libraries/wp-google-storage-api
* GitHub Branch: master
*/

/* Exit if accessed directly. */
if ( ! defined( 'ABSPATH' ) ) {
	exit; }

/* Check if class exists. */
if ( ! class_exists( 'GoogleStorageAPI' ) ) {

	/**
	 * GoogleStorageAPI Class.
	 */
	class GoogleStorageAPI {
		/**
		 * API Key.
		 *
		 * @var string
		 */
		protected static $api_token;

		/**
		 * Google storage api Endpoint
		 *
		 * @var string
		 * @access protected
		 */
		protected $base_uri = 'https://storage.googleapis.com/storage/v1/';

		/**
		 * Route being called.
		 *
		 * @var string
		 */
		protected $route = '';


		/**
		 * Class constructor.
		 *
		 * @param string $api_token             Google API Key.
		 * @param string $auth_email            Email associated to the account.
		 * @param string $user_service_key      User Service key.
		 */
		public function __construct( $api_token ) {
			static::$api_token = $api_token;
		}

		/**
		 * Prepares API request.
		 *
		 * @param  string $route   API route to make the call to.
		 * @param  array  $args    Arguments to pass into the API call.
		 * @param  array  $method  HTTP Method to use for request.
		 * @return self            Returns an instance of itself so it can be chained to the fetch method.
		 */
		protected function build_request( $route, $args = array(), $method = 'GET' ) {
			// Start building query.
			$this->set_headers();
			$this->args['method'] = $method;
			$this->route          = $route;

			// Generate query string for GET requests.
			if ( 'GET' === $method ) {
				$this->route = add_query_arg( array_filter( $args ), $route );
			} elseif ( 'application/json' === $this->args['headers']['Content-Type'] ) {
				$this->args['body'] = wp_json_encode( $args );
			} else {
				$this->args['body'] = $args;
			}

			return $this;
		}


		/**
		 * Fetch the request from the API.
		 *
		 * @access private
		 * @return array|WP_Error Request results or WP_Error on request failure.
		 */
		protected function fetch() {
			// Make the request.
			$response = wp_remote_request( $this->base_uri . $this->route, $this->args );

			// var_dump( $response );

			// Retrieve Status code & body.
			$code = wp_remote_retrieve_response_code( $response );
			$body = json_decode( wp_remote_retrieve_body( $response ) );

			$this->clear();
			// Return WP_Error if request is not successful.
			if ( ! $this->is_status_ok( $code ) ) {
				return new WP_Error( 'response-error', sprintf( __( 'Status: %d', 'wp-google-storage-api' ), $code ), $body );
			}

			return $body;
		}


		/**
		 * Set request headers.
		 */
		protected function set_headers() {

			// Set request headers.
			$this->args['headers'] = array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . static::$api_token,
			);
		}

		/**
		 * Clear query data.
		 */
		protected function clear() {
			$this->args       = array();
			$this->query_args = array();
		}

		/**
		 * Check if HTTP status code is a success.
		 *
		 * @param  int $code HTTP status code.
		 * @return boolean       True if status is within valid range.
		 */
		protected function is_status_ok( $code ) {
			return ( 200 <= $code && 300 > $code );
		}

		/**
		 * Get Bucket.
		 * @param  string $bucket [description]
		 * @param  array  $args   [description]
		 * @return [type]         [description]
		 */
		public function get_bucket( string $bucket, $args = array() ) {
			return $this->build_request( "b/$bucket", array( 'key' => static::$api_token ) )->fetch();
		}

		/**
		 * Get Object.
		 * @param  string $bucket [description]
		 * @param  string $object [description]
		 * @param  array  $args   [description]
		 * @return [type]         [description]
		 */
		public function get_object( string $bucket, string $object, $args = array() ) {

			$bucket = urlencode( $bucket );
			$object = urlencode( $object );

			return $this->build_request( "b/$bucket/o/$object", array( 'key' => static::$api_token ) )->fetch();
		}

		/**
		 * Insert Object (https://cloud.google.com/storage/docs/json_api/v1/objects/insert)
		 * @param  [type] $bucket [description]
		 * @param  array  $args   [description]
		 * @return [type]         [description]
		 */
		public function insert_object( string $bucket, $args = array() ) {
			$bucket = urlencode( $bucket );
			return $this->build_request( "b/$bucket/o", array( 'key' => static::$api_token ), 'POST' )->fetch();
		}

		/**
		 * Delete Object.
		 * @param  string $bucket [description]
		 * @param  string $object [description]
		 * @param  array  $args   [description]
		 * @return [type]         [description]
		 */
		public function delete_object( string $bucket, string $object, $args = array() ) {
			$bucket = urlencode( $bucket );
			$object = urlencode( $object );
			return $this->build_request( "b/$bucket/o/$object", array( 'key' => static::$api_token ), 'DELETE' )->fetch();
		}

		/**
		 * Update Object.
		 * @param  string $bucket [description]
		 * @param  string $object [description]
		 * @param  array  $args   [description]
		 * @return [type]         [description]
		 */
		public function update_object( string $bucket, string $object, $args = array() ) {
			$bucket = urlencode( $bucket );
			$object = urlencode( $object );
			return $this->build_request( "b/$bucket/o/$object", array( 'key' => static::$api_token ), 'PUT' )->fetch();
		}

	}

}
