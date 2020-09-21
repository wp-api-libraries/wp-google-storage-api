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
		protected $base_uri   = 'https://storage.googleapis.com/storage/v1/';

		/**
		 * Google storage upload api Endpoint
		 *
		 * @var string
		 */
		protected $upload_uri = 'https://storage.googleapis.com/upload/storage/v1/';

		/**
		 * Route being called.
		 *
		 * @access protected
		 * @var string
		 */
		protected $route = '';

		/**
		 * Is the api call an upload.
		 *
		 * @var boolean
		 */
		protected $is_upload   = false;

		/**
		 * Object upload mime type
		 *
		 * @var string
		 */
		protected $upload_type;


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
				$this->route = add_query_arg( array_filter( $args ), $this->route );
			} elseif ( ! $this->is_upload && 'application/json' === $this->args['headers']['Content-Type'] ) {
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
			// Choose correct uri.
			$uri  = ($this->is_upload) ? $this->upload_uri : $this->base_uri;

			// Make the request.
			$response = wp_remote_request( $uri . $this->route, $this->args );

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
				'Content-Type'  => ( $this->is_upload ) ? $this->upload_type : 'application/json',
				'Authorization' => 'Bearer ' . static::$api_token,
			);
		}

		/**
		 * Clear query data.
		 */
		protected function clear() {
			$this->is_upload  = false;
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
		 * 
		 * @param  string $bucket [description]
		 * @param  array  $args   [description]
		 * @return [type]         [description]
		 */
		public function get_bucket( string $bucket, $args = array() ) {
			$bucket = urlencode( $bucket );
			return $this->build_request( "b/$bucket", $args = array() )->fetch();
		}

		/**
		 * Get Object.
		 * 
		 * @link https://cloud.google.com/storage/docs/json_api/v1/objects/get
		 * 
		 * @param  string $bucket Bucket Name
		 * @param  string $object Object Name
		 * @param  array  $args   https://cloud.google.com/storage/docs/json_api/v1/objects/get#parameters 
		 * 						  ( Setting 'alt' =>'media' as a query arg, retrieves object data ).
		 * @return JSON|OBJECT    Returns JSON with object metadata or the object data if 'alt' is set to 'media'.
		 */
		public function get_object( string $bucket, string $object, $args = array( 'alt' => 'json' ) ) {

			$bucket = urlencode( $bucket );
			$object = urlencode( $object );

			return $this->build_request( "b/$bucket/o/$object", $args )->fetch();
		}

		/**
		 * Insert Object (https://cloud.google.com/storage/docs/json_api/v1/objects/insert)
		 * 
		 * @see https://cloud.google.com/storage/docs/uploading-objects#rest-upload-objects
		 * 
		 * @param  string $bucket      Bucket Name
		 * @param  string $upload_type The type of upload request (media|multipart|resumable) default=media.
		 * @param  string $file_path   File path of the file to upload
		 * @param  string $name        File name. If null the name in the filepath will be used.
		 * @return JSON                JSON response.
		 */
		public function insert_object( string $bucket, string $file_path, string $name = null, string $upload_type ='media') {
			$this->is_upload  = true;

			$bucket = urlencode( $bucket );

			// Set file name from filepath if null.
			$name = ( is_null($name) ) ? wp_basename( $file_path ) : $name;

			// Set mime type.
			$this->upload_type = mime_content_type( $file_path );

			$file = file_get_contents($file_path);
			$route = add_query_arg( array(
				'uploadType' => $upload_type,
				'name'       => $name
			), "b/$bucket/o" );

			return $this->build_request( $route, $file, 'POST' )->fetch();
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
			return $this->build_request( "b/$bucket/o/$object", $args, 'DELETE' )->fetch();
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
