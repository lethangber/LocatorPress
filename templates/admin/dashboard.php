<?php
// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Fetch overall statistics.
global $wpdb;
$table_locations  = \LocatorPress\Database\Installer::get_locations_table();
$table_regions    = \LocatorPress\Database\Installer::get_regions_table();

$total_locations  = intval( $wpdb->get_var( "SELECT COUNT(id) FROM $table_locations" ) );
$active_locations = intval( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM $table_locations WHERE status = %s", 'active' ) ) );
$total_regions    = intval( $wpdb->get_var( "SELECT COUNT(id) FROM $table_regions" ) );

$map_provider     = get_option( 'lp_default_map_provider', 'leaflet' );
$geocode_provider = get_option( 'lp_geocoding_provider', 'openstreetmap' );
?>

<div class="lp-admin-wrap">
	<header class="lp-admin-header">
		<h1 class="lp-admin-title">LocatorPress</h1>
		<p class="lp-admin-subtitle"><?php esc_html_e( 'Welcome to your Store Locator control panel.', 'locatorpress' ); ?></p>
	</header>

	<!-- ❤ Donate Banner -->
	<div class="lp-donate-banner" id="lp-donate-banner">
		<div class="lp-donate-banner__inner">
			<div class="lp-donate-banner__icon">❤️</div>
			<div class="lp-donate-banner__text">
				<strong><?php esc_html_e( 'Enjoy LocatorPress for free?', 'locatorpress' ); ?></strong>
				<span><?php esc_html_e( 'If this plugin saves you time or money, a small donation helps keep it actively maintained and ad-free.', 'locatorpress' ); ?></span>
			</div>
			<a href="https://paypal.me/thangme" target="_blank" rel="noopener noreferrer" class="lp-donate-banner__btn">
				<?php esc_html_e( '☕ Buy me a coffee', 'locatorpress' ); ?>
			</a>
			<button type="button" class="lp-donate-banner__dismiss" id="lp-donate-dismiss" title="<?php esc_attr_e( 'Dismiss', 'locatorpress' ); ?>">✕</button>
		</div>
	</div>
	<style>
	.lp-donate-banner {
		background: linear-gradient(135deg, #fff7ed 0%, #fef3c7 100%);
		border: 1.5px solid #fcd34d;
		border-radius: 10px;
		margin-bottom: 24px;
		padding: 0;
		animation: lp-banner-in 0.4s ease both;
	}
	@keyframes lp-banner-in {
		from { opacity: 0; transform: translateY(-8px); }
		to   { opacity: 1; transform: translateY(0); }
	}
	.lp-donate-banner__inner {
		display: flex;
		align-items: center;
		gap: 16px;
		padding: 16px 20px;
		flex-wrap: wrap;
	}
	.lp-donate-banner__icon {
		font-size: 28px;
		line-height: 1;
		flex-shrink: 0;
	}
	.lp-donate-banner__text {
		flex: 1;
		min-width: 200px;
		display: flex;
		flex-direction: column;
		gap: 3px;
	}
	.lp-donate-banner__text strong {
		font-size: 14px;
		color: #92400e;
		font-weight: 700;
	}
	.lp-donate-banner__text span {
		font-size: 13px;
		color: #78350f;
		opacity: 0.85;
	}
	.lp-donate-banner__btn {
		display: inline-flex;
		align-items: center;
		gap: 6px;
		background: #f59e0b;
		color: #ffffff !important;
		padding: 9px 20px;
		border-radius: 8px;
		font-size: 13px;
		font-weight: 700;
		text-decoration: none;
		transition: background 0.2s, transform 0.15s;
		white-space: nowrap;
		flex-shrink: 0;
		box-shadow: 0 2px 8px rgba(245,158,11,0.3);
	}
	.lp-donate-banner__btn:hover {
		background: #d97706;
		transform: translateY(-1px);
		box-shadow: 0 4px 14px rgba(245,158,11,0.4);
	}
	.lp-donate-banner__dismiss {
		background: none;
		border: none;
		cursor: pointer;
		color: #92400e;
		opacity: 0.5;
		font-size: 16px;
		padding: 4px;
		border-radius: 4px;
		transition: opacity 0.2s;
		flex-shrink: 0;
	}
	.lp-donate-banner__dismiss:hover { opacity: 1; }
	</style>
	<script>
	(function($) {
		var dismissed = localStorage.getItem('lp_donate_dismissed');
		if (dismissed) {
			$('#lp-donate-banner').hide();
		}
		$('#lp-donate-dismiss').on('click', function() {
			localStorage.setItem('lp_donate_dismissed', '1');
			$('#lp-donate-banner').slideUp(250);
		});
	})(jQuery);
	</script>

	<!-- Stat Cards -->
	<div class="lp-admin-dashboard-grid">
		<div class="lp-admin-stat-card">
			<div class="lp-admin-stat-icon">
				<span class="dashicons dashicons-location-alt"></span>
			</div>
			<div class="lp-admin-stat-info">
				<h3 class="lp-admin-stat-number"><?php echo esc_html( $total_locations ); ?></h3>
				<p class="lp-admin-stat-label"><?php esc_html_e( 'Total Locations', 'locatorpress' ); ?></p>
				<span class="lp-admin-stat-subtext"><?php echo esc_html( $active_locations ); ?> <?php esc_html_e( 'active', 'locatorpress' ); ?></span>
			</div>
		</div>

		<div class="lp-admin-stat-card">
			<div class="lp-admin-stat-icon">
				<span class="dashicons dashicons-admin-site-alt"></span>
			</div>
			<div class="lp-admin-stat-info">
				<h3 class="lp-admin-stat-number"><?php echo esc_html( $total_regions ); ?></h3>
				<p class="lp-admin-stat-label"><?php esc_html_e( 'Regions', 'locatorpress' ); ?></p>
				<span class="lp-admin-stat-subtext"><?php esc_html_e( 'Hierarchical categories', 'locatorpress' ); ?></span>
			</div>
		</div>

		<div class="lp-admin-stat-card">
			<div class="lp-admin-stat-icon">
				<span class="dashicons dashicons-admin-appearance"></span>
			</div>
			<div class="lp-admin-stat-info">
				<h3 class="lp-admin-stat-number"><?php echo esc_html( ucfirst( $map_provider ) ); ?></h3>
				<p class="lp-admin-stat-label"><?php esc_html_e( 'Map Provider', 'locatorpress' ); ?></p>
				<span class="lp-admin-stat-subtext"><?php esc_html_e( 'Changeable in Settings', 'locatorpress' ); ?></span>
			</div>
		</div>

		<div class="lp-admin-stat-card">
			<div class="lp-admin-stat-icon">
				<span class="dashicons dashicons-cloud"></span>
			</div>
			<div class="lp-admin-stat-info">
				<h3 class="lp-admin-stat-number"><?php echo esc_html( ucfirst( 'openstreetmap' === $geocode_provider ? 'OSM' : $geocode_provider ) ); ?></h3>
				<p class="lp-admin-stat-label"><?php esc_html_e( 'Geocoding Service', 'locatorpress' ); ?></p>
				<span class="lp-admin-stat-subtext"><?php esc_html_e( 'Automatic fallback active', 'locatorpress' ); ?></span>
			</div>
		</div>
	</div>

	<div class="lp-admin-columns">
		<!-- Main Content -->
		<div class="lp-admin-column-main">
			<div class="lp-admin-box">
				<h2><?php esc_html_e( 'Getting Started', 'locatorpress' ); ?></h2>
				<p><?php esc_html_e( 'LocatorPress lets you display stores, branches, or any locations on beautiful interactive maps. Follow these steps to get started:', 'locatorpress' ); ?></p>

				<ul class="lp-admin-steps-list">
					<li>
						<strong>1. <?php esc_html_e( 'Create Regions', 'locatorpress' ); ?></strong>
						<p><?php esc_html_e( 'Build a hierarchy like Country → State → City to categorize your locations.', 'locatorpress' ); ?></p>
					</li>
					<li>
						<strong>2. <?php esc_html_e( 'Add Locations', 'locatorpress' ); ?></strong>
						<p><?php esc_html_e( 'Enter your stores with address, contact info, opening hours and images — or import them via CSV.', 'locatorpress' ); ?></p>
					</li>
					<li>
						<strong>3. <?php esc_html_e( 'Embed on Your Site', 'locatorpress' ); ?></strong>
						<p><?php esc_html_e( 'Use the shortcode [locatorpress] on any page, Gutenberg block, or Elementor widget.', 'locatorpress' ); ?></p>
					</li>
				</ul>

				<div class="lp-admin-actions">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=locatorpress-locations&action=add' ) ); ?>" class="button button-primary button-large"><?php esc_html_e( 'Add Location', 'locatorpress' ); ?></a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=locatorpress-regions' ) ); ?>" class="button button-secondary button-large"><?php esc_html_e( 'Manage Regions', 'locatorpress' ); ?></a>
				</div>
			</div>
		</div>

		<!-- Right Sidebar -->
		<div class="lp-admin-column-sidebar">
			<div class="lp-admin-box lp-admin-box--accent">
				<h3><span class="dashicons dashicons-editor-code"></span> <?php esc_html_e( 'Shortcode Reference', 'locatorpress' ); ?></h3>
				<p><?php esc_html_e( 'Embed the store locator anywhere:', 'locatorpress' ); ?></p>
				<code class="lp-admin-code-snippet">[locatorpress]</code>

				<h4><?php esc_html_e( 'Optional Attributes:', 'locatorpress' ); ?></h4>
				<table class="lp-admin-attributes-table">
					<tr>
						<th><?php esc_html_e( 'Attribute', 'locatorpress' ); ?></th>
						<th><?php esc_html_e( 'Default', 'locatorpress' ); ?></th>
					</tr>
					<tr><td><code>zoom</code></td><td><code>12</code></td></tr>
					<tr><td><code>height</code></td><td><code>500px</code></td></tr>
					<tr><td><code>region</code></td><td><?php esc_html_e( 'None', 'locatorpress' ); ?></td></tr>
				</table>
			</div>

			<!-- Support & Donate Card -->
			<div class="lp-admin-box" style="border-left: 4px solid #f59e0b; background: linear-gradient(135deg, #fffbeb 0%, #fff 100%);">
				<h3 style="color: #92400e;">❤️ <?php esc_html_e( 'Support This Plugin', 'locatorpress' ); ?></h3>
				<p style="font-size: 13px; color: #78350f; line-height: 1.6;">
					<?php esc_html_e( 'LocatorPress is free and open source. If it helps your project, please consider leaving a 5-star review or making a small donation.', 'locatorpress' ); ?>
				</p>
				<div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 14px;">
					<a href="https://paypal.me/thangme" target="_blank" rel="noopener noreferrer" class="button button-primary" style="background: #f59e0b; border-color: #d97706; color: #fff; text-decoration: none;">
						☕ <?php esc_html_e( 'Donate via PayPal', 'locatorpress' ); ?>
					</a>
					<a href="https://wordpress.org/plugins/locatorpress/#reviews" target="_blank" rel="noopener noreferrer" class="button button-secondary" style="text-decoration: none;">
						★ <?php esc_html_e( 'Leave a Review', 'locatorpress' ); ?>
					</a>
				</div>
			</div>
		</div>
	</div>
</div>
