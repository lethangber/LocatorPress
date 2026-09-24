<?php
// Direkten Zugriff verhindern.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="lp-admin-wrap">
	<header class="lp-admin-header">
		<h1 class="lp-admin-title"><?php esc_html_e( 'CSV Import & Export', 'locatorpress' ); ?></h1>
		<p class="lp-admin-subtitle"><?php esc_html_e( 'Importieren Sie Hunderte von Standorten auf einmal oder exportieren Sie Ihre bestehende Datenbank.', 'locatorpress' ); ?></p>
	</header>

	<div class="lp-admin-columns">
		<!-- Linke Spalte: Import -->
		<div class="lp-admin-column-main">
			<div class="lp-admin-box">
				<h3><span class="dashicons dashicons-upload"></span> <?php esc_html_e( 'Standorte aus CSV importieren', 'locatorpress' ); ?></h3>
				<p><?php esc_html_e( 'Wählen Sie eine CSV-Datei aus. Die Adressen können automatisch im Hintergrund geokodiert werden, falls keine Längen- und Breitengrade vorhanden sind.', 'locatorpress' ); ?></p>

				<!-- Import-Formular -->
				<form id="lp-csv-import-form" method="POST" enctype="multipart/form-data" style="margin-top: 20px;">
					<?php wp_nonce_field( 'lp_csv_import_nonce', 'lp_csv_nonce' ); ?>
					
					<div class="lp-admin-form-group">
						<label for="lp_csv_file"><?php esc_html_e( 'CSV-Datei auswählen *', 'locatorpress' ); ?></label>
						<input type="file" id="lp_csv_file" name="csv_file" accept=".csv" required />
					</div>

					<div class="lp-admin-form-group">
						<label>
							<input type="checkbox" name="auto_geocode" value="1" checked />
							<strong><?php esc_html_e( 'Fehlende Geokoordinaten automatisch ermitteln', 'locatorpress' ); ?></strong>
						</label>
						<p class="description"><?php esc_html_e( 'Achtung: Dies verlängert den Import-Vorgang je nach Anzahl der Standorte, da Adressabfragen durchgeführt werden.', 'locatorpress' ); ?></p>
					</div>

					<button type="submit" id="lp-start-import-btn" class="button button-primary button-large"><?php esc_html_e( 'Import starten', 'locatorpress' ); ?></button>
				</form>

				<!-- Fortschrittsanzeige (Standardmäßig versteckt) -->
				<div id="lp-import-progress-container" class="lp-admin-import-progress-wrap" style="display: none; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
					<h4><?php esc_html_e( 'Importfortschritt', 'locatorpress' ); ?></h4>
					<div class="lp-admin-progress-bar-bg" style="background: #e0e0e0; height: 20px; border-radius: 10px; overflow: hidden; margin-bottom: 10px; width: 100%;">
						<div id="lp-import-progress-bar" style="background: #0073aa; width: 0%; height: 100%; transition: width 0.3s ease;"></div>
					</div>
					<p id="lp-import-status-text" class="description"><?php esc_html_e( 'Lese CSV-Datei...', 'locatorpress' ); ?></p>
					
					<!-- Log-Ausgabe -->
					<div id="lp-import-logs" class="lp-admin-import-logs-box" style="background: #23282d; color: #fff; padding: 15px; border-radius: 4px; height: 150px; overflow-y: scroll; font-family: monospace; font-size: 12px; margin-top: 15px; line-height: 1.5;">
					</div>
				</div>
			</div>
		</div>

		<!-- Rechte Spalte: Export & Anleitung -->
		<div class="lp-admin-column-sidebar">
			<!-- Export Box -->
			<div class="lp-admin-box">
				<h3><span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Standorte exportieren', 'locatorpress' ); ?></h3>
				<p><?php esc_html_e( 'Exportieren Sie alle eingetragenen Standorte in eine strukturierte CSV-Datei.', 'locatorpress' ); ?></p>
				
				<form method="POST" action="" style="margin-top: 15px;">
					<?php wp_nonce_field( 'lp_csv_export_nonce', 'lp_csv_nonce' ); ?>
					<input type="hidden" name="lp_csv_export_action" value="export" />
					<button type="submit" class="button button-secondary button-large" style="width: 100%; text-align: center; justify-content: center; display: inline-flex;"><?php esc_html_e( 'CSV herunterladen (.csv)', 'locatorpress' ); ?></button>
				</form>
			</div>

			<!-- Format-Anleitung Box -->
			<div class="lp-admin-box">
				<h3><span class="dashicons dashicons-info"></span> <?php esc_html_e( 'CSV-Formatierung', 'locatorpress' ); ?></h3>
				<p><?php esc_html_e( 'Die CSV-Datei muss UTF-8-kodiert sein und ein Semikolon (;) oder Komma (,) als Trennzeichen verwenden.', 'locatorpress' ); ?></p>
				<p><?php esc_html_e( 'Die erste Zeile muss genau folgende Spaltenüberschriften enthalten:', 'locatorpress' ); ?></p>
				
				<code class="lp-admin-code-snippet lp-admin-code-snippet--scroll" style="display: block; font-size: 11px; white-space: nowrap; overflow-x: auto;">
					title,address,latitude,longitude,phone,email,website,region_id,status
				</code>

				<p class="description" style="margin-top: 10px;">
					<strong>title:</strong> <?php esc_html_e( 'Name des Standortes (erforderlich)', 'locatorpress' ); ?><br />
					<strong>address:</strong> <?php esc_html_e( 'Genaue Postanschrift (erforderlich)', 'locatorpress' ); ?><br />
					<strong>latitude / longitude:</strong> <?php esc_html_e( 'Dezimalzahlen (optional)', 'locatorpress' ); ?><br />
					<strong>region_id:</strong> <?php esc_html_e( 'Numerische ID der Region (optional)', 'locatorpress' ); ?><br />
					<strong>status:</strong> <code>active</code> <?php esc_html_e( 'oder', 'locatorpress' ); ?> <code>inactive</code>
				</p>
			</div>
		</div>
	</div>
</div>
