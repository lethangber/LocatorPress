<?php
/**
 * LocatorPress — Uninstall Script
 *
 * Called when the user deletes the plugin from the WordPress admin.
 * Removes all plugin database tables and WordPress options completely.
 *
 * @package LocatorPress
 */

// Abort if this file is not called from WordPress uninstall.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Load the autoloader to access the Installer class.
require_once plugin_dir_path( __FILE__ ) . 'includes/helpers/class-autoload.php';
\LocatorPress\Helpers\Autoload::register();

// Remove all tables and options.
\LocatorPress\Database\Installer::uninstall();
