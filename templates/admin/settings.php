<?php
// Direkten Zugriff verhindern.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="lp-admin-wrap">
	<header class="lp-admin-header">
		<h1 class="lp-admin-title"><?php esc_html_e( 'LocatorPress Einstellungen', 'locatorpress' ); ?></h1>
		<p class="lp-admin-subtitle"><?php esc_html_e( 'Konfigurieren Sie Kartenanbieter, API-Schlüssel und das Verhalten des Store Locators.', 'locatorpress' ); ?></p>
	</header>

	<?php if ( isset( $_GET['settings-updated'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Einstellungen erfolgreich gespeichert.', 'locatorpress' ); ?></p></div>
	<?php endif; ?>

	<form method="POST" action="" class="lp-admin-form-container lp-admin-form-container--settings">
		<input type="hidden" name="lp_settings_action" value="save_settings" />
		<?php wp_nonce_field( 'lp_save_settings_data', 'lp_settings_nonce' ); ?>

		<div class="lp-admin-form-main" style="width: 100%; max-width: 900px;">

			<!-- Lokalisierung & Sprache (ganz oben) -->
			<div class="lp-admin-box">
				<h3><?php esc_html_e( 'Lokalisierung & Sprachen', 'locatorpress' ); ?></h3>

				<div class="lp-admin-form-group">
					<label for="lp_locale_override"><?php esc_html_e( 'Sprache manuell erzwingen', 'locatorpress' ); ?></label>
					<select id="lp_locale_override" name="lp_locale_override">
						<option value="" <?php selected( get_option( 'lp_locale_override', '' ), '' ); ?>><?php esc_html_e( 'Standard (WordPress Spracherkennung)', 'locatorpress' ); ?></option>
						<option value="en_US" <?php selected( get_option( 'lp_locale_override', '' ), 'en_US' ); ?>>English (en_US)</option>
						<option value="de_DE" <?php selected( get_option( 'lp_locale_override', '' ), 'de_DE' ); ?>>Deutsch (de_DE)</option>
						<option value="vi" <?php selected( get_option( 'lp_locale_override', '' ), 'vi' ); ?>>Tiếng Việt (vi)</option>
					</select>
					<p class="description"><?php esc_html_e( 'Ermöglicht es Ihnen, die Anzeigesprache für Frontend und Backend festzulegen. Standardmäßig wird die aktive WordPress-Sprachumgebung automatisch erkannt.', 'locatorpress' ); ?></p>
				</div>
			</div>

			<!-- Karten & Geokodierung -->
			<div class="lp-admin-box">
				<h3><?php esc_html_e( 'Kartenanbieter & APIs', 'locatorpress' ); ?></h3>

				<div class="lp-admin-form-group">
					<label for="lp_default_map_provider"><?php esc_html_e( 'Standard-Kartenanbieter', 'locatorpress' ); ?></label>
					<select id="lp_default_map_provider" name="lp_default_map_provider">
						<?php
						$providers = apply_filters( 'locatorpress_map_providers', [
							'leaflet' => __( 'Leaflet (OpenStreetMap - Kostenlos)', 'locatorpress' ),
						] );
						$saved_provider = get_option( 'lp_default_map_provider', 'leaflet' );
						foreach ( $providers as $pid => $pname ) :
						?>
							<option value="<?php echo esc_attr( $pid ); ?>" <?php selected( $saved_provider, $pid ); ?>><?php echo esc_html( $pname ); ?></option>
						<?php endforeach; ?>
						<?php if ( ! defined( 'LOCATORPRESS_PRO_VERSION' ) ) : ?>
							<option value="google" disabled>Google Maps [Pro Only]</option>
							<option value="goong" disabled>Goong Maps [Pro Only]</option>
						<?php endif; ?>
					</select>
					<p class="description"><?php esc_html_e( 'Leaflet benötigt keinen API-Schlüssel und ist standardmäßig aktiv.', 'locatorpress' ); ?></p>
				</div>

				<div class="lp-admin-form-group">
					<label for="lp_geocoding_provider"><?php esc_html_e( 'Geocoding-Dienst', 'locatorpress' ); ?></label>
					<select id="lp_geocoding_provider" name="lp_geocoding_provider">
						<?php
						$geocoders = apply_filters( 'locatorpress_geocoding_providers', [
							'openstreetmap' => __( 'OpenStreetMap Nominatim (Kostenlos)', 'locatorpress' ),
						] );
						$saved_geocoder = get_option( 'lp_geocoding_provider', 'openstreetmap' );
						foreach ( $geocoders as $gid => $gname ) :
						?>
							<option value="<?php echo esc_attr( $gid ); ?>" <?php selected( $saved_geocoder, $gid ); ?>><?php echo esc_html( $gname ); ?></option>
						<?php endforeach; ?>
						<?php if ( ! defined( 'LOCATORPRESS_PRO_VERSION' ) ) : ?>
							<option value="google" disabled>Google Geocoding API [Pro Only]</option>
							<option value="goong" disabled>Goong Geocoding API [Pro Only]</option>
						<?php endif; ?>
					</select>
					<p class="description"><?php esc_html_e( 'Dienst zur Adressauflösung beim Importieren oder Speichern von Standorten.', 'locatorpress' ); ?></p>
				</div>
				<hr class="lp-api-divider" style="display: none;" />

				<div class="lp-admin-form-group lp-google-key-group" style="display: none;">
					<label for="lp_google_maps_api_key">
						<?php esc_html_e( 'Google Maps API-Key', 'locatorpress' ); ?>
						<?php if ( ! defined( 'LOCATORPRESS_PRO_VERSION' ) ) : ?>
							<span class="lp-pro-badge" style="background:#2563eb;color:#fff;font-size:9px;padding:2px 6px;border-radius:4px;font-weight:bold;margin-left:5px;text-transform:uppercase;">Pro</span>
						<?php endif; ?>
					</label>
					<input type="text" id="lp_google_maps_api_key" name="lp_google_maps_api_key" value="<?php echo esc_attr( get_option( 'lp_google_maps_api_key', '' ) ); ?>" class="large-text" <?php disabled( ! defined( 'LOCATORPRESS_PRO_VERSION' ) ); ?> />
					<p class="description">
						<?php esc_html_e( 'Erforderlich, falls Google Maps als Kartenanbieter oder Geocoding-Dienst ausgewählt ist.', 'locatorpress' ); ?>
						<?php if ( ! defined( 'LOCATORPRESS_PRO_VERSION' ) ) : ?>
							<span style="display:block;margin-top:5px;font-weight:bold;"><a href="https://paypal.me/thangme" target="_blank" style="color:#2563eb;">Upgrade to LocatorPress Pro to unlock Google Maps integration</a></span>
						<?php endif; ?>
					</p>
				</div>

				<div class="lp-admin-form-group lp-goong-key-group" style="display: none;">
					<label for="lp_goong_maps_api_key">
						<?php esc_html_e( 'Goong Maps Map Token (Key)', 'locatorpress' ); ?>
						<?php if ( ! defined( 'LOCATORPRESS_PRO_VERSION' ) ) : ?>
							<span class="lp-pro-badge" style="background:#2563eb;color:#fff;font-size:9px;padding:2px 6px;border-radius:4px;font-weight:bold;margin-left:5px;text-transform:uppercase;">Pro</span>
						<?php endif; ?>
					</label>
					<input type="text" id="lp_goong_maps_api_key" name="lp_goong_maps_api_key" value="<?php echo esc_attr( get_option( 'lp_goong_maps_api_key', '' ) ); ?>" class="large-text" <?php disabled( ! defined( 'LOCATORPRESS_PRO_VERSION' ) ); ?> />
					<p class="description">
						<?php esc_html_e( 'Erforderlich für das Laden der Goong-Kartenkacheln.', 'locatorpress' ); ?>
						<?php if ( ! defined( 'LOCATORPRESS_PRO_VERSION' ) ) : ?>
							<span style="display:block;margin-top:5px;font-weight:bold;"><a href="https://paypal.me/thangme" target="_blank" style="color:#2563eb;">Upgrade to LocatorPress Pro to unlock Goong Maps integration</a></span>
						<?php endif; ?>
					</p>
				</div>

				<div class="lp-admin-form-group lp-goong-key-group" style="display: none;">
					<label for="lp_goong_geocoding_api_key">
						<?php esc_html_e( 'Goong Geocoding API-Key', 'locatorpress' ); ?>
						<?php if ( ! defined( 'LOCATORPRESS_PRO_VERSION' ) ) : ?>
							<span class="lp-pro-badge" style="background:#2563eb;color:#fff;font-size:9px;padding:2px 6px;border-radius:4px;font-weight:bold;margin-left:5px;text-transform:uppercase;">Pro</span>
						<?php endif; ?>
					</label>
					<input type="text" id="lp_goong_geocoding_api_key" name="lp_goong_geocoding_api_key" value="<?php echo esc_attr( get_option( 'lp_goong_geocoding_api_key', '' ) ); ?>" class="large-text" <?php disabled( ! defined( 'LOCATORPRESS_PRO_VERSION' ) ); ?> />
					<p class="description">
						<?php esc_html_e( 'Wird für Goong-Suchen und Adressauflösung (Geocoding) benötigt.', 'locatorpress' ); ?>
						<?php if ( ! defined( 'LOCATORPRESS_PRO_VERSION' ) ) : ?>
							<span style="display:block;margin-top:5px;font-weight:bold;"><a href="https://paypal.me/thangme" target="_blank" style="color:#2563eb;">Upgrade to LocatorPress Pro to unlock Goong Geocoding integration</a></span>
						<?php endif; ?>
					</p>
				</div>
			</div>

			<!-- Karteneinstellungen -->
			<div class="lp-admin-box">
				<h3><?php esc_html_e( 'Kartenverhalten & Standardwerte', 'locatorpress' ); ?></h3>

				<div class="lp-admin-form-row">
					<div class="lp-admin-form-group">
						<label for="lp_default_zoom"><?php esc_html_e( 'Standard-Zoomstufe', 'locatorpress' ); ?></label>
						<input type="number" id="lp_default_zoom" name="lp_default_zoom" value="<?php echo esc_attr( get_option( 'lp_default_zoom', 12 ) ); ?>" min="1" max="20" class="small-text" />
					</div>
					
					<div class="lp-admin-form-group">
						<label for="lp_radius_units"><?php esc_html_e( 'Entfernungs-Maßeinheit', 'locatorpress' ); ?></label>
						<select id="lp_radius_units" name="lp_radius_units">
							<option value="km" <?php selected( get_option( 'lp_radius_units', 'km' ), 'km' ); ?>><?php esc_html_e( 'Kilometer (Km)', 'locatorpress' ); ?></option>
							<option value="miles" <?php selected( get_option( 'lp_radius_units', 'km' ), 'miles' ); ?>><?php esc_html_e( 'Meilen (Miles)', 'locatorpress' ); ?></option>
						</select>
					</div>
				</div>

				<div class="lp-admin-form-row">
					<div class="lp-admin-form-group">
						<label for="lp_default_center_lat"><?php esc_html_e( 'Standard-Breitengrad (Lat)', 'locatorpress' ); ?></label>
						<input type="text" id="lp_default_center_lat" name="lp_default_center_lat" value="<?php echo esc_attr( get_option( 'lp_default_center_lat', '48.135125' ) ); ?>" class="regular-text" />
					</div>
					<div class="lp-admin-form-group">
						<label for="lp_default_center_lng"><?php esc_html_e( 'Standard-Längengrad (Lng)', 'locatorpress' ); ?></label>
						<input type="text" id="lp_default_center_lng" name="lp_default_center_lng" value="<?php echo esc_attr( get_option( 'lp_default_center_lng', '11.581981' ) ); ?>" class="regular-text" />
					</div>
				</div>

				<hr />

				<!-- Map Marker Configuration -->
				<div class="lp-admin-marker-config" style="margin-top: 15px;">
					<div class="lp-admin-marker-fields">
						<div class="lp-admin-color-pickers">
							<div class="lp-admin-form-group lp-admin-color-picker-item">
								<label for="lp_marker_fa_color"><?php esc_html_e( 'Marker-Farbe', 'locatorpress' ); ?></label>
								<div class="lp-admin-color-picker-wrapper">
									<input type="color" id="lp_marker_fa_color" name="lp_marker_fa_color" value="<?php echo esc_attr( get_option( 'lp_marker_fa_color', '#2563eb' ) ); ?>" />
								</div>
							</div>

							<div class="lp-admin-form-group lp-admin-color-picker-item">
								<label for="lp_marker_fa_bg_color"><?php esc_html_e( 'Pin Hintergrund', 'locatorpress' ); ?></label>
								<div class="lp-admin-color-picker-wrapper">
									<input type="color" id="lp_marker_fa_bg_color" name="lp_marker_fa_bg_color" value="<?php echo esc_attr( get_option( 'lp_marker_fa_bg_color', '#ffffff' ) ); ?>" />
								</div>
							</div>
						</div>
					</div>

					<div class="lp-admin-marker-preview-box">
						<label><?php esc_html_e( 'Vorschau', 'locatorpress' ); ?></label>
						<div class="lp-admin-marker-preview-container">
							<div id="lp-settings-marker-fa-preview" class="lp-marker-pin-wrapper">
								<?php
								$saved_fa_color    = get_option( 'lp_marker_fa_color', '#2563eb' );
								$saved_fa_bg_color = get_option( 'lp_marker_fa_bg_color', '#ffffff' );
								?>
								<div class="lp-marker-pin">
									<svg class="lp-marker-pin-svg" viewBox="0 0 36 46" width="36" height="46">
										<path d="M18 0C8.1 0 0 8.1 0 18c0 12.6 15.5 26.6 17.1 27.8a1.5 1.5 0 0 0 1.8 0C20.5 44.6 36 30.6 36 18c0-9.9-8.1-18-18-18z" 
											  fill="<?php echo esc_attr( $saved_fa_color ); ?>" 
											  stroke="#ffffff" 
											  stroke-width="1.5"></path>
										<circle cx="18" cy="18" r="11" fill="<?php echo esc_attr( $saved_fa_bg_color ); ?>"></circle>
										<circle cx="18" cy="18" r="5" fill="<?php echo esc_attr( $saved_fa_color ); ?>"></circle>
									</svg>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- Aussehen & Design (Appearance Settings) -->
			<div class="lp-admin-box">
				<h3><?php esc_html_e( 'Aussehen & Design (Appearance Settings)', 'locatorpress' ); ?></h3>



				<div class="lp-admin-form-row">
					<div class="lp-admin-form-group">
						<label for="lp_primary_color"><?php esc_html_e( 'Primärfarbe (Primary Color)', 'locatorpress' ); ?></label>
						<input type="color" id="lp_primary_color" name="lp_primary_color" value="<?php echo esc_attr( get_option( 'lp_primary_color', '#2563eb' ) ); ?>" />
					</div>
					<div class="lp-admin-form-group">
						<label for="lp_secondary_color"><?php esc_html_e( 'Sekundärfarbe (Secondary Color)', 'locatorpress' ); ?></label>
						<input type="color" id="lp_secondary_color" name="lp_secondary_color" value="<?php echo esc_attr( get_option( 'lp_secondary_color', '#0f172a' ) ); ?>" />
					</div>
				</div>

				<div class="lp-admin-form-row" style="margin-top: 15px;">
					<div class="lp-admin-form-group">
						<label for="lp_button_color"><?php esc_html_e( 'Button-Farbe (Button Color)', 'locatorpress' ); ?></label>
						<input type="color" id="lp_button_color" name="lp_button_color" value="<?php echo esc_attr( get_option( 'lp_button_color', '#2563eb' ) ); ?>" />
					</div>
					<div class="lp-admin-form-group">
						<label for="lp_text_color"><?php esc_html_e( 'Textfarbe (Text Color)', 'locatorpress' ); ?></label>
						<input type="color" id="lp_text_color" name="lp_text_color" value="<?php echo esc_attr( get_option( 'lp_text_color', '#1a202c' ) ); ?>" />
					</div>
				</div>

				<div class="lp-admin-form-row" style="margin-top: 15px;">
					<div class="lp-admin-form-group">
						<label for="lp_border_radius"><?php esc_html_e( 'Eckenabrundung (Border Radius)', 'locatorpress' ); ?></label>
						<input type="text" id="lp_border_radius" name="lp_border_radius" value="<?php echo esc_attr( get_option( 'lp_border_radius', '12px' ) ); ?>" class="regular-text" />
						<p class="description">z.B. <code>12px</code> oder <code>8px</code></p>
					</div>
					<div class="lp-admin-form-group">
						<label for="lp_card_shadow"><?php esc_html_e( 'Karten-Schattenwurf (Card Shadow)', 'locatorpress' ); ?></label>
						<input type="text" id="lp_card_shadow" name="lp_card_shadow" value="<?php echo esc_attr( get_option( 'lp_card_shadow', '0 4px 6px -1px rgba(0,0,0,0.05)' ) ); ?>" class="regular-text" />
						<p class="description">CSS Box-Shadow Syntax</p>
					</div>
				</div>

				<div class="lp-admin-form-row" style="margin-top: 15px;">
					<div class="lp-admin-form-group">
						<label for="lp_color_mode"><?php esc_html_e( 'Farbschema (Light / Dark Mode)', 'locatorpress' ); ?></label>
						<select id="lp_color_mode" name="lp_color_mode">
							<option value="auto" <?php selected( get_option( 'lp_color_mode', 'light' ), 'auto' ); ?>><?php esc_html_e( 'Automatisch (Browsereinstellung)', 'locatorpress' ); ?></option>
							<option value="light" <?php selected( get_option( 'lp_color_mode', 'light' ), 'light' ); ?>><?php esc_html_e( 'Hell (Light Mode)', 'locatorpress' ); ?></option>
							<option value="dark" <?php selected( get_option( 'lp_color_mode', 'light' ), 'dark' ); ?>><?php esc_html_e( 'Dunkel (Dark Mode)', 'locatorpress' ); ?></option>
						</select>
					</div>
					<div class="lp-admin-form-group">
						<label for="lp_map_style"><?php esc_html_e( 'Kartendesign (Map Style)', 'locatorpress' ); ?></label>
						<select id="lp_map_style" name="lp_map_style">
							<option value="standard" <?php selected( get_option( 'lp_map_style', 'standard' ), 'standard' ); ?>><?php esc_html_e( 'Standard', 'locatorpress' ); ?></option>
							<option value="dark" <?php selected( get_option( 'lp_map_style', 'standard' ), 'dark' ); ?>><?php esc_html_e( 'Dark Mode (Dunkel)', 'locatorpress' ); ?></option>
							<option value="silver" <?php selected( get_option( 'lp_map_style', 'standard' ), 'silver' ); ?>><?php esc_html_e( 'Silber', 'locatorpress' ); ?></option>
							<option value="retro" <?php selected( get_option( 'lp_map_style', 'standard' ), 'retro' ); ?>><?php esc_html_e( 'Retro', 'locatorpress' ); ?></option>
						</select>
				</div>
			</div>

			<!-- Anzeige-Elemente (Display Elements) -->
			<div class="lp-admin-box">
				<h3><?php esc_html_e( 'Sichtbare Elemente auf Karten', 'locatorpress' ); ?></h3>

				<div class="lp-admin-form-group">
					<div style="display: flex; flex-direction: column; gap: 12px; margin-top: 5px;">
						<label>
							<input type="checkbox" name="lp_show_featured_image" value="1" <?php checked( get_option( 'lp_show_featured_image', 1 ), 1 ); ?> />
							<strong><?php esc_html_e( 'Beitragsbild (Featured Image) anzeigen', 'locatorpress' ); ?></strong>
						</label>
						
						<label>
							<input type="checkbox" name="lp_show_address" value="1" <?php checked( get_option( 'lp_show_address', 1 ), 1 ); ?> />
							<strong><?php esc_html_e( 'Adresse anzeigen', 'locatorpress' ); ?></strong>
						</label>

						<label>
							<input type="checkbox" name="lp_show_phone" value="1" <?php checked( get_option( 'lp_show_phone', 1 ), 1 ); ?> />
							<strong><?php esc_html_e( 'Telefonnummer anzeigen', 'locatorpress' ); ?></strong>
						</label>

						<label>
							<input type="checkbox" name="lp_show_email" value="1" <?php checked( get_option( 'lp_show_email', 1 ), 1 ); ?> />
							<strong><?php esc_html_e( 'E-Mail anzeigen', 'locatorpress' ); ?></strong>
						</label>

						<label>
							<input type="checkbox" name="lp_show_website" value="1" <?php checked( get_option( 'lp_show_website', 1 ), 1 ); ?> />
							<strong><?php esc_html_e( 'Website anzeigen', 'locatorpress' ); ?></strong>
						</label>

						<label>
							<input type="checkbox" name="lp_show_opening_hours" value="1" <?php checked( get_option( 'lp_show_opening_hours', 1 ), 1 ); ?> />
							<strong><?php esc_html_e( 'Öffnungszeiten anzeigen', 'locatorpress' ); ?></strong>
						</label>
					</div>
					<p class="description" style="margin-top: 10px;"><?php esc_html_e( 'Wählen Sie aus, welche Elemente auf den Standortkarten im Frontend angezeigt werden sollen.', 'locatorpress' ); ?></p>
				</div>
			</div>

			<!-- Performance & Cache -->
			<div class="lp-admin-box">
				<h3><?php esc_html_e( 'Performance & Features', 'locatorpress' ); ?></h3>

				<div class="lp-admin-form-group">
					<label>
						<input type="checkbox" name="lp_enable_clustering" value="1" <?php checked( get_option( 'lp_enable_clustering', 1 ), 1 ); ?> />
						<strong><?php esc_html_e( 'Marker-Clustering aktivieren', 'locatorpress' ); ?></strong>
					</label>
					<p class="description"><?php esc_html_e( 'Fasst nahe beieinander liegende Standorte bei niedrigen Zoomstufen zu Clustern zusammen.', 'locatorpress' ); ?></p>
				</div>

				<div class="lp-admin-form-group">
					<label>
						<input type="checkbox" name="lp_enable_near_me" value="1" <?php checked( get_option( 'lp_enable_near_me', 1 ), 1 ); ?> />
						<strong><?php esc_html_e( '„In meiner Nähe" (GPS-Ortung) aktivieren', 'locatorpress' ); ?></strong>
					</label>
					<p class="description"><?php esc_html_e( 'Ermöglicht es Frontend-Besuchern, ihren aktuellen Standort per GPS abzurufen, um nahegelegene Standorte zu suchen.', 'locatorpress' ); ?></p>
				</div>

				<div class="lp-admin-form-group">
					<label for="lp_cache_duration"><?php esc_html_e( 'Cache-Dauer der Suchanfragen (in Sekunden)', 'locatorpress' ); ?></label>
					<input type="number" id="lp_cache_duration" name="lp_cache_duration" value="<?php echo esc_attr( get_option( 'lp_cache_duration', 3600 ) ); ?>" min="0" class="regular-text" />
					<p class="description"><?php esc_html_e( 'Verhindert wiederholte schwere Datenbankabfragen bei identischen Suchanfragen. Setzen Sie 0, um den Cache zu deaktivieren.', 'locatorpress' ); ?></p>
				</div>
			</div>

			<div class="lp-admin-form-actions" style="margin-top: 20px;">
				<button type="submit" class="button button-primary button-large"><?php esc_html_e( 'Einstellungen speichern', 'locatorpress' ); ?></button>
			</div>
		</div>
	</form>
</div>

<!-- Icon Picker Styles -->
<style>
.lp-icon-picker-wrapper {
	margin-top: 8px;
}
.lp-icon-picker-selected {
	display: flex;
	align-items: center;
	gap: 12px;
	padding: 10px 14px;
	background: #f8fafc;
	border: 1.5px solid #e2e8f0;
	border-radius: 8px;
	width: fit-content;
	min-width: 280px;
}
.lp-icon-picker-selected i {
	font-size: 22px;
	width: 28px;
	text-align: center;
	color: #2563eb;
}
.lp-icon-picker-selected span {
	flex: 1;
	font-family: monospace;
	font-size: 13px;
	color: #475569;
}
.lp-icon-picker-panel {
	margin-top: 10px;
	border: 1.5px solid #e2e8f0;
	border-radius: 10px;
	background: #fff;
	padding: 14px;
	max-width: 560px;
	box-shadow: 0 4px 12px rgba(0,0,0,0.06);
}
.lp-icon-picker-grid {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(68px, 1fr));
	gap: 6px;
	max-height: 280px;
	overflow-y: auto;
}
.lp-icon-item {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	gap: 4px;
	padding: 8px 4px;
	border: 1.5px solid #e2e8f0;
	border-radius: 8px;
	cursor: pointer;
	transition: all 0.15s ease;
	font-size: 11px;
	color: #64748b;
	text-align: center;
	overflow: hidden;
	background: #f8fafc;
}
.lp-icon-item:hover {
	border-color: #2563eb;
	background: rgba(37,99,235,0.06);
	color: #2563eb;
}
.lp-icon-item--active {
	border-color: #2563eb;
	background: rgba(37,99,235,0.08);
	color: #2563eb;
}
.lp-icon-item i {
	font-size: 20px;
	color: inherit;
}
.lp-icon-item span {
	font-size: 10px;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
	max-width: 60px;
	color: inherit;
}
.lp-icon-item.lp-hidden { display: none; }
</style>
