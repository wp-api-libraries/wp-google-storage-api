
<?php
/**
 * WP Google Storage API
 *
 * @link hhttps://cloud.google.com/storage/docs/json_api/
 * @package WP-API-Libraries\WP-Google-Stroage-API
 */
/*
* Plugin Name: WP Google Storage API
* Plugin URI: https://github.com/wp-api-libraries/wp-google-storage-api
* Description: Perform API requests to Google Storage in WordPress.
* Author: WP API Libraries
* Version: 1.0.2
* Author URI: https://wp-api-libraries.com
* GitHub Plugin URI: https://github.com/wp-api-libraries/wp-google-storage-api
* GitHub Branch: master
*/
/* Exit if accessed directly. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* Check if class exists. */
if ( ! class_exists( 'GoogleStorageAPI' ) ) {
	
	/**
	 * GoogleStorageAPI Class.
	 */
	class GoogleStorageAPI {
		
		/**
		 * BaseAPI Endpoint
		 *
		 * @var string
		 * @access protected
		 */
		protected $base_uri = 'https://www.googleapis.com/storage/v1';

	}
}