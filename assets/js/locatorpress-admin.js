/**
 * LocatorPress Admin JavaScript
 * 
 * Verarbeitet die Admin-Interaktionen:
 * - WordPress Media Uploader für Standortbilder und Marker-Icons.
 * - Interaktiver Leaflet Map-Picker zur Festlegung von Koordinaten (Klick & Drag).
 * - Ein-/Ausblenden von Zeiten in den Öffnungszeiten.
 * - AJAX-basierter Batch-CSV-Importeur mit Echtzeit-Fortschrittsanzeige im Hintergrund.
 */
jQuery(document).ready(function($) {
	'use strict';

	// ==========================================
	// 1. MEDIEN-UPLOAD (WPMedia)
	// ==========================================
	function setupMediaUploader(buttonId, removeButtonId, hiddenInputId, previewContainerId, isMarker) {
		var fileFrame;

		$(buttonId).on('click', function(e) {
			e.preventDefault();

			// Falls der Frame bereits existiert, diesen öffnen.
			if (fileFrame) {
				fileFrame.open();
				return;
			}

			// Medien-Frame erstellen.
			fileFrame = wp.media({
				title: locatorPressAdmin.selectImageText,
				button: {
					text: locatorPressAdmin.useImageText
				},
				multiple: false
			});

			// Wenn ein Bild ausgewählt wird, Daten auslesen.
			fileFrame.on('select', function() {
				var attachment = fileFrame.state().get('selection').first().toJSON();
				$(hiddenInputId).val(attachment.id);

				var imgUrl = attachment.url;
				if (attachment.sizes && attachment.sizes.thumbnail) {
					imgUrl = attachment.sizes.thumbnail.url;
				}

				var imgHtml = '<img src="' + imgUrl + '" style="max-width: 100%; max-height: 100%; object-fit: cover;" />';
				$(previewContainerId).html(imgHtml);
				$(removeButtonId).show();
			});

			fileFrame.open();
		});

		// Bild entfernen.
		$(removeButtonId).on('click', function(e) {
			e.preventDefault();
			$(hiddenInputId).val(0);
			var fallbackIcon = isMarker ? 'dashicons-location' : 'dashicons-format-image';
			$(previewContainerId).html('<div class="lp-admin-no-image"><span class="dashicons ' + fallbackIcon + '"></span></div>');
			$(this).hide();
		});
	}

	// Media-Uploader für Standorte initialisieren.
	if ($('#lp-upload-image-button').length) {
		setupMediaUploader(
			'#lp-upload-image-button',
			'#lp-remove-image-button',
			'#lp-location-image-id',
			'#lp-location-image-preview',
			false
		);
	}

	// Media-Uploader für Einstellungs-Marker-Icon initialisieren.
	if ($('#lp-upload-marker-button').length) {
		setupMediaUploader(
			'#lp-upload-marker-button',
			'#lp-remove-marker-button',
			'#lp-settings-marker-id',
			'#lp-settings-marker-preview',
			true
		);
	}

	// ==========================================
	// 2. INTERAKTIVER KARTEN-PICKER (Leaflet)
	// ==========================================
	var mapPickerContainer = $('#lp-admin-map-picker');
	if (mapPickerContainer.length) {
		var latInput = $('#latitude');
		var lngInput = $('#longitude');

		var initLat = parseFloat(latInput.val()) || parseFloat(locatorPressAdmin.defaultCenterLat);
		var initLng = parseFloat(lngInput.val()) || parseFloat(locatorPressAdmin.defaultCenterLng);
		var initZoom = parseFloat(locatorPressAdmin.defaultZoom);

		// Leaflet-Karte initialisieren.
		var map = L.map('lp-admin-map-picker').setView([initLat, initLng], initZoom);

		L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
			attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
		}).addTo(map);

		// Marker hinzufügen.
		var marker = L.marker([initLat, initLng], {
			draggable: true
		}).addTo(map);

		// Hilfsfunktion zum Aktualisieren der Inputfelder.
		function updateCoordinates(lat, lng) {
			latInput.val(lat.toFixed(8));
			lngInput.val(lng.toFixed(8));
		}

		// Event: Marker ziehen (Drag).
		marker.on('dragend', function(e) {
			var position = marker.getLatLng();
			updateCoordinates(position.lat, position.lng);
		});

		// Event: Klick auf die Karte setzt Marker.
		map.on('click', function(e) {
			marker.setLatLng(e.latlng);
			updateCoordinates(e.latlng.lat, e.latlng.lng);
		});

		// ==========================================
		// AUTOMATISCHE GEOKODIERUNG BEI ADRESSEINGABE
		// ==========================================
		var addressInput = $('#address');
		var geocodeBtn = $('#lp-btn-geocode-address');

		function geocodeAddress() {
			var address = addressInput.val().trim();
			if (!address) {
				return;
			}

			geocodeBtn.prop('disabled', true).html('<span class="dashicons dashicons-update" style="margin-right: 4px; vertical-align: middle; animation: spin 2s infinite linear;"></span>...');

			$.ajax({
				url: locatorPressAdmin.ajaxUrl,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'lp_admin_geocode',
					security: locatorPressAdmin.securityNonce,
					address: address
				},
				success: function(response) {
					geocodeBtn.prop('disabled', false).html('<span class="dashicons dashicons-location-alt" style="margin-right: 4px; vertical-align: middle;"></span>' + (locatorPressAdmin.getCoordinatesText || 'Get Coordinates'));
					
					if (response.success && response.data) {
						var lat = parseFloat(response.data.lat);
						var lng = parseFloat(response.data.lng);
						
						updateCoordinates(lat, lng);
						marker.setLatLng([lat, lng]);
						map.setView([lat, lng], 15);
					} else {
						alert(locatorPressAdmin.geocodingFailed);
					}
				},
				error: function() {
					geocodeBtn.prop('disabled', false).html('<span class="dashicons dashicons-location-alt" style="margin-right: 4px; vertical-align: middle;"></span>' + (locatorPressAdmin.getCoordinatesText || 'Get Coordinates'));
					alert(locatorPressAdmin.geocodingFailed);
				}
			});
		}

		// Klick auf den Geokodieren-Button.
		geocodeBtn.on('click', function(e) {
			e.preventDefault();
			geocodeAddress();
		});

		// Automatischer Trigger wenn Fokus der Adresse verloren geht (Blur) und Koordinaten leer sind.
		addressInput.on('blur', function() {
			if (latInput.val().trim() === '' || lngInput.val().trim() === '') {
				geocodeAddress();
			}
		});
	}

	// ==========================================
	// 3. ÖFFNUNGSZEITEN STEUERUNG (Multi-Slot + Copy-to-Days)
	// ==========================================

	// Sortable initialization
	if ($('#lp-oh-days-list').length) {
		$('#lp-oh-days-list').sortable({
			handle: '.lp-oh-drag-handle',
			axis: 'y',
			opacity: 0.7,
			placeholder: 'lp-oh-day-row-placeholder'
		});
	}

	// Helper to get translated texts
	var t = {
		closedAllDay: locatorPressAdmin.closedAllDayText || 'Closed all day',
		openAllDay: locatorPressAdmin.openAllDayText || 'Open all day',
		addTimeRange: locatorPressAdmin.addTimeRangeText || 'Add Time Range',
		removeTimeRange: locatorPressAdmin.removeTimeRangeText || 'Remove time range',
		confirmReset: locatorPressAdmin.confirmResetText || 'Are you sure you want to reset opening hours to default?',
		open: locatorPressAdmin.openText || 'Open',
		closed: locatorPressAdmin.closedText || 'Closed'
	};

	// Toggle Custom Status Dropdown Menu
	$(document).on('click', '.lp-oh-status-btn', function(e) {
		e.stopPropagation();
		var $wrap = $(this).closest('.lp-oh-status-wrap');
		var $menu = $wrap.find('.lp-oh-status-menu');
		
		// Close all other menus
		$('.lp-oh-status-menu').not($menu).hide();
		$('.lp-oh-status-wrap').not($wrap).removeClass('lp-oh-dropdown-open');

		$wrap.toggleClass('lp-oh-dropdown-open');
		$menu.toggle();
	});

	// Select Custom Status Option
	$(document).on('click', '.lp-oh-status-opt', function(e) {
		e.stopPropagation();
		var $opt = $(this);
		var val = $opt.data('val');
		var $wrap = $opt.closest('.lp-oh-status-wrap');
		var $input = $wrap.find('.lp-oh-status-input');
		var $btn = $wrap.find('.lp-oh-status-btn');
		var $menu = $wrap.find('.lp-oh-status-menu');

		// Update active class
		$wrap.find('.lp-oh-status-opt').removeClass('lp-oh-status-opt--active');
		$opt.addClass('lp-oh-status-opt--active');

		// Update button dot class and text
		var dotClass = (val === 'open' || val === 'open_all_day') ? 'lp-oh-dot--open' : 'lp-oh-dot--closed';
		var labelText = val === 'open' ? t.open : (val === 'open_all_day' ? t.openAllDay : t.closed);
		
		$btn.find('.lp-oh-dot').removeClass('lp-oh-dot--open lp-oh-dot--closed').addClass(dotClass);
		if (val === 'open_all_day') {
			$btn.find('.lp-oh-dot').css('background', '#10b981');
		} else {
			$btn.find('.lp-oh-dot').css('background', '');
		}
		$btn.find('.lp-oh-status-label').text(labelText);
		$btn.attr('aria-label', labelText);

		// Hide menu
		$menu.hide();
		$wrap.removeClass('lp-oh-dropdown-open');

		// Set hidden input value and trigger change
		$input.val(val).trigger('change');
	});

	// Document click closes all custom menus
	$(document).on('click', function() {
		$('.lp-oh-status-menu').hide();
		$('.lp-oh-status-wrap').removeClass('lp-oh-dropdown-open');
		$('#lp-oh-add-all-menu').hide();
	});

	// Handle hidden status input changes
	$(document).on('change', '.lp-oh-status-input', function() {
		var $input = $(this);
		var val = $input.val();
		var $row = $input.closest('.lp-oh-day-row');
		var day = $row.data('day');
		var $center = $row.find('.lp-oh-day-center');
		var $copyBtn = $row.find('.lp-oh-copy-btn');

		if (val === 'open') {
			$row.removeClass('lp-oh-day--closed').addClass('lp-oh-day--open');
			$copyBtn.show();

			// Render slots if they don't exist
			if ($center.find('.lp-oh-slots-list').length === 0) {
				var slotsHtml = 
					'<div class="lp-oh-slots-list">' +
						'<div class="lp-oh-slot-row">' +
							'<input type="time" name="opening_hours[' + day + '][slots][0][open]" value="09:00" class="lp-oh-time-input" />' +
							'<span class="lp-oh-time-sep">–</span>' +
							'<input type="time" name="opening_hours[' + day + '][slots][0][close]" value="18:00" class="lp-oh-time-input" />' +
							'<button type="button" class="lp-oh-del-slot" title="' + t.removeTimeRange + '">' +
								'<span class="dashicons dashicons-trash"></span>' +
							'</button>' +
						'</div>' +
					'</div>' +
					'<button type="button" class="lp-oh-add-range-btn" data-day="' + day + '">' +
						'<span class="dashicons dashicons-plus-alt2"></span>' +
						' ' + t.addTimeRange +
					'</button>';
				$center.html(slotsHtml);
			}
		} else if (val === 'open_all_day') {
			$row.removeClass('lp-oh-day--closed').addClass('lp-oh-day--open');
			$copyBtn.show();
			$center.html('<span class="lp-oh-open-all-day-label">' + t.openAllDay + '</span>');
		} else {
			$row.removeClass('lp-oh-day--open').addClass('lp-oh-day--closed');
			$copyBtn.hide();
			$center.html('<span class="lp-oh-closed-label">' + t.closedAllDay + '</span>');
		}
	});

	// Add time range slot dynamically
	$(document).on('click', '.lp-oh-add-range-btn', function() {
		var day = $(this).data('day');
		var $center = $(this).closest('.lp-oh-day-center');
		var $list = $center.find('.lp-oh-slots-list');
		var newIndex = $list.find('.lp-oh-slot-row').length;

		var $newRow = $('<div class="lp-oh-slot-row">' +
			'<input type="time" name="opening_hours[' + day + '][slots][' + newIndex + '][open]" value="09:00" class="lp-oh-time-input" />' +
			'<span class="lp-oh-time-sep">–</span>' +
			'<input type="time" name="opening_hours[' + day + '][slots][' + newIndex + '][close]" value="18:00" class="lp-oh-time-input" />' +
			'<button type="button" class="lp-oh-del-slot" title="' + t.removeTimeRange + '">' +
				'<span class="dashicons dashicons-trash"></span>' +
			'</button>' +
		'</div>');

		$list.append($newRow);
		$newRow.hide().fadeIn(200);
	});

	// Remove slot dynamically
	$(document).on('click', '.lp-oh-del-slot', function() {
		var $row = $(this).closest('.lp-oh-slot-row');
		var $list = $row.closest('.lp-oh-slots-list');
		var day = $row.closest('.lp-oh-day-row').data('day');

		$row.fadeOut(200, function() {
			$row.remove();
			// Re-index remaining rows
			$list.find('.lp-oh-slot-row').each(function(idx) {
				$(this).find('input[type="time"]:first').attr('name', 'opening_hours[' + day + '][slots][' + idx + '][open]');
				$(this).find('input[type="time"]:last').attr('name', 'opening_hours[' + day + '][slots][' + idx + '][close]');
			});
		});
	});

	// Add to all days menu toggle
	$('#lp-oh-add-all-btn').on('click', function(e) {
		e.stopPropagation();
		$('#lp-oh-add-all-menu').toggle();
	});

	// Add to all days menu options handler
	$('.lp-oh-add-all-opt').on('click', function(e) {
		e.stopPropagation();
		var targetType = $(this).data('target'); // 'all', 'weekdays', 'weekends'
		$('#lp-oh-add-all-menu').hide();

		// Monday is the source
		var $monRow = $('.lp-oh-day-row[data-day="monday"]');
		var isMonOpen = $monRow.hasClass('lp-oh-day--open');
		
		// Read source slots
		var monSlots = [];
		if (isMonOpen) {
			$monRow.find('.lp-oh-slot-row').each(function() {
				monSlots.push({
					open: $(this).find('input[type="time"]:first').val(),
					close: $(this).find('input[type="time"]:last').val()
				});
			});
		}

		// Determine target days
		var targets = [];
		if (targetType === 'all') {
			targets = ['tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
		} else if (targetType === 'weekdays') {
			targets = ['tuesday', 'wednesday', 'thursday', 'friday'];
		} else if (targetType === 'weekends') {
			targets = ['saturday', 'sunday'];
		}

		targets.forEach(function(targetDay) {
			var $targetRow = $('.lp-oh-day-row[data-day="' + targetDay + '"]');
			var $targetInput = $targetRow.find('.lp-oh-status-input');
			var $targetCenter = $targetRow.find('.lp-oh-day-center');

			if (isMonOpen) {
				// Copy slots
				var slotsHtml = '<div class="lp-oh-slots-list">';
				monSlots.forEach(function(slot, idx) {
					slotsHtml += '<div class="lp-oh-slot-row">' +
						'<input type="time" name="opening_hours[' + targetDay + '][slots][' + idx + '][open]" value="' + slot.open + '" class="lp-oh-time-input" />' +
						'<span class="lp-oh-time-sep">–</span>' +
						'<input type="time" name="opening_hours[' + targetDay + '][slots][' + idx + '][close]" value="' + slot.close + '" class="lp-oh-time-input" />' +
						'<button type="button" class="lp-oh-del-slot" title="' + t.removeTimeRange + '">' +
							'<span class="dashicons dashicons-trash"></span>' +
						'</button>' +
					'</div>';
				});
				slotsHtml += '</div>' +
					'<button type="button" class="lp-oh-add-range-btn" data-day="' + targetDay + '">' +
						'<span class="dashicons dashicons-plus-alt2"></span>' +
						' ' + t.addTimeRange +
					'</button>';

				$targetCenter.html(slotsHtml);
				
				// Set custom dropdown to Open
				updateCustomStatusDropdown($targetRow, 'open');
			} else {
				// Set custom dropdown to Closed
				updateCustomStatusDropdown($targetRow, 'closed');
			}
		});
	});

	// Helper to update custom status dropdown programmatically
	function updateCustomStatusDropdown($row, statusVal) {
		var $wrap = $row.find('.lp-oh-status-wrap');
		var $btn = $wrap.find('.lp-oh-status-btn');
		var $opt = $wrap.find('.lp-oh-status-opt[data-val="' + statusVal + '"]');
		
		$wrap.find('.lp-oh-status-opt').removeClass('lp-oh-status-opt--active');
		$opt.addClass('lp-oh-status-opt--active');

		var dotClass = (statusVal === 'open' || statusVal === 'open_all_day') ? 'lp-oh-dot--open' : 'lp-oh-dot--closed';
		var labelText = statusVal === 'open' ? t.open : (statusVal === 'open_all_day' ? t.openAllDay : t.closed);

		$btn.find('.lp-oh-dot').removeClass('lp-oh-dot--open lp-oh-dot--closed').addClass(dotClass);
		if (statusVal === 'open_all_day') {
			$btn.find('.lp-oh-dot').css('background', '#10b981');
		} else {
			$btn.find('.lp-oh-dot').css('background', '');
		}
		$btn.find('.lp-oh-status-label').text(labelText);
		$btn.attr('aria-label', labelText);

		var $input = $wrap.find('.lp-oh-status-input');
		$input.val(statusVal).trigger('change');
	}

	// Reset to Default button
	$('#lp-oh-reset-btn').on('click', function() {
		if (confirm(t.confirmReset)) {
			var weekdays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
			var weekends = ['saturday', 'sunday'];

			weekdays.forEach(function(day) {
				var $row = $('.lp-oh-day-row[data-day="' + day + '"]');
				var $center = $row.find('.lp-oh-day-center');

				var slotsHtml = '<div class="lp-oh-slots-list">' +
					'<div class="lp-oh-slot-row">' +
						'<input type="time" name="opening_hours[' + day + '][slots][0][open]" value="09:00" class="lp-oh-time-input" />' +
						'<span class="lp-oh-time-sep">–</span>' +
						'<input type="time" name="opening_hours[' + day + '][slots][0][close]" value="18:00" class="lp-oh-time-input" />' +
						'<button type="button" class="lp-oh-del-slot" title="' + t.removeTimeRange + '">' +
							'<span class="dashicons dashicons-trash"></span>' +
						'</button>' +
					'</div>' +
				'</div>' +
				'<button type="button" class="lp-oh-add-range-btn" data-day="' + day + '">' +
					'<span class="dashicons dashicons-plus-alt2"></span>' +
					' ' + t.addTimeRange +
				'</button>';

				$center.html(slotsHtml);
				updateCustomStatusDropdown($row, 'open');
			});

			weekends.forEach(function(day) {
				var $row = $('.lp-oh-day-row[data-day="' + day + '"]');
				updateCustomStatusDropdown($row, 'closed');
			});
		}
	});

	// ---- Copy-to-Days Modal ----
	var lpCopySourceDay = null;

	$(document).on('click', '.lp-oh-copy-btn', function() {
		lpCopySourceDay = $(this).data('day');
		
		// Pre-uncheck all, and hide the source day
		$('#lp-oh-copy-day-list input[type="checkbox"]').prop('checked', false);
		$('#lp-oh-copy-day-list label').show();
		$('#lp-oh-copy-day-list input[value="' + lpCopySourceDay + '"]').closest('label').hide();
		$('#lp-oh-copy-modal').css('display', 'flex');
	});

	$('#lp-oh-copy-cancel').on('click', function() {
		$('#lp-oh-copy-modal').hide();
		lpCopySourceDay = null;
	});

	$('#lp-oh-copy-confirm').on('click', function() {
		if (!lpCopySourceDay) return;

		var $sourceRow = $('.lp-oh-day-row[data-day="' + lpCopySourceDay + '"]');
		var sourceStatus = $sourceRow.find('.lp-oh-status-input').val();
		var isSourceOpen = (sourceStatus === 'open' || sourceStatus === 'open_all_day');

		// Gather source slots
		var sourceSlots = [];
		if (sourceStatus === 'open') {
			$sourceRow.find('.lp-oh-slot-row').each(function() {
				sourceSlots.push({
					open: $(this).find('input[type="time"]:first').val(),
					close: $(this).find('input[type="time"]:last').val()
				});
			});
		}

		// Get target days
		var targets = [];
		$('#lp-oh-copy-day-list input:checked').each(function() {
			targets.push($(this).val());
		});

		targets.forEach(function(targetDay) {
			var $targetRow = $('.lp-oh-day-row[data-day="' + targetDay + '"]');
			var $targetCenter = $targetRow.find('.lp-oh-day-center');

			if (sourceStatus === 'open') {
				var slotsHtml = '<div class="lp-oh-slots-list">';
				sourceSlots.forEach(function(slot, idx) {
					slotsHtml += '<div class="lp-oh-slot-row">' +
						'<input type="time" name="opening_hours[' + targetDay + '][slots][' + idx + '][open]" value="' + slot.open + '" class="lp-oh-time-input" />' +
						'<span class="lp-oh-time-sep">–</span>' +
						'<input type="time" name="opening_hours[' + targetDay + '][slots][' + idx + '][close]" value="' + slot.close + '" class="lp-oh-time-input" />' +
						'<button type="button" class="lp-oh-del-slot" title="' + t.removeTimeRange + '">' +
							'<span class="dashicons dashicons-trash"></span>' +
						'</button>' +
					'</div>';
				});
				slotsHtml += '</div>' +
					'<button type="button" class="lp-oh-add-range-btn" data-day="' + targetDay + '">' +
						'<span class="dashicons dashicons-plus-alt2"></span>' +
						' ' + t.addTimeRange +
					'</button>';

				$targetCenter.html(slotsHtml);
				updateCustomStatusDropdown($targetRow, 'open');
			} else if (sourceStatus === 'open_all_day') {
				updateCustomStatusDropdown($targetRow, 'open_all_day');
			} else {
				updateCustomStatusDropdown($targetRow, 'closed');
			}
		});

		$('#lp-oh-copy-modal').hide();
		lpCopySourceDay = null;
	});

	// ==========================================
	// 4. AJAX CSV BATCH IMPORT
	// ==========================================
	$('#lp-csv-import-form').on('submit', function(e) {
		e.preventDefault();

		var fileInput = $('#lp_csv_file')[0];
		if (!fileInput.files.length) {
			alert(locatorPressAdmin.importCsvNoFile);
			return;
		}

		var file = fileInput.files[0];
		var autoGeocode = $('input[name="auto_geocode"]').is(':checked') ? 1 : 0;

		$('#lp-start-import-btn').prop('disabled', true);
		$('#lp-import-progress-container').slideDown(300);
		$('#lp-import-logs').html('<p style="color: #39b54a;">>>> ' + locatorPressAdmin.importStarting + '</p>');
		updateProgressBar(0);

		var reader = new FileReader();
		reader.onload = function(evt) {
			var csvContent = evt.target.result;
			processCsvData(csvContent, autoGeocode);
		};
		reader.readAsText(file, 'UTF-8');
	});

	function updateProgressBar(percent) {
		$('#lp-import-progress-bar').css('width', percent + '%');
	}

	function logMessage(msg, type) {
		var color = '#fff';
		if (type === 'success') color = '#39b54a';
		if (type === 'error') color = '#ff5c5c';
		if (type === 'info') color = '#8ce1fd';

		var logBox = $('#lp-import-logs');
		logBox.append('<p style="margin: 4px 0; color: ' + color + ';">' + msg + '</p>');
		logBox.scrollTop(logBox[0].scrollHeight);
	}

	function processCsvData(csvText, autoGeocode) {
		// CSV zeilenweise trennen.
		var lines = csvText.split(/\r\n|\n/);
		if (lines.length < 2) {
			logMessage(locatorPressAdmin.importNoData, 'error');
			$('#lp-start-import-btn').prop('disabled', false);
			return;
		}

		// Erste Zeile = Spaltennamen.
		var headers = parseCsvLine(lines[0]);
		var rows = [];

		for (var i = 1; i < lines.length; i++) {
			if (lines[i].trim() === '') continue;
			var fields = parseCsvLine(lines[i]);
			
			// Objekt aus Header und Zeile bauen.
			var rowObj = {};
			for (var j = 0; j < headers.length; j++) {
				var key = headers[j].trim().toLowerCase();
				rowObj[key] = fields[j] ? fields[j].trim() : '';
			}
			rows.push(rowObj);
		}

		if (rows.length === 0) {
			logMessage(locatorPressAdmin.importNoEntries, 'error');
			$('#lp-start-import-btn').prop('disabled', false);
			return;
		}

		var limit = (locatorPressAdmin.csvImportLimit !== undefined) ? parseInt(locatorPressAdmin.csvImportLimit) : 100;
		if (limit > 0 && rows.length > limit) {
			var limitMsg = locatorPressAdmin.importLimitMsg.replace('%d', limit);
			logMessage(limitMsg, 'error');
			alert(limitMsg);
			$('#lp-start-import-btn').prop('disabled', false);
			return;
		}

		logMessage(locatorPressAdmin.importRowsRead.replace('%d', rows.length), 'info');

		// Batching starten.
		var batchSize = 3; // Kleinere Batches, um Geocoding-Timeouts zu verhindern.
		var offset = 0;
		var total = rows.length;

		function sendNextBatch() {
			if (offset >= total) {
				logMessage(locatorPressAdmin.importComplete, 'success');
				$('#lp-import-status-text').text(locatorPressAdmin.importSuccess);
				$('#lp-start-import-btn').prop('disabled', false);
				updateProgressBar(100);
				return;
			}

			var batch = rows.slice(offset, offset + batchSize);
			var progressPercent = Math.round((offset / total) * 100);
			updateProgressBar(progressPercent);
			
			$('#lp-import-status-text').text(locatorPressAdmin.importProcessingRows.replace('%s', offset + 1).replace('%s', Math.min(offset + batchSize, total)).replace('%s', total));

			$.ajax({
				url: locatorPressAdmin.ajaxUrl,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'lp_import_csv_batch',
					security: locatorPressAdmin.securityNonce,
					batch: JSON.stringify(batch),
					auto_geocode: autoGeocode
				},
				success: function(response) {
					if (response.success) {
						if (response.data.logs && response.data.logs.length) {
							response.data.logs.forEach(function(log) {
								logMessage(log.message, log.type);
							});
						}
						offset += batch.length;
						// Nächsten Stapel senden.
						sendNextBatch();
					} else {
						logMessage(locatorPressAdmin.importBatchError + ' ' + (response.data.message || 'Unknown server error'), 'error');
						$('#lp-start-import-btn').prop('disabled', false);
					}
				},
				error: function() {
					logMessage(locatorPressAdmin.importNetworkError, 'error');
					$('#lp-start-import-btn').prop('disabled', false);
				}
			});
		}

		// Ersten Batch anstoßen.
		sendNextBatch();
	}

	/**
	 * Hilfsfunktion zum korrekten Parsen von CSV-Zeilen, die Anführungszeichen enthalten.
	 */
	function parseCsvLine(line) {
		var result = [];
		var current = '';
		var inQuotes = false;
		// Standardmäßig Komma oder Semikolon erkennen.
		var separator = line.indexOf(';') !== -1 ? ';' : ',';

		for (var i = 0; i < line.length; i++) {
			var char = line[i];
			if (char === '"') {
				inQuotes = !inQuotes;
			} else if (char === separator && !inQuotes) {
				result.push(current);
				current = '';
			} else {
				current += char;
			}
		}
		result.push(current);

		// Anführungszeichen säubern.
		return result.map(function(item) {
			return item.replace(/^"|"$/g, '').trim();
		});
	}

	// ==========================================
	// 5. SETTINGS: MARKER LIVE PREVIEW
	// ==========================================
	var previewContainer = $('#lp-settings-marker-fa-preview');
	if (previewContainer.length) {
		var faColorInput = $('#lp_marker_fa_color');
		var faBgColorInput = $('#lp_marker_fa_bg_color');
		var faSvgPath = previewContainer.find('svg path');
		var faSvgCircles = previewContainer.find('svg circle');

		function updateFaPreview() {
			var iconColor = faColorInput.val();
			var bgColor = faBgColorInput.val();

			// Update preview pin
			faSvgPath.attr('fill', iconColor);
			$(faSvgCircles[0]).attr('fill', bgColor);   // inner circle
			$(faSvgCircles[1]).attr('fill', iconColor); // center core dot
		}

		faColorInput.on('change input', updateFaPreview);
		faBgColorInput.on('change input', updateFaPreview);
		updateFaPreview();
	}

	// ==========================================
	// 6. SETTINGS: DYNAMIC API FIELDS SHOW/HIDE
	// ==========================================
	var mapProviderSelect = $('#lp_default_map_provider');
	var geocodeProviderSelect = $('#lp_geocoding_provider');
	var googleGroup = $('.lp-google-key-group');
	var goongGroups = $('.lp-goong-key-group');
	var apiDivider = $('.lp-api-divider');

	function toggleApiFields() {
		var mapProv = mapProviderSelect.val();
		var geoProv = geocodeProviderSelect.val();

		var showGoogle = (mapProv === 'google' || geoProv === 'google');
		var showGoong = (mapProv === 'goong' || geoProv === 'goong');

		if (showGoogle) {
			googleGroup.show();
		} else {
			googleGroup.hide();
		}

		if (showGoong) {
			goongGroups.show();
		} else {
			goongGroups.hide();
		}

		if (showGoogle || showGoong) {
			apiDivider.show();
		} else {
			apiDivider.hide();
		}
	}

	if (mapProviderSelect.length && geocodeProviderSelect.length) {
		mapProviderSelect.on('change', toggleApiFields);
		geocodeProviderSelect.on('change', toggleApiFields);
		toggleApiFields();
	}
});
