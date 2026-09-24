<?php
namespace LocatorPress\Admin;

// Direkten Zugriff verhindern.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use LocatorPress\Database\Installer;

/**
 * Controller-Klasse zur Verwaltung aller globalen Plugin-Einstellungen.
 * Speichert API-Keys, Kartenanbieter und standardmäßige Kartenausschnitte sicher ab.
 */
class Settings_Controller {

	/**
	 * Singleton-Instanz.
	 *
	 * @var Settings_Controller|null
	 */
	private static $instance = null;

	/**
	 * Holt die Singleton-Instanz.
	 *
	 * @return Settings_Controller
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Konstruktor.
	 */
	private function __construct() {
		add_action( 'admin_init', [ $this, 'save_settings' ] );
	}

	/**
	 * Verarbeitet das Absenden des Einstellungsformulars im WordPress Admin.
	 */
	public function save_settings() {
		if ( ! isset( $_POST['lp_settings_action'] ) || 'save_settings' !== $_POST['lp_settings_action'] ) {
			return;
		}

		// Berechtigungsprüfung.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sie haben keine Berechtigung für diese Aktion.', 'locatorpress' ) );
		}

		// Nonce-Sicherheitsüberprüfung.
		if ( ! isset( $_POST['lp_settings_nonce'] ) || ! wp_verify_nonce( $_POST['lp_settings_nonce'], 'lp_save_settings_data' ) ) {
			wp_die( esc_html__( 'Ungültiger Nonce-Sicherheitsschlüssel.', 'locatorpress' ) );
		}

		// Optionen validieren und säubern.
		$default_map_provider = sanitize_text_field( $_POST['lp_default_map_provider'] );
		$google_maps_api_key  = sanitize_text_field( $_POST['lp_google_maps_api_key'] );
		$goong_maps_api_key   = sanitize_text_field( $_POST['lp_goong_maps_api_key'] );
		$goong_geo_api_key    = sanitize_text_field( $_POST['lp_goong_geocoding_api_key'] );
		$default_zoom         = intval( $_POST['lp_default_zoom'] );
		$radius_units         = in_array( $_POST['lp_radius_units'], [ 'km', 'miles' ], true ) ? $_POST['lp_radius_units'] : 'km';
		$default_center_lat   = sanitize_text_field( $_POST['lp_default_center_lat'] );
		$default_center_lng   = sanitize_text_field( $_POST['lp_default_center_lng'] );
		$marker_type          = isset( $_POST['lp_marker_type'] ) ? sanitize_text_field( $_POST['lp_marker_type'] ) : 'custom_svg';
		$marker_fa_icon       = isset( $_POST['lp_marker_fa_icon'] ) ? sanitize_text_field( $_POST['lp_marker_fa_icon'] ) : '';
		$marker_fa_color      = sanitize_hex_color( $_POST['lp_marker_fa_color'] );
		$marker_fa_bg_color   = sanitize_hex_color( $_POST['lp_marker_fa_bg_color'] );
		$marker_icon          = isset( $_POST['lp_marker_icon'] ) ? intval( $_POST['lp_marker_icon'] ) : 0;
		
		$enable_clustering    = isset( $_POST['lp_enable_clustering'] ) ? 1 : 0;
		$enable_near_me       = isset( $_POST['lp_enable_near_me'] ) ? 1 : 0;
		$cache_duration       = intval( $_POST['lp_cache_duration'] );
		$geocoding_provider   = sanitize_text_field( $_POST['lp_geocoding_provider'] );
		$locale_override      = sanitize_text_field( $_POST['lp_locale_override'] );
		
		// Aussehen und Design speichern.
		$enable_auto_color    = 0;
		$primary_color        = sanitize_hex_color( $_POST['lp_primary_color'] );
		$secondary_color      = sanitize_hex_color( $_POST['lp_secondary_color'] );
		$button_color         = sanitize_hex_color( $_POST['lp_button_color'] );
		$text_color           = sanitize_hex_color( $_POST['lp_text_color'] );
		$border_radius        = sanitize_text_field( $_POST['lp_border_radius'] );
		$card_shadow          = sanitize_text_field( $_POST['lp_card_shadow'] );
		$color_mode           = in_array( $_POST['lp_color_mode'], [ 'auto', 'light', 'dark' ], true ) ? $_POST['lp_color_mode'] : 'light';
		$map_style            = in_array( $_POST['lp_map_style'], [ 'standard', 'dark', 'silver', 'retro' ], true ) ? $_POST['lp_map_style'] : 'standard';

		// Display settings.
		$show_featured_image  = isset( $_POST['lp_show_featured_image'] ) ? 1 : 0;
		$show_address         = isset( $_POST['lp_show_address'] ) ? 1 : 0;
		$show_phone           = isset( $_POST['lp_show_phone'] ) ? 1 : 0;
		$show_email           = isset( $_POST['lp_show_email'] ) ? 1 : 0;
		$show_website         = isset( $_POST['lp_show_website'] ) ? 1 : 0;
		$show_opening_hours   = isset( $_POST['lp_show_opening_hours'] ) ? 1 : 0;

		// In der WordPress-Optionstabelle abspeichern.
		update_option( 'lp_default_map_provider', $default_map_provider );
		update_option( 'lp_google_maps_api_key', $google_maps_api_key );
		update_option( 'lp_goong_maps_api_key', $goong_maps_api_key );
		update_option( 'lp_goong_geocoding_api_key', $goong_geo_api_key );
		update_option( 'lp_default_zoom', $default_zoom );
		update_option( 'lp_radius_units', $radius_units );
		update_option( 'lp_default_center_lat', $default_center_lat );
		update_option( 'lp_default_center_lng', $default_center_lng );
		
		update_option( 'lp_marker_type', $marker_type );
		update_option( 'lp_marker_fa_icon', $marker_fa_icon );
		update_option( 'lp_marker_fa_color', $marker_fa_color );
		update_option( 'lp_marker_fa_bg_color', $marker_fa_bg_color );
		update_option( 'lp_marker_icon', $marker_icon );
		
		update_option( 'lp_enable_clustering', $enable_clustering );
		update_option( 'lp_enable_near_me', $enable_near_me );
		update_option( 'lp_cache_duration', $cache_duration );
		update_option( 'lp_geocoding_provider', $geocoding_provider );
		update_option( 'lp_locale_override', $locale_override );
		update_option( 'lp_enable_auto_color', $enable_auto_color );
		update_option( 'lp_primary_color', $primary_color );
		update_option( 'lp_secondary_color', $secondary_color );
		update_option( 'lp_button_color', $button_color );
		update_option( 'lp_text_color', $text_color );
		update_option( 'lp_border_radius', $border_radius );
		update_option( 'lp_card_shadow', $card_shadow );
		update_option( 'lp_color_mode', $color_mode );
		update_option( 'lp_map_style', $map_style );
		update_option( 'lp_show_featured_image', $show_featured_image );
		update_option( 'lp_show_address', $show_address );
		update_option( 'lp_show_phone', $show_phone );
		update_option( 'lp_show_email', $show_email );
		update_option( 'lp_show_website', $show_website );
		update_option( 'lp_show_opening_hours', $show_opening_hours );

		// Gesamten Query Cache nach Änderung löschen.
		Installer::clear_all_caches();

		// Erfolgreiche Speicherung signalisieren durch Weiterleitung.
		wp_safe_redirect( admin_url( 'admin.php?page=locatorpress-settings&settings-updated=true' ) );
		exit;
	}
}
