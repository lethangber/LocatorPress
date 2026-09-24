<?php
namespace LocatorPress\Admin;

// Direkten Zugriff verhindern.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use LocatorPress\Database\Installer;
use LocatorPress\Geocoding\Geocoding_Service;

/**
 * Klasse zur Verarbeitung von Import- und Exportaktionen im CSV-Format.
 * Nutzt asynchrone AJAX-Requests zur performanten Stapelverarbeitung.
 */
class Csv_Processor {

	/**
	 * Singleton-Instanz.
	 *
	 * @var Csv_Processor|null
	 */
	private static $instance = null;

	/**
	 * Holt die Singleton-Instanz.
	 *
	 * @return Csv_Processor
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
		// Hooks für den AJAX-basierten Stapelimport.
		add_action( 'wp_ajax_lp_import_csv_batch', [ $this, 'ajax_import_batch' ] );

		// Hook für den CSV-Export.
		add_action( 'admin_init', [ $this, 'handle_export_request' ] );
	}

	/**
	 * Verarbeitet den AJAX-Batch-Import von CSV-Daten.
	 */
	public function ajax_import_batch() {
		// Nonce- und Sicherheitsüberprüfung.
		check_ajax_referer( 'lp_admin_ajax_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Keine ausreichenden Berechtigungen.', 'locatorpress' ) ] );
		}

		$batch_json   = isset( $_POST['batch'] ) ? wp_unslash( $_POST['batch'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$auto_geocode = isset( $_POST['auto_geocode'] ) ? intval( $_POST['auto_geocode'] ) : 0;

		$rows = json_decode( $batch_json, true );

		if ( empty( $rows ) || ! is_array( $rows ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Keine Daten empfangen.', 'locatorpress' ) ] );
		}

		global $wpdb;
		$table_name = Installer::get_locations_table();
		$logs       = [];

		foreach ( $rows as $row ) {
			$title     = isset( $row['title'] ) ? sanitize_text_field( $row['title'] ) : '';
			$address   = isset( $row['address'] ) ? sanitize_textarea_field( $row['address'] ) : '';
			$latitude  = ! empty( $row['latitude'] ) ? floatval( $row['latitude'] ) : null;
			$longitude = ! empty( $row['longitude'] ) ? floatval( $row['longitude'] ) : null;
			$phone     = isset( $row['phone'] ) ? sanitize_text_field( $row['phone'] ) : '';
			$email     = isset( $row['email'] ) ? sanitize_email( $row['email'] ) : '';
			$website   = isset( $row['website'] ) ? esc_url_raw( $row['website'] ) : '';
			$region_id = isset( $row['region_id'] ) ? intval( $row['region_id'] ) : 0;
			$status    = isset( $row['status'] ) && 'inactive' === $row['status'] ? 'inactive' : 'active';

			if ( empty( $title ) || empty( $address ) ) {
				$logs[] = [
					'message' => esc_html__( 'Zeile übersprungen: Name oder Adresse fehlt.', 'locatorpress' ),
					'type'    => 'error',
				];
				continue;
			}

			$geocoded_status = '';

			// Fallback-Geocoding falls Koordinaten fehlen.
			if ( ( null === $latitude || null === $longitude ) && $auto_geocode && ! empty( $address ) ) {
				$coords = Geocoding_Service::geocode( $address );
				if ( $coords ) {
					$latitude        = $coords['lat'];
					$longitude       = $coords['lng'];
					$geocoded_status = ' ' . esc_html__( '(Erfolgreich geokodiert!)', 'locatorpress' );
				} else {
					$geocoded_status = ' ' . esc_html__( '(Geokodierung fehlgeschlagen!)', 'locatorpress' );
				}
			}

			// In die Datenbank einfügen.
			$db_data = [
				'title'     => $title,
				'address'   => $address,
				'latitude'  => $latitude,
				'longitude' => $longitude,
				'phone'     => $phone,
				'email'     => $email,
				'website'   => $website,
				'region_id' => $region_id,
				'status'    => $status,
			];

			$inserted = $wpdb->insert( $table_name, $db_data );

			if ( $inserted ) {
				$logs[] = [
					'message' => sprintf( esc_html__( 'Importiert: %s%s', 'locatorpress' ), $title, $geocoded_status ),
					'type'    => 'success',
				];
			} else {
				$logs[] = [
					'message' => sprintf( esc_html__( 'Fehler beim Speichern von: %s', 'locatorpress' ), $title ),
					'type'    => 'error',
				];
			}
		}

		// Cache löschen.
		Installer::clear_all_caches();

		wp_send_json_success( [ 'logs' => $logs ] );
	}

	/**
	 * Verarbeitet einen Export-Request. Sendet die CSV-Datei an den Browser.
	 */
	public function handle_export_request() {
		if ( ! isset( $_POST['lp_csv_export_action'] ) || 'export' !== $_POST['lp_csv_export_action'] ) {
			return;
		}

		// Rechteprüfung.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Keine Berechtigung.', 'locatorpress' ) );
		}

		// Nonce-Überprüfung.
		if ( ! isset( $_POST['lp_csv_nonce'] ) || ! wp_verify_nonce( $_POST['lp_csv_nonce'], 'lp_csv_export_nonce' ) ) {
			wp_die( esc_html__( 'Ungültiger Nonce-Sicherheitsschlüssel.', 'locatorpress' ) );
		}

		global $wpdb;
		$table_name = Installer::get_locations_table();
		$results    = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY title ASC", ARRAY_A );

		// Dateiname für den Download.
		$filename = 'locatorpress-export-' . date( 'Y-m-d-H-i' ) . '.csv';

		// HTTP-Header für Datei-Download setzen.
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		// Ausgabe-Stream öffnen.
		$output = fopen( 'php://output', 'w' );

		// UTF-8 BOM für Excel-Kompatibilität schreiben.
		fwrite( $output, "\xEF\xBB\xBF" );

		// CSV Kopfzeile ausgeben.
		fputcsv( $output, [
			'title',
			'address',
			'latitude',
			'longitude',
			'phone',
			'email',
			'website',
			'region_id',
			'status',
		], ';' );

		// Daten ausgeben.
		if ( ! empty( $results ) ) {
			foreach ( $results as $row ) {
				fputcsv( $output, [
					$row['title'],
					$row['address'],
					$row['latitude'],
					$row['longitude'],
					$row['phone'],
					$row['email'],
					$row['website'],
					$row['region_id'],
					$row['status'],
				], ';' );
			}
		}

		fclose( $output );
		exit;
	}
}
