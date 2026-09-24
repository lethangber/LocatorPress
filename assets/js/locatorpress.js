/**
 * LocatorPress Frontend JavaScript
 * 
 * Steuert das interaktive Verhalten des Store Locators im Frontend:
 * - Unterstützt Leaflet, Google Maps und Goong Maps.
 * - AJAX-basierte Live-Suchen mit Debouncing und Abbrechen von veralteten Anfragen.
 * - Marker-Clustering für hohe Performance.
 * - GPS-Ortung („Meinen Standort ermitteln“).
 * - Interaktive Verknüpfung zwischen Seitenleisten-Ergebnissen und Karten-Pins.
 */
jQuery(document).ready(function($) {
	'use strict';

	// Direkte Ausführung verhindern, falls das Shortcode-Element fehlt.
	var wrapper = $('#locatorpress-wrapper');
	if (!wrapper.length) {
		return;
	}

	// ==========================================
	// CONFIGURATION & STATE
	// ==========================================
	var state = {
		mapInstance: null,
		markers: [],
		clusterGroup: null,
		activeRequest: null,
		userCoords: null,
		mapProvider: locatorPress.provider,
		defaultZoom: parseInt(wrapper.data('zoom')) || 12,
		centerLat: parseFloat(wrapper.data('lat')) || 48.135125,
		centerLng: parseFloat(wrapper.data('lng')) || 11.581981,
		predefinedRegion: parseInt(wrapper.data('region')) || 0,
		isAutoScrolling: false,
		activeMarkerId: null
	};

	// DOM Referenzen
	var ui = {
		form: $('#lp-search-form'),
		keyword: $('#lp-search-keyword'),
		region: $('#lp-search-region'),
		radius: $('#lp-search-radius'),
		nearMeBtn: $('#lp-btn-near-me'),
		resultsList: $('#lp-location-results-list'),
		resultsCount: $('#lp-results-count-text')
	};

	// Falls im Shortcode eine Region vordefiniert wurde, diese als Standard setzen.
	if (state.predefinedRegion > 0) {
		ui.region.val(state.predefinedRegion);
	}

	// Karte initialisieren.
	initMap();

	// Erste Abfrage beim Laden der Seite ausführen.
	triggerSearch();

	// ==========================================
	// 1. KARTEN-INITIALISIERUNG
	// ==========================================
	function initMap() {
		try {
			if (state.mapProvider === 'leaflet') {
				initLeaflet();
			} else if (state.mapProvider === 'google') {
				// Google Maps wird asynchron geladen, wir prüfen die Verfügbarkeit.
				if (typeof google !== 'undefined' && google.maps) {
					initGoogle();
				} else {
					// Fallback zu Leaflet falls Google nicht geladen wurde.
					state.mapProvider = 'leaflet';
					initLeaflet();
				}
			} else if (state.mapProvider === 'goong') {
				if (typeof goongjs !== 'undefined') {
					initGoong();
				} else {
					state.mapProvider = 'leaflet';
					initLeaflet();
				}
			}
		} catch (e) {
			console.error('Fehler beim Initialisieren der Karte:', e);
		}
	}

	// 1.1 Leaflet Initialisierung
	function initLeaflet() {
		state.mapInstance = L.map('locatorpress-map').setView([state.centerLat, state.centerLng], state.defaultZoom);

		L.tileLayer(locatorPress.mapConfig.tileUrl, {
			attribution: locatorPress.mapConfig.attribution
		}).addTo(state.mapInstance);

		if (locatorPress.enableClustering) {
			state.clusterGroup = L.markerClusterGroup();
			state.mapInstance.addLayer(state.clusterGroup);
		}

		// Register popup close to deactivate active marker style
		state.mapInstance.on('popupclose', function(e) {
			if (e.popup && e.popup._source) {
				var m = e.popup._source;
				if (m.lpId) {
					deactivateMarker(m.lpId);
					$('.lp-card[data-id="' + m.lpId + '"]').removeClass('lp-card--active');
				}
			}
		});

		window.lpMapInstance = state.mapInstance;
		window.lpMapProvider = state.mapProvider;
		window.lpState = state;
	}

	// 1.2 Google Maps Initialisierung
	function initGoogle() {
		state.mapInstance = new google.maps.Map(document.getElementById('locatorpress-map'), {
			center: { lat: state.centerLat, lng: state.centerLng },
			zoom: state.defaultZoom,
			mapTypeControl: false,
			streetViewControl: false
		});

		window.lpMapInstance = state.mapInstance;
		window.lpMapProvider = state.mapProvider;
		window.lpState = state;
	}

	// 1.3 Goong Maps (Mapbox Fork) Initialisierung
	function initGoong() {
		goongjs.accessToken = locatorPress.mapConfig.mapToken;
		state.mapInstance = new goongjs.Map({
			container: 'locatorpress-map',
			style: 'https://tiles.goong.io/assets/goong_map_web.json',
			center: [state.centerLng, state.centerLat],
			zoom: state.defaultZoom
		});

		window.lpMapInstance = state.mapInstance;
		window.lpMapProvider = state.mapProvider;
		window.lpState = state;
	}

	// ==========================================
	// 2. AJAX LIVE-SUCHE MIT RACING-VERHINDERUNG
	// ==========================================
	function triggerSearch() {
		// Eventuelle vorherige unvollständige Anfragen abbrechen (Race Conditions verhindern).
		if (state.activeRequest) {
			state.activeRequest.abort();
		}

		showLoader();

		var searchKeyword = ui.keyword.val() || '';
		var searchRegion = ui.region.val() || 0;
		var searchRadius = ui.radius.val() || 0;

		var ajaxData = {
			action: 'lp_search_locations',
			security: locatorPress.nonce,
			keyword: searchKeyword,
			region: searchRegion,
			radius: searchRadius
		};

		// Add Pro fields if they exist
		if (jQuery('#lp-filter-open-now').length) {
			ajaxData.open_now = jQuery('#lp-filter-open-now').is(':checked') ? 1 : 0;
		}
		if (jQuery('#lp-filter-tags').length) {
			ajaxData.tag_id = jQuery('#lp-filter-tags').val();
		}

		// Ortungskoordinaten mitsenden, falls vorhanden.
		if (state.userCoords) {
			ajaxData.lat = state.userCoords.lat;
			ajaxData.lng = state.userCoords.lng;
		}

		state.activeRequest = $.ajax({
			url: locatorPress.ajaxUrl,
			type: 'POST',
			dataType: 'json',
			data: ajaxData,
			success: function(response) {
				if (response.success) {
					renderResults(response.data);
				} else {
					ui.resultsList.html('<p class="lp-no-results">' + locatorPress.strings.noResults + '</p>');
				}
			},
			error: function(xhr, status) {
				if (status !== 'abort') {
					ui.resultsList.html('<p class="lp-no-results">' + locatorPress.strings.noResults + '</p>');
				}
			}
		});
	}

	// Debounce Helper zur Entlastung des Servers bei Tastatureingabe.
	function debounce(func, wait) {
		var timeout;
		return function() {
			var context = this, args = arguments;
			clearTimeout(timeout);
			timeout = setTimeout(function() {
				func.apply(context, args);
			}, wait);
		};
	}

	// Event Listener für Echtzeitsuche.
	ui.keyword.on('input', debounce(function() {
		// Bei leerem Input die Benutzerortung wieder vergessen, damit wieder global gesucht wird.
		if ($(this).val() === '') {
			state.userCoords = null;
		}
		triggerSearch();
	}, 400));

	ui.region.on('change', triggerSearch);
	ui.radius.on('change', triggerSearch);
	jQuery(document).on('change', '#lp-filter-open-now, #lp-filter-tags', triggerSearch);
	ui.form.on('submit', function(e) {
		e.preventDefault();
		triggerSearch();
	});

	// ==========================================
	// 3. ERGEBNISSE DARSTELLEN (RENDERING)
	// ==========================================
	function renderResults(data) {
		// Seitenleiste rendern.
		if (data.count > 0) {
			ui.resultsCount.text(data.count + ' ' + (data.count === 1 ? locatorPress.strings.resultSingular : locatorPress.strings.resultPlural));
			ui.resultsList.html(data.html);
		} else {
			ui.resultsCount.text(locatorPress.strings.resultZero);
			ui.resultsList.html('<p class="lp-no-results">' + locatorPress.strings.noResults + '</p>');
		}

		updateResultsMeta();

		// Marker auf der Karte aktualisieren.
		clearMarkers();
		
		if (data.locations && data.locations.length > 0) {
			addMarkersToMap(data.locations);
		}
	}

	function updateResultsMeta() {
		var regionText = '';
		var radiusText = '';

		if (ui.region.length) {
			regionText = ui.region.find('option:selected').text();
		}
		if (ui.radius.length) {
			var radiusVal = ui.radius.val();
			if (radiusVal === '0') {
				radiusText = ui.radius.find('option:selected').text();
			} else {
				var unit = locatorPress.radiusUnits || 'km';
				var withinStr = locatorPress.strings.within || 'Within';
				radiusText = withinStr + ' ' + radiusVal + ' ' + unit;
			}
		}

		var metaParts = [];
		if (regionText) metaParts.push(regionText);
		if (radiusText) metaParts.push(radiusText);

		$('#lp-results-meta-text').text(metaParts.join(' • '));
	}

	function showLoader() {
		ui.resultsList.html('<div class="lp-loader-placeholder"><div class="lp-pulse-loader"></div></div>');
	}

	// ==========================================
	// 4. MARKER-STEUERUNG & INTERAKTIONEN
	// ==========================================
	// Clear all map markers
	function clearMarkers() {
		if (state.mapProvider === 'leaflet') {
			if (locatorPress.enableClustering && state.clusterGroup) {
				state.clusterGroup.clearLayers();
			} else {
				state.markers.forEach(function(marker) {
					state.mapInstance.removeLayer(marker);
				});
			}
		} else if (state.mapProvider === 'google') {
			state.markers.forEach(function(marker) {
				marker.setMap(null);
			});
		} else if (state.mapProvider === 'goong') {
			state.markers.forEach(function(marker) {
				marker.remove();
			});
		}
		state.markers = [];
		state.activeMarkerId = null;
	}

	function hexToRgb(hex) {
		var shorthandRegex = /^#?([a-f\d])([a-f\d])([a-f\d])$/i;
		hex = hex.replace(shorthandRegex, function(m, r, g, b) {
			return r + r + g + g + b + b;
		});
		var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
		return result ? {
			r: parseInt(result[1], 16),
			g: parseInt(result[2], 16),
			b: parseInt(result[3], 16)
		} : null;
	}

	function activateMarker(id) {
		if (state.activeMarkerId && state.activeMarkerId !== id) {
			deactivateMarker(state.activeMarkerId);
		}
		state.activeMarkerId = id;
		var marker = state.markers.find(function(m) { return m.lpId === id; });
		if (!marker) return;

		if (state.mapProvider === 'leaflet') {
			if (marker._icon) {
				$(marker._icon).addClass('lp-marker--active');
				marker.setZIndexOffset(1000);
			}
		} else if (state.mapProvider === 'goong') {
			var el = marker.getElement();
			if (el) {
				$(el).addClass('lp-marker--active');
			}
		} else if (state.mapProvider === 'google') {
			marker.setZIndex(google.maps.Marker.MAX_ZINDEX + 100);
		}
	}

	function deactivateMarker(id) {
		if (state.activeMarkerId === id) {
			state.activeMarkerId = null;
		}
		var marker = state.markers.find(function(m) { return m.lpId === id; });
		if (!marker) return;

		if (state.mapProvider === 'leaflet') {
			if (marker._icon) {
				$(marker._icon).removeClass('lp-marker--active');
				marker.setZIndexOffset(0);
			}
		} else if (state.mapProvider === 'goong') {
			var el = marker.getElement();
			if (el) {
				$(el).removeClass('lp-marker--active');
			}
		} else if (state.mapProvider === 'google') {
			marker.setZIndex(null);
		}
	}

	function getFaUnicode(iconClass) {
		var mapping = {
			'fa-location-dot': '\uf3c5',
			'fa-map-marker-alt': '\uf3c5',
			'fa-store': '\uf54e',
			'fa-building': '\uf1ad',
			'fa-hospital': '\uf0f8',
			'fa-school': '\uf549',
			'fa-church': '\uf51d',
			'fa-hotel': '\uf594',
			'fa-utensils': '\uf2e7',
			'fa-coffee': '\uf0f4',
			'fa-gas-pump': '\uf52f',
			'fa-car': '\uf1b9',
			'fa-bus': '\uf207',
			'fa-plane': '\uf072',
			'fa-train': '\uf238',
			'fa-bicycle': '\uf206',
			'fa-shopping-cart': '\uf07a',
			'fa-shopping-bag': '\uf290',
			'fa-home': '\uf015',
			'fa-heart': '\uf004',
			'fa-star': '\uf005',
			'fa-phone': '\uf095',
			'fa-envelope': '\uf0e0',
			'fa-globe': '\uf0ac',
			'fa-wifi': '\uf1eb',
			'fa-bank': '\uf19c',
			'fa-university': '\uf19c',
			'fa-industry': '\uf275',
			'fa-warehouse': '\uf494',
			'fa-dumbbell': '\uf44b',
			'fa-swimming-pool': '\uf5c5',
			'fa-tree': '\uf1bb',
			'fa-park': '\uf1bb',
			'fa-map': '\uf279',
			'fa-compass': '\uf14e',
			'fa-flag': '\uf024',
			'fa-info-circle': '\uf05a',
			'fa-camera': '\uf030',
			'fa-music': '\uf001',
			'fa-film': '\uf008',
			'fa-book': '\uf02d',
			'fa-graduation-cap': '\uf19d',
			'fa-stethoscope': '\uf0f1',
			'fa-pills': '\uf484',
			'fa-ambulance': '\uf0f9',
			'fa-fire-extinguisher': '\uf134',
			'fa-tools': '\uf7d9',
			'fa-wrench': '\uf0ad',
			'fa-hammer': '\uf6e3',
			'fa-recycle': '\uf1b8',
			'fa-leaf': '\uf06c',
			'fa-sun': '\uf185',
			'fa-snowflake': '\uf2dc',
			'fa-paw': '\uf1b0',
			'fa-wine-glass': '\uf4e3',
			'fa-pizza-slice': '\uf818',
			'fa-ice-cream': '\uf810',
			'fa-beer': '\uf0fc',
			'fa-tshirt': '\uf553',
			'fa-gem': '\uf3a5'
		};
		var classes = iconClass.split(' ');
		for (var i = 0; i < classes.length; i++) {
			var cls = classes[i];
			if (mapping[cls]) {
				return mapping[cls];
			}
			var cleanCls = cls.replace('fa-', '');
			if (mapping[cleanCls]) {
				return mapping[cleanCls];
			}
			if (mapping['fa-' + cleanCls]) {
				return mapping['fa-' + cleanCls];
			}
		}
		return '\uf3c5';
	}

	function addMarkersToMap(locations) {
		var bounds = [];

		// Custom Marker Definition.
		var markerIconUrl = locatorPress.markerIconUrl;

		locations.forEach(function(loc, index) {
			var lat = parseFloat(loc.latitude);
			var lng = parseFloat(loc.longitude);
			var num = loc.index || (index + 1);

			if (isNaN(lat) || isNaN(lng)) return;

			bounds.push([lat, lng]);

			if (state.mapProvider === 'leaflet') {
				var leafIcon;
				var markerColor = locatorPress.markerFaColor || '#2563eb';
				var markerBgColor = locatorPress.markerFaBgColor || '#ffffff';
				var rgb = hexToRgb(markerColor);
				var rgbStr = rgb ? rgb.r + ', ' + rgb.g + ', ' + rgb.b : '37, 99, 235';

				var pinHtml = '<div class="lp-marker-pin-wrapper" style="--marker-color: ' + markerColor + '; --marker-color-rgb: ' + rgbStr + ';">' +
					'<div class="lp-marker-ripple-ring lp-ripple-1"></div>' +
					'<div class="lp-marker-ripple-ring lp-ripple-2"></div>' +
					'<div class="lp-marker-pin">' +
						'<svg class="lp-marker-pin-svg" viewBox="0 0 36 46" width="36" height="46">' +
							'<path d="M18 0C8.1 0 0 8.1 0 18c0 12.6 15.5 26.6 17.1 27.8a1.5 1.5 0 0 0 1.8 0C20.5 44.6 36 30.6 36 18c0-9.9-8.1-18-18-18z" fill="' + markerColor + '" stroke="#ffffff" stroke-width="1.5"></path>' +
							'<circle cx="18" cy="18" r="11" fill="' + markerBgColor + '"></circle>' +
							'<circle cx="18" cy="18" r="5" fill="' + markerColor + '"></circle>' +
						'</svg>' +
					'</div>' +
				'</div>';

				leafIcon = L.divIcon({
					html: pinHtml,
					className: 'lp-custom-div-icon',
					iconSize: [36, 46],
					iconAnchor: [18, 46],
					popupAnchor: [0, -42]
				});

				var marker = L.marker([lat, lng], { icon: leafIcon }).bindPopup(loc.popup_html);
				
				// Marker ID mitspeichern zur Synchronisierung.
				marker.lpId = loc.id;

				marker.on('click', function() {
					highlightSidebarCard(loc.id);
					activateMarker(loc.id);
				});

				marker.on('mouseover', function() {
					$('.lp-card[data-id="' + loc.id + '"]').addClass('lp-card--hover');
				});
				marker.on('mouseout', function() {
					$('.lp-card[data-id="' + loc.id + '"]').removeClass('lp-card--hover');
				});

				if (locatorPress.enableClustering && state.clusterGroup) {
					state.clusterGroup.addLayer(marker);
				} else {
					marker.addTo(state.mapInstance);
				}
				
				state.markers.push(marker);

			} else if (state.mapProvider === 'google') {
				var markerColor = locatorPress.markerFaColor || '#2563eb';
				var markerOptions = {
					position: { lat: lat, lng: lng },
					map: state.mapInstance,
					title: loc.title
				};

				markerOptions.icon = {
					path: 'M16 0C7.2 0 0 7.2 0 16c0 10.5 13.5 24.5 15.2 25.8.5.4 1.1.4 1.6 0C18.5 40.5 32 26.5 32 16 32 7.2 24.8 0 16 0z',
					fillColor: markerColor,
					fillOpacity: 1,
					strokeColor: '#ffffff',
					strokeWeight: 1.5,
					scale: 1.1,
					anchor: new google.maps.Point(16, 42)
				};

				var gMarker = new google.maps.Marker(markerOptions);

				gMarker.lpId = loc.id;
				state.markers.push(gMarker);

				var infoWindow = new google.maps.InfoWindow({
					content: loc.popup_html
				});

				gMarker.addListener('click', function() {
					infoWindow.open(state.mapInstance, gMarker);
					highlightSidebarCard(loc.id);
					activateMarker(loc.id);
				});

				infoWindow.addListener('closeclick', function() {
					deactivateMarker(loc.id);
					$('.lp-card[data-id="' + loc.id + '"]').removeClass('lp-card--active');
				});

				gMarker.addListener('mouseover', function() {
					$('.lp-card[data-id="' + loc.id + '"]').addClass('lp-card--hover');
				});
				gMarker.addListener('mouseout', function() {
					$('.lp-card[data-id="' + loc.id + '"]').removeClass('lp-card--hover');
				});

			} else if (state.mapProvider === 'goong') {
				var el = document.createElement('div');
				el.className = 'lp-goong-marker';
				
				var markerColor = locatorPress.markerFaColor || '#2563eb';
				var markerBgColor = locatorPress.markerFaBgColor || '#ffffff';
				var rgb = hexToRgb(markerColor);
				var rgbStr = rgb ? rgb.r + ', ' + rgb.g + ', ' + rgb.b : '37, 99, 235';
				
				var pinHtml = '<div class="lp-marker-pin-wrapper" style="--marker-color: ' + markerColor + '; --marker-color-rgb: ' + rgbStr + ';">' +
					'<div class="lp-marker-ripple-ring lp-ripple-1"></div>' +
					'<div class="lp-marker-ripple-ring lp-ripple-2"></div>' +
					'<div class="lp-marker-pin">' +
						'<svg class="lp-marker-pin-svg" viewBox="0 0 36 46" width="36" height="46">' +
							'<path d="M18 0C8.1 0 0 8.1 0 18c0 12.6 15.5 26.6 17.1 27.8a1.5 1.5 0 0 0 1.8 0C20.5 44.6 36 30.6 36 18c0-9.9-8.1-18-18-18z" fill="' + markerColor + '" stroke="#ffffff" stroke-width="1.5"></path>' +
							'<circle cx="18" cy="18" r="11" fill="' + markerBgColor + '"></circle>' +
							'<circle cx="18" cy="18" r="5" fill="' + markerColor + '"></circle>' +
						'</svg>' +
					'</div>' +
				'</div>';

				el.innerHTML = pinHtml;
				el.style.width = '36px';
				el.style.height = '46px';

				var popup = new goongjs.Popup({ offset: 25 }).setHTML(loc.popup_html);

				var marker = new goongjs.Marker(el)
					.setLngLat([lng, lat])
					.setPopup(popup)
					.addTo(state.mapInstance);

				marker.lpId = loc.id;
				state.markers.push(marker);

				el.addEventListener('click', function() {
					highlightSidebarCard(loc.id);
					activateMarker(loc.id);
				});

				popup.on('close', function() {
					deactivateMarker(loc.id);
					$('.lp-card[data-id="' + loc.id + '"]').removeClass('lp-card--active');
				});

				el.addEventListener('mouseenter', function() {
					$('.lp-card[data-id="' + loc.id + '"]').addClass('lp-card--hover');
				});
				el.addEventListener('mouseleave', function() {
					$('.lp-card[data-id="' + loc.id + '"]').removeClass('lp-card--hover');
				});
			}
		});

		// Karte automatisch einpassen (Bounds anpassen).
		if (bounds.length > 0) {
			fitMapBounds(bounds);
		}
	}

	function fitMapBounds(bounds) {
		if (state.mapProvider === 'leaflet') {
			state.mapInstance.fitBounds(bounds, { padding: [30, 30] });
		} else if (state.mapProvider === 'google') {
			var gBounds = new google.maps.LatLngBounds();
			bounds.forEach(function(coord) {
				gBounds.extend(new google.maps.LatLng(coord[0], coord[1]));
			});
			state.mapInstance.fitBounds(gBounds);
		} else if (state.mapProvider === 'goong') {
			var minLng = Math.min.apply(null, bounds.map(function(c) { return c[1]; }));
			var minLat = Math.min.apply(null, bounds.map(function(c) { return c[0]; }));
			var maxLng = Math.max.apply(null, bounds.map(function(c) { return c[1]; }));
			var maxLat = Math.max.apply(null, bounds.map(function(c) { return c[0]; }));
			state.mapInstance.fitBounds([[minLng, minLat], [maxLng, maxLat]], { padding: 40 });
		}
	}

	// ==========================================
	// 5. INTERAKTIVE SYNCHRONISIERUNG SIDEBAR / MARKER
	// ==========================================
	// Hovering cards to highlight markers.
	ui.resultsList.on('mouseenter', '.lp-card', function() {
		var id = parseInt($(this).data('id'));
		highlightMarker(id);
	}).on('mouseleave', '.lp-card', function() {
		var id = parseInt($(this).data('id'));
		unhighlightMarker(id);
	});

	function highlightMarker(id) {
		var marker = state.markers.find(function(m) { return m.lpId === id; });
		if (!marker) return;

		if (state.mapProvider === 'leaflet') {
			if (marker._icon) {
				$(marker._icon).addClass('lp-marker--highlighted');
			}
		} else if (state.mapProvider === 'goong') {
			var el = marker.getElement();
			if (el) {
				$(el).addClass('lp-marker--highlighted');
			}
		} else if (state.mapProvider === 'google') {
			marker.setZIndex(google.maps.Marker.MAX_ZINDEX + 1);
			if (marker.getIcon()) {
				var icon = marker.getIcon();
				icon.scale = 1.25;
				marker.setIcon(icon);
			}
		}
	}

	function unhighlightMarker(id) {
		var marker = state.markers.find(function(m) { return m.lpId === id; });
		if (!marker) return;

		if (state.mapProvider === 'leaflet') {
			if (marker._icon) {
				$(marker._icon).removeClass('lp-marker--highlighted');
			}
		} else if (state.mapProvider === 'goong') {
			var el = marker.getElement();
			if (el) {
				$(el).removeClass('lp-marker--highlighted');
			}
		} else if (state.mapProvider === 'google') {
			marker.setZIndex(null);
			if (marker.getIcon()) {
				var icon = marker.getIcon();
				icon.scale = 1.0;
				marker.setIcon(icon);
			}
		}
	}

	// Klick auf Sidebar-Eintrag zentriert Karte und öffnet Info-Popup.
	ui.resultsList.on('click', '.lp-card', function(e) {
		// Sicherstellen, dass Klicks auf Links nicht stören.
		if ($(e.target).closest('a, button').length) {
			return;
		}

		var card = $(this);
		var id = parseInt(card.data('id'));
		var lat = parseFloat(card.data('lat'));
		var lng = parseFloat(card.data('lng'));

		focusMarkerAndMap(id, lat, lng);
	});

	function focusMarkerAndMap(id, lat, lng) {
		// Sidebar Karte hervorheben.
		highlightSidebarCard(id);
		activateMarker(id);

		// Karte zentrieren.
		if (state.mapProvider === 'leaflet') {
			state.mapInstance.setView([lat, lng], 15);
			
			// Marker suchen und Popup öffnen.
			var marker = state.markers.find(function(m) { return m.lpId === id; });
			if (marker) {
				// Falls wir Clustering verwenden, müssen wir ggf. in die Gruppe spannen.
				if (locatorPress.enableClustering && state.clusterGroup) {
					state.clusterGroup.zoomToShowLayer(marker, function() {
						marker.openPopup();
					});
				} else {
					marker.openPopup();
				}
			}
		} else if (state.mapProvider === 'google') {
			state.mapInstance.setCenter({ lat: lat, lng: lng });
			state.mapInstance.setZoom(15);
			
			var gMarker = state.markers.find(function(m) { return m.lpId === id; });
			if (gMarker) {
				google.maps.event.trigger(gMarker, 'click');
			}
		} else if (state.mapProvider === 'goong') {
			state.mapInstance.flyTo({ center: [lng, lat], zoom: 15 });
			
			var marker = state.markers.find(function(m) { return m.lpId === id; });
			if (marker) {
				// Set zoom/fly center and ensure popup opens
				var popup = marker.getPopup();
				if (popup && !popup.isOpen()) {
					marker.togglePopup();
				}
			}
		}
	}

	function highlightSidebarCard(id) {
		$('.lp-card').removeClass('lp-card--active');
		var card = $('.lp-card[data-id="' + id + '"]');
		card.addClass('lp-card--active');
		
		// Karte in der Seitenleiste zentrieren / scrollen falls nötig.
		if (card.length) {
			var container = ui.resultsList;
			if ($(window).width() <= 768) {
				var containerWidth = container.width();
				var cardWidth = card.outerWidth();
				var cardLeft = card.position().left;
				var targetScroll = container.scrollLeft() + cardLeft - (containerWidth / 2) + (cardWidth / 2);
				
				if (Math.abs(container.scrollLeft() - targetScroll) > 10) {
					state.isAutoScrolling = true;
					container.animate({
						scrollLeft: targetScroll
					}, 300, function() {
						setTimeout(function() {
							state.isAutoScrolling = false;
						}, 100);
					});
				}
			} else {
				container.animate({
					scrollTop: card.offset().top - container.offset().top + container.scrollTop() - 10
				}, 300);
			}
		}
	}

	// ==========================================
	// 6. GEOLOCATION („NEAR ME“)
	// ==========================================
	if (ui.nearMeBtn.length) {
		ui.nearMeBtn.on('click', function(e) {
			e.preventDefault();
			
			if (navigator.geolocation) {
				ui.nearMeBtn.addClass('lp-loading');
				
				navigator.geolocation.getCurrentPosition(
					function(position) {
						state.userCoords = {
							lat: position.coords.latitude,
							lng: position.coords.longitude
						};
						
						ui.nearMeBtn.removeClass('lp-loading');
						logGeoMessage(locatorPress.strings.locationFound, 'success');
						
						// Ortung ausführen.
						triggerSearch();
					},
					function() {
						ui.nearMeBtn.removeClass('lp-loading');
						logGeoMessage(locatorPress.strings.locationFailed, 'error');
					}
				);
			} else {
				logGeoMessage(locatorPress.strings.locationFailed, 'error');
			}
		});
	}

	function logGeoMessage(msg, type) {
		ui.resultsCount.html('<span style="color:' + (type === 'success' ? 'var(--lp-success)' : 'var(--lp-danger)') + ';">' + msg + '</span>');
	}

	// ==========================================
	// 7. ROUTENPLANER EVENT HANDLER
	// ==========================================
	ui.resultsList.on('click', '.lp-btn-get-directions', function(e) {
		e.preventDefault();
		e.stopPropagation();
		
		var address = $(this).data('address');
		if (address) {
			var url = 'https://www.google.com/maps/dir/?api=1&destination=' + encodeURIComponent(address);
			
			// Wenn der Benutzer geortet wurde, tragen wir den Startpunkt ein.
			if (state.userCoords) {
				url += '&origin=' + state.userCoords.lat + ',' + state.userCoords.lng;
			}
			
			window.open(url, '_blank');
		}
	});

	// ==========================================
	// 8. MOBILE HORIZONTAL SWIPE / SCROLL SYNC
	// ==========================================
	var scrollTimeout;
	ui.resultsList.on('scroll', function() {
		if ($(window).width() > 768 || state.isAutoScrolling) {
			return;
		}
		
		clearTimeout(scrollTimeout);
		scrollTimeout = setTimeout(function() {
			syncMobileCenteredCard();
		}, 150);
	});

	function syncMobileCenteredCard() {
		if ($(window).width() > 768 || state.isAutoScrolling) {
			return;
		}

		var container = ui.resultsList;
		var containerWidth = container.width();
		var containerCenter = container.offset().left + (containerWidth / 2);

		var closestCard = null;
		var minDistance = Infinity;

		container.find('.lp-card').each(function() {
			var card = $(this);
			var cardCenter = card.offset().left + (card.outerWidth() / 2);
			var distance = Math.abs(cardCenter - containerCenter);

			if (distance < minDistance) {
				minDistance = distance;
				closestCard = card;
			}
		});

		if (closestCard && !closestCard.hasClass('lp-card--active')) {
			var id = parseInt(closestCard.data('id'));
			var lat = parseFloat(closestCard.data('lat'));
			var lng = parseFloat(closestCard.data('lng'));

			if (!isNaN(id) && !isNaN(lat) && !isNaN(lng)) {
				focusMarkerAndMap(id, lat, lng);
			}
		}
	}
});
