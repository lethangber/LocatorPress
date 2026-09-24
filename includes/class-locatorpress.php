<?php
namespace LocatorPress;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class (Singleton).
 * Loads all necessary components: Admin, Frontend, AJAX, REST API.
 *
 * @package LocatorPress
 */
class LocatorPress {

	/**
	 * Singleton instance.
	 *
	 * @var LocatorPress|null
	 */
	private static $instance = null;

	/**
	 * Returns the single instance of this class.
	 *
	 * @return LocatorPress
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor to enforce Singleton pattern.
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
	}

	/**
	 * Load all internal system files and core components.
	 */
	private function load_dependencies() {
		// All classes are loaded automatically via the PSR-4 autoloader on demand.
	}

	/**
	 * Register all WordPress hooks (Admin and Frontend).
	 */
	private function init_hooks() {
		// Initialize multilingual support.
		\LocatorPress\Helpers\Language_Loader::get_instance();

		// Initialize modules based on context.
		if ( is_admin() ) {
			$this->init_admin();
		}

		// AJAX handlers.
		$this->init_ajax();

		// REST API endpoints.
		$this->init_api();

		// Frontend shortcodes & hooks.
		$this->init_frontend();
	}

	/**
	 * Initialize the admin area components.
	 */
	private function init_admin() {
		\LocatorPress\Admin\Admin_Menu::get_instance();
		\LocatorPress\Admin\Location_Controller::get_instance();
		\LocatorPress\Admin\Region_Controller::get_instance();
		\LocatorPress\Admin\Settings_Controller::get_instance();
		\LocatorPress\Admin\Csv_Processor::get_instance();
	}

	/**
	 * Initialize AJAX handlers for frontend and admin.
	 */
	private function init_ajax() {
		\LocatorPress\Ajax\Ajax_Handler::get_instance();
	}

	/**
	 * Initialize custom WordPress REST API v1 endpoints.
	 */
	private function init_api() {
		\LocatorPress\Api\Rest_Api::get_instance();
	}

	/**
	 * Initialize shortcodes, styles and scripts for the frontend.
	 */
	private function init_frontend() {
		\LocatorPress\Frontend\Shortcode::get_instance();
		\LocatorPress\Helpers\Style_Manager::get_instance();
	}
}
