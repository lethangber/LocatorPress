<?php
namespace LocatorPress\Api;

// Direkten Zugriff verhindern.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use LocatorPress\Database\Installer;

/**
 * Registriert und steuert alle benutzerdefinierten WordPress REST-API Endpunkte.
 * Pfad: /wp-json/locatorpress/v1/...
 */
class Rest_Api {

	/**
	 * Singleton-Instanz.
	 *
	 * @var Rest_Api|null
	 */
	private static $instance = null;

	/**
	 * Namespace der API.
	 */
	private $namespace = 'locatorpress/v1';

	/**
	 * Holt die Singleton-Instanz.
	 *
	 * @return Rest_Api
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
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Registriert alle Endpunkte bei WordPress.
	 */
	public function register_routes() {
		// GET /wp-json/locatorpress/v1/locations
		register_rest_route( $this->namespace, '/locations', [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [ $this, 'get_locations' ],
			'permission_callback' => '__return_true',
		] );

		// GET /wp-json/locatorpress/v1/locations/{id}
		register_rest_route( $this->namespace, '/locations/(?P<id>\d+)', [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [ $this, 'get_location' ],
			'permission_callback' => '__return_true',
		] );

		// GET /wp-json/locatorpress/v1/regions
		register_rest_route( $this->namespace, '/regions', [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [ $this, 'get_regions' ],
			'permission_callback' => '__return_true',
		] );

		// GET /wp-json/locatorpress/v1/regions/{id}
		register_rest_route( $this->namespace, '/regions/(?P<id>\d+)', [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [ $this, 'get_region' ],
			'permission_callback' => '__return_true',
		] );
	}

	/**
	 * Holt eine Liste von Standorten mit optionaler Filterung (Radius, Region, Pagination).
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function get_locations( WP_REST_Request $request ) {
		global $wpdb;

		$lat       = $request->get_param( 'lat' ) ? floatval( $request->get_param( 'lat' ) ) : null;
		$lng       = $request->get_param( 'lng' ) ? floatval( $request->get_param( 'lng' ) ) : null;
		$radius    = $request->get_param( 'radius' ) ? floatval( $request->get_param( 'radius' ) ) : 0;
		$region_id = $request->get_param( 'region' ) ? intval( $request->get_param( 'region' ) ) : 0;
		
		$page      = $request->get_param( 'page' ) ? intval( $request->get_param( 'page' ) ) : 1;
		$per_page  = $request->get_param( 'per_page' ) ? intval( $request->get_param( 'per_page' ) ) : 10;
		$offset    = ( $page - 1 ) * $per_page;

		$table_name   = Installer::get_locations_table();
		$unit         = get_option( 'lp_radius_units', 'km' );
		$earth_radius = 'miles' === $unit ? 3959 : 6371;

		$select_fields = "*";
		$having_clause = "";
		$where_clauses = [ "status = 'active'" ];

		if ( null !== $lat && null !== $lng ) {
			$select_fields .= $wpdb->prepare(
				", ( %d * acos( cos( radians(%f) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(%f) ) + sin( radians(%f) ) * sin( radians( latitude ) ) ) ) AS distance",
				$earth_radius,
				$lat,
				$lng,
				$lat
			);

			if ( $radius > 0 ) {
				$having_clause = $wpdb->prepare( "HAVING distance <= %f", $radius );
			}
		}

		if ( $region_id > 0 ) {
			$where_clauses[] = $wpdb->prepare( "region_id = %d", $region_id );
		}

		$where_sql = implode( ' AND ', $where_clauses );
		$order_sql = ( null !== $lat && null !== $lng ) ? "ORDER BY distance ASC" : "ORDER BY title ASC";

		// Pagination Limits
		$limit_sql = $wpdb->prepare( "LIMIT %d OFFSET %d", $per_page, $offset );

		$query = "SELECT $select_fields FROM $table_name WHERE $where_sql $having_clause $order_sql $limit_sql";
		$results = $wpdb->get_results( $query, ARRAY_A );

		// Gesamtanzahl für Paging Meta-Header abfragen.
		$count_query = "SELECT COUNT(id) FROM $table_name WHERE $where_sql";
		$total_items = intval( $wpdb->get_var( $count_query ) );

		// JSON-Öffnungszeiten parsen.
		if ( ! empty( $results ) ) {
			foreach ( $results as &$row ) {
				if ( ! empty( $row['opening_hours'] ) ) {
					$row['opening_hours'] = is_string( $row['opening_hours'] ) ? json_decode( $row['opening_hours'], true ) : $row['opening_hours'];
				}
			}
		}

		$response = new WP_REST_Response( $results, 200 );
		$response->header( 'X-WP-Total', $total_items );
		$response->header( 'X-WP-TotalPages', ceil( $total_items / $per_page ) );

		return $response;
	}

	/**
	 * Holt einen einzelnen Standort anhand seiner ID.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function get_location( WP_REST_Request $request ) {
		global $wpdb;

		$id         = intval( $request->get_param( 'id' ) );
		$table_name = Installer::get_locations_table();

		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d AND status = 'active'", $id ), ARRAY_A );

		if ( ! $row ) {
			return new WP_REST_Response( [ 'message' => esc_html__( 'Standort nicht gefunden.', 'locatorpress' ) ], 404 );
		}

		if ( ! empty( $row['opening_hours'] ) ) {
			$row['opening_hours'] = is_string( $row['opening_hours'] ) ? json_decode( $row['opening_hours'], true ) : $row['opening_hours'];
		}

		return new WP_REST_Response( $row, 200 );
	}

	/**
	 * Holt alle Regionen.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function get_regions( WP_REST_Request $request ) {
		global $wpdb;

		$table_name = Installer::get_regions_table();
		$results    = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY name ASC", ARRAY_A );

		return new WP_REST_Response( $results, 200 );
	}

	/**
	 * Holt eine einzelne Region anhand ihrer ID.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function get_region( WP_REST_Request $request ) {
		global $wpdb;

		$id         = intval( $request->get_param( 'id' ) );
		$table_name = Installer::get_regions_table();

		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $id ), ARRAY_A );

		if ( ! $row ) {
			return new WP_REST_Response( [ 'message' => esc_html__( 'Region nicht gefunden.', 'locatorpress' ) ], 404 );
		}

		return new WP_REST_Response( $row, 200 );
	}
}
