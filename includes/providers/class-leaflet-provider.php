<?php
namespace LocatorPress\Providers;

// Direkten Zugriff verhindern.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Leaflet Karten-Provider-Implementierung.
 * Nutzt OpenStreetMap und erfordert keinen API-Schlüssel.
 */
class Leaflet_Provider extends Abstract_Map_Provider {

	/**
	 * @return string
	 */
	public function get_id() {
		return 'leaflet';
	}

	/**
	 * @return string
	 */
	public function get_name() {
		return esc_html__( 'Leaflet (OpenStreetMap - Keine API benötigt)', 'locatorpress' );
	}

	/**
	 * Enqueue Leaflet-spezifische Scripts und Styles aus CDN.
	 */
	public function enqueue_assets() {
		// Leaflet Core CSS & JS.
		wp_enqueue_style( 'leaflet-core', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], '1.9.4' );
		wp_enqueue_script( 'leaflet-core', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], '1.9.4', true );

		// Leaflet MarkerCluster, falls in den Einstellungen aktiviert.
		if ( get_option( 'lp_enable_clustering', 1 ) ) {
			wp_enqueue_style( 'leaflet-markercluster', 'https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css', [ 'leaflet-core' ], '1.4.1' );
			wp_enqueue_style( 'leaflet-markercluster-default', 'https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css', [ 'leaflet-core' ], '1.4.1' );
			wp_enqueue_script( 'leaflet-markercluster', 'https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js', [ 'leaflet-core' ], '1.4.1', true );
		}
	}

	public function get_js_config() {
		$style = get_option( 'lp_map_style', 'standard' );
		$color_mode = get_option( 'lp_color_mode', 'auto' );

		// Automatischer Wechsel zu Dark Tiles bei erzwungenem Darkmode.
		if ( 'dark' === $color_mode && 'standard' === $style ) {
			$style = 'dark';
		}

		$tile_url = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
		$attribution = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> Mitwirkende';

		if ( 'dark' === $style ) {
			$tile_url = 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png';
			$attribution = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/attributions">CARTO</a>';
		} elseif ( 'silver' === $style ) {
			$tile_url = 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png';
			$attribution = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/attributions">CARTO</a>';
		} elseif ( 'retro' === $style ) {
			$tile_url = 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png';
			$attribution = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/attributions">CARTO</a>';
		}

		return [
			'tileUrl'     => $tile_url,
			'attribution' => $attribution,
		];
	}
}
