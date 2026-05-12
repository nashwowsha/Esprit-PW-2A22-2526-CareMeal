(function () {
  "use strict";

  var DEFAULT_CENTER = [34.0, 9.0]; // Tunisia
  var DEFAULT_ZOOM = 6;
  var GEOCODE_CACHE_PREFIX = "caremeal_geo_v1:";
  var DEFAULT_RESTAURANT_ICON_PATH = (typeof window.caremealPath === "function")
    ? window.caremealPath("assets/map-markers/restaurant-pin.png")
    : "/assets/map-markers/restaurant-pin.png";
  var DEFAULT_DRIVER_ICON_PATH = (typeof window.caremealPath === "function")
    ? window.caremealPath("assets/map-markers/deliveryboy.png")
    : "/assets/map-markers/deliveryboy.png";

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

  function renderMapUnavailable(container, message) {
    if (!container) return;
    container.innerHTML =
      '<div style="height:100%;display:flex;align-items:center;justify-content:center;padding:16px;text-align:center;color:#6b7280;font-size:0.95rem;">'
      + escapeHtml(message || "Carte indisponible pour le moment.")
      + "</div>";
  }

  function resolveRestaurantIconUrl(options) {
    if (options && typeof options.restaurantIconUrl === "string" && options.restaurantIconUrl.trim() !== "") {
      return options.restaurantIconUrl.trim();
    }
    return DEFAULT_RESTAURANT_ICON_PATH;
  }

  function resolveDriverIconUrl(options) {
    if (options && typeof options.driverIconUrl === "string" && options.driverIconUrl.trim() !== "") {
      return options.driverIconUrl.trim();
    }
    return DEFAULT_DRIVER_ICON_PATH;
  }

  function buildRestaurantIcon(iconUrl) {
    return L.icon({
      iconUrl: iconUrl,
      iconSize: [38, 38],
      iconAnchor: [19, 38],
      popupAnchor: [0, -34],
    });
  }

  function buildDriverIcon(iconUrl) {
    return L.icon({
      iconUrl: iconUrl,
      iconSize: [34, 34],
      iconAnchor: [17, 34],
      popupAnchor: [0, -30],
    });
  }

  function easeOutCubic(t) {
    var x = Math.max(0, Math.min(1, t));
    return 1 - Math.pow(1 - x, 3);
  }

  function animateMarkerTo(marker, targetLat, targetLng, durationMs) {
    if (!marker || !Number.isFinite(targetLat) || !Number.isFinite(targetLng)) {
      return null;
    }

    var start = marker.getLatLng();
    var startLat = Number(start.lat);
    var startLng = Number(start.lng);
    if (!Number.isFinite(startLat) || !Number.isFinite(startLng)) {
      marker.setLatLng([targetLat, targetLng]);
      return null;
    }

    var dLat = targetLat - startLat;
    var dLng = targetLng - startLng;
    var approxMeters = Math.sqrt(
      Math.pow(dLat * 111320, 2) +
      Math.pow(dLng * 111320 * Math.cos((startLat + targetLat) * Math.PI / 360), 2)
    );

    // Ignore micro deltas to avoid visual jitter.
    if (approxMeters < 0.2) {
      marker.setLatLng([targetLat, targetLng]);
      return null;
    }

    var startedAt = performance.now();
    var dur = Math.max(120, Number(durationMs) || 280);
    var frameId = null;

    var tick = function (now) {
      var t = (now - startedAt) / dur;
      if (t >= 1) {
        marker.setLatLng([targetLat, targetLng]);
        return;
      }
      var e = easeOutCubic(t);
      marker.setLatLng([startLat + dLat * e, startLng + dLng * e]);
      frameId = requestAnimationFrame(tick);
    };

    frameId = requestAnimationFrame(tick);
    return frameId;
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
    var driverName = escapeHtml(point.driver_name || "");
    var driverContact = escapeHtml(point.driver_contact || "");
    var updatedAt = escapeHtml(point.updated_at || "");
    var isLive = Boolean(point.is_live);
    var secondsSinceUpdate = Number(point.seconds_since_update || 0);

    var html = "<strong>" + label + "</strong><br>";
    html += "Location: " + location;
    if (status) html += "<br>Status: " + status;
    if (mode) html += "<br>Mode: " + mode;
    if (kind) html += "<br>Type: " + kind;
    if (driverName) html += "<br>Livreur: " + driverName;
    if (driverContact) html += "<br>Contact: " + driverContact;
    if (updatedAt) html += "<br>Maj: " + updatedAt;
    if (kind === "driver") {
      if (isLive) {
        html += "<br>Etat: Live";
      } else if (Number.isFinite(secondsSinceUpdate) && secondsSinceUpdate > 0) {
        html += "<br>Etat: Dernier signal il y a " + secondsSinceUpdate + "s";
      }
    }
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

  function splitPoints(points) {
    var staticPoints = [];
    var driverPoints = [];
    for (var i = 0; i < points.length; i += 1) {
      var point = points[i] || {};
      if (point.kind === "driver") {
        driverPoints.push(point);
      } else {
        staticPoints.push(point);
      }
    }
    return { staticPoints: staticPoints, driverPoints: driverPoints };
  }

  async function fetchLivePoints(endpoint) {
    if (!endpoint) return [];
    try {
      var joiner = endpoint.indexOf("?") === -1 ? "?" : "&";
      var url = endpoint + joiner + "_t=" + Date.now();
      var response = await fetch(url, {
        method: "GET",
        cache: "no-store",
        headers: {
          "Cache-Control": "no-cache, no-store, max-age=0",
          Pragma: "no-cache"
        }
      });
      if (!response.ok) return [];
      var data = await response.json();
      if (!data || !data.ok || !Array.isArray(data.points)) return [];
      return data.points;
    } catch (e) {
      return [];
    }
  }

  async function initCollectesMap(options) {
    if (!options || !options.containerId) return;
    var container = document.getElementById(options.containerId);
    if (!container) return;
    if (!window.L) {
      renderMapUnavailable(container, "Carte indisponible (Leaflet non charge).");
      return;
    }

    var points = Array.isArray(options.points) ? options.points : [];
    var split = splitPoints(points);
    var staticPoints = split.staticPoints;
    var initialDriverPoints = split.driverPoints;
    var restaurantIcon = buildRestaurantIcon(resolveRestaurantIconUrl(options));
    var driverIcon = buildDriverIcon(resolveDriverIconUrl(options));
    var markerAnimationMs = Number(options.markerAnimationMs);
    if (!Number.isFinite(markerAnimationMs) || markerAnimationMs < 120) {
      markerAnimationMs = 280;
    }
    var map = L.map(container).setView(DEFAULT_CENTER, DEFAULT_ZOOM);
    var driverMarkers = {};
    var driverAnimationFrames = {};

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      maxZoom: 19,
      attribution: "&copy; OpenStreetMap contributors",
    }).addTo(map);

    var bounds = [];

    if (staticPoints.length) {
      var resolved = await resolvePoints(staticPoints);
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
    }

    function upsertDriverPoints(driverPoints) {
      var seen = {};
      for (var d = 0; d < driverPoints.length; d += 1) {
        var point = driverPoints[d] || {};
        var key = String(point.collecte_id || ("driver_" + d));
        var lat = Number(point.lat);
        var lng = Number(point.lng);
        if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
          continue;
        }
        seen[key] = true;

        if (!driverMarkers[key]) {
          driverMarkers[key] = L.marker([lat, lng], { icon: driverIcon }).addTo(map);
        } else {
          if (driverAnimationFrames[key]) {
            cancelAnimationFrame(driverAnimationFrames[key]);
            driverAnimationFrames[key] = null;
          }
          driverAnimationFrames[key] = animateMarkerTo(
            driverMarkers[key],
            lat,
            lng,
            markerAnimationMs
          );
        }
        driverMarkers[key].bindPopup(buildPopup(point));
      }

      Object.keys(driverMarkers).forEach(function (key) {
        if (!seen[key]) {
          if (driverAnimationFrames[key]) {
            cancelAnimationFrame(driverAnimationFrames[key]);
            delete driverAnimationFrames[key];
          }
          map.removeLayer(driverMarkers[key]);
          delete driverMarkers[key];
        }
      });
    }

    function upsertSingleDriverPoint(point) {
      if (!point || point.kind !== "driver") return;
      var key = String(point.collecte_id || "");
      var lat = Number(point.lat);
      var lng = Number(point.lng);
      if (!key || !Number.isFinite(lat) || !Number.isFinite(lng)) return;

      if (!driverMarkers[key]) {
        driverMarkers[key] = L.marker([lat, lng], { icon: driverIcon }).addTo(map);
      } else {
        if (driverAnimationFrames[key]) {
          cancelAnimationFrame(driverAnimationFrames[key]);
          driverAnimationFrames[key] = null;
        }
        driverAnimationFrames[key] = animateMarkerTo(driverMarkers[key], lat, lng, markerAnimationMs);
      }
      driverMarkers[key].bindPopup(buildPopup(point));
    }

    upsertDriverPoints(initialDriverPoints);

    if (bounds.length === 1) {
      map.setView(bounds[0], 13);
    } else if (bounds.length > 1) {
      map.fitBounds(bounds, { padding: [30, 30] });
    }

    var liveEndpoint = typeof options.liveEndpoint === "string" ? options.liveEndpoint.trim() : "";
    var pollMs = Number(options.pollMs);
    if (!Number.isFinite(pollMs) || pollMs < 120) {
      pollMs = 250;
    }

    if (liveEndpoint) {
      var poll = async function () {
        var livePoints = await fetchLivePoints(liveEndpoint);
        var onlyDrivers = [];
        for (var p = 0; p < livePoints.length; p += 1) {
          if ((livePoints[p] || {}).kind === "driver") {
            onlyDrivers.push(livePoints[p]);
          }
        }
        upsertDriverPoints(onlyDrivers);
      };
      poll();
      setInterval(poll, pollMs);
    }

    var pusherCfg = (options && typeof options.pusher === "object" && options.pusher) ? options.pusher : null;
    if (
      pusherCfg &&
      pusherCfg.enabled === true &&
      typeof window.Pusher !== "undefined" &&
      typeof pusherCfg.key === "string" &&
      pusherCfg.key.trim() !== "" &&
      typeof pusherCfg.cluster === "string" &&
      pusherCfg.cluster.trim() !== "" &&
      typeof pusherCfg.channel === "string" &&
      pusherCfg.channel.trim() !== ""
    ) {
      try {
        if (typeof window.Pusher.logToConsole !== "undefined") {
          window.Pusher.logToConsole = false;
        }
        var pusher = new window.Pusher(pusherCfg.key.trim(), {
          cluster: pusherCfg.cluster.trim(),
          forceTLS: true,
        });
        var channel = pusher.subscribe(pusherCfg.channel.trim());
        var eventName = (typeof pusherCfg.eventName === "string" && pusherCfg.eventName.trim() !== "")
          ? pusherCfg.eventName.trim()
          : "driver-location";

        channel.bind(eventName, function (eventPayload) {
          var payload = eventPayload;
          if (typeof payload === "string") {
            try {
              payload = JSON.parse(payload);
            } catch (e) {
              return;
            }
          }
          if (!payload || typeof payload !== "object") return;
          upsertSingleDriverPoint(payload);
        });
      } catch (e) {
        // keep polling fallback alive
      }
    }
  }

  window.initCollectesMap = function (options) {
    initCollectesMap(options).catch(function () {
      // keep page stable even if map init fails
    });
  };
})();

