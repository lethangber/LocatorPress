<?php
namespace LocatorPress\Geocoding;

// Direkten Zugriff verhindern.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Geocoding-Dienst zur Konvertierung von Adressen in Breiten- und Längengrade.
 * Unterstützt OpenStreetMap Nominatim, Google Geocoding und Goong Geocoding mit automatischer Fallback-Logik.
 */
class Geocoding_Service {

	/**
	 * Geocodiert eine Adresse zu Breitengrad (lat) und Längengrad (lng).
	 *
	 * @param string $address Die zu suchende Adresse.
	 * @return array|false Ein Array mit ['lat', 'lng'] bei Erfolg, sonst false.
	 */
	public static function geocode( $address ) {
		if ( empty( $address ) ) {
			return false;
		}

		$primary_provider = get_option( 'lp_geocoding_provider', 'openstreetmap' );
		$providers        = apply_filters( 'locatorpress_geocoding_providers_queue', [ 'openstreetmap' ] );

		// Den primären Provider an den Anfang der Warteschlange stellen, falls er erlaubt ist.
		if ( in_array( $primary_provider, $providers, true ) ) {
			$providers = array_unique( array_merge( [ $primary_provider ], $providers ) );
		} else {
			$primary_provider = reset( $providers );
			$providers = [ $primary_provider ];
		}

		foreach ( $providers as $provider ) {
			$coords = false;

			switch ( $provider ) {
				case 'openstreetmap':
					$coords = self::geocode_openstreetmap( $address );
					break;
				case 'google':
					$coords = self::geocode_google( $address );
					break;
				case 'goong':
					$coords = self::geocode_goong( $address );
					break;
			}

			if ( $coords !== false && ! empty( $coords['lat'] ) && ! empty( $coords['lng'] ) ) {
				return $coords;
			}
		}

		return false;
	}

	/**
	 * Geocodierung über OpenStreetMap Nominatim API.
	 *
	 * @param string $address
	 * @return array|false
	 */
	private static function geocode_openstreetmap( $address ) {
		$url = add_query_arg(
			[
				'q'      => rawurlencode( $address ),
				'format' => 'json',
				'limit'  => 1,
			],
			'https://nominatim.openstreetmap.org/search'
		);

		// OSM verlangt einen eindeutigen User-Agent.
		$args = [
			'headers' => [
				'User-Agent' => 'LocatorPress/' . LOCATORPRESS_VERSION . ' (' . home_url() . ')',
			],
			'timeout' => 5,
		];

		$response = wp_remote_get( $url, $args );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! empty( $data ) && is_array( $data ) && isset( $data[0]['lat'] ) ) {
			return [
				'lat' => floatval( $data[0]['lat'] ),
				'lng' => floatval( $data[0]['lon'] ),
			];
		}

		return false;
	}

	/**
	 * Geocodierung über Google Geocoding API.
	 *
	 * @param string $address
	 * @return array|false
	 */
	private static function geocode_google( $address ) {
		$api_key = get_option( 'lp_google_maps_api_key', '' );
		if ( empty( $api_key ) ) {
			return false;
		}

		$url = add_query_arg(
			[
				'address' => rawurlencode( $address ),
				'key'     => $api_key,
			],
			'https://maps.googleapis.com/maps/api/geocode/json'
		);

		$response = wp_remote_get( $url, [ 'timeout' => 5 ] );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! empty( $data ) && isset( $data['status'] ) && 'OK' === $data['status'] ) {
			$location = $data['results'][0]['geometry']['location'];
			return [
				'lat' => floatval( $location['lat'] ),
				'lng' => floatval( $location['lng'] ),
			];
		}

		return false;
	}

	/**
	 * Geocodierung über Goong Geocoding API.
	 *
	 * @param string $address
	 * @return array|false
	 */
	private static function geocode_goong( $address ) {
		$api_key = get_option( 'lp_goong_geocoding_api_key', '' );
		if ( empty( $api_key ) ) {
			// Alternativ den Goong Maps Key als Fallback verwenden.
			$api_key = get_option( 'lp_goong_maps_api_key', '' );
		}
		
		if ( empty( $api_key ) ) {
			return false;
		}

		$url = add_query_arg(
			[
				'address' => rawurlencode( $address ),
				'api_key' => $api_key,
			],
			'https://rest.goong.io/geocode'
		);

		$response = wp_remote_get( $url, [ 'timeout' => 5 ] );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! empty( $data ) && isset( $data['results'] ) && ! empty( $data['results'] ) ) {
			$location = $data['results'][0]['geometry']['location'];
			return [
				'lat' => floatval( $location['lat'] ),
				'lng' => floatval( $location['lng'] ),
			];
		}

		return false;
	}
}
