# LocatorPress 📍

> **High-Performance, Modern Store & Location Locator for WordPress**  
> *Zero API keys required out of the box — Powered by Leaflet & OpenStreetMap, with custom high-speed database tables.*

[![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-blue.svg)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-GPLv2%2Bor%20later-green.svg)](LICENSE)
[![Release](https://img.shields.io/badge/Version-1.0.4-orange.svg)](https://github.com)
[![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg)](https://github.com)

---

## 🌟 Overview

**LocatorPress** is a lightning-fast, production-ready Store Locator plugin for WordPress designed for businesses, agencies, chains, and directories. 

Unlike conventional plugins that store locations in standard WordPress `posts` and `postmeta` tables (leading to sluggish queries and high server load), **LocatorPress** utilizes dedicated, optimized custom SQL tables (`wp_lp_locations` and `wp_lp_regions`) combined with mathematical **Haversine formula queries**. This architecture guarantees sub-millisecond distance lookups even across thousands of stores.

Best of all: **No Google Maps billing or API key is required.** It runs 100% out of the box using **Leaflet.js** and **OpenStreetMap**, while offering seamless optional support for Google Maps and Goong Maps (optimized for Vietnam).

---

## ✨ Features

- 🗺️ **100% Free Maps Out of the Box**: Native Leaflet.js & OpenStreetMap integration without requiring credit cards or Google API keys.
- ⚡ **Ultra-Fast Spatial Queries**: Custom SQL tables bypass postmeta bloat with optimized radius and distance calculations.
- 📍 **GPS "Near Me" Geolocation**: Allow visitors to find the nearest branches with one tap using HTML5 Geolocation.
- 🕒 **Multi-Slot Opening Hours**: Configure multiple opening ranges per day (e.g., `09:00 - 12:00` and `14:00 - 18:00`) with a 1-click "Copy to other days" utility.
- 🎨 **Visual Icon Picker & Custom Pins**: Choose from over 60+ Font Awesome markers and customize icon colors and pin background hues independently.
- 📂 **Background CSV Batch Importer**: Upload hundreds of locations seamlessly with real-time AJAX batching and live progress logs.
- 🌐 **Hierarchical Regions**: Group branches by nested geographical taxonomies (Country → State/Province → City).
- 🌓 **Dark Mode / Light Mode**: Built-in modern appearance presets that seamlessly blend into your theme.
- 🧩 **Page Builder Compatible**: Works out-of-the-box with Elementor, Gutenberg, Bricks Builder, Divi, Beaver Builder, and classic themes.
- 🔌 **Native REST API**: Headless-ready JSON endpoints (`/wp-json/locatorpress/v1/locations`) for custom mobile apps or external integrations.
- 🌍 **Multilingual & i18n Ready**: Bundled with translation packs for **English (en_US)**, **German (de_DE)**, and **Vietnamese (vi_VN)**.

---

## 🚀 Quick Start

### Installation

1. Clone or download this repository into your WordPress plugins folder:
   ```bash
   cd wp-content/plugins
   git clone https://github.com/[your-username]/LocatorPress.git locatorpress
   ```
2. Activate the plugin in **WordPress Admin → Plugins → Installed Plugins**.
3. Upon activation, LocatorPress automatically creates the required database tables and sets default configurations.
4. Navigate to **LocatorPress → Locations** to create your first store.
5. Embed the locator on any page using the shortcode:
   ```text
   [locatorpress]
   ```

---

## 📋 Shortcode Reference

You can customize the map container using optional shortcode attributes:

```text
[locatorpress zoom="14" height="600px" region="2"]
```

| Parameter | Type | Default | Description |
| :--- | :--- | :--- | :--- |
| `zoom` | `int` | `12` | Initial map zoom level (1–18). |
| `height` | `string` | `500px` | CSS height of the locator container (e.g. `600px`, `80vh`). |
| `region` | `int` | `0` | Pre-filter locations by specific Region ID. |
| `center_lat` | `float` | *(Settings default)* | Custom initial latitude coordinate. |
| `center_lng` | `float` | *(Settings default)* | Custom initial longitude coordinate. |

---

## 🔌 REST API Documentation

LocatorPress exposes a public REST API endpoint for fetching locations programmatically:

### `GET /wp-json/locatorpress/v1/locations`

#### Query Parameters:
- `lat` *(float, optional)*: User latitude for radius search.
- `lng` *(float, optional)*: User longitude for radius search.
- `radius` *(int, optional)*: Search radius in km or miles (default from plugin settings).
- `region` *(int, optional)*: Filter by Region ID.
- `search` *(string, optional)*: Search query (title, address, city).

#### Sample JSON Response:
```json
[
  {
    "id": 1,
    "title": "Flagship Store Berlin",
    "address": "Alexanderplatz 1, 10178 Berlin, Germany",
    "lat": 52.521918,
    "lng": 13.413215,
    "phone": "+49 30 1234567",
    "email": "berlin@example.com",
    "website": "https://example.com",
    "opening_hours": {
      "monday": [
        {"open": "09:00", "close": "18:00"}
      ]
    },
    "distance": 0.45
  }
]
```

---

## 🏛️ Architecture & Database

LocatorPress is architectured around WordPress best practices and modern clean code standards:

- **Namespace**: `\LocatorPress`
- **Autoloader**: PSR-4 compatible autoloader in `includes/helpers/class-autoload.php`.
- **Database Tables**:
  - `wp_lp_locations`: Stores title, address, geocodes (`lat`, `lng`), contact info, opening hours (JSON), custom marker properties, and region bindings.
  - `wp_lp_regions`: Stores parent-child geographical hierarchy.
- **Frontend Assets**: Scoped CSS (`.lp-*`) to prevent styling bleed into theme layouts.

---

## 🌍 Localization (i18n)

The plugin is fully translated and ready for localization:
- Text Domain: `locatorpress`
- Domain Path: `/languages/`
- Included Translations:
  - English (`en_US`)
  - German (`de_DE`)
  - Vietnamese (`vi_VN`, `vi`)
- Includes master `locatorpress.pot` template for creating additional language translations via Poedit or Loco Translate.

---

## 🤝 Contributing

Contributions, feature requests, and bug reports are welcome!  
Feel free to open an [Issue](https://github.com) or submit a Pull Request:

1. Fork the Project.
2. Create your Feature Branch (`git checkout -b feature/AmazingFeature`).
3. Commit your Changes (`git commit -m 'Add some AmazingFeature'`).
4. Push to the Branch (`git push origin feature/AmazingFeature`).
5. Open a Pull Request.

---

## 📄 License

Distributed under the **GNU General Public License v2 or later (GPL-2.0-or-later)**. See [`LICENSE`](LICENSE) for more details.

---

## ☕ Support / Donations

If LocatorPress saves you time and enhances your projects, consider supporting its development:
- **PayPal**: [paypal.me/thangme](https://paypal.me/thangme)
- **Author**: [Me Toolkit](https://www.me-toolkit.com)
