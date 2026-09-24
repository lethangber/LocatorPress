<?php
/**
 * Plugin Name:       LocatorPress
 * Plugin URI:        https://www.me-toolkit.com
 * Description:       A fast, free Store Locator for WordPress. Uses custom database tables for ultra-fast searches. Compatible with all page builders. No Google Maps API key required.
 * Version:           1.0.4
 * Author:            Me Toolkit
 * Author URI:        https://www.me-toolkit.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       locatorpress
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Tested up to:      6.7
 * Donate link:       https://paypal.me/thangme
 *
 * @package LocatorPress
 */

// Prevent direct access.
if (!defined('ABSPATH')) {
	exit;
}

// Define plugin constants.
define('LOCATORPRESS_VERSION', '1.0.4');
define('LOCATORPRESS_FILE', __FILE__);
define('LOCATORPRESS_PATH', plugin_dir_path(__FILE__));
define('LOCATORPRESS_URL', plugin_dir_url(__FILE__));

/**
 * PSR-4 Autoloader.
 * Loads all classes in the LocatorPress namespace automatically from the includes directory.
 */
require_once LOCATORPRESS_PATH . 'includes/helpers/class-autoload.php';
\LocatorPress\Helpers\Autoload::register();

/**
 * Plugin activation hook.
 * Creates the required database tables on activation.
 */
register_activation_hook(__FILE__, function () {
	\LocatorPress\Database\Installer::activate();
});

/**
 * Plugin deactivation hook.
 * Runs cleanup tasks on deactivation (no data deletion to prevent accidental data loss).
 */
register_deactivation_hook(__FILE__, function () {
	\LocatorPress\Database\Installer::deactivate();
});

/**
 * Initialize the plugin after all plugins are loaded.
 */
add_action('plugins_loaded', function () {
	\LocatorPress\LocatorPress::get_instance();
});
