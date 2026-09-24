<?php
namespace LocatorPress\Admin;

// Direkten Zugriff verhindern.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use LocatorPress\Database\Installer;
use LocatorPress\Geocoding\Geocoding_Service;

/**
 * Controller-Klasse zur Steuerung aller Backend-Aktivitäten von Standorten.
 * Beinhaltet sichere Validierung, Berechtigungsprüfungen und CRUD-Aktionen.
 */
class Location_Controller {

	/**
	 * Singleton-Instanz.
	 *
	 * @var Location_Controller|null
	 */
	private static $instance = null;

	/**
	 * Holt die Singleton-Instanz.
	 *
	 * @return Location_Controller
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
		add_action( 'admin_init', [ $this, 'handle_actions' ] );
		add_action( 'wp_ajax_lp_admin_geocode', [ $this, 'ajax_admin_geocode' ] );
	}

	/**
	 * Verarbeitet den AJAX-Geocoding-Request für den Admin-Bereich.
	 */
	public function ajax_admin_geocode() {
		check_ajax_referer( 'lp_admin_ajax_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Keine Berechtigung.', 'locatorpress' ) ] );
		}

		$address = isset( $_POST['address'] ) ? sanitize_textarea_field( $_POST['address'] ) : '';

		if ( empty( $address ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Adresse ist leer.', 'locatorpress' ) ] );
		}

		$coords = Geocoding_Service::geocode( $address );

		if ( $coords ) {
			wp_send_json_success( $coords );
		} else {
			wp_send_json_error( [ 'message' => esc_html__( 'Geokodierung fehlgeschlagen.', 'locatorpress' ) ] );
		}
	}

	/**
	 * Verarbeitet POST-Aktionen (Speichern und Löschen) von Standorten.
	 */
	public function handle_actions() {
		if ( ! isset( $_POST['lp_location_action'] ) ) {
			return;
		}

		// Rechteprüfung.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sie haben keine Berechtigung für diese Aktion.', 'locatorpress' ) );
		}

		$action = sanitize_text_field( $_POST['lp_location_action'] );

		if ( 'save' === $action ) {
			// Nonce-Überprüfung.
			if ( ! isset( $_POST['lp_location_nonce'] ) || ! wp_verify_nonce( $_POST['lp_location_nonce'], 'lp_save_location' ) ) {
				wp_die( esc_html__( 'Ungültiger Nonce-Sicherheitsschlüssel.', 'locatorpress' ) );
			}

			$this->save_location( $_POST );
		} elseif ( 'delete' === $action ) {
			// Nonce-Überprüfung.
			$id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
			if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'lp_delete_location_' . $id ) ) {
				wp_die( esc_html__( 'Ungültiger Nonce-Sicherheitsschlüssel.', 'locatorpress' ) );
			}

			$this->delete_location( $id );
		}
	}

	/**
	 * Speichert oder aktualisiert einen Standort in der Datenbank.
	 *
	 * @param array $data Formulardaten ($_POST).
	 */
	private function save_location( $data ) {
		global $wpdb;

		$id        = isset( $data['id'] ) ? intval( $data['id'] ) : 0;
		$title     = sanitize_text_field( $data['title'] );
		$address   = sanitize_textarea_field( $data['address'] );
		$latitude  = ! empty( $data['latitude'] ) ? floatval( $data['latitude'] ) : null;
		$longitude = ! empty( $data['longitude'] ) ? floatval( $data['longitude'] ) : null;
		$phone     = sanitize_text_field( $data['phone'] );
		$email     = sanitize_email( $data['email'] );
		$website   = esc_url_raw( $data['website'] );
		$image_id  = isset( $data['image_id'] ) ? intval( $data['image_id'] ) : 0;
		$region_id = isset( $data['region_id'] ) ? intval( $data['region_id'] ) : 0;
		$status    = sanitize_text_field( $data['status'] );

		// Falls keine Koordinaten angegeben wurden, versuchen wir die Adresse zu geocodieren.
		if ( ( null === $latitude || null === $longitude ) && ! empty( $address ) ) {
			$coords = Geocoding_Service::geocode( $address );
			if ( $coords ) {
				$latitude  = $coords['lat'];
				$longitude = $coords['lng'];
			}
		}

		// Öffnungszeiten-Array in ein JSON-Objekt umwandeln (unterstützt mehrere Zeiträume pro Tag).
		$days          = [ 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' ];
		$opening_hours = [];

		foreach ( $days as $day ) {
			$status_day = isset( $data['opening_hours'][ $day ]['status'] ) ? sanitize_text_field( $data['opening_hours'][ $day ]['status'] ) : 'closed';

			$raw_slots = isset( $data['opening_hours'][ $day ]['slots'] ) ? $data['opening_hours'][ $day ]['slots'] : [];
			$slots     = [];

			if ( ! empty( $raw_slots ) ) {
				foreach ( $raw_slots as $slot ) {
					$open_t  = isset( $slot['open'] ) ? sanitize_text_field( $slot['open'] ) : '';
					$close_t = isset( $slot['close'] ) ? sanitize_text_field( $slot['close'] ) : '';
					if ( ! empty( $open_t ) && ! empty( $close_t ) ) {
						$slots[] = [ 'open' => $open_t, 'close' => $close_t ];
					}
				}
			}

			// Fallback: old format with open/close at root level.
			if ( empty( $slots ) && isset( $data['opening_hours'][ $day ]['open'] ) ) {
				$open_t  = sanitize_text_field( $data['opening_hours'][ $day ]['open'] );
				$close_t = sanitize_text_field( $data['opening_hours'][ $day ]['close'] );
				if ( $open_t && $close_t ) {
					$slots[] = [ 'open' => $open_t, 'close' => $close_t ];
				}
			}

			if ( empty( $slots ) ) {
				$slots = [ [ 'open' => '09:00', 'close' => '18:00' ] ];
			}

			$opening_hours[ $day ] = [
				'status' => $status_day,
				'slots'  => $slots,
			];
		}

		$hours_json = wp_json_encode( $opening_hours );

		$db_data = [
			'title'         => $title,
			'address'       => $address,
			'latitude'      => $latitude,
			'longitude'     => $longitude,
			'phone'         => $phone,
			'email'         => $email,
			'website'       => $website,
			'image_id'      => $image_id,
			'region_id'     => $region_id,
			'status'        => $status,
			'opening_hours' => $hours_json,
		];

		$table_name = Installer::get_locations_table();

		if ( $id > 0 ) {
			// Update.
			$wpdb->update( $table_name, $db_data, [ 'id' => $id ] );
			$redirect_url = admin_url( 'admin.php?page=locatorpress-locations&action=edit&id=' . $id . '&updated=true' );
			$saved_id     = $id;
		} else {
			// Einfügen.
			$wpdb->insert( $table_name, $db_data );
			$new_id       = $wpdb->insert_id;
			$redirect_url = admin_url( 'admin.php?page=locatorpress-locations&action=edit&id=' . $new_id . '&created=true' );
			$saved_id     = $new_id;
		}

		// Save custom fields hook
		do_action( 'locatorpress_save_location', $saved_id, $data );

		// Cache leeren bei Datenbank-Aktualisierung.
		Installer::clear_all_caches();

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Löscht einen Standort aus der Datenbank.
	 *
	 * @param int $id Die ID des Standortes.
	 */
	private function delete_location( $id ) {
		global $wpdb;

		// Delete custom fields hook
		do_action( 'locatorpress_delete_location', $id );

		$table_name = Installer::get_locations_table();
		$wpdb->delete( $table_name, [ 'id' => $id ] );

		// Cache leeren.
		Installer::clear_all_caches();

		wp_safe_redirect( admin_url( 'admin.php?page=locatorpress-locations&deleted=true' ) );
		exit;
	}

	/**
	 * Holt alle Standorte aus der Datenbank.
	 *
	 * @param array $args Filter- und Suchparameter.
	 * @return array
	 */
	public function get_locations( $args = [] ) {
		global $wpdb;

		$table_locations = Installer::get_locations_table();
		$table_regions   = Installer::get_regions_table();

		$defaults = [
			'search'   => '',
			'region'   => 0,
			'status'   => '',
			'orderby'  => 'title',
			'order'    => 'ASC',
			'limit'    => 20,
			'offset'   => 0,
		];

		$args = wp_parse_args( $args, $defaults );

		$query = "SELECT l.*, r.name as region_name 
				  FROM $table_locations l 
				  LEFT JOIN $table_regions r ON l.region_id = r.id 
				  WHERE 1=1";
		
		$sql_args = [];

		if ( ! empty( $args['search'] ) ) {
			$query      .= " AND (l.title LIKE %s OR l.address LIKE %s OR l.phone LIKE %s OR l.email LIKE %s)";
			$like_search = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$sql_args[]  = $like_search;
			$sql_args[]  = $like_search;
			$sql_args[]  = $like_search;
			$sql_args[]  = $like_search;
		}

		if ( ! empty( $args['region'] ) ) {
			$query      .= " AND l.region_id = %d";
			$sql_args[]  = intval( $args['region'] );
		}

		if ( ! empty( $args['status'] ) ) {
			$query      .= " AND l.status = %s";
			$sql_args[]  = sanitize_text_field( $args['status'] );
		}

		// Sortierung erlauben (Whitelist-basiert).
		$orderby = in_array( $args['orderby'], [ 'id', 'title', 'region_id', 'status', 'created_at' ], true ) ? $args['orderby'] : 'title';
		$order   = in_array( strtoupper( $args['order'] ), [ 'ASC', 'DESC' ], true ) ? $args['order'] : 'ASC';
		$query  .= " ORDER BY l.$orderby $order";

		// Pagination.
		$query      .= " LIMIT %d OFFSET %d";
		$sql_args[]  = intval( $args['limit'] );
		$sql_args[]  = intval( $args['offset'] );

		if ( ! empty( $sql_args ) ) {
			return $wpdb->get_results( $wpdb->prepare( $query, $sql_args ), ARRAY_A );
		}

		return $wpdb->get_results( $query, ARRAY_A );
	}

	/**
	 * Holt die Gesamtzahl der Standorte für Pagination-Zwecke.
	 *
	 * @param array $args
	 * @return int
	 */
	public function get_locations_count( $args = [] ) {
		global $wpdb;

		$table_locations = Installer::get_locations_table();
		$query           = "SELECT COUNT(id) FROM $table_locations WHERE 1=1";
		$sql_args        = [];

		if ( ! empty( $args['search'] ) ) {
			$query      .= " AND (title LIKE %s OR address LIKE %s)";
			$like_search = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$sql_args[]  = $like_search;
			$sql_args[]  = $like_search;
		}

		if ( ! empty( $args['region'] ) ) {
			$query      .= " AND region_id = %d";
			$sql_args[]  = intval( $args['region'] );
		}

		if ( ! empty( $args['status'] ) ) {
			$query      .= " AND status = %s";
			$sql_args[]  = sanitize_text_field( $args['status'] );
		}

		if ( ! empty( $sql_args ) ) {
			return intval( $wpdb->get_var( $wpdb->prepare( $query, $sql_args ) ) );
		}

		return intval( $wpdb->get_var( $query ) );
	}

	/**
	 * Liest eine einzelne Standort-Zeile aus.
	 *
	 * @param int $id
	 * @return array|false
	 */
	public function get_location( $id ) {
		global $wpdb;

		$table_name = Installer::get_locations_table();
		$row        = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $id ), ARRAY_A );

		if ( ! $row ) {
			return false;
		}

		// Öffnungszeiten-JSON wieder in ein Array umwandeln.
		if ( ! empty( $row['opening_hours'] ) ) {
			$row['opening_hours'] = is_string( $row['opening_hours'] ) ? json_decode( $row['opening_hours'], true ) : $row['opening_hours'];
		} else {
			$row['opening_hours'] = [];
		}

		return $row;
	}
}
