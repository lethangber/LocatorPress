<?php
namespace LocatorPress\Helpers;

// Direkten Zugriff verhindern.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hilfsklasse zum Laden von Frontend-Templates.
 * Unterstützt das Überschreiben von standardmäßigen Templates durch den aktiven WordPress-Theme-Ordner.
 * Struktur im Theme: {aktiv-theme}/locatorpress/{template-name}.php
 */
class Template_Loader {

	/**
	 * Lädt ein bestimmtes Template. Falls vorhanden aus dem Theme, sonst aus dem Plugin.
	 *
	 * @param string $template_name Name des Templates (z. B. 'map-container').
	 * @param array  $args          Variablen, die im Template zur Verfügung stehen sollen.
	 */
	public static function locate_template( $template_name, $args = [] ) {
		// Sicherstellen, dass die Dateiendung vorhanden ist.
		if ( false === strpos( $template_name, '.php' ) ) {
			$template_name .= '.php';
		}

		// Variablen entpacken, damit sie im Geltungsbereich des Templates zugänglich sind.
		if ( ! empty( $args ) && is_array( $args ) ) {
			extract( $args ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		}

		// 1. Pfad im aktiven Theme prüfen.
		$theme_file = locate_template( 'locatorpress/' . $template_name );

		if ( ! empty( $theme_file ) ) {
			include $theme_file;
			return;
		}

		// 2. Fallback zum Standardpfad im Plugin.
		$plugin_file = LOCATORPRESS_PATH . 'templates/frontend/' . $template_name;
		$plugin_file = apply_filters( 'locatorpress_template_path', $plugin_file, $template_name );

		if ( file_exists( $plugin_file ) ) {
			include $plugin_file;
		}
	}
}
