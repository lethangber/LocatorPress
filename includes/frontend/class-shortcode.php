<?php
namespace LocatorPress\Frontend;

// Direkten Zugriff verhindern.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use LocatorPress\Helpers\Template_Loader;
use LocatorPress\Providers\Leaflet_Provider;

/**
 * Klasse zur Registrierung und Steuerung des [locatorpress] Shortcodes.
 * Sorgt für eine extrem performante, bedarfsgesteuerte Bereitstellung aller Scripts & Styles (Lazy Loading).
 */
class Shortcode {

	/**
	 * Singleton-Instanz.
	 *
	 * @var Shortcode|null
	 */
	private static $instance = null;

	/**
	 * Holt die Singleton-Instanz.
	 *
	 * @return Shortcode
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
		add_shortcode( 'locatorpress', [ $this, 'render_shortcode' ] );
	}

	/**
	 * Verarbeitet und rendert den Shortcode im Frontend.
	 *
	 * @param array $atts Shortcode-Attribute.
	 * @return string HTML-Ausgabe.
	 */
	public function render_shortcode( $atts ) {
		// Attribute definieren und filtern.
		$args = shortcode_atts( [
			'region'     => 0,
			'zoom'       => get_option( 'lp_default_zoom', 12 ),
			'height'     => '500px',
			'center_lat' => get_option( 'lp_default_center_lat', '48.135125' ),
			'center_lng' => get_option( 'lp_default_center_lng', '11.581981' ),
			'class'      => '',
		], $atts, 'locatorpress' );

		// 1. Assets für den ausgewählten Kartenprovider laden (Lazy Loading).
		$provider_id = apply_filters( 'locatorpress_active_map_provider', get_option( 'lp_default_map_provider', 'leaflet' ) );
		$provider    = null;

		if ( 'leaflet' === $provider_id ) {
			$provider = new Leaflet_Provider();
		}
		$provider = apply_filters( 'locatorpress_map_provider_instance', $provider, $provider_id );

		if ( $provider ) {
			$provider->enqueue_assets();
		}

		// 2. Eigene Frontend-Assets laden.
		$deps = [ 'jquery' ];
		if ( 'google' === $provider_id && wp_script_is( 'google-maps-api', 'enqueued' ) ) {
			$deps[] = 'google-maps-api';
		} elseif ( 'goong' === $provider_id && wp_script_is( 'goong-maps-js', 'enqueued' ) ) {
			$deps[] = 'goong-maps-js';
		}

		wp_enqueue_style( 'font-awesome-free', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', [], '6.4.0' );
		wp_enqueue_style( 'locatorpress-frontend-css', LOCATORPRESS_URL . 'assets/css/locatorpress.css', [], LOCATORPRESS_VERSION );
		wp_enqueue_script( 'locatorpress-frontend-js', LOCATORPRESS_URL . 'assets/js/locatorpress.js', $deps, LOCATORPRESS_VERSION, true );

		// 3. Einstellungen an JavaScript übergeben.
		$marker_id  = get_option( 'lp_marker_icon', 0 );
		$marker_url = $marker_id > 0 ? wp_get_attachment_url( $marker_id ) : LOCATORPRESS_URL . 'assets/images/default-marker.png';

		wp_localize_script( 'locatorpress-frontend-js', 'locatorPress', [
			'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
			'nonce'            => wp_create_nonce( 'lp_frontend_ajax_nonce' ),
			'provider'         => $provider_id,
			'radiusUnits'      => get_option( 'lp_radius_units', 'km' ),
			'enableClustering' => get_option( 'lp_enable_clustering', 1 ),
			'enableNearMe'     => get_option( 'lp_enable_near_me', 1 ),
			'markerType'       => get_option( 'lp_marker_type', 'fontawesome' ),
			'markerFaIcon'     => get_option( 'lp_marker_fa_icon', 'fa-solid fa-location-dot' ),
			'markerFaColor'    => get_option( 'lp_marker_fa_color', '#2563eb' ),
			'markerFaBgColor'  => get_option( 'lp_marker_fa_bg_color', '#ffffff' ),
			'markerIconUrl'    => esc_url( $marker_url ),
			'mapConfig'        => $provider ? $provider->get_js_config() : [],
			'strings'          => [
				'locationFound'         => esc_html__( 'Ihr Standort wurde gefunden.', 'locatorpress' ),
				'locationFailed'        => esc_html__( 'Standortbestimmung fehlgeschlagen.', 'locatorpress' ),
				'noResults'             => esc_html__( 'Keine Standorte in diesem Radius gefunden.', 'locatorpress' ),
				'openText'              => esc_html__( 'Geöffnet', 'locatorpress' ),
				'closedText'            => esc_html__( 'Geschlossen', 'locatorpress' ),
				'resultSingular'        => esc_html__( 'Standort gefunden', 'locatorpress' ),
				'resultPlural'          => esc_html__( 'Standorte gefunden', 'locatorpress' ),
				'resultZero'            => esc_html__( '0 Standorte', 'locatorpress' ),
				'within'                => esc_html__( 'Within', 'locatorpress' ),
			]
		] );

		// 4. Output-Buffering nutzen, um das Template sauber zu laden.
		ob_start();
		
		Template_Loader::locate_template( 'map-container', $args );

		return ob_get_clean();
	}
}
