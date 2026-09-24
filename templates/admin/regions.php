<?php
// Direkten Zugriff verhindern.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$controller = \LocatorPress\Admin\Region_Controller::get_instance();

$action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : 'list';
$id     = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

$editing_region = null;
if ( $id > 0 ) {
	$editing_region = $controller->get_region( $id );
}

// Regionen abfragen und hierarchisch aufbereiten.
$regions      = $controller->get_regions();
$tree         = $controller->build_region_tree( $regions );
$flat_regions = [];
$controller->flatten_tree( $tree, $flat_regions );

// Für die Eltern-Selektionsbox.
$parent_regions = $flat_regions;
if ( $editing_region ) {
	// Verhindern, dass die aktuelle Region oder deren Kinder als Elternteil gewählt werden können.
	$parent_regions = array_filter( $flat_regions, function( $reg ) use ( $editing_region ) {
		return intval( $reg['id'] ) !== intval( $editing_region['id'] );
	} );
}
?>

<div class="lp-admin-wrap">
	<header class="lp-admin-header">
		<h1 class="lp-admin-title"><?php esc_html_e( 'Regionen verwalten', 'locatorpress' ); ?></h1>
		<p class="lp-admin-subtitle"><?php esc_html_e( 'Erstellen Sie eine hierarchische Gliederung (z. B. Land -> Bundesland -> Stadt) zur Organisation Ihrer Standorte.', 'locatorpress' ); ?></p>
	</header>

	<?php if ( isset( $_GET['deleted'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Region erfolgreich gelöscht.', 'locatorpress' ); ?></p></div>
	<?php endif; ?>
	<?php if ( isset( $_GET['created'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Region erfolgreich angelegt.', 'locatorpress' ); ?></p></div>
	<?php endif; ?>
	<?php if ( isset( $_GET['updated'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Region erfolgreich aktualisiert.', 'locatorpress' ); ?></p></div>
	<?php endif; ?>

	<div class="lp-admin-columns">
		<!-- Linke Spalte: Formular zum Hinzufügen / Bearbeiten -->
		<div class="lp-admin-column-sidebar">
			<div class="lp-admin-box">
				<h3><?php echo $editing_region ? esc_html__( 'Region bearbeiten', 'locatorpress' ) : esc_html__( 'Neue Region hinzufügen', 'locatorpress' ); ?></h3>
				
				<form method="POST" action="">
					<input type="hidden" name="lp_region_action" value="save" />
					<input type="hidden" name="id" value="<?php echo esc_attr( $editing_region ? $editing_region['id'] : 0 ); ?>" />
					<?php wp_nonce_field( 'lp_save_region', 'lp_region_nonce' ); ?>

					<div class="lp-admin-form-group">
						<label for="region_name"><?php esc_html_e( 'Name der Region *', 'locatorpress' ); ?></label>
						<input type="text" id="region_name" name="name" value="<?php echo esc_attr( $editing_region ? $editing_region['name'] : '' ); ?>" required class="large-text" />
					</div>

					<div class="lp-admin-form-group">
						<label for="region_slug"><?php esc_html_e( 'Slug (URL-freundlich)', 'locatorpress' ); ?></label>
						<input type="text" id="region_slug" name="slug" value="<?php echo esc_attr( $editing_region ? $editing_region['slug'] : '' ); ?>" class="large-text" />
						<p class="description"><?php esc_html_e( 'Die URL-taugliche Variante des Namens. Standardmäßig automatisch generiert.', 'locatorpress' ); ?></p>
					</div>

					<div class="lp-admin-form-group">
						<label for="region_parent"><?php esc_html_e( 'Übergeordnete Region', 'locatorpress' ); ?></label>
						<select id="region_parent" name="parent_id" class="postform">
							<option value="0"><?php esc_html_e( 'Keine (Oberste Ebene)', 'locatorpress' ); ?></option>
							<?php foreach ( $parent_regions as $reg ) : ?>
								<option value="<?php echo esc_attr( $reg['id'] ); ?>" <?php selected( $editing_region ? $editing_region['parent_id'] : 0, $reg['id'] ); ?>><?php echo esc_html( $reg['name'] ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="lp-admin-form-group">
						<label for="region_description"><?php esc_html_e( 'Beschreibung', 'locatorpress' ); ?></label>
						<textarea id="region_description" name="description" rows="4" class="large-text"><?php echo esc_textarea( $editing_region ? $editing_region['description'] : '' ); ?></textarea>
					</div>

					<div class="lp-admin-form-actions">
						<button type="submit" class="button button-primary"><?php echo $editing_region ? esc_html__( 'Region aktualisieren', 'locatorpress' ) : esc_html__( 'Region anlegen', 'locatorpress' ); ?></button>
						<?php if ( $editing_region ) : ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=locatorpress-regions' ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Abbrechen', 'locatorpress' ); ?></a>
						<?php endif; ?>
					</div>
				</form>
			</div>
		</div>

		<!-- Rechte Spalte: Baumliste der Regionen -->
		<div class="lp-admin-column-main">
			<div class="lp-admin-box">
				<h3><?php esc_html_e( 'Alle Regionen', 'locatorpress' ); ?></h3>
				
				<table class="wp-list-table widefat fixed striped table-view-list tags">
					<thead>
						<tr>
							<th scope="col" class="manage-column column-title column-primary"><?php esc_html_e( 'Name', 'locatorpress' ); ?></th>
							<th scope="col" class="manage-column"><?php esc_html_e( 'Beschreibung', 'locatorpress' ); ?></th>
							<th scope="col" class="manage-column"><?php esc_html_e( 'Slug', 'locatorpress' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( ! empty( $flat_regions ) ) : ?>
							<?php foreach ( $flat_regions as $reg ) : 
								$edit_url   = admin_url( 'admin.php?page=locatorpress-regions&action=edit&id=' . $reg['id'] );
								$delete_url = admin_url( 'admin.php?page=locatorpress-regions&lp_region_action=delete&id=' . $reg['id'] );
								$delete_url = wp_nonce_url( $delete_url, 'lp_delete_region_' . $reg['id'] );
								
								// Die originale Region laden für Beschreibung.
								$orig_reg = array_filter( $regions, function( $r ) use ( $reg ) {
									return intval( $r['id'] ) === intval( $reg['id'] );
								} );
								$orig_reg = reset( $orig_reg );
								?>
								<tr>
									<td class="column-title column-primary has-row-actions">
										<strong><a href="<?php echo esc_url( $edit_url ); ?>"><?php echo esc_html( $reg['name'] ); ?></a></strong>
										<div class="row-actions">
											<span class="edit"><a href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Bearbeiten', 'locatorpress' ); ?></a> | </span>
											<span class="trash"><a href="<?php echo esc_url( $delete_url ); ?>" class="submitdelete" onclick="return confirm('<?php esc_attr_e( 'Sind Sie sicher? Alle Unterregionen werden nach oben verschoben und die zugewiesenen Standorte werden unkategorisiert.', 'locatorpress' ); ?>');"><?php esc_html_e( 'Löschen', 'locatorpress' ); ?></a></span>
										</div>
									</td>
									<td><?php echo esc_html( ! empty( $orig_reg['description'] ) ? $orig_reg['description'] : '—' ); ?></td>
									<td><code><?php echo esc_html( $reg['slug'] ); ?></code></td>
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr>
								<td colspan="3"><?php esc_html_e( 'Keine Regionen angelegt.', 'locatorpress' ); ?></td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
