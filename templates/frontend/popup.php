<?php
// Direkten Zugriff verhindern.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Frontend-Template für das Marker-Popup (Infobox) auf der interaktiven Karte.
 * Wird über JavaScript bei Klick auf einen Pin dynamisch geladen und gerendert.
 */
?>
<div class="lp-popup">
	<?php if ( ! empty( $location['image_id'] ) ) : ?>
		<div class="lp-popup-image">
			<?php echo wp_get_attachment_image( $location['image_id'], 'medium' ); ?>
		</div>
	<?php endif; ?>
	
	<div class="lp-popup-content">
		<h4 class="lp-popup-title"><?php echo esc_html( $location['title'] ); ?></h4>
		<p class="lp-popup-address"><?php echo esc_html( $location['address'] ); ?></p>
		
		<?php if ( ! empty( $location['phone'] ) || ! empty( $location['website'] ) ) : ?>
			<div class="lp-popup-contact">
				<?php if ( ! empty( $location['phone'] ) ) : ?>
					<p class="lp-popup-phone">
						<i class="fa-solid fa-phone" style="margin-right: 4px; vertical-align: middle;"></i> 
						<a href="tel:<?php echo esc_attr( $location['phone'] ); ?>"><?php echo esc_html( $location['phone'] ); ?></a>
					</p>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<div class="lp-popup-actions" style="margin-top: 10px;">
			<a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo esc_attr( $location['latitude'] . ',' . $location['longitude'] ); ?>" 
			   target="_blank" 
			   rel="noopener noreferrer" 
			   class="lp-popup-btn lp-popup-btn--directions">
				<i class="fa-solid fa-diamond-turn-right"></i> <?php esc_html_e( 'Routenplaner', 'locatorpress' ); ?>
			</a>
		</div>
	</div>
</div>
