
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
	
	
	/* BUCKET ACCESS CONTROLS. */
	
	/* BUCKETS. */
	
	/* CHANNELS. */
	
	public function stop_channel() {
		
	}
	
	/* DEFAULT OBJECT ACCESS CONTROLS. */
	
	/* NOTIFICATIONS. */
	
	public function delete_notification() {
		
	}
	
	public function get_notification() {
		
	}
	
	public function insert_notifications() {
		
	}
	
	public function list_notifications() {
		
	}
	
	/* OBJECT ACCESS CONTROLS. */
	
	public function delete_object_acl() {
		
	}
	
	public function get_object_acl() {
		
	}
	
	public function insert_object_acl() {
		
	}
	
	public function list_object_acl() {
		
	}
	
	public function patch_object_acl() {
		
	}
	
	public function update_object_acl() {
		
	}
	
	/* OBJECTS. */
	
	public function compose_object() {
		
	}
	
	public function copy_object() {
		
	}
	
	public function delete_object() {
		
	}
	
	public function get_object() {
		
	}
	
	public function get_object_iam_policy() {
		
	}
	
	public function insert_object() {
		
	}
	
	public function list_object() {
		
	}
	
	public function patch_object() {
		
	}
	
	public function rewrite_object() {
		
	}
	
	public function set_object_iam_policy() {
		
	}
	
	public function test_object_iam_permissions() {
		
	}
	
	public function update_object() {
		
	}
	
	public function watch_all_objects() {
		
	}
	
	/* PROJECTS SERVICE ACCOUNT. */
	
	/**
	 * Get the email address of this project's Google Cloud Storage service account.
	 * 
	 * @access public
	 * @param mixed $project_id Project ID.
	 * @return void
	 */
	public function get_project_service_account( $project_id ) {
		
	}
	
}