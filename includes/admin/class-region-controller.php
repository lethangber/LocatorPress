<?php
namespace LocatorPress\Admin;

// Direkten Zugriff verhindern.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use LocatorPress\Database\Installer;

/**
 * Controller-Klasse zur Verwaltung der hierarchischen Regionenstruktur.
 * Unterstützt unbegrenzte Verschachtelungen (Country -> State -> City -> District).
 */
class Region_Controller {

	/**
	 * Singleton-Instanz.
	 *
	 * @var Region_Controller|null
	 */
	private static $instance = null;

	/**
	 * Holt die Singleton-Instanz.
	 *
	 * @return Region_Controller
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
	}

	/**
	 * Verarbeitet POST-Aktionen (Speichern und Löschen) von Regionen.
	 */
	public function handle_actions() {
		if ( ! isset( $_POST['lp_region_action'] ) ) {
			return;
		}

		// Rechteprüfung.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sie haben keine Berechtigung für diese Aktion.', 'locatorpress' ) );
		}

		$action = sanitize_text_field( $_POST['lp_region_action'] );

		if ( 'save' === $action ) {
			// Nonce-Überprüfung.
			if ( ! isset( $_POST['lp_region_nonce'] ) || ! wp_verify_nonce( $_POST['lp_region_nonce'], 'lp_save_region' ) ) {
				wp_die( esc_html__( 'Ungültiger Nonce-Sicherheitsschlüssel.', 'locatorpress' ) );
			}

			$this->save_region( $_POST );
		} elseif ( 'delete' === $action ) {
			// Nonce-Überprüfung.
			$id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
			if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'lp_delete_region_' . $id ) ) {
				wp_die( esc_html__( 'Ungültiger Nonce-Sicherheitsschlüssel.', 'locatorpress' ) );
			}

			$this->delete_region( $id );
		}
	}

	/**
	 * Speichert oder aktualisiert eine Region in der Datenbank.
	 *
	 * @param array $data Formulardaten ($_POST).
	 */
	private function save_region( $data ) {
		global $wpdb;

		$id          = isset( $data['id'] ) ? intval( $data['id'] ) : 0;
		$name        = sanitize_text_field( $data['name'] );
		$slug        = ! empty( $data['slug'] ) ? sanitize_title( $data['slug'] ) : sanitize_title( $name );
		$parent_id   = isset( $data['parent_id'] ) ? intval( $data['parent_id'] ) : 0;
		$description = sanitize_textarea_field( $data['description'] );

		// Verhindern, dass eine Region ihr eigener Elternteil wird.
		if ( $id > 0 && $id === $parent_id ) {
			$parent_id = 0;
		}

		$db_data = [
			'name'        => $name,
			'slug'        => $slug,
			'parent_id'   => $parent_id,
			'description' => $description,
		];

		$table_name = Installer::get_regions_table();

		if ( $id > 0 ) {
			// Region aktualisieren.
			$wpdb->update( $table_name, $db_data, [ 'id' => $id ] );
			$redirect_url = admin_url( 'admin.php?page=locatorpress-regions&action=edit&id=' . $id . '&updated=true' );
		} else {
			// Neue Region einfügen.
			$wpdb->insert( $table_name, $db_data );
			$redirect_url = admin_url( 'admin.php?page=locatorpress-regions&created=true' );
		}

		// Query-Caches leeren.
		Installer::clear_all_caches();

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Löscht eine Region. Falls sie gelöscht wird, werden Unterregionen auf parent_id = 0 gesetzt.
	 *
	 * @param int $id Die ID der Region.
	 */
	private function delete_region( $id ) {
		global $wpdb;

		$table_name = Installer::get_regions_table();

		// Unterregionen updaten (parent_id auf 0 setzen).
		$wpdb->update( $table_name, [ 'parent_id' => 0 ], [ 'parent_id' => $id ] );

		// Region löschen.
		$wpdb->delete( $table_name, [ 'id' => $id ] );

		// Standorte in dieser Region auf region_id = 0 setzen.
		$table_locations = Installer::get_locations_table();
		$wpdb->update( $table_locations, [ 'region_id' => 0 ], [ 'region_id' => $id ] );

		// Query-Caches leeren.
		Installer::clear_all_caches();

		wp_safe_redirect( admin_url( 'admin.php?page=locatorpress-regions&deleted=true' ) );
		exit;
	}

	/**
	 * Holt alle Regionen.
	 *
	 * @return array
	 */
	public function get_regions() {
		global $wpdb;

		$table_name = Installer::get_regions_table();
		return $wpdb->get_results( "SELECT * FROM $table_name ORDER BY name ASC", ARRAY_A );
	}

	/**
	 * Holt eine einzelne Region.
	 *
	 * @param int $id
	 * @return array|false
	 */
	public function get_region( $id ) {
		global $wpdb;

		$table_name = Installer::get_regions_table();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $id ), ARRAY_A );
	}

	/**
	 * Erstellt eine hierarchische Baumstruktur der Regionen.
	 *
	 * @param array $regions Flache Liste der Regionen.
	 * @param int   $parent_id Eltern-ID für die aktuelle Ebene.
	 * @param int   $depth Aktuelle Verschachtelungstiefe.
	 * @return array
	 */
	public function build_region_tree( $regions, $parent_id = 0, $depth = 0 ) {
		$branch = [];

		foreach ( $regions as $region ) {
			if ( intval( $region['parent_id'] ) === $parent_id ) {
				$region['depth']    = $depth;
				$region['children'] = $this->build_region_tree( $regions, intval( $region['id'] ), $depth + 1 );
				$branch[]           = $region;
			}
		}

		return $branch;
	}

	/**
	 * Formatiert den hierarchischen Baum in eine flache Liste mit Einrückungspräfixen für Select-Boxen.
	 *
	 * @param array  $tree Der hierarchische Baum.
	 * @param array  $flat Referenz auf das flache Ziel-Array.
	 * @param string $prefix Einrückungszeichen.
	 */
	public function flatten_tree( $tree, &$flat = [], $prefix = '— ' ) {
		foreach ( $tree as $node ) {
			$indented_name = str_repeat( $prefix, $node['depth'] ) . $node['name'];
			$flat[]        = [
				'id'   => $node['id'],
				'name' => $indented_name,
				'slug' => $node['slug'],
			];

			if ( ! empty( $node['children'] ) ) {
				$this->flatten_tree( $node['children'], $flat, $prefix );
			}
		}
	}
}
