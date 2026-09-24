<?php
namespace LocatorPress\Helpers;

// Direkten Zugriff verhindern.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Löst automatisch das Farbschema des aktiven WordPress-Themes auf.
 * Unterstützt Block-Themes, Astra, GeneratePress, Kadence, Flatsome und CSS-Custom-Variables.
 */
class Theme_Detector {

	/**
	 * Ermittelt die primäre Farbe des aktiven Themes mit intelligenten Fallbacks.
	 *
	 * @return string Hex-Farbe (z. B. '#2563eb').
	 */
	public static function detect_primary_color() {
		$theme = wp_get_theme();
		$theme_name = strtolower( $theme->get( 'Name' ) );
		$template   = strtolower( $theme->get_template() );

		// 1. Bekannte Frameworks/Themes in der Datenbank abfragen.
		
		// Astra Theme
		if ( 'astra' === $template || 'astra' === $theme_name ) {
			$astra_settings = get_option( 'astra-settings' );
			if ( is_array( $astra_settings ) && isset( $astra_settings['theme-color-1'] ) ) {
				return $astra_settings['theme-color-1'];
			}
		}

		// GeneratePress Theme
		if ( 'generatepress' === $template || 'generatepress' === $theme_name ) {
			$gp_settings = get_option( 'generate_settings' );
			if ( is_array( $gp_settings ) && isset( $gp_settings['global_colors'][0]['color'] ) ) {
				return $gp_settings['global_colors'][0]['color'];
			}
		}

		// Flatsome Theme
		if ( 'flatsome' === $template || 'flatsome' === $theme_name ) {
			$flatsome_options = get_option( 'flatsome_options' );
			if ( is_array( $flatsome_options ) && isset( $flatsome_options['color_primary'] ) ) {
				return $flatsome_options['color_primary'];
			}
		}

		// Kadence Theme
		if ( 'kadence' === $template || 'kadence' === $theme_name ) {
			$kadence_settings = get_option( 'kadence_theme_settings' );
			if ( is_array( $kadence_settings ) && isset( $kadence_settings['palette']['palette'][0] ) ) {
				return $kadence_settings['palette']['palette'][0];
			}
		}

		// 2. Block-Themes (theme.json) überprüfen.
		if ( function_exists( 'wp_get_global_settings' ) ) {
			$palette = wp_get_global_settings( [ 'color', 'palette', 'theme' ] );
			if ( ! empty( $palette ) && is_array( $palette ) ) {
				foreach ( $palette as $color ) {
					if ( isset( $color['slug'] ) && 'primary' === $color['slug'] && isset( $color['color'] ) ) {
						return $color['color'];
					}
				}
				// Falls kein 'primary' Slug existiert, nehmen wir die erste Farbe aus der Palette.
				if ( isset( $palette[0]['color'] ) ) {
					return $palette[0]['color'];
				}
			}
		}

		// 3. Fallback zur Standardfarbe des Plugins.
		return '#2563eb';
	}
}
