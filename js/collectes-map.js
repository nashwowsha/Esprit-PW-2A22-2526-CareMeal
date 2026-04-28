(function () {
  "use strict";

  var DEFAULT_CENTER = [34.0, 9.0]; // Tunisia
  var DEFAULT_ZOOM = 6;
  var GEOCODE_CACHE_PREFIX = "caremeal_geo_v1:";
  var DEFAULT_RESTAURANT_ICON_PATH = "/caremeal/assets/map-markers/restaurant-pin.png";

  function normalizeText(value) {
    return String(value || "")
      .trim()
      .toLowerCase()
      .replace(/\s+/g, " ");
  }

  function escapeHtml(value) {
    return String(value || "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  function resolveRestaurantIconUrl(options) {
    if (options && typeof options.restaurantIconUrl === "string" && options.restaurantIconUrl.trim() !== "") {
      return options.restaurantIconUrl.trim();
    }
    return DEFAULT_RESTAURANT_ICON_PATH;
  }

  function buildRestaurantIcon(iconUrl) {
    return L.icon({
      iconUrl: iconUrl,
      iconSize: [38, 38],
      iconAnchor: [19, 38],
      popupAnchor: [0, -34],
    });
  }

  function fallbackCoords(rawLocation) {
    var location = normalizeText(rawLocation);
    if (!location) return null;

    var rules = [
      { key: "tunis", coords: [36.8065, 10.1815] },
      { key: "ariana", coords: [36.8665, 10.1647] },
      { key: "marsa", coords: [36.8782, 10.3247] },
      { key: "lac", coords: [36.8403, 10.2713] },
      { key: "manar", coords: [36.8088, 10.1321] },
      { key: "nasr", coords: [36.8625, 10.1686] },
      { key: "sfax", coords: [34.7406, 10.7603] },
      { key: "sousse", coords: [35.8256, 10.6369] },
      { key: "monastir", coords: [35.7779, 10.8262] },
      { key: "nabeul", coords: [36.4513, 10.7354] },
      { key: "bizerte", coords: [37.2744, 9.8739] },
      { key: "gabes", coords: [33.8815, 10.0982] },
    ];

    for (var i = 0; i < rules.length; i += 1) {
      if (location.indexOf(rules[i].key) !== -1) {
        return rules[i].coords;
      }
    }
    return null;
  }

  function readCachedCoords(key) {
    try {
      var raw = window.localStorage.getItem(GEOCODE_CACHE_PREFIX + key);
      if (!raw) return null;
      var parsed = JSON.parse(raw);
      if (!Array.isArray(parsed) || parsed.length !== 2) return null;
      var lat = Number(parsed[0]);
      var lng = Number(parsed[1]);
      if (!Number.isFinite(lat) || !Number.isFinite(lng)) return null;
      return [lat, lng];
    } catch (e) {
      return null;
    }
  }

  function saveCachedCoords(key, coords) {
    try {
      window.localStorage.setItem(
        GEOCODE_CACHE_PREFIX + key,
        JSON.stringify([coords[0], coords[1]])
      );
    } catch (e) {
      // ignore cache write issues
    }
  }

  async function geocodeLocation(rawLocation) {
    var location = normalizeText(rawLocation);
    if (!location) return null;

    var cached = readCachedCoords(location);
    if (cached) return cached;

    var fallback = fallbackCoords(location);

    try {
      var query = encodeURIComponent(rawLocation + ", Tunisia");
      var url = "https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&q=" + query;
      var response = await fetch(url, { method: "GET" });
      if (!response.ok) {
        if (fallback) return fallback;
        return null;
      }
      var data = await response.json();
      if (Array.isArray(data) && data.length > 0) {
        var lat = Number(data[0].lat);
        var lng = Number(data[0].lon);
        if (Number.isFinite(lat) && Number.isFinite(lng)) {
          var coords = [lat, lng];
          saveCachedCoords(location, coords);
          return coords;
        }
      }
    } catch (e) {
      // ignore request errors
    }

    return fallback;
  }

  function buildPopup(point) {
    var label = escapeHtml(point.label || "Collecte");
    var location = escapeHtml(point.location || "-");
    var status = escapeHtml(point.status || "");
    var mode = escapeHtml(point.mode || "");
    var kind = escapeHtml(point.kind || "");

    var html = "<strong>" + label + "</strong><br>";
    html += "Location: " + location;
    if (status) html += "<br>Status: " + status;
    if (mode) html += "<br>Mode: " + mode;
    if (kind) html += "<br>Type: " + kind;
    return html;
  }

  async function resolvePoints(points) {
    var locationMap = {};
    var keys = [];
    var resolved = [];

    for (var i = 0; i < points.length; i += 1) {
      var directLat = Number(points[i].lat);
      var directLng = Number(points[i].lng);
      if (Number.isFinite(directLat) && Number.isFinite(directLng)) {
        resolved.push({
          lat: directLat,
          lng: directLng,
          point: points[i],
        });
        continue;
      }
      var key = normalizeText(points[i].location || "");
      if (!key) continue;
      if (!Object.prototype.hasOwnProperty.call(locationMap, key)) {
        locationMap[key] = null;
        keys.push(key);
      }
    }

    for (var k = 0; k < keys.length; k += 1) {
      var raw = keys[k];
      locationMap[raw] = await geocodeLocation(raw);
    }

    for (var j = 0; j < points.length; j += 1) {
      var p = points[j];
      var lat = Number(p.lat);
      var lng = Number(p.lng);
      if (Number.isFinite(lat) && Number.isFinite(lng)) {
        continue;
      }
      var normalized = normalizeText(p.location || "");
      if (!normalized) continue;
      var coords = locationMap[normalized];
      if (!coords) continue;
      resolved.push({
        lat: coords[0],
        lng: coords[1],
        point: p,
      });
    }
    return resolved;
  }

  async function initCollectesMap(options) {
    if (!window.L || !options || !options.containerId) return;
    var container = document.getElementById(options.containerId);
    if (!container) return;

    var points = Array.isArray(options.points) ? options.points : [];
    var restaurantIcon = buildRestaurantIcon(resolveRestaurantIconUrl(options));
    var map = L.map(container).setView(DEFAULT_CENTER, DEFAULT_ZOOM);

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      maxZoom: 19,
      attribution: "&copy; OpenStreetMap contributors",
    }).addTo(map);

    if (!points.length) {
      return;
    }

    var resolved = await resolvePoints(points);
    if (!resolved.length) {
      return;
    }

    var bounds = [];
    for (var i = 0; i < resolved.length; i += 1) {
      var item = resolved[i];
      var markerConfig = {};
      if (item.point && item.point.kind === "restaurant") {
        markerConfig.icon = restaurantIcon;
      }
      var marker = L.marker([item.lat, item.lng], markerConfig).addTo(map);
      marker.bindPopup(buildPopup(item.point));
      bounds.push([item.lat, item.lng]);
    }

    if (bounds.length === 1) {
      map.setView(bounds[0], 13);
    } else {
      map.fitBounds(bounds, { padding: [30, 30] });
    }
  }

  window.initCollectesMap = function (options) {
    initCollectesMap(options).catch(function () {
      // keep page stable even if map init fails
    });
  };
})();
