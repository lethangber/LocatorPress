<?php
namespace LocatorPress\Helpers;

// Direkten Zugriff verhindern.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Klasse zur dynamischen Generierung und Injektion von Stylesheets.
 * Injiziert CSS-Variablen in das Frontend, um Farben, Eckenabrundung und Schatten
 * nahtlos an das aktive Theme anzupassen. Unterstützt automatischen & manuellen Darkmode.
 */
class Style_Manager {

	/**
	 * Singleton-Instanz.
	 *
	 * @var Style_Manager|null
	 */
	private static $instance = null;

	/**
	 * Holt die Singleton-Instanz.
	 *
	 * @return Style_Manager
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
		add_action( 'wp_head', [ $this, 'inject_dynamic_styles' ], 100 );
	}

	/**
	 * Generiert und druckt die dynamischen CSS-Variablen im Header der Website aus.
	 */
	public function inject_dynamic_styles() {
		// Nur ausgeben, wenn wir uns nicht im Admin befinden.
		if ( is_admin() ) {
			return;
		}

		$color_mode = get_option( 'lp_color_mode', 'light' );
		$cache_key  = 'lp_styles_cache_' . md5( $color_mode );
		
		// Prüfen, ob Caching aktiv ist.
		$cache_duration = intval( get_option( 'lp_cache_duration', 3600 ) );
		$css = false;

		if ( $cache_duration > 0 ) {
			$css = get_transient( $cache_key );
		}

		if ( false === $css ) {
			$css = $this->generate_css_string();
			if ( $cache_duration > 0 ) {
				set_transient( $cache_key, $css, $cache_duration );
			}
		}

		// CSS sicher ausgeben.
		echo '<!-- LocatorPress Dynamic Theme Color Injection -->';
		echo '<style id="locatorpress-dynamic-css">' . wp_strip_all_tags( $css ) . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Generiert das rohe CSS-Regelwerk basierend auf den Einstellungen.
	 *
	 * @return string
	 */
	private function generate_css_string() {
		$vars = Color_Engine::get_all_variables();
		
		// RGB-Variablen berechnen für Opacity-Effekte.
		$primary_rgb = Color_Engine::hex_to_rgb( $vars['--lp-primary'] );

		// 1. Basis-Styles (Gilt für das gesamte LocatorPress Wrapper-Element).
		$css = "#locatorpress-wrapper {
			--lp-primary: {$vars['--lp-primary']};
			--lp-primary-rgb: {$primary_rgb};
			--lp-secondary: {$vars['--lp-secondary']};
			--lp-btn-color: {$vars['--lp-button-color']};
			--lp-text-dark: {$vars['--lp-text-dark']};
			--lp-radius-lg: {$vars['--lp-border-radius']};
			--lp-shadow-md: {$vars['--lp-card-shadow']};
		}\n";

		// 2. Darkmode Overrides injizieren.
		$color_mode = get_option( 'lp_color_mode', 'auto' );

		$dark_css = "
			#locatorpress-wrapper {
				--lp-bg-white: #0f172a;
				--lp-bg-sidebar: #0b0f19;
				--lp-border-color: #334155;
				--lp-text-dark: #f1f5f9;
				--lp-text-light: #94a3b8;
				--lp-secondary: #1e293b;
			}
			#locatorpress-wrapper .lp-card {
				background: #1e293b;
				border-color: #334155;
			}
			#locatorpress-wrapper .lp-card:hover {
				border-color: rgba(var(--lp-primary-rgb), 0.4);
				box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3), 0 8px 10px -6px rgba(0, 0, 0, 0.3);
			}
			#locatorpress-wrapper .lp-card--active {
				border-color: var(--lp-primary);
				box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3), 0 0 0 3px rgba(var(--lp-primary-rgb), 0.25);
			}
			#locatorpress-wrapper .lp-search-bar,
			#locatorpress-wrapper .lp-results-header {
				background: #0f172a;
				border-color: #334155;
			}
			#locatorpress-wrapper .lp-search-field input,
			#locatorpress-wrapper .lp-search-field select {
				background: #1e293b;
				color: #f1f5f9;
				border-color: #334155;
			}
			#locatorpress-wrapper .lp-search-field input:focus,
			#locatorpress-wrapper .lp-search-field select:focus {
				border-color: var(--lp-primary);
				box-shadow: 0 0 0 4px rgba(var(--lp-primary-rgb), 0.2);
			}
			#locatorpress-wrapper .lp-btn--secondary {
				background: #1e293b;
				color: var(--lp-primary);
				border-color: #334155;
			}
			#locatorpress-wrapper .lp-btn--secondary:hover {
				background: #334155;
				color: var(--lp-primary);
			}
			#locatorpress-wrapper .lp-card-footer {
				border-color: #334155;
			}
			#locatorpress-wrapper .lp-card-link {
				background: #1e293b;
				background: rgba(var(--lp-primary-rgb), 0.15);
				color: var(--lp-primary);
				border-color: rgba(var(--lp-primary-rgb), 0.2);
			}
			#locatorpress-wrapper .lp-card-link:hover {
				background: var(--lp-primary);
				color: #ffffff;
				border-color: var(--lp-primary);
				box-shadow: 0 4px 8px rgba(var(--lp-primary-rgb), 0.3);
			}
			#locatorpress-wrapper .lp-results-count {
				color: #94a3b8;
			}
			#locatorpress-wrapper .lp-card-title {
				color: #f1f5f9;
			}
			#locatorpress-wrapper .lp-card-status--open {
				background: rgba(16, 185, 129, 0.15);
				border-color: rgba(16, 185, 129, 0.3);
				color: #34d399;
			}
			#locatorpress-wrapper .lp-card-status--closed {
				background: rgba(239, 68, 68, 0.15);
				border-color: rgba(239, 68, 68, 0.3);
				color: #f87171;
			}
			#locatorpress-wrapper .lp-results-list::-webkit-scrollbar-thumb {
				background: #475569;
			}
			#locatorpress-wrapper .lp-results-list::-webkit-scrollbar-thumb:hover {
				background: #64748b;
			}
			#locatorpress-wrapper .leaflet-popup-content-wrapper {
				background: #0f172a !important;
				color: #f1f5f9 !important;
				border-color: #334155 !important;
			}
			#locatorpress-wrapper .leaflet-popup-tip {
				background: #0f172a !important;
				border-color: #334155 !important;
			}
			#locatorpress-wrapper .lp-popup-title {
				color: #f1f5f9 !important;
			}
			#locatorpress-wrapper .lp-popup-address {
				color: #94a3b8 !important;
			}
			#locatorpress-wrapper .lp-popup-phone {
				color: #f1f5f9 !important;
			}
			#locatorpress-wrapper .leaflet-bar a {
				background: #0f172a !important;
				color: #f1f5f9 !important;
				border-color: #334155 !important;
			}
			#locatorpress-wrapper .leaflet-bar a:hover {
				background: #1e293b !important;
				color: var(--lp-primary) !important;
			}
		";

		if ( 'dark' === $color_mode ) {
			// Erzwungener Darkmode.
			$css .= $dark_css;
		} elseif ( 'auto' === $color_mode ) {
			// Automatischer Darkmode über Media Query.
			$css .= "@media (prefers-color-scheme: dark) {\n" . $dark_css . "\n}\n";
		}

		return $css;
	}
}
