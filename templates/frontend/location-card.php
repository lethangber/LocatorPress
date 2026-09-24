<?php
// Direkten Zugriff verhindern.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Frontend-Template für eine Standortkarte in der Ergebnis-Seitenleiste.
 *
 * PC-Layout (Grid):
 *   Row1: [Bild] | [Titel + Adresse]    | [>]
 *   Row2: [Bild] | [● Status-Pill]      | [>]
 *   Row3: [Bild] | [📞 ✉ 🌐 Buttons]   | [>]
 *
 * Mobile-Layout (Grid):
 *   Row1: [Bild] | [Titel + Adresse]    | [>]
 *   Row2: [● Status-Pill – volle Breite]
 *   Row3: [─────── Divider ───────────]
 *   Row4: [ 📞 Anrufen | ✉ E-Mail | 🌐 Website ]
 */

// Status ermitteln.
$current_day = strtolower( gmdate( 'l', current_time( 'timestamp' ) ) );
$hours_data  = [];
if ( ! empty( $location['opening_hours'] ) ) {
	$hours_data = is_array( $location['opening_hours'] ) ? $location['opening_hours'] : json_decode( $location['opening_hours'], true );
}
$status_text  = esc_html__( 'Geschlossen', 'locatorpress' );
$status_class = 'closed';

if ( isset( $hours_data[ $current_day ] ) ) {
	$day_info = $hours_data[ $current_day ];
	if ( 'open_all_day' === $day_info['status'] ) {
		$status_text  = esc_html__( 'Open all day', 'locatorpress' );
		$status_class = 'open';
	} elseif ( 'open' === $day_info['status'] ) {
		$now_time = current_time( 'H:i' );
		$slots    = isset( $day_info['slots'] ) ? $day_info['slots'] : [];
		if ( empty( $slots ) && isset( $day_info['open'] ) ) {
			$slots = [ [ 'open' => $day_info['open'], 'close' => $day_info['close'] ] ];
		}
		foreach ( $slots as $slot ) {
			$open_time  = $slot['open'];
			$close_time = $slot['close'];
			if ( $now_time >= $open_time && $now_time <= $close_time ) {
				$status_text  = sprintf( esc_html__( 'Geöffnet bis %s', 'locatorpress' ), $close_time );
				$status_class = 'open';
				break;
			}
		}
		if ( 'closed' === $status_class && ! empty( $slots ) ) {
			$first_slot  = reset( $slots );
			$status_text = sprintf( esc_html__( 'Geschlossen (Öffnet um %s)', 'locatorpress' ), $first_slot['open'] );
		}
	}
}

// Distanz formatieren.
$distance_text = '';
if ( isset( $location['distance'] ) ) {
	$unit          = get_option( 'lp_radius_units', 'km' );
	$distance_text = round( floatval( $location['distance'] ), 1 ) . ' ' . $unit;
}
?>
<div class="lp-card" data-id="<?php echo esc_attr( $location['id'] ); ?>" data-lat="<?php echo esc_attr( $location['latitude'] ); ?>" data-lng="<?php echo esc_attr( $location['longitude'] ); ?>">

	<!-- Linke Spalte: Bild -->
	<div class="lp-card-left">
		<?php if ( get_option( 'lp_show_featured_image', 1 ) ) : ?>
			<div class="lp-card-media">
				<?php if ( isset( $index ) ) : ?>
					<div class="lp-card-number-badge"><?php echo intval( $index ); ?></div>
				<?php endif; ?>
				<?php if ( ! empty( $location['image_id'] ) ) : ?>
					<?php echo wp_get_attachment_image( $location['image_id'], 'medium' ); ?>
				<?php else : ?>
					<div class="lp-card-fallback-img"><i class="fa-solid fa-store"></i></div>
				<?php endif; ?>
			</div>
		<?php elseif ( isset( $index ) ) : ?>
			<div class="lp-card-number-badge lp-card-number-badge--standalone"><?php echo intval( $index ); ?></div>
		<?php endif; ?>
	</div>

	<!-- Mittlere Spalte: Titel + Adresse -->
	<div class="lp-card-info">
		<h4 class="lp-card-title">
			<?php echo esc_html( $location['title'] ); ?>
			<?php if ( ! empty( $distance_text ) ) : ?>
				<span class="lp-card-distance"><?php echo esc_html( $distance_text ); ?></span>
			<?php endif; ?>
		</h4>
		<?php if ( get_option( 'lp_show_address', 1 ) ) : ?>
			<p class="lp-card-address">
				<i class="fa-solid fa-location-dot lp-card-address-icon"></i>
				<span class="lp-card-address-text"><?php echo esc_html( $location['address'] ); ?></span>
			</p>
		<?php endif; ?>
	</div>

	<!-- Rechte Spalte: Chevron -->
	<div class="lp-card-chevron">
		<i class="fa-solid fa-chevron-right"></i>
	</div>

	<!-- Status-Badge (direktes Grid-Kind für flexible Platzierung) -->
	<?php if ( get_option( 'lp_show_opening_hours', 1 ) ) : ?>
		<div class="lp-card-status lp-card-status--<?php echo esc_attr( $status_class ); ?>">
			<span class="lp-status-dot"></span>
			<span class="lp-status-label"><?php echo esc_html( $status_text ); ?></span>
		</div>
	<?php endif; ?>

	<!-- Divider (nur Mobile sichtbar) -->
	<div class="lp-card-divider"></div>

	<!-- Aktions-Buttons -->
	<div class="lp-card-actions">
		<?php if ( get_option( 'lp_show_phone', 1 ) && ! empty( $location['phone'] ) ) : ?>
			<a href="tel:<?php echo esc_attr( $location['phone'] ); ?>" class="lp-card-btn lp-card-btn--phone" title="<?php esc_attr_e( 'Anrufen', 'locatorpress' ); ?>" onclick="event.stopPropagation();">
				<span class="lp-card-btn-icon"><i class="fa-solid fa-phone"></i></span>
				<span class="lp-card-btn-text"><?php esc_html_e( 'Anrufen', 'locatorpress' ); ?></span>
			</a>
		<?php endif; ?>
		<?php if ( get_option( 'lp_show_email', 1 ) && ! empty( $location['email'] ) ) : ?>
			<a href="mailto:<?php echo esc_attr( $location['email'] ); ?>" class="lp-card-btn lp-card-btn--email" title="<?php esc_attr_e( 'E-Mail senden', 'locatorpress' ); ?>" onclick="event.stopPropagation();">
				<span class="lp-card-btn-icon"><i class="fa-solid fa-envelope"></i></span>
				<span class="lp-card-btn-text"><?php esc_html_e( 'E-Mail', 'locatorpress' ); ?></span>
			</a>
		<?php endif; ?>
		<?php if ( get_option( 'lp_show_website', 1 ) && ! empty( $location['website'] ) ) : ?>
			<a href="<?php echo esc_url( $location['website'] ); ?>" target="_blank" rel="noopener noreferrer" class="lp-card-btn lp-card-btn--website" title="<?php esc_attr_e( 'Website besuchen', 'locatorpress' ); ?>" onclick="event.stopPropagation();">
				<span class="lp-card-btn-icon"><i class="fa-solid fa-globe"></i></span>
				<span class="lp-card-btn-text"><?php esc_html_e( 'Website', 'locatorpress' ); ?></span>
			</a>
		<?php endif; ?>
	</div>

</div>
