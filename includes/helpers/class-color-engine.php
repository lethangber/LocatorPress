<?php
namespace LocatorPress\Helpers;

// Direkten Zugriff verhindern.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Kernklasse des Farbsystems (Color Engine).
 * Kombiniert Benutzerdefinierte Overrides, automatisch erkannte Theme-Farben
 * und Standard-Fallbacks zu einem performanten Variablen-Set.
 */
class Color_Engine {

	/**
	 * Liefert das vollständige CSS-Variablen-Array für das Frontend.
	 *
	 * @return array
	 */
	public static function get_all_variables() {
		// 1. Primärfarbe ermitteln.
		$primary_color = '#2563eb'; // Globaler Fallback.
		$custom_primary = get_option( 'lp_primary_color', '' );
		if ( ! empty( $custom_primary ) ) {
			$primary_color = $custom_primary;
		}

		// 2. Sekundärfarbe.
		$secondary_color = get_option( 'lp_secondary_color', '' );
		if ( empty( $secondary_color ) ) {
			$secondary_color = '#0f172a'; // Standard dunkles Schiefergrau.
		}

		// 3. Buttonfarbe (Falls leer, wird Primärfarbe verwendet).
		$button_color = get_option( 'lp_button_color', '' );
		if ( empty( $button_color ) ) {
			$button_color = $primary_color;
		}

		// 4. Textfarbe.
		$text_color = get_option( 'lp_text_color', '' );
		if ( empty( $text_color ) ) {
			$text_color = '#1a202c';
		}

		// 5. Border Radius.
		$border_radius = get_option( 'lp_border_radius', '' );
		if ( empty( $border_radius ) ) {
			$border_radius = '12px';
		}

		// 6. Card Shadow (Schattenwurf).
		$card_shadow = get_option( 'lp_card_shadow', '' );
		if ( empty( $card_shadow ) ) {
			$card_shadow = '0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03)';
		}

		return [
			'--lp-primary'          => $primary_color,
			'--lp-secondary'        => $secondary_color,
			'--lp-button-color'     => $button_color,
			'--lp-text-dark'        => $text_color,
			'--lp-border-radius'    => $border_radius,
			'--lp-card-shadow'      => $card_shadow,
		];
	}

	/**
	 * Wandelt eine Hex-Farbe in RGB-Komponenten um.
	 * Hilfreich für CSS-Transparenzen (rgba).
	 *
	 * @param string $hex
	 * @return string 'R, G, B'
	 */
	public static function hex_to_rgb( $hex ) {
		$hex = str_replace( '#', '', $hex );
		if ( strlen( $hex ) === 3 ) {
			$r = hexdec( substr( $hex, 0, 1 ) . substr( $hex, 0, 1 ) );
			$g = hexdec( substr( $hex, 1, 1 ) . substr( $hex, 1, 1 ) );
			$b = hexdec( substr( $hex, 2, 1 ) . substr( $hex, 2, 1 ) );
		} else {
			$r = hexdec( substr( $hex, 0, 2 ) );
			$g = hexdec( substr( $hex, 2, 2 ) );
			$b = hexdec( substr( $hex, 4, 2 ) );
		}
		return "$r, $g, $b";
	}
}
