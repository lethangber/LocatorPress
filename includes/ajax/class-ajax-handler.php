<?php
namespace LocatorPress\Ajax;

// Direkten Zugriff verhindern.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use LocatorPress\Database\Installer;
use LocatorPress\Geocoding\Geocoding_Service;
use LocatorPress\Helpers\Template_Loader;

/**
 * Klasse zur Verarbeitung aller Frontend-AJAX-Suchanfragen.
 * Verwendet die mathematische Haversine-Formel in optimierten SQL-Abfragen
 * für die Umkreissuche und einen transienten Cache zur Steigerung der Gesamtperformance.
 */
class Ajax_Handler {

	/**
	 * Singleton-Instanz.
	 *
	 * @var Ajax_Handler|null
	 */
	private static $instance = null;

	/**
	 * Holt die Singleton-Instanz.
	 *
	 * @return Ajax_Handler
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
		// AJAX-Hooks für angemeldete und unangemeldete Besucher.
		add_action( 'wp_ajax_lp_search_locations', [ $this, 'search_locations' ] );
		add_action( 'wp_ajax_nopriv_lp_search_locations', [ $this, 'search_locations' ] );
	}

	/**
	 * Führt die AJAX-Suche nach Standorten aus und liefert JSON-Ergebnisse.
	 */
	public function search_locations() {
		// Nonce-Sicherheit prüfen.
		check_ajax_referer( 'lp_frontend_ajax_nonce', 'security' );

		global $wpdb;

		// Parameter säubern.
		$keyword   = isset( $_POST['keyword'] ) ? sanitize_text_field( $_POST['keyword'] ) : '';
		$region_id = isset( $_POST['region'] ) ? intval( $_POST['region'] ) : 0;
		$radius    = isset( $_POST['radius'] ) ? floatval( $_POST['radius'] ) : 0;
		$user_lat  = isset( $_POST['lat'] ) ? floatval( $_POST['lat'] ) : null;
		$user_lng  = isset( $_POST['lng'] ) ? floatval( $_POST['lng'] ) : null;

		// Eindeutigen Cache-Schlüssel für diese spezifische Anfrage generieren (inklusive Locale).
		$locale    = apply_filters( 'plugin_locale', get_locale(), 'locatorpress' );
		$cache_key = 'lp_ajax_s_' . md5( $keyword . $region_id . $radius . $user_lat . $user_lng . $locale );
		$cache_duration = intval( get_option( 'lp_cache_duration', 3600 ) );

		if ( $cache_duration > 0 && ! current_user_can( 'manage_options' ) ) {
			$cached_results = get_transient( $cache_key );
			if ( false !== $cached_results ) {
				do_action( 'locatorpress_search_locations_searched', $cached_results, $_POST );
				wp_send_json_success( $cached_results );
			}
		}

		$table_locations = Installer::get_locations_table();
		$unit            = get_option( 'lp_radius_units', 'km' );
		$earth_radius    = 'miles' === $unit ? 3959 : 6371; // Erdradius in Meilen oder Kilometern.

		// Falls ein Stichwort eingegeben wurde, aber keine Koordinaten vorliegen (z. B. Adresssuche).
		if ( ! empty( $keyword ) && ( null === $user_lat || null === $user_lng ) ) {
			$coords = Geocoding_Service::geocode( $keyword );
			if ( $coords ) {
				$user_lat = $coords['lat'];
				$user_lng = $coords['lng'];
			}
		}

		$select_fields = "l.*";
		$select_fields = apply_filters( 'locatorpress_search_select_fields', $select_fields, $_POST );

		$join_sql = "";
		$join_sql = apply_filters( 'locatorpress_search_join_sql', $join_sql, $_POST );

		$having_clause = "";
		$sql_args      = [];
		$where_clauses = [ "l.status = 'active'" ];

		// Wenn Koordinaten vorliegen, berechnen wir die Entfernungen per Haversine-Formel.
		if ( null !== $user_lat && null !== $user_lng ) {
			$select_fields .= $wpdb->prepare(
				", ( %d * acos( cos( radians(%f) ) * cos( radians( l.latitude ) ) * cos( radians( l.longitude ) - radians(%f) ) + sin( radians(%f) ) * sin( radians( l.latitude ) ) ) ) AS distance",
				$earth_radius,
				$user_lat,
				$user_lng,
				$user_lat
			);

			if ( $radius > 0 ) {
				$having_clause = $wpdb->prepare( "HAVING distance <= %f", $radius );
			}
		}

		if ( $region_id > 0 ) {
			$where_clauses[] = $wpdb->prepare( "l.region_id = %d", $region_id );
		}

		// Falls keine Koordinaten ermittelt werden konnten, machen wir eine einfache Volltextsuche.
		if ( ( null === $user_lat || null === $user_lng ) && ! empty( $keyword ) ) {
			$where_clauses[] = $wpdb->prepare(
				"(l.title LIKE %s OR l.address LIKE %s)",
				'%' . $wpdb->esc_like( $keyword ) . '%',
				'%' . $wpdb->esc_like( $keyword ) . '%'
			);
		}

		$where_clauses = apply_filters( 'locatorpress_search_where_clauses', $where_clauses, $_POST );
		$where_sql = implode( ' AND ', $where_clauses );
		$order_sql = ( null !== $user_lat && null !== $user_lng ) ? "ORDER BY distance ASC" : "ORDER BY l.title ASC";

		$query = "SELECT $select_fields FROM $table_locations l $join_sql WHERE $where_sql $having_clause $order_sql";
		$locations = $wpdb->get_results( $query, ARRAY_A );
		$locations = apply_filters( 'locatorpress_search_results', $locations, $_POST );

		// Render HTML-Templates für die Standorte in der Seitenleiste.
		$html_cards = '';
		$parsed_locations = [];

		if ( ! empty( $locations ) ) {
			$index = 1;
			foreach ( $locations as $loc ) {
				$loc['index'] = $index;

				// Card-Template rendern.
				ob_start();
				Template_Loader::locate_template( 'location-card', [ 'location' => $loc, 'index' => $index ] );
				$html_cards .= ob_get_clean();

				// Marker-Popup-Inhalt vorrendern.
				ob_start();
				Template_Loader::locate_template( 'popup', [ 'location' => $loc ] );
				$popup_html = ob_get_clean();

				$loc['popup_html'] = $popup_html;
				$parsed_locations[] = $loc;
				$index++;
			}
		}

		$response_data = [
			'locations' => $parsed_locations,
			'html'      => $html_cards,
			'count'     => count( $locations ),
			'center'    => [
				'lat' => $user_lat,
				'lng' => $user_lng,
			],
		];

		// In Transient-Cache speichern, falls konfiguriert.
		if ( $cache_duration > 0 ) {
			set_transient( $cache_key, $response_data, $cache_duration );
		}

		do_action( 'locatorpress_search_locations_searched', $response_data, $_POST );

		wp_send_json_success( $response_data );
	}
}
