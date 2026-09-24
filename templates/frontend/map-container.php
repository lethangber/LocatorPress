<?php
// Direkten Zugriff verhindern.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use LocatorPress\Helpers\Template_Loader;

/**
 * Frontend-Template für das Store Locator Layout.
 * Flexibel gegliedert in eine Seitenleiste für Standorte und den Kartenbereich.
 */
// Ensure height has a unit if it is a raw number
$height_css = trim( $height );
if ( is_numeric( $height_css ) ) {
	$height_css .= 'px';
}
?>
<div class="lp-container <?php echo esc_attr( $class ); ?>" 
	 id="locatorpress-wrapper" 
	 style="--lp-map-height: <?php echo esc_attr( $height_css ); ?>;"
	 data-zoom="<?php echo esc_attr( $zoom ); ?>"
	 data-height="<?php echo esc_attr( $height_css ); ?>"
	 data-lat="<?php echo esc_attr( $center_lat ); ?>"
	 data-lng="<?php echo esc_attr( $center_lng ); ?>"
	 data-region="<?php echo esc_attr( $region ); ?>">
	
	<!-- Suchleiste (Search Form) -->
	<?php Template_Loader::locate_template( 'search-form' ); ?>

	<div class="lp-layout">
		<!-- Seitenleiste mit Standortkarten -->
		<aside class="lp-sidebar">
			<div class="lp-results-header">
				<div class="lp-results-header-icon-wrap">
					<i class="fa-solid fa-location-dot"></i>
				</div>
				<div class="lp-results-header-text">
					<h3 class="lp-results-title" id="lp-results-count-text">
						<?php esc_html_e( 'Suche starten...', 'locatorpress' ); ?>
					</h3>
					<p class="lp-results-meta" id="lp-results-meta-text"></p>
				</div>
			</div>
			<div class="lp-results-list" id="lp-location-results-list">
				<!-- AJAX lädt hier dynamisch die Location Cards -->
				<div class="lp-loader-placeholder">
					<div class="lp-pulse-loader"></div>
				</div>
			</div>
		</aside>

		<!-- Kartenbereich -->
		<main class="lp-map-view" style="height: <?php echo esc_attr( $height_css ); ?>;">
			<div id="locatorpress-map" style="width: 100%; height: 100%;"></div>
		</main>
	</div>
</div>
