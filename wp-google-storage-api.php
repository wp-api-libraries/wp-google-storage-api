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
		protected $is_upload = false;

		/**
		 * Object upload mime type
		 *
		 * @var string
		 */
		protected $upload_type;


		/**
		 * Class constructor.
		 *
		 * @param string $api_token Google API Key.
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
			$uri = ( $this->is_upload ) ? $this->upload_uri : $this->base_uri;

			// Make the request.
			$response = wp_remote_request( $uri . $this->route, $this->args );

			// Retrieve Status code & body.
			$code = wp_remote_retrieve_response_code( $response );
			$body = json_decode( wp_remote_retrieve_body( $response ) );

			$this->clear();

			// Return WP_Error if request is not successful.
			if ( ! $this->is_status_ok( $code ) ) {
				// translators: Server response status code.
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
		 * @param  string $bucket Bucket to retrieve.
		 * @param  array  $args   Args to pass in to api call.
		 * @return [type]         [description]
		 */
		public function get_bucket( string $bucket, $args = array() ) {
			$bucket = rawurlencode( $bucket );
			return $this->build_request( "b/$bucket", $args = array() )->fetch();
		}

		/**
		 * Get Object.
		 *
		 * @link https://cloud.google.com/storage/docs/json_api/v1/objects/get
		 *
		 * @param  string $bucket Bucket Name.
		 * @param  string $object Object Name.
		 * @param  array  $args   https://cloud.google.com/storage/docs/json_api/v1/objects/get#parameters ( Setting 'alt' =>'media' as a query arg, retrieves object data ).
		 * @return JSON|OBJECT    Returns JSON with object metadata or the object data if 'alt' is set to 'media'.
		 */
		public function get_object( string $bucket, string $object, $args = array( 'alt' => 'json' ) ) {

			$bucket = rawurlencode( $bucket );
			$object = rawurlencode( $object );

			return $this->build_request( "b/$bucket/o/$object", $args )->fetch();
		}

		/**
		 * Insert Object (https://cloud.google.com/storage/docs/json_api/v1/objects/insert)
		 *
		 * @see https://cloud.google.com/storage/docs/uploading-objects#rest-upload-objects
		 *
		 * @param  string $bucket      Bucket Name.
		 * @param  string $file_path   File path of the file to upload.
		 * @param  string $name        File name. If null the name in the filepath will be used.
		 * @param  string $upload_type The type of upload request (media|multipart|resumable) default=media.
		 * @return JSON                JSON response.
		 */
		public function insert_object( string $bucket, string $file_path, string $name = null, string $upload_type = 'media' ) {
			$this->is_upload = true;

			$bucket = rawurlencode( $bucket );

			// Set file name from filepath if null.
			$name = ( is_null( $name ) ) ? wp_basename( $file_path ) : $name;

			// Set mime type.
			$this->upload_type = mime_content_type( $file_path );

			$file  = file_get_contents( $file_path );
			$route = add_query_arg(
				array(
					'uploadType' => $upload_type,
					'name'       => $name,
				),
				"b/$bucket/o"
			);

			return $this->build_request( $route, $file, 'POST' )->fetch();
		}

		/**
		 * Delete Object.
		 *
		 * @param  string $bucket Bucket Name.
		 * @param  string $object Object Name.
		 * @param  array  $args   https://cloud.google.com/storage/docs/json_api/v1/objects/delete.
		 * @return JSON           JSON response.
		 */
		public function delete_object( string $bucket, string $object, $args = array() ) {
			$bucket = rawurlencode( $bucket );
			$object = rawurlencode( $object );

			// Add additional query args to request.
			$route = add_query_arg( $args, "b/$bucket/o/$object" );

			return $this->build_request( $route, array(), 'DELETE' )->fetch();
		}

		/**
		 * Update Object.
		 *
		 * Updates entire object to to only what is specified in args.
		 *
		 * @param  string $bucket Bucket Name.
		 * @param  string $object Object Name.
		 * @param  array  $args   https://cloud.google.com/storage/docs/json_api/v1/objects#resource i.e array( 'metadata' => array( "foo" => "bar" ) ).
		 * @return JSON           JSON response.
		 */
		public function update_object( string $bucket, string $object, $args = array() ) {
			$bucket = rawurlencode( $bucket );
			$object = rawurlencode( $object );
			return $this->build_request( "b/$bucket/o/$object", $args, 'PUT' )->fetch();
		}

		/**
		 * Patch Object.
		 *
		 * Only updates fields specified in args.
		 *
		 * @param  string $bucket Bucket Name.
		 * @param  string $object Object Name.
		 * @param  array  $args   https://cloud.google.com/storage/docs/json_api/v1/objects#resource i.e array( 'metadata' => array( "foo" => "bar" ) ).
		 * @return JSON           JSON response.
		 */
		public function patch_object( string $bucket, string $object, $args = array() ) {
			$bucket = rawurlencode( $bucket );
			$object = rawurlencode( $object );
			return $this->build_request( "b/$bucket/o/$object", $args, 'PATCH' )->fetch();
		}

		/**
		 * Generate Signed URL's V4 method
		 *
		 * @param string  $service_account_file JSON string of the service account file.
		 * @param string  $bucket_name          Name of the google storage bucket.
		 * @param string  $object_name          Object name aka the filepath.
		 * @param integer $expiration           Expiration time in seconds.
		 * @param string  $http_method          HTTP method for signed URL.
		 * @return string|WP_Error
		 */
		public static function generate_signed_urlv4( $service_account_file, $bucket_name, $object_name, $expiration = 604800, $http_method = 'GET' ) {

			// Max expiration time is 7 days.
			if ( $expiration > 604800 ) {
				return new WP_Error( 'invalid-expiration', 'Expiration Time can\'t be longer than 604800 seconds (7 days).' );
			}

			// Check if service account file is valid.
			$service_account = self::is_json_valid( $service_account_file );
			if ( is_wp_error( $service_account ) ) {
				return $service_account;
			}

			$active_time         = gmdate( 'Ymd\THis\Z' );
			$escaped_object_name = rawurlencode( $object_name );

			// Prepare Canonical Query String.
			$resource_url     = 'https://storage.googleapis.com/' . $bucket_name . '/' . $escaped_object_name;
			$credential_scope = gmdate( 'Ymd' ) . '/auto/storage/goog4_request';
			$canonical_args   = array(
				'X-Goog-Algorithm'     => 'GOOG4-RSA-SHA256',
				'X-Goog-Credential'    => rawurlencode( $service_account->client_email . '/' . $credential_scope ),
				'X-Goog-Date'          => $active_time,
				'X-Goog-Expires'       => $expiration,
				'X-Goog-SignedHeaders' => 'host',
			);
			$canonical_url    = add_query_arg( array_filter( $canonical_args ), $resource_url );

			// Prepare the string to sign.
			$canonical_request        = $http_method . "\n/" . $bucket_name . '/' . $escaped_object_name . "\n" . str_replace( $resource_url . '?', '', $canonical_url ) . "\nhost:storage.googleapis.com\n\nhost\nUNSIGNED-PAYLOAD";
			$hashed_canonical_request = hash( 'sha256', $canonical_request );
			$string_to_sign           = "GOOG4-RSA-SHA256\n" . $active_time . "\n" . $credential_scope . "\n" . $hashed_canonical_request;

			if ( openssl_sign( $string_to_sign, $signature, $service_account->private_key, OPENSSL_ALGO_SHA256 ) ) {
				$signature = bin2hex( $signature );
				return $canonical_url . '&X-Goog-Signature=' . $signature;
			}

			return new WP_Error( 'invalid-private-key', 'The URL could not be signed. Please check your private key in the service account' );
		}

		/**
		 * Generate Signed URL's V2 method
		 *
		 * @param string  $service_account_file JSON string of the service account file.
		 * @param string  $bucket_name          Name of the google storage bucket.
		 * @param string  $object_name          Object name aka the filepath.
		 * @param integer $expiration           Expiration time in seconds.
		 * @param string  $http_method          HTTP method for signed URL.
		 * @return string|WP_Error
		 */
		public static function generate_signed_urlv2( $service_account_file, $bucket_name, $object_name, $expiration = 604800, $http_method = 'GET' ) {

			if ( $expiration > 604800 ) {
				return new WP_Error( 'invalid-expiration', 'Expiration Time can\'t be longer than 604800 seconds (7 days).' );
			}

			$service_account = self::is_json_valid( $service_account_file );
			if ( is_wp_error( $service_account ) ) {
				return $service_account;
			}

			$expiry              = time() + $expiration;
			$escaped_object_name = rawurlencode( $object_name );
			$access_id           = rawurlencode( $service_account->client_email );
			$policy_string       = $http_method . "\n\n\n" . $expiry . "\n/" . $bucket_name . '/' . $escaped_object_name;

			if ( openssl_sign( $policy_string, $signature, $service_account->private_key, OPENSSL_ALGO_SHA256 ) ) {
				$signature = rawurlencode( base64_encode( $signature ) );
				return 'https://storage.googleapis.com/' . $bucket_name . '/' . $escaped_object_name . '?GoogleAccessId=' . $access_id . '&Expires=' . $expiry . '&Signature=' . $signature;
			}

			return new WP_Error( 'invalid-private-key', 'The URL could not be signed. Please check your private key in the service account' );
		}

		/**
		 * Is Service account JSON valid.
		 *
		 * @param  string $service_account_json Service account json string to be validated.
		 * @return Object|WP_Error
		 */
		private static function is_json_valid( $service_account_json ) {
			$service_account = json_decode( $service_account_json );

			if ( json_last_error() !== JSON_ERROR_NONE
				|| ! array_key_exists( 'private_key', $service_account )
				|| ! array_key_exists( 'client_email', $service_account )
				|| ! array_key_exists( 'token_uri', $service_account )
				|| ! array_key_exists( 'auth_uri', $service_account )
			) {
				return new WP_Error( 'invalid-service-account-json', __( 'Please verify that a valid service account json string is being used.', 'wp-google-auth-api' ) );
			}

			return $service_account;

		}

	}

}
