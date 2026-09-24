<?php
namespace LocatorPress\Helpers;

// Direkten Zugriff verhindern.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Autoloader-Klasse für PSR-4 konformes automatisches Laden von Klassen.
 * Berücksichtigt die WordPress-Dateinamenskonvention (class-name.php).
 */
class Autoload {

	/**
	 * Registriert den Autoloader bei PHP.
	 */
	public static function register() {
		spl_autoload_register( [ __CLASS__, 'autoload' ] );
	}

	/**
	 * Lädt die angeforderte Klasse.
	 *
	 * @param string $class Die voll qualifizierte Klasse.
	 */
	public static function autoload( $class ) {
		// Namespace-Präfix definieren.
		$prefix = 'LocatorPress\\';
		$len    = strlen( $prefix );

		// Prüfen, ob die Klasse unseren Namespace verwendet.
		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
			return;
		}

		// Relativen Klassennamen ermitteln.
		$relative_class = substr( $class, $len );

		// In Verzeichnisstruktur aufteilen.
		$parts = explode( '\\', $relative_class );

		// Der letzte Teil ist der eigentliche Klassenname, die davor sind Unterordner.
		$class_name = array_pop( $parts );
		
		// Unterordner in Kleinbuchstaben konvertieren.
		$sub_path = '';
		if ( ! empty( $parts ) ) {
			$sub_path = implode( '/', array_map( 'strtolower', $parts ) ) . '/';
		}

		// WordPress-Dateinamenformat erstellen: 'class-' gefolgt vom Klassennamen in Kleinbuchstaben mit Bindestrichen statt Unterstrichen.
		$file_name = 'class-' . str_replace( '_', '-', strtolower( $class_name ) ) . '.php';

		// Absoluten Pfad zur Datei zusammenbauen.
		$file = LOCATORPRESS_PATH . 'includes/' . $sub_path . $file_name;

		// Wenn die Datei existiert, einbinden.
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
}
