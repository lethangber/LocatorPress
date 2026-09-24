<?php
namespace LocatorPress\Admin;

// Direkten Zugriff verhindern.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Klasse zur Registrierung des Admin-Menüs und der Seitensteuerung.
 * Verwaltet außerdem das bedingte Laden von Admin-Ressourcen (Assets), um Konflikte zu vermeiden.
 */
class Admin_Menu {

	/**
	 * Singleton-Instanz.
	 *
	 * @var Admin_Menu|null
	 */
	private static $instance = null;

	/**
	 * @var array Liste der registrierten Menü-Seiten-Hooks.
	 */
	private $page_hooks = [];

	/**
	 * Holt die Singleton-Instanz.
	 *
	 * @return Admin_Menu
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
		add_action( 'admin_menu', [ $this, 'register_menu_pages' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );

		// Schnelllinks auf der Plugin-Seite hinzufügen.
		add_filter( 'plugin_action_links_' . plugin_basename( LOCATORPRESS_FILE ), [ $this, 'add_plugin_action_links' ] );
	}

	/**
	 * Fügt Schnelllinks zur Plugin-Liste hinzu (z. B. Einstellungen und Dashboard).
	 *
	 * @param array $links
	 * @return array
	 */
	public function add_plugin_action_links( $links ) {
		$action_links = [
			'settings'  => '<a href="' . admin_url( 'admin.php?page=locatorpress-settings' ) . '">' . esc_html__( 'Settings', 'locatorpress' ) . '</a>',
			'dashboard' => '<a href="' . admin_url( 'admin.php?page=locatorpress' ) . '">' . esc_html__( 'Dashboard', 'locatorpress' ) . '</a>',
			'donate'    => '<a href="https://paypal.me/thangme" target="_blank" rel="noopener noreferrer" style="color:#f59e0b; font-weight:700;">❤ ' . esc_html__( 'Donate', 'locatorpress' ) . '</a>',
		];
		return array_merge( $action_links, $links );
	}

	/**
	 * Registriert das LocatorPress Menü und die Untermenüs im WordPress-Admin-Dashboard.
	 */
	public function register_menu_pages() {
		// Hauptmenüpunkt.
		$this->page_hooks['dashboard'] = add_menu_page(
			esc_html__( 'LocatorPress Dashboard', 'locatorpress' ),
			'LocatorPress',
			'manage_options',
			'locatorpress',
			[ $this, 'render_dashboard_page' ],
			'dashicons-location-alt',
			26
		);

		// Standorte Untermenü.
		$this->page_hooks['locations'] = add_submenu_page(
			'locatorpress',
			esc_html__( 'Standorte', 'locatorpress' ),
			esc_html__( 'Standorte', 'locatorpress' ),
			'manage_options',
			'locatorpress-locations',
			[ $this, 'render_locations_page' ]
		);

		// Regionen Untermenü.
		$this->page_hooks['regions'] = add_submenu_page(
			'locatorpress',
			esc_html__( 'Regionen', 'locatorpress' ),
			esc_html__( 'Regionen', 'locatorpress' ),
			'manage_options',
			'locatorpress-regions',
			[ $this, 'render_regions_page' ]
		);

		// Import/Export Untermenü.
		$this->page_hooks['import_export'] = add_submenu_page(
			'locatorpress',
			esc_html__( 'Import / Export', 'locatorpress' ),
			esc_html__( 'Import / Export', 'locatorpress' ),
			'manage_options',
			'locatorpress-import-export',
			[ $this, 'render_import_export_page' ]
		);

		// Einstellungen Untermenü.
		$this->page_hooks['settings'] = add_submenu_page(
			'locatorpress',
			esc_html__( 'Einstellungen', 'locatorpress' ),
			esc_html__( 'Einstellungen', 'locatorpress' ),
			'manage_options',
			'locatorpress-settings',
			[ $this, 'render_settings_page' ]
		);
	}

	/**
	 * Lädt Admin-Assets (Stylesheets und Skripte) nur auf den eigenen Plugin-Seiten,
	 * um Konflikte mit Editoren wie Elementor oder anderen Plugins auszuschließen.
	 *
	 * @param string $hook_suffix Der Name der aktuellen Adminseite.
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		// Prüfen, ob wir uns auf einer LocatorPress-Admin-Seite befinden.
		if ( ! in_array( $hook_suffix, $this->page_hooks, true ) ) {
			return;
		}

		// WordPress Standard Media Library Styles & Scripts laden für die Bildauswahl.
		wp_enqueue_media();

		// Leaflet-CSS & JS für die Standort-Auswahlkarte im Admin laden.
		wp_enqueue_style( 'leaflet-core', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], '1.9.4' );
		wp_enqueue_script( 'leaflet-core', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], '1.9.4', true );

		// Eigene Stylesheets für modernes, responsives Admin-Design (BEM-Konvention).
		wp_enqueue_style( 'font-awesome-free', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', [], '6.4.0' );
		wp_enqueue_style(
			'locatorpress-admin-css',
			LOCATORPRESS_URL . 'assets/css/locatorpress-admin.css',
			[],
			LOCATORPRESS_VERSION
		);

		// Eigenes JavaScript für die Admin-Logik (Karteninteraktion, CSV-Imports, Tab-Steuerung).
		wp_enqueue_script(
			'locatorpress-admin-js',
			LOCATORPRESS_URL . 'assets/js/locatorpress-admin.js',
			[ 'jquery', 'jquery-ui-sortable', 'leaflet-core' ],
			LOCATORPRESS_VERSION,
			true
		);

		// Lokalisierungsdaten für JavaScript übergeben (Nonces, Ajax-Url, Texte).
		wp_localize_script( 'locatorpress-admin-js', 'locatorPressAdmin', [
			'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
			'securityNonce'     => wp_create_nonce( 'lp_admin_ajax_nonce' ),
			'defaultCenterLat'  => get_option( 'lp_default_center_lat', '48.135125' ),
			'defaultCenterLng'  => get_option( 'lp_default_center_lng', '11.581981' ),
			'defaultZoom'       => get_option( 'lp_default_zoom', 12 ),
			'selectImageText'   => esc_html__( 'Bild auswählen', 'locatorpress' ),
			'useImageText'      => esc_html__( 'Bild verwenden', 'locatorpress' ),
			'geocodingFailed'   => esc_html__( 'Geokodierung fehlgeschlagen. Bitte Breitengrad/Längengrad manuell eingeben.', 'locatorpress' ),
			'getCoordinatesText'=> esc_html__( 'Get Coordinates', 'locatorpress' ),
			'closedAllDayText'  => esc_html__( 'Closed all day', 'locatorpress' ),
			'openAllDayText'    => esc_html__( 'Open all day', 'locatorpress' ),
			'addTimeRangeText'  => esc_html__( 'Add Time Range', 'locatorpress' ),
			'removeTimeRangeText'=> esc_html__( 'Remove time range', 'locatorpress' ),
			'confirmResetText'  => esc_html__( 'Are you sure you want to reset opening hours to default?', 'locatorpress' ),
			'openText'          => esc_html__( 'Open', 'locatorpress' ),
			'closedText'        => esc_html__( 'Closed', 'locatorpress' ),
			'csvImportLimit'    => apply_filters( 'locatorpress_csv_import_limit', 100 ),
			// Import UI strings
			'importCsvNoFile'       => esc_html__( 'Bitte wählen Sie eine CSV-Datei aus.', 'locatorpress' ),
			'importStarting'        => esc_html__( 'Starte Importvorgang...', 'locatorpress' ),
			'importNoData'          => esc_html__( 'Die CSV-Datei enthält keine nutzbaren Daten.', 'locatorpress' ),
			'importNoEntries'       => esc_html__( 'Keine Einträge zum Importieren gefunden.', 'locatorpress' ),
			'importLimitMsg'        => esc_html__( 'Der Import ist in der kostenlosen Version auf %d Datensätze beschränkt. Bitte upgraden Sie auf LocatorPress Pro für unbegrenzte Imports.', 'locatorpress' ),
			'importRowsRead'        => esc_html__( '%d Einträge eingelesen. Starte Stapelverarbeitung...', 'locatorpress' ),
			'importComplete'        => esc_html__( '>>> Import vollständig beendet!', 'locatorpress' ),
			'importSuccess'         => esc_html__( 'Erfolgreich abgeschlossen!', 'locatorpress' ),
			'importProcessingRows'  => esc_html__( 'Verarbeite Zeilen %s bis %s von %s...', 'locatorpress' ),
			'importBatchError'      => esc_html__( 'Fehler im Stapel:', 'locatorpress' ),
			'importNetworkError'    => esc_html__( 'Netzwerk- oder Serverfehler bei der Stapelverarbeitung.', 'locatorpress' ),
		] );
	}

	/**
	 * Rendert die Dashboard-Seite.
	 */
	public function render_dashboard_page() {
		$template = LOCATORPRESS_PATH . 'templates/admin/dashboard.php';
		if ( file_exists( $template ) ) {
			include $template;
		}
	}

	/**
	 * Rendert die Standorte-Liste oder die Formulare zum Hinzufügen/Bearbeiten.
	 */
	public function render_locations_page() {
		$template = LOCATORPRESS_PATH . 'templates/admin/locations.php';
		if ( file_exists( $template ) ) {
			include $template;
		}
	}

	/**
	 * Rendert die Regionen-Verwaltung.
	 */
	public function render_regions_page() {
		$template = LOCATORPRESS_PATH . 'templates/admin/regions.php';
		if ( file_exists( $template ) ) {
			include $template;
		}
	}

	/**
	 * Rendert die Import / Export-Seite.
	 */
	public function render_import_export_page() {
		$template = LOCATORPRESS_PATH . 'templates/admin/import-export.php';
		if ( file_exists( $template ) ) {
			include $template;
		}
	}

	/**
	 * Rendert die Einstellungsseite.
	 */
	public function render_settings_page() {
		$template = LOCATORPRESS_PATH . 'templates/admin/settings.php';
		if ( file_exists( $template ) ) {
			include $template;
		}
	}
}
