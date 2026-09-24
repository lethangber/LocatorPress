<?php
// Direkten Zugriff verhindern.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$region_controller = \LocatorPress\Admin\Region_Controller::get_instance();
$raw_regions       = $region_controller->get_regions();
$region_tree       = $region_controller->build_region_tree( $raw_regions );
$flat_regions      = [];
$region_controller->flatten_tree( $region_tree, $flat_regions );

$radius_unit = get_option( 'lp_radius_units', 'km' );
$enable_near_me = get_option( 'lp_enable_near_me', 1 );
?>
<form class="lp-search-bar" id="lp-search-form" action="" method="GET">
	<!-- Adress- / Stichwortsuche -->
	<div class="lp-search-field lp-search-field--keyword">
		<i class="fa-solid fa-magnifying-glass lp-search-icon"></i>
		<input type="text" id="lp-search-keyword" placeholder="<?php esc_attr_e( 'PLZ, Stadt oder Name eingeben...', 'locatorpress' ); ?>" aria-label="<?php esc_attr_e( 'Suche', 'locatorpress' ); ?>" />
	</div>

	<!-- Regionenfilter -->
	<div class="lp-search-field lp-search-field--select">
		<select id="lp-search-region" aria-label="<?php esc_attr_e( 'Region filtern', 'locatorpress' ); ?>">
			<option value="0"><?php esc_html_e( 'Alle Regionen', 'locatorpress' ); ?></option>
			<?php foreach ( $flat_regions as $reg ) : ?>
				<option value="<?php echo esc_attr( $reg['id'] ); ?>"><?php echo esc_html( $reg['name'] ); ?></option>
			<?php endforeach; ?>
		</select>
	</div>

	<!-- Radiusfilter -->
	<div class="lp-search-field lp-search-field--select">
		<select id="lp-search-radius" aria-label="<?php esc_attr_e( 'Umkreis', 'locatorpress' ); ?>">
			<option value="0"><?php esc_html_e( 'Gesamtes Gebiet', 'locatorpress' ); ?></option>
			<option value="5">5 <?php echo esc_html( $radius_unit ); ?></option>
			<option value="10">10 <?php echo esc_html( $radius_unit ); ?></option>
			<option value="25" selected>25 <?php echo esc_html( $radius_unit ); ?></option>
			<option value="50">50 <?php echo esc_html( $radius_unit ); ?></option>
			<option value="100">100 <?php echo esc_html( $radius_unit ); ?></option>
		</select>
	</div>

	<!-- Suchen-Button & Ortungs-Button -->
	<div class="lp-search-actions">
		<button type="submit" class="lp-btn lp-btn--primary">
			<?php esc_html_e( 'Suchen', 'locatorpress' ); ?>
		</button>
		
		<?php if ( $enable_near_me ) : ?>
			<button type="button" id="lp-btn-near-me" class="lp-btn lp-btn--secondary" title="<?php esc_attr_e( 'Meinen Standort ermitteln', 'locatorpress' ); ?>">
				<i class="fa-solid fa-location-crosshairs"></i>
			</button>
		<?php endif; ?>
	</div>
</form>
