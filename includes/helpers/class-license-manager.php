<?php
/**
 * License Manager stub — kept for backward compatibility only.
 * This class is intentionally empty. License management has been removed.
 *
 * @package LocatorPress
 */

namespace LocatorPress\Helpers;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Empty stub to prevent autoloader errors on sites that may still
 * reference this class via cached autoload maps.
 *
 * @deprecated 1.0.0 Unused — kept as a safe stub only.
 */
class License_Manager {

	/**
	 * Singleton stub.
	 *
	 * @return License_Manager
	 */
	public static function get_instance() {
		static $instance = null;
		if ( null === $instance ) {
			$instance = new self();
		}
		return $instance;
	}

	private function __construct() {}
}
