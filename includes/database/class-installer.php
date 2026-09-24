<?php
namespace LocatorPress\Database;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Installer class for creating and upgrading database tables.
 * Uses standard WordPress methods for safe database operations.
 *
 * @package LocatorPress
 */
class Installer {

	/**
	 * Returns the locations table name with WordPress prefix.
	 *
	 * @return string
	 */
	public static function get_locations_table() {
		global $wpdb;
		return $wpdb->prefix . 'lp_locations';
	}

	/**
	 * Returns the regions table name with WordPress prefix.
	 *
	 * @return string
	 */
	public static function get_regions_table() {
		global $wpdb;
		return $wpdb->prefix . 'lp_regions';
	}

	/**
	 * Called on plugin activation.
	 * Creates the custom tables and default indexes.
	 */
	public static function activate() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$table_locations = self::get_locations_table();
		$table_regions   = self::get_regions_table();

		// Regions table.
		$sql_regions = "CREATE TABLE $table_regions (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			slug varchar(255) NOT NULL,
			parent_id bigint(20) unsigned NOT NULL DEFAULT 0,
			description text NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY slug (slug),
			KEY parent_id (parent_id)
		) $charset_collate;";

		// Locations table.
		$sql_locations = "CREATE TABLE $table_locations (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			title varchar(255) NOT NULL,
			address text NOT NULL,
			latitude decimal(10, 8) NULL,
			longitude decimal(11, 8) NULL,
			phone varchar(50) NULL,
			email varchar(100) NULL,
			website varchar(255) NULL,
			image_id bigint(20) unsigned NULL DEFAULT 0,
			region_id bigint(20) unsigned NULL DEFAULT 0,
			status varchar(20) NOT NULL DEFAULT 'active',
			opening_hours text NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY latitude (latitude),
			KEY longitude (longitude),
			KEY region_id (region_id),
			KEY status (status)
		) $charset_collate;";

		// Use WordPress dbDelta to safely create or update tables.
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql_regions );
		dbDelta( $sql_locations );

		// Set default plugin options if not already set.
		self::initialize_options();
	}

	/**
	 * Sets default plugin options in the WordPress options table.
	 */
	private static function initialize_options() {
		add_option( 'lp_default_map_provider', 'leaflet' );
		add_option( 'lp_google_maps_api_key', '' );
		add_option( 'lp_goong_maps_api_key', '' );
		add_option( 'lp_goong_geocoding_api_key', '' );
		add_option( 'lp_default_zoom', 12 );
		add_option( 'lp_radius_units', 'km' );
		add_option( 'lp_default_center_lat', '48.135125' );
		add_option( 'lp_default_center_lng', '11.581981' );

		// Marker settings.
		add_option( 'lp_marker_type', 'custom_svg' );
		add_option( 'lp_marker_fa_icon', 'fa-solid fa-location-dot' );
		add_option( 'lp_marker_fa_color', '#2563eb' );
		add_option( 'lp_marker_fa_bg_color', '#ffffff' );
		add_option( 'lp_marker_icon', '' );

		// Features.
		add_option( 'lp_enable_clustering', 1 );
		add_option( 'lp_enable_near_me', 1 );
		add_option( 'lp_cache_duration', 3600 );
		add_option( 'lp_geocoding_provider', 'openstreetmap' );
		add_option( 'lp_locale_override', '' );

		// Appearance.
		add_option( 'lp_enable_auto_color', 0 );
		add_option( 'lp_primary_color', '#2563eb' );
		add_option( 'lp_secondary_color', '#0f172a' );
		add_option( 'lp_button_color', '#2563eb' );
		add_option( 'lp_text_color', '#1a202c' );
		add_option( 'lp_border_radius', '12px' );
		add_option( 'lp_card_shadow', '0 4px 6px -1px rgba(0, 0, 0, 0.05)' );
		add_option( 'lp_color_mode', 'light' );
		add_option( 'lp_map_style', 'standard' );

		// Display settings.
		add_option( 'lp_show_featured_image', 1 );
		add_option( 'lp_show_address', 1 );
		add_option( 'lp_show_phone', 1 );
		add_option( 'lp_show_email', 1 );
		add_option( 'lp_show_website', 1 );
		add_option( 'lp_show_opening_hours', 1 );
	}

	/**
	 * Called on plugin deactivation.
	 * Flushes transient caches but does not delete data.
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * Deletes all transient search caches and the global query cache.
	 */
	public static function clear_all_caches() {
		global $wpdb;
		$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_lp_ajax_s_%' OR option_name LIKE '_transient_timeout_lp_ajax_s_%'" );
		$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_lp_styles_cache_%' OR option_name LIKE '_transient_timeout_lp_styles_cache_%'" );
		delete_transient( 'lp_locations_query_cache' );
	}

	/**
	 * Called when the plugin is deleted from WordPress.
	 * Removes all custom tables and stored plugin options completely.
	 */
	public static function uninstall() {
		global $wpdb;

		$table_locations = self::get_locations_table();
		$table_regions   = self::get_regions_table();

		// Drop tables.
		$wpdb->query( "DROP TABLE IF EXISTS $table_locations;" );
		$wpdb->query( "DROP TABLE IF EXISTS $table_regions;" );

		// Delete all plugin options from wp_options.
		$options = [
			'lp_default_map_provider',
			'lp_google_maps_api_key',
			'lp_goong_maps_api_key',
			'lp_goong_geocoding_api_key',
			'lp_default_zoom',
			'lp_radius_units',
			'lp_default_center_lat',
			'lp_default_center_lng',
			'lp_marker_type',
			'lp_marker_fa_icon',
			'lp_marker_fa_color',
			'lp_marker_fa_bg_color',
			'lp_marker_icon',
			'lp_enable_clustering',
			'lp_enable_near_me',
			'lp_cache_duration',
			'lp_geocoding_provider',
			'lp_locale_override',
			'lp_enable_auto_color',
			'lp_primary_color',
			'lp_secondary_color',
			'lp_button_color',
			'lp_text_color',
			'lp_border_radius',
			'lp_card_shadow',
			'lp_color_mode',
			'lp_map_style',
			'lp_show_featured_image',
			'lp_show_address',
			'lp_show_phone',
			'lp_show_email',
			'lp_show_website',
			'lp_show_opening_hours',
		];

		foreach ( $options as $option ) {
			delete_option( $option );
		}
	}
}
