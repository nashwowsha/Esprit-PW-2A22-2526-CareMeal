(function () {
  "use strict";

  function escapeHtml(value) {
    return String(value || "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  function isFiniteNumber(value) {
    return typeof value === "number" && Number.isFinite(value);
  }

  function toNumber(value) {
    var n = Number(value);
    return Number.isFinite(n) ? n : null;
  }

  async function geocodeSearch(query) {
    var text = String(query || "").trim();
    if (!text) return null;
    var url =
      "https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&q=" +
      encodeURIComponent(text);
    var response = await fetch(url, { method: "GET" });
    if (!response.ok) return null;
    var data = await response.json();
    if (!Array.isArray(data) || data.length === 0) return null;
    var lat = Number(data[0].lat);
    var lng = Number(data[0].lon);
    if (!Number.isFinite(lat) || !Number.isFinite(lng)) return null;
    return {
      lat: lat,
      lng: lng,
      displayName: String(data[0].display_name || ""),
    };
  }

  async function reverseGeocode(lat, lng) {
    if (!isFiniteNumber(lat) || !isFiniteNumber(lng)) return "";
    var url =
      "https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=" +
      encodeURIComponent(String(lat)) +
      "&lon=" +
      encodeURIComponent(String(lng));
    var response = await fetch(url, { method: "GET" });
    if (!response.ok) return "";
    var data = await response.json();
    return String(data.display_name || "");
  }

  function updateCoordsLabel(labelNode, lat, lng) {
    if (!labelNode) return;
    if (!isFiniteNumber(lat) || !isFiniteNumber(lng)) {
      labelNode.textContent = "Coordonnees: non selectionnees";
      return;
    }
    labelNode.textContent = "Coordonnees: " + lat.toFixed(6) + ", " + lng.toFixed(6);
  }

  window.initCollecteAddressPicker = function (config) {
    if (!window.L || !config) return;

    var mapEl = document.getElementById(config.mapId || "");
    var addressInput = document.getElementById(config.addressInputId || "");
    var latInput = document.getElementById(config.latInputId || "");
    var lngInput = document.getElementById(config.lngInputId || "");
    var searchInput = document.getElementById(config.searchInputId || "");
    var searchBtn = document.getElementById(config.searchBtnId || "");
    var currentLocationBtn = document.getElementById(config.currentLocationBtnId || "");
    var coordsLabel = document.getElementById(config.coordsLabelId || "");

    if (!mapEl || !addressInput || !latInput || !lngInput) return;

    var center = Array.isArray(config.defaultCenter) && config.defaultCenter.length === 2
      ? config.defaultCenter
      : [36.8065, 10.1815];
    var zoom = Number.isFinite(Number(config.defaultZoom)) ? Number(config.defaultZoom) : 12;

    var map = L.map(mapEl).setView(center, zoom);
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      maxZoom: 19,
      attribution: "&copy; OpenStreetMap contributors",
    }).addTo(map);

    if (!window.__collecteAddressMaps) {
      window.__collecteAddressMaps = {};
    }
    window.__collecteAddressMaps[String(config.mapId || "")] = map;

    var marker = null;

    function setCoords(lat, lng) {
      if (!isFiniteNumber(lat) || !isFiniteNumber(lng)) {
        latInput.value = "";
        lngInput.value = "";
        updateCoordsLabel(coordsLabel, null, null);
        return;
      }
      latInput.value = String(lat.toFixed(7));
      lngInput.value = String(lng.toFixed(7));
      updateCoordsLabel(coordsLabel, lat, lng);
    }

    function setMarker(lat, lng, popupText) {
      if (!isFiniteNumber(lat) || !isFiniteNumber(lng)) return;
      if (!marker) {
        marker = L.marker([lat, lng], { draggable: true }).addTo(map);
        marker.on("dragend", function () {
          var pos = marker.getLatLng();
          setCoords(pos.lat, pos.lng);
          reverseGeocode(pos.lat, pos.lng).then(function (address) {
            if (address) {
              addressInput.value = address;
              marker.bindPopup(escapeHtml(address));
            }
          }).catch(function () {});
        });
      } else {
        marker.setLatLng([lat, lng]);
      }
      setCoords(lat, lng);
      map.setView([lat, lng], Math.max(map.getZoom(), 14));
      if (popupText) {
        marker.bindPopup(escapeHtml(popupText));
      }
    }

    function setCoordsError(message) {
      if (!coordsLabel) return;
      coordsLabel.textContent = String(message || "Coordonnees: non selectionnees");
    }

    var initialLat = toNumber(latInput.value);
    var initialLng = toNumber(lngInput.value);
    if (isFiniteNumber(initialLat) && isFiniteNumber(initialLng)) {
      setMarker(initialLat, initialLng, addressInput.value || "Adresse selectionnee");
    } else {
      updateCoordsLabel(coordsLabel, null, null);
    }

    map.on("click", function (event) {
      var lat = Number(event.latlng.lat);
      var lng = Number(event.latlng.lng);
      setMarker(lat, lng, "Adresse selectionnee");
      reverseGeocode(lat, lng).then(function (address) {
        if (address) {
          addressInput.value = address;
          if (marker) marker.bindPopup(escapeHtml(address));
        }
      }).catch(function () {});
    });

    if (addressInput) {
      addressInput.addEventListener("input", function () {
        if (marker) {
          marker.unbindPopup();
        }
        setCoords(null, null);
      });
    }

    if (searchBtn && searchInput) {
      searchBtn.addEventListener("click", function () {
        var text = String(searchInput.value || "").trim();
        if (!text) return;
        geocodeSearch(text).then(function (result) {
          if (!result) return;
          setMarker(result.lat, result.lng, result.displayName || text);
          if (result.displayName) {
            addressInput.value = result.displayName;
          }
        }).catch(function () {});
      });

      searchInput.addEventListener("keydown", function (event) {
        if (event.key === "Enter") {
          event.preventDefault();
          searchBtn.click();
        }
      });
    }

    if (currentLocationBtn && navigator.geolocation) {
      currentLocationBtn.addEventListener("click", function () {
        navigator.geolocation.getCurrentPosition(
          function (position) {
            var lat = Number(position.coords.latitude);
            var lng = Number(position.coords.longitude);
            if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
              setCoordsError("Coordonnees invalides depuis votre position.");
              return;
            }
            setMarker(lat, lng, "Ma position actuelle");
            reverseGeocode(lat, lng).then(function (address) {
              if (address) {
                addressInput.value = address;
                if (marker) marker.bindPopup(escapeHtml(address));
              }
            }).catch(function () {});
          },
          function () {
            setCoordsError("Impossible d'obtenir votre position actuelle.");
          },
          {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0,
          }
        );
      });
    }

    window.setTimeout(function () {
      if (map && typeof map.invalidateSize === "function") {
        map.invalidateSize();
      }
    }, 120);
  };
})();
