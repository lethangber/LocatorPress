<?php
// Direkten Zugriff verhindern.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$controller        = \LocatorPress\Admin\Location_Controller::get_instance();
$region_controller = \LocatorPress\Admin\Region_Controller::get_instance();

// Regionen für das Dropdown-Menü abfragen.
$raw_regions  = $region_controller->get_regions();
$region_tree  = $region_controller->build_region_tree( $raw_regions );
$flat_regions = [];
$region_controller->flatten_tree( $region_tree, $flat_regions );

$action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : 'list';
$id     = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

$location = null;
if ( $id > 0 ) {
	$location = $controller->get_location( $id );
}

if ( 'add' === $action || 'edit' === $action ) {
	// Standardwerte für neue Standorte.
	if ( ! $location ) {
		$location = [
			'id'            => 0,
			'title'         => '',
			'address'       => '',
			'latitude'      => '',
			'longitude'     => '',
			'phone'         => '',
			'email'         => '',
			'website'       => '',
			'image_id'      => 0,
			'region_id'     => 0,
			'status'        => 'active',
			'opening_hours' => [],
		];
	}

	$days = [
		'monday'    => __( 'Montag', 'locatorpress' ),
		'tuesday'   => __( 'Dienstag', 'locatorpress' ),
		'wednesday' => __( 'Mittwoch', 'locatorpress' ),
		'thursday'  => __( 'Donnerstag', 'locatorpress' ),
		'friday'    => __( 'Freitag', 'locatorpress' ),
		'saturday'  => __( 'Samstag', 'locatorpress' ),
		'sunday'    => __( 'Sonntag', 'locatorpress' ),
	];
	?>
	<div class="lp-admin-wrap">
		<header class="lp-admin-header lp-admin-header--flex">
			<div>
				<h1 class="lp-admin-title"><?php echo $id > 0 ? esc_html__( 'Standort bearbeiten', 'locatorpress' ) : esc_html__( 'Neuen Standort hinzufügen', 'locatorpress' ); ?></h1>
				<p class="lp-admin-subtitle"><?php esc_html_e( 'Erfassen Sie alle Adress- und Kontaktdaten für diesen Shop.', 'locatorpress' ); ?></p>
			</div>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=locatorpress-locations' ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Zurück zur Übersicht', 'locatorpress' ); ?></a>
		</header>

		<?php if ( isset( $_GET['updated'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Standort erfolgreich aktualisiert.', 'locatorpress' ); ?></p></div>
		<?php endif; ?>
		<?php if ( isset( $_GET['created'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Standort erfolgreich angelegt.', 'locatorpress' ); ?></p></div>
		<?php endif; ?>

		<form method="POST" action="" class="lp-admin-form-container">
			<input type="hidden" name="lp_location_action" value="save" />
			<input type="hidden" name="id" value="<?php echo esc_attr( $location['id'] ); ?>" />
			<?php wp_nonce_field( 'lp_save_location', 'lp_location_nonce' ); ?>

			<div class="lp-admin-form-main">
				<!-- Allgemeine Details -->
				<div class="lp-admin-box">
					<h3><?php esc_html_e( 'Allgemeine Informationen', 'locatorpress' ); ?></h3>
					<div class="lp-admin-form-group">
						<label for="title"><?php esc_html_e( 'Name des Standortes *', 'locatorpress' ); ?></label>
						<input type="text" id="title" name="title" value="<?php echo esc_attr( $location['title'] ); ?>" required class="large-text" />
					</div>

					<div class="lp-admin-form-group">
						<label for="address"><?php esc_html_e( 'Adresse (Vollständig) *', 'locatorpress' ); ?></label>
						<textarea id="address" name="address" rows="3" required class="large-text"><?php echo esc_textarea( $location['address'] ); ?></textarea>
						<p class="description" style="margin-bottom: 8px;"><?php esc_html_e( 'Wird zur Geokodierung verwendet, falls Breitengrad/Längengrad leer gelassen werden.', 'locatorpress' ); ?></p>
						<button type="button" id="lp-btn-geocode-address" class="button button-secondary">
							<span class="dashicons dashicons-location-alt" style="margin-right: 4px; vertical-align: middle;"></span>
							<?php esc_html_e( 'Get Coordinates', 'locatorpress' ); ?>
						</button>
					</div>

					<div class="lp-admin-form-row">
						<div class="lp-admin-form-group">
							<label for="latitude"><?php esc_html_e( 'Breitengrad (Latitude)', 'locatorpress' ); ?></label>
							<input type="text" id="latitude" name="latitude" value="<?php echo esc_attr( $location['latitude'] ); ?>" class="regular-text" />
						</div>
						<div class="lp-admin-form-group">
							<label for="longitude"><?php esc_html_e( 'Längengrad (Longitude)', 'locatorpress' ); ?></label>
							<input type="text" id="longitude" name="longitude" value="<?php echo esc_attr( $location['longitude'] ); ?>" class="regular-text" />
						</div>
					</div>

					<!-- Karten-Picker -->
					<div class="lp-admin-form-group">
						<label><?php esc_html_e( 'Standort auf Karte festlegen', 'locatorpress' ); ?></label>
						<div id="lp-admin-map-picker" style="height: 250px; border-radius: 4px; border: 1px solid #ccc; margin-top: 8px;"></div>
						<p class="description"><?php esc_html_e( 'Ziehen Sie den Marker oder klicken Sie auf die Karte, um die Koordinaten automatisch zu erfassen.', 'locatorpress' ); ?></p>
					</div>
				</div>

				<!-- Kontakt & Bild -->
				<div class="lp-admin-box">
					<h3><?php esc_html_e( 'Kontakt & Details', 'locatorpress' ); ?></h3>
					<div class="lp-admin-form-row">
						<div class="lp-admin-form-group">
							<label for="phone"><?php esc_html_e( 'Telefonnummer', 'locatorpress' ); ?></label>
							<input type="text" id="phone" name="phone" value="<?php echo esc_attr( $location['phone'] ); ?>" class="regular-text" />
						</div>
						<div class="lp-admin-form-group">
							<label for="email"><?php esc_html_e( 'E-Mail-Adresse', 'locatorpress' ); ?></label>
							<input type="email" id="email" name="email" value="<?php echo esc_attr( $location['email'] ); ?>" class="regular-text" />
						</div>
					</div>

					<div class="lp-admin-form-group">
						<label for="website"><?php esc_html_e( 'Website-URL', 'locatorpress' ); ?></label>
						<input type="url" id="website" name="website" value="<?php echo esc_attr( $location['website'] ); ?>" class="large-text" />
					</div>

					<!-- WordPress Medien Upload -->
					<div class="lp-admin-form-group">
						<label><?php esc_html_e( 'Standortbild', 'locatorpress' ); ?></label>
						<div class="lp-admin-image-upload-wrapper">
							<input type="hidden" id="lp-location-image-id" name="image_id" value="<?php echo esc_attr( $location['image_id'] ); ?>" />
							<div id="lp-location-image-preview" class="lp-admin-image-preview">
								<?php if ( $location['image_id'] > 0 ) : ?>
									<?php echo wp_get_attachment_image( $location['image_id'], 'medium' ); ?>
								<?php else : ?>
									<div class="lp-admin-no-image"><span class="dashicons dashicons-format-image"></span></div>
								<?php endif; ?>
							</div>
							<div class="lp-admin-image-buttons" style="margin-top: 10px;">
								<button type="button" id="lp-upload-image-button" class="button button-secondary"><?php esc_html_e( 'Bild auswählen', 'locatorpress' ); ?></button>
								<button type="button" id="lp-remove-image-button" class="button button-link-delete" style="<?php echo $location['image_id'] > 0 ? '' : 'display:none;'; ?>"><?php esc_html_e( 'Bild entfernen', 'locatorpress' ); ?></button>
							</div>
						</div>
					</div>
				</div>

				<!-- Opening Hours -->
				<div class="lp-oh-container">

					<!-- Header -->
					<div class="lp-oh-header">
						<div class="lp-oh-header-left">
							<div class="lp-oh-header-icon">
								<span class="dashicons dashicons-clock"></span>
							</div>
							<div>
								<h3 class="lp-oh-title"><?php esc_html_e( 'Opening Hours', 'locatorpress' ); ?></h3>
								<p class="lp-oh-subtitle"><?php esc_html_e( 'Set your business hours for each day of the week.', 'locatorpress' ); ?></p>
							</div>
						</div>
						<div class="lp-oh-add-all-wrap" id="lp-oh-add-all-wrap">
							<button type="button" class="lp-oh-add-all-btn" id="lp-oh-add-all-btn">
								<span class="dashicons dashicons-plus-alt2" style="font-size:14px;width:14px;height:14px;margin-right:4px;"></span>
								<?php esc_html_e( 'Add to All Days', 'locatorpress' ); ?>
								<span class="dashicons dashicons-arrow-down-alt2" style="font-size:13px;width:13px;height:13px;margin-left:6px;"></span>
							</button>
							<div class="lp-oh-add-all-menu" id="lp-oh-add-all-menu" style="display:none;">
								<div class="lp-oh-add-all-opt" data-target="all"><?php esc_html_e( 'All Days', 'locatorpress' ); ?></div>
								<div class="lp-oh-add-all-opt" data-target="weekdays"><?php esc_html_e( 'Weekdays Only (Mon–Fri)', 'locatorpress' ); ?></div>
								<div class="lp-oh-add-all-opt" data-target="weekends"><?php esc_html_e( 'Weekends Only (Sat–Sun)', 'locatorpress' ); ?></div>
							</div>
						</div>
					</div>

					<!-- Days List -->
					<div class="lp-oh-days-list" id="lp-oh-days-list">
						<?php
						$weekend_keys = [ 'saturday', 'sunday' ];
						foreach ( $days as $key => $name ) :
							$day_data = isset( $location['opening_hours'][ $key ] )
								? $location['opening_hours'][ $key ]
								: [ 'status' => 'closed', 'slots' => [ [ 'open' => '09:00', 'close' => '18:00' ] ] ];

							// Backward-compat: convert old flat format to slots.
							if ( isset( $day_data['open'] ) && ! isset( $day_data['slots'] ) ) {
								$day_data['slots'] = [ [ 'open' => $day_data['open'], 'close' => $day_data['close'] ] ];
							}
							if ( empty( $day_data['slots'] ) ) {
								$day_data['slots'] = [ [ 'open' => '09:00', 'close' => '18:00' ] ];
							}

							$is_open         = ( 'open' === $day_data['status'] );
							$is_open_all_day = ( 'open_all_day' === $day_data['status'] );
							$is_closed       = ( 'closed' === $day_data['status'] );
							$is_weekend      = in_array( $key, $weekend_keys, true ) ? '1' : '0';

							if ( $is_open_all_day ) {
								$status_label = esc_html__( 'Open all day', 'locatorpress' );
							} elseif ( $is_open ) {
								$status_label = esc_html__( 'Open', 'locatorpress' );
							} else {
								$status_label = esc_html__( 'Closed', 'locatorpress' );
							}
						?>
						<div class="lp-oh-day-row<?php echo ( $is_open || $is_open_all_day ) ? ' lp-oh-day--open' : ' lp-oh-day--closed'; ?>"
							 data-day="<?php echo esc_attr( $key ); ?>"
							 data-weekend="<?php echo esc_attr( $is_weekend ); ?>">

							<!-- Left: drag + name + status -->
							<div class="lp-oh-day-left">
								<span class="lp-oh-drag-handle" title="<?php esc_attr_e( 'Drag to reorder', 'locatorpress' ); ?>">
									<svg width="12" height="18" viewBox="0 0 12 18" fill="none" xmlns="http://www.w3.org/2000/svg" style="display: block;">
										<circle cx="2" cy="2" r="2" fill="#cbd5e1"/>
										<circle cx="2" cy="9" r="2" fill="#cbd5e1"/>
										<circle cx="2" cy="16" r="2" fill="#cbd5e1"/>
										<circle cx="10" cy="2" r="2" fill="#cbd5e1"/>
										<circle cx="10" cy="9" r="2" fill="#cbd5e1"/>
										<circle cx="10" cy="16" r="2" fill="#cbd5e1"/>
									</svg>
								</span>
								<span class="lp-oh-day-name"><?php echo esc_html( $name ); ?></span>

								<!-- Custom status dropdown -->
								<div class="lp-oh-status-wrap">
									<input type="hidden"
										   name="opening_hours[<?php echo esc_attr( $key ); ?>][status]"
										   value="<?php echo esc_attr( $day_data['status'] ); ?>"
										   class="lp-oh-status-input">
									<div class="lp-oh-status-btn" tabindex="0" role="button"
										 aria-haspopup="true" aria-expanded="false"
										 aria-label="<?php echo esc_attr( $status_label ); ?>">
										<span class="lp-oh-dot <?php echo ( $is_open || $is_open_all_day ) ? 'lp-oh-dot--open' : 'lp-oh-dot--closed'; ?>" <?php if ( $is_open_all_day ) echo 'style="background:#10b981;"'; ?>></span>
										<span class="lp-oh-status-label"><?php echo $status_label; ?></span>
										<span class="dashicons dashicons-arrow-down-alt2 lp-oh-status-arrow"></span>
									</div>
									<div class="lp-oh-status-menu" role="menu" style="display:none;">
										<div class="lp-oh-status-opt<?php echo $is_open ? ' lp-oh-status-opt--active' : ''; ?>"
											 data-val="open" role="menuitem">
											<span class="lp-oh-dot lp-oh-dot--open"></span>
											<?php esc_html_e( 'Open', 'locatorpress' ); ?>
										</div>
										<div class="lp-oh-status-opt<?php echo $is_open_all_day ? ' lp-oh-status-opt--active' : ''; ?>"
											 data-val="open_all_day" role="menuitem">
											<span class="lp-oh-dot lp-oh-dot--open" style="background:#10b981;"></span>
											<?php esc_html_e( 'Open all day', 'locatorpress' ); ?>
										</div>
										<div class="lp-oh-status-opt<?php echo $is_closed ? ' lp-oh-status-opt--active' : ''; ?>"
											 data-val="closed" role="menuitem">
											<span class="lp-oh-dot lp-oh-dot--closed"></span>
											<?php esc_html_e( 'Closed', 'locatorpress' ); ?>
										</div>
									</div>
								</div>

								<!-- Copy Hours Button -->
								<button type="button" class="lp-oh-copy-btn"
										data-day="<?php echo esc_attr( $key ); ?>"
										title="<?php esc_attr_e( 'Copy hours to other days', 'locatorpress' ); ?>"
										style="<?php echo ( $is_open || $is_open_all_day ) ? 'display:inline-flex;' : 'display:none;'; ?>">
									<span class="dashicons dashicons-admin-page"></span>
								</button>
							</div>

							<!-- Center: time slots or closed label -->
							<div class="lp-oh-day-center">
								<?php if ( $is_open ) : ?>
								<div class="lp-oh-slots-list">
									<?php foreach ( $day_data['slots'] as $si => $slot ) : ?>
									<div class="lp-oh-slot-row">
										<input type="time"
											   name="opening_hours[<?php echo esc_attr( $key ); ?>][slots][<?php echo esc_attr( $si ); ?>][open]"
											   value="<?php echo esc_attr( $slot['open'] ); ?>"
											   class="lp-oh-time-input">
										<span class="lp-oh-time-sep">–</span>
										<input type="time"
											   name="opening_hours[<?php echo esc_attr( $key ); ?>][slots][<?php echo esc_attr( $si ); ?>][close]"
											   value="<?php echo esc_attr( $slot['close'] ); ?>"
											   class="lp-oh-time-input">
										<button type="button"
												class="lp-oh-del-slot"
												title="<?php esc_attr_e( 'Remove time range', 'locatorpress' ); ?>">
											<span class="dashicons dashicons-trash"></span>
										</button>
									</div>
									<?php endforeach; ?>
								</div>
								<button type="button" class="lp-oh-add-range-btn" data-day="<?php echo esc_attr( $key ); ?>">
									<span class="dashicons dashicons-plus-alt2"></span>
									<?php esc_html_e( 'Add Time Range', 'locatorpress' ); ?>
								</button>
								<?php elseif ( $is_open_all_day ) : ?>
								<span class="lp-oh-open-all-day-label"><?php esc_html_e( 'Open all day', 'locatorpress' ); ?></span>
								<?php else : ?>
								<span class="lp-oh-closed-label"><?php esc_html_e( 'Closed all day', 'locatorpress' ); ?></span>
								<?php endif; ?>
							</div>

							<!-- Right: collapse toggle -->
							<button type="button" class="lp-oh-collapse-btn"
									title="<?php esc_attr_e( 'Toggle time slots', 'locatorpress' ); ?>">
								<span class="dashicons dashicons-arrow-down-alt2"></span>
							</button>
						</div>
						<?php endforeach; ?>
					</div>

					<!-- Footer -->
					<div class="lp-oh-footer">
						<div class="lp-oh-footer-tip">
							<span class="dashicons dashicons-info-outline"></span>
							<?php esc_html_e( 'Tip: Use "Add to All Days" to quickly set the same hours across multiple days.', 'locatorpress' ); ?>
						</div>
						<div class="lp-oh-footer-actions">
							<button type="button" class="button button-secondary lp-oh-reset-btn" id="lp-oh-reset-btn">
								<span class="dashicons dashicons-image-rotate" style="font-size:14px;width:14px;height:14px;margin-right:4px;vertical-align:middle;"></span>
								<?php esc_html_e( 'Reset to Default', 'locatorpress' ); ?>
							</button>
						</div>
					</div>
				</div><!-- .lp-oh-container -->

				<!-- Copy-Days Modal (hidden) -->
				<div id="lp-oh-copy-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;z-index:100000;background:rgba(0,0,0,0.45);align-items:center;justify-content:center;">
					<div style="background:#fff;border-radius:14px;padding:28px;max-width:440px;width:92%;box-shadow:0 24px 48px rgba(0,0,0,0.2);">
						<h3 style="margin:0 0 6px;font-size:16px;"><?php esc_html_e( 'Copy Hours To…', 'locatorpress' ); ?></h3>
						<p style="color:#64748b;margin:0 0 18px;font-size:13px;"><?php esc_html_e( 'Select the days you want to copy these hours to:', 'locatorpress' ); ?></p>
						<div id="lp-oh-copy-day-list" style="display:flex;flex-direction:column;gap:8px;margin-bottom:22px;">
							<?php foreach ( $days as $key => $name ) : ?>
							<label class="lp-oh-copy-label">
								<input type="checkbox" class="lp-oh-copy-target" value="<?php echo esc_attr( $key ); ?>">
								<?php echo esc_html( $name ); ?>
							</label>
							<?php endforeach; ?>
						</div>
						<div style="display:flex;gap:10px;justify-content:flex-end;">
							<button type="button" id="lp-oh-copy-cancel" class="button button-secondary"><?php esc_html_e( 'Cancel', 'locatorpress' ); ?></button>
							<button type="button" id="lp-oh-copy-confirm" class="button button-primary"><?php esc_html_e( 'Apply', 'locatorpress' ); ?></button>
						</div>
					</div>
				</div>
			</div>

			<div class="lp-admin-form-sidebar">
				<div class="lp-admin-box">
					<h3><?php esc_html_e( 'Veröffentlichung', 'locatorpress' ); ?></h3>
					
					<div class="lp-admin-form-group">
						<label for="status"><?php esc_html_e( 'Status', 'locatorpress' ); ?></label>
						<select id="status" name="status" class="postform">
							<option value="active" <?php selected( $location['status'], 'active' ); ?>><?php esc_html_e( 'Aktiv (Sichtbar)', 'locatorpress' ); ?></option>
							<option value="inactive" <?php selected( $location['status'], 'inactive' ); ?>><?php esc_html_e( 'Inaktiv (Ausgeblendet)', 'locatorpress' ); ?></option>
						</select>
					</div>

					<div class="lp-admin-form-group">
						<label for="region_id"><?php esc_html_e( 'Region', 'locatorpress' ); ?></label>
						<select id="region_id" name="region_id" class="postform">
							<option value="0"><?php esc_html_e( '— Keine Region —', 'locatorpress' ); ?></option>
							<?php foreach ( $flat_regions as $reg ) : ?>
								<option value="<?php echo esc_attr( $reg['id'] ); ?>" <?php selected( $location['region_id'], $reg['id'] ); ?>><?php echo esc_html( $reg['name'] ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>

					<?php do_action( 'locatorpress_location_form_sidebar', $location ); ?>

					<hr />

					<div class="lp-admin-sidebar-actions">
						<button type="submit" class="button button-primary button-large" style="width: 100%; text-align: center; justify-content: center; display: inline-flex;"><?php esc_html_e( 'Standort speichern', 'locatorpress' ); ?></button>
					</div>
				</div>
			</div>
		</form>
	</div>
	<?php
} else {
	// Listenansicht.
	$search       = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
	$region_id    = isset( $_GET['region'] ) ? intval( $_GET['region'] ) : 0;
	$status_val   = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';
	$paged        = isset( $_GET['paged'] ) ? intval( $_GET['paged'] ) : 1;
	$limit        = 20;
	$offset       = ( $paged - 1 ) * $limit;

	$args = [
		'search'  => $search,
		'region'  => $region_id,
		'status'  => $status_val,
		'limit'   => $limit,
		'offset'  => $offset,
		'orderby' => 'title',
		'order'   => 'ASC',
	];

	$locations = $controller->get_locations( $args );
	$total     = $controller->get_locations_count( $args );
	$num_pages = ceil( $total / $limit );
	?>
	<div class="lp-admin-wrap">
		<header class="lp-admin-header lp-admin-header--flex">
			<div>
				<h1 class="lp-admin-title"><?php esc_html_e( 'Standorte verwalten', 'locatorpress' ); ?></h1>
				<p class="lp-admin-subtitle"><?php esc_html_e( 'Hier sehen Sie alle eingetragenen Filialen und Geschäfte Ihres Locators.', 'locatorpress' ); ?></p>
			</div>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=locatorpress-locations&action=add' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Neuen Standort hinzufügen', 'locatorpress' ); ?></a>
		</header>

		<?php if ( isset( $_GET['deleted'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Standort erfolgreich gelöscht.', 'locatorpress' ); ?></p></div>
		<?php endif; ?>

		<!-- Filterbereich -->
		<div class="tablenav top">
			<form method="GET" action="">
				<input type="hidden" name="page" value="locatorpress-locations" />
				
				<div class="alignleft actions">
					<select name="region">
						<option value="0"><?php esc_html_e( 'Alle Regionen', 'locatorpress' ); ?></option>
						<?php foreach ( $flat_regions as $reg ) : ?>
							<option value="<?php echo esc_attr( $reg['id'] ); ?>" <?php selected( $region_id, $reg['id'] ); ?>><?php echo esc_html( $reg['name'] ); ?></option>
						<?php endforeach; ?>
					</select>

					<select name="status">
						<option value=""><?php esc_html_e( 'Alle Stati', 'locatorpress' ); ?></option>
						<option value="active" <?php selected( $status_val, 'active' ); ?>><?php esc_html_e( 'Aktiv', 'locatorpress' ); ?></option>
						<option value="inactive" <?php selected( $status_val, 'inactive' ); ?>><?php esc_html_e( 'Inaktiv', 'locatorpress' ); ?></option>
					</select>

					<button type="submit" class="button"><?php esc_html_e( 'Filtern', 'locatorpress' ); ?></button>
				</div>

				<p class="search-box">
					<label class="screen-reader-text" for="post-search-input"><?php esc_html_e( 'Standorte suchen:', 'locatorpress' ); ?></label>
					<input type="search" id="post-search-input" name="s" value="<?php echo esc_attr( $search ); ?>" />
					<input type="submit" id="search-submit" class="button" value="<?php esc_attr_e( 'Suchen', 'locatorpress' ); ?>" />
				</p>
			</form>
		</div>

		<!-- Datentabelle -->
		<table class="wp-list-table widefat fixed striped table-view-list posts">
			<thead>
				<tr>
					<th scope="col" class="manage-column column-title column-primary"><?php esc_html_e( 'Name', 'locatorpress' ); ?></th>
					<th scope="col" class="manage-column"><?php esc_html_e( 'Adresse', 'locatorpress' ); ?></th>
					<th scope="col" class="manage-column"><?php esc_html_e( 'Region', 'locatorpress' ); ?></th>
					<th scope="col" class="manage-column"><?php esc_html_e( 'Geokoordinaten', 'locatorpress' ); ?></th>
					<th scope="col" class="manage-column"><?php esc_html_e( 'Status', 'locatorpress' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( ! empty( $locations ) ) : ?>
					<?php foreach ( $locations as $loc ) : 
						$edit_url   = admin_url( 'admin.php?page=locatorpress-locations&action=edit&id=' . $loc['id'] );
						$delete_url = admin_url( 'admin.php?page=locatorpress-locations&lp_location_action=delete&id=' . $loc['id'] );
						$delete_url = wp_nonce_url( $delete_url, 'lp_delete_location_' . $loc['id'] );
						?>
						<tr>
							<td class="column-title column-primary has-row-actions">
								<strong><a href="<?php echo esc_url( $edit_url ); ?>" class="row-title"><?php echo esc_html( $loc['title'] ); ?></a></strong>
								<div class="row-actions">
									<span class="edit"><a href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Bearbeiten', 'locatorpress' ); ?></a> | </span>
									<span class="trash"><a href="<?php echo esc_url( $delete_url ); ?>" class="submitdelete" onclick="return confirm('<?php esc_attr_e( 'Sind Sie sicher?', 'locatorpress' ); ?>');"><?php esc_html_e( 'Löschen', 'locatorpress' ); ?></a></span>
								</div>
							</td>
							<td><?php echo esc_html( $loc['address'] ); ?></td>
							<td><?php echo esc_html( ! empty( $loc['region_name'] ) ? $loc['region_name'] : '—' ); ?></td>
							<td><code><?php echo esc_html( $loc['latitude'] . ', ' . $loc['longitude'] ); ?></code></td>
							<td>
								<span class="lp-admin-badge lp-admin-badge--<?php echo esc_attr( $loc['status'] ); ?>">
									<?php echo 'active' === $loc['status'] ? esc_html__( 'Aktiv', 'locatorpress' ) : esc_html__( 'Inaktiv', 'locatorpress' ); ?>
								</span>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr>
						<td colspan="5"><?php esc_html_e( 'Keine Standorte gefunden.', 'locatorpress' ); ?></td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>

		<!-- Pagination -->
		<?php if ( $num_pages > 1 ) : ?>
			<div class="tablenav bottom">
				<div class="tablenav-pages">
					<span class="displaying-num"><?php echo sprintf( esc_html__( '%d Einträge', 'locatorpress' ), $total ); ?></span>
					<span class="pagination-links">
						<?php for ( $i = 1; $i <= $num_pages; $i ++ ) : 
							$current_class = $paged === $i ? 'active-page' : '';
							$page_url      = add_query_arg( [ 'paged' => $i ], admin_url( 'admin.php?page=locatorpress-locations' ) );
							?>
							<a href="<?php echo esc_url( $page_url ); ?>" class="page-numbers <?php echo esc_attr( $current_class ); ?>"><?php echo esc_html( $i ); ?></a>
						<?php endfor; ?>
					</span>
				</div>
			</div>
		<?php endif; ?>
	</div>
	<?php
}
