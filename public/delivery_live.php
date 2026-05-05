<?php
require_once __DIR__ . '/../Controller/PlanningCollecteController.php';

$controller = new PlanningCollecteController();
$token = trim((string)($_GET['token'] ?? ''));
$session = $controller->getDriverSessionByToken($token);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CareMeal - Tracking Livreur</title>
  <link rel="stylesheet" href="../css/main.css">
  <link rel="stylesheet" href="../assets/vendor/leaflet/leaflet.css">
  <style>
    body { margin: 0; background: #0d1b2a; color: #fff; font-family: Poppins, sans-serif; }
    .wrap { padding: 14px; display: grid; gap: 12px; }
    .card {
      background: #162233;
      border: 1px solid rgba(255,255,255,.08);
      border-radius: 12px;
      padding: 12px;
    }
    .title { margin: 0 0 8px; font-size: 1.1rem; }
    .meta { color: #9fb0c4; font-size: .9rem; margin: 4px 0; }
    #driver-map {
      width: 100%;
      height: 60vh;
      min-height: 360px;
      border-radius: 12px;
      border: 1px solid rgba(255,255,255,.1);
      overflow: hidden;
    }
    .status-pill {
      display: inline-block;
      border-radius: 999px;
      padding: 4px 10px;
      font-size: .82rem;
      background: rgba(249,115,22,.18);
      border: 1px solid rgba(249,115,22,.35);
      color: #ffd7c2;
    }
    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      border: 1px solid rgba(255,255,255,.14);
      border-radius: 10px;
      background: rgba(255,255,255,.04);
      color: #fff;
      padding: 10px 12px;
      cursor: pointer;
      width: 100%;
      font-weight: 600;
    }
    .btn:disabled { opacity: .6; cursor: not-allowed; }
  </style>
</head>
<body>
  <div class="wrap">
    <section class="card">
      <?php if (!$session): ?>
        <h1 class="title">Session de tracking introuvable</h1>
        <p class="meta">Lien invalide ou livraison non disponible.</p>
      <?php else: ?>
        <h1 class="title">Tracking Livreur - Collecte #<?= (int)$session['id_collecte'] ?></h1>
        <p class="meta">Livreur: <?= htmlspecialchars(trim((string)$session['delivery_driver_first_name'] . ' ' . (string)$session['delivery_driver_last_name']), ENT_QUOTES, 'UTF-8') ?></p>
        <p class="meta">Restaurant: <?= htmlspecialchars((string)($session['restaurant_nom'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?></p>
        <p class="meta">Destination: <?= htmlspecialchars((string)($session['restaurant_localisation'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
        <p class="meta">Statut: <span class="status-pill" id="driver-live-status">Initialisation...</span></p>
      <?php endif; ?>
    </section>

    <?php if ($session): ?>
      <section id="driver-map"></section>
      <button id="driver-start-btn" class="btn" type="button">Demarrer tracking GPS</button>
    <?php endif; ?>
  </div>

  <?php if ($session): ?>
    <script src="../assets/vendor/leaflet/leaflet.js"></script>
    <script>
      (function () {
        const token = <?= json_encode($token, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const trackerClientStorageKey = 'caremeal_tracker_client_id_v1';
        function getTrackerClientId() {
          try {
            var existing = window.localStorage.getItem(trackerClientStorageKey);
            if (existing && /^[a-z0-9_-]{8,80}$/.test(existing)) {
              return existing;
            }
            var generated = 'trk_' + Math.random().toString(36).slice(2, 10) + Date.now().toString(36).slice(-6);
            generated = generated.toLowerCase().replace(/[^a-z0-9_-]/g, '');
            window.localStorage.setItem(trackerClientStorageKey, generated);
            return generated;
          } catch (e) {
            return 'trk_fallback';
          }
        }
        const trackerClientId = getTrackerClientId();
        const statusEl = document.getElementById('driver-live-status');
        const startBtn = document.getElementById('driver-start-btn');
        const map = L.map('driver-map').setView([36.8065, 10.1815], 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          maxZoom: 19,
          attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        const driverIcon = L.icon({
          iconUrl: '../assets/map-markers/deliveryboy.png',
          iconSize: [34, 34],
          iconAnchor: [17, 34],
          popupAnchor: [0, -30]
        });

        let marker = null;
        let watchId = null;
        let lastSentAt = 0;
        let lastAcceptedAt = 0;
        let lastLat = null;
        let lastLng = null;
        const minSendIntervalMs = 700;
        const minDistanceMeters = 3.5;
        const poorAccuracyWarningMeters = 60;
        const maxAcceptedAccuracyMeters = 90;
        const maxSpeedMps = 40;

        function distanceMeters(lat1, lng1, lat2, lng2) {
          const toRad = function (v) { return v * Math.PI / 180; };
          const dLat = toRad(lat2 - lat1);
          const dLng = toRad(lng2 - lng1);
          const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
            Math.sin(dLng / 2) * Math.sin(dLng / 2);
          return 6371000 * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        }

        function setStatus(text) {
          if (statusEl) statusEl.textContent = text;
        }

        async function ping(lat, lng, accuracy) {
          const body = new URLSearchParams();
          body.set('tracking_token', token);
          body.set('lat', String(lat));
          body.set('lng', String(lng));
          body.set('accuracy', String(Math.max(0, Number(accuracy || 0))));
          body.set('tracker_client_id', trackerClientId);
          const response = await fetch('../Controller/planning_collecte.php?action=driver_ping', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
          });
          const data = await response.json();
          if (!data || !data.ok) {
            const status = (data && data.status) ? String(data.status) : 'ping_failed';
            throw new Error(status);
          }
          return data;
        }

        function humanizePingError(code) {
          const map = {
            'error_not_trackable_status': 'Collecte non trackable (statut non actif).',
            'error_not_delivery_mode': 'Tracking disponible seulement pour le mode livraison.',
            'error_not_found': 'Session tracking introuvable ou token obsolete.',
            'error_tracker_locked': 'Un autre appareil envoie deja le tracking pour cette collecte.',
            'error_validation': 'Coordonnees GPS invalides.',
            'error_invalid_request': 'Requete tracking invalide.',
            'error_db': 'Erreur serveur base de donnees.',
            'ignored_accuracy_jitter': 'Point GPS ignore (jitter de precision).',
            'ignored_large_jump': 'Point GPS ignore (grand saut non fiable).'
          };
          return map[code] || ('Erreur envoi position: ' + code);
        }

        async function onPosition(position) {
          const lat = position.coords.latitude;
          const lng = position.coords.longitude;
          const accuracy = Math.max(0, Number(position.coords.accuracy || 0));
          const speedFromDevice = Number(position.coords.speed || 0);

          const now = Date.now();
          if (accuracy > maxAcceptedAccuracyMeters) {
            setStatus('GPS faible (' + Math.round(accuracy) + 'm). En attente d\'un meilleur signal...');
            return;
          }

          const shouldSendByTime = (now - lastSentAt) >= minSendIntervalMs;
          const shouldSendByMove = (function () {
            if (lastLat === null || lastLng === null) return true;
            const dist = distanceMeters(lastLat, lastLng, lat, lng);
            return dist >= minDistanceMeters;
          })();

          if (!shouldSendByTime && !shouldSendByMove) {
            return;
          }

          if (lastLat !== null && lastLng !== null && lastAcceptedAt > 0) {
            const dist = distanceMeters(lastLat, lastLng, lat, lng);
            const dt = Math.max(0.25, (now - lastAcceptedAt) / 1000);
            const computedSpeed = dist / dt;
            const speedRef = speedFromDevice > 0 ? speedFromDevice : computedSpeed;
            if (speedRef > maxSpeedMps && accuracy > 35) {
              setStatus('Point GPS ignore (saut suspect).');
              return;
            }
          }

          try {
            const pingResult = await ping(lat, lng, accuracy);
            if (pingResult && pingResult.ignored) {
              setStatus('Point ignore (' + String(pingResult.status || 'ignored') + ').');
              return;
            }
            lastSentAt = now;
            lastAcceptedAt = now;
            lastLat = lat;
            lastLng = lng;

            if (!marker) {
              marker = L.marker([lat, lng], { icon: driverIcon }).addTo(map);
            } else {
              marker.setLatLng([lat, lng]);
            }

            map.setView([lat, lng], Math.max(map.getZoom(), 17));
            var accText = 'Live: ' + new Date().toLocaleTimeString() + ' | acc ' + Math.round(accuracy) + 'm';
            if (pingResult && pingResult.source === 'snapped_osrm') {
              accText += ' | route';
            }
            if (accuracy > poorAccuracyWarningMeters) {
              accText += ' (precision faible)';
            }
            setStatus(accText);
          } catch (e) {
            setStatus(humanizePingError(String(e && e.message ? e.message : 'ping_failed')));
          }
        }

        function onError(error) {
          setStatus('GPS erreur: ' + (error && error.message ? error.message : 'inconnue'));
        }

        function startTracking() {
          if (!navigator.geolocation) {
            setStatus('Geolocalisation non supportee sur ce navigateur.');
            return;
          }
          if (watchId !== null) {
            return;
          }
          setStatus('Demande permission GPS...');
          watchId = navigator.geolocation.watchPosition(onPosition, onError, {
            enableHighAccuracy: true,
            maximumAge: 0,
            timeout: 9000
          });
        }

        if (startBtn) {
          startBtn.addEventListener('click', function () {
            startTracking();
            startBtn.disabled = true;
            startBtn.textContent = 'Tracking actif';
          });
        }
      })();
    </script>
  <?php endif; ?>
</body>
</html>

