<?php
namespace LocatorPress\Providers;

// Direkten Zugriff verhindern.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstrakte Basisklasse für alle Kartenanbieter (Map Provider).
 * Definiert die Schnittstelle, die jeder Kartendienst implementieren muss.
 */
abstract class Abstract_Map_Provider {

	/**
	 * Liefert die eindeutige ID des Providers.
	 *
	 * @return string
	 */
	abstract public function get_id();

	/**
	 * Liefert den Anzeigenamen des Providers.
	 *
	 * @return string
	 */
	abstract public function get_name();

	/**
	 * Registriert und lädt die notwendigen JavaScript- und CSS-Dateien im Frontend.
	 */
	abstract public function enqueue_assets();

	/**
	 * Liefert die JavaScript-Konfigurationsdaten für diesen Provider.
	 *
	 * @return array
	 */
	abstract public function get_js_config();
}
