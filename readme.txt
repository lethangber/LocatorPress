=== LocatorPress ===
Contributors: metoolkit
Donate link: https://paypal.me/thangme
Tags: store locator, location finder, map, leaflet, google maps
Requires at least: 5.8
Tested up to: 6.7
Stable tag: 1.0.4
Requires PHP: 7.4
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A fast, free Store Locator for WordPress. No Google Maps API key required — works 100% free with Leaflet & OpenStreetMap.

== Description ==

**LocatorPress** is a high-performance Store Locator plugin for WordPress. It uses custom database tables for ultra-fast searches and delivers a premium, modern user interface without requiring any paid API keys.

=== ✨ Key Features ===

* **100% Free Map** — Works out of the box with Leaflet.js and OpenStreetMap. No API key needed.
* **Google Maps & Goong Maps Support** — Optionally connect to Google Maps or Goong Maps (Vietnam) with your own API key.
* **Ultra-Fast Search** — Uses custom database tables with optimized Haversine SQL queries for radius-based searches.
* **Multi-Slot Opening Hours** — Set multiple opening time ranges per day with one-click copy to other days.
* **CSV Import** — Background batch import of hundreds of locations with real-time progress bar.
* **Font Awesome Icon Picker** — Choose from 60+ icons for your map markers with a visual picker.
* **Custom Marker Colors** — Set icon color and pin background color independently.
* **Region/Category Hierarchy** — Organize locations in nested regions (e.g. Country → State → City).
* **Responsive SaaS Layout** — Modern sidebar layout that works beautifully on all devices.
* **REST API** — Built-in JSON REST API endpoint for headless or custom integrations.
* **GPS "Near Me" Button** — Lets visitors find locations near their current position.
* **Marker Clustering** — Groups markers at low zoom levels for better performance.
* **Page Builder Compatible** — Works with Elementor, Beaver Builder, Divi, and the Gutenberg block editor.

=== 🗺️ Supported Map Providers ===

* Leaflet (OpenStreetMap) — **Free, no API key required**
* Google Maps (requires Google Maps API key)
* Goong Maps (requires Goong API key — optimized for Vietnam)

=== 🔧 Shortcode Usage ===

Simply add the shortcode to any page, post, or widget:

`[locatorpress]`

Available optional attributes:

* `zoom` — Map zoom level (default: 12)
* `height` — Map container height (default: 500px)
* `region` — Pre-filter by region ID
* `center_lat` / `center_lng` — Override default map center

Example:
`[locatorpress zoom="14" height="600px" region="3"]`

=== 🌍 Multilingual / Translation Ready ===

LocatorPress is fully translation-ready with a `.pot` file included. The plugin supports automatic language detection from your WordPress settings, or you can force a specific language from the Settings page.

=== 📦 Data & Privacy ===

* All location data is stored in your own WordPress database.
* No data is sent to any external service except for geocoding (OpenStreetMap Nominatim, Google, or Goong — only when you explicitly geocode an address).
* No tracking, no analytics, no external calls on the frontend beyond the map tile provider you chose.

== Installation ==

1. Upload the `locatorpress` folder to the `/wp-content/plugins/` directory, or install directly from the WordPress plugin repository.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to **LocatorPress → Settings** to configure your map provider (Leaflet is enabled by default — no API key needed).
4. Add your locations via **LocatorPress → Locations**.
5. Insert the shortcode `[locatorpress]` on any page where you want the map to appear.

== Frequently Asked Questions ==

= Do I need a Google Maps API key? =

No! LocatorPress works 100% out of the box using Leaflet.js with free OpenStreetMap tiles. A Google Maps API key is only required if you explicitly choose Google Maps as your map provider.

= How do I import locations? =

Go to **LocatorPress → Import / Export** and upload a CSV file. The importer processes records in the background in small batches to prevent timeouts, and shows a real-time progress log.

= What CSV columns are supported? =

The CSV importer accepts: `title`, `address`, `latitude`, `longitude`, `phone`, `email`, `website`, `status`, `region`. If you omit latitude/longitude, the address is automatically geocoded.

= Can I use multiple time slots per day for opening hours? =

Yes! When a day is set to "Open", you can add multiple time ranges (e.g., 09:00–12:00 and 14:00–18:00). You can also copy one day's schedule to other days with a single click.

= Is it compatible with page builders? =

Yes. LocatorPress uses a standard shortcode and outputs clean, scoped CSS (`.lp-*` prefix) that avoids conflicts with Elementor, Divi, Beaver Builder, and other builders.

= Can I filter the map by region? =

Yes. The frontend search form includes a Region dropdown. You can also pre-filter via the shortcode attribute: `[locatorpress region="5"]`.

= Does it support dark mode? =

Yes. You can set the color mode to Light, Dark, or Automatic (follows the visitor's browser preference) from **Settings → Appearance**.

= Where is my data stored? =

All data is stored in your WordPress database in custom tables (`{prefix}_lp_locations` and `{prefix}_lp_regions`). No data is sent to any external server.

= How do I uninstall cleanly? =

Deactivate, then delete the plugin. All database tables and WordPress options created by LocatorPress will be removed automatically via the `uninstall.php` hook.

== Screenshots ==

1. Frontend store locator with map and sidebar results list.
2. Admin — Add/Edit Location form with map picker and opening hours.
3. Admin — Settings page with map provider, marker, and appearance options.
4. Admin — CSV Import with real-time progress log.
5. Plugin Dashboard with statistics overview and quickstart guide.
6. Font Awesome icon picker for map markers.

== Changelog ==

= 1.0.4 =
* Performance enhancements for spatial Haversine search queries.
* Updated full language packs (.po / .mo) for German (de_DE), English (en_US), and Vietnamese (vi_VN).
* Fixed CSS compatibility issues with popular WordPress themes and page builders.

= 1.0.3 =
* Improved CSV batch importer stability and error reporting.
* Added support for custom location status filtering.

= 1.0.2 =
* Enhanced responsive layout for mobile viewport and touch gestures.
* Polished opening hours interface and copy-to-days modal.

= 1.0.1 =
* Added custom map pin colors and Font Awesome icon picker options.
* Minor bugfixes in REST API serialization.

= 1.0.0 =
* Initial public release.
* Leaflet / OpenStreetMap support (free, no API key).
* Google Maps and Goong Maps integration.
* Custom database tables for ultra-fast searches.
* Multi-slot opening hours per day with copy-to-days feature.
* Font Awesome icon picker for map markers.
* Background CSV batch importer with real-time progress.
* Region hierarchy management.
* REST API endpoint.
* GPS "Near Me" geolocation button.
* Marker clustering.
* Appearance customization (colors, dark mode, border radius).
* Multilingual / translation ready.

== Upgrade Notice ==

= 1.0.4 =
Recommended update: includes performance improvements, complete translation packs, and UI enhancements.
