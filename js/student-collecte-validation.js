document.addEventListener('DOMContentLoaded', function () {
  var form = document.getElementById('student-collecte-form');
  if (!form) return;

  var userField = form.querySelector('input[name="id_user"]');
  var prefField = form.querySelector('input[name="id_pref"]');
  var restaurantField = form.querySelector('input[name="id_restaurant"]');
  var modeField = document.getElementById('student_collecte_mode');
  var addressGroup = document.getElementById('student_collecte_address_group');
  var addressField = document.getElementById('student_collecte_adresse');
  var addressLatField = document.getElementById('student_collecte_adresse_lat');
  var addressLngField = document.getElementById('student_collecte_adresse_lng');
  var timeField = document.getElementById('student_collecte_heure');
  var itemsField = document.getElementById('student_collecte_items_json');
  var prefAllergiesRaw = String(window.STUDENT_COLLECTE_PREF_ALLERGIES || '');
  var selectedMealsMeta = Array.isArray(window.STUDENT_COLLECTE_SELECTED_MEALS) ? window.STUDENT_COLLECTE_SELECTED_MEALS : [];

  var openTime = String(form.getAttribute('data-open-time') || '').trim();
  var closeTime = String(form.getAttribute('data-close-time') || '').trim();

  function setError(errorId, field, message) {
    var errorEl = document.getElementById(errorId);
    if (errorEl) {
      errorEl.textContent = message;
      errorEl.style.display = message ? 'block' : 'none';
    }
    if (field) {
      if (message) field.classList.add('input-error');
      else field.classList.remove('input-error');
    }
  }

  function isPositiveInt(value) {
    var text = String(value || '').trim();
    if (!/^\d+$/.test(text)) return false;
    return parseInt(text, 10) > 0;
  }

  function toMinutes(hhmm) {
    var m = String(hhmm || '').match(/^(\d{2}):(\d{2})$/);
    if (!m) return null;
    var h = parseInt(m[1], 10);
    var min = parseInt(m[2], 10);
    if (h < 0 || h > 23 || min < 0 || min > 59) return null;
    return (h * 60) + min;
  }

  function normalizeToken(value) {
    var token = String(value || '').trim().toLowerCase();
    token = token.replace(/[_\s]+/g, '-').replace(/-+/g, '-').replace(/[^a-z0-9-]/g, '');
    var map = {
      'arachides': 'arachide',
      'cacahuete': 'arachide',
      'cacahuetes': 'arachide',
      'peanut': 'arachide',
      'peanuts': 'arachide',
      'milk': 'lactose',
      'lait': 'lactose',
      'fromage': 'lactose',
      'wheat': 'gluten',
      'ble': 'gluten',
      'soy': 'soja',
      'egg': 'oeuf',
      'eggs': 'oeuf',
      'fish': 'poisson',
      'nuts': 'fruits-a-coque'
    };
    return map[token] || token;
  }

  function parseTokenList(value) {
    return String(value || '')
      .split(/[,;]+/)
      .map(function (part) { return normalizeToken(part); })
      .filter(function (part) { return part.length > 0; })
      .filter(function (part, index, arr) { return arr.indexOf(part) === index; });
  }

  function findDeclaredMealConflicts() {
    var allergies = parseTokenList(prefAllergiesRaw);
    if (allergies.length === 0) return [];
    var conflicts = [];
    selectedMealsMeta.forEach(function (meal) {
      var mealAllergens = parseTokenList(meal && meal.allergens ? meal.allergens : '');
      mealAllergens.forEach(function (token) {
        if (allergies.indexOf(token) !== -1) {
          conflicts.push({
            meal_name: String((meal && meal.meal_name) || (meal && meal.meal_id) || ''),
            allergen: token
          });
        }
      });
    });
    return conflicts;
  }

  function syncAddressVisibility() {
    var mode = modeField ? modeField.value : 'pickup';
    if (!addressGroup || !addressField) return;
    if (mode === 'delivery') {
      addressGroup.style.display = '';
      addressField.disabled = false;
      window.setTimeout(function () {
        var maps = window.__collecteAddressMaps || {};
        var map = maps.student_collecte_address_map;
        if (map && typeof map.invalidateSize === 'function') {
          map.invalidateSize();
        }
      }, 120);
    } else {
      addressGroup.style.display = 'none';
      addressField.disabled = true;
      addressField.value = '';
      if (addressLatField) addressLatField.value = '';
      if (addressLngField) addressLngField.value = '';
      setError('student_collecte_adresse-error', addressField, '');
    }
  }

  if (modeField) {
    modeField.addEventListener('change', syncAddressVisibility);
  }
  syncAddressVisibility();

  form.addEventListener('submit', function (event) {
    var valid = true;

    setError('student_collecte_mode-error', modeField, '');
    setError('student_collecte_adresse-error', addressField, '');
    setError('student_collecte_heure-error', timeField, '');

    if (!isPositiveInt(userField ? userField.value : '')) valid = false;
    if (!isPositiveInt(prefField ? prefField.value : '')) valid = false;
    if (!isPositiveInt(restaurantField ? restaurantField.value : '')) valid = false;

    var mode = modeField ? String(modeField.value || '').trim() : '';
    if (mode !== 'pickup' && mode !== 'delivery') {
      setError('student_collecte_mode-error', modeField, 'Mode invalide.');
      valid = false;
    }

    var address = addressField ? String(addressField.value || '').trim() : '';
    var lat = addressLatField ? Number(addressLatField.value) : NaN;
    var lng = addressLngField ? Number(addressLngField.value) : NaN;
    if (mode === 'delivery' && (address.length < 5 || address.length > 255)) {
      setError('student_collecte_adresse-error', addressField, 'Adresse obligatoire (5-255).');
      valid = false;
    }
    if (mode === 'delivery') {
      var coordsValid = Number.isFinite(lat) && Number.isFinite(lng)
        && lat >= -90 && lat <= 90
        && lng >= -180 && lng <= 180;
      if (!coordsValid) {
        setError('student_collecte_adresse-error', addressField, 'Choisis une adresse exacte depuis la carte.');
        valid = false;
      }
    }

    var time = timeField ? String(timeField.value || '').trim() : '';
    if (!/^\d{2}:\d{2}$/.test(time)) {
      setError('student_collecte_heure-error', timeField, 'Heure invalide.');
      valid = false;
    } else {
      var requested = toMinutes(time);
      var open = toMinutes(openTime);
      var close = toMinutes(closeTime);
      if (requested === null) {
        setError('student_collecte_heure-error', timeField, 'Heure invalide.');
        valid = false;
      } else if (open !== null && close !== null && (requested < open || requested > close || open >= close)) {
        setError('student_collecte_heure-error', timeField, 'Heure hors horaires du restaurant.');
        valid = false;
      }
    }

    var items = [];
    try {
      items = JSON.parse(itemsField ? itemsField.value : '[]');
    } catch (e) {
      items = [];
    }
    if (!Array.isArray(items) || items.length === 0) {
      setError('student_collecte_mode-error', modeField, 'Aucun item selectionne.');
      valid = false;
    }
    var declaredConflicts = findDeclaredMealConflicts();
    if (declaredConflicts.length > 0) {
      var first = declaredConflicts[0];
      setError(
        'student_collecte_mode-error',
        modeField,
        'Risque allergene detecte: meal "' + first.meal_name + '" (allergene: ' + first.allergen + '). Le serveur appliquera un blocage strict.'
      );
      valid = false;
    }

    if (!valid) {
      event.preventDefault();
    }
  });
});
