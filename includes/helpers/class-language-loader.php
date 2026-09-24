<?php
namespace LocatorPress\Helpers;

// Direkten Zugriff verhindern.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dedizierter Sprach-Loader zur Internationalisierung des Plugins.
 * Ermöglicht automatische WordPress-Spracherkennung sowie manuelle Umschaltung
 * über die Administration oder direkt im Frontend per Cookie.
 */
class Language_Loader {

	/**
	 * Singleton-Instanz.
	 *
	 * @var Language_Loader|null
	 */
	private static $instance = null;

	/**
	 * Holt die Singleton-Instanz.
	 *
	 * @return Language_Loader
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
		// Cookie-Verarbeitung früh in der Anfrage durchführen.
		add_action( 'init', [ $this, 'handle_manual_language_switch' ], 1 );

		// Hook zum Überschreiben des plugin-spezifischen Locales.
		add_filter( 'plugin_locale', [ $this, 'override_plugin_locale' ], 99, 2 );

		// Lade Übersetzungen auf dem 'init' Hook (empfohlen ab WP 6.7) statt sofort in 'plugins_loaded'.
		add_action( 'init', [ $this, 'load_translations' ], 9 );
	}

	/**
	 * Prüft auf manuelle Sprachumschaltung per URL-Parameter (?lp_lang=...).
	 * Speichert die Auswahl in einem Cookie ab.
	 */
	public function handle_manual_language_switch() {
		if ( isset( $_GET['lp_lang'] ) ) {
			$lang = sanitize_text_field( $_GET['lp_lang'] );
			
			// Erlaubt das Zurücksetzen des Sprach-Cookies.
			if ( 'default' === $lang || 'reset' === $lang ) {
				setcookie( 'lp_locale_cookie', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN );
				unset( $_COOKIE['lp_locale_cookie'] );
				$redirect_url = remove_query_arg( 'lp_lang' );
				wp_safe_redirect( $redirect_url );
				exit;
			}

			// Erlaubte Sprachcodes einschränken (Whitelist).
			$allowed = [ 'en_US', 'de_DE', 'vi', 'vi_VN' ];

			if ( in_array( $lang, $allowed, true ) ) {
				// Cookie für 30 Tage setzen.
				setcookie( 'lp_locale_cookie', $lang, time() + ( 30 * DAY_IN_SECONDS ), COOKIEPATH, COOKIE_DOMAIN );
				$_COOKIE['lp_locale_cookie'] = $lang; // Sofort im aktuellen Request verfügbar machen.

				// Optional: Parameter entfernen und weiterleiten, um saubere URLs zu wahren.
				$redirect_url = remove_query_arg( 'lp_lang' );
				wp_safe_redirect( $redirect_url );
				exit;
			}
		}
	}

	/**
	 * Überschreibt die Spracheinstellung AUSSCHLIESSLICH für die Textdomain 'locatorpress'.
	 * Dadurch bleibt das globale WordPress-System unberührt, während das Plugin
	 * in der gewünschten Sprache übersetzt wird.
	 *
	 * @param string $locale Das originale WordPress Locale.
	 * @param string $domain Die aktuelle Textdomain der Übersetzung.
	 * @return string
	 */
	public function override_plugin_locale( $locale, $domain ) {
		if ( 'locatorpress' !== $domain && 'locatorpress-pro' !== $domain ) {
			return $locale;
		}

		// 1. Im Admin-Bereich (ausgenommen AJAX-Anfragen) hat die globale Datenbank-Einstellung absolute Priorität vor Cookies.
		if ( is_admin() && ! wp_doing_ajax() ) {
			$override = get_option( 'lp_locale_override', '' );
			if ( ! empty( $override ) ) {
				return $override;
			}
		}

		// 2. Cookie prüfen (z.B. für manuelle Umschaltung im Frontend oder bei AJAX).
		if ( ! empty( $_COOKIE['lp_locale_cookie'] ) ) {
			$cookie_lang = sanitize_text_field( $_COOKIE['lp_locale_cookie'] );
			if ( in_array( $cookie_lang, [ 'en_US', 'de_DE', 'vi', 'vi_VN' ], true ) ) {
				return $cookie_lang;
			}
		}

		// 3. Globale Admin-Einstellung prüfen (als Fallback).
		$override = get_option( 'lp_locale_override', '' );
		if ( ! empty( $override ) ) {
			return $override;
		}

		return $locale;
	}

	/**
	 * Lädt die .mo-Sprachdateien des Plugins aus dem languages-Ordner.
	 */
	public function load_translations() {
		// Entlädt die Textdomains, falls WordPress diese bereits geladen hat.
		unload_textdomain( 'locatorpress' );
		unload_textdomain( 'locatorpress-pro' );

		// Bestimme das aktive Locale für das Plugin.
		$locale = $this->override_plugin_locale( get_locale(), 'locatorpress' );

		// Liste der möglichen Fallbacks für das Locale erstellen.
		$candidates = [ $locale ];
		
		// Falls das Locale ein Unter-Locale hat (z. B. de_DE, vi_VN), füge das Haupt-Locale als Fallback hinzu.
		if ( strpos( $locale, '_' ) !== false ) {
			$parts = explode( '_', $locale );
			$candidates[] = $parts[0];
		}
		
		// Standard-Fallbacks hinzufügen.
		if ( 0 === strpos( $locale, 'de' ) ) {
			$candidates[] = 'de_DE'; // Haupt-Quellsprache des Plugins
			$candidates[] = 'en_US'; // Globaler Fallback
		} else {
			$candidates[] = 'en_US'; // Globaler Fallback
			$candidates[] = 'de_DE'; // Haupt-Quellsprache des Plugins
		}

		$loaded = false;
		foreach ( $candidates as $candidate ) {
			$mofile = LOCATORPRESS_PATH . 'languages/locatorpress-' . $candidate . '.mo';
			if ( file_exists( $mofile ) ) {
				$loaded_lp  = load_textdomain( 'locatorpress', $mofile );
				$loaded_pro = load_textdomain( 'locatorpress-pro', $mofile );
				if ( $loaded_lp ) {
					$loaded = true;
					break;
				}
			}
		}
		
		// Fallback zu load_plugin_textdomain, falls kein Direktladen erfolgreich war
		if ( ! $loaded ) {
			load_plugin_textdomain(
				'locatorpress',
				false,
				dirname( plugin_basename( LOCATORPRESS_FILE ) ) . '/languages/'
			);
			load_plugin_textdomain(
				'locatorpress-pro',
				false,
				dirname( plugin_basename( LOCATORPRESS_FILE ) ) . '/languages/'
			);
		}
	}
}
